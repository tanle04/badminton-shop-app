<?php
header('Content-Type: application/json; charset=utf-8');
// JSON: {customerID, shippingAddress, items:[{variantID,quantity}]}
require_once '../bootstrap.php';
$in = json_decode(file_get_contents('php://input'), true);
$cid  = (int)($in['customerID'] ?? 0);
$addr = trim($in['shippingAddress'] ?? '');
$items= $in['items'] ?? [];

if(!$cid || !$items){ http_response_code(400); echo json_encode(["error"=>"invalid_input"]); exit; }

$mysqli->begin_transaction();
try{
  // tính tổng
  $total = 0;
  foreach($items as $it){
    $vid = (int)$it['variantID']; $qty = (int)$it['quantity'];
    $q = $mysqli->query("SELECT price, stock FROM product_variants WHERE variantID=".$vid." FOR UPDATE");
    $row = $q->fetch_assoc();
    if(!$row || $row['stock'] < $qty) throw new Exception("out_of_stock");
    $total += $row['price'] * $qty;
  }

  // tạo order
  $st = $mysqli->prepare("INSERT INTO orders(customerID,total,shippingAddress) VALUES (?,?,?)");
  $st->bind_param("ids",$cid,$total,$addr);
  $st->execute(); $orderID = $st->insert_id; $st->close();

  // insert details + trừ tồn
  $stD = $mysqli->prepare("INSERT INTO orderdetails(orderID,variantID,quantity,price) VALUES (?,?,?,?)");
  foreach($items as $it){
    $vid = (int)$it['variantID']; $qty = (int)$it['quantity'];
    $r = $mysqli->query("SELECT price FROM product_variants WHERE variantID=".$vid);
    $price = (float)$r->fetch_assoc()['price'];
    $stD->bind_param("iiid", $orderID, $vid, $qty, $price);
    $stD->execute();
    $mysqli->query("UPDATE product_variants SET stock = stock - ".(int)$qty." WHERE variantID=".$vid);
  }
  $stD->close();

  $mysqli->commit();
  echo json_encode(["message"=>"ok", "orderID"=>$orderID], JSON_UNESCAPED_UNICODE);
}catch(Exception $e){
  $mysqli->rollback();
  http_response_code(409);
  echo json_encode(["error"=>$e->getMessage()]);
}

$mysqli->close();
// --- IGNORE ---