using SmsPlatform.Application.DTOs;
using SmsPlatform.Domain.Enums;

namespace SmsPlatform.Tests;

public class SmsEncodingTests
{
    [Fact]
    public void CalculateEncoding_Gsm7Only_ReturnsGsm7Encoding()
    {
        var content = "Hello World";
        
        var result = SmsSubmitValidator.CalculateEncoding(content);
        
        Assert.Equal(SmsEncoding.GSM7, result.Encoding);
        Assert.False(result.HasExtendedChars);
    }
    
    [Fact]
    public void CalculateEncoding_WithExtendedChars_ReturnsUcs2Encoding()
    {
        var content = "Hello {world}";
        
        var result = SmsSubmitValidator.CalculateEncoding(content);
        
        Assert.Equal(SmsEncoding.UCS2, result.Encoding);
        Assert.True(result.HasExtendedChars);
    }
    
    [Fact]
    public void CalculateEncoding_WithChinese_ReturnsUcs2Encoding()
    {
        var content = "你好世界";
        
        var result = SmsSubmitValidator.CalculateEncoding(content);
        
        Assert.Equal(SmsEncoding.UCS2, result.Encoding);
    }
    
    [Fact]
    public void CalculateEncoding_ShortMessage_ReturnsSingleSegment()
    {
        var content = "Hello";
        
        var result = SmsSubmitValidator.CalculateEncoding(content);
        
        Assert.Equal(1, result.TotalSegments);
        Assert.Equal(153, result.SegmentSize);
    }
    
    [Fact]
    public void CalculateEncoding_LongMessage_ReturnsMultipleSegments()
    {
        var content = new string('A', 200);
        
        var result = SmsSubmitValidator.CalculateEncoding(content);
        
        Assert.True(result.TotalSegments > 1);
    }
    
    [Fact]
    public void CalculateEncoding_EmptyContent_ReturnsOneSegment()
    {
        var content = "";
        
        var result = SmsSubmitValidator.CalculateEncoding(content);
        
        Assert.Equal(1, result.TotalSegments);
    }
    
    [Fact]
    public void ValidateNumber_ValidNumber_ReturnsTrue()
    {
        var result = SmsSubmitValidator.ValidateNumber("+8613800138000", null);
        
        Assert.True(result.IsValid);
    }
    
    [Fact]
    public void ValidateNumber_EmptyNumber_ReturnsFalse()
    {
        var result = SmsSubmitValidator.ValidateNumber("", null);
        
        Assert.False(result.IsValid);
    }
    
    [Fact]
    public void ValidateNumber_WithSpaces_NormalizesNumber()
    {
        var result = SmsSubmitValidator.ValidateNumber("+86 138 0013 8000", null);
        
        Assert.True(result.IsValid);
    }
}
