<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../bootstrap.php';

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
    // ⭐ THÊM ĐIỀU KIỆN 'AND is_active = 1'
    $stmt = $mysqli->prepare("
        SELECT * FROM customer_addresses 
        WHERE customerID = ? AND is_active = 1 
        ORDER BY isDefault DESC, addressID DESC
    ");
    
    if (!$stmt) {
        respond(['isSuccess' => false, 'message' => 'SQL Prepare failed: ' . $mysqli->error], 500);
    }
    
    $stmt->bind_param("i", $customerID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $addresses = [];

    while ($row = $result->fetch_assoc()) {
        $row['isDefault'] = (bool)$row['isDefault']; 
        $addresses[] = $row;
    }

    $stmt->close();
    
    respond([
        "isSuccess" => true,
        "message" => "Addresses loaded successfully.",
        "addresses" => $addresses
    ]);

} catch (Throwable $e) {
    error_log("Get Addresses API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra phía server: ' . $e->getMessage()], 500);
}
