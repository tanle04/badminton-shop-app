<?php
header("Content-Type: application/json; charset=UTF-8");
require_once("../bootstrap.php");

$sql = "SELECT categoryID, categoryName FROM categories ORDER BY categoryID ASC";
$res = $mysqli->query($sql);

$items = [];
while ($row = $res->fetch_assoc()) {
    $items[] = $row;
}

echo json_encode([
    "success" => true,
    "items" => $items
], JSON_UNESCAPED_UNICODE);

$mysqli->close();
?>
