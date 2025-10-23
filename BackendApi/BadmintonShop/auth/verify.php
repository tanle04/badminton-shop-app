<?php
// Tệp này giả định rằng bạn đã có kết nối DB ($mysqli) và autoload PHPMailer (bootstrap.php)
require_once __DIR__ . '/../bootstrap.php'; 

// ⭐ SCHEME ĐÃ CẤU HÌNH TRONG ANDROIDMANIFEST.XML ⭐
// Deep Link cho kết quả thành công
const SUCCESS_REDIRECT_URL = "badmintonshop://verify/success"; 
// Deep Link cho kết quả thất bại
const FAILURE_REDIRECT_URL = "badmintonshop://verify/failure"; 

/**
 * Hàm chung để chuyển hướng bằng HTML/JS nhằm kích hoạt Deep Link đáng tin cậy.
 * Hiển thị thông báo và cung cấp liên kết thủ công nếu tự động mở thất bại.
 *
 * @param string $url URL Deep Link (badmintonshop://...)
 * @param string $message Thông báo hiển thị cho người dùng
 */
function redirectToApp(string $url, string $message, bool $isSuccess = true): void {
    // Ngăn cache trình duyệt
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    
    // Đặt Content-Type thành HTML
    header('Content-Type: text/html; charset=UTF-8');
    
    $messageClass = $isSuccess ? 'success' : 'failure';

    echo <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Xác Nhận Tài Khoản</title>
    <!-- 1. Cố gắng chuyển hướng ngay lập tức bằng Meta Refresh -->
    <meta http-equiv="refresh" content="0; url=$url">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Inter', sans-serif; text-align: center; padding: 50px; background-color: #f8f9fa; }
        .container { 
            background: white; 
            padding: 40px; 
            border-radius: 12px; 
            box-shadow: 0 8px 16px rgba(0,0,0,0.1); 
            max-width: 400px;
            margin: 0 auto;
        }
        .success { color: #28a745; font-size: 1.5rem; }
        .failure { color: #dc3545; font-size: 1.5rem; }
        a { text-decoration: none; color: #007bff; font-weight: bold; transition: color 0.3s; }
        a:hover { color: #0056b3; }
        p { margin-top: 15px; color: #6c757d; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="$messageClass">$message</h1>
        <p>Nếu ứng dụng không tự động mở, vui lòng nhấp vào liên kết dưới đây:</p>
        <p><a id="app-link" href="$url">Mở Ứng Dụng Badminton Shop</a></p>
    </div>

    <script>
        // 2. Fallback JavaScript (phương pháp đáng tin cậy)
        // Kích hoạt Deep Link bằng cách cố gắng click
        document.getElementById('app-link').click(); 
        
        // Ghi log để theo dõi nếu deep link không hoạt động
        setTimeout(function() {
            console.log("Deep link failed or cancelled by user.");
        }, 3000);
    </script>
</body>
</html>
HTML;
    exit;
}

// Bắt đầu luồng xác nhận tài khoản
try {
    // 1. Nhận và kiểm tra Token cơ bản
    $token = $_GET['token'] ?? '';
    if (empty($token) || !ctype_xdigit($token) || strlen($token) !== 64) { 
        error_log("Verification Error: Invalid token format received: " . $token);
        redirectToApp(FAILURE_REDIRECT_URL . '?error=invalid_token', 'Liên kết không hợp lệ', false);
    }
    
    // Giả định $mysqli là biến kết nối cơ sở dữ liệu đã được khởi tạo trong bootstrap.php
    if (!isset($mysqli)) {
        throw new Exception("Database connection not initialized.");
    }

    // 2. Kiểm tra Token trong DB
    $current_time = date('Y-m-d H:i:s');
    $stmt = $mysqli->prepare(
        "SELECT customerID, fullName FROM customers 
         WHERE verificationToken = ? 
         AND isEmailVerified = 0 
         AND tokenExpiry > ?"
    );
    
    $stmt->bind_param("ss", $token, $current_time);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        // Token đã hết hạn, không tồn tại hoặc đã được xác nhận.
        redirectToApp(FAILURE_REDIRECT_URL . '?error=expired_or_used', 'Xác nhận thất bại. Liên kết có thể đã hết hạn hoặc đã được sử dụng.', false);
    }

    $customer = $result->fetch_assoc();
    $customerID = $customer['customerID'];
    $stmt->close();

    // 3. Xác nhận (Update DB)
    $stmt_update = $mysqli->prepare(
        "UPDATE customers 
         SET isEmailVerified = 1, verificationToken = NULL, tokenExpiry = NULL 
         WHERE customerID = ?"
    );
    
    $stmt_update->bind_param("i", $customerID);
    $stmt_update->execute();

    if ($stmt_update->affected_rows > 0) {
        // ⭐ XÁC NHẬN THÀNH CÔNG: Chuyển hướng bằng HTML/JS
        error_log("Verification Success: Customer ID " . $customerID . " verified successfully.");
        redirectToApp(SUCCESS_REDIRECT_URL, 'Tài khoản đã được kích hoạt thành công!', true);
    } else {
        // Đã được xác nhận (hoặc lỗi hiếm gặp)
        redirectToApp(FAILURE_REDIRECT_URL . '?error=no_update', 'Xác nhận thất bại. Vui lòng thử đăng nhập.', false);
    }

} catch (Throwable $e) {
    error_log("Verification API System Error: " . $e->getMessage());
    // Lỗi hệ thống: Chuyển hướng đến trang lỗi chung
    redirectToApp(FAILURE_REDIRECT_URL . '?error=system_error', 'Lỗi hệ thống', false);
}

// Đảm bảo kết nối DB được đóng
if (isset($mysqli)) {
    $mysqli->close();
}
