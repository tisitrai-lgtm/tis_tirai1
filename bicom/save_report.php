<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $main = $_POST['main_license'];
    $trail = $_POST['trailer_license'] ?: null;
    $h_code = $_POST['h_code'];
    $statn_code = $_SESSION['statn_code'] ?? 101;
    $report_date = $_POST['report_date'];
    
    // รับค่าประเภทอ้อยจาก Dropdown (อ้อยลำ/อ้อยท่อน)
    $owner_name = $_POST['owner_name'] ?? 'ไม่ระบุ';

    try {
        // เพิ่มฟิลด์ owner_name ในคำสั่ง INSERT
        $stmt = $pdo->prepare("INSERT INTO daily_truck_reports (report_date, statn_code, main_truck_license, trailer_license, harvester_code, owner_name) VALUES (?, ?, ?, ?, ?, ?)");
        
        // ตรวจสอบผลการทำงานผ่าน execute โดยตรง
        if ($stmt->execute([$report_date, $statn_code, $main, $trail, $h_code, $owner_name])) {
            $_SESSION['success'] = "บันทึกข้อมูลรถ $main ($owner_name) เรียบร้อยแล้ว";
        } else {
            $_SESSION['error'] = "ไม่สามารถบันทึกข้อมูลได้ (Database Error)";
        }
        
    } catch (Exception $e) {
        // หากเกิด Error เช่น Primary Key ซ้ำ หรือฟิลด์ไม่ครบ
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }

    // ย้าย header มาไว้ข้างในนี้ เพื่อให้ส่งกลับหลังจากจัดการ Session เสร็จ
    header("Location: manage_trucks.php?report_date=" . $report_date);
    exit(); 
}
?>