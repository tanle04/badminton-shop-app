<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../bootstrap.php'; // Giả định chứa hàm respond() và $mysqli
require_once __DIR__ . '/../utils/price_calculator.php'; // ⭐ THÊM: Logic tính giá sale

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }
    
    $pid = (int)($_GET['productID'] ?? 0); 
    if ($pid <= 0) {
        respond(['isSuccess' => false, 'message' => 'productID không hợp lệ.'], 400);
    }

    // ⭐ CÂU LỆNH SQL: Cần lấy thêm productID và reservedStock nếu cần cho Android
    $sql = "
        SELECT v.variantID, v.productID, v.sku, v.price, v.stock, v.reservedStock,
               GROUP_CONCAT(pav.valueName ORDER BY a.attributeName SEPARATOR ', ') AS attributes
        FROM product_variants v
        JOIN products p ON p.productID = v.productID /* Cần JOIN để lọc is_active */
        LEFT JOIN variant_attribute_values vav ON vav.variantID = v.variantID
        LEFT JOIN product_attribute_values pav ON pav.valueID = vav.valueID
        LEFT JOIN product_attributes a ON a.attributeID = pav.attributeID
        WHERE v.productID = ? AND p.is_active = 1 /* ĐIỀU KIỆN LỌC SẢN PHẨM HOẠT ĐỘNG */
        GROUP BY v.variantID
        ORDER BY v.price ASC";
        
    $st = $mysqli->prepare($sql);
    
    if (!$st) {
        respond(['isSuccess' => false, 'message' => 'SQL Prepare failed: ' . $mysqli->error], 500);
    }
    
    $st->bind_param("i", $pid);
    $st->execute(); 
    $res = $st->get_result();

    $variants = [];
    while ($row = $res->fetch_assoc()) {
        $variantID = (int)$row['variantID'];

        // ⭐ BƯỚC QUAN TRỌNG: GỌI HÀM TÍNH GIÁ SALE VÀ GẮN CÁC TRƯỜNG MỚI
        $price_details = get_best_sale_price($mysqli, $variantID);
        
        // Gắn các trường sale vào biến thể
        $row['originalPrice'] = (float)$row['price'];
        $row['salePrice'] = $price_details['salePrice'];
        $row['isDiscounted'] = $price_details['isDiscounted'];
        
        // Ghi đè trường price cũ bằng giá sale cuối cùng
        $row['price'] = $row['salePrice'];
        
        // Ép kiểu (price, stock, variantID)
        $row['price'] = (float)$row['price'];
        $row['stock'] = (int)$row['stock'];
        $row['variantID'] = (int)$row['variantID'];
        
        $variants[] = $row;
    }
    
    $st->close();
    
    if (empty($variants)) {
        respond([
            'isSuccess' => true, 
            'message' => 'Không tìm thấy biến thể cho sản phẩm này.',
            'variants' => []
        ]); // Trả về 200 OK với mảng rỗng nếu không tìm thấy, vì không phải lỗi server
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