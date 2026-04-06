#!/bin/bash

echo "========================================="
echo "SMS Platform Load Testing Script"
echo "========================================="
echo ""

API_BASE="${API_BASE:-http://localhost:5000}"
CONCURRENT="${CONCURRENT:-100}"
REQUESTS="${REQUESTS:-1000}"

echo "Configuration:"
echo "  API Base URL: $API_BASE"
echo "  Concurrent Users: $CONCURRENT"
echo "  Total Requests: $REQUESTS"
echo ""

echo "[1/4] Testing Health Endpoint..."
curl -s -o /dev/null -w "HTTP Status: %{http_code}, Time: %{time_total}s\n" "$API_BASE/api/sms/encode" -X POST -H "Content-Type: application/json" -d '"test"'

echo ""
echo "[2/4] Testing Login Endpoint (Creating test user first)..."
curl -s -o /dev/null -w "HTTP Status: %{http_code}, Time: %{time_total}s\n" "$API_BASE/api/user/register" -X POST -H "Content-Type: application/json" -d '{"username":"loadtest_'"$(date +%s)"'","email":"loadtest@test.com","password":"Test123456"}'

echo ""
echo "[3/4] Running Load Test with Apache Bench (ab)..."
if command -v ab &> /dev/null; then
    echo "Using Apache Bench"
    ab -n $REQUESTS -c $CONCURRENT -p /tmp/post_data.txt -T "application/json" "$API_BASE/api/sms/encode"
else
    echo "Apache Bench (ab) not found, using alternative method..."
    echo "Using wrk or hey if available..."
    
    if command -v hey &> /dev/null; then
        echo "Using hey for load testing"
        hey -n $REQUESTS -c $CONCURRENT -m POST -T "application/json" -p /tmp/post_data.txt -body '{"content":"test"}' "$API_BASE/api/sms/encode"
    elif command -v wrk &> /dev/null; then
        echo "Using wrk for load testing"
        wrk -t$CONCURRENT -c$CONCURRENT -d30s -s /tmp/wrk_script.lua "$API_BASE/api/sms/encode"
    else
        echo "No load testing tool found (ab, hey, or wrk). Skipping load test."
        echo "Install Apache Bench: apt-get install apache2-utils"
    fi
fi

echo ""
echo "[4/4] Testing Multiple Concurrent Connections..."
for i in {1..10}; do
    curl -s -o /dev/null -w "Request $i: HTTP %{http_code}, Time: %{time_total}s\n" \
        "$API_BASE/api/sms/encode" \
        -X POST -H "Content-Type: application/json" &
done
wait

echo ""
echo "Load testing completed!"
