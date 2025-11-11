<?php
// Tệp: BackendApi/auth/password-request-otp.php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../bootstrap.php'; 
require_once __DIR__ . '/../utils/email_helper.php'; 
require_once __DIR__ . '/../utils/json.php'; 

// ... (Bật ghi log) ...
error_log("====== [REQUEST-OTP] Bắt đầu ======");

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? '';
    error_log("[REQUEST-OTP] Đã nhận email: " . $email);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email không hợp lệ.', 400);
    }

    // 1. Kiểm tra email
    $stmt = $mysqli->prepare("SELECT customerID FROM customers WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    // ⭐ THAY ĐỔI CHÍNH Ở ĐÂY
    if (!$user) {
        // Thay vì trả về 200, hãy ném lỗi 404 (Not Found)
        error_log("[REQUEST-OTP] Email '{$email}' không tồn tại. Ném lỗi 404.");
        throw new Exception('Email không tồn tại trong hệ thống.', 404);
    }

    // 2. Tạo mã OTP
    $otp = random_int(100000, 999999); 
    // ... (Phần còn lại của file giữ nguyên) ...
    $otp_hash = password_hash((string)$otp, PASSWORD_DEFAULT);
    $created_at = date('Y-m-d H:i:s'); 

    error_log("[REQUEST-OTP] Đã tạo OTP: {$otp} cho email: {$email}.");
    $mysqli->begin_transaction();

    // 3. Xóa mọi OTP cũ
    $stmt_delete = $mysqli->prepare("DELETE FROM password_resets WHERE email = ?");
    $stmt_delete->bind_param("s", $email);
    $stmt_delete->execute();
    $stmt_delete->close();

    // 4. Lưu OTP mới
    error_log("[REQUEST-OTP] Đang lưu OTP mới vào CSDL với thời gian: {$created_at}");
    $stmt_insert = $mysqli->prepare("INSERT INTO password_resets (email, token, created_at) VALUES (?, ?, ?)");
    $stmt_insert->bind_param("sss", $email, $otp_hash, $created_at);
    $stmt_insert->execute();
    
    if ($stmt_insert->affected_rows === 0) {
        throw new Exception("Không thể lưu mã reset vào CSDL.");
    }
    $stmt_insert->close();

    // 5. Gửi email
    error_log("[REQUEST-OTP] Đang gửi email OTP...");
    $emailSent = sendOtpEmail($email, (string)$otp); 

    if (!$emailSent) {
        throw new Exception("Không thể gửi email OTP. (Lỗi PHPMailer)");
    }
    
    $mysqli->commit();
    error_log("[SUCCESS] [REQUEST-OTP] Hoàn tất cho {$email}.");
    respond(['isSuccess' => true, 'message' => 'Mã OTP đã được gửi đến email của bạn.']);

} catch (Throwable $e) { // Khối catch này sẽ bắt lỗi 404
    $mysqli->rollback();
    $error_message = $e->getMessage();
    $error_code = $e->getCode();
    
    // Đảm bảo mã lỗi nằm trong khoảng 400-599
    if ($error_code < 400 || $error_code >= 600) $error_code = 500;
    
    error_log("[FATAL ERROR] [REQUEST-OTP]: Code: {$error_code} - " . $error_message);
    // Trả về JSON lỗi và mã HTTP tương ứng (ví dụ: 404)
    respond(['isSuccess' => false, 'message' => $error_message], $error_code);
}
?>
