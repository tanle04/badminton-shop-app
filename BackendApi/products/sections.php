<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../bootstrap.php'; // Chứa hàm respond() và $mysqli
require_once '../utils/price_calculator.php'; // ⭐ THÊM: Logic tính giá sale

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }

    // ⭐ HÀM PHỤ TRỢ: Lấy URL ảnh chính cho mỗi sản phẩm (Giữ nguyên)
    $imageSubquery = "(
        SELECT pi.imageUrl FROM productimages pi 
        WHERE pi.productID = p.productID 
        ORDER BY pi.imageID ASC LIMIT 1
    ) AS imageUrl";

    // Hàm lấy dữ liệu và áp dụng Sale Price
    function fetch_products_with_sale($mysqli, $sql, $imageSubquery) {
        $result = $mysqli->query($sql)->fetch_all(MYSQLI_ASSOC);
        $processed_data = [];

        foreach ($result as $row) {
            $productID = (int)$row['productID'];
            
            // ⭐ GỌI HÀM TÍNH GIÁ SALE VÀ GHI ĐÈ GIÁ
            // Lấy giá sale thấp nhất và cờ sale
            $price_details = get_best_sale_price_for_product_list($mysqli, $productID);

            // Ghi đè giá hiển thị bằng giá sale cuối cùng
            $row['price'] = $price_details['salePrice'];
            
            // Thêm các cờ sale cho Android
            $row['originalPriceMin'] = $price_details['originalPrice']; 
            $row['isDiscounted'] = $price_details['isDiscounted'];
            
            // Ép kiểu (price, productID)
            $row['productID'] = $productID;
            $row['price'] = (float)$row['price'];
            
            // Xóa trường 'sold' nếu không cần thiết
            if (isset($row['sold'])) {
                 $row['sold'] = (int)$row['sold'];
            }
            
            $processed_data[] = $row;
        }
        return $processed_data;
    }

    // --- 1. Coming Soon (Stock = 0) ---
    $sql_coming = "
        SELECT p.productID, p.productName, p.price, COALESCE({$imageSubquery}, '') AS imageUrl
        FROM products p
        LEFT JOIN product_variants pv ON pv.productID = p.productID
        WHERE p.is_active = 1
        GROUP BY p.productID
        HAVING COALESCE(SUM(pv.stock), p.stock) = 0 
        ORDER BY p.productID DESC LIMIT 10
    ";
    $coming = fetch_products_with_sale($mysqli, $sql_coming, $imageSubquery);

    // --- 2. New Arrivals (Sort by createdDate desc) ---
    $sql_new = "
        SELECT p.productID, p.productName, p.price, COALESCE({$imageSubquery}, '') AS imageUrl
        FROM products p
        WHERE p.is_active = 1
        ORDER BY p.createdDate DESC
        LIMIT 10
    ";
    $newArrivals = fetch_products_with_sale($mysqli, $sql_new, $imageSubquery);

    // --- 3. Best Selling (Từ orderdetails aggregate) ---
    $sql_best = "
        SELECT 
            p.productID, p.productName, p.price, COALESCE({$imageSubquery}, '') AS imageUrl,
            SUM(od.quantity) as sold
        FROM orderdetails od
        JOIN product_variants pv ON pv.variantID = od.variantID
        JOIN products p ON p.productID = pv.productID
        WHERE p.is_active = 1
        GROUP BY p.productID
        ORDER BY sold DESC
        LIMIT 10
    ";
    $bestSelling = fetch_products_with_sale($mysqli, $sql_best, $imageSubquery);

    // --- 4. Featured (Sản phẩm đang có chương trình product_discounts) ---
    $sql_featured = "
        SELECT DISTINCT p.productID, p.productName, p.price, COALESCE({$imageSubquery}, '') AS imageUrl
        FROM products p
        JOIN product_variants pv ON pv.productID = p.productID
        /* JOIN với product_discounts qua category/brand/product/variant */
        WHERE p.is_active = 1
        AND EXISTS (
            SELECT 1 FROM product_discounts pd
            WHERE pd.isActive = 1 AND pd.startDate <= NOW() AND pd.endDate >= NOW()
            AND (
                (pd.appliedToType = 'variant' AND pd.appliedToID = pv.variantID) OR
                (pd.appliedToType = 'product' AND pd.appliedToID = p.productID) OR
                (pd.appliedToType = 'brand' AND pd.appliedToID = p.brandID) OR
                (pd.appliedToType = 'category' AND pd.appliedToID = p.categoryID)
            )
        )
        ORDER BY p.productID DESC
        LIMIT 10
    ";
    $featured = fetch_products_with_sale($mysqli, $sql_featured, $imageSubquery);


    // ⭐ GÓI GỌN PHẢN HỒI CUỐI CÙNG
    respond([
        'isSuccess' => true,
        'message' => 'Home data loaded successfully.',
        'comingSoon'  => $coming,
        'newArrivals' => $newArrivals,
        'bestSelling' => $bestSelling,
        'featured'    => $featured
    ]);

} catch (Throwable $e) {
    error_log("Home Data API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra phía server.' . $e->getMessage()], 500);
}