<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../bootstrap.php'; // Giả định chứa hàm respond()

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }
    
    $id = (int)($_GET['id'] ?? 0); 
    if ($id <= 0) {
        respond(['isSuccess' => false, 'message' => 'ID sản phẩm không hợp lệ.'], 400);
    }

    // 1. Lấy thông tin sản phẩm chính
    $stmt = $mysqli->prepare("
        SELECT 
            p.productID, p.productName, p.description, p.price, p.stock AS stockTotal, 
            p.categoryID, p.brandID, p.createdDate,
            b.brandName, c.categoryName
        FROM products p
        LEFT JOIN brands b ON b.brandID = p.brandID
        LEFT JOIN categories c ON c.categoryID = p.categoryID
        WHERE p.productID=?
    ");
    
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
    
    // 2. Lấy danh sách ảnh (Trả về mảng Object để khớp với ProductDto.ImageDto)
    $imgs = [];
    $st2 = $mysqli->prepare("SELECT imageUrl FROM productimages WHERE productID=? ORDER BY imageID ASC");
    $st2->bind_param("i", $id);
    $st2->execute();
    $res2 = $st2->get_result();
    
    // Chuyển kết quả fetch sang mảng objects (chỉ có key 'imageUrl')
    while($r = $res2->fetch_assoc()) {
        $imgs[] = ['imageUrl' => $r['imageUrl']];
    }
    $st2->close();
    
    // 3. Lấy thông tin biến thể (variants) - Tương tự như detail.php đã sửa
    $sqlVariants = "
        SELECT v.variantID, v.productID, v.sku, v.price, v.stock, 
               GROUP_CONCAT(pav.valueName SEPARATOR ', ') AS attributes
        FROM product_variants v
        LEFT JOIN variant_attribute_values vv ON v.variantID = vv.variantID
        LEFT JOIN product_attribute_values pav ON vv.valueID = pav.valueID
        WHERE v.productID = ?
        GROUP BY v.variantID
        ORDER BY v.price ASC
    ";
    $stmtVar = $mysqli->prepare($sqlVariants);
    $stmtVar->bind_param("i", $id);
    $stmtVar->execute();
    $variants = $stmtVar->get_result()->fetch_all(MYSQLI_ASSOC);
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
// Lưu ý: Thẻ đóng ?> bị loại bỏ.