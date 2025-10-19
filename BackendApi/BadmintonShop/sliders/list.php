<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../bootstrap.php'; // Giả định chứa kết nối $mysqli và hàm respond()

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }

    // ⭐ SỬA: Loại bỏ header() ban đầu vì đã có trong bootstrap
    // header('Content-Type: application/json; charset=utf-8'); 
    
    $sql = "
        SELECT s.sliderID, s.title, s.imageUrl, s.backlink, s.status,
               s.createdDate, e.fullName AS createdBy
        FROM sliders s
        LEFT JOIN employees e ON s.employeeID = e.employeeID
        WHERE s.status = 'active'
        ORDER BY s.createdDate DESC
    ";

    $res = $mysqli->query($sql);
    
    if ($res === false) {
        // Xử lý lỗi SQL
        respond(['isSuccess' => false, 'message' => 'Lỗi truy vấn database: ' . $mysqli->error], 500);
    }
    
    $data = [];
    $base_url_path = '/api/BadmintonShop/images/sliders/'; // ⭐ Đảm bảo đường dẫn này đúng
    
    while ($row = $res->fetch_assoc()) {
        // ⭐ SỬA: Sử dụng BASE_URL cố định 10.0.2.2 cho emulator hoặc URL thực
        // Tùy chọn 1: Dùng HTTP_HOST thực tế
        $base_url = 'http://' . $_SERVER['HTTP_HOST']; 
        
        // Tùy chọn 2: Dùng URL cố định của emulator (Tốt hơn cho phát triển)
        // $base_url = 'http://10.0.2.2';
        
        $row['imageUrl'] = $base_url . $base_url_path . $row['imageUrl'];
        $data[] = $row;
    }

    $mysqli->close();
    
    // ⭐ PHẢN HỒI THÀNH CÔNG: Trả về mảng dữ liệu. 
    // Vì DTO Android mong đợi List<SliderDto>, ta cần kiểm tra xem respond có xử lý trả về mảng không.
    // Nếu respond chỉ gói object, ta sẽ trả thẳng mảng như code gốc.
    
    // ⭐ GIỮ NGUYÊN PHƯƠNG PHÁP TRẢ MẢNG ĐƠN GIẢN (Vì ApiService đã khai báo Call<List<SliderDto>>)
    // Chúng ta sẽ bỏ qua tiêu chuẩn isSuccess cho endpoint này để khớp với ApiService.
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    // Xử lý lỗi toàn cục
    error_log("Slider API Error: " . $e->getMessage());
    // Trả về cấu trúc lỗi (có thể gây lỗi JSON parsing ở Android nếu nó mong đợi List)
    http_response_code(500); 
    echo json_encode(['error' => 'server_error', 'message' => 'Lỗi server không xác định.'], JSON_UNESCAPED_UNICODE);
}
// Lưu ý: Thẻ đóng ?> 