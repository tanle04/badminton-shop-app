<?php
/**
 * ✅ FIXED VERSION - Cached php://input to prevent double-read
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$db_config_path = __DIR__ . '/config/db.php';
if (!file_exists($db_config_path)) {
    http_response_code(500);
    echo json_encode(['error' => 'Database config not found']);
    exit();
}

require_once $db_config_path;
$conn = $mysqli;

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ==========================================================
// ✅ *** SỬA LỖI ***
// Đọc JSON body MỘT LẦN duy nhất và lưu vào cache.
// ==========================================================
$json_input_cache = json_decode(file_get_contents('php://input'), true);
if (!is_array($json_input_cache)) {
    $json_input_cache = []; // Đảm bảo $json_input_cache luôn là mảng
}


/**
 * Lấy customer_id từ tất cả các nguồn
 * @param array $cached_input Dữ liệu JSON body đã đọc
 * @return int
 */
function getCustomerId($cached_input) {
    // ✅ PRIORITY 1: JSON body (từ cache)
    if (isset($cached_input['customer_id']) && (int)$cached_input['customer_id'] > 0) {
        $customer_id = (int)$cached_input['customer_id'];
        error_log("[SUPPORT API] 📱 Customer ID from JSON body: {$customer_id}");
        return $customer_id;
    }
    
    // ✅ PRIORITY 2: POST form data
    if (isset($_POST['customer_id']) && (int)$_POST['customer_id'] > 0) {
        $customer_id = (int)$_POST['customer_id'];
        error_log("[SUPPORT API] 📱 Customer ID from POST form: {$customer_id}");
        return $customer_id;
    }
    
    // ✅ PRIORITY 3: GET query string
    if (isset($_GET['customer_id']) && (int)$_GET['customer_id'] > 0) {
        $customer_id = (int)$_GET['customer_id'];
        error_log("[SUPPORT API] 📱 Customer ID from GET query: {$customer_id}");
        return $customer_id;
    }
    
    // ❌ NOT FOUND
    error_log("[SUPPORT API] ❌ No customer_id found in request!");
    return 0;
}

function validateCustomerId($customer_id) {
    if ($customer_id == 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Missing or invalid customer_id',
            'debug' => 'Customer ID is required for this action'
        ]);
        return false;
    }
    return true;
}

function triggerLaravelBroadcast($message_id) {
    try {
        $url = 'https://tanbadminton.id.vn/admin/public/api/bridge/support/trigger-broadcast';
        $api_key = 'BadmintonShop2025SecretKey_ChangeInProduction';
        
        error_log("[SUPPORT API] 🔔 Broadcasting message_id: {$message_id}");
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['message_id' => (int)$message_id]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-API-Key: ' . $api_key,
            ],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            error_log("[SUPPORT API] ✅ Broadcast success");
            return "SUCCESS";
        }
        
        error_log("[SUPPORT API] ❌ Broadcast failed: HTTP {$http_code}");
        return "ERROR: HTTP {$http_code}";
        
    } catch (Exception $e) {
        error_log("[SUPPORT API] ❌ Broadcast exception: " . $e->getMessage());
        return "ERROR: " . $e->getMessage();
    }
}

try {
    // ✅ Lấy customer_id MỘT LẦN ở đây, sử dụng $json_input_cache
    $customer_id = getCustomerId($json_input_cache);

    switch ($action) {
        case 'init':
            // ✅ VALIDATE
            if (!validateCustomerId($customer_id)) {
                exit();
            }
            
            error_log("[SUPPORT API] 🔐 Init for customer_id: {$customer_id}");
            
            $employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : null;
            
            if ($employee_id) {
                $query = "SELECT sc.*, e.fullName as emp_name, e.email as emp_email, e.img_url as emp_img
                          FROM support_conversations sc
                          LEFT JOIN employees e ON sc.assigned_employee_id = e.employeeID
                          WHERE sc.customer_id = ? AND sc.assigned_employee_id = ? AND sc.status = 'open'
                          LIMIT 1";
                
                $stmt = $conn->prepare($query);
                $stmt->bind_param('ii', $customer_id, $employee_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $conversation = $result->fetch_assoc();
                } else {
                    $conversation_id = 'CONV-' . $customer_id . '-' . $employee_id . '-' . uniqid();
                    
                    $insert = "INSERT INTO support_conversations 
                               (conversation_id, customer_id, assigned_employee_id, status, priority, subject, last_message_at, created_at, updated_at) 
                               VALUES (?, ?, ?, 'open', 'normal', 'Hỗ trợ từ Mobile App', NOW(), NOW(), NOW())";
                    
                    $stmt = $conn->prepare($insert);
                    $stmt->bind_param('sii', $conversation_id, $customer_id, $employee_id);
                    $stmt->execute();
                    
                    $emp_query = "SELECT employeeID, fullName, email, img_url FROM employees WHERE employeeID = ?";
                    $stmt = $conn->prepare($emp_query);
                    $stmt->bind_param('i', $employee_id);
                    $stmt->execute();
                    $emp = $stmt->get_result()->fetch_assoc();
                    
                    $conversation = [
                        'conversation_id' => $conversation_id,
                        'customer_id' => $customer_id,
                        'status' => 'open',
                        'assigned_employee_id' => $employee_id,
                        'emp_name' => $emp['fullName'],
                        'emp_email' => $emp['email'],
                        'emp_img' => $emp['img_url']
                    ];
                }
            } else {
                $query = "SELECT sc.*, e.fullName as emp_name, e.email as emp_email, e.img_url as emp_img
                          FROM support_conversations sc
                          LEFT JOIN employees e ON sc.assigned_employee_id = e.employeeID
                          WHERE sc.customer_id = ? AND sc.status = 'open' 
                          ORDER BY sc.last_message_at DESC LIMIT 1";
                
                $stmt = $conn->prepare($query);
                $stmt->bind_param('i', $customer_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $conversation = $result->fetch_assoc();
                } else {
                    $conversation_id = 'CONV-' . $customer_id . '-' . uniqid();
                    
                    $emp_query = "SELECT e.employeeID, e.fullName, e.email, e.img_url
                                  FROM employees e
                                  LEFT JOIN support_conversations sc ON e.employeeID = sc.assigned_employee_id AND sc.status = 'open'
                                  WHERE e.role IN ('Staff', 'Admin', 'Marketer')
                                  GROUP BY e.employeeID
                                  ORDER BY COUNT(sc.conversation_id) ASC
                                  LIMIT 1";
                    
                    $emp_result = $conn->query($emp_query);
                    $emp = $emp_result->num_rows > 0 ? $emp_result->fetch_assoc() : null;
                    $assigned_employee_id = $emp ? $emp['employeeID'] : null;
                    
                    $insert = "INSERT INTO support_conversations 
                               (conversation_id, customer_id, assigned_employee_id, status, priority, subject, last_message_at, created_at, updated_at) 
                               VALUES (?, ?, ?, 'open', 'normal', 'Hỗ trợ từ Mobile App', NOW(), NOW(), NOW())";
                    
                    $stmt = $conn->prepare($insert);
                    $stmt->bind_param('sii', $conversation_id, $customer_id, $assigned_employee_id);
                    $stmt->execute();
                    
                    $conversation = [
                        'conversation_id' => $conversation_id,
                        'customer_id' => $customer_id,
                        'status' => 'open',
                        'assigned_employee_id' => $assigned_employee_id,
                        'emp_name' => $emp['fullName'] ?? null,
                        'emp_email' => $emp['email'] ?? null,
                        'emp_img' => $emp['img_url'] ?? null
                    ];
                }
            }
            
            $assigned_employee = null;
            if (!empty($conversation['assigned_employee_id'])) {
                $assigned_employee = [
                    'employeeID' => (int)$conversation['assigned_employee_id'],
                    'fullName' => $conversation['emp_name'],
                    'email' => $conversation['emp_email'],
                    'img_url' => $conversation['emp_img']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'conversation_id' => $conversation['conversation_id'],
                'customer_id' => (int)$customer_id,
                'status' => $conversation['status'],
                'assigned_employee' => $assigned_employee,
                'message' => 'Kết nối thành công'
            ]);
            break;
            
        case 'history':
            // ✅ VALIDATE
            if (!validateCustomerId($customer_id)) {
                exit();
            }
            
            error_log("[SUPPORT API] 📜 History for customer_id: {$customer_id}");
            
            $conversation_id = isset($_GET['conversation_id']) ? $_GET['conversation_id'] : null;
            
            if ($conversation_id) {
                // ✅ Verify ownership
                $conv_query = "SELECT conversation_id FROM support_conversations 
                               WHERE conversation_id = ? AND customer_id = ? LIMIT 1";
                
                $stmt = $conn->prepare($conv_query);
                $stmt->bind_param('si', $conversation_id, $customer_id);
                $stmt->execute();
                
                if ($stmt->get_result()->num_rows === 0) {
                    error_log("[SUPPORT API] ❌ Conversation {$conversation_id} not owned by customer {$customer_id}");
                    echo json_encode(['conversation_id' => null, 'messages' => []]);
                    break;
                }
            } else {
                $conv_query = "SELECT conversation_id FROM support_conversations 
                               WHERE customer_id = ? AND status = 'open' 
                               ORDER BY last_message_at DESC LIMIT 1";
                
                $stmt = $conn->prepare($conv_query);
                $stmt->bind_param('i', $customer_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    echo json_encode(['conversation_id' => null, 'messages' => []]);
                    break;
                }
                
                $conversation_id = $result->fetch_assoc()['conversation_id'];
            }
            
            $msg_query = "SELECT m.*, 
                                 c.fullName as customer_name,
                                 e.fullName as employee_name,
                                 e.img_url as employee_img
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
                    $sender = ['fullName' => $row['employee_name'], 'img_url' => $row['employee_img'], 'type' => 'employee'];
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
            // ✅ VALIDATE
            if (!validateCustomerId($customer_id)) {
                exit();
            }
            
            error_log("[SUPPORT API] 📤 Send from customer_id: {$customer_id}");
            
            // ✅ *** SỬA LỖI ***: Dùng biến $json_input_cache đã cache
            $input = $json_input_cache;
            
            if (empty($input['conversation_id']) || empty($input['message'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                break;
            }
            
            $conversation_id = $input['conversation_id'];
            $message = $input['message'];
            
            // ✅ Verify ownership
            $verify = "SELECT assigned_employee_id, status FROM support_conversations 
                       WHERE conversation_id = ? AND customer_id = ?";
            
            $stmt = $conn->prepare($verify);
            $stmt->bind_param('si', $conversation_id, $customer_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                http_response_code(403);
                error_log("[SUPPORT API] ❌ Conversation {$conversation_id} not owned by customer {$customer_id}");
                echo json_encode(['success' => false, 'message' => 'Conversation not found or access denied']);
                break;
            }
            
            $conv = $result->fetch_assoc();
            $assigned_employee_id = $conv['assigned_employee_id'];
            
            $insert = "INSERT INTO support_messages 
                       (conversation_id, sender_type, sender_id, message, assigned_employee_id, created_at, updated_at) 
                       VALUES (?, 'customer', ?, ?, ?, NOW(), NOW())";
            
            $stmt = $conn->prepare($insert);
            $stmt->bind_param('sisi', $conversation_id, $customer_id, $message, $assigned_employee_id);
            $stmt->execute();
            
            $message_id = $conn->insert_id;
            
            $update = "UPDATE support_conversations SET last_message_at = NOW() WHERE conversation_id = ?";
            $stmt = $conn->prepare($update);
            $stmt->bind_param('s', $conversation_id);
            $stmt->execute();
            
            $cust_query = "SELECT fullName FROM customers WHERE customerID = ?";
            $stmt = $conn->prepare($cust_query);
            $stmt->bind_param('i', $customer_id);
            $stmt->execute();
            $customer_info = $stmt->get_result()->fetch_assoc();
            
            triggerLaravelBroadcast($message_id);
            
            echo json_encode([
                'success' => true,
                'message' => [
                    'id' => $message_id,
                    'conversation_id' => $conversation_id,
                    'sender_type' => 'customer',
                    'sender_id' => $customer_id,
                    'message' => $message,
                    'attachment_path' => null,
                    'attachment_name' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'sender' => [
                        'fullName' => $customer_info['fullName'],
                        'type' => 'customer'
                    ]
                ]
            ]);
            break;
            
        case 'employees':
            error_log('[SUPPORT API] 📋 Getting employees...');
            
            try {
                $query = "SELECT employeeID, fullName, email, img_url, role 
                          FROM employees 
                          WHERE role IN ('Staff', 'Admin', 'Marketer') 
                          AND is_active = 1
                          ORDER BY fullName ASC";
                
                $result = $conn->query($query);
                
                if (!$result) {
                    throw new Exception('Query failed: ' . $conn->error);
                }
                
                $employees = [];
                while ($row = $result->fetch_assoc()) {
                    $employees[] = [
                        'employeeID' => (int)$row['employeeID'],
                        'fullName' => $row['fullName'],
                        'email' => $row['email'],
                        'img_url' => $row['img_url'],
                        'role' => $row['role']
                    ];
                }
                
                error_log('[SUPPORT API] ✅ Found ' . count($employees) . ' employees');
                
                echo json_encode([
                    'success' => true,
                    'employees' => $employees
                ]);
                
            } catch (Exception $e) {
                error_log('[SUPPORT API] ❌ Error: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
            break;
            
        case 'transfer':
            // ✅ VALIDATE
            if (!validateCustomerId($customer_id)) {
                exit();
            }
            
            error_log("[SUPPORT API] 🔄 Transfer for customer_id: {$customer_id}");
            
            // ✅ *** SỬA LỖI ***: Dùng biến $json_input_cache đã cache
            $input = $json_input_cache;
            
            if (empty($input['new_employee_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing new_employee_id']);
                break;
            }
            
            $new_employee_id = (int)$input['new_employee_id'];
            
            $emp_query = "SELECT employeeID, fullName, email, img_url FROM employees WHERE employeeID = ?";
            $stmt = $conn->prepare($emp_query);
            $stmt->bind_param('i', $new_employee_id);
            $stmt->execute();
            $emp_result = $stmt->get_result();
            
            if ($emp_result->num_rows === 0) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Employee not found']);
                break;
            }
            
            $employee = $emp_result->fetch_assoc();
            
            $check_conv = "SELECT conversation_id FROM support_conversations 
                           WHERE customer_id = ? AND assigned_employee_id = ? AND status = 'open' LIMIT 1";
            
            $stmt = $conn->prepare($check_conv);
            $stmt->bind_param('ii', $customer_id, $new_employee_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $new_conversation_id = $result->fetch_assoc()['conversation_id'];
            } else {
                $new_conversation_id = 'CONV-' . $customer_id . '-' . $new_employee_id . '-' . uniqid();
                
                $create_new = "INSERT INTO support_conversations 
                               (conversation_id, customer_id, assigned_employee_id, status, priority, subject, last_message_at, created_at, updated_at) 
                               VALUES (?, ?, ?, 'open', 'normal', 'Hỗ trợ từ Mobile App', NOW(), NOW(), NOW())";
                
                $stmt = $conn->prepare($create_new);
                $stmt->bind_param('sii', $new_conversation_id, $customer_id, $new_employee_id);
                $stmt->execute();
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Conversation switched successfully',
                'new_conversation_id' => $new_conversation_id,
                'new_employee' => [
                    'employeeID' => (int)$employee['employeeID'],
                    'fullName' => $employee['fullName'],
                    'email' => $employee['email'],
                    'img_url' => $employee['img_url']
                ]
            ]);
            break;
            
        case 'unread-count':
            // ✅ VALIDATE
            if (!validateCustomerId($customer_id)) {
                exit();
            }
            
            error_log("[SUPPORT API] 🔢 Unread count for customer_id: {$customer_id}");
            
            $query = "SELECT COUNT(*) as count 
                      FROM support_messages m
                      INNER JOIN support_conversations c ON m.conversation_id = c.conversation_id
                      WHERE c.customer_id = ? AND m.sender_type = 'employee' AND m.is_read = 0";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $customer_id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            
            echo json_encode(['count' => (int)$row['count']]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'error' => 'Invalid action',
                'valid_actions' => ['init', 'history', 'send', 'employees', 'transfer', 'unread-count']
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