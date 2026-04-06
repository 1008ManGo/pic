using System.IdentityModel.Tokens.Jwt;
using System.Security.Claims;
using System.Security.Cryptography;
using System.Text;
using Microsoft.Extensions.Configuration;
using Microsoft.Extensions.Logging;
using Microsoft.IdentityModel.Tokens;
using SmsPlatform.Application.DTOs;
using SmsPlatform.Application.Interfaces;
using SmsPlatform.Domain.Entities;
using SmsPlatform.Domain.Enums;
using SmsPlatform.Domain.Interfaces;

namespace SmsPlatform.Application.Services;

public class UserService : IUserService
{
    private readonly IUserRepository _userRepository;
    private readonly IApiKeyRepository _apiKeyRepository;
    private readonly IRedisService _redisService;
    private readonly IConfiguration _configuration;
    private readonly ILogger<UserService> _logger;
    
    public UserService(
        IUserRepository userRepository,
        IApiKeyRepository apiKeyRepository,
        IRedisService redisService,
        IConfiguration configuration,
        ILogger<UserService> logger)
    {
        _userRepository = userRepository;
        _apiKeyRepository = apiKeyRepository;
        _redisService = redisService;
        _configuration = configuration;
        _logger = logger;
    }
    
    public async Task<UserResponse> CreateUserAsync(UserCreateRequest request)
    {
        var existingUser = await _userRepository.GetByUsernameAsync(request.Username);
        if (existingUser != null)
            throw new InvalidOperationException("Username already exists");
        
        var existingEmail = await _userRepository.GetByEmailAsync(request.Email);
        if (existingEmail != null)
            throw new InvalidOperationException("Email already exists");
        
        var user = new User
        {
            Username = request.Username,
            PasswordHash = HashPassword(request.Password),
            Email = request.Email,
            CompanyName = request.CompanyName,
            Balance = request.InitialBalance,
            Status = UserStatus.Active
        };
        
        await _userRepository.CreateAsync(user);
        
        return MapToUserResponse(user);
    }
    
    public async Task<UserResponse?> GetUserByIdAsync(Guid id)
    {
        var user = await _userRepository.GetByIdAsync(id);
        return user != null ? MapToUserResponse(user) : null;
    }
    
    public async Task<UserResponse?> GetUserByUsernameAsync(string username)
    {
        var user = await _userRepository.GetByUsernameAsync(username);
        return user != null ? MapToUserResponse(user) : null;
    }
    
    public async Task<IEnumerable<UserResponse>> GetAllUsersAsync(int page = 1, int pageSize = 20)
    {
        var users = await _userRepository.GetAllAsync(page, pageSize);
        return users.Select(MapToUserResponse);
    }
    
    public async Task<UserResponse> UpdateUserAsync(UserUpdateRequest request)
    {
        var user = await _userRepository.GetByIdAsync(request.Id);
        if (user == null)
            throw new InvalidOperationException("User not found");
        
        user.Email = request.Email;
        user.CompanyName = request.CompanyName;
        user.Status = request.Status;
        user.AllowCustomSenderId = request.AllowCustomSenderId;
        user.UpdatedAt = DateTime.UtcNow;
        
        await _userRepository.UpdateAsync(user);
        
        return MapToUserResponse(user);
    }
    
    public async Task<LoginResponse> LoginAsync(LoginRequest request)
    {
        var user = await _userRepository.GetByUsernameAsync(request.Username);
        if (user == null)
            throw new UnauthorizedAccessException("Invalid username or password");
        
        if (!VerifyPassword(request.Password, user.PasswordHash))
            throw new UnauthorizedAccessException("Invalid username or password");
        
        if (user.Status != UserStatus.Active)
            throw new UnauthorizedAccessException("User account is not active");
        
        var token = GenerateJwtToken(user);
        
        return new LoginResponse(
            token,
            "Bearer",
            3600,
            MapToUserResponse(user)
        );
    }
    
    public async Task<ApiKeyResponse> CreateApiKeyAsync(Guid userId, string name)
    {
        var apiKey = new ApiKey
        {
            UserId = userId,
            Name = name,
            Key = GenerateApiKey(),
            IsActive = true
        };
        
        await _apiKeyRepository.CreateAsync(apiKey);
        
        return new ApiKeyResponse(
            apiKey.Id,
            apiKey.Name,
            apiKey.Key,
            apiKey.IsActive,
            apiKey.CreatedAt,
            apiKey.ExpiresAt,
            apiKey.LastUsedAt
        );
    }
    
    public async Task<IEnumerable<ApiKeyResponse>> GetUserApiKeysAsync(Guid userId)
    {
        var apiKeys = await _apiKeyRepository.GetByUserIdAsync(userId);
        return apiKeys.Select(k => new ApiKeyResponse(
            k.Id,
            k.Name,
            k.Key,
            k.IsActive,
            k.CreatedAt,
            k.ExpiresAt,
            k.LastUsedAt
        ));
    }
    
    public async Task RevokeApiKeyAsync(Guid apiKeyId)
    {
        var apiKey = await _apiKeyRepository.GetByIdAsync(apiKeyId);
        if (apiKey != null)
        {
            apiKey.IsActive = false;
            await _apiKeyRepository.UpdateAsync(apiKey);
        }
    }
    
    public async Task<decimal> GetUserBalanceAsync(Guid userId)
    {
        var cachedBalance = await _redisService.GetUserBalanceAsync(userId);
        if (cachedBalance.HasValue)
            return cachedBalance.Value;
        
        var user = await _userRepository.GetByIdAsync(userId);
        if (user == null)
            return 0;
        
        await _redisService.SetUserBalanceCachedAsync(userId, user.Balance);
        return user.Balance;
    }
    
    private string GenerateJwtToken(User user)
    {
        var key = _configuration["Jwt:Key"] ?? "YourSuperSecretKeyForJwtTokenGeneration2024!";
        var issuer = _configuration["Jwt:Issuer"] ?? "SmsPlatform";
        var audience = _configuration["Jwt:Audience"] ?? "SmsPlatformUsers";
        
        var securityKey = new SymmetricSecurityKey(Encoding.UTF8.GetBytes(key));
        var credentials = new SigningCredentials(securityKey, SecurityAlgorithms.HmacSha256);
        
        var claims = new[]
        {
            new Claim(JwtRegisteredClaimNames.Sub, user.Id.ToString()),
            new Claim(JwtRegisteredClaimNames.Email, user.Email),
            new Claim("username", user.Username),
            new Claim(ClaimTypes.Role, "User"),
            new Claim(JwtRegisteredClaimNames.Jti, Guid.NewGuid().ToString())
        };
        
        var token = new JwtSecurityToken(
            issuer: issuer,
            audience: audience,
            claims: claims,
            expires: DateTime.UtcNow.AddHours(1),
            signingCredentials: credentials
        );
        
        return new JwtSecurityTokenHandler().WriteToken(token);
    }
    
    private static string GenerateApiKey()
    {
        var key = new byte[32];
        using var rng = RandomNumberGenerator.Create();
        rng.GetBytes(key);
        return Convert.ToBase64String(key).Replace("+", "").Replace("/", "").Replace("=", "");
    }
    
    private static string HashPassword(string password)
    {
        using var sha256 = SHA256.Create();
        var hashedBytes = sha256.ComputeHash(Encoding.UTF8.GetBytes(password));
        return Convert.ToBase64String(hashedBytes);
    }
    
    private static bool VerifyPassword(string password, string hash)
    {
        return HashPassword(password) == hash;
    }
    
    private static UserResponse MapToUserResponse(User user)
    {
        return new UserResponse(
            user.Id,
            user.Username,
            user.Email,
            user.CompanyName,
            user.Status,
            user.Balance,
            user.AllowCustomSenderId,
            user.CreatedAt
        );
    }
}
