package config

import (
	"os"

	"gopkg.in/yaml.v3"
)

type Config struct {
	Database DatabaseConfig `yaml:"database"`
	Redis    RedisConfig    `yaml:"redis"`
	App      AppConfig      `yaml:"app"`
	SMS      SMSConfig      `yaml:"sms"`
}

type DatabaseConfig struct {
	Host     string `yaml:"host"`
	Port     int    `yaml:"port"`
	Username string `yaml:"username"`
	Password string `yaml:"password"`
	Name     string `yaml:"name"`
}

type RedisConfig struct {
	Host     string `yaml:"host"`
	Port     int    `yaml:"port"`
	Password string `yaml:"password"`
	DB       int    `yaml:"db"`
}

type AppConfig struct {
	Host      string `yaml:"host"`
	Port      int    `yaml:"port"`
	JWTSecret string `yaml:"jwt_secret"`
}

type SMSConfig struct {
	MaxTPS           int    `yaml:"max_tps"`
	QueueName        string `yaml:"queue_name"`
	DLQName          string `yaml:"dlq_name"`
	DLRName          string `yaml:"dlr_name"`
	ConsumerGroup    string `yaml:"consumer_group"`
	DLRConsumerGroup string `yaml:"dlr_consumer_group"`
}

var GlobalConfig *Config

func Load(path string) (*Config, error) {
	data, err := os.ReadFile(path)
	if err != nil {
		return nil, err
	}

	var cfg Config
	if err := yaml.Unmarshal(data, &cfg); err != nil {
		return nil, err
	}

	GlobalConfig = &cfg
	return &cfg, nil
}
