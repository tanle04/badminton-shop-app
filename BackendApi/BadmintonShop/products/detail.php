<?php
header("Content-Type: application/json; charset=UTF-8");
require_once("../bootstrap.php"); // Giả định chứa hàm respond()

// Hàm respond(data, status_code) được giả định đã tồn tại trong bootstrap.php

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }

    if (!isset($_GET['productID'])) {
        respond(['isSuccess' => false, 'message' => 'Thiếu productID.'], 400);
    }

    $productID = intval($_GET['productID']);

    // 1. Lấy thông tin sản phẩm chính
    $sqlProduct = "SELECT p.*, b.brandName, c.categoryName
                   FROM products p
                   LEFT JOIN brands b ON p.brandID = b.brandID
                   LEFT JOIN categories c ON p.categoryID = c.categoryID
                   WHERE p.productID = ?";
    
    $stmt = $mysqli->prepare($sqlProduct);
    if (!$stmt) {
        respond(['isSuccess' => false, 'message' => 'SQL Prepare failed: ' . $mysqli->error], 500);
    }
    
    $stmt->bind_param("i", $productID);
    $stmt->execute();
    $res = $stmt->get_result();
    $product = $res->fetch_assoc();
    $stmt->close(); // Đóng stmt sau khi fetch

    if (!$product) {
        respond(['isSuccess' => false, 'message' => 'Không tìm thấy sản phẩm.'], 404);
    }

    // 2. Lấy Ảnh sản phẩm
    $sqlImg = "SELECT imageUrl FROM productimages WHERE productID = ? ORDER BY imageID ASC";
    $stmtImg = $mysqli->prepare($sqlImg);
    $stmtImg->bind_param("i", $productID);
    $stmtImg->execute();
    $resImg = $stmtImg->get_result();
    $images = $resImg->fetch_all(MYSQLI_ASSOC);
    $product['images'] = $images;
    $stmtImg->close(); // Đóng stmtImg

    // 3. Lấy danh sách biến thể (size, giá, tồn kho)
    $sqlVariants = "
        SELECT v.variantID, v.sku, v.price, v.stock, p.productID, -- ⭐ THÊM productID vào biến thể
               GROUP_CONCAT(pav.valueName SEPARATOR ', ') AS attributes
        FROM product_variants v
        LEFT JOIN products p ON p.productID = v.productID -- Join products để lấy p.productID
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
    $variants = $resVar->fetch_all(MYSQLI_ASSOC);
    $product['variants'] = $variants;
    $stmtVar->close(); // Đóng stmtVar

    // 4. Trả về phản hồi THÀNH CÔNG (cấu trúc ProductDetailResponse)
    respond([
        'isSuccess' => true,
        'message' => 'Product detail fetched successfully.',
        'product' => $product // Dữ liệu sản phẩm được gói trong key 'product'
    ]);

    // Không cần đóng $mysqli ở đây nếu nó được đóng trong respond() hoặc bootstrap.php

} catch (Throwable $e) {
    // Xử lý lỗi toàn cục
    error_log("Product Detail API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra phía server: ' . $e->getMessage()], 500);
}
// Lưu ý: Thẻ đóng ?> bị loại bỏ.