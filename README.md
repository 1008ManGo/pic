# 企业级短信平台

基于 .NET 8 + Vue 3 的企业级短信发送平台，支持 SMPP 协议通道对接。

## 一键安装（推荐）

```bash
# 克隆项目
git clone https://github.com/1008ManGo/pic.git
cd pic

# 运行一键安装脚本（自动安装Docker、Node.js、前端构建、启动服务）
chmod +x install.sh
sudo ./install.sh
```

安装脚本会自动：
1. 安装 Docker 和 Docker Compose
2. 安装 Node.js（用于构建前端）
3. 构建前端
4. 开放防火墙端口
5. 启动所有服务

## 快速启动（已安装Docker）

```bash
# 克隆项目
git clone https://github.com/1008ManGo/pic.git
cd pic

# 构建并启动
chmod +x install.sh
sudo ./install.sh
```

## 访问地址

| 服务 | 地址 |
|------|------|
| 前端界面 | http://你的IP:18000 |
| API文档 | http://你的IP:18080/swagger |
| RabbitMQ管理 | http://你的IP:15672 |

## 端口说明

| 服务 | 端口 |
|------|------|
| Web | 18000 |
| API | 18080 |
| MySQL | 13306 |
| Redis | 16379 |
| RabbitMQ | 5672, 15672 |

## 技术栈

| 模块 | 技术 |
|------|------|
| 后端 | ASP.NET Core (.NET 8) |
| 数据库 | MySQL 8.0 |
| 队列 | RabbitMQ |
| 缓存 | Redis |
| 前端 | Vue 3 + Element Plus |
| 部署 | Docker |

## 详细文档

请查看 [部署文档](./docs/DEPLOYMENT.md)

## 项目结构

```
├── SmsPlatform.sln          # .NET 8 解决方案
├── SmsPlatform.Api/        # Web API
├── SmsPlatform.Application/  # 应用服务
├── SmsPlatform.Domain/      # 领域模型
├── SmsPlatform.Infrastructure/ # 基础设施
├── sms-web/                 # Vue 3 前端源码
├── web/                    # 前端构建文件
├── docs/
│   └── DEPLOYMENT.md       # 部署教程
├── docker-compose.yml       # Docker 编排
├── Dockerfile              # API 镜像构建
├── install.sh              # 一键安装脚本
└── nginx.conf             # Nginx 配置
```
