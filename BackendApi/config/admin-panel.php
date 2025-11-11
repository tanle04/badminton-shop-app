<?php
/**
 * File: config/admin-panel.php
 * Admin Panel Connection Config
 */

return [

 'admin_panel_url' => 'https://tanbadminton.id.vn/admin/public/api/bridge', // <-- SỬA LẠI
 'api_key' => env('BRIDGE_API_KEY', 'BadmintonShop2025SecretKey_ChangeInProduction'),
    // Timeout cho HTTP requests
    'timeout' => 30,
];