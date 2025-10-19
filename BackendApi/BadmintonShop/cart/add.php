<?php
// File: cart/add.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once '../bootstrap.php'; // Giả định chứa hàm respond() và kết nối $mysqli

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }
    
    // Lấy dữ liệu từ POST request
    $customerID = (int)($_POST['customerID'] ?? 0);
    $variantID = (int)($_POST['variantID'] ?? 0);
    $quantityToAdd = (int)($_POST['quantity'] ?? 1); // Số lượng mới cần thêm

    // --- 1. Validate Input ---
    if ($customerID <= 0 || $variantID <= 0 || $quantityToAdd <= 0) {
        respond(['isSuccess' => false, 'message' => 'Dữ liệu không hợp lệ (ID/Số lượng).'], 400);
    }

    // --- BƯỚC 2: TÍNH TOÁN TỔNG SỐ LƯỢNG YÊU CẦU VÀ KIỂM TRA TỒN KHO ---

    // Lấy tồn kho hiện tại (stock) và số lượng đã có trong giỏ (cartQuantity)
    $sql_check = "
        SELECT 
            pv.stock, 
            COALESCE(sc.quantity, 0) AS cartQuantity,
            p.productName
        FROM product_variants pv
        LEFT JOIN shopping_cart sc ON sc.variantID = pv.variantID AND sc.customerID = ?
        JOIN products p ON p.productID = pv.productID
        WHERE pv.variantID = ?
    ";
    
    $stmt_check = $mysqli->prepare($sql_check);
    if (!$stmt_check) {
        throw new Exception("Lỗi chuẩn bị SQL kiểm tra tồn kho: " . $mysqli->error);
    }
    
    $stmt_check->bind_param("ii", $customerID, $variantID);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if (!$result_check) {
        respond(['isSuccess' => false, 'message' => 'VariantID không tồn tại.'], 404);
    }
    
    $currentStock = (int)$result_check['stock'];
    $currentCartQuantity = (int)$result_check['cartQuantity'];
    $productName = $result_check['productName'] ?? 'Sản phẩm';
    
    $newTotalQuantity = $currentCartQuantity + $quantityToAdd;

    if ($newTotalQuantity > $currentStock) {
        // Hoàn trả lỗi nếu tổng số lượng vượt quá tồn kho
        $message = $currentStock > 0 
                 ? "Chỉ còn $currentStock sản phẩm ($productName) trong kho."
                 : "Sản phẩm ($productName) đã hết hàng.";
        respond(['isSuccess' => false, 'message' => $message], 409); // 409 Conflict
    }


    // --- BƯỚC 3: THỰC THI INSERT ... ON DUPLICATE KEY UPDATE ---

    $sql = "INSERT INTO shopping_cart (customerID, variantID, quantity) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new Exception("Lỗi chuẩn bị SQL thêm giỏ hàng: " . $mysqli->error);
    }
    
    $stmt->bind_param("iii", $customerID, $variantID, $quantityToAdd);

    if ($stmt->execute()) {
        $message = ($currentCartQuantity > 0) 
                 ? "Đã thêm $quantityToAdd vào giỏ hàng. Tổng số lượng: $newTotalQuantity"
                 : "Đã thêm $quantityToAdd vào giỏ hàng.";
                 
        respond(['isSuccess' => true, 'message' => $message], 200);
    } else {
        throw new Exception("Lỗi thực thi câu lệnh SQL: " . $stmt->error);
    }

    $stmt->close();

} catch (Throwable $e) {
    error_log("Cart Add API Error: " . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Lỗi Server: ' . $e->getMessage()], 500);
}
// Lưu ý: Thẻ đóng ?> bị loại bỏ.