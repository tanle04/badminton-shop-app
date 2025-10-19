<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../bootstrap.php'; // Chứa hàm respond()

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }

    // ⭐ HÀM PHỤ TRỢ: Lấy URL ảnh chính cho mỗi sản phẩm
    // Sử dụng MIN() trên chuỗi GROUP_CONCAT để lấy URL đầu tiên (hoặc sử dụng subquery)
    $imageSubquery = "(
        SELECT pi.imageUrl FROM productimages pi 
        WHERE pi.productID = p.productID 
        ORDER BY pi.imageID ASC LIMIT 1
    ) AS imageUrl";

    // --- 1. Coming Soon (Stock = 0) ---
    // Giả định bạn muốn sản phẩm sắp về (Coming Soon) dựa trên stockTotal=0 hoặc là một flag riêng
    $coming = $mysqli->query("
        SELECT p.productID, p.productName, p.price, COALESCE({$imageSubquery}, '') AS imageUrl
        FROM products p
        LEFT JOIN product_variants pv ON pv.productID = p.productID
        GROUP BY p.productID
        HAVING SUM(pv.stock) = 0 
        ORDER BY p.productID DESC LIMIT 10
    ")->fetch_all(MYSQLI_ASSOC);

    // --- 2. New Arrivals (Sort by createdDate desc) ---
    $newArrivals = $mysqli->query("
        SELECT p.productID, p.productName, p.price, COALESCE({$imageSubquery}, '') AS imageUrl
        FROM products p
        ORDER BY p.createdDate DESC
        LIMIT 10
    ")->fetch_all(MYSQLI_ASSOC);

    // --- 3. Best Selling (Từ orderdetails aggregate) ---
    $bestSelling = $mysqli->query("
        SELECT 
            p.productID, p.productName, p.price, COALESCE({$imageSubquery}, '') AS imageUrl,
            SUM(od.quantity) as sold
        FROM orderdetails od
        JOIN product_variants pv ON pv.variantID = od.variantID
        JOIN products p ON p.productID = pv.productID
        GROUP BY p.productID
        ORDER BY sold DESC
        LIMIT 10
    ")->fetch_all(MYSQLI_ASSOC);

    // --- 4. Featured (Đang có promotions) ---
    // ⭐ SỬA: Dùng MySQL CURDATE() và NOW()
    $featured = $mysqli->query("
        SELECT DISTINCT p.productID, p.productName, p.price, COALESCE({$imageSubquery}, '') AS imageUrl
        FROM promotionproducts pp
        JOIN promotions pr ON pr.promoID = pp.promoID
        JOIN products p ON p.productID = pp.productID
        WHERE DATE(pr.startDate) <= CURDATE() AND DATE(pr.endDate) >= CURDATE()
        ORDER BY p.productID DESC
        LIMIT 10
    ")->fetch_all(MYSQLI_ASSOC);

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
// Lưu ý: Thẻ đóng ?> đã được loại bỏ.