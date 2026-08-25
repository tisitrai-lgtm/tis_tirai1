<?php
/**
 * harvester_map.php — แผนที่พิกัดรถตัดและแปลงอ้อยภาคสนาม (GIS Harvester Live Map)
 * TIS SMART FIELD - ฝ่ายไร่
 */
require_once 'config.php';
session_start();

if (!isset($_SESSION['emp_id'])) {
    header("Location: login.php");
    exit;
}

$is_admin = ($_SESSION['emp_level'] ?? 'u') === 'a';

// ─────────────────────────────────────────────────────────────
// 1. รับค่าตัวกรอง (Filters)
// ─────────────────────────────────────────────────────────────
$default_crop = $_SESSION['crop_year'] ?? '69/70';
$crop_year    = trim($_GET['crop_year'] ?? $default_crop);
$filter_zone  = trim($_GET['zone'] ?? '');
$filter_status= trim($_GET['status'] ?? 'all'); // all, pass, fail

$today = date('Y-m-d');
$preset       = trim($_GET['preset'] ?? '');
$date_from    = trim($_GET['date_from'] ?? '');
$date_to      = trim($_GET['date_to'] ?? '');

// ── เริ่มต้นรายงานเป็นวันที่ปัจจุบัน (Today) เป็นค่าเริ่มต้น ──
if ($preset === '' && $date_from === '' && $date_to === '') {
    $preset    = 'today';
    $date_from = $today;
    $date_to   = $today;
} elseif ($preset === 'today') {
    $date_from = $today;
    $date_to   = $today;
} elseif ($preset === '7days') {
    $date_from = date('Y-m-d', strtotime('-7 days'));
    $date_to   = $today;
} elseif ($preset === '30days') {
    $date_from = date('Y-m-d', strtotime('-30 days'));
    $date_to   = $today;
} elseif ($preset === 'all') {
    $date_from = '';
    $date_to   = '';
}

// รายการปีการผลิต
$all_crops = [];
try {
    $stmt_c = $conn->query("SELECT DISTINCT crop_year FROM check_sessions WHERE crop_year IS NOT NULL AND crop_year != '' ORDER BY crop_year DESC");
    $all_crops = $stmt_c->fetchAll(PDO::FETCH_COLUMN);
    if (empty($all_crops) && !empty($default_crop)) $all_crops = [$default_crop];
} catch (Exception $e) {}

// รายการหน่วยงาน
$all_zones = [];
try {
    $stmt_z = $conn->query("SELECT DISTINCT emp_unit FROM employee WHERE emp_unit IS NOT NULL AND emp_unit != '' ORDER BY emp_unit ASC");
    $all_zones = $stmt_z->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {}

// ─────────────────────────────────────────────────────────────
// 2. ดึงข้อมูลพิกัดรถตัด (Check Sessions with GPS)
// ─────────────────────────────────────────────────────────────
$where = ["1=1"];
$params = [];

if ($crop_year !== '' && $crop_year !== 'all') {
    $where[] = "cs.crop_year = :crop";
    $params[':crop'] = $crop_year;
}
if ($date_from !== '') {
    $where[] = "DATE(cs.checked_at) >= :dfrom";
    $params[':dfrom'] = $date_from;
}
if ($date_to !== '') {
    $where[] = "DATE(cs.checked_at) <= :dto";
    $params[':dto'] = $date_to;
}
if ($filter_zone !== '') {
    $where[] = "e.emp_unit = :zone";
    $params[':zone'] = $filter_zone;
}

$where_sql = implode(" AND ", $where);

$sessions = [];
$total_with_gps = 0;
$total_pass = 0;
$total_fail = 0;

try {
    $sql = "
        SELECT 
            cs.*,
            e.emp_name,
            e.emp_unit,
            COUNT(cr.result_id) as total_items,
            SUM(CASE WHEN cr.pass = 1 THEN 1 ELSE 0 END) as pass_count,
            SUM(CASE WHEN cr.pass = 0 THEN 1 ELSE 0 END) as fail_count
        FROM check_sessions cs
        LEFT JOIN employee e ON cs.emp_id = e.emp_id
        LEFT JOIN check_results cr ON cs.session_id = cr.session_id
        WHERE $where_sql
        GROUP BY cs.session_id
        ORDER BY cs.checked_at DESC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $all_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($all_rows as $row) {
        $is_pass = ((int)$row['fail_count'] === 0 && (int)$row['total_items'] > 0);
        $row['is_pass'] = $is_pass;

        // ดึงรายการที่ไม่ผ่าน (ถ้ามี)
        $fails = [];
        if (!$is_pass && (int)$row['fail_count'] > 0) {
            $stmt_f = $conn->prepare("
                SELECT cic.item_name_cut, cr.note
                FROM check_results cr
                JOIN check_items_cut cic ON cr.item_id = cic.item_id
                WHERE cr.session_id = ? AND cr.pass = 0
            ");
            $stmt_f->execute([$row['session_id']]);
            $fails = $stmt_f->fetchAll(PDO::FETCH_ASSOC);
        }
        $row['fails'] = $fails;

        // กรองตาม status filter (all, pass, fail)
        if ($filter_status === 'pass' && !$is_pass) continue;
        if ($filter_status === 'fail' && $is_pass) continue;

        if (!empty($row['latitude']) && !empty($row['longitude'])) {
            $total_with_gps++;
            if ($is_pass) $total_pass++;
            else $total_fail++;
        }

        $sessions[] = $row;
    }
} catch (Exception $e) {}

include 'includes/nav_u_header.php';
?>

<!-- Leaflet CSS & MarkerCluster CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />

<style>
* { box-sizing: border-box; }
body { font-family: 'Sarabun', sans-serif; background: #f8fafc; color: #1e293b; margin: 0; }

.page-wrapper { display: flex; min-height: 100vh; width: 100%; align-items: flex-start; }
.dash-wrap { flex: 1; padding: 20px 24px; min-width: 0; overflow-x: hidden; }
.content-wrapper { max-width: 1400px; margin: 0 auto; }

/* ── MAP HEADER ── */
.map-header {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border-radius: 16px;
    padding: 20px 24px;
    color: white;
    margin-bottom: 18px;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.15);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 14px;
}
.mh-left { display: flex; align-items: center; gap: 14px; }
.mh-icon {
    width: 48px; height: 48px;
    background: linear-gradient(135deg, #0284c7, #0369a1);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; color: white;
    box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);
    flex-shrink: 0;
}
.mh-title { font-size: 1.25rem; font-weight: 800; margin: 0 0 2px 0; }
.mh-sub { font-size: 0.8rem; color: #94a3b8; margin: 0; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }

.mh-stats { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.mh-stat-chip {
    background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12);
    border-radius: 10px; padding: 6px 14px; text-align: center;
}
.mh-stat-num { font-size: 1.15rem; font-weight: 800; line-height: 1; }
.mh-stat-lbl { font-size: 0.68rem; color: #cbd5e1; font-weight: 600; margin-top: 2px; }

/* ── TOOLBAR / FILTER ── */
.map-filter-bar {
    background: white; border-radius: 14px; border: 1px solid #e2e8f0;
    padding: 14px 18px; margin-bottom: 18px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.02);
}
.filter-row { display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; }
.f-col { display: flex; flex-direction: column; gap: 3px; flex: 1 1 130px; }
.f-lbl { font-size: 0.74rem; font-weight: 700; color: #475569; }
.f-inp {
    padding: 8px 12px; border: 1.5px solid #e2e8f0; border-radius: 8px;
    font-size: 0.84rem; font-family: 'Sarabun', sans-serif; background: #f8fafc;
    color: #1e293b; outline: none; width: 100%; transition: all 0.15s;
    height: 37px;
    -webkit-appearance: none !important;
    appearance: none !important;
    min-width: 0 !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
    display: block;
}
.f-inp::-webkit-date-and-time-value {
    text-align: left !important;
    min-height: 1.3em;
}
.f-inp:focus { border-color: #0284c7; background: white; }

.f-col-btn { display: flex; align-items: flex-end; }
.btn-f-apply {
    padding: 8px 18px; background: #0f172a; color: white; border: none;
    border-radius: 8px; font-weight: 700; font-size: 0.84rem;
    font-family: 'Sarabun', sans-serif; cursor: pointer; height: 37px;
    display: inline-flex; align-items: center; justify-content: center; gap: 6px;
    white-space: nowrap; box-shadow: 0 2px 6px rgba(15, 23, 42, 0.15);
    transition: background 0.15s ease;
}
.btn-f-apply:hover { background: #1e293b; }

@media (max-width: 768px) {
    .filter-row {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    .f-col {
        max-width: 100% !important;
        width: 100%;
    }
    .f-col-btn {
        grid-column: 1 / -1;
        width: 100%;
    }
    .btn-f-apply {
        width: 100%;
        height: 42px;
        font-size: 0.9rem;
    }
    .map-layout {
        grid-template-columns: 1fr !important;
        height: auto !important;
    }
    #harvesterMap {
        height: 420px !important;
    }
}

.f-presets-wrap {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 12px;
    padding-bottom: 10px;
    border-bottom: 1px solid #f1f5f9;
    flex-wrap: wrap;
}
.btn-preset-chip {
    padding: 4px 12px;
    border-radius: 999px;
    font-size: 0.76rem;
    font-weight: 700;
    text-decoration: none;
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.btn-preset-chip:hover {
    background: #e2e8f0;
    color: #0f172a;
}
.btn-preset-chip.active {
    background: #0284c7;
    color: #ffffff;
    border-color: #0284c7;
    box-shadow: 0 2px 6px rgba(2, 132, 199, 0.3);
}
.dark-mode .f-presets-wrap {
    border-bottom-color: #1e293b !important;
}
.dark-mode .btn-preset-chip {
    background: #0b1120 !important;
    color: #94a3b8 !important;
    border-color: #1e293b !important;
}
.dark-mode .btn-preset-chip:hover {
    background: #1e293b !important;
    color: #f8fafc !important;
}
.dark-mode .btn-preset-chip.active {
    background: #0284c7 !important;
    color: #ffffff !important;
    border-color: #0284c7 !important;
}

/* ── MAIN MAP CONTAINER ── */
.map-layout {
    display: grid;
    grid-template-columns: 340px 1fr;
    gap: 16px;
    height: calc(100vh - 270px);
    min-height: 560px;
    margin-bottom: 20px;
}

/* ── SIDE LIST PANEL ── */
.map-sidebar-panel {
    background: white;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.panel-search-box {
    padding: 12px 14px;
    border-bottom: 1px solid #f1f5f9;
    position: relative;
}
.panel-search-input {
    width: 100%; padding: 8px 12px 8px 34px;
    border: 1.5px solid #e2e8f0; border-radius: 8px;
    font-size: 0.84rem; font-family: 'Sarabun', sans-serif;
    outline: none; background: #f8fafc;
}
.panel-search-input:focus { border-color: #0284c7; background: white; }
.panel-search-icon {
    position: absolute; left: 24px; top: 50%; transform: translateY(-50%);
    color: #94a3b8; font-size: 0.85rem;
}

.panel-list-wrap {
    flex: 1;
    overflow-y: auto;
    padding: 10px;
}
.panel-list-wrap::-webkit-scrollbar { width: 5px; }
.panel-list-wrap::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 6px; }

.hv-item-card {
    background: #f8fafc;
    border: 1.5px solid #f1f5f9;
    border-radius: 10px;
    padding: 10px 12px;
    margin-bottom: 8px;
    cursor: pointer;
    transition: all 0.15s ease;
}
.hv-item-card:hover {
    background: white;
    border-color: #0284c7;
    box-shadow: 0 4px 12px rgba(2, 132, 199, 0.1);
    transform: translateY(-1px);
}
.hv-item-card.has-no-gps {
    opacity: 0.6;
    background: #fafafa;
}
.hvic-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; }
.hvic-title { font-size: 0.9rem; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 6px; }
.hvic-sub { font-size: 0.74rem; color: #64748b; margin-top: 2px; }
.hvic-badge {
    font-size: 0.7rem; font-weight: 700; padding: 2px 7px; border-radius: 10px;
}
.hvic-badge.pass { background: #dcfce7; color: #15803d; }
.hvic-badge.fail { background: #fee2e2; color: #991b1b; }

/* ── MAP VIEW CARD ── */
.map-view-wrap {
    background: white;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    position: relative;
    overflow: hidden;
}
#harvesterMap {
    width: 100%;
    height: 100%;
    border-radius: 14px;
    z-index: 1;
}

/* Map layer toggle buttons */
.map-layer-controls {
    position: absolute;
    top: 12px;
    right: 12px;
    z-index: 999;
    display: flex;
    gap: 6px;
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(8px);
    padding: 5px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
.btn-layer-toggle {
    padding: 5px 10px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    background: white;
    color: #475569;
    font-size: 0.74rem;
    font-weight: 700;
    font-family: 'Sarabun', sans-serif;
    cursor: pointer;
    transition: all 0.15s;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.btn-layer-toggle.active {
    background: #0284c7;
    color: white;
    border-color: #0284c7;
}

/* ── MAP FULLSCREEN STYLES ── */
.map-view-wrap.is-fullscreen {
    position: fixed !important;
    inset: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    max-width: none !important;
    max-height: none !important;
    z-index: 9999999 !important;
    border-radius: 0 !important;
    border: none !important;
    margin: 0 !important;
    padding: 0 !important;
    box-shadow: none !important;
}
.map-view-wrap.is-fullscreen #harvesterMap {
    border-radius: 0 !important;
    width: 100% !important;
    height: 100% !important;
}
.map-view-wrap.is-fullscreen .map-layer-controls {
    top: 20px !important;
    right: 20px !important;
    z-index: 10000000 !important;
    box-shadow: 0 8px 24px rgba(0,0,0,0.3) !important;
}
.map-view-wrap.is-fullscreen .btn-fullscreen-toggle {
    background: #e11d48 !important;
    color: #ffffff !important;
    border-color: #e11d48 !important;
}

/* ── Custom Zoom Control Positioned at Bottom-Left ── */
.leaflet-bottom.leaflet-left {
    bottom: 16px !important;
    left: 16px !important;
    z-index: 998 !important;
}
.leaflet-control-zoom {
    border: 1px solid #e2e8f0 !important;
    border-radius: 10px !important;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.12) !important;
}
.leaflet-control-zoom-in,
.leaflet-control-zoom-out {
    background: #ffffff !important;
    color: #1e293b !important;
    width: 34px !important;
    height: 34px !important;
    line-height: 34px !important;
    font-size: 1.1rem !important;
    font-weight: bold !important;
    border-color: #f1f5f9 !important;
    transition: all 0.15s !important;
}
.leaflet-control-zoom-in:hover,
.leaflet-control-zoom-out:hover {
    background: #f8fafc !important;
    color: #0284c7 !important;
}
.dark-mode .leaflet-control-zoom {
    border-color: #1e293b !important;
}
.dark-mode .leaflet-control-zoom-in,
.dark-mode .leaflet-control-zoom-out {
    background: #131b2e !important;
    color: #f8fafc !important;
    border-color: #1e293b !important;
}
.dark-mode .leaflet-control-zoom-in:hover,
.dark-mode .leaflet-control-zoom-out:hover {
    background: #1e293b !important;
    color: #38bdf8 !important;
}

/* Custom Marker Pins */
.custom-leaflet-div-icon {
    background: transparent !important;
    border: none !important;
}
.custom-pin-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    color: white;
    font-size: 0.95rem;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
    border: 2.5px solid #ffffff;
    cursor: pointer;
    transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.custom-pin-wrap:hover {
    transform: scale(1.25);
}
.custom-pin-pass {
    background: linear-gradient(135deg, #10b981, #059669);
}
.custom-pin-fail {
    background: linear-gradient(135deg, #ef4444, #b91c1c);
    animation: pinPulse 2s infinite;
}
@keyframes pinPulse {
    0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
    100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
}

/* Popup styling */
.leaflet-popup-content-wrapper {
    border-radius: 14px;
    padding: 0;
    overflow: hidden;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    font-family: 'Sarabun', sans-serif;
}
.leaflet-popup-content {
    margin: 0;
    line-height: 1.4;
}
.mpop-header {
    background: linear-gradient(135deg, #1e293b, #0f172a);
    padding: 12px 16px;
    color: white;
    border-bottom: 3px solid #0284c7;
}
.mpop-header.fail { border-bottom-color: #ef4444; }
.mpop-title { font-size: 0.95rem; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 6px; }
.mpop-body { padding: 14px 16px; font-size: 0.8rem; color: #334155; }
.mpop-row { margin-bottom: 6px; display: flex; justify-content: space-between; }
.mpop-lbl { color: #64748b; font-weight: 600; }
.mpop-val { font-weight: 700; color: #0f172a; }
.mpop-btn-nav {
    display: block; width: 100%; text-align: center;
    background: #0284c7; color: white; text-decoration: none;
    padding: 8px 12px; border-radius: 8px; font-weight: 700; font-size: 0.8rem;
    margin-top: 10px; transition: background 0.15s;
}
.mpop-btn-nav:hover { background: #0369a1; }

@media(max-width: 900px) {
    .map-layout { grid-template-columns: 1fr; height: auto; }
    #harvesterMap { height: 420px; }
    .map-sidebar-panel { max-height: 300px; }
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
.dark-mode .map-filter-bar {
    background: #131b2e !important;
    border-color: #1e293b !important;
}
.dark-mode .f-lbl {
    color: #cbd5e1 !important;
}
.dark-mode .f-inp {
    background: #0b1120 !important;
    color: #f8fafc !important;
    border-color: #1e293b !important;
}
.dark-mode .f-inp:focus {
    background: #0f172a !important;
    border-color: #0284c7 !important;
}
.dark-mode .map-sidebar-panel,
.dark-mode .map-view-wrap {
    background: #131b2e !important;
    border-color: #1e293b !important;
}
.dark-mode .panel-search-box {
    border-bottom-color: #1e293b !important;
}
.dark-mode .panel-search-input {
    background: #0b1120 !important;
    color: #f8fafc !important;
    border-color: #1e293b !important;
}
.dark-mode .hv-item-card {
    background: #0b1120 !important;
    border-color: #1e293b !important;
}
.dark-mode .hv-item-card:hover {
    background: #1e293b !important;
    border-color: #0284c7 !important;
}
.dark-mode .hvic-title {
    color: #f8fafc !important;
}
.dark-mode .hvic-sub {
    color: #94a3b8 !important;
}
.dark-mode .map-layer-controls {
    background: rgba(19, 27, 46, 0.9) !important;
    border-color: #1e293b !important;
}
.dark-mode .btn-layer-toggle {
    background: #0b1120 !important;
    color: #cbd5e1 !important;
    border-color: #1e293b !important;
}
.dark-mode .btn-layer-toggle.active {
    background: #0284c7 !important;
    color: white !important;
    border-color: #0284c7 !important;
}
</style>

<div class="page-wrapper">
    <?php include 'includes/nav_u_sidebar.php'; ?>

    <div class="dash-wrap">
        <div class="content-wrapper">

            <!-- 1. MAP HEADER -->
            <div class="map-header">
                <div class="mh-left">
                    <div class="mh-icon"><i class="fa-solid fa-map-location-dot"></i></div>
                    <div>
                        <h1 class="mh-title">แผนที่พิกัดรถตัดและแปลงอ้อย (GIS Field Map)</h1>
                        <div class="mh-sub">
                            <span>ระบบติดตามตำแหน่งการทำงานของรถตัดภาคสนาม</span>
                            <span>•</span>
                            <span>ปีการผลิต <?php echo htmlspecialchars($crop_year === 'all' ? 'ทั้งหมด' : $crop_year); ?></span>
                            <span>•</span>
                            <span style="color:<?php echo ($date_from===$today&&$date_to===$today)?'#38bdf8':'#cbd5e1'; ?>;font-weight:700;">
                                <i class="fa-solid fa-calendar-day"></i> 
                                <?php 
                                if ($date_from === $date_to && $date_from === $today) {
                                    echo 'ข้อมูลประจำวันนี้ (' . date('d/m/Y') . ')';
                                } elseif ($date_from && $date_to) {
                                    echo 'ช่วงวันที่ ' . date('d/m/Y', strtotime($date_from)) . ' ถึง ' . date('d/m/Y', strtotime($date_to));
                                } else {
                                    echo 'ทุกช่วงเวลา';
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="mh-stats">
                    <div class="mh-stat-chip">
                        <div class="mh-stat-num" style="color:#38bdf8;"><?php echo $total_with_gps; ?></div>
                        <div class="mh-stat-lbl">มีพิกัด GPS บนแผนที่</div>
                    </div>
                    <div class="mh-stat-chip">
                        <div class="mh-stat-num" style="color:#4ade80;"><?php echo $total_pass; ?></div>
                        <div class="mh-stat-lbl">ผ่านสมบูรณ์</div>
                    </div>
                    <div class="mh-stat-chip" style="<?php echo ($total_fail > 0) ? 'background:rgba(239,68,68,0.18);border-color:#ef4444;' : ''; ?>">
                        <div class="mh-stat-num" style="color:#f87171;"><?php echo $total_fail; ?></div>
                        <div class="mh-stat-lbl">พบจุดบกพร่อง / ต้องแก้ไข</div>
                    </div>
                </div>
            </div>

            <!-- 2. FILTER TOOLBAR -->
            <div class="map-filter-bar">
                <!-- Preset Quick Buttons -->
                <div class="f-presets-wrap">
                    <span style="font-size:0.75rem;font-weight:700;color:#64748b;display:inline-flex;align-items:center;gap:4px;">
                        <i class="fa-solid fa-bolt" style="color:#eab308;"></i> ตัวกรองด่วน:
                    </span>
                    <a href="harvester_map.php?preset=today&crop_year=<?php echo urlencode($crop_year); ?>&zone=<?php echo urlencode($filter_zone); ?>&status=<?php echo urlencode($filter_status); ?>" class="btn-preset-chip <?php echo ($preset==='today'||($date_from===$today&&$date_to===$today))?'active':''; ?>">
                        <i class="fa-solid fa-calendar-day"></i> วันนี้ (<?php echo date('d/m'); ?>)
                    </a>
                    <a href="harvester_map.php?preset=7days&crop_year=<?php echo urlencode($crop_year); ?>&zone=<?php echo urlencode($filter_zone); ?>&status=<?php echo urlencode($filter_status); ?>" class="btn-preset-chip <?php echo ($preset==='7days')?'active':''; ?>">
                        7 วันล่าสุด
                    </a>
                    <a href="harvester_map.php?preset=30days&crop_year=<?php echo urlencode($crop_year); ?>&zone=<?php echo urlencode($filter_zone); ?>&status=<?php echo urlencode($filter_status); ?>" class="btn-preset-chip <?php echo ($preset==='30days')?'active':''; ?>">
                        30 วัน
                    </a>
                    <a href="harvester_map.php?preset=all&crop_year=<?php echo urlencode($crop_year); ?>&zone=<?php echo urlencode($filter_zone); ?>&status=<?php echo urlencode($filter_status); ?>" class="btn-preset-chip <?php echo ($preset==='all'||(empty($date_from)&&empty($date_to)))?'active':''; ?>">
                        ทุกช่วงเวลา
                    </a>
                </div>

                <form method="GET" action="harvester_map.php">
                    <div class="filter-row">
                        <!-- ปีการผลิต -->
                        <div class="f-col" style="max-width: 130px;">
                            <label class="f-lbl"><i class="fa-solid fa-calendar"></i> ปีการผลิต</label>
                            <select name="crop_year" class="f-inp">
                                <option value="all" <?php echo $crop_year==='all'?'selected':''; ?>>ทุกปีผลิต</option>
                                <?php foreach($all_crops as $cy): ?>
                                <option value="<?php echo htmlspecialchars($cy); ?>" <?php echo $crop_year===$cy?'selected':''; ?>>ปี <?php echo htmlspecialchars($cy); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- หน่วยงาน -->
                        <div class="f-col" style="max-width: 170px;">
                            <label class="f-lbl"><i class="fa-solid fa-location-dot"></i> หน่วยงาน</label>
                            <select name="zone" class="f-inp">
                                <option value="">ทุกหน่วยงาน</option>
                                <?php foreach($all_zones as $zn): ?>
                                <option value="<?php echo htmlspecialchars($zn); ?>" <?php echo $filter_zone===$zn?'selected':''; ?>><?php echo htmlspecialchars($zn); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- สถานะ -->
                        <div class="f-col" style="max-width: 140px;">
                            <label class="f-lbl"><i class="fa-solid fa-filter"></i> สถานะผลตรวจ</label>
                            <select name="status" class="f-inp">
                                <option value="all" <?php echo $filter_status==='all'?'selected':''; ?>>ทั้งหมด</option>
                                <option value="pass" <?php echo $filter_status==='pass'?'selected':''; ?>>🟢 ผ่านทั้งหมด</option>
                                <option value="fail" <?php echo $filter_status==='fail'?'selected':''; ?>>🔴 พบจุดบกพร่อง</option>
                            </select>
                        </div>

                        <!-- วันที่ -->
                        <div class="f-col" style="max-width: 140px;">
                            <label class="f-lbl">ตั้งแต่วันที่</label>
                            <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" class="f-inp" style="-webkit-appearance:none; appearance:none; min-width:0; max-width:100%; box-sizing:border-box; height:37px; display:block;">
                        </div>
                        <div class="f-col" style="max-width: 140px;">
                            <label class="f-lbl">ถึงวันที่</label>
                            <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" class="f-inp" style="-webkit-appearance:none; appearance:none; min-width:0; max-width:100%; box-sizing:border-box; height:37px; display:block;">
                        </div>

                        <div class="f-col-btn">
                            <button type="submit" class="btn-f-apply">
                                <i class="fa-solid fa-magnifying-glass"></i> ค้นหา
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- 3. MAIN MAP & SIDEBAR LIST -->
            <div class="map-layout">
                <!-- Sidebar List Panel -->
                <div class="map-sidebar-panel">
                    <div class="panel-search-box">
                        <i class="fa-solid fa-magnifying-glass panel-search-icon"></i>
                        <input type="text" id="harvesterSearchInput" class="panel-search-input" placeholder="พิมพ์ค้นหาเบอร์รถ / พนักงาน..." oninput="filterHarvesterList(this.value)">
                    </div>

                    <div class="panel-list-wrap" id="harvesterListContainer">
                        <?php if (empty($sessions)): ?>
                        <div style="text-align:center; padding:30px 10px; color:#94a3b8; font-size:0.84rem;">
                            <i class="fa-solid fa-inbox" style="font-size:1.8rem; display:block; margin-bottom:6px;"></i>
                            ไม่พบรายการตรวจเช็คตามเงื่อนไข
                        </div>
                        <?php else: ?>
                        <?php foreach ($sessions as $idx => $s): 
                            $has_gps = (!empty($s['latitude']) && !empty($s['longitude']));
                        ?>
                        <div class="hv-item-card <?php echo !$has_gps ? 'has-no-gps' : ''; ?>" 
                             data-num="<?php echo htmlspecialchars($s['harvester_number']); ?>"
                             data-name="<?php echo htmlspecialchars($s['emp_name']); ?>"
                             data-lat="<?php echo $s['latitude'] ?? ''; ?>"
                             data-lng="<?php echo $s['longitude'] ?? ''; ?>"
                             data-idx="<?php echo $idx; ?>"
                             onclick="focusHarvesterMarker(<?php echo $idx; ?>)">
                            <div class="hvic-top">
                                <div class="hvic-title">
                                    <i class="fa-solid fa-tractor" style="color:<?php echo $s['is_pass'] ? '#10b981' : '#ef4444'; ?>;"></i>
                                    <?php echo htmlspecialchars($s['harvester_number']); ?>
                                </div>
                                <span class="hvic-badge <?php echo $s['is_pass'] ? 'pass' : 'fail'; ?>">
                                    <?php echo $s['is_pass'] ? 'ผ่าน' : 'มีจุดแก้ไข'; ?>
                                </span>
                            </div>
                            <div class="hvic-sub">
                                <div><i class="fa-solid fa-user" style="font-size:0.7rem; margin-right:3px;"></i> <?php echo htmlspecialchars($s['emp_name'] ?: $s['emp_id']); ?> (<?php echo htmlspecialchars($s['emp_unit'] ?: '-'); ?>)</div>
                                <div style="display:flex; justify-content:space-between; margin-top:2px;">
                                    <span><i class="fa-solid fa-leaf" style="font-size:0.7rem; margin-right:3px;"></i> <?php echo htmlspecialchars($s['field_condition'] ?: 'แปลงทั่วไป'); ?></span>
                                    <span><?php echo date('d/m/y H:i', strtotime($s['checked_at'])); ?></span>
                                </div>
                                <?php if($has_gps): ?>
                                <div style="color:#0284c7; font-weight:700; font-size:0.7rem; margin-top:3px;">
                                    <i class="fa-solid fa-location-dot"></i> มีพิกัด GPS (คลิกเพื่อดูบนแผนที่)
                                </div>
                                <?php else: ?>
                                <div style="color:#94a3b8; font-size:0.7rem; margin-top:3px;">
                                    <i class="fa-solid fa-location-slash"></i> ไม่ได้ระบุพิกัด GPS
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Map View Area -->
                <div class="map-view-wrap">
                    <!-- Layer switcher buttons -->
                    <div class="map-layer-controls">
                        <button type="button" class="btn-layer-toggle active" id="btnStreet" onclick="switchMapLayer('street')">
                            <i class="fa-solid fa-map"></i> แผนที่ถนน
                        </button>
                        <button type="button" class="btn-layer-toggle" id="btnSat" onclick="switchMapLayer('satellite')">
                            <i class="fa-solid fa-satellite"></i> ภาพถ่ายดาวเทียม
                        </button>
                        <button type="button" class="btn-layer-toggle btn-fullscreen-toggle" id="btnFullscreen" onclick="toggleMapFullscreen()" title="ขยายแผนที่เต็มจอ / ย่อขนาด">
                            <i class="fa-solid fa-expand" id="iconFullscreen"></i> <span id="textFullscreen">เต็มจอ</span>
                        </button>
                    </div>

                    <div id="harvesterMap"></div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Leaflet JS & MarkerCluster JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

<script>
const sessionsData = <?php echo json_encode($sessions); ?>;

let map;
let streetLayer;
let satelliteLayer;
let markersGroup;
let markerInstances = {};

document.addEventListener('DOMContentLoaded', function() {
    // 1. Initialize Leaflet Map (Center default to Sukhothai / Uttaradit KTIS area)
    const defaultCenter = [17.6200, 100.0990];
    map = L.map('harvesterMap', {
        center: defaultCenter,
        zoom: 10,
        zoomControl: false
    });

    // ย้ายปุ่มซูม (+) (-) มาไว้มุมซ้ายล่าง เพื่อไม่ให้ทับกับแถบสลับเลเยอร์แผนที่
    L.control.zoom({
        position: 'bottomleft'
    }).addTo(map);

    // Street Layer (OpenStreetMap)
    streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Satellite Layer (Esri World Imagery)
    satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19,
        attribution: '&copy; Esri & Maxar'
    });

    // Marker Cluster Group
    markersGroup = L.markerClusterGroup({
        maxClusterRadius: 40,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,
        zoomToBoundsOnClick: true
    });

    // 2. Plot Harvester Markers
    let validBounds = [];

    sessionsData.forEach((s, idx) => {
        if (!s.latitude || !s.longitude) return;

        const lat = parseFloat(s.latitude);
        const lng = parseFloat(s.longitude);
        if (isNaN(lat) || isNaN(lng)) return;

        validBounds.push([lat, lng]);

        // Custom HTML Marker Icon
        const isPass = s.is_pass;
        const iconClass = isPass ? 'custom-pin-pass' : 'custom-pin-fail';
        const iconSymbol = isPass ? '<i class="fa-solid fa-tractor"></i>' : '<i class="fa-solid fa-triangle-exclamation"></i>';

        const customIcon = L.divIcon({
            className: 'custom-leaflet-div-icon',
            html: `<div class="custom-pin-wrap ${iconClass}">${iconSymbol}</div>`,
            iconSize: [36, 36],
            iconAnchor: [18, 18],
            popupAnchor: [0, -20]
        });

        // Popup Content
        let failsHtml = '';
        if (!isPass && s.fails && s.fails.length > 0) {
            failsHtml = '<div style="background:#fef2f2; border:1px solid #fecaca; border-radius:6px; padding:6px; margin-top:8px; font-size:0.74rem;">' +
                        '<strong style="color:#991b1b;"><i class="fa-solid fa-triangle-exclamation"></i> รายการที่ไม่ผ่าน:</strong><ul style="margin:4px 0 0 16px; padding:0; color:#b91c1c;">';
            s.fails.forEach(f => {
                failsHtml += `<li>${f.item_name_cut}${f.note ? ' (' + f.note + ')' : ''}</li>`;
            });
            failsHtml += '</ul></div>';
        }

        let imgHtml = '';
        if (s.img_harvester) {
            imgHtml = `<div style="margin-top:8px;"><img src="${s.img_harvester}" style="width:100%; height:110px; object-fit:cover; border-radius:8px; border:1px solid #e2e8f0;"></div>`;
        }

        const popupContent = `
            <div class="mpop-header ${isPass ? 'pass' : 'fail'}">
                <div class="mpop-title">
                    <i class="fa-solid fa-tractor"></i>
                    ${s.harvester_number}
                </div>
            </div>
            <div class="mpop-body">
                <div class="mpop-row"><span class="mpop-lbl">ผู้ตรวจ:</span><span class="mpop-val">${s.emp_name || s.emp_id}</span></div>
                <div class="mpop-row"><span class="mpop-lbl">หน่วยงาน:</span><span class="mpop-val">${s.emp_unit || '-'}</span></div>
                <div class="mpop-row"><span class="mpop-lbl">สภาพแปลง:</span><span class="mpop-val">${s.field_condition || '-'}</span></div>
                <div class="mpop-row"><span class="mpop-lbl">วันที่/เวลา:</span><span class="mpop-val">${s.checked_at}</span></div>
                <div class="mpop-row"><span class="mpop-lbl">พิกัด GPS:</span><span class="mpop-val" style="font-family:monospace;">${lat.toFixed(5)}, ${lng.toFixed(5)}</span></div>
                ${failsHtml}
                ${imgHtml}
                <a href="https://www.google.com/maps?q=${lat},${lng}" target="_blank" class="mpop-btn-nav">
                    <i class="fa-solid fa-diamond-turn-right"></i> เปิดนำทางใน Google Maps
                </a>
            </div>
        `;

        const marker = L.marker([lat, lng], { icon: customIcon }).bindPopup(popupContent, { maxWidth: 280 });
        markersGroup.addLayer(marker);
        markerInstances[idx] = marker;
    });

    map.addLayer(markersGroup);

    // Auto-fit map to bounds if we have pins
    if (validBounds.length > 0) {
        map.fitBounds(validBounds, { padding: [40, 40], maxZoom: 14 });
    }
});

// Switch Map Layers
function switchMapLayer(type) {
    if (type === 'street') {
        if (map.hasLayer(satelliteLayer)) map.removeLayer(satelliteLayer);
        map.addLayer(streetLayer);
        document.getElementById('btnStreet').classList.add('active');
        document.getElementById('btnSat').classList.remove('active');
    } else {
        if (map.hasLayer(streetLayer)) map.removeLayer(streetLayer);
        map.addLayer(satelliteLayer);
        document.getElementById('btnSat').classList.add('active');
        document.getElementById('btnStreet').classList.remove('active');
    }
}

// Focus on Marker from Sidebar List
function focusHarvesterMarker(idx) {
    const marker = markerInstances[idx];
    if (marker) {
        markersGroup.zoomToShowLayer(marker, function() {
            marker.openPopup();
            map.panTo(marker.getLatLng());
        });
    } else {
        Swal.fire({
            icon: 'info',
            title: 'ไม่มีพิกัด GPS',
            text: 'รายการตรวจเช็คของรถตัดคันนี้ไม่ได้มีการบันทึกพิกัด GPS ไว้',
            confirmButtonText: 'ตกลง',
            confirmButtonColor: '#0284c7'
        });
    }
}

// Live Search Filter for Sidebar List
function filterHarvesterList(query) {
    const q = query.trim().toLowerCase();
    const items = document.querySelectorAll('.hv-item-card');
    items.forEach(card => {
        const num = (card.dataset.num || '').toLowerCase();
        const name = (card.dataset.name || '').toLowerCase();
        if (num.includes(q) || name.includes(q)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// ── Fullscreen Map Toggle ──
function toggleMapFullscreen() {
    const mapWrap = document.querySelector('.map-view-wrap');
    const icon = document.getElementById('iconFullscreen');
    const text = document.getElementById('textFullscreen');
    
    if (!mapWrap) return;
    
    const isFull = mapWrap.classList.toggle('is-fullscreen');
    
    if (isFull) {
        document.body.style.overflow = 'hidden';
        if (icon) icon.className = 'fa-solid fa-compress';
        if (text) text.textContent = 'ย่อจอ';
    } else {
        document.body.style.overflow = '';
        if (icon) icon.className = 'fa-solid fa-expand';
        if (text) text.textContent = 'เต็มจอ';
    }
    
    // บังคับให้ Leaflet คำนวณขนาดหน้าจอใหม่ทันที
    setTimeout(() => {
        if (map) {
            map.invalidateSize();
        }
    }, 200);
}

// รองรับปุ่ม ESC เพื่อออกจากโหมดเต็มจอ
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const mapWrap = document.querySelector('.map-view-wrap');
        if (mapWrap && mapWrap.classList.contains('is-fullscreen')) {
            toggleMapFullscreen();
        }
    }
});
</script>
</body>
</html>
