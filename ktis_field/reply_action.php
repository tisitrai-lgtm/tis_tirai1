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
    $post_id    = isset($_POST['post_id'])    ? intval($_POST['post_id'])    : 0;
    $reply_text = isset($_POST['reply_text']) ? trim($_POST['reply_text'])   : '';

    if ($post_id <= 0 || empty($reply_text)) {
        echo json_encode(["status" => "error", "message" => "กรุณากรอกข้อความแสดงความคิดเห็น"]);
        exit;
    }

    // ── อัปโหลดรูปภาพ (ถ้ามี) ──
    $reply_image_path = null;
    if (isset($_FILES['reply_image']) && $_FILES['reply_image']['error'] == 0) {
        $finfo     = new finfo(FILEINFO_MIME_TYPE);
        $real_mime = $finfo->file($_FILES['reply_image']['tmp_name']);
        $mime_to_ext = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];

        if (isset($mime_to_ext[$real_mime])) {
            $ext        = $mime_to_ext[$real_mime];
            $new_filename = "reply_".time()."_".uniqid().".".$ext;
            $upload_dir = "uploads/".date('Y/m/d')."/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            if (move_uploaded_file($_FILES['reply_image']['tmp_name'], $upload_dir.$new_filename)) {
                $reply_image_path = $upload_dir.$new_filename;
            }
        }
    }

    try {
        $conn->beginTransaction();

        // 1. บันทึกคอมเมนต์
        $stmt_reply = $conn->prepare(
            "INSERT INTO replies (post_id, emp_id, emp_name, emp_unit, reply_text, reply_image, created_at)
             VALUES (:post_id, :emp_id, :emp_name, :emp_unit, :reply_text, :reply_image, NOW())"
        );
        $stmt_reply->execute([
            ':post_id'     => $post_id,
            ':emp_id'      => $_SESSION['emp_id'],
            ':emp_name'    => $_SESSION['emp_name'] ?? 'พนักงาน',
            ':emp_unit'    => $_SESSION['emp_unit'] ?? '',
            ':reply_text'  => $reply_text,
            ':reply_image' => $reply_image_path,
        ]);

        // 2. แจ้งเตือนเจ้าของโพสต์
        $stmt_post = $conn->prepare("SELECT emp_id, target_unit FROM posts WHERE post_id = :post_id");
        $stmt_post->execute([':post_id' => $post_id]);
        $post_info = $stmt_post->fetch();

        if ($post_info && $post_info['emp_id'] != $_SESSION['emp_id']) {
            $noti_text = "คุณ ".($_SESSION['emp_name'] ?? 'พนักงานไร่')." ได้รายงานความคืบหน้าในโพสต์แจ้งปัญหาของคุณ";
            $conn->prepare(
                "INSERT INTO notifications (post_id, emp_id, target_unit, noti_text, is_read, created_at)
                 VALUES (:post_id, :emp_id, :target_unit, :noti_text, 0, NOW())"
            )->execute([
                ':post_id'     => $post_id,
                ':emp_id'      => $post_info['emp_id'],
                ':target_unit' => $post_info['target_unit'],
                ':noti_text'   => $noti_text,
            ]);
        }

        $new_reply_id = (int)$conn->lastInsertId();
        $conn->commit();

        // ── สร้าง HTML ส่งกลับ ──
        $ts           = date('H:i น.', time());
        $name_esc     = htmlspecialchars($_SESSION['emp_name'] ?? 'พนักงาน');
        $unit_esc     = htmlspecialchars($_SESSION['emp_unit'] ?? '');
        $text_esc     = nl2br(htmlspecialchars($reply_text));
        $text_raw     = htmlspecialchars($reply_text);
        $is_admin     = (($_SESSION['emp_level'] ?? 'u') === 'a');
        $avatar_color = $is_admin ? 'avatar-blue' : 'avatar-green';
        $avatar_init  = htmlspecialchars(mb_substr(trim($_SESSION['emp_name'] ?? 'พ'), 0, 2, 'UTF-8'));

        $img_html = '';
        if ($reply_image_path) {
            $img_esc  = htmlspecialchars($reply_image_path);
            $img_html = '<div><img src="'.$img_esc.'" class="chat-embedded-img" onclick="window.open(this.src,\'_blank\')" style="cursor:pointer;"></div>';
        }

        $edit_btn = '<button class="btn-edit-reply" onclick="enableEditMode('.$new_reply_id.')" title="แก้ไขข้อความ"><i class="fa-solid fa-pen-to-square"></i></button>';
        $del_btn  = $is_admin
            ? '<button class="btn-edit-reply" style="color:#e11d48;" onclick="deleteReply('.$new_reply_id.','.$post_id.')" title="ลบความคิดเห็นนี้"><i class="fa-solid fa-trash-can"></i></button>'
            : '';

        $html = '
<div class="chat-row" id="reply-row-'.$new_reply_id.'">
    <div class="chat-avatar '.$avatar_color.'">'.$avatar_init.'</div>
    <div class="chat-content-box">
        <div class="chat-info-header">
            <div class="chat-user-name">
                '.$name_esc.'
                <span class="chat-user-unit">(หน่วย'.$unit_esc.')</span>
            </div>
            <div class="chat-timestamp">
                '.$ts.'
                '.$edit_btn.'
                '.$del_btn.'
            </div>
        </div>
        <p class="chat-text" id="reply-text-'.$new_reply_id.'">'.$text_esc.'</p>
        <div id="edit-box-'.$new_reply_id.'" style="display:none; margin-top:8px;">
            <input type="text" id="edit-input-'.$new_reply_id.'" value="'.$text_raw.'" class="form-input" style="padding:6px; margin-bottom:5px; width:100%;">
            <div style="display:flex;align-items:center;gap:6px;margin-bottom:6px;flex-wrap:wrap;">
                <label style="display:inline-flex;align-items:center;gap:4px;font-size:.78rem;font-weight:700;color:#475569;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:4px 9px;cursor:pointer;">
                    <i class="fa-solid fa-camera" style="color:#e11d48;"></i> แนบรูป
                    <input type="file" id="edit-img-input-'.$new_reply_id.'" accept="image/*" style="display:none;"
                           onchange="previewEditImg(this,'.$new_reply_id.')">
                </label>
                <button type="button" style="font-size:.78rem;font-weight:700;color:#e11d48;background:#fff1f2;border:1px solid #fecaca;border-radius:6px;padding:4px 9px;cursor:pointer;font-family:Sarabun,sans-serif;"
                        onclick="markDeleteEditImg('.$new_reply_id.')">
                    <i class="fa-solid fa-trash-can"></i> ลบรูป
                </button>
                <span id="edit-img-name-'.$new_reply_id.'" style="font-size:.72rem;color:#64748b;"></span>
                <input type="hidden" id="edit-del-img-'.$new_reply_id.'" value="0">
            </div>
            <img id="edit-img-preview-'.$new_reply_id.'" src="" alt="preview"
                 style="display:none;max-width:180px;max-height:120px;object-fit:cover;border-radius:8px;margin-bottom:6px;border:1.5px solid #e2e8f0;">
            <div style="display:flex; gap:5px;">
                <button type="button" class="tab-item tab-success active" style="border:none;cursor:pointer;padding:4px 10px;font-family:Sarabun,sans-serif;" onclick="saveEdit('.$new_reply_id.')">บันทึก</button>
                <button type="button" class="tab-item tab-inactive" style="border:none;cursor:pointer;padding:4px 10px;font-family:Sarabun,sans-serif;" onclick="cancelEdit('.$new_reply_id.')">ยกเลิก</button>
            </div>
        </div>
        '.$img_html.'
    </div>
</div>';

        echo json_encode(["status" => "success", "message" => "บันทึกความคิดเห็นสำเร็จ", "html" => $html, "reply_id" => $new_reply_id]);

    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(["status" => "error", "message" => "เกิดข้อผิดพลาด: ".$e->getMessage()]);
    }
}
?>