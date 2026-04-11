package worker

import (
	"context"
	"log"
	"sync"
	"time"

	"github.com/google/uuid"
	"gorm.io/gorm"
	"sms-gateway/internal/config"
	"sms-gateway/internal/encoder"
	"sms-gateway/internal/model"
	"sms-gateway/internal/parser"
	"sms-gateway/internal/queue"
	"sms-gateway/internal/service"
	"sms-gateway/internal/smpp"
	"sms-gateway/internal/tps"
)

type Consumer struct {
	db           *gorm.DB
	queue        *queue.RedisStream
	channelSvc   *service.ChannelService
	smsSvc       *service.SmsService
	tpsManager   *tps.TPSManager
	smppPool     *smpp.ClientPool
	cfg          *config.SMSConfig
	consumerName string
	wg           sync.WaitGroup
	stopCh       chan struct{}
	enc          *encoder.SmsEncoder
	phoneParser  *parser.PhoneParser
}

func NewConsumer(
	db *gorm.DB,
	q *queue.RedisStream,
	channelSvc *service.ChannelService,
	smsSvc *service.SmsService,
	tpsManager *tps.TPSManager,
	smppPool *smpp.ClientPool,
	cfg *config.SMSConfig,
) *Consumer {
	return &Consumer{
		db:           db,
		queue:        q,
		channelSvc:   channelSvc,
		smsSvc:       smsSvc,
		tpsManager:   tpsManager,
		smppPool:     smppPool,
		cfg:          cfg,
		consumerName: "consumer-" + uuid.New().String()[:8],
		stopCh:       make(chan struct{}),
		enc:          encoder.NewSmsEncoder(),
		phoneParser:  parser.NewPhoneParser(),
	}
}

func (c *Consumer) Start(ctx context.Context, workerCount int) error {
	if err := c.queue.InitConsumerGroup(ctx); err != nil {
		log.Printf("Failed to init consumer group: %v", err)
	}

	for i := 0; i < workerCount; i++ {
		c.wg.Add(1)
		go c.runWorker(ctx, i)
	}

	log.Printf("Started %d SMS workers", workerCount)
	return nil
}

func (c *Consumer) runWorker(ctx context.Context, id int) {
	defer c.wg.Done()

	log.Printf("Worker %d started", id)

	for {
		select {
		case <-ctx.Done():
			log.Printf("Worker %d stopping", id)
			return
		case <-c.stopCh:
			log.Printf("Worker %d stopping", id)
			return
		default:
			c.processMessages(ctx, id)
		}
	}
}

func (c *Consumer) processMessages(ctx context.Context, workerID int) {
	tasks, ids, err := c.queue.ConsumeTasks(ctx, c.consumerName, 10)
	if err != nil {
		log.Printf("Worker %d: failed to consume tasks: %v", workerID, err)
		time.Sleep(time.Second)
		return
	}

	if len(tasks) == 0 {
		time.Sleep(time.Millisecond * 100)
		return
	}

	for i, task := range tasks {
		c.processTask(ctx, &task, ids[i])
	}
}

func (c *Consumer) processTask(ctx context.Context, task *model.SmsTask, msgID string) {
	log.Printf("Processing SMS task: %s, phone: %s", task.TaskID, task.Phone)

	client, ok := c.smppPool.Get(task.ChannelID)
	if !ok {
		log.Printf("Channel not found: %s", task.ChannelID)
		c.queue.EnqueueDLQ(ctx, task, "channel not found")
		c.queue.AckTask(ctx, msgID)
		c.smsSvc.UpdateRecordStatus(task.TaskID, task.Phone, "error", "通道不存在")
		return
	}

	if !client.IsConnected() {
		_, err := c.channelSvc.GetByID(task.ChannelID)
		if err != nil {
			c.queue.EnqueueDLQ(ctx, task, "channel config error")
			c.queue.AckTask(ctx, msgID)
			c.smsSvc.UpdateRecordStatus(task.TaskID, task.Phone, "error", "通道连接失败")
			return
		}

		if err := client.Connect(ctx); err != nil {
			c.queue.EnqueueDLQ(ctx, task, "connection failed")
			c.queue.AckTask(ctx, msgID)
			c.smsSvc.UpdateRecordStatus(task.TaskID, task.Phone, "error", "通道连接失败")
			return
		}
	}

	limiter := c.tpsManager.GetLimiter(task.ChannelID, c.cfg.MaxTPS, nil)
	if err := limiter.WaitContext(ctx); err != nil {
		c.queue.EnqueueDLQ(ctx, task, "rate limit timeout")
		c.queue.AckTask(ctx, msgID)
		return
	}

	normalizedPhone, err := c.phoneParser.Normalize(task.Phone, task.CountryCode)
	if err != nil {
		c.queue.AckTask(ctx, msgID)
		c.smsSvc.UpdateRecordStatus(task.TaskID, task.Phone, "failed", "号码格式错误")
		return
	}

	encodingResult, err := c.enc.Encode(task.Content)
	if err != nil || !encodingResult.IsSupported {
		c.queue.AckTask(ctx, msgID)
		c.smsSvc.UpdateRecordStatus(task.TaskID, task.Phone, "failed", "内容编码失败")
		return
	}

	for _, segment := range encodingResult.Segments {
		submitTime := time.Now()
		msgIDStr, err := client.SendShortMessage(ctx, &model.SmsTask{
			TaskID:      task.TaskID,
			UserID:      task.UserID,
			ChannelID:   task.ChannelID,
			CountryCode: task.CountryCode,
			SenderID:    task.SenderID,
			Phone:       normalizedPhone,
			Content:     segment,
			SubmitTime:  submitTime,
		})

		if err != nil {
			log.Printf("Failed to send SMS: %v", err)
			c.smsSvc.UpdateRecordStatus(task.TaskID, task.Phone, "error", err.Error())
			continue
		}

		c.queue.EnqueueDLR(ctx, msgIDStr, "submitted")
		c.smsSvc.UpdateRecordStatus(task.TaskID, task.Phone, "submitted", "")
	}

	c.queue.AckTask(ctx, msgID)
	c.smsSvc.UpdateRecordStatus(task.TaskID, task.Phone, "success", "")
	log.Printf("SMS sent successfully: %s to %s", task.TaskID, task.Phone)
}

func (c *Consumer) Stop() {
	close(c.stopCh)
	c.wg.Wait()
	log.Printf("All workers stopped")
}
