<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "utm_db";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("เชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}
$conn->set_charset("utf8");
?>