using SmsPlatform.Domain.Entities;
using SmsPlatform.Domain.Enums;

namespace SmsPlatform.Application.DTOs;

public record SmsSubmitRequest(
    string SenderId,
    string ReceiverNumber,
    string Content,
    MessagePriority Priority = MessagePriority.Normal,
    bool AppendRandomChars = false
);

public record SmsBatchSubmitRequest(
    string SenderId,
    string FileContent,
    string Content,
    MessagePriority Priority = MessagePriority.Normal,
    bool AppendRandomChars = false
);

public record SmsSubmitResponse(
    Guid MessageId,
    string ExternalId,
    int TotalSegments,
    decimal TotalPrice,
    string Status
);

public record SmsStatusQuery(
    Guid MessageId
);

public record SmsStatusResponse(
    Guid MessageId,
    string ExternalId,
    SmsMessageStatus Status,
    string? StatusMessage,
    int TotalSegments,
    int DeliveredSegments,
    DateTime CreatedAt,
    DateTime? SentAt,
    DateTime? DeliveredAt
);

public record SmsEncodingInfo(
    SmsEncoding Encoding,
    int CharacterCount,
    int SegmentSize,
    int TotalSegments,
    bool HasExtendedChars
);

public class SmsSubmitValidator
{
    public static (bool IsValid, string? Error) ValidateNumber(string number, Country? country)
    {
        if (string.IsNullOrWhiteSpace(number))
            return (false, "Phone number is required");
        
        number = number.Trim().Replace(" ", "").Replace("-", "");
        
        if (number.StartsWith("+"))
        {
            var digits = number[1..];
            if (!digits.All(char.IsDigit))
                return (false, "Invalid phone number format");
        }
        else if (!number.All(char.IsDigit))
        {
            return (false, "Phone number must contain only digits");
        }
        
        if (country != null)
        {
            var digits = number.StartsWith("+") ? number[1..] : number;
            if (digits.Length < country.MinLength || digits.Length > country.MaxLength)
                return (false, $"Phone number length must be between {country.MinLength} and {country.MaxLength} digits for this country");
        }
        
        return (true, null);
    }
    
    public static SmsEncodingInfo CalculateEncoding(string content)
    {
        bool hasExtendedChars = HasGsm7ExtendedChars(content);
        bool isPureGsm7 = IsGsm7Only(content) && !hasExtendedChars;
        
        SmsEncoding encoding = isPureGsm7 ? SmsEncoding.GSM7 : SmsEncoding.UCS2;
        
        int segmentSize = encoding == SmsEncoding.GSM7 ? 153 : 67;
        
        int totalSegments = (int)Math.Ceiling((double)content.Length / segmentSize);
        
        return new SmsEncodingInfo(
            encoding,
            content.Length,
            segmentSize,
            totalSegments > 0 ? totalSegments : 1,
            hasExtendedChars
        );
    }
    
    private static bool IsGsm7Only(string content)
    {
        string gsm7Chars = "@£$¥èéùìò\r\nÇØøÅøÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";
        return content.All(c => gsm7Chars.Contains(c));
    }
    
    private static bool HasGsm7ExtendedChars(string content)
    {
        string extendedChars = "^{}\\[~]|€";
        return content.Any(c => extendedChars.Contains(c));
    }
}

public record ChannelCreateRequest(
    string Name,
    string Host,
    int Port,
    string Username,
    string Password,
    int MaxTps = 100,
    int MaxBindCount = 10,
    string? SystemType = null,
    int HeartbeatInterval = 30,
    int ReconnectDelay = 5,
    bool IsDefault = false
);

public record ChannelUpdateRequest(
    Guid Id,
    string Name,
    string Host,
    int Port,
    string Username,
    string Password,
    int MaxTps,
    int MaxBindCount,
    string? SystemType,
    int HeartbeatInterval,
    int ReconnectDelay,
    SmppChannelStatus Status
);

public record ChannelStatusResponse(
    Guid Id,
    string Name,
    bool IsOnline,
    int CurrentBindCount,
    int MaxBindCount,
    int CurrentTps,
    int MaxTps,
    long QueueLength,
    double SuccessRate,
    double FailureRate,
    SmppChannelStatus Status
);

public record UserCreateRequest(
    string Username,
    string Password,
    string Email,
    string? CompanyName = null,
    decimal InitialBalance = 0
);

public record UserUpdateRequest(
    Guid Id,
    string Email,
    string? CompanyName,
    UserStatus Status,
    bool AllowCustomSenderId
);

public record UserResponse(
    Guid Id,
    string Username,
    string Email,
    string? CompanyName,
    UserStatus Status,
    decimal Balance,
    bool AllowCustomSenderId,
    DateTime CreatedAt
);

public record CountryPricingRequest(
    Guid CountryId,
    decimal PricePerSms,
    int SegmentSize = 160,
    int LongMessageSegmentSize = 153
);

public record UserCountryPermissionRequest(
    Guid UserId,
    Guid CountryId,
    bool IsAllowed
);

public record SenderIdCreateRequest(
    Guid UserId,
    string SenderIdValue,
    bool IsDefault = false
);

public record SenderIdResponse(
    Guid Id,
    string SenderIdValue,
    bool IsApproved,
    bool IsDefault,
    DateTime CreatedAt
);

public record RechargeRequest(
    Guid UserId,
    decimal Amount,
    string? PaymentMethod = null,
    string? Notes = null
);

public record RechargeResponse(
    Guid Id,
    decimal Amount,
    decimal BalanceBefore,
    decimal BalanceAfter,
    RechargeStatus Status,
    DateTime CreatedAt
);

public record BalanceResponse(
    Guid UserId,
    decimal Balance,
    DateTime UpdatedAt
);

public record LoginRequest(
    string Username,
    string Password
);

public record LoginResponse(
    string AccessToken,
    string TokenType,
    int ExpiresIn,
    UserResponse User
);

public record ApiKeyResponse(
    Guid Id,
    string Name,
    string Key,
    bool IsActive,
    DateTime CreatedAt,
    DateTime? ExpiresAt,
    DateTime? LastUsedAt
);

public record SmsReportRequest(
    Guid? UserId,
    Guid? CountryId,
    DateTime FromDate,
    DateTime ToDate,
    SmsMessageStatus? Status = null
);

public record SmsReportResponse(
    int TotalMessages,
    int SuccessCount,
    int FailureCount,
    int PendingCount,
    decimal TotalRevenue,
    Dictionary<string, int> MessagesByCountry,
    Dictionary<string, int> MessagesByDay
);

public record FinanceReportRequest(
    Guid? UserId,
    DateTime FromDate,
    DateTime ToDate
);

public record FinanceReportResponse(
    decimal TotalRecharge,
    decimal TotalExpense,
    decimal NetBalance,
    List<RechargeRecord> RechargeRecords,
    List<FeeLog> FeeLogs
);
