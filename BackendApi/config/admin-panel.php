<?php
/**
 * File: config/admin-panel.php
 * Admin Panel Connection Config
 */

return [
    // Base URL của AdminPanel (Laravel)
    'base_url' => getenv('ADMIN_PANEL_URL') ?: 'http://localhost:8000',  // Port 8000, không phải 8001
    
    // API Key để xác thực (phải khớp BRIDGE_API_KEY trong .env AdminPanel)
    'api_key' => getenv('ADMIN_PANEL_API_KEY') ?: 'BadmintonShop2025SecretKey_ChangeInProduction',
    
    // Timeout cho HTTP requests
    'timeout' => 30,
];