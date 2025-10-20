<?php
// Không cần header JSON vì đây là endpoint web (sẽ redirect)
require_once __DIR__ . '/../bootstrap.php'; // Giả định chứa kết nối $mysqli

// ⭐⭐ THAY ĐỔI: Sử dụng DEEP LINKS để chuyển hướng người dùng về thẳng ứng dụng ⭐⭐
// Scheme đã cấu hình trong AndroidManifest.xml: badmintonshop://verify/...
const SUCCESS_REDIRECT_URL = "badmintonshop://verify/success"; 
const FAILURE_REDIRECT_URL = "badmintonshop://verify/failure"; 

try {
    // 1. Nhận Token
    $token = $_GET['token'] ?? '';

    // Kiểm tra tính hợp lệ cơ bản của token (ví dụ: độ dài 64 ký tự hex)
    if (empty($token) || !ctype_xdigit($token) || strlen($token) !== 64) { 
        // Nếu token không hợp lệ, chuyển hướng đến trang lỗi
        error_log("Verification Error: Invalid token format received: " . $token);
        header('Location: ' . FAILURE_REDIRECT_URL . '?error=invalid_token');
        exit;
    }
    
    // 2. Kiểm tra Token
    $current_time = date('Y-m-d H:i:s');
    $stmt = $mysqli->prepare(
        "SELECT customerID, fullName FROM customers 
         WHERE verificationToken = ? 
         AND isEmailVerified = 0 
         AND tokenExpiry > ?" // Kiểm tra token còn hạn
    );
    
    if (!$stmt) {
        throw new Exception("Lỗi chuẩn bị SQL tìm token: " . $mysqli->error);
    }

    $stmt->bind_param("ss", $token, $current_time);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        // Token đã hết hạn, không tồn tại hoặc đã được xác nhận.
        header('Location: ' . FAILURE_REDIRECT_URL . '?error=expired_or_used');
        exit;
    }

    $customer = $result->fetch_assoc();
    $customerID = $customer['customerID'];
    $stmt->close();

    // 3. Xác nhận
    $stmt_update = $mysqli->prepare(
        "UPDATE customers 
         SET isEmailVerified = 1, verificationToken = NULL, tokenExpiry = NULL 
         WHERE customerID = ?"
    );
    
    if (!$stmt_update) {
        throw new Exception("Lỗi chuẩn bị SQL xác nhận: " . $mysqli->error);
    }
    
    $stmt_update->bind_param("i", $customerID);
    $stmt_update->execute();

    if ($stmt_update->affected_rows > 0) {
        // ⭐ XÁC NHẬN THÀNH CÔNG: Chuyển hướng về Deep Link
        error_log("Verification Success: Customer ID " . $customerID . " verified successfully.");
        header('Location: ' . SUCCESS_REDIRECT_URL);
        exit;
    } else {
        // Có thể là đã được xác nhận giữa lúc tìm kiếm và update (hiếm)
        header('Location: ' . FAILURE_REDIRECT_URL . '?error=no_update');
        exit;
    }

} catch (Throwable $e) {
    error_log("Verification API System Error: " . $e->getMessage());
    // Lỗi hệ thống: Chuyển hướng đến trang lỗi chung
    header('Location: ' . FAILURE_REDIRECT_URL . '?error=system_error');
    exit;
}
