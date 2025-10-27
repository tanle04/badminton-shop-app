<?php
/**
 * shipping_helper.php
 * Chứa logic xác minh và tính toán phí vận chuyển.
 */

// ⭐ HÀM MỚI: Đọc giá trị cấu hình từ DB (Giả định có bảng app_settings)
function getSetting(\mysqli $mysqli, string $key, float $default) {
    $stmt = $mysqli->prepare("SELECT `value` FROM app_settings WHERE `key` = ?");
    if (!$stmt) return $default;
    
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    $value = $result->fetch_assoc()['value'] ?? null;
    $stmt->close();
    
    // Nếu giá trị tồn tại trong DB, chuyển đổi và trả về. Ngược lại, trả về giá trị mặc định.
    return $value !== null ? (float)$value : $default;
}

function getShippingRateDetails(\mysqli $mysqli, int $rateID) {
    $stmt = $mysqli->prepare("
        SELECT r.serviceName, r.price AS basePrice, r.estimatedDelivery, c.carrierName
        FROM shipping_rates r
        JOIN shipping_carriers c ON r.carrierID = c.carrierID
        WHERE r.rateID = ? AND c.isActive = 1
    ");
    if (!$stmt) return null;
    
    $stmt->bind_param("i", $rateID);
    $stmt->execute();
    $result = $stmt->get_result();
    $rate = $result->fetch_assoc();
    $stmt->close();
    
    return $rate;
}

function verifyShippingFee(\mysqli $mysqli, int $rateID, float $subtotal, float $shippingFeeClient) {
    
    $rateDetails = getShippingRateDetails($mysqli, $rateID);
    
    if (!$rateDetails) {
        return ['isValid' => false, 'message' => 'Rate vận chuyển không tồn tại hoặc không hoạt động.'];
    }

    // ⭐ SỬA LỖI: ĐỌC NGƯỠNG FREE SHIP TỪ DB ⭐
    // Đọc ngưỡng từ DB. Giá trị mặc định 2000000.00 chỉ dùng khi DB không có.
    $FREE_SHIP_THRESHOLD = getSetting($mysqli, 'free_ship_threshold', 2000000.00); 
    $BASE_SHIPPING_FEE = getSetting($mysqli, 'base_shipping_fee', 30000.00); // Tương tự cho phí cơ bản
    
    // 1. Phí cơ sở lấy từ DB cho dịch vụ được chọn
    $basePriceFromDB = (float)$rateDetails['basePrice'];

    // Khởi tạo phí xác minh bằng phí gốc của dịch vụ
    $verifiedFee = $basePriceFromDB; 
    $isFreeShip = false;
    
    // Áp dụng Freeship
    if ($subtotal >= $FREE_SHIP_THRESHOLD) {
        $verifiedFee = 0.00;
        $isFreeShip = true;
    } 
    // Nếu phí gốc là 0 (lỗi data) và KHÔNG Freeship, ta dùng phí cơ bản (chỉ là dự phòng)
    // Nếu $basePriceFromDB == 0.00 và $subtotal < $FREE_SHIP_THRESHOLD, gán phí cơ bản
    elseif ($basePriceFromDB < 0.01 && !$isFreeShip) { 
        $verifiedFee = $BASE_SHIPPING_FEE;
    }


    // 2. Kiểm tra phí gửi từ client có khớp với phí server tính được không
    // Sử dụng dung sai nhỏ (0.01) để so sánh số thực
    if (abs($verifiedFee - $shippingFeeClient) > 0.01) { 
        // Lỗi không khớp
        $expectedFee = number_format($verifiedFee, 2, '.', '');
        $receivedFee = number_format($shippingFeeClient, 2, '.', '');
        
        return [
            'isValid' => false, 
            'message' => "Phí vận chuyển không khớp. SERVER tính: {$expectedFee}đ (Ngưỡng FS: {$FREE_SHIP_THRESHOLD}đ), CLIENT gửi: {$receivedFee}đ.",
        ];
    }
    
    // 3. THÀNH CÔNG
    return [
        'isValid' => true, 
        'verifiedFee' => $verifiedFee, // Phí đã được xác minh (từ DB hoặc 0.00)
        'rateDetails' => $rateDetails,
        'message' => $isFreeShip ? 'Miễn phí vận chuyển.' : 'Phí đã được xác minh.'
    ];
}