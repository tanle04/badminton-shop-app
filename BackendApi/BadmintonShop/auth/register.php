<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

// ⭐ THAY ĐỔI 1: Tải tất cả các thư viện (PHPMailer) qua Composer Autoload
// Đường dẫn: /auth/ -> /vendor/autoload.php
require_once __DIR__ . '/../vendor/autoload.php'; 

require_once __DIR__ . '/../bootstrap.php'; // Giả định chứa hàm respond() và $mysqli
require_once __DIR__ . '/../utils/email_helper.php'; // Chứa hàm sendVerificationEmail()

// ======================================================================
// ⭐⭐ CẤU HÌNH ĐƯỜNG DẪN XÁC MINH (BẮT BUỘC SỬA) ⭐⭐
// ======================================================================

// Phương án 1 (Production/Hosting): Sử dụng domain thật của bạn
// const VERIFICATION_URL_BASE = "https://mybadmintonshop.com/api/auth/verify.php?token=";

// Phương án 2 (Local Testing/Máy ảo Android): 
// 10.0.2.2 là địa chỉ đặc biệt để máy ảo Android truy cập máy tính chủ (Localhost)
// ĐƯỜNG DẪN ĐÃ SỬA để khớp với cấu trúc thư mục C:\xampp\htdocs\api\BadmintonShop\auth
const VERIFICATION_URL_BASE = "http://10.0.2.2/api/BadmintonShop/auth/verify.php?token="; 

// ======================================================================

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    
    $fullName = trim($input['fullName'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = (string)($input['password'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $address = trim($input['address'] ?? ''); // Giữ lại address từ input dù không lưu vào customers

    // --- Validation ---
    if ($fullName === '' || $password === '' || $phone === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(['isSuccess' => false, 'message' => 'Vui lòng điền đầy đủ họ tên, email hợp lệ, mật khẩu và số điện thoại.'], 400);
    }
    if (strlen($password) < 6) {
        respond(['isSuccess' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự.'], 400);
    }

    // Kiểm tra email trùng lặp
    $stmt_check = $mysqli->prepare("SELECT 1 FROM customers WHERE email = ?");
    if (!$stmt_check) {
        throw new Exception("Lỗi chuẩn bị SQL kiểm tra email: " . $mysqli->error);
    }
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $stmt_check->close();
        respond(['isSuccess' => false, 'message' => 'Email này đã được sử dụng.'], 409);
    }
    $stmt_check->close();

    // --- Tạo Token và Thời gian hết hạn ---
    $verificationToken = bin2hex(random_bytes(32)); // Tạo token 64 ký tự hex
    $tokenExpiry = date('Y-m-d H:i:s', strtotime('+1 day'));
    
    // --- Thực hiện đăng ký ---
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // SQL: Thêm 3 cột mới (isEmailVerified, verificationToken, tokenExpiry)
    $stmt_insert = $mysqli->prepare(
        "INSERT INTO customers (fullName, email, password_hash, phone, isEmailVerified, verificationToken, tokenExpiry)
         VALUES (?, ?, ?, ?, 0, ?, ?)" // isEmailVerified mặc định là 0
    );
    if (!$stmt_insert) {
        throw new Exception("Lỗi chuẩn bị SQL đăng ký: " . $mysqli->error);
    }
    
    $stmt_insert->bind_param("ssssss", $fullName, $email, $password_hash, $phone, $verificationToken, $tokenExpiry);
    $stmt_insert->execute();
    
    $newId = $mysqli->insert_id;
    $stmt_insert->close();
    
    if ($newId === 0) {
        throw new Exception("Không thể tạo bản ghi khách hàng.");
    }

    // ⭐ Gửi email và xử lý lỗi ⭐
    try {
        $verificationLink = VERIFICATION_URL_BASE . $verificationToken;
        $emailSent = sendVerificationEmail($email, $fullName, $verificationLink); 

        if (!$emailSent) {
            // Ghi log nếu hàm gửi email báo thất bại (return false)
            error_log("Failed to send verification email (returned false) to: " . $email);
        }
    } catch (\Throwable $emailException) {
        // Bắt bất kỳ lỗi nghiêm trọng nào
        error_log("Email sending system exception: " . $emailException->getMessage());
        // Cho phép luồng đăng ký thành công tiếp tục
    }

    // PHẢN HỒI THÀNH CÔNG: Thông báo rằng cần xác nhận email
    respond([
        'isSuccess' => true,
        'message' => 'registered_pending_verification', // Message cho client Android xử lý
        'customerID' => $newId,
        'user' => [
            'customerID' => $newId,
            'fullName' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'isEmailVerified' => 0 
        ]
    ], 201); // 201 Created

} catch (Throwable $e) {
    // Đây là khối catch cho các lỗi SQL hoặc logic chính
    error_log("Register API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra phía server: ' . $e->getMessage()], 500);
}
