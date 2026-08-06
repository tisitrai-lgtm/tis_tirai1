<?php
/**
 * notification_api.php - ระบบอัปเดตสถานะการอ่านกระดิ่งแจ้งเตือน (Mark as Read)
 */
require_once 'config.php';
session_start();

// ตรวจสอบล็อกอิน
if(!isset($_SESSION["emp_id"])) {
    echo json_encode(["status" => "error", "message" => "ไม่ได้ล็อกอิน"]);
    exit;
}

// รับค่าไอดีโพสต์ที่พนักงานกดเปิดดูจากกระดิ่ง
$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
$emp_id = $_SESSION['emp_id'];

if($post_id > 0) {
    try {
        // อัปเดตสถานะเป็นอ่านแล้ว (is_read = 1) เฉพาะของพนักงานคนนั้นในโพสต์นั้น ๆ
        $sql = "UPDATE notifications SET is_read = 1 WHERE post_id = :post_id AND emp_id = :emp_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':post_id' => $post_id,
            ':emp_id' => $emp_id
        ]);
        
        // ดึงวันที่ของโพสต์เพื่อพาไปหน้าวันที่ถูกต้อง
        $stmt2 = $conn->prepare("SELECT DATE(created_at) as post_date FROM posts WHERE post_id = :post_id");
        $stmt2->execute([':post_id' => $post_id]);
        $row = $stmt2->fetch(PDO::FETCH_ASSOC);
        $post_date = $row ? $row['post_date'] : date('Y-m-d');
        
        // เมื่อเคลียร์กระดิ่งเสร็จ วิ่งนำทางพนักงานตรงไปยังโพสต์หลักบนหน้าฟีดทันที (พร้อมพาเปลี่ยนวันที่)
        header("Location: index.php?search_date=" . $post_date . "#post-card-" . $post_id);
        exit;
    } catch (Exception $e) {
        die("เกิดข้อผิดพลาดในการอัปเดตแจ้งเตือน: " . $e->getMessage());
    }
} else {
    // หากกดดูแจ้งเตือนทั้งหมดรวม ๆ ให้เคลียร์ทุกอันของพนักงานคนนี้
    try {
        $sql = "UPDATE notifications SET is_read = 1 WHERE emp_id = :emp_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':emp_id' => $emp_id]);
        
        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        die("เกิดข้อผิดพลาด: " . $e->getMessage());
    }
}
?>