<?php
// config/mail.php
// KHÔNG BAO GIỜ commit file này lên Git! (Thêm vào .gitignore)

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'tan07112004@gmail.com');
define('SMTP_PASSWORD', 'tthb azje kcax bkpa'); // Mật khẩu ứng dụng của bạn
define('SMTP_PORT', 465);
define('SMTP_SECURE', PHPMailer::ENCRYPTION_SMTPS);

define('MAIL_FROM_ADDRESS', 'noreply@badmintonshop.com');
define('MAIL_FROM_NAME', 'Badminton Shop');