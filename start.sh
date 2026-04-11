#!/bin/bash

set -e

echo "========================================="
echo "   短信平台 Docker 一键部署"
echo "========================================="

PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"

cd "$PROJECT_DIR"

echo ""
echo "[1/6] 创建数据目录..."
mkdir -p data/mysql data/redis
mkdir -p sms-gateway/logs

echo ""
echo "[2/6] 检查 Docker 环境..."
if ! command -v docker &> /dev/null; then
    echo "错误: Docker 未安装"
    exit 1
fi

if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null; then
    echo "错误: Docker Compose 未安装"
    exit 1
fi

DOCKER_COMPOSE="docker-compose"
if ! command -v docker-compose &> /dev/null; then
    DOCKER_COMPOSE="docker compose"
fi

echo ""
echo "[3/6] 构建并启动服务..."
$DOCKER_COMPOSE up -d --build

echo ""
echo "[4/6] 等待服务启动..."
echo "等待 MySQL..."
for i in {1..30}; do
    if $DOCKER_COMPOSE exec -T mysql mysqladmin ping -h localhost --silent 2>/dev/null; then
        echo "MySQL 已就绪"
        break
    fi
    echo "等待 MySQL 启动... ($i/30)"
    sleep 2
done

echo "等待 Redis..."
for i in {1..15}; do
    if $DOCKER_COMPOSE exec -T redis redis-cli ping &>/dev/null; then
        echo "Redis 已就绪"
        break
    fi
    echo "等待 Redis 启动... ($i/15)"
    sleep 1
done

echo ""
echo "[5/6] 检查服务状态..."
$DOCKER_COMPOSE ps

echo ""
echo "[6/6] 显示访问信息..."
echo ""
echo "========================================="
echo "   部署完成!"
echo "========================================="
echo ""
echo "访问地址:"
echo "  前端界面: http://localhost:15789"
echo "  API服务:   http://localhost:8080"
echo ""
echo "默认账号:"
echo "  用户名: admin"
echo "  密码:   admin123"
echo ""
echo "========================================="
echo ""
echo "常用命令:"
echo "  查看日志: docker-compose logs -f"
echo "  停止服务: docker-compose down"
echo "  重启服务: docker-compose restart"
echo "  重新构建: docker-compose up -d --build"
echo ""
