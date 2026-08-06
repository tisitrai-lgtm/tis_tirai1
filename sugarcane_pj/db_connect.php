<?php
$servername = "localhost";  // หรือ IP ของเซิร์ฟเวอร์ฐานข้อมูล
$username = "root";         // ชื่อผู้ใช้ฐานข้อมูล
$password = "";             // รหัสผ่านฐานข้อมูล (ถ้ามี)
$dbname = "sugarcane_db";  // ชื่อฐานข้อมูลที่สร้างไว้

// สร้างการเชื่อมต่อ
$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->query("SET time_zone = '+07:00'");
// กำหนด charset ให้รองรับ utf8mb4
$conn->set_charset("utf8mb4");
?>