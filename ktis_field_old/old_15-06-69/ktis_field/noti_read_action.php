<?php
/**
 * noti_read_action.php
 * @deprecated ไฟล์นี้ไม่ได้ใช้งานแล้ว — ระบบใหม่ใช้ delete_notification.php แทน
 * เก็บไว้เพื่อ backward compat เท่านั้น (ป้องกัน 404 หากมี link เก่าเรียกอยู่)
 */
require_once 'config.php';
session_start();

header('Content-Type: application/json');

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['emp_id'])) {
    $noti_id = isset($_POST['noti_id']) ? intval($_POST['noti_id']) : 0;
    
    if($noti_id > 0) {
        try {
            $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE noti_id = :noti_id AND emp_id = :emp_id");
            $stmt->execute([':noti_id' => $noti_id, ':emp_id' => $_SESSION['emp_id']]);
            echo json_encode(["status" => "success"]);
        } catch(Exception $e) {
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        exit;
    }
}
echo json_encode(["status" => "error"]);