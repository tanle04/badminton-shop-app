<?php
// Tên file: cleanup_abandoned_vnpay_orders.php (Chạy định kỳ, ví dụ 10 phút/lần)
error_reporting(E_ALL);
require_once __DIR__ . '/../bootstrap.php'; 
// require_once __DIR__ . '/../utils/email_helper.php'; // Không cần gửi email hủy

date_default_timezone_set('Asia/Ho_Chi_Minh'); 

// THIẾT LẬP NGƯỠNG THỜI GIAN HỦY: Ví dụ: 30 phút
$cancellation_threshold = date('Y-m-d H:i:s', strtotime('-30 minutes'));

$sql = "
    SELECT 
        o.orderID, od.variantID, od.quantity
    FROM orders o
    JOIN orderdetails od ON o.orderID = od.orderID
    WHERE o.status = 'Pending' 
    AND o.paymentMethod = 'VNPay'
    AND o.orderDate < ? 
";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $cancellation_threshold);
$stmt->execute();
$result = $stmt->get_result();

$ordersToCancel = [];
while ($row = $result->fetch_assoc()) {
    $orderID = $row['orderID'];
    $ordersToCancel[$orderID]['items'][] = ['variantID' => $row['variantID'], 'quantity' => $row['quantity']];
}
$stmt->close();

if (!empty($ordersToCancel)) {
    $mysqli->begin_transaction();
    try {
        $orderIDs = implode(',', array_keys($ordersToCancel));
        
        // 1. Cập nhật trạng thái đơn hàng thành Cancelled
        $mysqli->query("UPDATE orders SET status = 'Cancelled', paymentStatus = 'Unpaid' WHERE orderID IN ($orderIDs)");
        
        // 2. Phục hồi reserved stock
        $stmt_restore = $mysqli->prepare("UPDATE product_variants SET stock = stock + ?, reservedStock = reservedStock - ? WHERE variantID = ?");
        
        foreach ($ordersToCancel as $orderID => $data) {
            foreach ($data['items'] as $item) {
                $stmt_restore->bind_param("iii", $item['quantity'], $item['quantity'], $item['variantID']);
                $stmt_restore->execute();
            }
        }
        $stmt_restore->close();

        $mysqli->commit();
        error_log("[CLEANUP] Automatically cancelled abandoned orders: " . $orderIDs);

    } catch (\Exception $e) {
        $mysqli->rollback();
        error_log("[CLEANUP_ERROR] Failed to cancel abandoned orders: " . $e->getMessage());
    }
}
?>