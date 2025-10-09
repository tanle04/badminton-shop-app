<?php
require_once __DIR__ . '/../config/db.php';   // lùi ra 1 cấp rồi vào config

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => $mysqli->ping()]);
$mysqli->close();
