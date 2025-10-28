<?php
// BẬT DEBUG: HIỂN THỊ LỖI TRONG MÔI TRƯỜM DEV
error_reporting(E_ALL); 
ini_set('display_errors', 1);

// Thiết lập JSON Header
header('Content-Type: application/json');

// Tải các file cần thiết
require_once __DIR__ . '/../bootstrap.php'; 
require_once __DIR__ . '/../utils/shipping_helper.php'; // Chứa getSetting
// ⭐ BẮT BUỘC: Thêm file này để tính giá an toàn
require_once __DIR__ . '/../utils/price_calculator.php'; 

// Hàm tiện ích trả về JSON lỗi và dừng script
function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// 1. LẤY THAM SỐ ĐẦU VÀO
// ⭐ SỬA LỖI: Không dùng $_GET['subtotal']. Dùng $_GET['itemsJSON']
$itemsJSON = $_GET['itemsJSON'] ?? '[]';
$items = json_decode($itemsJSON, true);

if (empty($items) || !is_array($items)) {
    sendError("Không có sản phẩm để tính phí vận chuyển (itemsJSON).", 422);
}

// 2. TÍNH TOÁN SUBTOTAL AN TOÀN TRÊN SERVER
// (Logic này phải giống hệt logic trong create_order.php)
$serverSubtotal = 0.0;
try {
    foreach ($items as $item) {
        $variantID = (int)($item['variantID'] ?? 0);
        $quantity = (int)($item['quantity'] ?? 0);
        if ($variantID <= 0 || $quantity <= 0) continue;

        // Gọi hàm tính giá "an toàn" (giống hệt create_order.php)
        $price_details = get_best_sale_price($mysqli, $variantID);
        $finalPrice = $price_details['salePrice'];
        $serverSubtotal += $finalPrice * $quantity;
    }
} catch (Exception $e) {
    sendError("Lỗi khi tính toán giá: " . $e->getMessage(), 500);
}

if ($serverSubtotal <= 0) {
    sendError("Giá trị đơn hàng không hợp lệ.", 422);
}
// ⭐ KẾT THÚC TÍNH SUBTOTAL AN TOÀN

// Khởi tạo mảng kết quả
$shippingRates = [];

// 3. LOGIC TÍNH PHÍ VÀ LẤY DANH SÁCH RATE TỪ DB
// ⭐ SỬA LỖI: ĐỒNG BỘ GIÁ TRỊ DỰ PHÒNG
$FREE_SHIP_THRESHOLD = getSetting($mysqli, 'free_ship_threshold', 2500000.00); // <-- ĐÃ SỬA
$DEFAULT_SHIPPING_FEE = getSetting($mysqli, 'base_shipping_fee', 30000.00); 

// ⭐ SỬA LỖI: Dùng $serverSubtotal (an toàn) thay vì $_GET['subtotal']
$isFreeShip = ($serverSubtotal >= $FREE_SHIP_THRESHOLD);

$MAX_RATE_TO_DISPLAY = 100000.00; 

// 4. TRUY VẤN DATABASE để lấy danh sách các dịch vụ đang hoạt động
$sql = "
    SELECT 
        r.rateID, 
        c.carrierName, 
        r.serviceName, 
        r.price AS basePrice, 
        r.estimatedDelivery
    FROM shipping_rates r
    JOIN shipping_carriers c ON r.carrierID = c.carrierID
    WHERE c.isActive = 1 AND r.price <= ? 
    ORDER BY r.price ASC
";

if (!$stmt = $mysqli->prepare($sql)) {
    sendError("Lỗi chuẩn bị truy vấn: " . $mysqli->error, 500);
}

$stmt->bind_param("d", $MAX_RATE_TO_DISPLAY); 
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $rateBasePrice = (float)$row['basePrice'];

    if ($isFreeShip) {
        $rateFee = 0.00;
    } else {
        $rateFee = $rateBasePrice;
    }
    
    if ($rateFee < 0.01 && !$isFreeShip) { 
        $rateFee = $DEFAULT_SHIPPING_FEE;
    }

    $shippingRates[] = [
        'rateID' => (int)$row['rateID'],
        'carrierName' => $row['carrierName'],
        'serviceName' => $row['serviceName'],
        'estimatedDelivery' => $row['estimatedDelivery'],
        'shippingFee' => round($rateFee, 2),
        'isFreeShip' => $isFreeShip
    ];
}
$stmt->close();

// 5. TRẢ VỀ KẾT QUẢ JSON (Giữ nguyên logic dự phòng)
if (empty($shippingRates)) {
    $shippingRates[] = [
        'rateID' => 0,
        'carrierName' => 'Mặc định',
        'serviceName' => 'Giao hàng Tiêu chuẩn',
        'estimatedDelivery' => '5-7 ngày',
        'shippingFee' => round($isFreeShip ? 0.00 : $DEFAULT_SHIPPING_FEE, 2), 
        'isFreeShip' => $isFreeShip
    ];
}

echo json_encode([
    'success' => true,
    'data' => $shippingRates
]);

?>