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
    $recipientName = trim($_POST['recipientName'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $street = trim($_POST['street'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $postalCode = trim($_POST['postalCode'] ?? '');
    $country = trim($_POST['country'] ?? '');

    // --- 1. Validation ---
    if ($addressID <= 0 || $customerID <= 0 || empty($recipientName) || empty($phone) || empty($street) || empty($city) || empty($country)) {
        respond(['isSuccess' => false, 'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc.'], 400);
    }

    // --- 2. Thực hiện UPDATE ---
    $sql = "UPDATE customer_addresses SET recipientName = ?, phone = ?, street = ?, city = ?, postalCode = ?, country = ? WHERE addressID = ? AND customerID = ?";
    $stmt = $mysqli->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Lỗi chuẩn bị SQL UPDATE: " . $mysqli->error);
    }
    
    // ⭐ LƯU Ý: Kiểu dữ liệu 's s s s s s i i' là chính xác
    $stmt->bind_param("ssssssii", $recipientName, $phone, $street, $city, $postalCode, $country, $addressID, $customerID);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            // Cập nhật thành công
            respond(['isSuccess' => true, 'message' => 'Cập nhật địa chỉ thành công'], 200);
        } else {
            // Không có dòng nào bị ảnh hưởng (dữ liệu không thay đổi hoặc địa chỉ không tồn tại)
            // Trong trường hợp này, vẫn coi là thành công vì yêu cầu đã được đáp ứng
            respond(['isSuccess' => true, 'message' => 'Cập nhật thành công (Không có thay đổi dữ liệu).'], 200);
        }
    } else {
        throw new Exception("Cập nhật thất bại: " . $stmt->error);
    }

    $stmt->close();

} catch (Throwable $e) {
    error_log("Update Address API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Lỗi Server: ' . $e->getMessage()], 500);
}
// Lưu ý: Thẻ đóng ?> bị loại bỏ.