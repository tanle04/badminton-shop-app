<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../bootstrap.php'; // Giả định chứa hàm respond() và $mysqli
require_once __DIR__ . '/../utils/price_calculator.php'; // ⭐ THÊM: Logic tính giá sale

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }
    
    $id = (int)($_GET['id'] ?? 0); 
    if ($id <= 0) {
        respond(['isSuccess' => false, 'message' => 'ID sản phẩm không hợp lệ.'], 400);
    }

    // 1. Lấy thông tin sản phẩm chính và thống kê reviews (Cần sửa SQL để lấy AVG/COUNT reviews)
    $sqlProduct = "
        SELECT 
            p.productID, p.productName, p.description, p.price, p.stock AS stockTotal, 
            p.categoryID, p.brandID, p.createdDate, p.is_active,
            b.brandName, c.categoryName,
            COALESCE(AVG(r.rating), 0) AS averageRating,
            COUNT(r.reviewID) AS totalReviews
        FROM products p
        LEFT JOIN brands b ON b.brandID = p.brandID
        LEFT JOIN categories c ON c.categoryID = p.categoryID
        LEFT JOIN reviews r ON r.productID = p.productID AND r.status = 'published'
        WHERE p.productID=?
        GROUP BY p.productID
    "; 
    
    $stmt = $mysqli->prepare($sqlProduct);
    if (!$stmt) {
        respond(['isSuccess' => false, 'message' => 'SQL Prepare failed: ' . $mysqli->error], 500);
    }
    
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$product) {
        respond(['isSuccess' => false, 'message' => 'Không tìm thấy sản phẩm.'], 404);
    }
    
    // Ép kiểu dữ liệu mới
    $product['averageRating'] = round((float)$product['averageRating'], 1);
    $product['totalReviews'] = (int)$product['totalReviews'];
    $product['stockTotal'] = (int)$product['stockTotal']; // Đảm bảo đúng kiểu


    // 2. Lấy danh sách ảnh (Giữ nguyên)
    $imgs = [];
    $st2 = $mysqli->prepare("SELECT imageUrl FROM productimages WHERE productID=? ORDER BY imageID ASC");
    $st2->bind_param("i", $id);
    $st2->execute();
    $res2 = $st2->get_result();
    
    while($r = $res2->fetch_assoc()) {
        $imgs[] = ['imageUrl' => $r['imageUrl']];
    }
    $st2->close();
    
    // 3. Lấy thông tin biến thể (variants) và áp dụng SALE PRICE
    $sqlVariants = "
        SELECT v.variantID, v.productID, v.sku, v.price, v.stock, v.reservedStock, 
               GROUP_CONCAT(pav.valueName SEPARATOR ', ') AS attributes
        FROM product_variants v
        /* ⭐ SỬA: Loại bỏ JOIN p ON p.productID = v.productID nếu không cần, hoặc đảm bảo v.productID được dùng */
        LEFT JOIN variant_attribute_values vv ON v.variantID = vv.variantID
        LEFT JOIN product_attribute_values pav ON vv.valueID = pav.valueID
        WHERE v.productID = ?
        GROUP BY v.variantID
        ORDER BY v.price ASC
    ";
    
    $stmtVar = $mysqli->prepare($sqlVariants);
    $stmtVar->bind_param("i", $id);
    $stmtVar->execute();
    $resultVar = $stmtVar->get_result();
    
    $variants = [];
    while ($row = $resultVar->fetch_assoc()) {
        $variantID = (int)$row['variantID'];

        // ⭐ GỌI HÀM TÍNH GIÁ SALE VÀ GẮN CÁC TRƯỜNG MỚI
        $price_details = get_best_sale_price($mysqli, $variantID);
        
        $row['originalPrice'] = (float)$row['price'];
        $row['salePrice'] = $price_details['salePrice'];
        $row['isDiscounted'] = $price_details['isDiscounted'];
        $row['discountID'] = $price_details['discountID'];
        
        // Ghi đè trường price cũ bằng giá sale cuối cùng (cho gọn DTO trên Android)
        $row['price'] = $row['salePrice'];
        
        // Ép kiểu các giá trị
        $row['price'] = (float)$row['price']; 
        $row['stock'] = (int)$row['stock'];
        $row['reservedStock'] = (int)$row['reservedStock'];
        $row['variantID'] = (int)$row['variantID'];
        
        $variants[] = $row;
    }
    $stmtVar->close();


    // 4. Gói dữ liệu
    $product['images'] = $imgs;
    $product['variants'] = $variants;

    // 5. Trả về phản hồi THÀNH CÔNG (cấu trúc ProductDetailResponse)
    respond([
        'isSuccess' => true,
        'message' => 'Product detail loaded successfully.',
        'product' => $product 
    ]);
    
} catch (Throwable $e) {
    // Xử lý lỗi server
    error_log("Product Detail API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra phía server: ' . $e->getMessage()], 500);
}