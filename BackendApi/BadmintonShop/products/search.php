<?php
require_once __DIR__ . '/../bootstrap.php';

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'GET') respond(['error'=>'method_not_allowed'],405);

  $q   = trim($_GET['q'] ?? '');
  $cat = (int)($_GET['categoryID'] ?? 0);
  $br  = (int)($_GET['brandID'] ?? 0);

  $page  = max(1, (int)($_GET['page']  ?? 1));
  $limit = max(1, min(50, (int)($_GET['limit'] ?? 10)));
  $offset = ($page-1)*$limit;

  $where = [];
  $params = [];
  $types  = '';

  if ($q !== '') { $where[] = "p.productName LIKE CONCAT('%',?,'%')"; $params[]=$q; $types.='s'; }
  if ($cat>0)     { $where[] = "p.categoryID=?"; $params[]=$cat; $types.='i'; }
  if ($br>0)      { $where[] = "p.brandID=?";    $params[]=$br;  $types.='i'; }
  $W = $where ? ("WHERE ".implode(" AND ", $where)) : '';

  // total
  $sqlCount = "SELECT COUNT(*) c FROM products p $W";
  $stc = $mysqli->prepare($sqlCount);
  if ($types) $stc->bind_param($types, ...$params);
  $stc->execute();
  $total = (int)$stc->get_result()->fetch_assoc()['c'];
  $stc->close();

  $sql = "
    SELECT p.productID, p.productName, p.description, p.price, p.stock,
           p.categoryID, p.brandID, p.createdDate,
           COALESCE(pi.imageUrl,'') AS imageUrl
    FROM products p
    LEFT JOIN productimages pi ON pi.productID=p.productID
    $W
    ORDER BY p.productID DESC
    LIMIT ? OFFSET ?
  ";
  $types2 = $types.'ii'; $params2 = $params; array_push($params2, $limit, $offset);
  $st = $mysqli->prepare($sql);
  $st->bind_param($types2, ...$params2);
  $st->execute();
  $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
  $st->close();

  respond([
    'data'=>$rows,
    'meta'=>['page'=>$page,'limit'=>$limit,'total'=>$total,'pages'=>ceil($total/$limit)]
  ]);
} catch (Throwable $e) {
  respond(['error'=>'server_error'],500);
}
