<?php
// config/db.php

// 1. Thông tin kết nối Database trên Hosting
define('DB_HOST', 'localhost');
define('DB_NAME', 'ipkfeohnhosting_Badminton_Shop');
define('DB_USER', 'ipkfeohnhosting_root');
define('DB_PASS', 'Tanle2004@'); // <-- Mật khẩu của bạn

// 2. Cài đặt PDO
try {
    $pdo = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    // Nếu kết nối thất bại, trả về lỗi JSON
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]));
}

// 3. Cài đặt mysqli (Một số file cũ của bạn dùng cái này)
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'message' => 'Database connection failed (mysqli): ' . $mysqli->connect_error
    ]));
}
$mysqli->set_charset('utf8mb4');

// Hàm respond (vì file db.php cũ của bạn có gọi hàm này)
if (!function_exists('respond')) {
    function respond($data, $status_code = 200) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($status_code);
        echo json_encode($data);
        exit;
    }
}
?>