<?php
$servername = "localhost";
$username = "givewatersugar_view_queue"; // เปลี่ยนเป็นชื่อผู้ใช้จริง
$password = "Supanat@2544"; // เปลี่ยนเป็นรหัสผ่านจริง
$dbname = "givewatersugar_view_queue"; // เปลี่ยนเป็นชื่อฐานข้อมูลจริง

// สร้างการเชื่อมต่อ
$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// กำหนด charset เป็น UTF-8 เพื่อรองรับภาษาไทย
$conn->set_charset("utf8mb4");
?>