#!/bin/bash
#
# SMS Gateway 压力测试脚本
# 测试矩阵: 1万/5万/20万 条消息 × 50/100/200 TPS
# 同时记录系统资源消耗
#

set -e

# 配置
API_URL="${API_URL:-http://localhost:8080/api}"
TOKEN="${TOKEN:-}"
RESULTS_DIR="stress_test_results"

# 测试矩阵
declare -a TEST_MATRIX=(
    "10000:50"
    "10000:100"
    "10000:200"
    "50000:50"
    "50000:100"
    "50000:200"
    "200000:50"
    "200000:100"
    "200000:200"
)

# 颜色输出
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[OK]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

# 检查依赖
check_deps() {
    local deps=("python3" "curl")
    for dep in "${deps[@]}"; do
        if ! command -v "$dep" &> /dev/null; then
            log_error "$dep is required but not installed"
            exit 1
        fi
    done
    
    # 检查 psutil
    if ! python3 -c "import psutil" 2>/dev/null; then
        log_warn "psutil not installed, installing..."
        pip3 install psutil aiohttp --quiet
    fi
}

# 获取系统指标
get_system_metrics() {
    python3 << 'EOF'
import psutil
import json

metrics = {
    "cpu_percent": psutil.cpu_percent(interval=0.5),
    "cpu_count": psutil.cpu_count(),
    "memory_percent": psutil.virtual_memory().percent,
    "memory_used_gb": psutil.virtual_memory().used / (1024**3),
    "memory_total_gb": psutil.virtual_memory().total / (1024**3),
    "disk_usage_percent": psutil.disk_usage('/').percent,
    "network_connections": len(psutil.net_connections())
}

# CPU per core
cpu_per_core = psutil.cpu_percent(interval=0.1, percpu=True)
metrics["cpu_per_core"] = cpu_per_core

print(json.dumps(metrics))
EOF
}

# 等待服务就绪
wait_for_service() {
    local max_attempts=30
    local attempt=1
    
    log_info "等待服务就绪..."
    while [ $attempt -le $max_attempts ]; do
        if curl -s -o /dev/null -w "%{http_code}" "${API_URL%/*}/health" 2>/dev/null | grep -q "200"; then
            log_success "服务已就绪"
            return 0
        fi
        echo -n "."
        sleep 2
        attempt=$((attempt + 1))
    done
    
    log_error "服务未响应"
    return 1
}

# 运行单个测试
run_single_test() {
    local count=$1
    local tps=$2
    local output_file=$3
    
    log_info "开始测试: ${count} 条消息 @ ${tps} TPS"
    
    # 记录测试前指标
    local metrics_before=$(get_system_metrics)
    local start_time=$(date +%s)
    
    # 运行测试
    python3 << EOF
import asyncio
import aiohttp
import time
import json
import sys

async def send_sms(session, url, token, phone, content, sender_id):
    start = time.time()
    try:
        payload = {"phones": [phone], "content": content}
        if sender_id:
            payload["sender_id"] = sender_id
        
        headers = {"Authorization": f"Bearer {token}", "Content-Type": "application/json"}
        async with session.post(url, json=payload, headers=headers) as resp:
            await resp.json()
            latency = (time.time() - start) * 1000
            return (resp.status == 200, latency)
    except Exception as e:
        return (False, 0)

async def run_test():
    url = "${API_URL}/sms/send"
    token = "${TOKEN}"
    count = ${count}
    tps = ${tps}
    content = "压力测试消息"
    sender_id = "STRESS"
    
    interval = 1.0 / tps if tps > 0 else 0
    success = 0
    failed = 0
    latencies = []
    
    connector = aiohttp.TCPConnector(limit=tps * 3)
    timeout = aiohttp.ClientTimeout(total=60)
    
    async with aiohttp.ClientSession(connector=connector, timeout=timeout) as session:
        tasks = []
        start = time.time()
        
        for i in range(count):
            phone = f"+447{70000000 + i % 10000000}"
            task = send_sms(session, url, token, phone, content, sender_id)
            tasks.append(task)
            
            if interval > 0 and (i + 1) % tps == 0:
                await asyncio.sleep(interval)
            
            if (i + 1) % 1000 == 0:
                elapsed = time.time() - start
                rps = (i + 1) / elapsed
                print(f"\r    进度: {i + 1}/{count} ({rps:.1f} SMS/s)", end="", flush=True)
        
        results = await asyncio.gather(*tasks)
        
        for ok, latency in results:
            if ok:
                success += 1
                if latency > 0:
                    latencies.append(latency)
            else:
                failed += 1
        
        elapsed = time.time() - start
        
        latencies.sort()
        p95_idx = int(len(latencies) * 0.95)
        p99_idx = int(len(latencies) * 0.99)
        
        result = {
            "test_name": f"${count}sms_${tps}tps",
            "config": {
                "total_sms": count,
                "target_tps": tps,
                "actual_tps": count / elapsed
            },
            "results": {
                "total_sent": count,
                "success": success,
                "failed": failed,
                "success_rate": success / count * 100,
                "duration_seconds": elapsed,
                "latency_ms": {
                    "avg": sum(latencies) / len(latencies) if latencies else 0,
                    "min": min(latencies) if latencies else 0,
                    "max": max(latencies) if latencies else 0,
                    "p95": latencies[p95_idx] if latencies and p95_idx < len(latencies) else 0,
                    "p99": latencies[p99_idx] if latencies and p99_idx < len(latencies) else 0
                }
            }
        }
        
        print(f"\r    完成: {count} 条消息, 成功率: {result['results']['success_rate']:.1f}%, RPS: {result['config']['actual_tps']:.1f}")
        
        with open("${output_file}", "w") as f:
            json.dump(result, f, indent=2)

asyncio.run(run_test())
EOF
    
    local end_time=$(date +%s)
    local duration=$((end_time - start_time))
    
    # 记录测试后指标
    local metrics_after=$(get_system_metrics)
    
    # 生成完整报告
    cat > "${output_file}.full" << EOF
{
    "test_name": "${count}sms_${tps}tps",
    "start_time": $(date -d @$start_time +%Y-%m-%dT%H:%M:%S),
    "duration_seconds": $duration,
    "metrics_before": $metrics_before,
    "metrics_after": $metrics_after,
    "test_results_file": "${output_file}"
}
EOF
    
    log_success "测试完成: ${count} 条消息 @ ${tps} TPS (耗时: ${duration}s)"
}

# 主函数
main() {
    echo "=========================================="
    echo "   SMS Gateway 压力测试                  "
    echo "=========================================="
    echo ""
    
    # 检查参数
    if [ -z "$TOKEN" ]; then
        log_error "请设置 TOKEN 环境变量"
        echo "Usage: TOKEN=your_token $0"
        exit 1
    fi
    
    # 检查依赖
    check_deps
    
    # 等待服务就绪
    wait_for_service || exit 1
    
    # 创建结果目录
    mkdir -p "$RESULTS_DIR"
    local timestamp=$(date +%Y%m%d_%H%M%S)
    local summary_file="${RESULTS_DIR}/summary_${timestamp}.txt"
    
    # 记录初始系统状态
    echo "==========================================" >> "$summary_file"
    echo "   压力测试结果汇总                       " >> "$summary_file"
    echo "==========================================" >> "$summary_file"
    echo "测试时间: $(date)" >> "$summary_file"
    echo "" >> "$summary_file"
    
    echo "系统初始状态:" >> "$summary_file"
    get_system_metrics >> "$summary_file"
    echo "" >> "$summary_file"
    
    log_info "开始测试矩阵..."
    echo ""
    
    local total_tests=${#TEST_MATRIX[@]}
    local current=0
    
    for test_spec in "${TEST_MATRIX[@]}"; do
        IFS=':' read -r count tps <<< "$test_spec"
        current=$((current + 1))
        
        echo ""
        echo -e "${BLUE}[${current}/${total_tests}]${NC} 测试: ${count} 条 @ ${tps} TPS"
        echo "------------------------------------------"
        
        local output_file="${RESULTS_DIR}/result_${count}sms_${tps}tps_${timestamp}.json"
        
        # 冷却时间
        if [ $current -gt 1 ]; then
            log_info "冷却 30 秒..."
            sleep 30
        fi
        
        # 运行测试
        run_single_test "$count" "$tps" "$output_file"
        
        # 更新汇总
        echo "" >> "$summary_file"
        echo "--------------------------------------" >> "$summary_file"
        echo "测试: ${count} 条 @ ${tps} TPS" >> "$summary_file"
        if [ -f "$output_file" ]; then
            echo "结果:" >> "$summary_file"
            cat "$output_file" | python3 -c "import sys,json; d=json.load(sys.stdin); r=d.get('results',{}); print(f\"  发送: {r.get('total_sent',0)}, 成功: {r.get('success',0)}, 失败: {r.get('failed',0)}, 成功率: {r.get('success_rate',0):.2f}%\")" >> "$summary_file"
            echo "性能:" >> "$summary_file"
            cat "$output_file" | python3 -c "import sys,json; d=json.load(sys.stdin); r=d.get('results',{}); lat=r.get('latency_ms',{}); print(f\"  RPS: {d.get('config',{}).get('actual_tps',0):.2f}, 延迟: avg={lat.get('avg',0):.2f}ms, p95={lat.get('p95',0):.2f}ms\")" >> "$summary_file"
        fi
    done
    
    echo ""
    echo "=========================================="
    echo "   所有测试完成                           "
    echo "=========================================="
    echo ""
    log_success "结果已保存到: $RESULTS_DIR/"
    echo ""
    echo "汇总报告:"
    cat "$summary_file"
}

main "$@"
