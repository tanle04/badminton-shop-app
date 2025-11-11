<?php
// Tệp: BackendApi/auth/password-reset.php
// GĐ 2: Xác thực OTP và Đặt lại Mật khẩu (Sửa lỗi Timezone + Sửa tên cột)

header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../bootstrap.php'; 
require_once __DIR__ . '/../utils/json.php'; // Đảm bảo hàm respond() đã được nạp

// Bật ghi log
ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/../php-error.log');
error_log("====== [RESET-PASS] Bắt đầu ======");

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? '';
    $otp = $input['otp'] ?? '';
    $new_password = $input['new_password'] ?? '';

    error_log("[RESET-PASS] Đã nhận dữ liệu - Email: {$email}, OTP: {$otp}");

    // 1. Validation (Ném Exception)
    if (empty($email) || empty($otp) || empty($new_password)) {
        throw new Exception('Vui lòng nhập đầy đủ mã OTP và mật khẩu mới.', 400);
    }
    if (strlen($new_password) < 6) {
        throw new Exception('Mật khẩu mới phải có ít nhất 6 ký tự.', 400);
    }
    if (!preg_match('/^[0-9]{6}$/', $otp)) {
        throw new Exception('Mã OTP phải là 6 chữ số.', 400);
    }

    // 2. Lấy mã token CHỈ bằng email
    error_log("[RESET-PASS] Đang tìm OTP cho {$email}...");
    $stmt = $mysqli->prepare("
        SELECT * FROM password_resets 
        WHERE email = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $reset_request = $result->fetch_assoc();
    $stmt->close();

    if (!$reset_request) {
        error_log("[WARNING] [RESET-PASS]: Lỗi 400 - Không tìm thấy yêu cầu reset nào cho '{$email}'.");
        throw new Exception('Mã OTP không hợp lệ.', 400);
    }
    
    // 3. Kiểm tra hết hạn (10 phút) bằng logic của PHP (Miễn nhiễm Timezone)
    $created_time = strtotime($reset_request['created_at']);
    $expiry_time = $created_time + 600; // 600 giây = 10 phút
    $current_time = time();

    error_log("[RESET-PASS] TimeCheck - Created: {$reset_request['created_at']} | Expiry_TS: {$expiry_time} | Current_TS: {$current_time}");

    if ($current_time > $expiry_time) {
        error_log("[WARNING] [RESET-PASS]: Lỗi 400 - Mã OTP đã hết hạn.");
        $mysqli->query("DELETE FROM password_resets WHERE email = '{$email}'");
        throw new Exception('Mã OTP đã hết hạn. Vui lòng yêu cầu mã mới.', 400);
    }

    // 4. Xác thực mã OTP
    error_log("[RESET-PASS] Mã còn hạn. Đang xác thực (password_verify)...");
    
    if (!password_verify($otp, $reset_request['token'])) {
        error_log("[WARNING] [RESET-PASS]: Lỗi 400 - Mã OTP KHÔNG KHỚP.");
        throw new Exception('Mã OTP không chính xác.', 400);
    }

    // 5. Mọi thứ đều OK -> Cập nhật mật khẩu mới
    error_log("[RESET-PASS] OTP chính xác. Đang hash mật khẩu mới...");
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    $mysqli->begin_transaction();
    
    // ⭐ SỬA LỖI TÊN CỘT: Sửa "password" thành "password_hash"
    $stmt_update = $mysqli->prepare("UPDATE customers SET password_hash = ? WHERE email = ?");
    $stmt_update->bind_param("ss", $new_password_hash, $email);
    $stmt_update->execute();
    
    if ($stmt_update->affected_rows === 0) {
         $mysqli->rollback();
         error_log("[ERROR] [RESET-PASS]: Lỗi 404 - Không tìm thấy tài khoản '{$email}' để cập nhật.");
         throw new Exception('Không tìm thấy tài khoản để cập nhật.', 404);
    }
    $stmt_update->close();
    error_log("[DEBUG] [RESET-PASS]: Đã cập nhật mật khẩu (cột password_hash).");

    // 6. Xóa OTP đã sử dụng
    $stmt_delete = $mysqli->prepare("DELETE FROM password_resets WHERE email = ?");
    $stmt_delete->bind_param("s", $email);
    $stmt_delete->execute();
    $stmt_delete->close();
    error_log("[DEBUG] [RESET-PASS]: Đã xóa OTP.");
    
    // 7. Hoàn tất
    $mysqli->commit();
    error_log("[SUCCESS] [RESET-PASS]: Hoàn tất. Mật khẩu cho {$email} đã được đặt lại.");
    respond(['isSuccess' => true, 'message' => 'Đặt lại mật khẩu thành công! Bạn có thể đăng nhập ngay.']);

} catch (Throwable $e) { // Bắt mọi lỗi
    $mysqli->rollback();
    $error_message = $e->getMessage();
    $error_code = $e->getCode();
    
    if ($error_code < 400 || $error_code >= 600) {
        $error_code = 500;
    }

    error_log("[FATAL ERROR] [RESET-PASS]: Code: {$error_code} - " . $error_message);
    
    respond([
        'isSuccess' => false, 
        'message' => $error_message
    ], $error_code);
}
?>