<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../bootstrap.php'; // Giả định chứa hàm respond()

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }
    
    $pid = (int)($_GET['productID'] ?? 0); 
    if ($pid <= 0) {
        respond(['isSuccess' => false, 'message' => 'productID không hợp lệ.'], 400);
    }

    // ⭐ CÂU LỆNH SQL ĐÃ SỬA: Sử dụng GROUP_CONCAT để tạo chuỗi 'attributes' duy nhất
    $sql = "
        SELECT v.variantID, v.sku, v.price, v.stock,
               GROUP_CONCAT(pav.valueName ORDER BY a.attributeName SEPARATOR ', ') AS attributes
        FROM product_variants v
        LEFT JOIN variant_attribute_values vav ON vav.variantID = v.variantID
        LEFT JOIN product_attribute_values pav ON pav.valueID = vav.valueID
        LEFT JOIN product_attributes a ON a.attributeID = pav.attributeID
        WHERE v.productID = ?
        AND p.is_active = 1 /* ĐIỀU KIỆN LỌC SẢN PHẨM HOẠT ĐỘNG */
        GROUP BY v.variantID, v.sku, v.price, v.stock
        ORDER BY v.price ASC";
        
    $st = $mysqli->prepare($sql);
    
    if (!$st) {
        respond(['isSuccess' => false, 'message' => 'SQL Prepare failed: ' . $mysqli->error], 500);
    }
    
    $st->bind_param("i", $pid);
    $st->execute(); 
    $res = $st->get_result();

    $variants = $res->fetch_all(MYSQLI_ASSOC);
    
    $st->close();
    
    if (empty($variants)) {
        respond([
            'isSuccess' => false, 
            'message' => 'Không tìm thấy biến thể cho sản phẩm này.',
            'variants' => []
        ], 404);
    }

    // ⭐ ĐÓNG GÓI PHẢN HỒI ĐỂ KHỚP VỚI VariantListResponse DTO
    respond([
        'isSuccess' => true,
        'message' => 'Variants loaded successfully.',
        'variants' => $variants
    ]);

} catch (Throwable $e) {
    error_log("Variant API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra phía server: ' . $e->getMessage()], 500);
}
// Lưu ý: Thẻ đóng ?> bị loại bỏ.