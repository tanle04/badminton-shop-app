<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
error_reporting(E_ALL);

// Tải các file cần thiết
require_once __DIR__ . '/../vendor/autoload.php'; 
require_once __DIR__ . '/../bootstrap.php'; 
require_once __DIR__ . '/../utils/email_helper.php'; 
require_once __DIR__ . '/../utils/vnpay_helper.php'; 

// Thiết lập múi giờ
date_default_timezone_set('Asia/Ho_Chi_Minh'); 

// Nhận dữ liệu POST từ Android
$customerID = (int)($_POST['customerID'] ?? 0);
$addressID = (int)($_POST['addressID'] ?? 0);
$paymentMethod = $_POST['paymentMethod'] ?? ''; 
$total = (float)($_POST['total'] ?? 0.0);
$itemsJSON = $_POST['items'] ?? '[]';
$voucherID = (int)($_POST['voucherID'] ?? -1);
$items = json_decode($itemsJSON, true); 
$emailSent = false; 
$totalPayment = $total; 

// --- Validation ---
if ($customerID <= 0 || $addressID <= 0 || empty($paymentMethod) || $total <= 0 || empty($items) || !is_array($items)) {
    respond(['isSuccess' => false, 'message' => 'Dữ liệu đầu vào không hợp lệ.'], 400);
}

// --- Bắt đầu Transaction ---
$mysqli->begin_transaction();

try {
    // 1. Lấy thông tin khách hàng và địa chỉ (giữ nguyên)
    $stmt_info = $mysqli->prepare("
        SELECT 
            c.fullName, 
            c.email, 
            a.street AS streetAddress, 
            a.city 
        FROM customers c 
        JOIN customer_addresses a ON a.addressID = ? AND a.customerID = c.customerID
        WHERE c.customerID = ?
    ");
    $stmt_info->bind_param("ii", $addressID, $customerID);
    $stmt_info->execute();
    $customer_info = $stmt_info->get_result()->fetch_assoc();
    $stmt_info->close();

    if (!$customer_info) {
        throw new Exception("Thông tin khách hàng hoặc địa chỉ không tồn tại.");
    }
    
    $shippingAddress = $customer_info['streetAddress'] . ', ' . $customer_info['city'];
    
    // --- Bước 2: Kiểm tra và tạm trừ tồn kho (RESERVE STOCK) (giữ nguyên)
    $stmt_update_stock = $mysqli->prepare(
        "UPDATE product_variants SET stock = stock - ?, reservedStock = reservedStock + ? WHERE variantID = ? AND stock >= ?"
    );
    if ($stmt_update_stock === false) {
        throw new Exception("Lỗi chuẩn bị SQL cho tồn kho: " . $mysqli->error);
    }

    foreach ($items as $item) {
        $quantity = (int)($item['quantity'] ?? 0);
        $variantID = (int)($item['variantID'] ?? 0);
        if ($variantID <= 0 || $quantity <= 0) continue;

        $stmt_update_stock->bind_param("iiii", $quantity, $quantity, $variantID, $quantity);
        $stmt_update_stock->execute();
        if ($stmt_update_stock->affected_rows == 0) {
            $check_stmt = $mysqli->prepare("SELECT stock FROM product_variants WHERE variantID = ?");
            if ($check_stmt) {
                $check_stmt->bind_param("i", $variantID);
                $check_stmt->execute();
                $current_stock = $check_stmt->get_result()->fetch_assoc()['stock'] ?? 0;
                $check_stmt->close();
            } else { $current_stock = 0; }

            throw new Exception("Sản phẩm '" . ($item['productName'] ?? 'ID: ' . $variantID) .
                "' không đủ số lượng tồn kho. Hiện còn: " . $current_stock);
        }
    }
    $stmt_update_stock->close();

    // --- Bước 3: Tạo đơn hàng trong bảng `orders` ---
    $paymentStatus = ('COD' === $paymentMethod) ? 'Unpaid' : 'Pending';
    $initialStatus = ('COD' === $paymentMethod) ? 'Processing' : 'Pending';
    
    // ⭐ LOGIC BỔ SUNG CHO VNPAY ⭐
    $paymentToken = null;
    $paymentExpiry = null;
    if ($paymentMethod === 'VNPay') {
        $paymentToken = "ORDER_" . time(); // Tạo TxnRef tạm thời
        $paymentExpiry = date('Y-m-d H:i:s', strtotime('+1 hour')); // 1 giờ
    }

    // ⭐ SỬA SQL: Thêm paymentToken và paymentExpiry
    $sql_order = "
        INSERT INTO orders (customerID, addressID, paymentMethod, paymentStatus, total, status, voucherID, paymentToken, paymentExpiry) 
        VALUES (?, ?, ?, ?, ?, ?, IF(? > 0, ?, NULL), ?, ?)
    ";

    $stmt_order = $mysqli->prepare($sql_order);
    if ($stmt_order === false) throw new Exception("Lỗi chuẩn bị SQL cho đơn hàng: " . $mysqli->error);
    
    // Bind: i i s s d s i i s s
    $stmt_order->bind_param("iissdsiiss", 
        $customerID, $addressID, $paymentMethod, $paymentStatus, $total, $initialStatus, 
        $voucherID, $voucherID, $paymentToken, $paymentExpiry
    );
    $stmt_order->execute();
    $orderID = $mysqli->insert_id;
    $stmt_order->close();
    
    if ($orderID == 0) throw new Exception("Không thể tạo bản ghi đơn hàng.");

    // --- Bước 4 & 5 (Voucher & OrderDetails) ⭐ LOGIC VOUCHER ĐÃ SỬA ⭐ ---
    if ($voucherID > 0) {
        $stmt_voucher_info = $mysqli->prepare("SELECT isPrivate FROM vouchers WHERE voucherID = ?");
        $stmt_voucher_info->bind_param("i", $voucherID);
        $stmt_voucher_info->execute();
        $voucher_info = $stmt_voucher_info->get_result()->fetch_assoc();
        $stmt_voucher_info->close();

        if ($voucher_info) {
            $isPrivate = (bool)$voucher_info['isPrivate'];
            
            // 1. Xử lý Voucher Cá nhân (Đảm bảo chỉ dùng 1 lần)
            if ($isPrivate) {
                $stmt_update_cust_voucher = $mysqli->prepare("UPDATE customer_vouchers SET status = 'used' WHERE customerID = ? AND voucherID = ? AND status = 'available'");
                $stmt_update_cust_voucher->bind_param("ii", $customerID, $voucherID);
                $stmt_update_cust_voucher->execute();
                if ($stmt_update_cust_voucher->affected_rows == 0) {
                    throw new Exception("Voucher cá nhân đã được sử dụng hoặc không hợp lệ (VoucherID: {$voucherID}).");
                }
                $stmt_update_cust_voucher->close();
            } 
            
            // 2. Xử lý giới hạn sử dụng Toàn cầu (Áp dụng cho cả chung và cá nhân)
            // Đảm bảo maxUsage được tuân thủ cho cả 2 loại voucher
            $stmt_update_global_voucher = $mysqli->prepare("UPDATE vouchers SET usedCount = usedCount + 1 WHERE voucherID = ? AND usedCount < maxUsage");
            $stmt_update_global_voucher->bind_param("i", $voucherID);
            $stmt_update_global_voucher->execute();
            if ($stmt_update_global_voucher->affected_rows == 0) {
                // Nếu voucher là private, ta cần rollback trạng thái 'used' vừa cập nhật.
                if ($isPrivate) {
                    // Cố gắng đặt lại trạng thái về 'available'
                    $stmt_rollback_cust_voucher = $mysqli->prepare("UPDATE customer_vouchers SET status = 'available' WHERE customerID = ? AND voucherID = ? AND status = 'used'");
                    $stmt_rollback_cust_voucher->bind_param("ii", $customerID, $voucherID);
                    $stmt_rollback_cust_voucher->execute();
                    $stmt_rollback_cust_voucher->close();
                    // Lưu ý: Nếu rollback thất bại (affected_rows == 0) thì ta chấp nhận mất voucher, 
                    // nhưng sẽ throw exception để transaction lớn rollback
                }
                throw new Exception("Voucher đã hết lượt sử dụng (maxUsage).");
            }
            $stmt_update_global_voucher->close();
        }
    }

    // Order Details Insertion
    $stmt_details = $mysqli->prepare("INSERT INTO orderdetails (orderID, variantID, quantity, price) VALUES (?, ?, ?, ?)");
    if ($stmt_details === false) throw new Exception("Lỗi chuẩn bị SQL cho chi tiết đơn hàng: " . $mysqli->error);

    $cartIDsToDelete = [];
    $productsForEmail = []; 

    foreach ($items as $item) {
        $variantID = (int)($item['variantID'] ?? 0);
        $quantity = (int)($item['quantity'] ?? 0);
        $price = (float)($item['variantPrice'] ?? 0.0);
        $cartIDsToDelete[] = (int)($item['cartID'] ?? 0);
        $productsForEmail[] = ['productName' => $item['productName'] ?? 'Sản phẩm không tên', 'quantity' => $quantity, 'price' => $price];

        $stmt_details->bind_param("iiid", $orderID, $variantID, $quantity, $price);
        $stmt_details->execute();
    }
    $stmt_details->close();

    // --- Bước 6: Xóa Cart (giữ nguyên) ---
    if (!empty($cartIDsToDelete)) {
        $placeholders = implode(',', array_fill(0, count($cartIDsToDelete), '?'));
        // Cần thêm 1 'i' cho $customerID ở đầu
        $types = 'i' . str_repeat('i', count($cartIDsToDelete)); 
        $stmt_delete = $mysqli->prepare("DELETE FROM shopping_cart WHERE customerID = ? AND cartID IN ($placeholders)");
        if ($stmt_delete === false) throw new Exception("Lỗi chuẩn bị SQL xóa giỏ hàng: " . $mysqli->error);
        $bind_params = array_merge([$types], [$customerID], $cartIDsToDelete);
        $refs = [];
        foreach ($bind_params as $key => $value) { $refs[$key] = &$bind_params[$key]; }
        call_user_func_array([$stmt_delete, 'bind_param'], $refs);
        $stmt_delete->execute();
        $stmt_delete->close();
    }
    
    // =========================================================================
    // ⭐⭐ BƯỚC 7: XỬ LÝ THANH TOÁN / GỬI EMAIL ⭐⭐
    // =========================================================================
    
    if ($paymentMethod === 'VNPay') {
        // LOGIC VNPAY
        
        $txnRef = $paymentToken . "_" . $orderID; // Cần có orderID để sau này truy vấn dễ dàng hơn
        
        // Cập nhật txnRef đầy đủ (có orderID) vào DB
        $stmt_update_token = $mysqli->prepare("UPDATE orders SET paymentToken = ? WHERE orderID = ?");
        $stmt_update_token->bind_param("si", $txnRef, $orderID);
        $stmt_update_token->execute();
        $stmt_update_token->close();
        
        // COMMIT ở đây để đơn hàng đã được ghi nhận trước khi chuyển hướng
        $mysqli->commit(); 

        // Tạo URL VNPay và trả về cho client Android
        $vnpayUrl = generateVnPayUrl($orderID, $totalPayment, $txnRef, $customer_info['email']); 
        
        respond([
            'isSuccess' => true,
            'message' => 'VNPAY_REDIRECT', 
            'orderID' => $orderID,
            'vnpayUrl' => $vnpayUrl,
        ], 200);

    } else {
        // LOGIC COD
        
        try {
            // GỬI EMAIL XÁC NHẬN ĐƠN HÀNG (Dành cho COD)
            $orderDataForEmail = [
                'orderID' => $orderID,
                'totalAmount' => $totalPayment,
                'shippingAddress' => $shippingAddress,
                'items' => $productsForEmail
            ];
            $emailSent = sendOrderConfirmationEmail(
                $customer_info['email'], 
                $customer_info['fullName'], 
                $orderDataForEmail
            );
        } catch (\Throwable $emailE) {
            error_log("[ORDER_API] Email Confirmation Failed: " . $emailE->getMessage());
        }

        // --- Hoàn tất, xác nhận tất cả các thay đổi ---
        $mysqli->commit();

        respond([
            'isSuccess' => true, 
            'message' => 'Đặt hàng thành công! Đơn hàng sẽ được giao trong vài ngày tới.', 
            'orderID' => $orderID,
            'emailStatus' => $emailSent ? 'sent' : 'failed_to_send'
        ], 200);
    }
    
} catch (Exception $e) {
    // Nếu có bất kỳ lỗi nào, hoàn tác tất cả các thay đổi
    $mysqli->rollback();
    error_log("[ORDER_API] Transaction Rollback. Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Đặt hàng thất bại: ' . $e->getMessage()], 500); 
}