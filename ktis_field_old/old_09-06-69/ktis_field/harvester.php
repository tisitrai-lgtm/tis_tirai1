<?php
/**
 * harvester.php - บันทึกผลตรวจเช็กรถตัดอ้อย + ตารางประวัติ
 */
require_once 'config.php';
session_start();

if(!isset($_SESSION["emp_id"])){
    header("location: login.php");
    exit;
}
if(($_SESSION['emp_level'] ?? 'u') === 'a'){
    header("location: harvester_admin.php");
    exit;
}

$message = "";
$status  = "";

// เช็ค Flash Message จาก Session (แสดงข้อความหลัง Redirect)
if (isset($_SESSION['flash_msg'])) {
    $message = $_SESSION['flash_msg'];
    $status  = $_SESSION['flash_status'];
    unset($_SESSION['flash_msg']);
    unset($_SESSION['flash_status']);
}

// ── บันทึกฟอร์ม ──
if($_SERVER["REQUEST_METHOD"] == "POST") {
    $harvester_number  = trim($_POST['harvester_number']);
    $check_blade       = isset($_POST['check_blade'])       ? intval($_POST['check_blade'])       : 0;
    $check_top_cutter  = isset($_POST['check_top_cutter'])  ? intval($_POST['check_top_cutter'])  : 0;
    $check_chopper     = isset($_POST['check_chopper'])     ? intval($_POST['check_chopper'])     : 0;
    $check_base_cutter = isset($_POST['check_base_cutter']) ? intval($_POST['check_base_cutter']) : 0;
    $check_extractor   = isset($_POST['check_extractor'])   ? intval($_POST['check_extractor'])   : 0;
    $crop_year         = $_SESSION['crop_year'];
    $check_date        = date('Y-m-d');

    if(empty($harvester_number)) {
        $status  = "error";
        $message = "กรุณาระบุเบอร์รถตัดอ้อยก่อนบันทึก";
    } else {
        try {
            $sql = "INSERT INTO harvester_checks 
                        (emp_id, harvester_number, check_blade, check_top_cutter, check_chopper, check_base_cutter, check_extractor, crop_year, check_date)
                    VALUES 
                        (:emp_id, :harvester_number, :check_blade, :check_top_cutter, :check_chopper, :check_base_cutter, :check_extractor, :crop_year, :check_date)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':emp_id'            => $_SESSION['emp_id'],
                ':harvester_number'  => $harvester_number,
                ':check_blade'       => $check_blade,
                ':check_top_cutter'  => $check_top_cutter,
                ':check_chopper'     => $check_chopper,
                ':check_base_cutter' => $check_base_cutter,
                ':check_extractor'   => $check_extractor,
                ':crop_year'         => $crop_year,
                ':check_date'        => $check_date,
            ]);
            
            // ใช้ Session Flash Messages & Redirect ป้องกันบั๊กบันทึกซ้ำเวลากดรีเฟรชหน้า
            $_SESSION['flash_status'] = "success";
            $_SESSION['flash_msg'] = "บันทึกรถตัดเบอร์ <strong>" . htmlspecialchars($harvester_number) . "</strong> เรียบร้อยแล้ว";
            header("Location: harvester.php");
            exit;
            
        } catch(Exception $e) {
            $status  = "error";
            $message = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }
}

// ── ดึงประวัติ: 30 รายการล่าสุด กรองตาม crop_year ──
$history = [];
$stmt_h = $conn->prepare(
    "SELECT hc.*, e.emp_name, e.emp_unit
     FROM harvester_checks hc
     JOIN employee e ON hc.emp_id = e.emp_id
     WHERE hc.crop_year = :crop_year
     ORDER BY hc.created_at DESC
     LIMIT 30"
);
$stmt_h->execute([':crop_year' => $_SESSION['crop_year']]);
$history = $stmt_h->fetchAll();

// ── helper ──
function badge(int $val): string {
    return $val
        ? '<span class="chk-ok"><i class="fa-solid fa-check"></i> สมบูรณ์</span>'
        : '<span class="chk-fail"><i class="fa-solid fa-xmark"></i> ไม่สมบูรณ์</span>';
}

function dot(int $val): string {
    return $val
        ? '<span class="dot dot-ok" title="สมบูรณ์"></span>'
        : '<span class="dot dot-fail" title="ไม่สมบูรณ์"></span>';
}

$check_items = [
    'check_blade'       => ['label'=>'ใบพัดสับท่อน',     'icon'=>'fa-fan'],
    'check_top_cutter'  => ['label'=>'ตัดยอดอ้อย',       'icon'=>'fa-scissors'],
    'check_chopper'     => ['label'=>'สับย่อย/ตัดต่อ',   'icon'=>'fa-circle-nodes'],
    'check_base_cutter' => ['label'=>'ตัดโคนอ้อย',       'icon'=>'fa-arrow-down-to-line'],
    'check_extractor'   => ['label'=>'พัดลมดูดใบ',       'icon'=>'fa-wind'],
];

include 'includes/nav_u_header.php';

// Thai date
$thai_months = ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน',
                'กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
$now_d = (int)date('d');
$now_m = (int)date('m');
$now_y = (int)date('Y') + 543;
$thai_date_now = $now_d . ' ' . $thai_months[$now_m] . ' ' . $now_y;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตรวจเช็กรถตัดอ้อย - KTIS SMART FIELD</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Sarabun', sans-serif; background: #f1f5f9; margin: 0; }
        .content-wrapper { flex: 1 0 auto; }
        .page-wrap { max-width: 760px; margin: 24px auto; padding: 0 14px 60px; }

        /* ── Page header ── */
        .page-header {
            display: flex; align-items: center; gap: 12px;
            margin-bottom: 20px; flex-wrap: wrap;
        }
        .page-header-icon {
            width: 46px; height: 46px; background: linear-gradient(135deg,#10b981,#059669);
            border-radius: 11px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .page-header-icon i { color: white; font-size: 1.3rem; }
        .page-header-title { font-size: 1.15rem; font-weight: 700; color: #1e293b; margin-bottom: 2px; }
        .page-header-sub   { font-size: 0.8rem; color: #64748b; }

        /* ── Alert ── */
        .alert {
            display: flex; align-items: flex-start; gap: 10px;
            padding: 13px 16px; border-radius: 9px; margin-bottom: 18px;
            font-weight: 600; font-size: 0.9rem;
        }
        .alert-success { background: #d1fae5; border: 1px solid #a7f3d0; color: #065f46; }
        .alert-error   { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; }
        .alert i { margin-top: 2px; flex-shrink: 0; }

        /* ── Form card ── */
        .form-card {
            background: white; border-radius: 14px; border: 0.5px solid #e2e8f0;
            overflow: hidden; margin-bottom: 28px;
        }
        .form-card-header {
            background: #1e293b; padding: 14px 20px;
            display: flex; align-items: center; gap: 10px;
            border-bottom: 3px solid #10b981;
        }
        .form-card-header i   { color: #10b981; font-size: 1rem; }
        .form-card-header span { color: #f8fafc; font-weight: 700; font-size: 0.95rem; }
        .form-card-body { padding: 20px; }

        /* ── Meta info bar ── */
        .meta-bar {
            display: flex; gap: 10px; flex-wrap: wrap;
            background: #f8fafc; border: 1px solid #e2e8f0;
            border-radius: 9px; padding: 11px 14px; margin-bottom: 20px;
        }
        .meta-chip {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 0.82rem; font-weight: 600; color: #475569;
        }
        .meta-chip i { color: #94a3b8; font-size: 0.85rem; }
        .meta-sep { color: #e2e8f0; }

        /* ── Input ── */
        .field-label {
            display: block; font-weight: 700; font-size: 0.83rem;
            color: #374151; margin-bottom: 7px;
        }
        .field-label .req { color: #e11d48; }
        .form-input {
            width: 100%; padding: 11px 13px; border: 1.5px solid #e2e8f0;
            border-radius: 8px; font-size: 0.95rem; font-family: 'Sarabun', sans-serif;
            background: #f8fafc; color: #1e293b; outline: none; transition: border-color .15s;
        }
        .form-input:focus { border-color: #10b981; background: white; }

        /* ── Checklist ── */
        .section-label {
            font-weight: 700; font-size: 0.85rem; color: #1e293b;
            display: flex; align-items: center; gap: 7px; margin: 18px 0 12px;
            padding-bottom: 8px; border-bottom: 1px solid #f1f5f9;
        }
        .section-label i { color: #10b981; }

        .check-row {
            display: flex; align-items: center; justify-content: space-between;
            padding: 11px 0; border-bottom: 1px solid #f8fafc; gap: 10px;
        }
        .check-row:last-of-type { border-bottom: none; }
        .check-row-label {
            display: flex; align-items: center; gap: 9px;
            font-weight: 600; color: #334155; font-size: 0.92rem;
        }
        .check-row-label .icon-wrap {
            width: 30px; height: 30px; background: #f0fdf4; border-radius: 7px;
            display: flex; align-items: center; justify-content: center;
        }
        .check-row-label i { color: #10b981; font-size: 0.85rem; }
        .radio-group { display: flex; gap: 8px; }
        .radio-btn { display: none; }
        .radio-label {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 6px 13px; border-radius: 7px; cursor: pointer; font-weight: 700;
            font-size: 0.82rem; border: 1.5px solid #e2e8f0; transition: all .15s;
        }
        .radio-btn[value="1"]:checked + .radio-label {
            background: #10b981; color: white; border-color: #10b981;
        }
        .radio-btn[value="0"]:checked + .radio-label {
            background: #e11d48; color: white; border-color: #e11d48;
        }
        .radio-label.ok  { color: #059669; background: #f0fdf4; }
        .radio-label.bad { color: #e11d48; background: #fef2f2; }

        /* ── Submit ── */
        .btn-submit {
            width: 100%; padding: 13px; background: #10b981; color: white;
            border: none; border-radius: 9px; font-size: 1rem; font-weight: 700;
            font-family: 'Sarabun', sans-serif; cursor: pointer; margin-top: 20px;
            display: flex; align-items: center; justify-content: center; gap: 7px;
            transition: background .15s;
        }
        .btn-submit:hover { background: #059669; }

        /* ── History card ── */
        .history-card {
            background: white; border-radius: 14px; border: 0.5px solid #e2e8f0; overflow: hidden;
        }
        .history-header {
            background: #1e293b; padding: 13px 18px;
            display: flex; align-items: center; justify-content: space-between;
            border-bottom: 3px solid #64748b;
        }
        .history-header-left { display: flex; align-items: center; gap: 8px; }
        .history-header-left i   { color: #94a3b8; }
        .history-header-left span { color: #f8fafc; font-weight: 700; font-size: 0.92rem; }
        .history-count {
            background: rgba(255,255,255,.1); color: #cbd5e1;
            font-size: 0.75rem; font-weight: 700; padding: 2px 9px; border-radius: 10px;
        }

        /* Desktop table */
        .tbl-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 580px; }
        thead th {
            background: #f8fafc; color: #475569; font-size: 0.78rem; font-weight: 700;
            padding: 10px 14px; text-align: left; border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }
        tbody td {
            padding: 10px 14px; border-bottom: 1px solid #f8fafc;
            font-size: 0.85rem; color: #334155; vertical-align: middle;
        }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #f8fafc; }

        .harvester-num { font-weight: 700; color: #1e293b; font-family: monospace; }
        .emp-cell { display: flex; flex-direction: column; }
        .emp-cell .emp-name { font-weight: 700; color: #1e293b; font-size: 0.83rem; }
        .emp-cell .emp-unit { font-size: 0.73rem; color: #94a3b8; }

        .chk-ok   { background: #d1fae5; color: #065f46; font-size: 0.72rem; font-weight: 700; padding: 2px 7px; border-radius: 4px; display: inline-flex; align-items: center; gap: 3px; white-space: nowrap; }
        .chk-fail { background: #fee2e2; color: #991b1b; font-size: 0.72rem; font-weight: 700; padding: 2px 7px; border-radius: 4px; display: inline-flex; align-items: center; gap: 3px; white-space: nowrap; }
        .dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; }
        .dot-ok   { background: #10b981; }
        .dot-fail { background: #e11d48; }

        /* Mobile history cards */
        .mobile-history { display: none; }
        .hist-card {
            padding: 14px 16px; border-bottom: 1px solid #f1f5f9;
        }
        .hist-card:last-child { border-bottom: none; }
        .hist-card-top {
            display: flex; align-items: flex-start; justify-content: space-between;
            margin-bottom: 10px; gap: 8px;
        }
        .hist-num { font-weight: 700; color: #1e293b; font-size: 0.95rem; }
        .hist-meta { font-size: 0.75rem; color: #94a3b8; margin-top: 2px; }
        .hist-dots { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }
        .hist-dot-item { display: flex; align-items: center; gap: 4px; font-size: 0.73rem; color: #64748b; }
        .hist-date-badge {
            background: #f1f5f9; color: #475569; font-size: 0.72rem; font-weight: 700;
            padding: 3px 9px; border-radius: 12px; white-space: nowrap; flex-shrink: 0;
        }

        .empty-hist {
            text-align: center; padding: 40px 20px; color: #94a3b8;
        }
        .empty-hist i { font-size: 2rem; display: block; margin-bottom: 8px; }

        @media (max-width: 640px) {
            .tbl-wrap table { display: none; }
            .mobile-history { display: block; }
            .check-row { flex-direction: column; align-items: flex-start; gap: 8px; }
            .radio-group { width: 100%; }
            .radio-label { flex: 1; justify-content: center; }
        }
    </style>
</head>
<body>
<div class="content-wrapper">

<div class="page-wrap">

    <!-- Page header -->
    <div class="page-header">
        <div class="page-header-icon"><i class="fa-solid fa-tractor"></i></div>
        <div>
            <div class="page-header-title">บันทึกตรวจเช็กรถตัดอ้อยประจำวัน</div>
            <div class="page-header-sub">ปีการผลิต <?php echo htmlspecialchars($_SESSION['crop_year']); ?> · วันที่ <?php echo $thai_date_now; ?></div>
        </div>
    </div>

    <!-- Alert -->
    <?php if(!empty($message)): ?>
    <div class="alert <?php echo $status === 'success' ? 'alert-success' : 'alert-error'; ?>">
        <i class="fa-solid <?php echo $status === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
        <span><?php echo $message; ?></span>
    </div>
    <?php endif; ?>

    <!-- Form card -->
    <div class="form-card">
        <div class="form-card-header">
            <i class="fa-solid fa-clipboard-list"></i>
            <span>แบบฟอร์มบันทึกผลการตรวจสอบ</span>
        </div>
        <div class="form-card-body">

            <!-- Meta bar: ผู้บันทึก + วันที่ -->
            <div class="meta-bar">
                <div class="meta-chip">
                    <i class="fa-solid fa-user"></i>
                    <span><?php echo htmlspecialchars($_SESSION['emp_name']); ?></span>
                </div>
                <span class="meta-sep">|</span>
                <div class="meta-chip">
                    <i class="fa-solid fa-location-dot"></i>
                    <span><?php echo htmlspecialchars($_SESSION['emp_unit']); ?></span>
                </div>
                <span class="meta-sep">|</span>
                <div class="meta-chip">
                    <i class="fa-solid fa-calendar-day"></i>
                    <span><?php echo $thai_date_now; ?></span>
                </div>
                <span class="meta-sep">|</span>
                <div class="meta-chip">
                    <i class="fa-solid fa-clock"></i>
                    <span id="live-time"><?php echo date('H:i'); ?> น.</span>
                </div>
            </div>

            <form action="harvester.php" method="POST">

                <label class="field-label">
                    เบอร์รถตัดอ้อย <span class="req">*</span>
                </label>
                <input type="text" name="harvester_number" class="form-input"
                       placeholder="เช่น MC-01, คันที่ 25" required autofocus>

                <div class="section-label">
                    <i class="fa-solid fa-clipboard-check"></i>
                    รายการตรวจสอบความสมบูรณ์ภาคสนาม
                </div>

                <?php foreach($check_items as $key => $item): ?>
                <div class="check-row">
                    <div class="check-row-label">
                        <div class="icon-wrap"><i class="fa-solid <?php echo $item['icon']; ?>"></i></div>
                        <?php echo $item['label']; ?>
                    </div>
                    <div class="radio-group">
                        <input type="radio" class="radio-btn" name="<?php echo $key; ?>" id="<?php echo $key; ?>_ok" value="1" checked>
                        <label class="radio-label ok" for="<?php echo $key; ?>_ok"><i class="fa-solid fa-check"></i> สมบูรณ์</label>
                        <input type="radio" class="radio-btn" name="<?php echo $key; ?>" id="<?php echo $key; ?>_fail" value="0">
                        <label class="radio-label bad" for="<?php echo $key; ?>_fail"><i class="fa-solid fa-xmark"></i> ไม่สมบูรณ์</label>
                    </div>
                </div>
                <?php endforeach; ?>

                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-floppy-disk"></i> บันทึกผลการตรวจสอบ
                </button>
            </form>
        </div>
    </div>

    <!-- History card -->
    <div class="history-card">
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

        <!-- Desktop table -->
        <div class="tbl-wrap">
            <table>
                <thead>
                    <tr>
                        <th>วันที่/เวลา</th>
                        <th>เบอร์รถตัด</th>
                        <th>ผู้บันทึก</th>
                        <th>ใบพัด</th>
                        <th>ตัดยอด</th>
                        <th>ตัดต่อ</th>
                        <th>ตัดโคน</th>
                        <th>พัดลม</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($history as $h):
                        $d  = (int)date('d', strtotime($h['check_date']));
                        $mo = (int)date('m', strtotime($h['check_date']));
                        $yr = (int)date('Y', strtotime($h['check_date'])) + 543;
                        $date_str = $d . ' ' . $thai_months[$mo] . ' ' . $yr;
                        $time_str = date('H:i น.', strtotime($h['created_at']));
                        $all_ok   = ($h['check_blade'] && $h['check_top_cutter'] && $h['check_chopper'] && $h['check_base_cutter'] && $h['check_extractor']);
                    ?>
                    <tr>
                        <td>
                            <div style="font-weight:700; color:#1e293b; font-size:0.82rem;"><?php echo $date_str; ?></div>
                            <div style="font-size:0.75rem; color:#94a3b8;"><?php echo $time_str; ?></div>
                        </td>
                        <td>
                            <span class="harvester-num"><?php echo htmlspecialchars($h['harvester_number']); ?></span>
                            <?php if(!$all_ok): ?>
                                <div style="font-size:0.7rem; color:#e11d48; margin-top:2px; font-weight:700;">
                                    <i class="fa-solid fa-triangle-exclamation"></i> พบข้อบกพร่อง
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="emp-cell">
                                <span class="emp-name"><?php echo htmlspecialchars($h['emp_name']); ?></span>
                                <span class="emp-unit"><?php echo htmlspecialchars($h['emp_unit']); ?></span>
                            </div>
                        </td>
                        <td><?php echo badge($h['check_blade']); ?></td>
                        <td><?php echo badge($h['check_top_cutter']); ?></td>
                        <td><?php echo badge($h['check_chopper']); ?></td>
                        <td><?php echo badge($h['check_base_cutter']); ?></td>
                        <td><?php echo badge($h['check_extractor']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile cards -->
        <div class="mobile-history">
            <?php foreach($history as $h):
                $d  = (int)date('d', strtotime($h['check_date']));
                $mo = (int)date('m', strtotime($h['check_date']));
                $yr = (int)date('Y', strtotime($h['check_date'])) + 543;
                $date_str = $d . ' ' . $thai_months[$mo] . ' ' . $yr;
                $time_str = date('H:i น.', strtotime($h['created_at']));
                $all_ok   = ($h['check_blade'] && $h['check_top_cutter'] && $h['check_chopper'] && $h['check_base_cutter'] && $h['check_extractor']);

                $fields = [
                    'ใบพัด'   => $h['check_blade'],
                    'ตัดยอด'  => $h['check_top_cutter'],
                    'ตัดต่อ'  => $h['check_chopper'],
                    'ตัดโคน'  => $h['check_base_cutter'],
                    'พัดลม'   => $h['check_extractor'],
                ];
            ?>
            <div class="hist-card">
                <div class="hist-card-top">
                    <div>
                        <div class="hist-num">
                            <i class="fa-solid fa-tractor" style="color:#10b981; margin-right:4px;"></i>
                            <?php echo htmlspecialchars($h['harvester_number']); ?>
                            <?php if(!$all_ok): ?>
                                <span style="font-size:0.72rem; color:#e11d48; font-weight:700; margin-left:4px;">
                                    <i class="fa-solid fa-triangle-exclamation"></i> พบข้อบกพร่อง
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="hist-meta">
                            <i class="fa-solid fa-user" style="margin-right:3px;"></i><?php echo htmlspecialchars($h['emp_name']); ?>
                            · <?php echo htmlspecialchars($h['emp_unit']); ?>
                        </div>
                    </div>
                    <div class="hist-date-badge">
                        <i class="fa-solid fa-calendar" style="margin-right:3px;"></i>
                        <?php echo $date_str; ?><br>
                        <span style="color:#94a3b8;"><?php echo $time_str; ?></span>
                    </div>
                </div>
                <div class="hist-dots">
                    <?php foreach($fields as $fname => $fval): ?>
                    <div class="hist-dot-item">
                        <?php echo dot($fval); ?>
                        <span><?php echo $fname; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php endif; ?>
    </div><!-- /history-card -->

</div><!-- /page-wrap -->
</div><!-- /content-wrapper -->

<?php include 'includes/nav_u_footer.php'; ?>

<script>
// นาฬิกา live
function updateTime() {
    const now = new Date();
    const h = String(now.getHours()).padStart(2,'0');
    const m = String(now.getMinutes()).padStart(2,'0');
    const el = document.getElementById('live-time');
    if(el) el.textContent = h + ':' + m + ' น.';
}
setInterval(updateTime, 30000);

// Radio visual fix: เมื่อ page load ให้ checked label ไฮไลท์ทันที
document.querySelectorAll('input.radio-btn').forEach(r => {
    r.addEventListener('change', function() {
        const name = this.name;
        document.querySelectorAll(`input[name="${name}"] + .radio-label`).forEach(l => {
            l.style.background = '';
            l.style.color = '';
            l.style.borderColor = '';
        });
    });
});
</script>
</body>
</html>
