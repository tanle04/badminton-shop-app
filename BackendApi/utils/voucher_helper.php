<?php
// utils/voucher_helper.php

/**
 * Lấy thông tin voucher và tính toán số tiền giảm giá thực tế.
 * * @param mysqli $mysqli Connection object.
 * @param int $voucherID ID của voucher được áp dụng.
 * @param float $subtotal Tổng tiền hàng (sau khi giảm giá sản phẩm, trước phí ship).
 * @return array ['isValid' => bool, 'discountAmount' => float, 'message' => string]
 */
function getVoucherDiscountDetails($mysqli, $voucherID, $subtotal) {
    if ($voucherID <= 0) {
        return [
            'isValid' => false, 
            'discountAmount' => 0.0, 
            'message' => 'Voucher ID không hợp lệ.'
        ];
    }
    
    $now = date('Y-m-d H:i:s');
    
    // 1. Lấy thông tin Voucher
    $sql = "
        SELECT voucherCode, discountType, discountValue, minOrderValue, maxDiscountAmount, maxUsage, usedCount, startDate, endDate
        FROM vouchers
        WHERE voucherID = ? AND isActive = 1
    ";
    
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        return ['isValid' => false, 'discountAmount' => 0.0, 'message' => 'Lỗi truy vấn voucher.'];
    }
    
    $stmt->bind_param("i", $voucherID);
    $stmt->execute();
    $result = $stmt->get_result();
    $voucher = $result->fetch_assoc();
    $stmt->close();
    
    if (!$voucher) {
        return ['isValid' => false, 'discountAmount' => 0.0, 'message' => 'Mã voucher không tồn tại hoặc đã bị vô hiệu hóa.'];
    }

    $discountAmount = 0.0;
    $discountValue = (float)$voucher['discountValue'];
    $minOrderValue = (float)$voucher['minOrderValue'];
    $maxDiscountAmount = (float)$voucher['maxDiscountAmount'];
    $maxUsage = (int)$voucher['maxUsage'];
    $usedCount = (int)$voucher['usedCount'];

    // 2. Kiểm tra điều kiện thời gian và giới hạn sử dụng
    if ($now < $voucher['startDate'] || $now > $voucher['endDate']) {
        return ['isValid' => false, 'discountAmount' => 0.0, 'message' => 'Voucher chưa/đã hết hạn sử dụng.'];
    }
    
    if ($usedCount >= $maxUsage) {
        return ['isValid' => false, 'discountAmount' => 0.0, 'message' => 'Voucher đã hết lượt sử dụng toàn cầu.'];
    }
    
    // 3. Kiểm tra Min Order Value
    if ($subtotal < $minOrderValue) {
        return [
            'isValid' => false, 
            'discountAmount' => 0.0, 
            'message' => 'Giá trị đơn hàng tối thiểu chưa đạt (Tối thiểu: ' . number_format($minOrderValue) . ' VNĐ).'
        ];
    }

    // 4. Tính toán số tiền giảm giá thực tế
    if ($voucher['discountType'] === 'percentage') {
        $discountAmount = $subtotal * ($discountValue / 100.0);
        
        // Áp dụng Max Discount Amount
        if ($maxDiscountAmount > 0 && $discountAmount > $maxDiscountAmount) {
            $discountAmount = $maxDiscountAmount;
        }
    } elseif ($voucher['discountType'] === 'fixed') {
        // Với fixed amount, không thể giảm quá subtotal
        $discountAmount = min($discountValue, $subtotal);
    }
    
    return [
        'isValid' => true, 
        'discountAmount' => round($discountAmount, 2), 
        'message' => 'Voucher hợp lệ.'
    ];
}