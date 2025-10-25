<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
require_once '../bootstrap.php'; // Giả định chứa hàm respond()

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }

    // Lấy dữ liệu từ POST request
    $addressID = (int)($_POST['addressID'] ?? 0);
    $customerID = (int)($_POST['customerID'] ?? 0);

    // --- 1. Validation ---
    if ($addressID <= 0 || $customerID <= 0) {
        respond(['isSuccess' => false, 'message' => 'Dữ liệu không hợp lệ (addressID hoặc customerID).'], 400);
    }

    // Bắt đầu một transaction để đảm bảo an toàn dữ liệu
    $mysqli->begin_transaction();

    // Bước 1: Bỏ tất cả các địa chỉ khác của người dùng này khỏi trạng thái mặc định
    $stmt1 = $mysqli->prepare("UPDATE customer_addresses SET isDefault = 0 WHERE customerID = ?");
    if (!$stmt1) {
         throw new Exception("Lỗi chuẩn bị SQL (Bước 1): " . $mysqli->error);
    }
    $stmt1->bind_param("i", $customerID);
    $stmt1->execute();
    $stmt1->close();

    // Bước 2: Đặt địa chỉ được chọn làm mặc định mới
    $stmt2 = $mysqli->prepare("UPDATE customer_addresses SET isDefault = 1 WHERE addressID = ? AND customerID = ?");
    if (!$stmt2) {
         throw new Exception("Lỗi chuẩn bị SQL (Bước 2): " . $mysqli->error);
    }
    $stmt2->bind_param("ii", $addressID, $customerID);
    $stmt2->execute();
    
    // Nếu không có dòng nào được cập nhật ở bước 2, throw exception và rollback
    if ($stmt2->affected_rows == 0) {
        throw new Exception("Không tìm thấy địa chỉ để đặt làm mặc định.");
    }
    
    $stmt2->close();
    
    // Nếu tất cả thành công, commit transaction
    $mysqli->commit();
    
    // ⭐ PHẢN HỒI THÀNH CÔNG: Sử dụng respond() với cấu trúc DTO
    respond(['isSuccess' => true, 'message' => 'Đặt làm địa chỉ mặc định thành công'], 200);

} catch (Throwable $e) {
    // Nếu có lỗi, rollback tất cả các thay đổi
    if (isset($mysqli) && $mysqli->in_transaction) {
        $mysqli->rollback();
    }
    
    error_log("Set Default Address API Error: " . $e->getMessage());
    
    // ⭐ PHẢN HỒI THẤT BẠI: Sử dụng respond() với cấu trúc DTO
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
}

// Lưu ý: $mysqli->close() đã được xử lý trong khối try/catch hoặc bootstrap/respond
// Thẻ đóng ?> bị loại bỏ.