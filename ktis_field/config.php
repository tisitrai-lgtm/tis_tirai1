<?php
/**
 * config.php - ไฟล์เชื่อมต่อฐานข้อมูลระบบ ktis_smart_field
 */
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // เปลี่ยนเป็น Username ของ Server พี่
define('DB_PASSWORD', '');     // เปลี่ยนเป็น Password ของ Server พี่
define('DB_NAME', 'ktis_smart_field');

date_default_timezone_set('Asia/Bangkok');

// เชื่อมต่อด้วย PDO เพื่อความปลอดภัยและรองรับ PHP 8.2+
try {
    $conn = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USERNAME, DB_PASSWORD);
    // ตั้งค่าให้ออกเป็น Exception เมื่อเกิด Error
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("ERROR: ไม่สามารถเชื่อมต่อฐานข้อมูลได้. " . $e->getMessage());
}
?>