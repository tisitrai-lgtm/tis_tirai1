<?php
// config.php
$host = 'localhost';
$db_name = 'gdb_gps';
$username = 'root';
$password = ''; // ใส่รหัสผ่าน MySQL ของคุณ (ถ้าใช้ XAMPP ปกติจะว่างไว้)

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("เชื่อมต่อฐานข้อมูลล้มเหลว: " . $e->getMessage());
}
?>