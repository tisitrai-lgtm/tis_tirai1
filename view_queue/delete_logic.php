<?php
// delete_logic.php
require_once 'queue_logic.php';

$entry_id = $_GET['entry_id'] ?? null;
$view_round = $_GET['round'] ?? 0;

if ($entry_id) {
    // ดึงเลขคิวมาก่อนลบเพื่อเอาไปโชว์ในแจ้งเตือน
    $stmt_info = $conn->prepare("SELECT queue_number FROM queue_entries WHERE entry_id = ?");
    $stmt_info->bind_param("i", $entry_id);
    $stmt_info->execute();
    $data = $stmt_info->get_result()->fetch_assoc();
    $q_num = $data['queue_number'] ?? '';

    // ทำการลบ
    $stmt_del = $conn->prepare("DELETE FROM queue_entries WHERE entry_id = ?");
    $stmt_del->bind_param("i", $entry_id);
    
    if ($stmt_del->execute()) {
        $msg = "ลบคิวที่ $q_num เรียบร้อยแล้ว";
        $type = "danger"; // ใช้สีแดงสำหรับการลบ
    } else {
        $msg = "ไม่สามารถลบข้อมูลได้";
        $type = "warning";
    }
}

// ส่งกลับหน้าหลัก
header("Location: add_queue.php?view_round=$view_round&msg=" . urlencode($msg) . "&type=$type");
exit();