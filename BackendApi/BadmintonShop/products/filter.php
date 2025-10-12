<?php
header("Content-Type: application/json; charset=UTF-8");
require_once("../bootstrap.php");

// Lấy tên danh mục
$category = isset($_GET['category']) ? trim($_GET['category']) : "";

if ($category === "") {
    echo json_encode(["success" => false, "message" => "Thiếu tham số category"]);
    exit;
}

// Phân trang
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, min(50, (int)($_GET['limit'] ?? 20)));
$offset = ($page - 1) * $limit;

// ✅ Giống list.php, chỉ khác có điều kiện lọc theo category
$sql = "
SELECT 
  p.productID, 
  p.productName, 
  p.description,
  COALESCE(MIN(v.price), p.price) AS priceMin,
  COALESCE(SUM(v.stock), p.stock) AS stockTotal,
  b.brandName, 
  c.categoryName,
  (
    SELECT pi.imageUrl 
    FROM productimages pi 
    WHERE pi.productID = p.productID 
    ORDER BY pi.imageID ASC LIMIT 1
  ) AS imageUrl
FROM products p
LEFT JOIN product_variants v ON v.productID = p.productID
LEFT JOIN brands b ON b.brandID = p.brandID
LEFT JOIN categories c ON c.categoryID = p.categoryID
WHERE c.categoryName = ?
GROUP BY p.productID
ORDER BY p.createdDate DESC
LIMIT ? OFFSET ?";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("sii", $category, $limit, $offset);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($row = $res->fetch_assoc()) {
    // ✅ Trả lại chỉ tên file ảnh
    $img = $row['imageUrl'] ?? "";
    if ($img && preg_match('/^http/', $img)) {
        // Cắt bỏ prefix nếu backend cũ trả full URL
        $img = basename($img);
    }
    $row['imageUrl'] = $img ?: "no_image.png";
    $data[] = $row;
}

echo json_encode([
    "success" => true,
    "page" => $page,
    "items" => $data
], JSON_UNESCAPED_UNICODE);

$stmt->close();
$mysqli->close();
?>
