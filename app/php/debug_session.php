<?php
session_start();

header('Content-Type: text/plain; charset=utf-8');

echo "=== Session Debug ===\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n\n";

echo "=== Session Data ===\n";
echo "token exists: " . (isset($_SESSION['token']) ? 'YES' : 'NO') . "\n";
if (isset($_SESSION['token'])) {
    $token = $_SESSION['token'];
    echo "token length: " . strlen($token) . "\n";
    echo "token first 50 chars: " . substr($token, 0, 50) . "\n";
    echo "token last 20 chars: " . substr($token, -20) . "\n";
} else {
    echo "token: NOT SET\n";
}
echo "\nuser_info exists: " . (isset($_SESSION['user_info']) ? 'YES' : 'NO') . "\n";
echo "username exists: " . (isset($_SESSION['username']) ? 'YES' : 'NO') . "\n";

echo "\n=== Cookie Data ===\n";
echo "PHPSESSID cookie: " . ($_COOKIE['PHPSESSID'] ?? 'NOT SET') . "\n";

echo "\n=== Full Session Array ===\n";
print_r($_SESSION);
