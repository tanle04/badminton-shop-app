<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../bootstrap.php'; // Giả định chứa hàm respond()

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }
    
    $orderID = (int)($_GET['orderID'] ?? 0);
    
    if ($orderID <= 0) {
        respond(['isSuccess' => false, 'message' => 'ID đơn hàng không hợp lệ.'], 400);
    }

    // ⭐ SỬA SQL: Lấy giá gốc hiện tại của Variant (pv.price)
    $sql = "
        SELECT 
            od.orderDetailID, od.quantity, od.price, /* od.price là giá mua */
            od.isReviewed,
            p.productID, p.productName,
            pv.variantID, 
            pv.price AS originalPriceCurrent, /* ⭐ LẤY GIÁ GỐC HIỆN TẠI TỪ BẢNG variants */
            GROUP_CONCAT(CONCAT(pa.attributeName, ': ', pav.valueName) SEPARATOR ', ') AS variantDetails,
            (SELECT pi.imageUrl FROM productimages pi WHERE pi.productID = p.productID ORDER BY pi.imageID ASC LIMIT 1) AS imageUrl
        FROM orderdetails od
        JOIN product_variants pv ON od.variantID = pv.variantID
        JOIN products p ON pv.productID = p.productID
        LEFT JOIN orders o ON o.orderID = od.orderID
        LEFT JOIN variant_attribute_values vav ON pv.variantID = vav.variantID
        LEFT JOIN product_attribute_values pav ON vav.valueID = pav.valueID
        LEFT JOIN product_attributes pa ON pa.attributeID = pav.attributeID
        WHERE od.orderID = ? AND o.status = 'Delivered' 
        GROUP BY od.orderDetailID
        ORDER BY od.orderDetailID ASC
    ";
    
    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        respond(['isSuccess' => false, 'message' => 'Lỗi chuẩn bị SQL: ' . $mysqli->error], 500);
    }
    
    $stmt->bind_param("i", $orderID);
    $stmt->execute();
    $result = $stmt->get_result();

    $orderDetails = [];
    while ($row = $result->fetch_assoc()) {
        $priceBought = (double) $row['price'];
        $originalPriceCurrent = (double) $row['originalPriceCurrent'];

        // ⭐ LOGIC SALE: Nếu giá mua thấp hơn giá gốc hiện tại, ta giả định đây là giao dịch sale.
        $isDiscountedAtPurchase = $priceBought < $originalPriceCurrent;
        
        // Ép kiểu dữ liệu (cho DTO OrderDetailDto)
        $row['price'] = $priceBought;
        $row['originalPrice'] = $originalPriceCurrent; // Gắn giá gốc
        $row['isDiscounted'] = $isDiscountedAtPurchase; // Gắn cờ sale
        $row['isReviewed'] = (bool) $row['isReviewed'];
        $row['quantity'] = (int) $row['quantity'];
        
        unset($row['originalPriceCurrent']); // Xóa cột tạm
        
        $orderDetails[] = $row;
    }
    $stmt->close();

    if (empty($orderDetails)) {
          respond(['isSuccess' => false, 'message' => 'Không tìm thấy chi tiết đơn hàng hoặc đơn hàng chưa được giao.'], 404);
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