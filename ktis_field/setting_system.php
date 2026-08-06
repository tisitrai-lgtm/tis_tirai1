<?php
/**
 * setting_system.php — ตั้งค่าระบบ (Admin only)
 * TIS SMART FIELD - ฝ่ายไร่
 */
require_once 'config.php';
session_start();

if(!isset($_SESSION['emp_id']) || $_SESSION['emp_level'] !== 'a'){
    header("location: login.php"); exit;
}
header('Content-Type: text/html; charset=utf-8');

// ── ดึงข้อมูลทั้งหมด ──
$problems    = $conn->query("SELECT * FROM problem_types ORDER BY problem_id ASC")->fetchAll();
$zones       = $conn->query("SELECT * FROM zones ORDER BY zone_id ASC")->fetchAll();
$cut_items   = $conn->query("SELECT * FROM check_items_cut ORDER BY section_no ASC, item_id ASC")->fetchAll();
$field_items = $conn->query("SELECT * FROM check_items_field ORDER BY item_id ASC")->fetchAll();

// ── สถิติด่วน ──
$stat_posts   = $conn->query("SELECT COUNT(*) FROM posts WHERE crop_year='".$_SESSION['crop_year']."'")->fetchColumn();
$stat_pending = $conn->query("SELECT COUNT(*) FROM posts WHERE crop_year='".$_SESSION['crop_year']."' AND job_status='pending'")->fetchColumn();
$stat_emps    = $conn->query("SELECT COUNT(*) FROM employee")->fetchColumn();
$stat_checks  = 0;
try { $stat_checks = $conn->query("SELECT COUNT(*) FROM check_sessions WHERE crop_year='".$_SESSION['crop_year']."'")->fetchColumn(); } catch(Exception $e){}

// ── system settings ──
$settings_raw = [];
try { $settings_raw = $conn->query("SELECT * FROM system_settings ORDER BY setting_group, setting_key")->fetchAll(); } catch(Exception $e){}
$settings = [];
foreach($settings_raw as $s) $settings[$s['setting_key']] = $s;

include 'includes/nav_u_header.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
<link rel="icon" type="image/png" href="icon/iconweb.png">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ตั้งค่าระบบ - TIS SMART FIELD</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;}
body{font-family:'Sarabun',sans-serif;background:#f1f5f9;margin:0;}
.pw{max-width:1100px;margin:24px auto;padding:0 16px 60px;}

.ph{display:flex;align-items:center;gap:12px;margin-bottom:24px;flex-wrap:wrap;}
.ph-icon{width:46px;height:46px;background:linear-gradient(135deg,#e11d48,#be123c);border-radius:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.ph-icon i{color:#fff;font-size:1.3rem;}
.ph-title{font-size:1.15rem;font-weight:700;color:#1e293b;}
.ph-sub{font-size:.78rem;color:#64748b;}

.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:28px;}
.stat-card{background:#fff;border-radius:11px;border:.5px solid #e2e8f0;padding:16px;display:flex;align-items:center;gap:12px;}
.stat-ico{width:40px;height:40px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.stat-num{font-size:1.5rem;font-weight:700;color:#1e293b;line-height:1;}
.stat-lbl{font-size:.75rem;color:#64748b;margin-top:3px;}

.section-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;}
.card{background:#fff;border-radius:13px;border:.5px solid #e2e8f0;overflow:hidden;}
.card-full{grid-column:1/-1;}
.card-hd{background:#1e293b;padding:13px 18px;display:flex;align-items:center;justify-content:space-between;border-bottom:3px solid #e11d48;}
.card-hd-l{display:flex;align-items:center;gap:8px;}
.card-hd-l i{color:#e11d48;}
.card-hd-l span{color:#f8fafc;font-weight:700;font-size:.92rem;}
.card-hd.green{border-bottom-color:#10b981;} .card-hd.green i{color:#10b981;}
.card-hd.blue{border-bottom-color:#3b82f6;}  .card-hd.blue i{color:#3b82f6;}
.card-hd.purple{border-bottom-color:#8b5cf6;}.card-hd.purple i{color:#8b5cf6;}
.cnt-badge{background:rgba(255,255,255,.12);color:#cbd5e1;font-size:.73rem;font-weight:700;padding:2px 9px;border-radius:10px;}
.card-bd{padding:16px;}

.add-row{display:flex;gap:8px;margin-bottom:14px;}
.add-input{flex:1;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:.88rem;font-family:'Sarabun',sans-serif;background:#f8fafc;color:#1e293b;outline:none;transition:border-color .15s;}
.add-input:focus{border-color:#e11d48;background:#fff;}
.add-input.green:focus{border-color:#10b981;}
.btn-add{padding:9px 16px;background:#e11d48;color:#fff;border:none;border-radius:7px;font-weight:700;font-size:.85rem;font-family:'Sarabun',sans-serif;cursor:pointer;white-space:nowrap;display:flex;align-items:center;gap:5px;transition:background .15s;}
.btn-add:hover{background:#be123c;}
.btn-add.green{background:#10b981;} .btn-add.green:hover{background:#059669;}

.list-item{display:flex;align-items:center;justify-content:space-between;padding:9px 12px;border-radius:8px;margin-bottom:6px;background:#f8fafc;border:1px solid #f1f5f9;transition:background .13s;}
.list-item:hover{background:#f1f5f9;}
.list-item-text{font-size:.87rem;color:#1e293b;font-weight:600;}
.list-item-sub{font-size:.75rem;color:#94a3b8;margin-top:1px;}
.btn-del{background:none;border:1px solid #fecaca;border-radius:6px;padding:5px 9px;cursor:pointer;color:#e11d48;font-size:.78rem;transition:all .13s;font-family:'Sarabun',sans-serif;}
.btn-del:hover{background:#fee2e2;}
.empty-list{text-align:center;padding:24px;color:#94a3b8;font-size:.85rem;}

.zone-add-row{display:grid;grid-template-columns:120px 1fr auto;gap:8px;margin-bottom:14px;}

.alert-box{padding:11px 14px;border-radius:8px;font-size:.85rem;font-weight:600;margin-bottom:14px;display:flex;align-items:center;gap:8px;}
.alert-success{background:#d1fae5;border:1px solid #a7f3d0;color:#065f46;}
.alert-error{background:#fee2e2;border:1px solid #fecaca;color:#991b1b;}

.scroll-list-7{max-height:420px;overflow-y:auto;padding-right:4px;}
.scroll-list-7::-webkit-scrollbar{width:5px;}
.scroll-list-7::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:10px;}
.scroll-list-7::-webkit-scrollbar-track{background:#f1f5f9;}

#log-container::-webkit-scrollbar{width:7px;}
#log-container::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:4px;}
#log-container table thead th{position:sticky;top:0;background:#f1f5f9;z-index:1;border-bottom:2px solid #e2e8f0;}

@media(max-width:768px){
    .stats{grid-template-columns:repeat(2,1fr);}
    .section-grid{grid-template-columns:1fr;}
    .card-full{grid-column:1;}
    .zone-add-row{grid-template-columns:1fr 1fr;grid-template-rows:auto auto;}
    .zone-add-row .btn-add{grid-column:1/-1;}
}
</style>
<link rel="stylesheet" href="global_smoothness.css">
</head>
<body>
<div class="content-wrapper">
<div class="pw">

<div class="ph">
    <div class="ph-icon"><i class="fa-solid fa-sliders"></i></div>
    <div>
        <div class="ph-title">ตั้งค่าระบบ</div>
        <div class="ph-sub">ปีการผลิต <?php echo htmlspecialchars($_SESSION['crop_year']); ?> · จัดการข้อมูลอ้างอิงและดูประวัติระบบ</div>
    </div>
</div>

<div id="alert-area"></div>

<!-- Stat Cards -->
<div class="stats">
    <div class="stat-card">
        <div class="stat-ico" style="background:#fef2f2;"><i class="fa-solid fa-file-lines" style="color:#e11d48;font-size:1.1rem;"></i></div>
        <div><div class="stat-num"><?php echo $stat_posts; ?></div><div class="stat-lbl">โพสต์ทั้งหมด (ปีนี้)</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-ico" style="background:#fff7ed;"><i class="fa-solid fa-clock" style="color:#f97316;font-size:1.1rem;"></i></div>
        <div><div class="stat-num" style="color:#f97316;"><?php echo $stat_pending; ?></div><div class="stat-lbl">รอดำเนินการ</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-ico" style="background:#f0fdf4;"><i class="fa-solid fa-users" style="color:#10b981;font-size:1.1rem;"></i></div>
        <div><div class="stat-num" style="color:#10b981;"><?php echo $stat_emps; ?></div><div class="stat-lbl">พนักงานในระบบ</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-ico" style="background:#eff6ff;"><i class="fa-solid fa-tractor" style="color:#3b82f6;font-size:1.1rem;"></i></div>
        <div><div class="stat-num" style="color:#3b82f6;"><?php echo $stat_checks; ?></div><div class="stat-lbl">บันทึกรถตัด (ปีนี้)</div></div>
    </div>
</div>

<div class="section-grid">
    <?php include 'includes/settings/problems.php'; ?>
    <?php include 'includes/settings/zones.php'; ?>
    <?php include 'includes/settings/check_items.php'; ?>
    <?php include 'includes/settings/harvesters.php'; ?>
    <?php include 'includes/settings/system_settings.php'; ?>
    <?php include 'includes/settings/logs.php'; ?>
</div>

</div>
</div>

<?php include 'includes/nav_u_footer.php'; ?>
<?php include 'includes/settings/settings_scripts.php'; ?>
</body>
</html>