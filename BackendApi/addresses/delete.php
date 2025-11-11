<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
require_once '../bootstrap.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }
    
    $addressID = (int)($_POST['addressID'] ?? 0);
    $customerID = (int)($_POST['customerID'] ?? 0);

    // --- 1. Validation ---
    if ($addressID <= 0 || $customerID <= 0) {
        respond(['isSuccess' => false, 'message' => 'Dữ liệu không hợp lệ.'], 400);
    }

    // --- 2. Kiểm tra địa chỉ mặc định ---
    // (Logic này vẫn giữ nguyên, rất tốt. Không nên cho người dùng xóa/ẩn địa chỉ mặc định cuối cùng)
    $stmt_check_default = $mysqli->prepare("SELECT isDefault FROM customer_addresses WHERE addressID = ? AND customerID = ?");
    $stmt_check_default->bind_param("ii", $addressID, $customerID);
    $stmt_check_default->execute();
    $result_check = $stmt_check_default->get_result();
    $address_info = $result_check->fetch_assoc();
    $stmt_check_default->close();

    if ($address_info && $address_info['isDefault']) {
        $stmt_count = $mysqli->prepare("SELECT COUNT(*) AS total FROM customer_addresses WHERE customerID = ? AND is_active = 1"); // Chỉ đếm địa chỉ active
        $stmt_count->bind_param("i", $customerID);
        $stmt_count->execute();
        $count = $stmt_count->get_result()->fetch_assoc()['total'];
        $stmt_count->close();

        if ($count <= 1) {
            respond(['isSuccess' => false, 'message' => 'Vui lòng thêm hoặc đặt địa chỉ khác làm mặc định trước khi xóa địa chỉ này.'], 409);
        }
    }

    // --- 3. Thực hiện "XÓA MỀM" (Soft Delete) ---
    // ⭐ THAY ĐỔI TỪ DELETE SANG UPDATE
    $stmt = $mysqli->prepare("UPDATE customer_addresses SET is_active = 0 WHERE addressID = ? AND customerID = ?");
    
    if (!$stmt) {
        throw new Exception("Lỗi chuẩn bị SQL UPDATE: " . $mysqli->error);
    }
    
    $stmt->bind_param("ii", $addressID, $customerID);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            respond(['isSuccess' => true, 'message' => 'Xóa địa chỉ thành công.'], 200);
        } else {
            // Không tìm thấy địa chỉ (ID sai hoặc đã bị xóa)
            respond(['isSuccess' => true, 'message' => 'Không tìm thấy địa chỉ để xóa.'], 200); 
        }
    } else {
        throw new Exception("Xóa (ẩn) địa chỉ thất bại: " . $stmt->error);
    }

    $stmt->close();

} catch (Throwable $e) {
    error_log("Delete Address API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Lỗi Server: ' . $e->getMessage()], 500);
}
