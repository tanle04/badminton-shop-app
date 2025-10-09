<?php
require_once __DIR__ . '/../bootstrap.php';
try {
  $rows = $mysqli->query("SELECT brandID, brandName FROM brands ORDER BY brandID")->fetch_all(MYSQLI_ASSOC);
  respond(['brands'=>$rows]);
} catch (Throwable $e) { respond(['error'=>'server_error'],500); }
// error_log($e->getMessage());
// respond(['error'=>'server_error'],500);