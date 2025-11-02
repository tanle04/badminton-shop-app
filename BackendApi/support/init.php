<?php
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

require_once '../bootstrap.php';
require_once '../services/AdminPanelService.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(['isSuccess' => false, 'message' => 'Phương thức không được phép.'], 405);
    }

    // Verify JWT token (giả sử bạn có hàm verify_jwt_token)
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        respond(['isSuccess' => false, 'message' => 'Unauthorized'], 401);
    }
    
    $token = $matches[1];
    
    // Decode JWT - adjust theo cách bạn xử lý JWT
    // Ví dụ: $decoded = verify_jwt_token($token);
    // Hoặc nếu có JWTHandler:
    require_once '../utils/JWTHandler.php';
    $jwtHandler = new JWTHandler();
    $decoded = $jwtHandler->decodeToken($token);
    
    if (!$decoded || !isset($decoded->customerID)) {
        respond(['isSuccess' => false, 'message' => 'Token không hợp lệ'], 401);
    }
    
    $customerId = $decoded->customerID;
    
    // Get request body
    $input = json_decode(file_get_contents('php://input'), true);
    $subject = $input['subject'] ?? 'Hỗ trợ từ Mobile App';
    
    // Call AdminPanel
    $adminPanel = new AdminPanelService();
    $result = $adminPanel->initConversation($customerId, $subject);
    
    if ($result && isset($result['success']) && $result['success']) {
        respond([
            'isSuccess' => true,
            'message' => 'Khởi tạo cuộc hội thoại thành công',
            'conversationId' => $result['conversation_id'],
            'data' => $result
        ]);
    } else {
        respond([
            'isSuccess' => false,
            'message' => 'Không thể khởi tạo cuộc hội thoại'
        ], 500);
    }
    
} catch (Throwable $e) {
    error_log('Support Init API Error: ' . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra phía server: ' . $e->getMessage()], 500);
}