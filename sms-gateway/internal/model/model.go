package model

import (
	"time"
)

type User struct {
	ID          int64     `json:"id" gorm:"primaryKey;autoIncrement"`
	Username    string    `json:"username" gorm:"uniqueIndex;size:64;not null"`
	Password    string    `json:"password" gorm:"size:255;not null"`
	Balance     float64   `json:"balance" gorm:"type:decimal(10,4);default:0"`
	SmppChannel string    `json:"smpp_channel" gorm:"size:32;not null"`
	CountryCode string    `json:"country_code" gorm:"char(2);not null"`
	Price       float64   `json:"price" gorm:"type:decimal(10,4);not null"`
	Role        string    `json:"role" gorm:"type:enum('user','admin');default:'user'"`
	Status      int       `json:"status" gorm:"default:1"`
	CreatedAt   time.Time `json:"created_at"`
	UpdatedAt   time.Time `json:"updated_at"`
}

func (User) TableName() string {
	return "users"
}

type Channel struct {
	ID        string    `json:"id" gorm:"primaryKey;size:32"`
	Name      string    `json:"name" gorm:"size:64;not null"`
	IP        string    `json:"ip" gorm:"size:64;not null"`
	Port      int       `json:"port" gorm:"default:2775"`
	Username  string    `json:"username" gorm:"size:64"`
	Password  string    `json:"-" gorm:"size:255"`
	MaxTPS    int       `json:"max_tps" gorm:"default:50"`
	Status    string    `json:"status" gorm:"type:enum('active','error','stopped');default:'active'"`
	CreatedAt time.Time `json:"created_at"`
	UpdatedAt time.Time `json:"updated_at"`
}

func (Channel) TableName() string {
	return "channels"
}

type SmsRecord struct {
	ID          int64      `json:"id" gorm:"primaryKey;autoIncrement"`
	TaskID      string     `json:"task_id" gorm:"size:64;not null;index"`
	UserID      int64      `json:"user_id" gorm:"not null;index"`
	ChannelID   string     `json:"channel_id" gorm:"size:32"`
	CountryCode string     `json:"country_code" gorm:"char(2);not null"`
	SenderID    string     `json:"sender_id" gorm:"size:21"`
	Phone       string     `json:"phone" gorm:"size:32;not null;index"`
	Content     string     `json:"content" gorm:"type:text;not null"`
	Encoding    string     `json:"encoding" gorm:"type:enum('GSM7','UCS2');not null"`
	SmsCount    int        `json:"sms_count" gorm:"not null"`
	Price       float64    `json:"price" gorm:"type:decimal(10,4);not null"`
	TotalPrice  float64    `json:"total_price" gorm:"type:decimal(10,4);not null"`
	Status      string     `json:"status" gorm:"type:enum('pending','submitted','success','failed','unknown','error');default:'pending';index"`
	ErrorMsg    string     `json:"error_msg" gorm:"type:text"`
	SubmitTime  *time.Time `json:"submit_time"`
	DoneTime    *time.Time `json:"done_time"`
	CreatedAt   time.Time  `json:"created_at" gorm:"index"`
}

func (SmsRecord) TableName() string {
	return "sms_records"
}

type BillingLog struct {
	ID            int64     `json:"id" gorm:"primaryKey;autoIncrement"`
	UserID        int64     `json:"user_id" gorm:"not null;index"`
	TaskID        string    `json:"task_id" gorm:"size:64;not null"`
	SmsCount      int       `json:"sms_count" gorm:"not null"`
	Amount        float64   `json:"amount" gorm:"type:decimal(10,4);not null"`
	BalanceBefore float64   `json:"balance_before" gorm:"type:decimal(10,4);not null"`
	BalanceAfter  float64   `json:"balance_after" gorm:"type:decimal(10,4);not null"`
	CreatedAt     time.Time `json:"created_at"`
}

func (BillingLog) TableName() string {
	return "billing_log"
}

type Announcement struct {
	ID        int64     `json:"id" gorm:"primaryKey;autoIncrement"`
	Title     string    `json:"title" gorm:"size:255;not null"`
	Content   string    `json:"content" gorm:"type:text;not null"`
	CreatedAt time.Time `json:"created_at"`
}

func (Announcement) TableName() string {
	return "announcements"
}

type Setting struct {
	KeyName   string    `json:"key_name" gorm:"primaryKey;size:64"`
	Value     string    `json:"value" gorm:"type:text"`
	UpdatedAt time.Time `json:"updated_at"`
}

func (Setting) TableName() string {
	return "settings"
}

type Country struct {
	Code string `json:"code" gorm:"primaryKey;char(2)"`
	Name string `json:"name" gorm:"size:64;not null"`
}

func (Country) TableName() string {
	return "countries"
}

type SmsTask struct {
	TaskID      string    `json:"task_id"`
	UserID      int64     `json:"user_id"`
	Username    string    `json:"username"`
	ChannelID   string    `json:"channel_id"`
	CountryCode string    `json:"country_code"`
	Price       float64   `json:"price"`
	SenderID    string    `json:"sender_id"`
	Phone       string    `json:"phone"`
	Content     string    `json:"content"`
	Encoding    string    `json:"encoding"`
	SubmitTime  time.Time `json:"submit_time"`
}

type LoginRequest struct {
	Username string `json:"username" binding:"required"`
	Password string `json:"password" binding:"required"`
}

type SendSmsRequest struct {
	Phones  []string `json:"phones" binding:"required,min=1"`
	Content string   `json:"content" binding:"required"`
}

type SendSmsResponse struct {
	TaskID       string  `json:"task_id"`
	TotalPhones  int     `json:"total_phones"`
	SmsCount     int     `json:"sms_count"`
	PricePerSms  float64 `json:"price_per_sms"`
	TotalCost    float64 `json:"total_cost"`
	BalanceAfter float64 `json:"balance_after"`
	SenderID     string  `json:"sender_id,omitempty"`
}

type TaskActivity struct {
	TaskID    string `json:"task_id"`
	Total     int    `json:"total"`
	Submitted int    `json:"submitted"`
	Success   int    `json:"success"`
	Failed    int    `json:"failed"`
	Unknown   int    `json:"unknown"`
	Error     int    `json:"error"`
}
