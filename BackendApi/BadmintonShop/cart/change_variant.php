<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../bootstrap.php';

$cartID = (int)($_POST['cartID'] ?? 0);
$newVariantID = (int)($_POST['newVariantID'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 0);
$customerID = (int)($_POST['customerID'] ?? 0);

if ($cartID <= 0 || $newVariantID <= 0 || $quantity <= 0 || $customerID <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Dữ liệu không hợp lệ."]);
    exit();
}

// Cập nhật dòng đã có trong giỏ hàng
$stmt = $mysqli->prepare("UPDATE shopping_cart SET variantID = ?, quantity = ? WHERE cartID = ? AND customerID = ?");
$stmt->bind_param("iiii", $newVariantID, $quantity, $cartID, $customerID);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(["success" => true, "message" => "Đã cập nhật sản phẩm trong giỏ hàng"]);
} else {
    echo json_encode(["success" => false, "message" => "Không có gì thay đổi"]);
}

$stmt->close();
$mysqli->close();
?>