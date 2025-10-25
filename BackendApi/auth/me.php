<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
require_once __DIR__ . '/../bootstrap.php'; // Giả định chứa hàm respond()

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        // ⭐ SỬA: Đảm bảo phản hồi lỗi sử dụng cấu trúc isSuccess
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        // ⭐ SỬA: Đảm bảo phản hồi lỗi sử dụng cấu trúc isSuccess
        respond(['isSuccess' => false, 'message' => 'ID người dùng không hợp lệ.'], 400);
    }

    // Câu lệnh SELECT
    $stmt = $mysqli->prepare(
        "SELECT customerID, fullName, email, phone, createdDate
         FROM customers WHERE customerID = ?"
    );
    
    if (!$stmt) {
        throw new Exception("Lỗi chuẩn bị SQL: " . $mysqli->error);
    }
    
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        // ⭐ SỬA: Đảm bảo phản hồi lỗi sử dụng cấu trúc isSuccess
        respond(['isSuccess' => false, 'message' => 'Không tìm thấy người dùng.'], 404);
    }

    // ⭐ PHẢN HỒI THÀNH CÔNG: Gói dữ liệu và trả về isSuccess: true
    respond([
        'isSuccess' => true,
        'message' => 'Profile loaded successfully.',
        'user' => $user
    ]);

} catch (Throwable $e) {
    // Ghi log lỗi để debug
    error_log("Get Profile API Error: " . $e->getMessage());
    // ⭐ SỬA: Đảm bảo phản hồi lỗi sử dụng cấu trúc isSuccess
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra phía server: ' . $e->getMessage()], 500);
}
// Lưu ý: Thẻ đóng ?> bị loại bỏ.