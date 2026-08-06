<?php
/**
 * harvester_admin.php — Admin ดูตารางสรุปข้อมูลการตรวจเช็กรถตัดทั้งหมด
 */
date_default_timezone_set('Asia/Bangkok');
require_once 'config.php';
session_start();

if(!isset($_SESSION["emp_id"])){ header("location: login.php"); exit; }
if($_SESSION['emp_level'] !== 'a'){ header("location: harvester.php"); exit; }

$crop_year = $_SESSION['crop_year'];
$thai_months=['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
$message = "";
$status = "";

if (isset($_SESSION['flash_msg'])) {
    $message = $_SESSION['flash_msg'];
    $status = $_SESSION['flash_status'] ?? 'success';
    unset($_SESSION['flash_msg'], $_SESSION['flash_status']);
}

// ── Filter ──
$filter_date = $_GET['date'] ?? date('Y-m-d');
$filter_unit = $_GET['unit'] ?? '';

function redirect_harvester_admin(string $date, string $unit = ''): void {
    $query = ['date' => $date];
    if ($unit !== '') $query['unit'] = $unit;
    header("Location: harvester_admin.php?" . http_build_query($query));
    exit;
}

// ── POST: delete / update จาก check_sessions ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action     = $_POST['action']     ?? '';
    $session_id = isset($_POST['session_id']) ? (int)$_POST['session_id'] : 0;
    $return_date = $_POST['date'] ?? date('Y-m-d');
    $return_unit = $_POST['unit'] ?? '';

    try {
        if ($session_id <= 0) throw new Exception("ไม่พบรายการที่ต้องการจัดการ");

        if ($action === 'delete') {
            // ลบ check_results ก่อน (cascade ก็ได้แต่ explicit ดีกว่า)
            $conn->prepare("DELETE FROM check_results WHERE session_id = :sid")->execute([':sid' => $session_id]);
            $conn->prepare("DELETE FROM check_sessions WHERE session_id = :sid AND crop_year = :cy")->execute([':sid' => $session_id, ':cy' => $crop_year]);
            $_SESSION['flash_status'] = "success";
            $_SESSION['flash_msg']    = "ลบรายการตรวจเช็กเรียบร้อยแล้ว";

        } elseif ($action === 'update') {
            $harvester_number  = trim($_POST['harvester_number'] ?? '');
            $field_condition   = trim($_POST['field_condition']  ?? '');
            if ($harvester_number === '') throw new Exception("กรุณาระบุเบอร์รถตัดอ้อย");

            // อัปเดต check_sessions
            $conn->prepare(
                "UPDATE check_sessions
                 SET harvester_number = :hn, field_condition = :fc
                 WHERE session_id = :sid AND crop_year = :cy"
            )->execute([':hn'=>$harvester_number, ':fc'=>$field_condition, ':sid'=>$session_id, ':cy'=>$crop_year]);

            // อัปเดต check_results รายข้อ (item_id 1-5)
            $item_map = [
                'check_blade'=>1,'check_top_cutter'=>2,'check_chopper'=>3,
                'check_base_cutter'=>4,'check_extractor'=>5,
            ];
            $stmt_upd = $conn->prepare(
                "UPDATE check_results SET pass = :pass WHERE session_id = :sid AND item_id = :iid"
            );
            foreach($item_map as $field => $iid) {
                $stmt_upd->execute([
                    ':pass' => (int)($_POST[$field] ?? 0),
                    ':sid'  => $session_id,
                    ':iid'  => $iid,
                ]);
            }
            $_SESSION['flash_status'] = "success";
            $_SESSION['flash_msg']    = "แก้ไขรายการตรวจเช็กเรียบร้อยแล้ว";
        }
    } catch(Exception $e) {
        $_SESSION['flash_status'] = "error";
        $_SESSION['flash_msg']    = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
    redirect_harvester_admin($return_date, $return_unit);
}

// ── dropdown หน่วย ──
$units_raw = $conn->query(
    "SELECT CASE WHEN zone_id='000' THEN zone_name ELSE CONCAT(zone_id,' ',zone_name) END AS unit_name
     FROM zones ORDER BY zone_id ASC"
);
$all_units = $units_raw->fetchAll(PDO::FETCH_COLUMN);

// ── ดึง check_sessions พร้อม check_results (pivot) ──
$where  = "WHERE cs.crop_year=:cy AND DATE(cs.checked_at)=:dt";
$params = [':cy'=>$crop_year, ':dt'=>$filter_date];
if($filter_unit !== '') { $where .= " AND e.emp_unit=:unit"; $params[':unit']=$filter_unit; }

$stmt = $conn->prepare(
    "SELECT cs.*,
            e.emp_name, e.emp_unit,
            -- pivot check_results item 1-5
            MAX(CASE WHEN cr.item_id=1 THEN cr.pass END) AS check_blade,
            MAX(CASE WHEN cr.item_id=2 THEN cr.pass END) AS check_top_cutter,
            MAX(CASE WHEN cr.item_id=3 THEN cr.pass END) AS check_chopper,
            MAX(CASE WHEN cr.item_id=4 THEN cr.pass END) AS check_base_cutter,
            MAX(CASE WHEN cr.item_id=5 THEN cr.pass END) AS check_extractor,
            -- note รายข้อที่ไม่ผ่าน
            MAX(CASE WHEN cr.item_id=1 AND cr.pass=0 THEN cr.note END) AS note_blade,
            MAX(CASE WHEN cr.item_id=2 AND cr.pass=0 THEN cr.note END) AS note_top,
            MAX(CASE WHEN cr.item_id=3 AND cr.pass=0 THEN cr.note END) AS note_chop,
            MAX(CASE WHEN cr.item_id=4 AND cr.pass=0 THEN cr.note END) AS note_base,
            MAX(CASE WHEN cr.item_id=5 AND cr.pass=0 THEN cr.note END) AS note_ext
     FROM check_sessions cs
     JOIN employee e ON cs.emp_id = e.emp_id
     LEFT JOIN check_results cr ON cs.session_id = cr.session_id
     $where
     GROUP BY cs.session_id
     ORDER BY cs.checked_at DESC"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// ── สถิติ ──
$total    = count($rows);
$cnt_ok   = 0; $cnt_fail = 0;
foreach($rows as $r) {
    $ok = ($r['check_blade'] && $r['check_top_cutter'] && $r['check_chopper'] && $r['check_base_cutter'] && $r['check_extractor']);
    if($ok) $cnt_ok++; else $cnt_fail++;
}

// ── ยังไม่บันทึก ──
$miss_where  = "WHERE e.emp_level='u' AND e.emp_id NOT IN (SELECT cs2.emp_id FROM check_sessions cs2 WHERE DATE(cs2.checked_at)=:dt AND cs2.crop_year=:cy)";
$miss_params = [':dt'=>$filter_date, ':cy'=>$crop_year];
if($filter_unit !== '') { $miss_where .= " AND e.emp_unit=:unit"; $miss_params[':unit']=$filter_unit; }
$stmt_miss = $conn->prepare("SELECT e.emp_id,e.emp_name,e.emp_unit FROM employee e $miss_where ORDER BY e.emp_unit,e.emp_name");
$stmt_miss->execute($miss_params);
$missing = $stmt_miss->fetchAll();

// ── สรุปรายหน่วย ──
$summary = [];
foreach($rows as $r){
    $u = $r['emp_unit'];
    if(!isset($summary[$u])) $summary[$u]=['total'=>0,'ok'=>0,'fail'=>0];
    $summary[$u]['total']++;
    $ok=($r['check_blade']&&$r['check_top_cutter']&&$r['check_chopper']&&$r['check_base_cutter']&&$r['check_extractor']);
    if($ok) $summary[$u]['ok']++; else $summary[$u]['fail']++;
}

// ── ดึง field_items สำหรับ dropdown แก้ไข ──
$field_items = [];
try {
    $field_items = $conn->query("SELECT * FROM check_items_field ORDER BY item_id ASC")->fetchAll();
} catch(Exception $e) {}

function badge(mixed $v, string $note=''): string {
    $v = (int)$v;
    $out = $v
        ? '<span class="ok"><i class="fa-solid fa-check"></i></span>'
        : '<span class="fail"><i class="fa-solid fa-xmark"></i></span>';
    if(!$v && $note)
        $out .= '<div class="fail-note" title="'.htmlspecialchars($note).'"><i class="fa-solid fa-pen-to-square"></i> '.mb_substr(htmlspecialchars($note),0,20).(mb_strlen($note)>20?'…':'').'</div>';
    return $out;
}

$fd_d=(int)date('d',strtotime($filter_date));
$fd_m=(int)date('m',strtotime($filter_date));
$fd_y=(int)date('Y',strtotime($filter_date))+543;
$thai_filter=$fd_d.' '.$thai_months[$fd_m].' '.$fd_y;

include 'includes/nav_u_header.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <link rel="icon" type="image/jpeg" href="icon/iconweb.png">
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>สรุปตรวจเช็กรถตัด (Admin) - KTIS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
    *{box-sizing:border-box;}
    body{font-family:'Sarabun',sans-serif;background:#f1f5f9;margin:0;}
    .pw{max-width:1200px;margin:24px auto;padding:0 14px 60px;}
    .ph{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;}
    .ph-left{display:flex;align-items:center;gap:12px;}
    .ph-icon{width:46px;height:46px;background:linear-gradient(135deg,#e11d48,#be123c);border-radius:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
    .ph-icon i{color:#fff;font-size:1.3rem;}
    .ph-title{font-size:1.1rem;font-weight:700;color:#1e293b;}
    .ph-sub{font-size:.78rem;color:#64748b;}
    .stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;}
    .stat-card{background:#fff;border-radius:11px;border:.5px solid #e2e8f0;padding:14px 16px;display:flex;align-items:center;gap:12px;}
    .stat-ico{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
    .stat-num{font-size:1.4rem;font-weight:700;color:#1e293b;line-height:1;}
    .stat-lbl{font-size:.75rem;color:#64748b;margin-top:2px;}
    .filter-bar{background:#fff;border-radius:11px;border:.5px solid #e2e8f0;padding:14px 16px;margin-bottom:18px;display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;}
    .alert{display:flex;align-items:flex-start;gap:10px;padding:12px 15px;border-radius:9px;margin-bottom:18px;font-weight:700;font-size:.86rem;}
    .alert-success{background:#d1fae5;border:1px solid #a7f3d0;color:#065f46;}
    .alert-error{background:#fee2e2;border:1px solid #fecaca;color:#991b1b;}
    .fb-group{display:flex;flex-direction:column;gap:5px;}
    .fb-lbl{font-size:.78rem;font-weight:700;color:#374151;}
    .fb-input{padding:8px 11px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:.85rem;font-family:'Sarabun',sans-serif;background:#f8fafc;color:#1e293b;outline:none;}
    .fb-input:focus{border-color:#e11d48;}
    select.fb-input{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 10px center;padding-right:28px;}
    .btn-filter{padding:9px 18px;background:#e11d48;color:#fff;border:none;border-radius:7px;font-weight:700;font-size:.85rem;font-family:'Sarabun',sans-serif;cursor:pointer;display:flex;align-items:center;gap:5px;white-space:nowrap;}
    .btn-filter:hover{background:#be123c;}
    .btn-today{padding:9px 14px;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;border-radius:7px;font-weight:700;font-size:.85rem;font-family:'Sarabun',sans-serif;cursor:pointer;white-space:nowrap;text-decoration:none;display:flex;align-items:center;gap:5px;}
    .btn-today:hover{background:#e2e8f0;}
    .section-title{font-size:.88rem;font-weight:700;color:#1e293b;display:flex;align-items:center;gap:7px;margin-bottom:10px;}
    .section-title i{color:#94a3b8;}
    .unit-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;margin-bottom:20px;}
    .unit-card{background:#fff;border-radius:10px;border:.5px solid #e2e8f0;padding:12px 14px;}
    .unit-name{font-weight:700;font-size:.83rem;color:#1e293b;margin-bottom:6px;display:flex;align-items:center;gap:5px;}
    .unit-name i{color:#e11d48;font-size:.75rem;}
    .unit-bars{display:flex;gap:6px;}
    .unit-bar-ok{flex:1;background:#d1fae5;border-radius:5px;padding:5px 8px;text-align:center;font-size:.75rem;font-weight:700;color:#065f46;}
    .unit-bar-fail{flex:1;background:#fee2e2;border-radius:5px;padding:5px 8px;text-align:center;font-size:.75rem;font-weight:700;color:#991b1b;}
    .card{background:#fff;border-radius:13px;border:.5px solid #e2e8f0;overflow:hidden;margin-bottom:20px;}
    .card-hd{background:#1e293b;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;border-bottom:3px solid #e11d48;}
    .card-hd-l{display:flex;align-items:center;gap:8px;}
    .card-hd-l i{color:#e11d48;}
    .card-hd-l span{color:#f8fafc;font-weight:700;font-size:.9rem;}
    .cnt-badge{background:rgba(255,255,255,.12);color:#cbd5e1;font-size:.73rem;font-weight:700;padding:2px 9px;border-radius:10px;}
    .tbl-w{overflow-x:auto;}
    table{width:100%;border-collapse:collapse;min-width:960px;}
    thead th{background:#f8fafc;color:#475569;font-size:.75rem;font-weight:700;padding:9px 12px;text-align:left;border-bottom:1px solid #e2e8f0;white-space:nowrap;}
    tbody td{padding:9px 12px;border-bottom:1px solid #f8fafc;font-size:.83rem;color:#334155;vertical-align:middle;}
    tbody tr:last-child td{border-bottom:none;}
    tbody tr:hover{background:#fafafa;}
    .row-fail{background:#fffbf0 !important;}
    .row-fail:hover{background:#fff7ed !important;}
    .emp-n{font-weight:700;color:#1e293b;font-size:.82rem;}
    .emp-u{font-size:.72rem;color:#94a3b8;}
    .hn{font-weight:700;color:#1e293b;font-family:monospace;}
    .ok{background:#d1fae5;color:#065f46;font-size:.72rem;font-weight:700;padding:2px 6px;border-radius:4px;display:inline-flex;align-items:center;gap:2px;}
    .fail{background:#fee2e2;color:#991b1b;font-size:.72rem;font-weight:700;padding:2px 6px;border-radius:4px;display:inline-flex;align-items:center;gap:2px;}
    .fail-note{font-size:.68rem;color:#92400e;background:#fff7ed;border-left:2px solid #f59e0b;padding:1px 5px;margin-top:2px;border-radius:0 3px 3px 0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:130px;}
    .pass-all{background:#d1fae5;color:#065f46;font-size:.71rem;font-weight:700;padding:2px 8px;border-radius:12px;white-space:nowrap;}
    .has-fail{background:#fee2e2;color:#991b1b;font-size:.71rem;font-weight:700;padding:2px 8px;border-radius:12px;white-space:nowrap;display:inline-flex;align-items:center;gap:3px;}

    /* field condition badge */
    .fc-badge{display:inline-flex;align-items:center;gap:3px;font-size:.71rem;font-weight:700;padding:2px 7px;border-radius:4px;background:#f0fdf4;color:#065f46;border:1px solid #a7f3d0;}
    .fc-badge.bad{background:#fff7ed;color:#92400e;border-color:#fcd34d;}

    /* รูปภาพ thumb */
    .img-thumb{width:32px;height:32px;object-fit:cover;border-radius:5px;border:1px solid #e2e8f0;cursor:pointer;transition:transform .15s;}
    .img-thumb:hover{transform:scale(1.15);}
    .img-thumbs{display:flex;gap:4px;}

    /* Lightbox */
    .lightbox{display:none;position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:9999;align-items:center;justify-content:center;}
    .lightbox.show{display:flex;}
    .lightbox img{max-width:90vw;max-height:88vh;border-radius:10px;box-shadow:0 8px 32px rgba(0,0,0,.5);}
    .lightbox-close{position:absolute;top:18px;right:22px;color:#fff;font-size:1.8rem;cursor:pointer;background:none;border:none;}

    .row-actions{display:flex;gap:6px;align-items:center;white-space:nowrap;}
    .btn-mini{border:none;border-radius:7px;padding:6px 9px;font-size:.75rem;font-weight:700;font-family:'Sarabun',sans-serif;cursor:pointer;display:inline-flex;align-items:center;gap:4px;}
    .btn-edit{background:#e0f2fe;color:#0369a1;}
    .btn-edit:hover{background:#bae6fd;}
    .btn-del{background:#fee2e2;color:#991b1b;}
    .btn-del:hover{background:#fecaca;}
    .btn-save{background:#10b981;color:#fff;}
    .btn-save:hover{background:#059669;}
    .edit-row{display:none;background:#f8fafc;}
    .edit-row.show{display:table-row;}
    .edit-box{padding:14px 16px;background:#f8fafc;border-top:1px solid #e2e8f0;}
    .edit-grid{display:grid;grid-template-columns:minmax(160px,1fr) minmax(150px,1fr) repeat(5,minmax(90px,auto)) auto;gap:10px;align-items:end;}
    .edit-field{display:flex;flex-direction:column;gap:4px;}
    .edit-lbl{font-size:.72rem;font-weight:700;color:#64748b;}
    .edit-input,.edit-select{padding:8px 9px;border:1.5px solid #e2e8f0;border-radius:7px;background:#fff;color:#1e293b;font-family:'Sarabun',sans-serif;font-size:.82rem;}

    .missing-card{background:#fff;border-radius:13px;border:.5px solid #fecaca;overflow:hidden;}
    .miss-hd{background:#fef2f2;padding:12px 16px;display:flex;align-items:center;gap:8px;border-bottom:1px solid #fecaca;}
    .miss-hd i{color:#e11d48;}
    .miss-hd span{font-weight:700;color:#991b1b;font-size:.9rem;}
    .miss-row{display:flex;align-items:center;justify-content:space-between;padding:9px 14px;border-bottom:1px solid #fef2f2;font-size:.83rem;}
    .miss-row:last-child{border-bottom:none;}
    .miss-name{font-weight:700;color:#1e293b;}
    .miss-unit{font-size:.75rem;color:#94a3b8;margin-top:1px;}
    .miss-badge{background:#fef2f2;color:#e11d48;font-size:.72rem;font-weight:700;padding:2px 8px;border-radius:10px;border:1px solid #fecaca;white-space:nowrap;}
    .no-miss{padding:20px;text-align:center;color:#10b981;font-weight:700;font-size:.88rem;}
    .empty-s{text-align:center;padding:40px;color:#94a3b8;}
    .empty-s i{font-size:2rem;display:block;margin-bottom:8px;}

    @media(max-width:768px){
        .stats{grid-template-columns:repeat(2,1fr);}
        .filter-bar{flex-direction:column;align-items:stretch;}
        .edit-grid{grid-template-columns:1fr 1fr;}
    }
    @media(max-width:480px){
        .stats{grid-template-columns:1fr 1fr;}
        .unit-grid{grid-template-columns:1fr 1fr;}
    }
    </style>
</head>
<body>
<div class="content-wrapper">
<div class="pw">

<div class="ph">
    <div class="ph-left">
        <div class="ph-icon"><i class="fa-solid fa-chart-bar"></i></div>
        <div>
            <div class="ph-title">สรุปผลตรวจเช็กรถตัดอ้อย (Admin)</div>
            <div class="ph-sub">ปีการผลิต <?php echo htmlspecialchars($crop_year); ?> · กำลังดูวันที่ <?php echo $thai_filter; ?></div>
        </div>
    </div>
    <a href="?date=<?php echo date('Y-m-d');?>" class="btn-today"><i class="fa-solid fa-calendar-day"></i> วันนี้</a>
</div>

<?php if(!empty($message)): ?>
<div class="alert <?php echo $status === 'success' ? 'alert-success' : 'alert-error'; ?>">
    <i class="fa-solid <?php echo $status === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
    <span><?php echo $message; ?></span>
</div>
<?php endif; ?>

<div class="stats">
    <div class="stat-card">
        <div class="stat-ico" style="background:#e0f2fe;"><i class="fa-solid fa-tractor" style="color:#0369a1;font-size:1.1rem;"></i></div>
        <div><div class="stat-num"><?php echo $total;?></div><div class="stat-lbl">บันทึกทั้งหมด</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-ico" style="background:#d1fae5;"><i class="fa-solid fa-check-double" style="color:#059669;font-size:1.1rem;"></i></div>
        <div><div class="stat-num" style="color:#059669;"><?php echo $cnt_ok;?></div><div class="stat-lbl">สมบูรณ์ทั้งหมด</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-ico" style="background:#fee2e2;"><i class="fa-solid fa-triangle-exclamation" style="color:#e11d48;font-size:1.1rem;"></i></div>
        <div><div class="stat-num" style="color:#e11d48;"><?php echo $cnt_fail;?></div><div class="stat-lbl">พบข้อบกพร่อง</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-ico" style="background:#fff7ed;"><i class="fa-solid fa-user-slash" style="color:#f97316;font-size:1.1rem;"></i></div>
        <div><div class="stat-num" style="color:#f97316;"><?php echo count($missing);?></div><div class="stat-lbl">ยังไม่บันทึก</div></div>
    </div>
</div>

<form method="GET" action="harvester_admin.php">
    <div class="filter-bar">
        <div class="fb-group">
            <span class="fb-lbl"><i class="fa-solid fa-calendar"></i> เลือกวันที่</span>
            <input type="date" name="date" class="fb-input" value="<?php echo htmlspecialchars($filter_date);?>" max="<?php echo date('Y-m-d');?>">
        </div>
        <div class="fb-group">
            <span class="fb-lbl"><i class="fa-solid fa-location-dot"></i> กรองตามหน่วย</span>
            <select name="unit" class="fb-input">
                <option value="">ทุกหน่วย</option>
                <?php foreach($all_units as $u):?>
                <option value="<?php echo htmlspecialchars($u);?>" <?php echo $filter_unit===$u?'selected':'';?>>
                    <?php echo htmlspecialchars($u);?>
                </option>
                <?php endforeach;?>
            </select>
        </div>
        <button type="submit" class="btn-filter"><i class="fa-solid fa-magnifying-glass"></i> ค้นหา</button>
    </div>
</form>

<?php if(!empty($summary)):?>
<div class="section-title"><i class="fa-solid fa-chart-pie"></i> สรุปรายหน่วย</div>
<div class="unit-grid">
    <?php foreach($summary as $uname=>$us):?>
    <div class="unit-card">
        <div class="unit-name"><i class="fa-solid fa-location-dot"></i><?php echo htmlspecialchars($uname);?></div>
        <div class="unit-bars">
            <div class="unit-bar-ok"><i class="fa-solid fa-check"></i> <?php echo $us['ok'];?></div>
            <?php if($us['fail']>0):?>
            <div class="unit-bar-fail"><i class="fa-solid fa-xmark"></i> <?php echo $us['fail'];?></div>
            <?php endif;?>
        </div>
    </div>
    <?php endforeach;?>
</div>
<?php endif;?>

<!-- Main table -->
<div class="card">
    <div class="card-hd">
        <div class="card-hd-l"><i class="fa-solid fa-table-list"></i><span>รายละเอียดการบันทึก — <?php echo $thai_filter;?></span></div>
        <span class="cnt-badge"><?php echo $total;?> รายการ</span>
    </div>
    <?php if(empty($rows)):?>
    <div class="empty-s"><i class="fa-solid fa-clipboard-list"></i>ไม่มีข้อมูลในวันที่เลือก</div>
    <?php else:?>
    <div class="tbl-w">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>เวลา</th>
                    <th>ผู้บันทึก</th>
                    <th>เบอร์รถตัด</th>
                    <th>สภาพแปลง</th>
                    <th>ใบพัด</th>
                    <th>ตัดยอด</th>
                    <th>ตัดต่อ</th>
                    <th>ตัดโคน</th>
                    <th>พัดลม</th>
                    <th>ภาพรวม</th>
                    <th>ภาพ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($rows as $i=>$r):
                $allok = ($r['check_blade'] && $r['check_top_cutter'] && $r['check_chopper'] && $r['check_base_cutter'] && $r['check_extractor']);
                $ts    = date('H:i น.', strtotime($r['checked_at']));
                $is_bad_field = !in_array($r['field_condition'] ?? '', ['ปกติ','']);
            ?>
            <tr class="<?php echo !$allok?'row-fail':'';?>">
                <td style="color:#94a3b8;font-size:.78rem;"><?php echo $i+1;?></td>
                <td style="font-size:.8rem;color:#64748b;"><?php echo $ts;?></td>
                <td>
                    <div class="emp-n"><?php echo htmlspecialchars($r['emp_name']);?></div>
                    <div class="emp-u"><?php echo htmlspecialchars($r['emp_unit']);?></div>
                </td>
                <td><span class="hn"><?php echo htmlspecialchars($r['harvester_number']);?></span></td>
                <td>
                    <?php if(!empty($r['field_condition'])): ?>
                        <span class="fc-badge <?php echo $is_bad_field?'bad':''; ?>">
                            <i class="fa-solid <?php echo $is_bad_field?'fa-triangle-exclamation':'fa-leaf';?>"></i>
                            <?php echo htmlspecialchars($r['field_condition']); ?>
                        </span>
                        <?php if(!empty($r['field_condition_etc'])): ?>
                            <div style="font-size:.68rem;color:#64748b;margin-top:2px;"><?php echo htmlspecialchars($r['field_condition_etc']);?></div>
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="color:#94a3b8;font-size:.78rem;">-</span>
                    <?php endif; ?>
                </td>
                <td><?php echo badge((int)$r['check_blade'],      $r['note_blade'] ?? '');?></td>
                <td><?php echo badge((int)$r['check_top_cutter'], $r['note_top']   ?? '');?></td>
                <td><?php echo badge((int)$r['check_chopper'],    $r['note_chop']  ?? '');?></td>
                <td><?php echo badge((int)$r['check_base_cutter'],$r['note_base']  ?? '');?></td>
                <td><?php echo badge((int)$r['check_extractor'],  $r['note_ext']   ?? '');?></td>
                <td>
                    <?php if($allok):?>
                    <span class="pass-all"><i class="fa-solid fa-check-double"></i> ผ่านทั้งหมด</span>
                    <?php else:?>
                    <span class="has-fail"><i class="fa-solid fa-triangle-exclamation"></i> พบข้อบกพร่อง</span>
                    <?php endif;?>
                </td>
                <td>
                    <div class="img-thumbs">
                        <?php if(!empty($r['img_harvester'])): ?>
                            <img class="img-thumb" src="<?php echo htmlspecialchars($r['img_harvester']);?>" onclick="openLightbox(this.src)" title="รูปรถตัด">
                        <?php endif; ?>
                        <?php if(!empty($r['img_field'])): ?>
                            <img class="img-thumb" src="<?php echo htmlspecialchars($r['img_field']);?>" onclick="openLightbox(this.src)" title="รูปแปลงอ้อย">
                        <?php endif; ?>
                        <?php if(empty($r['img_harvester']) && empty($r['img_field'])): ?>
                            <span style="color:#94a3b8;font-size:.78rem;">-</span>
                        <?php endif; ?>
                    </div>
                </td>
                <td>
                    <div class="row-actions">
                        <button type="button" class="btn-mini btn-edit" onclick="toggleEditRow(<?php echo (int)$r['session_id'];?>)">
                            <i class="fa-solid fa-pen"></i> แก้ไข
                        </button>
                        <form method="POST" action="harvester_admin.php" onsubmit="return confirm('ยืนยันลบรายการนี้?');" style="display:inline;">
                            <input type="hidden" name="action"     value="delete">
                            <input type="hidden" name="session_id" value="<?php echo (int)$r['session_id'];?>">
                            <input type="hidden" name="date"       value="<?php echo htmlspecialchars($filter_date);?>">
                            <input type="hidden" name="unit"       value="<?php echo htmlspecialchars($filter_unit);?>">
                            <button type="submit" class="btn-mini btn-del"><i class="fa-solid fa-trash"></i> ลบ</button>
                        </form>
                    </div>
                </td>
            </tr>

            <!-- Edit row -->
            <tr class="edit-row" id="edit-row-<?php echo (int)$r['session_id'];?>">
                <td colspan="13">
                    <form method="POST" action="harvester_admin.php" class="edit-box">
                        <input type="hidden" name="action"     value="update">
                        <input type="hidden" name="session_id" value="<?php echo (int)$r['session_id'];?>">
                        <input type="hidden" name="date"       value="<?php echo htmlspecialchars($filter_date);?>">
                        <input type="hidden" name="unit"       value="<?php echo htmlspecialchars($filter_unit);?>">
                        <div class="edit-grid">
                            <label class="edit-field">
                                <span class="edit-lbl">เบอร์รถตัด</span>
                                <input type="text" name="harvester_number" class="edit-input" value="<?php echo htmlspecialchars($r['harvester_number']);?>" required>
                            </label>
                            <label class="edit-field">
                                <span class="edit-lbl">สภาพแปลง</span>
                                <select name="field_condition" class="edit-select">
                                    <option value="">-- เลือก --</option>
                                    <?php foreach($field_items as $fi): ?>
                                    <option value="<?php echo htmlspecialchars($fi['item_name_field']);?>"
                                        <?php echo ($r['field_condition'] ?? '') === $fi['item_name_field'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($fi['item_name_field']);?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <?php
                            $edit_fields = [
                                'check_blade'       => 'ใบพัด',
                                'check_top_cutter'  => 'ตัดยอด',
                                'check_chopper'     => 'ตัดต่อ',
                                'check_base_cutter' => 'ตัดโคน',
                                'check_extractor'   => 'พัดลม',
                            ];
                            foreach($edit_fields as $field => $label): ?>
                            <label class="edit-field">
                                <span class="edit-lbl"><?php echo $label;?></span>
                                <select name="<?php echo $field;?>" class="edit-select">
                                    <option value="1" <?php echo (int)$r[$field]===1?'selected':'';?>>สมบูรณ์</option>
                                    <option value="0" <?php echo (int)$r[$field]===0?'selected':'';?>>ไม่สมบูรณ์</option>
                                </select>
                            </label>
                            <?php endforeach;?>
                            <button type="submit" class="btn-mini btn-save"><i class="fa-solid fa-floppy-disk"></i> บันทึก</button>
                        </div>
                    </form>
                </td>
            </tr>
            <?php endforeach;?>
            </tbody>
        </table>
    </div>
    <?php endif;?>
</div>

<!-- Missing -->
<div class="missing-card">
    <div class="miss-hd"><i class="fa-solid fa-user-slash"></i><span>พนักงานที่ยังไม่บันทึกวันนี้ (<?php echo count($missing);?> คน)</span></div>
    <?php if(empty($missing)):?>
    <div class="no-miss"><i class="fa-solid fa-circle-check"></i>พนักงานทุกคนบันทึกครบแล้ววันนี้</div>
    <?php else:?>
    <?php foreach($missing as $m):?>
    <div class="miss-row">
        <div>
            <div class="miss-name"><?php echo htmlspecialchars($m['emp_name']);?></div>
            <div class="miss-unit"><?php echo htmlspecialchars($m['emp_unit']);?></div>
        </div>
        <span class="miss-badge"><i class="fa-solid fa-clock"></i> ยังไม่บันทึก</span>
    </div>
    <?php endforeach;?>
    <?php endif;?>
</div>

</div>
</div>

<div class="lightbox" id="lightbox" onclick="closeLightbox()">
    <button class="lightbox-close" onclick="closeLightbox()"><i class="fa-solid fa-xmark"></i></button>
    <img src="" id="lightbox-img" alt="ภาพขยาย">
</div>

<?php include 'includes/nav_u_footer.php'; ?>
<script>
function toggleEditRow(id) {
    const row = document.getElementById('edit-row-' + id);
    if(row) row.classList.toggle('show');
}
function openLightbox(src) {
    document.getElementById('lightbox-img').src = src;
    document.getElementById('lightbox').classList.add('show');
}
function closeLightbox() {
    document.getElementById('lightbox').classList.remove('show');
}
document.addEventListener('keydown', e => { if(e.key==='Escape') closeLightbox(); });
</script>
</body>
</html>