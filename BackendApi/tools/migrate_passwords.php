<?php
$s="localhost"; $u="root"; $p=""; $db="badminton_shop";
$conn = new mysqli($s,$u,$p,$db);
$conn->set_charset('utf8mb4');

/* chọn những record còn plaintext và chưa có hash */
$sql = "SELECT customerID, password FROM customers
        WHERE (password IS NOT NULL AND password <> '')
          AND (password_hash IS NULL OR password_hash = '')";
$res = $conn->query($sql);

$ok = 0; $fail = 0;
while ($row = $res->fetch_assoc()) {
  $id   = (int)$row['customerID'];
  $pwd  = $row['password'];
  $hash = password_hash($pwd, PASSWORD_DEFAULT);

  $stmt = $conn->prepare("UPDATE customers SET password_hash=? WHERE customerID=?");
  $stmt->bind_param("si", $hash, $id);
  if ($stmt->execute()) $ok++; else $fail++;
  $stmt->close();
}
$conn->close();
echo json_encode(["migrated"=>$ok, "failed"=>$fail]);
