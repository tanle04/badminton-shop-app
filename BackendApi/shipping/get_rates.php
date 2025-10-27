<?php
// BẬT DEBUG: HIỂN THỊ LỖI TRONG MÔI TRƯỜM DEV
error_reporting(E_ALL); 
ini_set('display_errors', 1);

// Thiết lập JSON Header
header('Content-Type: application/json');

// Tải các file cần thiết (Đảm bảo bootstrap.php chứa kết nối $mysqli)
require_once __DIR__ . '/../bootstrap.php'; 
// ⭐ THÊM REQUIRE CHO SHIPPING HELPER ĐỂ CÓ HÀM getSetting
require_once __DIR__ . '/../utils/shipping_helper.php'; 

// Hàm tiện ích trả về JSON lỗi và dừng script
function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// 1. LẤY THAM SỐ ĐẦU VÀO
$subtotal = (float)($_GET['subtotal'] ?? 0);
$addressID = (int)($_GET['addressID'] ?? 0); 

if ($subtotal <= 0) {
    sendError("Giá trị đơn hàng không hợp lệ.", 422);
}

// Khởi tạo mảng kết quả
$shippingRates = [];

// 2. LOGIC TÍNH PHÍ VÀ LẤY DANH SÁCH RATE TỪ DB

// ⭐ SỬA LỖI: ĐỌC NGƯỠNG TỪ DB (app_settings) ⭐
// Giả định getSetting là hàm đã được định nghĩa trong shipping_helper.php
$FREE_SHIP_THRESHOLD = getSetting($mysqli, 'free_ship_threshold', 2000000.00);
$DEFAULT_SHIPPING_FEE = getSetting($mysqli, 'base_shipping_fee', 30000.00); 

// Kiểm tra điều kiện Free Ship
$isFreeShip = ($subtotal >= $FREE_SHIP_THRESHOLD);

// Khóa cấu hình hiển thị (Giữ nguyên)
$MAX_RATE_TO_DISPLAY = 100000.00; 

// 3. TRUY VẤN DATABASE để lấy danh sách các dịch vụ đang hoạt động
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

// Lấy các rate có giá gốc <= MAX_RATE_TO_DISPLAY
$stmt->bind_param("d", $MAX_RATE_TO_DISPLAY); 
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    
    $rateBasePrice = (float)$row['basePrice'];

    // 1. Nếu Free Ship: Phí luôn là 0.00.
    if ($isFreeShip) {
        $rateFee = 0.00;
    } else {
        // 2. Nếu KHÔNG Free Ship: Phí là giá gốc của dịch vụ (basePrice).
        $rateFee = $rateBasePrice;
    }
    
    // ⭐ XỬ LÝ LỖI DỮ LIỆU: Nếu phí gốc là 0 (hoặc rất nhỏ) và KHÔNG phải Freeship, dùng phí mặc định
    if ($rateFee < 0.01 && !$isFreeShip) { 
        $rateFee = $DEFAULT_SHIPPING_FEE;
    }

    $shippingRates[] = [
        'rateID' => (int)$row['rateID'],
        'carrierName' => $row['carrierName'],
        'serviceName' => $row['serviceName'],
        'estimatedDelivery' => $row['estimatedDelivery'],
        'shippingFee' => round($rateFee, 2), // Phí đã được tính toán (0 nếu Freeship)
        'isFreeShip' => $isFreeShip
    ];
}

$stmt->close();

// 4. TRẢ VỀ KẾT QUẢ JSON
if (empty($shippingRates)) {
    // Nếu không tìm thấy rate nào trong DB, ta vẫn có thể trả về một lựa chọn mặc định.
    $shippingRates[] = [
        'rateID' => 0, // ID 0 cho rate mặc định/khẩn cấp
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