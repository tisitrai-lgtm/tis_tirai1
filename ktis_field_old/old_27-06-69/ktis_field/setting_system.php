<?php
/**
 * setting_system.php — ตั้งค่าระบบ (Admin only)
 * TIS SMART FIELD - ฝ่ายไร่
 */
require_once 'config.php';
session_start();

if(!isset($_SESSION['emp_id']) || $_SESSION['emp_level'] !== 'a'){
    header("location: ../login.php"); exit;
}

header('Content-Type: text/html; charset=utf-8');

// ── ดึงข้อมูลทั้งหมด ──
$problems = $conn->query("SELECT * FROM problem_types ORDER BY problem_id ASC")->fetchAll();
$zones    = $conn->query("SELECT * FROM zones ORDER BY zone_id ASC")->fetchAll();
$logs     = $conn->query("SELECT sl.*, e.emp_name FROM system_logs sl LEFT JOIN employee e ON sl.action_by = e.emp_id ORDER BY sl.log_id DESC LIMIT 50")->fetchAll();
// ── check items ──
$cut_items   = $conn->query("SELECT * FROM check_items_cut   ORDER BY item_id ASC")->fetchAll();
$field_items = $conn->query("SELECT * FROM check_items_field ORDER BY item_id ASC")->fetchAll();
// ── สถิติด่วน ──
$stat_posts    = $conn->query("SELECT COUNT(*) FROM posts WHERE crop_year='".$_SESSION['crop_year']."'")->fetchColumn();
$stat_pending  = $conn->query("SELECT COUNT(*) FROM posts WHERE crop_year='".$_SESSION['crop_year']."' AND job_status='pending'")->fetchColumn();
$stat_emps     = $conn->query("SELECT COUNT(*) FROM employee")->fetchColumn();
$stat_checks   = $conn->query("SELECT COUNT(*) FROM harvester_checks WHERE crop_year='".$_SESSION['crop_year']."'")->fetchColumn();

// ── crop years ที่มีอยู่ในระบบ ──
$crop_years = $conn->query("SELECT DISTINCT crop_year FROM posts ORDER BY crop_year DESC")->fetchAll(PDO::FETCH_COLUMN);

// ── system settings ──
$settings_raw = [];
try {
    $settings_raw = $conn->query("SELECT * FROM system_settings ORDER BY setting_group, setting_key")->fetchAll();
} catch(Exception $e) { /* ตารางยังไม่มี */ }
$settings = [];
foreach($settings_raw as $s) { $settings[$s['setting_key']] = $s; }

include 'includes/nav_u_header.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
<link rel="icon" type="image/jpeg" href="icon/iconweb.png">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ตั้งค่าระบบ - TIS SMART FIELD</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;}
body{font-family:'Sarabun',sans-serif;background:#f1f5f9;margin:0;}
.pw{max-width:1100px;margin:24px auto;padding:0 16px 60px;}

/* page header */
.ph{display:flex;align-items:center;gap:12px;margin-bottom:24px;flex-wrap:wrap;}
.ph-icon{width:46px;height:46px;background:linear-gradient(135deg,#e11d48,#be123c);border-radius:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.ph-icon i{color:#fff;font-size:1.3rem;}
.ph-title{font-size:1.15rem;font-weight:700;color:#1e293b;}
.ph-sub{font-size:.78rem;color:#64748b;}

/* stat cards */
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:28px;}
.stat-card{background:#fff;border-radius:11px;border:.5px solid #e2e8f0;padding:16px;display:flex;align-items:center;gap:12px;}
.stat-ico{width:40px;height:40px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.stat-num{font-size:1.5rem;font-weight:700;color:#1e293b;line-height:1;}
.stat-lbl{font-size:.75rem;color:#64748b;margin-top:3px;}

/* section cards */
.section-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;}
.card{background:#fff;border-radius:13px;border:.5px solid #e2e8f0;overflow:hidden;}
.card-full{grid-column:1/-1;}
.card-hd{background:#1e293b;padding:13px 18px;display:flex;align-items:center;justify-content:space-between;border-bottom:3px solid #e11d48;}
.card-hd-l{display:flex;align-items:center;gap:8px;}
.card-hd-l i{color:#e11d48;}
.card-hd-l span{color:#f8fafc;font-weight:700;font-size:.92rem;}
.card-hd.green{border-bottom-color:#10b981;}
.card-hd.green i{color:#10b981;}
.card-hd.blue{border-bottom-color:#3b82f6;}
.card-hd.blue i{color:#3b82f6;}
.card-hd.purple{border-bottom-color:#8b5cf6;}
.card-hd.purple i{color:#8b5cf6;}
.cnt-badge{background:rgba(255,255,255,.12);color:#cbd5e1;font-size:.73rem;font-weight:700;padding:2px 9px;border-radius:10px;}
.card-bd{padding:16px;}

/* input row */
.add-row{display:flex;gap:8px;margin-bottom:14px;}
.add-input{flex:1;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:.88rem;font-family:'Sarabun',sans-serif;background:#f8fafc;color:#1e293b;outline:none;}
.add-input:focus{border-color:#e11d48;background:#fff;}
.add-input.green:focus{border-color:#10b981;}
.btn-add{padding:9px 16px;background:#e11d48;color:#fff;border:none;border-radius:7px;font-weight:700;font-size:.85rem;font-family:'Sarabun',sans-serif;cursor:pointer;white-space:nowrap;display:flex;align-items:center;gap:5px;transition:background .15s;}
.btn-add:hover{background:#be123c;}
.btn-add.green{background:#10b981;}
.btn-add.green:hover{background:#059669;}

/* list items */
.list-item{display:flex;align-items:center;justify-content:space-between;padding:9px 12px;border-radius:8px;margin-bottom:6px;background:#f8fafc;border:1px solid #f1f5f9;transition:background .13s;}
.list-item:hover{background:#f1f5f9;}
.list-item-text{font-size:.87rem;color:#1e293b;font-weight:600;}
.list-item-sub{font-size:.75rem;color:#94a3b8;margin-top:1px;}
.btn-del{background:none;border:1px solid #fecaca;border-radius:6px;padding:5px 9px;cursor:pointer;color:#e11d48;font-size:.78rem;transition:all .13s;}
.btn-del:hover{background:#fee2e2;}
.empty-list{text-align:center;padding:24px;color:#94a3b8;font-size:.85rem;}

/* logs table */
.log-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;min-width:600px;}
thead th{background:#f8fafc;color:#475569;font-size:.75rem;font-weight:700;padding:9px 12px;text-align:left;border-bottom:1px solid #e2e8f0;white-space:nowrap;}
tbody td{padding:9px 12px;border-bottom:1px solid #f8fafc;font-size:.82rem;color:#334155;vertical-align:middle;}
tbody tr:last-child td{border-bottom:none;}
tbody tr:hover{background:#f8fafc;}
.action-badge{display:inline-flex;align-items:center;gap:4px;font-size:.72rem;font-weight:700;padding:2px 8px;border-radius:4px;}
.badge-edit{background:#e0f2fe;color:#0369a1;}
.badge-delete{background:#fee2e2;color:#991b1b;}
.badge-create{background:#d1fae5;color:#065f46;}
.badge-status{background:#fef9c3;color:#713f12;}
.badge-other{background:#f1f5f9;color:#475569;}

/* zone input 2 col */
.zone-add-row{display:grid;grid-template-columns:120px 1fr auto;gap:8px;margin-bottom:14px;}

/* alert */
.alert-box{padding:11px 14px;border-radius:8px;font-size:.85rem;font-weight:600;margin-bottom:14px;display:flex;align-items:center;gap:8px;}
.alert-success{background:#d1fae5;border:1px solid #a7f3d0;color:#065f46;}
.alert-error{background:#fee2e2;border:1px solid #fecaca;color:#991b1b;}

@media(max-width:768px){
    .stats{grid-template-columns:repeat(2,1fr);}
    .section-grid{grid-template-columns:1fr;}
    .card-full{grid-column:1;}
    .zone-add-row{grid-template-columns:1fr 1fr;grid-template-rows:auto auto;}
    .zone-add-row .btn-add{grid-column:1/-1;}
}
/* ทำให้หัวตารางค้างอยู่ด้านบนเวลาเลื่อน */
    #log-container table thead th {
        position: sticky;
        top: 0;
        background: #f1f5f9; /* สีพื้นหลังของหัวตาราง */
        z-index: 1;
        padding: 10px;
        border-bottom: 2px solid #e2e8f0;
    }
    
    /* ตกแต่งแถบ scroll ให้ดูสวยงาม (สำหรับ Chrome/Safari) */
    #log-container::-webkit-scrollbar {
        width: 8px;
    }
    #log-container::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    .scroll-list-7 {
    max-height: 460px;
    overflow-y: auto;
    padding-right: 4px;
}

/* scrollbar สวยขึ้นเล็กน้อย */
.scroll-list-7::-webkit-scrollbar {
    width: 6px;
}
.scroll-list-7::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}
.scroll-list-7::-webkit-scrollbar-track {
    background: #f1f5f9;
}
</style>
    <link rel="stylesheet" href="global_smoothness.css">
</head>
<body>
<div class="content-wrapper">
<div class="pw">

<!-- Page Header -->
<div class="ph">
    <div class="ph-icon"><i class="fa-solid fa-sliders"></i></div>
    <div>
        <div class="ph-title">ตั้งค่าระบบ</div>
        <div class="ph-sub">ปีการผลิต <?php echo htmlspecialchars($_SESSION['crop_year']); ?> · จัดการข้อมูลอ้างอิงและดูประวัติระบบ</div>
    </div>
</div>

<!-- Alert -->
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

    <!-- ── Problem Types ── -->
    <div class="card">
        <div class="card-hd">
            <div class="card-hd-l"><i class="fa-solid fa-triangle-exclamation"></i><span>ประเภทปัญหาที่พบ</span></div>
            <span class="cnt-badge" id="prob-count"><?php echo count($problems); ?> รายการ</span>
        </div>
        <div class="card-bd">
            <div class="add-row">
                <input type="text" id="new-prob" class="add-input" placeholder="ชื่อปัญหาใหม่ เช่น อ้อยสกปรก">
                <button class="btn-add" onclick="addProblem()"><i class="fa-solid fa-plus"></i> เพิ่ม</button>
            </div>
             <div id="prob-list" class="scroll-list-7">
                <?php foreach($problems as $p): ?>
                <div class="list-item" id="prob-<?php echo $p['problem_id']; ?>">
                    <div>
                        <div class="list-item-text"><i class="fa-solid fa-circle-exclamation" style="color:#e11d48;font-size:.75rem;margin-right:5px;"></i><?php echo htmlspecialchars($p['problem_name']); ?></div>
                    </div>
                    <button class="btn-del" onclick="deleteProblem(<?php echo $p['problem_id']; ?>)"><i class="fa-solid fa-trash-can"></i> ลบ</button>
                </div>
                <?php endforeach; ?>
                <?php if(empty($problems)): ?><div class="empty-list"><i class="fa-solid fa-inbox" style="font-size:1.5rem;display:block;margin-bottom:6px;"></i>ยังไม่มีข้อมูล</div><?php endif; ?>
            </div>
        </div>
    </div>
<!-- ── Check Items: ชุดใบมีด/ตัด ── -->
<div class="card">
    <div class="card-hd blue">
        <div class="card-hd-l"><i class="fa-solid fa-fan"></i><span>รายการตรวจ: ชุดใบมีด/ตัด</span></div>
        <span class="cnt-badge" id="cut-count"><?php echo count($cut_items); ?> รายการ</span>
    </div>
    <div class="card-bd">
        <div class="add-row">
            <input type="text" id="new-cut" class="add-input" placeholder="เช่น ใบพัดสับท่อน"
                   style="--focus:#3b82f6;" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
            <button class="btn-add" style="background:#3b82f6;" onmouseover="this.style.background='#2563eb'" onmouseout="this.style.background='#3b82f6'" onclick="addCutItem()">
                <i class="fa-solid fa-plus"></i> เพิ่ม
            </button>
        </div>
        <div id="cut-list" class="scroll-list-7">
            <?php foreach($cut_items as $c): ?>
            <div class="list-item" id="cut-<?php echo $c['item_id']; ?>">
                <div class="list-item-text">
                    <i class="fa-solid fa-screwdriver-wrench" style="color:#3b82f6;font-size:.75rem;margin-right:5px;"></i>
                    <?php echo htmlspecialchars($c['item_name_cut']); ?>
                </div>
                <div style="display:flex;gap:6px;">
                    <button class="btn-del" onclick="deleteCutItem(<?php echo $c['item_id']; ?>)">
                        <i class="fa-solid fa-trash-can"></i> ลบ
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if(empty($cut_items)): ?>
                <div class="empty-list"><i class="fa-solid fa-inbox" style="font-size:1.5rem;display:block;margin-bottom:6px;"></i>ยังไม่มีข้อมูล</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ── Check Items: สภาพแปลงอ้อย ── -->
<div class="card">
    <div class="card-hd" style="border-bottom-color:#f59e0b;">
        <div class="card-hd-l">
            <i class="fa-solid fa-leaf" style="color:#f59e0b;"></i>
            <span>รายการตรวจ: สภาพแปลงอ้อย</span>
        </div>
        <span class="cnt-badge" id="field-count"><?php echo count($field_items); ?> รายการ</span>
    </div>
    <div class="card-bd">
        <div class="add-row">
            <input type="text" id="new-field" class="add-input" placeholder="เช่น อ้อยล้ม, หญ้ารก"
                   onfocus="this.style.borderColor='#f59e0b'" onblur="this.style.borderColor='#e2e8f0'">
            <button class="btn-add" style="background:#f59e0b;" onmouseover="this.style.background='#d97706'" onmouseout="this.style.background='#f59e0b'" onclick="addFieldItem()">
                <i class="fa-solid fa-plus"></i> เพิ่ม
            </button>
        </div>
        <div id="field-list" class="scroll-list-7">
            <?php foreach($field_items as $f): ?>
            <div class="list-item" id="field-<?php echo $f['item_id']; ?>">
                <div class="list-item-text">
                    <i class="fa-solid fa-seedling" style="color:#f59e0b;font-size:.75rem;margin-right:5px;"></i>
                    <?php echo htmlspecialchars($f['item_name_field']); ?>
                </div>
                <div style="display:flex;gap:6px;">
                    <button class="btn-del" onclick="deleteFieldItem(<?php echo $f['item_id']; ?>)">
                        <i class="fa-solid fa-trash-can"></i> ลบ
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if(empty($field_items)): ?>
                <div class="empty-list"><i class="fa-solid fa-inbox" style="font-size:1.5rem;display:block;margin-bottom:6px;"></i>ยังไม่มีข้อมูล</div>
            <?php endif; ?>
        </div>
    </div>
</div>               
    <!-- ── Zones ── -->
    <div class="card">
        <div class="card-hd green">
            <div class="card-hd-l"><i class="fa-solid fa-location-dot"></i><span>หน่วยส่งเสริม (Zones)</span></div>
            <span class="cnt-badge" id="zone-count"><?php echo count($zones); ?> หน่วย</span>
        </div>
        <div class="card-bd">
            <div class="zone-add-row">
                <input type="text" id="new-zone-id" class="add-input green" placeholder="รหัส เช่น 235">
                <input type="text" id="new-zone-name" class="add-input green" placeholder="ชื่อหน่วย เช่น วังทอง">
                <button class="btn-add green" onclick="addZone()"><i class="fa-solid fa-plus"></i> เพิ่ม</button>
            </div>
            <div id="zone-list" style="max-height:320px;overflow-y:auto;">
                <?php foreach($zones as $z): ?>
                <div class="list-item" id="zone-<?php echo htmlspecialchars($z['zone_id']); ?>">
                    <div>
                        <div class="list-item-text">
                            <span style="background:#e0f2fe;color:#0369a1;padding:1px 7px;border-radius:4px;font-size:.75rem;font-weight:700;margin-right:6px;"><?php echo htmlspecialchars($z['zone_id']); ?></span>
                            <?php echo htmlspecialchars($z['zone_name']); ?>
                        </div>
                    </div>
                    <button class="btn-del" onclick="deleteZone('<?php echo htmlspecialchars($z['zone_id']); ?>')"><i class="fa-solid fa-trash-can"></i> ลบ</button>
                </div>
                <?php endforeach; ?>
                <?php if(empty($zones)): ?><div class="empty-list">ยังไม่มีข้อมูล</div><?php endif; ?>
            </div>
        </div>
    </div>
   <!-- ── System Settings ── -->
    <div class="card card-full">
        <div class="card-hd" style="border-bottom-color:#f59e0b;">
            <div class="card-hd-l">
                <i class="fa-solid fa-sliders" style="color:#f59e0b;"></i>
                <span>ตั้งค่าระบบ (System Settings)</span>
            </div>
            <button onclick="saveAllSettings()" style="background:#f59e0b;border:none;color:#fff;padding:6px 14px;border-radius:7px;font-weight:700;font-size:.82rem;font-family:'Sarabun',sans-serif;cursor:pointer;display:flex;align-items:center;gap:5px;" onmouseover="this.style.background='#d97706'" onmouseout="this.style.background='#f59e0b'">
                <i class="fa-solid fa-floppy-disk"></i> บันทึกทั้งหมด
            </button>
        </div>
        <div class="card-bd">
            <?php if(empty($settings)): ?>
                <div class="empty-list" style="padding:24px;">
                    <i class="fa-solid fa-triangle-exclamation" style="color:#f59e0b;font-size:1.5rem;display:block;margin-bottom:8px;"></i>
                    ยังไม่มีตาราง system_settings — กรุณารัน <code>create_system_settings.sql</code> ก่อน
                </div>
            <?php else: ?>
            <?php
            $groups = ['company'=>'ข้อมูลบริษัท','system'=>'ตั้งค่าระบบ','general'=>'ทั่วไป'];
            $grouped = [];
            foreach($settings as $k=>$s) { $grouped[$s['setting_group']][] = $s; }
            foreach($groups as $gkey=>$glabel):
                if(empty($grouped[$gkey])) continue;
            ?>
            <div style="margin-bottom:20px;">
                <div style="font-size:.78rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.8px;margin-bottom:10px;padding-bottom:6px;border-bottom:1px solid #f1f5f9;">
                    <?php echo $glabel; ?>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:10px;">
                <?php foreach($grouped[$gkey] as $s): ?>
                <div style="display:flex;flex-direction:column;gap:5px;">
                    <label style="font-size:.8rem;font-weight:700;color:#374151;"><?php echo htmlspecialchars($s['setting_label']); ?></label>
                    <input type="text" class="add-input setting-input"
                           data-key="<?php echo htmlspecialchars($s['setting_key']); ?>"
                           value="<?php echo htmlspecialchars($s['setting_value']); ?>"
                           onfocus="this.style.borderColor='#f59e0b'" onblur="this.style.borderColor='#e2e8f0'">
                    <span style="font-size:.7rem;color:#94a3b8;font-family:monospace;"><?php echo htmlspecialchars($s['setting_key']); ?></span>
                </div>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── System Logs ── -->
   <div class="card card-full">
    <div class="card-hd purple">
        <div class="card-hd-l"><i class="fa-solid fa-clock-rotate-left"></i><span>ประวัติการทำงาน</span></div>
        <select id="log-limit" onchange="loadLogs()" style="background:#fff; border-radius:5px; border:none; padding:4px 8px; font-size:0.8rem; cursor:pointer;">
            <option value="50"selected>50 รายการ</option>
            <option value="100">100 รายการ</option>
            <option value="999999">ทั้งหมด</option>
        </select>
    </div>
    <div class="card-bd" id="log-container" style="max-height: 400px; overflow-y: auto; overflow-x: auto;">
        </div>
</div>

      



<script>
function showAlert(msg, type='success') {
    const a = document.getElementById('alert-area');
    a.innerHTML = `<div class="alert-box alert-${type}"><i class="fa-solid fa-${type==='success'?'circle-check':'circle-exclamation'}"></i>${msg}</div>`;
    setTimeout(() => { a.innerHTML = ''; }, 3500);
}

// ── Problem Types ──
function addProblem() {
    const name = document.getElementById('new-prob').value.trim();
    if(!name) { showAlert('กรุณากรอกชื่อปัญหา', 'error'); return; }
    const fd = new FormData();
    fd.append('problem_name', name);
    fetch('api_problem_types.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if(d.status === 'success') {
            document.getElementById('new-prob').value = '';
            const item = `<div class="list-item" id="prob-${d.new_id}">
                <div><div class="list-item-text"><i class="fa-solid fa-circle-exclamation" style="color:#e11d48;font-size:.75rem;margin-right:5px;"></i>${name}</div></div>
                <button class="btn-del" onclick="deleteProblem(${d.new_id})"><i class="fa-solid fa-trash-can"></i> ลบ</button>
            </div>`;
            const list = document.getElementById('prob-list');
            const empty = list.querySelector('.empty-list');
            if(empty) empty.remove();
            list.insertAdjacentHTML('beforeend', item);
            updateCount('prob-count', document.querySelectorAll('[id^="prob-"]').length, 'รายการ');
            showAlert('เพิ่มประเภทปัญหา "' + name + '" เรียบร้อย');
        } else { showAlert(d.message, 'error'); }
    });
}

function deleteProblem(id) {
    if(!confirm('ยืนยันลบประเภทปัญหานี้?')) return;
    fetch('api_problem_types.php', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'problem_id=' + id
    })
    .then(r => r.json())
    .then(d => {
        if(d.status === 'success') {
            const el = document.getElementById('prob-' + id);
            if(el) { el.style.opacity='0'; el.style.transition='opacity .2s'; setTimeout(()=>el.remove(), 200); }
            showAlert('ลบเรียบร้อยแล้ว');
        } else { showAlert(d.message, 'error'); }
    });
}

// ── Zones ──
function addZone() {
    const zid  = document.getElementById('new-zone-id').value.trim();
    const zname = document.getElementById('new-zone-name').value.trim();
    if(!zid || !zname) { showAlert('กรุณากรอกรหัสและชื่อหน่วยให้ครบ', 'error'); return; }
    const fd = new FormData();
    fd.append('zone_id', zid);
    fd.append('zone_name', zname);
    fetch('api_zones.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if(d.status === 'success') {
            document.getElementById('new-zone-id').value = '';
            document.getElementById('new-zone-name').value = '';
            const item = `<div class="list-item" id="zone-${zid}">
                <div><div class="list-item-text">
                    <span style="background:#e0f2fe;color:#0369a1;padding:1px 7px;border-radius:4px;font-size:.75rem;font-weight:700;margin-right:6px;">${zid}</span>${zname}
                </div></div>
                <button class="btn-del" onclick="deleteZone('${zid}')"><i class="fa-solid fa-trash-can"></i> ลบ</button>
            </div>`;
            const list = document.getElementById('zone-list');
            const empty = list.querySelector('.empty-list');
            if(empty) empty.remove();
            list.insertAdjacentHTML('beforeend', item);
            showAlert('เพิ่มหน่วย ' + zid + ' ' + zname + ' เรียบร้อย', 'success');
        } else { showAlert(d.message, 'error'); }
    });
}

function deleteZone(zid) {
    if(!confirm('ยืนยันลบหน่วยส่งเสริม ' + zid + '?\n⚠️ จะส่งผลกับพนักงานที่สังกัดหน่วยนี้')) return;
    const fd = new FormData();
    fd.append('zone_id', zid);
    fetch('api_zones.php', { method: 'DELETE', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: 'zone_id=' + zid })
    .then(r => r.json())
    .then(d => {
        if(d.status === 'success') {
            const el = document.getElementById('zone-' + zid);
            if(el) { el.style.opacity='0'; el.style.transition='opacity .2s'; setTimeout(()=>el.remove(), 200); }
            showAlert('ลบหน่วย ' + zid + ' เรียบร้อย');
        } else { showAlert(d.message, 'error'); }
    });
}

function updateCount(id, n, unit) {
    const el = document.getElementById(id);
    if(el) el.textContent = n + ' ' + unit;
}
// ── Check Items Cut ──
function addCutItem() {
    const name = document.getElementById('new-cut').value.trim();
    if(!name) { showAlert('กรุณากรอกชื่อรายการ', 'error'); return; }
    const fd = new FormData();
    fd.append('action', 'add');
    fd.append('item_name', name);
    fetch('api_check_items.php?table=cut', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if(d.status === 'success') {
            document.getElementById('new-cut').value = '';
            const list = document.getElementById('cut-list');
            const empty = list.querySelector('.empty-list');
            if(empty) empty.remove();
            list.insertAdjacentHTML('beforeend', `
                <div class="list-item" id="cut-${d.new_id}">
                    <div class="list-item-text">
                        <i class="fa-solid fa-screwdriver-wrench" style="color:#3b82f6;font-size:.75rem;margin-right:5px;"></i>${name}
                    </div>
                    <div style="display:flex;gap:6px;">
                        <button class="btn-del" style="border-color:#bfdbfe;color:#3b82f6;"
                                onmouseover="this.style.background='#eff6ff'" onmouseout="this.style.background=''"
                                onclick="editCutItem(${d.new_id}, '${name}')">
                            <i class="fa-solid fa-pen"></i> แก้ไข
                        </button>
                        <button class="btn-del" onclick="deleteCutItem(${d.new_id})">
                            <i class="fa-solid fa-trash-can"></i> ลบ
                        </button>
                    </div>
                </div>`);
            updateCount('cut-count', document.querySelectorAll('[id^="cut-"]').length, 'รายการ');
            showAlert('เพิ่ม "' + name + '" เรียบร้อย');
        } else { showAlert(d.message, 'error'); }
    });
}

function editCutItem(id, oldName) {
    const newName = prompt('แก้ไขชื่อรายการ:', oldName);
    if(!newName || newName.trim() === oldName) return;
    const fd = new FormData();
    fd.append('action', 'edit');
    fd.append('item_id', id);
    fd.append('item_name', newName.trim());
    fetch('api_check_items.php?table=cut', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if(d.status === 'success') {
            const el = document.getElementById('cut-' + id);
            if(el) el.querySelector('.list-item-text').innerHTML =
                `<i class="fa-solid fa-screwdriver-wrench" style="color:#3b82f6;font-size:.75rem;margin-right:5px;"></i>${newName.trim()}`;
            showAlert('แก้ไขเรียบร้อยแล้ว');
        } else { showAlert(d.message, 'error'); }
    });
}

function deleteCutItem(id) {
    if(!confirm('ยืนยันลบรายการนี้?')) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('item_id', id);
    fetch('api_check_items.php?table=cut', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if(d.status === 'success') {
            const el = document.getElementById('cut-' + id);
            if(el) { el.style.opacity='0'; el.style.transition='opacity .2s'; setTimeout(()=>el.remove(), 200); }
            showAlert('ลบเรียบร้อยแล้ว');
        } else { showAlert(d.message, 'error'); }
    });
}

// ── Check Items Field ──
function addFieldItem() {
    const name = document.getElementById('new-field').value.trim();
    if(!name) { showAlert('กรุณากรอกชื่อรายการ', 'error'); return; }
    const fd = new FormData();
    fd.append('action', 'add');
    fd.append('item_name', name);
    fetch('api_check_items.php?table=field', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if(d.status === 'success') {
            document.getElementById('new-field').value = '';
            const list = document.getElementById('field-list');
            const empty = list.querySelector('.empty-list');
            if(empty) empty.remove();
            list.insertAdjacentHTML('beforeend', `
                <div class="list-item" id="field-${d.new_id}">
                    <div class="list-item-text">
                        <i class="fa-solid fa-seedling" style="color:#f59e0b;font-size:.75rem;margin-right:5px;"></i>${name}
                    </div>
                    <div style="display:flex;gap:6px;">
                        <button class="btn-del" style="border-color:#fde68a;color:#d97706;"
                                onmouseover="this.style.background='#fffbeb'" onmouseout="this.style.background=''"
                                onclick="editFieldItem(${d.new_id}, '${name}')">
                            <i class="fa-solid fa-pen"></i> แก้ไข
                        </button>
                        <button class="btn-del" onclick="deleteFieldItem(${d.new_id})">
                            <i class="fa-solid fa-trash-can"></i> ลบ
                        </button>
                    </div>
                </div>`);
            updateCount('field-count', document.querySelectorAll('[id^="field-"]').length, 'รายการ');
            showAlert('เพิ่ม "' + name + '" เรียบร้อย');
        } else { showAlert(d.message, 'error'); }
    });
}

function editFieldItem(id, oldName) {
    const newName = prompt('แก้ไขชื่อรายการ:', oldName);
    if(!newName || newName.trim() === oldName) return;
    const fd = new FormData();
    fd.append('action', 'edit');
    fd.append('item_id', id);
    fd.append('item_name', newName.trim());
    fetch('api_check_items.php?table=field', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if(d.status === 'success') {
            const el = document.getElementById('field-' + id);
            if(el) el.querySelector('.list-item-text').innerHTML =
                `<i class="fa-solid fa-seedling" style="color:#f59e0b;font-size:.75rem;margin-right:5px;"></i>${newName.trim()}`;
            showAlert('แก้ไขเรียบร้อยแล้ว');
        } else { showAlert(d.message, 'error'); }
    });
}

function deleteFieldItem(id) {
    if(!confirm('ยืนยันลบรายการนี้?')) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('item_id', id);
    fetch('api_check_items.php?table=field', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
        if(d.status === 'success') {
            const el = document.getElementById('field-' + id);
            if(el) { el.style.opacity='0'; el.style.transition='opacity .2s'; setTimeout(()=>el.remove(), 200); }
            showAlert('ลบเรียบร้อยแล้ว');
        } else { showAlert(d.message, 'error'); }
    });
}
// ── System Settings ──
async function saveAllSettings() {
    // เลือกทุก input ที่มี class setting-input
    const inputs = document.querySelectorAll('.setting-input');
    
    for (const inp of inputs) {
        const fd = new FormData();
        fd.append('setting_key', inp.dataset.key);
        fd.append('setting_value', inp.value.trim());
        
        try {
            const response = await fetch('api_settings.php', { method: 'POST', body: fd });
            const result = await response.json();
            
            if (result.status !== 'success') {
                alert('เกิดข้อผิดพลาดที่ ' + inp.dataset.key + ': ' + result.message);
                return; // หยุดการทำงานถ้าเจอ Error
            }
        } catch (err) {
            alert('เชื่อมต่อเซิร์ฟเวอร์ไม่ได้: ' + err);
            return;
        }
    }
    
    alert('บันทึกการตั้งค่าทั้งหมดเรียบร้อยแล้ว');
    location.reload(); // รีโหลดหน้าจอเพื่อแสดงผลใหม่
}
function loadLogs() {
    const limit = document.getElementById('log-limit').value;
    fetch('api_get_logs.php?limit=' + limit)
    .then(r => r.text())
    .then(html => {
        document.getElementById('log-container').innerHTML = html;
    });
}
// เรียกใช้งานครั้งแรกตอนโหลดหน้าเว็บ
loadLogs();
</script>
</body>
</html>