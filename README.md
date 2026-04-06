# 企业级短信平台

基于 .NET 8 + Vue 3 的企业级短信发送平台，支持 SMPP 协议通道对接。

## 技术栈

| 模块 | 技术 |
|------|------|
| 后端 | ASP.NET Core (.NET 8) |
| 数据库 | MySQL 8.0 |
| 队列 | RabbitMQ |
| 缓存 | Redis |
| 前端 | Vue 3 + Element Plus |
| 部署 | Docker |

## 快速部署

```bash
# 克隆项目
git clone https://github.com/1008ManGo/pic.git
cd pic

# 一键启动所有服务
docker-compose up -d
```

## 访问地址

| 服务 | 地址 |
|------|------|
| 前端界面 | http://localhost |
| 后端API | http://localhost:5000 |
| Swagger文档 | http://localhost:5000/swagger |
| RabbitMQ管理 | http://localhost:15672 |

## 详细部署教程

请查看 [部署文档](./docs/DEPLOYMENT.md)

## 项目结构

```
├── SmsPlatform.sln          # .NET 8 解决方案
├── SmsPlatform.Api/        # Web API
├── SmsPlatform.Application/  # 应用服务
├── SmsPlatform.Domain/      # 领域模型
├── SmsPlatform.Infrastructure/ # 基础设施
├── sms-web/                 # Vue 3 前端
├── docs/
│   └── DEPLOYMENT.md       # 部署教程
├── docker-compose.yml       # Docker 编排
└── Dockerfile              # API 镜像构建
```

## 功能特性

- 单条/批量短信发送
- SMPP 协议通道对接
- 多通道负载均衡
- GSM7/UCS2 编码自动识别
- 用户管理/充值
- 国家独立定价
- 实时监控告警
