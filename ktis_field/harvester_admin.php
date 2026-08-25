<?php
/**
 * harvester_admin.php — Admin ดูตารางสรุปข้อมูลการตรวจเช็กรถตัด (รองรับ 19 รายการ/8 หมวด)
 */
date_default_timezone_set('Asia/Bangkok');
require_once 'config.php';
session_start();

if(!isset($_SESSION["emp_id"])){ header("location: login.php"); exit; }
if(!in_array($_SESSION['emp_level'] ?? 'u', ['a', 'm'])){ header("location: harvester.php"); exit; }

$is_admin  = ($_SESSION['emp_level'] ?? '') === 'a';
$is_mech   = ($_SESSION['emp_level'] ?? '') === 'm';
$crop_year = $_SESSION['crop_year'];
$thai_months=['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
$message = ""; $status = "";

if (isset($_SESSION['flash_msg'])) {
    $message = $_SESSION['flash_msg'];
    $status  = $_SESSION['flash_status'] ?? 'success';
    unset($_SESSION['flash_msg'], $_SESSION['flash_status']);
}

$filter_date = $_GET['date'] ?? date('Y-m-d');
$filter_unit = $_GET['unit'] ?? '';
$filter_harvester = trim($_GET['harvester'] ?? ($_GET['h'] ?? ''));

function redirect_harvester_admin(string $date, string $unit='', string $harvester=''): void {
    $q = ['date'=>$date]; 
    if($unit!=='') $q['unit']=$unit;
    if($harvester!=='') $q['harvester']=$harvester;
    header("Location: harvester_admin.php?".http_build_query($q)); exit;
}

// ── ดึงรายการตรวจทั้งหมด แบ่งหมวด (ใช้ทั้งหน้า) ──
$all_cut_items = [];
try { $all_cut_items = $conn->query("SELECT * FROM check_items_cut ORDER BY section_no ASC, item_id ASC")->fetchAll(); } catch(Exception $e){}
$grouped_cut_items = [];
foreach($all_cut_items as $it){ $grouped_cut_items[$it['section_label']][] = $it; }

$field_items = [];
try { $field_items = $conn->query("SELECT * FROM check_items_field ORDER BY item_id ASC")->fetchAll(); } catch(Exception $e){}

// ── POST: delete / update (เฉพาะ Admin เท่านั้น) ──
if($_SERVER['REQUEST_METHOD']==='POST'){
    $action           = $_POST['action'] ?? '';
    $session_id       = (int)($_POST['session_id'] ?? 0);
    $return_date      = $_POST['date'] ?? date('Y-m-d');
    $return_unit      = $_POST['unit'] ?? '';
    $return_harvester = $_POST['harvester'] ?? '';

    try {
        if(!$is_admin){
            throw new Exception("คุณไม่มีสิทธิ์แก้ไขหรือลบข้อมูล (เฉพาะ Admin เท่านั้น)");
        }
        if($session_id <= 0) throw new Exception("ไม่พบรายการที่ต้องการจัดการ");

        if($action === 'delete'){
            $conn->prepare("DELETE FROM check_results WHERE session_id=:sid")->execute([':sid'=>$session_id]);
            $conn->prepare("DELETE FROM check_sessions WHERE session_id=:sid AND crop_year=:cy")->execute([':sid'=>$session_id,':cy'=>$crop_year]);
            $_SESSION['flash_status']='success';
            $_SESSION['flash_msg']='ลบรายการตรวจเช็กเรียบร้อยแล้ว';

        } elseif($action === 'update'){
            $harvester_number = trim($_POST['harvester_number'] ?? '');
            $field_condition  = trim($_POST['field_condition']  ?? '');
            if($harvester_number==='') throw new Exception("กรุณาระบุเบอร์รถตัดอ้อย");

            $conn->prepare(
                "UPDATE check_sessions SET harvester_number=:hn, field_condition=:fc WHERE session_id=:sid AND crop_year=:cy"
            )->execute([':hn'=>$harvester_number, ':fc'=>$field_condition, ':sid'=>$session_id, ':cy'=>$crop_year]);

            // อัปเดตทุกรายการ (item_<id> มาจาก checkbox ในฟอร์ม edit)
            $stmt_upd = $conn->prepare("UPDATE check_results SET pass=:pass, note=:note WHERE session_id=:sid AND item_id=:iid");
            foreach($all_cut_items as $ci){
                $iid  = $ci['item_id'];
                $pass = isset($_POST["item_$iid"]) ? (int)$_POST["item_$iid"] : 1;
                $note = (!$pass) ? trim($_POST["note_item_$iid"] ?? '') : null;
                $stmt_upd->execute([':pass'=>$pass, ':note'=>$note, ':sid'=>$session_id, ':iid'=>$iid]);
            }
            $_SESSION['flash_status']='success';
            $_SESSION['flash_msg']='แก้ไขรายการตรวจเช็กเรียบร้อยแล้ว';
        }
    } catch(Exception $e){
        $_SESSION['flash_status']='error';
        $_SESSION['flash_msg']='เกิดข้อผิดพลาด: '.$e->getMessage();
    }
    redirect_harvester_admin($return_date, $return_unit, $return_harvester);
}

// ── dropdown หน่วย ──
$all_units = $conn->query(
    "SELECT CASE WHEN zone_id='000' THEN zone_name ELSE CONCAT(zone_id,' ',zone_name) END AS unit_name FROM zones ORDER BY zone_id ASC"
)->fetchAll(PDO::FETCH_COLUMN);

// ── ดึง check_sessions พร้อมนับ pass/fail รวม (ไม่ pivot รายข้อแล้ว) ──
$where  = "WHERE cs.crop_year=:cy AND DATE(cs.checked_at)=:dt";
$params = [':cy'=>$crop_year, ':dt'=>$filter_date];
if($filter_unit !== ''){ $where .= " AND e.emp_unit=:unit"; $params[':unit']=$filter_unit; }
if($filter_harvester !== ''){
    $where .= " AND (cs.harvester_number = :hn_exact OR cs.harvester_number = :hn_full OR cs.harvester_number = :hn_short OR cs.harvester_number LIKE :hn_like)";
    $h_short = str_replace('รถตัดเบอร์ ', '', $filter_harvester);
    $h_full  = !str_contains($filter_harvester, 'รถตัดเบอร์') ? "รถตัดเบอร์ " . $filter_harvester : $filter_harvester;
    $params[':hn_exact'] = $filter_harvester;
    $params[':hn_full']  = $h_full;
    $params[':hn_short'] = $h_short;
    $params[':hn_like']  = '%' . $h_short . '%';
}

$stmt = $conn->prepare(
    "SELECT cs.*, e.emp_name, e.emp_unit,
            COUNT(cr.result_id) AS total_items,
            SUM(cr.pass) AS pass_count
     FROM check_sessions cs
     JOIN employee e ON cs.emp_id=e.emp_id
     LEFT JOIN check_results cr ON cs.session_id=cr.session_id
     $where
     GROUP BY cs.session_id
     ORDER BY cs.checked_at DESC"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// ── ดึงรายละเอียดผลตรวจของแต่ละ session (เก็บไว้ใช้ใน expand row + edit) ──
$stmt_detail = $conn->prepare(
    "SELECT cr.item_id, cr.pass, cr.note FROM check_results cr WHERE cr.session_id=:sid"
);
foreach($rows as &$r){
    $stmt_detail->execute([':sid'=>$r['session_id']]);
    $details = $stmt_detail->fetchAll();
    $r['results_map'] = [];
    foreach($details as $d){ $r['results_map'][$d['item_id']] = ['pass'=>(int)$d['pass'], 'note'=>$d['note']]; }
}
unset($r);

// ── สถิติ ──
$total = count($rows);
$cnt_ok = 0; $cnt_fail = 0;
foreach($rows as $r){
    $t = (int)($r['total_items'] ?? 0); $p = (int)($r['pass_count'] ?? 0);
    if($t > 0 && $p == $t) $cnt_ok++; elseif($t > 0) $cnt_fail++;
}

// ── ยังไม่บันทึก ──
$miss_where = "WHERE e.emp_level='u' AND e.emp_id NOT IN (SELECT cs2.emp_id FROM check_sessions cs2 WHERE DATE(cs2.checked_at)=:dt AND cs2.crop_year=:cy)";
$miss_params = [':dt'=>$filter_date, ':cy'=>$crop_year];
if($filter_unit !== ''){ $miss_where .= " AND e.emp_unit=:unit"; $miss_params[':unit']=$filter_unit; }
$stmt_miss = $conn->prepare("SELECT e.emp_id,e.emp_name,e.emp_unit FROM employee e $miss_where ORDER BY e.emp_unit,e.emp_name");
$stmt_miss->execute($miss_params);
$missing = $stmt_miss->fetchAll();

// ── สรุปรายหน่วย ──
$summary = [];
foreach($rows as $r){
    $u = $r['emp_unit'];
    if(!isset($summary[$u])) $summary[$u] = ['total'=>0,'ok'=>0,'fail'=>0];
    $summary[$u]['total']++;
    $t=(int)($r['total_items']??0); $p=(int)($r['pass_count']??0);
    if($t>0 && $p==$t) $summary[$u]['ok']++; elseif($t>0) $summary[$u]['fail']++;
}

$section_icons = [1=>'fa-arrow-up',2=>'fa-rotate',3=>'fa-arrow-down',4=>'fa-circle-notch',5=>'fa-scissors',6=>'fa-fan',7=>'fa-wind',8=>'fa-broom'];

$fd_d=(int)date('d',strtotime($filter_date)); $fd_m=(int)date('m',strtotime($filter_date)); $fd_y=(int)date('Y',strtotime($filter_date))+543;
$thai_filter = $fd_d.' '.$thai_months[$fd_m].' '.$fd_y;

include 'includes/nav_u_header.php';
?>
<style>
*{box-sizing:border-box;}
body{font-family:'Sarabun',sans-serif;background:#f1f5f9;margin:0;}
.page-wrapper { display: flex; min-height: 100vh; width: 100%; align-items: flex-start; }
.dash-wrap { flex: 1; padding: 24px 28px 60px; min-width: 0; overflow-x: hidden; }
.content-wrapper { width: 100%; max-width: 100%; margin: 0 auto; }
.pw { width: 100%; max-width: 100%; margin: 0; padding: 0 0 60px; }

.ph{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;}
.ph-left{display:flex;align-items:center;gap:12px;}
.ph-icon{width:46px;height:46px;background:linear-gradient(135deg,#e11d48,#be123c);border-radius:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.ph-icon i{color:#fff;font-size:1.3rem;}
.ph-title{font-size:1.1rem;font-weight:700;color:#1e293b;}
.ph-sub{font-size:.78rem;color:#64748b;}
.btn-today{padding:9px 14px;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;border-radius:7px;font-weight:700;font-size:.85rem;font-family:'Sarabun',sans-serif;cursor:pointer;text-decoration:none;display:flex;align-items:center;gap:5px;}
.btn-today:hover{background:#e2e8f0;}

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

/* 📥 Export Modal Popup Styles */
.export-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.78);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: 2147483648;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
    opacity: 0;
    transition: opacity 0.2s ease;
}
.export-modal-overlay.show {
    display: flex;
    opacity: 1;
}
.export-modal-card {
    background: #ffffff;
    border-radius: 20px;
    width: 100%;
    max-width: 480px;
    box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.35);
    overflow: hidden;
    transform: translateY(20px) scale(0.97);
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
.export-modal-overlay.show .export-modal-card {
    transform: translateY(0) scale(1);
}
.export-modal-hd {
    background: #1e293b;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 3px solid #10b981;
}
.em-title {
    color: #ffffff;
    font-weight: 800;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 8px;
}
.btn-em-close {
    background: rgba(255,255,255,0.1);
    border: none;
    color: #94a3b8;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all .15s;
}
.btn-em-close:hover {
    background: #e11d48;
    color: #fff;
}
.export-modal-bd {
    padding: 20px;
}
.em-subtitle {
    margin: 0 0 14px;
    font-size: 0.84rem;
    color: #64748b;
}
.em-presets {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    margin-bottom: 14px;
}
.em-preset-btn {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 5px 10px;
    font-size: 0.78rem;
    font-weight: 700;
    color: #475569;
    cursor: pointer;
    transition: all .15s;
    font-family: inherit;
}
.em-preset-btn:hover {
    background: #d1fae5;
    border-color: #10b981;
    color: #065f46;
}
.em-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}
.em-field-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
}
.em-label {
    font-size: 0.8rem;
    font-weight: 700;
    color: #334155;
}
.em-input {
    padding: 8px 12px;
    border: 1.5px solid #e2e8f0;
    border-radius: 9px;
    font-size: 0.88rem;
    background: #f8fafc;
    color: #1e293b;
    outline: none;
    font-family: inherit;
}
.em-input:focus {
    border-color: #10b981;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(16,185,129,0.15);
}
.export-modal-ft {
    padding: 14px 20px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
.btn-em-cancel {
    padding: 8px 16px;
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    border-radius: 9px;
    color: #475569;
    font-weight: 700;
    font-size: 0.85rem;
    cursor: pointer;
    font-family: inherit;
}
.btn-em-submit {
    padding: 8px 18px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border: none;
    border-radius: 9px;
    color: #ffffff;
    font-weight: 800;
    font-size: 0.88rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 4px 12px rgba(16,185,129,0.3);
    font-family: inherit;
}
.btn-em-submit:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(16,185,129,0.4);
}

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
table{width:100%;border-collapse:collapse;min-width:760px;}
thead th{background:#f8fafc;color:#475569;font-size:.75rem;font-weight:700;padding:9px 12px;text-align:left;border-bottom:1px solid #e2e8f0;white-space:nowrap;}
tbody td{padding:9px 12px;border-bottom:1px solid #f8fafc;font-size:.83rem;color:#334155;vertical-align:middle;}
tbody tr:last-child td{border-bottom:none;}
tbody tr.main-row:hover{background:#fafafa;cursor:pointer;}
.row-fail{background:#fffbf0 !important;}
.row-fail:hover{background:#fff7ed !important;}
.emp-n{font-weight:700;color:#1e293b;font-size:.82rem;}
.emp-u{font-size:.72rem;color:#94a3b8;}
.hn{font-weight:700;color:#1e293b;font-family:monospace;}

.fc-badge{display:inline-flex;align-items:center;gap:3px;font-size:.71rem;font-weight:700;padding:2px 7px;border-radius:4px;background:#f0fdf4;color:#065f46;border:1px solid #a7f3d0;}
.fc-badge.bad{background:#fff7ed;color:#92400e;border-color:#fcd34d;}

.progress-cell{display:flex;align-items:center;gap:8px;min-width:140px;}
.progress-track{flex:1;background:#f1f5f9;border-radius:20px;height:8px;overflow:hidden;}
.progress-fill{height:8px;border-radius:20px;background:#10b981;}
.progress-fill.has-fail{background:#f97316;}
.progress-text{font-size:.74rem;font-weight:700;color:#475569;white-space:nowrap;}

.pass-all{background:#d1fae5;color:#065f46;font-size:.71rem;font-weight:700;padding:3px 9px;border-radius:10px;white-space:nowrap;display:inline-flex;align-items:center;gap:3px;}
.has-fail-badge{background:#fee2e2;color:#991b1b;font-size:.71rem;font-weight:700;padding:3px 9px;border-radius:10px;white-space:nowrap;display:inline-flex;align-items:center;gap:3px;}

.img-thumbs{display:flex;gap:4px;}
.img-thumb{width:32px;height:32px;object-fit:cover;border-radius:5px;border:1px solid #e2e8f0;cursor:pointer;transition:transform .15s;}
.img-thumb:hover{transform:scale(1.15);}

.row-actions{display:flex;gap:6px;align-items:center;white-space:nowrap;}
.btn-mini{border:none;border-radius:7px;padding:6px 9px;font-size:.75rem;font-weight:700;font-family:'Sarabun',sans-serif;cursor:pointer;display:inline-flex;align-items:center;gap:4px;}
.btn-edit{background:#e0f2fe;color:#0369a1;}
.btn-edit:hover{background:#bae6fd;}
.btn-del{background:#fee2e2;color:#991b1b;}
.btn-del:hover{background:#fecaca;}
.btn-save{background:#10b981;color:#fff;}
.btn-save:hover{background:#059669;}

/* expand detail row */
.detail-row{display:none;background:#f8fafc;}
.detail-row.show{display:table-row;}
.detail-box{padding:16px 18px;}

.detail-tabs{display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap;}
.detail-tab-btn{padding:6px 13px;border-radius:18px;border:1.5px solid #e2e8f0;background:#fff;font-size:.78rem;font-weight:700;color:#64748b;cursor:pointer;font-family:'Sarabun',sans-serif;}
.detail-tab-btn.active{background:#e11d48;color:#fff;border-color:#e11d48;}

.detail-mode{display:none;}
.detail-mode.show{display:block;}

/* view mode: read-only list grouped */
.dview-section{margin-bottom:14px;}
.dview-section-hd{font-size:.78rem;font-weight:700;color:#065f46;background:#f0fdf4;padding:6px 12px;border-radius:7px;margin-bottom:6px;display:flex;align-items:center;gap:6px;}
.dview-item{display:flex;align-items:center;justify-content:space-between;padding:6px 12px;font-size:.83rem;color:#334155;border-bottom:1px solid #f1f5f9;}
.dview-item:last-child{border-bottom:none;}
.dview-ok{color:#059669;font-weight:700;font-size:.75rem;}
.dview-fail{color:#e11d48;font-weight:700;font-size:.75rem;}
.dview-note{font-size:.74rem;color:#92400e;background:#fff7ed;border-left:2px solid #f59e0b;padding:3px 8px;margin-top:3px;border-radius:0 4px 4px 0;}

/* edit mode form */
.edit-grid-top{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;}
.edit-field{display:flex;flex-direction:column;gap:5px;}
.edit-lbl{font-size:.72rem;font-weight:700;color:#64748b;}
.edit-input,.edit-select{padding:8px 9px;border:1.5px solid #e2e8f0;border-radius:7px;background:#fff;color:#1e293b;font-family:'Sarabun',sans-serif;font-size:.82rem;width:100%;}

.edit-cut-section{margin-bottom:12px;}
.edit-cut-hd{font-size:.76rem;font-weight:700;color:#065f46;background:#f0fdf4;padding:6px 12px;border-radius:7px;margin-bottom:6px;display:flex;align-items:center;gap:6px;}
.edit-cut-row{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:6px 12px;border-bottom:1px solid #f8fafc;flex-wrap:wrap;}
.edit-cut-label{font-size:.82rem;color:#334155;flex:1;min-width:140px;}
.edit-radio-group{display:flex;gap:6px;}
.edit-radio-btn{display:none;}
.edit-radio-label{padding:4px 10px;border-radius:6px;font-size:.74rem;font-weight:700;cursor:pointer;border:1.5px solid #e2e8f0;}
.edit-radio-btn[value="1"]:checked + .edit-radio-label{background:#10b981;color:#fff;border-color:#10b981;}
.edit-radio-btn[value="0"]:checked + .edit-radio-label{background:#e11d48;color:#fff;border-color:#e11d48;}
.edit-note-wrap{display:none;width:100%;margin-top:6px;}
.edit-note-wrap.show{display:block;}
.edit-note-input{width:100%;padding:6px 9px;border:1.5px solid #fecaca;border-radius:6px;font-size:.78rem;font-family:'Sarabun',sans-serif;background:#fff5f5;resize:vertical;min-height:40px;}

.empty-s{text-align:center;padding:40px;color:#94a3b8;}
.empty-s i{font-size:2rem;display:block;margin-bottom:8px;}

.missing-card{background:#fff;border-radius:13px;border:.5px solid #fecaca;overflow:hidden;}
.miss-hd{background:#fef2f2;padding:12px 16px;display:flex;align-items:center;gap:8px;border-bottom:1px solid #fecaca;}
.miss-hd i{color:#e11d48;}
.miss-hd span{font-weight:700;color:#991b1b;font-size:.9rem;}
.miss-search-box{padding:10px 14px;background:#fff5f5;border-bottom:1px solid #fecaca;}
.miss-search-input{width:100%;padding:8px 12px;border:1.5px solid #fca5a5;border-radius:6px;font-family:'Sarabun',sans-serif;font-size:.85rem;outline:none;}
.miss-search-input:focus{border-color:#e11d48;}
.miss-list-container{max-height:480px;overflow-y:auto;}
.miss-list-container::-webkit-scrollbar{width:6px;}
.miss-list-container::-webkit-scrollbar-thumb{background:#fca5a5;border-radius:4px;}
.miss-list-container::-webkit-scrollbar-track{background:#fff;}
.miss-row{display:flex;align-items:center;justify-content:space-between;padding:9px 14px;border-bottom:1px solid #fef2f2;font-size:.83rem;}
.miss-row:last-child{border-bottom:none;}
.miss-name{font-weight:700;color:#1e293b;}
.miss-unit{font-size:.75rem;color:#94a3b8;margin-top:1px;}
.miss-badge{background:#fef2f2;color:#e11d48;font-size:.72rem;font-weight:700;padding:2px 8px;border-radius:10px;border:1px solid #fecaca;white-space:nowrap;}
.no-miss{padding:20px;text-align:center;color:#10b981;font-weight:700;font-size:.88rem;}



@media(max-width:1024px){
    .dash-wrap{padding:14px 14px 80px;}
}
@media(max-width:768px){
    .stats{grid-template-columns:repeat(2,1fr);}
    .filter-bar{flex-direction:column;align-items:stretch;}
    .edit-grid-top{grid-template-columns:1fr;}
}
@media(max-width:480px){
    .stats{grid-template-columns:1fr 1fr;}
    .unit-grid{grid-template-columns:1fr 1fr;}
}

/* ══════════════════════════════════════════
   DARK MODE OVERRIDES
   ══════════════════════════════════════════ */
.dark-mode body,
html.dark-mode body {
    background: #090d16 !important;
    color: #f1f5f9 !important;
}
.dark-mode .dash-wrap {
    background: #090d16 !important;
}
.dark-mode .ph-title {
    color: #f8fafc !important;
}
.dark-mode .ph-sub {
    color: #94a3b8 !important;
}
.dark-mode .btn-today {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #cbd5e1 !important;
}
.dark-mode .btn-today:hover {
    background: #334155 !important;
    color: #f8fafc !important;
}
.dark-mode .stat-card {
    background: #131b2e !important;
    border-color: #1e293b !important;
}
.dark-mode .stat-num {
    color: #f8fafc !important;
}
.dark-mode .stat-lbl {
    color: #94a3b8 !important;
}
.dark-mode .filter-bar {
    background: #131b2e !important;
    border-color: #1e293b !important;
}
.dark-mode .fb-lbl {
    color: #cbd5e1 !important;
}
.dark-mode .fb-input {
    background: #0b1120 !important;
    border-color: #1e293b !important;
    color: #f8fafc !important;
}
.dark-mode .fb-input:focus {
    border-color: #e11d48 !important;
    background: #0f172a !important;
}
.dark-mode .section-title {
    color: #f8fafc !important;
}
.dark-mode .unit-card {
    background: #131b2e !important;
    border-color: #1e293b !important;
}
.dark-mode .unit-name {
    color: #f8fafc !important;
}
.dark-mode .card {
    background: #131b2e !important;
    border-color: #1e293b !important;
}
.dark-mode thead th {
    background: #0b1120 !important;
    color: #94a3b8 !important;
    border-color: #1e293b !important;
}
.dark-mode tbody td {
    color: #cbd5e1 !important;
    border-color: #1e293b !important;
}
.dark-mode tbody tr.main-row:hover {
    background: #1e293b !important;
}
.dark-mode .emp-n {
    color: #f8fafc !important;
}
.dark-mode .emp-u {
    color: #94a3b8 !important;
}
.dark-mode .hn {
    color: #38bdf8 !important;
}
.dark-mode .row-fail {
    background: rgba(245, 158, 11, 0.08) !important;
}
.dark-mode .row-fail:hover {
    background: rgba(245, 158, 11, 0.15) !important;
}
.dark-mode .progress-track {
    background: #1e293b !important;
}
.dark-mode .progress-text {
    color: #cbd5e1 !important;
}
.dark-mode .detail-row {
    background: #0b1120 !important;
}
.dark-mode .detail-box {
    background: #0b1120 !important;
}
.dark-mode .detail-tab-btn {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #cbd5e1 !important;
}
.dark-mode .detail-tab-btn.active {
    background: #e11d48 !important;
    border-color: #e11d48 !important;
    color: #fff !important;
}
.dark-mode .dview-section-hd {
    background: rgba(16, 185, 129, 0.15) !important;
    color: #34d399 !important;
}
.dark-mode .dview-item {
    color: #e2e8f0 !important;
    border-color: #1e293b !important;
}
.dark-mode .dview-note {
    background: rgba(245, 158, 11, 0.15) !important;
    color: #fbbf24 !important;
    border-left-color: #f59e0b !important;
}
.dark-mode .edit-lbl {
    color: #94a3b8 !important;
}
.dark-mode .edit-input,
.dark-mode .edit-select {
    background: #131b2e !important;
    border-color: #334155 !important;
    color: #f8fafc !important;
}
.dark-mode .edit-cut-hd {
    background: rgba(16, 185, 129, 0.15) !important;
    color: #34d399 !important;
}
.dark-mode .edit-cut-row {
    border-color: #1e293b !important;
}
.dark-mode .edit-cut-label {
    color: #e2e8f0 !important;
}
.dark-mode .edit-radio-label {
    border-color: #334155 !important;
    color: #94a3b8 !important;
}
.dark-mode .edit-note-input {
    background: rgba(239, 68, 68, 0.1) !important;
    border-color: rgba(239, 68, 68, 0.3) !important;
    color: #fca5a5 !important;
}
.dark-mode .missing-card {
    background: #131b2e !important;
    border-color: rgba(239, 68, 68, 0.3) !important;
}
.dark-mode .miss-hd {
    background: rgba(239, 68, 68, 0.15) !important;
    border-color: rgba(239, 68, 68, 0.25) !important;
}
.dark-mode .miss-hd span {
    color: #f87171 !important;
}
.dark-mode .miss-search-box {
    background: #0b1120 !important;
    border-color: #1e293b !important;
}
.dark-mode .miss-search-input {
    background: #131b2e !important;
    border-color: #334155 !important;
    color: #f8fafc !important;
}
.dark-mode .miss-row {
    border-color: #1e293b !important;
}
.dark-mode .miss-name {
    color: #f8fafc !important;
}
.dark-mode .miss-unit {
    color: #94a3b8 !important;
}
.dark-mode .miss-badge {
    background: rgba(239, 68, 68, 0.15) !important;
    color: #f87171 !important;
    border-color: rgba(239, 68, 68, 0.3) !important;
}
</style>
<div class="page-wrapper">
<?php include 'includes/nav_u_sidebar.php'; ?>
<div class="dash-wrap">
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
<div class="alert <?php echo $status==='success'?'alert-success':'alert-error'; ?>">
    <i class="fa-solid <?php echo $status==='success'?'fa-circle-check':'fa-circle-exclamation'; ?>"></i>
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
        <div><div class="stat-num" style="color:#059669;"><?php echo $cnt_ok;?></div><div class="stat-lbl">ผ่านทั้งหมด</div></div>
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
                <option value="<?php echo htmlspecialchars($u);?>" <?php echo $filter_unit===$u?'selected':'';?>><?php echo htmlspecialchars($u);?></option>
                <?php endforeach;?>
            </select>
        </div>
        <div class="fb-group">
            <span class="fb-lbl"><i class="fa-solid fa-tractor"></i> เบอร์รถตัด</span>
            <input type="text" name="harvester" class="fb-input" placeholder="เช่น รถตัดเบอร์ 1" value="<?php echo htmlspecialchars($filter_harvester);?>">
        </div>
        <button type="submit" class="btn-filter"><i class="fa-solid fa-magnifying-glass"></i> ค้นหา</button>
        <button type="button" class="btn-export-excel" onclick="openExportModal('harvester')" title="เลือกช่วงวันที่เพื่อส่งออกรายงาน Excel">
            <i class="fa-solid fa-file-excel"></i> ส่งออก Excel
        </button>
        <?php if($filter_unit !== '' || $filter_harvester !== '' || $filter_date !== date('Y-m-d')): ?>
        <a href="harvester_admin.php?date=<?php echo date('Y-m-d'); ?>" class="btn-today" style="background:#f8fafc;" title="ล้างตัวกรอง"><i class="fa-solid fa-rotate-left"></i> ล้างตัวกรอง</a>
        <?php endif; ?>
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
            <?php if($us['fail']>0):?><div class="unit-bar-fail"><i class="fa-solid fa-xmark"></i> <?php echo $us['fail'];?></div><?php endif;?>
        </div>
    </div>
    <?php endforeach;?>
</div>
<?php endif;?>

<div class="card">
    <div class="card-hd">
        <div class="card-hd-l"><i class="fa-solid fa-table-list"></i><span>รายละเอียดการบันทึก — <?php echo $thai_filter;?> (คลิกแถวเพื่อดูรายละเอียด 19 รายการ)</span></div>
        <span class="cnt-badge"><?php echo $total;?> รายการ</span>
    </div>
    <?php if(empty($rows)):?>
    <div class="empty-s"><i class="fa-solid fa-clipboard-list"></i>ไม่มีข้อมูลในวันที่เลือก<?php echo $filter_harvester!=='' ? ' สำหรับ "'.htmlspecialchars($filter_harvester).'"' : ''; ?></div>
    <?php else:?>
    <div class="tbl-w">
        <table>
            <thead>
                <tr>
                    <th>#</th><th>เวลา</th><th>ผู้บันทึก</th><th>เบอร์รถตัด</th>
                    <th>สภาพแปลง</th><th>ผลตรวจ (19 รายการ)</th><th>สรุป</th><th>ภาพ</th>
                    <?php if($is_admin): ?><th>จัดการ</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach($rows as $i=>$r):
                $total_items=(int)($r['total_items']??0); $pass_count=(int)($r['pass_count']??0);
                $allok = ($total_items>0 && $pass_count==$total_items);
                $ts=date('H:i น.',strtotime($r['checked_at']));
                $is_bad_field=!in_array($r['field_condition']??'', ['ปกติ','']);
                $pct = $total_items>0 ? round($pass_count/$total_items*100) : 0;
                $sid = (int)$r['session_id'];
            ?>
            <tr class="main-row <?php echo !$allok?'row-fail':''; ?>" onclick="toggleDetailRow(<?php echo $sid; ?>, event)">
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
                    <?php else: ?><span style="color:#94a3b8;font-size:.78rem;">-</span><?php endif; ?>
                </td>
                <td>
                    <div class="progress-cell">
                        <div class="progress-track"><div class="progress-fill <?php echo !$allok?'has-fail':''; ?>" style="width:<?php echo $pct; ?>%;"></div></div>
                        <span class="progress-text"><?php echo $pass_count;?>/<?php echo $total_items;?></span>
                    </div>
                </td>
                <td>
                    <?php if($allok):?><span class="pass-all"><i class="fa-solid fa-check-double"></i> ผ่านทั้งหมด</span>
                    <?php else:?><span class="has-fail-badge"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $total_items-$pass_count;?> ไม่ผ่าน</span><?php endif;?>
                </td>
                <td onclick="event.stopPropagation();">
                    <div class="img-thumbs">
                        <?php if(!empty($r['img_harvester'])): ?><img class="img-thumb" src="<?php echo htmlspecialchars($r['img_harvester']);?>" onclick="openLightbox(this.src)" title="รูปรถตัด"><?php endif; ?>
                        <?php if(!empty($r['img_field'])): ?><img class="img-thumb" src="<?php echo htmlspecialchars($r['img_field']);?>" onclick="openLightbox(this.src)" title="รูปแปลงอ้อย"><?php endif; ?>
                        <?php if(empty($r['img_harvester']) && empty($r['img_field'])): ?><span style="color:#94a3b8;font-size:.78rem;">-</span><?php endif; ?>
                    </div>
                </td>
                <?php if($is_admin): ?>
                <td onclick="event.stopPropagation();">
                    <div class="row-actions">
                        <form method="POST" action="harvester_admin.php" onsubmit="return confirm('ยืนยันลบรายการนี้?');" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="session_id" value="<?php echo $sid;?>">
                            <input type="hidden" name="date" value="<?php echo htmlspecialchars($filter_date);?>">
                            <input type="hidden" name="unit" value="<?php echo htmlspecialchars($filter_unit);?>">
                            <input type="hidden" name="harvester" value="<?php echo htmlspecialchars($filter_harvester);?>">
                            <button type="submit" class="btn-mini btn-del" title="ลบรายการ"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </div>
                </td>
                <?php endif; ?>
            </tr>

            <!-- Detail Row: View + Edit mode toggle -->
            <tr class="detail-row" id="detail-row-<?php echo $sid;?>">
                <td colspan="<?php echo $is_admin ? 9 : 8; ?>">
                    <div class="detail-box">
                        <?php if($is_admin): ?>
                        <div class="detail-tabs">
                            <button type="button" class="detail-tab-btn active" id="tab-view-<?php echo $sid;?>" onclick="switchTab(<?php echo $sid;?>,'view')">
                                <i class="fa-solid fa-eye"></i> ดูรายละเอียด
                            </button>
                            <button type="button" class="detail-tab-btn" id="tab-edit-<?php echo $sid;?>" onclick="switchTab(<?php echo $sid;?>,'edit')">
                                <i class="fa-solid fa-pen"></i> แก้ไข
                            </button>
                        </div>
                        <?php else: ?>
                        <div style="font-weight:700; font-size:0.86rem; color:#1e293b; margin-bottom:12px; display:flex; align-items:center; gap:6px;">
                            <i class="fa-solid fa-clipboard-list" style="color:#0284c7;"></i> รายละเอียดผลการตรวจเช็กรถตัด (19 รายการ)
                        </div>
                        <?php endif; ?>

                        <!-- VIEW MODE -->
                        <div class="detail-mode show" id="mode-view-<?php echo $sid;?>">
                            <?php foreach($grouped_cut_items as $sec_label => $items):
                                $sec_no = $items[0]['section_no']; $icon = $section_icons[$sec_no] ?? 'fa-gear';
                            ?>
                            <div class="dview-section">
                                <div class="dview-section-hd"><i class="fa-solid <?php echo $icon;?>"></i> <?php echo htmlspecialchars($sec_label);?></div>
                                <?php foreach($items as $item):
                                    $iid=$item['item_id'];
                                    $res = $r['results_map'][$iid] ?? ['pass'=>1,'note'=>null];
                                ?>
                                <div class="dview-item">
                                    <span><?php echo htmlspecialchars($item['item_name_cut']);?></span>
                                    <?php if($res['pass']): ?>
                                        <span class="dview-ok"><i class="fa-solid fa-check"></i> ผ่าน/ปกติ</span>
                                    <?php else: ?>
                                        <span class="dview-fail"><i class="fa-solid fa-xmark"></i> ไม่ผ่าน</span>
                                    <?php endif; ?>
                                </div>
                                <?php if(!$res['pass'] && !empty($res['note'])): ?>
                                <div class="dview-note"><?php echo htmlspecialchars($res['note']);?></div>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if($is_admin): ?>
                        <!-- EDIT MODE (เฉพาะ Admin เท่านั้น) -->
                        <div class="detail-mode" id="mode-edit-<?php echo $sid;?>">
                            <form method="POST" action="harvester_admin.php">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="session_id" value="<?php echo $sid;?>">
                                <input type="hidden" name="date" value="<?php echo htmlspecialchars($filter_date);?>">
                                <input type="hidden" name="unit" value="<?php echo htmlspecialchars($filter_unit);?>">
                                <input type="hidden" name="harvester" value="<?php echo htmlspecialchars($filter_harvester);?>">

                                <div class="edit-grid-top">
                                    <label class="edit-field">
                                        <span class="edit-lbl">เบอร์รถตัด</span>
                                        <input type="text" name="harvester_number" class="edit-input" value="<?php echo htmlspecialchars($r['harvester_number']);?>" required>
                                    </label>
                                    <label class="edit-field">
                                        <span class="edit-lbl">สภาพแปลง</span>
                                        <select name="field_condition" class="edit-select">
                                            <option value="">-- เลือก --</option>
                                             <?php foreach($field_items as $fi): ?>
                                            <option value="<?php echo htmlspecialchars($fi['item_name_field']);?>" <?php echo ($r['field_condition']??'')===$fi['item_name_field']?'selected':'';?>>
                                                <?php echo htmlspecialchars($fi['item_name_field']);?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                </div>

                                <?php foreach($grouped_cut_items as $sec_label => $items):
                                    $sec_no = $items[0]['section_no']; $icon = $section_icons[$sec_no] ?? 'fa-gear';
                                ?>
                                <div class="edit-cut-section">
                                    <div class="edit-cut-hd"><i class="fa-solid <?php echo $icon;?>"></i> <?php echo htmlspecialchars($sec_label);?></div>
                                    <?php foreach($items as $item):
                                        $iid=$item['item_id'];
                                        $res = $r['results_map'][$iid] ?? ['pass'=>1,'note'=>null];
                                        $uid = $sid.'_'.$iid;
                                    ?>
                                    <div class="edit-cut-row">
                                        <span class="edit-cut-label"><?php echo htmlspecialchars($item['item_name_cut']);?></span>
                                        <div class="edit-radio-group">
                                            <input type="radio" class="edit-radio-btn" name="item_<?php echo $iid;?>" id="eok_<?php echo $uid;?>" value="1" <?php echo $res['pass']?'checked':'';?> onchange="toggleEditNote('<?php echo $uid;?>',1)">
                                            <label class="edit-radio-label" for="eok_<?php echo $uid;?>" style="color:#059669;">ผ่าน</label>
                                            <input type="radio" class="edit-radio-btn" name="item_<?php echo $iid;?>" id="efail_<?php echo $uid;?>" value="0" <?php echo !$res['pass']?'checked':'';?> onchange="toggleEditNote('<?php echo $uid;?>',0)">
                                            <label class="edit-radio-label" for="efail_<?php echo $uid;?>" style="color:#e11d48;">ไม่ผ่าน</label>
                                        </div>
                                        <div class="edit-note-wrap <?php echo !$res['pass']?'show':''; ?>" id="enote_wrap_<?php echo $uid;?>">
                                            <textarea class="edit-note-input" name="note_item_<?php echo $iid;?>" placeholder="ระบุสาเหตุ..."><?php echo htmlspecialchars($res['note'] ?? '');?></textarea>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endforeach; ?>

                                <button type="submit" class="btn-mini btn-save" style="margin-top:8px;">
                                    <i class="fa-solid fa-floppy-disk"></i> บันทึกการแก้ไข
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach;?>
            </tbody>
        </table>
    </div>
    <?php endif;?>
</div>

<div class="missing-card">
    <div class="miss-hd"><i class="fa-solid fa-user-slash"></i><span>พนักงานที่ยังไม่บันทึกวันนี้ (<span id="miss-count-display"><?php echo count($missing);?></span> คน)</span></div>
    <?php if(empty($missing)):?>
    <div class="no-miss"><i class="fa-solid fa-circle-check"></i>พนักงานทุกคนบันทึกครบแล้ววันนี้</div>
    <?php else:?>
    <div class="miss-search-box">
        <input type="text" id="missSearchInput" class="miss-search-input" placeholder="🔍 พิมพ์ค้นหาชื่อ หรือ หน่วย..." onkeyup="filterMissing()">
    </div>
    <div class="miss-list-container" id="missListContainer">
        <?php foreach($missing as $m):?>
        <div class="miss-row" data-search="<?php echo htmlspecialchars(strtolower($m['emp_name'].' '.$m['emp_unit'])); ?>">
            <div><div class="miss-name"><?php echo htmlspecialchars($m['emp_name']);?></div><div class="miss-unit">หน่วย: <?php echo htmlspecialchars($m['emp_unit']);?></div></div>
            <span class="miss-badge"><i class="fa-solid fa-clock"></i> ยังไม่บันทึก</span>
        </div>
        <?php endforeach;?>
    </div>
    <?php endif;?>
</div>

</div>
</div>


</div></div><!-- dash-wrap / page-wrapper -->
<script>
function toggleDetailRow(sid, e){
    // ไม่เปิดถ้าคลิกที่ form/button/img/a
    const tag = e.target.tagName.toLowerCase();
    if(['button','input','a','img','select','textarea'].includes(tag)) return;
    const row = document.getElementById('detail-row-'+sid);
    if(row) row.classList.toggle('show');
}
function switchTab(sid, mode){
    document.getElementById('tab-view-'+sid).classList.toggle('active', mode==='view');
    document.getElementById('tab-edit-'+sid).classList.toggle('active', mode==='edit');
    document.getElementById('mode-view-'+sid).classList.toggle('show', mode==='view');
    document.getElementById('mode-edit-'+sid).classList.toggle('show', mode==='edit');
}
function toggleEditNote(uid, val){
    const wrap = document.getElementById('enote_wrap_'+uid);
    if(!wrap) return;
    if(val==0) wrap.classList.add('show'); else wrap.classList.remove('show');
}
function openLightbox(src){ window.open(src, '_blank'); }
function filterMissing() {
    let input = document.getElementById('missSearchInput').value.toLowerCase();
    let rows = document.querySelectorAll('.miss-row');
    let visibleCount = 0;
    
    rows.forEach(row => {
        let text = row.getAttribute('data-search');
        if (text.includes(input)) {
            row.style.display = 'flex';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    let countDisplay = document.getElementById('miss-count-display');
    if(countDisplay) countDisplay.innerText = visibleCount;
}

window.addEventListener('DOMContentLoaded', () => {
    <?php if(!empty($filter_harvester) && count($rows) > 0): ?>
        // ถ้าค้นหา/ส่งเบอร์รถมาจากหน้าแดชบอร์ด ให้เปิดขยายรายละเอียดแถวแรกและเลื่อนจอไปหาอัตโนมัติ
        const firstSid = <?php echo (int)$rows[0]['session_id']; ?>;
        const autoRow = document.getElementById('detail-row-' + firstSid);
        if(autoRow) {
            autoRow.classList.add('show');
            setTimeout(() => {
                autoRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 100);
        }
    <?php endif; ?>
});
</script>

<!-- 📥 Excel Export Date-Range Modal -->
<div class="export-modal-overlay" id="exportModal" onclick="closeExportModal(event)">
    <div class="export-modal-card" onclick="event.stopPropagation()">
        <div class="export-modal-hd">
            <div class="em-title">
                <i class="fa-solid fa-file-excel" style="color:#10b981;"></i>
                <span id="exportModalTitle">ส่งออกรายงานผลตรวจเช็กรถตัด (Excel)</span>
            </div>
            <button type="button" class="btn-em-close" onclick="closeExportModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="export-modal-bd">
            <p class="em-subtitle">เลือกช่วงวันที่ที่ต้องการสรุปข้อมูลเพื่อดาวน์โหลดเป็นไฟล์ Excel (.xls)</p>
            
            <div class="em-presets">
                <button type="button" class="em-preset-btn" onclick="setExportRange(0, 0)">วันนี้</button>
                <button type="button" class="em-preset-btn" onclick="setExportRange(6, 0)">7 วันล่าสุด</button>
                <button type="button" class="em-preset-btn" onclick="setExportRange(29, 0)">30 วันล่าสุด</button>
                <button type="button" class="em-preset-btn" onclick="setExportMonthRange()">เดือนนี้</button>
            </div>

            <div class="em-grid">
                <div class="em-field-group">
                    <label class="em-label"><i class="fa-solid fa-calendar-day"></i> วันเริ่มต้น</label>
                    <input type="date" id="emDateFrom" class="em-input" value="<?php echo htmlspecialchars($filter_date); ?>" max="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="em-field-group">
                    <label class="em-label"><i class="fa-solid fa-calendar-check"></i> วันสิ้นสุด</label>
                    <input type="date" id="emDateTo" class="em-input" value="<?php echo htmlspecialchars($filter_date); ?>" max="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>

            <div class="em-field-group" style="margin-top: 12px;">
                <label class="em-label"><i class="fa-solid fa-location-dot"></i> หน่วยส่งเสริม (ระบุหรือไม่ระบุก็ได้)</label>
                <select id="emUnit" class="em-input">
                    <option value="">-- ทุกหน่วยส่งเสริม --</option>
                    <?php foreach($all_units as $u): ?>
                    <option value="<?php echo htmlspecialchars($u); ?>" <?php echo ($filter_unit===$u)?'selected':''; ?>><?php echo htmlspecialchars($u); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="export-modal-ft">
            <button type="button" class="btn-em-cancel" onclick="closeExportModal()">ยกเลิก</button>
            <button type="button" class="btn-em-submit" onclick="submitExportExcel()">
                <i class="fa-solid fa-cloud-arrow-down"></i> ดาวน์โหลดรายงาน Excel
            </button>
        </div>
    </div>
</div>

<script>
window.currentExportType = 'harvester';
function openExportModal(type) {
    window.currentExportType = type || 'harvester';
    const overlay = document.getElementById('exportModal');
    if (overlay) {
        overlay.classList.add('show');
    }
}
function closeExportModal(e) {
    if (e && e.target !== e.currentTarget) return;
    const overlay = document.getElementById('exportModal');
    if (overlay) overlay.classList.remove('show');
}
function setExportRange(daysAgoFrom, daysAgoTo) {
    const today = new Date();
    const dFrom = new Date();
    dFrom.setDate(today.getDate() - daysAgoFrom);
    const dTo = new Date();
    dTo.setDate(today.getDate() - daysAgoTo);
    
    document.getElementById('emDateFrom').value = dFrom.toISOString().split('T')[0];
    document.getElementById('emDateTo').value = dTo.toISOString().split('T')[0];
}
function setExportMonthRange() {
    const today = new Date();
    const y = today.getFullYear();
    const m = String(today.getMonth() + 1).padStart(2, '0');
    document.getElementById('emDateFrom').value = `${y}-${m}-01`;
    document.getElementById('emDateTo').value = today.toISOString().split('T')[0];
}
function submitExportExcel() {
    const dateFrom = document.getElementById('emDateFrom').value;
    const dateTo   = document.getElementById('emDateTo').value;
    const unit     = document.getElementById('emUnit').value;
    const cy       = "<?php echo htmlspecialchars($crop_year); ?>";
    const hv       = "<?php echo htmlspecialchars($filter_harvester); ?>";
    
    if (!dateFrom || !dateTo) {
        alert('กรุณาเลือกช่วงวันที่ให้ครบถ้วน');
        return;
    }
    
    const targetScript = (window.currentExportType === 'posts') ? 'export_posts_excel.php' : 'export_harvester_excel.php';
    const url = `${targetScript}?date=${encodeURIComponent(dateFrom)}&date_end=${encodeURIComponent(dateTo)}&unit=${encodeURIComponent(unit)}&harvester=${encodeURIComponent(hv)}&crop_year=${encodeURIComponent(cy)}`;
    
    closeExportModal();
    window.location.href = url;
}
</script>
</body>
</html>