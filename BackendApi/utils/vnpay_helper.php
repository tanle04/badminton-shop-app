<?php
// ⭐ THÔNG TIN CẤU HÌNH VNPAY TEST ⭐
// Cần thay thế các placeholder này bằng thông tin bạn nhận được từ email VNPay.
const VNPAY_TMN_CODE = "BMH8VVU8"; 
const VNPAY_HASH_SECRET = "L84RURSU748VB8FULHKJP12ADCBEZLSJ"; 
// ĐÃ SỬA: Điều chỉnh từ .html sang .htm theo cấu hình chuẩn VNPay
const VNPAY_URL = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html"; 

// QUAN TRỌNG: URL CALLBACK PHẢI LÀ HTTPS CÔNG KHAI CỦA NGROK
// Chú ý: Bạn cần thay thế 'slimiest-unmisgivingly-abdul.ngrok-free.dev' nếu ngrok của bạn đổi domain.
const VNPAY_RETURN_URL = "https://tanbadminton.id.vn/api/payments/vnpay_return.php";
/**
 * Tạo URL thanh toán VNPay với chữ ký bảo mật (SHA512).
 *
 * @param int $orderId ID đơn hàng.
 * @param float $amount Tổng số tiền cần thanh toán.
 * @param string $txnRef Mã tham chiếu giao dịch.
 * @param string $customerEmail Email khách hàng (để theo dõi).
 * @return string URL chuyển hướng đến cổng thanh toán VNPay.
 */
function generateVnPayUrl(int $orderId, float $amount, string $txnRef, string $customerEmail): string {
    // ⭐ ĐÃ THÊM: Thiết lập múi giờ để khắc phục lỗi Time-out
    date_default_timezone_set('Asia/Ho_Chi_Minh'); 

    $vnp_TmnCode = VNPAY_TMN_CODE;
    $vnp_HashSecret = VNPAY_HASH_SECRET;
    $vnp_Url = VNPAY_URL;
    $vnp_ReturnUrl = VNPAY_RETURN_URL;
    $vnp_Amount = $amount * 100; // VNPay tính bằng cent
    // Lấy IP: Quan trọng cho môi trường test
    $vnp_IpAddr = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'; 

    $vnp_Params = array();
    $vnp_Params['vnp_Version'] = '2.1.0';
    $vnp_Params['vnp_Command'] = 'pay';
    $vnp_Params['vnp_TmnCode'] = $vnp_TmnCode;
    $vnp_Params['vnp_Amount'] = $vnp_Amount;
    $vnp_Params['vnp_CurrCode'] = 'VND';
    $vnp_Params['vnp_TxnRef'] = $txnRef;
    // Bổ sung email vào OrderInfo
    $vnp_Params['vnp_OrderInfo'] = "Thanh toan don hang #{$orderId} - Email: {$customerEmail}";
    $vnp_Params['vnp_OrderType'] = 'billpayment';
    $vnp_Params['vnp_Locale'] = 'vn';
    $vnp_Params['vnp_ReturnUrl'] = $vnp_ReturnUrl;
    $vnp_Params['vnp_IpAddr'] = $vnp_IpAddr;
    $vnp_Params['vnp_CreateDate'] = date('YmdHis');
    
    // Sắp xếp tham số theo thứ tự alphabet (BẮT BUỘC)
    ksort($vnp_Params);
    $query = '';
    $hashdata = '';

    foreach ($vnp_Params as $key => $value) {
        // Chỉ thêm tham số vào query và hashdata nếu giá trị khác rỗng và không phải HashSecret
        if ($key !== 'vnp_HashSecret' && $value !== '') { 
            $query .= urlencode($key) . '=' . urlencode($value) . '&';
            $hashdata .= urlencode($key) . '=' . urlencode($value) . '&';
        }
    }
    
    $hashdata = rtrim($hashdata, '&');
    $query = rtrim($query, '&');
    
    // Tính toán chữ ký bảo mật SHA512
    $vnp_SecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
    $vnp_Url .= "?" . $query . '&vnp_SecureHash=' . $vnp_SecureHash;

    return $vnp_Url;
}