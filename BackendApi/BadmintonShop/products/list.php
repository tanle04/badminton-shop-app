<?php
// GET ?page=1&limit=10
require_once '../bootstrap.php'; // mở db + headers
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, min(50, (int)($_GET['limit'] ?? 10)));
$offset = ($page-1)*$limit;

$sql = "
SELECT 
  p.productID, p.productName, p.description,
  COALESCE(MIN(v.price), p.price)       AS priceMin,
  COALESCE(SUM(v.stock), p.stock)       AS stockTotal,
  b.brandName, c.categoryName,
  (SELECT pi.imageUrl FROM productimages pi 
     WHERE pi.productID = p.productID ORDER BY pi.imageID ASC LIMIT 1) AS imageUrl
FROM products p
LEFT JOIN product_variants v ON v.productID = p.productID
LEFT JOIN brands b ON b.brandID = p.brandID
LEFT JOIN categories c ON c.categoryID = p.categoryID
GROUP BY p.productID
ORDER BY p.createdDate DESC
LIMIT ? OFFSET ?";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$res = $stmt->get_result();
$data = [];
while ($row = $res->fetch_assoc()) $data[] = $row;
echo json_encode(["page"=>$page, "items"=>$data], JSON_UNESCAPED_UNICODE);
$stmt->close();
$mysqli->close();