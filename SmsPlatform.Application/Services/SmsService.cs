using Microsoft.Extensions.Logging;
using SmsPlatform.Application.DTOs;
using SmsPlatform.Application.Interfaces;
using SmsPlatform.Domain.Entities;
using SmsPlatform.Domain.Enums;
using SmsPlatform.Domain.Interfaces;

namespace SmsPlatform.Application.Services;

public class SmsService : ISmsService
{
    private readonly ISmsRepository _smsRepository;
    private readonly IUserRepository _userRepository;
    private readonly ICountryRepository _countryRepository;
    private readonly ICountryPricingRepository _pricingRepository;
    private readonly IUserCountryPermissionRepository _permissionRepository;
    private readonly ISmppChannelRepository _channelRepository;
    private readonly IQueueService _queueService;
    private readonly IRedisService _redisService;
    private readonly ISmppClientManager _smppManager;
    private readonly ILogger<SmsService> _logger;
    
    public SmsService(
        ISmsRepository smsRepository,
        IUserRepository userRepository,
        ICountryRepository countryRepository,
        ICountryPricingRepository pricingRepository,
        IUserCountryPermissionRepository permissionRepository,
        ISmppChannelRepository channelRepository,
        IQueueService queueService,
        IRedisService redisService,
        ISmppClientManager smppManager,
        ILogger<SmsService> logger)
    {
        _smsRepository = smsRepository;
        _userRepository = userRepository;
        _countryRepository = countryRepository;
        _pricingRepository = pricingRepository;
        _permissionRepository = permissionRepository;
        _channelRepository = channelRepository;
        _queueService = queueService;
        _redisService = redisService;
        _smppManager = smppManager;
        _logger = logger;
    }
    
    public async Task<SmsSubmitResponse> SubmitSmsAsync(Guid userId, SmsSubmitRequest request)
    {
        var user = await _userRepository.GetByIdAsync(userId);
        if (user == null)
            throw new UnauthorizedAccessException("User not found");
        
        if (user.Status != UserStatus.Active)
            throw new UnauthorizedAccessException("User account is not active");
        
        var (normalizedNumber, country) = await ParseAndValidateNumberAsync(request.ReceiverNumber);
        
        var hasPermission = await _permissionRepository.HasPermissionAsync(userId, country.Id);
        if (!hasPermission)
            throw new InvalidOperationException($"User does not have permission to send SMS to {country.Code}");
        
        var content = request.Content;
        if (request.AppendRandomChars)
        {
            var randomChars = GenerateRandomChars(5);
            content = $"{content}{randomChars}";
        }
        
        var encodingInfo = SmsSubmitValidator.CalculateEncoding(content);
        
        var pricing = await _pricingRepository.GetByCountryAndUserAsync(country.Id, userId);
        if (pricing == null)
        {
            pricing = await _pricingRepository.GetByCountryAndUserAsync(country.Id, null);
        }
        
        var totalPrice = pricing!.PricePerSms * encodingInfo.TotalSegments;
        
        var balance = await _redisService.GetUserBalanceAsync(userId);
        if (balance == null)
        {
            balance = user.Balance;
        }
        
        if (balance < totalPrice)
            throw new InvalidOperationException("Insufficient balance");
        
        var channel = await GetAvailableChannelAsync();
        
        var message = new SmsMessage
        {
            UserId = userId,
            CountryId = country.Id,
            SmppChannelId = channel?.Id,
            SenderId = request.SenderId,
            ReceiverNumber = normalizedNumber,
            Content = request.Content,
            AppendedRandomChars = request.AppendRandomChars ? content[(content.Length - (request.AppendRandomChars ? 5 : 0))..] : null,
            Encoding = encodingInfo.Encoding,
            Priority = request.Priority,
            Status = SmsMessageStatus.Pending,
            TotalSegments = encodingInfo.TotalSegments,
            UnitPrice = pricing.PricePerSms,
            TotalPrice = totalPrice,
            ExternalId = Guid.NewGuid().ToString("N")
        };
        
        await _smsRepository.CreateAsync(message);
        
        await DeductBalanceAsync(userId, totalPrice);
        
        await _queueService.PublishSmsMessageAsync(message);
        
        message.Status = SmsMessageStatus.Queued;
        await _smsRepository.UpdateAsync(message);
        
        return new SmsSubmitResponse(
            message.Id,
            message.ExternalId!,
            message.TotalSegments,
            message.TotalPrice,
            message.Status.ToString()
        );
    }
    
    public async Task<SmsSubmitResponse> SubmitBatchSmsAsync(Guid userId, SmsBatchSubmitRequest request)
    {
        var numbers = request.FileContent
            .Split(new[] { '\r', '\n' }, StringSplitOptions.RemoveEmptyEntries)
            .Select(n => n.Trim())
            .Where(n => !string.IsNullOrEmpty(n))
            .Distinct()
            .ToList();
        
        var job = new BatchSmsJob
        {
            UserId = userId,
            FileName = "batch.txt",
            SenderId = request.SenderId,
            Content = request.Content,
            AppendRandomChars = request.AppendRandomChars,
            Priority = request.Priority,
            TotalNumbers = numbers.Count,
            Status = BatchJobStatus.Pending
        };
        
        await _queueService.PublishBatchJobAsync(job, numbers);
        
        return new SmsSubmitResponse(
            job.Id,
            job.Id.ToString("N"),
            numbers.Count,
            0,
            BatchJobStatus.Processing.ToString()
        );
    }
    
    public async Task<SmsStatusResponse?> GetSmsStatusAsync(Guid userId, Guid messageId)
    {
        var message = await _smsRepository.GetByIdAsync(messageId);
        if (message == null || message.UserId != userId)
            return null;
        
        return MapToStatusResponse(message);
    }
    
    public async Task<IEnumerable<SmsStatusResponse>> GetSmsHistoryAsync(Guid userId, int page = 1, int pageSize = 20)
    {
        var messages = await _smsRepository.GetByUserIdAsync(userId, page, pageSize);
        return messages.Select(MapToStatusResponse);
    }
    
    public async Task<IEnumerable<SmsStatusResponse>> ResubmitFailedSmsAsync(Guid userId, IEnumerable<Guid> messageIds)
    {
        var results = new List<SmsStatusResponse>();
        
        foreach (var messageId in messageIds)
        {
            var message = await _smsRepository.GetByIdAsync(messageId);
            if (message == null || message.UserId != userId)
                continue;
            
            if (message.Status != SmsMessageStatus.Failed && message.Status != SmsMessageStatus.NotSent)
                continue;
            
            message.Status = SmsMessageStatus.Pending;
            message.UpdatedAt = DateTime.UtcNow;
            await _smsRepository.UpdateAsync(message);
            
            await _queueService.PublishSmsMessageAsync(message);
            
            results.Add(MapToStatusResponse(message));
        }
        
        return results;
    }
    
    public async Task<SmsReportResponse> GenerateReportAsync(SmsReportRequest request)
    {
        var messages = await Task.FromResult(Enumerable.Empty<SmsMessage>());
        
        return new SmsReportResponse(
            TotalMessages: 0,
            SuccessCount: 0,
            FailureCount: 0,
            PendingCount: 0,
            TotalRevenue: 0,
            MessagesByCountry: new Dictionary<string, int>(),
            MessagesByDay: new Dictionary<string, int>()
        );
    }
    
    private async Task<(string Number, Country Country)> ParseAndValidateNumberAsync(string number)
    {
        number = number.Trim().Replace(" ", "").Replace("-", "");
        
        string? countryCode = null;
        Country? country = null;
        
        if (number.StartsWith("+"))
        {
            var digits = number[1..];
            var countries = await _countryRepository.GetAllAsync();
            
            foreach (var c in countries)
            {
                if (digits.StartsWith(c.DialCode.Replace("+", "")))
                {
                    countryCode = c.Code;
                    country = c;
                    break;
                }
            }
        }
        
        if (country == null)
        {
            var defaultCountry = await _countryRepository.GetByCodeAsync("CN");
            country = defaultCountry ?? throw new InvalidOperationException("No country found");
        }
        
        var validation = SmsSubmitValidator.ValidateNumber(number, country);
        if (!validation.IsValid)
            throw new InvalidOperationException(validation.Error);
        
        return (number, country);
    }
    
    private async Task<SmppChannel?> GetAvailableChannelAsync()
    {
        var channels = await _channelRepository.GetActiveChannelsAsync();
        return channels.FirstOrDefault(c => c.IsOnline);
    }
    
    private async Task DeductBalanceAsync(Guid userId, decimal amount)
    {
        var user = await _userRepository.GetByIdAsync(userId);
        if (user == null) return;
        
        var newBalance = user.Balance - amount;
        await _userRepository.UpdateBalanceAsync(userId, newBalance);
        await _redisService.SetUserBalanceCachedAsync(userId, newBalance);
        
        var feeLog = new FeeLog
        {
            UserId = userId,
            Amount = -amount,
            BalanceBefore = user.Balance,
            BalanceAfter = newBalance,
            Description = "SMS Submit"
        };
    }
    
    private static string GenerateRandomChars(int length)
    {
        const string chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        var random = new Random();
        return new string(Enumerable.Range(0, length)
            .Select(_ => chars[random.Next(chars.Length)])
            .ToArray());
    }
    
    private static SmsStatusResponse MapToStatusResponse(SmsMessage message)
    {
        return new SmsStatusResponse(
            message.Id,
            message.ExternalId ?? "",
            message.Status,
            message.StatusMessage,
            message.TotalSegments,
            message.DeliveredSegments,
            message.CreatedAt,
            message.SentAt,
            message.DeliveredAt
        );
    }
}
