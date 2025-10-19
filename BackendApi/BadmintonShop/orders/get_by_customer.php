<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../bootstrap.php';

// Hàm helper để bind tham số động an toàn (bắt buộc)
function safe_bind_param($stmt, $types, $params) {
    if (empty($types)) return true; // Không có tham số để bind

    $bind_names = [$types];
    $refs = [];
    
    // Tạo mảng tham chiếu cho các giá trị tham số
    foreach ($params as $key => $value) {
        $bind_names[] = &$params[$key];
    }

    // Bind các tham số
    return call_user_func_array([$stmt, 'bind_param'], $bind_names);
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }

    $customerID = (int)($_GET['customerID'] ?? 0);
    $statusFilter = trim($_GET['status'] ?? 'All'); 

    if ($customerID <= 0) {
        respond(['isSuccess' => false, 'message' => 'ID khách hàng không hợp lệ.'], 400);
    }
    
    // Khởi tạo các mảng cho dữ liệu
    $orders = [];
    $whereClauses = ["o.customerID = ?"];
    $params = [$customerID];
    $types = "i";
    
    // Thêm điều kiện lọc theo trạng thái
    if ($statusFilter !== 'All') {
        $whereClauses[] = "o.status = ?";
        $params[] = $statusFilter;
        $types .= "s";
    }

    // --- 1. Lấy thông tin đơn hàng chính (Order Headers) ---
    $sql_headers = "
        SELECT orderID, orderDate, status, total, paymentMethod
        FROM orders o
        WHERE " . implode(' AND ', $whereClauses) . "
        ORDER BY orderDate DESC
    ";
    
    $stmt_headers = $mysqli->prepare($sql_headers);
    if (!$stmt_headers) throw new Exception("Prepare failed for headers: " . $mysqli->error);
    
    // ⭐ BIND ĐỘNG an toàn (dùng hàm helper)
    if (!safe_bind_param($stmt_headers, $types, $params)) {
        throw new Exception("Bind param failed for headers: " . $stmt_headers->error);
    }
    
    $stmt_headers->execute();
    $result_headers = $stmt_headers->get_result();
    
    while ($row = $result_headers->fetch_assoc()) {
        // ⭐ Ép kiểu total sang chuỗi
        $row['total'] = (string) $row['total']; 
        $orders[$row['orderID']] = $row;
        $orders[$row['orderID']]['items'] = []; 
    }
    $stmt_headers->close();

    if (empty($orders)) {
        respond(['isSuccess' => true, 'message' => 'Không tìm thấy đơn hàng nào.', 'orders' => []]);
    }
    
    // --- 2. Lấy Chi tiết đơn hàng (Order Details) ---
    $orderIDs = array_keys($orders);
    $placeholders = implode(',', array_fill(0, count($orderIDs), '?'));
    $types_details = str_repeat('i', count($orderIDs));
    
    $sql_details = "
        SELECT 
            od.orderID, od.orderDetailID, od.quantity, od.price, 
            p.productName, p.productID,
            (SELECT pi.imageUrl FROM productimages pi WHERE pi.productID = p.productID ORDER BY pi.imageID ASC LIMIT 1) AS imageUrl
        FROM orderdetails od
        JOIN product_variants pv ON od.variantID = pv.variantID
        JOIN products p ON pv.productID = p.productID
        WHERE od.orderID IN ({$placeholders})
    ";

    $stmt_details = $mysqli->prepare($sql_details);
    if (!$stmt_details) throw new Exception("Prepare failed for details: " . $mysqli->error);
    
    // ⭐ BIND ĐỘNG an toàn (dùng hàm helper)
    if (!safe_bind_param($stmt_details, $types_details, $orderIDs)) {
        throw new Exception("Bind param failed for details: " . $stmt_details->error);
    }
    
    $stmt_details->execute();
    $result_details = $stmt_details->get_result();
    
    while ($row = $result_details->fetch_assoc()) {
        // ⭐ Ép kiểu price và quantity sang kiểu Android mong đợi
        $row['price'] = (string) $row['price'];
        $row['quantity'] = (int) $row['quantity']; 

        // Gắn chi tiết vào đơn hàng cha
        $orders[$row['orderID']]['items'][] = $row;
    }
    $stmt_details->close();

    // Trả về kết quả
    respond(['isSuccess' => true, 'message' => 'Orders loaded successfully.', 'orders' => array_values($orders)]);
    
} catch (Throwable $e) {
    error_log("Customer Orders API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra phía server: ' . $e->getMessage()], 500);
}