<?php
header("Content-Type: application/json; charset=UTF-8");
require_once("../bootstrap.php");

$input = json_decode(file_get_contents("php://input"), true);
if (!$input || !isset($input['customerID']) || !isset($input['productID'])) {
    echo json_encode(["success" => false, "message" => "Thiếu dữ liệu yêu cầu"]);
    exit;
}

$customerID = intval($input['customerID']);
$productID = intval($input['productID']);

$sql = "DELETE FROM wishlists WHERE customerID = ? AND productID = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("ii", $customerID, $productID);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Đã xóa khỏi wishlist"]);
} else {
    echo json_encode(["success" => false, "message" => "Không thể xóa"]);
}

$stmt->close();
$mysqli->close();
