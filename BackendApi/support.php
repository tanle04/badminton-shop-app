<?php
// File: api/BadmintonShop/support.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database config
require_once __DIR__ . '/config/database.php';

// Determine action
$action = $_GET['action'] ?? '';

// Get customer ID from request (simplified - should use JWT in production)
function getCustomerId() {
    // Try to get from Authorization header
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $token = str_replace('Bearer ', '', $headers['Authorization']);
        // TODO: Decode JWT to get customer_id
    }
    
    // For testing: get from POST body
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['customer_id'])) {
        return $input['customer_id'];
    }
    
    // For testing: hardcode customer ID 1
    return 1; // CHANGE THIS IN PRODUCTION!
}

// Generate conversation ID
function generateConversationId($customer_id) {
    return 'CONV-' . $customer_id . '-' . time();
}

// Get database connection
try {
    $database = new Database();
    $conn = $database->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// Route to action
switch ($action) {
    case 'init':
        // POST /support/init - Initialize or get existing conversation
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit();
        }
        
        $customer_id = getCustomerId();
        
        // Check for existing open conversation
        $query = "SELECT * FROM support_conversations 
                  WHERE customer_id = ? AND status != 'closed' 
                  ORDER BY last_message_at DESC LIMIT 1";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Existing conversation
            $conversation = $result->fetch_assoc();
        } else {
            // Create new conversation
            $conversation_id = generateConversationId($customer_id);
            
            $insert = "INSERT INTO support_conversations 
                      (conversation_id, customer_id, status, priority, subject, last_message_at, created_at, updated_at) 
                      VALUES (?, ?, 'open', 'normal', 'Hỗ trợ từ Mobile App', NOW(), NOW(), NOW())";
            
            $stmt = $conn->prepare($insert);
            $stmt->bind_param('si', $conversation_id, $customer_id);
            $stmt->execute();
            
            $conversation = [
                'conversation_id' => $conversation_id,
                'customer_id' => $customer_id,
                'status' => 'open',
                'assigned_employee_id' => null
            ];
        }
        
        // Get assigned employee info
        $assigned_employee = null;
        if (!empty($conversation['assigned_employee_id'])) {
            $emp_query = "SELECT employeeID, fullName, img_url FROM employees WHERE employeeID = ?";
            $emp_stmt = $conn->prepare($emp_query);
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
        break;
        
    case 'history':
        // GET /support/history - Get message history
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit();
        }
        
        $customer_id = getCustomerId();
        
        // Get conversation
        $conv_query = "SELECT conversation_id FROM support_conversations 
                      WHERE customer_id = ? AND status != 'closed' 
                      ORDER BY last_message_at DESC LIMIT 1";
        
        $stmt = $conn->prepare($conv_query);
        $stmt->bind_param('i', $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode([
                'conversation_id' => null,
                'messages' => []
            ]);
            exit();
        }
        
        $conversation = $result->fetch_assoc();
        $conversation_id = $conversation['conversation_id'];
        
        // Get messages
        $msg_query = "SELECT m.*, 
                            c.fullName as customer_name,
                            e.fullName as employee_name,
                            e.img_url as employee_img,
                            e.role as employee_role
                     FROM customer_support_messages m
                     LEFT JOIN customers c ON m.sender_id = c.customerID AND m.sender_type = 'customer'
                     LEFT JOIN employees e ON m.sender_id = e.employeeID AND m.sender_type = 'employee'
                     WHERE m.conversation_id = ?
                     ORDER BY m.created_at ASC";
        
        $stmt = $conn->prepare($msg_query);
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
        
        // Mark as read
        $update_query = "UPDATE customer_support_messages 
                        SET is_read = 1, read_at = NOW() 
                        WHERE conversation_id = ? 
                        AND sender_type = 'employee' 
                        AND is_read = 0";
        
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param('s', $conversation_id);
        $stmt->execute();
        
        echo json_encode([
            'conversation_id' => $conversation_id,
            'messages' => $messages
        ]);
        break;
        
    case 'send':
        // POST /support/send - Send a message
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit();
        }
        
        $customer_id = getCustomerId();
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['conversation_id']) || empty($input['message'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit();
        }
        
        $conversation_id = $input['conversation_id'];
        $message = $input['message'];
        
        // Verify conversation belongs to this customer
        $verify_query = "SELECT assigned_employee_id FROM support_conversations 
                        WHERE conversation_id = ? AND customer_id = ?";
        
        $stmt = $conn->prepare($verify_query);
        $stmt->bind_param('si', $conversation_id, $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
            exit();
        }
        
        $conv_data = $result->fetch_assoc();
        
        // Insert message
        $insert_query = "INSERT INTO customer_support_messages 
                        (conversation_id, sender_type, sender_id, message, assigned_employee_id, created_at, updated_at) 
                        VALUES (?, 'customer', ?, ?, ?, NOW(), NOW())";
        
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param('sisi', $conversation_id, $customer_id, $message, $conv_data['assigned_employee_id']);
        $stmt->execute();
        
        $message_id = $conn->insert_id;
        
        // Update last_message_at
        $update_conv = "UPDATE support_conversations SET last_message_at = NOW() WHERE conversation_id = ?";
        $stmt = $conn->prepare($update_conv);
        $stmt->bind_param('s', $conversation_id);
        $stmt->execute();
        
        // Get customer info
        $cust_query = "SELECT fullName FROM customers WHERE customerID = ?";
        $stmt = $conn->prepare($cust_query);
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
        break;
        
    case 'unread-count':
        // GET /support/unread-count - Get unread message count
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit();
        }
        
        $customer_id = getCustomerId();
        
        $query = "SELECT COUNT(*) as count 
                 FROM customer_support_messages m
                 INNER JOIN support_conversations c ON m.conversation_id = c.conversation_id
                 WHERE c.customer_id = ? 
                 AND m.sender_type = 'employee' 
                 AND m.is_read = 0";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        echo json_encode(['count' => (int)$row['count']]);
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
        break;
}

$conn->close();
?>