<?php
session_start();
require_once 'db_connect.php';

// ตรวจสอบสิทธิ์การเข้าถึง
if (!isset($_SESSION['statn_code']) || !isset($_GET['log_id'])) {
    header("Location: index.php");
    exit;
}

$log_id = $_GET['log_id'];
$statn_code = $_SESSION['statn_code'];

try {
    // ลบข้อมูลโดยเช็ค STATN_CODE เพื่อความปลอดภัย (ลบได้เฉพาะหน่วยของตนเอง)
    $sql = "DELETE FROM conversion_logs WHERE LOG_ID = ? AND STATN_CODE = ?";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$log_id, $statn_code])) {
        // ลบสำเร็จ กลับไปหน้าหลักพร้อมส่งข้อความแจ้งเตือน
        header("Location: index.php?msg=deleted");
    } else {
        echo "ไม่สามารถลบข้อมูลได้";
    }
} catch (PDOException $e) {
    echo "เกิดข้อผิดพลาด: " . $e->getMessage();
}