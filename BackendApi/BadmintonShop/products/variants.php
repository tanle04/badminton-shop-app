<?php
require_once '../bootstrap.php';
$pid = (int)($_GET['productID'] ?? 0);

$sql = "
SELECT v.variantID, v.sku, v.price, v.stock,
       a.attributeName, pav.valueName
FROM product_variants v
LEFT JOIN variant_attribute_values vav ON vav.variantID = v.variantID
LEFT JOIN product_attribute_values pav ON pav.valueID = vav.valueID
LEFT JOIN product_attributes a ON a.attributeID = pav.attributeID
WHERE v.productID = ?
ORDER BY v.variantID";
$st = $mysqli->prepare($sql);
$st->bind_param("i",$pid);
$st->execute(); $res = $st->get_result();

$byVariant = [];
while($row = $res->fetch_assoc()){
  $vid = $row['variantID'];
  if(!isset($byVariant[$vid])){
    $byVariant[$vid] = [
      "variantID"=>$vid, "sku"=>$row['sku'],
      "price"=>$row['price'], "stock"=>$row['stock'],
      "attrs"=>[]
    ];
  }
  if($row['attributeName']){
    $byVariant[$vid]["attrs"][] = [
      "name"=>$row['attributeName'],
      "value"=>$row['valueName']
    ];
  }
}
echo json_encode(array_values($byVariant), JSON_UNESCAPED_UNICODE);
$st->close();
$mysqli->close();