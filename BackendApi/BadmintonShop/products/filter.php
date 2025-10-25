<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../bootstrap.php'; // Mở db + headers + hàm respond()

try {
    // Kiểm tra phương thức
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }

    // --- Lấy tất cả các tham số lọc có thể có từ URL ---
    $categoryName = isset($_GET['category']) ? trim($_GET['category']) : null;
    $brandName = isset($_GET['brand']) ? trim($_GET['brand']) : null;
    // Đọc giá trị và đảm bảo là float hoặc null
    $price_min = isset($_GET['price_min']) && is_numeric($_GET['price_min']) ? (float)$_GET['price_min'] : null;
    $price_max = isset($_GET['price_max']) && is_numeric($_GET['price_max']) ? (float)$_GET['price_max'] : null;

    // Phân trang
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, min(50, (int)($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;

    // --- Bắt đầu xây dựng câu lệnh SQL một cách linh hoạt ---
    $sql = "
        SELECT 
            p.productID, p.productName, p.description,
            COALESCE(MIN(v.price), p.price) AS priceMin,
            b.brandName, c.categoryName,
            (SELECT pi.imageUrl FROM productimages pi 
               WHERE pi.productID = p.productID ORDER BY pi.imageType ASC, pi.imageID ASC LIMIT 1) AS imageUrl
        FROM products p
        LEFT JOIN product_variants v ON v.productID = p.productID
        LEFT JOIN brands b ON b.brandID = p.brandID
        LEFT JOIN categories c ON c.categoryID = p.categoryID
    ";

    $whereClauses = []; 
    $params = []; 
    $types = ""; 

    // 0. ĐIỀU KIỆN CỐ ĐỊNH: Chỉ lấy sản phẩm đang hoạt động (p.is_active = 1)
    $whereClauses[] = "p.is_active = 1"; // ⭐ ĐÃ THÊM

    // 1. Thêm điều kiện lọc theo Danh mục (nếu có)
    if ($categoryName && strtolower($categoryName) !== 'featured' && strtolower($categoryName) !== 'all') {
        $whereClauses[] = "c.categoryName = ?";
        $params[] = $categoryName;
        $types .= "s";
    }

    // 2. Thêm điều kiện lọc theo Thương hiệu (nếu có)
    if ($brandName && strtolower($brandName) !== 'tất cả') {
        $whereClauses[] = "b.brandName = ?";
        $params[] = $brandName;
        $types .= "s";
    }

    // 3. Thêm điều kiện lọc theo Giá
    if ($price_min !== null) {
        $whereClauses[] = "p.price >= ?";
        $params[] = $price_min;
        $types .= "d";
    }
    if ($price_max !== null) {
        $whereClauses[] = "p.price <= ?";
        $params[] = $price_max;
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

    // Xử lý lỗi prepare (nếu có)
    if (!$stmt) {
        respond(['isSuccess' => false, 'message' => 'SQL Prepare failed: ' . $mysqli->error], 500);
    }

    // Bind các tham số một cách linh hoạt
    if (!empty($types)) {
        // Sử dụng call_user_func_array để bind động
        $bind_names = array($types);
        for ($i = 0; $i < count($params); $i++) {
            $bind_name = 'p' . $i;
            $$bind_name = $params[$i]; // Tạo biến động
            $bind_names[] = &$$bind_name; // Lấy tham chiếu
        }
        call_user_func_array(array($stmt, 'bind_param'), $bind_names);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);

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
    // Trả về phản hồi THẤT BẠI
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra phía server: ' . $e->getMessage()], 500);
}
// Lưu ý: Thẻ đóng ?> bị loại bỏ.