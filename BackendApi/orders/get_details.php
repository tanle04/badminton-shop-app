<?php
// ⭐ SỬA LỖI: THÊM 3 DÒNG NÀY ĐỂ CHỐNG CACHE
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Code gốc của bạn
header("Content-Type: application/json; charset=UTF-8");
require_once '../bootstrap.php'; 

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }
    
    $orderID = (int)($_GET['orderID'] ?? 0);
    // ⭐ BỔ SUNG: Lấy customerID để kiểm tra chính chủ
    $customerID = (int)($_GET['customerID'] ?? 0);
    
    if ($orderID <= 0 || $customerID <= 0) {
        respond(['isSuccess' => false, 'message' => 'ID đơn hàng hoặc khách hàng không hợp lệ.'], 400);
    }

    // ⭐ SỬA SQL: Lấy giá gốc hiện tại của Variant (pv.price)
    // ⭐ SỬA SQL: Thêm check o.customerID
    $sql = "
        SELECT 
            od.orderDetailID, od.quantity, od.price, /* od.price là giá mua */
            od.isReviewed,
            p.productID, p.productName,
            pv.variantID, 
            pv.price AS originalPriceCurrent, /* ⭐ LẤY GIÁ GỐC HIỆN TẠI */
            GROUP_CONCAT(CONCAT(pa.attributeName, ': ', pav.valueName) SEPARATOR ', ') AS variantDetails,
            (SELECT pi.imageUrl FROM productimages pi WHERE pi.productID = p.productID ORDER BY (pi.imageType = 'main') DESC, pi.imageID ASC LIMIT 1) AS imageUrl
        FROM orderdetails od
        JOIN product_variants pv ON od.variantID = pv.variantID
        JOIN products p ON pv.productID = p.productID
        JOIN orders o ON o.orderID = od.orderID /* ⭐ SỬA: JOIN orders */
        LEFT JOIN variant_attribute_values vav ON pv.variantID = vav.variantID
        LEFT JOIN product_attribute_values pav ON vav.valueID = pav.valueID
        LEFT JOIN product_attributes pa ON pa.attributeID = pav.attributeID
        WHERE od.orderID = ? AND o.customerID = ? AND o.status = 'Delivered' 
        GROUP BY od.orderDetailID
        ORDER BY od.orderDetailID ASC
    ";
    
    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        respond(['isSuccess' => false, 'message' => 'Lỗi chuẩn bị SQL: ' . $mysqli->error], 500);
    }
    
    // ⭐ SỬA: bind 2 tham số
    $stmt->bind_param("ii", $orderID, $customerID);
    $stmt->execute();
    $result = $stmt->get_result();

    $orderDetails = [];
    
    // ⭐ SỬA LỖI URL ẢNH: Định nghĩa base URL 1 lần
    $base_url = 'https://' . $_SERVER['HTTP_HOST'] . '/admin/public/storage/';
    
    while ($row = $result->fetch_assoc()) {
        $priceBought = (double) $row['price'];
        $originalPriceCurrent = (double) $row['originalPriceCurrent'];

        $isDiscountedAtPurchase = $priceBought < $originalPriceCurrent;
        
        $row['price'] = $priceBought;
        $row['originalPrice'] = $originalPriceCurrent; 
        $row['isDiscounted'] = $isDiscountedAtPurchase; 
        $row['isReviewed'] = (bool) $row['isReviewed'];
        $row['quantity'] = (int) $row['quantity'];
        
        // ⭐ SỬA LỖI URL ẢNH: Tạo URL ảnh đầy đủ
        if (!empty($row['imageUrl'])) {
            $row['imageUrl'] = $base_url . $row['imageUrl'];
        }
        
        unset($row['originalPriceCurrent']); 
        
        $orderDetails[] = $row;
    }
    $stmt->close();

    if (empty($orderDetails)) {
         respond(['isSuccess' => false, 'message' => 'Không tìm thấy chi tiết đơn hàng, đơn hàng chưa được giao, hoặc bạn không phải chủ đơn hàng.'], 404);
    }
    
    respond([
        "isSuccess" => true,
        "message" => "Order details loaded successfully.",
        "orderDetails" => $orderDetails 
    ]);

} catch (Throwable $e) {
    error_log("Order Details API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra phía server: ' . $e->getMessage()], 500);
}