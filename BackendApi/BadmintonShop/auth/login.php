<?php
require_once __DIR__ . '/../bootstrap.php';

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['error' => 'method_not_allowed'], 405);
  }

  // JSON hoặc form
  $in = json_decode(file_get_contents('php://input'), true) ?: $_POST;
  $email = isset($in['email']) ? trim($in['email']) : '';
  $pass  = isset($in['password']) ? (string)$in['password'] : '';

  if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $pass === '') {
    respond(['error' => 'invalid_input'], 400);
  }

  // lấy user
  $stmt = $mysqli->prepare(
    "SELECT customerID, fullName, email, password_hash, phone, address, createdDate
     FROM customers WHERE email = ? LIMIT 1"
  );
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $user = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$user || !password_verify($pass, $user['password_hash'])) {
    // usleep(300000); // chống brute force (tùy chọn)
    respond(['error' => 'invalid_credentials'], 401);
  }

  // rehash nếu cần
  if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
    $newHash = password_hash($pass, PASSWORD_DEFAULT);
    $up = $mysqli->prepare("UPDATE customers SET password_hash=? WHERE customerID=?");
    $cid = (int)$user['customerID'];
    $up->bind_param("si", $newHash, $cid);
    $up->execute(); 
    $up->close();
  }

  unset($user['password_hash']);
  respond(['message' => 'ok', 'user' => $user]);

} catch (Throwable $e) {
  respond(['error' => $e->getMessage()], 500);
}
// error_log($e->getMessage());
// respond(['error' => 'server_error'], 500);
