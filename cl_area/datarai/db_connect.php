<?php
// กำหนดตัวแปรสำหรับเชื่อมต่อฐานข้อมูล
$servername = "localhost"; // หรือ IP address ของ Server
$username = "root";       // เปลี่ยนเป็น username ของฐานข้อมูลของคุณ
$password = "";           // เปลี่ยนเป็น password ของฐานข้อมูลของคุณ
$dbname = "cl_area";      // ชื่อฐานข้อมูลที่คุณสร้าง

try {
    // สร้างการเชื่อมต่อ PDO
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // ตั้งค่าโหมด Error เป็น Exception เพื่อให้จับ Error ได้ง่ายขึ้น
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch(PDOException $e) {
    // ถ้าเชื่อมต่อไม่สำเร็จ จะแสดงข้อความแจ้งเตือน
    die("Connection failed: " . $e->getMessage());
}
?>