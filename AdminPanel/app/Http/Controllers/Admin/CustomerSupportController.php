<?php
// File: BackendApi/controllers/CustomerSupportController.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/database.php';

class CustomerSupportController {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    /**
     * Get or Create Conversation
     * POST /support/init
     */
    public function initConversation() {
        try {
            // Lấy customer_id từ JWT token hoặc từ request
            $customer_id = $this->getAuthenticatedCustomerId();
            
            if (!$customer_id) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Unauthorized'
                ]);
                return;
            }
            
            // Tìm conversation đang mở
            $query = "SELECT * FROM support_conversations 
                      WHERE customer_id = ? 
                      AND status != 'closed' 
                      ORDER BY last_message_at DESC 
                      LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param('i', $customer_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Có conversation rồi
                $conversation = $result->fetch_assoc();
            } else {
                // Tạo mới
                $conversation_id = $this->generateConversationId($customer_id);
                
                $insert_query = "INSERT INTO support_conversations 
                                (conversation_id, customer_id, status, priority, subject, last_message_at, created_at, updated_at) 
                                VALUES (?, ?, 'open', 'normal', 'Hỗ trợ từ Mobile App', NOW(), NOW(), NOW())";
                
                $stmt = $this->conn->prepare($insert_query);
                $stmt->bind_param('si', $conversation_id, $customer_id);
                $stmt->execute();
                
                $conversation = [
                    'conversation_id' => $conversation_id,
                    'customer_id' => $customer_id,
                    'status' => 'open',
                    'assigned_employee_id' => null
                ];
            }
            
            // Lấy thông tin nhân viên được assign (nếu có)
            $assigned_employee = null;
            if (!empty($conversation['assigned_employee_id'])) {
                $emp_query = "SELECT employeeID, fullName, img_url FROM employees WHERE employeeID = ?";
                $emp_stmt = $this->conn->prepare($emp_query);
                $emp_stmt->bind_param('i', $conversation['assigned_employee_id']);
                $emp_stmt->execute();
                $emp_result = $emp_stmt->get_result();
                
                if ($emp_result->num_rows > 0) {
                    $emp_data = $emp_result->fetch_assoc();
                    $assigned_employee = [
                        'fullName' => $emp_data['fullName'],
                        'img_url' => $emp_data['img_url']
                    ];
                }
            }
            
            echo json_encode([
                'conversation_id' => $conversation['conversation_id'],
                'status' => $conversation['status'],
                'assigned_employee' => $assigned_employee
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get Message History
     * GET /support/history
     */
    public function getHistory() {
        try {
            $customer_id = $this->getAuthenticatedCustomerId();
            
            if (!$customer_id) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                return;
            }
            
            // Lấy conversation
            $conv_query = "SELECT conversation_id FROM support_conversations 
                          WHERE customer_id = ? AND status != 'closed' 
                          ORDER BY last_message_at DESC LIMIT 1";
            
            $stmt = $this->conn->prepare($conv_query);
            $stmt->bind_param('i', $customer_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                echo json_encode([
                    'conversation_id' => null,
                    'messages' => []
                ]);
                return;
            }
            
            $conversation = $result->fetch_assoc();
            $conversation_id = $conversation['conversation_id'];
            
            // Lấy messages
            $msg_query = "SELECT m.*, 
                                c.fullName as customer_name,
                                e.fullName as employee_name,
                                e.img_url as employee_img,
                                e.role as employee_role
                         FROM support_messages m
                         LEFT JOIN customers c ON m.sender_id = c.customerID AND m.sender_type = 'customer'
                         LEFT JOIN employees e ON m.sender_id = e.employeeID AND m.sender_type = 'employee'
                         WHERE m.conversation_id = ?
                         ORDER BY m.created_at ASC";
            
            $stmt = $this->conn->prepare($msg_query);
            $stmt->bind_param('s', $conversation_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $messages = [];
            while ($row = $result->fetch_assoc()) {
                $sender = null;
                
                if ($row['sender_type'] === 'customer') {
                    $sender = [
                        'fullName' => $row['customer_name'],
                        'type' => 'customer'
                    ];
                } else if ($row['sender_type'] === 'employee') {
                    $sender = [
                        'fullName' => $row['employee_name'],
                        'img_url' => $row['employee_img'],
                        'role' => $row['employee_role'],
                        'type' => 'employee'
                    ];
                }
                
                $messages[] = [
                    'id' => $row['id'],
                    'sender_type' => $row['sender_type'],
                    'message' => $row['message'],
                    'attachment_path' => $row['attachment_path'],
                    'attachment_name' => $row['attachment_name'],
                    'created_at' => $row['created_at'],
                    'sender' => $sender
                ];
            }
            
            // Đánh dấu đã đọc
            $update_query = "UPDATE support_messages 
                            SET is_read = 1, read_at = NOW() 
                            WHERE conversation_id = ? 
                            AND sender_type = 'employee' 
                            AND is_read = 0";
            
            $stmt = $this->conn->prepare($update_query);
            $stmt->bind_param('s', $conversation_id);
            $stmt->execute();
            
            echo json_encode([
                'conversation_id' => $conversation_id,
                'messages' => $messages
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * Send Message
     * POST /support/send
     */
    public function sendMessage() {
        try {
            $customer_id = $this->getAuthenticatedCustomerId();
            
            if (!$customer_id) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                return;
            }
            
            // Lấy dữ liệu
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['conversation_id']) || empty($input['message'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                return;
            }
            
            $conversation_id = $input['conversation_id'];
            $message = $input['message'];
            
            // Xác thực conversation thuộc về customer này
            $verify_query = "SELECT assigned_employee_id FROM support_conversations 
                            WHERE conversation_id = ? AND customer_id = ?";
            
            $stmt = $this->conn->prepare($verify_query);
            $stmt->bind_param('si', $conversation_id, $customer_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Forbidden']);
                return;
            }
            
            $conv_data = $result->fetch_assoc();
            
            // Insert message
           $insert_query = "INSERT INTO support_messages 
                            (conversation_id, sender_type, sender_id, message, assigned_employee_id, created_at, updated_at) 
                            VALUES (?, 'customer', ?, ?, ?, NOW(), NOW())";
            
            $stmt = $this->conn->prepare($insert_query);
            $stmt->bind_param('sisi', $conversation_id, $customer_id, $message, $conv_data['assigned_employee_id']);
            $stmt->execute();
            
            $message_id = $this->conn->insert_id;
            
            // Update last_message_at
            $update_conv = "UPDATE support_conversations SET last_message_at = NOW() WHERE conversation_id = ?";
            $stmt = $this->conn->prepare($update_conv);
            $stmt->bind_param('s', $conversation_id);
            $stmt->execute();
            
            // Lấy thông tin customer
            $cust_query = "SELECT fullName FROM customers WHERE customerID = ?";
            $stmt = $this->conn->prepare($cust_query);
            $stmt->bind_param('i', $customer_id);
            $stmt->execute();
            $cust_result = $stmt->get_result();
            $customer_info = $cust_result->fetch_assoc();
            
            echo json_encode([
                'success' => true,
                'message' => [
                    'id' => $message_id,
                    'sender_type' => 'customer',
                    'message' => $message,
                    'created_at' => date('Y-m-d\TH:i:s'),
                    'sender' => [
                        'fullName' => $customer_info['fullName'],
                        'type' => 'customer'
                    ]
                ]
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    
    /**
     * Get Unread Count
     * GET /support/unread-count
     */
    public function getUnreadCount() {
        try {
            $customer_id = $this->getAuthenticatedCustomerId();
            
            if (!$customer_id) {
                echo json_encode(['count' => 0]);
                return;
            }
            
            $query = "SELECT COUNT(*) as count 
                     FROM support_messages m
                     INNER JOIN support_conversations c ON m.conversation_id = c.conversation_id
                     WHERE c.customer_id = ? 
                     AND m.sender_type = 'employee' 
                     AND m.is_read = 0";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param('i', $customer_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            echo json_encode(['count' => (int)$row['count']]);
            
        } catch (Exception $e) {
            echo json_encode(['count' => 0]);
        }
    }
    
    /**
     * Helper: Get authenticated customer ID
     */
    private function getAuthenticatedCustomerId() {
        // Option 1: From JWT token in Authorization header
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $token = str_replace('Bearer ', '', $headers['Authorization']);
            // Decode JWT and get customer_id
            // TODO: Implement JWT decoding
            
            // For now, try to get from session or other method
        }
        
        // Option 2: From session (if web-based)
        if (isset($_SESSION['customer_id'])) {
            return $_SESSION['customer_id'];
        }
        
        // Option 3: From POST data (for testing)
        $input = json_decode(file_get_contents('php://input'), true);
        if (isset($input['customer_id'])) {
            return $input['customer_id'];
        }
        
        return null;
    }
    
    /**
     * Helper: Generate conversation ID
     */
    private function generateConversationId($customer_id) {
        return 'CONV-' . $customer_id . '-' . time();
    }
}

// Router
$controller = new CustomerSupportController();
$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? $_SERVER['REQUEST_URI'];

// Parse route
if (strpos($path, '/support/init') !== false) {
    if ($method === 'POST') {
        $controller->initConversation();
    }
} elseif (strpos($path, '/support/history') !== false) {
    if ($method === 'GET') {
        $controller->getHistory();
    }
} elseif (strpos($path, '/support/send') !== false) {
    if ($method === 'POST') {
        $controller->sendMessage();
    }
} elseif (strpos($path, '/support/unread-count') !== false) {
    if ($method === 'GET') {
        $controller->getUnreadCount();
    }
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Not Found']);
}
?>