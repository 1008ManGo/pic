package handler

import (
	"strconv"

	"github.com/gin-gonic/gin"
	"sms-gateway/internal/model"
	"sms-gateway/internal/service"
	"sms-gateway/pkg/response"
)

type AdminHandler struct {
	userSvc    *service.UserService
	channelSvc *service.ChannelService
	smsSvc     *service.SmsService
}

func NewAdminHandler(
	userSvc *service.UserService,
	channelSvc *service.ChannelService,
	smsSvc *service.SmsService,
) *AdminHandler {
	return &AdminHandler{
		userSvc:    userSvc,
		channelSvc: channelSvc,
		smsSvc:     smsSvc,
	}
}

func (h *AdminHandler) GetDashboard(c *gin.Context) {
	response.Success(c, gin.H{
		"message": "admin dashboard",
	})
}

func (h *AdminHandler) GetUsers(c *gin.Context) {
	page, _ := strconv.Atoi(c.DefaultQuery("page", "1"))
	limit, _ := strconv.Atoi(c.DefaultQuery("limit", "20"))

	if page < 1 {
		page = 1
	}
	if limit < 1 || limit > 100 {
		limit = 20
	}

	users, total, err := h.userSvc.List(page, limit)
	if err != nil {
		response.InternalServerError(c)
		return
	}

	response.Success(c, gin.H{
		"list":  users,
		"total": total,
		"page":  page,
		"limit": limit,
	})
}

func (h *AdminHandler) CreateUser(c *gin.Context) {
	var user model.User
	if err := c.ShouldBindJSON(&user); err != nil {
		response.BadRequest(c, "参数错误")
		return
	}

	if err := h.userSvc.Create(&user); err != nil {
		response.InternalServerError(c)
		return
	}

	response.Success(c, gin.H{"user_id": user.ID})
}

func (h *AdminHandler) GetUser(c *gin.Context) {
	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		response.BadRequest(c, "无效的用户ID")
		return
	}

	user, err := h.userSvc.GetByID(id)
	if err != nil {
		response.Fail(c, response.CodeServerError)
		return
	}

	response.Success(c, user)
}

func (h *AdminHandler) UpdateUser(c *gin.Context) {
	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		response.BadRequest(c, "无效的用户ID")
		return
	}

	var req struct {
		Username    string  `json:"username"`
		Password    string  `json:"password"`
		Balance     float64 `json:"balance"`
		SmppChannel string  `json:"smpp_channel"`
		CountryCode string  `json:"country_code"`
		Price       float64 `json:"price"`
		Role        string  `json:"role"`
		Status      int     `json:"status"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		response.BadRequest(c, "参数错误")
		return
	}

	fields := make(map[string]interface{})
	if req.Username != "" {
		fields["username"] = req.Username
	}
	if req.Password != "" {
		hashedPassword, err := service.HashPassword(req.Password)
		if err != nil {
			response.InternalServerError(c)
			return
		}
		fields["password"] = hashedPassword
	}
	if req.SmppChannel != "" {
		fields["smpp_channel"] = req.SmppChannel
	}
	if req.CountryCode != "" {
		fields["country_code"] = req.CountryCode
	}
	if req.Role != "" {
		fields["role"] = req.Role
	}

	if err := h.userSvc.UpdateFields(id, fields); err != nil {
		response.InternalServerError(c)
		return
	}

	response.SuccessMsg(c, "更新成功")
}

func (h *AdminHandler) DeleteUser(c *gin.Context) {
	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		response.BadRequest(c, "无效的用户ID")
		return
	}

	if err := h.userSvc.Delete(id); err != nil {
		response.InternalServerError(c)
		return
	}

	response.SuccessMsg(c, "删除成功")
}

func (h *AdminHandler) UpdateBalance(c *gin.Context) {
	id, err := strconv.ParseInt(c.Param("id"), 10, 64)
	if err != nil {
		response.BadRequest(c, "无效的用户ID")
		return
	}

	var req struct {
		Balance float64 `json:"balance" binding:"required"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		response.BadRequest(c, "参数错误")
		return
	}

	if err := h.userSvc.UpdateBalance(id, req.Balance); err != nil {
		response.InternalServerError(c)
		return
	}

	response.SuccessMsg(c, "余额调整成功")
}

func (h *AdminHandler) GetChannels(c *gin.Context) {
	channels, err := h.channelSvc.List()
	if err != nil {
		response.InternalServerError(c)
		return
	}

	response.Success(c, channels)
}

func (h *AdminHandler) CreateChannel(c *gin.Context) {
	var channel model.Channel
	if err := c.ShouldBindJSON(&channel); err != nil {
		response.BadRequest(c, "参数错误")
		return
	}

	if err := h.channelSvc.Create(&channel); err != nil {
		response.InternalServerError(c)
		return
	}

	response.Success(c, gin.H{"channel_id": channel.ID})
}

func (h *AdminHandler) GetChannel(c *gin.Context) {
	id := c.Param("id")

	channel, err := h.channelSvc.GetByID(id)
	if err != nil {
		response.Fail(c, response.CodeServerError)
		return
	}

	response.Success(c, channel)
}

func (h *AdminHandler) UpdateChannel(c *gin.Context) {
	id := c.Param("id")

	var req struct {
		Name     string `json:"name"`
		IP       string `json:"ip"`
		Port     int    `json:"port"`
		Username string `json:"username"`
		Password string `json:"password"`
		MaxTPS   int    `json:"max_tps"`
		Status   string `json:"status"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		response.BadRequest(c, "参数错误")
		return
	}

	fields := make(map[string]interface{})
	if req.Name != "" {
		fields["name"] = req.Name
	}
	if req.IP != "" {
		fields["ip"] = req.IP
	}
	if req.Port != 0 {
		fields["port"] = req.Port
	}
	if req.Username != "" {
		fields["username"] = req.Username
	}
	if req.Password != "" {
		fields["password"] = req.Password
	}
	if req.MaxTPS != 0 {
		fields["max_tps"] = req.MaxTPS
	}
	if req.Status != "" {
		fields["status"] = req.Status
	}

	if err := h.channelSvc.UpdateFields(id, fields); err != nil {
		response.InternalServerError(c)
		return
	}

	response.SuccessMsg(c, "更新成功")
}

func (h *AdminHandler) DeleteChannel(c *gin.Context) {
	id := c.Param("id")

	if err := h.channelSvc.Delete(id); err != nil {
		response.InternalServerError(c)
		return
	}

	response.SuccessMsg(c, "删除成功")
}

func (h *AdminHandler) GetSmsRecords(c *gin.Context) {
	page, _ := strconv.Atoi(c.DefaultQuery("page", "1"))
	limit, _ := strconv.Atoi(c.DefaultQuery("limit", "20"))
	status := c.Query("status")

	if page < 1 {
		page = 1
	}
	if limit < 1 || limit > 100 {
		limit = 20
	}

	records, total, err := h.smsSvc.GetRecords(0, page, limit, status)
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

func (h *AdminHandler) ExportSmsRecords(c *gin.Context) {
	response.Success(c, gin.H{
		"message": "export functionality",
	})
}

func (h *AdminHandler) CreateAnnouncement(c *gin.Context) {
	response.SuccessMsg(c, "公告发布成功")
}

func (h *AdminHandler) GetSettings(c *gin.Context) {
	response.Success(c, gin.H{
		"site_name":      "短信平台",
		"allow_register": "true",
	})
}

func (h *AdminHandler) UpdateSettings(c *gin.Context) {
	response.SuccessMsg(c, "设置更新成功")
}

func (h *AdminHandler) GetCountries(c *gin.Context) {
	countries := []model.Country{
		{Code: "CN", Name: "中国"},
		{Code: "US", Name: "美国"},
		{Code: "GB", Name: "英国"},
		{Code: "JP", Name: "日本"},
		{Code: "KR", Name: "韩国"},
		{Code: "IN", Name: "印度"},
	}
	response.Success(c, countries)
}
