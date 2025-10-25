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

    // 2. Câu SQL JOIN nhiều bảng để lấy thông tin chi tiết
    $sql = "
        SELECT
            sc.cartID,
            sc.quantity,
            p.productID,
            p.productName,
            pv.variantID,
            pv.price AS variantPrice,
            pv.stock, -- LẤY CỘT TỒN KHO
            (SELECT pi.imageUrl FROM productimages pi WHERE pi.productID = p.productID ORDER BY pi.imageID ASC LIMIT 1) AS imageUrl,
            GROUP_CONCAT(CONCAT(pa.attributeName, ': ', pav.valueName) SEPARATOR ', ') AS variantDetails
        FROM shopping_cart sc
        JOIN product_variants pv ON sc.variantID = pv.variantID
        JOIN products p ON pv.productID = p.productID
        LEFT JOIN variant_attribute_values vav ON pv.variantID = vav.variantID
        LEFT JOIN product_attribute_values pav ON vav.valueID = pav.valueID
        LEFT JOIN product_attributes pa ON pav.attributeID = pa.attributeID
        WHERE sc.customerID = ?
        AND p.is_active = 1 /* ⭐ ĐIỀU KIỆN LỌC SẢN PHẨM HOẠT ĐỘNG */
        GROUP BY sc.cartID, sc.quantity, p.productID, p.productName, pv.variantID, pv.price, pv.stock, imageUrl
        ORDER BY sc.addedDate DESC
    ";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        respond(['isSuccess' => false, 'message' => 'SQL Prepare failed: ' . $mysqli->error], 500);
    }
    
    $stmt->bind_param("i", $customerID);
    $stmt->execute();
    $res = $stmt->get_result();

    $data = [];
    while ($row = $res->fetch_assoc()) {
        $row['variantPrice'] = (string) $row['variantPrice'];
        // ÉP KIỂU STOCK SANG INT
        $row['stock'] = (int) $row['stock']; 
        
        $img = $row['imageUrl'] ?? "";
        // Xử lý tên file (giữ nguyên logic của bạn)
        if ($img && preg_match('/^http/', $img)) {
            $img = basename($img); 
        }
        $row['imageUrl'] = $img ?: "no_image.png";
        
        $row['isSelected'] = true; 
        $data[] = $row;
    }

    $stmt->close();
    
    // PHẢN HỒI THÀNH CÔNG: Sử dụng respond() với cấu trúc DTO
    respond([
        "isSuccess" => true,
        "message" => "Cart items loaded successfully.",
        "items" => $data // Khớp với trường 'items' trong CartResponse
    ]);

} catch (Throwable $e) {
    error_log("Cart Get API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra phía server: ' . $e->getMessage()], 500);
}
// Lưu ý: Thẻ đóng ?> bị loại bỏ.