<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../bootstrap.php';

// Nhận dữ liệu POST
$cartID = (int)($_POST['cartID'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? -1);
$customerID = (int)($_POST['customerID'] ?? 0); // Để bảo mật

if ($cartID <= 0 || $quantity < 0 || $customerID <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Dữ liệu không hợp lệ."]);
    exit();
}

if ($quantity > 0) {
    // Nếu số lượng > 0, cập nhật lại số lượng
    $stmt = $mysqli->prepare("UPDATE shopping_cart SET quantity = ? WHERE cartID = ? AND customerID = ?");
    $stmt->bind_param("iii", $quantity, $cartID, $customerID);
} else {
    // Nếu số lượng == 0, xóa sản phẩm
    $stmt = $mysqli->prepare("DELETE FROM shopping_cart WHERE cartID = ? AND customerID = ?");
    $stmt->bind_param("ii", $cartID, $customerID);
}

$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(["success" => true, "message" => "Cập nhật giỏ hàng thành công"]);
} else {
    echo json_encode(["success" => false, "message" => "Không có gì thay đổi"]);
}

$stmt->close();
$mysqli->close();
?>