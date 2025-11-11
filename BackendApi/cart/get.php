<?php
// ⭐ THÊM 3 DÒNG NÀY ĐỂ CHỐNG CACHE
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Code gốc của bạn
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../bootstrap.php'; // Giả định chứa hàm respond() và $mysqli
require_once __DIR__ . '/../utils/price_calculator.php'; // ⭐ THÊM: Logic tính giá sale

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }
    
    // 1. Validation
    if (!isset($_GET['customerID']) || !is_numeric($_GET['customerID'])) {
        respond(['isSuccess' => false, 'message' => 'customerID không hợp lệ.'], 400);
    }
    $customerID = (int)$_GET['customerID'];

    // 2. Câu SQL JOIN nhiều bảng để lấy thông tin chi tiết
    // ⭐ SỬA SQL: Đổi tên pv.price thành pv.priceBase để tránh nhầm lẫn với giá sale cuối cùng
    $sql = "
        SELECT
            sc.cartID,
            sc.quantity,
            p.productID,
            p.productName,
            pv.variantID,
            pv.price AS priceBase, /* ⭐ LẤY GIÁ GỐC BIẾN THỂ */
            pv.stock,
            (SELECT pi.imageUrl FROM productimages pi WHERE pi.productID = p.productID ORDER BY (pi.imageType = 'main') DESC, pi.imageID ASC LIMIT 1) AS imageUrl,
            GROUP_CONCAT(CONCAT(pa.attributeName, ': ', pav.valueName) SEPARATOR ', ') AS variantDetails
        FROM shopping_cart sc
        JOIN product_variants pv ON sc.variantID = pv.variantID
        JOIN products p ON pv.productID = p.productID
        LEFT JOIN variant_attribute_values vav ON pv.variantID = vav.variantID
        LEFT JOIN product_attribute_values pav ON vav.valueID = pav.valueID
        LEFT JOIN product_attributes pa ON pav.attributeID = pa.attributeID
        WHERE sc.customerID = ?
        AND p.is_active = 1 
        AND pv.stock > 0 /* ⭐ SỬA: Chỉ lấy các sản phẩm có stock > 0 (còn hàng) */
        GROUP BY sc.cartID, sc.quantity, p.productID, p.productName, pv.variantID, pv.price, pv.stock, imageUrl
        ORDER BY sc.addedDate DESC
    ";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        respond(['isSuccess' => false, 'message' => 'SQL Prepare failed: ' . $mysqli->error], 500);
    }
    
    $stmt->bind_param("i", $customerID);
    $stmt->execute();
    $res = $stmt->get_result();

    $data = [];
    $subtotal = 0; // ⭐ Biến tính tổng tiền hàng (sau sale)
    
    while ($row = $res->fetch_assoc()) {
        $variantID = (int)$row['variantID'];
        $quantity = (int)$row['quantity'];
        
        // ⭐ BƯỚC QUAN TRỌNG: GỌI HÀM TÍNH GIÁ SALE
        $price_details = get_best_sale_price($mysqli, $variantID);
        $salePrice = $price_details['salePrice'];
        
        // Gắn các trường sale vào item giỏ hàng
        $row['variantPrice'] = $salePrice; // Ghi đè giá hiển thị bằng giá sale
        $row['originalPrice'] = $price_details['originalPrice'];
        $row['isDiscounted'] = $price_details['isDiscounted'];
        
        // Cập nhật tổng tiền
        $subtotal += $salePrice * $quantity;
        
        // Ép kiểu cuối cùng
        $row['variantPrice'] = (float) $row['variantPrice'];
        $row['stock'] = (int) $row['stock']; 
        
        // ✅ SỬA LỖI URL ẢNH: Tạo URL ảnh đầy đủ
        if (!empty($row['imageUrl'])) {
            // Dùng https và trỏ đến thư mục storage public của AdminPanel
            $base_url = 'https://' . $_SERVER['HTTP_HOST'] . '/admin/public/storage/';
            
            // $row['imageUrl'] từ DB đã có dạng: "products/ten_file.jpg"
            $row['imageUrl'] = $base_url . $row['imageUrl'];
        }
        
        $row['isSelected'] = true; 
        
        unset($row['priceBase']); // Loại bỏ cột gốc đã đổi tên
        $data[] = $row;
    }

    $stmt->close();
    
    // PHẢN HỒI THÀNH CÔNG: Bao gồm cả subtotal
    respond([
        "isSuccess" => true,
        "message" => "Cart items loaded successfully.",
        "subtotal" => round($subtotal, 2), // ⭐ THÊM SUB TOTAL
        "items" => $data 
    ]);

} catch (Throwable $e) {
    error_log("Cart Get API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra phía server: ' . $e->getMessage()], 500);
}