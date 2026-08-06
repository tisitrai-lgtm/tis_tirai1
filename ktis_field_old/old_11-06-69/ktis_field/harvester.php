<?php
/**
 * harvester.php - บันทึกผลตรวจเช็กรถตัดอ้อย + สภาพแปลง + อัปโหลดรูปภาพ
 */
date_default_timezone_set('Asia/Bangkok');
require_once 'config.php';
session_start();

if(!isset($_SESSION["emp_id"])){
    header("location: login.php");
    exit;
}
if(($_SESSION['emp_level'] ?? 'u') === 'a'){
    header("location: harvester_admin.php");
    exit;
}

$message = "";
$status  = "";

if (isset($_SESSION['flash_msg'])) {
    $message = $_SESSION['flash_msg'];
    $status  = $_SESSION['flash_status'];
    unset($_SESSION['flash_msg']);
    unset($_SESSION['flash_status']);
}

// ── ฟังก์ชันอัปโหลด + บีบอัดรูป (resize 800px / quality 75%) ──
function uploadImage(string $field_name, string $base_dir): ?string {
    if (empty($_FILES[$field_name]['name'])) return null;

    $file    = $_FILES[$field_name];
    $allowed = ['image/jpeg','image/jpg','image/png','image/webp'];
    if (!in_array($file['type'], $allowed))        return null;
    if ($file['size'] > 10 * 1024 * 1024)          return null; // รับไม่เกิน 10MB ก่อนบีบ

    $date_folder = date('Y-m-d');
    $dir = rtrim($base_dir, '/') . '/im_user_check/' . $date_folder . '/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $filename = time() . '_' . mt_rand(1000,9999) . '.jpg'; // บีบแล้วบันทึกเป็น jpg เสมอ
    $dest     = $dir . $filename;

    // โหลดภาพตามชนิดไฟล์
    $src_img = match($file['type']) {
        'image/png'  => imagecreatefrompng($file['tmp_name']),
        'image/webp' => imagecreatefromwebp($file['tmp_name']),
        default      => imagecreatefromjpeg($file['tmp_name']),
    };
    if (!$src_img) return null;

    $orig_w = imagesx($src_img);
    $orig_h = imagesy($src_img);
    $max_w  = 800;

    // resize ถ้ากว้างเกิน 800px
    if ($orig_w > $max_w) {
        $new_w = $max_w;
        $new_h = (int)round($orig_h * $max_w / $orig_w);
        $dst_img = imagecreatetruecolor($new_w, $new_h);
        // รองรับ PNG transparency
        imagealphablending($dst_img, false);
        imagesavealpha($dst_img, true);
        imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $new_w, $new_h, $orig_w, $orig_h);
        imagedestroy($src_img);
    } else {
        $dst_img = $src_img;
    }

    $ok = imagejpeg($dst_img, $dest, 75); // quality 75%
    imagedestroy($dst_img);

    return $ok ? 'im_user_check/' . $date_folder . '/' . $filename : null;
}

// ── บันทึกฟอร์ม ──
if($_SERVER["REQUEST_METHOD"] == "POST") {
    $harvester_number   = trim($_POST['harvester_number']);
    $check_blade        = isset($_POST['check_blade'])       ? intval($_POST['check_blade'])       : 0;
    $check_top_cutter   = isset($_POST['check_top_cutter'])  ? intval($_POST['check_top_cutter'])  : 0;
    $check_chopper      = isset($_POST['check_chopper'])     ? intval($_POST['check_chopper'])     : 0;
    $check_base_cutter  = isset($_POST['check_base_cutter']) ? intval($_POST['check_base_cutter']) : 0;
    $check_extractor    = isset($_POST['check_extractor'])   ? intval($_POST['check_extractor'])   : 0;
    $field_condition    = trim($_POST['field_condition']    ?? '');
    $field_condition_etc= trim($_POST['field_condition_etc']?? '');
    $crop_year          = $_SESSION['crop_year'];
    $check_date         = date('Y-m-d');

    // หมายเหตุรายข้อ (note_<key>)
    $notes = [
        'check_blade'       => trim($_POST['note_check_blade']       ?? ''),
        'check_top_cutter'  => trim($_POST['note_check_top_cutter']  ?? ''),
        'check_chopper'     => trim($_POST['note_check_chopper']     ?? ''),
        'check_base_cutter' => trim($_POST['note_check_base_cutter'] ?? ''),
        'check_extractor'   => trim($_POST['note_check_extractor']   ?? ''),
    ];

    if(empty($harvester_number)) {
        $status  = "error";
        $message = "กรุณาระบุเบอร์รถตัดอ้อยก่อนบันทึก";
    } elseif(empty($field_condition)) {
        $status  = "error";
        $message = "กรุณาเลือกสภาพแปลงอ้อย";
    } else {
        try {
            $base_dir      = __DIR__;
            $img_harvester = uploadImage('img_harvester', $base_dir);
            $img_field     = uploadImage('img_field',     $base_dir);

            // บันทึก check_sessions
            $sql = "INSERT INTO check_sessions
                        (emp_id, harvester_number, crop_year,
                         field_condition, field_condition_etc,
                         img_harvester, img_field, checked_at)
                    VALUES
                        (:emp_id, :harvester_number, :crop_year,
                         :field_condition, :field_condition_etc,
                         :img_harvester, :img_field, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':emp_id'              => $_SESSION['emp_id'],
                ':harvester_number'    => $harvester_number,
                ':crop_year'           => $crop_year,
                ':field_condition'     => $field_condition,
                ':field_condition_etc' => $field_condition_etc ?: null,
                ':img_harvester'       => $img_harvester,
                ':img_field'           => $img_field,
            ]);
            $session_id = $conn->lastInsertId();

            // บันทึก check_results พร้อม note
            $item_map = [
                'check_blade'       => 1,
                'check_top_cutter'  => 2,
                'check_chopper'     => 3,
                'check_base_cutter' => 4,
                'check_extractor'   => 5,
            ];
            $cut_items = [
                'check_blade'       => $check_blade,
                'check_top_cutter'  => $check_top_cutter,
                'check_chopper'     => $check_chopper,
                'check_base_cutter' => $check_base_cutter,
                'check_extractor'   => $check_extractor,
            ];

            $stmt_r = $conn->prepare(
                "INSERT INTO check_results (session_id, item_id, pass, note)
                 VALUES (:sid, :iid, :pass, :note)"
            );
            foreach($cut_items as $key => $val) {
                $note_val = (!$val && !empty($notes[$key])) ? $notes[$key] : null;
                $stmt_r->execute([
                    ':sid'  => $session_id,
                    ':iid'  => $item_map[$key],
                    ':pass' => $val,
                    ':note' => $note_val,
                ]);
            }

            // backward compat: harvester_checks เดิม
            $sql_old = "INSERT INTO harvester_checks
                            (emp_id, harvester_number, check_blade, check_top_cutter,
                             check_chopper, check_base_cutter, check_extractor, crop_year, check_date)
                        VALUES
                            (:emp_id, :harvester_number, :check_blade, :check_top_cutter,
                             :check_chopper, :check_base_cutter, :check_extractor, :crop_year, :check_date)";
            $stmt_old = $conn->prepare($sql_old);
            $stmt_old->execute([
                ':emp_id'            => $_SESSION['emp_id'],
                ':harvester_number'  => $harvester_number,
                ':check_blade'       => $check_blade,
                ':check_top_cutter'  => $check_top_cutter,
                ':check_chopper'     => $check_chopper,
                ':check_base_cutter' => $check_base_cutter,
                ':check_extractor'   => $check_extractor,
                ':crop_year'         => $crop_year,
                ':check_date'        => $check_date,
            ]);

            $_SESSION['flash_status'] = "success";
            $_SESSION['flash_msg']    = "บันทึกรถตัดเบอร์ <strong>" . htmlspecialchars($harvester_number) . "</strong> เรียบร้อยแล้ว";
            header("Location: harvester.php");
            exit;

        } catch(Exception $e) {
            $status  = "error";
            $message = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }
}

// ── ดึงประวัติ ──
$history = [];
try {
    $stmt_h = $conn->prepare(
        "SELECT cs.*, e.emp_name, e.emp_unit,
                SUM(cr.pass) AS pass_count,
                COUNT(cr.result_id) AS total_items
         FROM check_sessions cs
         JOIN employee e ON cs.emp_id = e.emp_id
         LEFT JOIN check_results cr ON cs.session_id = cr.session_id
         WHERE cs.crop_year = :crop_year
         GROUP BY cs.session_id
         ORDER BY cs.checked_at DESC
         LIMIT 30"
    );
    $stmt_h->execute([':crop_year' => $_SESSION['crop_year']]);
    $history = $stmt_h->fetchAll();
} catch(Exception $e) {}

// ── ดึงรายการสภาพแปลง ──
$field_items = [];
try {
    $stmt_fi = $conn->query("SELECT * FROM check_items_field ORDER BY item_id ASC");
    $field_items = $stmt_fi->fetchAll();
} catch(Exception $e) {}

function badge(int $val, string $note = ''): string {
    if ($val) return '<span class="chk-ok"><i class="fa-solid fa-check"></i> สมบูรณ์</span>';
    $out = '<span class="chk-fail"><i class="fa-solid fa-xmark"></i> ไม่สมบูรณ์</span>';
    if ($note) $out .= '<div class="fail-note"><i class="fa-solid fa-pen-to-square"></i> ' . htmlspecialchars($note) . '</div>';
    return $out;
}
function dot(int $val): string {
    return $val
        ? '<span class="dot dot-ok" title="สมบูรณ์"></span>'
        : '<span class="dot dot-fail" title="ไม่สมบูรณ์"></span>';
}

$check_items = [
    'check_blade'       => ['label'=>'ใบพัดสับท่อน',   'icon'=>'fa-fan'],
    'check_top_cutter'  => ['label'=>'ตัดยอดอ้อย',     'icon'=>'fa-scissors'],
    'check_chopper'     => ['label'=>'สับย่อย/ตัดต่อ', 'icon'=>'fa-circle-nodes'],
    'check_base_cutter' => ['label'=>'ตัดโคนอ้อย',     'icon'=>'fa-arrow-down-to-line'],
    'check_extractor'   => ['label'=>'พัดลมดูดใบ',     'icon'=>'fa-wind'],
];

include 'includes/nav_u_header.php';

$thai_months = ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน',
                'กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
$now_d  = (int)date('d');
$now_m  = (int)date('m');
$now_y  = (int)date('Y') + 543;
$thai_date_now = $now_d . ' ' . $thai_months[$now_m] . ' ' . $now_y;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตรวจเช็กรถตัดอ้อย - KTIS SMART FIELD</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Sarabun', sans-serif; background: #f1f5f9; margin: 0; }
        .content-wrapper { flex: 1 0 auto; }
        .page-wrap { max-width: 760px; margin: 24px auto; padding: 0 14px 60px; }

        .page-header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
        .page-header-icon { width: 46px; height: 46px; background: linear-gradient(135deg,#10b981,#059669); border-radius: 11px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .page-header-icon i { color: white; font-size: 1.3rem; }
        .page-header-title { font-size: 1.15rem; font-weight: 700; color: #1e293b; margin-bottom: 2px; }
        .page-header-sub   { font-size: 0.8rem; color: #64748b; }

        .alert { display: flex; align-items: flex-start; gap: 10px; padding: 13px 16px; border-radius: 9px; margin-bottom: 18px; font-weight: 600; font-size: 0.9rem; }
        .alert-success { background: #d1fae5; border: 1px solid #a7f3d0; color: #065f46; }
        .alert-error   { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; }
        .alert i { margin-top: 2px; flex-shrink: 0; }

        .form-card { background: white; border-radius: 14px; border: 0.5px solid #e2e8f0; overflow: hidden; margin-bottom: 28px; }
        .form-card-header { background: #1e293b; padding: 14px 20px; display: flex; align-items: center; gap: 10px; border-bottom: 3px solid #10b981; }
        .form-card-header i    { color: #10b981; font-size: 1rem; }
        .form-card-header span { color: #f8fafc; font-weight: 700; font-size: 0.95rem; }
        .form-card-body { padding: 20px; }

        .meta-bar { display: flex; gap: 10px; flex-wrap: wrap; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 9px; padding: 11px 14px; margin-bottom: 20px; }
        .meta-chip { display: inline-flex; align-items: center; gap: 6px; font-size: 0.82rem; font-weight: 600; color: #475569; }
        .meta-chip i { color: #94a3b8; font-size: 0.85rem; }
        .meta-sep { color: #e2e8f0; }

        .field-label { display: block; font-weight: 700; font-size: 0.83rem; color: #374151; margin-bottom: 7px; }
        .field-label .req { color: #e11d48; }
        .form-input { width: 100%; padding: 11px 13px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; font-family: 'Sarabun', sans-serif; background: #f8fafc; color: #1e293b; outline: none; transition: border-color .15s; }
        .form-input:focus { border-color: #10b981; background: white; }
        select.form-input { cursor: pointer; }

        .field-etc-wrap { margin-top: 10px; display: none; }
        .field-etc-wrap.show { display: block; }

        .section-label { font-weight: 700; font-size: 0.85rem; color: #1e293b; display: flex; align-items: center; gap: 7px; margin: 18px 0 12px; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9; }
        .section-label i { color: #10b981; }

        /* ── check row + หมายเหตุ ── */
        .check-row { padding: 11px 0; border-bottom: 1px solid #f8fafc; }
        .check-row:last-of-type { border-bottom: none; }
        .check-row-top { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .check-row-label { display: flex; align-items: center; gap: 9px; font-weight: 600; color: #334155; font-size: 0.92rem; }
        .check-row-label .icon-wrap { width: 30px; height: 30px; background: #f0fdf4; border-radius: 7px; display: flex; align-items: center; justify-content: center; }
        .check-row-label i { color: #10b981; font-size: 0.85rem; }
        .radio-group { display: flex; gap: 8px; }
        .radio-btn { display: none; }
        .radio-label { display: inline-flex; align-items: center; gap: 5px; padding: 6px 13px; border-radius: 7px; cursor: pointer; font-weight: 700; font-size: 0.82rem; border: 1.5px solid #e2e8f0; transition: all .15s; }
        .radio-btn[value="1"]:checked + .radio-label { background: #10b981; color: white; border-color: #10b981; }
        .radio-btn[value="0"]:checked + .radio-label { background: #e11d48; color: white; border-color: #e11d48; }
        .radio-label.ok  { color: #059669; background: #f0fdf4; }
        .radio-label.bad { color: #e11d48; background: #fef2f2; }

        /* หมายเหตุเมื่อไม่สมบูรณ์ */
        .note-wrap { display: none; margin-top: 8px; }
        .note-wrap.show { display: block; }
        .note-input {
            width: 100%; padding: 8px 11px; border: 1.5px solid #fecaca;
            border-radius: 7px; font-size: 0.85rem; font-family: 'Sarabun', sans-serif;
            background: #fff5f5; color: #1e293b; outline: none; resize: vertical; min-height: 60px;
            transition: border-color .15s;
        }
        .note-input:focus { border-color: #e11d48; background: white; }
        .note-label { font-size: 0.78rem; color: #e11d48; font-weight: 700; margin-bottom: 4px; display: flex; align-items: center; gap: 5px; }

        /* ── Upload ── */
        .upload-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 4px; }
        .upload-box { border: 2px dashed #e2e8f0; border-radius: 10px; padding: 16px 12px; text-align: center; cursor: pointer; transition: border-color .2s, background .2s; background: #f8fafc; position: relative; }
        .upload-box:hover { border-color: #10b981; background: #f0fdf4; }
        .upload-box input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
        .upload-box .up-icon { font-size: 1.6rem; color: #94a3b8; margin-bottom: 6px; }
        .upload-box .up-title { font-weight: 700; font-size: 0.82rem; color: #334155; margin-bottom: 2px; }
        .upload-box .up-hint  { font-size: 0.72rem; color: #94a3b8; }
        .upload-box .up-preview { display: none; }
        .upload-box.has-file { border-color: #10b981; background: #f0fdf4; }
        .upload-box.has-file .up-icon { color: #10b981; }
        .upload-box.has-file .up-preview { display: block; margin-top: 8px; }
        .upload-box.has-file .up-preview img { width: 100%; max-height: 100px; object-fit: cover; border-radius: 6px; border: 1px solid #a7f3d0; }
        .upload-box.has-file .up-hint { color: #059669; font-weight: 600; }

        .btn-submit { width: 100%; padding: 13px; background: #10b981; color: white; border: none; border-radius: 9px; font-size: 1rem; font-weight: 700; font-family: 'Sarabun', sans-serif; cursor: pointer; margin-top: 20px; display: flex; align-items: center; justify-content: center; gap: 7px; transition: background .15s; }
        .btn-submit:hover { background: #059669; }

        /* ── History ── */
        .history-card { background: white; border-radius: 14px; border: 0.5px solid #e2e8f0; overflow: hidden; }
        .history-header { background: #1e293b; padding: 13px 18px; display: flex; align-items: center; justify-content: space-between; border-bottom: 3px solid #64748b; }
        .history-header-left { display: flex; align-items: center; gap: 8px; }
        .history-header-left i    { color: #94a3b8; }
        .history-header-left span { color: #f8fafc; font-weight: 700; font-size: 0.92rem; }
        .history-count { background: rgba(255,255,255,.1); color: #cbd5e1; font-size: 0.75rem; font-weight: 700; padding: 2px 9px; border-radius: 10px; }

        .tbl-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 620px; }
        thead th { background: #f8fafc; color: #475569; font-size: 0.78rem; font-weight: 700; padding: 10px 14px; text-align: left; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
        tbody td { padding: 10px 14px; border-bottom: 1px solid #f8fafc; font-size: 0.85rem; color: #334155; vertical-align: middle; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #f8fafc; }
        .harvester-num { font-weight: 700; color: #1e293b; font-family: monospace; }
        .emp-cell { display: flex; flex-direction: column; }
        .emp-cell .emp-name { font-weight: 700; color: #1e293b; font-size: 0.83rem; }
        .emp-cell .emp-unit { font-size: 0.73rem; color: #94a3b8; }
        .chk-ok   { background: #d1fae5; color: #065f46; font-size: 0.72rem; font-weight: 700; padding: 2px 7px; border-radius: 4px; display: inline-flex; align-items: center; gap: 3px; white-space: nowrap; }
        .chk-fail { background: #fee2e2; color: #991b1b; font-size: 0.72rem; font-weight: 700; padding: 2px 7px; border-radius: 4px; display: inline-flex; align-items: center; gap: 3px; white-space: nowrap; }
        .fail-note { font-size: 0.72rem; color: #92400e; background: #fff7ed; border-left: 2px solid #f59e0b; padding: 2px 6px; margin-top: 3px; border-radius: 0 4px 4px 0; }
        .dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; }
        .dot-ok   { background: #10b981; }
        .dot-fail { background: #e11d48; }
        .field-badge { display: inline-flex; align-items: center; gap: 4px; background: #f0fdf4; color: #065f46; font-size: 0.72rem; font-weight: 700; padding: 2px 8px; border-radius: 4px; border: 1px solid #a7f3d0; }
        .field-badge.bad { background: #fff7ed; color: #92400e; border-color: #fcd34d; }
        .img-thumb { width: 36px; height: 36px; object-fit: cover; border-radius: 5px; border: 1px solid #e2e8f0; cursor: pointer; transition: transform .15s; }
        .img-thumb:hover { transform: scale(1.1); }
        .img-thumbs { display: flex; gap: 4px; }
        .lightbox { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.85); z-index: 9999; align-items: center; justify-content: center; }
        .lightbox.show { display: flex; }
        .lightbox img { max-width: 90vw; max-height: 88vh; border-radius: 10px; box-shadow: 0 8px 32px rgba(0,0,0,.5); }
        .lightbox-close { position: absolute; top: 18px; right: 22px; color: white; font-size: 1.8rem; cursor: pointer; background: none; border: none; }

        .mobile-history { display: none; }
        .hist-card { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; }
        .hist-card:last-child { border-bottom: none; }
        .hist-card-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 8px; gap: 8px; }
        .hist-num { font-weight: 700; color: #1e293b; font-size: 0.95rem; }
        .hist-meta { font-size: 0.75rem; color: #94a3b8; margin-top: 2px; }
        .hist-date-badge { background: #f1f5f9; color: #475569; font-size: 0.72rem; font-weight: 700; padding: 3px 9px; border-radius: 12px; white-space: nowrap; flex-shrink: 0; }
        .empty-hist { text-align: center; padding: 40px 20px; color: #94a3b8; }
        .empty-hist i { font-size: 2rem; display: block; margin-bottom: 8px; }

        @media (max-width: 640px) {
            .tbl-wrap table { display: none; }
            .mobile-history { display: block; }
            .check-row-top { flex-direction: column; align-items: flex-start; gap: 8px; }
            .radio-group { width: 100%; }
            .radio-label { flex: 1; justify-content: center; }
            .upload-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="content-wrapper">
<div class="page-wrap">

    <div class="page-header">
        <div class="page-header-icon"><i class="fa-solid fa-tractor"></i></div>
        <div>
            <div class="page-header-title">บันทึกตรวจเช็กรถตัดอ้อยประจำวัน</div>
            <div class="page-header-sub">ปีการผลิต <?php echo htmlspecialchars($_SESSION['crop_year']); ?> · วันที่ <?php echo $thai_date_now; ?></div>
        </div>
    </div>

    <?php if(!empty($message)): ?>
    <div class="alert <?php echo $status === 'success' ? 'alert-success' : 'alert-error'; ?>">
        <i class="fa-solid <?php echo $status === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
        <span><?php echo $message; ?></span>
    </div>
    <?php endif; ?>

    <div class="form-card">
        <div class="form-card-header">
            <i class="fa-solid fa-clipboard-list"></i>
            <span>แบบฟอร์มบันทึกผลการตรวจสอบ</span>
        </div>
        <div class="form-card-body">

            <div class="meta-bar">
                <div class="meta-chip"><i class="fa-solid fa-user"></i><span><?php echo htmlspecialchars($_SESSION['emp_name']); ?></span></div>
                <span class="meta-sep">|</span>
                <div class="meta-chip"><i class="fa-solid fa-location-dot"></i><span><?php echo htmlspecialchars($_SESSION['emp_unit']); ?></span></div>
                <span class="meta-sep">|</span>
                <div class="meta-chip"><i class="fa-solid fa-calendar-day"></i><span><?php echo $thai_date_now; ?></span></div>
                <span class="meta-sep">|</span>
                <div class="meta-chip"><i class="fa-solid fa-clock"></i><span id="live-time"><?php echo date('H:i'); ?> น.</span></div>
            </div>

            <form action="harvester.php" method="POST" enctype="multipart/form-data">

                <label class="field-label">เบอร์รถตัดอ้อย <span class="req">*</span></label>
                <input type="text" name="harvester_number" class="form-input"
                       placeholder="เช่น MC-01, คันที่ 25" required autofocus>

                <div class="section-label" style="margin-top:20px;">
                    <i class="fa-solid fa-leaf"></i> สภาพแปลงอ้อยขณะรถตัดทำงาน
                </div>

                <label class="field-label">เลือกสภาพแปลง <span class="req">*</span></label>
                <select name="field_condition" id="field_condition" class="form-input" required onchange="toggleEtc(this.value)">
                    <option value="">-- กรุณาเลือก --</option>
                    <?php foreach($field_items as $fi): ?>
                        <option value="<?php echo htmlspecialchars($fi['item_name_field']); ?>">
                            <?php echo htmlspecialchars($fi['item_name_field']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="field-etc-wrap" id="etc_wrap">
                    <label class="field-label" style="margin-top:10px;">ระบุรายละเอียดเพิ่มเติม <span class="req">*</span></label>
                    <input type="text" name="field_condition_etc" id="field_condition_etc"
                           class="form-input" placeholder="โปรดระบุสภาพแปลง...">
                </div>

                <!-- ── รายการตรวจสอบ ── -->
                <div class="section-label">
                    <i class="fa-solid fa-clipboard-check"></i>
                    รายการตรวจสอบความสมบูรณ์ภาคสนาม
                </div>

                <?php foreach($check_items as $key => $item): ?>
                <div class="check-row">
                    <div class="check-row-top">
                        <div class="check-row-label">
                            <div class="icon-wrap"><i class="fa-solid <?php echo $item['icon']; ?>"></i></div>
                            <?php echo $item['label']; ?>
                        </div>
                        <div class="radio-group">
                            <input type="radio" class="radio-btn" name="<?php echo $key; ?>"
                                   id="<?php echo $key; ?>_ok" value="1" checked
                                   onchange="toggleNote('<?php echo $key; ?>', 1)">
                            <label class="radio-label ok" for="<?php echo $key; ?>_ok">
                                <i class="fa-solid fa-check"></i> สมบูรณ์
                            </label>
                            <input type="radio" class="radio-btn" name="<?php echo $key; ?>"
                                   id="<?php echo $key; ?>_fail" value="0"
                                   onchange="toggleNote('<?php echo $key; ?>', 0)">
                            <label class="radio-label bad" for="<?php echo $key; ?>_fail">
                                <i class="fa-solid fa-xmark"></i> ไม่สมบูรณ์
                            </label>
                        </div>
                    </div>
                    <!-- หมายเหตุ: โผล่เมื่อเลือก "ไม่สมบูรณ์" -->
                    <div class="note-wrap" id="note_wrap_<?php echo $key; ?>">
                        <div class="note-label">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            ระบุสาเหตุที่ไม่สมบูรณ์
                        </div>
                        <textarea class="note-input" name="note_<?php echo $key; ?>"
                                  placeholder="เช่น ใบพัดสึกหรอ, ต้องเปลี่ยนอะไหล่..."></textarea>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- ── อัปโหลดรูปภาพ ── -->
                <div class="section-label">
                    <i class="fa-solid fa-camera"></i> ภาพประกอบ (ไม่บังคับ)
                </div>
                <div style="font-size:0.75rem; color:#64748b; margin-bottom:10px;">
                    <i class="fa-solid fa-compress"></i> รูปจะถูกบีบอัดอัตโนมัติ (800px / 75%) เพื่อประหยัดพื้นที่
                </div>

                <div class="upload-grid">
                    <div class="upload-box" id="box_harvester">
                        <input type="file" name="img_harvester" id="img_harvester"
                               accept="image/jpeg,image/png,image/webp"
                               onchange="previewImg(this,'box_harvester','prev_harvester')">
                        <div class="up-icon"><i class="fa-solid fa-tractor"></i></div>
                        <div class="up-title">รูปรถตัด</div>
                        <div class="up-hint">JPG / PNG / WEBP ไม่เกิน 10MB</div>
                        <div class="up-preview" id="prev_harvester"><img src="" alt="preview"></div>
                    </div>
                    <div class="upload-box" id="box_field">
                        <input type="file" name="img_field" id="img_field"
                               accept="image/jpeg,image/png,image/webp"
                               onchange="previewImg(this,'box_field','prev_field')">
                        <div class="up-icon"><i class="fa-solid fa-seedling"></i></div>
                        <div class="up-title">รูปแปลงอ้อย</div>
                        <div class="up-hint">JPG / PNG / WEBP ไม่เกิน 10MB</div>
                        <div class="up-preview" id="prev_field"><img src="" alt="preview"></div>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-floppy-disk"></i> บันทึกผลการตรวจสอบ
                </button>
            </form>
        </div>
    </div>

    <!-- History -->
    <div class="history-card">
        <div class="history-header">
            <div class="history-header-left">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span>ประวัติการบันทึก</span>
            </div>
            <span class="history-count"><?php echo count($history); ?> รายการล่าสุด · ปี <?php echo htmlspecialchars($_SESSION['crop_year']); ?></span>
        </div>

        <?php if(empty($history)): ?>
            <div class="empty-hist">
                <i class="fa-solid fa-clipboard-list"></i>
                ยังไม่มีประวัติการบันทึกในปีการผลิตนี้
            </div>
        <?php else: ?>

        <div class="tbl-wrap">
            <table>
                <thead>
                    <tr>
                        <th>วันที่/เวลา</th>
                        <th>เบอร์รถตัด</th>
                        <th>ผู้บันทึก</th>
                        <th>สภาพแปลง</th>
                        <th>ชุดตัด</th>
                        <th>ภาพ</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($history as $h):
                    $d  = (int)date('d', strtotime($h['checked_at']));
                    $mo = (int)date('m', strtotime($h['checked_at']));
                    $yr = (int)date('Y', strtotime($h['checked_at'])) + 543;
                    $date_str    = $d . ' ' . $thai_months[$mo] . ' ' . $yr;
                    $time_str    = date('H:i น.', strtotime($h['checked_at']));
                    $pass_count  = (int)($h['pass_count']  ?? 0);
                    $total_items = (int)($h['total_items'] ?? 0);
                    $all_ok      = ($total_items > 0 && $pass_count == $total_items);
                    $is_bad_field = !in_array($h['field_condition'] ?? '', ['ปกติ', '']);
                ?>
                <tr>
                    <td>
                        <div style="font-weight:700; color:#1e293b; font-size:0.82rem;"><?php echo $date_str; ?></div>
                        <div style="font-size:0.75rem; color:#94a3b8;"><?php echo $time_str; ?></div>
                    </td>
                    <td>
                        <span class="harvester-num"><?php echo htmlspecialchars($h['harvester_number']); ?></span>
                        <?php if($total_items > 0 && !$all_ok): ?>
                            <div style="font-size:0.7rem; color:#e11d48; margin-top:2px; font-weight:700;">
                                <i class="fa-solid fa-triangle-exclamation"></i> พบข้อบกพร่อง
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="emp-cell">
                            <span class="emp-name"><?php echo htmlspecialchars($h['emp_name']); ?></span>
                            <span class="emp-unit"><?php echo htmlspecialchars($h['emp_unit']); ?></span>
                        </div>
                    </td>
                    <td>
                        <?php if(!empty($h['field_condition'])): ?>
                            <span class="field-badge <?php echo $is_bad_field ? 'bad' : ''; ?>">
                                <i class="fa-solid <?php echo $is_bad_field ? 'fa-triangle-exclamation' : 'fa-check'; ?>"></i>
                                <?php echo htmlspecialchars($h['field_condition']); ?>
                            </span>
                            <?php if(!empty($h['field_condition_etc'])): ?>
                                <div style="font-size:0.72rem; color:#64748b; margin-top:3px;"><?php echo htmlspecialchars($h['field_condition_etc']); ?></div>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color:#94a3b8; font-size:0.78rem;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($total_items > 0): ?>
                            <?php echo $all_ok
                                ? '<span class="chk-ok"><i class="fa-solid fa-check"></i> ผ่านทั้งหมด</span>'
                                : '<span class="chk-fail"><i class="fa-solid fa-xmark"></i> ' . ($total_items - $pass_count) . ' รายการ</span>'; ?>
                        <?php else: ?>
                            <span style="color:#94a3b8; font-size:0.78rem;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="img-thumbs">
                            <?php if(!empty($h['img_harvester'])): ?>
                                <img class="img-thumb" src="<?php echo htmlspecialchars($h['img_harvester']); ?>" onclick="openLightbox(this.src)" title="รูปรถตัด">
                            <?php endif; ?>
                            <?php if(!empty($h['img_field'])): ?>
                                <img class="img-thumb" src="<?php echo htmlspecialchars($h['img_field']); ?>" onclick="openLightbox(this.src)" title="รูปแปลงอ้อย">
                            <?php endif; ?>
                            <?php if(empty($h['img_harvester']) && empty($h['img_field'])): ?>
                                <span style="color:#94a3b8; font-size:0.78rem;">-</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile cards -->
        <div class="mobile-history">
            <?php foreach($history as $h):
                $d  = (int)date('d', strtotime($h['checked_at']));
                $mo = (int)date('m', strtotime($h['checked_at']));
                $yr = (int)date('Y', strtotime($h['checked_at'])) + 543;
                $date_str    = $d . ' ' . $thai_months[$mo] . ' ' . $yr;
                $time_str    = date('H:i น.', strtotime($h['checked_at']));
                $pass_count  = (int)($h['pass_count']  ?? 0);
                $total_items = (int)($h['total_items'] ?? 0);
                $all_ok      = ($total_items > 0 && $pass_count == $total_items);
                $is_bad_field = !in_array($h['field_condition'] ?? '', ['ปกติ', '']);
            ?>
            <div class="hist-card">
                <div class="hist-card-top">
                    <div>
                        <div class="hist-num">
                            <i class="fa-solid fa-tractor" style="color:#10b981; margin-right:4px;"></i>
                            <?php echo htmlspecialchars($h['harvester_number']); ?>
                            <?php if($total_items > 0 && !$all_ok): ?>
                                <span style="font-size:0.72rem; color:#e11d48; font-weight:700; margin-left:4px;">
                                    <i class="fa-solid fa-triangle-exclamation"></i> พบข้อบกพร่อง
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="hist-meta">
                            <i class="fa-solid fa-user" style="margin-right:3px;"></i><?php echo htmlspecialchars($h['emp_name']); ?>
                            · <?php echo htmlspecialchars($h['emp_unit']); ?>
                        </div>
                        <?php if(!empty($h['field_condition'])): ?>
                        <div style="margin-top:5px;">
                            <span class="field-badge <?php echo $is_bad_field ? 'bad' : ''; ?>">
                                <i class="fa-solid <?php echo $is_bad_field ? 'fa-triangle-exclamation' : 'fa-leaf'; ?>"></i>
                                <?php echo htmlspecialchars($h['field_condition']); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="hist-date-badge">
                        <i class="fa-solid fa-calendar" style="margin-right:3px;"></i>
                        <?php echo $date_str; ?><br>
                        <span style="color:#94a3b8;"><?php echo $time_str; ?></span>
                    </div>
                </div>
                <?php if(!empty($h['img_harvester']) || !empty($h['img_field'])): ?>
                <div class="img-thumbs" style="margin-top:6px;">
                    <?php if(!empty($h['img_harvester'])): ?>
                        <img class="img-thumb" src="<?php echo htmlspecialchars($h['img_harvester']); ?>" onclick="openLightbox(this.src)" title="รูปรถตัด">
                    <?php endif; ?>
                    <?php if(!empty($h['img_field'])): ?>
                        <img class="img-thumb" src="<?php echo htmlspecialchars($h['img_field']); ?>" onclick="openLightbox(this.src)" title="รูปแปลงอ้อย">
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <?php endif; ?>
    </div>

</div>
</div>

<div class="lightbox" id="lightbox" onclick="closeLightbox()">
    <button class="lightbox-close" onclick="closeLightbox()"><i class="fa-solid fa-xmark"></i></button>
    <img src="" id="lightbox-img" alt="ภาพขยาย">
</div>

<?php include 'includes/nav_u_footer.php'; ?>

<script>
// นาฬิกา live (ใช้เวลาจากเครื่อง server ผ่าน PHP แล้ว update ด้วย JS)
function updateTime() {
    const now = new Date();
    const h = String(now.getHours()).padStart(2,'0');
    const m = String(now.getMinutes()).padStart(2,'0');
    const el = document.getElementById('live-time');
    if(el) el.textContent = h + ':' + m + ' น.';
}
updateTime();
setInterval(updateTime, 10000);

// แสดง/ซ่อน ช่องอื่นๆ
function toggleEtc(val) {
    const wrap = document.getElementById('etc_wrap');
    const inp  = document.getElementById('field_condition_etc');
    if(val === 'อื่นๆ') {
        wrap.classList.add('show');
        inp.required = true;
    } else {
        wrap.classList.remove('show');
        inp.required = false;
        inp.value = '';
    }
}

// แสดง/ซ่อน หมายเหตุเมื่อเลือกไม่สมบูรณ์
function toggleNote(key, val) {
    const wrap = document.getElementById('note_wrap_' + key);
    if (!wrap) return;
    if (val == 0) {
        wrap.classList.add('show');
    } else {
        wrap.classList.remove('show');
        const ta = wrap.querySelector('textarea');
        if (ta) ta.value = '';
    }
}

// Preview รูป
function previewImg(input, boxId, prevId) {
    const box  = document.getElementById(boxId);
    const prev = document.getElementById(prevId);
    if(input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            prev.querySelector('img').src = e.target.result;
            box.classList.add('has-file');
            const sizeKB = Math.round(input.files[0].size / 1024);
            box.querySelector('.up-hint').textContent = input.files[0].name + ' (' + sizeKB + ' KB)';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Lightbox
function openLightbox(src) {
    document.getElementById('lightbox-img').src = src;
    document.getElementById('lightbox').classList.add('show');
}
function closeLightbox() {
    document.getElementById('lightbox').classList.remove('show');
}
document.addEventListener('keydown', e => { if(e.key === 'Escape') closeLightbox(); });
</script>
</body>
</html>