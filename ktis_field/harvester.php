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

// ── บีบอัดรูป 800px / 75% (รองรับทั้ง direct, _cam, _gal) ──
function uploadImage(string $field_name, string $base_dir): ?string {
    $file = null;
    if (!empty($_FILES[$field_name]['name']) && ($_FILES[$field_name]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $file = $_FILES[$field_name];
    } elseif (!empty($_FILES[$field_name . '_cam']['name']) && ($_FILES[$field_name . '_cam']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $file = $_FILES[$field_name . '_cam'];
    } elseif (!empty($_FILES[$field_name . '_gal']['name']) && ($_FILES[$field_name . '_gal']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $file = $_FILES[$field_name . '_gal'];
    }
    if (!$file) return null;

    $allowed = ['image/jpeg','image/jpg','image/png','image/webp'];
    if (!in_array($file['type'], $allowed)) return null;
    if ($file['size'] > 10*1024*1024) return null;

    $date_folder = date('Y/m/d');
    $dir = rtrim($base_dir,'/').'/im_user_check/'.$date_folder.'/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $src = match($file['type']){
        'image/png'  => @imagecreatefrompng($file['tmp_name']),
        'image/webp' => @imagecreatefromwebp($file['tmp_name']),
        default      => @imagecreatefromjpeg($file['tmp_name']),
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

// ── เคลียร์ session รถตัด หากเข้าหน้า harvester.php ผ่าน GET ปกติ (ไม่ค้างรถเดิมข้ามหน้า) ──
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (!isset($_GET['step']) || $_GET['step'] != '2')) {
    unset($_SESSION['hv_truck_number']);
}

// ── STEP 1: บันทึกเบอร์รถตัดลง session แล้วไป step 2 ──
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '')==='set_truck'){
    $hv = trim($_POST['harvester_number'] ?? '');
    if(empty($hv)){
        $_SESSION['flash_status']='error';
        $_SESSION['flash_msg']='กรุณาเลือกรถตัดอ้อยก่อนเริ่มตรวจ';
        header("Location: harvester.php"); exit;
    } else {
        $_SESSION['hv_truck_number'] = $hv;
        header("Location: harvester.php?step=2"); exit;
    }
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
    $crop_year           = $_SESSION['crop_year'] ?? '';

    if(empty($harvester_number)){
        $_SESSION['flash_status']='error';
        $_SESSION['flash_msg']='ไม่พบเบอร์รถตัด กรุณาเริ่มใหม่';
        header("Location: harvester.php"); exit;
    }
    if(empty($field_condition)){
        $status='error'; $message='กรุณาเลือกสภาพแปลงอ้อย';
    } else {
        // ตรวจสอบว่าถ้ามีข้อไหนไม่ผ่าน ต้องระบุสาเหตุ
        $cut_items_all = $conn->query("SELECT item_id, item_name_cut FROM check_items_cut ORDER BY section_no ASC, item_id ASC")->fetchAll();
        $missing_note_item = null;
        foreach($cut_items_all as $ci){
            $iid  = $ci['item_id'];
            $pass = isset($_POST["item_$iid"]) ? (int)$_POST["item_$iid"] : 1;
            $note = trim($_POST["note_item_$iid"] ?? '');
            if($pass === 0 && empty($note)){
                $missing_note_item = $ci['item_name_cut'];
                break;
            }
        }

        if($missing_note_item !== null){
            $status='error'; 
            $message='กรุณาระบุสาเหตุที่ต้องแก้ไขสำหรับรายการ: "'.htmlspecialchars($missing_note_item).'" ก่อนบันทึกข้อมูล';
        } else {
            try {
                $base_dir      = __DIR__;
                $img_harvester = uploadImage('img_harvester', $base_dir);
                $img_field     = uploadImage('img_field',     $base_dir);

                $latitude      = !empty($_POST['latitude']) ? floatval($_POST['latitude']) : null;
                $longitude     = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : null;
                $location_name = trim($_POST['location_name'] ?? '');

                $stmt = $conn->prepare(
                    "INSERT INTO check_sessions
                        (emp_id, harvester_number, crop_year,
                         field_condition, field_condition_etc,
                         latitude, longitude, location_name,
                         img_harvester, img_field, checked_at)
                     VALUES
                        (:emp_id, :hn, :cy, :fc, :fce, :lat, :lng, :loc, :imh, :imf, NOW())"
                );
                $stmt->execute([
                    ':emp_id'=>$_SESSION['emp_id'], ':hn'=>$harvester_number, ':cy'=>$crop_year,
                    ':fc'=>$field_condition, ':fce'=>$field_condition_etc?:null,
                    ':lat'=>$latitude, ':lng'=>$longitude, ':loc'=>$location_name?:null,
                    ':imh'=>$img_harvester, ':imf'=>$img_field,
                ]);
                $session_id = $conn->lastInsertId();

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
}

$show_step = (!empty($_SESSION['hv_truck_number']) && (isset($_GET['step']) && $_GET['step'] == '2')) ? 2 : 1;
$current_truck = $_SESSION['hv_truck_number'] ?? '';

// ── ดึงเฉพาะรายการรถตัดที่อยู่ในความรับผิดชอบของพนักงานที่ล็อกอินอยู่ ──
$my_harvesters = [];
try {
    $stmt_my = $conn->prepare("
        SELECT DISTINCT h.harvester_id, h.harvester_number, h.harvester_name 
        FROM employee_harvester eh 
        JOIN harvesters h ON eh.harvester_id = h.harvester_id 
        LEFT JOIN employee e ON (eh.emp_id = e.emp_id OR eh.emp_id = e.ID)
        WHERE (e.emp_id = :emp1 OR e.ID = :emp2 OR eh.emp_id = :emp3) AND h.is_active = 1 
        ORDER BY h.harvester_id ASC
    ");
    $stmt_my->execute([
        ':emp1' => $_SESSION['emp_id'],
        ':emp2' => $_SESSION['emp_id'],
        ':emp3' => $_SESSION['emp_id'],
    ]);
    $my_harvesters = $stmt_my->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

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

// ── ดึงประวัติเฉพาะของ "ผู้ใช้ที่ล็อกอินอยู่" (20 รายการล่าสุด) ──
$history = [];
try {
    $stmt_h = $conn->prepare(
        "SELECT cs.*, e.emp_name, e.emp_unit,
                COUNT(cr.result_id) AS total_items,
                SUM(cr.pass) AS pass_count
         FROM check_sessions cs
         JOIN employee e ON cs.emp_id=e.emp_id
         LEFT JOIN check_results cr ON cs.session_id=cr.session_id
         WHERE cs.crop_year=:cy AND cs.emp_id=:emp_id
         GROUP BY cs.session_id
         ORDER BY cs.checked_at DESC LIMIT 20"
    );
    $stmt_h->execute([':cy'=>$_SESSION['crop_year'], ':emp_id'=>$_SESSION['emp_id']]);
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
<title>ตรวจเช็กรถตัดอ้อย - KTIS SMART FIELD</title>
<!-- SweetAlert2 สำหรับ popup แจ้งเตือน -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
/* ── Global & Typography Reset ── */
*, *::before, *::after {
    box-sizing: border-box;
}
body, input, button, select, textarea, p, span, div, h1, h2, h3, h4, h5, h6, a, label {
    font-family: 'Sarabun', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
/* Protect Font Awesome Icons */
.fa, .fas, .far, .fal, .fad, .fab, .fa-solid, .fa-regular, .fa-brands, [class*="fa-"], [class*="fa-"]::before, [class*="fa-"]::after {
    font-family: "Font Awesome 6 Free", "Font Awesome 5 Free", "FontAwesome" !important;
}

body {
    background-color: #f8fafc;
    color: #1e293b;
    margin: 0;
    -webkit-font-smoothing: antialiased;
}
.content-wrapper { flex: 1 0 auto; }
.page-wrap { max-width: 820px; margin: 24px auto; padding: 0 16px 80px; }

/* ── Page Header ── */
.page-header { display: flex; align-items: center; gap: 14px; margin-bottom: 24px; flex-wrap: wrap; }
.page-header-icon {
    width: 48px; height: 48px;
    background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);
    border-radius: 14px; display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; box-shadow: 0 4px 14px rgba(225, 29, 72, 0.25);
}
.page-header-icon i { color: #ffffff; font-size: 1.35rem; }
.page-header-title { font-size: 1.25rem; font-weight: 800; color: #0f172a; line-height: 1.2; }
.page-header-sub { font-size: 0.84rem; color: #64748b; font-weight: 600; margin-top: 3px; }

/* ── Alerts ── */
.alert { display: flex; align-items: flex-start; gap: 10px; padding: 14px 18px; border-radius: 14px; margin-bottom: 20px; font-weight: 700; font-size: 0.92rem; }
.alert-success { background: #dcfce7; border: 1px solid #bbf7d0; color: #15803d; }
.alert-error { background: #ffe4e6; border: 1px solid #fecdd3; color: #be123c; }
.alert i { margin-top: 2px; flex-shrink: 0; font-size: 1.05rem; }

/* ── Step 1 Card ── */
.step1-card {
    background: #ffffff; border-radius: 20px; border: 1px solid #e2e8f0;
    padding: 36px 24px; text-align: center;
    box-shadow: 0 4px 20px -4px rgba(0,0,0,0.05);
    margin-bottom: 24px;
}
.step1-icon {
    width: 70px; height: 70px;
    background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);
    border-radius: 20px; display: flex; align-items: center; justify-content: center;
    margin: 0 auto 16px; box-shadow: 0 6px 20px rgba(225, 29, 72, 0.28);
}
.step1-icon i { color: #ffffff; font-size: 1.8rem; }
.step1-title { font-size: 1.25rem; font-weight: 800; color: #0f172a; margin-bottom: 6px; }
.step1-sub { font-size: 0.88rem; color: #64748b; font-weight: 600; margin-bottom: 26px; }

.step1-select {
    width: 100%; padding: 14px 18px;
    border: 2px solid #e2e8f0; border-radius: 14px;
    font-size: 1.15rem; font-weight: 700; text-align: left;
    color: #1e293b; outline: none; background: #f8fafc;
    transition: all 0.2s ease; cursor: pointer;
    font-family: 'Sarabun', sans-serif;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23e11d48' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 18px center;
    padding-right: 48px;
}
.step1-select:focus {
    border-color: #e11d48; background: #ffffff;
    box-shadow: 0 0 0 4px rgba(225, 29, 72, 0.12);
}
.step1-select optgroup {
    font-weight: 800;
    color: #e11d48;
    background: #f1f5f9;
    padding: 8px 12px;
}
.step1-select option {
    font-weight: 600;
    color: #1e293b;
    background: #ffffff;
    padding: 10px 14px;
}
.step1-btn {
    width: 100%; margin-top: 18px; padding: 14px;
    background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);
    color: #ffffff; border: none; border-radius: 14px;
    font-size: 1.05rem; font-weight: 800; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    box-shadow: 0 4px 15px rgba(225, 29, 72, 0.3);
    transition: all 0.2s ease;
}
.step1-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(225, 29, 72, 0.4); }
.step1-btn:active { transform: translateY(0); }

/* ── Truck Badge Bar (Step 2) ── */
.truck-badge-bar {
    display: flex; align-items: center; justify-content: space-between;
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border-radius: 16px; padding: 16px 20px; margin-bottom: 20px;
    color: #ffffff; flex-wrap: wrap; gap: 12px;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.15);
}
.truck-badge-l { display: flex; align-items: center; gap: 14px; }
.truck-badge-icon {
    width: 44px; height: 44px;
    background: rgba(225, 29, 72, 0.2); border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    border: 1px solid rgba(225, 29, 72, 0.4);
}
.truck-badge-icon i { color: #f43f5e; font-size: 1.2rem; }
.truck-badge-label { font-size: 0.76rem; color: #94a3b8; font-weight: 600; }
.truck-badge-num { font-size: 1.25rem; font-weight: 800; letter-spacing: 0.03em; color: #f8fafc; }
.truck-badge-change {
    color: #fecdd3; text-decoration: none; font-size: 0.84rem; font-weight: 700;
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 14px; border: 1px solid rgba(254, 205, 211, 0.3); border-radius: 10px;
    background: rgba(225, 29, 72, 0.1); transition: all 0.15s ease;
}
.truck-badge-change:hover { background: rgba(225, 29, 72, 0.25); color: #ffffff; }

/* ── Form Card ── */
.form-card {
    background: #ffffff; border-radius: 20px; border: 1px solid #e2e8f0;
    overflow: hidden; margin-bottom: 28px;
    box-shadow: 0 4px 20px -4px rgba(0,0,0,0.05);
}
.form-card-header {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    padding: 16px 22px; display: flex; align-items: center; gap: 10px;
    border-bottom: 3px solid #e11d48;
}
.form-card-header i { color: #e11d48; font-size: 1.1rem; }
.form-card-header span { color: #f8fafc; font-weight: 800; font-size: 1rem; }
.form-card-body { padding: 24px; }

/* Meta chips */
.meta-bar {
    display: flex; gap: 10px; flex-wrap: wrap;
    background: #f8fafc; border: 1px solid #e2e8f0;
    border-radius: 12px; padding: 12px 16px; margin-bottom: 22px;
}
.meta-chip { display: inline-flex; align-items: center; gap: 6px; font-size: 0.85rem; font-weight: 700; color: #475569; }
.meta-chip i { color: #e11d48; font-size: 0.9rem; }
.meta-sep { color: #cbd5e1; }

.field-label { display: block; font-weight: 700; font-size: 0.88rem; color: #334155; margin-bottom: 8px; }
.field-label .req { color: #e11d48; }

.form-input {
    width: 100%; padding: 11px 14px;
    border: 1.5px solid #e2e8f0; border-radius: 12px;
    font-size: 0.95rem; font-weight: 600;
    background: #f8fafc; color: #1e293b; outline: none;
    transition: all 0.2s ease;
}
.form-input:focus {
    border-color: #e11d48; background: #ffffff;
    box-shadow: 0 0 0 3.5px rgba(225, 29, 72, 0.12);
}
select.form-input {
    cursor: pointer;
    height: 48px;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    background-color: #f8fafc;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2.5' stroke='%23e11d48'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19.5 8.25l-7.5 7.5-7.5-7.5' /%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    background-size: 18px 18px;
    padding-right: 42px;
    font-weight: 700;
    font-size: 0.95rem;
}
select.form-input option {
    background-color: #ffffff;
    color: #1e293b;
    font-weight: 600;
    padding: 10px;
}

.field-etc-wrap { margin-top: 10px; display: none; }
.field-etc-wrap.show { display: block; }

/* Checklist Sections */
.cut-section { margin-top: 24px; }
.cut-section-hd {
    display: flex; align-items: center; gap: 10px;
    background: #fff1f2; border-radius: 12px;
    padding: 10px 16px; margin-bottom: 12px;
    border: 1px solid #ffe4e6;
}
.cut-section-hd .sec-icon {
    width: 32px; height: 32px; background: linear-gradient(135deg, #e11d48, #be123c);
    border-radius: 9px; display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.cut-section-hd .sec-icon i { color: #ffffff; font-size: 0.85rem; }
.cut-section-hd .sec-title { font-weight: 800; font-size: 0.92rem; color: #9f1239; }

.check-row { padding: 14px 6px; border-bottom: 1px solid #f1f5f9; }
.check-row:last-of-type { border-bottom: none; }
.check-row-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.check-row-label { flex: 1; min-width: 170px; font-weight: 700; color: #1e293b; font-size: 0.95rem; }

.radio-group { display: flex; gap: 8px; }
.radio-btn { display: none; }
.radio-label {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 16px; border-radius: 999px; cursor: pointer;
    font-weight: 700; font-size: 0.85rem; border: 1.5px solid #e2e8f0;
    transition: all 0.2s ease; white-space: nowrap;
}
.radio-label.ok { color: #15803d; background: #f0fdf4; border-color: #bbf7d0; }
.radio-label.bad { color: #be123c; background: #fff1f2; border-color: #fecdd3; }
.radio-btn[value="1"]:checked + .radio-label.ok {
    background: linear-gradient(135deg, #10b981, #059669);
    color: #ffffff; border-color: #059669;
    box-shadow: 0 3px 10px rgba(16, 185, 129, 0.25);
}
.radio-btn[value="0"]:checked + .radio-label.bad {
    background: linear-gradient(135deg, #e11d48, #be123c);
    color: #ffffff; border-color: #be123c;
    box-shadow: 0 3px 10px rgba(225, 29, 72, 0.25);
}

.note-wrap { display: none; margin-top: 10px; animation: slideDownFade 0.25s ease forwards; }
.note-wrap.show { display: block; }
.note-input {
    width: 100%; padding: 10px 14px;
    border: 1.5px solid #fecaca; border-radius: 10px;
    font-size: 0.9rem; font-weight: 600;
    background: #fff5f5; color: #1e293b; outline: none;
    resize: vertical; min-height: 64px; transition: all 0.2s ease;
}
.note-input:focus { border-color: #e11d48; background: #ffffff; box-shadow: 0 0 0 3px rgba(225, 29, 72, 0.12); }
.note-label { font-size: 0.82rem; color: #be123c; font-weight: 800; margin-bottom: 6px; display: flex; align-items: center; gap: 5px; }

.section-label {
    font-weight: 800; font-size: 0.95rem; color: #0f172a;
    display: flex; align-items: center; gap: 8px;
    margin: 26px 0 12px; padding-bottom: 8px; border-bottom: 1px solid #f1f5f9;
}
.section-label i { color: #e11d48; }

/* ── Modern Photo Upload Cards (Dual Choice: Camera / Gallery) ── */
.upload-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.upload-card {
    border: 1.5px solid #e2e8f0; border-radius: 16px;
    padding: 16px; background: #f8fafc;
    transition: all 0.2s ease; position: relative;
}
.upload-card-header {
    display: flex; align-items: center; gap: 8px;
    font-weight: 800; font-size: 0.92rem; color: #1e293b;
    margin-bottom: 12px;
}
.upload-card-header i { font-size: 1.1rem; }
.upload-actions { display: flex; flex-direction: column; gap: 8px; }
.btn-up-action {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    padding: 9px 12px; border-radius: 10px; border: 1.5px solid #e2e8f0;
    background: #ffffff; color: #334155; font-size: 0.85rem; font-weight: 700;
    cursor: pointer; transition: all 0.15s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
}
.btn-up-action:hover {
    border-color: #e11d48; background: #fee2e2; color: #e11d48;
    transform: translateY(-1px);
}
.btn-up-cam { border-color: #fecdd3; color: #be123c; }
.btn-up-gal { border-color: #cbd5e1; }
.hidden-file-input { display: none; }

.up-hint-text { font-size: 0.74rem; color: #94a3b8; font-weight: 600; margin-top: 8px; text-align: center; }

.up-preview { display: none; margin-top: 10px; text-align: center; position: relative; }
.up-preview img { width: 100%; max-height: 140px; object-fit: cover; border-radius: 10px; border: 1.5px solid #e2e8f0; }
.btn-remove-img {
    margin-top: 6px; padding: 4px 10px; border-radius: 6px;
    border: 1px solid #fecaca; background: #fff1f2; color: #e11d48;
    font-size: 0.78rem; font-weight: 700; cursor: pointer;
    display: inline-flex; align-items: center; gap: 4px;
}
.btn-remove-img:hover { background: #fee2e2; }

/* ── Submit Button ── */
.btn-submit {
    width: 100%; padding: 14px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #ffffff; border: none; border-radius: 14px;
    font-size: 1.05rem; font-weight: 800; cursor: pointer;
    margin-top: 26px; display: flex; align-items: center; justify-content: center; gap: 8px;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
    transition: all 0.2s ease;
}
.btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4); }
.btn-submit:active { transform: translateY(0); }

/* ── History Card ── */
.history-card {
    background: #ffffff; border-radius: 20px; border: 1px solid #e2e8f0;
    overflow: hidden; box-shadow: 0 4px 20px -4px rgba(0,0,0,0.05);
}
.history-header {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    padding: 15px 22px; display: flex; align-items: center; justify-content: space-between;
    border-bottom: 3px solid #64748b; flex-wrap: wrap; gap: 10px;
}
.history-header-left { display: flex; align-items: center; gap: 10px; }
.history-header-left i { color: #94a3b8; font-size: 1.1rem; }
.history-header-left span { color: #f8fafc; font-weight: 800; font-size: 0.98rem; }
.history-count {
    background: rgba(255,255,255,0.12); color: #f1f5f9;
    font-size: 0.78rem; font-weight: 700; padding: 4px 12px; border-radius: 999px;
}

.hist-tbl-wrap { max-height: 380px; overflow-y: auto; overflow-x: auto; -webkit-overflow-scrolling: touch; }
.hist-tbl-wrap::-webkit-scrollbar { width: 5px; height: 5px; }
.hist-tbl-wrap::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.hist-tbl-wrap::-webkit-scrollbar-track { background: #f8fafc; }

.hist-tbl { width: 100%; border-collapse: collapse; min-width: 650px; }
.hist-tbl thead th {
    background: #f8fafc; color: #64748b; font-size: 0.78rem; font-weight: 800;
    padding: 10px 14px; text-align: left; border-bottom: 2px solid #e2e8f0;
    white-space: nowrap; position: sticky; top: 0; z-index: 1;
}
.hist-tbl tbody td {
    padding: 12px 14px; border-bottom: 1px solid #f1f5f9;
    font-size: 0.86rem; color: #334155; vertical-align: middle;
}
.hist-tbl tbody tr:last-child td { border-bottom: none; }
.hist-tbl tbody tr:hover { background: #f8fafc; }
.hist-num { font-weight: 800; color: #0f172a; font-size: 0.95rem; }

.field-badge {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 0.75rem; font-weight: 700; padding: 3px 8px; border-radius: 6px;
    background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0;
}
.field-badge.bad { background: #fff7ed; color: #c2410c; border-color: #fed7aa; }

.pass-all {
    background: #dcfce7; color: #15803d; font-size: 0.75rem; font-weight: 800;
    padding: 3px 10px; border-radius: 999px; white-space: nowrap;
    display: inline-flex; align-items: center; gap: 4px;
}
.pass-some-fail {
    background: #ffe4e6; color: #be123c; font-size: 0.75rem; font-weight: 800;
    padding: 3px 10px; border-radius: 999px; white-space: nowrap;
    display: inline-flex; align-items: center; gap: 4px;
}
.btn-fail-detail {
    margin-top: 4px; display: inline-flex; align-items: center; gap: 4px;
    font-size: 0.72rem; font-weight: 700; color: #9a3412; background: #fff7ed;
    border: 1px solid #fed7aa; border-radius: 6px; padding: 3px 8px; cursor: pointer;
    transition: all 0.15s ease;
}
.btn-fail-detail:hover { background: #ffedd5; }

.img-thumbs { display: flex; gap: 5px; }
.img-thumb { width: 34px; height: 34px; object-fit: cover; border-radius: 6px; border: 1px solid #e2e8f0; cursor: pointer; transition: transform 0.15s ease; }
.img-thumb:hover { transform: scale(1.15); box-shadow: 0 4px 8px rgba(0,0,0,0.15); }

.btn-action-edit {
    display: inline-flex; align-items: center; justify-content: center;
    width: 32px; height: 32px; border-radius: 8px; background: #fffbeb;
    border: 1px solid #fef08a; color: #b45309; text-decoration: none; font-size: 0.85rem;
    transition: all 0.15s ease; margin-right: 4px;
}
.btn-action-edit:hover { background: #fef9c3; }
.btn-action-del {
    display: inline-flex; align-items: center; justify-content: center;
    width: 32px; height: 32px; border-radius: 8px; background: #fff1f2;
    border: 1px solid #fecdd3; color: #be123c; text-decoration: none; font-size: 0.85rem;
    transition: all 0.15s ease;
}
.btn-action-del:hover { background: #fee2e2; }

.empty-hist { text-align: center; padding: 48px 20px; color: #94a3b8; }
.empty-hist i { font-size: 2.2rem; display: block; margin-bottom: 10px; color: #cbd5e1; }

/* ── Modal รายการไม่ผ่าน ── */
.fail-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.55); z-index: 99999; align-items: center; justify-content: center; }
.fail-modal-overlay.show { display: flex; }
.fail-modal { background: #ffffff; border-radius: 20px; width: 90%; max-width: 460px; overflow: hidden; box-shadow: 0 20px 50px rgba(0,0,0,0.25); }
.fail-modal-hd {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    padding: 16px 20px; display: flex; align-items: center; justify-content: space-between;
    border-bottom: 3px solid #e11d48;
}
.fail-modal-hd-l { display: flex; align-items: center; gap: 8px; }
.fail-modal-hd-l i { color: #e11d48; font-size: 1.1rem; }
.fail-modal-hd-l span { color: #f8fafc; font-weight: 800; font-size: 0.95rem; }
.fail-modal-close { color: #94a3b8; background: none; border: none; font-size: 1.3rem; cursor: pointer; line-height: 1; }
.fail-modal-close:hover { color: #ffffff; }
.fail-modal-body { padding: 18px 20px; max-height: 380px; overflow-y: auto; }
.fail-item { display: flex; gap: 10px; padding: 10px 0; border-bottom: 1px solid #f1f5f9; align-items: flex-start; }
.fail-item:last-child { border-bottom: none; }
.fail-dot { width: 8px; height: 8px; border-radius: 50%; background: #e11d48; flex-shrink: 0; margin-top: 6px; }
.fail-item-name { font-weight: 800; color: #1e293b; font-size: 0.9rem; }
.fail-item-note { font-size: 0.82rem; color: #64748b; margin-top: 3px; }

/* ── Autocomplete Dropdown ── */
.autocomplete-dropdown {
    position: absolute; top: 100%; left: 0; right: 0;
    background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 12px;
    box-shadow: 0 10px 25px -3px rgba(0,0,0,0.1); z-index: 999;
    max-height: 220px; overflow-y: auto; margin-top: 6px;
}
.autocomplete-item {
    padding: 11px 16px; cursor: pointer; font-size: 0.95rem; font-weight: 700;
    color: #1e293b; transition: all 0.1s ease; border-bottom: 1px solid #f1f5f9;
}
.autocomplete-item:last-child { border-bottom: none; }
.autocomplete-item:hover { background: #fee2e2; color: #e11d48; }
.autocomplete-no-result { padding: 12px 16px; color: #64748b; font-size: 0.9rem; text-align: center; }

/* ── Responsive ── */
@media(max-width:640px){
    .check-row-top { flex-direction: column; align-items: flex-start; }
    .radio-group { width: 100%; }
    .radio-label { flex: 1; justify-content: center; }
    .upload-grid { grid-template-columns: 1fr; }
    .page-wrap { padding: 0 12px 60px; }
}

/* ── Dark Mode ── */
.dark-mode body { background-color: #0f172a !important; color: #f8fafc !important; }
.dark-mode .step1-card,
.dark-mode .form-card,
.dark-mode .history-card,
.dark-mode .fail-modal {
    background: #1e293b !important; border-color: #334155 !important;
    color: #f8fafc !important; box-shadow: 0 4px 25px -4px rgba(0,0,0,0.3) !important;
}
.dark-mode .step1-title,
.dark-mode .page-header-title,
.dark-mode .check-row-label,
.dark-mode .section-label,
.dark-mode .upload-card-header,
.dark-mode .hist-num,
.dark-mode .fail-item-name { color: #f8fafc !important; }

.dark-mode .step1-select,
.dark-mode .form-input {
    background-color: #0f172a !important; border-color: #475569 !important; color: #f8fafc !important;
}
.dark-mode .step1-select:focus,
.dark-mode .form-input:focus { background-color: #16202e !important; border-color: #e11d48 !important; }
.dark-mode .step1-select optgroup {
    background-color: #1e293b !important; color: #fda4af !important;
}
.dark-mode .step1-select option {
    background-color: #0f172a !important; color: #f8fafc !important;
}
.dark-mode select.form-input {
    background-color: #0f172a !important;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2.5' stroke='%23f43f5e'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19.5 8.25l-7.5 7.5-7.5-7.5' /%3E%3C/svg%3E") !important;
}
.dark-mode select.form-input option {
    background-color: #1e293b !important;
    color: #f8fafc !important;
}

.dark-mode .meta-bar { background: #0f172a !important; border-color: #334155 !important; }
.dark-mode .meta-chip { color: #cbd5e1 !important; }

.dark-mode .cut-section-hd { background: rgba(225, 29, 72, 0.15) !important; border-color: rgba(225, 29, 72, 0.3) !important; }
.dark-mode .cut-section-hd .sec-title { color: #fda4af !important; }

.dark-mode .upload-card { background: #0f172a !important; border-color: #334155 !important; }
.dark-mode .btn-up-action { background: #1e293b !important; border-color: #334155 !important; color: #cbd5e1 !important; }
.dark-mode .btn-up-action:hover { background: rgba(225, 29, 72, 0.25) !important; border-color: #e11d48 !important; color: #f43f5e !important; }

.dark-mode .note-input { background: rgba(225, 29, 72, 0.12) !important; border-color: #7f1d1d !important; color: #f8fafc !important; }
.dark-mode .note-input:focus { background: #0f172a !important; border-color: #e11d48 !important; }

.dark-mode .hist-tbl thead th { background: #16202e !important; color: #94a3b8 !important; border-bottom-color: #334155 !important; }
.dark-mode .hist-tbl tbody tr:hover { background: #16202e !important; }
.dark-mode .hist-tbl tbody td { border-bottom-color: #263244 !important; color: #e2e8f0 !important; }

.dark-mode .autocomplete-dropdown { background: #1e293b !important; border-color: #475569 !important; }
.dark-mode .autocomplete-item { color: #f8fafc !important; border-bottom-color: #334155 !important; }
.dark-mode .autocomplete-item:hover { background: #be123c !important; color: white !important; }

.swal2-popup.sa2-th, .swal2-popup { font-family: 'Sarabun', sans-serif !important; }
</style>
<div class="content-wrapper">
<div class="page-wrap">

    <div class="page-header">
        <div class="page-header-icon"><i class="fa-solid fa-tractor"></i></div>
        <div>
            <div class="page-header-title">ตรวจเช็กรถตัดอ้อยประจำวัน</div>
            <div class="page-header-sub">ปีการผลิต <?php echo htmlspecialchars($_SESSION['crop_year']); ?> · วันที่ <?php echo $thai_date_now; ?></div>
        </div>
    </div>

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
        <div class="step1-sub">เลือกรถตัดอ้อยที่อยู่ในความรับผิดชอบของคุณ</div>
        <form method="POST" action="harvester.php" id="step1Form">
            <input type="hidden" name="action" value="set_truck">
            <div style="position: relative; max-width: 440px; margin: 0 auto 18px;">
                <?php if (!empty($my_harvesters)): ?>
                    <select name="harvester_number" id="harvester_select" class="step1-select" required autofocus>
                        <option value="">-- เลือกรถตัดที่คุณรับผิดชอบ --</option>
                        <?php foreach ($my_harvesters as $mh): ?>
                            <option value="<?php echo htmlspecialchars($mh['harvester_number']); ?>">
                                🚜 <?php echo htmlspecialchars($mh['harvester_number']); ?><?php echo !empty($mh['harvester_name']) ? ' (' . htmlspecialchars($mh['harvester_name']) . ')' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="step1-btn">
                        <i class="fa-solid fa-arrow-right"></i> เริ่มตรวจเช็ก
                    </button>
                <?php else: ?>
                    <div style="background:#fee2e2;border:1.5px solid #fecaca;color:#991b1b;padding:16px;border-radius:14px;font-weight:600;font-size:0.92rem;line-height:1.5;">
                        <i class="fa-solid fa-triangle-exclamation" style="font-size:1.4rem;display:block;margin-bottom:6px;"></i>
                        ยังไม่มีรถตัดที่อยู่ในความรับผิดชอบของคุณ<br>
                        <span style="font-size:0.8rem;color:#b91c1c;font-weight:normal;">กรุณาติดต่อผู้ดูแลระบบ (Admin) เพื่อผูกรถตัดที่รับผิดชอบในระบบ</span>
                    </div>
                <?php endif; ?>
            </div>
        </form>
    </div>

<!-- ประวัติการบันทึก (เฉพาะของคนที่กำลังล็อกอินบันทึก) -->
<div class="history-card" style="margin-top:20px;">
    <div class="history-header">
        <div class="history-header-left">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>ประวัติการบันทึกของฉัน</span>
        </div>
        <span class="history-count"><?php echo count($history); ?> รายการล่าสุด · ปี <?php echo htmlspecialchars($_SESSION['crop_year']); ?></span>
    </div>
    <?php if(empty($history)): ?>
    <div class="empty-hist">
        <i class="fa-solid fa-clipboard-list"></i>
        ยังไม่มีประวัติการบันทึกของคุณในปีการผลิตนี้
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
                    <div style="font-weight:800;font-size:0.85rem;color:#1e293b;"><?php echo $date_str; ?></div>
                    <div style="font-size:0.75rem;color:#94a3b8;font-weight:600;margin-top:2px;"><?php echo $time_str; ?></div>
                </td>
                <td>
                    <span class="hist-num">
                        <i class="fa-solid fa-tractor" style="color:#e11d48;margin-right:4px;font-size:0.85rem;"></i>
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
                            $fail_json = json_encode(array_map(fn($f)=>[
                                'name'=>$f['item_name_cut'],
                                'note'=>$f['note']??''
                            ], $h['fails']), JSON_UNESCAPED_UNICODE);
                        ?>
                        <button class="btn-fail-detail" onclick='openFailModal(<?php echo htmlspecialchars($fail_json,ENT_QUOTES); ?>, "<?php echo htmlspecialchars($h['harvester_number']); ?>")'>
                            <i class="fa-solid fa-magnifying-glass"></i> ดูสาเหตุ
                        </button>
                        <?php endif; ?>
                    <?php else: ?><span style="color:#cbd5e1;">—</span><?php endif; ?>
                </td>
                <td>
                    <div class="img-thumbs rp-list">
                        <?php if(!empty($h['img_harvester'])): ?><img class="img-thumb rp-thumb js-lightbox-img" data-gallery='["<?php echo addslashes($h['img_harvester']); ?>"]' data-index="0" src="<?php echo htmlspecialchars($h['img_harvester']); ?>" title="รูปรถตัด"><?php endif; ?>
                        <?php if(!empty($h['img_field'])): ?><img class="img-thumb rp-thumb js-lightbox-img" data-gallery='["<?php echo addslashes($h['img_field']); ?>"]' data-index="0" src="<?php echo htmlspecialchars($h['img_field']); ?>" title="รูปแปลงอ้อย"><?php endif; ?>
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
        <div class="form-card-header" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px;">
            <div style="display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-clipboard-list"></i>
                <span>แบบฟอร์มบันทึกผลการตรวจสอบ</span>
            </div>
            <div id="gps-indicator" style="font-size:0.74rem; color:#38bdf8; font-family:monospace; font-weight:700; display:inline-flex; align-items:center; gap:4px; opacity:0; transition:opacity 0.3s ease;" title="พิกัด GPS ที่ตรวจพบ">
                <i class="fa-solid fa-location-dot" style="font-size:0.7rem;"></i>
                <span id="gps-live-coords"></span>
            </div>
        </div>
        <div class="form-card-body">

            <div class="meta-bar">
                <div class="meta-chip"><i class="fa-solid fa-user"></i><span><?php echo htmlspecialchars($_SESSION['emp_name']); ?></span></div>
                <span class="meta-sep">|</span>
                <div class="meta-chip"><i class="fa-solid fa-location-dot"></i><span>หน่วย<?php echo htmlspecialchars($_SESSION['emp_unit'] ?? ''); ?></span></div>
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
                            <div class="note-label">
                                <i class="fa-solid fa-triangle-exclamation"></i> 
                                ระบุสาเหตุที่ต้องแก้ไข <span class="req">* (บังคับกรอก)</span>
                            </div>
                            <textarea class="note-input" name="note_item_<?php echo $iid; ?>" id="note_item_<?php echo $iid; ?>"
                                      placeholder="เช่น ใบมีดสึกหรอมาก, สับไม่ขาด, ต้องเปลี่ยนอะไหล่..."></textarea>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>

                <div class="section-label"><i class="fa-solid fa-camera"></i> ภาพประกอบ (ไม่บังคับ)</div>
                <div style="font-size:0.78rem;color:#64748b;margin-bottom:12px;font-weight:600;">
                    <i class="fa-solid fa-compress"></i> สามารถถ่ายรูปจากกล้องมือถือ หรือเลือกไฟล์รูปภาพจากเครื่องได้ (ระบบบีบอัดให้อัตโนมัติ 800px)
                </div>
                
                <div class="upload-grid">
                    <!-- Harvester Photo -->
                    <div class="upload-card" id="card_harvester">
                        <div class="upload-card-header">
                            <i class="fa-solid fa-tractor" style="color:#e11d48;"></i>
                            <span>รูปรถตัด</span>
                        </div>
                        <div class="upload-actions">
                            <label class="btn-up-action btn-up-cam" title="เปิดกล้องถ่ายภาพทันที">
                                <i class="fa-solid fa-camera"></i> ถ่ายรูปจากกล้อง
                                <input type="file" name="img_harvester_cam" id="img_harvester_cam"
                                       accept="image/*" capture="environment" class="hidden-file-input"
                                       onchange="previewImgChoice(this, 'card_harvester', 'prev_harvester', 'hint_harvester', 'img_harvester_gal')">
                            </label>
                            <label class="btn-up-action btn-up-gal" title="เลือกรูปจากคลังภาพในมือถือหรือไฟล์ในคอม">
                                <i class="fa-solid fa-images"></i> เลือกจากคลังรูป / ในคอม
                                <input type="file" name="img_harvester_gal" id="img_harvester_gal"
                                       accept="image/*" class="hidden-file-input"
                                       onchange="previewImgChoice(this, 'card_harvester', 'prev_harvester', 'hint_harvester', 'img_harvester_cam')">
                            </label>
                        </div>
                        <div class="up-hint-text" id="hint_harvester">JPG / PNG / WEBP ไม่เกิน 10MB</div>
                        <div class="up-preview" id="prev_harvester">
                            <img src="" alt="preview">
                            <button type="button" class="btn-remove-img" onclick="removeImgChoice('card_harvester', 'prev_harvester', 'hint_harvester', 'img_harvester_cam', 'img_harvester_gal')" title="ยกเลิกรูปนี้">
                                <i class="fa-solid fa-xmark"></i> ลบรูป
                            </button>
                        </div>
                    </div>

                    <!-- Field Photo -->
                    <div class="upload-card" id="card_field">
                        <div class="upload-card-header">
                            <i class="fa-solid fa-seedling" style="color:#10b981;"></i>
                            <span>รูปแปลงอ้อย</span>
                        </div>
                        <div class="upload-actions">
                            <label class="btn-up-action btn-up-cam" title="เปิดกล้องถ่ายภาพทันที">
                                <i class="fa-solid fa-camera"></i> ถ่ายรูปจากกล้อง
                                <input type="file" name="img_field_cam" id="img_field_cam"
                                       accept="image/*" capture="environment" class="hidden-file-input"
                                       onchange="previewImgChoice(this, 'card_field', 'prev_field', 'hint_field', 'img_field_gal')">
                            </label>
                            <label class="btn-up-action btn-up-gal" title="เลือกรูปจากคลังภาพในมือถือหรือไฟล์ในคอม">
                                <i class="fa-solid fa-images"></i> เลือกจากคลังรูป / ในคอม
                                <input type="file" name="img_field_gal" id="img_field_gal"
                                       accept="image/*" class="hidden-file-input"
                                       onchange="previewImgChoice(this, 'card_field', 'prev_field', 'hint_field', 'img_field_cam')">
                            </label>
                        </div>
                        <div class="up-hint-text" id="hint_field">JPG / PNG / WEBP ไม่เกิน 10MB</div>
                        <div class="up-preview" id="prev_field">
                            <img src="" alt="preview">
                            <button type="button" class="btn-remove-img" onclick="removeImgChoice('card_field', 'prev_field', 'hint_field', 'img_field_cam', 'img_field_gal')" title="ยกเลิกรูปนี้">
                                <i class="fa-solid fa-xmark"></i> ลบรูป
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Hidden GPS Location Inputs (Auto-collected in background) -->
                <input type="hidden" name="latitude" id="input_latitude">
                <input type="hidden" name="longitude" id="input_longitude">
                <input type="hidden" name="location_name" id="input_location_name">

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
    const ta=wrap.querySelector('textarea');
    if(val==0){
        wrap.classList.add('show');
        if(ta){ ta.required=true; ta.focus(); }
    } else {
        wrap.classList.remove('show');
        if(ta){ ta.required=false; ta.value=''; }
    }
}

// ── จัดการเลือกรูปถ่าย / คลังภาพ ──
function previewImgChoice(input, cardId, prevId, hintId, otherInputId){
    const card = document.getElementById(cardId);
    const prev = document.getElementById(prevId);
    const hint = document.getElementById(hintId);
    const otherInput = document.getElementById(otherInputId);

    if(input.files && input.files[0]){
        // เคลียร์ input อีกตัวหนึ่งเพื่อไม่ให้ส่งไฟล์ซ้ำ
        if(otherInput) otherInput.value = '';

        const file = input.files[0];
        const reader = new FileReader();
        reader.onload = e => {
            prev.querySelector('img').src = e.target.result;
            prev.style.display = 'block';
            const kb = Math.round(file.size / 1024);
            hint.innerHTML = `<span style="color:#10b981;font-weight:700;"><i class="fa-solid fa-circle-check"></i> ${escHtml(file.name)} (${kb} KB)</span>`;
        };
        reader.readAsDataURL(file);
    }
}

function removeImgChoice(cardId, prevId, hintId, input1Id, input2Id){
    const prev = document.getElementById(prevId);
    const hint = document.getElementById(hintId);
    const input1 = document.getElementById(input1Id);
    const input2 = document.getElementById(input2Id);

    if(input1) input1.value = '';
    if(input2) input2.value = '';
    if(prev) {
        prev.querySelector('img').src = '';
        prev.style.display = 'none';
    }
    if(hint) {
        hint.innerHTML = 'JPG / PNG / WEBP ไม่เกิน 10MB';
    }
}

function openFailModal(fails, truckNo){
    document.getElementById('failModalTitle').textContent='รถเบอร์ '+truckNo+' — สาเหตุที่ไม่ผ่าน ('+fails.length+' รายการ)';
    let html='';
    fails.forEach(f=>{
        html+=`<div class="fail-item"><div class="fail-dot"></div><div><div class="fail-item-name">${escHtml(f.name)}</div>${f.note?`<div class="fail-item-note"><i class="fa-solid fa-triangle-exclamation" style="color:#e11d48;margin-right:3px;"></i>สาเหตุ: ${escHtml(f.note)}</div>`:''}</div></div>`;
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

// ── Validation หน้ากรอกผลตรวจ: ถ้ามีไม่ผ่าน บังคับกรอกสาเหตุ ──
const checkForm = document.getElementById('checkForm');
// ── Helper: บีบอัดรูปเป็น Base64 ในฝั่ง Client สำหรับโหมด Offline ──
function fileToBase64Compressed(file, maxWidth = 800) {
    return new Promise((resolve) => {
        if (!file) return resolve(null);
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = new Image();
            img.onload = function() {
                let w = img.width;
                let h = img.height;
                if (w > maxWidth) {
                    h = Math.round((h * maxWidth) / w);
                    w = maxWidth;
                }
                const canvas = document.createElement('canvas');
                canvas.width = w;
                canvas.height = h;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, w, h);
                resolve(canvas.toDataURL('image/jpeg', 0.75));
            };
            img.onerror = () => resolve(null);
            img.src = e.target.result;
        };
        reader.onerror = () => resolve(null);
        reader.readAsDataURL(file);
    });
}

// ── Form Submit: ตรวจสอบข้อมูล + ดึงพิกัด GPS + รองรับโหมดออฟไลน์ ──
if(checkForm){
    let isSubmitting = false;
    checkForm.addEventListener('submit', async function(e){
        if(isSubmitting) return;

        // 1. ตรวจสอบสภาพแปลง
        const fieldCond = document.getElementById('field_condition');
        if(fieldCond && !fieldCond.value.trim()){
            e.preventDefault();
            fieldCond.focus();
            Swal.fire({
                icon: 'warning',
                title: 'กรุณาเลือกสภาพแปลงอ้อย',
                confirmButtonText: 'ตกลง',
                confirmButtonColor: '#e11d48',
                customClass: { popup: 'sa2-th' }
            });
            return false;
        }

        // 2. ตรวจสอบรายการที่ไม่ผ่าน
        const failRadios = checkForm.querySelectorAll('.radio-btn[value="0"]:checked');
        for (let r of failRadios) {
            const row = r.closest('.check-row');
            const textarea = row ? row.querySelector('.note-input') : null;
            const itemName = row ? row.querySelector('.check-row-label').textContent.trim() : 'รายการที่ไม่ผ่าน';
            if (textarea && !textarea.value.trim()) {
                e.preventDefault();
                textarea.focus();
                Swal.fire({
                    icon: 'warning',
                    title: 'กรุณากรอกสาเหตุที่ต้องแก้ไข',
                    html: `กรุณาระบุรายละเอียด/สาเหตุของ <strong>"${escHtml(itemName)}"</strong> ก่อนบันทึกข้อมูล`,
                    confirmButtonText: 'ตกลง',
                    confirmButtonColor: '#e11d48',
                    customClass: { popup: 'sa2-th' }
                });
                return false;
            }
        }

        const latInp = document.getElementById('input_latitude');
        const lngInp = document.getElementById('input_longitude');
        const btn = document.getElementById('submitBtn');

        // 3. กรณีไม่มีสัญญาณอินเทอร์เน็ต (OFFLINE MODE) -> บันทึกลง IndexedDB ในเครื่อง
        if (!navigator.onLine) {
            e.preventDefault();
            if(btn){
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-cloud-arrow-down fa-spin"></i> กำลังบันทึกลงหน่วยความจำเครื่อง (ออฟไลน์)...';
            }

            const fileHv = (document.getElementById('img_harvester_cam')?.files[0]) || (document.getElementById('img_harvester_gal')?.files[0]);
            const fileFd = (document.getElementById('img_field_cam')?.files[0]) || (document.getElementById('img_field_gal')?.files[0]);

            const [b64Hv, b64Fd] = await Promise.all([
                fileToBase64Compressed(fileHv),
                fileToBase64Compressed(fileFd)
            ]);

            const items = [];
            const itemRows = checkForm.querySelectorAll('.check-row');
            itemRows.forEach(row => {
                const okRadio = row.querySelector('input[type="radio"][value="1"]');
                const pass = (okRadio && okRadio.checked) ? 1 : 0;
                const iid = okRadio ? okRadio.name.replace('item_', '') : null;
                const noteInp = row.querySelector('.note-input');
                const note = noteInp ? noteInp.value.trim() : '';
                if (iid) {
                    items.push({ item_id: iid, pass: pass, note: note });
                }
            });

            const offlineRecord = {
                emp_id: "<?php echo addslashes($_SESSION['emp_id'] ?? ''); ?>",
                harvester_number: "<?php echo addslashes($_SESSION['hv_truck_number'] ?? ''); ?>",
                crop_year: "<?php echo addslashes($_SESSION['crop_year'] ?? ''); ?>",
                field_condition: fieldCond ? fieldCond.value : 'ปกติ',
                field_condition_etc: document.getElementById('field_condition_etc') ? document.getElementById('field_condition_etc').value : '',
                latitude: latInp ? latInp.value : '',
                longitude: lngInp ? lngInp.value : '',
                location_name: '',
                checked_at: new Date().toISOString().slice(0, 19).replace('T', ' '),
                img_harvester_b64: b64Hv,
                img_field_b64: b64Fd,
                items: items
            };

            if (window.KTIS_OFFLINE) {
                await KTIS_OFFLINE.saveInspectionOffline(offlineRecord);
            }

            Swal.fire({
                icon: 'success',
                title: 'บันทึกในโหมดออฟไลน์แล้ว 📥',
                html: `ข้อมูลผลตรวจรถตัด <strong>${offlineRecord.harvester_number}</strong> ถูกจัดเก็บไว้ในมือถือแล้ว<br><small style="color:#64748b;margin-top:6px;display:block;">ระบบจะอัปโหลดขึ้นเซิร์ฟเวอร์ให้อัตโนมัติเมื่อมีสัญญาณอินเทอร์เน็ต</small>`,
                confirmButtonText: 'ตกลง',
                confirmButtonColor: '#10b981'
            }).then(() => {
                window.location.href = 'harvester.php?reset_truck=1';
            });
            return;
        }

        // 4. กรณีออนไลน์: ถ้ายังไม่ได้พิกัด GPS ให้พยายามดึงพิกัดด่วน 2.5 วินาที
        if ((!latInp || !latInp.value) && navigator.geolocation) {
            e.preventDefault();
            if(btn){
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> กำลังบันทึกข้อมูล...';
            }

            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    if (latInp) latInp.value = pos.coords.latitude.toFixed(6);
                    if (lngInp) lngInp.value = pos.coords.longitude.toFixed(6);
                    isSubmitting = true;
                    checkForm.submit();
                },
                function(err) {
                    isSubmitting = true;
                    checkForm.submit();
                },
                { enableHighAccuracy: true, timeout: 2500, maximumAge: 60000 }
            );
            return;
        }
        
        if(btn){
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> กำลังบันทึกข้อมูล...';
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
        confirmButtonColor: '#e11d48',
        customClass: { popup: 'sa2-th' }
    });
});
<?php endif; ?>

// ══════════════════════════════════════════
//  Auto Background GPS Capture (ดึงพิกัดอัตโนมัติขณะกรอกข้อมูล)
// ══════════════════════════════════════════
function autoCaptureGPS() {
    if (!navigator.geolocation) return;
    const latInp = document.getElementById('input_latitude');
    const lngInp = document.getElementById('input_longitude');
    const gpsLive = document.getElementById('gps-live-coords');
    const gpsInd  = document.getElementById('gps-indicator');
    if (!latInp || !lngInp) return;

    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude.toFixed(6);
            const lng = position.coords.longitude.toFixed(6);
            latInp.value = lat;
            lngInp.value = lng;
            if (gpsLive && gpsInd) {
                gpsLive.textContent = `${lat}, ${lng}`;
                gpsInd.style.opacity = '1';
            }
        },
        function(err) {
            // เก็บพิกัดไม่สำเร็จในพื้นหลัง ไม่รบกวนการทำงานของผู้ใช้
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 60000
        }
    );
}

// เริ่มดึงพิกัดในพื้นหลังทันทีเมื่อเข้าหน้ากรอกข้อมูล
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('checkForm')) {
        autoCaptureGPS();
    }
});
</script>
</body>
</html>