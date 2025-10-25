<?php
header("Content-Type: application/json; charset=UTF-8");
require_once("../bootstrap.php"); // Giả định chứa hàm respond()

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }
    
    // ⭐ 1. VALIDATION CHO customerID VÀ productID
    if (!isset($_GET['customerID']) || !isset($_GET['productID'])) {
        respond(['isSuccess' => false, 'message' => 'Thiếu customerID hoặc productID.'], 400);
    }

    $customerID = (int)($_GET['customerID'] ?? 0);
    $productID = (int)($_GET['productID'] ?? 0);
    
    if ($customerID <= 0 || $productID <= 0) {
        respond(['isSuccess' => false, 'message' => 'ID khách hàng hoặc sản phẩm không hợp lệ.'], 400);
    }

    // ⭐ 2. TRUY VẤN: Kiểm tra sự tồn tại của cặp (customerID, productID)
    $sql = "
        SELECT 
            COUNT(wishlistID) AS count
        FROM wishlists
        WHERE customerID = ? AND productID = ?
    ";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        respond(['isSuccess' => false, 'message' => 'SQL Prepare failed: ' . $mysqli->error], 500);
    }
    
    $stmt->bind_param("ii", $customerID, $productID);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    
    $exists = ($row['count'] > 0);

    // ⭐ 3. PHẢN HỒI: isSuccess = true nếu sản phẩm tồn tại trong wishlist
    if ($exists) {
        respond([
            "isSuccess" => true,
            "message" => "Sản phẩm nằm trong Wishlist.",
            "exists" => true
        ], 200);
    } else {
        respond([
            "isSuccess" => false, // Trả về false theo logic API khi không tìm thấy
            "message" => "Sản phẩm không nằm trong Wishlist.",
            "exists" => false
        ], 200);
    }

} catch (Throwable $e) {
    error_log("Wishlist Check API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra phía server: ' . $e->getMessage()], 500);
}
// Lưu ý: Thẻ đóng ?> bị loại bỏ.