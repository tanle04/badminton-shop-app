<?php
header("Content-Type: application/json; charset=UTF-8");
require_once("../bootstrap.php"); // Giả định chứa hàm respond()

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }

    $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

    if ($keyword === '') {
        // ⭐ SỬA: Dùng respond() thay vì echo json_encode()
        respond(['isSuccess' => false, 'message' => 'Thiếu từ khóa tìm kiếm.'], 400);
    }

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
    WHERE p.productName LIKE CONCAT('%', ?, '%')
    GROUP BY p.productID
    ORDER BY p.createdDate DESC
    LIMIT 50";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        respond(['isSuccess' => false, 'message' => 'SQL Prepare failed: ' . $mysqli->error], 500);
    }
    
    $stmt->bind_param("s", $keyword);
    $stmt->execute();
    $res = $stmt->get_result();

    $data = [];
    while ($row = $res->fetch_assoc()) {
        // Xử lý tên file ảnh (giữ nguyên logic)
        $img = $row['imageUrl'] ?? "";
        if ($img && preg_match('/^http/', $img)) {
            $img = basename($img);
        }
        $row['imageUrl'] = $img ?: "no_image.png";
        $data[] = $row;
    }

    $stmt->close();
    
    // ⭐ SỬA: Dùng respond() với cấu trúc DTO thành công
    respond([
        "isSuccess" => true,
        "message" => "Found " . count($data) . " products.",
        "items" => $data
    ]);

} catch (Throwable $e) {
    error_log("Search API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra phía server: ' . $e->getMessage()], 500);
}
// Lưu ý: Thẻ đóng ?> bị loại bỏ.