<?php
require_once __DIR__ . '/../bootstrap.php';

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    respond(['error' => 'method_not_allowed'], 405);
  }

  $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  if ($id <= 0) respond(['error' => 'invalid_id'], 400);

  $stmt = $mysqli->prepare(
    "SELECT customerID, fullName, email, phone, address, createdDate
     FROM customers WHERE customerID=?"
  );
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $user = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$user) respond(['error' => 'not_found'], 404);

  respond(['user' => $user]);

} catch (Throwable $e) {
  // error_log($e->getMessage());
  respond(['error' => 'server_error'], 500);
}
