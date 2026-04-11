package worker

import (
	"context"
	"log"
	"sync"
	"time"

	"github.com/google/uuid"
	"gorm.io/gorm"
	"sms-gateway/internal/model"
	"sms-gateway/internal/queue"
	"sms-gateway/internal/service"
)

type DLRHandler struct {
	db           *gorm.DB
	queue        *queue.RedisStream
	smsSvc       *service.SmsService
	consumerName string
	wg           sync.WaitGroup
	stopCh       chan struct{}
}

func NewDLRHandler(
	db *gorm.DB,
	q *queue.RedisStream,
	smsSvc *service.SmsService,
) *DLRHandler {
	return &DLRHandler{
		db:           db,
		queue:        q,
		smsSvc:       smsSvc,
		consumerName: "dlr-" + uuid.New().String()[:8],
		stopCh:       make(chan struct{}),
	}
}

func (h *DLRHandler) Start(ctx context.Context) error {
	h.wg.Add(1)
	go h.run(ctx)
	log.Printf("DLR handler started")
	return nil
}

func (h *DLRHandler) run(ctx context.Context) {
	defer h.wg.Done()

	for {
		select {
		case <-ctx.Done():
			log.Printf("DLR handler stopping")
			return
		case <-h.stopCh:
			log.Printf("DLR handler stopping")
			return
		default:
			h.processDLRs(ctx)
		}
	}
}

func (h *DLRHandler) processDLRs(ctx context.Context) {
	dlrs, err := h.queue.ConsumeDLR(ctx, h.consumerName)
	if err != nil {
		log.Printf("Failed to consume DLRs: %v", err)
		time.Sleep(time.Second)
		return
	}

	if len(dlrs) == 0 {
		time.Sleep(time.Millisecond * 100)
		return
	}

	for _, dlr := range dlrs {
		h.processDLR(ctx, &dlr)
	}
}

func (h *DLRHandler) processDLR(ctx context.Context, dlr *queue.DLRMessage) {
	log.Printf("Processing DLR: task_id=%s, status=%s", dlr.TaskID, dlr.Status)

	var record model.SmsRecord
	if err := h.db.Where("task_id = ?", dlr.TaskID).First(&record).Error; err != nil {
		if err == gorm.ErrRecordNotFound {
			log.Printf("Record not found for task_id: %s", dlr.TaskID)
		} else {
			log.Printf("Failed to find record: %v", err)
		}
		return
	}

	var status string
	var errorMsg string

	switch dlr.Status {
	case "delivered":
		status = "success"
	case "undelivered":
		status = "failed"
		errorMsg = "短信未送达"
	case "expired":
		status = "failed"
		errorMsg = "短信已过期"
	case "rejected":
		status = "error"
		errorMsg = "短信被拒绝"
	default:
		status = "unknown"
	}

	if err := h.smsSvc.UpdateRecordStatus(record.TaskID, record.Phone, status, errorMsg); err != nil {
		log.Printf("Failed to update record status: %v", err)
		return
	}

	if err := h.queue.AckDLR(ctx, dlr.ID); err != nil {
		log.Printf("Failed to ack DLR: %v", err)
	}

	log.Printf("DLR processed: task_id=%s, status=%s, new_status=%s", dlr.TaskID, dlr.Status, status)
}

func (h *DLRHandler) Stop() {
	close(h.stopCh)
	h.wg.Wait()
	log.Printf("DLR handler stopped")
}
