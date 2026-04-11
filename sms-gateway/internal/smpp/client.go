package smpp

import (
	"context"
	"errors"
	"fmt"
	"log"
	"sync"
	"time"

	"github.com/linxGnu/gosmpp"
	"github.com/linxGnu/gosmpp/data"
	"github.com/linxGnu/gosmpp/pdu"
	"sms-gateway/internal/model"
)

var (
	ErrNotConnected   = errors.New("SMPP client not connected")
	ErrClientExists   = errors.New("client already exists in pool")
	ErrClientNotFound = errors.New("client not found in pool")
	ErrSendFailed     = errors.New("failed to send SMS")
)

type Client struct {
	cfg       *model.Channel
	session   *gosmpp.Session
	status    string
	mu        sync.RWMutex
	connected bool
	limiter   *TPSLimiter
}

func NewClient(cfg *model.Channel) *Client {
	return &Client{
		cfg:    cfg,
		status: "stopped",
	}
}

type TPSLimiter struct {
	maxTPS     int
	windowMs   int64
	mu         sync.Mutex
	timestamps []int64
}

func NewTPSLimiter(maxTPS int) *TPSLimiter {
	if maxTPS <= 0 {
		maxTPS = 50
	}
	return &TPSLimiter{
		maxTPS:   maxTPS,
		windowMs: 1000,
	}
}

func (l *TPSLimiter) Allow() bool {
	l.mu.Lock()
	defer l.mu.Unlock()

	now := time.Now().UnixMilli()
	windowStart := now - l.windowMs

	var valid []int64
	for _, ts := range l.timestamps {
		if ts > windowStart {
			valid = append(valid, ts)
		}
	}
	l.timestamps = valid

	if len(l.timestamps) < l.maxTPS {
		l.timestamps = append(l.timestamps, now)
		return true
	}
	return false
}

func (l *TPSLimiter) Wait() {
	for !l.Allow() {
		time.Sleep(10 * time.Millisecond)
	}
}

func (l *TPSLimiter) WaitContext(ctx context.Context) error {
	for {
		select {
		case <-ctx.Done():
			return ctx.Err()
		default:
			if l.Allow() {
				return nil
			}
			time.Sleep(10 * time.Millisecond)
		}
	}
}

func (l *TPSLimiter) GetTPS() int {
	l.mu.Lock()
	defer l.mu.Unlock()

	now := time.Now().UnixMilli()
	windowStart := now - l.windowMs

	var count int
	for _, ts := range l.timestamps {
		if ts > windowStart {
			count++
		}
	}
	return count
}

func (c *Client) Connect(ctx context.Context) error {
	c.mu.Lock()
	defer c.mu.Unlock()

	addr := fmt.Sprintf("%s:%d", c.cfg.IP, c.cfg.Port)

	auth := gosmpp.Auth{
		SMSC:       addr,
		SystemID:   c.cfg.Username,
		Password:   c.cfg.Password,
		SystemType: "sms",
	}

	c.limiter = NewTPSLimiter(c.cfg.MaxTPS)

	settings := gosmpp.Settings{
		ReadTimeout:  60 * time.Second,
		WriteTimeout: 10 * time.Second,
		EnquireLink:  30 * time.Second,
		OnSubmitError: func(p pdu.PDU, err error) {
			log.Printf("Submit error: %v", err)
		},
		OnReceivingError: func(err error) {
			log.Printf("Receiving error: %v", err)
			c.setStatus("error")
		},
		OnClosed: func(state gosmpp.State) {
			log.Printf("SMPP connection closed: %v", state)
			c.setStatus("stopped")
			c.setConnected(false)
		},
		OnPDU: c.handlePDU,
		WindowedRequestTracking: &gosmpp.WindowedRequestTracking{
			MaxWindowSize:      uint8(c.cfg.MaxTPS),
			PduExpireTimeOut:   30 * time.Second,
			ExpireCheckTimer:   5 * time.Second,
			StoreAccessTimeOut: 500 * time.Millisecond,
			EnableAutoRespond:  true,
		},
	}

	session, err := gosmpp.NewSession(
		gosmpp.TRXConnector(gosmpp.NonTLSDialer, auth),
		settings,
		30*time.Second,
	)

	if err != nil {
		c.status = "error"
		return fmt.Errorf("failed to connect to %s: %w", addr, err)
	}

	c.session = session
	c.connected = true
	c.status = "active"

	log.Printf("SMPP client connected to %s (TPS limit: %d)", addr, c.cfg.MaxTPS)
	return nil
}

func (c *Client) handlePDU(p pdu.PDU, responded bool) {
	if p == nil {
		return
	}

	h := p.GetHeader()
	switch h.CommandID {
	case data.DELIVER_SM:
		c.handleDeliverSM(p)
	}
}

func (c *Client) handleDeliverSM(p pdu.PDU) {
	sm, ok := p.(*pdu.DeliverSM)
	if !ok {
		return
	}

	sourceAddr := sm.SourceAddr.Address()
	destAddr := sm.DestAddr.Address()
	msg, _ := sm.Message.GetMessage()

	log.Printf("Received DeliverSM: from=%s, to=%s, msg=%s",
		sourceAddr, destAddr, msg)
}

func (c *Client) Disconnect() error {
	c.mu.Lock()
	defer c.mu.Unlock()

	if c.session != nil {
		_ = c.session.Close()
		c.session = nil
	}
	c.connected = false
	c.status = "stopped"
	return nil
}

func (c *Client) SendShortMessage(ctx context.Context, task *model.SmsTask) (string, error) {
	c.mu.RLock()
	if !c.connected || c.session == nil {
		c.mu.RUnlock()
		return "", ErrNotConnected
	}
	c.mu.RUnlock()

	if c.limiter != nil {
		if err := c.limiter.WaitContext(ctx); err != nil {
			return "", err
		}
	}

	sm := pdu.NewSubmitSM().(*pdu.SubmitSM)

	sourceAddr := task.SenderID
	if sourceAddr == "" {
		sourceAddr = "12345"
	}
	sm.SourceAddr.SetAddress(sourceAddr)
	sm.DestAddr.SetAddress(task.Phone)

	var enc data.Encoding
	if task.Encoding == "UCS2" {
		enc = data.UCS2
	} else {
		enc = data.GSM7BIT
	}
	sm.Message.SetMessageWithEncoding(task.Content, enc)
	sm.RegisteredDelivery = 1

	err := c.session.Transceiver().Submit(sm)
	if err != nil {
		return "", fmt.Errorf("%w: %v", ErrSendFailed, err)
	}

	return fmt.Sprintf("%d", sm.SequenceNumber), nil
}

func (c *Client) GetStatus() string {
	c.mu.RLock()
	defer c.mu.RUnlock()
	return c.status
}

func (c *Client) IsConnected() bool {
	c.mu.RLock()
	defer c.mu.RUnlock()
	return c.connected
}

func (c *Client) setStatus(status string) {
	c.mu.Lock()
	defer c.mu.Unlock()
	c.status = status
}

func (c *Client) setConnected(connected bool) {
	c.mu.Lock()
	defer c.mu.Unlock()
	c.connected = connected
}

func (c *Client) GetCurrentTPS() int {
	if c.limiter == nil {
		return 0
	}
	return c.limiter.GetTPS()
}

type ClientPool struct {
	clients map[string]*Client
	mu      sync.RWMutex
}

func NewClientPool() *ClientPool {
	return &ClientPool{
		clients: make(map[string]*Client),
	}
}

func (p *ClientPool) Add(client *Client) error {
	p.mu.Lock()
	defer p.mu.Unlock()

	if _, ok := p.clients[client.cfg.ID]; ok {
		return ErrClientExists
	}

	p.clients[client.cfg.ID] = client
	return nil
}

func (p *ClientPool) Get(id string) (*Client, bool) {
	p.mu.RLock()
	defer p.mu.RUnlock()

	client, ok := p.clients[id]
	return client, ok
}

func (p *ClientPool) Remove(id string) error {
	p.mu.Lock()
	defer p.mu.Unlock()

	client, ok := p.clients[id]
	if !ok {
		return ErrClientNotFound
	}

	client.Disconnect()
	delete(p.clients, id)
	return nil
}

func (p *ClientPool) List() []*Client {
	p.mu.RLock()
	defer p.mu.RUnlock()

	var clients []*Client
	for _, client := range p.clients {
		clients = append(clients, client)
	}
	return clients
}

func (p *ClientPool) GetActive() []*Client {
	p.mu.RLock()
	defer p.mu.RUnlock()

	var active []*Client
	for _, client := range p.clients {
		if client.status == "active" {
			active = append(active, client)
		}
	}
	return active
}
