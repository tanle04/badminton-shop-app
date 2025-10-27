<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../bootstrap.php'; // Giả định chứa hàm respond() và $mysqli
require_once '../utils/price_calculator.php'; // ⭐ THÊM: Logic tính giá sale

// Hàm respond(data, status_code) được giả định đã tồn tại trong bootstrap.php

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }

    if (!isset($_GET['productID'])) {
        respond(['isSuccess' => false, 'message' => 'Thiếu productID.'], 400);
    }

    $productID = intval($_GET['productID']);

    // 1. Lấy thông tin sản phẩm chính VÀ Tóm tắt Đánh giá
    // (Giữ nguyên SQL ban đầu của bạn)
    $sqlProduct = "
        SELECT 
            p.*, 
            b.brandName, 
            c.categoryName,
            COALESCE(AVG(r.rating), 0) AS averageRating,
            COUNT(r.reviewID) AS totalReviews
        FROM products p
        LEFT JOIN brands b ON p.brandID = b.brandID
        LEFT JOIN categories c ON p.categoryID = c.categoryID
        LEFT JOIN reviews r ON r.productID = p.productID AND r.status = 'published'
        WHERE p.productID = ? AND p.is_active = 1
        GROUP BY p.productID, p.productName, p.description, p.price, p.stock, p.categoryID, p.brandID, p.createdDate, p.is_active, b.brandName, c.categoryName
    "; 
    
    $stmt = $mysqli->prepare($sqlProduct);
    if (!$stmt) {
        respond(['isSuccess' => false, 'message' => 'SQL Prepare failed: ' . $mysqli->error], 500);
    }
    
    $stmt->bind_param("i", $productID);
    $stmt->execute();
    $res = $stmt->get_result();
    $product = $res->fetch_assoc();
    $stmt->close();

    // Nếu không tìm thấy sản phẩm HOẶC sản phẩm không active, trả về 404
    if (!$product) {
        respond(['isSuccess' => false, 'message' => 'Không tìm thấy sản phẩm.'], 404);
    }
    
    // Ép kiểu dữ liệu
    $product['averageRating'] = round((float)$product['averageRating'], 1);
    $product['totalReviews'] = (int)$product['totalReviews'];


    // 2. Lấy Ảnh sản phẩm (Giữ nguyên)
    $sqlImg = "SELECT imageUrl FROM productimages WHERE productID = ? ORDER BY imageID ASC";
    $stmtImg = $mysqli->prepare($sqlImg);
    $stmtImg->bind_param("i", $productID);
    $stmtImg->execute();
    $resImg = $stmtImg->get_result();
    $images = $resImg->fetch_all(MYSQLI_ASSOC);
    $product['images'] = $images;
    $stmtImg->close();

    // 3. Lấy danh sách biến thể (size, giá, tồn kho)
    $sqlVariants = "
        SELECT v.variantID, v.sku, v.price, v.stock, v.reservedStock, p.productID, 
               GROUP_CONCAT(pav.valueName SEPARATOR ', ') AS attributes
        FROM product_variants v
        INNER JOIN products p ON p.productID = v.productID 
        LEFT JOIN variant_attribute_values vv ON v.variantID = vv.variantID
        LEFT JOIN product_attribute_values pav ON vv.valueID = pav.valueID
        WHERE v.productID = ?
        GROUP BY v.variantID
        ORDER BY v.price ASC
    ";
    $stmtVar = $mysqli->prepare($sqlVariants);
    $stmtVar->bind_param("i", $productID);
    $stmtVar->execute();
    $resVar = $stmtVar->get_result();
    
    $variants = [];
    while ($row = $resVar->fetch_assoc()) {
        $variantID = (int)$row['variantID'];
        
        // ⭐ BƯỚC QUAN TRỌNG: GỌI HÀM TÍNH GIÁ SALE VÀ GẮN CÁC TRƯỜNG MỚI
        // Hàm get_best_sale_price() được định nghĩa trong utils/price_calculator.php
        $price_details = get_best_sale_price($mysqli, $variantID);
        
        // Gán các trường giá sale vào biến thể
        // Price cũ (row['price']) là giá gốc. Ta sẽ sử dụng các trường mới
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
    
    $product['variants'] = $variants;
    $stmtVar->close();
    
    // 4. Trả về phản hồi THÀNH CÔNG (cấu trúc ProductDetailResponse)
    respond([
        'isSuccess' => true,
        'message' => 'Product detail fetched successfully.',
        'product' => $product // Dữ liệu sản phẩm được gói trong key 'product'
    ]);

} catch (Throwable $e) {
    // Xử lý lỗi toàn cục
    error_log("Product Detail API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra phía server: ' . $e->getMessage()], 500);
}