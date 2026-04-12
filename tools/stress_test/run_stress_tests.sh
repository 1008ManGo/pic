#!/bin/bash

# SMS Gateway Stress Test Runner
# Tests: 10K/50K/200K messages at 50/100/200 TPS

set -e

API_URL="${API_URL:-http://localhost:8080/api}"
TOKEN="${TOKEN:-}"

if [ -z "$TOKEN" ]; then
    echo "Error: TOKEN environment variable is required"
    echo "Usage: TOKEN=your_token ./run_stress_tests.sh"
    exit 1
fi

OUTPUT_DIR="stress_test_results"
mkdir -p "$OUTPUT_DIR"

cd /workspace/tools/stress_test

echo "========================================"
echo "   SMS Gateway Stress Test Suite       "
echo "========================================"
echo ""
echo "API URL: $API_URL"
echo ""

# Test matrix: Total SMS / TPS
TEST_MATRIX=(
    "10000,50"
    "10000,100"
    "10000,200"
    "50000,50"
    "50000,100"
    "50000,200"
    "200000,50"
    "200000,100"
    "200000,200"
)

# Collect baseline metrics
echo "[Baseline] Collecting idle system metrics..."
go run main.go \
    -api="$API_URL/sms/send" \
    -token="$TOKEN" \
    -count=10 \
    -tps=10 \
    -prefix="+447" \
    -content="Baseline test" > /dev/null 2>&1 || true

SUMMARY_FILE="$OUTPUT_DIR/summary_$(date +%Y%m%d_%H%M%S).txt"
echo "SMS Gateway Stress Test Results" > "$SUMMARY_FILE"
echo "================================" >> "$SUMMARY_FILE"
echo "Test Date: $(date)" >> "$SUMMARY_FILE"
echo "" >> "$SUMMARY_FILE"

TOTAL_TESTS=${#TEST_MATRIX[@]}
CURRENT_TEST=0

for test_spec in "${TEST_MATRIX[@]}"; do
    IFS=',' read -r count tps <<< "$test_spec"
    CURRENT_TEST=$((CURRENT_TEST + 1))
    
    echo ""
    echo "----------------------------------------"
    echo "[$CURRENT_TEST/$TOTAL_TESTS] Running: $count messages at $tps TPS"
    echo "----------------------------------------"
    
    OUTPUT_FILE="$OUTPUT_DIR/result_${count}sms_${tps}tps_$(date +%Y%m%d_%H%M%S).json"
    
    # Run the test
    START_TIME=$(date +%s)
    
    go run main.go \
        -api="$API_URL/sms/send" \
        -token="$TOKEN" \
        -count="$count" \
        -tps="$tps" \
        -prefix="+447" \
        -content="Stress test message from SMS Gateway" \
        -sender="SYSTEST" 2>&1 | tee /tmp/current_test.log
    
    END_TIME=$(date +%s)
    DURATION=$((END_TIME - START_TIME))
    
    # Extract key metrics from last run
    if [ -f "stress_test_report.json" ]; then
        mv stress_test_report.json "$OUTPUT_FILE"
        
        # Append to summary
        echo "----------------------------------" >> "$SUMMARY_FILE"
        echo "Test: ${count}sms @ ${tps}tps" >> "$SUMMARY_FILE"
        echo "Duration: ${DURATION}s" >> "$SUMMARY_FILE"
        
        # Extract metrics using jq if available
        if command -v jq &> /dev/null; then
            SUCCESS=$(jq -r '.result.total_success' "$OUTPUT_FILE" 2>/dev/null || echo "N/A")
            FAILED=$(jq -r '.result.total_failed' "$OUTPUT_FILE" 2>/dev/null || echo "N/A")
            RPS=$(jq -r '.result.requests_per_sec' "$OUTPUT_FILE" 2>/dev/null || echo "N/A")
            AVG_LAT=$(jq -r '.result.avg_latency_ms' "$OUTPUT_FILE" 2>/dev/null || echo "N/A")
            P99_LAT=$(jq -r '.result.p99_latency_ms' "$OUTPUT_FILE" 2>/dev/null || echo "N/A")
            
            echo "Success: $SUCCESS, Failed: $FAILED" >> "$SUMMARY_FILE"
            echo "RPS: $RPS, Avg Latency: ${AVG_LAT}ms, P99: ${P99_LAT}ms" >> "$SUMMARY_FILE"
        fi
        echo "" >> "$SUMMARY_FILE"
    fi
    
    # Cool down between tests
    if [ $CURRENT_TEST -lt $TOTAL_TESTS ]; then
        echo ""
        echo "[Cool-down] Waiting 30 seconds before next test..."
        sleep 30
    fi
done

echo ""
echo "========================================"
echo "   All Tests Completed                 "
echo "========================================"
echo ""
echo "Results saved to: $OUTPUT_DIR/"
echo ""
cat "$SUMMARY_FILE"
