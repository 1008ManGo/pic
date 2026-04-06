using SmsPlatform.Domain.Enums;

namespace SmsPlatform.Domain.Entities;

public class SmppChannel
{
    public Guid Id { get; set; } = Guid.NewGuid();
    public string Name { get; set; } = string.Empty;
    public string Host { get; set; } = string.Empty;
    public int Port { get; set; }
    public string Username { get; set; } = string.Empty;
    public string Password { get; set; } = string.Empty;
    public int MaxTps { get; set; } = 100;
    public int MaxBindCount { get; set; } = 10;
    public string? SystemType { get; set; }
    public int HeartbeatInterval { get; set; } = 30;
    public int ReconnectDelay { get; set; } = 5;
    public SmppChannelStatus Status { get; set; } = SmppChannelStatus.Active;
    public bool IsDefault { get; set; } = false;
    public DateTime CreatedAt { get; set; } = DateTime.UtcNow;
    public DateTime? UpdatedAt { get; set; }
    
    public virtual ICollection<SmsMessage> SmsMessages { get; set; } = new List<SmsMessage>();
    
    public bool IsOnline { get; set; } = false;
    public int CurrentBindCount { get; set; } = 0;
    public int CurrentTps { get; set; } = 0;
    public long QueueLength { get; set; } = 0;
    public double SuccessRate { get; set; } = 100.0;
    public double FailureRate { get; set; } = 0.0;
}
