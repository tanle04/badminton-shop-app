<?php
// ⭐ SỬA LỖI: Đường dẫn đúng là lùi 1 CẤP (từ /utils về /api), không phải 2
require_once __DIR__ . '/../vendor/autoload.php'; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Gửi email xác nhận đơn hàng...
 * (Hàm gốc của bạn - Giữ nguyên)
 */
function sendOrderConfirmationEmail(string $recipientEmail, string $recipientName, array $orderData): bool {
    $mail = new PHPMailer(true);
    try {
        // Cấu hình SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'tan07112004@gmail.com'; 
        $mail->Password   = 'tthb azje kcax bkpa'; // Mật khẩu ứng dụng
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
        $mail->Port       = 465; 
        $mail->CharSet = 'UTF-8';

        // Cài đặt Người gửi
        $mail->setFrom('noreply@badmintonshop.com', 'Badminton Shop');
        $mail->addAddress($recipientEmail, $recipientName);

        // Chuẩn bị danh sách sản phẩm
        $itemsHtml = '<ul>';
        foreach ($orderData['items'] as $item) {
            $itemsHtml .= "<li>{$item['productName']} (x{$item['quantity']}) - " . number_format($item['price'] * $item['quantity']) . " VNĐ</li>";
        }
        $itemsHtml .= '</ul>';

        // Nội dung Email
        $mail->isHTML(true);
        $mail->Subject = 'Xác nhận Đơn hàng #' . $orderData['orderID'] . ' của bạn';
        $mail->Body    = "Xin chào <b>{$recipientName}</b>,<br><br>"
                       . "Cảm ơn bạn đã đặt hàng! Dưới đây là tóm tắt đơn hàng của bạn:<br><br>"
                       . "<b>Mã đơn hàng:</b> #{$orderData['orderID']}<br>"
                       . "<b>Tổng tiền:</b> " . number_format($orderData['totalAmount']) . " VNĐ<br>"
                       . "<b>Địa chỉ giao hàng:</b> {$orderData['shippingAddress']}<br><br>"
                       . "<b>Chi tiết sản phẩm:</b>{$itemsHtml}<br>"
                       . "Chúng tôi đang xử lý đơn hàng của bạn và sẽ thông báo khi hàng được giao.";
        
        $mail->AltBody = "Cảm ơn bạn đã đặt hàng! Mã đơn hàng: {$orderData['orderID']}. Tổng tiền: {$orderData['totalAmount']} VNĐ.";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Order Confirmation Email failed to send to {$recipientEmail}. Error: {$e->getMessage()} | Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Gửi email thông báo hủy đơn hàng
 * (Hàm gốc của bạn - Giữ nguyên)
 */
function sendCancellationEmail(string $recipientEmail, string $recipientName, int $orderID): bool {
    $mail = new PHPMailer(true);
    try {
        // Cấu hình SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'tan07112004@gmail.com'; 
        $mail->Password   = 'tthb azje kcax bkpa'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
        $mail->Port       = 465; 
        $mail->CharSet = 'UTF-8';

        // Cài đặt Người gửi
        $mail->setFrom('noreply@badmintonshop.com', 'Badminton Shop');
        $mail->addAddress($recipientEmail, $recipientName);

        // Nội dung Email
        $mail->isHTML(true);
        $mail->Subject = 'Thông báo HỦY Đơn hàng #' . $orderID;
        $mail->Body    = "Xin chào <b>{$recipientName}</b>,<br><br>"
                       . "Chúng tôi xin thông báo rằng Đơn hàng **#{$orderID}** của bạn đã được hủy theo yêu cầu của bạn. <br>"
                       . "Tồn kho đã được phục hồi. Bạn có thể đặt hàng lại bất cứ lúc nào.<br><br>"
                       . "Cảm ơn bạn đã tin tưởng Cửa hàng Cầu lông!";
        
        $mail->AltBody = "Đơn hàng #{$orderID} đã bị hủy. Tồn kho đã được phục hồi.";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Cancellation Email failed to send to {$recipientEmail}. Error: {$e->getMessage()} | Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Gửi email cảm ơn khi trạng thái đơn hàng chuyển sang 'Delivered'.
 * (Hàm gốc của bạn - Giữ nguyên)
 */
function sendDeliveryConfirmationEmail(string $recipientEmail, string $recipientName, int $orderID): bool {
    $mail = new PHPMailer(true);
    try {
        // Cấu hình SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'tan07112004@gmail.com'; 
        $mail->Password   = 'tthb azje kcax bkpa'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
        $mail->Port       = 465; 
        $mail->CharSet = 'UTF-8';

        // Cài đặt Người gửi
        $mail->setFrom('noreply@badmintonshop.com', 'Badminton Shop');
        $mail->addAddress($recipientEmail, $recipientName);

        // Nội dung Email
        $mail->isHTML(true);
        $mail->Subject = 'Đơn hàng #' . $orderID . ' đã được giao thành công!';
        $mail->Body    = "Xin chào <b>{$recipientName}</b>,<br><br>"
                       . "Chúng tôi xin thông báo rằng Đơn hàng **#{$orderID}** của bạn đã được giao thành công. <br>"
                       . "Cảm ơn bạn đã tin tưởng và ủng hộ Cửa hàng Cầu lông!<br><br>"
                       . "Chúng tôi hy vọng bạn hài lòng với sản phẩm của mình. Nếu có bất kỳ vấn đề gì, đừng ngần ngại liên hệ với chúng tôi.";
        
        $mail->AltBody = "Đơn hàng #{$orderID} đã được giao thành công. Cảm ơn bạn!";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Delivery Confirmation Email failed to send to {$recipientEmail}. Error: {$e->getMessage()} | Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
function sendVerificationEmail(string $recipientEmail, string $recipientName, string $verificationLink): bool {
    $mail = new PHPMailer(true);
    try {
        // Cấu hình SMTP (sao chép từ các hàm khác của bạn)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'tan07112004@gmail.com'; // Email của bạn
        $mail->Password   = 'tthb azje kcax bkpa'; // Mật khẩu ứng dụng của bạn
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
        $mail->Port       = 465; 
        $mail->CharSet = 'UTF-8';

        // Cài đặt Người gửi
        $mail->setFrom('noreply@badmintonshop.com', 'Badminton Shop');
        $mail->addAddress($recipientEmail, $recipientName);

        // Nội dung Email
        $mail->isHTML(true);
        $mail->Subject = 'Kích hoạt tài khoản Badminton Shop của bạn';
        $mail->Body    = "<div style='font-family: Arial, sans-serif; line-height: 1.6;'>
                            <h2>Chào mừng, <b>{$recipientName}</b>!</h2>"
                         . "<p>Cảm ơn bạn đã đăng ký tài khoản tại Badminton Shop. Vui lòng nhấp vào liên kết bên dưới để kích hoạt tài khoản của bạn:</p>"
                         . "<p style='text-align: center; margin: 20px 0;'>
                                <a href='{$verificationLink}' 
                                   style='background-color: #007BFF; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-size: 16px;'>
                                   Kích hoạt Tài khoản
                                </a>
                            </p>"
                         . "<p>Nếu nút không hoạt động, bạn cũng có thể sao chép và dán URL này vào trình duyệt:</p>"
                         . "<p style='word-break: break-all;'>{$verificationLink}</p>"
                         . "<p>Liên kết này sẽ hết hạn sau 24 giờ.</p>"
                         . "<p>Nếu bạn không đăng ký, vui lòng bỏ qua email này.</p><br>"
                         . "<p>Trân trọng,<br>Đội ngũ BadmintonShop</p></div>";
        
        $mail->AltBody = "Chào mừng {$recipientName}! Vui lòng kích hoạt tài khoản của bạn bằng cách truy cập liên kết sau: {$verificationLink}";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        // Ghi lại lỗi chi tiết để debug
        error_log("Verification Email failed to send to {$recipientEmail}. Error: {$e->getMessage()} | Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
function sendOtpEmail(string $recipientEmail, string $otp): bool {
    $mail = new PHPMailer(true);
    try {
        // Cấu hình SMTP (sao chép từ các hàm khác của bạn)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'tan07112004@gmail.com'; 
        $mail->Password   = 'tthb azje kcax bkpa'; // Mật khẩu ứng dụng
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
        $mail->Port       = 465; 
        $mail->CharSet = 'UTF-8';

        // Cài đặt Người gửi/Người nhận
        $mail->setFrom('noreply@badmintonshop.com', 'Badminton Shop');
        $mail->addAddress($recipientEmail); // Không cần tên

        // Nội dung Email
        $mail->isHTML(true);
        $mail->Subject = 'Mã OTP Đặt Lại Mật Khẩu BadmintonShop';
        $mail->Body    = "<div style='font-family: Arial, sans-serif; line-height: 1.6;'>
                        <h2>Xin chào,</h2>"
                       . "<p>Bạn đã yêu cầu đặt lại mật khẩu cho tài khoản BadmintonShop. Mã OTP của bạn là:</p>"
                       . "<h1 style='font-size: 36px; letter-spacing: 5px; color: #007BFF; text-align: center;'><b>{$otp}</b></h1>"
                       . "<p>Mã này sẽ hết hạn sau 10 phút. Vui lòng không chia sẻ mã này cho bất kỳ ai.</p>"
                       . "<p>Nếu bạn không yêu cầu điều này, vui lòng bỏ qua email này.</p>"
                       . "<br><p>Trân trọng,<br>Đội ngũ BadmintonShop</p></div>";
        
        $mail->AltBody = "Mã OTP đặt lại mật khẩu của bạn là: {$otp}. Mã sẽ hết hạn sau 10 phút.";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("OTP Email failed to send to {$recipientEmail}. Error: {$e->getMessage()} | Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// -----------------------------------------------------------------
// ⭐ BỔ SUNG 2 HÀM MỚI CHO NGHIỆP VỤ TRẢ HÀNG
// -----------------------------------------------------------------

/**
 * ⭐ HÀM MỚI 1: Gửi email XÁC NHẬN YÊU CẦU (Khi khách vừa bấm nút)
 */
function sendRefundRequestConfirmationEmail(string $recipientEmail, string $recipientName, int $orderID): bool {
    $mail = new PHPMailer(true);
    try {
        // Cấu hình SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'tan07112004@gmail.com'; 
        $mail->Password   = 'tthb azje kcax bkpa'; // Mật khẩu ứng dụng
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
        $mail->Port       = 465; 
        $mail->CharSet = 'UTF-8';

        // Cài đặt Người gửi
        $mail->setFrom('noreply@badmintonshop.com', 'Badminton Shop');
        $mail->addAddress($recipientEmail, $recipientName);

        // Nội dung Email
        $mail->isHTML(true);
        $mail->Subject = "Đã nhận yêu cầu trả hàng cho đơn hàng #{$orderID}";
        $mail->Body    = "Chào <b>{$recipientName}</b>,<br><br>"
                       . "Chúng tôi đã nhận được yêu cầu trả hàng/hoàn tiền của bạn cho đơn hàng <b>#{$orderID}</b>.<br>"
                       . "Yêu cầu của bạn đang được xem xét. Chúng tôi sẽ thông báo cho bạn ngay khi có kết quả.<br><br>"
                       . "Cảm ơn bạn.";
        
        $mail->AltBody = "Chúng tôi đã nhận được yêu cầu trả hàng của bạn cho đơn hàng #{$orderID} và đang xem xét.";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Refund Request Email failed: " . $e->getMessage() . " | Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * ⭐ HÀM MỚI 2: Gửi email XÁC NHẬN ĐÃ DUYỆT (Cho Admin Panel dùng sau)
 */
function sendRefundApprovedEmail(string $recipientEmail, string $recipientName, int $orderID, string $adminNotes): bool {
    $mail = new PHPMailer(true);
    try {
        // Cấu hình SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'tan07112004@gmail.com'; 
        $mail->Password   = 'tthb azje kcax bkpa'; // Mật khẩu ứng dụng
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
        $mail->Port       = 465; 
        $mail->CharSet = 'UTF-8';

        // Cài đặt Người gửi
        $mail->setFrom('noreply@badmintonshop.com', 'Badminton Shop');
        $mail->addAddress($recipientEmail, $recipientName);

        // Nội dung Email
        $mail->isHTML(true);
        $mail->Subject = "Yêu cầu trả hàng #{$orderID} của bạn ĐÃ ĐƯỢC DUYỆT";
        $mail->Body    = "Chào <b>{$recipientName}</b>,<br><br>"
                       . "Yêu cầu trả hàng/hoàn tiền của bạn cho đơn hàng <b>#{$orderID}</b> đã được chấp thuận.<br><br>"
                       . "<b>Ghi chú từ quản trị viên:</b> <i>{$adminNotes}</i><br><br>"
                       . "Tiền sẽ được hoàn về tài khoản của bạn trong 3-5 ngày làm việc.<br>"
                       . "Cảm ơn bạn.";
        
        $mail->AltBody = "Yêu cầu trả hàng #{$orderID} đã được duyệt. Ghi chú: {$adminNotes}";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Refund Approved Email failed to send to {$recipientEmail}. Error: {$e->getMessage()} | Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
// KHÔNG CÓ THẺ ĐÓNG PHP Ở CUỐI FILE