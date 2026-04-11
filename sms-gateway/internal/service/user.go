package service

import (
	"errors"

	"golang.org/x/crypto/bcrypt"
	"gorm.io/gorm"
	"sms-gateway/internal/middleware"
	"sms-gateway/internal/model"
)

type UserService struct {
	db *gorm.DB
}

func NewUserService(db *gorm.DB) *UserService {
	return &UserService{db: db}
}

func (s *UserService) Login(username, password string) (*model.User, string, error) {
	var user model.User
	if err := s.db.Where("username = ?", username).First(&user).Error; err != nil {
		if errors.Is(err, gorm.ErrRecordNotFound) {
			return nil, "", ErrUserNotFound
		}
		return nil, "", err
	}

	if user.Status == 0 {
		return nil, "", ErrUserDisabled
	}

	if err := bcrypt.CompareHashAndPassword([]byte(user.Password), []byte(password)); err != nil {
		return nil, "", ErrInvalidPassword
	}

	claims := &middleware.Claims{
		UserID:   user.ID,
		Username: user.Username,
		Role:     user.Role,
	}

	token, err := middleware.GenerateJWT(claims, "your-secret-key-change-in-production")
	if err != nil {
		return nil, "", err
	}

	return &user, token, nil
}

func (s *UserService) GetByID(id int64) (*model.User, error) {
	var user model.User
	if err := s.db.First(&user, id).Error; err != nil {
		if errors.Is(err, gorm.ErrRecordNotFound) {
			return nil, ErrUserNotFound
		}
		return nil, err
	}
	return &user, nil
}

func (s *UserService) GetByUsername(username string) (*model.User, error) {
	var user model.User
	if err := s.db.Where("username = ?", username).First(&user).Error; err != nil {
		if errors.Is(err, gorm.ErrRecordNotFound) {
			return nil, ErrUserNotFound
		}
		return nil, err
	}
	return &user, nil
}

func (s *UserService) Create(user *model.User) error {
	hashedPassword, err := bcrypt.GenerateFromPassword([]byte(user.Password), bcrypt.DefaultCost)
	if err != nil {
		return err
	}
	user.Password = string(hashedPassword)
	return s.db.Create(user).Error
}

func (s *UserService) Update(user *model.User) error {
	return s.db.Save(user).Error
}

func (s *UserService) UpdateFields(id int64, fields map[string]interface{}) error {
	return s.db.Model(&model.User{}).Where("id = ?", id).Updates(fields).Error
}

func (s *UserService) Delete(id int64) error {
	return s.db.Delete(&model.User{}, id).Error
}

func (s *UserService) List(page, limit int) ([]model.User, int64, error) {
	var users []model.User
	var total int64

	s.db.Model(&model.User{}).Count(&total)

	offset := (page - 1) * limit
	if err := s.db.Offset(offset).Limit(limit).Find(&users).Error; err != nil {
		return nil, 0, err
	}

	return users, total, nil
}

func (s *UserService) UpdateBalance(userID int64, newBalance float64) error {
	return s.db.Model(&model.User{}).Where("id = ?", userID).Update("balance", newBalance).Error
}

func (s *UserService) DeductBalance(userID int64, amount float64) error {
	return s.db.Transaction(func(tx *gorm.DB) error {
		var user model.User
		if err := tx.First(&user, userID).Error; err != nil {
			return err
		}

		if user.Balance < amount {
			return ErrInsufficientBalance
		}

		newBalance := user.Balance - amount
		return tx.Model(&user).Update("balance", newBalance).Error
	})
}

func HashPassword(password string) (string, error) {
	bytes, err := bcrypt.GenerateFromPassword([]byte(password), bcrypt.DefaultCost)
	return string(bytes), err
}

var (
	ErrUserNotFound        = errors.New("用户不存在")
	ErrInvalidPassword     = errors.New("密码错误")
	ErrInsufficientBalance = errors.New("余额不足")
)
