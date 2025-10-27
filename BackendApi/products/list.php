<?php
// GET ?page=1&limit=10
require_once '../bootstrap.php'; // mở db + headers
// ⭐ THÊM: INCLUDE PRICE CALCULATOR
require_once '../utils/price_calculator.php';

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
    p.price AS priceMin, /* Dùng p.price làm giá trị ban đầu (sẽ bị ghi đè) */
    COALESCE(SUM(v.stock), p.stock) AS stockTotal,
    b.brandName, c.categoryName,
    (SELECT pi.imageUrl FROM productimages pi 
        WHERE pi.productID = p.productID ORDER BY pi.imageID ASC LIMIT 1) AS imageUrl
FROM products p
LEFT JOIN product_variants v ON v.productID = p.productID
LEFT JOIN brands b ON b.brandID = p.brandID
LEFT JOIN categories c ON c.categoryID = p.categoryID
WHERE p.is_active = 1 
GROUP BY p.productID
ORDER BY p.createdDate DESC
LIMIT ? OFFSET ?";

try {
    // ... (Prepare và execute SQL) ...
    
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        json_response(false, "SQL Prepare failed: " . $mysqli->error);
    }
    
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $data = [];
    while ($row = $res->fetch_assoc()) {
        $productID = (int)$row['productID'];
        
        // ⭐ BƯỚC QUAN TRỌNG: GỌI HÀM TÍNH GIÁ SALE VÀ GHI ĐÈ GIÁ MIN
        $price_details = get_best_sale_price_for_product_list($mysqli, $productID);

        $row['priceMin'] = $price_details['salePrice'];
        // Gán các cờ sale cần thiết cho ProductDto chính
        $row['originalPriceMin'] = $price_details['originalPrice']; 
        $row['isDiscounted'] = $price_details['isDiscounted'];
        
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