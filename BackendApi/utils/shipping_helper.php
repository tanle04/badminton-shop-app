<?php
/**
 * shipping_helper.php
 * Chứa logic xác minh và tính toán phí vận chuyển.
 */

function getSetting(\mysqli $mysqli, string $key, float $default) {
    $stmt = $mysqli->prepare("SELECT `value` FROM app_settings WHERE `key` = ?");
    if (!$stmt) return $default;
    
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    $value = $result->fetch_assoc()['value'] ?? null;
    $stmt->close();
    
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

    // Lấy cài đặt (Đã đồng bộ ở lần trước)
    $FREE_SHIP_THRESHOLD = getSetting($mysqli, 'free_ship_threshold', 2500000.00); 
    $BASE_SHIPPING_FEE = getSetting($mysqli, 'base_shipping_fee', 30000.00); 
    
    $basePriceFromDB = (float)$rateDetails['basePrice'];
    $verifiedFee = $basePriceFromDB; 
    $isFreeShip = false;
    
    // Áp dụng Freeship
    if ($subtotal >= $FREE_SHIP_THRESHOLD) {
        $verifiedFee = 0.00;
        $isFreeShip = true;
    } 
    elseif ($basePriceFromDB < 0.01 && !$isFreeShip) { 
        $verifiedFee = $BASE_SHIPPING_FEE;
    }

    // ⭐ SỬA ĐỔI CHÍNH ⭐
    // So sánh phí
    if (abs($verifiedFee - $shippingFeeClient) > 0.01) { 
        $expectedFee = number_format($verifiedFee, 2, '.', '');
        $receivedFee = number_format($shippingFeeClient, 2, '.', '');
        
        return [
            'isValid' => false, 
            'isPriceMismatch' => true, // <-- THÊM FLAG NÀY
            'message' => "Giá hoặc phí ship đã thay đổi. Server tính phí: {$expectedFee}đ (dựa trên subtotal mới), Client gửi: {$receivedFee}đ."
        ];
    }
    
    // THÀNH CÔNG
    return [
        'isValid' => true, 
        'verifiedFee' => $verifiedFee,
        'rateDetails' => $rateDetails,
        'message' => $isFreeShip ? 'Miễn phí vận chuyển.' : 'Phí đã được xác minh.'
    ];
}
?>