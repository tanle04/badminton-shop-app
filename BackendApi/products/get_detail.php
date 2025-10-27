<?php
header("Content-Type: application/json; charset=UTF-8");
// Giả định file bootstrap.php chứa kết nối $mysqli và hàm respond()
require_once '../bootstrap.php'; 
// YÊU CẦU: Phải có file price_calculator.php chứa hàm get_best_sale_price()
require_once '../utils/price_calculator.php'; 

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }
    
    // 1. Validation
    if (!isset($_GET['productID']) || !is_numeric($_GET['productID'])) {
        respond(['isSuccess' => false, 'message' => 'productID không hợp lệ.'], 400);
    }
    $productID = (int)$_GET['productID'];

    // 2. Lấy thông tin sản phẩm chính
    $stmt_product = $mysqli->prepare("
        SELECT p.*, c.categoryName, b.brandName
        FROM products p
        JOIN categories c ON p.categoryID = c.categoryID
        JOIN brands b ON p.brandID = b.brandID
        WHERE p.productID = ? AND p.is_active = 1
    ");
    
    if (!$stmt_product) {
        respond(['isSuccess' => false, 'message' => 'SQL Prepare (Product) failed: ' . $mysqli->error], 500);
    }
    
    $stmt_product->bind_param("i", $productID);
    $stmt_product->execute();
    $result_product = $stmt_product->get_result();
    $product = $result_product->fetch_assoc();
    $stmt_product->close();
    
    if (!$product) {
        respond(['isSuccess' => false, 'message' => 'Không tìm thấy sản phẩm.'], 404);
    }

    // 3. Lấy thông tin Variants và áp dụng Sale Price
    $stmt_variants = $mysqli->prepare("SELECT * FROM product_variants WHERE productID = ?");
    
    if (!$stmt_variants) {
        respond(['isSuccess' => false, 'message' => 'SQL Prepare (Variants) failed: ' . $mysqli->error], 500);
    }
    
    $stmt_variants->bind_param("i", $productID);
    $stmt_variants->execute();
    $result_variants = $stmt_variants->get_result();
    
    $variants = [];
    while ($row = $result_variants->fetch_assoc()) {
        
        $variantID = (int)$row['variantID'];

        // ⭐ TÍNH GIÁ SALE VÀ GẮN VÀO DỮ LIỆU TRẢ VỀ
        // Hàm get_best_sale_price() cần được định nghĩa trong price_calculator.php
        $price_details = get_best_sale_price($mysqli, $variantID);
        
        $row['originalPrice'] = (float)$row['price'];
        $row['price'] = $price_details['salePrice']; // Ghi đè trường price bằng giá sale cuối cùng
        $row['isDiscounted'] = $price_details['isDiscounted'];
        $row['discountID'] = $price_details['discountID']; // ID chương trình sale áp dụng
        
        // Chuyển đổi các giá trị Decimal/String sang Float để nhất quán trong JSON
        $row['price'] = (float)$row['price'];
        
        $variants[] = $row;
    }
    
    $stmt_variants->close();

    // 4. Kết hợp và Phản hồi
    $product['variants'] = $variants;

    respond([
        "isSuccess" => true,
        "message" => "Product details loaded successfully.",
        "product" => $product
    ]);

} catch (Throwable $e) {
    error_log("Get Product Detail API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra phía server: ' . $e->getMessage()], 500);
}