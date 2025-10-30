<?php
// TẮT DEBUG: CHUYỂN SANG MÔI TRƯỜNG HOẠT ĐỘNG BÌNH THƯỜNG (Production ready)
error_reporting(0);
ini_set('display_errors', 0);

// Thiết lập múi giờ
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Tải các file cần thiết
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../utils/email_helper.php';
require_once __DIR__ . '/../utils/product_helper.php';

// HẰNG SỐ CẤU HÌNH (Đảm bảo HASH SECRET chính xác)
const VNPAY_HASH_SECRET = "L84RURSU748VB8FULHKJP12ADCBEZLSJ";

// 1. KHỞI TẠO VÀ LẤY THAM SỐ
$vnp_Params = $_GET;
$vnp_SecureHash = $vnp_Params['vnp_SecureHash'] ?? '';
$vnp_TxnRef = $vnp_Params['vnp_TxnRef'] ?? '';
$vnp_ResponseCode = $vnp_Params['vnp_ResponseCode'] ?? '';
// Lấy thêm mã giao dịch VNPay
$vnp_TransactionNo = $vnp_Params['vnp_TransactionNo'] ?? '';

// Chuyển về số thực để đảm bảo tính toán chính xác
$vnp_Amount = (float)($vnp_Params['vnp_Amount'] ?? 0);
$amountPaid = $vnp_Amount / 100; // Số tiền thực tế đã thanh toán (đơn vị: VND)

// --- SỬA LỖI LẤY ORDERID TỪ vnp_TxnRef ---
$parts = explode('_', $vnp_TxnRef); // Ví dụ: "ORDER_96_1761742850"
$orderID = 0;
// Cần ít nhất 2 phần tử (index 0: ORDER, index 1: 96)
if (count($parts) >= 2) {
    $orderID = (int)$parts[1]; // Lấy OrderID thực tế (96)
    error_log("[VNPAY_DEBUG] Extracted OrderID: {$orderID} from TxnRef: {$vnp_TxnRef}");
} else {
    error_log("[VNPAY_DEBUG] Cannot extract OrderID from TxnRef: {$vnp_TxnRef}");
}
// ---------------------------------
// ---------------------------------

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

// Mặc định chuyển hướng về thất bại
$returnUrl = "badmintonshop://yourorders?status=failed_cancelled&orderID={$orderID}";
$mysqli_connected = (isset($mysqli) && !$mysqli->connect_error);

// 3. XỬ LÝ LOGIC CHÍNH
if ($orderID > 0) {
    // Ghi log bắt đầu xử lý
    error_log("[VNPAY_RETURN_START] OrderID: {$orderID}, TxnRef: {$vnp_TxnRef}, ResponseCode: {$vnp_ResponseCode}");

    // Kiểm tra kết nối database
    if (!$mysqli_connected) {
        error_log("[VNPAY_RETURN] FATAL ERROR: Database connection failed.");
    } else {

        // Bắt đầu Transaction
        $mysqli->begin_transaction();

        // Lấy thông tin đơn hàng, chi tiết sản phẩm và khách hàng (FOR UPDATE)
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

        // CHẶN KHI PREPARE LỖI
        if (!$stmt_check) {
            error_log("[VNPAY_RETURN] SQL Prepare Error (stmt_check): " . $mysqli->error);
            $mysqli->rollback();
        } else {
            $stmt_check->bind_param("i", $orderID);
            $stmt_check->execute();
            $result = $stmt_check->get_result();

            $order_data = null;
            $variants_to_restore = [];
            $productsForEmail = [];
            $orderTotalDB = 0.0;

            // Chỉ fetch_assoc nếu get_result() thành công
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    if (!$order_data) {
                        $order_data = $row;
                        $orderTotalDB = (float)$row['total'];
                    }

                    // Ghi nhận thông tin sản phẩm (Cho Email và Khôi phục Stock)
                    $variants_to_restore[] = ['variantID' => $row['variantID'], 'quantity' => $row['quantity']];
                    $productsForEmail[] = [
                        'productName' => $row['productName'] ?? 'Sản phẩm không tên',
                        'quantity' => (int)$row['quantity'],
                        'price' => (float)$row['price']
                    ];
                }
            }
            $stmt_check->close();

            // Ghi log dữ liệu đơn hàng lấy từ DB
            error_log("[VNPAY_DB_DATA] Status: " . ($order_data['status'] ?? 'N/A') . ", Total DB: {$orderTotalDB}, Total Paid: {$amountPaid}");

            // Kiểm tra tính hợp lệ của đơn hàng (chỉ xử lý nếu tồn tại và đang là Pending)
            if ($order_data && $order_data['status'] === 'Pending' && $orderTotalDB > 0) {

                // ⭐ BƯỚC KIỂM TRA QUAN TRỌNG NHẤT: XÁC MINH HASH VÀ MÃ PHẢN HỒI
                if ($secureHash == $vnp_SecureHash) {

                    // Ghi log Hash thành công
                    error_log("[VNPAY_HASH_SUCCESS] SecureHash Verified.");

                    // 3.1. KIỂM TRA SỐ TIỀN THANH TOÁN (Cho phép sai số 1 VND để tránh lỗi float)
                    $epsilon = 1;
                    if (abs($orderTotalDB - $amountPaid) > $epsilon) {
                        error_log("[VNPAY_ERROR] Amount mismatch for OrderID: {$orderID}. DB: {$orderTotalDB}, Paid: {$amountPaid}");
                        $vnp_ResponseCode = '01'; // Buộc xử lý như thất bại
                    }

                    if ($vnp_ResponseCode == '00') {
                        // ⭐ TRƯỜNG HỢP THÀNH CÔNG: CẬP NHẬT DB, GHI NHẬN THANH TOÁN, VÀ GỬI EMAIL

                        // Ghi log trước khi update DB
                        error_log("[VNPAY_PROCESSING] Updating order {$orderID} to Paid/Processing.");

                        $newPaymentStatus = 'Paid';
                        $newOrderStatus = 'Processing';
                        $returnUrl = "badmintonshop://yourorders?status=success&orderID={$orderID}";

                        // 1. Cập nhật trạng thái ORDERS
                        $stmt_update = $mysqli->prepare("UPDATE orders SET paymentStatus = ?, status = ? WHERE orderID = ?");

                        $isUpdateSuccessful = false;
                        if (!$stmt_update) {
                            error_log("[VNPAY_RETURN] SQL Prepare Error (stmt_update): " . $mysqli->error);
                        } else {
                            $stmt_update->bind_param("ssi", $newPaymentStatus, $newOrderStatus, $orderID);
                            $stmt_update->execute();
                            $isUpdateSuccessful = ($stmt_update->affected_rows > 0);
                            $stmt_update->close();

                            if ($isUpdateSuccessful) {
                                error_log("[VNPAY_UPDATE_SUCCESS] Order {$orderID} updated successfully.");
                            } else {
                                error_log("[VNPAY_UPDATE_FAIL] Order {$orderID} update failed (0 affected rows). Last error: " . $mysqli->error);
                            }
                        }

                        // 2. GHI NHẬN GIAO DỊCH VÀO BẢNG PAYMENTS (Nếu update order thành công)
                        if ($isUpdateSuccessful) {
                            // Kiểm tra để tránh trùng lặp nếu có retry
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
                                    // Sử dụng $orderTotalDB làm số tiền chính thức
                                    $stmt_payment->bind_param("isd", $orderID, $vnp_TransactionNo, $orderTotalDB);
                                    $stmt_payment->execute();
                                    error_log("[VNPAY_PAYMENT_INSERT] Payment record inserted for OrderID {$orderID}, TxnNo: {$vnp_TransactionNo}.");
                                    $stmt_payment->close();
                                } else {
                                    error_log("[VNPAY_RETURN] SQL Prepare Error (stmt_payment): " . $mysqli->error);
                                }
                            } else {
                                error_log("[VNPAY_PAYMENT_SKIP] Payment record already exists for OrderID {$orderID}. Skipping insert.");
                            }
                        }

                        // 3. Gửi Email (Chỉ gửi nếu update order thành công)
                        if ($isUpdateSuccessful) {
                            $orderDataForEmail = [
                                'orderID' => $orderID,
                                'totalAmount' => $orderTotalDB,
                                'shippingAddress' => $order_data['street'] . ', ' . $order_data['city'],
                                'items' => $productsForEmail
                            ];
                            try {
                                sendOrderConfirmationEmail($order_data['email'], $order_data['fullName'], $orderDataForEmail);
                                error_log("[VNPAY_EMAIL_SUCCESS] Confirmation email sent to {$order_data['email']}.");
                            } catch (\Throwable $emailE) {
                                // GHI LOG LỖI GỬI EMAIL VÀ BỎ QUA
                                error_log("[VNPAY_RETURN] Email Failed: " . $emailE->getMessage());
                            }
                        }
                    } else {
                        // ⭐ TRƯỜNG HỢP THẤT BẠI
                        error_log("[VNPAY_FAILED] Response code '{$vnp_ResponseCode}' received or Amount Mismatch forced failure.");

                        $newOrderStatus = 'Cancelled';
                        $newPaymentStatus = 'Unpaid';
                        $returnUrl = "badmintonshop://yourorders?status=failed_cancelled&orderID={$orderID}";

                        // 1. Hủy đơn hàng
                        $stmt_update = $mysqli->prepare("UPDATE orders SET paymentStatus = ?, status = ? WHERE orderID = ?");
                        if ($stmt_update) {
                            $stmt_update->bind_param("ssi", $newPaymentStatus, $newOrderStatus, $orderID);
                            $stmt_update->execute();
                            $stmt_update->close();
                            error_log("[VNPAY_CANCEL] Order {$orderID} marked as Cancelled/Unpaid.");
                        }

                        // 2. Phục hồi reserved stock (Dự trữ)
                        $stmt_restore = $mysqli->prepare("UPDATE product_variants SET stock = stock + ?, reservedStock = reservedStock - ? WHERE variantID = ? AND reservedStock >= ?");
                        if ($stmt_restore) {
                            foreach ($variants_to_restore as $item) {
                                $quantity = (int)$item['quantity'];
                                $variantID = (int)$item['variantID'];
                                $stmt_restore->bind_param("iiii", $quantity, $quantity, $variantID, $quantity);
                                $stmt_restore->execute();
                            }
                            $stmt_restore->close();
                            error_log("[VNPAY_RESTORE] Reserved stock restored for OrderID {$orderID}.");
                        } else {
                            error_log("[VNPAY_RETURN] SQL Error: Cannot prepare stmt_restore: " . $mysqli->error);
                        }
                    }
                } else {
                    // Lỗi Hash không khớp (Giả mạo)
                    error_log("[VNPAY_SECURITY_ERROR] Hash mismatch for OrderID: {$orderID}. Calculated: {$secureHash}, Received: {$vnp_SecureHash}");
                    $returnUrl = "badmintonshop://yourorders?status=hash_mismatch";
                    // Giữ nguyên Pending để Admin kiểm tra
                }

                $mysqli->commit(); // Hoàn tất giao dịch
                error_log("[VNPAY_COMMIT_SUCCESS] Transaction committed for OrderID {$orderID}.");
            } else if ($order_data) {
                // Đã được xử lý (trạng thái khác Pending)
                error_log("[VNPAY_SKIP] Order {$orderID} already processed (Status: {$order_data['status']}).");
                $status_code = strtolower($order_data['status']);
                $returnUrl = "badmintonshop://yourorders?status=already_processed_{$status_code}&orderID={$orderID}";
            } else {
                // Đơn hàng không tồn tại
                error_log("[VNPAY_NOT_FOUND] OrderID {$orderID} not found in DB.");
                $returnUrl = "badmintonshop://yourorders?status=not_found";
            }
        }
    }
} else {
    // OrderID không hợp lệ
    error_log("[VNPAY_INVALID_ID] Invalid OrderID extracted: {$orderID}.");
    $returnUrl = "badmintonshop://yourorders?status=invalid_id";
}

error_log("[VNPAY_REDIRECT] Redirecting to: {$returnUrl}");

// 4. CHUYỂN HƯỚNG CUỐI CÙNG VỀ ỨNG DỤNG ANDROID
header("Location: {$returnUrl}");
exit;
