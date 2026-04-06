using Microsoft.EntityFrameworkCore;
using SmsPlatform.Domain.Entities;
using SmsPlatform.Domain.Enums;
using SmsPlatform.Domain.Interfaces;
using SmsPlatform.Infrastructure.Data;

namespace SmsPlatform.Infrastructure.Repositories;

public class SmsRepository : ISmsRepository
{
    private readonly SmsDbContext _context;
    
    public SmsRepository(SmsDbContext context)
    {
        _context = context;
    }
    
    public async Task<SmsMessage?> GetByIdAsync(Guid id)
    {
        return await _context.SmsMessages
            .Include(s => s.Segments)
            .Include(s => s.Country)
            .Include(s => s.SmppChannel)
            .FirstOrDefaultAsync(s => s.Id == id);
    }
    
    public async Task<IEnumerable<SmsMessage>> GetByUserIdAsync(Guid userId, int page = 1, int pageSize = 20)
    {
        return await _context.SmsMessages
            .Where(s => s.UserId == userId)
            .OrderByDescending(s => s.CreatedAt)
            .Skip((page - 1) * pageSize)
            .Take(pageSize)
            .ToListAsync();
    }
    
    public async Task<IEnumerable<SmsMessage>> GetByStatusAsync(SmsMessageStatus status, int page = 1, int pageSize = 100)
    {
        return await _context.SmsMessages
            .Where(s => s.Status == status)
            .OrderBy(s => s.CreatedAt)
            .Skip((page - 1) * pageSize)
            .Take(pageSize)
            .ToListAsync();
    }
    
    public async Task<SmsMessage> CreateAsync(SmsMessage message)
    {
        _context.SmsMessages.Add(message);
        await _context.SaveChangesAsync();
        return message;
    }
    
    public async Task UpdateAsync(SmsMessage message)
    {
        _context.SmsMessages.Update(message);
        await _context.SaveChangesAsync();
    }
    
    public async Task<IEnumerable<SmsMessage>> GetPendingMessagesAsync(int limit = 100)
    {
        return await _context.SmsMessages
            .Where(s => s.Status == SmsMessageStatus.Pending || s.Status == SmsMessageStatus.Queued)
            .OrderBy(s => s.Priority)
            .ThenBy(s => s.CreatedAt)
            .Take(limit)
            .ToListAsync();
    }
}

public class UserRepository : IUserRepository
{
    private readonly SmsDbContext _context;
    
    public UserRepository(SmsDbContext context)
    {
        _context = context;
    }
    
    public async Task<User?> GetByIdAsync(Guid id)
    {
        return await _context.Users
            .Include(u => u.CountryPermissions)
            .ThenInclude(cp => cp.Country)
            .FirstOrDefaultAsync(u => u.Id == id);
    }
    
    public async Task<User?> GetByUsernameAsync(string username)
    {
        return await _context.Users.FirstOrDefaultAsync(u => u.Username == username);
    }
    
    public async Task<User?> GetByEmailAsync(string email)
    {
        return await _context.Users.FirstOrDefaultAsync(u => u.Email == email);
    }
    
    public async Task<IEnumerable<User>> GetAllAsync(int page = 1, int pageSize = 20)
    {
        return await _context.Users
            .OrderBy(u => u.CreatedAt)
            .Skip((page - 1) * pageSize)
            .Take(pageSize)
            .ToListAsync();
    }
    
    public async Task<User> CreateAsync(User user)
    {
        _context.Users.Add(user);
        await _context.SaveChangesAsync();
        return user;
    }
    
    public async Task UpdateAsync(User user)
    {
        _context.Users.Update(user);
        await _context.SaveChangesAsync();
    }
    
    public async Task UpdateBalanceAsync(Guid userId, decimal newBalance)
    {
        var user = await GetByIdAsync(userId);
        if (user != null)
        {
            user.Balance = newBalance;
            user.UpdatedAt = DateTime.UtcNow;
            await _context.SaveChangesAsync();
        }
    }
}

public class SmppChannelRepository : ISmppChannelRepository
{
    private readonly SmsDbContext _context;
    
    public SmppChannelRepository(SmsDbContext context)
    {
        _context = context;
    }
    
    public async Task<SmppChannel?> GetByIdAsync(Guid id)
    {
        return await _context.SmppChannels.FindAsync(id);
    }
    
    public async Task<IEnumerable<SmppChannel>> GetAllAsync()
    {
        return await _context.SmppChannels.ToListAsync();
    }
    
    public async Task<IEnumerable<SmppChannel>> GetActiveChannelsAsync()
    {
        return await _context.SmppChannels
            .Where(c => c.Status == SmppChannelStatus.Active)
            .ToListAsync();
    }
    
    public async Task<SmppChannel> CreateAsync(SmppChannel channel)
    {
        _context.SmppChannels.Add(channel);
        await _context.SaveChangesAsync();
        return channel;
    }
    
    public async Task UpdateAsync(SmppChannel channel)
    {
        _context.SmppChannels.Update(channel);
        await _context.SaveChangesAsync();
    }
    
    public async Task DeleteAsync(Guid id)
    {
        var channel = await GetByIdAsync(id);
        if (channel != null)
        {
            _context.SmppChannels.Remove(channel);
            await _context.SaveChangesAsync();
        }
    }
}

public class CountryRepository : ICountryRepository
{
    private readonly SmsDbContext _context;
    
    public CountryRepository(SmsDbContext context)
    {
        _context = context;
    }
    
    public async Task<Country?> GetByIdAsync(Guid id)
    {
        return await _context.Countries.FindAsync(id);
    }
    
    public async Task<Country?> GetByCodeAsync(string code)
    {
        return await _context.Countries.FirstOrDefaultAsync(c => c.Code == code);
    }
    
    public async Task<IEnumerable<Country>> GetAllAsync()
    {
        return await _context.Countries.ToListAsync();
    }
    
    public async Task<Country> CreateAsync(Country country)
    {
        _context.Countries.Add(country);
        await _context.SaveChangesAsync();
        return country;
    }
    
    public async Task UpdateAsync(Country country)
    {
        _context.Countries.Update(country);
        await _context.SaveChangesAsync();
    }
}

public class CountryPricingRepository : ICountryPricingRepository
{
    private readonly SmsDbContext _context;
    
    public CountryPricingRepository(SmsDbContext context)
    {
        _context = context;
    }
    
    public async Task<CountryPricing?> GetByIdAsync(Guid id)
    {
        return await _context.CountryPricings
            .Include(p => p.Country)
            .FirstOrDefaultAsync(p => p.Id == id);
    }
    
    public async Task<CountryPricing?> GetByCountryAndUserAsync(Guid countryId, Guid? userId)
    {
        if (userId.HasValue)
        {
            return await _context.CountryPricings
                .FirstOrDefaultAsync(p => p.CountryId == countryId && p.UserId == userId.Value);
        }
        
        return await _context.CountryPricings
            .FirstOrDefaultAsync(p => p.CountryId == countryId && p.UserId == null);
    }
    
    public async Task<IEnumerable<CountryPricing>> GetByUserIdAsync(Guid userId)
    {
        return await _context.CountryPricings
            .Include(p => p.Country)
            .Where(p => p.UserId == userId || p.UserId == null)
            .ToListAsync();
    }
    
    public async Task<CountryPricing> CreateAsync(CountryPricing pricing)
    {
        _context.CountryPricings.Add(pricing);
        await _context.SaveChangesAsync();
        return pricing;
    }
    
    public async Task UpdateAsync(CountryPricing pricing)
    {
        _context.CountryPricings.Update(pricing);
        await _context.SaveChangesAsync();
    }
}

public class UserCountryPermissionRepository : IUserCountryPermissionRepository
{
    private readonly SmsDbContext _context;
    
    public UserCountryPermissionRepository(SmsDbContext context)
    {
        _context = context;
    }
    
    public async Task<UserCountryPermission?> GetByIdAsync(Guid id)
    {
        return await _context.UserCountryPermissions.FindAsync(id);
    }
    
    public async Task<IEnumerable<UserCountryPermission>> GetByUserIdAsync(Guid userId)
    {
        return await _context.UserCountryPermissions
            .Include(p => p.Country)
            .Where(p => p.UserId == userId)
            .ToListAsync();
    }
    
    public async Task<bool> HasPermissionAsync(Guid userId, Guid countryId)
    {
        var permission = await _context.UserCountryPermissions
            .FirstOrDefaultAsync(p => p.UserId == userId && p.CountryId == countryId);
        
        return permission?.IsAllowed ?? false;
    }
    
    public async Task<UserCountryPermission> CreateAsync(UserCountryPermission permission)
    {
        _context.UserCountryPermissions.Add(permission);
        await _context.SaveChangesAsync();
        return permission;
    }
    
    public async Task UpdateAsync(UserCountryPermission permission)
    {
        _context.UserCountryPermissions.Update(permission);
        await _context.SaveChangesAsync();
    }
    
    public async Task DeleteAsync(Guid id)
    {
        var permission = await GetByIdAsync(id);
        if (permission != null)
        {
            _context.UserCountryPermissions.Remove(permission);
            await _context.SaveChangesAsync();
        }
    }
}

public class ApiKeyRepository : IApiKeyRepository
{
    private readonly SmsDbContext _context;
    
    public ApiKeyRepository(SmsDbContext context)
    {
        _context = context;
    }
    
    public async Task<ApiKey?> GetByIdAsync(Guid id)
    {
        return await _context.ApiKeys.FindAsync(id);
    }
    
    public async Task<ApiKey?> GetByKeyAsync(string key)
    {
        return await _context.ApiKeys.FirstOrDefaultAsync(k => k.Key == key);
    }
    
    public async Task<IEnumerable<ApiKey>> GetByUserIdAsync(Guid userId)
    {
        return await _context.ApiKeys
            .Where(k => k.UserId == userId)
            .OrderByDescending(k => k.CreatedAt)
            .ToListAsync();
    }
    
    public async Task<ApiKey> CreateAsync(ApiKey apiKey)
    {
        _context.ApiKeys.Add(apiKey);
        await _context.SaveChangesAsync();
        return apiKey;
    }
    
    public async Task UpdateAsync(ApiKey apiKey)
    {
        _context.ApiKeys.Update(apiKey);
        await _context.SaveChangesAsync();
    }
    
    public async Task DeleteAsync(Guid id)
    {
        var apiKey = await GetByIdAsync(id);
        if (apiKey != null)
        {
            _context.ApiKeys.Remove(apiKey);
            await _context.SaveChangesAsync();
        }
    }
}

public class RechargeRecordRepository : IRechargeRecordRepository
{
    private readonly SmsDbContext _context;
    
    public RechargeRecordRepository(SmsDbContext context)
    {
        _context = context;
    }
    
    public async Task<RechargeRecord?> GetByIdAsync(Guid id)
    {
        return await _context.RechargeRecords.FindAsync(id);
    }
    
    public async Task<IEnumerable<RechargeRecord>> GetByUserIdAsync(Guid userId, int page = 1, int pageSize = 20)
    {
        return await _context.RechargeRecords
            .Where(r => r.UserId == userId)
            .OrderByDescending(r => r.CreatedAt)
            .Skip((page - 1) * pageSize)
            .Take(pageSize)
            .ToListAsync();
    }
    
    public async Task<RechargeRecord> CreateAsync(RechargeRecord record)
    {
        _context.RechargeRecords.Add(record);
        await _context.SaveChangesAsync();
        return record;
    }
    
    public async Task UpdateAsync(RechargeRecord record)
    {
        _context.RechargeRecords.Update(record);
        await _context.SaveChangesAsync();
    }
}

public class FeeLogRepository : IFeeLogRepository
{
    private readonly SmsDbContext _context;
    
    public FeeLogRepository(SmsDbContext context)
    {
        _context = context;
    }
    
    public async Task<FeeLog?> GetByIdAsync(Guid id)
    {
        return await _context.FeeLogs.FindAsync(id);
    }
    
    public async Task<IEnumerable<FeeLog>> GetByUserIdAsync(Guid userId, DateTime? fromDate = null, DateTime? toDate = null)
    {
        var query = _context.FeeLogs.Where(f => f.UserId == userId);
        
        if (fromDate.HasValue)
            query = query.Where(f => f.CreatedAt >= fromDate.Value);
        
        if (toDate.HasValue)
            query = query.Where(f => f.CreatedAt <= toDate.Value);
        
        return await query.OrderByDescending(f => f.CreatedAt).ToListAsync();
    }
    
    public async Task<IEnumerable<FeeLog>> GetBySmsMessageIdAsync(Guid smsMessageId)
    {
        return await _context.FeeLogs
            .Where(f => f.SmsMessageId == smsMessageId)
            .ToListAsync();
    }
    
    public async Task<FeeLog> CreateAsync(FeeLog feeLog)
    {
        _context.FeeLogs.Add(feeLog);
        await _context.SaveChangesAsync();
        return feeLog;
    }
}

public class SenderIdRepository : ISenderIdRepository
{
    private readonly SmsDbContext _context;
    
    public SenderIdRepository(SmsDbContext context)
    {
        _context = context;
    }
    
    public async Task<SenderId?> GetByIdAsync(Guid id)
    {
        return await _context.SenderIds.FindAsync(id);
    }
    
    public async Task<IEnumerable<SenderId>> GetByUserIdAsync(Guid userId)
    {
        return await _context.SenderIds
            .Where(s => s.UserId == userId)
            .OrderByDescending(s => s.IsDefault)
            .ThenBy(s => s.CreatedAt)
            .ToListAsync();
    }
    
    public async Task<SenderId?> GetDefaultByUserIdAsync(Guid userId)
    {
        return await _context.SenderIds
            .FirstOrDefaultAsync(s => s.UserId == userId && s.IsDefault);
    }
    
    public async Task<SenderId> CreateAsync(SenderId senderId)
    {
        _context.SenderIds.Add(senderId);
        await _context.SaveChangesAsync();
        return senderId;
    }
    
    public async Task UpdateAsync(SenderId senderId)
    {
        _context.SenderIds.Update(senderId);
        await _context.SaveChangesAsync();
    }
    
    public async Task DeleteAsync(Guid id)
    {
        var senderId = await GetByIdAsync(id);
        if (senderId != null)
        {
            _context.SenderIds.Remove(senderId);
            await _context.SaveChangesAsync();
        }
    }
}

public class SystemSettingRepository : ISystemSettingRepository
{
    private readonly SmsDbContext _context;
    
    public SystemSettingRepository(SmsDbContext context)
    {
        _context = context;
    }
    
    public async Task<SystemSetting?> GetByKeyAsync(string key)
    {
        return await _context.SystemSettings.FirstOrDefaultAsync(s => s.Key == key);
    }
    
    public async Task<IEnumerable<SystemSetting>> GetAllAsync()
    {
        return await _context.SystemSettings.ToListAsync();
    }
    
    public async Task<SystemSetting> CreateAsync(SystemSetting setting)
    {
        _context.SystemSettings.Add(setting);
        await _context.SaveChangesAsync();
        return setting;
    }
    
    public async Task UpdateAsync(SystemSetting setting)
    {
        _context.SystemSettings.Update(setting);
        await _context.SaveChangesAsync();
    }
    
    public async Task<string?> GetValueAsync(string key)
    {
        var setting = await GetByKeyAsync(key);
        return setting?.Value;
    }
}

public class AlertRepository : IAlertRepository
{
    private readonly SmsDbContext _context;
    
    public AlertRepository(SmsDbContext context)
    {
        _context = context;
    }
    
    public async Task<Alert?> GetByIdAsync(Guid id)
    {
        return await _context.Alerts.FindAsync(id);
    }
    
    public async Task<IEnumerable<Alert>> GetActiveAlertsAsync()
    {
        return await _context.Alerts
            .Include(a => a.SmppChannel)
            .Where(a => a.Status == AlertStatus.Active || a.Status == AlertStatus.Acknowledged)
            .OrderByDescending(a => a.CreatedAt)
            .ToListAsync();
    }
    
    public async Task<IEnumerable<Alert>> GetByChannelIdAsync(Guid channelId)
    {
        return await _context.Alerts
            .Where(a => a.SmppChannelId == channelId)
            .OrderByDescending(a => a.CreatedAt)
            .ToListAsync();
    }
    
    public async Task<Alert> CreateAsync(Alert alert)
    {
        _context.Alerts.Add(alert);
        await _context.SaveChangesAsync();
        return alert;
    }
    
    public async Task UpdateAsync(Alert alert)
    {
        _context.Alerts.Update(alert);
        await _context.SaveChangesAsync();
    }
}

public class AlertPolicyRepository : IAlertPolicyRepository
{
    private readonly SmsDbContext _context;
    
    public AlertPolicyRepository(SmsDbContext context)
    {
        _context = context;
    }
    
    public async Task<AlertPolicy?> GetByIdAsync(Guid id)
    {
        return await _context.AlertPolicies.FindAsync(id);
    }
    
    public async Task<IEnumerable<AlertPolicy>> GetAllAsync()
    {
        return await _context.AlertPolicies.ToListAsync();
    }
    
    public async Task<IEnumerable<AlertPolicy>> GetEnabledAsync()
    {
        return await _context.AlertPolicies
            .Where(p => p.IsEnabled)
            .ToListAsync();
    }
    
    public async Task<AlertPolicy> CreateAsync(AlertPolicy policy)
    {
        _context.AlertPolicies.Add(policy);
        await _context.SaveChangesAsync();
        return policy;
    }
    
    public async Task UpdateAsync(AlertPolicy policy)
    {
        _context.AlertPolicies.Update(policy);
        await _context.SaveChangesAsync();
    }
}
