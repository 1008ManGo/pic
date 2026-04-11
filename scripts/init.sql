-- 短信平台数据库初始化脚本

-- 创建用户表
CREATE TABLE IF NOT EXISTS users (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

-- 创建SMPP通道表
CREATE TABLE IF NOT EXISTS channels (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='SMPP通道表';

-- 创建短信记录表
CREATE TABLE IF NOT EXISTS sms_records (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='短信记录表';

-- 创建计费记录表
CREATE TABLE IF NOT EXISTS billing_log (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL COMMENT '用户ID',
    task_id VARCHAR(64) NOT NULL COMMENT '任务ID',
    sms_count INT NOT NULL COMMENT '计费条数',
    amount DECIMAL(10,4) NOT NULL COMMENT '扣费金额',
    balance_before DECIMAL(10,4) NOT NULL COMMENT '扣前余额',
    balance_after DECIMAL(10,4) NOT NULL COMMENT '扣后余额',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_task_id (task_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='计费记录表';

-- 创建公告表
CREATE TABLE IF NOT EXISTS announcements (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL COMMENT '标题',
    content TEXT NOT NULL COMMENT '内容',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='公告表';

-- 创建系统设置表
CREATE TABLE IF NOT EXISTS settings (
    key_name VARCHAR(64) PRIMARY KEY,
    value TEXT COMMENT '值',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统设置表';

-- 创建国家代码表
CREATE TABLE IF NOT EXISTS countries (
    code CHAR(2) PRIMARY KEY COMMENT '国家代码',
    name VARCHAR(64) NOT NULL COMMENT '国家名称'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='国家代码表';

-- 插入默认设置
INSERT INTO settings (key_name, value) VALUES
('site_name', '短信平台'),
('allow_register', 'true')
ON DUPLICATE KEY UPDATE value=value;

-- 插入国家代码
INSERT INTO countries (code, name) VALUES
('CN', '中国'),
('US', '美国'),
('GB', '英国'),
('JP', '日本'),
('KR', '韩国'),
('IN', '印度'),
('AU', '澳大利亚'),
('CA', '加拿大'),
('DE', '德国'),
('FR', '法国'),
('RU', '俄罗斯'),
('BR', '巴西'),
('ID', '印度尼西亚'),
('TH', '泰国'),
('VN', '越南'),
('MY', '马来西亚'),
('SG', '新加坡'),
('PH', '菲律宾'),
('PK', '巴基斯坦'),
('BD', '孟加拉国'),
('EG', '埃及'),
('ZA', '南非'),
('NG', '尼日利亚'),
('KE', '肯尼亚'),
('AR', '阿根廷'),
('MX', '墨西哥'),
('CL', '智利'),
('CO', '哥伦比亚'),
('PE', '秘鲁')
ON DUPLICATE KEY UPDATE name=name;

-- 插入默认管理员账号 (密码: admin123)
INSERT INTO users (username, password, balance, smpp_channel, country_code, price, role, status) VALUES
('admin', '$2a$10$8K1p/a0dR1xqM8K.QhZK4O1UxQkZRE.m3TqNqM9L4fUG9QbQ5K7a', 10000.0000, 'CH_001', 'CN', 0.0500, 'admin', 1)
ON DUPLICATE KEY UPDATE username=username;

-- 插入示例通道
INSERT INTO channels (id, name, ip, port, username, password, max_tps, status) VALUES
('CH_001', '测试通道', '156.226.175.3', 2775, 'admin', 'aa', 50, 'active')
ON DUPLICATE KEY UPDATE name=name;
