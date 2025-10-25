<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
error_reporting(E_ALL);

// Thiết lập múi giờ
date_default_timezone_set('Asia/Ho_Chi_Minh'); 

// Tải các file cần thiết
require_once __DIR__ . '/../bootstrap.php'; // Chứa hàm respond() và $mysqli
require_once __DIR__ . '/../utils/vnpay_helper.php'; // Chứa hàm generateVnPayUrl()

// Nhận dữ liệu POST
$orderID = (int)($_POST['orderID'] ?? 0);
$customerID = (int)($_POST['customerID'] ?? 0);
$newMethod = $_POST['newMethod'] ?? ''; // 'COD' hoặc 'VNPay'

// --- Validation cơ bản ---
if ($orderID <= 0 || $customerID <= 0 || !in_array($newMethod, ['COD', 'VNPay'])) {
    respond(['isSuccess' => false, 'message' => 'Dữ liệu đầu vào không hợp lệ.'], 400);
}

$mysqli->begin_transaction();

try {
    // 1. Lấy thông tin đơn hàng, khóa nó và kiểm tra trạng thái
    // ⭐ SỬA: Thêm paymentToken vào SELECT
    $stmt = $mysqli->prepare("
        SELECT 
            o.total, o.paymentExpiry, o.paymentStatus, o.status, o.paymentToken, c.email
        FROM orders o 
        JOIN customers c ON o.customerID = c.customerID 
        WHERE o.orderID = ? AND o.customerID = ? FOR UPDATE
    ");
    $stmt->bind_param("ii", $orderID, $customerID);
    $stmt->execute();
    $order_info = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$order_info) {
        throw new Exception("Đơn hàng không tồn tại.");
    }
    
    $total = $order_info['total'];
    $customerEmail = $order_info['email'];
    $currentStatus = $order_info['status'];
    $currentPaymentStatus = $order_info['paymentStatus'];
    $currentExpiry = $order_info['paymentExpiry'];
    $now = new DateTime('now');

    // Kiểm tra Đơn hàng có ở trạng thái cho phép thanh toán lại không
    if ($currentStatus !== 'Pending' || $currentPaymentStatus === 'Paid' || $currentPaymentStatus === 'Refunded') {
        throw new Exception("Đơn hàng không ở trạng thái chờ thanh toán (Pending). Trạng thái hiện tại: {$currentStatus}/{$currentPaymentStatus}.");
    }

    // Kiểm tra thời gian hết hạn (Nếu đã hết hạn, cần hủy đơn hàng)
    // Nếu đơn hàng đang pending, nhưng hết hạn, ta vẫn cho phép thanh toán lại (sẽ bỏ qua bước hủy của Cron Job).
    // Tuy nhiên, việc kiểm tra này thường chỉ mang tính thông báo cho người dùng.
    // Logic cho phép thanh toán lại sẽ làm mới thời gian hết hạn.
    // if ($currentExpiry && $now > new DateTime($currentExpiry)) {
    //     throw new Exception("Đơn hàng đã hết thời gian chờ thanh toán.");
    // }

    // --- 2. Xử lý logic theo Phương thức mới ---
    
    if ($newMethod === 'COD') {
        // ĐỔI SANG COD: Hủy thời gian hết hạn và cập nhật trạng thái
        
        $stmt_update = $mysqli->prepare("
            UPDATE orders 
            SET paymentMethod = 'COD', paymentStatus = 'Unpaid', status = 'Processing', 
                paymentExpiry = NULL, paymentToken = NULL 
            WHERE orderID = ?
        ");
        $stmt_update->bind_param("i", $orderID);
        $stmt_update->execute();
        $stmt_update->close();
        
        $mysqli->commit();
        respond(['isSuccess' => true, 'message' => 'Đã đổi sang thanh toán khi nhận hàng (COD). Đơn hàng đang được xử lý.']);
        
    } elseif ($newMethod === 'VNPay') {
        // THANH TOÁN LẠI VNPay: Gia hạn và tạo link mới
        
        $newExpiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $txnRef = "ORDER_" . $orderID . "_" . time(); // Tạo TxnRef mới cho giao dịch mới
        
        // ⭐ SỬA SQL: Cập nhật lại paymentExpiry và paymentToken mới
        $stmt_update = $mysqli->prepare("
            UPDATE orders 
            SET paymentMethod = 'VNPay', paymentExpiry = ?, paymentToken = ?, paymentStatus = 'Pending' 
            WHERE orderID = ?
        ");
        $stmt_update->bind_param("ssi", $newExpiry, $txnRef, $orderID);
        $stmt_update->execute();
        $stmt_update->close();

        $mysqli->commit();
        
        // Tạo URL VNPay mới (hàm này phải được định nghĩa trong vnpay_helper.php)
        $vnpayUrl = generateVnPayUrl($orderID, $total, $txnRef, $customerEmail); 
        
        respond([
            'isSuccess' => true, 
            'message' => 'VNPAY_REDIRECT', 
            'orderID' => $orderID,
            'vnpayUrl' => $vnpayUrl
        ], 200);
    }

} catch (Exception $e) {
    $mysqli->rollback();
    error_log("[REPAY_API] Transaction Rollback. Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Lỗi xử lý yêu cầu: ' . $e->getMessage()], 500); 
}