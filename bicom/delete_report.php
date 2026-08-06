<?php
session_start();
require_once 'db_connect.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $report_date = $_GET['report_date'] ?? date('Y-m-d');
    
    // ลบข้อมูลโดยตรวจสอบ ID
    $stmt = $pdo->prepare("DELETE FROM daily_truck_reports WHERE id = ?");
    if ($stmt->execute([$id])) {
        // ลบสำเร็จ กลับไปที่หน้าหลักพร้อมวันที่เดิม
        header("Location: manage_trucks.php?report_date=" . $report_date);
        exit();
    }
}
?>