<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../bootstrap.php'; // Giả định chứa hàm respond()

try {
    // 1. Kiểm tra phương thức và Validation
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }

    if (!isset($_GET['productID']) || !is_numeric($_GET['productID'])) {
        respond(['isSuccess' => false, 'message' => 'Thiếu hoặc productID không hợp lệ.'], 400);
    }
    $productID = (int)$_GET['productID'];
    
    // (Tùy chọn) Lọc theo số sao (rating)
    $rating = isset($_GET['rating']) && is_numeric($_GET['rating']) ? (int)$_GET['rating'] : null;

    // 2. Xây dựng truy vấn SQL cho ĐÁNH GIÁ CHI TIẾT (ITEMS)
    $sqlDetails = "
        SELECT
            r.reviewID, r.rating, r.reviewContent, r.reviewDate, c.fullName AS customerName, c.customerID,
            (
                SELECT GROUP_CONCAT(rm.mediaUrl SEPARATOR '||')
                FROM review_media rm
                WHERE rm.reviewID = r.reviewID 
            ) AS reviewPhotos
        FROM reviews r
        JOIN customers c ON r.customerID = c.customerID
        JOIN products p ON r.productID = p.productID
        WHERE r.productID = ? 
        AND r.status = 'published'
        AND p.is_active = 1
    ";
    
    $params = [$productID];
    $types = "i";
    
    // Thêm điều kiện lọc theo số sao nếu có
    if ($rating !== null && $rating >= 1 && $rating <= 5) {
        $sqlDetails .= " AND r.rating = ?";
        $params[] = $rating;
        $types .= "i";
    }

    $sqlDetails .= " ORDER BY r.reviewDate DESC";
    
    // 3. Chuẩn bị và thực thi truy vấn chi tiết
    $stmtDetails = $mysqli->prepare($sqlDetails);
    if (!$stmtDetails) {
        respond(['isSuccess' => false, 'message' => 'SQL Details Prepare failed: ' . $mysqli->error], 500);
    }

    $bind_names = array($types);
    for ($i = 0; $i < count($params); $i++) {
        $bind_names[] = &$params[$i];
    }
    call_user_func_array(array($stmtDetails, 'bind_param'), $bind_names);
    
    $stmtDetails->execute();
    $resDetails = $stmtDetails->get_result();

    $data = [];
    
    // ✅ SỬA LỖI: Định nghĩa base URL 1 lần bên ngoài vòng lặp
    $base_url = 'https://' . $_SERVER['HTTP_HOST'] . '/admin/public/storage/';
            
    while ($row = $resDetails->fetch_assoc()) {
        
        // Lấy danh sách ảnh/video
        $mediaUrls = $row['reviewPhotos'] ? explode('||', $row['reviewPhotos']) : [];
        $processedMedia = [];

        // ✅ SỬA LỖI: Lặp qua từng media URL và thêm base_url
        if (!empty($mediaUrls)) {
            foreach ($mediaUrls as $url) {
                if (!empty($url)) {
                    // $url từ DB là "reviews/media/image.jpg"
                    $processedMedia[] = $base_url . $url;
                }
            }
        }
        
        $row['reviewPhotos'] = $processedMedia; // Gán mảng đã xử lý
        $data[] = $row;
    }
    $stmtDetails->close();
    
    // 4. Lấy dữ liệu TÓM TẮT (SUMMARY) - KHÔNG ÁP DỤNG LỌC THEO SỐ SAO
    $sqlSummary = "
        SELECT 
            COALESCE(AVG(r.rating), 0) AS averageRating, 
            COUNT(r.reviewID) AS totalReviews
        FROM reviews r
        JOIN products p ON r.productID = p.productID
        WHERE r.productID = ? AND r.status = 'published' AND p.is_active = 1
    ";

    $stmtSummary = $mysqli->prepare($sqlSummary);
    if (!$stmtSummary) {
        respond(['isSuccess' => false, 'message' => 'SQL Summary Prepare failed: ' . $mysqli->error], 500);
    }
    $stmtSummary->bind_param("i", $productID);
    $stmtSummary->execute();
    $summaryResult = $stmtSummary->get_result();
    $summary = $summaryResult->fetch_assoc();
    $stmtSummary->close();
    
    // 5. Trả về phản hồi thành công (Gộp dữ liệu tóm tắt và chi tiết)
    respond([
        "isSuccess" => true,
        "message" => "Reviews loaded successfully.",
        "productID" => $productID,
        "averageRating" => round((float)$summary['averageRating'], 1), 
        "totalReviews" => (int)$summary['totalReviews'], 
        "items" => $data
    ]);

} catch (Throwable $e) {
    error_log("Reviews API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra phía server: ' . $e->getMessage()], 500);
}