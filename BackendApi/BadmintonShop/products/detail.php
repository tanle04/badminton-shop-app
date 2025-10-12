<?php
header("Content-Type: application/json; charset=UTF-8");
require_once("../bootstrap.php");

if (!isset($_GET['productID'])) {
    echo json_encode(["success" => false, "message" => "Thiếu productID"]);
    exit;
}

$productID = intval($_GET['productID']);

// Lấy thông tin sản phẩm
$sqlProduct = "SELECT p.*, b.brandName, c.categoryName
               FROM products p
               LEFT JOIN brands b ON p.brandID = b.brandID
               LEFT JOIN categories c ON p.categoryID = c.categoryID
               WHERE p.productID = ?";
$stmt = $mysqli->prepare($sqlProduct);
$stmt->bind_param("i", $productID);
$stmt->execute();
$res = $stmt->get_result();
$product = $res->fetch_assoc();

if (!$product) {
    echo json_encode(["success" => false, "message" => "Không tìm thấy sản phẩm"]);
    exit;
}

// Ảnh sản phẩm
$sqlImg = "SELECT imageUrl FROM productimages WHERE productID = ? ORDER BY imageID ASC";
$stmtImg = $mysqli->prepare($sqlImg);
$stmtImg->bind_param("i", $productID);
$stmtImg->execute();
$resImg = $stmtImg->get_result();
$images = [];
while ($row = $resImg->fetch_assoc()) {
    if (!preg_match('/^http/', $row['imageUrl'])) {
        $row['imageUrl'] = "http://10.0.2.2/api/BadmintonShop/uploads/" . $row['imageUrl'];
    }
    $images[] = $row;
}
$product['images'] = $images;

// ✅ Lấy danh sách biến thể (size, giá, tồn kho)
$sqlVariants = "
    SELECT v.variantID, v.sku, v.price, v.stock, GROUP_CONCAT(pav.valueName SEPARATOR ', ') AS attributes
    FROM product_variants v
    LEFT JOIN variant_attribute_values vv ON v.variantID = vv.variantID
    LEFT JOIN product_attribute_values pav ON vv.valueID = pav.valueID
    WHERE v.productID = ?
    GROUP BY v.variantID
    ORDER BY v.price ASC
";
$stmtVar = $mysqli->prepare($sqlVariants);
$stmtVar->bind_param("i", $productID);
$stmtVar->execute();
$resVar = $stmtVar->get_result();
$variants = [];
while ($row = $resVar->fetch_assoc()) {
    $variants[] = $row;
}
$product['variants'] = $variants;

$product['success'] = true;

echo json_encode($product, JSON_UNESCAPED_UNICODE);
$stmt->close();
$stmtImg->close();
$stmtVar->close();
$mysqli->close();
