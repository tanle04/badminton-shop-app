<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../bootstrap.php'; // Giả định chứa hàm respond()

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }
    
    if (!isset($_GET['productID']) || !is_numeric($_GET['productID'])) {
        respond(['isSuccess' => false, 'message' => 'Thiếu productID hoặc productID không hợp lệ.'], 400);
    }

    $productID = (int)$_GET['productID'];
    
    $sql = "SELECT v.variantID, v.price, v.stock, GROUP_CONCAT(pav.valueName SEPARATOR ', ') AS attributes
            FROM product_variants v
            JOIN variant_attribute_values vav ON v.variantID = vav.variantID
            JOIN product_attribute_values pav ON vav.valueID = pav.valueID
            WHERE v.productID = ?
            GROUP BY v.variantID, v.price, v.stock"; // Thêm price, stock vào GROUP BY
    
    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        respond(['isSuccess' => false, 'message' => 'SQL Prepare failed: ' . $mysqli->error], 500);
    }
    
    $stmt->bind_param("i", $productID);
    $stmt->execute();
    $result = $stmt->get_result();

    $variants = [];
    while ($row = $result->fetch_assoc()) {
        // ⭐ Ép kiểu giá trị DECIMAL/FLOAT sang chuỗi để khớp với DTO Android (nếu cần)
        $row['price'] = (string) $row['price'];
        $variants[] = $row;
    }

    $stmt->close();
    
    // ⭐ PHẢN HỒI THÀNH CÔNG: Sử dụng respond() với cấu trúc DTO
    respond([
        "isSuccess" => true,
        "message" => "Variants loaded successfully.",
        "variants" => $variants // Khớp với trường 'variants' trong VariantListResponse
    ]);

} catch (Throwable $e) {
    error_log("Variant List API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra phía server: ' . $e->getMessage()], 500);
}

// Lưu ý: Thẻ đóng ?> bị loại bỏ.