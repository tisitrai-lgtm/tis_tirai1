<?php
/**
 * reply_edit.php - เวอร์ชันเก็บเวลาดั้งเดิมของข้อความเก่าลง Log
 */
require_once 'config.php';
session_start();

if(!isset($_SESSION["emp_id"])) {
    echo json_encode(["status" => "error", "message" => "กรุณาล็อกอินก่อนทำรายการ"]);
    exit;
}

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $reply_id = isset($_POST['reply_id']) ? intval($_POST['reply_id']) : 0;
    $new_text = trim($_POST['reply_text']);
    
    if($reply_id <= 0 || empty($new_text)) {
        echo json_encode(["status" => "error", "message" => "กรุณากรอกข้อความที่ต้องการแก้ไข"]);
        exit;
    }

    try {
        // 🔒 ดึงข้อมูลเดิมรวมถึงเวลาสร้างล่าสุด (created_at) ออกมาด้วย
        $stmt_check = $conn->prepare("SELECT emp_id, reply_text, created_at FROM replies WHERE reply_id = :reply_id");
        $stmt_check->execute([':reply_id' => $reply_id]);
        $reply_data = $stmt_check->fetch();

        if(!$reply_data) {
            echo json_encode(["status" => "error", "message" => "ไม่พบข้อมูลคอมเมนต์"]);
            exit;
        }

        if($reply_data['emp_id'] !== $_SESSION['emp_id'] && $_SESSION['emp_level'] !== 'a') {
            echo json_encode(["status" => "error", "message" => "คุณไม่มีสิทธิ์แก้ไขข้อความของผู้อื่น"]);
            exit;
        }

        // 📝 บันทึกประวัติเก่าลง Log พร้อมพ่วงเวลาดั้งเดิม (old_created_at)
        if($reply_data['reply_text'] !== $new_text) {
            $stmt_log = $conn->prepare("INSERT INTO reply_logs (reply_id, old_text, old_created_at) VALUES (:reply_id, :old_text, :old_created_at)");
            $stmt_log->execute([
                ':reply_id' => $reply_id,
                ':old_text' => $reply_data['reply_text'],
                ':old_created_at' => $reply_data['created_at'] // ดึงเวลาเดิมจากคอมเมนต์มาบันทึกเก็บไว้ที่นี่
            ]);
        }

        // 🔄 อัปเดตข้อความคอมเมนต์ล่าสุดโดยใช้ updated_at แทน (ไม่เขียนทับ created_at เดิม)
        // หมายเหตุ: ต้องมีคอลัมน์ updated_at ในตาราง replies (ALTER TABLE replies ADD updated_at DATETIME NULL DEFAULT NULL;)
        $stmt_update = $conn->prepare("UPDATE replies SET reply_text = :new_text, updated_at = NOW() WHERE reply_id = :reply_id");
        $stmt_update->execute([
            ':new_text' => $new_text,
            ':reply_id' => $reply_id
        ]);

        echo json_encode(["status" => "success", "message" => "แก้ไขข้อความและบันทึกประวัติเวลาเดิมแล้ว"]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "เกิดข้อผิดพลาด: " . $e->getMessage()]);
    }
}
?>