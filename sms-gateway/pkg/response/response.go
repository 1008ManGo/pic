package response

import (
	"net/http"

	"github.com/gin-gonic/gin"
)

type Response struct {
	Code int         `json:"code"`
	Msg  string      `json:"message"`
	Data interface{} `json:"data,omitempty"`
}

const (
	CodeSuccess             = 0
	CodeBalanceInsufficient = 1001
	CodeInvalidChar         = 1002
	CodeInvalidPhone        = 1003
	CodeUserDisabled        = 1004
	CodeChannelUnavailable  = 2001
	CodeLoginFailed         = 3001
	CodeNoPermission        = 3002
	CodeTokenExpired        = 3003
	CodeParamError          = 4001
	CodeServerError         = 5000
)

var codeMessages = map[int]string{
	CodeSuccess:             "success",
	CodeBalanceInsufficient: "余额不足",
	CodeInvalidChar:         "短信内容包含不支持的字符",
	CodeInvalidPhone:        "手机号格式错误",
	CodeUserDisabled:        "用户已禁用",
	CodeChannelUnavailable:  "通道不可用",
	CodeLoginFailed:         "登录失败",
	CodeNoPermission:        "无权限",
	CodeTokenExpired:        "Token过期，请重新登录",
	CodeParamError:          "参数错误",
	CodeServerError:         "服务器内部错误",
}

func Success(c *gin.Context, data interface{}) {
	c.JSON(http.StatusOK, Response{
		Code: CodeSuccess,
		Msg:  "success",
		Data: data,
	})
}

func Error(c *gin.Context, httpCode int, code int, msg string) {
	if msg == "" {
		msg = codeMessages[code]
	}
	c.JSON(httpCode, Response{
		Code: code,
		Msg:  msg,
	})
}

func ErrorWithMsg(c *gin.Context, httpCode int, code int, msg string) {
	c.JSON(httpCode, Response{
		Code: code,
		Msg:  msg,
	})
}

func SuccessMsg(c *gin.Context, msg string) {
	c.JSON(http.StatusOK, Response{
		Code: CodeSuccess,
		Msg:  msg,
	})
}

func Fail(c *gin.Context, code int) {
	Error(c, http.StatusOK, code, "")
}

func FailWithMsg(c *gin.Context, code int, msg string) {
	Error(c, http.StatusOK, code, msg)
}

func BadRequest(c *gin.Context, msg string) {
	Error(c, http.StatusBadRequest, CodeParamError, msg)
}

func Unauthorized(c *gin.Context) {
	Error(c, http.StatusUnauthorized, CodeTokenExpired, "")
}

func Forbidden(c *gin.Context) {
	Error(c, http.StatusForbidden, CodeNoPermission, "")
}

func InternalServerError(c *gin.Context) {
	Error(c, http.StatusInternalServerError, CodeServerError, "")
}
