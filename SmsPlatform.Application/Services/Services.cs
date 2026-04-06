using Microsoft.Extensions.Logging;
using SmsPlatform.Application.DTOs;
using SmsPlatform.Application.Interfaces;
using SmsPlatform.Domain.Entities;
using SmsPlatform.Domain.Enums;
using SmsPlatform.Domain.Interfaces;

namespace SmsPlatform.Application.Services;

public class ChannelService : IChannelService
{
    private readonly ISmppChannelRepository _channelRepository;
    private readonly ISmppClientManager _smppManager;
    private readonly IQueueService _queueService;
    private readonly IRedisService _redisService;
    private readonly ILogger<ChannelService> _logger;
    
    public ChannelService(
        ISmppChannelRepository channelRepository,
        ISmppClientManager smppManager,
        IQueueService queueService,
        IRedisService redisService,
        ILogger<ChannelService> logger)
    {
        _channelRepository = channelRepository;
        _smppManager = smppManager;
        _queueService = queueService;
        _redisService = redisService;
        _logger = logger;
    }
    
    public async Task<ChannelStatusResponse> CreateChannelAsync(ChannelCreateRequest request)
    {
        var channel = new SmppChannel
        {
            Name = request.Name,
            Host = request.Host,
            Port = request.Port,
            Username = request.Username,
            Password = request.Password,
            MaxTps = request.MaxTps,
            MaxBindCount = request.MaxBindCount,
            SystemType = request.SystemType,
            HeartbeatInterval = request.HeartbeatInterval,
            ReconnectDelay = request.ReconnectDelay,
            IsDefault = request.IsDefault,
            Status = SmppChannelStatus.Active
        };
        
        await _channelRepository.CreateAsync(channel);
        
        try
        {
            await _smppManager.ConnectAsync(channel);
            channel.IsOnline = true;
            await _channelRepository.UpdateAsync(channel);
        }
        catch (Exception ex)
        {
            _logger.LogWarning(ex, "Failed to connect to channel {ChannelName} during creation", channel.Name);
        }
        
        return MapToStatusResponse(channel);
    }
    
    public async Task<ChannelStatusResponse?> GetChannelByIdAsync(Guid id)
    {
        var channel = await _channelRepository.GetByIdAsync(id);
        return channel != null ? MapToStatusResponse(channel) : null;
    }
    
    public async Task<IEnumerable<ChannelStatusResponse>> GetAllChannelsAsync()
    {
        var channels = await _channelRepository.GetAllAsync();
        return channels.Select(MapToStatusResponse);
    }
    
    public async Task<IEnumerable<ChannelStatusResponse>> GetActiveChannelsAsync()
    {
        var channels = await _channelRepository.GetActiveChannelsAsync();
        return channels.Select(MapToStatusResponse);
    }
    
    public async Task<ChannelStatusResponse> UpdateChannelAsync(ChannelUpdateRequest request)
    {
        var channel = await _channelRepository.GetByIdAsync(request.Id);
        if (channel == null)
            throw new InvalidOperationException("Channel not found");
        
        channel.Name = request.Name;
        channel.Host = request.Host;
        channel.Port = request.Port;
        channel.Username = request.Username;
        channel.Password = request.Password;
        channel.MaxTps = request.MaxTps;
        channel.MaxBindCount = request.MaxBindCount;
        channel.SystemType = request.SystemType;
        channel.HeartbeatInterval = request.HeartbeatInterval;
        channel.ReconnectDelay = request.ReconnectDelay;
        channel.Status = request.Status;
        channel.UpdatedAt = DateTime.UtcNow;
        
        await _channelRepository.UpdateAsync(channel);
        
        return MapToStatusResponse(channel);
    }
    
    public async Task DeleteChannelAsync(Guid id)
    {
        await _smppManager.DisconnectAsync(id);
        await _channelRepository.DeleteAsync(id);
    }
    
    public async Task<Dictionary<Guid, ChannelStatusResponse>> GetAllChannelStatusesAsync()
    {
        var statuses = await _smppManager.GetAllChannelStatusesAsync();
        var result = new Dictionary<Guid, ChannelStatusResponse>();
        
        foreach (var kvp in statuses)
        {
            var channel = await _channelRepository.GetByIdAsync(kvp.Key);
            if (channel != null)
            {
                var isOnline = kvp.Value == SmppChannelStatus.Active;
                var response = new ChannelStatusResponse(
                    channel.Id,
                    channel.Name,
                    isOnline,
                    channel.CurrentBindCount,
                    channel.MaxBindCount,
                    channel.CurrentTps,
                    channel.MaxTps,
                    channel.QueueLength,
                    channel.SuccessRate,
                    channel.FailureRate,
                    channel.Status
                );
                result[kvp.Key] = response;
            }
        }
        
        return result;
    }
    
    private ChannelStatusResponse MapToStatusResponse(SmppChannel channel)
    {
        return new ChannelStatusResponse(
            channel.Id,
            channel.Name,
            channel.IsOnline,
            channel.CurrentBindCount,
            channel.MaxBindCount,
            channel.CurrentTps,
            channel.MaxTps,
            channel.QueueLength,
            channel.SuccessRate,
            channel.FailureRate,
            channel.Status
        );
    }
}

public class PricingService : IPricingService
{
    private readonly ICountryRepository _countryRepository;
    private readonly ICountryPricingRepository _pricingRepository;
    private readonly IRedisService _redisService;
    
    public PricingService(
        ICountryRepository countryRepository,
        ICountryPricingRepository pricingRepository,
        IRedisService redisService)
    {
        _countryRepository = countryRepository;
        _pricingRepository = pricingRepository;
        _redisService = redisService;
    }
    
    public async Task<CountryPricing> SetCountryPricingAsync(Guid countryId, decimal price, Guid? userId = null)
    {
        var existing = await _pricingRepository.GetByCountryAndUserAsync(countryId, userId);
        
        if (existing != null)
        {
            existing.PricePerSms = price;
            existing.UpdatedAt = DateTime.UtcNow;
            await _pricingRepository.UpdateAsync(existing);
            return existing;
        }
        
        var pricing = new CountryPricing
        {
            CountryId = countryId,
            UserId = userId,
            PricePerSms = price
        };
        
        await _pricingRepository.CreateAsync(pricing);
        return pricing;
    }
    
    public async Task<CountryPricing?> GetCountryPricingAsync(Guid countryId, Guid? userId = null)
    {
        var cachedPrice = await _redisService.GetCountryPriceAsync(countryId, userId);
        
        var pricing = await _pricingRepository.GetByCountryAndUserAsync(countryId, userId);
        if (pricing == null && userId.HasValue)
        {
            pricing = await _pricingRepository.GetByCountryAndUserAsync(countryId, null);
        }
        
        return pricing;
    }
    
    public async Task<IEnumerable<CountryPricing>> GetUserPricingsAsync(Guid userId)
    {
        return await _pricingRepository.GetByUserIdAsync(userId);
    }
    
    public async Task<IEnumerable<Country>> GetAllCountriesAsync()
    {
        return await _countryRepository.GetAllAsync();
    }
}

public class FinanceService : IFinanceService
{
    private readonly IUserRepository _userRepository;
    private readonly IRechargeRecordRepository _rechargeRepository;
    private readonly IFeeLogRepository _feeLogRepository;
    private readonly IRedisService _redisService;
    
    public FinanceService(
        IUserRepository userRepository,
        IRechargeRecordRepository rechargeRepository,
        IFeeLogRepository feeLogRepository,
        IRedisService redisService)
    {
        _userRepository = userRepository;
        _rechargeRepository = rechargeRepository;
        _feeLogRepository = feeLogRepository;
        _redisService = redisService;
    }
    
    public async Task<RechargeResponse> RechargeAsync(RechargeRequest request)
    {
        var user = await _userRepository.GetByIdAsync(request.UserId);
        if (user == null)
            throw new InvalidOperationException("User not found");
        
        var record = new RechargeRecord
        {
            UserId = request.UserId,
            Amount = request.Amount,
            BalanceBefore = user.Balance,
            BalanceAfter = user.Balance + request.Amount,
            Status = RechargeStatus.Completed,
            PaymentMethod = request.PaymentMethod,
            Notes = request.Notes,
            CompletedAt = DateTime.UtcNow
        };
        
        await _rechargeRepository.CreateAsync(record);
        
        await _userRepository.UpdateBalanceAsync(request.UserId, record.BalanceAfter);
        await _redisService.SetUserBalanceCachedAsync(request.UserId, record.BalanceAfter);
        
        return new RechargeResponse(
            record.Id,
            record.Amount,
            record.BalanceBefore,
            record.BalanceAfter,
            record.Status,
            record.CreatedAt
        );
    }
    
    public async Task<decimal> GetBalanceAsync(Guid userId)
    {
        var cached = await _redisService.GetUserBalanceAsync(userId);
        if (cached.HasValue)
            return cached.Value;
        
        var user = await _userRepository.GetByIdAsync(userId);
        if (user == null)
            return 0;
        
        await _redisService.SetUserBalanceCachedAsync(userId, user.Balance);
        return user.Balance;
    }
    
    public async Task<FinanceReportResponse> GenerateFinanceReportAsync(FinanceReportRequest request)
    {
        var feeLogs = request.UserId.HasValue
            ? await _feeLogRepository.GetByUserIdAsync(request.UserId.Value, request.FromDate, request.ToDate)
            : Enumerable.Empty<FeeLog>();
            
        var rechargeRecords = request.UserId.HasValue
            ? await _rechargeRepository.GetByUserIdAsync(request.UserId.Value)
            : Enumerable.Empty<RechargeRecord>();
        
        var totalRecharge = rechargeRecords.Where(r => r.Status == RechargeStatus.Completed).Sum(r => r.Amount);
        var totalExpense = feeLogs.Sum(f => Math.Abs(f.Amount));
        
        return new FinanceReportResponse(
            totalRecharge,
            totalExpense,
            totalRecharge - totalExpense,
            rechargeRecords.ToList(),
            feeLogs.ToList()
        );
    }
    
    public async Task<IEnumerable<FeeLog>> GetFeeLogsAsync(Guid userId, DateTime? fromDate = null, DateTime? toDate = null)
    {
        return await _feeLogRepository.GetByUserIdAsync(userId, fromDate, toDate);
    }
}

public class SenderIdService : ISenderIdService
{
    private readonly ISenderIdRepository _senderIdRepository;
    
    public SenderIdService(ISenderIdRepository senderIdRepository)
    {
        _senderIdRepository = senderIdRepository;
    }
    
    public async Task<SenderIdResponse> CreateSenderIdAsync(SenderIdCreateRequest request)
    {
        var senderId = new SenderId
        {
            UserId = request.UserId,
            SenderIdValue = request.SenderIdValue,
            IsDefault = request.IsDefault
        };
        
        await _senderIdRepository.CreateAsync(senderId);
        
        return new SenderIdResponse(
            senderId.Id,
            senderId.SenderIdValue,
            senderId.IsApproved,
            senderId.IsDefault,
            senderId.CreatedAt
        );
    }
    
    public async Task<IEnumerable<SenderIdResponse>> GetUserSenderIdsAsync(Guid userId)
    {
        var senderIds = await _senderIdRepository.GetByUserIdAsync(userId);
        return senderIds.Select(s => new SenderIdResponse(
            s.Id,
            s.SenderIdValue,
            s.IsApproved,
            s.IsDefault,
            s.CreatedAt
        ));
    }
    
    public async Task<SenderIdResponse> SetDefaultSenderIdAsync(Guid userId, Guid senderId)
    {
        var sender = await _senderIdRepository.GetByIdAsync(senderId);
        if (sender == null || sender.UserId != userId)
            throw new InvalidOperationException("Sender ID not found");
        
        sender.IsDefault = true;
        await _senderIdRepository.UpdateAsync(sender);
        
        return new SenderIdResponse(
            sender.Id,
            sender.SenderIdValue,
            sender.IsApproved,
            sender.IsDefault,
            sender.CreatedAt
        );
    }
    
    public async Task ApproveSenderIdAsync(Guid senderId)
    {
        var sender = await _senderIdRepository.GetByIdAsync(senderId);
        if (sender != null)
        {
            sender.IsApproved = true;
            sender.ApprovedAt = DateTime.UtcNow;
            await _senderIdRepository.UpdateAsync(sender);
        }
    }
    
    public async Task DeleteSenderIdAsync(Guid id)
    {
        await _senderIdRepository.DeleteAsync(id);
    }
}

public class AlertService : IAlertService
{
    private readonly IAlertRepository _alertRepository;
    private readonly IAlertPolicyRepository _policyRepository;
    private readonly ISmppChannelRepository _channelRepository;
    private readonly IQueueService _queueService;
    
    public AlertService(
        IAlertRepository alertRepository,
        IAlertPolicyRepository policyRepository,
        ISmppChannelRepository channelRepository,
        IQueueService queueService)
    {
        _alertRepository = alertRepository;
        _policyRepository = policyRepository;
        _channelRepository = channelRepository;
        _queueService = queueService;
    }
    
    public async Task CheckAndCreateAlertsAsync()
    {
        var policies = await _policyRepository.GetEnabledAsync();
        var channels = await _channelRepository.GetAllAsync();
        
        foreach (var channel in channels)
        {
            if (channel.FailureRate > 10)
            {
                var existingAlert = (await _alertRepository.GetByChannelIdAsync(channel.Id))
                    .FirstOrDefault(a => a.Type == AlertType.HighFailureRate && a.Status == AlertStatus.Active);
                
                if (existingAlert == null)
                {
                    var alert = new Alert
                    {
                        SmppChannelId = channel.Id,
                        Type = AlertType.HighFailureRate,
                        Message = $"Channel {channel.Name} has high failure rate: {channel.FailureRate}%"
                    };
                    
                    await _alertRepository.CreateAsync(alert);
                }
            }
        }
    }
    
    public async Task<IEnumerable<Alert>> GetActiveAlertsAsync()
    {
        return await _alertRepository.GetActiveAlertsAsync();
    }
    
    public async Task AcknowledgeAlertAsync(Guid alertId)
    {
        var alert = await _alertRepository.GetByIdAsync(alertId);
        if (alert != null)
        {
            alert.Status = AlertStatus.Acknowledged;
            await _alertRepository.UpdateAsync(alert);
        }
    }
    
    public async Task ResolveAlertAsync(Guid alertId)
    {
        var alert = await _alertRepository.GetByIdAsync(alertId);
        if (alert != null)
        {
            alert.Status = AlertStatus.Resolved;
            alert.ResolvedAt = DateTime.UtcNow;
            await _alertRepository.UpdateAsync(alert);
        }
    }
}
