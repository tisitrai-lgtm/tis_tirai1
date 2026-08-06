<?php
/**
 * reply_action.php - ตัวเต็มแก้ไขให้แมตช์ตามโครงสร้างฐานข้อมูล ktis_smart_field
 */
require_once 'config.php';
session_start();

if (!isset($_SESSION["emp_id"])) {
    echo json_encode(["status" => "error", "message" => "กรุณาล็อกอินเข้าสู่ระบบก่อนทำรายการ"]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $reply_text = isset($_POST['reply_text']) ? trim($_POST['reply_text']) : '';
    
    if ($post_id <= 0 || empty($reply_text)) {
        echo json_encode(["status" => "error", "message" => "กรุณากรอกข้อความแสดงความคิดเห็น"]);
        exit;
    }

    // จัดการอัปโหลดรูปภาพคอมเมนต์ (ถ้ามี)
    $reply_image_path = null;
    if (isset($_FILES['reply_image']) && $_FILES['reply_image']['error'] == 0) {
        // ตรวจสอบ MIME ด้วย finfo_file() จากไฟล์จริง (ไม่ใช่จาก HTTP Header ที่ Client ปลอมได้)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $real_mime = $finfo->file($_FILES['reply_image']['tmp_name']);
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (in_array($real_mime, $allowed_mimes)) {
            // ตรวจนามสกุลไฟล์เพิ่มเติมเพื่อความปลอดภัย
            $mime_to_ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
            $ext = $mime_to_ext[$real_mime];
            $new_filename = "reply_" . time() . "_" . uniqid() . "." . $ext;
            $upload_dir = "uploads/" . date('Y-m-d') . "/";
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            if (move_uploaded_file($_FILES['reply_image']['tmp_name'], $upload_dir . $new_filename)) {
                $reply_image_path = $upload_dir . $new_filename;
            }
        }
    }

    try {
        $conn->beginTransaction();

        // 1. บันทึกคอมเมนต์ลงตาราง replies
        $stmt_reply = $conn->prepare("INSERT INTO replies (post_id, emp_id, emp_name, emp_unit, reply_text, reply_image, created_at) 
                                      VALUES (:post_id, :emp_id, :emp_name, :emp_unit, :reply_text, :reply_image, NOW())");
        $stmt_reply->execute([
            ':post_id'     => $post_id,
            ':emp_id'      => $_SESSION['emp_id'],
            ':emp_name'    => $_SESSION['emp_name'] ?? 'พนักงาน',
            ':emp_unit'    => $_SESSION['emp_unit'] ?? '',
            ':reply_text'  => $reply_text,
            ':reply_image' => $reply_image_path
        ]);

        // 2. ค้นหาเจ้าของโพสต์เพื่อยิงกระดิ่งแจ้งเตือน
        $stmt_post = $conn->prepare("SELECT emp_id, target_unit FROM posts WHERE post_id = :post_id");
        $stmt_post->execute([':post_id' => $post_id]);
        $post_info = $stmt_post->fetch();

        if ($post_info) {
            $owner_id = $post_info['emp_id'];
            $target_unit = $post_info['target_unit']; // เช่น "111 บางขลัง"

            // ส่งสัญญาณแจ้งเตือนหาคนอื่นที่ไม่ใช่คนพิมพ์คอมเมนต์ตัวเอง
            if ($owner_id != $_SESSION['emp_id']) {
                $sender_name = $_SESSION['emp_name'] ?? 'พนักงานไร่';
                $noti_text = "คุณ " . $sender_name . " ได้รายงานความคืบหน้าในโพสต์แจ้งปัญหาของคุณ";

                // บันทึกเข้าตาราง notifications
                $stmt_noti = $conn->prepare("INSERT INTO notifications (post_id, emp_id, target_unit, noti_text, is_read, created_at) 
                                             VALUES (:post_id, :emp_id, :target_unit, :noti_text, 0, NOW())");
                $stmt_noti->execute([
                    ':post_id'     => $post_id,
                    ':emp_id'      => $owner_id,       // แจ้งเตือนไปยังไอดีเจ้าของโพสต์
                    ':target_unit' => $target_unit,    // สแตมป์ชื่อหน่วย เช่น "111 บางขลัง"
                    ':noti_text'   => $noti_text
                ]);
            }
        }

        $conn->commit();
        echo json_encode(["status" => "success", "message" => "บันทึกความคิดเห็นสำเร็จ"]);

    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(["status" => "error", "message" => "เกิดข้อผิดพลาด: " . $e->getMessage()]);
    }
}
?>