<?php
/**
 * problem_reports_admin.php — Admin จัดการรายการแจ้งปัญหา
 */
date_default_timezone_set('Asia/Bangkok');
require_once 'config.php';
session_start();

if(!isset($_SESSION['emp_id']) || $_SESSION['emp_level'] !== 'a'){
    header("location: login.php"); exit;
}

$message = "";
$status  = "";

if(isset($_SESSION['flash_msg'])){
    $message = $_SESSION['flash_msg'];
    $status  = $_SESSION['flash_status'];
    unset($_SESSION['flash_msg'], $_SESSION['flash_status']);
}

// ── POST: อัปเดตสถานะ + admin_note ──
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $report_id  = (int)($_POST['report_id'] ?? 0);
    $new_status = trim($_POST['new_status'] ?? '');
    $admin_note = trim($_POST['admin_note'] ?? '');

    if($report_id > 0 && in_array($new_status, ['pending','inprogress','done'])){
        try {
            $conn->prepare(
                "UPDATE problem_reports SET status=:st, admin_note=:note, updated_at=NOW() WHERE report_id=:id"
            )->execute([':st'=>$new_status, ':note'=>$admin_note?:null, ':id'=>$report_id]);

            // แจ้งเตือน user เจ้าของรายการ
            $row = $conn->prepare("SELECT emp_id, prob_type FROM problem_reports WHERE report_id=:id");
            $row->execute([':id'=>$report_id]);
            $rpt = $row->fetch();
            if($rpt){
                $st_th = ['pending'=>'รอดำเนินการ','inprogress'=>'กำลังดำเนินการ','done'=>'แก้ไขแล้ว'];
                $noti_txt = 'ปัญหา "'.$rpt['prob_type'].'" อัปเดตสถานะเป็น: '.($st_th[$new_status]??$new_status);
                $conn->prepare(
                    "INSERT INTO notifications (emp_id, noti_text, is_read, created_at) VALUES (:eid, :txt, 0, NOW())"
                )->execute([':eid'=>$rpt['emp_id'], ':txt'=>$noti_txt]);
            }

            $_SESSION['flash_status'] = 'success';
            $_SESSION['flash_msg']    = 'อัปเดตสถานะเรียบร้อยแล้ว';
        } catch(Exception $e){
            $_SESSION['flash_status'] = 'error';
            $_SESSION['flash_msg']    = 'เกิดข้อผิดพลาด: '.$e->getMessage();
        }
    }
    $qs = http_build_query(array_filter([
        'cat'      => $_POST['filter_cat']    ?? '',
        'status'   => $_POST['filter_status'] ?? '',
        'priority' => $_POST['filter_pri']    ?? '',
    ]));
    header("Location: problem_reports_admin.php".($qs?"?$qs":'')); exit;
}

// ── Filter ──
$f_cat    = $_GET['cat']      ?? '';
$f_status = $_GET['status']   ?? '';
$f_pri    = $_GET['priority'] ?? '';

$where  = "WHERE 1=1";
$params = [];
if($f_cat)    { $where .= " AND pr.category=:cat";    $params[':cat']=$f_cat; }
if($f_status) { $where .= " AND pr.status=:st";       $params[':st']=$f_status; }
if($f_pri)    { $where .= " AND pr.priority=:pri";    $params[':pri']=$f_pri; }

$stmt = $conn->prepare(
    "SELECT pr.*, e.emp_name, e.emp_unit
     FROM problem_reports pr
     JOIN employee e ON pr.emp_id = e.emp_id
     $where
     ORDER BY
       FIELD(pr.priority,'urgent','high','normal','low'),
       FIELD(pr.status,'pending','inprogress','done'),
       pr.created_at DESC"
);
$stmt->execute($params);
$reports = $stmt->fetchAll();

// ── สถิติ ──
$stat = $conn->query(
    "SELECT
        COUNT(*) AS total,
        SUM(status='pending')    AS pending,
        SUM(status='inprogress') AS inprogress,
        SUM(status='done')       AS done,
        SUM(priority='urgent')   AS urgent
     FROM problem_reports"
)->fetch();

$thai_months=['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน',
              'กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];

include 'includes/nav_u_header.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>จัดการแจ้งปัญหา (Admin) - KTIS</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;}
body{font-family:'Sarabun',sans-serif;background:#f1f5f9;margin:0;}
.pw{max-width:1100px;margin:24px auto;padding:0 14px 60px;}

.ph{display:flex;align-items:center;gap:12px;margin-bottom:22px;}
.ph-icon{width:46px;height:46px;background:linear-gradient(135deg,#e11d48,#be123c);border-radius:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.ph-icon i{color:#fff;font-size:1.3rem;}
.ph-title{font-size:1.15rem;font-weight:700;color:#1e293b;margin-bottom:2px;}
.ph-sub{font-size:.8rem;color:#64748b;}

/* alert */
.alert{display:flex;align-items:flex-start;gap:10px;padding:12px 16px;border-radius:9px;margin-bottom:18px;font-weight:600;font-size:.88rem;}
.alert-success{background:#d1fae5;border:1px solid #a7f3d0;color:#065f46;}
.alert-error{background:#fee2e2;border:1px solid #fecaca;color:#991b1b;}

/* stats */
.stats{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:20px;}
.stat-card{background:#fff;border-radius:11px;border:.5px solid #e2e8f0;padding:14px 16px;display:flex;align-items:center;gap:10px;}
.stat-ico{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.stat-num{font-size:1.4rem;font-weight:700;color:#1e293b;line-height:1;}
.stat-lbl{font-size:.73rem;color:#64748b;margin-top:2px;}

/* filter */
.filter-bar{background:#fff;border-radius:11px;border:.5px solid #e2e8f0;padding:13px 16px;margin-bottom:18px;display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;}
.fb-group{display:flex;flex-direction:column;gap:4px;}
.fb-lbl{font-size:.75rem;font-weight:700;color:#374151;}
.fb-input{padding:8px 11px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:.85rem;font-family:'Sarabun',sans-serif;background:#f8fafc;color:#1e293b;outline:none;min-width:130px;}
.fb-input:focus{border-color:#e11d48;}
select.fb-input{cursor:pointer;}
.btn-filter{padding:9px 18px;background:#e11d48;color:#fff;border:none;border-radius:7px;font-weight:700;font-size:.85rem;font-family:'Sarabun',sans-serif;cursor:pointer;white-space:nowrap;}
.btn-filter:hover{background:#be123c;}
.btn-reset{padding:9px 14px;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;border-radius:7px;font-weight:700;font-size:.85rem;font-family:'Sarabun',sans-serif;cursor:pointer;text-decoration:none;white-space:nowrap;}
.btn-reset:hover{background:#e2e8f0;}

/* report list */
.rp-list{display:flex;flex-direction:column;gap:14px;}

.rp-card{background:#fff;border-radius:13px;border:.5px solid #e2e8f0;overflow:hidden;transition:box-shadow .15s;}
.rp-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.07);}
.rp-card.urgent-card{border-left:4px solid #e11d48;}
.rp-card.high-card{border-left:4px solid #f97316;}

.rp-top{padding:14px 16px;display:flex;gap:12px;align-items:flex-start;cursor:pointer;}
.rp-cat-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1rem;}
.rp-cat-system{background:#eff6ff;color:#3b82f6;}
.rp-cat-field{background:#f0fdf4;color:#10b981;}

.rp-main{flex:1;min-width:0;}
.rp-type{font-weight:700;font-size:.92rem;color:#1e293b;margin-bottom:3px;}
.rp-detail{font-size:.82rem;color:#64748b;margin-bottom:6px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.rp-meta{display:flex;gap:8px;flex-wrap:wrap;align-items:center;}
.rp-reporter{font-size:.78rem;color:#475569;font-weight:600;display:flex;align-items:center;gap:4px;}
.rp-date{font-size:.73rem;color:#94a3b8;display:flex;align-items:center;gap:3px;}

/* badges */
.st-badge{font-size:.7rem;font-weight:700;padding:3px 9px;border-radius:10px;display:inline-flex;align-items:center;gap:3px;cursor:default;}
.st-pending{background:#fff7ed;color:#c2410c;border:1px solid #fed7aa;}
.st-inprogress{background:#e0f2fe;color:#0369a1;border:1px solid #bae6fd;}
.st-done{background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;}
.pr-badge{font-size:.7rem;font-weight:700;padding:3px 8px;border-radius:10px;}
.pr-low{background:#f1f5f9;color:#475569;}
.pr-normal{background:#e0f2fe;color:#0369a1;}
.pr-high{background:#fff7ed;color:#c2410c;}
.pr-urgent{background:#fee2e2;color:#991b1b;}
.cat-badge{font-size:.7rem;font-weight:700;padding:3px 8px;border-radius:10px;}
.cat-system{background:#eff6ff;color:#1d4ed8;}
.cat-field{background:#f0fdf4;color:#065f46;}

/* thumb */
.rp-thumb{width:44px;height:44px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;cursor:pointer;flex-shrink:0;}

/* expand: form อัปเดตสถานะ */
.rp-expand{display:none;background:#f8fafc;border-top:1px solid #e2e8f0;padding:16px;}
.rp-expand.show{display:block;}
.expand-grid{display:grid;grid-template-columns:1fr 1fr auto;gap:12px;align-items:end;}
.exp-field{display:flex;flex-direction:column;gap:5px;}
.exp-lbl{font-size:.75rem;font-weight:700;color:#64748b;}
.exp-input,.exp-select{padding:9px 11px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.88rem;font-family:'Sarabun',sans-serif;background:#fff;color:#1e293b;outline:none;width:100%;}
.exp-input:focus,.exp-select:focus{border-color:#10b981;}
.btn-update{padding:9px 18px;background:#10b981;color:#fff;border:none;border-radius:8px;font-weight:700;font-size:.85rem;font-family:'Sarabun',sans-serif;cursor:pointer;white-space:nowrap;display:flex;align-items:center;gap:5px;}
.btn-update:hover{background:#059669;}

/* admin note display */
.admin-note-box{margin-top:10px;background:#fff;border:1px solid #d1fae5;border-left:3px solid #10b981;border-radius:0 7px 7px 0;padding:9px 12px;font-size:.82rem;color:#065f46;}
.admin-note-box i{margin-right:5px;}

.empty-s{text-align:center;padding:50px;color:#94a3b8;}
.empty-s i{font-size:2rem;display:block;margin-bottom:8px;}

/* lightbox */
.lightbox{display:none;position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:9999;align-items:center;justify-content:center;}
.lightbox.show{display:flex;}
.lightbox img{max-width:90vw;max-height:88vh;border-radius:10px;}
.lb-close{position:absolute;top:18px;right:22px;color:#fff;font-size:1.8rem;cursor:pointer;background:none;border:none;}

@media(max-width:768px){
    .stats{grid-template-columns:repeat(3,1fr);}
    .filter-bar{flex-direction:column;align-items:stretch;}
    .expand-grid{grid-template-columns:1fr;}
}
@media(max-width:480px){
    .stats{grid-template-columns:repeat(2,1fr);}
}
</style>
</head>
<body>
<div class="content-wrapper">
<div class="pw">

<div class="ph">
    <div class="ph-icon"><i class="fa-solid fa-circle-exclamation"></i></div>
    <div>
        <div class="ph-title">จัดการรายการแจ้งปัญหา</div>
        <div class="ph-sub">รวมปัญหาระบบเว็บไซต์และงานไร่อ้อย — เรียงตามความเร่งด่วน</div>
    </div>
</div>

<?php if(!empty($message)): ?>
<div class="alert <?php echo $status==='success'?'alert-success':'alert-error'; ?>">
    <i class="fa-solid <?php echo $status==='success'?'fa-circle-check':'fa-circle-exclamation'; ?>"></i>
    <span><?php echo htmlspecialchars($message); ?></span>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="stats">
    <div class="stat-card">
        <div class="stat-ico" style="background:#f1f5f9;"><i class="fa-solid fa-list" style="color:#475569;font-size:1rem;"></i></div>
        <div><div class="stat-num"><?php echo $stat['total'];?></div><div class="stat-lbl">ทั้งหมด</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-ico" style="background:#fff7ed;"><i class="fa-solid fa-clock" style="color:#f97316;font-size:1rem;"></i></div>
        <div><div class="stat-num" style="color:#f97316;"><?php echo $stat['pending'];?></div><div class="stat-lbl">รอดำเนินการ</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-ico" style="background:#e0f2fe;"><i class="fa-solid fa-gear" style="color:#0369a1;font-size:1rem;"></i></div>
        <div><div class="stat-num" style="color:#0369a1;"><?php echo $stat['inprogress'];?></div><div class="stat-lbl">กำลังดำเนินการ</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-ico" style="background:#d1fae5;"><i class="fa-solid fa-check-double" style="color:#059669;font-size:1rem;"></i></div>
        <div><div class="stat-num" style="color:#059669;"><?php echo $stat['done'];?></div><div class="stat-lbl">แก้ไขแล้ว</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-ico" style="background:#fee2e2;"><i class="fa-solid fa-triangle-exclamation" style="color:#e11d48;font-size:1rem;"></i></div>
        <div><div class="stat-num" style="color:#e11d48;"><?php echo $stat['urgent'];?></div><div class="stat-lbl">ด่วนมาก</div></div>
    </div>
</div>

<!-- Filter -->
<form method="GET" action="problem_reports_admin.php">
    <div class="filter-bar">
        <div class="fb-group">
            <span class="fb-lbl"><i class="fa-solid fa-layer-group"></i> หมวดหมู่</span>
            <select name="cat" class="fb-input">
                <option value="">ทั้งหมด</option>
                <option value="system" <?php echo $f_cat==='system'?'selected':'';?>>ระบบ / เว็บไซต์</option>
                <option value="field"  <?php echo $f_cat==='field' ?'selected':'';?>>งานไร่ / อ้อย</option>
            </select>
        </div>
        <div class="fb-group">
            <span class="fb-lbl"><i class="fa-solid fa-flag"></i> สถานะ</span>
            <select name="status" class="fb-input">
                <option value="">ทั้งหมด</option>
                <option value="pending"    <?php echo $f_status==='pending'?'selected':'';?>>รอดำเนินการ</option>
                <option value="inprogress" <?php echo $f_status==='inprogress'?'selected':'';?>>กำลังดำเนินการ</option>
                <option value="done"       <?php echo $f_status==='done'?'selected':'';?>>แก้ไขแล้ว</option>
            </select>
        </div>
        <div class="fb-group">
            <span class="fb-lbl"><i class="fa-solid fa-gauge-high"></i> ความเร่งด่วน</span>
            <select name="priority" class="fb-input">
                <option value="">ทั้งหมด</option>
                <option value="urgent" <?php echo $f_pri==='urgent'?'selected':'';?>>ด่วนมาก</option>
                <option value="high"   <?php echo $f_pri==='high'  ?'selected':'';?>>สูง</option>
                <option value="normal" <?php echo $f_pri==='normal'?'selected':'';?>>ปกติ</option>
                <option value="low"    <?php echo $f_pri==='low'   ?'selected':'';?>>ต่ำ</option>
            </select>
        </div>
        <button type="submit" class="btn-filter"><i class="fa-solid fa-magnifying-glass"></i> กรอง</button>
        <a href="problem_reports_admin.php" class="btn-reset"><i class="fa-solid fa-rotate-left"></i> รีเซ็ต</a>
    </div>
</form>

<!-- List -->
<?php if(empty($reports)): ?>
<div class="empty-s">
    <i class="fa-solid fa-clipboard-list"></i>
    ไม่มีรายการที่ตรงกับเงื่อนไข
</div>
<?php else: ?>
<div class="rp-list">
<?php
$st_map = [
    'pending'    => ['st-pending',    'fa-clock',        'รอดำเนินการ'],
    'inprogress' => ['st-inprogress', 'fa-gear fa-spin', 'กำลังดำเนินการ'],
    'done'       => ['st-done',       'fa-check-double', 'แก้ไขแล้ว'],
];
$pr_map = [
    'low'    => ['pr-low',    'ต่ำ'],
    'normal' => ['pr-normal', 'ปกติ'],
    'high'   => ['pr-high',   'สูง'],
    'urgent' => ['pr-urgent', '🔴 ด่วนมาก'],
];
foreach($reports as $r):
    $d  = (int)date('d', strtotime($r['created_at']));
    $mo = (int)date('m', strtotime($r['created_at']));
    $yr = (int)date('Y', strtotime($r['created_at'])) + 543;
    $date_str = $d.' '.$thai_months[$mo].' '.$yr.' '.date('H:i', strtotime($r['created_at'])).' น.';
    [$st_cls,$st_ico,$st_txt] = $st_map[$r['status']] ?? $st_map['pending'];
    [$pr_cls,$pr_txt]         = $pr_map[$r['priority']] ?? $pr_map['normal'];
    $border_cls = $r['priority']==='urgent'?'urgent-card':($r['priority']==='high'?'high-card':'');
?>
<div class="rp-card <?php echo $border_cls; ?>">
    <!-- หัวการ์ด (คลิกเพื่อเปิด/ปิด expand) -->
    <div class="rp-top" onclick="toggleExpand(<?php echo $r['report_id']; ?>)">
        <div class="rp-cat-icon <?php echo $r['category']==='system'?'rp-cat-system':'rp-cat-field'; ?>">
            <i class="fa-solid <?php echo $r['category']==='system'?'fa-computer':'fa-seedling'; ?>"></i>
        </div>
        <div class="rp-main">
            <div class="rp-type"><?php echo htmlspecialchars($r['prob_type']); ?></div>
            <div class="rp-detail"><?php echo htmlspecialchars($r['prob_detail']); ?></div>
            <div class="rp-meta">
                <span class="rp-reporter">
                    <i class="fa-solid fa-user" style="color:#94a3b8;"></i>
                    <?php echo htmlspecialchars($r['emp_name']); ?>
                    <span style="color:#94a3b8;font-weight:400;">(<?php echo htmlspecialchars($r['emp_unit']); ?>)</span>
                </span>
                <span class="rp-date"><i class="fa-regular fa-calendar"></i><?php echo $date_str; ?></span>
                <span class="st-badge <?php echo $st_cls; ?>"><i class="fa-solid <?php echo $st_ico; ?>"></i><?php echo $st_txt; ?></span>
                <span class="pr-badge <?php echo $pr_cls; ?>"><?php echo $pr_txt; ?></span>
                <span class="cat-badge <?php echo $r['category']==='system'?'cat-system':'cat-field'; ?>">
                    <?php echo $r['category']==='system'?'ระบบ':'งานไร่'; ?>
                </span>
            </div>
            <?php if(!empty($r['admin_note'])): ?>
            <div class="admin-note-box">
                <i class="fa-solid fa-reply"></i><strong>Admin:</strong> <?php echo htmlspecialchars($r['admin_note']); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php if(!empty($r['img_path'])): ?>
        <img class="rp-thumb" src="<?php echo htmlspecialchars($r['img_path']); ?>"
             onclick="event.stopPropagation(); openLB(this.src)" alt="ภาพ">
        <?php endif; ?>
        <div style="color:#94a3b8;font-size:.8rem;padding-top:2px;flex-shrink:0;">
            <i class="fa-solid fa-chevron-down" id="chev-<?php echo $r['report_id']; ?>"></i>
        </div>
    </div>

    <!-- Expand: form อัปเดต -->
    <div class="rp-expand" id="expand-<?php echo $r['report_id']; ?>">
        <form method="POST" action="problem_reports_admin.php">
            <input type="hidden" name="report_id"    value="<?php echo $r['report_id']; ?>">
            <input type="hidden" name="filter_cat"    value="<?php echo htmlspecialchars($f_cat); ?>">
            <input type="hidden" name="filter_status" value="<?php echo htmlspecialchars($f_status); ?>">
            <input type="hidden" name="filter_pri"    value="<?php echo htmlspecialchars($f_pri); ?>">
            <div class="expand-grid">
                <div class="exp-field">
                    <span class="exp-lbl"><i class="fa-solid fa-flag"></i> อัปเดตสถานะ</span>
                    <select name="new_status" class="exp-select">
                        <option value="pending"    <?php echo $r['status']==='pending'   ?'selected':'';?>>⏳ รอดำเนินการ</option>
                        <option value="inprogress" <?php echo $r['status']==='inprogress'?'selected':'';?>>⚙️ กำลังดำเนินการ</option>
                        <option value="done"       <?php echo $r['status']==='done'      ?'selected':'';?>>✅ แก้ไขแล้ว</option>
                    </select>
                </div>
                <div class="exp-field">
                    <span class="exp-lbl"><i class="fa-solid fa-reply"></i> หมายเหตุ / ตอบกลับ</span>
                    <input type="text" name="admin_note" class="exp-input"
                           value="<?php echo htmlspecialchars($r['admin_note'] ?? ''); ?>"
                           placeholder="ข้อความถึงผู้แจ้ง...">
                </div>
                <button type="submit" class="btn-update">
                    <i class="fa-solid fa-floppy-disk"></i> บันทึก
                </button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

</div>
</div>

<!-- Lightbox -->
<div class="lightbox" id="lb" onclick="closeLB()">
    <button class="lb-close"><i class="fa-solid fa-xmark"></i></button>
    <img src="" id="lb-img" alt="">
</div>

<?php include 'includes/nav_u_footer.php'; ?>
<script>
function toggleExpand(id) {
    const box  = document.getElementById('expand-' + id);
    const chev = document.getElementById('chev-' + id);
    const open = box.classList.toggle('show');
    chev.style.transform = open ? 'rotate(180deg)' : '';
    chev.style.transition = 'transform .2s';
}
function openLB(src){ document.getElementById('lb-img').src=src; document.getElementById('lb').classList.add('show'); }
function closeLB(){ document.getElementById('lb').classList.remove('show'); }
document.addEventListener('keydown', e => { if(e.key==='Escape') closeLB(); });
</script>
</body>
</html>