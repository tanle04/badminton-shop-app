<?php
// File: api/voucher/redeem_voucher.php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
require_once __DIR__ . '/../bootstrap.php'; // Giả định chứa hàm respond() và biến $mysqli

// Hàm này trả về chi tiết đầy đủ của một voucher, đã được chuẩn bị cho DTO Android
function format_voucher_data($voucher, $customerID, $mysqli) {
    if (!$voucher) return null;

    // (Đã xóa tính toán remainingUsagePercent ở đây)

    // Kiểm tra trạng thái customer_vouchers nếu là voucher riêng
    $customerVoucherStatus = null;
    $isPrivate = (bool)$voucher['isPrivate']; // Lấy isPrivate ở đây

    if ($isPrivate) {
        $sql_status = "SELECT status FROM customer_vouchers WHERE customerID = ? AND voucherID = ?";
        $stmt_status = $mysqli->prepare($sql_status);
        $stmt_status->bind_param("ii", $customerID, $voucher['voucherID']);
        $stmt_status->execute();
        $status_row = $stmt_status->get_result()->fetch_assoc();
        $stmt_status->close();
        $customerVoucherStatus = $status_row ? $status_row['status'] : null;
    }

    // Ép kiểu dữ liệu để khớp với DTO
    $voucher['voucherID'] = (int) $voucher['voucherID'];
    $voucher['discountValue'] = (string) $voucher['discountValue'];
    $voucher['minOrderValue'] = (string) $voucher['minOrderValue'];
    $voucher['maxDiscountAmount'] = $voucher['maxDiscountAmount'] !== null ? (string) $voucher['maxDiscountAmount'] : null;
    $voucher['maxUsage'] = (int) $voucher['maxUsage'];
    $voucher['usedCount'] = (int) $voucher['usedCount'];
    $voucher['isActive'] = (bool) $voucher['isActive'];
    $voucher['isPrivate'] = $isPrivate; // Gán lại giá trị bool

    // ⭐ SỬA LOGIC TÍNH %: (Giống file get_vouchers.php)
    // Nếu là voucher riêng VÀ 'available' cho user, luôn hiển thị là còn hàng (100%)
    // để app không vô hiệu hóa nó, bất kể usedCount/maxUsage toàn cục là bao nhiêu.
    if ($isPrivate && $customerVoucherStatus === 'available') {
        $voucher['usageLimitPercent'] = 100;
    } else {
        // Logic cũ cho voucher chung
        $voucher['usageLimitPercent'] = $voucher['maxUsage'] > 0
            ? round((($voucher['maxUsage'] - $voucher['usedCount']) / $voucher['maxUsage']) * 100)
            : 0;
    }
    
    $voucher['customerVoucherStatus'] = $customerVoucherStatus; // Thêm trạng thái cá nhân
    
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
        respond(['isSuccess' => false, 'message' => 'Yêu cầu mã giảm giá và ID khách hàng hợp lệ.'], 400);
    }

    // 1. Lấy thông tin voucher và kiểm tra điều kiện chung
    $sql = "
        SELECT voucherID, voucherCode, description, discountType, discountValue, minOrderValue, maxDiscountAmount, maxUsage, usedCount, startDate, endDate, isActive, isPrivate
        FROM vouchers 
        WHERE voucherCode = ? AND isActive = 1 AND startDate <= NOW() AND endDate >= NOW()
    ";
    $stmt = $mysqli->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Lỗi chuẩn bị SQL: " . $mysqli->error);
    }
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

    // 2. Xử lý logic GÁN (REDEEM)
    if ($isPrivate) {
        // --- LOGIC CHO VOUCHER CÁ NHÂN (Chỉ cần gán, KHÔNG tăng usedCount) ---

        // Bắt đầu giao dịch (Transaction)
        $mysqli->begin_transaction();

        try {
            // A. Kiểm tra khách hàng đã sở hữu mã này chưa (customer_vouchers)
            $sql_check_user = "SELECT status FROM customer_vouchers WHERE customerID = ? AND voucherID = ?";
            $stmt_check = $mysqli->prepare($sql_check_user);
            $stmt_check->bind_param("ii", $customerID, $voucherID);
            $stmt_check->execute();
            $user_voucher = $stmt_check->get_result()->fetch_assoc();
            $stmt_check->close();
            
            if ($user_voucher) {
                // Đã sở hữu, không cần gán lại
                $formattedVoucher = format_voucher_data($voucher, $customerID, $mysqli);
                
                // Nếu status là 'available', báo là đã sở hữu.
                if ($user_voucher['status'] === 'available') {
                    $mysqli->rollback();
                    respond([
                        'isSuccess' => false, 
                        'message' => 'Bạn đã sở hữu mã giảm giá này.',
                        'voucher' => $formattedVoucher
                    ], 409);
                } else {
                     // Nếu status là 'used'/'expired', báo trạng thái đó.
                    $mysqli->rollback();
                    respond([
                        'isSuccess' => false, 
                        'message' => "Mã giảm giá này đã được {$user_voucher['status']}.",
                        'voucher' => $formattedVoucher
                    ], 409);
                }
            }

            // B. Gán voucher cho khách hàng (status mặc định là 'available' do DB default)
            $sql_insert_user = "INSERT INTO customer_vouchers (customerID, voucherID) VALUES (?, ?)";
            $stmt_insert = $mysqli->prepare($sql_insert_user);
            $stmt_insert->bind_param("ii", $customerID, $voucherID);
            $stmt_insert->execute();

            if ($stmt_insert->affected_rows === 0) {
                throw new Exception("Lỗi gán mã voucher cho khách hàng.");
            }
            $stmt_insert->close();


            // ⭐ ĐÃ XÓA KHỐI C: TĂNG usedCount TẠI ĐÂY.
            // Logic tăng usedCount sẽ được chuyển sang API Thanh Toán (Checkout).


            // Commit giao dịch
            $mysqli->commit();

            // Lấy thông tin voucher sau khi commit (không còn +1 usedCount nữa)
            $formattedVoucher = format_voucher_data($voucher, $customerID, $mysqli);

            respond([
                'isSuccess' => true, 
                'message' => 'Chúc mừng! Mã đã được thêm vào tài khoản của bạn.',
                'voucher' => $formattedVoucher // TRẢ VỀ DTO ĐẦY ĐỦ
            ], 200);

        } catch (Throwable $e) {
            $mysqli->rollback(); // Rollback nếu có lỗi xảy ra
            throw $e; // Re-throw để bắt ở khối catch lớn
        }

    } else {
        // --- LOGIC CHO VOUCHER CHUNG (Chỉ kiểm tra và trả về DTO) ---
        
        $formattedVoucher = format_voucher_data($voucher, $customerID, $mysqli);
        
        respond([
            'isSuccess' => true, 
            'message' => 'Mã hợp lệ. Bạn có thể áp dụng tại trang thanh toán.',
            'voucher' => $formattedVoucher // TRẢ VỀ DTO ĐẦY ĐỦ
        ], 200);
    }

} catch (Throwable $e) {
    error_log("Redeem API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Lỗi server không xác định: ' . $e->getMessage()], 500);
}
