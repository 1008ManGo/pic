#!/bin/bash

# 自动开放端口并启动服务

echo "========================================="
echo "SMS Platform 启动脚本"
echo "========================================="

# 需要开放的端口
PORTS="18000 18080 13306 16379 5672 15672"

echo ""
echo "[1/2] 开放防火墙端口..."

# 检测防火墙工具
if command -v ufw &> /dev/null; then
    echo "使用 ufw 开放端口..."
    for PORT in $PORTS; do
        sudo ufw allow $PORT/tcp comment "SMS Platform" 2>/dev/null || true
        echo "  - 端口 $PORT 已开放"
    done
    echo "ufw 防火墙规则已更新"
elif command -v firewall-cmd &> /dev/null; then
    echo "使用 firewall-cmd 开放端口..."
    for PORT in $PORTS; do
        sudo firewall-cmd --permanent --add-port=${PORT}/tcp 2>/dev/null || true
        echo "  - 端口 $PORT 已开放"
    done
    sudo firewall-cmd --reload 2>/dev/null || true
    echo "firewalld 规则已更新"
elif command -v iptables &> /dev/null; then
    echo "使用 iptables 开放端口..."
    for PORT in $PORTS; do
        sudo iptables -A INPUT -p tcp --dport $PORT -j ACCEPT 2>/dev/null || true
        echo "  - 端口 $PORT 已开放"
    done
    echo "iptables 规则已更新"
else
    echo "未检测到防火墙工具 (ufw/firewalld/iptables)"
    echo "请手动开放以下端口: $PORTS"
fi

echo ""
echo "[2/2] 启动 Docker Compose 服务..."
docker-compose up -d

echo ""
echo "========================================="
echo "启动完成！"
echo "========================================="
echo ""
echo "访问地址："
echo "  - 前端界面: http://你的IP:18000"
echo "  - API文档:   http://你的IP:18080/swagger"
echo "  - RabbitMQ:   http://你的IP:15672"
echo ""
echo "查看日志: docker-compose logs -f"
echo "停止服务: docker-compose down"
