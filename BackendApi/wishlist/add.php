<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
require_once("../bootstrap.php"); // Giả định chứa hàm respond()

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }
    
    // ⭐ SỬA LỖI INPUT: Cố gắng đọc JSON, nếu thất bại, dùng $_POST (dữ liệu Form Data)
    $input = json_decode(file_get_contents("php://input"), true);
    if (!$input) {
        $input = $_POST;
    }
    
    // Kiểm tra dữ liệu từ $input
    if (!$input || !isset($input['customerID']) || !isset($input['productID'])) {
        respond(['isSuccess' => false, 'message' => 'Thiếu dữ liệu yêu cầu (customerID hoặc productID).'], 400);
    }

    $customerID = intval($input['customerID']);
    $productID = intval($input['productID']);

    if ($customerID <= 0 || $productID <= 0) {
        respond(['isSuccess' => false, 'message' => 'ID không hợp lệ.'], 400);
    }

    // ⭐ SỬA LỖI CỘT SQL: Thay 'createdDate' bằng 'created_at' để khớp với lược đồ DB
    $sql = "INSERT INTO wishlists (customerID, productID, created_at)
             VALUES (?, ?, CURRENT_TIMESTAMP)
             ON DUPLICATE KEY UPDATE created_at = CURRENT_TIMESTAMP"; 

    $stmt = $mysqli->prepare($sql);
    
    if (!$stmt) {
        $errorMessage = 'SQL Prepare failed: ' . $mysqli->error;
        error_log("SQL Prepare Error: " . $errorMessage);
        respond(['isSuccess' => false, 'message' => $errorMessage], 500);
    }
    
    $stmt->bind_param("ii", $customerID, $productID);

    if ($stmt->execute()) {
        if ($stmt->affected_rows >= 1) { 
            respond(['isSuccess' => true, 'message' => 'Đã thêm vào wishlist.'], 200);
        } else {
            respond(['isSuccess' => true, 'message' => 'Sản phẩm đã có trong wishlist.'], 200);
        }
    } else {
        $errorMessage = 'Lỗi thực thi SQL: ' . $stmt->error;
        error_log("SQL Execute Error: " . $errorMessage);
        respond(['isSuccess' => false, 'message' => $errorMessage], 500);
    }

    $stmt->close();

} catch (Throwable $e) {
    error_log("Wishlist Add API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra phía server: ' . $e->getMessage()], 500);
}
// Lưu ý: Thẻ đóng ?> bị loại bỏ.