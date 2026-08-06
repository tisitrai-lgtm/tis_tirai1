<?php
$servername = "localhost";
$username = "root"; // ชื่อผู้ใช้ฐานข้อมูลของคุณ
$password = ""; // รหัสผ่านฐานข้อมูลของคุณ
$dbname = "plan_ton"; // ชื่อฐานข้อมูลที่คุณสร้างไว้

// สร้างการเชื่อมต่อ
$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// กำหนด Charset ให้รองรับภาษาไทย
$conn->set_charset("utf8mb4");
?>