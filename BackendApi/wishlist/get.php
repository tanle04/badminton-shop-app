<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../bootstrap.php"; // Giả định chứa hàm respond() và $mysqli
require_once "../utils/price_calculator.php"; // ⭐ THÊM: Logic tính giá sale

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

    // ⭐ SỬA SQL: Lấy giá gốc từ p.price để PHP tính toán
    $sql = "
    SELECT 
        p.productID, 
        p.productName, 
        p.price AS basePrice, /* ⭐ Đổi tên cột giá để giữ giá gốc */
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
        $productID = (int)$row['productID'];
        
        // ⭐ BƯỚC QUAN TRỌNG: GỌI HÀM TÍNH GIÁ SALE VÀ GHI ĐÈ GIÁ
        $price_details = get_best_sale_price_for_product_list($mysqli, $productID);

        // price là trường mà Android đang đọc là giá cuối cùng
        $row['price'] = $price_details['salePrice'];
        
        // Gán các cờ sale cần thiết
        $row['originalPriceMin'] = $price_details['originalPrice']; 
        $row['isDiscounted'] = $price_details['isDiscounted'];
        
        // Xử lý tên file ảnh (giữ nguyên logic)
        $img = $row['imageUrl'] ?? "";
        if ($img && preg_match('/^http/', $img)) {
            $img = basename($img);
        }
        $row['imageUrl'] = $img ?: "no_image.png"; 

        // Loại bỏ cột tạm thời
        unset($row['basePrice']); 
        
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