<?php
// Ghi chú: File này được load bởi composer.json

/**
 * Gửi email xác nhận đơn hàng sau khi khách hàng đặt hàng thành công.
 */
function sendOrderConfirmationEmail(string $recipientEmail, string $recipientName, array $orderData): bool {
    // SỬA: Dùng Tên Lớp Hoàn Chỉnh Tuyệt đối (FQCN)
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        // Cấu hình SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'tan07112004@gmail.com'; 
        $mail->Password   = 'tthb azje kcax bkpa';
        // SỬA: Dùng FQCN tuyệt đối
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS; 
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
        $mail->Body      = "Xin chào <b>{$recipientName}</b>,<br><br>"
                         . "Cảm ơn bạn đã đặt hàng! Dưới đây là tóm tắt đơn hàng của bạn:<br><br>"
                         . "<b>Mã đơn hàng:</b> #{$orderData['orderID']}<br>"
                         . "<b>Tổng tiền:</b> " . number_format($orderData['totalAmount']) . " VNĐ<br>"
                         . "<b>Địa chỉ giao hàng:</b> {$orderData['shippingAddress']}<br><br>"
                         . "<b>Chi tiết sản phẩm:</b>{$itemsHtml}<br>"
                         . "Chúng tôi đang xử lý đơn hàng của bạn và sẽ thông báo khi hàng được giao.";
        
        $mail->AltBody = "Cảm ơn bạn đã đặt hàng! Mã đơn hàng: {$orderData['orderID']}. Tổng tiền: {$orderData['totalAmount']} VNĐ.";
        
        $mail->send();
        return true;
    } catch (\Exception $e) { // SỬA: Dùng Exception tuyệt đối
        error_log("Order Confirmation Email failed to send to {$recipientEmail}. Error: {$e->getMessage()} | Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Gửi email thông báo hủy đơn hàng.
 */
function sendCancellationEmail(string $recipientEmail, string $recipientName, int $orderID): bool {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true); // SỬA: Dùng FQCN tuyệt đối
    try {
        // Cấu hình SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'tan07112004@gmail.com'; 
        $mail->Password   = 'tthb azje kcax bkpa'; 
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS; // SỬA: Dùng FQCN tuyệt đối
        $mail->Port       = 465; 
        $mail->CharSet = 'UTF-8';

        // Cài đặt Người gửi
        $mail->setFrom('noreply@badmintonshop.com', 'Badminton Shop');
        $mail->addAddress($recipientEmail, $recipientName);

        // Nội dung Email
        $mail->isHTML(true);
        $mail->Subject = 'Thông báo HỦY Đơn hàng #' . $orderID;
        $mail->Body      = "Xin chào <b>{$recipientName}</b>,<br><br>"
                         . "Chúng tôi xin thông báo rằng Đơn hàng <b>#{$orderID}</b> của bạn đã được hủy. <br>"
                         . "Nếu bạn không yêu cầu hủy, vui lòng liên hệ chúng tôi ngay lập tức.<br>"
                         . "Cảm ơn bạn đã tin tưởng Cửa hàng Cầu lông!";
        
        $mail->AltBody = "Đơn hàng #{$orderID} đã bị hủy.";
        
        $mail->send();
        return true;
    } catch (\Exception $e) { // SỬA: Dùng Exception tuyệt đối
        error_log("Cancellation Email failed to send to {$recipientEmail}. Error: {$e->getMessage()} | Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Gửi email cảm ơn khi trạng thái đơn hàng chuyển sang 'Delivered'.
 */
function sendDeliveryConfirmationEmail(string $recipientEmail, string $recipientName, int $orderID): bool {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true); // SỬA: Dùng FQCN tuyệt đối
    try {
        // Cấu hình SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'tan07112004@gmail.com'; 
        $mail->Password   = 'tthb azje kcax bkpa'; 
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS; // SỬA: Dùng FQCN tuyệt đối
        $mail->Port       = 465; 
        $mail->CharSet = 'UTF-8';

        // Cài đặt Người gửi
        $mail->setFrom('noreply@badmintonshop.com', 'Badminton Shop');
        $mail->addAddress($recipientEmail, $recipientName);

        // Nội dung Email
        $mail->isHTML(true);
        $mail->Subject = 'Đơn hàng #' . $orderID . ' đã được giao thành công!';
        $mail->Body      = "Xin chào <b>{$recipientName}</b>,<br><br>"
                         . "Chúng tôi xin thông báo rằng Đơn hàng <b>#{$orderID}</b> của bạn đã được giao thành công. <br>"
                         . "Cảm ơn bạn đã tin tưởng và ủng hộ Cửa hàng Cầu lông!<br><br>"
                         . "Chúng tôi hy vọng bạn hài lòng với sản phẩm của mình. Nếu có bất kỳ vấn đề gì, đừng ngần ngại liên hệ với chúng tôi.";
        
        $mail->AltBody = "Đơn hàng #{$orderID} đã được giao thành công. Cảm ơn bạn!";
        
        $mail->send();
        return true;
    } catch (\Exception $e) { // SỬA: Dùng Exception tuyệt đối
        error_log("Delivery Confirmation Email failed to send to {$recipientEmail}. Error: {$e->getMessage()} | Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * ⭐ HÀM MỚI: Gửi email CHẤP NHẬN hoàn tiền.
 */
function sendRefundApprovedEmail(string $recipientEmail, string $recipientName, int $orderID, string $refundReason, string $paymentMethod): bool {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        // Cấu hình SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'tan07112004@gmail.com'; 
        $mail->Password   = 'tthb azje kcax bkpa'; 
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465; 
        $mail->CharSet = 'UTF-8';

        // Cài đặt Người gửi
        $mail->setFrom('noreply@badmintonshop.com', 'Badminton Shop');
        $mail->addAddress($recipientEmail, $recipientName);

        // Nội dung Email
        $mail->isHTML(true);
        $mail->Subject = "✅ Yêu cầu hoàn tiền đơn hàng #{$orderID} đã được chấp nhận";
        
        $refundMessage = ($paymentMethod == 'COD')
            ? "Vui lòng liên hệ bộ phận CSKH để cung cấp thông tin tài khoản ngân hàng nhận tiền hoàn."
            : "Số tiền sẽ được hoàn trả về tài khoản/ví điện tử bạn đã sử dụng để thanh toán trong 3-5 ngày làm việc.";

        $mail->Body = "Xin chào <b>{$recipientName}</b>,<br><br>"
                    . "Yêu cầu hoàn tiền của bạn cho đơn hàng <b>#{$orderID}</b> đã được chấp nhận.<br>"
                    . "<b>Lý do:</b> {$refundReason}<br>"
                    . "<b>Hình thức thanh toán của đơn hàng:</b> {$paymentMethod}<br><br>"
                    . "<b>Thông báo hoàn tiền:</b><br><i>{$refundMessage}</i><br><br>"
                    . "Cảm ơn bạn đã thông cảm.";

        $mail->send();
        return true;
    } catch (\Exception $e) {
        error_log("Refund Approved Email failed to send to {$recipientEmail}. Error: {$e->getMessage()} | Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * ⭐ HÀM MỚI: Gửi email TỪ CHỐI hoàn tiền.
 */
function sendRefundRejectedEmail(string $recipientEmail, string $recipientName, int $orderID, string $refundReason, string $rejectReason): bool {
     $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        // Cấu hình SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'tan07112004@gmail.com'; 
        $mail->Password   = 'tthb azje kcax bkpa'; 
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465; 
        $mail->CharSet = 'UTF-8';

        // Cài đặt Người gửi
        $mail->setFrom('noreply@badmintonshop.com', 'Badminton Shop');
        $mail->addAddress($recipientEmail, $recipientName);

        // Nội dung Email
        $mail->isHTML(true);
        $mail->Subject = "❌ Yêu cầu hoàn tiền đơn hàng #{$orderID} không được chấp nhận";
        $mail->Body = "Xin chào <b>{$recipientName}</b>,<br><br>"
                    . "Chúng tôi rất tiếc phải thông báo rằng yêu cầu hoàn tiền của bạn cho đơn hàng <b>#{$orderID}</b> không được chấp nhận.<br><br>"
                    . "<b>Lý do yêu cầu của bạn:</b> {$refundReason}<br>"
                    . "<b>Lý do từ chối từ chúng tôi:</b> {$rejectReason}<br><br>"
                    . "Nếu bạn có bất kỳ thắc mắc nào, vui lòng liên hệ bộ phận CSKH để được hỗ trợ chi tiết.<br>"
                    . "Cảm ơn bạn đã thông cảm.";
        
        $mail->send();
        return true;
    } catch (\Exception $e) {
        error_log("Refund Rejected Email failed to send to {$recipientEmail}. Error: {$e->getMessage()} | Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}