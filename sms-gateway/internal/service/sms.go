package service

import (
	"context"
	"errors"
	"fmt"
	"time"

	"github.com/google/uuid"
	"gorm.io/gorm"
	"sms-gateway/internal/encoder"
	"sms-gateway/internal/model"
	"sms-gateway/internal/parser"
	"sms-gateway/internal/queue"
)

var (
	ErrUserDisabled   = errors.New("用户已禁用")
	ErrInvalidContent = errors.New("短信内容包含不支持的字符")
	ErrInvalidPhone   = errors.New("手机号格式错误")
)

type SmsService struct {
	db          *gorm.DB
	userSvc     *UserService
	channelSvc  *ChannelService
	billingSvc  *BillingService
	queue       *queue.RedisStream
	encoder     *encoder.SmsEncoder
	phoneParser *parser.PhoneParser
}

func NewSmsService(
	db *gorm.DB,
	userSvc *UserService,
	channelSvc *ChannelService,
	billingSvc *BillingService,
	q *queue.RedisStream,
) *SmsService {
	return &SmsService{
		db:          db,
		userSvc:     userSvc,
		channelSvc:  channelSvc,
		billingSvc:  billingSvc,
		queue:       q,
		encoder:     encoder.NewSmsEncoder(),
		phoneParser: parser.NewPhoneParser(),
	}
}

func (s *SmsService) Send(ctx context.Context, userID int64, phones []string, content string) (*model.SendSmsResponse, error) {
	user, err := s.userSvc.GetByID(userID)
	if err != nil {
		return nil, err
	}

	if user.Status == 0 {
		return nil, ErrUserDisabled
	}

	valid, msg := s.encoder.ValidateContent(content)
	if !valid {
		return nil, errors.New(msg)
	}

	normalizedPhones, _ := parser.NormalizePhones(phones, user.CountryCode)
	if len(normalizedPhones) == 0 {
		return nil, ErrInvalidPhone
	}

	encodingResult, err := s.encoder.Encode(content)
	if err != nil || !encodingResult.IsSupported {
		return nil, ErrInvalidContent
	}

	totalSmsCount := len(normalizedPhones) * encodingResult.SmsCount
	totalCost := float64(totalSmsCount) * user.Price

	if user.Balance < totalCost {
		return nil, ErrInsufficientBalance
	}

	taskID := s.generateTaskID()

	billingLog, balanceAfter, err := s.billingSvc.DeductAtomic(ctx, userID, taskID, totalSmsCount, user.Price)
	if err != nil {
		return nil, err
	}

	_ = billingLog

	for _, phone := range normalizedPhones {
		task := &model.SmsTask{
			TaskID:      taskID,
			UserID:      userID,
			Username:    user.Username,
			ChannelID:   user.SmppChannel,
			CountryCode: user.CountryCode,
			Price:       user.Price,
			SenderID:    "",
			Phone:       phone,
			Content:     content,
			Encoding:    encodingResult.Encoding,
			SubmitTime:  time.Now(),
		}

		if err := s.queue.EnqueueTask(ctx, task); err != nil {
			s.billingSvc.Rollback(userID, taskID)
			return nil, err
		}
	}

	response := &model.SendSmsResponse{
		TaskID:       taskID,
		TotalPhones:  len(normalizedPhones),
		SmsCount:     totalSmsCount,
		PricePerSms:  user.Price,
		TotalCost:    totalCost,
		BalanceAfter: balanceAfter,
	}

	return response, nil
}

func (s *SmsService) GetRecords(userID int64, page, limit int, status string) ([]model.SmsRecord, int64, error) {
	var records []model.SmsRecord
	var total int64

	query := s.db.Model(&model.SmsRecord{}).Where("user_id = ?", userID)

	if status != "" {
		query = query.Where("status = ?", status)
	}

	query.Count(&total)

	offset := (page - 1) * limit
	if err := query.Offset(offset).Limit(limit).Order("created_at DESC").Find(&records).Error; err != nil {
		return nil, 0, err
	}

	return records, total, nil
}

func (s *SmsService) GetRecordByID(id int64) (*model.SmsRecord, error) {
	var record model.SmsRecord
	if err := s.db.First(&record, id).Error; err != nil {
		return nil, err
	}
	return &record, nil
}

func (s *SmsService) GetTaskActivity(taskID string) (*model.TaskActivity, error) {
	var records []model.SmsRecord
	if err := s.db.Where("task_id = ?", taskID).Find(&records).Error; err != nil {
		return nil, err
	}

	activity := &model.TaskActivity{
		TaskID: taskID,
		Total:  len(records),
	}

	for _, r := range records {
		switch r.Status {
		case "submitted":
			activity.Submitted++
		case "success":
			activity.Success++
		case "failed":
			activity.Failed++
		case "unknown":
			activity.Unknown++
		case "error":
			activity.Error++
		}
	}

	return activity, nil
}

func (s *SmsService) UpdateRecordStatus(taskID string, phone string, status string, errorMsg string) error {
	updates := map[string]interface{}{
		"status": status,
	}

	if status == "submitted" {
		now := time.Now()
		updates["submit_time"] = &now
	}

	if status == "success" || status == "failed" || status == "error" {
		now := time.Now()
		updates["done_time"] = &now
	}

	if errorMsg != "" {
		updates["error_msg"] = errorMsg
	}

	return s.db.Model(&model.SmsRecord{}).
		Where("task_id = ? AND phone = ?", taskID, phone).
		Updates(updates).Error
}

func (s *SmsService) CreateRecords(task *model.SmsTask, phones []string, smsCount int) error {
	encodingResult, _ := s.encoder.Encode(task.Content)

	records := make([]model.SmsRecord, 0, len(phones))
	for _, phone := range phones {
		record := model.SmsRecord{
			TaskID:      task.TaskID,
			UserID:      task.UserID,
			ChannelID:   task.ChannelID,
			CountryCode: task.CountryCode,
			SenderID:    task.SenderID,
			Phone:       phone,
			Content:     task.Content,
			Encoding:    encodingResult.Encoding,
			SmsCount:    encodingResult.SmsCount,
			Price:       task.Price,
			TotalPrice:  float64(encodingResult.SmsCount) * task.Price,
			Status:      "pending",
			CreatedAt:   time.Now(),
		}
		records = append(records, record)
	}

	return s.db.Create(&records).Error
}

func (s *SmsService) generateTaskID() string {
	now := time.Now()
	uuidStr := uuid.New().String()[:8]
	return fmt.Sprintf("%s_%s_%s", now.Format("20060102"), "001", uuidStr)
}
