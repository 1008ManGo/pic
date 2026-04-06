using SmsPlatform.Domain.Enums;

namespace SmsPlatform.Domain.Entities;

public class SystemSetting
{
    public Guid Id { get; set; } = Guid.NewGuid();
    public string Key { get; set; } = string.Empty;
    public string Value { get; set; } = string.Empty;
    public string? Description { get; set; }
    public DateTime CreatedAt { get; set; } = DateTime.UtcNow;
    public DateTime? UpdatedAt { get; set; }
}

public class AlertPolicy
{
    public Guid Id { get; set; } = Guid.NewGuid();
    public string Name { get; set; } = string.Empty;
    public AlertType AlertType { get; set; }
    public string Condition { get; set; } = string.Empty;
    public decimal Threshold { get; set; } = 0;
    public int TimeWindowMinutes { get; set; } = 5;
    public bool IsEnabled { get; set; } = true;
    public string? NotificationEmails { get; set; }
    public string? NotificationWebhooks { get; set; }
    public DateTime CreatedAt { get; set; } = DateTime.UtcNow;
    public DateTime? UpdatedAt { get; set; }
}

public class Alert
{
    public Guid Id { get; set; } = Guid.NewGuid();
    public Guid? SmppChannelId { get; set; }
    public Guid? UserId { get; set; }
    public AlertType Type { get; set; }
    public string Message { get; set; } = string.Empty;
    public AlertStatus Status { get; set; } = AlertStatus.Active;
    public DateTime CreatedAt { get; set; } = DateTime.UtcNow;
    public DateTime? ResolvedAt { get; set; }
    
    public virtual SmppChannel? SmppChannel { get; set; }
    public virtual User? User { get; set; }
}

public class ApiCallLog
{
    public Guid Id { get; set; } = Guid.NewGuid();
    public Guid? UserId { get; set; }
    public Guid? ApiKeyId { get; set; }
    public string Endpoint { get; set; } = string.Empty;
    public string Method { get; set; } = string.Empty;
    public string? RequestBody { get; set; }
    public string? ResponseBody { get; set; }
    public int StatusCode { get; set; } = 200;
    public string? IpAddress { get; set; }
    public string? UserAgent { get; set; }
    public DateTime CreatedAt { get; set; } = DateTime.UtcNow;
    
    public virtual User? User { get; set; }
    public virtual ApiKey? ApiKey { get; set; }
}
