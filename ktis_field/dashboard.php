<?php
/**
 * dashboard.php — สรุปภาพรวมโพสต์/ปัญหาประจำวัน (Admin)
 */
date_default_timezone_set('Asia/Bangkok');
require_once 'config.php';
session_start();

if(!isset($_SESSION['emp_id']) || $_SESSION['emp_level'] !== 'a'){
    header("location: login.php"); exit;
}

$crop_year   = $_SESSION['crop_year'];
$filter_date     = $_GET['date']     ?? date('Y-m-d');
$filter_date_end = $_GET['date_end'] ?? $filter_date; // ถ้าไม่ระบุ = วันเดียว
$filter_unit     = $_GET['unit']     ?? '';

// ถ้า date_end น้อยกว่า date ให้ swap
if($filter_date_end < $filter_date){
    [$filter_date, $filter_date_end] = [$filter_date_end, $filter_date];
}
$is_range = ($filter_date !== $filter_date_end);

$thai_months = ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน',
                'กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];

function thai_date(string $date, array $months): string {
    $d = (int)date('d', strtotime($date));
    $m = (int)date('m', strtotime($date));
    $y = (int)date('Y', strtotime($date)) + 543;
    return $d.' '.$months[$m].' '.$y;
}

// ── ดึง zones ──
$all_units = [];
try {
    $zu = $conn->query("SELECT CASE WHEN zone_id='000' THEN zone_name ELSE CONCAT(zone_id,' ',zone_name) END AS unit_name FROM zones ORDER BY zone_id ASC");
    $all_units = $zu->fetchAll(PDO::FETCH_COLUMN);
} catch(Exception $e){}

// ── WHERE base ──
if($is_range){
    $where  = "WHERE DATE(p.created_at) BETWEEN :dt AND :dt_end AND p.crop_year=:cy";
    $params = [':dt'=>$filter_date, ':dt_end'=>$filter_date_end, ':cy'=>$crop_year];
} else {
    $where  = "WHERE DATE(p.created_at)=:dt AND p.crop_year=:cy";
    $params = [':dt'=>$filter_date, ':cy'=>$crop_year];
}
if($filter_unit){ $where .= " AND p.target_unit=:unit"; $params[':unit']=$filter_unit; }

// ── สถิติหลัก ──
$stat = $conn->prepare(
    "SELECT
        COUNT(*)                          AS total,
        SUM(job_status='pending')         AS pending,
        SUM(job_status='success')         AS success,
        COUNT(DISTINCT target_unit)       AS unit_count,
        COUNT(DISTINCT truck_number)      AS truck_count
     FROM posts p $where"
);
$stat->execute($params);
$s = $stat->fetch();

// ── โพสต์ทั้งหมดวันนี้ ──
$stmt_posts = $conn->prepare(
    "SELECT p.*, e.emp_name, e.emp_unit
     FROM posts p
     JOIN employee e ON p.emp_id = e.emp_id
     $where
     ORDER BY p.job_status ASC, p.created_at DESC"
);
$stmt_posts->execute($params);
$posts = $stmt_posts->fetchAll();

// ── สรุปรายหน่วย ──
$stmt_unit = $conn->prepare(
    "SELECT target_unit,
            COUNT(*) AS total,
            SUM(job_status='pending') AS pending,
            SUM(job_status='success') AS success
     FROM posts p $where
     GROUP BY target_unit
     ORDER BY pending DESC, total DESC"
);
$stmt_unit->execute($params);
$by_unit = $stmt_unit->fetchAll();

// ── ปัญหาที่พบบ่อย (รวม 3 columns) ──
$stmt_prob = $conn->prepare(
    "SELECT prob, COUNT(*) AS cnt FROM (
        SELECT problem_detail   AS prob FROM posts p $where AND problem_detail   != ''
        UNION ALL
        SELECT problem_detail_2 AS prob FROM posts p $where AND problem_detail_2 IS NOT NULL AND problem_detail_2 != ''
        UNION ALL
        SELECT problem_detail_3 AS prob FROM posts p $where AND problem_detail_3 IS NOT NULL AND problem_detail_3 != ''
     ) AS all_probs
     GROUP BY prob ORDER BY cnt DESC LIMIT 8"
);
// params ต้องส่ง 3 รอบ
$p3 = array_merge($params, $params, $params);
// rebind key ซ้ำไม่ได้ ใช้ positional แทน
// ── Top Problems: สร้าง where/params แยกสำหรับ UNION (named params ซ้ำใน UNION ไม่ได้) ──
$prob_params = $params; // ใช้ params เดิมเป็นฐาน
if($is_range){
    $prob_where_1 = "WHERE DATE(p.created_at) BETWEEN :pdt1 AND :pdte1 AND p.crop_year=:pcy1" . ($filter_unit ? " AND p.target_unit=:pu1" : "");
    $prob_where_2 = "WHERE DATE(p.created_at) BETWEEN :pdt2 AND :pdte2 AND p.crop_year=:pcy2" . ($filter_unit ? " AND p.target_unit=:pu2" : "");
    $prob_where_3 = "WHERE DATE(p.created_at) BETWEEN :pdt3 AND :pdte3 AND p.crop_year=:pcy3" . ($filter_unit ? " AND p.target_unit=:pu3" : "");
    $prob_bind = [
        ':pdt1'=>$filter_date,':pdte1'=>$filter_date_end,':pcy1'=>$crop_year,
        ':pdt2'=>$filter_date,':pdte2'=>$filter_date_end,':pcy2'=>$crop_year,
        ':pdt3'=>$filter_date,':pdte3'=>$filter_date_end,':pcy3'=>$crop_year,
    ];
    if($filter_unit){
        $prob_bind[':pu1'] = $filter_unit;
        $prob_bind[':pu2'] = $filter_unit;
        $prob_bind[':pu3'] = $filter_unit;
    }
} else {
    $prob_where_1 = "WHERE DATE(p.created_at)=:pdt1 AND p.crop_year=:pcy1" . ($filter_unit ? " AND p.target_unit=:pu1" : "");
    $prob_where_2 = "WHERE DATE(p.created_at)=:pdt2 AND p.crop_year=:pcy2" . ($filter_unit ? " AND p.target_unit=:pu2" : "");
    $prob_where_3 = "WHERE DATE(p.created_at)=:pdt3 AND p.crop_year=:pcy3" . ($filter_unit ? " AND p.target_unit=:pu3" : "");
    $prob_bind = [
        ':pdt1'=>$filter_date,':pcy1'=>$crop_year,
        ':pdt2'=>$filter_date,':pcy2'=>$crop_year,
        ':pdt3'=>$filter_date,':pcy3'=>$crop_year,
    ];
    if($filter_unit){
        $prob_bind[':pu1'] = $filter_unit;
        $prob_bind[':pu2'] = $filter_unit;
        $prob_bind[':pu3'] = $filter_unit;
    }
}

$stmt_prob2 = $conn->prepare(
    "SELECT prob, COUNT(*) AS cnt FROM (
        SELECT problem_detail   AS prob FROM posts p $prob_where_1 AND problem_detail   != ''
        UNION ALL
        SELECT problem_detail_2 AS prob FROM posts p $prob_where_2 AND problem_detail_2 IS NOT NULL AND problem_detail_2 != ''
        UNION ALL
        SELECT problem_detail_3 AS prob FROM posts p $prob_where_3 AND problem_detail_3 IS NOT NULL AND problem_detail_3 != ''
     ) AS all_probs
     GROUP BY prob ORDER BY cnt DESC LIMIT 8"
);
$stmt_prob2->execute($prob_bind);
$top_problems = $stmt_prob2->fetchAll();

$thai_filter = $is_range
    ? thai_date($filter_date, $thai_months).' — '.thai_date($filter_date_end, $thai_months)
    : thai_date($filter_date, $thai_months);

include 'includes/nav_u_header.php';
?>
<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
<link href='https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap' rel='stylesheet'>
<style>
/* ── Dashboard CSS ── */
.pw{max-width:1200px;margin:0;padding:0;}
.content-wrapper{display:flex;min-height:calc(100vh - 60px);}
.dash-wrap{flex:1;min-width:0;padding:24px 14px 60px;overflow-x:hidden;}
*{box-sizing:border-box;}
body{font-family:'Sarabun',sans-serif;background:#f1f5f9;margin:0;}

/* page header */
.ph{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:22px;}
.ph-left{display:flex;align-items:center;gap:12px;}
.ph-icon{width:46px;height:46px;background:linear-gradient(135deg,#6366f1,#4f46e5);border-radius:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.ph-icon i{color:#fff;font-size:1.3rem;}
.ph-title{font-size:1.15rem;font-weight:700;color:#1e293b;margin-bottom:2px;}
.ph-sub{font-size:.8rem;color:#64748b;}
.btn-today{padding:9px 14px;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;border-radius:7px;font-weight:700;font-size:.85rem;font-family:'Sarabun',sans-serif;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:5px;}
.btn-today:hover{background:#e2e8f0;}

/* filter */
.filter-bar{background:#fff;border-radius:11px;border:.5px solid #e2e8f0;padding:13px 16px;margin-bottom:20px;display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;}
form{margin:0;}
.fb-group{display:flex;flex-direction:column;gap:4px;}
.fb-lbl{font-size:.75rem;font-weight:700;color:#374151;}
.fb-input{padding:8px 11px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:.85rem;font-family:'Sarabun',sans-serif;background:#f8fafc;color:#1e293b;outline:none;}
.fb-input:focus{border-color:#6366f1;}
select.fb-input{cursor:pointer;min-width:160px;}
.btn-filter{padding:9px 18px;background:#6366f1;color:#fff;border:none;border-radius:7px;font-weight:700;font-size:.85rem;font-family:'Sarabun',sans-serif;cursor:pointer;}
.btn-filter:hover{background:#4f46e5;}

.btn-export-excel {
    padding: 9px 14px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #ffffff;
    border: none;
    border-radius: 7px;
    font-weight: 700;
    font-size: .85rem;
    font-family: 'Sarabun', sans-serif;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.15s ease;
    box-shadow: 0 2px 6px rgba(16, 185, 129, 0.25);
    white-space: nowrap;
}
.btn-export-excel:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);
    color: #ffffff;
}
.btn-shortcut{padding:5px 10px;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;border-radius:6px;font-size:.75rem;font-weight:700;font-family:'Sarabun',sans-serif;cursor:pointer;white-space:nowrap;transition:all .15s;}
.btn-shortcut:hover{background:#ede9fe;color:#4f46e5;border-color:#c4b5fd;}

/* stat cards */
.stats{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:22px;}
.stat-card{background:#fff;border-radius:12px;border:.5px solid #e2e8f0;padding:16px;display:flex;align-items:center;gap:12px;}
.stat-ico{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.stat-num{font-size:1.6rem;font-weight:700;line-height:1;color:#1e293b;}
.stat-lbl{font-size:.73rem;color:#64748b;margin-top:3px;}

/* 2-col grid */
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px;}

/* card */
.card{background:#fff;border-radius:13px;border:.5px solid #e2e8f0;overflow:hidden;}
.card-full{grid-column:1/-1;}
.card-hd{background:#1e293b;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;border-bottom:3px solid #6366f1;}
.card-hd.green{border-bottom-color:#10b981;}
.card-hd.red{border-bottom-color:#e11d48;}
.card-hd.amber{border-bottom-color:#f59e0b;}
.card-hd-l{display:flex;align-items:center;gap:8px;}
.card-hd-l i{color:#6366f1;}
.card-hd.green .card-hd-l i{color:#10b981;}
.card-hd.red .card-hd-l i{color:#e11d48;}
.card-hd.amber .card-hd-l i{color:#f59e0b;}
.card-hd-l span{color:#f8fafc;font-weight:700;font-size:.9rem;}
.cnt-badge{background:rgba(255,255,255,.12);color:#cbd5e1;font-size:.73rem;font-weight:700;padding:2px 9px;border-radius:10px;}

/* unit summary */
.unit-row{display:flex;align-items:center;gap:10px;padding:10px 14px;border-bottom:1px solid #f8fafc;}
.unit-row:last-child{border-bottom:none;}
.unit-name{font-weight:700;font-size:.85rem;color:#1e293b;min-width:130px;}
.unit-bar-wrap{flex:1;display:flex;flex-direction:column;gap:4px;}
.progress-track{background:#f1f5f9;border-radius:20px;height:8px;overflow:hidden;}
.progress-fill{height:8px;border-radius:20px;transition:width .4s ease;}
.progress-fill.success{background:#10b981;}
.progress-fill.pending{background:#f97316;}
.unit-nums{display:flex;gap:10px;font-size:.75rem;}
.num-ok{color:#059669;font-weight:700;}
.num-wait{color:#f97316;font-weight:700;}
.num-total{color:#94a3b8;}

/* top problems */
.prob-row{display:flex;align-items:center;justify-content:space-between;padding:9px 14px;border-bottom:1px solid #f8fafc;}
.prob-row:last-child{border-bottom:none;}
.prob-rank{width:22px;height:22px;border-radius:50%;background:#f1f5f9;color:#64748b;font-size:.72rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.prob-rank.top1{background:#fef9c3;color:#854d0e;}
.prob-rank.top2{background:#f1f5f9;color:#475569;}
.prob-rank.top3{background:#fff7ed;color:#c2410c;}
.prob-name{flex:1;font-size:.85rem;color:#1e293b;font-weight:600;margin:0 10px;}
.prob-bar-wrap{width:100px;background:#f1f5f9;border-radius:20px;height:7px;overflow:hidden;margin:0 10px;}
.prob-bar-fill{height:7px;border-radius:20px;background:#6366f1;}
.prob-cnt{font-size:.8rem;font-weight:700;color:#6366f1;min-width:28px;text-align:right;}

/* posts table */
.tbl-w{overflow-x:auto;}
table{width:100%;border-collapse:collapse;min-width:800px;}
thead th{background:#f8fafc;color:#475569;font-size:.75rem;font-weight:700;padding:9px 12px;text-align:left;border-bottom:1px solid #e2e8f0;white-space:nowrap;}
tbody td{padding:9px 12px;border-bottom:1px solid #f8fafc;font-size:.82rem;color:#334155;vertical-align:middle;}
tbody tr:last-child td{border-bottom:none;}
tbody tr:hover{background:#fafafa;}
.row-pending{background:#fffbf0 !important;}
.row-pending:hover{background:#fff7ed !important;}
.emp-n{font-weight:700;color:#1e293b;font-size:.82rem;}
.emp-u{font-size:.72rem;color:#94a3b8;}
.truck{font-weight:700;font-family:monospace;color:#1e293b;}
.prob-tag{display:inline-block;background:#f1f5f9;color:#475569;font-size:.7rem;font-weight:600;padding:2px 7px;border-radius:4px;margin:1px;}
.st-success{background:#d1fae5;color:#065f46;font-size:.71rem;font-weight:700;padding:3px 9px;border-radius:10px;white-space:nowrap;display:inline-flex;align-items:center;gap:3px;}
.st-pending{background:#fff7ed;color:#c2410c;font-size:.71rem;font-weight:700;padding:3px 9px;border-radius:10px;white-space:nowrap;display:inline-flex;align-items:center;gap:3px;}
.img-thumb{width:32px;height:32px;object-fit:cover;border-radius:5px;border:1px solid #e2e8f0;cursor:pointer;}
.img-thumbs{display:flex;gap:3px;}

/* donut chart (CSS only) */
.donut-wrap{display:flex;align-items:center;justify-content:center;gap:24px;padding:20px 16px;}
.donut{position:relative;width:110px;height:110px;}
.donut svg{transform:rotate(-90deg);}
.donut-label{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;}
.donut-pct{font-size:1.25rem;font-weight:700;color:#1e293b;}
.donut-sub{font-size:.68rem;color:#64748b;}
.donut-legend{display:flex;flex-direction:column;gap:8px;}
.legend-item{display:flex;align-items:center;gap:7px;font-size:.82rem;font-weight:600;}
.legend-dot{width:12px;height:12px;border-radius:50%;flex-shrink:0;}

.empty-s{text-align:center;padding:40px;color:#94a3b8;}
.empty-s i{font-size:2rem;display:block;margin-bottom:8px;}

/* lightbox */
.lightbox{display:none;position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:9999;align-items:center;justify-content:center;}
.lightbox.show{display:flex;}
.lightbox img{max-width:90vw;max-height:88vh;border-radius:10px;}
.lb-close{position:absolute;top:18px;right:22px;color:#fff;font-size:1.8rem;cursor:pointer;background:none;border:none;}

@media(max-width:900px){
    .stats{grid-template-columns:repeat(3,1fr);}
    .grid2{grid-template-columns:1fr;}
    .card-full{grid-column:1;}
}
@media(max-width:540px){
    .stats{grid-template-columns:repeat(2,1fr);}
    .filter-bar{flex-direction:column;align-items:stretch;}
}
</style>
<div class="page-wrapper">
<?php include 'includes/nav_u_sidebar.php'; ?>
<div class="dash-wrap">
    

<!-- Header -->
<div class="ph">
    <div class="ph-left">
        <div class="ph-icon"><i class="fa-solid fa-chart-pie"></i></div>
        <div>
            <div class="ph-title">Dashboard สรุปภาพรวมประจำวัน</div>
            <div class="ph-sub">
                ปีการผลิต <?php echo htmlspecialchars($crop_year); ?> ·
                <?php if($is_range): ?>
                    <span style="background:#ede9fe;color:#4f46e5;padding:1px 8px;border-radius:10px;font-weight:700;">
                        ช่วง <?php echo $thai_filter; ?>
                    </span>
                <?php else: ?>
                    <?php echo $thai_filter; ?>
                <?php endif; ?>
                <?php echo $filter_unit?' · <span style="color:#10b981;font-weight:700;">'.htmlspecialchars($filter_unit).'</span>':''; ?>
            </div>
        </div>
    </div>
    <div style="display:flex;align-items:center;gap:8px;">
        <a href="harvester_daily_dashboard.php" class="btn-today" style="background:#10b981;color:#fff;border:none;">
            <i class="fa-solid fa-gauge-high"></i> การเช็ครถตัดประจำวัน
        </a>
        <a href="?date=<?php echo date('Y-m-d'); ?>" class="btn-today">
            <i class="fa-solid fa-calendar-day"></i> วันนี้
        </a>
    </div>
</div>

<!-- Filter -->
<form method="GET" action="dashboard.php">
    <div class="filter-bar">
        <div class="fb-group">
            <span class="fb-lbl"><i class="fa-solid fa-calendar-day"></i> วันเริ่มต้น</span>
            <input type="date" name="date" class="fb-input"
                   value="<?php echo htmlspecialchars($filter_date); ?>"
                   max="<?php echo date('Y-m-d'); ?>" id="dateFrom">
        </div>
        <div class="fb-group">
            <span class="fb-lbl"><i class="fa-solid fa-calendar-check"></i> วันสิ้นสุด</span>
            <input type="date" name="date_end" class="fb-input"
                   value="<?php echo htmlspecialchars($filter_date_end); ?>"
                   max="<?php echo date('Y-m-d'); ?>" id="dateTo">
        </div>
        <div class="fb-group">
            <span class="fb-lbl"><i class="fa-solid fa-location-dot"></i> กรองตามหน่วย</span>
            <select name="unit" class="fb-input">
                <option value="">ทุกหน่วย</option>
                <?php foreach($all_units as $u): ?>
                <option value="<?php echo htmlspecialchars($u); ?>" <?php echo $filter_unit===$u?'selected':''; ?>>
                    <?php echo htmlspecialchars($u); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="fb-group">
            <span class="fb-lbl">ย้อนหลัง</span>
            <div style="display:flex;gap:5px;flex-wrap:wrap;">
                <button type="button" class="btn-shortcut" onclick="setRange(0,0)">วันนี้</button>
                <button type="button" class="btn-shortcut" onclick="setRange(1,1)">เมื่อวาน</button>
                <button type="button" class="btn-shortcut" onclick="setRange(6,0)">7 วัน</button>
                <button type="button" class="btn-shortcut" onclick="setRange(29,0)">30 วัน</button>
                <button type="button" class="btn-shortcut" onclick="setMonthRange()">เดือนนี้</button>
            </div>
        </div>
        <button type="submit" class="btn-filter">
            <i class="fa-solid fa-magnifying-glass"></i> ดูสรุป
        </button>
        <a href="export_posts_excel.php?date=<?php echo urlencode($filter_date); ?>&date_end=<?php echo urlencode($filter_date_end); ?>&unit=<?php echo urlencode($filter_unit); ?>&crop_year=<?php echo urlencode($crop_year); ?>" class="btn-export-excel" title="ส่งออกรายงานปัญหาอ้อยเป็นไฟล์ Excel">
            <i class="fa-solid fa-file-excel"></i> ส่งออก Excel
        </a>
    </div>
</form>

<!-- Stat Cards -->
<div class="stats">
    <div class="stat-card">
        <div class="stat-ico" style="background:#ede9fe;"><i class="fa-solid fa-file-lines" style="color:#6366f1;font-size:1.1rem;"></i></div>
        <div><div class="stat-num"><?php echo $s['total']; ?></div><div class="stat-lbl">โพสต์ทั้งหมด</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-ico" style="background:#fee2e2;"><i class="fa-solid fa-clock" style="color:#e11d48;font-size:1.1rem;"></i></div>
        <div><div class="stat-num" style="color:#e11d48;"><?php echo $s['pending']; ?></div><div class="stat-lbl">ยังไม่ดำเนินการ</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-ico" style="background:#d1fae5;"><i class="fa-solid fa-check-double" style="color:#059669;font-size:1.1rem;"></i></div>
        <div><div class="stat-num" style="color:#059669;"><?php echo $s['success']; ?></div><div class="stat-lbl">ดำเนินการแล้ว</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-ico" style="background:#e0f2fe;"><i class="fa-solid fa-location-dot" style="color:#0369a1;font-size:1.1rem;"></i></div>
        <div><div class="stat-num" style="color:#0369a1;"><?php echo $s['unit_count']; ?></div><div class="stat-lbl">หน่วยที่รายงาน</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-ico" style="background:#fef9c3;"><i class="fa-solid fa-tractor" style="color:#854d0e;font-size:1.1rem;"></i></div>
        <div><div class="stat-num" style="color:#854d0e;"><?php echo $s['truck_count']; ?></div><div class="stat-lbl">รถตัดที่เกี่ยวข้อง</div></div>
    </div>
</div>

<!-- Row 1: Donut + Unit Summary -->
<div class="grid2">

    <!-- Donut Chart -->
    <div class="card">
        <div class="card-hd">
            <div class="card-hd-l"><i class="fa-solid fa-chart-pie"></i><span>สัดส่วนสถานะ</span></div>
        </div>
        <?php
        $total   = max((int)$s['total'], 1);
        $success = (int)$s['success'];
        $pending = (int)$s['pending'];
        $pct_ok  = round($success / $total * 100);
        $pct_wait= 100 - $pct_ok;
        // SVG circle: r=40, circumference=251.2
        $circ = 251.2;
        $dash_ok   = round($success / $total * $circ, 1);
        $dash_wait = round($pending / $total * $circ, 1);
        ?>
        <div class="donut-wrap">
            <div class="donut">
                <svg width="110" height="110" viewBox="0 0 110 110">
                    <circle cx="55" cy="55" r="40" fill="none" stroke="#f1f5f9" stroke-width="14"/>
                    <?php if($pending > 0): ?>
                    <circle cx="55" cy="55" r="40" fill="none" stroke="#f97316" stroke-width="14"
                            stroke-dasharray="<?php echo $dash_wait; ?> <?php echo $circ - $dash_wait; ?>"
                            stroke-dashoffset="0"/>
                    <?php endif; ?>
                    <?php if($success > 0): ?>
                    <circle cx="55" cy="55" r="40" fill="none" stroke="#10b981" stroke-width="14"
                            stroke-dasharray="<?php echo $dash_ok; ?> <?php echo $circ - $dash_ok; ?>"
                            stroke-dashoffset="-<?php echo $dash_wait; ?>"/>
                    <?php endif; ?>
                </svg>
                <div class="donut-label">
                    <div class="donut-pct"><?php echo $pct_ok; ?>%</div>
                    <div class="donut-sub">เสร็จแล้ว</div>
                </div>
            </div>
            <div class="donut-legend">
                <div class="legend-item">
                    <div class="legend-dot" style="background:#10b981;"></div>
                    <div>ดำเนินการแล้ว <strong><?php echo $success; ?></strong> รายการ</div>
                </div>
                <div class="legend-item">
                    <div class="legend-dot" style="background:#f97316;"></div>
                    <div>รอดำเนินการ <strong><?php echo $pending; ?></strong> รายการ</div>
                </div>
                <div class="legend-item">
                    <div class="legend-dot" style="background:#e2e8f0;"></div>
                    <div>ทั้งหมด <strong><?php echo $s['total']; ?></strong> รายการ</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Unit Summary -->
    <div class="card">
        <div class="card-hd green">
            <div class="card-hd-l"><i class="fa-solid fa-location-dot"></i><span>สรุปรายหน่วย</span></div>
            <span class="cnt-badge"><?php echo count($by_unit); ?> หน่วย</span>
        </div>
        <?php if(empty($by_unit)): ?>
            <div class="empty-s"><i class="fa-solid fa-inbox"></i>ไม่มีข้อมูล</div>
        <?php else: ?>
            <?php
            $max_total = max(array_column($by_unit,'total'));
            foreach($by_unit as $u):
                $pct_s = $u['total'] > 0 ? round($u['success']/$u['total']*100) : 0;
                $pct_p = 100 - $pct_s;
            ?>
            <div class="unit-row">
                <div class="unit-name"><?php echo htmlspecialchars($u['target_unit']); ?></div>
                <div class="unit-bar-wrap">
                    <div class="progress-track">
                        <div class="progress-fill success" style="width:<?php echo $pct_s; ?>%;"></div>
                    </div>
                    <div class="unit-nums">
                        <span class="num-ok"><i class="fa-solid fa-check"></i> <?php echo $u['success']; ?></span>
                        <span class="num-wait"><i class="fa-solid fa-clock"></i> <?php echo $u['pending']; ?></span>
                        <span class="num-total">/ <?php echo $u['total']; ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<!-- Row 2: Top Problems + Posts Table -->
<div class="grid2" style="margin-bottom:18px;">

    <!-- Top Problems -->
    <div class="card">
        <div class="card-hd amber">
            <div class="card-hd-l"><i class="fa-solid fa-triangle-exclamation"></i><span>ปัญหาที่พบบ่อย</span></div>
            <span class="cnt-badge"><?php echo count($top_problems); ?> รายการ</span>
        </div>
        <?php if(empty($top_problems)): ?>
            <div class="empty-s"><i class="fa-solid fa-inbox"></i>ไม่มีข้อมูล</div>
        <?php else:
            $max_cnt = max(array_column($top_problems,'cnt'));
            foreach($top_problems as $idx => $p):
                $rank_cls = $idx===0?'top1':($idx===1?'top2':($idx===2?'top3':''));
                $bar_w = round($p['cnt']/$max_cnt*100);
        ?>
        <div class="prob-row">
            <div class="prob-rank <?php echo $rank_cls; ?>"><?php echo $idx+1; ?></div>
            <div class="prob-name"><?php echo htmlspecialchars($p['prob']); ?></div>
            <div class="prob-bar-wrap">
                <div class="prob-bar-fill" style="width:<?php echo $bar_w; ?>%;"></div>
            </div>
            <div class="prob-cnt"><?php echo $p['cnt']; ?></div>
        </div>
        <?php endforeach; endif; ?>
    </div>

    <!-- Quick Stats by unit pending -->
    <div class="card">
        <div class="card-hd red">
            <div class="card-hd-l"><i class="fa-solid fa-circle-exclamation"></i><span>หน่วยที่ยังรอดำเนินการ</span></div>
        </div>
        <?php
        $pending_units = array_filter($by_unit, fn($u) => $u['pending'] > 0);
        if(empty($pending_units)): ?>
            <div class="empty-s" style="padding:24px;">
                <i class="fa-solid fa-circle-check" style="color:#10b981;"></i>
                <div style="color:#10b981;font-weight:700;margin-top:6px;">ทุกหน่วยดำเนินการครบแล้ว!</div>
            </div>
        <?php else:
            usort($pending_units, fn($a,$b) => $b['pending'] - $a['pending']);
            foreach($pending_units as $u):
                $pct = round($u['pending']/$u['total']*100);
        ?>
        <div class="unit-row">
            <div class="unit-name"><?php echo htmlspecialchars($u['target_unit']); ?></div>
            <div class="unit-bar-wrap">
                <div class="progress-track">
                    <div class="progress-fill pending" style="width:<?php echo $pct; ?>%;background:#f97316;"></div>
                </div>
                <div class="unit-nums">
                    <span class="num-wait"><i class="fa-solid fa-clock"></i> รอ <?php echo $u['pending']; ?> รายการ</span>
                    <span class="num-total">จาก <?php echo $u['total']; ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </div>

</div>

<!-- Posts Table -->
<div class="card card-full">
    <div class="card-hd">
        <div class="card-hd-l"><i class="fa-solid fa-table-list"></i><span>รายการโพสต์ทั้งหมด — <?php echo $thai_filter; ?></span></div>
        <span class="cnt-badge"><?php echo count($posts); ?> รายการ</span>
    </div>
    <?php if(empty($posts)): ?>
        <div class="empty-s"><i class="fa-solid fa-clipboard-list"></i>ไม่มีโพสต์ในวันที่เลือก</div>
    <?php else: ?>
    <div class="tbl-w">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>เวลา</th>
                    <th>ผู้รายงาน</th>
                    <th>หน่วย</th>
                    <th>เบอร์รถ</th>
                    <th>ปัญหาที่พบ</th>
                    <th>สถานะ</th>
                    <th>ภาพ</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($posts as $i => $p): ?>
            <tr class="<?php echo $p['job_status']==='pending'?'row-pending':''; ?>">
                <td style="color:#94a3b8;font-size:.75rem;"><?php echo $i+1; ?></td>
                <td style="font-size:.78rem;color:#64748b;"><?php echo date('H:i น.', strtotime($p['created_at'])); ?></td>
                <td>
                    <div class="emp-n"><?php echo htmlspecialchars($p['emp_name']); ?></div>
                    <div class="emp-u"><?php echo htmlspecialchars($p['emp_unit']); ?></div>
                </td>
                <td style="font-size:.82rem;color:#334155;"><?php echo htmlspecialchars($p['target_unit']); ?></td>
                <td><span class="truck"><?php echo htmlspecialchars($p['truck_number']); ?></span></td>
                <td>
                    <?php
                    $probs = array_filter([
                        $p['problem_detail'],
                        $p['problem_detail_2'],
                        $p['problem_detail_3'],
                    ]);
                    foreach($probs as $pr):
                    ?>
                    <span class="prob-tag"><?php echo htmlspecialchars($pr); ?></span>
                    <?php endforeach; ?>
                </td>
                <td>
                    <?php if($p['job_status']==='success'): ?>
                        <span class="st-success"><i class="fa-solid fa-check-double"></i> เสร็จแล้ว</span>
                    <?php else: ?>
                        <span class="st-pending"><i class="fa-solid fa-clock"></i> รอดำเนินการ</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="img-thumbs">
                    <?php foreach(['post_image','post_image_2','post_image_3'] as $img):
                        if(!empty($p[$img])): ?>
                        <img class="img-thumb" src="<?php echo htmlspecialchars($p[$img]); ?>"
                             onclick="openLB(this.src)" title="ภาพประกอบ">
                    <?php endif; endforeach; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

</div>
</div>

<div class="lightbox" id="lb" onclick="closeLB()">
    <button class="lb-close"><i class="fa-solid fa-xmark"></i></button>
    <img src="" id="lb-img" alt="">
</div>

<script>
function openLB(src){ document.getElementById('lb-img').src=src; document.getElementById('lb').classList.add('show'); }
function closeLB(){ document.getElementById('lb').classList.remove('show'); }
document.addEventListener('keydown', e => { if(e.key==='Escape') closeLB(); });

// ── Date Range Shortcuts ──
function fmtDate(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth()+1).padStart(2,'0');
    const dd = String(d.getDate()).padStart(2,'0');
    return y+'-'+m+'-'+dd;
}
function setRange(daysBack, daysBackEnd) {
    const today = new Date();
    const from  = new Date(today); from.setDate(today.getDate() - daysBack);
    const to    = new Date(today); to.setDate(today.getDate()   - daysBackEnd);
    document.getElementById('dateFrom').value = fmtDate(from);
    document.getElementById('dateTo').value   = fmtDate(to);
    document.querySelector('form').submit();
}
function setMonthRange() {
    const today = new Date();
    const from  = new Date(today.getFullYear(), today.getMonth(), 1);
    document.getElementById('dateFrom').value = fmtDate(from);
    document.getElementById('dateTo').value   = fmtDate(today);
    document.querySelector('form').submit();
}
// sync: ถ้า dateFrom > dateTo ให้ดึง dateTo ตาม
document.getElementById('dateFrom').addEventListener('change', function(){
    if(this.value > document.getElementById('dateTo').value){
        document.getElementById('dateTo').value = this.value;
    }
});
document.getElementById('dateTo').addEventListener('change', function(){
    if(this.value < document.getElementById('dateFrom').value){
        document.getElementById('dateFrom').value = this.value;
    }
});
</script>
</div></div><!-- main-content / page-wrapper -->
</body>
</html>