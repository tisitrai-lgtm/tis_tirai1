<?php
/**
 * report_all.php — รายงานภาพรวมระบบ (Executive Comprehensive Overview Report)
 * TIS SMART FIELD - ระบบรายงานสถิติและภาพรวมการปฏิบัติงานฝ่ายไร่
 */
require_once 'config.php';
session_start();

if (!isset($_SESSION['emp_id']) || $_SESSION['emp_level'] !== 'a') {
    header("location: login.php");
    exit;
}

// ─────────────────────────────────────────────────────────────
// 1. รับค่าตัวกรอง (Filters)
// ─────────────────────────────────────────────────────────────
$default_crop = $_SESSION['crop_year'] ?? '69/70';
$crop_year    = trim($_GET['crop_year'] ?? $default_crop);
$filter_zone  = trim($_GET['zone'] ?? '');
$preset       = trim($_GET['preset'] ?? 'all');
$date_from    = trim($_GET['date_from'] ?? '');
$date_to      = trim($_GET['date_to'] ?? '');

// คำนวณช่วงวันที่จาก preset
$today = date('Y-m-d');
if ($preset === 'today') {
    $date_from = $today;
    $date_to   = $today;
} elseif ($preset === '7days') {
    $date_from = date('Y-m-d', strtotime('-7 days'));
    $date_to   = $today;
} elseif ($preset === '30days') {
    $date_from = date('Y-m-d', strtotime('-30 days'));
    $date_to   = $today;
} elseif ($preset === 'month') {
    $date_from = date('Y-m-01');
    $date_to   = date('Y-m-t');
}

// รายการปีการผลิตทั้งหมดในระบบ
$all_crops = [];
try {
    $stmt_c = $conn->query("SELECT DISTINCT crop_year FROM posts WHERE crop_year IS NOT NULL AND crop_year != '' UNION SELECT DISTINCT crop_year FROM check_sessions WHERE crop_year IS NOT NULL AND crop_year != '' ORDER BY crop_year DESC");
    $all_crops = $stmt_c->fetchAll(PDO::FETCH_COLUMN);
    if (empty($all_crops) && !empty($default_crop)) $all_crops = [$default_crop];
} catch (Exception $e) {}

// รายการหน่วยงานทั้งหมด
$all_zones = [];
try {
    $stmt_z = $conn->query("SELECT DISTINCT emp_unit FROM employee WHERE emp_unit IS NOT NULL AND emp_unit != '' ORDER BY emp_unit ASC");
    $all_zones = $stmt_z->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {}

// ─────────────────────────────────────────────────────────────
// 2. สร้างเงื่อนไข SQL สำหรับ Posts และ Check Sessions
// ─────────────────────────────────────────────────────────────
$p_where = ["1=1"];
$p_params = [];
$c_where = ["1=1"];
$c_params = [];

if ($crop_year !== '' && $crop_year !== 'all') {
    $p_where[] = "p.crop_year = :p_crop";
    $p_params[':p_crop'] = $crop_year;
    $c_where[] = "cs.crop_year = :c_crop";
    $c_params[':c_crop'] = $crop_year;
}

if ($date_from !== '') {
    $p_where[] = "DATE(p.created_at) >= :p_dfrom";
    $p_params[':p_dfrom'] = $date_from;
    $c_where[] = "DATE(cs.checked_at) >= :c_dfrom";
    $c_params[':c_dfrom'] = $date_from;
}

if ($date_to !== '') {
    $p_where[] = "DATE(p.created_at) <= :p_dto";
    $p_params[':p_dto'] = $date_to;
    $c_where[] = "DATE(cs.checked_at) <= :c_dto";
    $c_params[':c_dto'] = $date_to;
}

if ($filter_zone !== '') {
    $p_where[] = "(p.target_unit = :p_zone OR e.emp_unit = :p_zone)";
    $p_params[':p_zone'] = $filter_zone;
    $c_where[] = "e.emp_unit = :c_zone";
    $c_params[':c_zone'] = $filter_zone;
}

$p_where_sql = implode(" AND ", $p_where);
$c_where_sql = implode(" AND ", $c_where);

// ─────────────────────────────────────────────────────────────
// 3. ดึงข้อมูลสถิติภาพรวม (KPIs)
// ─────────────────────────────────────────────────────────────

// 3.1 สถิติปัญหาและรายงานแปลง (Posts)
$kpi_posts_total     = 0;
$kpi_posts_pending   = 0;
$kpi_posts_completed = 0;

try {
    $stmt_p = $conn->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN p.job_status = 'pending' THEN 1 ELSE 0 END) as pending_cnt,
            SUM(CASE WHEN p.job_status = 'success' THEN 1 ELSE 0 END) as completed_cnt
        FROM posts p
        LEFT JOIN employee e ON p.emp_id = e.emp_id
        WHERE $p_where_sql
    ");
    $stmt_p->execute($p_params);
    $post_stats = $stmt_p->fetch(PDO::FETCH_ASSOC);
    if ($post_stats) {
        $kpi_posts_total     = (int)($post_stats['total'] ?? 0);
        $kpi_posts_pending   = (int)($post_stats['pending_cnt'] ?? 0);
        $kpi_posts_completed = (int)($post_stats['completed_cnt'] ?? 0);
    }
} catch (Exception $e) {}

$post_resolve_rate = $kpi_posts_total > 0 ? round(($kpi_posts_completed / $kpi_posts_total) * 100, 1) : 0;

// 3.2 สถิติการตรวจเช็กรถตัด (Harvester Check Sessions)
// 3.2 สถิติการตรวจเช็กรถตัด (Harvester Check Sessions)
$kpi_checks_total      = 0;
$kpi_checks_pass       = 0;
$kpi_checks_fail       = 0;
$kpi_active_harvesters = 0;

try {
    $stmt_c = $conn->prepare("
        SELECT 
            COUNT(DISTINCT cs.session_id) as total,
            COUNT(DISTINCT cs.harvester_number) as total_harvesters
        FROM check_sessions cs
        LEFT JOIN employee e ON cs.emp_id = e.emp_id
        WHERE $c_where_sql
    ");
    $stmt_c->execute($c_params);
    $chk_stats = $stmt_c->fetch(PDO::FETCH_ASSOC);
    if ($chk_stats) {
        $kpi_checks_total      = (int)($chk_stats['total'] ?? 0);
        $kpi_active_harvesters = (int)($chk_stats['total_harvesters'] ?? 0);
    }
} catch (Exception $e) {}

// ตรวจหาจำนวน session ที่มีจุดบกพร่อง (fail >= 1 item)
try {
    $stmt_fail = $conn->prepare("
        SELECT COUNT(DISTINCT cs.session_id) as fail_sessions
        FROM check_sessions cs
        LEFT JOIN employee e ON cs.emp_id = e.emp_id
        JOIN check_results cr ON cs.session_id = cr.session_id
        WHERE cr.pass = 0 AND $c_where_sql
    ");
    $stmt_fail->execute($c_params);
    $kpi_checks_fail = (int)$stmt_fail->fetchColumn();
    $kpi_checks_pass = max(0, $kpi_checks_total - $kpi_checks_fail);
} catch (Exception $e) {}

$check_pass_rate = $kpi_checks_total > 0 ? round(($kpi_checks_pass / $kpi_checks_total) * 100, 1) : 0;

// 3.3 สถิติกำลังพล & รถตัดในระบบ
$stat_total_emp     = 0;
$stat_mgr_emp       = 0;
$stat_total_hv      = 0;
$stat_active_hv     = 0;
try {
    $stat_total_emp = (int)$conn->query("SELECT COUNT(*) FROM employee WHERE status = 1")->fetchColumn();
    $stat_mgr_emp   = (int)$conn->query("SELECT COUNT(*) FROM employee WHERE is_harvester_manager = 1 AND status = 1")->fetchColumn();
    $stat_total_hv  = (int)$conn->query("SELECT COUNT(*) FROM harvesters")->fetchColumn();
    $stat_active_hv = (int)$conn->query("SELECT COUNT(*) FROM harvesters WHERE is_active = 1")->fetchColumn();
} catch (Exception $e) {}

// ─────────────────────────────────────────────────────────────
// 4. สรุปประเภทปัญหาที่พบบ่อย (Problem Types Breakdown)
// ─────────────────────────────────────────────────────────────
$problem_breakdown = [];
try {
    // รวมปัญหาจาก 3 คอลัมน์ problem_detail, problem_detail_2, problem_detail_3
    $pb_where_1 = "WHERE p.problem_detail IS NOT NULL AND p.problem_detail != '' " . ($crop_year !== 'all' ? "AND p.crop_year = :cy1 " : "") . ($date_from !== '' ? "AND DATE(p.created_at) >= :df1 " : "") . ($date_to !== '' ? "AND DATE(p.created_at) <= :dt1 " : "") . ($filter_zone !== '' ? "AND (p.target_unit = :zn1 OR e.emp_unit = :zn1) " : "");
    $pb_where_2 = "WHERE p.problem_detail_2 IS NOT NULL AND p.problem_detail_2 != '' " . ($crop_year !== 'all' ? "AND p.crop_year = :cy2 " : "") . ($date_from !== '' ? "AND DATE(p.created_at) >= :df2 " : "") . ($date_to !== '' ? "AND DATE(p.created_at) <= :dt2 " : "") . ($filter_zone !== '' ? "AND (p.target_unit = :zn2 OR e.emp_unit = :zn2) " : "");
    $pb_where_3 = "WHERE p.problem_detail_3 IS NOT NULL AND p.problem_detail_3 != '' " . ($crop_year !== 'all' ? "AND p.crop_year = :cy3 " : "") . ($date_from !== '' ? "AND DATE(p.created_at) >= :df3 " : "") . ($date_to !== '' ? "AND DATE(p.created_at) <= :dt3 " : "") . ($filter_zone !== '' ? "AND (p.target_unit = :zn3 OR e.emp_unit = :zn3) " : "");

    $pb_sql = "
        SELECT prob_name, COUNT(*) as cnt FROM (
            SELECT p.problem_detail as prob_name FROM posts p LEFT JOIN employee e ON p.emp_id = e.emp_id $pb_where_1
            UNION ALL
            SELECT p.problem_detail_2 as prob_name FROM posts p LEFT JOIN employee e ON p.emp_id = e.emp_id $pb_where_2
            UNION ALL
            SELECT p.problem_detail_3 as prob_name FROM posts p LEFT JOIN employee e ON p.emp_id = e.emp_id $pb_where_3
        ) as all_probs
        GROUP BY prob_name
        ORDER BY cnt DESC
        LIMIT 7
    ";
    $pb_params = [];
    for ($idx = 1; $idx <= 3; $idx++) {
        if ($crop_year !== 'all') $pb_params[":cy$idx"] = $crop_year;
        if ($date_from !== '')   $pb_params[":df$idx"] = $date_from;
        if ($date_to !== '')     $pb_params[":dt$idx"] = $date_to;
        if ($filter_zone !== '') $pb_params[":zn$idx"] = $filter_zone;
    }
    $stmt_pb = $conn->prepare($pb_sql);
    $stmt_pb->execute($pb_params);
    $problem_breakdown = $stmt_pb->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// ─────────────────────────────────────────────────────────────
// 5. สรุปผลงานแยกตามหน่วยงาน (Performance by Zone/Unit)
// ─────────────────────────────────────────────────────────────
$zone_performance = [];
try {
    $stmt_zp = $conn->prepare("
        SELECT 
            COALESCE(NULLIF(p.target_unit, ''), e.emp_unit, 'ไม่ระบุ') as unit_name,
            COUNT(DISTINCT p.post_id) as post_count,
            SUM(CASE WHEN p.job_status = 'success' THEN 1 ELSE 0 END) as post_done,
            COUNT(DISTINCT cs.session_id) as check_count
        FROM employee e
        LEFT JOIN posts p ON (e.emp_unit = p.target_unit OR e.emp_id = p.emp_id) " . ($crop_year !== 'all' ? "AND p.crop_year = :p_crop " : "") . "
        LEFT JOIN check_sessions cs ON e.emp_id = cs.emp_id " . ($crop_year !== 'all' ? "AND cs.crop_year = :c_crop " : "") . "
        WHERE e.emp_unit IS NOT NULL AND e.emp_unit != ''
        GROUP BY unit_name
        ORDER BY (COUNT(DISTINCT p.post_id) + COUNT(DISTINCT cs.session_id)) DESC
        LIMIT 10
    ");
    $zp_params = [];
    if ($crop_year !== 'all') {
        $zp_params[':p_crop'] = $crop_year;
        $zp_params[':c_crop'] = $crop_year;
    }
    $stmt_zp->execute($zp_params);
    $zone_performance = $stmt_zp->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// ─────────────────────────────────────────────────────────────
// 6. ชิ้นส่วนรถตัดที่พบข้อบกพร่องบ่อยที่สุด (Top Failed Machine Items)
// ─────────────────────────────────────────────────────────────
$failed_items = [];
try {
    $stmt_fi = $conn->prepare("
        SELECT 
            cic.item_name_cut,
            cic.section_label,
            COUNT(cr.result_id) as fail_count
        FROM check_results cr
        JOIN check_sessions cs ON cr.session_id = cs.session_id
        LEFT JOIN employee e ON cs.emp_id = e.emp_id
        JOIN check_items_cut cic ON cr.item_id = cic.item_id
        WHERE cr.pass = 0 AND $c_where_sql
        GROUP BY cic.item_id
        ORDER BY fail_count DESC
        LIMIT 6
    ");
    $stmt_fi->execute($c_params);
    $failed_items = $stmt_fi->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// ─────────────────────────────────────────────────────────────
// 7. แนวโน้มรายเดือน (Monthly Trends for Chart)
// ─────────────────────────────────────────────────────────────
$trend_labels = [];
$trend_posts  = [];
$trend_checks = [];

try {
    // 6 เดือนล่าสุด
    for ($i = 5; $i >= 0; $i--) {
        $ym = date('Y-m', strtotime("-$i months"));
        $label = date('M y', strtotime("-$i months"));
        $trend_labels[] = $label;

        // นับ posts ในเดือนนั้น
        $stmt_tm_p = $conn->prepare("SELECT COUNT(*) FROM posts WHERE DATE_FORMAT(created_at, '%Y-%m') = :ym " . ($crop_year !== 'all' ? "AND crop_year = :crop" : ""));
        $p_args = [':ym' => $ym];
        if ($crop_year !== 'all') $p_args[':crop'] = $crop_year;
        $stmt_tm_p->execute($p_args);
        $trend_posts[] = (int)$stmt_tm_p->fetchColumn();

        // นับ check sessions ในเดือนนั้น
        $stmt_tm_c = $conn->prepare("SELECT COUNT(*) FROM check_sessions WHERE DATE_FORMAT(checked_at, '%Y-%m') = :ym " . ($crop_year !== 'all' ? "AND crop_year = :crop" : ""));
        $c_args = [':ym' => $ym];
        if ($crop_year !== 'all') $c_args[':crop'] = $crop_year;
        $stmt_tm_c->execute($c_args);
        $trend_checks[] = (int)$stmt_tm_c->fetchColumn();
    }
} catch (Exception $e) {}

// ─────────────────────────────────────────────────────────────
// 8. ปัญหาที่ยังค้างรอดำเนินการล่าสุด (Urgent Pending Issues)
// ─────────────────────────────────────────────────────────────
$pending_posts = [];
try {
    $stmt_pp = $conn->prepare("
        SELECT p.*, e.emp_name, e.emp_unit as poster_unit
        FROM posts p
        LEFT JOIN employee e ON p.emp_id = e.emp_id
        WHERE p.job_status = 'pending' AND $p_where_sql
        ORDER BY p.created_at DESC
        LIMIT 6
    ");
    $stmt_pp->execute($p_params);
    $pending_posts = $stmt_pp->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

include 'includes/nav_u_header.php';
?>

<style>
* { box-sizing: border-box; }
body { font-family: 'Sarabun', sans-serif; background: #f8fafc; color: #1e293b; margin: 0; }

.page-wrapper { display: flex; min-height: 100vh; width: 100%; align-items: flex-start; }
.dash-wrap { flex: 1; padding: 24px 28px 60px; min-width: 0; overflow-x: hidden; width: 100%; }
.content-wrapper { max-width: 100%; width: 100%; margin: 0; }

/* EXECUTIVE HEADER */
.report-header {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border-radius: 16px;
    padding: 24px 28px;
    color: white;
    margin-bottom: 22px;
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.2);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    border: 1px solid rgba(255,255,255,0.08);
}
.rh-left { display: flex; align-items: center; gap: 16px; }
.rh-icon {
    width: 54px;
    height: 54px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    box-shadow: 0 8px 16px rgba(59, 130, 246, 0.3);
    flex-shrink: 0;
}
.rh-title { font-size: 1.35rem; font-weight: 800; margin: 0 0 4px 0; letter-spacing: -0.3px; }
.rh-sub { font-size: 0.84rem; color: #94a3b8; margin: 0; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.rh-tag { background: rgba(59,130,246,0.2); color: #60a5fa; border: 1px solid rgba(59,130,246,0.3); padding: 2px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; }

.rh-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
.btn-rh {
    padding: 9px 16px;
    border-radius: 9px;
    font-weight: 700;
    font-size: 0.84rem;
    font-family: 'Sarabun', sans-serif;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
    border: 1px solid transparent;
}
.btn-rh:hover { transform: translateY(-1px); }
.btn-rh-print { background: rgba(255,255,255,0.12); color: white; border-color: rgba(255,255,255,0.2); }
.btn-rh-print:hover { background: rgba(255,255,255,0.2); color: white; }
.btn-rh-refresh { background: #334155; color: #cbd5e1; }
.btn-rh-refresh:hover { background: #475569; color: white; }

/* FILTER TOOLBAR */
.filter-toolbar {
    background: white;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    padding: 16px 20px;
    margin-bottom: 22px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.02);
}
.filter-form { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; }
.f-box { display: flex; flex-direction: column; gap: 4px; flex: 1; min-width: 140px; }
.f-box-label { font-size: 0.76rem; font-weight: 700; color: #475569; display: flex; align-items: center; gap: 5px; }
.f-ctrl {
    padding: 8px 12px;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.85rem;
    font-family: 'Sarabun', sans-serif;
    background: #f8fafc;
    color: #1e293b;
    outline: none;
    transition: border-color 0.15s;
    width: 100%;
}
.f-ctrl:focus { border-color: #3b82f6; background: white; }

.preset-btns { display: flex; gap: 4px; flex-wrap: wrap; }
.btn-preset {
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 0.76rem;
    font-weight: 700;
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
    text-decoration: none;
    transition: all 0.15s;
}
.btn-preset:hover { background: #e2e8f0; color: #1e293b; }
.btn-preset.active { background: #3b82f6; color: white; border-color: #3b82f6; }

.btn-filter-submit {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
    border: none;
    padding: 9px 20px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.88rem;
    font-family: 'Sarabun', sans-serif;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.15s;
    box-shadow: 0 4px 10px rgba(59, 130, 246, 0.25);
    white-space: nowrap;
}
.btn-filter-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 14px rgba(59, 130, 246, 0.35); }

/* TOP KPI CARDS */
.kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 22px; }
.kpi-card {
    background: white;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    padding: 18px 20px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.02);
    position: relative;
    overflow: hidden;
}
.kpi-card-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
.kpi-num { font-size: 1.7rem; font-weight: 800; color: #1e293b; line-height: 1.1; }
.kpi-label { font-size: 0.8rem; color: #64748b; font-weight: 600; margin-top: 3px; }
.kpi-icon-wrap {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}
.kpi-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.76rem;
    color: #64748b;
    margin-top: 10px;
    padding-top: 8px;
    border-top: 1px solid #f1f5f9;
}
.kpi-badge {
    font-weight: 700;
    font-size: 0.72rem;
    padding: 2px 7px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 3px;
}

.prog-track { width: 100%; height: 6px; background: #f1f5f9; border-radius: 10px; overflow: hidden; margin: 8px 0; }
.prog-fill { height: 100%; border-radius: 10px; transition: width 0.4s ease; }

/* CHARTS & TABLES GRID */
.report-grid-2 { display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 22px; }
.report-grid-equal { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 22px; }

.rcard {
    background: white;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 6px rgba(0,0,0,0.02);
    overflow: hidden;
}
.rcard-hd {
    padding: 14px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
}
.rcard-title { font-size: 0.92rem; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px; }
.rcard-badge { font-size: 0.72rem; font-weight: 700; color: #64748b; background: white; border: 1px solid #cbd5e1; padding: 2px 8px; border-radius: 6px; }
.rcard-body { padding: 18px 20px; }

/* DATA TABLES */
.rtable-wrap { overflow-x: auto; }
.rtable { width: 100%; border-collapse: collapse; font-size: 0.83rem; text-align: left; }
.rtable th { background: #f8fafc; color: #475569; font-weight: 700; padding: 9px 12px; border-bottom: 1.5px solid #e2e8f0; white-space: nowrap; }
.rtable td { padding: 9px 12px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
.rtable tr:hover { background-color: #f8fafc; }

.st-badge { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; }
.st-pending { background: #fee2e2; color: #991b1b; }
.st-completed { background: #d1fae5; color: #065f46; }

/* RESPONSIVE */
@media (max-width: 1024px) {
    .kpi-grid { grid-template-columns: repeat(2, 1fr); }
    .report-grid-2, .report-grid-equal { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .dash-wrap { padding: 16px 12px 85px; }
    .kpi-grid { grid-template-columns: 1fr; }
    .report-header { padding: 18px; }
    .rh-left { flex-direction: column; align-items: flex-start; gap: 10px; }
}

@media print {
    body { background: white; color: black; }
    .page-wrapper { display: block; }
    .dash-wrap { padding: 0; }
    nav, header, .nav-u-header, .sidebar, .rh-actions, .filter-toolbar, .btn-preset { display: none !important; }
    .kpi-card, .rcard { border: 1px solid #ccc !important; box-shadow: none !important; page-break-inside: avoid; }
}
</style>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="global_smoothness.css">

<div class="page-wrapper">
    <?php include 'includes/nav_u_sidebar.php'; ?>
    <div class="dash-wrap">
        <div class="content-wrapper">

            <!-- 1. EXECUTIVE REPORT HEADER -->
            <div class="report-header">
                <div class="rh-left">
                    <div class="rh-icon"><i class="fa-solid fa-chart-line"></i></div>
                    <div>
                        <div class="rh-title">รายงานสรุปภาพรวมฝ่ายไร่ (Executive Report)</div>
                        <div class="rh-sub">
                            <span>ระบบสารสนเทศและการปฏิบัติงานภาคสนาม TIS SMART FIELD</span>
                            <span class="rh-tag">ปีการผลิต <?php echo htmlspecialchars($crop_year === 'all' ? 'ทั้งหมด' : $crop_year); ?></span>
                            <?php if(!empty($filter_zone)): ?>
                            <span class="rh-tag" style="background:rgba(16,185,129,0.2); color:#34d399; border-color:rgba(16,185,129,0.3);"><?php echo htmlspecialchars($filter_zone); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="rh-actions">
                    <a href="export_posts_excel.php?crop_year=<?php echo urlencode($crop_year); ?>" class="btn-rh" style="background:#10b981;border-color:#10b981;color:#fff;text-decoration:none;" title="ส่งออกรายงานปัญหาอ้อยเป็นไฟล์ Excel">
                        <i class="fa-solid fa-file-excel"></i> Excel ปัญหาอ้อย
                    </a>
                    <a href="export_harvester_excel.php?crop_year=<?php echo urlencode($crop_year); ?>&date=<?php echo date('Y-m-d'); ?>" class="btn-rh" style="background:#0284c7;border-color:#0284c7;color:#fff;text-decoration:none;" title="ส่งออกผลตรวจรถตัดเป็นไฟล์ Excel">
                        <i class="fa-solid fa-file-excel"></i> Excel รถตัด
                    </a>
                    <button type="button" class="btn-rh btn-rh-print" onclick="window.print()">
                        <i class="fa-solid fa-print"></i> พิมพ์รายงาน
                    </button>
                    <a href="report_all.php" class="btn-rh btn-rh-refresh" title="รีเซ็ต">
                        <i class="fa-solid fa-rotate"></i> รีเฟรช
                    </a>
                </div>
            </div>

            <!-- 2. FILTER TOOLBAR -->
            <div class="filter-toolbar">
                <form method="GET" action="report_all.php" class="filter-form">
                    <!-- ปีการผลิต -->
                    <div class="f-box" style="max-width: 140px;">
                        <label class="f-box-label"><i class="fa-solid fa-calendar"></i> ปีการผลิต</label>
                        <select name="crop_year" class="f-ctrl">
                            <option value="all" <?php echo $crop_year==='all'?'selected':''; ?>>ทุกปีผลิต</option>
                            <?php foreach($all_crops as $cy): ?>
                            <option value="<?php echo htmlspecialchars($cy); ?>" <?php echo $crop_year===$cy?'selected':''; ?>>ปี <?php echo htmlspecialchars($cy); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- หน่วยงาน -->
                    <div class="f-box" style="max-width: 180px;">
                        <label class="f-box-label"><i class="fa-solid fa-location-dot"></i> หน่วยงาน / โซน</label>
                        <select name="zone" class="f-ctrl">
                            <option value="">ทุกหน่วยงาน</option>
                            <?php foreach($all_zones as $zn): ?>
                            <option value="<?php echo htmlspecialchars($zn); ?>" <?php echo $filter_zone===$zn?'selected':''; ?>><?php echo htmlspecialchars($zn); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- วันที่เริ่มต้น - สิ้นสุด -->
                    <div class="f-box" style="max-width: 150px;">
                        <label class="f-box-label"><i class="fa-regular fa-calendar"></i> ตั้งแต่วันที่</label>
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" class="f-ctrl">
                    </div>
                    <div class="f-box" style="max-width: 150px;">
                        <label class="f-box-label"><i class="fa-regular fa-calendar"></i> ถึงวันที่</label>
                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" class="f-ctrl">
                    </div>

                    <!-- Preset Buttons -->
                    <div class="f-box" style="flex: 1.5; min-width: 250px;">
                        <label class="f-box-label"><i class="fa-solid fa-clock-rotate-left"></i> ช่วงเวลาด่วน</label>
                        <div class="preset-btns">
                            <a href="report_all.php?crop_year=<?php echo urlencode($crop_year); ?>&zone=<?php echo urlencode($filter_zone); ?>&preset=all" class="btn-preset <?php echo ($preset==='all'||$preset==='')?'active':''; ?>">ทั้งหมด</a>
                            <a href="report_all.php?crop_year=<?php echo urlencode($crop_year); ?>&zone=<?php echo urlencode($filter_zone); ?>&preset=today" class="btn-preset <?php echo $preset==='today'?'active':''; ?>">วันนี้</a>
                            <a href="report_all.php?crop_year=<?php echo urlencode($crop_year); ?>&zone=<?php echo urlencode($filter_zone); ?>&preset=7days" class="btn-preset <?php echo $preset==='7days'?'active':''; ?>">7 วันล่าสุด</a>
                            <a href="report_all.php?crop_year=<?php echo urlencode($crop_year); ?>&zone=<?php echo urlencode($filter_zone); ?>&preset=30days" class="btn-preset <?php echo $preset==='30days'?'active':''; ?>">30 วันล่าสุด</a>
                            <a href="report_all.php?crop_year=<?php echo urlencode($crop_year); ?>&zone=<?php echo urlencode($filter_zone); ?>&preset=month" class="btn-preset <?php echo $preset==='month'?'active':''; ?>">เดือนนี้</a>
                        </div>
                    </div>

                    <div>
                        <button type="submit" class="btn-filter-submit">
                            <i class="fa-solid fa-magnifying-glass"></i> ประมวลผล
                        </button>
                    </div>
                </form>
            </div>

            <!-- 3. TOP KEY PERFORMANCE INDICATORS (KPIs) -->
            <div class="kpi-grid">
                <!-- KPI 1: ปัญหาไร่ทั้งหมด -->
                <div class="kpi-card">
                    <div class="kpi-card-top">
                        <div>
                            <div class="kpi-num"><?php echo number_format($kpi_posts_total); ?></div>
                            <div class="kpi-label">รายงานปัญหาไร่ทั้งหมด</div>
                        </div>
                        <div class="kpi-icon-wrap" style="background:#fee2e2; color:#e11d48;">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                    </div>
                    <div class="prog-track">
                        <div class="prog-fill" style="width:<?php echo $post_resolve_rate; ?>%; background:#10b981;"></div>
                    </div>
                    <div class="kpi-footer">
                        <span>แก้ไขแล้ว <strong><?php echo $kpi_posts_completed; ?></strong> / รอ <strong><?php echo $kpi_posts_pending; ?></strong></span>
                        <span class="kpi-badge" style="background:#d1fae5; color:#065f46;">
                            <i class="fa-solid fa-check"></i> <?php echo $post_resolve_rate; ?>%
                        </span>
                    </div>
                </div>

                <!-- KPI 2: การตรวจเช็กรถตัด -->
                <div class="kpi-card">
                    <div class="kpi-card-top">
                        <div>
                            <div class="kpi-num" style="color:#0284c7;"><?php echo number_format($kpi_checks_total); ?></div>
                            <div class="kpi-label">การตรวจเช็กรถตัด (ครั้ง)</div>
                        </div>
                        <div class="kpi-icon-wrap" style="background:#e0f2fe; color:#0284c7;">
                            <i class="fa-solid fa-tractor"></i>
                        </div>
                    </div>
                    <div class="prog-track">
                        <div class="prog-fill" style="width:<?php echo $check_pass_rate; ?>%; background:#0284c7;"></div>
                    </div>
                    <div class="kpi-footer">
                        <span>ผ่าน <strong><?php echo $kpi_checks_pass; ?></strong> / จุดบกพร่อง <strong><?php echo $kpi_checks_fail; ?></strong></span>
                        <span class="kpi-badge" style="background:#e0f2fe; color:#0369a1;">
                            <?php echo $check_pass_rate; ?>% ผ่าน
                        </span>
                    </div>
                </div>

                <!-- KPI 3: รถตัดที่ตรวจเช็กแล้ว -->
                <div class="kpi-card">
                    <div class="kpi-card-top">
                        <div>
                            <div class="kpi-num" style="color:#059669;"><?php echo number_format($kpi_active_harvesters); ?> <span style="font-size:0.95rem; font-weight:600; color:#64748b;">/ <?php echo $stat_total_hv; ?> คัน</span></div>
                            <div class="kpi-label">รถตัดที่มีการบันทึกตรวจเช็ก</div>
                        </div>
                        <div class="kpi-icon-wrap" style="background:#d1fae5; color:#059669;">
                            <i class="fa-solid fa-tractor"></i>
                        </div>
                    </div>
                    <?php $hv_pct = $stat_total_hv > 0 ? round(($kpi_active_harvesters / $stat_total_hv) * 100) : 0; ?>
                    <div class="prog-track">
                        <div class="prog-fill" style="width:<?php echo $hv_pct; ?>%; background:#059669;"></div>
                    </div>
                    <div class="kpi-footer">
                        <span>ความครอบคลุมรถตัด</span>
                        <span class="kpi-badge" style="background:#d1fae5; color:#065f46;">
                            <?php echo $hv_pct; ?>% ในระบบ
                        </span>
                    </div>
                </div>

                <!-- KPI 4: กำลังพลพนักงานฝ่ายไร่ -->
                <div class="kpi-card">
                    <div class="kpi-card-top">
                        <div>
                            <div class="kpi-num" style="color:#d97706;"><?php echo number_format($stat_total_emp); ?> <span style="font-size:0.95rem; font-weight:600; color:#64748b;">คน</span></div>
                            <div class="kpi-label">พนักงานในระบบ (สถานะใช้งาน)</div>
                        </div>
                        <div class="kpi-icon-wrap" style="background:#fef3c7; color:#d97706;">
                            <i class="fa-solid fa-users"></i>
                        </div>
                    </div>
                    <div class="kpi-footer" style="margin-top: 18px;">
                        <span>ผู้ดูแลรถตัดที่ลงทะเบียน</span>
                        <span style="font-weight:700; color:#d97706;"><?php echo $stat_mgr_emp; ?> คน</span>
                    </div>
                </div>
            </div>

            <!-- 4. CHARTS ROW 1 (Trend + Problem Types) -->
            <div class="report-grid-2">
                <!-- Trend Chart -->
                <div class="rcard">
                    <div class="rcard-hd">
                        <div class="rcard-title"><i class="fa-solid fa-chart-area" style="color:#3b82f6;"></i> แนวโน้มการรายงานปัญหาและการตรวจรถตัด 6 เดือนล่าสุด</div>
                        <span class="rcard-badge">Timeline</span>
                    </div>
                    <div class="rcard-body">
                        <canvas id="trendChart" style="max-height: 270px; width:100%;"></canvas>
                    </div>
                </div>

                <!-- Problem Breakdown Chart -->
                <div class="rcard">
                    <div class="rcard-hd">
                        <div class="rcard-title"><i class="fa-solid fa-chart-pie" style="color:#e11d48;"></i> สัดส่วนประเภทปัญหาที่พบ</div>
                        <span class="rcard-badge">Top Categories</span>
                    </div>
                    <div class="rcard-body" style="display:flex; align-items:center; justify-content:center;">
                        <?php if(empty($problem_breakdown)): ?>
                        <div style="text-align:center; color:#94a3b8; padding:30px;"><i class="fa-solid fa-inbox" style="font-size:1.8rem; margin-bottom:6px; display:block;"></i>ไม่มีข้อมูลในช่วงเวลานี้</div>
                        <?php else: ?>
                        <canvas id="problemPieChart" style="max-height: 250px; max-width: 280px;"></canvas>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- 5. CHARTS & TABLES ROW 2 (Zone Performance + Machine Health) -->
            <div class="report-grid-equal">
                <!-- Performance by Zone -->
                <div class="rcard">
                    <div class="rcard-hd">
                        <div class="rcard-title"><i class="fa-solid fa-ranking-star" style="color:#10b981;"></i> ประสิทธิภาพการดำเนินงานแยกตามหน่วยงาน</div>
                        <span class="rcard-badge">Top Units</span>
                    </div>
                    <div class="rcard-body" style="padding:0;">
                        <?php if(empty($zone_performance)): ?>
                        <div style="text-align:center; color:#94a3b8; padding:30px;">ไม่มีข้อมูล</div>
                        <?php else: ?>
                        <div class="rtable-wrap">
                            <table class="rtable">
                                <thead>
                                    <tr>
                                        <th>หน่วยงาน</th>
                                        <th style="text-align:center;">รายงานปัญหา</th>
                                        <th style="text-align:center;">แก้ไขสำเร็จ</th>
                                        <th style="text-align:center;">ตรวจรถตัด</th>
                                        <th style="text-align:center;">อัตราสำเร็จ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($zone_performance as $zp): 
                                        $p_rate = $zp['post_count'] > 0 ? round(($zp['post_done'] / $zp['post_count']) * 100) : 0;
                                    ?>
                                    <tr>
                                        <td>
                                            <strong style="color:#1e293b;"><?php echo htmlspecialchars($zp['unit_name']); ?></strong>
                                        </td>
                                        <td style="text-align:center;">
                                            <span style="font-weight:700;"><?php echo number_format($zp['post_count']); ?></span>
                                        </td>
                                        <td style="text-align:center;">
                                            <span class="st-badge st-completed"><?php echo $zp['post_done']; ?></span>
                                        </td>
                                        <td style="text-align:center;">
                                            <span style="color:#0284c7; font-weight:700;"><?php echo number_format($zp['check_count']); ?></span>
                                        </td>
                                        <td style="text-align:center;">
                                            <span style="font-weight:700; color:#059669;"><?php echo $p_rate; ?>%</span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Top Failed Harvester Check Items -->
                <div class="rcard">
                    <div class="rcard-hd">
                        <div class="rcard-title"><i class="fa-solid fa-screwdriver-wrench" style="color:#f59e0b;"></i> จุดที่พบข้อบกพร่องบ่อยที่สุดในรถตัด</div>
                        <span class="rcard-badge">Maintenance Focus</span>
                    </div>
                    <div class="rcard-body">
                        <?php if(empty($failed_items)): ?>
                        <div style="text-align:center; color:#94a3b8; padding:30px;"><i class="fa-solid fa-circle-check" style="color:#10b981; font-size:2rem; margin-bottom:8px; display:block;"></i>ไม่พบรายการบกพร่อง รถตัดอยู่ในสภาพสมบูรณ์</div>
                        <?php else: ?>
                        <div style="display:flex; flex-direction:column; gap:12px;">
                            <?php 
                            $max_fail = max(array_column($failed_items, 'fail_count'));
                            foreach($failed_items as $fi): 
                                $pct = $max_fail > 0 ? round(($fi['fail_count'] / $max_fail) * 100) : 0;
                            ?>
                            <div>
                                <div style="display:flex; justify-content:space-between; font-size:0.83rem; font-weight:700; margin-bottom:4px;">
                                    <span><i class="fa-solid fa-wrench" style="color:#f59e0b; font-size:0.75rem; margin-right:4px;"></i><?php echo htmlspecialchars($fi['item_name_cut']); ?> <span style="font-size:0.72rem; color:#94a3b8; font-weight:400;">(<?php echo htmlspecialchars($fi['section_label']); ?>)</span></span>
                                    <span style="color:#e11d48;"><?php echo $fi['fail_count']; ?> ครั้ง</span>
                                </div>
                                <div class="prog-track">
                                    <div class="prog-fill" style="width:<?php echo $pct; ?>%; background:linear-gradient(90deg, #f59e0b, #e11d48);"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- 6. URGENT PENDING ISSUES TABLE -->
            <div class="rcard" style="margin-bottom: 30px;">
                <div class="rcard-hd">
                    <div class="rcard-title"><i class="fa-solid fa-clock-rotate-left" style="color:#e11d48;"></i> รายงานปัญหาที่อยู่ระหว่างรอดำเนินการ (Urgent Pending Issues)</div>
                    <a href="dashboard.php" style="font-size:0.8rem; font-weight:700; color:#3b82f6; text-decoration:none;">ดูทั้งหมดในระบบแดชบอร์ด &rarr;</a>
                </div>
                <div class="rcard-body" style="padding:0;">
                    <?php if(empty($pending_posts)): ?>
                    <div style="text-align:center; color:#94a3b8; padding:30px;"><i class="fa-solid fa-circle-check" style="color:#10b981; font-size:1.8rem; margin-bottom:6px; display:block;"></i>ไม่มีรายงานปัญหาค้าง ทุกรายการได้รับการดำเนินการเรียบร้อย</div>
                    <?php else: ?>
                    <div class="rtable-wrap">
                        <table class="rtable">
                            <thead>
                                <tr>
                                    <th>วันที่/เวลา</th>
                                    <th>ปัญหาที่พบ</th>
                                    <th>ทะเบียนรถ / เบอร์รถตัด</th>
                                    <th>หน่วยที่รับผิดชอบ</th>
                                    <th>ผู้รายงาน</th>
                                    <th>สถานะ</th>
                                    <th style="text-align:center;">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($pending_posts as $pp): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:700;"><?php echo date('d/m/Y', strtotime($pp['created_at'])); ?></div>
                                        <div style="font-size:0.72rem; color:#94a3b8;"><?php echo date('H:i', strtotime($pp['created_at'])); ?> น.</div>
                                    </td>
                                    <td>
                                        <span style="font-weight:700; color:#e11d48;">
                                            <i class="fa-solid fa-triangle-exclamation" style="font-size:0.75rem; margin-right:4px;"></i>
                                            <?php echo htmlspecialchars($pp['problem_detail'] ?: ($pp['problem_detail_2'] ?: ($pp['problem_detail_3'] ?: 'ปัญหาอ้อย'))); ?>
                                        </span>
                                        <?php if(!empty($pp['post_text'])): ?>
                                        <div style="font-size:0.75rem; color:#64748b; margin-top:2px; max-width:300px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                            <?php echo htmlspecialchars($pp['post_text']); ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span style="font-weight:700; color:#1e293b;"><?php echo htmlspecialchars($pp['truck_number'] ?: '-'); ?></span>
                                        <?php if(!empty($pp['harvester_number'])): ?>
                                        <div style="font-size:0.74rem; color:#64748b;"><i class="fa-solid fa-tractor" style="font-size:0.7rem;"></i> <?php echo htmlspecialchars($pp['harvester_number']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span style="background:#f1f5f9; padding:2px 6px; border-radius:4px; font-size:0.75rem; font-weight:700; color:#475569;"><?php echo htmlspecialchars($pp['target_unit'] ?: '-'); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($pp['emp_name'] ?: $pp['emp_id']); ?></td>
                                    <td>
                                        <span class="st-badge st-pending"><i class="fa-solid fa-clock"></i> รอดำเนินการ</span>
                                    </td>
                                    <td style="text-align:center;">
                                        <a href="post_detail.php?id=<?php echo $pp['post_id']; ?>" style="padding:4px 10px; background:#eff6ff; color:#1d4ed8; border-radius:6px; text-decoration:none; font-weight:700; font-size:0.75rem; display:inline-block;">
                                            ดูรายละเอียด
                                        </a>
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
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Chart: Monthly Activity Trends
    const ctxTrend = document.getElementById('trendChart');
    if (ctxTrend) {
        new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($trend_labels); ?>,
                datasets: [
                    {
                        label: 'รายงานปัญหาไร่',
                        data: <?php echo json_encode($trend_posts); ?>,
                        borderColor: '#e11d48',
                        backgroundColor: 'rgba(225, 29, 72, 0.08)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2.5,
                        pointBackgroundColor: '#e11d48',
                        pointRadius: 4
                    },
                    {
                        label: 'การตรวจรถตัด',
                        data: <?php echo json_encode($trend_checks); ?>,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.08)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2.5,
                        pointBackgroundColor: '#3b82f6',
                        pointRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { font: { family: 'Sarabun', size: 12, weight: '600' } } },
                    tooltip: { padding: 10, titleFont: { family: 'Sarabun' }, bodyFont: { family: 'Sarabun' } }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { family: 'Sarabun' } } },
                    x: { grid: { display: false }, ticks: { font: { family: 'Sarabun' } } }
                }
            }
        });
    }

    // 2. Chart: Problem Categories Breakdown
    const ctxPie = document.getElementById('problemPieChart');
    if (ctxPie) {
        const probNames = <?php echo json_encode(array_column($problem_breakdown, 'prob_name')); ?>;
        const probCounts = <?php echo json_encode(array_map('intval', array_column($problem_breakdown, 'cnt'))); ?>;
        
        new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: probNames,
                datasets: [{
                    data: probCounts,
                    backgroundColor: [
                        '#e11d48', '#f59e0b', '#3b82f6', '#10b981', '#8b5cf6', '#06b6d4', '#64748b'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, padding: 8, font: { family: 'Sarabun', size: 11, weight: '600' } } }
                },
                cutout: '65%'
            }
        });
    }
});
</script>
</body>
</html>
