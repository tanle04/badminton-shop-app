<?php
/**
 * File: api/BadmintonShop/support-simple.php
 * 
 * API Support Chat - Dùng config db.php có sẵn
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include db config có sẵn
$db_config_path = __DIR__ . '/config/db.php';
if (!file_exists($db_config_path)) {
    http_response_code(500);
    echo json_encode(['error' => 'Database config not found', 'path' => $db_config_path]);
    exit();
}

require_once $db_config_path;

// $mysqli đã được khởi tạo trong db.php
$conn = $mysqli;

// Get action
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Helper: Get customer ID
function getCustomerId() {
    // TODO: Decode JWT from Authorization header
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['customer_id'])) {
        return (int)$input['customer_id'];
    }
    
    if (isset($_POST['customer_id'])) {
        return (int)$_POST['customer_id'];
    }
    
    if (isset($_GET['customer_id'])) {
        return (int)$_GET['customer_id'];
    }
    
    // Default for testing (CHANGE IN PRODUCTION!)
    return 1;
}

// ✅ NEW: Trigger Laravel broadcast
function triggerLaravelBroadcast($message_id) {
    // Call Laravel artisan command to broadcast
    $laravel_path = dirname(dirname(dirname(__DIR__))); // Go up to laravel root
    $command = "cd " . escapeshellarg($laravel_path) . " && php artisan support:broadcast {$message_id} 2>&1";
    
    error_log("[SUPPORT API] 🔔 Triggering Laravel broadcast for message_id: {$message_id}");
    error_log("[SUPPORT API] 🔔 Command: {$command}");
    
    $output = shell_exec($command);
    error_log("[SUPPORT API] 🔔 Broadcast output: " . $output);
    
    return $output;
}

// Route handling
try {
    switch ($action) {
        case 'init':
            // Initialize or get existing conversation
            $customer_id = getCustomerId();
            
            // ✅ ADD: Debug log
            error_log('[SUPPORT API] Init conversation for customer_id: ' . $customer_id);
            
            // Check for existing conversation
            $query = "SELECT * FROM support_conversations 
                      WHERE customer_id = ? AND status != 'closed' 
                      ORDER BY last_message_at DESC LIMIT 1";
            
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $conn->error);
            }
            
            $stmt->bind_param('i', $customer_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $conversation = $result->fetch_assoc();
                error_log('[SUPPORT API] Found existing conversation: ' . $conversation['conversation_id']);
            } else {
                // Create new
                $conversation_id = 'CONV-' . $customer_id . '-' . uniqid();
                
                error_log('[SUPPORT API] Creating new conversation: ' . $conversation_id);
                
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
            
            // Get employee info
            $assigned_employee = null;
            if (!empty($conversation['assigned_employee_id'])) {
                $emp_query = "SELECT employeeID, fullName, email, img_url FROM employees WHERE employeeID = ?";
                $emp_stmt = $conn->prepare($emp_query);
                $emp_stmt->bind_param('i', $conversation['assigned_employee_id']);
                $emp_stmt->execute();
                $emp_result = $emp_stmt->get_result();
                
                if ($emp_result->num_rows > 0) {
                    $emp = $emp_result->fetch_assoc();
                    $assigned_employee = [
                        'employeeID' => (int)$emp['employeeID'],
                        'fullName' => $emp['fullName'],
                        'email' => $emp['email'],
                        'img_url' => $emp['img_url']
                    ];
                }
            }
            
            // ✅ CRITICAL FIX: Return customer_id in response
            $response = [
                'success' => true,
                'conversation_id' => $conversation['conversation_id'],
                'customer_id' => (int)$customer_id,
                'status' => $conversation['status'],
                'assigned_employee' => $assigned_employee,
                'message' => 'Kết nối thành công'
            ];
            
            error_log('[SUPPORT API] Response: ' . json_encode($response));
            echo json_encode($response);
            break;
            
        case 'history':
            // Get message history
            $customer_id = getCustomerId();
            
            $conv_query = "SELECT conversation_id FROM support_conversations 
                          WHERE customer_id = ? AND status != 'closed' 
                          ORDER BY last_message_at DESC LIMIT 1";
            
            $stmt = $conn->prepare($conv_query);
            $stmt->bind_param('i', $customer_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                echo json_encode(['conversation_id' => null, 'messages' => []]);
                break;
            }
            
            $conv = $result->fetch_assoc();
            $conversation_id = $conv['conversation_id'];
            
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
            
            $stmt = $conn->prepare($msg_query);
            $stmt->bind_param('s', $conversation_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $messages = [];
            while ($row = $result->fetch_assoc()) {
                $sender = null;
                if ($row['sender_type'] === 'customer') {
                    $sender = ['fullName' => $row['customer_name'], 'type' => 'customer'];
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
            
            echo json_encode(['conversation_id' => $conversation_id, 'messages' => $messages]);
            break;
            
        case 'send':
            // Send message
            $customer_id = getCustomerId();
            $input = json_decode(file_get_contents('php://input'), true);
            
            error_log('[SUPPORT API] 📤 Send message from customer_id: ' . $customer_id);
            error_log('[SUPPORT API] 📤 Input: ' . json_encode($input));
            
            if (empty($input['conversation_id']) || empty($input['message'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing fields']);
                break;
            }
            
            $conversation_id = $input['conversation_id'];
            $message = $input['message'];
            
            // Verify ownership
            $verify = "SELECT assigned_employee_id FROM support_conversations 
                      WHERE conversation_id = ? AND customer_id = ?";
            $stmt = $conn->prepare($verify);
            $stmt->bind_param('si', $conversation_id, $customer_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Forbidden']);
                break;
            }
            
            $conv_data = $result->fetch_assoc();
            
            // Insert message
            $insert = "INSERT INTO support_messages 
                      (conversation_id, sender_type, sender_id, message, assigned_employee_id, created_at, updated_at) 
                      VALUES (?, 'customer', ?, ?, ?, NOW(), NOW())";
            
            $stmt = $conn->prepare($insert);
            $stmt->bind_param('sisi', $conversation_id, $customer_id, $message, $conv_data['assigned_employee_id']);
            $stmt->execute();
            $message_id = $conn->insert_id;
            
            error_log('[SUPPORT API] ✅ Message inserted with ID: ' . $message_id);
            
            // Update conversation
            $update = "UPDATE support_conversations SET last_message_at = NOW() WHERE conversation_id = ?";
            $stmt = $conn->prepare($update);
            $stmt->bind_param('s', $conversation_id);
            $stmt->execute();
            
            // Get customer name
            $cust_query = "SELECT fullName FROM customers WHERE customerID = ?";
            $stmt = $conn->prepare($cust_query);
            $stmt->bind_param('i', $customer_id);
            $stmt->execute();
            $cust_result = $stmt->get_result();
            $customer_info = $cust_result->fetch_assoc();
            
            // ✅ CRITICAL: Trigger Laravel broadcast
            error_log('[SUPPORT API] 🔔 Triggering broadcast for message_id: ' . $message_id);
            triggerLaravelBroadcast($message_id);
            
            echo json_encode([
                'success' => true,
                'message' => [
                    'id' => $message_id,
                    'sender_type' => 'customer',
                    'message' => $message,
                    'created_at' => date('Y-m-d\TH:i:s'),
                    'sender' => ['fullName' => $customer_info['fullName'], 'type' => 'customer']
                ]
            ]);
            break;
            
        case 'unread-count':
            // Get unread count
            $customer_id = getCustomerId();
            
            $query = "SELECT COUNT(*) as count 
                     FROM support_messages m
                     INNER JOIN support_conversations c ON m.conversation_id = c.conversation_id
                     WHERE c.customer_id = ? AND m.sender_type = 'employee' AND m.is_read = 0";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $customer_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            echo json_encode(['count' => (int)$row['count']]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'error' => 'Invalid action',
                'received_action' => $action,
                'valid_actions' => ['init', 'history', 'send', 'unread-count']
            ]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log('[SUPPORT API] ❌ Error: ' . $e->getMessage());
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

$conn->close();
?>