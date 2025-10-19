<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
require_once __DIR__ . '/../bootstrap.php'; // Giả định chứa hàm respond()

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        // ⭐ SỬA: Đảm bảo phản hồi lỗi sử dụng cấu trúc isSuccess
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    
    $fullName = trim($input['fullName'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = (string)($input['password'] ?? '');
    $phone = trim($input['phone'] ?? '');
    
    // --- Validation ---
    if ($fullName === '' || $password === '' || $phone === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // ⭐ SỬA: Bổ sung phone vào kiểm tra lỗi
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
        // ⭐ SỬA: Dùng isSuccess trong phản hồi lỗi
        respond(['isSuccess' => false, 'message' => 'Email này đã được sử dụng.'], 409);
    }
    $stmt_check->close();

    // --- Thực hiện đăng ký ---
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt_insert = $mysqli->prepare(
        "INSERT INTO customers (fullName, email, password_hash, phone)
         VALUES (?, ?, ?, ?)"
    );
    if (!$stmt_insert) {
        throw new Exception("Lỗi chuẩn bị SQL đăng ký: " . $mysqli->error);
    }
    
    $stmt_insert->bind_param("ssss", $fullName, $email, $password_hash, $phone);
    $stmt_insert->execute();
    
    $newId = $mysqli->insert_id;
    $stmt_insert->close();
    
    if ($newId === 0) {
        throw new Exception("Không thể tạo bản ghi khách hàng.");
    }

    // ⭐ PHẢN HỒI THÀNH CÔNG: Sử dụng cấu trúc DTO nhất quán
    respond([
        'isSuccess' => true,
        'message' => 'registered', // Giữ lại message 'registered' cho code Android
        'customerID' => $newId,
        'user' => [
            'customerID' => $newId,
            'fullName' => $fullName,
            'email' => $email,
            'phone' => $phone
        ]
    ], 201); // 201 Created

} catch (Throwable $e) {
    // Ghi log lỗi để debug
    error_log("Register API Error: " . $e->getMessage());
    // ⭐ SỬA: Đảm bảo phản hồi lỗi sử dụng cấu trúc isSuccess
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra phía server: ' . $e->getMessage()], 500);
}
// Lưu ý: Thẻ đóng ?> bị loại bỏ.