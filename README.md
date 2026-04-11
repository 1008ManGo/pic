短信平台设计文档
技术栈：
前端技术栈使用PHP，
统一登录入口，根据登录的用户组身份跳转对应后台。用户前端提交短信携带以下内容入RabbitMQ队列(任务id,用户ID,国家代码,通道标识,SenderID,手机号码,短信内容)
用户组(管理员/普通用户)
A.用户前端界面：
1. 仪表盘：公告栏，显示当前余额，可发送国家+定价，总发送短信数量，今日发送短信数量，发送成功/未知/失败/错误数量。
2. 发送短信：选择发送通道列表框，SenderID输入框，手机号码框(一行一个)或者直接上传txt文件，短信内容输入框(实时识别编码，字符，条数(这里是为了防止用户不知道输入多少字每条短信算作多少条，分段规则GSM7：160/1条,超出153/1条。UCS2：70/1条, 超出67/1条))，提交按钮   通过这个方法限制用户输入emojiif (/\p{Extended_Pictographic}/u.test(text)) {
  alert("不支持emoji");
  return;
}
短信提交成功后跳转到短信活动界面,方便用户追踪短信发送进度
3. 短信记录：可筛选任务ID, SenderID，国家，手机号码，日期，可导出发送记录。
4.短信活动：每分钟更新发送进度(比如已提交多少, 已发送多少, 成功多少, 失败多少, 错误多少)

B.管理员前端界面：
1. 仪表盘：总用户数量/总消费金额/总发送短信数量，今日发送短信数量。发送成功/未知/失败/错误数量。
2.用户管理：添加/删除用户，显示所有用户列表➡️点击用户可以进入用户管理：每个账户只能设置1个国家/设置修改定价，分配通道，修改用户名/密码，增减余额。
3.通道管理：添加/删除SMPP通道(通道创建时必须设置唯一标识，后面不可修改)，显示所有通道列表(活跃/异常/停止)➡点击进入修改通道，ip地址/账户/密码/TPS/绑定列表框(tx.rz.trx)/最大连接数量/心跳间隔/是否断线重连
后端一定要配合好实时生效
4.短信记录：可筛选用户，SenderID，国家，手机号码，日期，可导出发送记录
5.全局公告发布：标题栏，内容，发布
6.基本设置：修改站点名称，logo，是否开启用户注册功能。

下面给你一套Go + SMPP 短信网关技术栈设计（精简但生产级结构），完全按你描述的流程拆出来，不做复杂废话。

一、整体技术栈
1. 基础组件
* Go 1.22+
* RabbitMQ（任务队列）
* Redis（TPS控制 / 滑动窗口 / 去重 / 状态缓存）
* MySQL （用户、通道、计费、日志）
* Prometheus + Grafana（TPS / 成功率 / 延迟监控）
RabbitMQ	github.com/rabbitmq/amqp091-go
SMPP	github.com/linxGnu/gosmpp
Redis（余额/锁）	github.com/redis/go-redis/v9
JSON处理	标准库
手机号解析	github.com/nyaruka/phonenumbers
二、核心模块划分（Go SMPP Gateway）
1. MQ消费模块（Consumer Worker）
职责：
* 从 RabbitMQ 拉取短信任务
* 解析任务结构
type SmsTask struct {
    TaskID      string
    UserID      int64
    CountryCode string
    ChannelID   string
    SenderID    string
    Phone       string
    Content     string
}

2. 用户与计费模块（Billing Service）
核心逻辑：
需要查：
* 用户余额
* 国家价格
* 通道标识
流程：
余额检查 -> 国家/通道校验 -> 直接扣费


3. 手机号标准化模块（MSISDN Parser）
功能：
* 去空格/符号
* 自动补 +
* 国家识别（libphonenumber-Go）
结果结构：
type ParsedNumber struct {
    E164        string // +8613800000000
    CountryCode string // CN / US
    Valid       bool
}

4. 短信拆分编码模块（Encoding Engine）
GSM7 / UCS2 判断：
func DetectEncoding(text string) EncodingType

分段规则：
GSM7
* 160 / 1条
* 153 / concat
UCS2
* 70 / 1条
* 67 / concat
type SmsPart struct {
    Index int
    Total int
    Text  string
    UDH   string
}

5. 通道管理模块（Channel Router）
功能：
* 根据 channel_id 找 SMPP connection pool
* 支持多连接负载均衡
type Channel struct {
    ID        string
    SmppConns []*SmppClient
    MaxTPS    int
}

6. SMPP连接层（核心）
设计重点：
* 每个通道最大连接数量
* 该通道所有连接共享TPS
* 自动重连
* 心跳时间
* 通道状态检测
* 窗口滑动（windowing）对标该通道共享TPS

SMPP Client结构：
type SmppClient struct {
    Conn       net.Conn
    TPSLimiter *TPSWindow
    Seq        uint32
}

7. TPS滑动窗口（关键）
逻辑：
* 每秒允许 N 条
* Redis 或内存 token bucket
推荐：Redis + Lua 或本地令牌桶
AllowSend(channelID string) bool

简化模型：
window = 1s
max = 100 TPS

每次发送：
- 检查当前窗口已用数量
- 未超：允许发送
- 超过：sleep / delay queue

8. 发送执行模块（Sender Engine）
流程：
任务 -> 编码拆分 -> 获取通道 -> TPS检查 -> SMPP发送

发送逻辑：
resp, err := smppClient.SendSMS(pdu)

9. 状态与DLR模块（Delivery Report Handler）
处理：
* success
* failed
* unknown
* error
* delivered
SMPP DLR回调按真实回调填入

10. 计费结算模块（Final Billing）
状态决定扣费：
状态	是否扣费
SMPP发送失败	❌不扣
号码无效不发送	❌不扣
余额不足不发送	❌不扣
通道不匹配不发送	❌不扣
成功/DELIVRD	✅扣费
UNDELIV	☑️扣费
三、核心数据流
Frontend
   ↓
RabbitMQ
   ↓
Go Worker
   ↓
号码解析 + 校验
   ↓
计费扣费
   ↓
编码拆分
   ↓
Channel Router
   ↓
TPS Window
   ↓
SMPP Send
   ↓
DLR回调仅提供状态
