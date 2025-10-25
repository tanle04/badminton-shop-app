<?php
// GET ?page=1&limit=10
require_once '../bootstrap.php'; // mở db + headers

// Hàm trả về JSON nhất quán
function json_response($isSuccess, $message, $page = null, $items = null) {
    $response = [
        'isSuccess' => $isSuccess,
        'message' => $message
    ];
    if ($page !== null) {
        $response['page'] = $page;
    }
    if ($items !== null) {
        $response['items'] = $items;
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, min(50, (int)($_GET['limit'] ?? 10)));
$offset = ($page-1)*$limit;

$sql = "
SELECT 
    p.productID, p.productName, p.description,
    COALESCE(MIN(v.price), p.price) AS priceMin,
    COALESCE(SUM(v.stock), p.stock) AS stockTotal,
    b.brandName, c.categoryName,
    (SELECT pi.imageUrl FROM productimages pi 
        WHERE pi.productID = p.productID ORDER BY pi.imageID ASC LIMIT 1) AS imageUrl
FROM products p
LEFT JOIN product_variants v ON v.productID = p.productID
LEFT JOIN brands b ON b.brandID = p.brandID
LEFT JOIN categories c ON c.categoryID = p.categoryID
WHERE p.is_active = 1  /* ĐIỀU KIỆN LỌC SẢN PHẨM HOẠT ĐỘNG */
GROUP BY p.productID
ORDER BY p.createdDate DESC
LIMIT ? OFFSET ?";

try {
    // Tắt các thông báo lỗi nếu cần (nếu không thể sửa bootstrap)
    // error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING); 
    // ini_set('display_errors', 0); 
    
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        json_response(false, "SQL Prepare failed: " . $mysqli->error);
    }
    
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
    
    $stmt->close();

    // GỌI HÀM TRẢ VỀ JSON HỢP LỆ VỚI isSuccess
    json_response(true, "Products listed successfully.", $page, $data);

} catch (Exception $e) {
    if (isset($stmt)) $stmt->close();
    
    // XỬ LÝ LỖI DB VÀ TRẢ VỀ isSuccess: false
    json_response(false, "Database Error: " . $e->getMessage());
}