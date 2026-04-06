using SmsPlatform.Domain.Enums;

namespace SmsPlatform.Domain.Entities;

public class User
{
    public Guid Id { get; set; } = Guid.NewGuid();
    public string Username { get; set; } = string.Empty;
    public string PasswordHash { get; set; } = string.Empty;
    public string Email { get; set; } = string.Empty;
    public string? CompanyName { get; set; }
    public UserStatus Status { get; set; } = UserStatus.Active;
    public decimal Balance { get; set; } = 0;
    public bool AllowCustomSenderId { get; set; } = false;
    public DateTime CreatedAt { get; set; } = DateTime.UtcNow;
    public DateTime? UpdatedAt { get; set; }
    
    public virtual ICollection<UserCountryPermission> CountryPermissions { get; set; } = new List<UserCountryPermission>();
    public virtual ICollection<ApiKey> ApiKeys { get; set; } = new List<ApiKey>();
    public virtual ICollection<SmsMessage> SmsMessages { get; set; } = new List<SmsMessage>();
    public virtual ICollection<RechargeRecord> RechargeRecords { get; set; } = new List<RechargeRecord>();
    public virtual ICollection<SenderId> SenderIds { get; set; } = new List<SenderId>();
}

public class ApiKey
{
    public Guid Id { get; set; } = Guid.NewGuid();
    public Guid UserId { get; set; } = Guid.Empty;
    public string Key { get; set; } = string.Empty;
    public string? Secret { get; set; }
    public string Name { get; set; } = string.Empty;
    public bool IsActive { get; set; } = true;
    public DateTime CreatedAt { get; set; } = DateTime.UtcNow;
    public DateTime? ExpiresAt { get; set; }
    public DateTime? LastUsedAt { get; set; }
    
    public virtual User? User { get; set; }
}
