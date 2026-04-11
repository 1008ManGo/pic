package service

import (
	"errors"

	"gorm.io/gorm"
	"sms-gateway/internal/model"
)

type ChannelService struct {
	db *gorm.DB
}

func NewChannelService(db *gorm.DB) *ChannelService {
	return &ChannelService{db: db}
}

func (s *ChannelService) GetByID(id string) (*model.Channel, error) {
	var channel model.Channel
	if err := s.db.First(&channel, "id = ?", id).Error; err != nil {
		if errors.Is(err, gorm.ErrRecordNotFound) {
			return nil, ErrChannelNotFound
		}
		return nil, err
	}
	return &channel, nil
}

func (s *ChannelService) GetActive() (*model.Channel, error) {
	var channel model.Channel
	if err := s.db.Where("status = ?", "active").First(&channel).Error; err != nil {
		if errors.Is(err, gorm.ErrRecordNotFound) {
			return nil, ErrChannelNotFound
		}
		return nil, err
	}
	return &channel, nil
}

func (s *ChannelService) Create(channel *model.Channel) error {
	return s.db.Create(channel).Error
}

func (s *ChannelService) Update(channel *model.Channel) error {
	return s.db.Save(channel).Error
}

func (s *ChannelService) Delete(id string) error {
	return s.db.Delete(&model.Channel{}, "id = ?", id).Error
}

func (s *ChannelService) List() ([]model.Channel, error) {
	var channels []model.Channel
	if err := s.db.Find(&channels).Error; err != nil {
		return nil, err
	}
	return channels, nil
}

func (s *ChannelService) UpdateStatus(id string, status string) error {
	return s.db.Model(&model.Channel{}).Where("id = ?", id).Update("status", status).Error
}

var ErrChannelNotFound = errors.New("通道不存在")
