<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
error_reporting(E_ALL); // Báo cáo tất cả lỗi
ini_set('display_errors', 0); // Tắt hiển thị lỗi cho client
ini_set('log_errors', 1); // Bật ghi log lỗi

// Thiết lập múi giờ
date_default_timezone_set('Asia/Ho_Chi_Minh');

error_log("[REPAY_API] Request received."); // Log bắt đầu

// Tải các file cần thiết
require_once __DIR__ . '/../bootstrap.php'; // Chứa hàm respond() và $mysqli
require_once __DIR__ . '/../utils/vnpay_helper.php'; // Chứa hàm generateVnPayUrl()

// Nhận dữ liệu POST
$orderID = (int)($_POST['orderID'] ?? 0);
$customerID = (int)($_POST['customerID'] ?? 0);

error_log("[REPAY_API] Input - orderID: " . $orderID . ", customerID: " . $customerID);

// --- Validation cơ bản ---
if ($orderID <= 0 || $customerID <= 0) {
    error_log("[REPAY_API] Validation Failed: Invalid orderID or customerID.");
    respond(['isSuccess' => false, 'message' => 'Dữ liệu ID đơn hàng hoặc khách hàng không hợp lệ.'], 400);
    exit; // ⭐ QUAN TRỌNG: Dừng script sau khi gửi lỗi 400
}

// Kiểm tra kết nối $mysqli
if (!$mysqli || $mysqli->connect_error) {
     $error_msg = $mysqli ? $mysqli->connect_error : "mysqli object is null";
     error_log("[REPAY_API] FATAL: Database connection error: " . $error_msg);
     respond(['isSuccess' => false, 'message' => 'Lỗi kết nối cơ sở dữ liệu.'], 500);
     exit; // ⭐ QUAN TRỌNG: Dừng script
}

// --- Bắt đầu Transaction ---
if (!$mysqli->begin_transaction()) {
    error_log("[REPAY_API] FATAL: Failed to begin transaction: " . $mysqli->error);
    respond(['isSuccess' => false, 'message' => 'Lỗi hệ thống khi bắt đầu giao dịch.'], 500);
    exit; // ⭐ QUAN TRỌNG: Dừng script
}
error_log("[REPAY_API] Transaction started for Order ID: " . $orderID);

try {
    // 1. Lấy thông tin đơn hàng và khóa bản ghi
    $stmt = $mysqli->prepare("
        SELECT
            o.total, o.paymentExpiry, o.paymentStatus, o.status, o.paymentToken, c.email
        FROM orders o
        JOIN customers c ON o.customerID = c.customerID
        WHERE o.orderID = ? AND o.customerID = ? FOR UPDATE
    ");
    if ($stmt === false) {
        throw new Exception("Lỗi chuẩn bị SQL lấy thông tin đơn hàng: " . $mysqli->error);
    }

    $stmt->bind_param("ii", $orderID, $customerID);
    if (!$stmt->execute()) {
        throw new Exception("Lỗi execute SQL lấy thông tin đơn hàng: " . $stmt->error);
    }
    $order_info_result = $stmt->get_result();
    $order_info = $order_info_result->fetch_assoc();
    $stmt->close();

    if (!$order_info) {
        error_log("[REPAY_API] Order not found or customer mismatch for Order ID: " . $orderID);
        throw new Exception("Đơn hàng không tồn tại hoặc không thuộc về bạn.");
    }
    error_log("[REPAY_API] Order found. Status: [" . $order_info['status'] . "], PaymentStatus: [" . $order_info['paymentStatus'] . "]");

    // Lấy thông tin cần thiết
    $total = $order_info['total'];
    $customerEmail = $order_info['email'];
    $currentStatus = $order_info['status'];
    $currentPaymentStatus = $order_info['paymentStatus']; // Giá trị này có thể là '', null, 'Pending', 'Unpaid', 'Failed'

    // Kiểm tra trạng thái cho phép thanh toán lại
    if ($currentStatus !== 'Pending' || $currentPaymentStatus === 'Paid' || $currentPaymentStatus === 'Refunded') {
        error_log("[REPAY_API] Order cannot be repaid. Status: " . $currentStatus . ", PaymentStatus: " . $currentPaymentStatus);
        throw new Exception("Đơn hàng không ở trạng thái có thể thanh toán lại.");
    }

    // --- Xử lý logic THANH TOÁN LẠI VNPay ---
    error_log("[REPAY_API] Proceeding with VNPay repayment logic.");

    $newExpiry = date('Y-m-d H:i:s', strtotime('+1 hour')); // Gia hạn 1 giờ
    $txnRef = "ORDER_" . $orderID . "_" . time(); // Luôn tạo TxnRef MỚI
    error_log("[REPAY_API] New Expiry: [" . $newExpiry . "], New TxnRef: [" . $txnRef . "]");

    // Cập nhật CSDL: Set paymentMethod='VNPay', cập nhật expiry, token, và đảm bảo paymentStatus='Pending'
    $stmt_update = $mysqli->prepare("
        UPDATE orders
        SET paymentMethod = 'VNPay', paymentExpiry = ?, paymentToken = ?, paymentStatus = 'Pending'
        WHERE orderID = ? AND customerID = ? AND status = 'Pending' /* Thêm điều kiện status an toàn */
    ");
    if ($stmt_update === false) {
        throw new Exception("Lỗi chuẩn bị SQL cập nhật đơn hàng: " . $mysqli->error);
    }

    $stmt_update->bind_param("ssii", $newExpiry, $txnRef, $orderID, $customerID);
    if (!$stmt_update->execute()) {
        throw new Exception("Lỗi execute SQL cập nhật đơn hàng: " . $stmt_update->error);
    }
    $affected_rows = $stmt_update->affected_rows;
    $stmt_update->close();

    if ($affected_rows <= 0) {
        error_log("[REPAY_API] Failed to update order record for Order ID: " . $orderID . ". Affected rows: " . $affected_rows);
        throw new Exception("Không thể cập nhật trạng thái đơn hàng để thanh toán lại (có thể đơn hàng đã thay đổi).");
    }
    error_log("[REPAY_API] Order record updated successfully.");

    // Tạo URL VNPay MỚI (sau khi cập nhật DB thành công)
    error_log("[REPAY_API] Generating VNPay URL...");
    // Đảm bảo hàm generateVnPayUrl tồn tại và xử lý lỗi bên trong nếu có
    if (!function_exists('generateVnPayUrl')) {
        error_log("[REPAY_API] FATAL: Function generateVnPayUrl not found!");
        throw new Exception('Lỗi hệ thống: Không tìm thấy hàm tạo URL VNPay.');
    }
    // Bọc trong try-catch nếu hàm generate có thể ném Exception
    try {
        $vnpayUrl = generateVnPayUrl($orderID, $total, $txnRef, $customerEmail);
    } catch (Exception $urlEx) {
         error_log("[REPAY_API] ERROR generating VNPay URL: " . $urlEx->getMessage());
         throw new Exception('Lỗi tạo URL thanh toán VNPay: ' . $urlEx->getMessage());
    }

    error_log("[REPAY_API] VNPay URL potentially generated (check value): " . $vnpayUrl);

    // Kiểm tra URL có hợp lệ không
    if (empty($vnpayUrl) || !filter_var($vnpayUrl, FILTER_VALIDATE_URL)) {
        error_log("[REPAY_API] ERROR: Generated VNPay URL is invalid or empty!");
        throw new Exception('Lỗi tạo URL thanh toán VNPay không hợp lệ.');
    }
    error_log("[REPAY_API] VNPay URL is valid.");


    // --- Hoàn tất Transaction và Gửi Phản hồi ---
    if (!$mysqli->commit()) {
         error_log("[REPAY_API] FATAL: Failed to commit transaction: " . $mysqli->error);
         // Dù commit lỗi, URL đã tạo, thử gửi về client? Hoặc throw Exception?
         // Tạm thời throw Exception để rollback (dù có thể không rollback được nữa)
         throw new Exception("Lỗi hệ thống khi lưu trạng thái đơn hàng.");
    }
    error_log("[REPAY_API] Transaction committed.");

    // Gửi phản hồi thành công
    respond([
        'isSuccess' => true,
        'message' => 'VNPAY_REDIRECT',
        'orderID' => $orderID,
        'vnpayUrl' => $vnpayUrl
    ], 200);
    error_log("[REPAY_API] Success response sent.");
    exit; // ⭐ QUAN TRỌNG: Dừng script sau khi gửi thành công

} catch (Exception $e) {
    // Rollback transaction nếu có lỗi xảy ra
    $mysqli->rollback(); // Cố gắng rollback
    error_log("[REPAY_API] Transaction Rollback triggered. Error: " . $e->getMessage());
    // Gửi phản hồi lỗi 500
    respond(['isSuccess' => false, 'message' => 'Lỗi xử lý yêu cầu thanh toán lại: ' . $e->getMessage()], 500);
    exit; // ⭐ QUAN TRỌNG: Dừng script sau khi gửi lỗi 500
}

// Dòng này không bao giờ nên được chạy tới
error_log("[REPAY_API] WARNING: Script reached end without exiting properly.");
?>