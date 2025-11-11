<?php
// ⭐ QUAN TRỌNG: Bắt đầu output buffering NGAY LẬP TỨC
ob_start();

// Tắt hiển thị lỗi ra output (vẫn ghi log)
ini_set('display_errors', '0');
error_reporting(E_ALL);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Origin: *");

// ⭐ THỬ NHIỀU ĐƯỜNG DẪN AUTOLOAD
$autoload_paths = [
    __DIR__ . '/../../vendor/autoload.php',  // Thử đường dẫn 1
    __DIR__ . '/../vendor/autoload.php',     // Thử đường dẫn 2
    dirname(dirname(__DIR__)) . '/vendor/autoload.php', // Thử đường dẫn 3
];

$autoload_loaded = false;
foreach ($autoload_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $autoload_loaded = true;
        error_log("Refund API: Loaded autoload from: $path");
        break;
    }
}

if (!$autoload_loaded) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'isSuccess' => false, 
        'message' => 'Không tìm thấy autoload.php. Kiểm tra cấu trúc thư mục.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Load bootstrap và helpers
require_once __DIR__ . '/../bootstrap.php';
// ⭐ SỬA LỖI TYPO: Bỏ chữ 's' ở cuối 'email_helpers.php'
require_once __DIR__ . '/../utils/email_helper.php';

use PHPMailer\PHPMailer\PHPMailer;

$uploaded_file_paths = []; 

error_log("=== Refund API: Script started ===");

try {
    // Kiểm tra method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Phương thức không được phép', 405);
    }

    // Kiểm tra dữ liệu
    if (!isset($_POST['refund_data'])) {
        throw new Exception('Thiếu dữ liệu refund_data', 400);
    }
    
    $data = json_decode($_POST['refund_data'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Lỗi đọc JSON: ' . json_last_error_msg(), 400);
    }

    // Validate dữ liệu
    $customerID = (int)($data['customerID'] ?? 0);
    $orderID = (int)($data['orderID'] ?? 0);
    $reason = trim($data['reason'] ?? 'Không có lý do');
    $items = $data['items'] ?? [];

    if ($customerID <= 0 || $orderID <= 0) {
        throw new Exception('CustomerID hoặc OrderID không hợp lệ', 400);
    }
    
    if (empty($items) || !is_array($items)) {
        throw new Exception('Danh sách sản phẩm trả hàng trống', 400);
    }
    
    error_log("Refund API: OrderID=$orderID, CustomerID=$customerID");

    // === XỬ LÝ UPLOAD MEDIA ===
    $upload_dir = __DIR__ . '/../../admin/storage/app/public/refunds/';
    $db_prefix = 'refunds/';
    
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) { // Sửa 0777 thành 0755 an toàn hơn
            throw new Exception('Không thể tạo thư mục upload');
        }
        error_log("Refund API: Created directory: $upload_dir");
    }
    
    $media_urls = [];
    
    // Upload Ảnh
    if (isset($_FILES['photos']) && is_array($_FILES['photos']['tmp_name'])) {
        $count = count($_FILES['photos']['tmp_name']);
        error_log("Refund API: Processing $count photos");
        
        for ($i = 0; $i < $count; $i++) {
            if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_OK) {
                $tmp = $_FILES['photos']['tmp_name'][$i];
                $ext = pathinfo($_FILES['photos']['name'][$i], PATHINFO_EXTENSION);
                $filename = sprintf('refund_%d_%s.%s', $orderID, uniqid(), $ext);
                $target = $upload_dir . $filename;
                
                if (move_uploaded_file($tmp, $target)) {
                    $media_urls[] = ['url' => $db_prefix . $filename, 'type' => 'photo'];
                    $uploaded_file_paths[] = $target;
                    error_log("Refund API: Uploaded photo: $filename");
                } else {
                    error_log("Refund API: Failed to move photo: $filename");
                }
            }
        }
    }
    
    // Upload Video
    if (isset($_FILES['videos']) && is_array($_FILES['videos']['tmp_name'])) {
        $count = count($_FILES['videos']['tmp_name']);
        error_log("Refund API: Processing $count videos");
        
        for ($i = 0; $i < $count; $i++) {
            if ($_FILES['videos']['error'][$i] === UPLOAD_ERR_OK) {
                $tmp = $_FILES['videos']['tmp_name'][$i];
                $ext = pathinfo($_FILES['videos']['name'][$i], PATHINFO_EXTENSION);
                $filename = sprintf('refund_%d_%s.%s', $orderID, uniqid(), $ext);
                $target = $upload_dir . $filename;
                
                if (move_uploaded_file($tmp, $target)) {
                    $media_urls[] = ['url' => $db_prefix . $filename, 'type' => 'video'];
                    $uploaded_file_paths[] = $target;
                    error_log("Refund API: Uploaded video: $filename");
                } else {
                    error_log("Refund API: Failed to move video: $filename");
                }
            }
        }
    }
    
    error_log("Refund API: Total media uploaded: " . count($media_urls));

    // === DATABASE TRANSACTION ===
    error_log("Refund API: Starting transaction");
    $mysqli->begin_transaction();

    try {
        // Kiểm tra đơn hàng
        $stmt = $mysqli->prepare("
            SELECT o.status, c.email, c.fullName 
            FROM orders o
            JOIN customers c ON o.customerID = c.customerID
            WHERE o.orderID = ? AND o.customerID = ?
            FOR UPDATE
        ");
        
        if (!$stmt) {
            throw new Exception("DB Error: " . $mysqli->error);
        }
        
        $stmt->bind_param("ii", $orderID, $customerID);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$result) {
            throw new Exception('Không tìm thấy đơn hàng hoặc không có quyền');
        }
        
        if ($result['status'] !== 'Delivered') {
            throw new Exception('Chỉ có thể trả hàng đã giao');
        }

        $customerEmail = $result['email'];
        $customerName = $result['fullName'];
        error_log("Refund API: Order verified. Customer: $customerName");

        // Tạo refund request
        $stmt = $mysqli->prepare("
            INSERT INTO refund_requests (orderID, customerID, reason, status) 
            VALUES (?, ?, ?, 'Pending')
        ");
        
        if (!$stmt) {
            throw new Exception("DB Error: " . $mysqli->error);
        }
        
        $stmt->bind_param("iis", $orderID, $customerID, $reason);
        $stmt->execute();
        $refundRequestID = $stmt->insert_id;
        $stmt->close();

        if ($refundRequestID <= 0) {
            throw new Exception('Không thể tạo yêu cầu trả hàng');
        }
        
        error_log("Refund API: Created refund_requests ID: $refundRequestID");

        // Thêm items
        $stmt = $mysqli->prepare("
            INSERT INTO refund_request_items (refundRequestID, orderDetailID, quantity, reason) 
            VALUES (?, ?, ?, ?)
        ");
        
        if (!$stmt) {
            throw new Exception("DB Error: " . $mysqli->error);
        }
        
        foreach ($items as $item) {
            $detailID = (int)$item['orderDetailID'];
            $qty = (int)$item['quantity'];
            $itemReason = $item['reason'] ?? null;
            
            $stmt->bind_param("iiis", $refundRequestID, $detailID, $qty, $itemReason);
            $stmt->execute();
        }
        $stmt->close();
        
        error_log("Refund API: Inserted " . count($items) . " items");

        // Thêm media
        if (!empty($media_urls)) {
            $stmt = $mysqli->prepare("
                INSERT INTO refund_request_media (refundRequestID, mediaUrl, mediaType) 
                VALUES (?, ?, ?)
            ");
            
            if (!$stmt) {
                throw new Exception("DB Error: " . $mysqli->error);
            }
            
            foreach ($media_urls as $media) {
                $stmt->bind_param("iss", $refundRequestID, $media['url'], $media['type']);
                $stmt->execute();
            }
            $stmt->close();
            
            error_log("Refund API: Inserted " . count($media_urls) . " media");
        }

        // Cập nhật trạng thái order
        $stmt = $mysqli->prepare("UPDATE orders SET status = 'Refund Requested' WHERE orderID = ?");
        if (!$stmt) {
            throw new Exception("DB Error: " . $mysqli->error);
        }
        
        $stmt->bind_param("i", $orderID);
        $stmt->execute();
        $stmt->close();
        
        error_log("Refund API: Updated order status");

        // Commit transaction
        $mysqli->commit();
        error_log("Refund API: Transaction committed");

        // Gửi email (không quan trọng nếu thất bại)
        if (!empty($customerEmail)) {
            try {
                sendRefundRequestConfirmationEmail($customerEmail, $customerName, $orderID);
                error_log("Refund API: Email sent");
            } catch (Exception $e) {
                error_log("Refund API: Email failed: " . $e->getMessage());
            }
        }

        // ⭐ QUAN TRỌNG: Clear buffer và trả JSON
        ob_end_clean();
        http_response_code(200);
        echo json_encode([
            'isSuccess' => true,
            'message' => 'Đã gửi yêu cầu trả hàng thành công',
            'refundRequestID' => $refundRequestID
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        $mysqli->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Refund API ERROR: " . $e->getMessage() . " | Line: " . $e->getLine());
    
    // Rollback nếu có transaction
    if (isset($mysqli) && $mysqli->in_transaction) {
        $mysqli->rollback();
    }
    
    // Xóa file đã upload
    foreach ($uploaded_file_paths as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
    
    // Trả về lỗi dạng JSON
    ob_end_clean();
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'isSuccess' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}