using System.Text.Json;
using Microsoft.Extensions.Logging;
using StackExchange.Redis;
using SmsPlatform.Domain.Interfaces;

namespace SmsPlatform.Infrastructure.Caching;

public class RedisService : IRedisService, IDisposable
{
    private readonly ConnectionMultiplexer? _redis;
    private readonly IDatabase? _db;
    private readonly ILogger<RedisService> _logger;
    private readonly bool _isConnected;
    
    public RedisService(ILogger<RedisService> logger, string? connectionString = null)
    {
        _logger = logger;
        
        try
        {
            var options = ConfigurationOptions.Parse(connectionString ?? "localhost");
            options.AbortOnConnectFail = false;
            options.ConnectTimeout = 5000;
            
            _redis = ConnectionMultiplexer.Connect(options);
            _db = _redis.GetDatabase();
            _isConnected = _redis.IsConnected;
            
            _logger.LogInformation("Redis connection {Status}", _isConnected ? "established" : "not connected");
        }
        catch (Exception ex)
        {
            _logger.LogWarning(ex, "Failed to connect to Redis. Caching will be disabled");
            _isConnected = false;
        }
    }
    
    public async Task<T?> GetAsync<T>(string key) where T : class
    {
        if (!_isConnected || _db == null) return null;
        
        try
        {
            var value = await _db.StringGetAsync(key);
            if (value.IsNullOrEmpty)
                return null;
            
            return JsonSerializer.Deserialize<T>(value!);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error getting value for key {Key}", key);
            return null;
        }
    }
    
    public async Task SetAsync<T>(string key, T value, TimeSpan? expiry = null) where T : class
    {
        if (!_isConnected || _db == null) return;
        
        try
        {
            var json = JsonSerializer.Serialize(value);
            await _db.StringSetAsync(key, json, expiry);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error setting value for key {Key}", key);
        }
    }
    
    public async Task<bool> DeleteAsync(string key)
    {
        if (!_isConnected || _db == null) return false;
        
        try
        {
            return await _db.KeyDeleteAsync(key);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error deleting key {Key}", key);
            return false;
        }
    }
    
    public async Task<bool> ExistsAsync(string key)
    {
        if (!_isConnected || _db == null) return false;
        
        try
        {
            return await _db.KeyExistsAsync(key);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error checking existence of key {Key}", key);
            return false;
        }
    }
    
    public async Task<bool> SetIfNotExistsAsync(string key, string value, TimeSpan? expiry = null)
    {
        if (!_isConnected || _db == null) return false;
        
        try
        {
            return await _db.StringSetAsync(key, value, expiry, When.NotExists);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error setting value for key {Key} if not exists", key);
            return false;
        }
    }
    
    public async Task<long> IncrementAsync(string key)
    {
        if (!_isConnected || _db == null) return 0;
        
        try
        {
            return await _db.StringIncrementAsync(key);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error incrementing key {Key}", key);
            return 0;
        }
    }
    
    public async Task<long> DecrementAsync(string key)
    {
        if (!_isConnected || _db == null) return 0;
        
        try
        {
            return await _db.StringDecrementAsync(key);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error decrementing key {Key}", key);
            return 0;
        }
    }
    
    public async Task<bool> TryAcquireLockAsync(string key, TimeSpan expiry)
    {
        if (!_isConnected || _db == null) return false;
        
        try
        {
            return await _db.StringSetAsync($"lock:{key}", Environment.MachineName, expiry, When.NotExists);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error acquiring lock for key {Key}", key);
            return false;
        }
    }
    
    public async Task ReleaseLockAsync(string key)
    {
        if (!_isConnected || _db == null) return;
        
        try
        {
            await _db.KeyDeleteAsync($"lock:{key}");
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error releasing lock for key {Key}", key);
        }
    }
    
    public async Task<bool> CheckUserCountryPermissionAsync(Guid userId, string countryCode)
    {
        var key = $"user:{userId}:country:{countryCode}";
        var cached = await GetAsync<PermissionCache>(key);
        if (cached != null)
            return cached.Allowed;
        
        return false;
    }
    
    public async Task SetUserCountryPermissionAsync(Guid userId, string countryCode, bool allowed)
    {
        var key = $"user:{userId}:country:{countryCode}";
        await SetAsync(key, new PermissionCache { Allowed = allowed }, TimeSpan.FromMinutes(30));
    }
    
    public async Task<decimal?> GetUserBalanceAsync(Guid userId)
    {
        var key = $"user:{userId}:balance";
        var cached = await GetAsync<BalanceCache>(key);
        return cached?.Balance;
    }
    
    public async Task SetUserBalanceCachedAsync(Guid userId, decimal balance)
    {
        var key = $"user:{userId}:balance";
        await SetAsync(key, new BalanceCache { Balance = balance }, TimeSpan.FromMinutes(10));
    }
    
    public async Task<int> GetChannelCurrentTpsAsync(Guid channelId)
    {
        var key = $"channel:{channelId}:tps";
        var value = await _db?.StringGetAsync(key);
        return value.HasValue ? (int)value : 0;
    }
    
    public async Task IncrementChannelTpsAsync(Guid channelId)
    {
        var key = $"channel:{channelId}:tps";
        await IncrementAsync(key);
        
        var expiryKey = $"channel:{channelId}:tps_expiry";
        var exists = await ExistsAsync(expiryKey);
        if (!exists)
        {
            await SetAsync(expiryKey, new object(), TimeSpan.FromSeconds(1));
        }
    }
    
    public async Task ResetChannelTpsAsync(Guid channelId)
    {
        var key = $"channel:{channelId}:tps";
        await _db?.KeyDeleteAsync(key);
    }
    
    public async Task<decimal?> GetCountryPriceAsync(Guid countryId, Guid? userId)
    {
        var key = userId.HasValue 
            ? $"pricing:user:{userId}:country:{countryId}" 
            : $"pricing:country:{countryId}";
        
        var cached = await GetAsync<PriceCache>(key);
        return cached?.Price;
    }
    
    public void Dispose()
    {
        _redis?.Dispose();
    }
}

internal class PermissionCache
{
    public bool Allowed { get; set; }
}

internal class BalanceCache
{
    public decimal Balance { get; set; }
}

internal class PriceCache
{
    public decimal Price { get; set; }
}
