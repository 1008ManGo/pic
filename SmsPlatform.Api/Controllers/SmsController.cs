using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using SmsPlatform.Application.DTOs;
using SmsPlatform.Application.Interfaces;

namespace SmsPlatform.Api.Controllers;

[ApiController]
[Route("api/[controller]")]
public class SmsController : ControllerBase
{
    private readonly ISmsService _smsService;
    private readonly ILogger<SmsController> _logger;
    
    public SmsController(ISmsService smsService, ILogger<SmsController> logger)
    {
        _smsService = smsService;
        _logger = logger;
    }
    
    [HttpPost("submit")]
    [Authorize]
    public async Task<ActionResult<SmsSubmitResponse>> SubmitSms(
        [FromBody] SmsSubmitRequest request,
        [FromHeader(Name = "X-User-Id")] Guid userId)
    {
        try
        {
            var result = await _smsService.SubmitSmsAsync(userId, request);
            return Ok(result);
        }
        catch (UnauthorizedAccessException ex)
        {
            _logger.LogWarning(ex, "Unauthorized SMS submission attempt");
            return Unauthorized(ex.Message);
        }
        catch (InvalidOperationException ex)
        {
            _logger.LogWarning(ex, "Invalid SMS submission");
            return BadRequest(ex.Message);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error submitting SMS");
            return StatusCode(500, "An error occurred while processing your request");
        }
    }
    
    [HttpPost("submit/batch")]
    [Authorize]
    [RequestSizeLimit(10_000_000)]
    public async Task<ActionResult<SmsSubmitResponse>> SubmitBatchSms(
        [FromBody] SmsBatchSubmitRequest request,
        [FromHeader(Name = "X-User-Id")] Guid userId)
    {
        try
        {
            var result = await _smsService.SubmitBatchSmsAsync(userId, request);
            return Ok(result);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error submitting batch SMS");
            return StatusCode(500, "An error occurred while processing your request");
        }
    }
    
    [HttpGet("status/{messageId}")]
    [Authorize]
    public async Task<ActionResult<SmsStatusResponse>> GetSmsStatus(
        Guid messageId,
        [FromHeader(Name = "X-User-Id")] Guid userId)
    {
        var result = await _smsService.GetSmsStatusAsync(userId, messageId);
        if (result == null)
            return NotFound();
        
        return Ok(result);
    }
    
    [HttpGet("history")]
    [Authorize]
    public async Task<ActionResult<IEnumerable<SmsStatusResponse>>> GetSmsHistory(
        [FromHeader(Name = "X-User-Id")] Guid userId,
        [FromQuery] int page = 1,
        [FromQuery] int pageSize = 20)
    {
        var results = await _smsService.GetSmsHistoryAsync(userId, page, pageSize);
        return Ok(results);
    }
    
    [HttpPost("resubmit")]
    [Authorize]
    public async Task<ActionResult<IEnumerable<SmsStatusResponse>>> ResubmitFailedSms(
        [FromBody] IEnumerable<Guid> messageIds,
        [FromHeader(Name = "X-User-Id")] Guid userId)
    {
        var results = await _smsService.ResubmitFailedSmsAsync(userId, messageIds);
        return Ok(results);
    }
    
    [HttpPost("encode")]
    [AllowAnonymous]
    public ActionResult<SmsEncodingInfo> CalculateEncoding([FromBody] string content)
    {
        var result = SmsSubmitValidator.CalculateEncoding(content);
        return Ok(result);
    }
}
