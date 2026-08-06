<?php
/**
 * post_delete.php - ลบโพสต์หลัก (เฉพาะ Admin ระดับ 'a' เท่านั้น)
 */
require_once 'config.php';
session_start();

if(!isset($_SESSION["emp_id"]) || $_SESSION['emp_level'] !== 'a') {
    echo json_encode(["status" => "error", "message" => "คุณไม่มีสิทธิ์ในการลบโพสต์นี้"]);
    exit;
}

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    
    if($post_id <= 0) {
        echo json_encode(["status" => "error", "message" => "ข้อมูลไม่ถูกต้อง"]);
        exit;
    }

    try {
        // ดึงที่อยู่ไฟล์รูปมาลบออกจาก server ก่อน
        $stmt_img = $conn->prepare("SELECT post_image, post_image_2, post_image_3 FROM posts WHERE post_id = :post_id AND crop_year = :crop_year");
        $stmt_img->execute([':post_id' => $post_id, ':crop_year' => $_SESSION['crop_year']]);
        $images = $stmt_img->fetch();

        if($images) {
            foreach(['post_image', 'post_image_2', 'post_image_3'] as $f) {
                if(!empty($images[$f]) && file_exists($images[$f])) {
                    unlink($images[$f]);
                }
            }
        }

        // ลบโพสต์ (replies และ notifications ถูกลบออโต้ด้วย CASCADE)
        $stmt = $conn->prepare("DELETE FROM posts WHERE post_id = :post_id AND crop_year = :crop_year");
        $stmt->execute([':post_id' => $post_id, ':crop_year' => $_SESSION['crop_year']]);

        echo json_encode(["status" => "success", "message" => "ลบรายการแจ้งเหตุเรียบร้อยแล้ว"]);
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "เกิดข้อผิดพลาด: " . $e->getMessage()]);
    }
}
?>