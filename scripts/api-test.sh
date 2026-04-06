#!/bin/bash

echo "========================================="
echo "SMS Platform API Integration Tests"
echo "========================================="
echo ""

API_BASE="${API_BASE:-http://localhost:5000}"
TEST_USER="apitest_$(date +%s)"
TEST_EMAIL="apitest_$(date +%s)@test.com"
TEST_PASS="Test123456"

echo "Test User: $TEST_USER"
echo "API Base: $API_BASE"
echo ""

pass_count=0
fail_count=0

test_endpoint() {
    local name=$1
    local expected_code=$2
    shift 2
    local response=$(curl -s -o /tmp/curl_response.txt -w "%{http_code}" "$@")
    local body=$(cat /tmp/curl_response.txt)
    
    if [ "$response" = "$expected_code" ]; then
        echo "✓ PASS: $name (HTTP $response)"
        ((pass_count++))
        return 0
    else
        echo "✗ FAIL: $name - Expected $expected_code, got $response"
        echo "  Response: ${body:0:200}"
        ((fail_count++))
        return 1
    fi
}

echo "[User Module Tests]"
echo "-----------------------"

test_endpoint "Register User" "200" -X POST "$API_BASE/api/user/register" \
    -H "Content-Type: application/json" \
    -d "{\"username\":\"$TEST_USER\",\"email\":\"$TEST_EMAIL\",\"password\":\"$TEST_PASS\"}"

LOGIN_RESPONSE=$(curl -s -X POST "$API_BASE/api/user/login" \
    -H "Content-Type: application/json" \
    -d "{\"username\":\"$TEST_USER\",\"password\":\"$TEST_PASS\"}")

TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"accessToken":"[^"]*' | cut -d'"' -f4)

if [ -n "$TOKEN" ]; then
    echo "✓ PASS: Login successful, token obtained"
    ((pass_count++))
else
    echo "✗ FAIL: Login failed, no token"
    ((fail_count++))
fi

echo ""
echo "[SMS Module Tests]"
echo "-----------------------"

if [ -n "$TOKEN" ]; then
    test_endpoint "Encode SMS" "200" -X POST "$API_BASE/api/sms/encode" \
        -H "Content-Type: application/json" \
        -H "Authorization: Bearer $TOKEN" \
        -d '"Hello World"'
    
    test_endpoint "Get SMS History" "200" -X GET "$API_BASE/api/sms/history?page=1&pageSize=20" \
        -H "Authorization: Bearer $TOKEN"
fi

echo ""
echo "[Channel Module Tests]"
echo "-----------------------"

if [ -n "$TOKEN" ]; then
    test_endpoint "Get All Channels" "200" -X GET "$API_BASE/api/channel" \
        -H "Authorization: Bearer $TOKEN"
    
    test_endpoint "Get Active Channels" "200" -X GET "$API_BASE/api/channel/active" \
        -H "Authorization: Bearer $TOKEN"
fi

echo ""
echo "[Admin Module Tests]"
echo "-----------------------"

if [ -n "$TOKEN" ]; then
    test_endpoint "Get Countries" "200" -X GET "$API_BASE/api/admin/countries" \
        -H "Authorization: Bearer $TOKEN"
fi

echo ""
echo "========================================="
echo "Test Summary"
echo "========================================="
echo "Passed: $pass_count"
echo "Failed: $fail_count"
echo ""

if [ $fail_count -eq 0 ]; then
    echo "All tests passed!"
    exit 0
else
    echo "Some tests failed!"
    exit 1
fi
