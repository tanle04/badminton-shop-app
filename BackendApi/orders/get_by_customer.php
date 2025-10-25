<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
require_once '../bootstrap.php'; 

// Hàm helper để bind tham số động an toàn (Giữ nguyên)
function safe_bind_param($stmt, $types, $params) {
    if (empty($types)) return true;
    $bind_names = [$types];
    
    foreach ($params as $key => $value) {
        $bind_names[] = &$params[$key];
    }
    return call_user_func_array([$stmt, 'bind_param'], $bind_names);
}

// ⭐ Hằng số phí ship (Cần thiết để tính Subtotal ngược)
const SHIPPING_FEE = 22200.00;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }

    $customerID = (int)($_GET['customerID'] ?? 0);
    $statusFilter = trim($_GET['status'] ?? 'All'); 

    if ($customerID <= 0) {
        respond(['isSuccess' => false, 'message' => 'ID khách hàng không hợp lệ.'], 400);
    }
    
    $orders = [];
    $whereClauses = ["o.customerID = ?"];
    $params = [$customerID];
    $types = "i";
    
    if ($statusFilter !== 'All') {
        $whereClauses[] = "o.status = ?";
        $params[] = $statusFilter;
        $types .= "s";
    }

    // --- 1. Lấy thông tin đơn hàng chính (Order Headers + ADDRESS + VOUCHER) ---
    $sql_headers = "
        SELECT 
            o.orderID, o.orderDate, o.status, o.total, o.paymentMethod, o.voucherID,
            a.recipientName, a.phone, a.street, a.city,
            v.voucherCode, v.discountType, v.discountValue 
        FROM orders o
        JOIN customer_addresses a ON o.addressID = a.addressID
        LEFT JOIN vouchers v ON o.voucherID = v.voucherID
        WHERE " . implode(' AND ', $whereClauses) . "
        ORDER BY orderDate DESC
    ";
    
    $stmt_headers = $mysqli->prepare($sql_headers);
    if (!$stmt_headers) throw new Exception("Prepare failed for headers: " . $mysqli->error);
    
    if (!safe_bind_param($stmt_headers, $types, $params)) {
        throw new Exception("Bind param failed for headers: " . $stmt_headers->error);
    }
    
    $stmt_headers->execute();
    $result_headers = $stmt_headers->get_result();
    
    while ($row = $result_headers->fetch_assoc()) {
        $row['total'] = (double) $row['total']; 
        
        // ⭐ LOGIC TÍNH DISCOUNT AMOUNT THỰC TẾ ⭐
        $discountAmount = 0.0;
        $voucherValue = (double) $row['discountValue'];
        
        if ($row['voucherID']) {
            // Sử dụng giá trị voucher như giá trị giảm thực tế (đã tính tại checkout)
            $discountAmount = $voucherValue; 
        }

        $row['discountAmount'] = $discountAmount;
        
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
    
    // Khối SQL Details (Đã dọn dẹp ký tự ẩn)
    $sql_details = "
        SELECT 
            od.orderID, od.orderDetailID, od.quantity, od.price, 
            od.isReviewed, 
            od.variantID, 
            p.productName, p.productID, pv.price AS originalPrice,
            GROUP_CONCAT(CONCAT(pa.attributeName, ': ', pav.valueName) ORDER BY pa.attributeID SEPARATOR ', ') AS variantDetails, 
            (SELECT pi.imageUrl FROM productimages pi WHERE pi.productID = p.productID ORDER BY pi.imageID ASC LIMIT 1) AS imageUrl
        FROM orderdetails od
        JOIN product_variants pv ON od.variantID = pv.variantID
        JOIN products p ON pv.productID = p.productID
        LEFT JOIN variant_attribute_values vav ON vav.variantID = pv.variantID
        LEFT JOIN product_attribute_values pav ON pav.valueID = vav.valueID
        LEFT JOIN product_attributes pa ON pa.attributeID = pav.attributeID
        WHERE od.orderID IN ({$placeholders})
        GROUP BY od.orderDetailID, od.orderID, od.quantity, od.price, od.isReviewed, od.variantID, p.productName, p.productID, pv.price
        ORDER BY od.orderDetailID ASC
    ";

    $stmt_details = $mysqli->prepare($sql_details);
    if (!$stmt_details) throw new Exception("Prepare failed for details: " . $mysqli->error);
    
    if (!safe_bind_param($stmt_details, $types_details, $orderIDs)) {
        throw new Exception("Bind param failed for details: " . $stmt_details->error);
    }
    
    $stmt_details->execute();
    $result_details = $stmt_details->get_result();
    
    while ($row = $result_details->fetch_assoc()) {
        // Ép kiểu cho Android DTO
        $row['price'] = (double) $row['price'];
        $row['originalPrice'] = (double) $row['originalPrice']; 
        $row['quantity'] = (int) $row['quantity']; 
        $row['variantID'] = (int) $row['variantID']; 
        $row['productID'] = (int) $row['productID']; 
        $row['isReviewed'] = (bool) $row['isReviewed']; 
        
        $orders[$row['orderID']]['items'][] = $row;
    }
    $stmt_details->close();

    // Trả về kết quả
    respond(['isSuccess' => true, 'message' => 'Orders loaded successfully.', 'orders' => array_values($orders)]);
    
} catch (Throwable $e) {
    error_log("Customer Orders API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra phía server: ' . $e->getMessage()], 500);
}

// LƯU Ý: KHÔNG CÓ THẺ ĐÓNG PHP Ở CUỐI FILE.