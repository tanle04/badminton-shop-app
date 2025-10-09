<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

function ok($data){ echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }
function fail($code, $msg){ http_response_code($code); ok(['error'=>$msg]); }
