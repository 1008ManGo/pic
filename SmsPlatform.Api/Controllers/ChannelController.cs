using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using SmsPlatform.Application.DTOs;
using SmsPlatform.Application.Interfaces;

namespace SmsPlatform.Api.Controllers;

[ApiController]
[Route("api/[controller]")]
[Authorize(Roles = "Admin")]
public class ChannelController : ControllerBase
{
    private readonly IChannelService _channelService;
    private readonly ILogger<ChannelController> _logger;
    
    public ChannelController(IChannelService channelService, ILogger<ChannelController> logger)
    {
        _channelService = channelService;
        _logger = logger;
    }
    
    [HttpPost]
    public async Task<ActionResult<ChannelStatusResponse>> CreateChannel([FromBody] ChannelCreateRequest request)
    {
        var result = await _channelService.CreateChannelAsync(request);
        return CreatedAtAction(nameof(GetChannel), new { id = result.Id }, result);
    }
    
    [HttpGet("{id}")]
    public async Task<ActionResult<ChannelStatusResponse>> GetChannel(Guid id)
    {
        var result = await _channelService.GetChannelByIdAsync(id);
        if (result == null)
            return NotFound();
        
        return Ok(result);
    }
    
    [HttpGet]
    public async Task<ActionResult<IEnumerable<ChannelStatusResponse>>> GetAllChannels()
    {
        var results = await _channelService.GetAllChannelsAsync();
        return Ok(results);
    }
    
    [HttpGet("active")]
    public async Task<ActionResult<IEnumerable<ChannelStatusResponse>>> GetActiveChannels()
    {
        var results = await _channelService.GetActiveChannelsAsync();
        return Ok(results);
    }
    
    [HttpPut]
    public async Task<ActionResult<ChannelStatusResponse>> UpdateChannel([FromBody] ChannelUpdateRequest request)
    {
        try
        {
            var result = await _channelService.UpdateChannelAsync(request);
            return Ok(result);
        }
        catch (InvalidOperationException ex)
        {
            return BadRequest(ex.Message);
        }
    }
    
    [HttpDelete("{id}")]
    public async Task<ActionResult> DeleteChannel(Guid id)
    {
        await _channelService.DeleteChannelAsync(id);
        return NoContent();
    }
    
    [HttpGet("statuses")]
    public async Task<ActionResult<Dictionary<Guid, ChannelStatusResponse>>> GetAllChannelStatuses()
    {
        var results = await _channelService.GetAllChannelStatusesAsync();
        return Ok(results);
    }
}
