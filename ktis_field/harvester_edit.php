<?php
/**
 * harvester_edit.php — แก้ไขผลตรวจรถตัดอ้อย
 */
date_default_timezone_set('Asia/Bangkok');
require_once 'config.php';
session_start();

if(!isset($_SESSION["emp_id"])){ header("location: login.php"); exit; }
if(in_array($_SESSION['emp_level'] ?? 'u', ['a', 'm'])){ header("location: harvester_admin.php"); exit; }

// ── ฟังก์ชัน upload รูป (เหมือนไฟล์หลัก) ──
function uploadImage(string $field_name, string $base_dir): ?string {
    if (empty($_FILES[$field_name]['name'])) return null;
    $file    = $_FILES[$field_name];
    $allowed = ['image/jpeg','image/jpg','image/png','image/webp'];
    if (!in_array($file['type'], $allowed)) return null;
    if ($file['size'] > 10*1024*1024) return null;

    $date_folder = date('Y/m/d');
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

// ── ดึง session_id จาก GET ──
$session_id = (int)($_GET['id'] ?? 0);
if(!$session_id){ header("Location: harvester.php"); exit; }

// ── ดึงข้อมูล session ──
$sess_data = null;
try {
    $st = $conn->prepare(
        "SELECT cs.*, e.emp_name, e.emp_unit
         FROM check_sessions cs
         JOIN employee e ON cs.emp_id=e.emp_id
         WHERE cs.session_id=:sid"
    );
    $st->execute([':sid'=>$session_id]);
    $sess_data = $st->fetch();
} catch(Exception $e){}

if(!$sess_data){ header("Location: harvester.php"); exit; }

// ── ป้องกัน: แก้ได้เฉพาะของตัวเอง (หรือ admin) ──
if($sess_data['emp_id'] != $_SESSION['emp_id'] && ($_SESSION['emp_level'] ?? 'u') !== 'a'){
    header("Location: harvester.php"); exit;
}

$message = "";
$status  = "";

// ── บันทึกการแก้ไข ──
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '')==='edit_check'){
    $field_condition     = trim($_POST['field_condition']     ?? '');
    $field_condition_etc = trim($_POST['field_condition_etc'] ?? '');

    if(empty($field_condition)){
        $status='error'; $message='กรุณาเลือกสภาพแปลงอ้อย';
    } else {
        try {
            $base_dir = __DIR__;
            $img_harvester = uploadImage('img_harvester', $base_dir);
            $img_field     = uploadImage('img_field',     $base_dir);

            // อัปเดต check_sessions
            $set_parts = ['field_condition=:fc','field_condition_etc=:fce'];
            $params = [
                ':fc'  => $field_condition,
                ':fce' => $field_condition_etc ?: null,
                ':sid' => $session_id,
            ];
            if($img_harvester){ $set_parts[]= 'img_harvester=:imh'; $params[':imh']=$img_harvester; }
            if($img_field)    { $set_parts[]= 'img_field=:imf';     $params[':imf']=$img_field; }

            $conn->prepare(
                "UPDATE check_sessions SET ".implode(',',$set_parts)." WHERE session_id=:sid"
            )->execute($params);

            // อัปเดต check_results (pass/note แต่ละรายการ)
            $cut_items_all = $conn->query("SELECT item_id FROM check_items_cut ORDER BY section_no ASC, item_id ASC")->fetchAll();
            $stmt_upd = $conn->prepare(
                "UPDATE check_results SET pass=:pass, note=:note WHERE session_id=:sid AND item_id=:iid"
            );
            foreach($cut_items_all as $ci){
                $iid  = $ci['item_id'];
                $pass = isset($_POST["item_$iid"]) ? (int)$_POST["item_$iid"] : 1;
                $note = (!$pass) ? trim($_POST["note_item_$iid"] ?? '') : '';
                $stmt_upd->execute([':pass'=>$pass, ':note'=>$note?:null, ':sid'=>$session_id, ':iid'=>$iid]);
            }

            $_SESSION['flash_status']='success';
            $_SESSION['flash_msg']="แก้ไขผลตรวจรถตัดเบอร์ <strong>".htmlspecialchars($sess_data['harvester_number'])."</strong> เรียบร้อยแล้ว";
            header("Location: harvester.php"); exit;

        } catch(Exception $e){
            $status='error'; $message='เกิดข้อผิดพลาด: '.$e->getMessage();
        }
    }
}

// ── ดึงรายการตรวจและผลปัจจุบัน ──
$cut_items = [];
try { $cut_items = $conn->query("SELECT * FROM check_items_cut ORDER BY section_no ASC, item_id ASC")->fetchAll(); } catch(Exception $e){}
$grouped_items = [];
foreach($cut_items as $it){ $grouped_items[$it['section_label']][] = $it; }

// ดึงผลตรวจปัจจุบัน (pass/note) ของ session นี้
$current_results = [];
try {
    $st2 = $conn->prepare("SELECT item_id, pass, note FROM check_results WHERE session_id=:sid");
    $st2->execute([':sid'=>$session_id]);
    foreach($st2->fetchAll() as $row){ $current_results[$row['item_id']] = $row; }
} catch(Exception $e){}

// ── ดึงรายการสภาพแปลง ──
$field_items = [];
try { $field_items = $conn->query("SELECT * FROM check_items_field ORDER BY item_id ASC")->fetchAll(); } catch(Exception $e){}

$section_icons = [
    1=>'fa-arrow-up', 2=>'fa-rotate', 3=>'fa-arrow-down', 4=>'fa-circle-notch',
    5=>'fa-scissors', 6=>'fa-fan', 7=>'fa-wind', 8=>'fa-broom',
];

$thai_months=['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน',
              'กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];

include 'includes/nav_u_header.php';
?>
<title>แก้ไขผลตรวจรถตัดอ้อย - KTIS SMART FIELD</title>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
*{box-sizing:border-box;}
body{font-family:'Sarabun',sans-serif;background:#f1f5f9;margin:0;}
.content-wrapper{flex:1 0 auto;}
.page-wrap{max-width:760px;margin:24px auto;padding:0 14px 60px;}

.page-header{display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap;}
.page-header-icon{width:46px;height:46px;background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.page-header-icon i{color:#fff;font-size:1.3rem;}
.page-header-title{font-size:1.15rem;font-weight:700;color:#1e293b;margin-bottom:2px;}
.page-header-sub{font-size:.8rem;color:#64748b;}

.alert{display:flex;align-items:flex-start;gap:10px;padding:13px 16px;border-radius:9px;margin-bottom:18px;font-weight:600;font-size:.9rem;}
.alert-error{background:#fee2e2;border:1px solid #fecaca;color:#991b1b;}
.alert i{margin-top:2px;flex-shrink:0;}

.truck-badge-bar{display:flex;align-items:center;justify-content:space-between;background:#1e293b;border-radius:12px;padding:14px 18px;margin-bottom:18px;color:#fff;flex-wrap:wrap;gap:10px;}
.truck-badge-l{display:flex;align-items:center;gap:12px;}
.truck-badge-icon{width:42px;height:42px;background:rgba(245,158,11,.2);border-radius:10px;display:flex;align-items:center;justify-content:center;}
.truck-badge-icon i{color:#f59e0b;font-size:1.1rem;}
.truck-badge-label{font-size:.72rem;color:#94a3b8;}
.truck-badge-num{font-size:1.15rem;font-weight:700;font-family:monospace;letter-spacing:.05em;}
.btn-back{color:#fda4af;text-decoration:none;font-size:.8rem;font-weight:700;display:flex;align-items:center;gap:5px;padding:6px 12px;border:1px solid rgba(253,164,175,.3);border-radius:7px;transition:background .15s;}
.btn-back:hover{background:rgba(225,29,72,.15);}

.form-card{background:#fff;border-radius:14px;border:.5px solid #e2e8f0;overflow:hidden;margin-bottom:28px;}
.form-card-header{background:#1e293b;padding:14px 20px;display:flex;align-items:center;gap:10px;border-bottom:3px solid #f59e0b;}
.form-card-header i{color:#f59e0b;font-size:1rem;}
.form-card-header span{color:#f8fafc;font-weight:700;font-size:.95rem;}
.form-card-body{padding:20px;}

.meta-bar{display:flex;gap:10px;flex-wrap:wrap;background:#f8fafc;border:1px solid #e2e8f0;border-radius:9px;padding:11px 14px;margin-bottom:20px;}
.meta-chip{display:inline-flex;align-items:center;gap:6px;font-size:.82rem;font-weight:600;color:#475569;}
.meta-chip i{color:#94a3b8;font-size:.85rem;}
.meta-sep{color:#e2e8f0;}

.field-label{display:block;font-weight:700;font-size:.83rem;color:#374151;margin-bottom:7px;}
.form-input{width:100%;padding:11px 14px;border:1.5px solid #e2e8f0;border-radius:12px;font-size:.95rem;font-weight:600;font-family:'Sarabun',sans-serif;background:#f8fafc;color:#1e293b;outline:none;transition:all .2s ease;}
.form-input:focus{border-color:#f59e0b;background:#fff;box-shadow:0 0 0 3.5px rgba(245,158,11,.15);}
select.form-input{
    cursor:pointer;
    height:48px;
    appearance:none;
    -webkit-appearance:none;
    -moz-appearance:none;
    background-color:#f8fafc;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2.5' stroke='%23f59e0b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19.5 8.25l-7.5 7.5-7.5-7.5' /%3E%3C/svg%3E");
    background-repeat:no-repeat;
    background-position:right 14px center;
    background-size:18px 18px;
    padding-right:42px;
    font-weight:700;
    font-size:.95rem;
}
select.form-input option{background-color:#fff;color:#1e293b;font-weight:600;padding:10px;}
.field-etc-wrap{margin-top:10px;display:none;}
.field-etc-wrap.show{display:block;}

.cut-section{margin-top:22px;}
.cut-section-hd{display:flex;align-items:center;gap:9px;background:#fffbeb;border-radius:9px;padding:9px 14px;margin-bottom:10px;}
.cut-section-hd .sec-icon{width:30px;height:30px;background:#f59e0b;border-radius:7px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.cut-section-hd .sec-icon i{color:#fff;font-size:.8rem;}
.cut-section-hd .sec-title{font-weight:700;font-size:.88rem;color:#78350f;}

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
.section-label i{color:#f59e0b;}
.upload-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.upload-box{border:2px dashed #e2e8f0;border-radius:10px;padding:16px 12px;text-align:center;cursor:pointer;transition:border-color .2s,background .2s;background:#f8fafc;position:relative;}
.upload-box:hover{border-color:#f59e0b;background:#fffbeb;}
.upload-box input[type="file"]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;}
.upload-box .up-icon{font-size:1.6rem;color:#94a3b8;margin-bottom:6px;}
.upload-box .up-title{font-weight:700;font-size:.82rem;color:#334155;margin-bottom:2px;}
.upload-box .up-hint{font-size:.72rem;color:#94a3b8;}
.upload-box.has-file{border-color:#f59e0b;background:#fffbeb;}
.upload-box.has-file .up-icon{color:#f59e0b;}
.upload-box.has-file .up-hint{color:#d97706;font-weight:600;}
.up-preview{display:none;margin-top:8px;}
.up-preview img{width:100%;max-height:100px;object-fit:cover;border-radius:6px;border:1px solid #fcd34d;}
.upload-box.has-file .up-preview{display:block;}

/* รูปเดิม */
.current-img-wrap{display:flex;gap:10px;margin-top:8px;flex-wrap:wrap;}
.current-img-box{text-align:center;}
.current-img-box img{width:72px;height:72px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;cursor:pointer;transition:transform .15s;}
.current-img-box img:hover{transform:scale(1.1);}
.current-img-label{font-size:.7rem;color:#94a3b8;margin-top:3px;}

.btn-save{width:100%;padding:13px;background:#f59e0b;color:#fff;border:none;border-radius:9px;font-size:1rem;font-weight:700;font-family:'Sarabun',sans-serif;cursor:pointer;margin-top:22px;display:flex;align-items:center;justify-content:center;gap:7px;transition:background .15s;}
.btn-save:hover{background:#d97706;}
.btn-cancel{display:block;text-align:center;margin-top:10px;padding:11px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:.9rem;font-weight:700;font-family:'Sarabun',sans-serif;color:#64748b;text-decoration:none;background:#fff;transition:background .15s;}
.btn-cancel:hover{background:#f8fafc;}

/* Lightbox styles removed - using global swipe lightbox instead */

@media(max-width:640px){
    .check-row-top{flex-direction:column;align-items:flex-start;}
    .radio-group{width:100%;}
    .radio-label{flex:1;justify-content:center;}
    .upload-grid{grid-template-columns:1fr;}
}
.swal2-popup.sa2-th, .swal2-popup { font-family:'Sarabun',sans-serif !important; }
</style>
<div class="content-wrapper">
<div class="page-wrap">

    <div class="page-header">
        <div class="page-header-icon"><i class="fa-solid fa-pen-to-square"></i></div>
        <div>
            <div class="page-header-title">แก้ไขผลตรวจรถตัดอ้อย</div>
            <div class="page-header-sub">เบอร์รถ: <?php echo htmlspecialchars($sess_data['harvester_number']); ?> · session #<?php echo $session_id; ?></div>
        </div>
    </div>

    <?php if(!empty($message) && $status==='error'): ?>
    <div class="alert alert-error">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?php echo htmlspecialchars($message); ?></span>
    </div>
    <?php endif; ?>

    <!-- badge รถตัด -->
    <div class="truck-badge-bar">
        <div class="truck-badge-l">
            <div class="truck-badge-icon"><i class="fa-solid fa-tractor"></i></div>
            <div>
                <div class="truck-badge-label">แก้ไขผลตรวจรถตัดเบอร์</div>
                <div class="truck-badge-num"><?php echo htmlspecialchars($sess_data['harvester_number']); ?></div>
            </div>
        </div>
        <a href="harvester.php" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> กลับ
        </a>
    </div>

    <div class="form-card">
        <div class="form-card-header">
            <i class="fa-solid fa-pen-to-square"></i>
            <span>แก้ไขแบบฟอร์มผลการตรวจสอบ</span>
        </div>
        <div class="form-card-body">

            <div class="meta-bar">
                <div class="meta-chip"><i class="fa-solid fa-user"></i><span><?php echo htmlspecialchars($sess_data['emp_name']); ?></span></div>
                <span class="meta-sep">|</span>
                <div class="meta-chip"><i class="fa-solid fa-location-dot"></i><span><?php echo htmlspecialchars($sess_data['emp_unit']); ?></span></div>
                <span class="meta-sep">|</span>
                <?php
                    $dt = strtotime($sess_data['checked_at']);
                    $d=(int)date('d',$dt); $mo=(int)date('m',$dt); $yr=(int)date('Y',$dt)+543;
                    echo '<div class="meta-chip"><i class="fa-solid fa-calendar-day"></i><span>'.$d.' '.$thai_months[$mo].' '.$yr.' '.date('H:i',$dt).' น.</span></div>';
                ?>
            </div>

            <form action="harvester_edit.php?id=<?php echo $session_id; ?>" method="POST" enctype="multipart/form-data" id="editForm">
                <input type="hidden" name="action" value="edit_check">

                <!-- สภาพแปลง -->
                <div class="section-label"><i class="fa-solid fa-leaf"></i> สภาพแปลงอ้อยขณะรถตัดทำงาน</div>
                <label class="field-label">เลือกสภาพแปลง <span class="req">*</span></label>
                <select name="field_condition" id="field_condition" class="form-input" required onchange="toggleEtc(this.value)">
                    <option value="">-- กรุณาเลือก --</option>
                    <?php foreach($field_items as $fi):
                        $sel = ($fi['item_name_field']===$sess_data['field_condition']) ? 'selected' : '';
                    ?>
                    <option value="<?php echo htmlspecialchars($fi['item_name_field']); ?>" <?php echo $sel; ?>>
                        <?php echo htmlspecialchars($fi['item_name_field']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <div class="field-etc-wrap" id="etc_wrap">
                    <label class="field-label" style="margin-top:10px;">ระบุรายละเอียดเพิ่มเติม <span class="req">*</span></label>
                    <input type="text" name="field_condition_etc" id="field_condition_etc" class="form-input"
                           placeholder="โปรดระบุสภาพแปลง..."
                           value="<?php echo htmlspecialchars($sess_data['field_condition_etc'] ?? ''); ?>">
                </div>

                <!-- รายการตรวจ -->
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
                        $iid      = $item['item_id'];
                        $cur_pass = $current_results[$iid]['pass'] ?? 1;
                        $cur_note = $current_results[$iid]['note'] ?? '';
                    ?>
                    <div class="check-row">
                        <div class="check-row-top">
                            <div class="check-row-label"><?php echo htmlspecialchars($item['item_name_cut']); ?></div>
                            <div class="radio-group">
                                <input type="radio" class="radio-btn" name="item_<?php echo $iid; ?>"
                                       id="item_<?php echo $iid; ?>_ok" value="1"
                                       <?php echo $cur_pass ? 'checked' : ''; ?>
                                       onchange="toggleNote(<?php echo $iid; ?>,1)">
                                <label class="radio-label ok" for="item_<?php echo $iid; ?>_ok">
                                    <i class="fa-solid fa-check"></i> ผ่าน/ปกติ
                                </label>
                                <input type="radio" class="radio-btn" name="item_<?php echo $iid; ?>"
                                       id="item_<?php echo $iid; ?>_fail" value="0"
                                       <?php echo !$cur_pass ? 'checked' : ''; ?>
                                       onchange="toggleNote(<?php echo $iid; ?>,0)">
                                <label class="radio-label bad" for="item_<?php echo $iid; ?>_fail">
                                    <i class="fa-solid fa-xmark"></i> ไม่ผ่าน/ต้องแก้ไข
                                </label>
                            </div>
                        </div>
                        <div class="note-wrap <?php echo !$cur_pass ? 'show' : ''; ?>" id="note_wrap_<?php echo $iid; ?>">
                            <div class="note-label"><i class="fa-solid fa-triangle-exclamation"></i> ระบุรายละเอียด/สาเหตุที่ต้องแก้ไข</div>
                            <textarea class="note-input" name="note_item_<?php echo $iid; ?>"
                                      placeholder="เช่น ใบมีดสึกหรอ, ต้องเปลี่ยนอะไหล่..."><?php echo htmlspecialchars($cur_note); ?></textarea>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>

                <!-- ภาพ -->
                <div class="section-label"><i class="fa-solid fa-camera"></i> ภาพประกอบ</div>
                <div style="font-size:.75rem;color:#64748b;margin-bottom:6px;">
                    <i class="fa-solid fa-info-circle"></i> อัปโหลดรูปใหม่เพื่อเปลี่ยนแทนรูปเดิม (ไม่บังคับ)
                </div>

                <?php if(!empty($sess_data['img_harvester']) || !empty($sess_data['img_field'])): ?>
                <div class="current-img-wrap rp-list" style="margin-bottom:12px; display:flex; gap:10px;">
                    <?php if(!empty($sess_data['img_harvester'])): ?>
                    <div class="current-img-box">
                        <img src="<?php echo htmlspecialchars($sess_data['img_harvester']); ?>" class="rp-thumb" style="cursor:pointer;" title="รูปรถตัดปัจจุบัน">
                        <div class="current-img-label">รูปรถตัด (ปัจจุบัน)</div>
                    </div>
                    <?php endif; ?>
                    <?php if(!empty($sess_data['img_field'])): ?>
                    <div class="current-img-box">
                        <img src="<?php echo htmlspecialchars($sess_data['img_field']); ?>" class="rp-thumb" style="cursor:pointer;" title="รูปแปลงอ้อยปัจจุบัน">
                        <div class="current-img-label">รูปแปลง (ปัจจุบัน)</div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="upload-grid">
                    <div class="upload-box" id="box_harvester">
                        <input type="file" name="img_harvester" id="img_harvester"
                               accept="image/jpeg,image/png,image/webp"
                               onchange="previewImg(this,'box_harvester','prev_harvester')">
                        <div class="up-icon"><i class="fa-solid fa-tractor"></i></div>
                        <div class="up-title">เปลี่ยนรูปรถตัด</div>
                        <div class="up-hint">JPG / PNG / WEBP ไม่เกิน 10MB</div>
                        <div class="up-preview" id="prev_harvester"><img src="" alt="preview"></div>
                    </div>
                    <div class="upload-box" id="box_field">
                        <input type="file" name="img_field" id="img_field"
                               accept="image/jpeg,image/png,image/webp"
                               onchange="previewImg(this,'box_field','prev_field')">
                        <div class="up-icon"><i class="fa-solid fa-seedling"></i></div>
                        <div class="up-title">เปลี่ยนรูปแปลงอ้อย</div>
                        <div class="up-hint">JPG / PNG / WEBP ไม่เกิน 10MB</div>
                        <div class="up-preview" id="prev_field"><img src="" alt="preview"></div>
                    </div>
                </div>

                <button type="submit" class="btn-save" id="saveBtn">
                    <i class="fa-solid fa-floppy-disk"></i> บันทึกการแก้ไข
                </button>
            </form>
            <a href="harvester.php" class="btn-cancel">
                <i class="fa-solid fa-xmark"></i> ยกเลิก ไม่บันทึก
            </a>
        </div>
    </div>

</div>
</div>

<!-- Lightbox HTML removed - using global swipe lightbox instead -->

<?php include 'includes/nav_u_footer.php'; ?>

<script>
function toggleEtc(val){
    const wrap=document.getElementById('etc_wrap');
    const inp=document.getElementById('field_condition_etc');
    if(val==='อื่นๆ'){ wrap.classList.add('show'); inp.required=true; }
    else { wrap.classList.remove('show'); inp.required=false; }
}
// ตรวจสอบค่าเดิมตอนโหลดหน้า
(function(){
    const sel=document.getElementById('field_condition');
    if(sel) toggleEtc(sel.value);
})();

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

// openLightbox/closeLightbox and Escape listeners removed - handled globally by nav_script.js

const editForm = document.getElementById('editForm');
if(editForm){
    editForm.addEventListener('submit', function(){
        const btn = document.getElementById('saveBtn');
        if(btn){ btn.disabled=true; btn.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i> กำลังบันทึก...'; }
    });
}
</script>
</body>
</html>