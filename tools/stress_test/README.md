# SMS Gateway 压力测试工具

## 概述

本工具用于对 SMS Gateway 进行压力测试，测试不同消息量和 TPS 下的系统性能表现。

## 测试矩阵

| 消息数量 | TPS | 说明 |
|---------|-----|------|
| 10,000 | 50/100/200 | 小规模测试 |
| 50,000 | 50/100/200 | 中规模测试 |
| 200,000 | 50/100/200 | 大规模测试 |

## 快速开始

### 1. 获取 Token

首先需要获取 API Token：

```bash
# 方式1: 直接登录获取
curl -X POST http://localhost:8080/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'
```

### 2. 运行测试

```bash
# 设置 Token
export TOKEN="your_token_here"
export API_URL="http://localhost:8080/api"

# 运行完整测试矩阵 (需要较长时间)
./run_tests.sh

# 或运行单个测试
python3 stress_test.py --token "$TOKEN" --count 10000 --tps 100
```

## 输出结果

测试完成后，结果保存在 `stress_test_results/` 目录：

```
stress_test_results/
├── summary_20260412_143000.txt    # 测试汇总
├── result_10000sms_50tps_*.json    # 单个测试结果
├── result_10000sms_100tps_*.json
├── result_10000sms_200tps_*.json
├── result_50000sms_50tps_*.json
└── ...
```

## 指标说明

### 性能指标

- **Total Sent**: 总发送数
- **Success/Failed**: 成功/失败数
- **Success Rate**: 成功率
- **Actual RPS**: 实际每秒请求数
- **Latency (ms)**:
  - Avg: 平均延迟
  - Min/Max: 最小/最大延迟
  - P95/P99: 95%/99% 分位延迟

### 系统指标

- **CPU Usage**: CPU 使用率
- **Memory Usage**: 内存使用率
- **Memory Used/Total**: 已用/总内存

## 依赖

- Python 3.8+
- psutil
- aiohttp

安装依赖:
```bash
pip install psutil aiohttp
```

## Go 版本

如果使用 Go 版本 (位于 `main.go`):

```bash
cd tools/stress_test
go mod tidy
go run main.go -token YOUR_TOKEN -count 10000 -tps 100
```

## 注意事项

1. **数据影响**: 测试会在数据库中产生大量短信记录，测试后建议清理
2. **TPS 限制**: 不要超过系统配置的最大 TPS
3. **冷却时间**: 连续测试之间建议有足够冷却时间
4. **网络延迟**: 本地测试和远程测试结果会有差异
