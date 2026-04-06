using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using SmsPlatform.Application.DTOs;
using SmsPlatform.Application.Interfaces;
using SmsPlatform.Domain.Entities;

namespace SmsPlatform.Api.Controllers;

[ApiController]
[Route("api/[controller]")]
[Authorize(Roles = "Admin")]
public class AdminController : ControllerBase
{
    private readonly IPricingService _pricingService;
    private readonly IFinanceService _financeService;
    private readonly ISenderIdService _senderIdService;
    private readonly IAlertService _alertService;
    private readonly ILogger<AdminController> _logger;
    
    public AdminController(
        IPricingService pricingService,
        IFinanceService financeService,
        ISenderIdService senderIdService,
        IAlertService alertService,
        ILogger<AdminController> logger)
    {
        _pricingService = pricingService;
        _financeService = financeService;
        _senderIdService = senderIdService;
        _alertService = alertService;
        _logger = logger;
    }
    
    [HttpGet("countries")]
    public async Task<ActionResult<IEnumerable<Country>>> GetAllCountries()
    {
        var results = await _pricingService.GetAllCountriesAsync();
        return Ok(results);
    }
    
    [HttpPost("pricing")]
    public async Task<ActionResult<CountryPricing>> SetCountryPricing([FromBody] CountryPricingRequest request)
    {
        var result = await _pricingService.SetCountryPricingAsync(
            request.CountryId,
            request.PricePerSms);
        return Ok(result);
    }
    
    [HttpGet("pricing/{countryId}")]
    public async Task<ActionResult<CountryPricing>> GetCountryPricing(
        Guid countryId,
        [FromQuery] Guid? userId = null)
    {
        var result = await _pricingService.GetCountryPricingAsync(countryId, userId);
        if (result == null)
            return NotFound();
        
        return Ok(result);
    }
    
    [HttpGet("user/{userId}/pricing")]
    public async Task<ActionResult<IEnumerable<CountryPricing>>> GetUserPricings(Guid userId)
    {
        var results = await _pricingService.GetUserPricingsAsync(userId);
        return Ok(results);
    }
    
    [HttpPost("recharge")]
    public async Task<ActionResult<RechargeResponse>> Recharge([FromBody] RechargeRequest request)
    {
        var result = await _financeService.RechargeAsync(request);
        return Ok(result);
    }
    
    [HttpPost("finance/report")]
    public async Task<ActionResult<FinanceReportResponse>> GenerateFinanceReport(
        [FromBody] FinanceReportRequest request)
    {
        var result = await _financeService.GenerateFinanceReportAsync(request);
        return Ok(result);
    }
    
    [HttpPost("sms/report")]
    public async Task<ActionResult<SmsReportResponse>> GenerateSmsReport([FromBody] SmsReportRequest request)
    {
        var result = await _financeService.GenerateFinanceReportAsync(
            new FinanceReportRequest(request.UserId, request.FromDate, request.ToDate));
        
        return Ok(result);
    }
    
    [HttpPost("senderid")]
    public async Task<ActionResult<SenderIdResponse>> CreateSenderId([FromBody] SenderIdCreateRequest request)
    {
        var result = await _senderIdService.CreateSenderIdAsync(request);
        return CreatedAtAction(nameof(GetUserSenderIds), new { userId = request.UserId }, result);
    }
    
    [HttpGet("user/{userId}/senderids")]
    public async Task<ActionResult<IEnumerable<SenderIdResponse>>> GetUserSenderIds(Guid userId)
    {
        var results = await _senderIdService.GetUserSenderIdsAsync(userId);
        return Ok(results);
    }
    
    [HttpPut("senderid/{senderId}/approve")]
    public async Task<ActionResult> ApproveSenderId(Guid senderId)
    {
        await _senderIdService.ApproveSenderIdAsync(senderId);
        return NoContent();
    }
    
    [HttpPut("senderid/{senderId}/default")]
    public async Task<ActionResult> SetDefaultSenderId(
        Guid senderId,
        [FromQuery] Guid userId)
    {
        var result = await _senderIdService.SetDefaultSenderIdAsync(userId, senderId);
        return Ok(result);
    }
    
    [HttpDelete("senderid/{senderId}")]
    public async Task<ActionResult> DeleteSenderId(Guid senderId)
    {
        await _senderIdService.DeleteSenderIdAsync(senderId);
        return NoContent();
    }
    
    [HttpGet("alerts")]
    public async Task<ActionResult<IEnumerable<Alert>>> GetActiveAlerts()
    {
        var results = await _alertService.GetActiveAlertsAsync();
        return Ok(results);
    }
    
    [HttpPut("alerts/{alertId}/acknowledge")]
    public async Task<ActionResult> AcknowledgeAlert(Guid alertId)
    {
        await _alertService.AcknowledgeAlertAsync(alertId);
        return NoContent();
    }
    
    [HttpPut("alerts/{alertId}/resolve")]
    public async Task<ActionResult> ResolveAlert(Guid alertId)
    {
        await _alertService.ResolveAlertAsync(alertId);
        return NoContent();
    }
}
