using SmsPlatform.Domain.Enums;

namespace SmsPlatform.Domain.Entities;

public class SenderId
{
    public Guid Id { get; set; } = Guid.NewGuid();
    public Guid UserId { get; set; } = Guid.Empty;
    public string SenderIdValue { get; set; } = string.Empty;
    public bool IsApproved { get; set; } = false;
    public bool IsDefault { get; set; } = false;
    public DateTime CreatedAt { get; set; } = DateTime.UtcNow;
    public DateTime? ApprovedAt { get; set; }
    
    public virtual User? User { get; set; }
}

public class RechargeRecord
{
    public Guid Id { get; set; } = Guid.NewGuid();
    public Guid UserId { get; set; } = Guid.Empty;
    public decimal Amount { get; set; } = 0;
    public decimal BalanceBefore { get; set; } = 0;
    public decimal BalanceAfter { get; set; } = 0;
    public RechargeStatus Status { get; set; } = RechargeStatus.Pending;
    public string? TransactionId { get; set; }
    public string? PaymentMethod { get; set; }
    public string? Notes { get; set; }
    public DateTime CreatedAt { get; set; } = DateTime.UtcNow;
    public DateTime? CompletedAt { get; set; }
    
    public virtual User? User { get; set; }
}

public class FeeLog
{
    public Guid Id { get; set; } = Guid.NewGuid();
    public Guid UserId { get; set; } = Guid.Empty;
    public Guid? SmsMessageId { get; set; }
    public Guid CountryId { get; set; } = Guid.Empty;
    public decimal Amount { get; set; } = 0;
    public decimal BalanceBefore { get; set; } = 0;
    public decimal BalanceAfter { get; set; } = 0;
    public string Description { get; set; } = string.Empty;
    public DateTime CreatedAt { get; set; } = DateTime.UtcNow;
    
    public virtual User? User { get; set; }
    public virtual SmsMessage? SmsMessage { get; set; }
}
