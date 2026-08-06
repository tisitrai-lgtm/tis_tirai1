<?php
require_once 'config.php';
$mId = $_POST['manager_id']; // คือ ID ของพนักงาน
$hId = $_POST['harvester_id']; // คือ harvester_id ของรถ

// ตรวจสอบว่าเคยมีข้อมูลนี้หรือไม่
$check = $conn->prepare("SELECT * FROM employee_harvester WHERE emp_id = ? AND harvester_id = ?");
$check->execute([$mId, $hId]);

if($check->rowCount() > 0) {
    // ถ้ามีแล้วให้ลบออก
    $conn->prepare("DELETE FROM employee_harvester WHERE emp_id = ? AND harvester_id = ?")->execute([$mId, $hId]);
} else {
    // ถ้ายังไม่มีให้เพิ่มเข้าไป (เพิ่ม NOW() สำหรับ assigned_at)
    $conn->prepare("INSERT INTO employee_harvester (emp_id, harvester_id, assigned_at) VALUES (?, ?, NOW())")->execute([$mId, $hId]);
}
?>