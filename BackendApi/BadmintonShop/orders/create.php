<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../bootstrap.php';
error_reporting(E_ALL); // Bật báo cáo lỗi để ghi vào log

// Hàm trả về phản hồi JSON (sử dụng isSuccess)
function return_response($isSuccess, $message, $orderID = null)
{
    global $mysqli;

    // Đảm bảo kết nối được đóng trước khi trả về
    if ($mysqli && !is_null($mysqli->get_server_info())) {
        $mysqli->close();
    }

    // TRẢ VỀ JSON VỚI TRƯỜNG "isSuccess"
    echo json_encode([
        'isSuccess' => $isSuccess,
        'message' => $message,
        'orderID' => $orderID
    ], JSON_UNESCAPED_UNICODE);
    exit();
}


// Nhận dữ liệu POST từ Android
$customerID = (int)($_POST['customerID'] ?? 0);
$addressID = (int)($_POST['addressID'] ?? 0);
$paymentMethod = $_POST['paymentMethod'] ?? ''; // "COD" hoặc "VNPay"
$total = (float)($_POST['total'] ?? 0.0);
$itemsJSON = $_POST['items'] ?? '[]';
$voucherID = (int)($_POST['voucherID'] ?? -1);
$items = json_decode($itemsJSON, true);

// LOG PHP 1: Ghi lại các tham số đầu vào
error_log("[ORDER_API] Request received. customerID: $customerID, total: $total, voucherID: $voucherID");


// --- Validation ---
if ($customerID <= 0 || $addressID <= 0 || empty($paymentMethod) || $total <= 0 || empty($items) || !is_array($items)) {
    http_response_code(400);
    return_response(false, 'Dữ liệu đầu vào không hợp lệ.');
}

// --- Bắt đầu Transaction để đảm bảo toàn vẹn dữ liệu ---
$mysqli->begin_transaction();

try {
    // --- Bước 1: Kiểm tra và tạm trừ tồn kho (RESERVE STOCK) ---
    $stmt_update_stock = $mysqli->prepare(
        "UPDATE product_variants SET stock = stock - ?, reservedStock = reservedStock + ? WHERE variantID = ? AND stock >= ?"
    );

    if ($stmt_update_stock === false) {
        throw new Exception("Lỗi chuẩn bị SQL cho tồn kho: " . $mysqli->error);
    }

    foreach ($items as $item) {
        $quantity = (int)($item['quantity'] ?? 0);
        $variantID = (int)($item['variantID'] ?? 0);

        if ($variantID <= 0 || $quantity <= 0) continue;

        $stmt_update_stock->bind_param("iiii", $quantity, $quantity, $variantID, $quantity);
        $stmt_update_stock->execute();

        // Nếu không có dòng nào được cập nhật, nghĩa là không đủ điều kiện
        if ($stmt_update_stock->affected_rows == 0) {
            $check_stmt = $mysqli->prepare("SELECT stock FROM product_variants WHERE variantID = ?");
            if ($check_stmt) {
                $check_stmt->bind_param("i", $variantID);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                $current_stock = $check_result->fetch_assoc()['stock'] ?? 0;
                $check_stmt->close();
            } else {
                $current_stock = 0; 
            }

            // Hoàn tác và báo lỗi
            throw new Exception("Sản phẩm '" . ($item['productName'] ?? 'ID: ' . $variantID) .
                "' không đủ số lượng tồn kho. Hiện còn: " . $current_stock);
        }
    }
    $stmt_update_stock->close();

    // --- Bước 2: Tạo đơn hàng trong bảng `orders` ---
    $paymentStatus = ('COD' === $paymentMethod) ? 'Unpaid' : 'Pending';

    $sql_order = "
        INSERT INTO orders (customerID, addressID, paymentMethod, paymentStatus, total, status, voucherID) 
        VALUES (?, ?, ?, ?, ?, 'Processing', IF(? > 0, ?, NULL))
    ";

    $stmt_order = $mysqli->prepare($sql_order);

    if ($stmt_order === false) {
        throw new Exception("Lỗi chuẩn bị SQL cho đơn hàng: " . $mysqli->error);
    }

    // Bind: i i s d s i i 
    $stmt_order->bind_param("iisdsii", $customerID, $addressID, $paymentMethod, $paymentStatus, $total, $voucherID, $voucherID);
    $stmt_order->execute();
    $orderID = $mysqli->insert_id;
    $stmt_order->close();

    if ($orderID == 0) {
        throw new Exception("Không thể tạo bản ghi đơn hàng.");
    }
    
    // --- Bước 2.5: Xử lý Voucher và Tăng UsedCount/Set Status ---
    if ($voucherID > 0) {
        $stmt_voucher_info = $mysqli->prepare("SELECT isPrivate FROM vouchers WHERE voucherID = ?");
        if (!$stmt_voucher_info) {
             throw new Exception("Lỗi chuẩn bị SQL kiểm tra voucher: " . $mysqli->error);
        }
        $stmt_voucher_info->bind_param("i", $voucherID);
        $stmt_voucher_info->execute();
        $voucher_info = $stmt_voucher_info->get_result()->fetch_assoc();
        $stmt_voucher_info->close();

        if ($voucher_info) {
            $isPrivate = (bool)$voucher_info['isPrivate'];

            if ($isPrivate) {
                // ⭐ LOGIC A: VOUCHER CÁ NHÂN/RIÊNG TƯ
                // ⭐ SỬA: Bỏ cột dateUsed khỏi UPDATE (hoặc thêm cột này vào DB)
                $stmt_update_cust_voucher = $mysqli->prepare(
                    "UPDATE customer_vouchers SET status = 'used' WHERE customerID = ? AND voucherID = ? AND status = 'available'"
                );
                $stmt_update_cust_voucher->bind_param("ii", $customerID, $voucherID);
                $stmt_update_cust_voucher->execute();
                
                if ($stmt_update_cust_voucher->affected_rows == 0) {
                     throw new Exception("Voucher cá nhân đã được sử dụng hoặc không hợp lệ (VoucherID: {$voucherID}).");
                }
                $stmt_update_cust_voucher->close();
                
                // KHÔNG CẦN TĂNG usedCount TRONG BẢNG VOUCHERS (đã tăng trong redeem.php)

            } else {
                // ⭐ LOGIC B: VOUCHER CHUNG
                $stmt_update_global_voucher = $mysqli->prepare(
                    "UPDATE vouchers SET usedCount = usedCount + 1 WHERE voucherID = ? AND usedCount < maxUsage"
                );
                $stmt_update_global_voucher->bind_param("i", $voucherID);
                $stmt_update_global_voucher->execute();
                
                if ($stmt_update_global_voucher->affected_rows == 0) {
                    throw new Exception("Voucher đã hết lượt sử dụng.");
                }
                $stmt_update_global_voucher->close();
            }
        }
    }
    
    // --- Bước 3: Thêm các sản phẩm vào `orderdetails` ---
    $stmt_details = $mysqli->prepare("INSERT INTO orderdetails (orderID, variantID, quantity, price) VALUES (?, ?, ?, ?)");
    if ($stmt_details === false) {
        throw new Exception("Lỗi chuẩn bị SQL cho chi tiết đơn hàng: " . $mysqli->error);
    }

    $cartIDsToDelete = [];
    foreach ($items as $item) {
        $variantID = (int)($item['variantID'] ?? 0);
        $quantity = (int)($item['quantity'] ?? 0);
        $price = (float)($item['variantPrice'] ?? 0.0);
        $cartIDsToDelete[] = (int)($item['cartID'] ?? 0);

        $stmt_details->bind_param("iiid", $orderID, $variantID, $quantity, $price);
        $stmt_details->execute();
    }
    $stmt_details->close();

    // --- Bước 4: Xóa các sản phẩm đã mua khỏi `shopping_cart` ---
    if (!empty($cartIDsToDelete)) {
        $placeholders = implode(',', array_fill(0, count($cartIDsToDelete), '?'));

        // Tạo chuỗi kiểu dữ liệu (i cho customerID + n lần i cho n cartIDs)
        $types = 'i' . str_repeat('i', count($cartIDsToDelete));

        $stmt_delete = $mysqli->prepare("DELETE FROM shopping_cart WHERE customerID = ? AND cartID IN ($placeholders)");
        if ($stmt_delete === false) {
            throw new Exception("Lỗi chuẩn bị SQL xóa giỏ hàng: " . $mysqli->error);
        }

        // ⭐ SỬA LỖI TRUYỀN THAM SỐ ĐỘNG: Tạo mảng tham số và truyền tham chiếu
        $bind_params = array_merge([$types], [$customerID], $cartIDsToDelete);

        // Tạo mảng tham chiếu để dùng call_user_func_array
        $refs = [];
        foreach ($bind_params as $key => $value) {
            $refs[$key] = &$bind_params[$key];
        }

        call_user_func_array([$stmt_delete, 'bind_param'], $refs);
        $stmt_delete->execute();
        $stmt_delete->close();
    }

    // --- Hoàn tất, xác nhận tất cả các thay đổi ---
    $mysqli->commit();

    http_response_code(200);
    return_response(true, 'Đặt hàng thành công!', $orderID);
} catch (Exception $e) {
    // Nếu có bất kỳ lỗi nào, hoàn tác tất cả các thay đổi
    $mysqli->rollback();
    error_log("[ORDER_API] Transaction Rollback. Error: " . $e->getMessage());
    http_response_code(500);
    return_response(false, 'Đặt hàng thất bại: ' . $e->getMessage());
}

// Chú ý: $mysqli->close() đã được gọi trong return_response()