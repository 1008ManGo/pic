package handler

import (
	"github.com/gin-gonic/gin"
	"sms-gateway/internal/service"
	"sms-gateway/pkg/response"
)

type UserHandler struct {
	userSvc *service.UserService
}

func NewUserHandler(userSvc *service.UserService) *UserHandler {
	return &UserHandler{userSvc: userSvc}
}

func (h *UserHandler) Login(c *gin.Context) {
	var req struct {
		Username string `json:"username" binding:"required"`
		Password string `json:"password" binding:"required"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		response.BadRequest(c, "参数错误")
		return
	}

	user, token, err := h.userSvc.Login(req.Username, req.Password)
	if err != nil {
		switch err {
		case service.ErrUserNotFound, service.ErrInvalidPassword:
			response.FailWithMsg(c, response.CodeLoginFailed, "用户名或密码错误")
		case service.ErrUserDisabled:
			response.FailWithMsg(c, response.CodeUserDisabled, "用户已禁用")
		default:
			response.InternalServerError(c)
		}
		return
	}

	response.Success(c, gin.H{
		"token": token,
		"user_info": gin.H{
			"id":           user.ID,
			"username":     user.Username,
			"balance":      user.Balance,
			"smpp_channel": user.SmppChannel,
			"country_code": user.CountryCode,
			"price":        user.Price,
			"role":         user.Role,
			"status":       user.Status,
		},
	})
}

func (h *UserHandler) Logout(c *gin.Context) {
	response.SuccessMsg(c, "登出成功")
}

func (h *UserHandler) GetInfo(c *gin.Context) {
	userID, _ := c.Get("user_id")

	user, err := h.userSvc.GetByID(userID.(int64))
	if err != nil {
		response.Fail(c, response.CodeServerError)
		return
	}

	response.Success(c, gin.H{
		"id":           user.ID,
		"username":     user.Username,
		"balance":      user.Balance,
		"smpp_channel": user.SmppChannel,
		"country_code": user.CountryCode,
		"price":        user.Price,
		"role":         user.Role,
		"status":       user.Status,
	})
}

func (h *UserHandler) GetDashboard(c *gin.Context) {
	userID, _ := c.Get("user_id")

	user, err := h.userSvc.GetByID(userID.(int64))
	if err != nil {
		response.Fail(c, response.CodeServerError)
		return
	}

	response.Success(c, gin.H{
		"balance": user.Balance,
		"channel": user.SmppChannel,
		"country": user.CountryCode,
		"price":   user.Price,
	})
}
