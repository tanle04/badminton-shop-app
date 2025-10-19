<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
require_once __DIR__ . '/../bootstrap.php'; // Giả định chứa hàm respond()

// Hàm này trả về chi tiết đầy đủ của một voucher, đã được chuẩn bị cho DTO Android
function format_voucher_data($voucher, $customerID, $mysqli) {
    if (!$voucher) return null;

    // Lấy số lần sử dụng cá nhân (nếu cần cho logic phức tạp)
    // Hiện tại chỉ cần usedCount (tổng) và maxUsage

    // ⭐ Tính toán phần trăm sử dụng còn lại (giống get_vouchers.php)
    $remainingUsagePercent = $voucher['maxUsage'] > 0
        ? round((($voucher['maxUsage'] - $voucher['usedCount']) / $voucher['maxUsage']) * 100)
        : 0;

    // ⭐ Ép kiểu dữ liệu để khớp với Android DTO (BigDecimal/boolean)
    $voucher['discountValue'] = (string) $voucher['discountValue'];
    $voucher['minOrderValue'] = (string) $voucher['minOrderValue'];
    $voucher['maxDiscountAmount'] = $voucher['maxDiscountAmount'] !== null ? (string) $voucher['maxDiscountAmount'] : null;
    $voucher['isActive'] = (bool) $voucher['isActive'];
    $voucher['isPrivate'] = (bool) $voucher['isPrivate'];
    $voucher['usageLimitPercent'] = $remainingUsagePercent;
    
    return $voucher;
}


try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }
    
    $input = json_decode(file_get_contents("php://input"), true) ?: $_POST;
    $code = trim($input['voucherCode'] ?? '');
    $customerID = (int)($input['customerID'] ?? 0); 
    
    if (empty($code) || $customerID <= 0) {
        respond(['isSuccess' => false, 'message' => 'Vui lòng nhập mã giảm giá.'], 400);
    }

    // ⭐ 1. Lấy thông tin voucher (Lấy đầy đủ các cột DTO)
    $sql = "
        SELECT voucherID, voucherCode, description, discountType, discountValue, minOrderValue, maxDiscountAmount, maxUsage, usedCount, startDate, endDate, isActive, isPrivate
        FROM vouchers 
        WHERE voucherCode = ? AND isActive = 1 AND startDate <= NOW() AND endDate >= NOW()
    ";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $voucher = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$voucher) {
        respond(['isSuccess' => false, 'message' => 'Mã giảm giá không hợp lệ hoặc đã hết hạn.'], 404);
    }

    $voucherID = (int)$voucher['voucherID'];
    $isPrivate = (bool)$voucher['isPrivate'];
    $remainingUsage = (int)$voucher['maxUsage'] - (int)$voucher['usedCount'];

    if ($remainingUsage <= 0) {
        respond(['isSuccess' => false, 'message' => 'Mã giảm giá đã hết lượt sử dụng.'], 409);
    }

    // ⭐ 2. Xử lý logic GÁN (REDEEM)
    if ($isPrivate) {
        // --- LOGIC CHO VOUCHER CÁ NHÂN ---
        
        $mysqli->begin_transaction();

        // Kiểm tra xem khách hàng đã sở hữu mã này chưa (customer_vouchers)
        $sql_check_user = "SELECT 1 FROM customer_vouchers WHERE customerID = ? AND voucherID = ?";
        $stmt_check = $mysqli->prepare($sql_check_user);
        $stmt_check->bind_param("ii", $customerID, $voucherID);
        $stmt_check->execute();
        
        if ($stmt_check->get_result()->num_rows > 0) {
            $stmt_check->close();
            respond(['isSuccess' => false, 'message' => 'Bạn đã sở hữu mã giảm giá này.'], 409);
        }
        $stmt_check->close();

        // Gán voucher cho khách hàng
        $sql_insert_user = "INSERT INTO customer_vouchers (customerID, voucherID) VALUES (?, ?)";
        $stmt_insert = $mysqli->prepare($sql_insert_user);
        $stmt_insert->bind_param("ii", $customerID, $voucherID);
        $stmt_insert->execute();
        
        // Tăng usedCount (giới hạn toàn cầu)
        $sql_inc = "UPDATE vouchers SET usedCount = usedCount + 1 WHERE voucherID = ?";
        $stmt_inc = $mysqli->prepare($sql_inc);
        $stmt_inc->bind_param("i", $voucherID);
        $stmt_inc->execute();
        
        if ($stmt_insert->affected_rows > 0 && $stmt_inc->affected_rows > 0) {
            $mysqli->commit();
            
            // ⭐ TRẢ VỀ DTO ĐẦY ĐỦ VÀ GỌI HÀM FORMAT
            $formattedVoucher = format_voucher_data($voucher, $customerID, $mysqli);

            respond([
                'isSuccess' => true, 
                'message' => 'Chúc mừng! Mã đã được thêm vào tài khoản của bạn.',
                'voucher' => $formattedVoucher // TRẢ VỀ DTO ĐẦY ĐỦ
            ], 200);
        } else {
             $mysqli->rollback();
             throw new Exception("Lỗi gán mã voucher.");
        }

    } else {
        // --- LOGIC CHO VOUCHER CHUNG (Chỉ kiểm tra và trả về DTO) ---
        
        // ⭐ TRẢ VỀ DTO ĐẦY ĐỦ VÀ GỌI HÀM FORMAT
        $formattedVoucher = format_voucher_data($voucher, $customerID, $mysqli);
        
        respond([
            'isSuccess' => true, 
            'message' => 'Mã hợp lệ, bạn có thể áp dụng tại trang thanh toán.',
            'voucher' => $formattedVoucher // TRẢ VỀ DTO ĐẦY ĐỦ
        ], 200);
    }

} catch (Throwable $e) {
    if (isset($mysqli) && $mysqli->in_transaction) $mysqli->rollback();
    error_log("Redeem API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Lỗi server không xác định: ' . $e->getMessage()], 500);
}
// Lưu ý: Thẻ đóng ?> bị loại bỏ.