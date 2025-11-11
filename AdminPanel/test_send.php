<?php
// Enable error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/api/BadmintonShop/config/db.php';

$conn = $mysqli;

// Test data
$customer_id = 1;
$conversation_id = 'CONV-1-690c90e1708c2';
$message = 'test message';

echo "<pre style='background: #000; color: #0f0; padding: 20px; font-family: monospace;'>";
echo "=== TESTING SEND MESSAGE ===\n\n";

// Step 1: Check conversation exists
echo "Step 1: Checking if conversation exists...\n";
$verify = "SELECT * FROM support_conversations WHERE conversation_id = ? AND customer_id = ?";
$stmt = $conn->prepare($verify);

if (!$stmt) {
    echo "❌ PREPARE FAILED: " . $conn->error . "\n";
    die();
}

$stmt->bind_param('si', $conversation_id, $customer_id);

if (!$stmt->execute()) {
    echo "❌ EXECUTE FAILED: " . $stmt->error . "\n";
    die();
}

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "❌ CONVERSATION NOT FOUND!\n\n";
    
    // Show all conversations
    echo "=== ALL CONVERSATIONS FOR CUSTOMER $customer_id ===\n";
    $all = "SELECT conversation_id, assigned_employee_id, status, created_at FROM support_conversations WHERE customer_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($all);
    $stmt->bind_param('i', $customer_id);
    $stmt->execute();
    $all_result = $stmt->get_result();
    
    while ($row = $all_result->fetch_assoc()) {
        echo "\nID: " . $row['conversation_id'];
        echo "\nEmployee: " . ($row['assigned_employee_id'] ?? 'NULL');
        echo "\nStatus: " . $row['status'];
        echo "\nCreated: " . $row['created_at'];
        echo "\n" . str_repeat("-", 80) . "\n";
    }
    
    die();
}

$conv = $result->fetch_assoc();
echo "✅ FOUND CONVERSATION!\n";
echo "Status: " . $conv['status'] . "\n";
echo "Employee: " . ($conv['assigned_employee_id'] ?? 'NULL') . "\n";
echo "Created: " . $conv['created_at'] . "\n\n";

if ($conv['status'] === 'closed') {
    echo "❌ CONVERSATION IS CLOSED!\n";
    die();
}

$assigned_employee_id = $conv['assigned_employee_id'];

// Step 2: Try to insert message
echo "Step 2: Attempting to insert message...\n";

$insert = "INSERT INTO support_messages 
          (conversation_id, sender_type, sender_id, message, assigned_employee_id, created_at, updated_at) 
          VALUES (?, 'customer', ?, ?, ?, NOW(), NOW())";

$stmt = $conn->prepare($insert);

if (!$stmt) {
    echo "❌ PREPARE INSERT FAILED: " . $conn->error . "\n";
    die();
}

$stmt->bind_param('sisi', $conversation_id, $customer_id, $message, $assigned_employee_id);

if (!$stmt->execute()) {
    echo "❌ INSERT FAILED: " . $stmt->error . "\n";
    echo "\nDETAILS:\n";
    echo "conversation_id: " . $conversation_id . "\n";
    echo "customer_id: " . $customer_id . "\n";
    echo "message: " . $message . "\n";
    echo "assigned_employee_id: " . ($assigned_employee_id ?? 'NULL') . "\n";
    die();
}

$message_id = $conn->insert_id;
echo "✅ MESSAGE INSERTED! ID: " . $message_id . "\n\n";

// Step 3: Update conversation
echo "Step 3: Updating conversation timestamp...\n";
$update = "UPDATE support_conversations SET last_message_at = NOW() WHERE conversation_id = ?";
$stmt = $conn->prepare($update);
$stmt->bind_param('s', $conversation_id);

if (!$stmt->execute()) {
    echo "❌ UPDATE FAILED: " . $stmt->error . "\n";
} else {
    echo "✅ CONVERSATION UPDATED!\n\n";
}

echo "=== TEST COMPLETED SUCCESSFULLY ===\n";
echo "</pre>";
?>