<?php
header("Content-Type: application/json; charset=UTF-8");
require_once("../bootstrap.php"); // Giả định chứa hàm respond()

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }
    
    $input = json_decode(file_get_contents("php://input"), true);
    
    if (!$input || !isset($input['customerID']) || !isset($input['productID'])) {
        respond(['isSuccess' => false, 'message' => 'Thiếu dữ liệu yêu cầu (customerID hoặc productID).'], 400);
    }

    $customerID = intval($input['customerID']);
    $productID = intval($input['productID']);

    if ($customerID <= 0 || $productID <= 0) {
        respond(['isSuccess' => false, 'message' => 'ID không hợp lệ.'], 400);
    }

    $sql = "DELETE FROM wishlists WHERE customerID = ? AND productID = ?";
    $stmt = $mysqli->prepare($sql);
    
    if (!$stmt) {
        respond(['isSuccess' => false, 'message' => 'SQL Prepare failed: ' . $mysqli->error], 500);
    }
    
    $stmt->bind_param("ii", $customerID, $productID);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            // Đã xóa thành công (ít nhất 1 dòng bị ảnh hưởng)
            respond(['isSuccess' => true, 'message' => 'Đã xóa khỏi wishlist.'], 200);
        } else {
            // Không có dòng nào bị ảnh hưởng (có thể sản phẩm không có trong wishlist)
            respond(['isSuccess' => true, 'message' => 'Sản phẩm không có trong wishlist hoặc đã bị xóa.'], 200);
        }
    } else {
        respond(['isSuccess' => false, 'message' => 'Lỗi thực thi SQL: ' . $stmt->error], 500);
    }

    $stmt->close();

} catch (Throwable $e) {
    error_log("Wishlist Remove API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra phía server: ' . $e->getMessage()], 500);
}
// Lưu ý: Thẻ đóng ?> bị loại bỏ.