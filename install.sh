#!/bin/bash

#=========================================
# SMS Platform 一键安装脚本
# 适用于全新Ubuntu/Debian系统
#=========================================

set -e

echo "========================================="
echo "SMS Platform 一键安装脚本"
echo "========================================="

# 检测是否为root用户
if [ "$EUID" -ne 0 ]; then 
    echo "请使用 sudo 运行此脚本"
    exit 1
fi

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_info() { echo -e "${GREEN}[INFO]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

# 获取脚本所在目录
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# 1. 安装基础依赖
log_info "安装基础依赖..."
apt-get update
apt-get install -y curl wget git unzip ca-certificates gnupg lsb-release sudo

# 2. 安装 Docker
if ! command -v docker &> /dev/null; then
    log_info "安装 Docker..."
    curl -fsSL https://get.docker.com | sh
    systemctl --now enable docker
    log_info "Docker 安装完成"
else
    log_info "Docker 已安装 ($(docker --version))"
fi

# 添加当前用户到docker组
usermod -aG docker $SUDO_USER 2>/dev/null || true

# 3. 安装 Docker Compose
if ! command -v docker-compose &> /dev/null; then
    log_info "安装 Docker Compose..."
    curl -L "https://github.com/docker/compose/releases/download/v2.24.0/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    chmod +x /usr/local/bin/docker-compose
    log_info "Docker Compose 安装完成"
else
    log_info "Docker Compose 已安装 ($(docker-compose --version))"
fi

# 4. 检查并安装 Node.js 22 (Vite 8 需要 Node.js 20+)
NODE_VERSION=$(node -v 2>/dev/null | tr -d 'v' || echo "0")
NODE_MAJOR=$(echo $NODE_VERSION | cut -d. -f1)

if [ "$NODE_MAJOR" -lt 20 ] 2>/dev/null; then
    log_info "Node.js 版本过低 ($NODE_VERSION)，安装 Node.js 22..."
    
    # 卸载旧版本
    apt-get remove -y nodejs 2>/dev/null || true
    
    # 安装 Node.js 22
    curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
    apt-get install -y nodejs
    
    log_info "Node.js 安装完成 ($(node -v))"
else
    log_info "Node.js 已安装 ($(node -v))"
fi

# 5. 检查/构建前端
log_info "检查前端构建..."
if [ ! -d "sms-web" ]; then
    log_error "sms-web 目录不存在！"
    exit 1
fi

if [ ! -d "sms-web/dist" ] || [ ! -f "sms-web/dist/index.html" ]; then
    log_info "前端未构建，开始构建..."
    cd sms-web
    rm -rf node_modules package-lock.json
    npm install
    npm run build
    cd ..
    log_info "前端构建完成"
else
    log_info "前端已构建，跳过构建步骤"
fi

# 6. 复制前端文件到 web 目录
log_info "复制前端文件到 web 目录..."
rm -rf web
mkdir -p web
cp -r sms-web/dist/* web/

# 7. 开放防火墙端口
log_info "开放防火墙端口..."
PORTS="18000 18080 13306 16379 5672 15672"
for PORT in $PORTS; do
    ufw allow $PORT/tcp comment "SMS Platform" 2>/dev/null || true
    echo "  - 端口 $PORT 已开放"
done

# 8. 停止旧容器（如有）
log_info "停止旧容器（如有）..."
docker-compose down 2>/dev/null || true

# 9. 启动服务
log_info "启动 Docker 服务..."
docker-compose up -d

# 10. 等待服务启动
log_info "等待服务启动（20秒）..."
sleep 20

# 11. 检查状态
echo ""
echo "========================================="
echo "安装完成！"
echo "========================================="
echo ""
docker-compose ps
echo ""
echo "访问地址（使用服务器IP替换 localhost）："
echo "  - 前端界面: http://localhost:18000"
echo "  - API文档:   http://localhost:18080/swagger"
echo "  - RabbitMQ:  http://localhost:15672"
echo ""
echo "常用命令："
echo "  查看日志: docker-compose logs -f"
echo "  停止服务: docker-compose down"
echo "  重启服务: docker-compose restart"
