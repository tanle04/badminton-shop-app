<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
error_reporting(E_ALL);

// Tải các file cần thiết
require_once __DIR__ . '/../vendor/autoload.php'; 
require_once __DIR__ . '/../bootstrap.php'; 
require_once __DIR__ . '/../utils/email_helper.php'; 
require_once __DIR__ . '/../utils/vnpay_helper.php'; 
require_once __DIR__ . '/../utils/price_calculator.php'; 
require_once __DIR__ . '/../utils/voucher_helper.php'; 
require_once __DIR__ . '/../utils/shipping_helper.php';

// ⭐ SỬA ĐỔI: Thêm Exception tùy chỉnh
class PriceMismatchException extends Exception {}

// Thiết lập múi giờ
date_default_timezone_set('Asia/Ho_Chi_Minh'); 

// ... (Các biến POST giữ nguyên) ...
$customerID = (int)($_POST['customerID'] ?? 0);
$addressID = (int)($_POST['addressID'] ?? 0);
$paymentMethod = $_POST['paymentMethod'] ?? ''; 
$clientTotal = (float)($_POST['total'] ?? 0.0);
$itemsJSON = $_POST['items'] ?? '[]';
$voucherID = (int)($_POST['voucherID'] ?? -1);
$selectedRateID = (int)($_POST['selectedRateID'] ?? 0);
$shippingFeeClient = (float)($_POST['shippingFee'] ?? 0.0);
// ... (Các biến khởi tạo giữ nguyên) ...
$items = json_decode($itemsJSON, true); 
$emailSent = false; 
$shippingFee = 0.0;
$voucherDiscount = 0.0;
$backendSubtotal = 0.0; 
$finalTotal = 0.0; 
$cartIDsToDelete = [];
$productsForEmail = []; 

// --- Validation ---
if ($customerID <= 0 || $addressID <= 0 || empty($paymentMethod) || $clientTotal <= 0 || empty($items) || !is_array($items) || $selectedRateID <= 0) {
    respond(['isSuccess' => false, 'message' => 'Dữ liệu đầu vào không hợp lệ hoặc thiếu thông tin vận chuyển.'], 400);
}

// --- Bắt đầu Transaction ---
$mysqli->begin_transaction();

// ⭐ SỬA ĐỔI: Thêm catch block cho PriceMismatchException
try {
    // 1. Lấy thông tin khách hàng (Giữ nguyên)
    $stmt_info = $mysqli->prepare("
        SELECT c.fullName, c.email, a.street AS streetAddress, a.city 
        FROM customers c JOIN customer_addresses a ON a.addressID = ? AND a.customerID = c.customerID
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
    
    // 2. Kiểm tra tồn kho (Giữ nguyên)
    $stmt_update_stock = $mysqli->prepare(
        "UPDATE product_variants SET stock = stock - ?, reservedStock = reservedStock + ? WHERE variantID = ? AND stock >= ?"
    );
    if ($stmt_update_stock === false) {
        throw new Exception("Lỗi chuẩn bị SQL cho tồn kho: " . $mysqli->error);
    }
    foreach ($items as $item) {
        $quantity = (int)($item['quantity'] ?? 0);
        $variantID = (int)($item['variantID'] ?? 0);
        $cartIDsToDelete[] = (int)($item['cartID'] ?? 0);
        if ($variantID <= 0 || $quantity <= 0) continue;

        $stmt_update_stock->bind_param("iiii", $quantity, $quantity, $variantID, $quantity);
        $stmt_update_stock->execute();
        if ($stmt_update_stock->affected_rows == 0) {
            // (Code kiểm tra tồn kho chi tiết giữ nguyên)
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

        // TÍNH SUBTOTAL TỪ SERVER (DÙNG GIÁ MỚI NHẤT)
        $price_details = get_best_sale_price($mysqli, $variantID);
        $finalPrice = $price_details['salePrice'];
        $backendSubtotal += $finalPrice * $quantity;
        
        $productsForEmail[] = [
            'productName' => $item['productName'] ?? 'Sản phẩm không tên', 
            'quantity' => $quantity, 
            'price' => $finalPrice
        ];
    }
    $stmt_update_stock->close();
    
    // --- Bước 3A: XÁC MINH PHÍ VẬN CHUYỂN TRÊN SERVER ---
    // ⭐ SỬA ĐỔI: Kiểm tra flag 'isPriceMismatch'
    $shippingDetails = verifyShippingFee($mysqli, $selectedRateID, $backendSubtotal, $shippingFeeClient);
    if (!$shippingDetails['isValid']) {
        // Nếu đây là lỗi do giá thay đổi, ném Exception tùy chỉnh
        if (isset($shippingDetails['isPriceMismatch']) && $shippingDetails['isPriceMismatch']) {
            throw new PriceMismatchException($shippingDetails['message']);
        }
        // Nếu là lỗi khác (vd: rateID không tồn tại)
        throw new Exception("Lỗi xác minh phí vận chuyển: " . $shippingDetails['message']);
    }
    $shippingFee = $shippingDetails['verifiedFee'];
    $rateDetails = $shippingDetails['rateDetails']; 

    
    // --- Bước 3B: TÍNH FINAL TOTAL (Giữ nguyên) ---
    if ($voucherID > 0) {
        $voucherDetails = getVoucherDiscountDetails($mysqli, $voucherID, $backendSubtotal);
        if (!$voucherDetails['isValid']) {
            throw new Exception("Voucher không hợp lệ: " . $voucherDetails['message']);
        }
        $voucherDiscount = $voucherDetails['discountAmount'];
    }
    
    $finalTotal = ($backendSubtotal + $shippingFee) - $voucherDiscount;
    $finalTotal = max(0.0, $finalTotal); 

    // ⭐ SỬA ĐỔI: Thêm bước xác minh TỔNG TIỀN CUỐI CÙNG
    // (Đây là một "safety net" thứ 2, so sánh tổng tiền server tính với tổng tiền client gửi)
    if (abs($finalTotal - $clientTotal) > 0.01) {
         throw new PriceMismatchException(
            "Tổng tiền cuối cùng không khớp. Server: " . number_format($finalTotal) . "đ, Client: " . number_format($clientTotal) . "đ. Có thể giá đã thay đổi."
         );
    }

    // --- Bước 4: INSERT ĐƠN HÀNG (Giữ nguyên) ---
    $paymentStatus = ('COD' === $paymentMethod) ? 'Unpaid' : 'Pending';
    $initialStatus = ('COD' === $paymentMethod) ? 'Processing' : 'Pending';
    $paymentToken = null;
    $paymentExpiry = null;
    if ($paymentMethod === 'VNPay') {
        $paymentToken = "ORDER_" . time();
        $paymentExpiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
    }
    $sql_order = "
        INSERT INTO orders (customerID, addressID, paymentMethod, paymentStatus, total, status, voucherID, paymentToken, paymentExpiry) 
        VALUES (?, ?, ?, ?, ?, ?, IF(? > 0, ?, NULL), ?, ?)
    ";
    $stmt_order = $mysqli->prepare($sql_order);
    if ($stmt_order === false) throw new Exception("Lỗi chuẩn bị SQL cho đơn hàng: " . $mysqli->error);
    $stmt_order->bind_param("iissdssiss", 
        $customerID, $addressID, $paymentMethod, $paymentStatus, $finalTotal, $initialStatus, 
        $voucherID, $voucherID, $paymentToken, $paymentExpiry
    );
    $stmt_order->execute();
    $orderID = $mysqli->insert_id;
    $stmt_order->close();
    if ($orderID == 0) throw new Exception("Không thể tạo bản ghi đơn hàng.");
    
    // --- BƯỚC MỚI: INSERT SHIPPING (Giữ nguyên) ---
    $shippingMethod = $rateDetails['carrierName'] . ' - ' . $rateDetails['serviceName'];
    $stmt_shipping = $mysqli->prepare("
        INSERT INTO shipping (orderID, shippingMethod, shippingFee) 
        VALUES (?, ?, ?)
    ");
    if (!$stmt_shipping) {
        throw new Exception("Lỗi chuẩn bị SQL cho shipping: " . $mysqli->error);
    }
    $stmt_shipping->bind_param("isd", $orderID, $shippingMethod, $shippingFee);
    $stmt_shipping->execute();
    $stmt_shipping->close();

    // --- Bước 5: Chèn Order Details (Giữ nguyên) ---
    $stmt_details = $mysqli->prepare("INSERT INTO orderdetails (orderID, variantID, quantity, price) VALUES (?, ?, ?, ?)");
    if ($stmt_details === false) throw new Exception("Lỗi chuẩn bị SQL cho chi tiết đơn hàng: " . $mysqli->error);
    foreach ($items as $item) {
        $variantID = (int)($item['variantID'] ?? 0);
        $quantity = (int)($item['quantity'] ?? 0);
        $price_details = get_best_sale_price($mysqli, $variantID); 
        $finalPrice = $price_details['salePrice'];
        $stmt_details->bind_param("iiid", $orderID, $variantID, $quantity, $finalPrice);
        $stmt_details->execute();
    }
    $stmt_details->close();
    
    // --- Bước 6: Xử lý Voucher và Xóa Cart (Giữ nguyên) ---
    // (Toàn bộ logic xử lý voucher... )
    if ($voucherID > 0) {
        $stmt_voucher_info = $mysqli->prepare("SELECT isPrivate FROM vouchers WHERE voucherID = ?");
        $stmt_voucher_info->bind_param("i", $voucherID);
        $stmt_voucher_info->execute();
        $voucher_info = $stmt_voucher_info->get_result()->fetch_assoc();
        $stmt_voucher_info->close();

        if ($voucher_info) {
            $isPrivate = (bool)$voucher_info['isPrivate'];
            
            if ($isPrivate) {
                $stmt_update_cust_voucher = $mysqli->prepare("UPDATE customer_vouchers SET status = 'used' WHERE customerID = ? AND voucherID = ? AND status = 'available'");
                $stmt_update_cust_voucher->bind_param("ii", $customerID, $voucherID);
                $stmt_update_cust_voucher->execute();
                if ($stmt_update_cust_voucher->affected_rows == 0) {
                    throw new Exception("Voucher cá nhân đã được sử dụng hoặc không hợp lệ (VoucherID: {$voucherID}).");
                }
                $stmt_update_cust_voucher->close();
            } 
            
            $stmt_update_global_voucher = $mysqli->prepare("UPDATE vouchers SET usedCount = usedCount + 1 WHERE voucherID = ? AND usedCount < maxUsage");
            $stmt_update_global_voucher->bind_param("i", $voucherID);
            $stmt_update_global_voucher->execute();
            if ($stmt_update_global_voucher->affected_rows == 0) {
                 if ($isPrivate) {
                    $stmt_rollback_cust_voucher = $mysqli->prepare("UPDATE customer_vouchers SET status = 'available' WHERE customerID = ? AND voucherID = ? AND status = 'used'");
                    $stmt_rollback_cust_voucher->bind_param("ii", $customerID, $voucherID);
                    $stmt_rollback_cust_voucher->execute();
                    $stmt_rollback_cust_voucher->close();
                 }
                throw new Exception("Voucher đã hết lượt sử dụng (maxUsage).");
            }
            $stmt_update_global_voucher->close();
        }
    }
    
    // (Toàn bộ logic xóa cart... )
    if (!empty($cartIDsToDelete)) {
        $placeholders = implode(',', array_fill(0, count($cartIDsToDelete), '?'));
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
    
    // --- BƯỚC 7: XỬ LÝ THANH TOÁN / GỬI EMAIL (Giữ nguyên) ---
    if ($paymentMethod === 'VNPay') {
        $txnRef = $paymentToken . "_" . $orderID; 
        $stmt_update_token = $mysqli->prepare("UPDATE orders SET paymentToken = ? WHERE orderID = ?");
        $stmt_update_token->bind_param("si", $txnRef, $orderID);
        $stmt_update_token->execute();
        $stmt_update_token->close();
        
        $mysqli->commit(); 
        $vnpayUrl = generateVnPayUrl($orderID, $finalTotal, $txnRef, $customer_info['email']); 
        
        respond([
            'isSuccess' => true,
            'message' => 'VNPAY_REDIRECT', 
            'orderID' => $orderID,
            'vnpayUrl' => $vnpayUrl,
        ], 200);

    } else {
        // LOGIC COD
        try {
            $orderDataForEmail = [
                'orderID' => $orderID,
                'totalAmount' => $finalTotal,
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

        $mysqli->commit();
        respond([
            'isSuccess' => true, 
            'message' => 'Đặt hàng thành công! Đơn hàng sẽ được giao trong vài ngày tới.', 
            'orderID' => $orderID,
            'emailStatus' => $emailSent ? 'sent' : 'failed_to_send'
        ], 200);
    }
    
// ⭐ SỬA ĐỔI: Thêm catch block mới cho lỗi 409
} catch (PriceMismatchException $pme) {
    // Đây là lỗi 409 (Conflict) do giá hoặc phí ship thay đổi
    $mysqli->rollback();
    error_log("[ORDER_API] Price Mismatch (409): " . $pme->getMessage());
    // Gửi về mã lỗi 409
    respond(['isSuccess' => false, 'message' => 'PRICE_MISMATCH: ' . $pme->getMessage()], 409);

} catch (Exception $e) {
    // Đây là lỗi 500 (Internal Error) chung
    $mysqli->rollback();
    error_log("[ORDER_API] Transaction Rollback (500). Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Đặt hàng thất bại: ' . $e->getMessage()], 500); 
}
?>