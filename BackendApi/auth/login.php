<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
require_once __DIR__ . '/../bootstrap.php'; // Giả định chứa hàm respond()

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        // ⭐ SỬA: Dùng isSuccess trong phản hồi lỗi
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $email = isset($input['email']) ? trim($input['email']) : '';
    $password = isset($input['password']) ? (string)$input['password'] : '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        // ⭐ SỬA: Dùng isSuccess trong phản hồi lỗi
        respond(['isSuccess' => false, 'message' => 'Email hoặc mật khẩu không hợp lệ.'], 400);
    }

    // ⭐ THAY ĐỔI 1: THÊM CỘT `isEmailVerified` VÀO CÂU LỆNH SELECT
    $stmt = $mysqli->prepare(
        "SELECT customerID, fullName, email, password_hash, phone, createdDate, isEmailVerified
         FROM customers WHERE email = ? LIMIT 1"
    );
    
    if (!$stmt) {
        throw new Exception("Lỗi chuẩn bị SQL: " . $mysqli->error);
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // 1. Kiểm tra tồn tại user và mật khẩu
    if (!$user || !password_verify($password, $user['password_hash'])) {
        // ⭐ SỬA: Dùng isSuccess trong phản hồi lỗi
        respond(['isSuccess' => false, 'message' => 'Sai email hoặc mật khẩu.'], 401);
    }
    
    // ⭐ THAY ĐỔI 2: KIỂM TRA TRẠNG THÁI XÁC NHẬN EMAIL
    if ($user['isEmailVerified'] == 0) {
        // Trả về lỗi nếu tài khoản chưa được xác nhận
        respond([
            'isSuccess' => false,
            // Sử dụng một message cụ thể để client (Android) có thể hiển thị
            'message' => 'unverified_email', 
            'error' => 'Tài khoản chưa được xác nhận email. Vui lòng kiểm tra hộp thư của bạn.'
        ], 403); // HTTP 403 Forbidden
    }

    // Rehash mật khẩu nếu cần
    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt_update = $mysqli->prepare("UPDATE customers SET password_hash = ? WHERE customerID = ?");
        if ($stmt_update) {
            $stmt_update->bind_param("si", $newHash, $user['customerID']);
            $stmt_update->execute();
            $stmt_update->close();
        }
    }

    // Không bao giờ gửi password_hash về cho client
    unset($user['password_hash']);
    
    // ⭐ PHẢN HỒI THÀNH CÔNG: Sử dụng cấu trúc DTO nhất quán (isSuccess)
    respond([
        'isSuccess' => true,
        'message' => 'ok', 
        'user' => $user // Bây giờ user cũng bao gồm isEmailVerified = 1
    ]);

} catch (Throwable $e) {
    error_log("Login API Error: " . $e->getMessage());
    // ⭐ SỬA: Dùng isSuccess trong phản hồi lỗi
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra phía server: ' . $e->getMessage()], 500);
}
// Lưu ý: Thẻ đóng ?> bị loại bỏ.