<?php
// ตั้งค่าการเชื่อมต่อฐานข้อมูล
$host = "localhost";      // ส่วนใหญ่คือ localhost
$dbname = "utm_db"; // เปลี่ยนเป็นชื่อ DB ที่คุณสร้างไว้
$username = "root";       // Username ของฐานข้อมูล (ปกติคือ root)
$password = "";           // Password ของฐานข้อมูล (ถ้าไม่มีให้ปล่อยว่าง)

try {
    // สร้างการเชื่อมต่อด้วย PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // ตั้งค่าให้แสดง Error หากเกิดปัญหา
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // ตั้งค่าการดึงข้อมูลให้เป็นแบบ Array ตามชื่อคอลัมน์
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // echo "เชื่อมต่อฐานข้อมูลสำเร็จ!"; // เปิดไว้เช็คตอนทำครั้งแรกได้ครับ
} catch (PDOException $e) {
    // ถ้าเชื่อมต่อไม่ได้ ให้แสดงข้อความ Error
    die("ขออภัย! ไม่สามารถเชื่อมต่อฐานข้อมูลได้: " . $e->getMessage());
}
?>