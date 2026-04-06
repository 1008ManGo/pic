# 企业级短信平台 - 安装部署教程

## 目录

- [项目简介](#项目简介)
- [环境要求](#环境要求)
- [快速部署（推荐）](#快速部署推荐)
- [手动部署](#手动部署)
- [验证部署](#验证部署)
- [配置说明](#配置说明)
- [常见问题](#常见问题)

---

## 项目简介

企业级短信平台是一套完整的SMS发送解决方案，支持：

- **单条/批量短信发送**
- **SMPP协议通道对接**
- **多通道负载均衡**
- **实时状态推送**
- **用户管理/充值**
- **财务统计**

## 环境要求

### 最低配置

| 组件 | 最低配置 |
|------|----------|
| CPU | 2核 |
| 内存 | 4GB |
| 磁盘 | 20GB |
| Docker | 20.x+ |
| Docker Compose | 2.x+ |

### 推荐配置

| 组件 | 推荐配置 |
|------|----------|
| CPU | 4核+ |
| 内存 | 8GB+ |
| 磁盘 | 50GB+ |
| Docker | 最新版 |
| Docker Compose | 最新版 |

---

## 快速部署（推荐）

### 一键部署

```bash
# 克隆项目
git clone https://github.com/1008ManGo/pic.git
cd pic

# 启动所有服务（后端+前端+数据库+Redis+RabbitMQ）
docker-compose up -d

# 查看服务状态
docker-compose ps
```

### 访问地址

| 服务 | 地址 |
|------|------|
| 前端界面 | http://localhost |
| 后端API | http://localhost:5000 |
| Swagger文档 | http://localhost:5000/swagger |
| RabbitMQ管理 | http://localhost:15672 |
| Hangfire任务 | http://localhost:5000/hangfire |

### 默认账号

```
管理员账号: admin
管理员密码: 请在注册后通过数据库设置
```

---

## 手动部署

### 前置准备

```bash
# 安装 Git
sudo apt-get update
sudo apt-get install -y git

# 安装 Docker
curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker $USER

# 安装 Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/download/v2.24.0/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
```

### 方式一：纯后端部署

```bash
# 1. 克隆项目
git clone https://github.com/1008ManGo/pic.git
cd pic

# 2. 配置数据库连接
# 编辑 SmsPlatform.Api/appsettings.json
# 修改 ConnectionStrings__DefaultConnection

# 3. 构建并运行
cd SmsPlatform.Api
dotnet restore
dotnet build
dotnet run --urls "http://0.0.0.0:5000"

# 4. 验证
curl http://localhost:5000/swagger
```

### 方式二：前后端分离部署

#### 后端部署

```bash
# 1. 启动依赖服务
docker-compose up -d mysql redis rabbitmq

# 2. 配置后端
cd pic
vim SmsPlatform.Api/appsettings.json

# 3. 运行后端
cd SmsPlatform.Api
dotnet run --urls "http://0.0.0.0:5000"
```

#### 前端部署

```bash
# 1. 安装 Node.js (v18+)
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

# 2. 构建前端
cd pic/sms-web
npm install
npm run build

# 3. 使用 Nginx 部署
sudo apt-get install -y nginx
sudo cp -r dist/* /var/www/html/
sudo vim /etc/nginx/conf.d/sms.conf

# 4. 重启 Nginx
sudo systemctl restart nginx
```

### Nginx 配置示例

```nginx
server {
    listen 80;
    server_name your-domain.com;
    
    root /var/www/html;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location /api {
        proxy_pass http://127.0.0.1:5000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

---

## 验证部署

### 1. 检查容器状态

```bash
docker-compose ps
```

输出应类似：
```
NAME                STATUS          PORTS
pic_api_1          Up              0.0.0.0:5000->5000/tcp
pic_mysql_1        Up              0.0.0.0:3306->3306/tcp
pic_rabbitmq_1      Up              0.0.0.0:5672->5672/tcp
pic_redis_1         Up              0.0.0.0:6379->6379/tcp
pic_web_1           Up              0.0.0.0:80->80/tcp
```

### 2. 检查端口监听

```bash
# Docker 部署
docker-compose exec api netstat -tlnp

# 本地部署
netstat -tlnp | grep 5000
```

### 3. 测试 API

```bash
# 测试编码接口
curl -X POST http://localhost:5000/api/sms/encode \
  -H "Content-Type: application/json" \
  -d '"Hello World"'

# 预期返回
{"encoding":"GSM7","characterCount":11,"segmentSize":153,"totalSegments":1}
```

### 4. 查看日志

```bash
# Docker 部署
docker-compose logs -f api

# 查看最近100行
docker-compose logs --tail=100 api
```

---

## 配置说明

### 环境变量

| 变量名 | 说明 | 默认值 |
|--------|------|--------|
| `ASPNETCORE_ENVIRONMENT` | 运行环境 | Development |
| `ConnectionStrings__DefaultConnection` | 数据库连接 | 见下方 |
| `RabbitMQ__ConnectionString` | RabbitMQ地址 | rabbitmq |
| `Redis__ConnectionString` | Redis地址 | redis |
| `Jwt__Key` | JWT密钥 | (请修改) |
| `Jwt__Issuer` | JWT发行者 | SmsPlatform |
| `Jwt__Audience` | JWT受众 | SmsPlatformUsers |

### 数据库连接

```json
// Docker 部署
"DefaultConnection": "Server=mysql;Database=sms_platform;User=root;Password=password;"

// 生产环境请修改密码
"DefaultConnection": "Server=mysql;Database=sms_platform;User=root;Password=YourStrongPassword;"
```

### 修改 JWT 密钥

```bash
# 生成随机密钥
openssl rand -base64 32

# 修改 appsettings.json
{
  "Jwt": {
    "Key": "这里填入生成的密钥"
  }
}
```

---

## Docker 部署详解

### docker-compose.yml 结构

```yaml
services:
  api:          # 后端 API 服务
  web:          # Nginx + 前端静态文件
  mysql:        # MySQL 8.0 数据库
  rabbitmq:     # RabbitMQ 消息队列
  redis:        # Redis 缓存

volumes:
  mysql_data:   # 持久化 MySQL 数据
```

### 自定义端口

修改 `docker-compose.yml`：

```yaml
services:
  api:
    ports:
      - "8080:5000"    # 后端 API
  
  web:
    ports:
      - "8888:80"      # 前端界面
  
  mysql:
    ports:
      - "3307:3306"    # MySQL
  
  rabbitmq:
    ports:
      - "5673:5672"    # RabbitMQ
```

### 数据持久化

```bash
# 查看数据卷
docker volume ls | grep pic

# 备份 MySQL 数据
docker-compose exec mysql mysqldump -u root -p sms_platform > backup.sql

# 恢复数据
docker-compose exec -T mysql mysql -u root -p sms_platform < backup.sql
```

---

## 常见问题

### Q1: 端口被占用

```bash
# 查找占用端口的进程
sudo lsof -i :5000
sudo lsof -i :80

# 杀掉进程或修改 docker-compose.yml 中的端口映射
```

### Q2: 数据库连接失败

```bash
# 检查 MySQL 容器状态
docker-compose ps mysql

# 查看 MySQL 日志
docker-compose logs mysql

# 重启 MySQL
docker-compose restart mysql
```

### Q3: 前端无法访问后端 API

```bash
# 检查 Nginx 配置
docker-compose exec web cat /etc/nginx/conf.d/default.conf

# 重启 Nginx
docker-compose exec web nginx -s reload
```

### Q4: RabbitMQ 无法连接

```bash
# 访问管理界面
http://localhost:15672

# 默认账号密码: guest / guest

# 查看连接日志
docker-compose logs rabbitmq
```

### Q5: 性能优化

```bash
# 增加 Docker 资源限制
# 编辑 docker-compose.yml
services:
  api:
    deploy:
      resources:
        limits:
          cpus: '2'
          memory: 2G
```

### Q6: 升级版本

```bash
# 拉取最新代码
git pull origin main

# 重新构建并启动
docker-compose down
docker-compose up -d --build

# 保留数据卷升级
docker-compose down
docker-compose up -d --build -V
```

---

## 安全建议

### 生产环境必做

1. **修改所有默认密码**
   ```bash
   # MySQL
   docker-compose exec mysql mysql -u root -p \
     "ALTER USER 'root'@'%' IDENTIFIED BY 'YourStrongPassword';"
   
   # RabbitMQ
   # 登录 http://localhost:15672 修改 guest 密码
   ```

2. **配置 HTTPS**
   ```nginx
   server {
       listen 443 ssl http2;
       ssl_certificate /path/to/cert.pem;
       ssl_certificate_key /path/to/key.pem;
       # ... 其他配置
   }
   ```

3. **限制 Redis 访问**
   ```bash
   # 设置 Redis 密码
   redis-server --requirepass YourRedisPassword
   ```

4. **配置防火墙**
   ```bash
   sudo ufw allow 80/tcp
   sudo ufw allow 443/tcp
   sudo ufw enable
   ```

---

## 联系与支持

- **GitHub Issues**: https://github.com/1008ManGo/pic/issues
- **文档版本**: v1.0.0
- **最后更新**: 2026-04-06

---

## 附录：常用命令

```bash
# 启动所有服务
docker-compose up -d

# 停止所有服务
docker-compose down

# 查看日志
docker-compose logs -f

# 重启某服务
docker-compose restart api

# 进入容器
docker-compose exec api /bin/bash

# 重新构建
docker-compose up -d --build

# 清理未使用的镜像
docker system prune -f
```
