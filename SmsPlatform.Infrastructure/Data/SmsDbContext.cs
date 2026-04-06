using Microsoft.EntityFrameworkCore;
using SmsPlatform.Domain.Entities;

namespace SmsPlatform.Infrastructure.Data;

public class SmsDbContext : DbContext
{
    public SmsDbContext(DbContextOptions<SmsDbContext> options) : base(options)
    {
    }
    
    public DbSet<SmsMessage> SmsMessages => Set<SmsMessage>();
    public DbSet<SmsSegment> SmsSegments => Set<SmsSegment>();
    public DbSet<SmppChannel> SmppChannels => Set<SmppChannel>();
    public DbSet<User> Users => Set<User>();
    public DbSet<ApiKey> ApiKeys => Set<ApiKey>();
    public DbSet<Country> Countries => Set<Country>();
    public DbSet<CountryPricing> CountryPricings => Set<CountryPricing>();
    public DbSet<UserCountryPermission> UserCountryPermissions => Set<UserCountryPermission>();
    public DbSet<SenderId> SenderIds => Set<SenderId>();
    public DbSet<RechargeRecord> RechargeRecords => Set<RechargeRecord>();
    public DbSet<FeeLog> FeeLogs => Set<FeeLog>();
    public DbSet<SystemSetting> SystemSettings => Set<SystemSetting>();
    public DbSet<AlertPolicy> AlertPolicies => Set<AlertPolicy>();
    public DbSet<Alert> Alerts => Set<Alert>();
    public DbSet<ApiCallLog> ApiCallLogs => Set<ApiCallLog>();
    public DbSet<BatchSmsJob> BatchSmsJobs => Set<BatchSmsJob>();
    
    protected override void OnModelCreating(ModelBuilder modelBuilder)
    {
        base.OnModelCreating(modelBuilder);
        
        modelBuilder.Entity<User>(entity =>
        {
            entity.HasIndex(e => e.Username).IsUnique();
            entity.HasIndex(e => e.Email).IsUnique();
        });
        
        modelBuilder.Entity<ApiKey>(entity =>
        {
            entity.HasIndex(e => e.Key).IsUnique();
        });
        
        modelBuilder.Entity<Country>(entity =>
        {
            entity.HasIndex(e => e.Code).IsUnique();
        });
        
        modelBuilder.Entity<SmsMessage>(entity =>
        {
            entity.HasIndex(e => e.ExternalId);
            entity.HasIndex(e => e.Status);
            entity.HasIndex(e => e.CreatedAt);
        });
        
        modelBuilder.Entity<SmsSegment>(entity =>
        {
            entity.HasIndex(e => e.ProviderMessageId);
        });
        
        modelBuilder.Entity<SmppChannel>(entity =>
        {
            entity.HasIndex(e => e.Name).IsUnique();
        });
        
        modelBuilder.Entity<SystemSetting>(entity =>
        {
            entity.HasIndex(e => e.Key).IsUnique();
        });
        
        SeedData(modelBuilder);
    }
    
    private void SeedData(ModelBuilder modelBuilder)
    {
        var countries = new[]
        {
            new Country { Id = Guid.Parse("11111111-1111-1111-1111-111111111111"), Code = "CN", Name = "China", DialCode = "+86", MinLength = 11, MaxLength = 11 },
            new Country { Id = Guid.Parse("22222222-2222-2222-2222-222222222222"), Code = "US", Name = "United States", DialCode = "+1", MinLength = 10, MaxLength = 10 },
            new Country { Id = Guid.Parse("33333333-3333-3333-3333-333333333333"), Code = "GB", Name = "United Kingdom", DialCode = "+44", MinLength = 10, MaxLength = 10 },
            new Country { Id = Guid.Parse("44444444-4444-4444-4444-444444444444"), Code = "IN", Name = "India", DialCode = "+91", MinLength = 10, MaxLength = 10 },
            new Country { Id = Guid.Parse("55555555-5555-5555-5555-555555555555"), Code = "JP", Name = "Japan", DialCode = "+81", MinLength = 10, MaxLength = 11 },
        };
        
        modelBuilder.Entity<Country>().HasData(countries);
        
        var defaultPricing = countries.Select(c => new CountryPricing
        {
            Id = Guid.NewGuid(),
            CountryId = c.Id,
            PricePerSms = 0.01m,
            SegmentSize = 160,
            LongMessageSegmentSize = 153
        }).ToList();
        
        modelBuilder.Entity<CountryPricing>().HasData(defaultPricing);
        
        modelBuilder.Entity<SystemSetting>().HasData(
            new SystemSetting { Id = Guid.NewGuid(), Key = "SiteName", Value = "SMS Platform", Description = "Site name" },
            new SystemSetting { Id = Guid.NewGuid(), Key = "DefaultSenderId", Value = "SMS", Description = "Default sender ID" },
            new SystemSetting { Id = Guid.NewGuid(), Key = "MaxBatchSize", Value = "1000000", Description = "Maximum batch size for file upload" },
            new SystemSetting { Id = Guid.NewGuid(), Key = "RandomCharLength", Value = "5", Description = "Length of random characters appended to messages" }
        );
    }
}
