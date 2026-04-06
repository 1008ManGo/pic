using SmsPlatform.Application.DTOs;
using SmsPlatform.Domain.Entities;
using SmsPlatform.Domain.Enums;

namespace SmsPlatform.Application.Interfaces;

public interface ISmsService
{
    Task<SmsSubmitResponse> SubmitSmsAsync(Guid userId, SmsSubmitRequest request);
    Task<SmsSubmitResponse> SubmitBatchSmsAsync(Guid userId, SmsBatchSubmitRequest request);
    Task<SmsStatusResponse?> GetSmsStatusAsync(Guid userId, Guid messageId);
    Task<IEnumerable<SmsStatusResponse>> GetSmsHistoryAsync(Guid userId, int page = 1, int pageSize = 20);
    Task<IEnumerable<SmsStatusResponse>> ResubmitFailedSmsAsync(Guid userId, IEnumerable<Guid> messageIds);
    Task<SmsReportResponse> GenerateReportAsync(SmsReportRequest request);
}

public interface IUserService
{
    Task<UserResponse> CreateUserAsync(UserCreateRequest request);
    Task<UserResponse?> GetUserByIdAsync(Guid id);
    Task<UserResponse?> GetUserByUsernameAsync(string username);
    Task<IEnumerable<UserResponse>> GetAllUsersAsync(int page = 1, int pageSize = 20);
    Task<UserResponse> UpdateUserAsync(UserUpdateRequest request);
    Task<LoginResponse> LoginAsync(LoginRequest request);
    Task<ApiKeyResponse> CreateApiKeyAsync(Guid userId, string name);
    Task<IEnumerable<ApiKeyResponse>> GetUserApiKeysAsync(Guid userId);
    Task RevokeApiKeyAsync(Guid apiKeyId);
    Task<decimal> GetUserBalanceAsync(Guid userId);
}

public interface IChannelService
{
    Task<ChannelStatusResponse> CreateChannelAsync(ChannelCreateRequest request);
    Task<ChannelStatusResponse?> GetChannelByIdAsync(Guid id);
    Task<IEnumerable<ChannelStatusResponse>> GetAllChannelsAsync();
    Task<IEnumerable<ChannelStatusResponse>> GetActiveChannelsAsync();
    Task<ChannelStatusResponse> UpdateChannelAsync(ChannelUpdateRequest request);
    Task DeleteChannelAsync(Guid id);
    Task<Dictionary<Guid, ChannelStatusResponse>> GetAllChannelStatusesAsync();
}

public interface IPricingService
{
    Task<CountryPricing> SetCountryPricingAsync(Guid countryId, decimal price, Guid? userId = null);
    Task<CountryPricing?> GetCountryPricingAsync(Guid countryId, Guid? userId = null);
    Task<IEnumerable<CountryPricing>> GetUserPricingsAsync(Guid userId);
    Task<IEnumerable<Country>> GetAllCountriesAsync();
}

public interface IFinanceService
{
    Task<RechargeResponse> RechargeAsync(RechargeRequest request);
    Task<decimal> GetBalanceAsync(Guid userId);
    Task<FinanceReportResponse> GenerateFinanceReportAsync(FinanceReportRequest request);
    Task<IEnumerable<FeeLog>> GetFeeLogsAsync(Guid userId, DateTime? fromDate = null, DateTime? toDate = null);
}

public interface ISenderIdService
{
    Task<SenderIdResponse> CreateSenderIdAsync(SenderIdCreateRequest request);
    Task<IEnumerable<SenderIdResponse>> GetUserSenderIdsAsync(Guid userId);
    Task<SenderIdResponse> SetDefaultSenderIdAsync(Guid userId, Guid senderId);
    Task ApproveSenderIdAsync(Guid senderId);
    Task DeleteSenderIdAsync(Guid id);
}

public interface IAlertService
{
    Task CheckAndCreateAlertsAsync();
    Task<IEnumerable<Alert>> GetActiveAlertsAsync();
    Task AcknowledgeAlertAsync(Guid alertId);
    Task ResolveAlertAsync(Guid alertId);
}

public interface ICacheService
{
    Task<bool> HasCountryPermissionAsync(Guid userId, string countryCode);
    Task SetCountryPermissionAsync(Guid userId, string countryCode, bool allowed);
    Task<decimal?> GetUserBalanceCachedAsync(Guid userId);
    Task SetUserBalanceCachedAsync(Guid userId, decimal balance);
    Task InvalidateUserBalanceAsync(Guid userId);
}
