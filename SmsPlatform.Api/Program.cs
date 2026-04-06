using System.Text;
using Hangfire;
using Hangfire.MemoryStorage;
using Microsoft.AspNetCore.Authentication.JwtBearer;
using Microsoft.EntityFrameworkCore;
using Microsoft.IdentityModel.Tokens;
using Microsoft.OpenApi.Models;
using Serilog;
using SmsPlatform.Application.Interfaces;
using SmsPlatform.Application.Services;
using SmsPlatform.Domain.Interfaces;
using SmsPlatform.Infrastructure.Caching;
using SmsPlatform.Infrastructure.Data;
using SmsPlatform.Infrastructure.Messaging;
using SmsPlatform.Infrastructure.Repositories;
using SmsPlatform.Infrastructure.Smpp;

Log.Logger = new LoggerConfiguration()
    .WriteTo.Console()
    .WriteTo.File("logs/sms-platform-.txt", rollingInterval: RollingInterval.Day)
    .Enrich.FromLogContext()
    .CreateLogger();

try
{
    Log.Information("Starting SMS Platform API");
    
    var builder = WebApplication.CreateBuilder(args);
    
    builder.Host.UseSerilog();
    
    var connectionString = builder.Configuration.GetConnectionString("DefaultConnection")
        ?? "Server=localhost;Database=sms_platform;User=root;Password=password;";
    
    builder.Services.AddDbContext<SmsDbContext>(options =>
        options.UseMySql(connectionString, ServerVersion.AutoDetect(connectionString)));
    
    var jwtKey = builder.Configuration["Jwt:Key"] ?? "YourSuperSecretKeyForJwtTokenGeneration2024!";
    var jwtIssuer = builder.Configuration["Jwt:Issuer"] ?? "SmsPlatform";
    var jwtAudience = builder.Configuration["Jwt:Audience"] ?? "SmsPlatformUsers";
    
    builder.Services.AddAuthentication(options =>
    {
        options.DefaultAuthenticateScheme = JwtBearerDefaults.AuthenticationScheme;
        options.DefaultChallengeScheme = JwtBearerDefaults.AuthenticationScheme;
    })
    .AddJwtBearer(options =>
    {
        options.TokenValidationParameters = new TokenValidationParameters
        {
            ValidateIssuer = true,
            ValidateAudience = true,
            ValidateLifetime = true,
            ValidateIssuerSigningKey = true,
            ValidIssuer = jwtIssuer,
            ValidAudience = jwtAudience,
            IssuerSigningKey = new SymmetricSecurityKey(Encoding.UTF8.GetBytes(jwtKey))
        };
    });
    
    builder.Services.AddAuthorization();
    
    builder.Services.AddSingleton<ISmppClientManager, SmppClientManager>();
    builder.Services.AddSingleton<IRedisService, RedisService>();
    builder.Services.AddSingleton<IQueueService, RabbitMqService>();
    
    builder.Services.AddHangfire(config =>
    {
        config.UseMemoryStorage();
    });
    builder.Services.AddHangfireServer();
    
    builder.Services.AddScoped<ISmsService, SmsService>();
    builder.Services.AddScoped<IUserService, UserService>();
    builder.Services.AddScoped<IChannelService, ChannelService>();
    builder.Services.AddScoped<IPricingService, PricingService>();
    builder.Services.AddScoped<IFinanceService, FinanceService>();
    builder.Services.AddScoped<ISenderIdService, SenderIdService>();
    builder.Services.AddScoped<IAlertService, AlertService>();
    
    builder.Services.AddScoped<ISmsRepository, SmsRepository>();
    builder.Services.AddScoped<IUserRepository, UserRepository>();
    builder.Services.AddScoped<ISmppChannelRepository, SmppChannelRepository>();
    builder.Services.AddScoped<ICountryRepository, CountryRepository>();
    builder.Services.AddScoped<ICountryPricingRepository, CountryPricingRepository>();
    builder.Services.AddScoped<IUserCountryPermissionRepository, UserCountryPermissionRepository>();
    builder.Services.AddScoped<IApiKeyRepository, ApiKeyRepository>();
    builder.Services.AddScoped<IRechargeRecordRepository, RechargeRecordRepository>();
    builder.Services.AddScoped<IFeeLogRepository, FeeLogRepository>();
    builder.Services.AddScoped<ISenderIdRepository, SenderIdRepository>();
    builder.Services.AddScoped<ISystemSettingRepository, SystemSettingRepository>();
    builder.Services.AddScoped<IAlertRepository, AlertRepository>();
    builder.Services.AddScoped<IAlertPolicyRepository, AlertPolicyRepository>();
    
    builder.Services.AddControllers();
    
    builder.Services.AddEndpointsApiExplorer();
    builder.Services.AddSwaggerGen(c =>
    {
        c.SwaggerDoc("v1", new OpenApiInfo
        {
            Title = "SMS Platform API",
            Version = "v1",
            Description = "Enterprise SMS Platform with SMPP support"
        });
        
        c.AddSecurityDefinition("Bearer", new OpenApiSecurityScheme
        {
            Description = "JWT Authorization header using the Bearer scheme",
            Name = "Authorization",
            In = ParameterLocation.Header,
            Type = SecuritySchemeType.ApiKey,
            Scheme = "Bearer"
        });
        
        c.AddSecurityRequirement(new OpenApiSecurityRequirement
        {
            {
                new OpenApiSecurityScheme
                {
                    Reference = new OpenApiReference
                    {
                        Type = ReferenceType.SecurityScheme,
                        Id = "Bearer"
                    }
                },
                Array.Empty<string>()
            }
        });
    });
    
    var app = builder.Build();
    
    using (var scope = app.Services.CreateScope())
    {
        var dbContext = scope.ServiceProvider.GetRequiredService<SmsDbContext>();
        try
        {
            dbContext.Database.EnsureCreated();
            Log.Information("Database initialized successfully");
        }
        catch (Exception ex)
        {
            Log.Warning(ex, "Database initialization failed");
        }
    }
    
    if (app.Environment.IsDevelopment())
    {
        app.UseSwagger();
        app.UseSwaggerUI();
    }
    
    app.UseSerilogRequestLogging();
    
    app.UseHangfireDashboard();
    
    app.UseAuthentication();
    app.UseAuthorization();
    
    app.MapControllers();
    
    app.UseSmppChannelMonitor();
    app.UseAlertChecker();
    
    Log.Information("SMS Platform API started successfully");
    
    app.Run();
}
catch (Exception ex)
{
    Log.Fatal(ex, "Application terminated unexpectedly");
}
finally
{
    Log.CloseAndFlush();
}

public static class ApplicationBuilderExtensions
{
    public static IApplicationBuilder UseSmppChannelMonitor(this IApplicationBuilder app)
    {
        var timer = new Timer(async _ =>
        {
            using var scope = app.ApplicationServices.CreateScope();
            var channelService = scope.ServiceProvider.GetRequiredService<IChannelService>();
            var smppManager = scope.ServiceProvider.GetRequiredService<ISmppClientManager>();
            
            try
            {
                var channels = await channelService.GetActiveChannelsAsync();
                foreach (var channel in channels)
                {
                    if (!channel.IsOnline)
                    {
                        await smppManager.ConnectAsync(new SmsPlatform.Domain.Entities.SmppChannel
                        {
                            Id = channel.Id,
                            Name = channel.Name
                        });
                    }
                }
            }
            catch (Exception ex)
            {
                Log.Error(ex, "Error in SMPP channel monitor");
            }
        }, null, TimeSpan.Zero, TimeSpan.FromSeconds(30));
        
        return app;
    }
    
    public static IApplicationBuilder UseAlertChecker(this IApplicationBuilder app)
    {
        var timer = new Timer(async _ =>
        {
            using var scope = app.ApplicationServices.CreateScope();
            var alertService = scope.ServiceProvider.GetRequiredService<IAlertService>();
            
            try
            {
                await alertService.CheckAndCreateAlertsAsync();
            }
            catch (Exception ex)
            {
                Log.Error(ex, "Error in alert checker");
            }
        }, null, TimeSpan.Zero, TimeSpan.FromMinutes(1));
        
        return app;
    }
}
