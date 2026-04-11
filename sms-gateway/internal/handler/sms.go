package handler

import (
	"strconv"

	"github.com/gin-gonic/gin"
	"sms-gateway/internal/service"
	"sms-gateway/pkg/response"
)

type SmsHandler struct {
	smsSvc *service.SmsService
}

func NewSmsHandler(smsSvc *service.SmsService) *SmsHandler {
	return &SmsHandler{smsSvc: smsSvc}
}

func (h *SmsHandler) Send(c *gin.Context) {
	var req struct {
		Phones  []string `json:"phones" binding:"required,min=1"`
		Content string   `json:"content" binding:"required"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		response.BadRequest(c, "参数错误：phones和content不能为空")
		return
	}

	userID, _ := c.Get("user_id")

	result, err := h.smsSvc.Send(c.Request.Context(), userID.(int64), req.Phones, req.Content)
	if err != nil {
		switch err {
		case service.ErrInsufficientBalance:
			response.FailWithMsg(c, response.CodeBalanceInsufficient, "余额不足")
		case service.ErrUserDisabled:
			response.FailWithMsg(c, response.CodeUserDisabled, "用户已禁用")
		case service.ErrInvalidContent:
			response.FailWithMsg(c, response.CodeInvalidChar, "短信内容包含不支持的字符")
		case service.ErrInvalidPhone:
			response.FailWithMsg(c, response.CodeInvalidPhone, "手机号格式错误")
		default:
			response.InternalServerError(c)
		}
		return
	}

	response.Success(c, result)
}

func (h *SmsHandler) GetRecords(c *gin.Context) {
	userID, _ := c.Get("user_id")

	page, _ := strconv.Atoi(c.DefaultQuery("page", "1"))
	limit, _ := strconv.Atoi(c.DefaultQuery("limit", "20"))
	status := c.Query("status")

	if page < 1 {
		page = 1
	}
	if limit < 1 || limit > 100 {
		limit = 20
	}

	records, total, err := h.smsSvc.GetRecords(userID.(int64), page, limit, status)
	if err != nil {
		response.InternalServerError(c)
		return
	}

	response.Success(c, gin.H{
		"list":  records,
		"total": total,
		"page":  page,
		"limit": limit,
	})
}

func (h *SmsHandler) GetActivity(c *gin.Context) {
	taskID := c.Param("task_id")
	if taskID == "" {
		response.BadRequest(c, "task_id不能为空")
		return
	}

	activity, err := h.smsSvc.GetTaskActivity(taskID)
	if err != nil {
		response.InternalServerError(c)
		return
	}

	response.Success(c, activity)
}
