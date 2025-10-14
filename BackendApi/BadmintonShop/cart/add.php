<?php
// Set headers for JSON response
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

// Include database connection (Đường dẫn ../ là chính xác)
require_once '../bootstrap.php';

// Get data from the POST request body
$customerID = (int)($_POST['customerID'] ?? 0);
$variantID = (int)($_POST['variantID'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 1);

// --- 1. Validate Input ---
if ($customerID <= 0 || $variantID <= 0 || $quantity <= 0) {
    http_response_code(400); // Bad Request
    echo json_encode(["success" => false, "message" => "Dữ liệu không hợp lệ (customerID, variantID, quantity)."]);
    exit();
}

// --- 2. Use INSERT ... ON DUPLICATE KEY UPDATE ---
$sql = "INSERT INTO shopping_cart (customerID, variantID, quantity) 
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("iii", $customerID, $variantID, $quantity);

// --- 3. Execute and check result ---
if ($stmt->execute()) {
    if ($stmt->affected_rows >= 1) {
        echo json_encode(["success" => true, "message" => "Đã cập nhật giỏ hàng"]);
    } else {
        echo json_encode(["success" => false, "message" => "Không có gì thay đổi trong giỏ hàng"]);
    }
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(["success" => false, "message" => "Lỗi thực thi câu lệnh SQL"]);
}

// --- 4. Cleanup ---
$stmt->close();
$mysqli->close();
?>