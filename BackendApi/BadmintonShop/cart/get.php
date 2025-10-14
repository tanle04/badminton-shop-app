<?php
require_once '../bootstrap.php'; // Điều chỉnh đường dẫn đến file bootstrap

// Lấy customerID từ request (GET)
if (!isset($_GET['customerID']) || !is_numeric($_GET['customerID'])) {
    http_response_code(400); // Bad Request
    echo json_encode(["success" => false, "message" => "customerID không hợp lệ"]);
    exit();
}
$customerID = (int)$_GET['customerID'];

// Câu SQL JOIN nhiều bảng để lấy thông tin chi tiết
$sql = "
    SELECT
        sc.cartID,
        sc.quantity,
        p.productID,
        p.productName,
        pv.variantID,
        pv.price AS variantPrice,
        (SELECT pi.imageUrl FROM productimages pi WHERE pi.productID = p.productID ORDER BY pi.imageID ASC LIMIT 1) AS imageUrl,
        GROUP_CONCAT(CONCAT(pa.attributeName, ': ', pav.valueName) SEPARATOR ', ') AS variantDetails
    FROM shopping_cart sc
    JOIN product_variants pv ON sc.variantID = pv.variantID
    JOIN products p ON pv.productID = p.productID
    LEFT JOIN variant_attribute_values vav ON pv.variantID = vav.variantID
    LEFT JOIN product_attribute_values pav ON vav.valueID = pav.valueID
    LEFT JOIN product_attributes pa ON pav.attributeID = pa.attributeID
    WHERE sc.customerID = ?
    GROUP BY sc.cartID
    ORDER BY sc.addedDate DESC
";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $customerID);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($row = $res->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode(["success" => true, "items" => $data], JSON_UNESCAPED_UNICODE);
$stmt->close();
$mysqli->close();
?>