<?php
// File: api/BadmintonShop/test-support.php
// Test file để kiểm tra API Support có hoạt động không

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'status' => 'ok',
    'message' => 'Support API is working!',
    'timestamp' => date('Y-m-d H:i:s'),
    'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'php_version' => PHP_VERSION,
    'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
    'endpoints' => [
        'POST /support/init' => 'Initialize conversation',
        'GET /support/history' => 'Get message history',
        'POST /support/send' => 'Send message',
        'GET /support/unread-count' => 'Get unread count'
    ]
]);
?>