<?php
// Bật ghi log lỗi, tắt hiển thị lỗi
error_reporting(E_ALL);
ini_set('display_errors', 0); // Tắt hiển thị lỗi ra trình duyệt
ini_set('log_errors', 1);     // Bật ghi lỗi vào file log
// ini_set('error_log', __DIR__ . '/../../logs/vnpay_ipn_errors.log'); // Đặt đường dẫn file log nếu cần

// Thiết lập múi giờ
date_default_timezone_set('Asia/Ho_Chi_Minh');

$requestTime = date('Y-m-d H:i:s');
error_log("[$requestTime] [VNPAY_IPN] ========== IPN Request Received ==========");
error_log("[$requestTime] [VNPAY_IPN] GET Params: " . print_r($_GET, true));

// Tải các file cần thiết
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../utils/email_helper.php';
require_once __DIR__ . '/../utils/product_helper.php'; // Cần chứa hàm restoreReservedStock()

// --- HẰNG SỐ CẤU HÌNH ---
const VNPAY_HASH_SECRET = "L84RURSU748VB8FULHKJP12ADCBEZLSJ"; // ⭐ Hash Secret cho cổng thanh toán
// ⭐ THÔNG TIN API TRUY VẤN (QUERYDR) CỦA VNPAY - THAY BẰNG THÔNG TIN THẬT ⭐
const VNPAY_TMN_CODE_QUERY = "BMH8VVU8"; // ⭐ TmnCode API truy vấn (có thể giống hoặc khác)
const VNPAY_HASH_SECRET_QUERY = "L84RURSU748VB8FULHKJP12ADCBEZLSJ"; // ⭐ Hash Secret API truy vấn (có thể giống hoặc khác)
const VNPAY_API_URL_QUERY = "https://sandbox.vnpayment.vn/merchant_webapi/api/transaction"; // ⭐ URL API truy vấn

// Mảng phản hồi mặc định cho VNPay
$returnData = [
    'RspCode' => '99',
    'Message' => 'Initial Process Error'
];

// --- HÀM GỌI API QUERYDR ---
// Hàm này thực hiện gọi API truy vấn của VNPay để xác thực trạng thái giao dịch
function callVnPayQueryDr(string $vnp_TxnRef, string $vnp_TransactionDate): array {
    $requestTimeFunc = date('Y-m-d H:i:s'); // Lấy thời gian hiện tại cho log trong hàm
    error_log("[$requestTimeFunc] [VNPAY_IPN] [QueryDR] Starting query for TxnRef: $vnp_TxnRef, TransDate: $vnp_TransactionDate");

    $vnp_RequestId = rand(1000000, 9999999); // ID yêu cầu ngẫu nhiên dài hơn
    $vnp_Version = "2.1.0";
    $vnp_Command = "querydr";
    $vnp_TmnCode = VNPAY_TMN_CODE_QUERY; // Sử dụng TmnCode cho API truy vấn
    $vnp_OrderInfo = 'Kiem tra trang thai giao dich'; // Mô tả ngắn gọn
    $vnp_IpAddr = $_SERVER['SERVER_ADDR'] ?? ($_SERVER['LOCAL_ADDR'] ?? '127.0.0.1'); // IP của server gọi đi
    $vnp_CreateDate = date('YmdHis'); // Thời gian tạo yêu cầu truy vấn

    $hashSecret = VNPAY_HASH_SECRET_QUERY; // Sử dụng Hash Secret cho API truy vấn

    $inputData = array(
        "vnp_RequestId" => $vnp_RequestId,
        "vnp_Version" => $vnp_Version,
        "vnp_Command" => $vnp_Command,
        "vnp_TmnCode" => $vnp_TmnCode,
        "vnp_TxnRef" => $vnp_TxnRef,               // Mã giao dịch cần truy vấn (từ IPN)
        "vnp_OrderInfo" => $vnp_OrderInfo,
        "vnp_TransactionDate" => $vnp_TransactionDate, // Ngày giao dịch (từ IPN vnp_PayDate)
        "vnp_CreateDate" => $vnp_CreateDate,         // Thời gian tạo yêu cầu truy vấn
        "vnp_IpAddr" => $vnp_IpAddr
    );

    ksort($inputData);
    $query = "";
    $hashdata = "";
    $i = 0;
    foreach ($inputData as $key => $value) {
        if ($i == 1) {
            $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
        } else {
            $hashdata .= urlencode($key) . "=" . urlencode($value);
            $i = 1;
        }
        $query .= urlencode($key) . "=" . urlencode($value) . '&';
    }

    $vnp_SecureHash = hash_hmac('sha512', $hashdata, $hashSecret);
    $query .= 'vnp_SecureHash=' . $vnp_SecureHash;
    $url = VNPAY_API_URL_QUERY . "?" . $query;

    error_log("[$requestTimeFunc] [VNPAY_IPN] [QueryDR] Request URL: " . $url);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Set timeout
    // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Bật kiểm tra SSL cho production
    // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    // curl_setopt($ch, CURLOPT_CAINFO, '/path/to/ca-bundle.crt'); // Đường dẫn CA cert nếu cần

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    error_log("[$requestTimeFunc] [VNPAY_IPN] [QueryDR] HTTP Code: " . $httpCode);
    error_log("[$requestTimeFunc] [VNPAY_IPN] [QueryDR] cURL Error: " . $curlError);
    error_log("[$requestTimeFunc] [VNPAY_IPN] [QueryDR] Raw Response: " . $response);

    if ($curlError) {
         error_log("[$requestTimeFunc] [VNPAY_IPN] [QueryDR] FATAL: cURL error: " . $curlError);
         return ['success' => false, 'message' => 'Lỗi kết nối đến VNPay QueryDR.', 'data' => null];
    }
    if ($httpCode != 200 || empty($response)) {
         error_log("[$requestTimeFunc] [VNPAY_IPN] [QueryDR] ERROR: HTTP Code: " . $httpCode . " or empty response.");
         return ['success' => false, 'message' => 'Lỗi truy vấn trạng thái từ VNPay (HTTP ' . $httpCode . ').', 'data' => null];
    }

    // Phân tích response từ VNPay
    $responseData = [];
    parse_str($response, $responseData);
    error_log("[$requestTimeFunc] [VNPAY_IPN] [QueryDR] Parsed Response: " . print_r($responseData, true));

    // Kiểm tra chữ ký của response từ QueryDR
     $vnp_SecureHash_Query = $responseData['vnp_SecureHash'] ?? '';
     unset($responseData['vnp_SecureHash']);
     ksort($responseData);

     $hashData_Query = "";
     $j = 0;
     foreach ($responseData as $key => $value) {
         if ($j == 1) {
             $hashData_Query .= '&' . urlencode($key) . "=" . urlencode($value);
         } else {
             $hashData_Query .= urlencode($key) . "=" . urlencode($value);
             $j = 1;
         }
     }
     $secureHashCheck_Query = hash_hmac('sha512', $hashData_Query, $hashSecret); // Dùng hash secret của QueryDR

     if ($secureHashCheck_Query != $vnp_SecureHash_Query) {
          error_log("[$requestTimeFunc] [VNPAY_IPN] [QueryDR] SECURITY ERROR: Response Signature Mismatch!");
          return ['success' => false, 'message' => 'Lỗi bảo mật khi xác thực phản hồi QueryDR.', 'data' => $responseData];
     }
     error_log("[$requestTimeFunc] [VNPAY_IPN] [QueryDR] Response Signature Verified.");

    // Trả về kết quả và toàn bộ dữ liệu response
    return ['success' => true, 'message' => 'QueryDR call successful.', 'data' => $responseData];
}


// --- BẮT ĐẦU XỬ LÝ IPN CHÍNH ---
try {
    // 1. LẤY VÀ KIỂM TRA THAM SỐ
    $vnp_Params = $_GET;
    $vnp_SecureHash = $vnp_Params['vnp_SecureHash'] ?? '';
    unset($vnp_Params['vnp_SecureHash']); // Loại bỏ hash để kiểm tra

    if (empty($vnp_Params) || empty($vnp_SecureHash)) {
        throw new Exception("IPN: Missing parameters or SecureHash.");
    }

    $vnp_TxnRef = $vnp_Params['vnp_TxnRef'] ?? '';
    $vnp_ResponseCode = $vnp_Params['vnp_ResponseCode'] ?? ''; // Mã từ IPN (chỉ tham khảo)
    $vnp_Amount = (float)($vnp_Params['vnp_Amount'] ?? 0);
    $amountPaid = $vnp_Amount / 100;
    $vnp_PayDate = $vnp_Params['vnp_PayDate'] ?? ''; // Ngày GD YYYYMMDDHHMMSS (Dùng cho QueryDR)
    // $vnp_TransactionDate = $vnp_Params['vnp_TransactionDate'] ?? ''; // Ngày tạo gốc YYYYMMDDHHMMSS (Dùng cho QueryDR)
    // Nếu vnp_TransactionDate không có, thử lấy từ vnp_PayDate (thường giống nhau)
     $vnp_TransactionDateForQuery = $vnp_Params['vnp_TransactionDate'] ?? $vnp_PayDate;


    // --- Tách OrderID ---
    $parts = explode('_', $vnp_TxnRef);
    $orderID = 0;
     if (!empty($parts)) {
        $potentialOrderId = end($parts);
        if (is_numeric($potentialOrderId)) { $orderID = (int)$potentialOrderId; }
    }
    if ($orderID <= 0) {
         throw new Exception("IPN: Invalid OrderID extracted: " . $orderID . " from TxnRef: " . $vnp_TxnRef);
    }
     error_log("[$requestTime] [VNPAY_IPN] Extracted OrderID: " . $orderID);

    // 2. XÁC THỰC CHỮ KÝ IPN
    ksort($vnp_Params);
    $hashData = "";
    $i = 0;
    foreach ($vnp_Params as $key => $value) {
        if ($i == 1) {
            $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
        } else {
            $hashData .= urlencode($key) . "=" . urlencode($value);
            $i = 1;
        }
    }
    $secureHashCheck = hash_hmac('sha512', $hashData, VNPAY_HASH_SECRET); // Dùng HASH SECRET Cổng thanh toán

    if ($secureHashCheck != $vnp_SecureHash) {
        error_log("[$requestTime] [VNPAY_IPN] SECURITY ERROR: Invalid IPN Signature for OrderID: " . $orderID);
        $returnData = ['RspCode' => '97', 'Message' => 'Invalid Signature'];
        echo json_encode($returnData);
        exit;
    }
    error_log("[$requestTime] [VNPAY_IPN] IPN Signature verified for OrderID: " . $orderID);

    // 3. KIỂM TRA ĐƠN HÀNG TRONG DATABASE
    if (!isset($mysqli) || $mysqli->connect_error) { throw new Exception("IPN: Database connection failed."); }

    if (!$mysqli->begin_transaction()) { throw new Exception("IPN: Failed to begin transaction: " . $mysqli->error); }
    error_log("[$requestTime] [VNPAY_IPN] DB Transaction started for Order ID: " . $orderID);

    // Lấy thông tin đơn hàng (khóa bản ghi)
     $stmt_check = $mysqli->prepare("
        SELECT o.orderID, o.customerID, o.paymentStatus, o.status, o.total,
               c.email AS customerEmail, c.fullName AS customerName,
               a.street, a.city
        FROM orders o
        JOIN customers c ON o.customerID = c.customerID
        JOIN customer_addresses a ON o.addressID = a.addressID
        WHERE o.orderID = ? FOR UPDATE
    ");
     if (!$stmt_check) { throw new Exception("IPN: SQL Prepare Error (stmt_check): " . $mysqli->error); }
    $stmt_check->bind_param("i", $orderID);
    if (!$stmt_check->execute()) { throw new Exception("IPN: SQL Execute Error (stmt_check): " . $stmt_check->error); }
    $result = $stmt_check->get_result();
    $order_data = $result->fetch_assoc();
    $stmt_check->close();

    if (!$order_data) {
        error_log("[$requestTime] [VNPAY_IPN] ERROR: OrderID " . $orderID . " not found in database.");
        $mysqli->rollback();
        $returnData = ['RspCode' => '01', 'Message' => 'Order not found'];
        echo json_encode($returnData);
        exit;
    }
    error_log("[$requestTime] [VNPAY_IPN] Order found in DB. Current Status: [" . $order_data['status'] . "], PaymentStatus: [" . $order_data['paymentStatus'] . "]");

    // KIỂM TRA SỐ TIỀN
    $orderTotalDB = (float)$order_data['total'];
    if (abs($orderTotalDB - $amountPaid) > 1) {
        error_log("[$requestTime] [VNPAY_IPN] ERROR: Amount mismatch for OrderID: " . $orderID . ". DB: " . $orderTotalDB . ", Paid: " . $amountPaid);
        $mysqli->rollback();
        $returnData = ['RspCode' => '04', 'Message' => 'Invalid amount'];
        echo json_encode($returnData);
        exit;
    }
    error_log("[$requestTime] [VNPAY_IPN] Amount verified for OrderID: " . $orderID);

    // KIỂM TRA TRẠNG THÁI (Chỉ xử lý nếu đang chờ)
    if ($order_data['status'] !== 'Pending' || $order_data['paymentStatus'] === 'Paid') {
        error_log("[$requestTime] [VNPAY_IPN] INFO: OrderID " . $orderID . " already processed or not pending. Ignoring IPN.");
        $mysqli->rollback();
        $returnData = ['RspCode' => '02', 'Message' => 'Order already confirmed'];
        echo json_encode($returnData);
        exit;
    }
    error_log("[$requestTime] [VNPAY_IPN] Order status is Pending. Proceeding with verification.");


    // 4. ⭐⭐⭐ GỌI API QUERYDR ĐỂ XÁC MINH TRẠNG THÁI CUỐI CÙNG ⭐⭐⭐
    $queryDrResult = callVnPayQueryDr($vnp_TxnRef, $vnp_PayDate); // Gọi hàm đã tạo ở trên

    $isPaymentSuccess = false; // Mặc định là thất bại
    if ($queryDrResult['success'] && isset($queryDrResult['data'])) {
        $queryData = $queryDrResult['data'];
        $query_RspCode = $queryData['vnp_ResponseCode'] ?? '99';
        $query_TransactionStatus = $queryData['vnp_TransactionStatus'] ?? '99';
        $query_Amount = isset($queryData['vnp_Amount']) ? ((float)$queryData['vnp_Amount'] / 100) : -1; // Lấy lại amount từ querydr

        error_log("[$requestTime] [VNPAY_IPN] QueryDR Result - RspCode: $query_RspCode, TransactionStatus: $query_TransactionStatus, Amount: $query_Amount");

        // Chỉ coi là thành công KHI và CHỈ KHI cả 2 mã là 00 VÀ số tiền khớp
        if ($query_RspCode == '00' && $query_TransactionStatus == '00' && abs($orderTotalDB - $query_Amount) <= 1) {
             $isPaymentSuccess = true;
             error_log("[$requestTime] [VNPAY_IPN] QueryDR verification confirms SUCCESS for OrderID: " . $orderID);
        } else {
             error_log("[$requestTime] [VNPAY_IPN] QueryDR verification confirms FAILED or PENDING for OrderID: " . $orderID . ". Status: $query_TransactionStatus, RspCode: $query_RspCode, Amount Match: " . (abs($orderTotalDB - $query_Amount) <= 1 ? 'Yes' : 'NO'));
        }
    } else {
         error_log("[$requestTime] [VNPAY_IPN] ERROR: callVnPayQueryDr failed. Message: " . $queryDrResult['message']);
         // Nếu không gọi được QueryDR -> Coi như thất bại để đảm bảo an toàn
         $isPaymentSuccess = false;
         // Bạn có thể throw Exception ở đây để dừng lại và báo lỗi 99 cho VNPay, hoặc tiếp tục xử lý như thất bại
         // throw new Exception("IPN: Failed to verify transaction with QueryDR.");
    }


    // 5. XỬ LÝ DỰA TRÊN KẾT QUẢ ĐÃ XÁC MINH TỪ QUERYDR
    if ($isPaymentSuccess) {
        // --- GIAO DỊCH THÀNH CÔNG (ĐÃ XÁC MINH) ---
        error_log("[$requestTime] [VNPAY_IPN] VERIFIED SUCCESS: Processing successful payment for OrderID: " . $orderID);
        $newPaymentStatus = 'Paid';
        $newOrderStatus = 'Processing';

        $stmt_update = $mysqli->prepare("UPDATE orders SET paymentStatus = ?, status = ? WHERE orderID = ? AND status = 'Pending'");
        if (!$stmt_update) { throw new Exception("IPN: SQL Prepare Error (update success): " . $mysqli->error); }
        $stmt_update->bind_param("ssi", $newPaymentStatus, $newOrderStatus, $orderID);
        if (!$stmt_update->execute()) { throw new Exception("IPN: SQL Execute Error (update success): " . $stmt_update->error); }
        $updatedRows = $stmt_update->affected_rows;
        $stmt_update->close();

        if ($updatedRows > 0) {
            error_log("[$requestTime] [VNPAY_IPN] SUCCESS: Database updated successfully for OrderID: " . $orderID);

            // Lấy chi tiết sản phẩm để gửi email
            $productsForEmail = [];
            // ... (Code lấy $productsForEmail giữ nguyên như trước) ...
            $stmt_details = $mysqli->prepare("SELECT od.quantity, od.price, p.productName FROM orderdetails od JOIN product_variants pv ON od.variantID = pv.variantID JOIN products p ON pv.productID = p.productID WHERE od.orderID = ?");
             if ($stmt_details) {
                 $stmt_details->bind_param("i", $orderID);
                 $stmt_details->execute();
                 $details_result = $stmt_details->get_result();
                 while ($detail_row = $details_result->fetch_assoc()) { $productsForEmail[] = ['productName' => $detail_row['productName'] ?? 'N/A', 'quantity' => (int)$detail_row['quantity'], 'price' => (float)$detail_row['price']]; }
                 $stmt_details->close();
             } else { error_log("[$requestTime] [VNPAY_IPN] WARNING: Could not prepare SQL to fetch order details for email. OrderID: " . $orderID); }

            // Gửi Email xác nhận
            $orderDataForEmail = [ /* ... (Dữ liệu email giữ nguyên) ... */
                'orderID' => $orderID, 'totalAmount' => $orderTotalDB, 'shippingAddress' => $order_data['street'] . ', ' . $order_data['city'], 'items' => $productsForEmail ];
            try {
                error_log("[$requestTime] [VNPAY_IPN] Attempting to send confirmation email for OrderID: " . $orderID . " to " . $order_data['customerEmail']);
                $emailSent = sendOrderConfirmationEmail($order_data['customerEmail'], $order_data['customerName'], $orderDataForEmail);
                error_log("[$requestTime] [VNPAY_IPN] Email send status for OrderID " . $orderID . ": " . ($emailSent ? 'Success' : 'Failed'));
            } catch (\Throwable $emailE) { error_log("[$requestTime] [VNPAY_IPN] ERROR sending email for OrderID " . $orderID . ": " . $emailE->getMessage()); }

            $returnData = ['RspCode' => '00', 'Message' => 'Confirm Success']; // Phản hồi thành công

        } else {
            error_log("[$requestTime] [VNPAY_IPN] WARNING: Update query (success) affected 0 rows for OrderID: " . $orderID . ". Re-checking status.");
            // Kiểm tra lại trạng thái, có thể đã được xử lý bởi IPN khác
             $stmt_recheck = $mysqli->prepare("SELECT status, paymentStatus FROM orders WHERE orderID = ?");
             if ($stmt_recheck) {
                 $stmt_recheck->bind_param("i", $orderID); $stmt_recheck->execute();
                 $final_order_status = $stmt_recheck->get_result()->fetch_assoc(); $stmt_recheck->close();
                 if ($final_order_status && ($final_order_status['status'] === 'Processing' || $final_order_status['paymentStatus'] === 'Paid')) {
                      $returnData = ['RspCode' => '00', 'Message' => 'Confirm Success (already processed)']; // Vẫn báo thành công
                 } else { $returnData = ['RspCode' => '99', 'Message' => 'Failed to update order status in DB']; } // Lỗi DB
             } else { $returnData = ['RspCode' => '99', 'Message' => 'Failed to re-check order status']; } // Lỗi DB
        }

    } else {
        // --- GIAO DỊCH THẤT BẠI (ĐÃ XÁC MINH TỪ QUERYDR HOẶC QUERYDR LỖI) ---
        error_log("[$requestTime] [VNPAY_IPN] VERIFIED FAILED/CANCELLED: Processing failed payment for OrderID: " . $orderID . ". QueryDR Message: " . ($queryDrResult['message'] ?? 'QueryDR Call Failed'));
        $newOrderStatus = 'Cancelled';
        $newPaymentStatus = 'Failed'; // Đánh dấu là Failed rõ ràng

        $stmt_update = $mysqli->prepare("UPDATE orders SET paymentStatus = ?, status = ? WHERE orderID = ? AND status = 'Pending'");
        if (!$stmt_update) { throw new Exception("IPN: SQL Prepare Error (update failed): " . $mysqli->error); }
        $stmt_update->bind_param("ssi", $newPaymentStatus, $newOrderStatus, $orderID);
        if (!$stmt_update->execute()) { throw new Exception("IPN: SQL Execute Error (update failed): " . $stmt_update->error); }
        $updatedRows = $stmt_update->affected_rows;
        $stmt_update->close();

        if ($updatedRows > 0) {
            error_log("[$requestTime] [VNPAY_IPN] OrderID: " . $orderID . " status updated to Cancelled/Failed.");
            // Phục hồi reserved stock
             if (function_exists('restoreReservedStock')) {
                 error_log("[$requestTime] [VNPAY_IPN] Calling restoreReservedStock for OrderID: " . $orderID);
                restoreReservedStock($mysqli, $orderID);
             } else { error_log("[$requestTime] [VNPAY_IPN] WARNING: Function restoreReservedStock not found for OrderID: " . $orderID); }
        } else { error_log("[$requestTime] [VNPAY_IPN] WARNING: Update query (failed) affected 0 rows for OrderID: " . $orderID . ". Possibly already cancelled."); }

        // Vẫn phản hồi 00 cho VNPay vì đã xử lý IPN
        $returnData = ['RspCode' => '00', 'Message' => 'Confirm Success'];
    }

    // Hoàn tất transaction
    if (!$mysqli->commit()) {
         error_log("[$requestTime] [VNPAY_IPN] FATAL ERROR: Failed to commit transaction for OrderID: " . $orderID);
         // Không thay đổi $returnData được nữa, chỉ log
    } else {
         error_log("[$requestTime] [VNPAY_IPN] Transaction committed successfully for OrderID: " . $orderID);
    }

} catch (Exception $e) {
    // Xử lý lỗi Exception chung
    error_log("[$requestTime] [VNPAY_IPN] EXCEPTION caught for OrderID " . ($orderID ?? 'N/A') . ": " . $e->getMessage());
    if (isset($mysqli) && $mysqli->thread_id && $mysqli->ping()) { // Kiểm tra kết nối trước khi rollback
         if (method_exists($mysqli, 'in_transaction') && $mysqli->in_transaction) { // Kiểm tra transaction state (PHP >= 8)
             $mysqli->rollback();
             error_log("[$requestTime] [VNPAY_IPN] Transaction rolled back due to exception.");
         } elseif (!method_exists($mysqli, 'in_transaction')) {
              // Fallback cho PHP < 8 (không chắc chắn 100%)
              @$mysqli->rollback(); // Thử rollback
              error_log("[$requestTime] [VNPAY_IPN] Attempted rollback (PHP < 8) due to exception.");
         }
    }
    // Phản hồi lỗi chung cho VNPay
    $returnData = ['RspCode' => '99', 'Message' => 'Internal Server Error'];
}

// Luôn gửi phản hồi cuối cùng cho VNPay
$jsonResponse = json_encode($returnData);
error_log("[$requestTime] [VNPAY_IPN] Final Response to VNPay: " . $jsonResponse);
echo $jsonResponse;
exit; // Dừng script hoàn toàn

?>
