<?php
header("Content-Type: application/json; charset=UTF-8");
require_once("../bootstrap.php"); // Giả định chứa hàm respond()

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }

    $sql = "SELECT categoryID, categoryName FROM categories ORDER BY categoryID ASC";
    $res = $mysqli->query($sql);
    
    if ($res === false) {
        // Xử lý lỗi SQL
        respond(['isSuccess' => false, 'message' => 'Lỗi truy vấn database: ' . $mysqli->error], 500);
    }

    $items = [];
    while ($row = $res->fetch_assoc()) {
        $items[] = $row;
    }

    // ⭐ PHẢN HỒI THÀNH CÔNG: Sử dụng respond() với cấu trúc DTO
    respond([
        "isSuccess" => true,
        "message" => "Categories listed successfully.",
        "items" => $items
    ]);

} catch (Throwable $e) {
    error_log("Category List API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra phía server: ' . $e->getMessage()], 500);
}

// Lưu ý: $mysqli->close() không cần thiết vì respond() sẽ exit.
// Thẻ đóng ?> bị loại bỏ.