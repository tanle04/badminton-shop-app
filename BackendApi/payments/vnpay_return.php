<?php
// TẮT DEBUG: Chuyển sang môi trường hoạt động bình thường
error_reporting(0);
ini_set('display_errors', 0);

// Thiết lập múi giờ
date_default_timezone_set('Asia/Ho_Chi_Minh'); 

// Tải các file cần thiết
require_once __DIR__ . '/../bootstrap.php'; 
require_once __DIR__ . '/../utils/email_helper.php';

// HẰNG SỐ CẤU HÌNH (Đảm bảo HASH SECRET chính xác)
const VNPAY_HASH_SECRET = "L84RURSU748VB8FULHKJP12ADCBEZLSJ"; 

// 1. KHỞI TẠO VÀ LẤY THAM SỐ
$vnp_Params = $_GET;
$vnp_SecureHash = $vnp_Params['vnp_SecureHash'] ?? '';
$vnp_TxnRef = $vnp_Params['vnp_TxnRef'] ?? '';
$vnp_ResponseCode = $vnp_Params['vnp_ResponseCode'] ?? '';
$amount = (float)($vnp_Params['vnp_Amount'] ?? 0) / 100;

// Tách OrderID từ chuỗi TxnRef (ví dụ: ORDER_ID_TIMESTAMP)
$parts = explode('_', $vnp_TxnRef);
$orderID = (int)($parts[1] ?? 0); 

// 2. TÁI TẠO CHỮ KÝ ĐỂ XÁC MINH
$inputData = array();
foreach ($vnp_Params as $key => $value) {
    if (substr($key, 0, 4) == "vnp_") {
        $inputData[$key] = $value;
    }
}
unset($inputData['vnp_SecureHash']); 
ksort($inputData);
$hashData = http_build_query($inputData, '', '&');
$secureHash = hash_hmac('sha512', $hashData, VNPAY_HASH_SECRET);

$returnUrl = "badmintonshop://yourorders?status=failed_cancelled&orderID={$orderID}"; // Mặc định là Thất bại/Hủy
$isUpdateSuccessful = false; 

// 3. XỬ LÝ LOGIC CHÍNH
if ($orderID > 0) {
    $mysqli->begin_transaction();
    
    // Lấy thông tin đơn hàng, chi tiết sản phẩm và khách hàng (JOIN)
    $stmt_check = $mysqli->prepare("
        SELECT 
            o.paymentStatus, o.status, o.total, c.email, c.fullName, a.street, a.city, od.variantID, od.quantity
        FROM orders o 
        JOIN customers c ON o.customerID = c.customerID 
        JOIN customer_addresses a ON o.addressID = a.addressID
        JOIN orderdetails od ON o.orderID = od.orderID
        WHERE o.orderID = ? AND o.status = 'Pending' FOR UPDATE
    ");
    $stmt_check->bind_param("i", $orderID);
    $stmt_check->execute();
    $result = $stmt_check->get_result();
    
    $order_data = [];
    $variants_to_restore = [];
    $order_processed = false;

    while ($row = $result->fetch_assoc()) {
        if (empty($order_data)) {
            $order_data = $row;
            $order_data['items'] = [];
        }
        $variants_to_restore[] = ['variantID' => $row['variantID'], 'quantity' => $row['quantity']];
        $order_data['items'][] = ['variantID' => $row['variantID'], 'quantity' => $row['quantity']];
    }
    $stmt_check->close();
    
    // Kiểm tra tính hợp lệ của đơn hàng (chỉ xử lý nếu đơn hàng là Pending)
    if (!empty($order_data) && $order_data['status'] === 'Pending') {
        
        if ($secureHash == $vnp_SecureHash && $vnp_ResponseCode == '00') {
            // ⭐ TRƯỜNG HỢP THÀNH CÔNG: CẬP NHẬT DB VÀ GỬI EMAIL
            $newPaymentStatus = 'Paid';
            $newOrderStatus = 'Processing'; 
            $returnUrl = "badmintonshop://yourorders?status=success&orderID={$orderID}";
            
            // 1. Cập nhật trạng thái
            $stmt_update = $mysqli->prepare("UPDATE orders SET paymentStatus = ?, status = ? WHERE orderID = ?");
            $stmt_update->bind_param("ssi", $newPaymentStatus, $newOrderStatus, $orderID);
            $stmt_update->execute();
            $isUpdateSuccessful = ($stmt_update->affected_rows > 0);
            $stmt_update->close();
            
            // 2. Gửi Email (Cần logic chi tiết sản phẩm)
            if ($isUpdateSuccessful) {
                // Lấy chi tiết sản phẩm (Đã JOIN trong query trên)
                // (Giữ lại logic email bạn đã sửa trước đó)
                // ...
            }
            
        } else {
            // ⭐ TRƯỜNG HỢP THẤT BẠI HOẶC HASH SAI: HỦY ĐƠN HÀNG VÀ PHỤC HỒI TỒN KHO
            $newOrderStatus = 'Cancelled'; 
            $newPaymentStatus = 'Unpaid';
            $returnUrl = "badmintonshop://yourorders?status=failed_cancelled&orderID={$orderID}";
            
            // 1. Hủy đơn hàng
            $stmt_update = $mysqli->prepare("UPDATE orders SET paymentStatus = ?, status = ? WHERE orderID = ?");
            $stmt_update->bind_param("ssi", $newPaymentStatus, $newOrderStatus, $orderID);
            $stmt_update->execute();
            $stmt_update->close();

            // 2. Phục hồi reserved stock
            $stmt_restore = $mysqli->prepare("UPDATE product_variants SET stock = stock + ?, reservedStock = reservedStock - ? WHERE variantID = ?");
            foreach ($variants_to_restore as $item) {
                $stmt_restore->bind_param("iii", $item['quantity'], $item['quantity'], $item['variantID']);
                $stmt_restore->execute();
            }
            $stmt_restore->close();
            
            // Gửi email thông báo hủy (Tùy chọn)
        }
        $mysqli->commit();
    } else {
        // Đã được xử lý hoặc không tồn tại (IPN Replay hoặc lỗi)
        $returnUrl = "badmintonshop://yourorders?status=already_processed&orderID={$orderID}";
    }
    
} else {
    // OrderID không hợp lệ
    $returnUrl = "badmintonshop://yourorders?status=invalid_id";
}


// 4. CHUYỂN HƯỚNG CUỐI CÙNG VỀ ỨNG DỤNG ANDROID
header("Location: {$returnUrl}");
exit;