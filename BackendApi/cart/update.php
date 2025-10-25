<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
require_once '../bootstrap.php'; // Giả định chứa hàm respond()

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }
    
    // Nhận dữ liệu POST
    $cartID = (int)($_POST['cartID'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? -1); // Số lượng mới (0 để xóa)
    $customerID = (int)($_POST['customerID'] ?? 0); // Để bảo mật

    // --- 1. Validation ---
    if ($cartID <= 0 || $quantity < 0 || $customerID <= 0) {
        respond(['isSuccess' => false, 'message' => 'Dữ liệu không hợp lệ (ID giỏ hàng, số lượng, khách hàng).'], 400);
    }
    
    $message = "Không có gì thay đổi"; // Thông báo mặc định

    if ($quantity > 0) {
        // --- 2. KIỂM TRA TỒN KHO ---
        // Lấy variantID, tồn kho (stock), và tên sản phẩm
        $sql_check = "
            SELECT pv.stock, p.productName, sc.variantID 
            FROM shopping_cart sc 
            JOIN product_variants pv ON sc.variantID = pv.variantID
            JOIN products p ON pv.productID = p.productID
            WHERE sc.cartID = ? AND sc.customerID = ?
        ";
        
        $stmt_check = $mysqli->prepare($sql_check);
        if (!$stmt_check) {
            throw new Exception("Lỗi chuẩn bị SQL kiểm tra tồn kho: " . $mysqli->error);
        }
        
        $stmt_check->bind_param("ii", $cartID, $customerID);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();

        if (!$result_check) {
            respond(['isSuccess' => false, 'message' => 'Không tìm thấy mục hàng trong giỏ.'], 404);
        }
        
        $currentStock = (int)$result_check['stock'];
        $productName = $result_check['productName'] ?? 'Sản phẩm';

        // ⭐ RÀNG BUỘC KHI TĂNG SỐ LƯỢNG: Kiểm tra nếu số lượng MỚI vượt quá tồn kho
        if ($quantity > $currentStock) {
            $message = $currentStock > 0 
                     ? "Chỉ còn $currentStock sản phẩm ($productName) trong kho. Vui lòng giảm số lượng."
                     : "Sản phẩm ($productName) đã hết hàng.";
            respond(['isSuccess' => false, 'message' => $message], 409); // 409 Conflict
        }
        
        // --- 3. Cập nhật số lượng ---
        // Không cần kiểm tra race condition ở đây vì stock chỉ được trừ khi đặt hàng cuối cùng.
        $stmt = $mysqli->prepare("UPDATE shopping_cart SET quantity = ? WHERE cartID = ? AND customerID = ?");
        if (!$stmt) {
            throw new Exception("Lỗi chuẩn bị SQL cập nhật: " . $mysqli->error);
        }
        $stmt->bind_param("iii", $quantity, $cartID, $customerID);
        $message = "Cập nhật giỏ hàng thành công";

    } else {
        // --- 4. Xóa sản phẩm (quantity == 0) ---
        $stmt = $mysqli->prepare("DELETE FROM shopping_cart WHERE cartID = ? AND customerID = ?");
        if (!$stmt) {
            throw new Exception("Lỗi chuẩn bị SQL xóa: " . $mysqli->error);
        }
        $stmt->bind_param("ii", $cartID, $customerID);
        $message = "Đã xóa sản phẩm khỏi giỏ hàng";
    }

    $stmt->execute();
    
    // --- 5. Trả về kết quả ---
    if ($stmt->affected_rows > 0) {
        respond(['isSuccess' => true, 'message' => $message], 200);
    } else {
        // Trả về thành công nếu không có hàng nào bị ảnh hưởng (ví dụ: cố gắng xóa mặt hàng không tồn tại)
        respond(['isSuccess' => true, 'message' => 'Không có gì thay đổi'], 200); 
    }

    $stmt->close();

} catch (Throwable $e) {
    error_log("Cart Update/Delete API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Lỗi Server: ' . $e->getMessage()], 500);
}
// Lưu ý: Thẻ đóng ?> bị loại bỏ.