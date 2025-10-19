<?php
header("Content-Type: application/json; charset=UTF-8");
require_once("../bootstrap.php"); // Giả định chứa hàm respond()

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }
    
    if (!isset($_GET['customerID'])) {
        respond(['isSuccess' => false, 'message' => 'Thiếu customerID.'], 400);
    }

    $customerID = intval($_GET['customerID']);
    
    if ($customerID <= 0) {
        respond(['isSuccess' => false, 'message' => 'ID khách hàng không hợp lệ.'], 400);
    }

    $sql = "
    SELECT 
        p.productID, 
        p.productName, 
        p.price, 
        b.brandName,
        (
            SELECT pi.imageUrl 
            FROM productimages pi 
            WHERE pi.productID = p.productID 
            ORDER BY pi.imageID ASC 
            LIMIT 1
        ) AS imageUrl
    FROM wishlists w
    JOIN products p ON w.productID = p.productID
    LEFT JOIN brands b ON p.brandID = b.brandID
    WHERE w.customerID = ?
    ORDER BY w.created_at DESC
    ";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        respond(['isSuccess' => false, 'message' => 'SQL Prepare failed: ' . $mysqli->error], 500);
    }
    
    $stmt->bind_param("i", $customerID);
    $stmt->execute();
    $res = $stmt->get_result();

    $wishlist = [];
    while ($row = $res->fetch_assoc()) {
        $img = $row['imageUrl'] ?? "";
        
        // Xử lý tên file ảnh (giữ nguyên logic)
        if ($img && preg_match('/^http/', $img)) {
            $img = basename($img); 
        }
        
        $row['imageUrl'] = $img ?: "no_image.png"; 
        $wishlist[] = $row;
    }

    $stmt->close();
    
    // ⭐ PHẢN HỒI THÀNH CÔNG: Sử dụng respond() với cấu trúc DTO
    respond([
        "isSuccess" => true,
        "message" => "Wishlist loaded successfully.",
        "count" => count($wishlist),
        "wishlist" => $wishlist // Khớp với trường 'wishlist' trong WishlistGetResponse
    ]);

} catch (Throwable $e) {
    error_log("Wishlist Get API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra phía server: ' . $e->getMessage()], 500);
}
// Lưu ý: Thẻ đóng ?> bị loại bỏ.