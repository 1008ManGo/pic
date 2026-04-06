using SmsPlatform.Application.DTOs;
using SmsPlatform.Domain.Entities;
using SmsPlatform.Domain.Enums;

namespace SmsPlatform.Tests;

public class EntityTests
{
    [Fact]
    public void SmsMessage_DefaultValues_AreCorrect()
    {
        var message = new SmsMessage();
        
        Assert.Equal(SmsMessageStatus.Pending, message.Status);
        Assert.Equal(MessagePriority.Normal, message.Priority);
        Assert.Equal(SmsEncoding.GSM7, message.Encoding);
        Assert.Equal(1, message.TotalSegments);
        Assert.NotEqual(Guid.Empty, message.Id);
    }
    
    [Fact]
    public void User_DefaultValues_AreCorrect()
    {
        var user = new User();
        
        Assert.Equal(UserStatus.Active, user.Status);
        Assert.Equal(0, user.Balance);
        Assert.False(user.AllowCustomSenderId);
        Assert.NotEqual(Guid.Empty, user.Id);
    }
    
    [Fact]
    public void SmppChannel_DefaultValues_AreCorrect()
    {
        var channel = new SmppChannel();
        
        Assert.Equal(SmppChannelStatus.Active, channel.Status);
        Assert.Equal(100, channel.MaxTps);
        Assert.Equal(10, channel.MaxBindCount);
        Assert.Equal(30, channel.HeartbeatInterval);
        Assert.Equal(5, channel.ReconnectDelay);
        Assert.False(channel.IsDefault);
    }
    
    [Fact]
    public void Country_DefaultValues_AreCorrect()
    {
        var country = new Country();
        
        Assert.True(country.IsActive);
        Assert.NotEqual(Guid.Empty, country.Id);
    }
    
    [Fact]
    public void SmsMessage_AddSegment_IncreasesCount()
    {
        var message = new SmsMessage();
        var segment = new SmsSegment
        {
            SmsMessageId = message.Id,
            SegmentNumber = 1,
            Content = "Part 1"
        };
        message.Segments.Add(segment);
        
        Assert.Single(message.Segments);
    }
    
    [Fact]
    public void User_AddApiKey_IncreasesCount()
    {
        var user = new User();
        var apiKey = new ApiKey
        {
            UserId = user.Id,
            Name = "Test Key",
            Key = "test-key-123"
        };
        user.ApiKeys.Add(apiKey);
        
        Assert.Single(user.ApiKeys);
    }
}
