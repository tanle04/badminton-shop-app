<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../bootstrap.php'; // Giả định chứa hàm respond() và $mysqli
require_once '../utils/price_calculator.php'; // ⭐ THÊM: Logic tính giá sale

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }
    
    if (!isset($_GET['productID']) || !is_numeric($_GET['productID'])) {
        respond(['isSuccess' => false, 'message' => 'Thiếu productID hoặc productID không hợp lệ.'], 400);
    }

    $productID = (int)$_GET['productID'];
    
    // ⭐ SỬA SQL: Lấy thêm reservedStock và join với products để lọc is_active
    $sql = "SELECT v.variantID, v.price AS priceBase, v.stock, v.reservedStock, v.productID,
               GROUP_CONCAT(pav.valueName SEPARATOR ', ') AS attributes
            FROM product_variants v
            JOIN products p ON p.productID = v.productID /* JOIN để lọc is_active */
            JOIN variant_attribute_values vav ON v.variantID = vav.variantID
            JOIN product_attribute_values pav ON vav.valueID = pav.valueID
            WHERE v.productID = ? AND p.is_active = 1 /* ⭐ Lọc theo is_active */
            GROUP BY v.variantID
            ORDER BY v.price ASC"; 
    
    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        respond(['isSuccess' => false, 'message' => 'SQL Prepare failed: ' . $mysqli->error], 500);
    }
    
    $stmt->bind_param("i", $productID);
    $stmt->execute();
    $result = $stmt->get_result();

    $variants = [];
    while ($row = $result->fetch_assoc()) {
        $variantID = (int)$row['variantID'];
        
        // ⭐ BƯỚC QUAN TRỌNG: GỌI HÀM TÍNH GIÁ SALE VÀ GẮN CÁC TRƯỜNG MỚI
        $price_details = get_best_sale_price($mysqli, $variantID);
        
        // Gắn các trường sale vào biến thể
        $row['originalPrice'] = (float)$row['priceBase'];
        $row['salePrice'] = $price_details['salePrice'];
        $row['isDiscounted'] = $price_details['isDiscounted'];
        
        // Ghi đè trường price cũ bằng giá sale cuối cùng (cho Android DTO)
        $row['price'] = $row['salePrice']; 
        
        // Ép kiểu cuối cùng
        $row['price'] = (float) $row['price'];
        $row['stock'] = (int) $row['stock'];
        $row['reservedStock'] = (int) $row['reservedStock'];
        $row['variantID'] = (int) $row['variantID'];
        
        unset($row['priceBase']); // Loại bỏ cột giá gốc tạm thời
        $variants[] = $row;
    }

    $stmt->close();
    
    // ⭐ PHẢN HỒI THÀNH CÔNG: Sử dụng respond() với cấu trúc DTO
    respond([
        "isSuccess" => true,
        "message" => "Variants loaded successfully.",
        "variants" => $variants
    ]);

} catch (Throwable $e) {
    error_log("Variant List API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra phía server: ' . $e->getMessage()], 500);
}