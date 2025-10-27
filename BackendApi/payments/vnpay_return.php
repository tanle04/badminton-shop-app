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
$vnp_Amount = (float)($vnp_Params['vnp_Amount'] ?? 0); // Lấy giá trị gốc VNPay gửi (đơn vị: cent)
$amountPaid = $vnp_Amount / 100; // Số tiền thực tế đã thanh toán (đơn vị: VND)

// --- ĐÃ SỬA LỖI TÁCH ORDERID ---
$parts = explode('_', $vnp_TxnRef);
$orderID = 0;
if (!empty($parts) && count($parts) > 1) {
    // Lấy phần tử cuối cùng của mảng, giả định đó là OrderID.
    $orderID = (int)end($parts); 
}
// --- KẾT THÚC SỬA LỖI TÁCH ORDERID ---

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


// 3. XỬ LÝ LOGIC CHÍNH
if ($orderID > 0) {
    // Kiểm tra kết nối database
    if (!isset($mysqli) || $mysqli->connect_error) {
        error_log("[VNPAY_RETURN] FATAL ERROR: Database connection failed.");
        // Giữ $returnUrl mặc định hoặc có thể chuyển hướng về lỗi chung
    } else {
        $mysqli->begin_transaction();
        
        // Lấy thông tin đơn hàng, chi tiết sản phẩm và khách hàng
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
            error_log("[VNPAY_RETURN] SQL Prepare Error (stmt_check): " . $mysqli->error);
            // Tiếp tục xử lý thất bại
        } else {
            $stmt_check->bind_param("i", $orderID);
            $stmt_check->execute();
            $result = $stmt_check->get_result();
            
            $order_data = null;
            $variants_to_restore = [];
            $productsForEmail = [];
            $orderTotalDB = 0.0;
            
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
            $stmt_check->close();
            
            // Kiểm tra tính hợp lệ của đơn hàng (chỉ xử lý nếu tồn tại và đang là Pending)
            if ($order_data && $order_data['status'] === 'Pending' && $orderTotalDB > 0) {
                
                // ⭐ BƯỚC KIỂM TRA QUAN TRỌNG NHẤT: XÁC MINH HASH VÀ MÃ PHẢN HỒI
                if ($secureHash == $vnp_SecureHash) {
                    
                    // 3.1. KIỂM TRA SỐ TIỀN THANH TOÁN
                    if (abs($orderTotalDB - $amountPaid) > 1) { 
                        error_log("[VNPAY_ERROR] Amount mismatch for OrderID: {$orderID}. DB: {$orderTotalDB}, Paid: {$amountPaid}");
                        $vnp_ResponseCode = '01'; // Buộc xử lý như thất bại
                    }
                    
                    if ($vnp_ResponseCode == '00') {
                        // ⭐ TRƯỜNG HỢP THÀNH CÔNG: CẬP NHẬT DB VÀ GỬI EMAIL
                        $newPaymentStatus = 'Paid';
                        $newOrderStatus = 'Processing'; 
                        $returnUrl = "badmintonshop://yourorders?status=success&orderID={$orderID}";
                        
                        // 1. Cập nhật trạng thái
                        $stmt_update = $mysqli->prepare("UPDATE orders SET paymentStatus = ?, status = ? WHERE orderID = ?");
                        
                        if (!$stmt_update) {
                            error_log("[VNPAY_RETURN] SQL Prepare Error (stmt_update): " . $mysqli->error);
                        } else {
                            $stmt_update->bind_param("ssi", $newPaymentStatus, $newOrderStatus, $orderID);
                            $stmt_update->execute();
                            $isUpdateSuccessful = ($stmt_update->affected_rows > 0);
                            $stmt_update->close();
                            
                            // 2. Gửi Email
                            if ($isUpdateSuccessful) {
                                $orderDataForEmail = [
                                    'orderID' => $orderID,
                                    'totalAmount' => $orderTotalDB, 
                                    'shippingAddress' => $order_data['street'] . ', ' . $order_data['city'], 
                                    'items' => $productsForEmail
                                ];
                                try {
                                    sendOrderConfirmationEmail($order_data['email'], $order_data['fullName'], $orderDataForEmail);
                                } catch (\Throwable $emailE) {
                                    // GHI LOG LỖI GỬI EMAIL
                                    error_log("[VNPAY_RETURN] Email Failed: " . $emailE->getMessage());
                                }
                            }
                        }
                        
                    } else {
                        // ⭐ TRƯỜNG HỢP THẤT BẠI
                        $newOrderStatus = 'Cancelled'; 
                        $newPaymentStatus = 'Unpaid';
                        $returnUrl = "badmintonshop://yourorders?status=failed_cancelled&orderID={$orderID}";
                        
                        // 1. Hủy đơn hàng và 2. Phục hồi reserved stock (Không thay đổi)
                        $stmt_update = $mysqli->prepare("UPDATE orders SET paymentStatus = ?, status = ? WHERE orderID = ?");
                        $stmt_update->bind_param("ssi", $newPaymentStatus, $newOrderStatus, $orderID);
                        $stmt_update->execute();
                        $stmt_update->close();

                        $stmt_restore = $mysqli->prepare("UPDATE product_variants SET stock = stock + ?, reservedStock = reservedStock - ? WHERE variantID = ? AND reservedStock >= ?");
                        if ($stmt_restore) {
                            foreach ($variants_to_restore as $item) {
                                $quantity = (int)$item['quantity'];
                                $variantID = (int)$item['variantID'];
                                $stmt_restore->bind_param("iiii", $quantity, $quantity, $variantID, $quantity);
                                $stmt_restore->execute();
                            }
                            $stmt_restore->close();
                        } else {
                            error_log("[VNPAY_RETURN] SQL Error: Cannot prepare stmt_restore: " . $mysqli->error);
                        }
                    }
                    
                } else {
                    // Lỗi Hash không khớp (Giả mạo)
                    error_log("[VNPAY_SECURITY_ERROR] Hash mismatch for OrderID: {$orderID}");
                    $returnUrl = "badmintonshop://yourorders?status=hash_mismatch";
                }
                
                $mysqli->commit();

            } else if ($order_data) {
                // Đã được xử lý (trạng thái khác Pending)
                $returnUrl = "badmintonshop://yourorders?status=already_processed&orderID={$orderID}";
            } else {
                // Đơn hàng không tồn tại
                $returnUrl = "badmintonshop://yourorders?status=not_found";
            }
        }
    }
    
} else {
    // OrderID không hợp lệ
    $returnUrl = "badmintonshop://yourorders?status=invalid_id";
}


// 4. CHUYỂN HƯỚNG CUỐI CÙNG VỀ ỨNG DỤNG ANDROID (ĐÃ BẬT LẠI)
header("Location: {$returnUrl}");
exit;
?>