package tps

import (
	"context"
	"fmt"
	"sync"
	"time"

	"github.com/redis/go-redis/v9"
)

type Limiter struct {
	client     *redis.Client
	key        string
	maxTPS     int
	window     time.Duration
	mu         sync.Mutex
	tokens     int
	lastRefill time.Time
}

func NewLimiter(client *redis.Client, channelID string, maxTPS int) *Limiter {
	return &Limiter{
		client:     client,
		key:        fmt.Sprintf("tps:%s", channelID),
		maxTPS:     maxTPS,
		window:     time.Second,
		tokens:     maxTPS,
		lastRefill: time.Now(),
	}
}

func (l *Limiter) Allow() bool {
	l.mu.Lock()
	defer l.mu.Unlock()

	l.refill()

	if l.tokens > 0 {
		l.tokens--
		return true
	}

	return false
}

func (l *Limiter) refill() {
	now := time.Now()
	elapsed := now.Sub(l.lastRefill)

	if elapsed >= l.window {
		l.tokens = l.maxTPS
		l.lastRefill = now
	}
}

func (l *Limiter) Wait() {
	for !l.Allow() {
		time.Sleep(time.Millisecond * 10)
	}
}

func (l *Limiter) WaitContext(ctx context.Context) error {
	for {
		select {
		case <-ctx.Done():
			return ctx.Err()
		default:
			if l.Allow() {
				return nil
			}
			time.Sleep(time.Millisecond * 10)
		}
	}
}

type RedisLimiter struct {
	client *redis.Client
}

func NewRedisLimiter(client *redis.Client) *RedisLimiter {
	return &RedisLimiter{client: client}
}

func (l *RedisLimiter) SlideWindow(ctx context.Context, key string, limit int, window time.Duration) (bool, error) {
	now := time.Now().UnixMilli()
	oneMsAgo := now - 1

	pipe := l.client.Pipeline()

	pipe.ZRemRangeByScore(ctx, key, "-inf", fmt.Sprintf("%d", oneMsAgo))

	pipe.ZCard(ctx, key)

	count, err := pipe.Exec(ctx)
	if err != nil {
		return false, err
	}

	currentCount := count[1].(*redis.IntCmd).Val()

	if currentCount >= int64(limit) {
		return false, nil
	}

	pipe2 := l.client.Pipeline()
	pipe2.ZAdd(ctx, key, redis.Z{Score: float64(now), Member: now})
	pipe2.Expire(ctx, key, window)

	_, err = pipe2.Exec(ctx)
	if err != nil {
		return false, err
	}

	return true, nil
}

func (l *RedisLimiter) Acquire(ctx context.Context, channelID string, maxTPS int) error {
	key := fmt.Sprintf("tps:redis:%s", channelID)
	window := time.Second

	allowed, err := l.SlideWindow(ctx, key, maxTPS, window)
	if err != nil {
		return err
	}

	if !allowed {
		return ErrRateLimitExceeded
	}

	return nil
}

func (l *RedisLimiter) GetTPS(ctx context.Context, channelID string) (int64, error) {
	key := fmt.Sprintf("tps:redis:%s", channelID)
	now := time.Now().UnixMilli()
	oneMsAgo := now - 1000

	count, err := l.client.ZCount(ctx, key, fmt.Sprintf("%d", oneMsAgo), "+inf").Result()
	if err != nil {
		return 0, err
	}

	return count, nil
}

var ErrRateLimitExceeded = fmt.Errorf("rate limit exceeded")

type TPSManager struct {
	limiters map[string]*Limiter
	mu       sync.RWMutex
}

func NewTPSManager() *TPSManager {
	return &TPSManager{
		limiters: make(map[string]*Limiter),
	}
}

func (m *TPSManager) GetLimiter(channelID string, maxTPS int, client *redis.Client) *Limiter {
	m.mu.RLock()
	limiter, ok := m.limiters[channelID]
	m.mu.RUnlock()

	if ok {
		return limiter
	}

	m.mu.Lock()
	defer m.mu.Unlock()

	if limiter, ok = m.limiters[channelID]; ok {
		return limiter
	}

	limiter = NewLimiter(client, channelID, maxTPS)
	m.limiters[channelID] = limiter
	return limiter
}

func (m *TPSManager) Remove(channelID string) {
	m.mu.Lock()
	defer m.mu.Unlock()
	delete(m.limiters, channelID)
}
