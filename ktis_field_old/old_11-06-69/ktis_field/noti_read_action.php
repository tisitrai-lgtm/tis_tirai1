<?php
/**
 * noti_read_action.php - อัปเดตสถานะแจ้งเตือน (อ่านแล้ว / ปิดทิ้ง) ทาง AJAX
 */
require_once 'config.php';
session_start();

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['emp_id'])) {
    $noti_id = isset($_POST['noti_id']) ? intval($_POST['noti_id']) : 0;
    $action  = isset($_POST['action']) ? $_POST['action'] : 'read'; // 'read' หรือ 'dismiss'
    
    if($noti_id > 0) {
        if($action === 'dismiss') {
            // ปิด/ซ่อนกระดิ่งออกจากรายการ — ใช้ is_read = 1 เพื่อซ่อน (ไม่ต้องเพิ่ม column ใหม่)
            $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE noti_id = :noti_id AND emp_id = :emp_id");
        } else {
            // Mark as read ปกติ
            $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE noti_id = :noti_id AND emp_id = :emp_id");
        }
        $stmt->execute([
            ':noti_id' => $noti_id,
            ':emp_id' => $_SESSION['emp_id']
        ]);
        echo json_encode(["status" => "success"]);
        exit;
    }
}
echo json_encode(["status" => "error"]);
?>