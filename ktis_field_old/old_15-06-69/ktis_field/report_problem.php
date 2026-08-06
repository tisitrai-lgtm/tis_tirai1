<?php
/**
 * report_problem.php — แจ้งปัญหาเว็บไซต์และงานไร่
 */
date_default_timezone_set('Asia/Bangkok');
require_once 'config.php';
session_start();

if(!isset($_SESSION['emp_id'])){ header("location: login.php"); exit; }

$message = "";
$status  = "";

if(isset($_SESSION['flash_msg'])){
    $message = $_SESSION['flash_msg'];
    $status  = $_SESSION['flash_status'];
    unset($_SESSION['flash_msg'], $_SESSION['flash_status']);
}

// ── ประเภทปัญหาแยกหมวด ──
$categories = [
    'system' => [
        'label' => 'ปัญหาระบบ / เว็บไซต์',
        'icon'  => 'fa-computer',
        'color' => '#3b82f6',
        'bg'    => '#eff6ff',
        'items' => [
            'เข้าสู่ระบบไม่ได้',
            'หน้าเว็บแสดงผลผิดพลาด',
            'บันทึกข้อมูลไม่ได้',
            'ข้อมูลแสดงไม่ถูกต้อง',
            'การแจ้งเตือนไม่ทำงาน',
            'ระบบช้า / ค้าง',
            'อื่นๆ (ระบบ)',
        ],
    ],
    'field' => [
        'label' => 'ปัญหางานไร่ / อ้อย',
        'icon'  => 'fa-seedling',
        'color' => '#10b981',
        'bg'    => '#f0fdf4',
        'items' => [
            'รถตัดอ้อยขัดข้อง',
            'อ้อยล้ม / เสียหาย',
            'ปัญหาน้ำท่วม / น้ำแช่ขัง',
            'หญ้ารกกีดขวาง',
            'ปัญหาเส้นทางเข้าแปลง',
            'ปัญหาการชั่งน้ำหนัก',
            'อื่นๆ (งานไร่)',
        ],
    ],
];

// ── POST บันทึก ──
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $category    = trim($_POST['category']    ?? '');
    $prob_type   = trim($_POST['prob_type']   ?? '');
    $prob_detail = trim($_POST['prob_detail'] ?? '');
    $priority    = trim($_POST['priority']    ?? 'normal');

    if(empty($category) || empty($prob_type) || empty($prob_detail)){
        $status  = 'error';
        $message = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } else {
        try {
            // อัปโหลดรูปภาพ (ถ้ามี)
            $img_path = null;
            if(!empty($_FILES['img_report']['name'])){
                $file    = $_FILES['img_report'];
                $allowed = ['image/jpeg','image/jpg','image/png','image/webp'];
                if(in_array($file['type'], $allowed) && $file['size'] <= 10*1024*1024){
                    $dir = __DIR__ . '/im_report/' . date('Y-m-d') . '/';
                    if(!is_dir($dir)) mkdir($dir, 0755, true);
                    // บีบอัด GD 800px/75%
                    $src = match($file['type']){
                        'image/png'  => imagecreatefrompng($file['tmp_name']),
                        'image/webp' => imagecreatefromwebp($file['tmp_name']),
                        default      => imagecreatefromjpeg($file['tmp_name']),
                    };
                    if($src){
                        $ow = imagesx($src); $oh = imagesy($src);
                        if($ow > 800){
                            $nw = 800; $nh = (int)round($oh * 800 / $ow);
                            $dst = imagecreatetruecolor($nw, $nh);
                            imagecopyresampled($dst,$src,0,0,0,0,$nw,$nh,$ow,$oh);
                            imagedestroy($src); $src = $dst;
                        }
                        $fname = time().'_'.mt_rand(1000,9999).'.jpg';
                        imagejpeg($src, $dir.$fname, 75);
                        imagedestroy($src);
                        $img_path = 'im_report/'.date('Y-m-d').'/'.$fname;
                    }
                }
            }

            $stmt = $conn->prepare(
                "INSERT INTO problem_reports
                    (emp_id, category, prob_type, prob_detail, priority, img_path, status, created_at)
                 VALUES
                    (:emp_id, :category, :prob_type, :prob_detail, :priority, :img_path, 'pending', NOW())"
            );
            $stmt->execute([
                ':emp_id'      => $_SESSION['emp_id'],
                ':category'    => $category,
                ':prob_type'   => $prob_type,
                ':prob_detail' => $prob_detail,
                ':priority'    => $priority,
                ':img_path'    => $img_path,
            ]);

            // แจ้งเตือน admin ทุกคน
            $admins = $conn->query("SELECT emp_id FROM employee WHERE emp_level='a'")->fetchAll();
            $noti_text = '['.($category==='system'?'ระบบ':'งานไร่').'] '.$_SESSION['emp_name'].' แจ้งปัญหา: '.$prob_type;
            $stmt_n = $conn->prepare(
                "INSERT INTO notifications (emp_id, noti_text, is_read, created_at)
                 VALUES (:emp_id, :text, 0, NOW())"
            );
            foreach($admins as $adm){
                $stmt_n->execute([':emp_id'=>$adm['emp_id'], ':text'=>$noti_text]);
            }

            $_SESSION['flash_status'] = 'success';
            $_SESSION['flash_msg']    = 'แจ้งปัญหาเรียบร้อยแล้ว ทีมงานจะดำเนินการโดยเร็ว';
            header('Location: report_problem.php'); exit;

        } catch(Exception $e){
            $status  = 'error';
            $message = 'เกิดข้อผิดพลาด: '.$e->getMessage();
        }
    }
}

// ── ดึงประวัติการแจ้งปัญหาของตัวเอง ──
$my_reports = [];
try {
    $stmt_r = $conn->prepare(
        "SELECT * FROM problem_reports
         WHERE emp_id = :emp_id
         ORDER BY report_id DESC LIMIT 20"
    );
    $stmt_r->execute([':emp_id' => $_SESSION['emp_id']]);
    $my_reports = $stmt_r->fetchAll();
} catch(Exception $e){}

include 'includes/nav_u_header.php';

$thai_months=['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน',
              'กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>แจ้งปัญหา - KTIS SMART FIELD</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;}
body{font-family:'Sarabun',sans-serif;background:#f1f5f9;margin:0;}
.pw{max-width:760px;margin:24px auto;padding:0 14px 60px;}

/* page header */
.ph{display:flex;align-items:center;gap:12px;margin-bottom:20px;}
.ph-icon{width:46px;height:46px;background:linear-gradient(135deg,#e11d48,#be123c);border-radius:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.ph-icon i{color:#fff;font-size:1.3rem;}
.ph-title{font-size:1.15rem;font-weight:700;color:#1e293b;margin-bottom:2px;}
.ph-sub{font-size:.8rem;color:#64748b;}

/* alert */
.alert{display:flex;align-items:flex-start;gap:10px;padding:13px 16px;border-radius:9px;margin-bottom:18px;font-weight:600;font-size:.9rem;}
.alert-success{background:#d1fae5;border:1px solid #a7f3d0;color:#065f46;}
.alert-error{background:#fee2e2;border:1px solid #fecaca;color:#991b1b;}
.alert i{margin-top:2px;flex-shrink:0;}

/* form card */
.form-card{background:#fff;border-radius:14px;border:.5px solid #e2e8f0;overflow:hidden;margin-bottom:28px;}
.card-hd{background:#1e293b;padding:14px 20px;display:flex;align-items:center;gap:10px;border-bottom:3px solid #e11d48;}
.card-hd i{color:#e11d48;}
.card-hd span{color:#f8fafc;font-weight:700;font-size:.95rem;}
.card-bd{padding:20px;}

/* meta bar */
.meta-bar{display:flex;gap:10px;flex-wrap:wrap;background:#f8fafc;border:1px solid #e2e8f0;border-radius:9px;padding:11px 14px;margin-bottom:20px;}
.meta-chip{display:inline-flex;align-items:center;gap:6px;font-size:.82rem;font-weight:600;color:#475569;}
.meta-chip i{color:#94a3b8;font-size:.85rem;}
.meta-sep{color:#e2e8f0;}

/* category selector */
.cat-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px;}
.cat-card{
    border:2px solid #e2e8f0;border-radius:11px;padding:16px;cursor:pointer;
    text-align:center;transition:all .15s;background:#f8fafc;
}
.cat-card:hover{border-color:#cbd5e1;background:#f1f5f9;}
.cat-card.selected-system{border-color:#3b82f6;background:#eff6ff;}
.cat-card.selected-field{border-color:#10b981;background:#f0fdf4;}
.cat-card .cat-icon{font-size:1.8rem;margin-bottom:8px;}
.cat-card .cat-label{font-weight:700;font-size:.9rem;color:#1e293b;}
.cat-card .cat-sub{font-size:.75rem;color:#64748b;margin-top:3px;}

/* section label */
.sec-label{font-weight:700;font-size:.85rem;color:#1e293b;display:flex;align-items:center;gap:7px;margin:18px 0 10px;padding-bottom:8px;border-bottom:1px solid #f1f5f9;}
.sec-label i{color:#e11d48;}

/* prob type chips */
.prob-chips{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:4px;}
.prob-chip{
    padding:7px 14px;border:1.5px solid #e2e8f0;border-radius:20px;
    font-size:.83rem;font-weight:600;color:#475569;cursor:pointer;
    background:#f8fafc;transition:all .15s;
}
.prob-chip:hover{border-color:#94a3b8;color:#1e293b;}
.prob-chip.active-system{background:#3b82f6;color:#fff;border-color:#3b82f6;}
.prob-chip.active-field{background:#10b981;color:#fff;border-color:#10b981;}
input[name="prob_type"]{display:none;}

/* priority */
.priority-row{display:flex;gap:8px;margin-bottom:4px;}
.pri-btn{
    flex:1;padding:8px;border:1.5px solid #e2e8f0;border-radius:8px;
    font-size:.82rem;font-weight:700;font-family:'Sarabun',sans-serif;
    cursor:pointer;background:#f8fafc;color:#475569;text-align:center;
    transition:all .15s;
}
.pri-btn:hover{border-color:#94a3b8;}
.pri-btn.active-low{background:#d1fae5;color:#065f46;border-color:#10b981;}
.pri-btn.active-normal{background:#e0f2fe;color:#0369a1;border-color:#3b82f6;}
.pri-btn.active-high{background:#fff7ed;color:#c2410c;border-color:#f97316;}
.pri-btn.active-urgent{background:#fee2e2;color:#991b1b;border-color:#e11d48;}
input[name="priority"]{display:none;}

/* inputs */
.field-label{display:block;font-weight:700;font-size:.83rem;color:#374151;margin-bottom:7px;}
.field-label .req{color:#e11d48;}
.form-input{width:100%;padding:11px 13px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.95rem;font-family:'Sarabun',sans-serif;background:#f8fafc;color:#1e293b;outline:none;transition:border-color .15s;resize:vertical;}
.form-input:focus{border-color:#e11d48;background:#fff;}

/* upload */
.upload-box{border:2px dashed #e2e8f0;border-radius:10px;padding:16px;text-align:center;cursor:pointer;transition:all .2s;background:#f8fafc;position:relative;margin-top:4px;}
.upload-box:hover{border-color:#10b981;background:#f0fdf4;}
.upload-box input{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;}
.upload-box .up-icon{font-size:1.5rem;color:#94a3b8;margin-bottom:6px;}
.upload-box .up-title{font-weight:700;font-size:.82rem;color:#334155;}
.upload-box .up-hint{font-size:.72rem;color:#94a3b8;margin-top:2px;}
.upload-box.has-file{border-color:#10b981;background:#f0fdf4;}
.upload-box.has-file .up-icon{color:#10b981;}
.up-preview{display:none;margin-top:8px;}
.up-preview img{width:100%;max-height:120px;object-fit:cover;border-radius:7px;border:1px solid #a7f3d0;}
.upload-box.has-file .up-preview{display:block;}

/* submit */
.btn-submit{width:100%;padding:13px;background:linear-gradient(135deg,#e11d48,#be123c);color:#fff;border:none;border-radius:9px;font-size:1rem;font-weight:700;font-family:'Sarabun',sans-serif;cursor:pointer;margin-top:20px;display:flex;align-items:center;justify-content:center;gap:7px;transition:opacity .15s;}
.btn-submit:hover{opacity:.9;}
.btn-submit:disabled{opacity:.6;cursor:not-allowed;}

/* ── history ── */
.hist-card{background:#fff;border-radius:14px;border:.5px solid #e2e8f0;overflow:hidden;}
.hist-hd{background:#1e293b;padding:13px 18px;display:flex;align-items:center;justify-content:space-between;border-bottom:3px solid #64748b;}
.hist-hd-l{display:flex;align-items:center;gap:8px;}
.hist-hd-l i{color:#94a3b8;}
.hist-hd-l span{color:#f8fafc;font-weight:700;font-size:.92rem;}
.cnt-badge{background:rgba(255,255,255,.12);color:#cbd5e1;font-size:.73rem;font-weight:700;padding:2px 9px;border-radius:10px;}

.rp-item{padding:14px 16px;border-bottom:1px solid #f1f5f9;display:flex;gap:12px;align-items:flex-start;}
.rp-item:last-child{border-bottom:none;}
.rp-cat-icon{width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:.9rem;}
.rp-cat-system{background:#eff6ff;color:#3b82f6;}
.rp-cat-field{background:#f0fdf4;color:#10b981;}
.rp-body{flex:1;min-width:0;}
.rp-type{font-weight:700;font-size:.88rem;color:#1e293b;margin-bottom:2px;}
.rp-detail{font-size:.8rem;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%;}
.rp-meta{display:flex;gap:8px;flex-wrap:wrap;margin-top:5px;align-items:center;}
.rp-date{font-size:.72rem;color:#94a3b8;}

/* status badge */
.st-badge{font-size:.7rem;font-weight:700;padding:2px 8px;border-radius:10px;display:inline-flex;align-items:center;gap:3px;}
.st-pending{background:#fff7ed;color:#c2410c;border:1px solid #fed7aa;}
.st-inprogress{background:#e0f2fe;color:#0369a1;border:1px solid #bae6fd;}
.st-done{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;}

/* priority badge */
.pr-badge{font-size:.7rem;font-weight:700;padding:2px 7px;border-radius:10px;}
.pr-low{background:#f1f5f9;color:#475569;}
.pr-normal{background:#e0f2fe;color:#0369a1;}
.pr-high{background:#fff7ed;color:#c2410c;}
.pr-urgent{background:#fee2e2;color:#991b1b;}

/* thumb */
.rp-thumb{width:40px;height:40px;object-fit:cover;border-radius:6px;border:1px solid #e2e8f0;cursor:pointer;flex-shrink:0;}

/* lightbox */
.lightbox{display:none;position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:9999;align-items:center;justify-content:center;}
.lightbox.show{display:flex;}
.lightbox img{max-width:90vw;max-height:88vh;border-radius:10px;}
.lb-close{position:absolute;top:18px;right:22px;color:#fff;font-size:1.8rem;cursor:pointer;background:none;border:none;}

.empty-hist{text-align:center;padding:36px;color:#94a3b8;}
.empty-hist i{font-size:2rem;display:block;margin-bottom:8px;}

@media(max-width:480px){
    .cat-grid{grid-template-columns:1fr 1fr;}
    .priority-row{flex-wrap:wrap;}
    .pri-btn{flex:calc(50% - 4px);}
}
</style>
</head>
<body>
<div class="content-wrapper">
<div class="pw">

<div class="ph">
    <div class="ph-icon"><i class="fa-solid fa-circle-exclamation"></i></div>
    <div>
        <div class="ph-title">แจ้งปัญหา</div>
        <div class="ph-sub">แจ้งปัญหาระบบเว็บไซต์หรืองานไร่อ้อย — ทีมงานจะดำเนินการโดยเร็ว</div>
    </div>
</div>

<?php if(!empty($message)): ?>
<div class="alert <?php echo $status==='success'?'alert-success':'alert-error'; ?>">
    <i class="fa-solid <?php echo $status==='success'?'fa-circle-check':'fa-circle-exclamation'; ?>"></i>
    <span><?php echo htmlspecialchars($message); ?></span>
</div>
<?php endif; ?>

<!-- Form -->
<div class="form-card">
    <div class="card-hd">
        <i class="fa-solid fa-pen-to-square"></i>
        <span>แบบฟอร์มแจ้งปัญหา</span>
    </div>
    <div class="card-bd">

        <!-- Meta -->
        <div class="meta-bar">
            <div class="meta-chip"><i class="fa-solid fa-user"></i><span><?php echo htmlspecialchars($_SESSION['emp_name']); ?></span></div>
            <span class="meta-sep">|</span>
            <div class="meta-chip"><i class="fa-solid fa-location-dot"></i><span><?php echo htmlspecialchars($_SESSION['emp_unit']); ?></span></div>
            <span class="meta-sep">|</span>
            <div class="meta-chip"><i class="fa-solid fa-calendar-day"></i><span id="thai-date-now"></span></div>
        </div>

        <form action="report_problem.php" method="POST" enctype="multipart/form-data" id="reportForm">

            <!-- ── STEP 1: เลือกหมวด ── -->
            <div class="sec-label"><i class="fa-solid fa-layer-group"></i> เลือกหมวดหมู่ปัญหา</div>
            <input type="hidden" name="category" id="cat-input" required>

            <div class="cat-grid">
                <div class="cat-card" id="cat-system" onclick="selectCat('system')">
                    <div class="cat-icon" style="color:#3b82f6;"><i class="fa-solid fa-computer"></i></div>
                    <div class="cat-label">ระบบ / เว็บไซต์</div>
                    <div class="cat-sub">Bug, ใช้งานไม่ได้, แสดงผลผิด</div>
                </div>
                <div class="cat-card" id="cat-field" onclick="selectCat('field')">
                    <div class="cat-icon" style="color:#10b981;"><i class="fa-solid fa-seedling"></i></div>
                    <div class="cat-label">งานไร่ / อ้อย</div>
                    <div class="cat-sub">รถตัด, แปลง, เส้นทาง</div>
                </div>
            </div>

            <!-- ── STEP 2: ประเภทปัญหา (โผล่หลังเลือกหมวด) ── -->
            <div id="step2" style="display:none;">
                <div class="sec-label"><i class="fa-solid fa-tags"></i> ประเภทปัญหา <span class="req" style="color:#e11d48;">*</span></div>
                <input type="hidden" name="prob_type" id="prob-type-input" required>
                <div class="prob-chips" id="prob-chips"></div>

                <!-- ── STEP 3: รายละเอียด ── -->
                <div class="sec-label" style="margin-top:20px;"><i class="fa-solid fa-align-left"></i> รายละเอียด <span class="req" style="color:#e11d48;">*</span></div>
                <textarea name="prob_detail" class="form-input" rows="4"
                          placeholder="อธิบายปัญหาให้ละเอียด เช่น เกิดขึ้นที่หน้าไหน ทำอะไรอยู่ก่อนเกิดปัญหา..." required></textarea>

                <!-- ── ระดับความเร่งด่วน ── -->
                <div class="sec-label" style="margin-top:18px;"><i class="fa-solid fa-gauge-high"></i> ระดับความเร่งด่วน</div>
                <input type="hidden" name="priority" id="priority-input" value="normal">
                <div class="priority-row">
                    <button type="button" class="pri-btn" id="pri-low"    onclick="selectPri('low')">
                        <i class="fa-solid fa-arrow-down"></i> ต่ำ
                    </button>
                    <button type="button" class="pri-btn active-normal" id="pri-normal" onclick="selectPri('normal')">
                        <i class="fa-solid fa-minus"></i> ปกติ
                    </button>
                    <button type="button" class="pri-btn" id="pri-high"   onclick="selectPri('high')">
                        <i class="fa-solid fa-arrow-up"></i> สูง
                    </button>
                    <button type="button" class="pri-btn" id="pri-urgent" onclick="selectPri('urgent')">
                        <i class="fa-solid fa-triangle-exclamation"></i> ด่วนมาก
                    </button>
                </div>

                <!-- ── แนบรูป ── -->
                <div class="sec-label" style="margin-top:18px;"><i class="fa-solid fa-camera"></i> แนบรูปภาพ (ไม่บังคับ)</div>
                <div class="upload-box" id="upload-box">
                    <input type="file" name="img_report" accept="image/jpeg,image/png,image/webp"
                           onchange="previewImg(this)">
                    <div class="up-icon"><i class="fa-solid fa-image"></i></div>
                    <div class="up-title">คลิกหรือลากรูปมาวาง</div>
                    <div class="up-hint">JPG / PNG / WEBP ไม่เกิน 10MB (จะบีบอัดอัตโนมัติ)</div>
                    <div class="up-preview" id="up-preview"><img src="" alt="preview"></div>
                </div>

                <button type="submit" class="btn-submit" id="submitBtn">
                    <i class="fa-solid fa-paper-plane"></i> ส่งแจ้งปัญหา
                </button>
            </div>

        </form>
    </div>
</div>

<!-- History -->
<div class="hist-card">
    <div class="hist-hd">
        <div class="hist-hd-l">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>ประวัติการแจ้งปัญหาของฉัน</span>
        </div>
        <span class="cnt-badge"><?php echo count($my_reports); ?> รายการ</span>
    </div>

    <?php if(empty($my_reports)): ?>
        <div class="empty-hist">
            <i class="fa-solid fa-clipboard-list"></i>
            ยังไม่มีประวัติการแจ้งปัญหา
        </div>
    <?php else: ?>
        <?php foreach($my_reports as $r):
            $d  = (int)date('d', strtotime($r['created_at']));
            $mo = (int)date('m', strtotime($r['created_at']));
            $yr = (int)date('Y', strtotime($r['created_at'])) + 543;
            $date_str = $d.' '.$thai_months[$mo].' '.$yr.' '.date('H:i น.', strtotime($r['created_at']));
            $st = $r['status'] ?? 'pending';
            $pr = $r['priority'] ?? 'normal';
            $st_map = ['pending'=>['st-pending','fa-clock','รอดำเนินการ'],'inprogress'=>['st-inprogress','fa-gear','กำลังดำเนินการ'],'done'=>['st-done','fa-check','แก้ไขแล้ว']];
            $pr_map = ['low'=>['pr-low','ต่ำ'],'normal'=>['pr-normal','ปกติ'],'high'=>['pr-high','สูง'],'urgent'=>['pr-urgent','ด่วนมาก']];
            [$st_cls,$st_ico,$st_txt] = $st_map[$st] ?? $st_map['pending'];
            [$pr_cls,$pr_txt] = $pr_map[$pr] ?? $pr_map['normal'];
        ?>
        <div class="rp-item">
            <div class="rp-cat-icon <?php echo $r['category']==='system'?'rp-cat-system':'rp-cat-field'; ?>">
                <i class="fa-solid <?php echo $r['category']==='system'?'fa-computer':'fa-seedling'; ?>"></i>
            </div>
            <div class="rp-body">
                <div class="rp-type"><?php echo htmlspecialchars($r['prob_type']); ?></div>
                <div class="rp-detail"><?php echo htmlspecialchars($r['prob_detail']); ?></div>
                <div class="rp-meta">
                    <span class="rp-date"><i class="fa-regular fa-calendar"></i> <?php echo $date_str; ?></span>
                    <span class="st-badge <?php echo $st_cls; ?>"><i class="fa-solid <?php echo $st_ico; ?>"></i> <?php echo $st_txt; ?></span>
                    <span class="pr-badge <?php echo $pr_cls; ?>"><?php echo $pr_txt; ?></span>
                </div>
            </div>
            <?php if(!empty($r['img_path'])): ?>
            <img class="rp-thumb" src="<?php echo htmlspecialchars($r['img_path']); ?>"
                 onclick="openLB(this.src)" alt="ภาพประกอบ">
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</div>
</div>

<!-- Lightbox -->
<div class="lightbox" id="lb" onclick="closeLB()">
    <button class="lb-close"><i class="fa-solid fa-xmark"></i></button>
    <img src="" id="lb-img" alt="">
</div>

<?php include 'includes/nav_u_footer.php'; ?>

<script>
// วันที่ไทย
(function(){
    const m = ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน',
               'กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
    const now = new Date();
    const el = document.getElementById('thai-date-now');
    if(el) el.textContent = now.getDate()+' '+m[now.getMonth()+1]+' '+(now.getFullYear()+543);
})();

// ── ข้อมูลประเภทปัญหา ──
const catItems = {
    system: ['เข้าสู่ระบบไม่ได้','หน้าเว็บแสดงผลผิดพลาด','บันทึกข้อมูลไม่ได้',
             'ข้อมูลแสดงไม่ถูกต้อง','การแจ้งเตือนไม่ทำงาน','ระบบช้า / ค้าง','อื่นๆ (ระบบ)'],
    field:  ['รถตัดอ้อยขัดข้อง','อ้อยล้ม / เสียหาย','ปัญหาน้ำท่วม / น้ำแช่ขัง',
             'หญ้ารกกีดขวาง','ปัญหาเส้นทางเข้าแปลง','ปัญหาการชั่งน้ำหนัก','อื่นๆ (งานไร่)'],
};

let currentCat = null;

function selectCat(cat) {
    currentCat = cat;
    document.getElementById('cat-input').value = cat;

    // highlight card
    document.getElementById('cat-system').className = 'cat-card' + (cat==='system' ? ' selected-system' : '');
    document.getElementById('cat-field').className  = 'cat-card' + (cat==='field'  ? ' selected-field'  : '');

    // render chips
    const chips = document.getElementById('prob-chips');
    chips.innerHTML = '';
    document.getElementById('prob-type-input').value = '';

    catItems[cat].forEach(item => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'prob-chip';
        btn.textContent = item;
        btn.onclick = () => selectProb(item, cat, btn);
        chips.appendChild(btn);
    });

    document.getElementById('step2').style.display = 'block';
    document.getElementById('step2').scrollIntoView({behavior:'smooth', block:'nearest'});
}

function selectProb(item, cat, el) {
    document.querySelectorAll('.prob-chip').forEach(c => c.className = 'prob-chip');
    el.classList.add('active-' + cat);
    document.getElementById('prob-type-input').value = item;
}

// ── Priority ──
const priLevels = ['low','normal','high','urgent'];
function selectPri(level) {
    priLevels.forEach(l => {
        const btn = document.getElementById('pri-' + l);
        btn.className = 'pri-btn';
    });
    document.getElementById('pri-' + level).classList.add('active-' + level);
    document.getElementById('priority-input').value = level;
}

// ── Preview รูป ──
function previewImg(input) {
    if(input.files && input.files[0]){
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('up-preview').querySelector('img').src = e.target.result;
            const box = document.getElementById('upload-box');
            box.classList.add('has-file');
            box.querySelector('.up-hint').textContent = input.files[0].name + ' (' + Math.round(input.files[0].size/1024) + ' KB)';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// ── Validate form ──
document.getElementById('reportForm').addEventListener('submit', function(e){
    if(!document.getElementById('cat-input').value){
        e.preventDefault();
        alert('กรุณาเลือกหมวดหมู่ปัญหาก่อน');
        return;
    }
    if(!document.getElementById('prob-type-input').value){
        e.preventDefault();
        alert('กรุณาเลือกประเภทปัญหา');
        return;
    }
    document.getElementById('submitBtn').disabled = true;
    document.getElementById('submitBtn').innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> กำลังส่ง...';
});

// ── Lightbox ──
function openLB(src){ document.getElementById('lb-img').src=src; document.getElementById('lb').classList.add('show'); }
function closeLB(){ document.getElementById('lb').classList.remove('show'); }
document.addEventListener('keydown', e => { if(e.key==='Escape') closeLB(); });
</script>
</body>
</html>