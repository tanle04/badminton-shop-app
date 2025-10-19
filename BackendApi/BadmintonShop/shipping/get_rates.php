<?php
// GET
// Tải danh sách các phương thức vận chuyển và phí tương ứng.

// Đường dẫn giả định đến file kết nối database và headers
require_once '../../bootstrap.php'; 

// Truy vấn lấy các mức phí vận chuyển đang hoạt động
$sql = "
SELECT 
    sr.rateID, 
    sr.serviceName, 
    sr.price, 
    sr.estimatedDelivery,
    sc.carrierName
FROM shipping_rates sr
JOIN shipping_carriers sc ON sc.carrierID = sr.carrierID
WHERE sc.isActive = 1
ORDER BY sr.price ASC";

$stmt = $mysqli->prepare($sql);
// Không cần bind param vì không có điều kiện động
$stmt->execute();
$res = $stmt->get_result();

$rates = [];
while ($row = $res->fetch_assoc()) {
    // Chuyển đổi giá trị Decimal sang float
    $row['price'] = (float) $row['price'];
    $rates[] = $row;
}

// Trả về JSON theo cấu trúc tương tự API Response
echo json_encode([
    "isSuccess" => true,
    "shippingRates" => $rates
], JSON_UNESCAPED_UNICODE);

$stmt->close();
$mysqli->close();
?>
