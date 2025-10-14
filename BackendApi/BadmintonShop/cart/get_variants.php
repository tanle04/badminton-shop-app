<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../bootstrap.php';

if (!isset($_GET['productID']) || !is_numeric($_GET['productID'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Thiếu productID']);
    exit();
}

$productID = (int)$_GET['productID'];

$sql = "SELECT v.variantID, v.price, v.stock, GROUP_CONCAT(pav.valueName SEPARATOR ', ') AS attributes
        FROM product_variants v
        JOIN variant_attribute_values vav ON v.variantID = vav.variantID
        JOIN product_attribute_values pav ON vav.valueID = pav.valueID
        WHERE v.productID = ?
        GROUP BY v.variantID";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $productID);
$stmt->execute();
$result = $stmt->get_result();

$variants = [];
while ($row = $result->fetch_assoc()) {
    $variants[] = $row;
}

echo json_encode(['success' => true, 'variants' => $variants]);

$stmt->close();
$mysqli->close();
?>