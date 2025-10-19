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

    // ⭐ CÂU LỆNH SQL ĐÃ SỬA: Bao gồm voucher chung VÀ voucher riêng đã được gán (JOIN customer_vouchers)
    $sql = "
    SELECT 
        v.voucherID, v.voucherCode, v.description, v.discountType, v.discountValue, 
        v.minOrderValue, v.maxDiscountAmount, v.maxUsage, v.usedCount, v.startDate, 
        v.endDate, v.isActive, v.isPrivate
    FROM vouchers v
    -- LEFT JOIN với bảng gán voucher cá nhân
    LEFT JOIN customer_vouchers cv ON v.voucherID = cv.voucherID AND cv.customerID = ?
    WHERE v.isActive = 1 
        AND v.startDate <= ?
        AND v.endDate >= ?
        AND v.minOrderValue <= ? 
        AND v.usedCount < v.maxUsage 
        -- ⭐ ĐK LỌC: Hoặc là voucher chung OR (voucher riêng AND đã được gán)
        AND (
            v.isPrivate = 0 OR 
            (v.isPrivate = 1 AND cv.customerID IS NOT NULL AND cv.status = 'available')
        )
        -- ⭐ ĐK BỔ SUNG: Loại trừ các voucher đã được sử dụng trong orders (Kiểm tra lại nếu cần)
        AND v.voucherID NOT IN (SELECT voucherID FROM orders WHERE customerID = ? AND voucherID IS NOT NULL)
    ORDER BY v.minOrderValue ASC";

    // Bind: i s s s i (customerID đầu tiên là cho LEFT JOIN, customerID cuối là cho NOT IN)
    $stmt = $mysqli->prepare($sql);
    if ($stmt === false) {
        respond(['isSuccess' => false, 'message' => 'Lỗi chuẩn bị SQL: ' . $mysqli->error], 500);
    }

    // ⭐ SỬA BIND: Bind 5 tham số (customerID, date, date, subtotal, customerID)
    $stmt->bind_param("isssi", $customerID, $currentDate, $currentDate, $subtotal_for_query, $customerID);
    $stmt->execute();
    $res = $stmt->get_result();

    $vouchers = [];
    while ($row = $res->fetch_assoc()) {
        $row['discountValue'] = (string) $row['discountValue'];
        $row['minOrderValue'] = (string) $row['minOrderValue'];
        $row['maxDiscountAmount'] = $row['maxDiscountAmount'] !== null ? (string) $row['maxDiscountAmount'] : null;
        $row['isActive'] = (bool) $row['isActive'];
        $row['isPrivate'] = (bool) $row['isPrivate'];

        $row['usageLimitPercent'] = $row['maxUsage'] > 0
            ? round((($row['maxUsage'] - $row['usedCount']) / $row['maxUsage']) * 100)
            : 0;

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