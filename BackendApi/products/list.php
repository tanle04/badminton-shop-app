<?php
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

require_once '../bootstrap.php';
require_once '../utils/price_calculator.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }

    // Phân trang
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, min(50, (int)($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    // Sorting (mới)
    $sortBy = $_GET['sortBy'] ?? 'newest'; // newest, price_asc, price_desc, popular
    
    // Xác định ORDER BY
    $orderClause = match($sortBy) {
        'price_asc' => 'p.price ASC',
        'price_desc' => 'p.price DESC',
        'popular' => 'p.productID DESC', // Có thể thay bằng số lượng đã bán
        default => 'p.createdDate DESC' // newest
    };

    $sql = "
    SELECT 
        p.productID, p.productName, p.description,
        p.price AS basePrice,
        p.stock,
        b.brandName, c.categoryName,
        (SELECT pi.imageUrl FROM productimages pi 
            WHERE pi.productID = p.productID 
            ORDER BY 
                CASE pi.imageType 
                    WHEN 'main' THEN 1 
                    ELSE 2 
                END, 
                pi.imageID ASC 
            LIMIT 1
        ) AS imageUrl,
        (SELECT COALESCE(SUM(v.stock - v.reservedStock), 0) 
         FROM product_variants v 
         WHERE v.productID = p.productID AND v.is_active = 1
        ) AS totalAvailableStock,
        (SELECT COALESCE(AVG(r.rating), 0) 
         FROM reviews r 
         WHERE r.productID = p.productID AND r.status = 'published'
        ) AS averageRating,
        (SELECT COUNT(*) 
         FROM reviews r 
         WHERE r.productID = p.productID AND r.status = 'published'
        ) AS reviewCount
    FROM products p
    LEFT JOIN brands b ON b.brandID = p.brandID
    LEFT JOIN categories c ON c.categoryID = p.categoryID
    WHERE p.is_active = 1
    GROUP BY p.productID
    ORDER BY {$orderClause}
    LIMIT ? OFFSET ?";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        respond(['isSuccess' => false, 'message' => 'SQL Prepare failed: ' . $mysqli->error], 500);
    }
    
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $data = [];
    while ($row = $res->fetch_assoc()) {
        $productID = (int)$row['productID'];
        
        // Tính giá sale
        $price_details = get_best_sale_price_for_product_list($mysqli, $productID);

        $row['priceMin'] = $price_details['salePrice'];
        $row['originalPriceMin'] = $price_details['originalPrice']; 
        $row['isDiscounted'] = $price_details['isDiscounted'];
        
        // Xử lý stock
        $totalStock = (int)$row['totalAvailableStock'];
        $row['totalAvailableStock'] = $totalStock;
        $row['isInStock'] = $totalStock > 0;
        $row['stockStatus'] = $totalStock > 10 ? 'in_stock' : ($totalStock > 0 ? 'low_stock' : 'out_of_stock');
        
        // Xử lý rating
        $row['averageRating'] = round((float)$row['averageRating'], 1);
        $row['reviewCount'] = (int)$row['reviewCount'];
        
        // Loại bỏ trường không cần thiết
        unset($row['basePrice']);
        unset($row['stock']);
        
        $data[] = $row;
    }
    
    $stmt->close();

    // Lấy tổng số sản phẩm để tính tổng số trang
    $countSql = "SELECT COUNT(*) as total FROM products WHERE is_active = 1";
    $countResult = $mysqli->query($countSql);
    $totalProducts = $countResult->fetch_assoc()['total'];
    $totalPages = ceil($totalProducts / $limit);

    respond([
        "isSuccess" => true,
        "message" => "Products listed successfully.",
        "page" => $page,
        "limit" => $limit,
        "totalPages" => $totalPages,
        "totalProducts" => (int)$totalProducts,
        "items" => $data
    ]);

} catch (Throwable $e) {
    error_log("Product List API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra phía server: ' . $e->getMessage()], 500);
}