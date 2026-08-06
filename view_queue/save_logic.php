<?php
// save_logic.php
require_once 'queue_logic.php'; // ไฟล์เชื่อมต่อ DB และ function
date_default_timezone_set('Asia/Bangkok');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'insert';
    $view_round = $_POST['view_round'] ?? 0;
    
    $queue_number = $_POST['queue_number'];
    $tractor_plate = $_POST['tractor_plate'];
    $trailer_plate = $_POST['trailer_plate'];
    $current_time = date('Y-m-d H:i:s');

    if ($action === 'update') {
        // --- กรณีแก้ไขข้อมูล ---
        $entry_id = $_POST['entry_id'];
        
        // SQL อัปเดตข้อมูลพร้อมเปลี่ยนเวลาเป็นเวลาปัจจุบัน (NOW())
        $sql = "UPDATE queue_entries 
                SET queue_number = ?, tractor_plate = ?, trailer_plate = ?, created_at = ? 
                WHERE entry_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $queue_number, $tractor_plate, $trailer_plate, $current_time, $entry_id);
        
        if ($stmt->execute()) {
            $msg = "อัปเดตข้อมูลและลำดับเวลาคิวที่ $queue_number เรียบร้อยแล้ว";
            $type = "success";
        } else {
            $msg = "เกิดข้อผิดพลาดในการอัปเดต";
            $type = "danger";
        }
    } else {
        // --- กรณีลงทะเบียนใหม่ (ถ้าคุณใช้ Logic นี้) ---
        // หา round_id จาก round_number
        $stmt_r = $conn->prepare("SELECT round_id FROM rounds WHERE round_number = ?");
        $stmt_r->bind_param("i", $view_round);
        $stmt_r->execute();
        $round_id = $stmt_r->get_result()->fetch_assoc()['round_id'];

        $sql = "INSERT INTO queue_entries (round_id, queue_number, tractor_plate, trailer_plate, created_at) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issss", $round_id, $queue_number, $tractor_plate, $trailer_plate, $current_time);
        
        if ($stmt->execute()) {
            $msg = "ลงทะเบียนคิวที่ $queue_number สำเร็จ";
            $type = "success";
        } else {
            $msg = "ไม่สามารถบันทึกได้ (เลขคิวอาจซ้ำ)";
            $type = "danger";
        }
    }

    // ส่งกลับหน้าหลักพร้อมข้อความแจ้งเตือน
    header("Location: add_queue.php?view_round=$view_round&msg=" . urlencode($msg) . "&type=$type");
    exit();
}