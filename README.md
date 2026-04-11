# 短信平台 设计文档

## 一、技术栈

| 组件 | 技术 | 说明 |
|------|------|------|
| 前端 | PHP 8.0+ | Laravel/原生 |
| 后端 | Go 1.22+ | SMS Gateway |
| 消息队列 | Redis Stream | 队列+计数器 |
| 数据库 | MySQL 8.0 | 持久化存储 |
| 缓存 | Redis | 缓存+Session |
| 协议 | gosmpp | SMPP3.4短信通道协议 |
| 手机号解析 | libphonenumber-go | 号码标准化 |
| 编码 | GSM-7 / UCS-2 | 短信编码 |
| 部署 | Docker | 一键部署 |

---

## 三、数据库设计

### 3.1 表结构

```sql
-- 用户表（核心）
CREATE TABLE users (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(64) UNIQUE NOT NULL COMMENT '用户名',
    password VARCHAR(255) NOT NULL COMMENT '密码(加密)',
    balance DECIMAL(10,4) DEFAULT 0 COMMENT '当前余额',
    smpp_channel VARCHAR(32) NOT NULL COMMENT 'SMPP通道ID',
    country_code CHAR(2) NOT NULL COMMENT '国家代码',
    price DECIMAL(10,4) NOT NULL COMMENT '单价(元/条)',    
    role ENUM('user', 'admin') DEFAULT 'user' COMMENT '身份组',
    status TINYINT DEFAULT 1 COMMENT '状态 1正常 0禁用',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) COMMENT '用户表';

-- SMPP通道表
CREATE TABLE channels (
    id VARCHAR(32) PRIMARY KEY COMMENT '通道ID(唯一)',
    name VARCHAR(64) NOT NULL COMMENT '通道名称',
    ip VARCHAR(64) NOT NULL COMMENT 'IP地址',
    port INT DEFAULT 2775 COMMENT '端口',
    username VARCHAR(64) COMMENT 'SMPP用户名',
    password VARCHAR(255) COMMENT 'SMPP密码',
    max_tps INT DEFAULT 50 COMMENT '最大TPS',
    status ENUM('active','error','stopped') DEFAULT 'active' COMMENT '状态',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) COMMENT 'SMPP通道表';

-- 短信记录表
CREATE TABLE sms_records (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    task_id VARCHAR(64) NOT NULL COMMENT '任务ID',
    user_id BIGINT NOT NULL COMMENT '用户ID',
    channel_id VARCHAR(32) COMMENT '通道ID',
    country_code CHAR(2) NOT NULL COMMENT '国家代码',
    sender_id VARCHAR(21) COMMENT '发件人ID',
    phone VARCHAR(32) NOT NULL COMMENT '目标手机号码',
    content TEXT NOT NULL COMMENT '短信内容',
    encoding ENUM('GSM7','UCS2') NOT NULL COMMENT '编码',
    sms_count INT NOT NULL COMMENT '计费条数',
    price DECIMAL(10,4) NOT NULL COMMENT '单价',
    total_price DECIMAL(10,4) NOT NULL COMMENT '总费用',
    status ENUM('pending','submitted','success','failed','unknown','error') DEFAULT 'pending' COMMENT '状态',
    error_msg TEXT COMMENT '错误信息',
    submit_time TIMESTAMP NULL COMMENT '提交时间',
    done_time TIMESTAMP NULL COMMENT '完成时间',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_task_id (task_id),
    INDEX idx_user_id (user_id),
    INDEX idx_phone (phone),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) COMMENT '短信记录表';

-- 计费记录表
CREATE TABLE billing_log (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL COMMENT '用户ID',
    task_id VARCHAR(64) NOT NULL COMMENT '任务ID',
    sms_count INT NOT NULL COMMENT '计费条数',
    amount DECIMAL(10,4) NOT NULL COMMENT '扣费金额',
    balance_before DECIMAL(10,4) NOT NULL COMMENT '扣前余额',
    balance_after DECIMAL(10,4) NOT NULL COMMENT '扣后余额',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id)
) COMMENT '计费记录表';

-- 公告表
CREATE TABLE announcements (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL COMMENT '标题',
    content TEXT NOT NULL COMMENT '内容',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) COMMENT '公告表';

-- 系统设置表
CREATE TABLE settings (
    key_name VARCHAR(64) PRIMARY KEY,
    value TEXT COMMENT '值',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) COMMENT '系统设置表';

-- 国家代码表(基础数据)
CREATE TABLE countries (
    code CHAR(2) PRIMARY KEY COMMENT '国家代码',
    name VARCHAR(64) NOT NULL COMMENT '国家名称'
) COMMENT '国家代码表';
```

### 3.2 初始化数据

```sql
-- 插入默认设置
INSERT INTO settings (key_name, value) VALUES
('site_name', '短信平台'),
('allow_register', 'true');
```

---

## 四、API 接口设计

### 4.1 用户端 API

| # | 接口路径 | 方法 | 说明 | 请求参数 | 返回数据 |
|---|----------|------|------|----------|----------|
| 1 | `/api/login` | POST | 用户登录 | username, password | token, user_info |
| 2 | `/api/user/logout` | POST | 用户登出 | - | {code:0} |
| 3 | `/api/user/info` | GET | 获取用户信息 | - | 用户信息(含通道/国家/价格) |
| 4 | `/api/dashboard` | GET | 仪表盘数据 | - | 余额,统计 |
| 5 | `/api/announcement` | GET | 获取公告 | - | 公告内容 |
| 6 | `/api/sms/send` | POST | 发送短信 | phones[], content | task_id, total, cost |
| 7 | `/api/sms/records` | GET | 短信记录 | page, limit, filters | 记录列表 |
| 8 | `/api/sms/activity/{task_id}` | GET | 活动进度 | task_id | 提交/成功/失败/错误数 |

### 4.2 管理端 API

| # | 接口路径 | 方法 | 说明 | 请求参数 | 返回数据 |
|---|----------|------|------|----------|----------|
| 1 | `/api/login` | POST | 管理员登录 | username, password | token |
| 2 | `/api/admin/dashboard` | GET | 全局仪表盘 | - | 用户数/消费/发送统计 |
| 3 | `/api/admin/users` | GET | 用户列表 | page, limit | 用户列表 |
| 4 | `/api/admin/users` | POST | 添加用户 | user信息 | user_id |
| 5 | `/api/admin/users/{id}` | GET | 用户详情 | id | 用户详情 |
| 6 | `/api/admin/users/{id}` | PUT | 修改用户 | user信息 | {code:0} |
| 7 | `/api/admin/users/{id}` | DELETE | 删除用户 | id | {code:0} |
| 8 | `/api/admin/users/{id}/balance` | PUT | 调整余额 | balance | {code:0} |
| 9 | `/api/admin/channels` | GET | 通道列表 | - | 通道列表 |
| 10 | `/api/admin/channels` | POST | 添加通道 | channel信息 | channel_id |
| 11 | `/api/admin/channels/{id}` | GET | 通道详情 | id | 通道详情 |
| 12 | `/api/admin/channels/{id}` | PUT | 修改通道 | channel信息 | {code:0} |
| 13 | `/api/admin/channels/{id}` | DELETE | 删除通道 | id | {code:0} |
| 14 | `/api/admin/sms/records` | GET | 短信记录 | page, filters | 记录列表 |
| 15 | `/api/admin/sms/export` | GET | 导出记录 | filters | CSV文件 |
| 16 | `/api/admin/announcement` | POST | 发布公告 | title, content | {code:0} |
| 17 | `/api/admin/settings` | GET | 获取设置 | - | 设置项 |
| 18 | `/api/admin/settings` | PUT | 修改设置 | settings | {code:0} |
| 19 | `/api/admin/countries` | GET | 国家列表 | - | 国家列表 |

### 4.3 发送短信接口(核心)

```json
// POST /api/sms/send
// Request (前端只传这些)
{
    "phones": [
        "+8613800000000",
        "+8613900001111"
    ],
    "content": "您的验证码是1234"
}

// Response
{
    "code": 0,
    "message": "success",
    "data": {
        "task_id": "20260111_001_abc123",
        "total_phones": 2,
        "sms_count": 2,
        "price_per_sms": 0.05,
        "total_cost": 0.10,
        "balance_after": 999.90
    }
}

// 后端自动从用户表获取:
// - smpp_channel
// - country_code
// - price
```

### 4.4 错误码

| 错误码 | 说明 |
|--------|------|
| 0 | 成功 |
| 1001 | 余额不足 |
| 1002 | 短信内容包含不支持的字符 |
| 1003 | 手机号格式错误 |
| 1004 | 用户已禁用 |
| 2001 | 通道不可用 |
| 3001 | 登录失败 |
| 3002 | 无权限 |
| 3003 | Token过期(请重新登录) |

---

## 五、Go 后端模块

### 5.1 项目结构

```
sms-gateway/
├── cmd/
│   └── server/
│       └── main.go
├── internal/
│   ├── config/
│   │   └── config.go
│   ├── handler/
│   │   ├── user.go
│   │   ├── sms.go
│   │   └── admin.go
│   ├── middleware/
│   │   ├── auth.go
│   │   └── cors.go
│   ├── model/
│   │   └── model.go
│   ├── service/
│   │   ├── user.go
│   │   ├── sms.go
│   │   ├── billing.go
│   │   └── channel.go
│   ├── queue/
│   │   └── redis_stream.go
│   ├── worker/
│   │   ├── consumer.go
│   │   └── dlr.go
│   ├── parser/
│   │   └── phone.go
│   ├── encoder/
│   │   └── sms.go
│   ├── smpp/
│   │   └── client.go
│   └── tps/
│       └── limiter.go
├── pkg/
│   └── response/
│       └── response.go
├── configs/
│   └── config.yaml
└── go.mod
```

### 5.2 核心数据模型

```go
// 用户模型
type User struct {
    ID           int64
    Username     string
    Password     string
    Balance      float64
    SmppChannel  string    // 用户绑定的SMPP通道
    CountryCode  string    // 用户可发送的国家
    Price        float64   // 该国家单价
    Role         string    // user / admin
    Status       int       // 1正常 0禁用
    CreatedAt    time.Time
    UpdatedAt    time.Time
}

// 短信任务(队列消息)
type SmsTask struct {
    TaskID      string    // 任务ID(批次号)
    UserID      int64
    Username     string
    ChannelID   string    // 从用户表获取
    CountryCode string    // 从用户表获取
    Price       float64   // 从用户表获取
    SenderID    string
    Phone       string
    Content     string
    SubmitTime  time.Time
}

// 短信记录
type SmsRecord struct {
    ID          int64
    TaskID      string
    UserID      int64
    Username    string
    ChannelID   string
    CountryCode string
    SenderID    string
    Phone       string
    Content     string
    Encoding    string    // GSM7 / UCS2
    SmsCount    int
    Price       float64
    TotalPrice  float64
    Status      string    // pending/submitted/success/failed/unknown/error
    ErrorMsg    string
    SubmitTime  *time.Time
    DoneTime    *time.Time
    CreatedAt   time.Time
}

// 通道配置
type Channel struct {
    ID                string
    Name              string
    IP                string
    Port              int
    Username          string
    Password          string
    MaxTPS            int
    HeartbeatInterval int
    Status            string // active/error/stopped
}
```

---

## 六、Redis Stream 队列设计

### 6.1 Stream 键

| 键名 | 类型 | 用途 | 消费者组 |
|------|------|------|----------|
| `sms:tasks` | Stream | 短信任务队列 | `sms-workers` |
| `sms:dlq` | Stream | 死信队列6小时清理 | - |
| `sms:dlr` | Stream | 状态回执队列 | `dlr-workers` |

### 6.2 消息格式

```json
// sms:tasks 消息
{
    "task_id": "20260111_001_abc123",
    "user_id": "1001",
    "channel_id": "CH_001",
    "country_code": "CN",
    "price": "0.0500",
    "sender_id": "xxxxx",
    "phone": "+8613800000000",
    "content": "您的验证码是1234",
    "submit_time": "1704969600"
}
```

---

## 七、短信处理流程

```
用户提交
    │
    ▼
PHP 检验清洗手机号码 + 短信内容emoji过滤
    │
    ▼
调用 /api/sms/send
    │
    ├─── 后端检查余额
    ├─── 后端从用户表获取 channel_id, country_code, price
    ├─── 计算短信条数和费用
    └─── 原子扣费
    │
    ▼
生成 task_id，短信入 Redis Stream (sms:tasks)
    │
    ▼
返回 task_id 给前端
    │
    ▼
Go Worker 消费 sms:tasks
    │
    ├─── 号码解析 + 标准化
    ├─── 编码检测 (英文GSM7/非英文UCS2)
    ├─── 短信分段
    ├─── TPS Window窗口滑动 (redis)
    └─── SMPP 发送
    │
    ├─── 成功 → status=success
    ├─── 失败 → status=failed + 错误信息
    └─── 未知 → status=unknown
    │
    ▼
更新 MySQL sms_records 表
    │
    ▼
活动进度实时可查
```

---

## 八、短信分段规则

| 编码 | 单条 | concat |
|------|------|--------|
| 英文GSM-7 | 160字符 | 153字符/条 |
| 非英文UCS-2 | 70字符 | 67字符/条 |

---

## 九、安全措施

| 措施 | 说明 |
|------|------|
| 通道隔离 | 用户只能用自己的通道，无法指定 |
| 余额校验 | 扣费前检查，不够直接拒绝 |
| Token认证 | JWT/Session 验证 |
| 前端只传手机+内容 | 通道/国家/价格/编码/分段/扣费后端处理 |
| 日志审计 | 所有操作记录到日志 |
| Emoji过滤 | 不支持emoji内容 |

---

## 十、Docker 部署

### 10.1 目录结构

```
sms-platform/
├── docker-compose.yml
├── docker/
│   ├── nginx/
│   │   └── nginx.conf
│   ├── php/
│   │   ├── Dockerfile
│   │   └── php.ini
│   ├── go/
│   │   └── Dockerfile
│   └── mysql/
│       └── my.cnf
├── app/
│   ├── php/              # PHP前端代码
│   └── gateway/          # Go SMS Gateway
├── data/
│   ├── mysql/            # MySQL数据卷
│   └── redis/            # Redis数据卷
└── scripts/
    └── init.sql          # 数据库初始化
```

### 10.2 一键启动

```bash
#!/bin/bash
# start.sh

echo "=== 短信平台 Docker 部署 ==="

# 创建数据目录
mkdir -p data/mysql data/redis

# 启动服务
docker-compose up -d --build

# 等待服务就绪
echo "等待服务启动..."
sleep 10

# 检查状态
docker-compose ps

echo ""
echo "=== 部署完成 ==="
echo "访问: http://ip:15789"
echo "API:  http://ip:15789/api"
```

---

## 十一、监控指标

| 指标 | 采集方式 | 告警阈值 |
|------|----------|----------|
| Go进程存活 | ps监控 | 进程不存在 |
| 内存使用 | /proc | >6GB |
| Redis连接 | info | >1000 |
| MySQL连接 | show status | >150 |
| 队列积压 | XLEN | >50000 |
| 成功率 | 统计 | <90% |
| 通道活跃连接 | 检测 | =0持续1min |
