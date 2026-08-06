<?php
require_once 'config.php';
session_start();

// ตรวจสอบสิทธิ์ (กันคนนอกแอบมาแก้สถานะ)
if (!isset($_SESSION['emp_level']) || $_SESSION['emp_level'] != 'a') {
    echo json_encode(['success' => false]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $status = intval($_POST['status']); // รับค่า 0 หรือ 1

    // ทำการอัปเดตสถานะลงในฐานข้อมูล
    $stmt = $conn->prepare("UPDATE employee SET status = ? WHERE ID = ?");
    $result = $stmt->execute([$status, $id]);

    // ส่งค่ากลับไปบอกหน้าเว็บว่าทำสำเร็จหรือไม่
    echo json_encode(['success' => $result]);
} else {
    echo json_encode(['success' => false]);
}
?>