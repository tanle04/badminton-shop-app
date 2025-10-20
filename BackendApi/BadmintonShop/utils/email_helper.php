<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
// Đã loại bỏ tất cả các lệnh require_once thủ công, dựa vào autoload.php từ register.php

function sendVerificationEmail(string $recipientEmail, string $recipientName, string $verificationLink): bool {
    $mail = new PHPMailer(true);
    try {
        // Cấu hình SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'tan07112004@gmail.com'; 
        $mail->Password   = 'tthb azje kcax bkpa'; // Mật khẩu ứng dụng 16 ký tự (Không có dấu cách)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; 
        $mail->Port       = 465; 
        
        // Thiết lập mã hóa UTF-8
        $mail->CharSet = 'UTF-8';

        // Cài đặt Người gửi
        $mail->setFrom('noreply@badmintonshop.com', 'Badminton Shop');
        $mail->addAddress($recipientEmail, $recipientName);

        // Nội dung Email
        $mail->isHTML(true);
        $mail->Subject = 'Xác nhận Tài khoản Cửa hàng Cầu lông';
        $mail->Body    = "Xin chào <b>{$recipientName}</b>,<br><br>"
                         . "Cảm ơn bạn đã đăng ký! Vui lòng nhấp vào liên kết dưới đây để kích hoạt tài khoản của bạn:<br><br>"
                         . "<p style='margin-top: 20px; text-align: center;'><a href='{$verificationLink}' style='padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>Xác nhận Tài khoản của tôi</a></p><br>"
                         . "Liên kết sẽ hết hạn trong 24 giờ.<br><br>"
                         . "Nếu bạn không đăng ký, vui lòng bỏ qua email này.";
        $mail->AltBody = "Vui lòng truy cập liên kết sau để xác nhận tài khoản: " . $verificationLink;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        // Ghi log lỗi SMTP chi tiết
        error_log("Email failed to send to {$recipientEmail}. Error: {$e->getMessage()} | Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
