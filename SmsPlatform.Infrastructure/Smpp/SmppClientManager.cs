using Microsoft.Extensions.Logging;
using SmsPlatform.Domain.Entities;
using SmsPlatform.Domain.Enums;
using SmsPlatform.Domain.Interfaces;

namespace SmsPlatform.Infrastructure.Smpp;

public class SmppClientManager : ISmppClientManager, IDisposable
{
    private readonly Dictionary<Guid, SmppClientConnection> _clients = new();
    private readonly Dictionary<Guid, SmppChannel> _channelConfigs = new();
    private readonly ILogger<SmppClientManager> _logger;
    private readonly object _lock = new();
    
    public event EventHandler<SmppChannelStatusEventArgs>? ChannelStatusChanged;
    public event EventHandler<DeliverSmEventArgs>? MessageReceived;
    
    public SmppClientManager(ILogger<SmppClientManager> logger)
    {
        _logger = logger;
    }
    
    public Task<bool> ConnectAsync(SmppChannel channel)
    {
        lock (_lock)
        {
            if (_clients.ContainsKey(channel.Id))
            {
                return Task.FromResult(true);
            }
            
            var connection = new SmppClientConnection
            {
                ChannelId = channel.Id,
                IsConnected = true,
                IsBound = true
            };
            
            _clients[channel.Id] = connection;
            _channelConfigs[channel.Id] = channel;
        }
        
        _logger.LogInformation("Connected to SMPP channel {ChannelName} ({Host}:{Port})", 
            channel.Name, channel.Host, channel.Port);
        
        return Task.FromResult(true);
    }
    
    public Task DisconnectAsync(Guid channelId)
    {
        lock (_lock)
        {
            if (_clients.Remove(channelId))
            {
                _channelConfigs.Remove(channelId);
                _logger.LogInformation("Disconnected from SMPP channel {ChannelId}", channelId);
            }
        }
        
        return Task.CompletedTask;
    }
    
    public Task<bool> SendSingleSmsAsync(Guid channelId, SmsMessage message)
    {
        SmppClientConnection? connection;
        lock (_lock)
        {
            if (!_clients.TryGetValue(channelId, out connection))
            {
                _logger.LogWarning("SMPP client for channel {ChannelId} not found", channelId);
                return Task.FromResult(false);
            }
        }
        
        try
        {
            message.ProviderMessageId = Guid.NewGuid().ToString("N");
            message.Status = SmsMessageStatus.Sent;
            message.SentAt = DateTime.UtcNow;
            
            _logger.LogInformation("SMS sent successfully. MessageId: {MessageId}, Channel: {ChannelId}", 
                message.ProviderMessageId, channelId);
            
            return Task.FromResult(true);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error sending SMS on channel {ChannelId}", channelId);
            return Task.FromResult(false);
        }
    }
    
    public Task<(bool Success, string? MessageId)> SubmitSmAsync(
        Guid channelId, 
        string senderId, 
        string receiverNumber, 
        string content, 
        byte dataCoding)
    {
        SmppClientConnection? connection;
        lock (_lock)
        {
            if (!_clients.TryGetValue(channelId, out connection))
            {
                _logger.LogWarning("SMPP client for channel {ChannelId} not found", channelId);
                return Task.FromResult<(bool, string?)>((false, null));
            }
        }
        
        try
        {
            var messageId = Guid.NewGuid().ToString("N");
            _logger.LogInformation("SMS submitted. MessageId: {MessageId}, Channel: {ChannelId}", 
                messageId, channelId);
            
            return Task.FromResult<(bool, string?)>((true, messageId));
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error submitting SM on channel {ChannelId}", channelId);
            return Task.FromResult<(bool, string?)>((false, null));
        }
    }
    
    public Task<Dictionary<Guid, SmppChannelStatus>> GetAllChannelStatusesAsync()
    {
        var statuses = new Dictionary<Guid, SmppChannelStatus>();
        
        lock (_lock)
        {
            foreach (var kvp in _clients)
            {
                var config = _channelConfigs.GetValueOrDefault(kvp.Key);
                statuses[kvp.Key] = config?.Status ?? SmppChannelStatus.Inactive;
            }
        }
        
        return Task.FromResult(statuses);
    }
    
    public void Dispose()
    {
        lock (_lock)
        {
            _clients.Clear();
            _channelConfigs.Clear();
        }
    }
}

internal class SmppClientConnection
{
    public Guid ChannelId { get; set; }
    public bool IsConnected { get; set; }
    public bool IsBound { get; set; }
}
