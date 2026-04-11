package service

import (
	"context"
	"time"

	"gorm.io/gorm"
	"sms-gateway/internal/model"
)

type BillingService struct {
	db *gorm.DB
}

func NewBillingService(db *gorm.DB) *BillingService {
	return &BillingService{db: db}
}

func (s *BillingService) Deduct(userID int64, taskID string, smsCount int, price float64) (*model.BillingLog, error) {
	var log model.BillingLog

	err := s.db.Transaction(func(tx *gorm.DB) error {
		var user model.User
		if err := tx.First(&user, userID).Error; err != nil {
			return err
		}

		totalCost := float64(smsCount) * price

		if user.Balance < totalCost {
			return ErrInsufficientBalance
		}

		balanceBefore := user.Balance
		balanceAfter := balanceBefore - totalCost

		if err := tx.Model(&user).Update("balance", balanceAfter).Error; err != nil {
			return err
		}

		log = model.BillingLog{
			UserID:        userID,
			TaskID:        taskID,
			SmsCount:      smsCount,
			Amount:        totalCost,
			BalanceBefore: balanceBefore,
			BalanceAfter:  balanceAfter,
			CreatedAt:     time.Now(),
		}

		if err := tx.Create(&log).Error; err != nil {
			return err
		}

		return nil
	})

	if err != nil {
		return nil, err
	}

	return &log, nil
}

func (s *BillingService) DeductAtomic(ctx context.Context, userID int64, taskID string, smsCount int, price float64) (*model.BillingLog, float64, error) {
	var log model.BillingLog
	var newBalance float64

	err := s.db.WithContext(ctx).Transaction(func(tx *gorm.DB) error {
		var user model.User
		if err := tx.Set("skip_updated_at", true).First(&user, userID).Error; err != nil {
			return err
		}

		totalCost := float64(smsCount) * price

		if user.Balance < totalCost {
			return ErrInsufficientBalance
		}

		balanceBefore := user.Balance
		balanceAfter := balanceBefore - totalCost
		newBalance = balanceAfter

		result := tx.Model(&model.User{}).
			Where("id = ? AND balance >= ?", userID, totalCost).
			Update("balance", gorm.Expr("balance - ?", totalCost))

		if result.Error != nil {
			return result.Error
		}

		if result.RowsAffected == 0 {
			return ErrInsufficientBalance
		}

		log = model.BillingLog{
			UserID:        userID,
			TaskID:        taskID,
			SmsCount:      smsCount,
			Amount:        totalCost,
			BalanceBefore: balanceBefore,
			BalanceAfter:  balanceAfter,
			CreatedAt:     time.Now(),
		}

		if err := tx.Create(&log).Error; err != nil {
			return err
		}

		return nil
	})

	if err != nil {
		return nil, 0, err
	}

	return &log, newBalance, nil
}

func (s *BillingService) GetUserBillingLogs(userID int64, page, limit int) ([]model.BillingLog, int64, error) {
	var logs []model.BillingLog
	var total int64

	query := s.db.Model(&model.BillingLog{}).Where("user_id = ?", userID)
	query.Count(&total)

	offset := (page - 1) * limit
	if err := query.Offset(offset).Limit(limit).Order("created_at DESC").Find(&logs).Error; err != nil {
		return nil, 0, err
	}

	return logs, total, nil
}

func (s *BillingService) Rollback(userID int64, taskID string) error {
	return s.db.Transaction(func(tx *gorm.DB) error {
		var logs []model.BillingLog
		if err := tx.Where("user_id = ? AND task_id = ?", userID, taskID).Find(&logs).Error; err != nil {
			return err
		}

		if len(logs) == 0 {
			return nil
		}

		var totalRefund float64
		for _, log := range logs {
			totalRefund += log.Amount
		}

		result := tx.Model(&model.User{}).Where("id = ?", userID).
			Update("balance", gorm.Expr("balance + ?", totalRefund))

		return result.Error
	})
}
