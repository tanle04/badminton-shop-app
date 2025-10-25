<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
require_once '../bootstrap.php'; // Giả định chứa hàm respond()

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }
    
    // Lấy dữ liệu từ POST request
    $customerID = (int)($_POST['customerID'] ?? 0);
    $recipientName = trim($_POST['recipientName'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $street = trim($_POST['street'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $postalCode = trim($_POST['postalCode'] ?? '');
    $country = trim($_POST['country'] ?? '');

    // --- 1. Validation ---
    if ($customerID <= 0 || empty($recipientName) || empty($phone) || empty($street) || empty($city) || empty($country)) {
        respond(['isSuccess' => false, 'message' => 'Vui lòng điền đầy đủ các trường bắt buộc.'], 400);
    }

    // --- 2. Kiểm tra địa chỉ đầu tiên (Logic đặt mặc định) ---
    $stmt_check = $mysqli->prepare("SELECT COUNT(*) as address_count FROM customer_addresses WHERE customerID = ?");
    if (!$stmt_check) {
        throw new Exception("Lỗi chuẩn bị SQL kiểm tra địa chỉ: " . $mysqli->error);
    }
    $stmt_check->bind_param("i", $customerID);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $isFirstAddress = ($result_check->fetch_assoc()['address_count'] == 0);
    $stmt_check->close();

    $isDefault = $isFirstAddress ? 1 : 0; // Nếu là địa chỉ đầu tiên, isDefault = 1 (true)

    // --- 3. Thực hiện INSERT ---
    $sql = "INSERT INTO customer_addresses (customerID, recipientName, phone, street, city, postalCode, country, isDefault) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Lỗi chuẩn bị SQL INSERT: " . $mysqli->error);
    }
    
    // ⭐ LƯU Ý: Kiểu dữ liệu 'i s s s s s s i' là chính xác cho các tham số
    $stmt->bind_param("issssssi", $customerID, $recipientName, $phone, $street, $city, $postalCode, $country, $isDefault);

    if ($stmt->execute()) {
        $newAddressID = $mysqli->insert_id;
        respond(['isSuccess' => true, 'message' => 'Thêm địa chỉ thành công', 'addressID' => $newAddressID], 201);
    } else {
        throw new Exception("Thêm địa chỉ thất bại: " . $stmt->error);
    }

    $stmt->close();

} catch (Throwable $e) {
    error_log("Add Address API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Lỗi Server: ' . $e->getMessage()], 500);
}
// Lưu ý: Thẻ đóng ?> bị loại bỏ.