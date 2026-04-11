package main

import (
	"context"
	"fmt"
	"log"
	"net/http"
	"os"
	"os/signal"
	"syscall"
	"time"

	"github.com/gin-gonic/gin"
	"gorm.io/driver/mysql"
	"gorm.io/gorm"
	"gorm.io/gorm/logger"

	"sms-gateway/internal/config"
	"sms-gateway/internal/handler"
	"sms-gateway/internal/middleware"
	"sms-gateway/internal/model"
	"sms-gateway/internal/queue"
	"sms-gateway/internal/service"
	"sms-gateway/internal/smpp"
	"sms-gateway/internal/tps"
	"sms-gateway/internal/worker"
	"sms-gateway/pkg/response"
)

func main() {
	cfg, err := config.Load("configs/config.yaml")
	if err != nil {
		log.Fatalf("Failed to load config: %v", err)
	}

	db, err := initDB(cfg)
	if err != nil {
		log.Fatalf("Failed to init database: %v", err)
	}

	redisClient := queue.GetRedisClient(&cfg.Redis)
	if err := redisClient.Ping(context.Background()).Err(); err != nil {
		log.Printf("Warning: Redis connection failed: %v", err)
	}

	redisStream := queue.NewRedisStream(redisClient, &cfg.SMS)

	userSvc := service.NewUserService(db)
	channelSvc := service.NewChannelService(db)
	billingSvc := service.NewBillingService(db)
	smsSvc := service.NewSmsService(db, userSvc, channelSvc, billingSvc, redisStream)

	userHandler := handler.NewUserHandler(userSvc)
	smsHandler := handler.NewSmsHandler(smsSvc)
	adminHandler := handler.NewAdminHandler(userSvc, channelSvc, smsSvc)

	tpsManager := tps.NewTPSManager()
	smppPool := smpp.NewClientPool()

	consumer := worker.NewConsumer(db, redisStream, channelSvc, smsSvc, tpsManager, smppPool, &cfg.SMS)
	dlrHandler := worker.NewDLRHandler(db, redisStream, smsSvc)

	ctx, cancel := context.WithCancel(context.Background())
	defer cancel()

	if err := consumer.Start(ctx, 5); err != nil {
		log.Fatalf("Failed to start consumer: %v", err)
	}

	if err := dlrHandler.Start(ctx); err != nil {
		log.Fatalf("Failed to start DLR handler: %v", err)
	}

	router := setupRouter(cfg, userHandler, smsHandler, adminHandler)

	srv := &http.Server{
		Addr:    fmt.Sprintf("%s:%d", cfg.App.Host, cfg.App.Port),
		Handler: router,
	}

	go func() {
		log.Printf("Server starting on %s:%d", cfg.App.Host, cfg.App.Port)
		if err := srv.ListenAndServe(); err != nil && err != http.ErrServerClosed {
			log.Fatalf("Server failed: %v", err)
		}
	}()

	quit := make(chan os.Signal, 1)
	signal.Notify(quit, syscall.SIGINT, syscall.SIGTERM)
	<-quit

	log.Println("Shutting down server...")

	cancel()

	consumer.Stop()
	dlrHandler.Stop()

	shutdownCtx, shutdownCancel := context.WithTimeout(context.Background(), 10*time.Second)
	defer shutdownCancel()

	if err := srv.Shutdown(shutdownCtx); err != nil {
		log.Fatalf("Server forced to shutdown: %v", err)
	}

	log.Println("Server exited")
}

func initDB(cfg *config.Config) (*gorm.DB, error) {
	dsn := fmt.Sprintf("%s:%s@tcp(%s:%d)/%s?charset=utf8mb4&parseTime=True&loc=Local",
		cfg.Database.Username,
		cfg.Database.Password,
		cfg.Database.Host,
		cfg.Database.Port,
		cfg.Database.Name,
	)

	db, err := gorm.Open(mysql.Open(dsn), &gorm.Config{
		Logger: logger.Default.LogMode(logger.Info),
	})
	if err != nil {
		return nil, err
	}

	sqlDB, err := db.DB()
	if err != nil {
		return nil, err
	}

	sqlDB.SetMaxIdleConns(10)
	sqlDB.SetMaxOpenConns(100)
	sqlDB.SetConnMaxLifetime(time.Hour)

	if err := db.AutoMigrate(
		&model.User{},
		&model.Channel{},
		&model.SmsRecord{},
		&model.BillingLog{},
		&model.Announcement{},
		&model.Setting{},
		&model.Country{},
	); err != nil {
		return nil, err
	}

	return db, nil
}

func setupRouter(
	cfg *config.Config,
	userHandler *handler.UserHandler,
	smsHandler *handler.SmsHandler,
	adminHandler *handler.AdminHandler,
) *gin.Engine {
	gin.SetMode(gin.ReleaseMode)
	router := gin.New()
	router.Use(gin.Logger())
	router.Use(gin.Recovery())
	router.Use(middleware.CorsMiddleware())

	router.GET("/health", func(c *gin.Context) {
		c.JSON(200, gin.H{"status": "ok"})
	})

	api := router.Group("/api")
	{
		api.POST("/login", userHandler.Login)

		user := api.Group("/user")
		user.Use(middleware.AuthMiddleware())
		{
			user.POST("/logout", userHandler.Logout)
			user.GET("/info", userHandler.GetInfo)
		}

		user.GET("/dashboard", middleware.AuthMiddleware(), userHandler.GetDashboard)
		user.GET("/announcement", func(c *gin.Context) {
			response.Success(c, gin.H{
				"title":   "系统公告",
				"content": "欢迎使用短信平台",
			})
		})

		api.POST("/sms/send", middleware.AuthMiddleware(), smsHandler.Send)
		api.GET("/sms/records", middleware.AuthMiddleware(), smsHandler.GetRecords)
		api.GET("/sms/activity/:task_id", middleware.AuthMiddleware(), smsHandler.GetActivity)

		admin := api.Group("/admin")
		{
			admin.POST("/login", userHandler.Login)

			admin.Use(middleware.AuthMiddleware(), middleware.AdminMiddleware())
			{
				admin.GET("/dashboard", adminHandler.GetDashboard)
				admin.GET("/users", adminHandler.GetUsers)
				admin.POST("/users", adminHandler.CreateUser)
				admin.GET("/users/:id", adminHandler.GetUser)
				admin.PUT("/users/:id", adminHandler.UpdateUser)
				admin.DELETE("/users/:id", adminHandler.DeleteUser)
				admin.PUT("/users/:id/balance", adminHandler.UpdateBalance)

				admin.GET("/channels", adminHandler.GetChannels)
				admin.POST("/channels", adminHandler.CreateChannel)
				admin.GET("/channels/:id", adminHandler.GetChannel)
				admin.PUT("/channels/:id", adminHandler.UpdateChannel)
				admin.DELETE("/channels/:id", adminHandler.DeleteChannel)

				admin.GET("/sms/records", adminHandler.GetSmsRecords)
				admin.GET("/sms/export", adminHandler.ExportSmsRecords)

				admin.POST("/announcement", adminHandler.CreateAnnouncement)
				admin.GET("/settings", adminHandler.GetSettings)
				admin.PUT("/settings", adminHandler.UpdateSettings)
				admin.GET("/countries", adminHandler.GetCountries)
			}
		}
	}

	return router
}
