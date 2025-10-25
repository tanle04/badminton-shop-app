<?php
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "badminton_shop";

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_error) {
  respond(["error" => "db_connect_failed"], 500);
}
$mysqli->set_charset('utf8mb4');