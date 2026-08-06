<?php
/**
 * harvester.php — ตรวจเช็กรถตัดอ้อย (2 ขั้นตอน: กรอกเบอร์รถ -> กรอกรายการตรวจ)
 */
date_default_timezone_set('Asia/Bangkok');
require_once 'config.php';
session_start();

if(!isset($_SESSION["emp_id"])){ header("location: login.php"); exit; }
if(($_SESSION['emp_level'] ?? 'u') === 'a'){ header("location: harvester_admin.php"); exit; }

$message = "";
$status  = "";
if(isset($_SESSION['flash_msg'])){
    $message = $_SESSION['flash_msg'];
    $status  = $_SESSION['flash_status'];
    unset($_SESSION['flash_msg'], $_SESSION['flash_status']);
}

// ── บีบอัดรูป 800px / 75% ──
function uploadImage(string $field_name, string $base_dir): ?string {
    if (empty($_FILES[$field_name]['name'])) return null;
    $file    = $_FILES[$field_name];
    $allowed = ['image/jpeg','image/jpg','image/png','image/webp'];
    if (!in_array($file['type'], $allowed)) return null;
    if ($file['size'] > 10*1024*1024) return null;

    $date_folder = date('Y-m-d');
    $dir = rtrim($base_dir,'/').'/im_user_check/'.$date_folder.'/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $src = match($file['type']){
        'image/png'  => imagecreatefrompng($file['tmp_name']),
        'image/webp' => imagecreatefromwebp($file['tmp_name']),
        default      => imagecreatefromjpeg($file['tmp_name']),
    };
    if(!$src) return null;

    $ow = imagesx($src); $oh = imagesy($src);
    if($ow > 800){
        $nw = 800; $nh = (int)round($oh * 800 / $ow);
        $dst = imagecreatetruecolor($nw,$nh);
        imagealphablending($dst,false); imagesavealpha($dst,true);
        imagecopyresampled($dst,$src,0,0,0,0,$nw,$nh,$ow,$oh);
        imagedestroy($src); $src=$dst;
    }
    $fname = time().'_'.mt_rand(1000,9999).'.jpg';
    $ok = imagejpeg($src, $dir.$fname, 75);
    imagedestroy($src);
    return $ok ? 'im_user_check/'.$date_folder.'/'.$fname : null;
}

// ── STEP 1: บันทึกเบอร์รถตัดลง session ──
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '')==='set_truck'){
    $hv = trim($_POST['harvester_number'] ?? '');
    if(empty($hv)){
        $_SESSION['flash_status']='error';
        $_SESSION['flash_msg']='กรุณาระบุเบอร์รถตัดอ้อยก่อนเริ่มตรวจ';
    } else {
        $_SESSION['hv_truck_number'] = $hv;
    }
    header("Location: harvester.php"); exit;
}

// ── เปลี่ยนเบอร์รถ (กลับไป step 1) ──
if(isset($_GET['reset_truck'])){
    unset($_SESSION['hv_truck_number']);
    header("Location: harvester.php"); exit;
}

// ── STEP 2: บันทึกผลตรวจทั้งหมด ──
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '')==='submit_check'){
    $harvester_number    = $_SESSION['hv_truck_number'] ?? '';
    $field_condition     = trim($_POST['field_condition']     ?? '');
    $field_condition_etc = trim($_POST['field_condition_etc'] ?? '');
    $crop_year = $_SESSION['crop_year'];

    if(empty($harvester_number)){
        $_SESSION['flash_status']='error';
        $_SESSION['flash_msg']='ไม่พบเบอร์รถตัด กรุณาเริ่มใหม่';
        header("Location: harvester.php"); exit;
    }
    if(empty($field_condition)){
        $status='error'; $message='กรุณาเลือกสภาพแปลงอ้อย';
    } else {
        try {
            $base_dir      = __DIR__;
            $img_harvester = uploadImage('img_harvester', $base_dir);
            $img_field     = uploadImage('img_field',     $base_dir);

            $stmt = $conn->prepare(
                "INSERT INTO check_sessions
                    (emp_id, harvester_number, crop_year,
                     field_condition, field_condition_etc,
                     img_harvester, img_field, checked_at)
                 VALUES
                    (:emp_id, :hn, :cy, :fc, :fce, :imh, :imf, NOW())"
            );
            $stmt->execute([
                ':emp_id'=>$_SESSION['emp_id'], ':hn'=>$harvester_number, ':cy'=>$crop_year,
                ':fc'=>$field_condition, ':fce'=>$field_condition_etc?:null,
                ':imh'=>$img_harvester, ':imf'=>$img_field,
            ]);
            $session_id = $conn->lastInsertId();

            $cut_items_all = $conn->query("SELECT item_id FROM check_items_cut ORDER BY section_no ASC, item_id ASC")->fetchAll();
            $stmt_r = $conn->prepare(
                "INSERT INTO check_results (session_id, item_id, pass, note) VALUES (:sid,:iid,:pass,:note)"
            );
            foreach($cut_items_all as $ci){
                $iid  = $ci['item_id'];
                $pass = isset($_POST["item_$iid"]) ? (int)$_POST["item_$iid"] : 1;
                $note = (!$pass) ? trim($_POST["note_item_$iid"] ?? '') : '';
                $stmt_r->execute([':sid'=>$session_id, ':iid'=>$iid, ':pass'=>$pass, ':note'=>$note?:null]);
            }

            unset($_SESSION['hv_truck_number']);
            $_SESSION['flash_status']='success';
            $_SESSION['flash_msg']="บันทึกผลตรวจรถตัดเบอร์ <strong>".htmlspecialchars($harvester_number)."</strong> เรียบร้อยแล้ว";
            header("Location: harvester.php"); exit;

        } catch(Exception $e){
            $status='error'; $message='เกิดข้อผิดพลาด: '.$e->getMessage();
        }
    }
}

$show_step = (!empty($_SESSION['hv_truck_number'])) ? 2 : 1;
$current_truck = $_SESSION['hv_truck_number'] ?? '';

// ── ดึงรายการสภาพแปลง ──
$field_items = [];
try { $field_items = $conn->query("SELECT * FROM check_items_field ORDER BY item_id ASC")->fetchAll(); } catch(Exception $e){}

// ── ดึงรายการตรวจชุดใบมีด/ตัด แบ่งกลุ่มตาม section ──
$cut_items = [];
try { $cut_items = $conn->query("SELECT * FROM check_items_cut ORDER BY section_no ASC, item_id ASC")->fetchAll(); } catch(Exception $e){}
$grouped_items = [];
foreach($cut_items as $it){ $grouped_items[$it['section_label']][] = $it; }

$section_icons = [
    1=>'fa-arrow-up', 2=>'fa-rotate', 3=>'fa-arrow-down', 4=>'fa-circle-notch',
    5=>'fa-scissors', 6=>'fa-fan', 7=>'fa-wind', 8=>'fa-broom',
];

// ── ดึงประวัติ 20 รายการล่าสุด ──
$history = [];
try {
    $stmt_h = $conn->prepare(
        "SELECT cs.*, e.emp_name, e.emp_unit,
                COUNT(cr.result_id) AS total_items,
                SUM(cr.pass) AS pass_count
         FROM check_sessions cs
         JOIN employee e ON cs.emp_id=e.emp_id
         LEFT JOIN check_results cr ON cs.session_id=cr.session_id
         WHERE cs.crop_year=:cy
         GROUP BY cs.session_id
         ORDER BY cs.checked_at DESC LIMIT 20"
    );
    $stmt_h->execute([':cy'=>$_SESSION['crop_year']]);
    $history = $stmt_h->fetchAll();

    $stmt_fail = $conn->prepare(
        "SELECT cr.note, ci.item_name_cut
         FROM check_results cr
         JOIN check_items_cut ci ON cr.item_id=ci.item_id
         WHERE cr.session_id=:sid AND cr.pass=0"
    );
    foreach($history as &$h){
        $stmt_fail->execute([':sid'=>$h['session_id']]);
        $h['fails'] = $stmt_fail->fetchAll();
    }
    unset($h);
} catch(Exception $e){}

$thai_months=['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน',
              'กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
$now_d=(int)date('d'); $now_m=(int)date('m'); $now_y=(int)date('Y')+543;
$thai_date_now = $now_d.' '.$thai_months[$now_m].' '.$now_y;

include 'includes/nav_u_header.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ตรวจเช็กรถตัดอ้อย - KTIS SMART FIELD</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
<!-- SweetAlert2 สำหรับ popup แจ้งเตือน -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
*{box-sizing:border-box;}
body{font-family:'Sarabun',sans-serif;background:#f1f5f9;margin:0;}
.content-wrapper{flex:1 0 auto;}
.page-wrap{max-width:760px;margin:24px auto;padding:0 14px 60px;}

.page-header{display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap;}
.page-header-icon{width:46px;height:46px;background:linear-gradient(135deg,#10b981,#059669);border-radius:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.page-header-icon i{color:#fff;font-size:1.3rem;}
.page-header-title{font-size:1.15rem;font-weight:700;color:#1e293b;margin-bottom:2px;}
.page-header-sub{font-size:.8rem;color:#64748b;}

.alert{display:flex;align-items:flex-start;gap:10px;padding:13px 16px;border-radius:9px;margin-bottom:18px;font-weight:600;font-size:.9rem;}
.alert-success{background:#d1fae5;border:1px solid #a7f3d0;color:#065f46;}
.alert-error{background:#fee2e2;border:1px solid #fecaca;color:#991b1b;}
.alert i{margin-top:2px;flex-shrink:0;}

.step1-card{background:#fff;border-radius:16px;border:.5px solid #e2e8f0;padding:32px 24px;text-align:center;box-shadow:0 8px 24px rgba(0,0,0,.05);}
.step1-icon{width:64px;height:64px;background:linear-gradient(135deg,#10b981,#059669);border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;}
.step1-icon i{color:#fff;font-size:1.7rem;}
.step1-title{font-size:1.1rem;font-weight:700;color:#1e293b;margin-bottom:6px;}
.step1-sub{font-size:.85rem;color:#64748b;margin-bottom:24px;}
.step1-input{width:100%;padding:14px 16px;border:2px solid #e2e8f0;border-radius:10px;font-size:1.2rem;font-weight:700;text-align:center;font-family:'Sarabun',sans-serif;color:#1e293b;outline:none;transition:border-color .15s;letter-spacing:.05em;}
.step1-input:focus{border-color:#10b981;}
.step1-btn{width:100%;margin-top:16px;padding:14px;background:#10b981;color:#fff;border:none;border-radius:10px;font-size:1rem;font-weight:700;font-family:'Sarabun',sans-serif;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:background .15s;}
.step1-btn:hover{background:#059669;}

.truck-badge-bar{display:flex;align-items:center;justify-content:space-between;background:#1e293b;border-radius:12px;padding:14px 18px;margin-bottom:18px;color:#fff;flex-wrap:wrap;gap:10px;}
.truck-badge-l{display:flex;align-items:center;gap:12px;}
.truck-badge-icon{width:42px;height:42px;background:rgba(16,185,129,.2);border-radius:10px;display:flex;align-items:center;justify-content:center;}
.truck-badge-icon i{color:#10b981;font-size:1.1rem;}
.truck-badge-label{font-size:.72rem;color:#94a3b8;}
.truck-badge-num{font-size:1.15rem;font-weight:700;font-family:monospace;letter-spacing:.05em;}
.truck-badge-change{color:#fda4af;text-decoration:none;font-size:.8rem;font-weight:700;display:flex;align-items:center;gap:5px;padding:6px 12px;border:1px solid rgba(253,164,175,.3);border-radius:7px;transition:background .15s;}
.truck-badge-change:hover{background:rgba(225,29,72,.15);}

.form-card{background:#fff;border-radius:14px;border:.5px solid #e2e8f0;overflow:hidden;margin-bottom:28px;}
.form-card-header{background:#1e293b;padding:14px 20px;display:flex;align-items:center;gap:10px;border-bottom:3px solid #10b981;}
.form-card-header i{color:#10b981;font-size:1rem;}
.form-card-header span{color:#f8fafc;font-weight:700;font-size:.95rem;}
.form-card-body{padding:20px;}

.meta-bar{display:flex;gap:10px;flex-wrap:wrap;background:#f8fafc;border:1px solid #e2e8f0;border-radius:9px;padding:11px 14px;margin-bottom:20px;}
.meta-chip{display:inline-flex;align-items:center;gap:6px;font-size:.82rem;font-weight:600;color:#475569;}
.meta-chip i{color:#94a3b8;font-size:.85rem;}
.meta-sep{color:#e2e8f0;}

.field-label{display:block;font-weight:700;font-size:.83rem;color:#374151;margin-bottom:7px;}
.field-label .req{color:#e11d48;}
.form-input{width:100%;padding:11px 13px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.95rem;font-family:'Sarabun',sans-serif;background:#f8fafc;color:#1e293b;outline:none;transition:border-color .15s;}
.form-input:focus{border-color:#10b981;background:#fff;}
select.form-input{cursor:pointer;}
.field-etc-wrap{margin-top:10px;display:none;}
.field-etc-wrap.show{display:block;}

.cut-section{margin-top:22px;}
.cut-section-hd{display:flex;align-items:center;gap:9px;background:#f0fdf4;border-radius:9px;padding:9px 14px;margin-bottom:10px;}
.cut-section-hd .sec-icon{width:30px;height:30px;background:#10b981;border-radius:7px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.cut-section-hd .sec-icon i{color:#fff;font-size:.8rem;}
.cut-section-hd .sec-title{font-weight:700;font-size:.88rem;color:#065f46;}

.check-row{padding:11px 4px;border-bottom:1px solid #f8fafc;}
.check-row:last-of-type{border-bottom:none;}
.check-row-top{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;}
.check-row-label{flex:1;min-width:160px;font-weight:600;color:#334155;font-size:.9rem;}
.radio-group{display:flex;gap:8px;}
.radio-btn{display:none;}
.radio-label{display:inline-flex;align-items:center;gap:5px;padding:6px 13px;border-radius:7px;cursor:pointer;font-weight:700;font-size:.8rem;border:1.5px solid #e2e8f0;transition:all .15s;white-space:nowrap;}
.radio-btn[value="1"]:checked + .radio-label{background:#10b981;color:#fff;border-color:#10b981;}
.radio-btn[value="0"]:checked + .radio-label{background:#e11d48;color:#fff;border-color:#e11d48;}
.radio-label.ok{color:#059669;background:#f0fdf4;}
.radio-label.bad{color:#e11d48;background:#fef2f2;}

.note-wrap{display:none;margin-top:8px;}
.note-wrap.show{display:block;}
.note-input{width:100%;padding:8px 11px;border:1.5px solid #fecaca;border-radius:7px;font-size:.85rem;font-family:'Sarabun',sans-serif;background:#fff5f5;color:#1e293b;outline:none;resize:vertical;min-height:56px;transition:border-color .15s;}
.note-input:focus{border-color:#e11d48;background:#fff;}
.note-label{font-size:.78rem;color:#e11d48;font-weight:700;margin-bottom:4px;display:flex;align-items:center;gap:5px;}

.section-label{font-weight:700;font-size:.85rem;color:#1e293b;display:flex;align-items:center;gap:7px;margin:22px 0 10px;padding-bottom:8px;border-bottom:1px solid #f1f5f9;}
.section-label i{color:#10b981;}
.upload-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.upload-box{border:2px dashed #e2e8f0;border-radius:10px;padding:16px 12px;text-align:center;cursor:pointer;transition:border-color .2s,background .2s;background:#f8fafc;position:relative;}
.upload-box:hover{border-color:#10b981;background:#f0fdf4;}
.upload-box input[type="file"]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;}
.upload-box .up-icon{font-size:1.6rem;color:#94a3b8;margin-bottom:6px;}
.upload-box .up-title{font-weight:700;font-size:.82rem;color:#334155;margin-bottom:2px;}
.upload-box .up-hint{font-size:.72rem;color:#94a3b8;}
.upload-box.has-file{border-color:#10b981;background:#f0fdf4;}
.upload-box.has-file .up-icon{color:#10b981;}
.upload-box.has-file .up-hint{color:#059669;font-weight:600;}
.up-preview{display:none;margin-top:8px;}
.up-preview img{width:100%;max-height:100px;object-fit:cover;border-radius:6px;border:1px solid #a7f3d0;}
.upload-box.has-file .up-preview{display:block;}

.btn-submit{width:100%;padding:13px;background:#10b981;color:#fff;border:none;border-radius:9px;font-size:1rem;font-weight:700;font-family:'Sarabun',sans-serif;cursor:pointer;margin-top:22px;display:flex;align-items:center;justify-content:center;gap:7px;transition:background .15s;}
.btn-submit:hover{background:#059669;}

.history-card{background:#fff;border-radius:14px;border:.5px solid #e2e8f0;overflow:hidden;}
.history-header{background:#1e293b;padding:13px 18px;display:flex;align-items:center;justify-content:space-between;border-bottom:3px solid #64748b;}
.history-header-left{display:flex;align-items:center;gap:8px;}
.history-header-left i{color:#94a3b8;}
.history-header-left span{color:#f8fafc;font-weight:700;font-size:.92rem;}
.history-count{background:rgba(255,255,255,.1);color:#cbd5e1;font-size:.75rem;font-weight:700;padding:2px 9px;border-radius:10px;}

.hist-tbl-wrap{max-height:295px;overflow-y:auto;overflow-x:auto;-webkit-overflow-scrolling:touch;}
.hist-tbl-wrap::-webkit-scrollbar{width:5px;height:5px;}
.hist-tbl-wrap::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:10px;}
.hist-tbl-wrap::-webkit-scrollbar-track{background:#f8fafc;}
.hist-tbl{width:100%;border-collapse:collapse;min-width:650px;}
.hist-tbl thead th{background:#f8fafc;color:#64748b;font-size:.73rem;font-weight:700;padding:8px 12px;text-align:left;border-bottom:2px solid #e2e8f0;white-space:nowrap;position:sticky;top:0;z-index:1;}
.hist-tbl tbody td{padding:10px 12px;border-bottom:1px solid #f1f5f9;font-size:.82rem;color:#334155;vertical-align:middle;}
.hist-tbl tbody tr:last-child td{border-bottom:none;}
.hist-tbl tbody tr:hover{background:#f8fafc;}
.hist-num{font-weight:700;color:#1e293b;font-family:monospace;font-size:.9rem;}
.field-badge{display:inline-flex;align-items:center;gap:3px;font-size:.7rem;font-weight:700;padding:2px 7px;border-radius:4px;background:#f0fdf4;color:#065f46;border:1px solid #a7f3d0;}
.field-badge.bad{background:#fff7ed;color:#92400e;border-color:#fcd34d;}
.pass-all{background:#d1fae5;color:#065f46;font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:10px;white-space:nowrap;display:inline-flex;align-items:center;gap:3px;}
.pass-some-fail{background:#fee2e2;color:#991b1b;font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:10px;white-space:nowrap;display:inline-flex;align-items:center;gap:3px;}
.fail-tags{display:flex;flex-wrap:wrap;gap:3px;margin-top:4px;}
.fail-tag{background:#fff7ed;border-left:2px solid #f59e0b;border-radius:0 3px 3px 0;padding:1px 5px;font-size:.7rem;color:#92400e;white-space:nowrap;}
.img-thumbs{display:flex;gap:4px;}
.img-thumb{width:30px;height:30px;object-fit:cover;border-radius:5px;border:1px solid #e2e8f0;cursor:pointer;transition:transform .15s;}
.img-thumb:hover{transform:scale(1.15);}
.empty-hist{text-align:center;padding:36px 20px;color:#94a3b8;}
.empty-hist i{font-size:2rem;display:block;margin-bottom:8px;}

/* ── ปุ่ม action ──*/
.btn-action-edit{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:6px;background:#fffbeb;border:1px solid #fcd34d;color:#d97706;text-decoration:none;font-size:.8rem;transition:background .15s;margin-right:4px;}
.btn-action-edit:hover{background:#fef3c7;}
.btn-action-del{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:6px;background:#fff1f2;border:1px solid #fecaca;color:#e11d48;text-decoration:none;font-size:.8rem;transition:background .15s;}
.btn-action-del:hover{background:#fee2e2;}
/* ── ปุ่มดูเพิ่มเติม ──*/
.btn-fail-detail{margin-top:4px;display:inline-flex;align-items:center;gap:4px;font-size:.68rem;font-weight:700;color:#92400e;background:#fff7ed;border:1px solid #fcd34d;border-radius:5px;padding:2px 7px;cursor:pointer;font-family:'Sarabun',sans-serif;transition:background .15s;}
.btn-fail-detail:hover{background:#fef3c7;}
/* ── Modal รายการไม่ผ่าน ──*/
.fail-modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:8888;align-items:center;justify-content:center;}
.fail-modal-overlay.show{display:flex;}
.fail-modal{background:#fff;border-radius:14px;width:90%;max-width:440px;overflow:hidden;box-shadow:0 20px 50px rgba(0,0,0,.2);}
.fail-modal-hd{background:#1e293b;padding:14px 18px;display:flex;align-items:center;justify-content:space-between;border-bottom:3px solid #e11d48;}
.fail-modal-hd-l{display:flex;align-items:center;gap:8px;}
.fail-modal-hd-l i{color:#e11d48;}
.fail-modal-hd-l span{color:#f8fafc;font-weight:700;font-size:.9rem;}
.fail-modal-close{color:#94a3b8;background:none;border:none;font-size:1.3rem;cursor:pointer;line-height:1;}
.fail-modal-body{padding:16px 18px;max-height:360px;overflow-y:auto;}
.fail-item{display:flex;gap:10px;padding:9px 0;border-bottom:1px solid #f1f5f9;align-items:flex-start;}
.fail-item:last-child{border-bottom:none;}
.fail-dot{width:8px;height:8px;border-radius:50%;background:#e11d48;flex-shrink:0;margin-top:5px;}
.fail-item-name{font-weight:700;color:#1e293b;font-size:.85rem;}
.fail-item-note{font-size:.78rem;color:#64748b;margin-top:2px;}

/* Lightbox styles removed - using global swipe lightbox instead */

@media(max-width:640px){
    .check-row-top{flex-direction:column;align-items:flex-start;}
    .radio-group{width:100%;}
    .radio-label{flex:1;justify-content:center;}
    .upload-grid{grid-template-columns:1fr;}
}
</style>
</head>
<body>
<div class="content-wrapper">
<div class="page-wrap">

    <div class="page-header">
        <div class="page-header-icon"><i class="fa-solid fa-tractor"></i></div>
        <div>
            <div class="page-header-title">ตรวจเช็กรถตัดอ้อยประจำวัน</div>
            <div class="page-header-sub">ปีการผลิต <?php echo htmlspecialchars($_SESSION['crop_year']); ?> · วันที่ <?php echo $thai_date_now; ?></div>
        </div>
    </div>

    <?php /* alert กล่องเดิมยังเก็บไว้สำหรับ error เท่านั้น ส่วน success ใช้ SweetAlert popup แทน */ ?>
    <?php if(!empty($message) && $status==='error'): ?>
    <div class="alert alert-error">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?php echo $message; ?></span>
    </div>
    <?php endif; ?>

    <?php if($show_step===1): ?>
    <div class="step1-card">
        <div class="step1-icon"><i class="fa-solid fa-tractor"></i></div>
        <div class="step1-title">เริ่มตรวจเช็กรถตัดอ้อย</div>
        <div class="step1-sub">กรอกเบอร์รถตัดอ้อยที่ต้องการตรวจสอบก่อนเริ่ม</div>
        <form method="POST" action="harvester.php">
            <input type="hidden" name="action" value="set_truck">
            <input type="text" name="harvester_number" class="step1-input"
                   placeholder="เช่น 21" required autofocus autocomplete="off">
            <button type="submit" class="step1-btn">
                <i class="fa-solid fa-arrow-right"></i> เริ่มตรวจเช็ก
            </button>
        </form>
    </div>

<!-- ประวัติการบันทึก (แสดงเฉพาะ Step 1) -->
<div class="history-card" style="margin-top:20px;">
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
    <div class="hist-tbl-wrap">
        <table class="hist-tbl">
            <thead>
                <tr>
                    <th>วันที่ / เวลา</th>
                    <th>เบอร์รถตัด</th>
                    <th>สภาพแปลง</th>
                    <th>ผลตรวจ (<?php echo count($cut_items); ?> รายการ)</th>
                    <th>ภาพ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($history as $h):
                $d=(int)date('d',strtotime($h['checked_at']));
                $mo=(int)date('m',strtotime($h['checked_at']));
                $yr=(int)date('Y',strtotime($h['checked_at']))+543;
                $date_str=$d.' '.$thai_months[$mo].' '.$yr;
                $time_str=date('H:i น.',strtotime($h['checked_at']));
                $total=(int)($h['total_items']??0);
                $pass=(int)($h['pass_count']??0);
                $allok=($total>0 && $pass==$total);
                $is_bad_field=!in_array($h['field_condition']??'',['ปกติ','']);
            ?>
            <tr>
                <td style="white-space:nowrap;">
                    <div style="font-weight:700;font-size:.8rem;color:#1e293b;"><?php echo $date_str; ?></div>
                    <div style="font-size:.72rem;color:#94a3b8;"><?php echo $time_str; ?></div>
                </td>
                <td>
                    <span class="hist-num">
                        <i class="fa-solid fa-tractor" style="color:#10b981;margin-right:4px;font-size:.8rem;"></i>
                        <?php echo htmlspecialchars($h['harvester_number']); ?>
                    </span>
                </td>
                <td>
                    <?php if(!empty($h['field_condition'])): ?>
                    <span class="field-badge <?php echo $is_bad_field?'bad':''; ?>">
                        <i class="fa-solid <?php echo $is_bad_field?'fa-triangle-exclamation':'fa-leaf'; ?>"></i>
                        <?php echo htmlspecialchars($h['field_condition']); ?>
                    </span>
                    <?php else: ?><span style="color:#cbd5e1;">—</span><?php endif; ?>
                </td>
                <td>
                    <?php if($allok): ?>
                        <span class="pass-all"><i class="fa-solid fa-check-double"></i> ผ่านทั้งหมด</span>
                    <?php elseif($total>0): ?>
                        <span class="pass-some-fail"><i class="fa-solid fa-triangle-exclamation"></i> ไม่ผ่าน <?php echo $total-$pass; ?>/<?php echo $total; ?></span>
                        <?php if(!empty($h['fails'])): ?>
                        <?php
                            // เตรียม JSON สำหรับ modal
                            $fail_json = json_encode(array_map(fn($f)=>[
                                'name'=>$f['item_name_cut'],
                                'note'=>$f['note']??''
                            ], $h['fails']), JSON_UNESCAPED_UNICODE);
                        ?>
                        <button class="btn-fail-detail" onclick='openFailModal(<?php echo htmlspecialchars($fail_json,ENT_QUOTES); ?>, "<?php echo htmlspecialchars($h['harvester_number']); ?>")'>
                            <i class="fa-solid fa-magnifying-glass"></i> ดูเพิ่มเติม
                        </button>
                        <?php endif; ?>
                    <?php else: ?><span style="color:#cbd5e1;">—</span><?php endif; ?>
                </td>
                <td>
                    <div class="img-thumbs rp-list">
                        <?php if(!empty($h['img_harvester'])): ?><img class="img-thumb rp-thumb" src="<?php echo htmlspecialchars($h['img_harvester']); ?>" title="รูปรถตัด"><?php endif; ?>
                        <?php if(!empty($h['img_field'])): ?><img class="img-thumb rp-thumb" src="<?php echo htmlspecialchars($h['img_field']); ?>" title="รูปแปลงอ้อย"><?php endif; ?>
                        <?php if(empty($h['img_harvester']) && empty($h['img_field'])): ?><span style="color:#cbd5e1;">—</span><?php endif; ?>
                    </div>
                </td>
                <td style="white-space:nowrap;">
                    <a href="harvester_edit.php?id=<?php echo $h['session_id']; ?>" class="btn-action-edit" title="แก้ไข">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </a>
                    <a href="harvester_delete.php?id=<?php echo $h['session_id']; ?>" class="btn-action-del" title="ลบ"
                       onclick="return confirm('ยืนยันลบผลตรวจรถเบอร์ <?php echo htmlspecialchars($h['harvester_number'],ENT_QUOTES); ?>?')">
                        <i class="fa-solid fa-trash-can"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

    <?php else: ?>

    <div class="truck-badge-bar">
        <div class="truck-badge-l">
            <div class="truck-badge-icon"><i class="fa-solid fa-tractor"></i></div>
            <div>
                <div class="truck-badge-label">กำลังตรวจรถตัดเบอร์</div>
                <div class="truck-badge-num"><?php echo htmlspecialchars($current_truck); ?></div>
            </div>
        </div>
        <a href="?reset_truck=1" class="truck-badge-change" onclick="return confirm('เปลี่ยนเบอร์รถตัด? ข้อมูลที่กรอกไว้ในฟอร์มจะหายไป')">
            <i class="fa-solid fa-rotate"></i> เปลี่ยนเบอร์
        </a>
    </div>

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

            <form action="harvester.php" method="POST" enctype="multipart/form-data" id="checkForm">
                <input type="hidden" name="action" value="submit_check">

                <div class="section-label"><i class="fa-solid fa-leaf"></i> สภาพแปลงอ้อยขณะรถตัดทำงาน</div>
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
                    <input type="text" name="field_condition_etc" id="field_condition_etc" class="form-input" placeholder="โปรดระบุสภาพแปลง...">
                </div>

                <div class="section-label" style="margin-top:24px;">
                    <i class="fa-solid fa-clipboard-check"></i> รายการตรวจสอบความสมบูรณ์ภาคสนาม
                </div>

                <?php foreach($grouped_items as $section_label => $items):
                    $sec_no = $items[0]['section_no'];
                    $icon   = $section_icons[$sec_no] ?? 'fa-gear';
                ?>
                <div class="cut-section">
                    <div class="cut-section-hd">
                        <div class="sec-icon"><i class="fa-solid <?php echo $icon; ?>"></i></div>
                        <div class="sec-title"><?php echo htmlspecialchars($section_label); ?></div>
                    </div>
                    <?php foreach($items as $item):
                        $iid = $item['item_id'];
                    ?>
                    <div class="check-row">
                        <div class="check-row-top">
                            <div class="check-row-label"><?php echo htmlspecialchars($item['item_name_cut']); ?></div>
                            <div class="radio-group">
                                <input type="radio" class="radio-btn" name="item_<?php echo $iid; ?>"
                                       id="item_<?php echo $iid; ?>_ok" value="1" checked
                                       onchange="toggleNote(<?php echo $iid; ?>,1)">
                                <label class="radio-label ok" for="item_<?php echo $iid; ?>_ok">
                                    <i class="fa-solid fa-check"></i> ผ่าน/ปกติ
                                </label>
                                <input type="radio" class="radio-btn" name="item_<?php echo $iid; ?>"
                                       id="item_<?php echo $iid; ?>_fail" value="0"
                                       onchange="toggleNote(<?php echo $iid; ?>,0)">
                                <label class="radio-label bad" for="item_<?php echo $iid; ?>_fail">
                                    <i class="fa-solid fa-xmark"></i> ไม่ผ่าน/ต้องแก้ไข
                                </label>
                            </div>
                        </div>
                        <div class="note-wrap" id="note_wrap_<?php echo $iid; ?>">
                            <div class="note-label"><i class="fa-solid fa-triangle-exclamation"></i> ระบุรายละเอียด/สาเหตุที่ต้องแก้ไข</div>
                            <textarea class="note-input" name="note_item_<?php echo $iid; ?>"
                                      placeholder="เช่น ใบมีดสึกหรอ, ต้องเปลี่ยนอะไหล่..."></textarea>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>

                <div class="section-label"><i class="fa-solid fa-camera"></i> ภาพประกอบ (ไม่บังคับ)</div>
                <div style="font-size:.75rem;color:#64748b;margin-bottom:10px;">
                    <i class="fa-solid fa-compress"></i> รูปจะถูกบีบอัดอัตโนมัติ (800px / 75%)
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

                <button type="submit" class="btn-submit" id="submitBtn">
                    <i class="fa-solid fa-floppy-disk"></i> บันทึกผลการตรวจสอบ
                </button>
            </form>
        </div>
    </div>

    <?php endif; ?>

</div>
</div>

<!-- Modal รายการไม่ผ่าน -->
<div class="fail-modal-overlay" id="failModalOverlay" onclick="closeFailModal(event)">
    <div class="fail-modal" onclick="event.stopPropagation()">
        <div class="fail-modal-hd">
            <div class="fail-modal-hd-l">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span id="failModalTitle">รายการที่ไม่ผ่าน</span>
            </div>
            <button class="fail-modal-close" onclick="closeFailModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="fail-modal-body" id="failModalBody"></div>
    </div>
</div>

<!-- Lightbox HTML removed - using global swipe lightbox instead -->

<?php include 'includes/nav_u_footer.php'; ?>

<script>
function updateTime(){
    const now=new Date();
    const h=String(now.getHours()).padStart(2,'0');
    const m=String(now.getMinutes()).padStart(2,'0');
    const el=document.getElementById('live-time');
    if(el) el.textContent=h+':'+m+' น.';
}
updateTime(); setInterval(updateTime,10000);

function toggleEtc(val){
    const wrap=document.getElementById('etc_wrap');
    const inp=document.getElementById('field_condition_etc');
    if(val==='อื่นๆ'){ wrap.classList.add('show'); inp.required=true; }
    else { wrap.classList.remove('show'); inp.required=false; inp.value=''; }
}

function toggleNote(id,val){
    const wrap=document.getElementById('note_wrap_'+id);
    if(!wrap) return;
    if(val==0){ wrap.classList.add('show'); }
    else { wrap.classList.remove('show'); const ta=wrap.querySelector('textarea'); if(ta) ta.value=''; }
}

function previewImg(input,boxId,prevId){
    const box=document.getElementById(boxId);
    const prev=document.getElementById(prevId);
    if(input.files && input.files[0]){
        const reader=new FileReader();
        reader.onload=e=>{
            prev.querySelector('img').src=e.target.result;
            box.classList.add('has-file');
            const kb=Math.round(input.files[0].size/1024);
            box.querySelector('.up-hint').textContent=input.files[0].name+' ('+kb+' KB)';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function openFailModal(fails, truckNo){
    document.getElementById('failModalTitle').textContent='รถเบอร์ '+truckNo+' — รายการที่ไม่ผ่าน ('+fails.length+' รายการ)';
    let html='';
    fails.forEach(f=>{
        html+=`<div class="fail-item"><div class="fail-dot"></div><div><div class="fail-item-name">${escHtml(f.name)}</div>${f.note?`<div class="fail-item-note"><i class="fa-solid fa-note-sticky" style="color:#f59e0b;margin-right:3px;"></i>${escHtml(f.note)}</div>`:''}</div></div>`;
    });
    document.getElementById('failModalBody').innerHTML=html;
    document.getElementById('failModalOverlay').classList.add('show');
}
function closeFailModal(e){
    if(!e || e.target===document.getElementById('failModalOverlay')){
        document.getElementById('failModalOverlay').classList.remove('show');
    }
}
function escHtml(s){ const d=document.createElement('div'); d.textContent=s; return d.innerHTML; }

document.addEventListener('keydown', e=>{ if(e.key==='Escape'){ closeFailModal(); } });

// ── ปุ่ม submit: โชว์สถานะกำลังบันทึก (ฟอร์ม step2 ยังคง submit แบบปกติ ไม่ใช่ AJAX) ──
const checkForm = document.getElementById('checkForm');
if(checkForm){
    checkForm.addEventListener('submit', function(){
        const btn = document.getElementById('submitBtn');
        if(btn){
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> กำลังบันทึก...';
        }
    });
}

// ── SweetAlert2: แจ้งเตือนผลบันทึกแบบ popup กลางจอ ──
<?php if(!empty($message) && $status==='success'): ?>
document.addEventListener('DOMContentLoaded', function(){
    Swal.fire({
        icon: 'success',
        title: 'บันทึกสำเร็จ!',
        html: <?php echo json_encode($message, JSON_UNESCAPED_UNICODE); ?>,
        confirmButtonText: 'ตกลง',
        confirmButtonColor: '#10b981',
        timer: 4000,
        timerProgressBar: true,
        customClass: { popup: 'sa2-th' }
    });
});
<?php elseif(!empty($message) && $status==='error'): ?>
document.addEventListener('DOMContentLoaded', function(){
    Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาด',
        html: <?php echo json_encode($message, JSON_UNESCAPED_UNICODE); ?>,
        confirmButtonText: 'ปิด',
        confirmButtonColor: '#e11d48'
    });
});
<?php endif; ?>
</script>
<style>
/* ปรับฟอนต์ SweetAlert ให้เป็น Sarabun ตามธีมระบบ */
.swal2-popup.sa2-th, .swal2-popup { font-family:'Sarabun',sans-serif !important; }
</style>
</body>
</html>