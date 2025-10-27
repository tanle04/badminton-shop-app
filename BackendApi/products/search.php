<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../bootstrap.php"; // Mở db + headers + hàm respond()
require_once "../utils/price_calculator.php"; // ⭐ THÊM: Logic tính giá sale

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }

    $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

    if ($keyword === '') {
        respond(['isSuccess' => false, 'message' => 'Thiếu từ khóa tìm kiếm.'], 400);
    }

    // ⭐ SỬA SQL: Bỏ COALESCE(MIN(v.price), p.price) AS priceMin
    // Thay vào đó, ta lấy p.price làm giá base để tính toán.
    $sql = "
        SELECT 
            p.productID, 
            p.productName, 
            p.description,
            p.price AS basePrice, /* ⭐ Lấy giá base để tính toán */
            COALESCE(SUM(v.stock), p.stock) AS stockTotal,
            b.brandName, 
            c.categoryName,
            (
                SELECT pi.imageUrl 
                FROM productimages pi 
                WHERE pi.productID = p.productID 
                ORDER BY pi.imageID ASC LIMIT 1
            ) AS imageUrl
        FROM products p
        LEFT JOIN product_variants v ON v.productID = p.productID
        LEFT JOIN brands b ON b.brandID = p.brandID
        LEFT JOIN categories c ON c.categoryID = p.categoryID
        WHERE p.productName LIKE CONCAT('%', ?, '%')
        AND p.is_active = 1 /* ĐIỀU KIỆN LỌC SẢN PHẨM HOẠT ĐỘNG */
        GROUP BY p.productID
        ORDER BY p.createdDate DESC
        LIMIT 50";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        respond(['isSuccess' => false, 'message' => 'SQL Prepare failed: ' . $mysqli->error], 500);
    }
    
    $stmt->bind_param("s", $keyword);
    $stmt->execute();
    $res = $stmt->get_result();

    $data = [];
    while ($row = $res->fetch_assoc()) {
        $productID = (int)$row['productID'];

        // ⭐ BƯỚC QUAN TRỌNG: GỌI HÀM TÍNH GIÁ SALE VÀ GHI ĐÈ GIÁ
        $price_details = get_best_sale_price_for_product_list($mysqli, $productID);

        // priceMin là trường mà Android đang đọc là giá cuối cùng (list view)
        $row['priceMin'] = $price_details['salePrice'];
        
        // Gán các cờ sale cần thiết cho ProductDto chính
        $row['originalPriceMin'] = $price_details['originalPrice']; 
        $row['isDiscounted'] = $price_details['isDiscounted'];
        
        // Loại bỏ trường basePrice không cần thiết trên Android
        unset($row['basePrice']); 

        // Xử lý tên file ảnh (giữ nguyên logic)
        $img = $row['imageUrl'] ?? "";
        if ($img && preg_match('/^http/', $img)) {
            $img = basename($img);
        }
        $row['imageUrl'] = $img ?: "no_image.png";
        
        // Xử lý stockTotal
        $row['stockTotal'] = (int)$row['stockTotal'];
        
        $data[] = $row;
    }

    $stmt->close();
    
    // ⭐ Dùng respond() với cấu trúc DTO thành công
    respond([
        "isSuccess" => true,
        "message" => "Found " . count($data) . " products.",
        "items" => $data
    ]);

} catch (Throwable $e) {
    error_log("Search API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra phía server: ' . $e->getMessage()], 500);
}