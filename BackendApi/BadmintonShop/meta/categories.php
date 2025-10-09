<?php
require_once __DIR__ . '/../bootstrap.php';
try {
  $rows = $mysqli->query("SELECT categoryID, categoryName FROM categories ORDER BY categoryID")->fetch_all(MYSQLI_ASSOC);
  respond(['categories'=>$rows]);
} catch (Throwable $e) { respond(['error'=>'server_error'],500); }
// error_log($e->getMessage());
// respond(['error'=>'server_error'],500);