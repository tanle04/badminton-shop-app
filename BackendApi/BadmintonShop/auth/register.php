<?php
require_once __DIR__ . '/../bootstrap.php';

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['error' => 'method_not_allowed'], 405);
  }

  $in = json_decode(file_get_contents('php://input'), true) ?: $_POST;
  $full  = trim($in['fullName'] ?? '');
  $email = trim($in['email'] ?? '');
  $pass  = (string)($in['password'] ?? '');
  $phone = trim($in['phone'] ?? '');
  $addr  = trim($in['address'] ?? '');

  if ($full === '' || $pass === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(['error' => 'invalid_input'], 400);
  }

  // trùng email?
  $ck = $mysqli->prepare("SELECT 1 FROM customers WHERE email=?");
  $ck->bind_param("s", $email);
  $ck->execute(); $ck->store_result();
  if ($ck->num_rows > 0) {
    $ck->close();
    respond(['error' => 'email_exists'], 409);
  }
  $ck->close();

  $hash = password_hash($pass, PASSWORD_DEFAULT);
  $ins = $mysqli->prepare(
    "INSERT INTO customers (fullName, email, password_hash, phone, address)
     VALUES (?, ?, ?, ?, ?)"
  );
  $ins->bind_param("sssss", $full, $email, $hash, $phone, $addr);
  $ins->execute();
  $newId = $ins->insert_id;
  $ins->close();

  respond(['message' => 'registered', 'customerID' => $newId], 201);

} catch (Throwable $e) {
  // error_log($e->getMessage());
  respond(['error' => 'server_error'], 500);
}
