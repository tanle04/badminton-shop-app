<?php
// THÊM 3 DÒNG NÀY ĐỂ CHỐNG CACHE
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

header("Content-Type: application/json; charset=UTF-8");
require_once '../bootstrap.php'; // Giả định chứa $mysqli và hàm respond()

/*
 * API THEO DÕI ĐƠN HÀNG (TRACKING)
 * Lấy thông tin chi tiết của một đơn hàng, địa chỉ, sản phẩm
 * và xây dựng mảng timeline logic dựa trên trạng thái hiện tại.
 */

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }
    
    $orderID = (int)($_GET['orderID'] ?? 0);
    // Lấy customerID để kiểm tra chính chủ (theo logic của get_details.php)
    $customerID = (int)($_GET['customerID'] ?? 0);
    
    if ($orderID <= 0 || $customerID <= 0) {
        respond(['isSuccess' => false, 'message' => 'ID đơn hàng hoặc khách hàng không hợp lệ.'], 400);
    }
    
    // Định nghĩa base URL 1 lần
    $base_url = 'https://' . $_SERVER['HTTP_HOST'] . '/admin/public/storage/';

    // --- Query 1: Lấy thông tin chính của Đơn hàng, Địa chỉ, Vận chuyển ---
    // Phải kiểm tra customerID để đảm bảo chính chủ
    $sqlOrder = "
        SELECT 
            o.orderID, o.status, o.orderDate, o.paymentMethod, o.paymentStatus, o.total,
            ca.recipientName, ca.phone, ca.street, ca.city, ca.postalCode, ca.country,
            s.shippingMethod, s.shippingFee, s.trackingCode, s.shippedDate
        FROM orders o
        JOIN customer_addresses ca ON o.addressID = ca.addressID
        LEFT JOIN shipping s ON o.orderID = s.orderID
        WHERE o.orderID = ? AND o.customerID = ?
    ";
    
    $stmtOrder = $mysqli->prepare($sqlOrder);
    if (!$stmtOrder) {
        throw new Exception("Lỗi chuẩn bị SQL (Order): " . $mysqli->error);
    }
    $stmtOrder->bind_param("ii", $orderID, $customerID);
    $stmtOrder->execute();
    $resultOrder = $stmtOrder->get_result();
    $orderInfo = $resultOrder->fetch_assoc();
    $stmtOrder->close();

    if (!$orderInfo) {
        respond(['isSuccess' => false, 'message' => 'Không tìm thấy đơn hàng hoặc bạn không có quyền xem.'], 404);
    }

    // --- Query 2: Lấy danh sách sản phẩm trong đơn hàng ---
    // Logic query này giống hệt get_details.php, chỉ bỏ check status = 'Delivered'
    $sqlItems = "
        SELECT 
            od.quantity, 
            p.productName,
            od.price, /* Giá tại thời điểm mua */
            (SELECT pi.imageUrl FROM productimages pi WHERE pi.productID = p.productID ORDER BY (pi.imageType = 'main') DESC, pi.imageID ASC LIMIT 1) AS imageUrl,
            GROUP_CONCAT(CONCAT(pa.attributeName, ': ', pav.valueName) SEPARATOR ', ') AS variantDetails
        FROM orderdetails od
        JOIN product_variants pv ON od.variantID = pv.variantID
        JOIN products p ON pv.productID = p.productID
        LEFT JOIN variant_attribute_values vav ON pv.variantID = vav.variantID
        LEFT JOIN product_attribute_values pav ON vav.valueID = pav.valueID
        LEFT JOIN product_attributes pa ON pa.attributeID = pav.attributeID
        WHERE od.orderID = ?
        GROUP BY od.orderDetailID
    ";

    $stmtItems = $mysqli->prepare($sqlItems);
    if (!$stmtItems) {
        throw new Exception("Lỗi chuẩn bị SQL (Items): " . $mysqli->error);
    }
    $stmtItems->bind_param("i", $orderID);
    $stmtItems->execute();
    $resultItems = $stmtItems->get_result();
    
    $products = [];
    while ($row = $resultItems->fetch_assoc()) {
        // Xử lý URL ảnh giống hệt get_details.php
        if (!empty($row['imageUrl'])) {
            $row['imageUrl'] = $base_url . $row['imageUrl'];
        }
        $products[] = $row;
    }
    $stmtItems->close();
    
    // --- Xây dựng Logic Timeline (Nghiệp vụ) ---
    $status = $orderInfo['status'];
    $orderDate = $orderInfo['orderDate'];
    $shippedDate = $orderInfo['shippedDate']; // Có thể NULL

    $timelineSteps = [];

    // 1. Đã đặt hàng (Pending) - Luôn luôn hoàn thành
    $timelineSteps[] = [
        "status" => "Pending",
        "title" => "Đã đặt hàng",
        "timestamp" => $orderDate,
        "isCompleted" => true 
    ];

    // 2. Đang xử lý (Processing)
    // Hoàn thành nếu status là Processing, Shipped, hoặc Delivered
    $isProcessing = in_array($status, ['Processing', 'Shipped', 'Delivered']);
    $timelineSteps[] = [
        "status" => "Processing",
        "title" => "Đang xử lý",
        // DB không có mốc thời gian này, dùng tạm orderDate nếu đã xảy ra
        "timestamp" => $isProcessing ? $orderDate : null, 
        "isCompleted" => $isProcessing
    ];

    // 3. Đang giao hàng (Shipped)
    // Hoàn thành nếu status là Shipped hoặc Delivered
    $isShipped = in_array($status, ['Shipped', 'Delivered']);
    $timelineSteps[] = [
        "status" => "Shipped",
        "title" => "Đang giao hàng",
        "timestamp" => $shippedDate, // Dùng thời gian thật từ bảng shipping
        "isCompleted" => $isShipped
    ];

    // 4. Đã giao (Delivered)
    $isDelivered = $status === 'Delivered';
    $timelineSteps[] = [
        "status" => "Delivered",
        "title" => "Đã giao",
        // DB không có mốc thời gian này, dùng tạm shippedDate (nếu có)
        "timestamp" => $isDelivered ? ($shippedDate ?? $orderDate) : null,
        "isCompleted" => $isDelivered
    ];

    // Xử lý trường hợp đặc biệt: Bị hủy / Hoàn tiền
    if ($status === 'Cancelled' || $status === 'Refunded' || $status === 'Refund Requested') {
        $finalTitle = "Đã hủy";
        if ($status === 'Refunded') $finalTitle = "Đã hoàn tiền";
        if ($status === 'Refund Requested') $finalTitle = "Yêu cầu hoàn tiền";

        // Ghi đè timeline cho rõ ràng
        $timelineSteps = [
            [
                "status" => "Pending",
                "title" => "Đã đặt hàng",
                "timestamp" => $orderDate,
                "isCompleted" => true
            ],
            [
                "status" => $status,
                "title" => $finalTitle,
                "timestamp" => $orderDate, // Không có timestamp hủy, dùng tạm
                "isCompleted" => true
            ]
        ];
    }
    
    // --- Trả về Dữ liệu Hoàn chỉnh ---
    respond([
        "isSuccess" => true,
        "message" => "Tải thông tin tracking thành công.",
        "data" => [
            "orderInfo" => $orderInfo,
            "products" => $products,
            "timelineSteps" => $timelineSteps
        ]
    ]);

} catch (Throwable $e) {
    error_log("Order Track API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
}