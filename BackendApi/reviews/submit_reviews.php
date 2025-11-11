<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
require_once '../bootstrap.php'; // Chứa $mysqli, respond()

// Hàm helper để đọc dữ liệu JSON từ phần 'review_data' của Multipart request
function get_multipart_data() {
    if (isset($_POST['review_data'])) {
        return json_decode($_POST['review_data'], true);
    }
    return null;
}

$uploaded_file_paths = []; // KHAI BÁO BIẾN TOÀN CỤC: Lưu trữ đường dẫn tệp vật lý

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }

    // ⭐ 1. Đọc dữ liệu JSON
    $input = get_multipart_data();
    
    $orderID = (int)($input['orderID'] ?? 0);
    $customerID = (int)($input['customerID'] ?? 0);
    $reviews_data = $input['reviews'] ?? []; 
    $successfully_submitted_detail_ids = []; 

    // Validation cơ bản
    if ($orderID <= 0 || $customerID <= 0 || empty($reviews_data) || !is_array($reviews_data)) {
        respond(['isSuccess' => false, 'message' => 'Dữ liệu đánh giá không hợp lệ.'], 400);
    }

    // ✅ SỬA LỖI 1: Thay đổi đường dẫn vật lý để trỏ vào storage của AdminPanel
    // __DIR__ là /public_html/api/reviews/
    // Chúng ta cần đi lùi 3 cấp để đến /public_html/ rồi vào /admin/storage/app/public/reviews/media/
    $upload_dir = __DIR__ . '/../../admin/storage/app/public/reviews/';
    
    // ✅ SỬA LỖI 2: Thay đổi tiền tố DB để khớp với AdminPanel
    $db_prefix = 'reviews/'; // Thêm 'media/'
    
    if (!is_dir($upload_dir)) {
        // Tạo thư mục nếu nó không tồn tại
        if (!mkdir($upload_dir, 0777, true) && !is_dir($upload_dir)) {
             throw new Exception("Không thể tạo thư mục upload: " . $upload_dir);
        }
    }
    
    $media_urls = []; // Mảng lưu trữ URL/Type của các tệp đã tải lên

    // ⭐ BẮT ĐẦU: XỬ LÝ UPLOAD TỆP
    
    // Xử lý Ảnh (photos[])
    if (isset($_FILES['photos'])) {
        $count = count($_FILES['photos']['tmp_name']);
        for ($i = 0; $i < $count; $i++) {
            if ($_FILES['photos']['error'][$i] == UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['photos']['tmp_name'][$i];
                $original_name = $_FILES['photos']['name'][$i];
                $file_name = uniqid() . '-' . basename($original_name);
                $file_path = $upload_dir . $file_name;

                if (move_uploaded_file($tmp_name, $file_path)) {
                    // Ghi đường dẫn tương đối (đã sửa) vào DB (ví dụ: reviews/media/filename.jpg)
                    $media_urls[] = ['url' => $db_prefix . $file_name, 'type' => 'photo']; 
                    $uploaded_file_paths[] = $file_path;
                }
            }
        }
    }

    // Xử lý Video (videos[])
    if (isset($_FILES['videos'])) {
        $count = count($_FILES['videos']['tmp_name']);
        for ($i = 0; $i < $count; $i++) {
            if ($_FILES['videos']['error'][$i] == UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['videos']['tmp_name'][$i];
                $original_name = $_FILES['videos']['name'][$i];
                $file_name = uniqid() . '-' . basename($original_name);
                $file_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($tmp_name, $file_path)) {
                    // Ghi đường dẫn tương đối (đã sửa) vào DB (ví dụ: reviews/media/filename.mp4)
                    $media_urls[] = ['url' => $db_prefix . $file_name, 'type' => 'video']; 
                    $uploaded_file_paths[] = $file_path;
                }
            }
        }
    }
    // ⭐ KẾT THÚC: XỬ LÝ UPLOAD TỆP

    
    $mysqli->begin_transaction();
    $totalSubmitted = 0;
    $submitted_review_ids = []; 

    // CHUẨN BỊ SQL STATEMENTS
    $sql_insert_review = "
        INSERT INTO reviews (orderDetailID, customerID, productID, rating, reviewContent) 
        VALUES (?, ?, ?, ?, ?)
    ";
    $sql_insert_media = "
        INSERT INTO review_media (reviewID, mediaUrl, mediaType) 
        VALUES (?, ?, ?)
    ";
    $sql_update_order_detail = "
        UPDATE orderdetails 
        SET isReviewed = 1 
        WHERE orderDetailID = ?
    ";
    
    $stmt_review = $mysqli->prepare($sql_insert_review);
    $stmt_media = $mysqli->prepare($sql_insert_media);
    $stmt_update_detail = $mysqli->prepare($sql_update_order_detail); 

    if (!$stmt_review || !$stmt_media || !$stmt_update_detail) {
        throw new Exception("Lỗi chuẩn bị SQL: " . $mysqli->error);
    }

    // 2. XỬ LÝ TỪNG ĐÁNH GIÁ
    foreach ($reviews_data as $review) {
        $orderDetailID = (int)($review['orderDetailID'] ?? 0);
        $productID = (int)($review['productID'] ?? 0);
        $rating = (int)($review['rating'] ?? 0);
        $content = trim($review['reviewContent'] ?? '');
        
        if ($orderDetailID <= 0 || $productID <= 0 || $rating < 1 || $rating > 5) {
            continue; 
        }

        // Chèn đánh giá
        $stmt_review->bind_param("iiiis", $orderDetailID, $customerID, $productID, $rating, $content);
        $stmt_review->execute();
        
        $new_review_id = $mysqli->insert_id;
        
        if ($new_review_id > 0) {
            $totalSubmitted++;
            $submitted_review_ids[] = $new_review_id;
            $successfully_submitted_detail_ids[] = $orderDetailID; 
        }
    }
    
    // 3. CHÈN MEDIA VÀO DB
    if (!empty($submitted_review_ids) && !empty($media_urls)) {
        foreach ($submitted_review_ids as $review_id) {
            foreach ($media_urls as $media) {
                $mediaUrl = $media['url'];
                $mediaType = $media['type'];
                
                $stmt_media->bind_param("iss", $review_id, $mediaUrl, $mediaType);
                $stmt_media->execute();
            }
        }
    }
    
    // 4. CẬP NHẬT TRẠNG THÁI ĐÃ ĐÁNH GIÁ (isReviewed = 1)
    if (!empty($successfully_submitted_detail_ids)) {
        foreach ($successfully_submitted_detail_ids as $detailID) {
            $stmt_update_detail->bind_param("i", $detailID);
            $stmt_update_detail->execute();
        }
    }
    
    $stmt_review->close();
    $stmt_media->close();
    $stmt_update_detail->close(); 
    
    if ($totalSubmitted > 0) {
        $mysqli->commit();
        respond(['isSuccess' => true, 'message' => "Đã gửi thành công {$totalSubmitted} đánh giá và media. Vui lòng tải lại trang đơn hàng để cập nhật."], 200);
    } else {
        $mysqli->rollback();
        
        // Xóa tệp đã tải lên nếu không có review nào được chèn thành công
        foreach ($uploaded_file_paths as $file_path) {
            if (file_exists($file_path)) unlink($file_path);
        }
        respond(['isSuccess' => false, 'message' => 'Không có đánh giá nào được chèn. Có thể đã đánh giá trước đó hoặc dữ liệu không hợp lệ.'], 200);
    }

} catch (Throwable $e) {
    if (isset($mysqli) && $mysqli->in_transaction) $mysqli->rollback();
    
    // XỬ LÝ LỖI: Xóa các tệp đã tải lên nếu transaction thất bại
    foreach ($uploaded_file_paths as $file_path) {
        if (file_exists($file_path)) unlink($file_path);
    }
    
    error_log("Submit Review API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Lỗi máy chủ khi gửi đánh giá: ' . $e->getMessage()], 500);
}