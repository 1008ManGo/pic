package queue

import (
	"context"
	"encoding/json"
	"fmt"
	"time"

	"github.com/redis/go-redis/v9"
	"sms-gateway/internal/config"
	"sms-gateway/internal/model"
)

type RedisStream struct {
	client *redis.Client
	cfg    *config.SMSConfig
}

func NewRedisStream(rdb *redis.Client, cfg *config.SMSConfig) *RedisStream {
	return &RedisStream{
		client: rdb,
		cfg:    cfg,
	}
}

func (q *RedisStream) InitConsumerGroup(ctx context.Context) error {
	groups := []string{q.cfg.ConsumerGroup, q.cfg.DLRConsumerGroup}
	streams := []string{q.cfg.QueueName, q.cfg.DLRName}

	for i, group := range groups {
		_, err := q.client.Expire(ctx, streams[i], time.Hour).Result()
		if err != nil {
			return err
		}
		err = q.client.XGroupCreateMkStream(ctx, streams[i], group, "0").Err()
		if err != nil && err.Error() != "BUSYGROUP Consumer Group name already exists" {
			return err
		}
	}
	return nil
}

func (q *RedisStream) EnqueueTask(ctx context.Context, task *model.SmsTask) error {
	data, err := json.Marshal(task)
	if err != nil {
		return err
	}

	_, err = q.client.XAdd(ctx, &redis.XAddArgs{
		Stream: q.cfg.QueueName,
		Values: map[string]interface{}{
			"data": string(data),
		},
	}).Result()

	return err
}

func (q *RedisStream) EnqueueDLQ(ctx context.Context, task *model.SmsTask, reason string) error {
	data, err := json.Marshal(task)
	if err != nil {
		return err
	}

	_, err = q.client.XAdd(ctx, &redis.XAddArgs{
		Stream: q.cfg.DLQName,
		Values: map[string]interface{}{
			"data":   string(data),
			"reason": reason,
			"time":   time.Now().Unix(),
		},
	}).Result()

	return err
}

func (q *RedisStream) ConsumeTasks(ctx context.Context, consumerName string, count int64) ([]model.SmsTask, []string, error) {
	streams, err := q.client.XReadGroup(ctx, &redis.XReadGroupArgs{
		Group:    q.cfg.ConsumerGroup,
		Consumer: consumerName,
		Streams:  []string{q.cfg.QueueName, ">"},
		Count:    count,
		Block:    time.Second * 5,
	}).Result()

	if err != nil && err != redis.Nil {
		return nil, nil, err
	}

	if len(streams) == 0 {
		return nil, nil, nil
	}

	var tasks []model.SmsTask
	var ids []string

	for _, stream := range streams[0].Messages {
		ids = append(ids, stream.ID)
		if dataStr, ok := stream.Values["data"].(string); ok {
			var task model.SmsTask
			if err := json.Unmarshal([]byte(dataStr), &task); err == nil {
				tasks = append(tasks, task)
			}
		}
	}

	return tasks, ids, nil
}

func (q *RedisStream) AckTask(ctx context.Context, id string) error {
	return q.client.XAck(ctx, q.cfg.QueueName, q.cfg.ConsumerGroup, id).Err()
}

func (q *RedisStream) GetQueueLen(ctx context.Context) (int64, error) {
	return q.client.XLen(ctx, q.cfg.QueueName).Result()
}

func (q *RedisStream) EnqueueDLR(ctx context.Context, taskID string, status string) error {
	_, err := q.client.XAdd(ctx, &redis.XAddArgs{
		Stream: q.cfg.DLRName,
		Values: map[string]interface{}{
			"task_id": taskID,
			"status":  status,
			"time":    time.Now().Unix(),
		},
	}).Result()
	return err
}

func (q *RedisStream) ConsumeDLR(ctx context.Context, consumerName string) ([]DLRMessage, error) {
	streams, err := q.client.XReadGroup(ctx, &redis.XReadGroupArgs{
		Group:    q.cfg.DLRConsumerGroup,
		Consumer: consumerName,
		Streams:  []string{q.cfg.DLRName, ">"},
		Count:    100,
		Block:    time.Second * 5,
	}).Result()

	if err != nil && err != redis.Nil {
		return nil, err
	}

	if len(streams) == 0 {
		return nil, nil
	}

	var dlrs []DLRMessage
	for _, stream := range streams[0].Messages {
		taskID, _ := stream.Values["task_id"].(string)
		status, _ := stream.Values["status"].(string)
		dlrs = append(dlrs, DLRMessage{
			ID:     stream.ID,
			TaskID: taskID,
			Status: status,
		})
	}

	return dlrs, nil
}

func (q *RedisStream) AckDLR(ctx context.Context, id string) error {
	return q.client.XAck(ctx, q.cfg.DLRName, q.cfg.DLRConsumerGroup, id).Err()
}

type DLRMessage struct {
	ID     string
	TaskID string
	Status string
}

func (q *RedisStream) Close() error {
	return q.client.Close()
}

func GetRedisClient(cfg *config.RedisConfig) *redis.Client {
	return redis.NewClient(&redis.Options{
		Addr:     fmt.Sprintf("%s:%d", cfg.Host, cfg.Port),
		Password: cfg.Password,
		DB:       cfg.DB,
	})
}
