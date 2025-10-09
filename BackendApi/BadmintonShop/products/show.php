<?php
require_once __DIR__ . '/../bootstrap.php';

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'GET') respond(['error'=>'method_not_allowed'],405);
  $id = (int)($_GET['id'] ?? 0); if ($id<=0) respond(['error'=>'invalid_id'],400);

  $stmt = $mysqli->prepare("
    SELECT p.productID, p.productName, p.description, p.price, p.stock,
           p.categoryID, p.brandID, p.createdDate
    FROM products p WHERE p.productID=?
  ");
  $stmt->bind_param("i",$id);
  $stmt->execute();
  $product = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  if (!$product) respond(['error'=>'not_found'],404);

  // images (nếu 1-n)
  $imgs = [];
  $st2 = $mysqli->prepare("SELECT imageUrl FROM productimages WHERE productID=?");
  $st2->bind_param("i",$id);
  $st2->execute();
  $res2 = $st2->get_result();
  while($r=$res2->fetch_assoc()) $imgs[] = $r['imageUrl'];
  $st2->close();

  $product['images'] = $imgs;
  respond(['product'=>$product]);
} catch (Throwable $e) {
  respond(['error'=>'server_error'],500);
}
// error_log($e->getMessage());
// respond(['error'=>'server_error'],500);