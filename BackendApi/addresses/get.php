<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../bootstrap.php'; // Giả định chứa hàm respond()

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }
    
    // 1. Validation
    if (!isset($_GET['customerID']) || !is_numeric($_GET['customerID'])) {
        respond(['isSuccess' => false, 'message' => 'customerID không hợp lệ.'], 400);
    }
    $customerID = (int)$_GET['customerID'];

    // 2. Thực hiện truy vấn
    $stmt = $mysqli->prepare("SELECT * FROM customer_addresses WHERE customerID = ? ORDER BY isDefault DESC, addressID DESC");
    
    if (!$stmt) {
        respond(['isSuccess' => false, 'message' => 'SQL Prepare failed: ' . $mysqli->error], 500);
    }
    
    $stmt->bind_param("i", $customerID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $addresses = [];

    // Chuyển đổi giá trị isDefault sang boolean
    while ($row = $result->fetch_assoc()) {
        // ⭐ Ép kiểu (int) 1 hoặc 0 thành (bool) true hoặc false
        $row['isDefault'] = (bool)$row['isDefault']; 
        $addresses[] = $row;
    }

    $stmt->close();

    // ⭐ PHẢN HỒI THÀNH CÔNG: Sử dụng respond() với cấu trúc DTO
    respond([
        "isSuccess" => true,
        "message" => "Addresses loaded successfully.",
        "addresses" => $addresses // Khớp với trường 'addresses' trong AddressListResponse
    ]);

} catch (Throwable $e) {
    error_log("Get Addresses API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra phía server: ' . $e->getMessage()], 500);
}
// Lưu ý: Thẻ đóng ?> bị loại bỏ.