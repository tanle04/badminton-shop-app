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

// Hằng số phí ship (Giữ nguyên)
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

    // --- 1. Lấy thông tin đơn hàng chính (Order Headers + ADDRESS + VOUCHER + SHIPPING) ---
    $sql_headers = "
        SELECT 
            o.orderID, o.orderDate, o.status, o.total, o.paymentMethod, o.voucherID,
            a.recipientName, a.phone, a.street, a.city,
            v.voucherCode, v.discountType, v.discountValue, v.maxDiscountAmount,
            s.shippingFee, s.shippingMethod
        FROM orders o
        JOIN customer_addresses a ON o.addressID = a.addressID
        LEFT JOIN vouchers v ON o.voucherID = v.voucherID
        LEFT JOIN shipping s ON o.orderID = s.orderID /* JOIN BẢNG SHIPPING */
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
        
        // ⭐ SỬA LỖI FREE SHIP: Đặt phí ship mặc định là 0.00 nếu NULL, dựa trên dữ liệu DB.
        $actualShippingFee = (double) ($row['shippingFee'] ?? 0.00); 

        // LOGIC TÍNH DISCOUNT AMOUNT THỰC TẾ
        $discountAmount = 0.0;
        $voucherValue = (double) $row['discountValue'];
        $maxDiscount = (double) $row['maxDiscountAmount'];
        
        if ($row['voucherID']) {
             if ($row['discountType'] == 'fixed') {
                 $discountAmount = $voucherValue;
             } elseif ($row['discountType'] == 'percentage') {
                 $discountRate = $voucherValue / 100.0;
                 
                 $estimatedSubtotalBeforeDiscount = $row['total'] - $actualShippingFee; 
                 
                 // KIỂM TRA CHIA CHO 0
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
        
        // --- CHUẨN HÓA KẾT QUẢ CUỐI CÙNG ---
        $row['voucherDiscountAmount'] = round($discountAmount, 2);
        
        // Gán phí ship thực tế đã lấy từ DB (có thể là 0.00)
        $row['shippingFee'] = round($actualShippingFee, 2); 
        
        // ⭐ BỔ SUNG CỜ FREESHIP ⭐
        // Cờ này buộc client phải nhận ra đây là Free Ship.
        $row['isFreeShip'] = ($actualShippingFee == 0.00); 
        
        // Tính Subtotal chính xác (Total - Shipping + Voucher Discount)
        $row['subtotal'] = round($row['total'] - $row['shippingFee'] + $row['voucherDiscountAmount'], 2);
        
        // Loại bỏ các trường voucher thô không cần thiết cho Android
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
    $types_details = str_repeat('i', count(array_filter($orderIDs))); // Dùng array_filter nếu có khả năng mảng rỗng
    
    $sql_details = "
        SELECT 
            od.orderID, od.orderDetailID, od.quantity, od.price,
            od.isReviewed, 
            od.variantID, 
            p.productName, p.productID, pv.price AS originalPriceBase, 
            GROUP_CONCAT(CONCAT(pa.attributeName, ': ', pav.valueName) ORDER BY pa.attributeID SEPARATOR ', ') AS variantDetails, 
            (SELECT pi.imageUrl FROM productimages pi WHERE pi.productID = p.productID ORDER BY pi.imageID ASC LIMIT 1) AS imageUrl
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
    
    while ($row = $result_details->fetch_assoc()) {
        $priceBought = (double) $row['price'];
        $originalPriceBase = (double) $row['originalPriceBase']; 
        
        $isItemDiscounted = $priceBought < $originalPriceBase;

        // Ép kiểu cho Android DTO
        $row['price'] = round($priceBought, 2); 
        $row['originalPrice'] = round($originalPriceBase, 2); 
        
        $row['isDiscounted'] = $isItemDiscounted; 
        $row['quantity'] = (int) $row['quantity']; 
        $row['variantID'] = (int) $row['variantID']; 
        $row['productID'] = (int) $row['productID']; 
        $row['isReviewed'] = (bool) $row['isReviewed']; 
        
        // Xử lý Image URL
        $row['imageUrl'] = $row['imageUrl'] ?? "no_image.png";

        $orders[$row['orderID']]['items'][] = $row;
    }
    $stmt_details->close();

    // Trả về kết quả
    respond(['isSuccess' => true, 'message' => 'Orders loaded successfully.', 'orders' => array_values($orders)]);
    
} catch (Throwable $e) {
    error_log("Customer Orders API Error: " . $e->getMessage());
    // Trả về thông báo lỗi chung chung, ghi chi tiết vào log
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra phía server: Vui lòng thử lại sau.'], 500);
}