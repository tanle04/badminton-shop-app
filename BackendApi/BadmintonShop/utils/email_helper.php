<?php
// ⭐ BƯỚC KHẮC PHỤC LỖI: Load thư viện PHPMailer
require_once __DIR__ . '/../vendor/autoload.php'; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Gửi email xác nhận đơn hàng sau khi khách hàng đặt hàng thành công.
 * @param string $recipientEmail Email người nhận.
 * @param string $recipientName Tên người nhận.
 * @param array $orderData Dữ liệu đơn hàng: orderID, totalAmount, shippingAddress, items (danh sách sản phẩm).
 * @return bool Trả về true nếu gửi thành công, false nếu thất bại.
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
        $mail->Body     = "Xin chào <b>{$recipientName}</b>,<br><br>"
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
 * Gửi email thông báo hủy đơn hàng (ĐÃ THÊM MỚI).
 * @param string $recipientEmail Email người nhận.
 * @param string $recipientName Tên người nhận.
 * @param int $orderID Mã đơn hàng.
 * @return bool Trả về true nếu gửi thành công, false nếu thất bại.
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
        $mail->Body     = "Xin chào <b>{$recipientName}</b>,<br><br>"
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
 * (Giữ nguyên logic của hàm này)
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
        $mail->Body     = "Xin chào <b>{$recipientName}</b>,<br><br>"
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
// KHÔNG CÓ THẺ ĐÓNG PHP Ở CUỐI FILE