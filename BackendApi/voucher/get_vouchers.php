<?php
// File: api/voucher/get_vouchers.php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../bootstrap.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }

    $customerID = (int)($_GET['customerID'] ?? 0);
    $subtotal = (float)($_GET['subtotal'] ?? 0.0);
    $currentDate = date('Y-m-d H:i:s');

    if ($customerID <= 0) {
        respond(['isSuccess' => false, 'message' => 'ID khách hàng không hợp lệ.'], 400);
    }

    $subtotal_for_query = number_format($subtotal, 2, '.', '');

    // ⭐ CÂU LỆNH SQL ĐÃ SỬA:
    // Logic mới:
    // 1. Voucher CHUNG (isPrivate=0): Phải còn lượt dùng chung (usedCount < maxUsage).
    // 2. Voucher RIÊNG (isPrivate=1): Chỉ cần được gán cho user VÀ status='available'.
    //    Không quan tâm đến usedCount < maxUsage nữa, vì lượt dùng đã được "đặt chỗ".
    $sql = "
    SELECT 
        v.voucherID, v.voucherCode, v.description, v.discountType, v.discountValue, 
        v.minOrderValue, v.maxDiscountAmount, v.maxUsage, v.usedCount, v.startDate, 
        v.endDate, v.isActive, v.isPrivate,
        cv.status as customerVoucherStatus 
    FROM vouchers v
    LEFT JOIN customer_vouchers cv ON v.voucherID = cv.voucherID AND cv.customerID = ?
    WHERE v.isActive = 1 
        AND v.startDate <= ?
        AND v.endDate >= ?
        AND v.minOrderValue <= ? 
        -- ⭐ ĐIỀU KIỆN LỌC CHÍNH ĐÃ SỬA:
        AND (
            -- 1. Là voucher chung (public) VÀ PHẢI CÒN LƯỢT DÙNG CHUNG
            (v.isPrivate = 0 AND v.usedCount < v.maxUsage) 
            OR 
            -- 2. Là voucher riêng (private) VÀ đã được gán cho user NÀY VÀ status = 'available'
            -- (Không cần check usedCount < maxUsage ở đây nữa)
            (v.isPrivate = 1 AND cv.customerID IS NOT NULL AND cv.status = 'available')
        )
    ORDER BY v.minOrderValue ASC";

    // Bind: i s s s (customerID cho LEFT JOIN, date, date, subtotal)
    $stmt = $mysqli->prepare($sql);
    if ($stmt === false) {
        respond(['isSuccess' => false, 'message' => 'Lỗi chuẩn bị SQL: ' . $mysqli->error], 500);
    }

    // Bind 4 tham số (customerID, date, date, subtotal)
    $stmt->bind_param("isss", $customerID, $currentDate, $currentDate, $subtotal_for_query);
    $stmt->execute();
    $res = $stmt->get_result();

    $vouchers = [];
    while ($row = $res->fetch_assoc()) {
        $row['discountValue'] = (string) $row['discountValue'];
        $row['minOrderValue'] = (string) $row['minOrderValue'];
        $row['maxDiscountAmount'] = $row['maxDiscountAmount'] !== null ? (string) $row['maxDiscountAmount'] : null;
        
        // Lấy các giá trị này ra biến tạm để check logic
        $isPrivate = (bool) $row['isPrivate'];
        $customerVoucherStatus = $row['customerVoucherStatus'] ?? null;

        $row['isActive'] = (bool) $row['isActive'];
        $row['isPrivate'] = $isPrivate;
        $row['customerVoucherStatus'] = $customerVoucherStatus;

        // ⭐ SỬA LOGIC TÍNH %:
        // Nếu là voucher riêng VÀ 'available' cho user, luôn hiển thị là còn hàng (100%)
        // để app không vô hiệu hóa nó, bất kể usedCount/maxUsage toàn cục là bao nhiêu.
        if ($isPrivate && $customerVoucherStatus === 'available') {
            $row['usageLimitPercent'] = 100;
        } else {
            // Logic cũ cho voucher chung
            $row['usageLimitPercent'] = $row['maxUsage'] > 0
                ? round((($row['maxUsage'] - $row['usedCount']) / $row['maxUsage']) * 100)
                : 0;
        }
        
        $vouchers[] = $row;
    }

    $stmt->close();

    respond([
        "isSuccess" => true,
        "message" => "Vouchers loaded successfully.",
        "vouchers" => $vouchers
    ]);
} catch (Throwable $e) {
    error_log("[VOUCHER_API] Global Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Lỗi server không xác định: ' . $e->getMessage()], 500);
}
// Lưu ý: Thẻ đóng ?> bị loại bỏ.

