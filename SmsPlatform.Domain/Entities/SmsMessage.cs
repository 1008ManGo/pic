using SmsPlatform.Domain.Enums;

namespace SmsPlatform.Domain.Entities;

public class SmsMessage
{
    public Guid Id { get; set; } = Guid.NewGuid();
    public Guid UserId { get; set; } = Guid.Empty;
    public Guid? SmppChannelId { get; set; }
    public Guid CountryId { get; set; } = Guid.Empty;
    
    public string SenderId { get; set; } = string.Empty;
    public string ReceiverNumber { get; set; } = string.Empty;
    public string Content { get; set; } = string.Empty;
    public string? AppendedRandomChars { get; set; }
    
    public SmsEncoding Encoding { get; set; } = SmsEncoding.GSM7;
    public MessagePriority Priority { get; set; } = MessagePriority.Normal;
    
    public SmsMessageStatus Status { get; set; } = SmsMessageStatus.Pending;
    public string? StatusMessage { get; set; }
    
    public int TotalSegments { get; set; } = 1;
    public int DeliveredSegments { get; set; } = 0;
    
    public string? ExternalId { get; set; }
    public string? ProviderMessageId { get; set; }
    
    public decimal UnitPrice { get; set; } = 0;
    public decimal TotalPrice { get; set; } = 0;
    
    public DateTime CreatedAt { get; set; } = DateTime.UtcNow;
    public DateTime? SentAt { get; set; }
    public DateTime? DeliveredAt { get; set; }
    public DateTime? UpdatedAt { get; set; }
    
    public virtual User? User { get; set; }
    public virtual SmppChannel? SmppChannel { get; set; }
    public virtual Country? Country { get; set; }
    public virtual ICollection<SmsSegment> Segments { get; set; } = new List<SmsSegment>();
}

public class SmsSegment
{
    public Guid Id { get; set; } = Guid.NewGuid();
    public Guid SmsMessageId { get; set; } = Guid.Empty;
    public int SegmentNumber { get; set; } = 1;
    public string Content { get; set; } = string.Empty;
    public string? ProviderMessageId { get; set; }
    public SmsMessageStatus Status { get; set; } = SmsMessageStatus.Pending;
    public DateTime CreatedAt { get; set; } = DateTime.UtcNow;
    public DateTime? SentAt { get; set; }
    public DateTime? DeliveredAt { get; set; }
    
    public virtual SmsMessage? SmsMessage { get; set; }
}

public class BatchSmsJob
{
    public Guid Id { get; set; } = Guid.NewGuid();
    public Guid UserId { get; set; } = Guid.Empty;
    public string FileName { get; set; } = string.Empty;
    public string SenderId { get; set; } = string.Empty;
    public string Content { get; set; } = string.Empty;
    public bool AppendRandomChars { get; set; } = false;
    public MessagePriority Priority { get; set; } = MessagePriority.Normal;
    
    public int TotalNumbers { get; set; } = 0;
    public int ProcessedNumbers { get; set; } = 0;
    public int SuccessCount { get; set; } = 0;
    public int FailureCount { get; set; } = 0;
    
    public BatchJobStatus Status { get; set; } = BatchJobStatus.Pending;
    public DateTime CreatedAt { get; set; } = DateTime.UtcNow;
    public DateTime? CompletedAt { get; set; }
    
    public virtual User? User { get; set; }
}

public enum BatchJobStatus
{
    Pending,
    Processing,
    Completed,
    Failed,
    Cancelled
}
