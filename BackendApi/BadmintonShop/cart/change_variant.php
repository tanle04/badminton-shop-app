<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../bootstrap.php'; // Giả định chứa hàm respond()

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }

    // Nhận dữ liệu POST
    $cartID = (int)($_POST['cartID'] ?? 0);
    $newVariantID = (int)($_POST['newVariantID'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    $customerID = (int)($_POST['customerID'] ?? 0);

    // --- 1. Validation ---
    if ($cartID <= 0 || $newVariantID <= 0 || $quantity <= 0 || $customerID <= 0) {
        respond(['isSuccess' => false, 'message' => 'Dữ liệu không hợp lệ (ID giỏ hàng, biến thể, số lượng, khách hàng).'], 400);
    }

    // --- 2. KIỂM TRA TỒN KHO CHO BIẾN THỂ MỚI ---
    $sql_check = "
        SELECT pv.stock, p.productName 
        FROM product_variants pv 
        JOIN products p ON p.productID = pv.productID 
        WHERE pv.variantID = ?
    ";
    
    $stmt_check = $mysqli->prepare($sql_check);
    if (!$stmt_check) {
        throw new Exception("Lỗi chuẩn bị SQL kiểm tra tồn kho: " . $mysqli->error);
    }
    
    $stmt_check->bind_param("i", $newVariantID);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if (!$result_check) {
        respond(['isSuccess' => false, 'message' => 'Mã biến thể mới không tồn tại.'], 404);
    }
    
    $currentStock = (int)$result_check['stock'];
    $productName = $result_check['productName'] ?? 'Sản phẩm';

    if ($quantity > $currentStock) {
        $message = $currentStock > 0 
                 ? "Chỉ còn $currentStock sản phẩm ($productName) trong kho."
                 : "Sản phẩm ($productName) đã hết hàng.";
        respond(['isSuccess' => false, 'message' => $message], 409); // 409 Conflict
    }

    // --- 3. CẬP NHẬT DÒNG TRONG GIỎ HÀNG ---
    $sql_update = "UPDATE shopping_cart SET variantID = ?, quantity = ? WHERE cartID = ? AND customerID = ?";
    
    $stmt = $mysqli->prepare($sql_update);
    if (!$stmt) {
        throw new Exception("Lỗi chuẩn bị SQL cập nhật giỏ hàng: " . $mysqli->error);
    }
    
    $stmt->bind_param("iiii", $newVariantID, $quantity, $cartID, $customerID);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        respond(['isSuccess' => true, 'message' => "Đã cập nhật sản phẩm trong giỏ hàng."], 200);
    } else {
        // Có thể là variantID và quantity không thay đổi, vẫn coi là thành công cho mục đích này
        respond(['isSuccess' => true, 'message' => "Không có gì thay đổi (Dữ liệu đã khớp)."], 200);
    }

    $stmt->close();

} catch (Throwable $e) {
    error_log("Cart Change Variant API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Lỗi Server: ' . $e->getMessage()], 500);
}
// Lưu ý: Thẻ đóng ?> bị loại bỏ.