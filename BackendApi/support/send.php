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

    // Verify JWT
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (empty($authHeader) || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        respond(['isSuccess' => false, 'message' => 'Unauthorized'], 401);
    }
    
    $token = $matches[1];
    
    require_once '../utils/JWTHandler.php';
    $jwtHandler = new JWTHandler();
    $decoded = $jwtHandler->decodeToken($token);
    
    if (!$decoded || !isset($decoded->customerID)) {
        respond(['isSuccess' => false, 'message' => 'Token không hợp lệ'], 401);
    }
    
    $customerId = $decoded->customerID;
    
    // Get input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate
    if (empty($input['conversation_id']) || empty($input['message'])) {
        respond([
            'isSuccess' => false,
            'message' => 'conversation_id và message là bắt buộc'
        ], 422);
    }
    
    $conversationId = $input['conversation_id'];
    $message = $input['message'];
    $attachmentUrl = $input['attachment_url'] ?? null;
    
    // Validate message length
    if (strlen($message) > 2000) {
        respond([
            'isSuccess' => false,
            'message' => 'Tin nhắn quá dài (tối đa 2000 ký tự)'
        ], 422);
    }
    
    // Call AdminPanel
    $adminPanel = new AdminPanelService();
    $result = $adminPanel->sendMessage($conversationId, $customerId, $message, $attachmentUrl);
    
    if ($result && isset($result['success']) && $result['success']) {
        respond([
            'isSuccess' => true,
            'message' => 'Gửi tin nhắn thành công',
            'data' => $result
        ], 201);
    } else {
        respond([
            'isSuccess' => false,
            'message' => 'Không thể gửi tin nhắn'
        ], 500);
    }
    
} catch (Throwable $e) {
    error_log('Support Send API Error: ' . $e->getMessage());
    respond(['isSuccess' => false, 'message' => 'Có lỗi xảy ra phía server: ' . $e->getMessage()], 500);
}