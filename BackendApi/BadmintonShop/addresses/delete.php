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
    $customerID = (int)($_POST['customerID'] ?? 0); // Dùng để xác thực

    // --- 1. Validation ---
    if ($addressID <= 0 || $customerID <= 0) {
        respond(['isSuccess' => false, 'message' => 'Dữ liệu không hợp lệ (addressID hoặc customerID).'], 400);
    }

    // --- 2. Kiểm tra địa chỉ mặc định ---
    // Ngăn người dùng xóa địa chỉ mặc định nếu đây là địa chỉ duy nhất
    $stmt_check_default = $mysqli->prepare("SELECT isDefault FROM customer_addresses WHERE addressID = ? AND customerID = ?");
    if (!$stmt_check_default) {
        throw new Exception("Lỗi chuẩn bị SQL kiểm tra địa chỉ mặc định: " . $mysqli->error);
    }
    $stmt_check_default->bind_param("ii", $addressID, $customerID);
    $stmt_check_default->execute();
    $result_check = $stmt_check_default->get_result();
    $address_info = $result_check->fetch_assoc();
    $stmt_check_default->close();

    if ($address_info && $address_info['isDefault']) {
        // Kiểm tra xem có địa chỉ nào khác không
        $stmt_count = $mysqli->prepare("SELECT COUNT(*) AS total FROM customer_addresses WHERE customerID = ?");
        $stmt_count->bind_param("i", $customerID);
        $stmt_count->execute();
        $count = $stmt_count->get_result()->fetch_assoc()['total'];
        $stmt_count->close();

        if ($count <= 1) {
            respond(['isSuccess' => false, 'message' => 'Vui lòng thêm hoặc đặt địa chỉ khác làm mặc định trước khi xóa địa chỉ này.'], 409);
        }
    }


    // --- 3. Thực hiện DELETE ---
    $stmt = $mysqli->prepare("DELETE FROM customer_addresses WHERE addressID = ? AND customerID = ?");
    
    if (!$stmt) {
        throw new Exception("Lỗi chuẩn bị SQL DELETE: " . $mysqli->error);
    }
    
    $stmt->bind_param("ii", $addressID, $customerID);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            respond(['isSuccess' => true, 'message' => 'Xóa địa chỉ thành công.'], 200);
        } else {
            // Không tìm thấy địa chỉ (đã bị xóa hoặc ID sai), vẫn coi là thành công logic
            respond(['isSuccess' => true, 'message' => 'Không tìm thấy địa chỉ để xóa.'], 200); 
        }
    } else {
        throw new Exception("Xóa địa chỉ thất bại: " . $stmt->error);
    }

    $stmt->close();

} catch (Throwable $e) {
    error_log("Delete Address API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Lỗi Server: ' . $e->getMessage()], 500);
}
// Lưu ý: Thẻ đóng ?> bị loại bỏ.