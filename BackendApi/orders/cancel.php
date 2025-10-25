<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
error_reporting(E_ALL);

date_default_timezone_set('Asia/Ho_Chi_Minh'); 

require_once __DIR__ . '/../bootstrap.php'; 
require_once __DIR__ . '/../utils/email_helper.php'; // Đảm bảo đã có hàm sendCancellationEmail

// Nhận dữ liệu POST từ Android
$orderID = (int)($_POST['orderID'] ?? 0);
$customerID = (int)($_POST['customerID'] ?? 0);

// --- Validation ---
if ($orderID <= 0 || $customerID <= 0) {
    respond(['isSuccess' => false, 'message' => 'Dữ liệu đơn hàng không hợp lệ.'], 400);
}

$mysqli->begin_transaction();

try {
    // 1. Lấy chi tiết đơn hàng (CHỈ ĐƠN HÀNG Ở TRẠNG THÁI PENDING HOẶC PROCESSING)
    $stmt_details = $mysqli->prepare("
        SELECT 
            o.status, c.email, c.fullName, od.variantID, od.quantity
        FROM orders o
        JOIN customers c ON o.customerID = c.customerID 
        JOIN orderdetails od ON o.orderID = od.orderID
        WHERE o.orderID = ? AND o.customerID = ? 
          AND o.status IN ('Pending', 'Processing') FOR UPDATE
    ");
    $stmt_details->bind_param("ii", $orderID, $customerID);
    $stmt_details->execute();
    $result = $stmt_details->get_result();
    
    $variants_to_restore = [];
    $customer_info = null;
    
    while ($row = $result->fetch_assoc()) {
        if ($customer_info === null) {
            $customer_info = ['email' => $row['email'], 'fullName' => $row['fullName']];
        }
        $variants_to_restore[] = ['variantID' => $row['variantID'], 'quantity' => $row['quantity']];
    }
    $stmt_details->close();
    
    if (empty($variants_to_restore)) {
        throw new Exception("Không tìm thấy đơn hàng hoặc đơn hàng không đủ điều kiện hủy.");
    }
    
    // 2. Cập nhật trạng thái đơn hàng thành Cancelled
    $stmt_update_order = $mysqli->prepare("UPDATE orders SET status = 'Cancelled', paymentStatus = 'Unpaid' WHERE orderID = ?");
    $stmt_update_order->bind_param("i", $orderID);
    $stmt_update_order->execute();
    $stmt_update_order->close();

    // 3. Phục hồi tồn kho
    $stmt_restore = $mysqli->prepare("
        UPDATE product_variants 
        SET stock = stock + ?, reservedStock = reservedStock - ? 
        WHERE variantID = ? AND reservedStock >= ?
    ");
    
    foreach ($variants_to_restore as $item) {
        $quantity = $item['quantity'];
        $variantID = $item['variantID'];
        $stmt_restore->bind_param("iiii", $quantity, $quantity, $variantID, $quantity);
        $stmt_restore->execute();
    }
    $stmt_restore->close();

    // --- 4. COMMIT VÀ GỬI EMAIL ---
    $mysqli->commit();
    
    if ($customer_info) {
        // ⭐ GỌI HÀM GỬI EMAIL HỦY ĐƠN HÀNG
        sendCancellationEmail($customer_info['email'], $customer_info['fullName'], $orderID);
    }
    
    respond(['isSuccess' => true, 'message' => "Đơn hàng #{$orderID} đã được hủy thành công và tồn kho đã được phục hồi."], 200);
    
} catch (Exception $e) {
    $mysqli->rollback();
    error_log("[CANCEL_API] Transaction Rollback. Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Hủy đơn hàng thất bại: ' . $e->getMessage()], 500); 
}
// KHÔNG CÓ THẺ ĐÓNG PHP Ở CUỐI FILE