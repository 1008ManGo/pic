using SmsPlatform.Domain.Entities;
using SmsPlatform.Domain.Enums;

namespace SmsPlatform.Domain.Interfaces;

public interface ISmsRepository
{
    Task<SmsMessage?> GetByIdAsync(Guid id);
    Task<IEnumerable<SmsMessage>> GetByUserIdAsync(Guid userId, int page = 1, int pageSize = 20);
    Task<IEnumerable<SmsMessage>> GetByStatusAsync(SmsMessageStatus status, int page = 1, int pageSize = 100);
    Task<SmsMessage> CreateAsync(SmsMessage message);
    Task UpdateAsync(SmsMessage message);
    Task<IEnumerable<SmsMessage>> GetPendingMessagesAsync(int limit = 100);
}

public interface ISmppChannelRepository
{
    Task<SmppChannel?> GetByIdAsync(Guid id);
    Task<IEnumerable<SmppChannel>> GetAllAsync();
    Task<IEnumerable<SmppChannel>> GetActiveChannelsAsync();
    Task<SmppChannel> CreateAsync(SmppChannel channel);
    Task UpdateAsync(SmppChannel channel);
    Task DeleteAsync(Guid id);
}

public interface IUserRepository
{
    Task<User?> GetByIdAsync(Guid id);
    Task<User?> GetByUsernameAsync(string username);
    Task<User?> GetByEmailAsync(string email);
    Task<IEnumerable<User>> GetAllAsync(int page = 1, int pageSize = 20);
    Task<User> CreateAsync(User user);
    Task UpdateAsync(User user);
    Task UpdateBalanceAsync(Guid userId, decimal newBalance);
}

public interface ICountryRepository
{
    Task<Country?> GetByIdAsync(Guid id);
    Task<Country?> GetByCodeAsync(string code);
    Task<IEnumerable<Country>> GetAllAsync();
    Task<Country> CreateAsync(Country country);
    Task UpdateAsync(Country country);
}

public interface ICountryPricingRepository
{
    Task<CountryPricing?> GetByIdAsync(Guid id);
    Task<CountryPricing?> GetByCountryAndUserAsync(Guid countryId, Guid? userId);
    Task<IEnumerable<CountryPricing>> GetByUserIdAsync(Guid userId);
    Task<CountryPricing> CreateAsync(CountryPricing pricing);
    Task UpdateAsync(CountryPricing pricing);
}

public interface IUserCountryPermissionRepository
{
    Task<UserCountryPermission?> GetByIdAsync(Guid id);
    Task<IEnumerable<UserCountryPermission>> GetByUserIdAsync(Guid userId);
    Task<bool> HasPermissionAsync(Guid userId, Guid countryId);
    Task<UserCountryPermission> CreateAsync(UserCountryPermission permission);
    Task UpdateAsync(UserCountryPermission permission);
    Task DeleteAsync(Guid id);
}

public interface IApiKeyRepository
{
    Task<ApiKey?> GetByIdAsync(Guid id);
    Task<ApiKey?> GetByKeyAsync(string key);
    Task<IEnumerable<ApiKey>> GetByUserIdAsync(Guid userId);
    Task<ApiKey> CreateAsync(ApiKey apiKey);
    Task UpdateAsync(ApiKey apiKey);
    Task DeleteAsync(Guid id);
}

public interface IRechargeRecordRepository
{
    Task<RechargeRecord?> GetByIdAsync(Guid id);
    Task<IEnumerable<RechargeRecord>> GetByUserIdAsync(Guid userId, int page = 1, int pageSize = 20);
    Task<RechargeRecord> CreateAsync(RechargeRecord record);
    Task UpdateAsync(RechargeRecord record);
}

public interface IFeeLogRepository
{
    Task<FeeLog?> GetByIdAsync(Guid id);
    Task<IEnumerable<FeeLog>> GetByUserIdAsync(Guid userId, DateTime? fromDate = null, DateTime? toDate = null);
    Task<IEnumerable<FeeLog>> GetBySmsMessageIdAsync(Guid smsMessageId);
    Task<FeeLog> CreateAsync(FeeLog feeLog);
}

public interface ISenderIdRepository
{
    Task<SenderId?> GetByIdAsync(Guid id);
    Task<IEnumerable<SenderId>> GetByUserIdAsync(Guid userId);
    Task<SenderId?> GetDefaultByUserIdAsync(Guid userId);
    Task<SenderId> CreateAsync(SenderId senderId);
    Task UpdateAsync(SenderId senderId);
    Task DeleteAsync(Guid id);
}

public interface ISystemSettingRepository
{
    Task<SystemSetting?> GetByKeyAsync(string key);
    Task<IEnumerable<SystemSetting>> GetAllAsync();
    Task<SystemSetting> CreateAsync(SystemSetting setting);
    Task UpdateAsync(SystemSetting setting);
    Task<string?> GetValueAsync(string key);
}

public interface IAlertRepository
{
    Task<Alert?> GetByIdAsync(Guid id);
    Task<IEnumerable<Alert>> GetActiveAlertsAsync();
    Task<IEnumerable<Alert>> GetByChannelIdAsync(Guid channelId);
    Task<Alert> CreateAsync(Alert alert);
    Task UpdateAsync(Alert alert);
}

public interface IAlertPolicyRepository
{
    Task<AlertPolicy?> GetByIdAsync(Guid id);
    Task<IEnumerable<AlertPolicy>> GetAllAsync();
    Task<IEnumerable<AlertPolicy>> GetEnabledAsync();
    Task<AlertPolicy> CreateAsync(AlertPolicy policy);
    Task UpdateAsync(AlertPolicy policy);
}
