<?php
require_once '../config/db.php'; // đảm bảo file db.php có $mysqli

header('Content-Type: application/json; charset=utf-8');

$sql = "
SELECT s.sliderID, s.title, s.imageUrl, s.backlink, s.status,
       s.createdDate, e.fullName AS createdBy
FROM sliders s
LEFT JOIN employees e ON s.employeeID = e.employeeID
WHERE s.status = 'active'
ORDER BY s.createdDate DESC
";

$res = $mysqli->query($sql);
$data = [];

if ($res) {
    while ($row = $res->fetch_assoc()) {
        // Ghép URL ảnh thật
        $row['imageUrl'] = 'http://' . $_SERVER['HTTP_HOST'] . '/api/BadmintonShop/images/sliders/' . $row['imageUrl'];
        $data[] = $row;
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['error' => $mysqli->error]);
}

$mysqli->close();
?>
