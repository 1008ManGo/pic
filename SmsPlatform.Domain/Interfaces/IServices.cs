using SmsPlatform.Domain.Entities;
using SmsPlatform.Domain.Enums;

namespace SmsPlatform.Domain.Interfaces;

public interface ISmppClientManager
{
    Task<bool> ConnectAsync(SmppChannel channel);
    Task DisconnectAsync(Guid channelId);
    Task<bool> SendSingleSmsAsync(Guid channelId, SmsMessage message);
    Task<(bool Success, string? MessageId)> SubmitSmAsync(Guid channelId, string senderId, string receiverNumber, string content, byte dataCoding);
    Task<Dictionary<Guid, SmppChannelStatus>> GetAllChannelStatusesAsync();
    event EventHandler<SmppChannelStatusEventArgs>? ChannelStatusChanged;
    event EventHandler<DeliverSmEventArgs>? MessageReceived;
}

public class SmppChannelStatusEventArgs : EventArgs
{
    public Guid ChannelId { get; set; }
    public bool IsOnline { get; set; }
    public string? ErrorMessage { get; set; }
}

public class DeliverSmEventArgs : EventArgs
{
    public Guid ChannelId { get; set; }
    public string? MessageId { get; set; }
    public string? SenderId { get; set; }
    public string? ReceiverNumber { get; set; }
    public string? Content { get; set; }
    public DateTime ReceivedAt { get; set; }
}

public interface IQueueService
{
    Task PublishSmsMessageAsync(SmsMessage message);
    Task<SmsMessage?> ConsumeSmsMessageAsync();
    Task PublishBatchJobAsync(BatchSmsJob job, IEnumerable<string> numbers);
    Task PublishDeliveryReportAsync(Guid messageId, string status, DateTime timestamp);
    Task<int> GetQueueLengthAsync();
}

public interface IRedisService
{
    Task<T?> GetAsync<T>(string key) where T : class;
    Task SetAsync<T>(string key, T value, TimeSpan? expiry = null) where T : class;
    Task<bool> DeleteAsync(string key);
    Task<bool> ExistsAsync(string key);
    Task<bool> SetIfNotExistsAsync(string key, string value, TimeSpan? expiry = null);
    Task<long> IncrementAsync(string key);
    Task<long> DecrementAsync(string key);
    Task<bool> TryAcquireLockAsync(string key, TimeSpan expiry);
    Task ReleaseLockAsync(string key);
    
    Task<bool> CheckUserCountryPermissionAsync(Guid userId, string countryCode);
    Task<decimal?> GetUserBalanceAsync(Guid userId);
    Task SetUserBalanceCachedAsync(Guid userId, decimal balance);
    Task<decimal?> GetCountryPriceAsync(Guid countryId, Guid? userId);
    Task<int> GetChannelCurrentTpsAsync(Guid channelId);
    Task IncrementChannelTpsAsync(Guid channelId);
    Task ResetChannelTpsAsync(Guid channelId);
}
