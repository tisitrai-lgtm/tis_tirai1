<?php
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "givewatersugar_view_queue"; 

// 1. สร้างการเชื่อมต่อ
$conn = new mysqli($servername, $username, $password, $dbname);

// 2. ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 3. ตั้งค่าภาษาไทย (สำคัญมากสำหรับชื่อทะเบียนรถ)
$conn->set_charset("utf8mb4");

// 4. ตั้งค่าเขตเวลาให้ตรงกับประเทศไทย (แก้ปัญหาเวลาไม่ตรง)
$conn->query("SET time_zone = '+07:00'");

// 5. (เพิ่มเติม) ตั้งค่าภาษาไทยสำหรับการ Query วันที่ใน SQL (ถ้าจำเป็น)
$conn->query("SET lc_time_names = 'th_TH'");
?>