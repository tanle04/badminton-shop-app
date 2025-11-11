<?php
// ⭐ SỬA LỖI: THÊM 3 DÒNG NÀY ĐỂ CHỐNG CACHE
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Code gốc của bạn
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../bootstrap.php'; 
require_once __DIR__ . '/../utils/price_calculator.php'; 

// Hàm helper (Giữ nguyên)
function safe_bind_param($stmt, $types, $params) {
    if (empty($types)) return true;
    $bind_names = [$types];
    foreach ($params as $key => $value) {
        $bind_names[] = &$params[$key];
    }
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
    
    $orders = [];
    $whereClauses = ["o.customerID = ?"];
    $params = [$customerID];
    $types = "i";
    
    if ($statusFilter !== 'All') {
        $whereClauses[] = "o.status = ?";
        $params[] = $statusFilter;
        $types .= "s";
    }

    // --- 1. Lấy thông tin đơn hàng chính (Order Headers) ---
    $sql_headers = "
        SELECT 
            o.orderID, o.orderDate, o.status, o.total, o.paymentMethod, o.voucherID,
            a.recipientName, a.phone, a.street, a.city,
            v.voucherCode, v.discountType, v.discountValue, v.maxDiscountAmount,
            s.shippingFee, s.shippingMethod
        FROM orders o
        JOIN customer_addresses a ON o.addressID = a.addressID
        LEFT JOIN vouchers v ON o.voucherID = v.voucherID
        LEFT JOIN shipping s ON o.orderID = s.orderID
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
        $orderID = $row['orderID'];
        $row['total'] = (double) $row['total']; 
        
        $actualShippingFee = (double) ($row['shippingFee'] ?? 0.00); 
        $discountAmount = 0.0;
        $voucherValue = (double) $row['discountValue'];
        $maxDiscount = (double) $row['maxDiscountAmount'];
        
        if ($row['voucherID']) {
             if ($row['discountType'] == 'fixed') {
                 $discountAmount = $voucherValue;
             } elseif ($row['discountType'] == 'percentage') {
                 $discountRate = $voucherValue / 100.0;
                 $estimatedSubtotalBeforeDiscount = $row['total'] - $actualShippingFee; 
                 $denominator = 1.0 - $discountRate;

                 if (abs($denominator) < 0.000001) { 
                     $calculatedSubtotal = $estimatedSubtotalBeforeDiscount; 
                     $discountAmount = $calculatedSubtotal; 
                 } else {
                     $calculatedSubtotal = $estimatedSubtotalBeforeDiscount / $denominator;
                     $discountAmount = $calculatedSubtotal * $discountRate;
                 }
                 
                 if ($maxDiscount > 0 && $discountAmount > $maxDiscount) {
                     $discountAmount = $maxDiscount;
                 }
             }
        } 
        
        $row['voucherDiscountAmount'] = round($discountAmount, 2);
        $row['shippingFee'] = round($actualShippingFee, 2); 
        $row['isFreeShip'] = ($actualShippingFee == 0.00); 
        $row['subtotal'] = round($row['total'] - $row['shippingFee'] + $row['voucherDiscountAmount'], 2);
        
        unset($row['discountValue']);
        unset($row['maxDiscountAmount']);

        $orders[$orderID] = $row;
        $orders[$orderID]['items'] = []; 
    }
    $stmt_headers->close();

    if (empty($orders)) {
        respond(['isSuccess' => true, 'message' => 'Không tìm thấy đơn hàng nào.', 'orders' => []]);
    }
    
    // --- 2. Lấy Chi tiết đơn hàng (Order Details) ---
    $orderIDs = array_keys($orders);
    $placeholders = implode(',', array_fill(0, count($orderIDs), '?'));
    $types_details = str_repeat('i', count(array_filter($orderIDs))); 
    
    $sql_details = "
        SELECT 
            od.orderID, od.orderDetailID, od.quantity, od.price,
            od.isReviewed, 
            od.variantID, 
            p.productName, p.productID, pv.price AS originalPriceBase, 
            GROUP_CONCAT(CONCAT(pa.attributeName, ': ', pav.valueName) ORDER BY pa.attributeID SEPARATOR ', ') AS variantDetails, 
            (SELECT pi.imageUrl FROM productimages pi WHERE pi.productID = p.productID ORDER BY (pi.imageType = 'main') DESC, pi.imageID ASC LIMIT 1) AS imageUrl
        FROM orderdetails od
        JOIN product_variants pv ON od.variantID = pv.variantID
        JOIN products p ON pv.productID = p.productID
        LEFT JOIN variant_attribute_values vav ON vav.variantID = pv.variantID
        LEFT JOIN product_attribute_values pav ON pav.valueID = vav.valueID
        LEFT JOIN product_attributes pa ON pa.attributeID = pav.attributeID
        WHERE od.orderID IN ({$placeholders})
        GROUP BY od.orderDetailID
        ORDER BY od.orderDetailID ASC
    ";

    $stmt_details = $mysqli->prepare($sql_details);
    if (!$stmt_details) throw new Exception("Prepare failed for details: " . $mysqli->error);
    
    if (!safe_bind_param($stmt_details, $types_details, $orderIDs)) {
        throw new Exception("Bind param failed for details: " . $stmt_details->error);
    }
    
    $stmt_details->execute();
    $result_details = $stmt_details->get_result();
    
    // ⭐ SỬA LỖI URL ẢNH: Định nghĩa base URL 1 lần
    $base_url = 'https://' . $_SERVER['HTTP_HOST'] . '/admin/public/storage/';
            
    while ($row = $result_details->fetch_assoc()) {
        $priceBought = (double) $row['price'];
        $originalPriceBase = (double) $row['originalPriceBase']; 
        $isItemDiscounted = $priceBought < $originalPriceBase;

        $row['price'] = round($priceBought, 2); 
        $row['originalPrice'] = round($originalPriceBase, 2); 
        $row['isDiscounted'] = $isItemDiscounted; 
        $row['quantity'] = (int) $row['quantity']; 
        $row['variantID'] = (int) $row['variantID']; 
        $row['productID'] = (int) $row['productID']; 
        $row['isReviewed'] = (bool) $row['isReviewed']; 
        
        // ⭐ SỬA LỖI URL ẢNH: Tạo URL ảnh đầy đủ
        if (!empty($row['imageUrl'])) {
            $row['imageUrl'] = $base_url . $row['imageUrl'];
        }

        $orders[$row['orderID']]['items'][] = $row;
    }
    $stmt_details->close();

    respond(['isSuccess' => true, 'message' => 'Orders loaded successfully.', 'orders' => array_values($orders)]);
    
} catch (Throwable $e) {
    error_log("Customer Orders API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra phía server: Vui lòng thử lại sau.'], 500);
}