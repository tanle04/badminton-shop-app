<?php
header("Content-Type: application/json; charset=UTF-8");
require_once("../bootstrap.php");

if (!isset($_GET['customerID'])) {
    echo json_encode(["success" => false, "message" => "Thiếu customerID"]);
    exit;
}

$customerID = intval($_GET['customerID']);

$sql = "
SELECT 
    p.productID, 
    p.productName, 
    p.price, 
    b.brandName,
    (
        SELECT pi.imageUrl 
        FROM productimages pi 
        WHERE pi.productID = p.productID 
        ORDER BY pi.imageID ASC 
        LIMIT 1
    ) AS thumbnail
FROM wishlists w
JOIN products p ON w.productID = p.productID
LEFT JOIN brands b ON p.brandID = b.brandID
WHERE w.customerID = ?
ORDER BY w.created_at DESC
";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $customerID);
$stmt->execute();
$res = $stmt->get_result();

$wishlist = [];
while ($row = $res->fetch_assoc()) {
    // Chuẩn hóa URL ảnh 1
    if (!empty($row["thumbnail"]) && !preg_match("/^http/", $row["thumbnail"])) {
        $row["thumbnail"] = "http://10.0.2.2/api/BadmintonShop/uploads/" . $row["thumbnail"];
    }
    $wishlist[] = $row;
}

echo json_encode([
    "success" => true,
    "count" => count($wishlist),
    "wishlist" => $wishlist
], JSON_UNESCAPED_UNICODE);

$stmt->close();
$mysqli->close();
