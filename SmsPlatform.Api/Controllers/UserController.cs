using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using SmsPlatform.Application.DTOs;
using SmsPlatform.Application.Interfaces;

namespace SmsPlatform.Api.Controllers;

[ApiController]
[Route("api/[controller]")]
public class UserController : ControllerBase
{
    private readonly IUserService _userService;
    private readonly ILogger<UserController> _logger;
    
    public UserController(IUserService userService, ILogger<UserController> logger)
    {
        _userService = userService;
        _logger = logger;
    }
    
    [HttpPost("register")]
    [AllowAnonymous]
    public async Task<ActionResult<UserResponse>> Register([FromBody] UserCreateRequest request)
    {
        try
        {
            var result = await _userService.CreateUserAsync(request);
            return CreatedAtAction(nameof(GetUser), new { id = result.Id }, result);
        }
        catch (InvalidOperationException ex)
        {
            return BadRequest(ex.Message);
        }
    }
    
    [HttpPost("login")]
    [AllowAnonymous]
    public async Task<ActionResult<LoginResponse>> Login([FromBody] LoginRequest request)
    {
        try
        {
            var result = await _userService.LoginAsync(request);
            return Ok(result);
        }
        catch (UnauthorizedAccessException ex)
        {
            return Unauthorized(ex.Message);
        }
    }
    
    [HttpGet("{id}")]
    [Authorize]
    public async Task<ActionResult<UserResponse>> GetUser(Guid id)
    {
        var result = await _userService.GetUserByIdAsync(id);
        if (result == null)
            return NotFound();
        
        return Ok(result);
    }
    
    [HttpGet("")]
    [Authorize(Roles = "Admin")]
    public async Task<ActionResult<IEnumerable<UserResponse>>> GetAllUsers(
        [FromQuery] int page = 1,
        [FromQuery] int pageSize = 20)
    {
        var results = await _userService.GetAllUsersAsync(page, pageSize);
        return Ok(results);
    }
    
    [HttpPut("{id}")]
    [Authorize(Roles = "Admin")]
    public async Task<ActionResult<UserResponse>> UpdateUser(
        Guid id,
        [FromBody] UserUpdateRequest request)
    {
        if (id != request.Id)
            return BadRequest("ID mismatch");
        
        try
        {
            var result = await _userService.UpdateUserAsync(request);
            return Ok(result);
        }
        catch (InvalidOperationException ex)
        {
            return BadRequest(ex.Message);
        }
    }
    
    [HttpGet("{userId}/balance")]
    [Authorize]
    public async Task<ActionResult<BalanceResponse>> GetBalance(
        Guid userId,
        [FromHeader(Name = "X-User-Id")] Guid requestUserId)
    {
        if (userId != requestUserId)
            return Unauthorized();
        
        var balance = await _userService.GetUserBalanceAsync(userId);
        return Ok(new BalanceResponse(userId, balance, DateTime.UtcNow));
    }
    
    [HttpPost("{userId}/apikeys")]
    [Authorize]
    public async Task<ActionResult<ApiKeyResponse>> CreateApiKey(
        Guid userId,
        [FromQuery] string name,
        [FromHeader(Name = "X-User-Id")] Guid requestUserId)
    {
        if (userId != requestUserId)
            return Unauthorized();
        
        var result = await _userService.CreateApiKeyAsync(userId, name);
        return CreatedAtAction(nameof(GetUserApiKeys), new { userId }, result);
    }
    
    [HttpGet("{userId}/apikeys")]
    [Authorize]
    public async Task<ActionResult<IEnumerable<ApiKeyResponse>>> GetUserApiKeys(
        Guid userId,
        [FromHeader(Name = "X-User-Id")] Guid requestUserId)
    {
        if (userId != requestUserId)
            return Unauthorized();
        
        var results = await _userService.GetUserApiKeysAsync(userId);
        return Ok(results);
    }
    
    [HttpDelete("{userId}/apikeys/{apiKeyId}")]
    [Authorize]
    public async Task<ActionResult> RevokeApiKey(
        Guid userId,
        Guid apiKeyId,
        [FromHeader(Name = "X-User-Id")] Guid requestUserId)
    {
        if (userId != requestUserId)
            return Unauthorized();
        
        await _userService.RevokeApiKeyAsync(apiKeyId);
        return NoContent();
    }
}
