namespace SmsPlatform.Domain.Enums;

public enum SmppChannelStatus
{
    Active,
    Inactive,
    Suspended
}

public enum UserStatus
{
    Active,
    Disabled
}

public enum SmsMessageStatus
{
    Pending,
    Queued,
    Sending,
    Sent,
    Delivered,
    Failed,
    Unknown,
    NotSent
}

public enum SmsEncoding
{
    GSM7,
    UCS2
}

public enum MessagePriority
{
    Low = 0,
    Normal = 1,
    High = 2,
    Urgent = 3
}

public enum RechargeStatus
{
    Pending,
    Completed,
    Failed,
    Cancelled
}

public enum AlertType
{
    ChannelOffline,
    HighFailureRate,
    LowBalance,
    QueueOverflow,
    TpsExceeded
}

public enum AlertStatus
{
    Active,
    Resolved,
    Acknowledged
}
