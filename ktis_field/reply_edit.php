<?php
/**
 * reply_edit.php - แก้ไขข้อความ + รูปภาพ + ลบรูป
 */
require_once 'config.php';
session_start();

if(!isset($_SESSION["emp_id"])) {
    echo json_encode(["status" => "error", "message" => "กรุณาล็อกอินก่อนทำรายการ"]);
    exit;
}

if($_SERVER["REQUEST_METHOD"] !== "POST") { exit; }

$reply_id = intval($_POST['reply_id'] ?? 0);
$new_text = trim($_POST['reply_text'] ?? '');

if($reply_id <= 0 || empty($new_text)) {
    echo json_encode(["status" => "error", "message" => "กรุณากรอกข้อความที่ต้องการแก้ไข"]);
    exit;
}

try {
    // ── ดึงข้อมูลเดิม ──
    $stmt_check = $conn->prepare("SELECT emp_id, reply_text, reply_image, created_at FROM replies WHERE reply_id = :reply_id");
    $stmt_check->execute([':reply_id' => $reply_id]);
    $reply_data = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if(!$reply_data) {
        echo json_encode(["status" => "error", "message" => "ไม่พบข้อมูลคอมเมนต์ (id: $reply_id)"]);
        exit;
    }

    // ── ตรวจสิทธิ์ (cast เป็น int ทั้งคู่ป้องกัน type mismatch) ──
    if((int)$reply_data['emp_id'] !== (int)$_SESSION['emp_id'] && ($_SESSION['emp_level'] ?? 'u') !== 'a') {
        echo json_encode(["status" => "error", "message" => "คุณไม่มีสิทธิ์แก้ไขข้อความของผู้อื่น"]);
        exit;
    }

    // ── จัดการรูปภาพ ──
    $new_image_path  = $reply_data['reply_image']; // default = รูปเดิม
    $delete_image    = ($_POST['delete_image'] ?? '0') === '1';

    // 1. ลบรูปเดิมก่อนเสมอถ้ามีการอัปโหลดใหม่ หรือกดลบ
    $has_new_file = isset($_FILES['reply_image']) && $_FILES['reply_image']['error'] === 0;

    if(($delete_image || $has_new_file) && !empty($reply_data['reply_image'])) {
        if(file_exists($reply_data['reply_image'])) {
            unlink($reply_data['reply_image']);
        }
        $new_image_path = null;
    }

    // 2. อัปโหลดรูปใหม่ (ถ้ามี และไม่ได้กดแค่ลบ)
    if($has_new_file && !$delete_image) {
        $finfo     = new finfo(FILEINFO_MIME_TYPE);
        $real_mime = $finfo->file($_FILES['reply_image']['tmp_name']);
        $allowed   = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];

        if(!isset($allowed[$real_mime])) {
            echo json_encode(["status" => "error", "message" => "ไฟล์รูปภาพไม่ถูกต้อง (รองรับ JPG, PNG, GIF, WEBP)"]);
            exit;
        }

        $ext   = $allowed[$real_mime];
        $fname = 'reply_'.time().'_'.uniqid().'.'.$ext;
        $dir   = 'uploads/'.date('Y/m/d').'/';
        if(!is_dir($dir)) mkdir($dir, 0777, true);

        if(move_uploaded_file($_FILES['reply_image']['tmp_name'], $dir.$fname)) {
            $new_image_path = $dir.$fname;
        }
    }

    // ── บันทึก log ถ้าข้อความเปลี่ยน ──
    if($reply_data['reply_text'] !== $new_text) {
        $conn->prepare("INSERT INTO reply_logs (reply_id, old_text, old_created_at) VALUES (:rid, :old, :ocat)")
             ->execute([':rid'=>$reply_id, ':old'=>$reply_data['reply_text'], ':ocat'=>$reply_data['created_at']]);
    }

    // ── อัปเดต ──
    $conn->prepare("UPDATE replies SET reply_text=:txt, reply_image=:img, updated_at=NOW() WHERE reply_id=:rid")
         ->execute([':txt'=>$new_text, ':img'=>$new_image_path, ':rid'=>$reply_id]);

    // ── สร้าง img HTML ส่งกลับ ──
    $img_html = '';
    if(!empty($new_image_path)) {
        $img_esc  = htmlspecialchars($new_image_path);
        $img_html = '<div><img src="'.$img_esc.'" class="chat-embedded-img" onclick="window.open(this.src,\'_blank\')" style="cursor:pointer;"></div>';
    }

    echo json_encode([
        "status"       => "success",
        "message"      => "แก้ไขเรียบร้อยแล้ว",
        "updated_text" => nl2br(htmlspecialchars($new_text)),
        "img_html"     => $img_html,
        "has_image"    => !empty($new_image_path),
    ]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "เกิดข้อผิดพลาด: ".$e->getMessage()]);
}
?>