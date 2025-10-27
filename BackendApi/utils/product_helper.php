<?php
/**
 * product_helper.php
 * Chứa các hàm hỗ trợ liên quan đến việc lấy thông tin chi tiết sản phẩm và biến thể (variant).
 * * Yêu cầu: Biến $mysqli (kết nối database) phải được định nghĩa 
 * (thường là trong file bootstrap.php hoặc file được require trước đó).
 */

// Hàm 1: Lấy chi tiết biến thể sản phẩm (variant) cùng với tên sản phẩm
// Được sử dụng khi xử lý đơn hàng (OrderDetails)
function getVariantDetails(int $variantID) {
    global $mysqli; // Sử dụng biến kết nối đã được định nghĩa ở ngoài

    if (!$mysqli) {
        error_log("[ProductHelper] Lỗi: Biến \$mysqli không được định nghĩa hoặc kết nối database bị lỗi.");
        return null;
    }

    $stmt = $mysqli->prepare("
        SELECT 
            pv.variantID, 
            pv.sku, 
            pv.price AS variantPrice, 
            pv.stock AS variantStock,
            p.productName, 
            p.productID
        FROM product_variants pv
        JOIN products p ON pv.productID = p.productID
        WHERE pv.variantID = ?
    ");

    if (!$stmt) {
        error_log("[ProductHelper] Lỗi SQL Prepare: " . $mysqli->error);
        return null;
    }

    $stmt->bind_param("i", $variantID);
    $stmt->execute();
    $result = $stmt->get_result();
    $details = $result->fetch_assoc();
    $stmt->close();

    return $details;
}

// Hàm 2: Lấy tên sản phẩm chỉ từ ProductID
function getProductName(int $productID) {
    global $mysqli;

    if (!$mysqli) return 'Database Error';

    $stmt = $mysqli->prepare("SELECT productName FROM products WHERE productID = ?");
    if (!$stmt) return 'SQL Error';
    
    $stmt->bind_param("i", $productID);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return $row['productName'] ?? 'Sản phẩm không tên';
}

// Bạn có thể thêm các hàm khác tùy theo nhu cầu của ứng dụng.

?>