<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $backlink = $_POST['backlink'] ?? '';
    $employeeID = $_POST['employeeID'] ?? null;

    if (!isset($_FILES['image'])) {
        die("No image uploaded!");
    }

    $file = $_FILES['image'];
    $targetDir = "../images/sliders/";
    $fileName = basename($file['name']);
    $targetFile = $targetDir . $fileName;

    // Upload ảnh thật
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        $sql = "INSERT INTO sliders (title, imageUrl, backlink, employeeID)
                VALUES (?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("sssi", $title, $fileName, $backlink, $employeeID);
        if ($stmt->execute()) {
            echo "✅ Upload thành công!";
        } else {
            echo "❌ Lỗi DB: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "❌ Upload thất bại!";
    }

    $mysqli->close();
} else {
?>
<form method="POST" enctype="multipart/form-data">
    <label>Title: <input type="text" name="title"></label><br>
    <label>Backlink: <input type="text" name="backlink"></label><br>
    <label>EmployeeID: <input type="number" name="employeeID"></label><br>
    <label>Image: <input type="file" name="image"></label><br>
    <button type="submit">Upload Slider</button>
</form>
<?php } ?>
