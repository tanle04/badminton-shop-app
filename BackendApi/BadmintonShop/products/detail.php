<?php
header("Content-Type: application/json; charset=UTF-8");
require_once("../bootstrap.php"); // ⚠ dùng bootstrap.php chứ không phải db.php

if (!isset($_GET['productID'])) {
    echo json_encode(["success" => false, "message" => "Thiếu productID"]);
    exit;
}

$productID = intval($_GET['productID']);

// Lấy thông tin sản phẩm
$sqlProduct = "SELECT * FROM products WHERE productID = ?";
$stmt = $mysqli->prepare($sqlProduct);
$stmt->bind_param("i", $productID);
$stmt->execute();
$res = $stmt->get_result();
$product = $res->fetch_assoc();

if (!$product) {
    echo json_encode(["success" => false, "message" => "Không tìm thấy sản phẩm"]);
    exit;
}

// Lấy danh sách ảnh của sản phẩm
$sqlImg = "SELECT imageUrl, imageType, sortOrder 
           FROM productimages 
           WHERE productID = ? 
           ORDER BY sortOrder ASC";
$stmtImg = $mysqli->prepare($sqlImg);
$stmtImg->bind_param("i", $productID);
$stmtImg->execute();
$resImg = $stmtImg->get_result();

$images = [];
while ($row = $resImg->fetch_assoc()) {
    // Nếu ảnh chỉ là tên file (vd: “abc.png”), thêm đường dẫn thật
    if (!preg_match('/^http/', $row['imageUrl'])) {
        $row['imageUrl'] = "http://10.0.2.2/api/BadmintonShop/uploads/" . $row['imageUrl'];
    }
    $images[] = $row;
}

$product['images'] = $images;
$product['success'] = true;

echo json_encode($product, JSON_UNESCAPED_UNICODE);
$stmt->close();
$stmtImg->close();
$mysqli->close();
