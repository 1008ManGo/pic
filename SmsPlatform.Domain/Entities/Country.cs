namespace SmsPlatform.Domain.Entities;

public class Country
{
    public Guid Id { get; set; } = Guid.NewGuid();
    public string Code { get; set; } = string.Empty;
    public string Name { get; set; } = string.Empty;
    public string DialCode { get; set; } = string.Empty;
    public int MinLength { get; set; } = 8;
    public int MaxLength { get; set; } = 15;
    public bool IsActive { get; set; } = true;
    
    public virtual ICollection<UserCountryPermission> UserPermissions { get; set; } = new List<UserCountryPermission>();
    public virtual ICollection<CountryPricing> Pricings { get; set; } = new List<CountryPricing>();
}

public class CountryPricing
{
    public Guid Id { get; set; } = Guid.NewGuid();
    public Guid CountryId { get; set; } = Guid.Empty;
    public Guid? UserId { get; set; }
    public decimal PricePerSms { get; set; } = 0.01m;
    public int SegmentSize { get; set; } = 160;
    public int LongMessageSegmentSize { get; set; } = 153;
    public bool IsActive { get; set; } = true;
    public DateTime CreatedAt { get; set; } = DateTime.UtcNow;
    public DateTime? UpdatedAt { get; set; }
    
    public virtual Country? Country { get; set; }
    public virtual User? User { get; set; }
}

public class UserCountryPermission
{
    public Guid Id { get; set; } = Guid.NewGuid();
    public Guid UserId { get; set; } = Guid.Empty;
    public Guid CountryId { get; set; } = Guid.Empty;
    public bool IsAllowed { get; set; } = true;
    public DateTime CreatedAt { get; set; } = DateTime.UtcNow;
    
    public virtual User? User { get; set; }
    public virtual Country? Country { get; set; }
}
