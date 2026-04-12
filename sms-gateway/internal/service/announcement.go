package service

import (
	"gorm.io/gorm"
	"sms-gateway/internal/model"
)

type AnnouncementService struct {
	db *gorm.DB
}

func NewAnnouncementService(db *gorm.DB) *AnnouncementService {
	return &AnnouncementService{db: db}
}

func (s *AnnouncementService) GetLatest() (*model.Announcement, error) {
	var announcement model.Announcement
	err := s.db.Order("id DESC").First(&announcement).Error
	if err != nil {
		if err == gorm.ErrRecordNotFound {
			return nil, nil
		}
		return nil, err
	}
	return &announcement, nil
}

func (s *AnnouncementService) List(limit int) ([]model.Announcement, error) {
	var announcements []model.Announcement
	err := s.db.Order("id DESC").Limit(limit).Find(&announcements).Error
	return announcements, err
}

func (s *AnnouncementService) Create(announcement *model.Announcement) error {
	return s.db.Create(announcement).Error
}

func (s *AnnouncementService) Delete(id int64) error {
	return s.db.Delete(&model.Announcement{}, id).Error
}
