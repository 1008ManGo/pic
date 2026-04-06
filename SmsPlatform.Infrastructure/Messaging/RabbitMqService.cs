using System.Text;
using System.Text.Json;
using Microsoft.Extensions.Logging;
using RabbitMQ.Client;
using RabbitMQ.Client.Events;
using SmsPlatform.Domain.Entities;
using SmsPlatform.Domain.Interfaces;

namespace SmsPlatform.Infrastructure.Messaging;

public class RabbitMqService : IQueueService, IDisposable
{
    private readonly IConnection? _connection;
    private readonly IModel? _channel;
    private readonly ILogger<RabbitMqService> _logger;
    private const string SmsQueueName = "sms_messages";
    private const string BatchQueueName = "batch_jobs";
    private const string DeliveryReportQueueName = "delivery_reports";
    
    public RabbitMqService(ILogger<RabbitMqService> logger, string? connectionString = null)
    {
        _logger = logger;
        
        try
        {
            var factory = new ConnectionFactory
            {
                HostName = connectionString ?? "localhost",
                Port = 5672,
                UserName = "guest",
                Password = "guest"
            };
            
            _connection = factory.CreateConnection();
            _channel = _connection.CreateModel();
            
            _channel.QueueDeclare(SmsQueueName, durable: true, exclusive: false, autoDelete: false);
            _channel.QueueDeclare(BatchQueueName, durable: true, exclusive: false, autoDelete: false);
            _channel.QueueDeclare(DeliveryReportQueueName, durable: true, exclusive: false, autoDelete: false);
            
            _logger.LogInformation("RabbitMQ connection established");
        }
        catch (Exception ex)
        {
            _logger.LogWarning(ex, "Failed to connect to RabbitMQ. Queue services will be disabled");
        }
    }
    
    public Task PublishSmsMessageAsync(SmsMessage message)
    {
        if (_channel == null)
        {
            _logger.LogWarning("RabbitMQ channel is not available");
            return Task.CompletedTask;
        }
        
        try
        {
            var json = JsonSerializer.Serialize(message);
            var body = Encoding.UTF8.GetBytes(json);
            
            var properties = _channel.CreateBasicProperties();
            properties.Persistent = true;
            properties.Priority = (byte)message.Priority;
            
            _channel.BasicPublish("", SmsQueueName, properties, body);
            
            _logger.LogDebug("Published SMS message {MessageId} to queue", message.Id);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error publishing SMS message {MessageId}", message.Id);
        }
        
        return Task.CompletedTask;
    }
    
    public Task<SmsMessage?> ConsumeSmsMessageAsync()
    {
        if (_channel == null)
        {
            return Task.FromResult<SmsMessage?>(null);
        }
        
        try
        {
            var result = _channel.BasicGet(SmsQueueName, autoAck: false);
            if (result != null)
            {
                var json = Encoding.UTF8.GetString(result.Body.ToArray());
                var message = JsonSerializer.Deserialize<SmsMessage>(json);
                _channel.BasicAck(result.DeliveryTag, false);
                return Task.FromResult(message);
            }
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error consuming SMS message");
        }
        
        return Task.FromResult<SmsMessage?>(null);
    }
    
    public Task PublishBatchJobAsync(BatchSmsJob job, IEnumerable<string> numbers)
    {
        if (_channel == null)
        {
            return Task.CompletedTask;
        }
        
        try
        {
            var batchMessage = new
            {
                Job = job,
                Numbers = numbers
            };
            
            var json = JsonSerializer.Serialize(batchMessage);
            var body = Encoding.UTF8.GetBytes(json);
            
            var properties = _channel.CreateBasicProperties();
            properties.Persistent = true;
            
            _channel.BasicPublish("", BatchQueueName, properties, body);
            
            _logger.LogInformation("Published batch job {JobId} with {Count} numbers", job.Id, numbers.Count());
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error publishing batch job {JobId}", job.Id);
        }
        
        return Task.CompletedTask;
    }
    
    public Task PublishDeliveryReportAsync(Guid messageId, string status, DateTime timestamp)
    {
        if (_channel == null)
        {
            return Task.CompletedTask;
        }
        
        try
        {
            var report = new
            {
                MessageId = messageId,
                Status = status,
                Timestamp = timestamp
            };
            
            var json = JsonSerializer.Serialize(report);
            var body = Encoding.UTF8.GetBytes(json);
            
            var properties = _channel.CreateBasicProperties();
            properties.Persistent = true;
            
            _channel.BasicPublish("", DeliveryReportQueueName, properties, body);
            
            _logger.LogDebug("Published delivery report for message {MessageId}", messageId);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error publishing delivery report for message {MessageId}", messageId);
        }
        
        return Task.CompletedTask;
    }
    
    public Task<int> GetQueueLengthAsync()
    {
        if (_channel == null)
        {
            return Task.FromResult(0);
        }
        
        try
        {
            var result = _channel.QueueDeclarePassive(SmsQueueName);
            return Task.FromResult((int)result.MessageCount);
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error getting queue length");
            return Task.FromResult(0);
        }
    }
    
    public void Dispose()
    {
        _channel?.Close();
        _channel?.Dispose();
        _connection?.Close();
        _connection?.Dispose();
    }
}
