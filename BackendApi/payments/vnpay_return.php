<?php
// TẮT DEBUG
error_reporting(0);
ini_set('display_errors', 0);
date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../utils/email_helper.php';

const VNPAY_HASH_SECRET = "L84RURSU748VB8FULHKJP12ADCBEZLSJ";

// 1. LẤY THAM SỐ
$vnp_Params = $_GET;
$vnp_SecureHash = $vnp_Params['vnp_SecureHash'] ?? '';
$vnp_TxnRef = $vnp_Params['vnp_TxnRef'] ?? '';
$vnp_ResponseCode = $vnp_Params['vnp_ResponseCode'] ?? '';
$vnp_TransactionNo = $vnp_Params['vnp_TransactionNo'] ?? '';
$vnp_Amount = (float)($vnp_Params['vnp_Amount'] ?? 0);
$amountPaid = $vnp_Amount / 100;

// Trích xuất OrderID từ TxnRef
$parts = explode('_', $vnp_TxnRef);
$orderID = 0;
if (count($parts) >= 2) {
    $orderID = (int)$parts[1];
    error_log("[VNPAY_RETURN] Extracted OrderID: {$orderID} from TxnRef: {$vnp_TxnRef}");
}

// 2. XÁC MINH CHỮ KÝ
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

// Mặc định: thất bại
$returnUrl = "badmintonshop://yourorders?status=failed_cancelled&orderID={$orderID}";
$mysqli_connected = (isset($mysqli) && !$mysqli->connect_error);

// 3. XỬ LÝ LOGIC
if ($orderID > 0 && $mysqli_connected) {
    error_log("[VNPAY_RETURN] Processing OrderID: {$orderID}, ResponseCode: {$vnp_ResponseCode}");

    $mysqli->begin_transaction();

    try {
        // Lấy thông tin đơn hàng (FOR UPDATE để lock)
        $stmt_check = $mysqli->prepare("
            SELECT 
                o.paymentStatus, o.status, o.total, c.email, c.fullName, 
                a.street, a.city, od.variantID, od.quantity, od.price,
                (SELECT p.productName FROM products p JOIN product_variants pv ON p.productID = pv.productID WHERE pv.variantID = od.variantID) AS productName
            FROM orders o 
            JOIN customers c ON o.customerID = c.customerID 
            JOIN customer_addresses a ON o.addressID = a.addressID
            JOIN orderdetails od ON o.orderID = od.orderID
            WHERE o.orderID = ? FOR UPDATE
        ");

        if (!$stmt_check) {
            throw new Exception("SQL Prepare Error: " . $mysqli->error);
        }

        $stmt_check->bind_param("i", $orderID);
        $stmt_check->execute();
        $result = $stmt_check->get_result();

        $order_data = null;
        $variants_to_restore = [];
        $productsForEmail = [];
        $orderTotalDB = 0.0;

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                if (!$order_data) {
                    $order_data = $row;
                    $orderTotalDB = (float)$row['total'];
                }
                $variants_to_restore[] = ['variantID' => $row['variantID'], 'quantity' => $row['quantity']];
                $productsForEmail[] = [
                    'productName' => $row['productName'] ?? 'Sản phẩm không tên',
                    'quantity' => (int)$row['quantity'],
                    'price' => (float)$row['price']
                ];
            }
        }
        $stmt_check->close();

        error_log("[VNPAY_RETURN] Order Status: " . ($order_data['status'] ?? 'N/A') . ", PaymentStatus: " . ($order_data['paymentStatus'] ?? 'N/A'));

        // ⭐ CHỈ XỬ LÝ ĐƠN HÀNG PENDING
        if ($order_data && $order_data['status'] === 'Pending' && $orderTotalDB > 0) {

            // Xác minh Hash
            if ($secureHash == $vnp_SecureHash) {
                error_log("[VNPAY_RETURN] Hash Verified ✓");

                // Kiểm tra số tiền (cho phép sai số 1 VND)
                if (abs($orderTotalDB - $amountPaid) > 1) {
                    error_log("[VNPAY_ERROR] Amount mismatch. DB: {$orderTotalDB}, Paid: {$amountPaid}");
                    $vnp_ResponseCode = '01'; // Buộc thất bại
                }

                if ($vnp_ResponseCode == '00') {
                    // ✅ THANH TOÁN THÀNH CÔNG
                    error_log("[VNPAY_SUCCESS] Payment successful for Order {$orderID}");

                    $newPaymentStatus = 'Paid';
                    $newOrderStatus = 'Processing';
                    $returnUrl = "badmintonshop://yourorders?status=success&orderID={$orderID}";

                    // 1. Cập nhật Orders
                    $stmt_update = $mysqli->prepare("UPDATE orders SET paymentStatus = ?, status = ? WHERE orderID = ?");
                    if ($stmt_update) {
                        $stmt_update->bind_param("ssi", $newPaymentStatus, $newOrderStatus, $orderID);
                        $stmt_update->execute();
                        $updateSuccess = ($stmt_update->affected_rows > 0);
                        $stmt_update->close();

                        if ($updateSuccess) {
                            error_log("[VNPAY_UPDATE] Order {$orderID} updated successfully");

                            // 2. Ghi nhận Payments (kiểm tra trùng)
                            $stmt_payment_check = $mysqli->prepare("SELECT paymentID FROM payments WHERE orderID = ?");
                            $stmt_payment_check->bind_param("i", $orderID);
                            $stmt_payment_check->execute();
                            $payment_exists = $stmt_payment_check->get_result()->num_rows > 0;
                            $stmt_payment_check->close();

                            if (!$payment_exists) {
                                $stmt_payment = $mysqli->prepare("
                                    INSERT INTO payments (orderID, transactionCode, amount, paymentGateway, status)
                                    VALUES (?, ?, ?, 'VNPay', 'Success')
                                ");
                                if ($stmt_payment) {
                                    $stmt_payment->bind_param("isd", $orderID, $vnp_TransactionNo, $orderTotalDB);
                                    $stmt_payment->execute();
                                    error_log("[VNPAY_PAYMENT] Payment record inserted");
                                    $stmt_payment->close();
                                }
                            }

                            // 3. Gửi Email
                            try {
                                $orderDataForEmail = [
                                    'orderID' => $orderID,
                                    'totalAmount' => $orderTotalDB,
                                    'shippingAddress' => $order_data['street'] . ', ' . $order_data['city'],
                                    'items' => $productsForEmail
                                ];
                                sendOrderConfirmationEmail($order_data['email'], $order_data['fullName'], $orderDataForEmail);
                                error_log("[VNPAY_EMAIL] Confirmation sent to {$order_data['email']}");
                            } catch (\Throwable $emailE) {
                                error_log("[VNPAY_EMAIL] Failed: " . $emailE->getMessage());
                            }
                        }
                    }

                } else {
                    // ❌ THANH TOÁN THẤT BẠI
                    error_log("[VNPAY_FAILED] Response code: {$vnp_ResponseCode}");

                    $newOrderStatus = 'Cancelled';
                    $newPaymentStatus = 'Unpaid';
                    $returnUrl = "badmintonshop://yourorders?status=failed_cancelled&orderID={$orderID}";

                    // 1. Hủy đơn hàng
                    $stmt_update = $mysqli->prepare("UPDATE orders SET paymentStatus = ?, status = ? WHERE orderID = ?");
                    if ($stmt_update) {
                        $stmt_update->bind_param("ssi", $newPaymentStatus, $newOrderStatus, $orderID);
                        $stmt_update->execute();
                        $stmt_update->close();
                        error_log("[VNPAY_CANCEL] Order {$orderID} cancelled");
                    }

                    // 2. Phục hồi stock
                    $stmt_restore = $mysqli->prepare("UPDATE product_variants SET stock = stock + ?, reservedStock = reservedStock - ? WHERE variantID = ? AND reservedStock >= ?");
                    if ($stmt_restore) {
                        foreach ($variants_to_restore as $item) {
                            $quantity = (int)$item['quantity'];
                            $variantID = (int)$item['variantID'];
                            $stmt_restore->bind_param("iiii", $quantity, $quantity, $variantID, $quantity);
                            $stmt_restore->execute();
                        }
                        $stmt_restore->close();
                        error_log("[VNPAY_RESTORE] Stock restored");
                    }
                }

            } else {
                // ⚠️ Hash không khớp
                error_log("[VNPAY_SECURITY] Hash mismatch! Expected: {$secureHash}, Got: {$vnp_SecureHash}");
                $returnUrl = "badmintonshop://yourorders?status=hash_mismatch&orderID={$orderID}";
            }

            $mysqli->commit();
            error_log("[VNPAY_COMMIT] Transaction committed for Order {$orderID}");

        } else if ($order_data) {
            // Đơn đã xử lý
            error_log("[VNPAY_SKIP] Order {$orderID} already processed (Status: {$order_data['status']})");
            $status_code = strtolower($order_data['status']);
            $returnUrl = "badmintonshop://yourorders?status=already_processed_{$status_code}&orderID={$orderID}";
            $mysqli->rollback();
        } else {
            // Không tìm thấy
            error_log("[VNPAY_NOT_FOUND] Order {$orderID} not found");
            $returnUrl = "badmintonshop://yourorders?status=not_found";
            $mysqli->rollback();
        }

    } catch (Exception $e) {
        $mysqli->rollback();
        error_log("[VNPAY_ERROR] Exception: " . $e->getMessage());
        $returnUrl = "badmintonshop://yourorders?status=error&orderID={$orderID}";
    }
} else {
    error_log("[VNPAY_INVALID] Invalid OrderID: {$orderID} or DB connection failed");
    $returnUrl = "badmintonshop://yourorders?status=invalid_id";
}

error_log("[VNPAY_REDIRECT] Redirecting to: {$returnUrl}");

// 4. CHUYỂN HƯỚNG VỀ APP
header("Location: {$returnUrl}");
exit;