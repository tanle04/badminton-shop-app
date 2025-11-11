<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../bootstrap.php'; // Mở db + headers + hàm respond()
require_once __DIR__ . '/../utils/price_calculator.php'; // ⭐ THÊM: Logic tính giá sale

try {
    // Kiểm tra phương thức
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }

    // --- Lấy tất cả các tham số lọc có thể có từ URL ---
    $categoryName = isset($_GET['category']) ? trim($_GET['category']) : null;
    $brandName = isset($_GET['brand']) ? trim($_GET['brand']) : null;
    $price_min_filter = isset($_GET['price_min']) && is_numeric($_GET['price_min']) ? (float)$_GET['price_min'] : null;
    $price_max_filter = isset($_GET['price_max']) && is_numeric($_GET['price_max']) ? (float)$_GET['price_max'] : null;

    // Phân trang
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, min(50, (int)($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;

    // --- Bắt đầu xây dựng câu lệnh SQL ---
    $sql = "
        SELECT 
            p.productID, p.productName, p.description,
            p.price AS basePrice, /* ⭐ Lấy giá base để tính toán */
            b.brandName, c.categoryName,
            (SELECT pi.imageUrl FROM productimages pi 
                WHERE pi.productID = p.productID ORDER BY pi.imageType ASC, pi.imageID ASC LIMIT 1) AS imageUrl
        FROM products p
        LEFT JOIN product_variants v ON v.productID = p.productID
        LEFT JOIN brands b ON b.brandID = p.brandID
        LEFT JOIN categories c ON p.categoryID = c.categoryID
    ";

    $whereClauses = []; 
    $params = []; 
    $types = ""; 

    // 0. ĐIỀU KIỆN CỐ ĐỊNH: Chỉ lấy sản phẩm đang hoạt động
    $whereClauses[] = "p.is_active = 1";

    // 1. Lọc theo Danh mục
    if ($categoryName && strtolower($categoryName) !== 'featured' && strtolower($categoryName) !== 'all') {
        $whereClauses[] = "c.categoryName = ?";
        $params[] = $categoryName;
        $types .= "s";
    }

    // 2. Lọc theo Thương hiệu
    if ($brandName && strtolower($brandName) !== 'tất cả') {
        $whereClauses[] = "b.brandName = ?";
        $params[] = $brandName;
        $types .= "s";
    }

    // 3. Lọc theo Giá (Dùng giá basePrice/price của sản phẩm để lọc)
    if ($price_min_filter !== null) {
        $whereClauses[] = "p.price >= ?";
        $params[] = $price_min_filter;
        $types .= "d";
    }
    if ($price_max_filter !== null) {
        $whereClauses[] = "p.price <= ?";
        $params[] = $price_max_filter;
        $types .= "d";
    }

    // --- Ghép các điều kiện WHERE lại ---
    if (!empty($whereClauses)) {
        $sql .= " WHERE " . implode(" AND ", $whereClauses);
    }

    // Thêm phần còn lại của câu lệnh
    $sql .= " GROUP BY p.productID ORDER BY p.createdDate DESC LIMIT ? OFFSET ?";
    
    // Thêm các tham số phân trang vào cuối
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        respond(['isSuccess' => false, 'message' => 'SQL Prepare failed: ' . $mysqli->error], 500);
    }

    // Bind các tham số một cách linh hoạt (cách dùng call_user_func_array của bạn)
    if (!empty($types)) {
        $bind_names = array($types);
        for ($i = 0; $i < count($params); $i++) {
            $bind_name = 'p' . $i;
            $$bind_name = $params[$i];
            $bind_names[] = &$$bind_name;
        }
        call_user_func_array(array($stmt, 'bind_param'), $bind_names);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    
    while ($row = $result->fetch_assoc()) {
        $productID = (int)$row['productID'];
        
        // ⭐ BƯỚC QUAN TRỌNG: GỌI HÀM TÍNH GIÁ SALE VÀ GHI ĐÈ GIÁ
        $price_details = get_best_sale_price_for_product_list($mysqli, $productID);

        // priceMin là trường mà Android đang đọc là giá cuối cùng
        $row['priceMin'] = $price_details['salePrice'];
        
        // Gán các cờ sale cần thiết cho ProductDto chính
        $row['originalPriceMin'] = $price_details['originalPrice']; 
        $row['isDiscounted'] = $price_details['isDiscounted'];

        // ✅ SỬA LỖI URL ẢNH: Tạo URL ảnh đầy đủ
        if (!empty($row['imageUrl'])) {
            // Dùng https và trỏ đến thư mục storage public của AdminPanel
            $base_url = 'https://' . $_SERVER['HTTP_HOST'] . '/admin/public/storage/';
            
            // $row['imageUrl'] từ DB đã có dạng: "products/ten_file.jpg"
            $row['imageUrl'] = $base_url . $row['imageUrl'];
        }
        
        // Loại bỏ trường basePrice không cần thiết trên Android
        unset($row['basePrice']); 
        
        $data[] = $row;
    }
    
    $stmt->close();

    // Trả về phản hồi THÀNH CÔNG
    respond([
        "isSuccess" => true,
        "message" => "Products filtered successfully.",
        "page" => $page,
        "items" => $data
    ]);

} catch (Throwable $e) {
    // Xử lý lỗi bất ngờ 
    error_log("Filter API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra phía server: ' . $e->getMessage()], 500);
}