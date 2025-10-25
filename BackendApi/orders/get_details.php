<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../bootstrap.php'; // Giả định chứa hàm respond()

// DTO Response cho API này: OrderDetailsListResponse { isSuccess, message, orderDetails: [] }

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }
    
    $orderID = (int)($_GET['orderID'] ?? 0);
    
    if ($orderID <= 0) {
        respond(['isSuccess' => false, 'message' => 'ID đơn hàng không hợp lệ.'], 400);
    }

    // ⭐ CÂU LỆNH SQL: Lấy chi tiết sản phẩm, tên, ảnh, và kiểm tra trạng thái đơn hàng
    $sql = "
        SELECT 
            od.orderDetailID, od.quantity, od.price,
            p.productID, p.productName,
            pv.variantID, 
            GROUP_CONCAT(CONCAT(pa.attributeName, ': ', pav.valueName) SEPARATOR ', ') AS variantDetails,
            (SELECT pi.imageUrl FROM productimages pi WHERE pi.productID = p.productID ORDER BY pi.imageID ASC LIMIT 1) AS imageUrl,
            (SELECT 1 FROM reviews r WHERE r.orderDetailID = od.orderDetailID) AS isReviewed 
        FROM orderdetails od
        JOIN product_variants pv ON od.variantID = pv.variantID
        JOIN products p ON pv.productID = p.productID
        LEFT JOIN orders o ON o.orderID = od.orderID
        LEFT JOIN variant_attribute_values vav ON pv.variantID = vav.variantID
        LEFT JOIN product_attribute_values pav ON vav.valueID = pav.valueID
        LEFT JOIN product_attributes pa ON pav.attributeID = pa.attributeID
        WHERE od.orderID = ? AND o.status = 'Delivered' 
        GROUP BY od.orderDetailID, od.quantity, od.price, p.productID, p.productName, pv.variantID, imageUrl
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
        // Ép kiểu dữ liệu (cho DTO OrderDetailDto)
        $row['price'] = (string) $row['price'];
        $row['isReviewed'] = (bool) $row['isReviewed'];
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