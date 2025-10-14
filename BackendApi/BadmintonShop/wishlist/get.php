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
    ) AS imageUrl
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
    // Lấy giá trị ảnh và đặt tên là $img
    $img = $row['imageUrl'] ?? "";
    
    // Loại bỏ logic nối URL, chỉ giữ lại tên file
    if ($img && preg_match('/^http/', $img)) {
        // Cắt bỏ prefix nếu backend cũ trả full URL
        $img = basename($img); 
    }
    
    // Đảm bảo trả về tên file ảnh hoặc mặc định nếu không có
    $row['imageUrl'] = $img ?: "no_image.png"; 

    $wishlist[] = $row;
}

echo json_encode([
    "success" => true,
    "count" => count($wishlist),
    "wishlist" => $wishlist
], JSON_UNESCAPED_UNICODE);

$stmt->close();
$mysqli->close();
