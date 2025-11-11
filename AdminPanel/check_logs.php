<?php
// File: /public_html/admin/check_logs.php

header('Content-Type: text/plain; charset=utf-8');

// Tìm PHP error log
$possible_logs = [
    ini_get('error_log'),
    '/home/ipkfeohnhosting/logs/error_log',
    '/home/ipkfeohnhosting/public_html/error_log',
    dirname(__FILE__) . '/error_log',
    dirname(__FILE__) . '/../error_log',
];

echo "=== CHECKING PHP ERROR LOGS ===\n\n";

foreach ($possible_logs as $log_path) {
    if (empty($log_path)) continue;
    
    echo "Checking: $log_path\n";
    
    if (file_exists($log_path)) {
        echo "✅ FOUND!\n";
        echo "File size: " . filesize($log_path) . " bytes\n";
        echo "Last 50 lines containing 'SUPPORT API':\n";
        echo str_repeat("-", 80) . "\n";
        
        $lines = file($log_path);
        $filtered = array_filter($lines, function($line) {
            return stripos($line, 'SUPPORT API') !== false;
        });
        
        $last_50 = array_slice($filtered, -50);
        
        if (empty($last_50)) {
            echo "No 'SUPPORT API' logs found.\n";
        } else {
            echo implode("", $last_50);
        }
        
        echo "\n" . str_repeat("=", 80) . "\n\n";
        break;
    } else {
        echo "❌ Not found\n\n";
    }
}

echo "\n\n=== PHP CONFIGURATION ===\n";
echo "error_log setting: " . ini_get('error_log') . "\n";
echo "log_errors: " . (ini_get('log_errors') ? 'enabled' : 'disabled') . "\n";
echo "display_errors: " . (ini_get('display_errors') ? 'enabled' : 'disabled') . "\n";
?>