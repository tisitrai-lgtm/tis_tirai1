<?php
/**
 * manage_users.php — จัดการรายชื่อพนักงานและสิทธิ์การดูแลรถตัด
 */
require_once 'config.php';
session_start();

if (!isset($_SESSION['emp_level']) || $_SESSION['emp_level'] != 'a') {
    die("คุณไม่มีสิทธิ์เข้าถึงหน้านี้ กรุณากลับหน้าหลัก");
}

// ----------------------------------------------------
// 1. สถิติภาพรวม (Statistics)
// ----------------------------------------------------
$stat_total = 0; $stat_managers = 0; $stat_active = 0; $stat_inactive = 0;
try {
    $stat_total     = (int)$conn->query("SELECT COUNT(*) FROM employee")->fetchColumn();
    $stat_managers  = (int)$conn->query("SELECT COUNT(*) FROM employee WHERE is_harvester_manager = 1")->fetchColumn();
    $stat_active    = (int)$conn->query("SELECT COUNT(*) FROM employee WHERE status = 1")->fetchColumn();
    $stat_inactive  = (int)$conn->query("SELECT COUNT(*) FROM employee WHERE status = 0")->fetchColumn();
} catch (Exception $e) {}

// รายการหน่วยทั้งหมดสำหรับ Filter Dropdown
$all_units = [];
try {
    $stmt_u = $conn->query("SELECT DISTINCT emp_unit FROM employee WHERE emp_unit IS NOT NULL AND emp_unit != '' ORDER BY emp_unit ASC");
    $all_units = $stmt_u->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {}

// ----------------------------------------------------
// 2. ตัวกรองและการค้นหา (Filters & Search)
// ----------------------------------------------------
$search_q      = trim($_GET['q'] ?? '');
$filter_unit   = trim($_GET['unit'] ?? '');
$filter_role   = trim($_GET['role'] ?? '');
$filter_status = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : '';

$limit  = 20; // จำกัดแสดงผล 20 คนต่อหน้าตามความต้องการ
$page   = max(1, (int)($_GET['page'] ?? 1));

$where_clauses = [];
$params        = [];

if ($search_q !== '') {
    $where_clauses[] = "(e.emp_id LIKE :sq OR e.emp_name LIKE :sq OR e.emp_unit LIKE :sq)";
    $params[':sq'] = "%$search_q%";
}
if ($filter_unit !== '') {
    $where_clauses[] = "e.emp_unit = :unit";
    $params[':unit'] = $filter_unit;
}
if ($filter_role === 'manager') {
    $where_clauses[] = "e.is_harvester_manager = 1";
} elseif ($filter_role === 'mechanic') {
    $where_clauses[] = "e.emp_level = 'm'";
} elseif ($filter_role === 'admin') {
    $where_clauses[] = "e.emp_level = 'a'";
} elseif ($filter_role === 'normal') {
    $where_clauses[] = "(e.is_harvester_manager = 0 OR e.is_harvester_manager IS NULL) AND e.emp_level = 'u'";
}
if ($filter_status !== '') {
    $where_clauses[] = "e.status = :status";
    $params[':status'] = (int)$filter_status;
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// นับจำนวนแถวทั้งหมดที่ตรงกับตัวกรอง
$count_stmt = $conn->prepare("SELECT COUNT(*) FROM employee e $where_sql");
$count_stmt->execute($params);
$total_filtered = (int)$count_stmt->fetchColumn();

$total_pages = max(1, (int)ceil($total_filtered / $limit));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $limit;

// ----------------------------------------------------
// 3. ดึงข้อมูลพนักงานตามหน้า (20 คน)
// ----------------------------------------------------
$sql = "SELECT e.*, 
        COUNT(eh.harvester_id) as harvester_count
        FROM employee e
        LEFT JOIN employee_harvester eh ON e.ID = eh.emp_id
        LEFT JOIN harvesters h ON eh.harvester_id = h.harvester_id AND h.is_active = 1
        $where_sql
        GROUP BY e.ID
        ORDER BY e.emp_unit ASC, e.emp_id ASC
        LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงรถตัดทั้งหมด (active) สำหรับ Modal
$harvesters = [];
try {
    $harvesters = $conn->query("SELECT harvester_id, harvester_number FROM harvesters WHERE is_active = 1 ORDER BY harvester_id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Flash Message / URL Errors
$flash_error   = $_GET['error'] ?? '';
$flash_success = $_GET['success'] ?? ($_GET['msg'] ?? '');
if (isset($_SESSION['flash_msg'])) {
    $flash_success = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']);
}
if (isset($_SESSION['flash_error'])) {
    $flash_error = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}
?>
<?php include 'includes/nav_u_header.php'; ?>
<style>
    * { box-sizing: border-box; }
    body { font-family: 'Sarabun', sans-serif; background-color: #f1f5f9; margin: 0; }

    .page-wrapper { display: flex; min-height: 100vh; width: 100%; align-items: flex-start; }
    .dash-wrap { flex: 1; padding: 24px 28px 60px; min-width: 0; overflow-x: hidden; width: 100%; }
    .content-wrapper { width: 100%; max-width: 100%; margin: 0 auto; }
    .page-container { width: 100%; max-width: 100%; margin: 0; padding: 0; }

    @media (max-width: 768px) {
        .dash-wrap { padding: 16px 12px 85px; }
    }

    /* PAGE HEADER */
    .page-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; margin-bottom: 22px; }
    .ph-title-box { display: flex; align-items: center; gap: 12px; }
    .ph-icon { width: 46px; height: 46px; background: linear-gradient(135deg, #e11d48, #be123c); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.3rem; box-shadow: 0 4px 12px rgba(225,29,72,0.25); flex-shrink: 0; }
    .ph-text h1 { font-size: 1.25rem; font-weight: 700; color: #1e293b; margin: 0 0 2px 0; line-height: 1.2; }
    .ph-text p { font-size: 0.8rem; color: #64748b; margin: 0; }
    
    .btn-add { background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 10px 18px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 0.88rem; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; white-space: nowrap; box-shadow: 0 4px 12px rgba(16,185,129,0.25); }
    .btn-add:hover { background: linear-gradient(135deg, #059669, #047857); transform: translateY(-1px); box-shadow: 0 6px 16px rgba(16,185,129,0.35); color: white; }

    /* STATS CARDS */
    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 20px; }
    .stat-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 14px 16px; display: flex; align-items: center; gap: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.03); transition: transform 0.15s; }
    .stat-card:hover { transform: translateY(-2px); }
    .stat-ico { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.2rem; }
    .stat-val { font-size: 1.45rem; font-weight: 800; color: #1e293b; line-height: 1.1; }
    .stat-lbl { font-size: 0.75rem; color: #64748b; font-weight: 600; margin-top: 2px; }

    /* ALERTS */
    .alert-box { padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 0.88rem; font-weight: 600; display: flex; align-items: center; gap: 10px; }
    .alert-error { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; }
    .alert-success { background: #d1fae5; border: 1px solid #a7f3d0; color: #065f46; }

    /* FILTER & SEARCH CARD */
    .filter-card { background: white; border-radius: 13px; border: 1px solid #e2e8f0; padding: 14px 18px; margin-bottom: 18px; box-shadow: 0 2px 6px rgba(0,0,0,0.03); }
    .filter-form { display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; }
    .f-group { display: flex; flex-direction: column; gap: 4px; flex: 1; min-width: 150px; }
    .f-group-search { flex: 1.8; min-width: 220px; }
    .f-lbl { font-size: 0.76rem; font-weight: 700; color: #475569; display: flex; align-items: center; gap: 5px; }
    .f-input { padding: 8px 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 0.85rem; font-family: 'Sarabun', sans-serif; background: #f8fafc; color: #1e293b; outline: none; transition: border-color 0.15s; width: 100%; }
    .f-input:focus { border-color: #e11d48; background: white; }
    select.f-input { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; padding-right: 28px; }
    
    .f-btn-group { display: flex; gap: 6px; align-items: center; }
    .btn-filter { padding: 8px 16px; background: #1e293b; color: white; border: none; border-radius: 8px; font-weight: 700; font-size: 0.85rem; font-family: 'Sarabun', sans-serif; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; height: 38px; transition: background 0.15s; }
    .btn-filter:hover { background: #0f172a; }
    .btn-reset { padding: 8px 12px; background: #f1f5f9; color: #64748b; border: 1.5px solid #e2e8f0; border-radius: 8px; font-weight: 700; font-size: 0.85rem; font-family: 'Sarabun', sans-serif; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; text-decoration: none; white-space: nowrap; height: 38px; transition: all 0.15s; }
    .btn-reset:hover { background: #e2e8f0; color: #1e293b; }

    /* TABLE WRAPPER */
    .table-card { background: white; border-radius: 14px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.03); margin-bottom: 20px; }
    .table-card-hd { background: #1e293b; padding: 12px 18px; display: flex; align-items: center; justify-content: space-between; border-bottom: 3px solid #e11d48; color: white; }
    .table-card-hd-l { display: flex; align-items: center; gap: 8px; font-weight: 700; font-size: 0.92rem; }
    .table-card-hd-l i { color: #e11d48; }
    .table-cnt-badge { background: rgba(255,255,255,0.12); color: #cbd5e1; font-size: 0.75rem; font-weight: 700; padding: 3px 10px; border-radius: 12px; }

    .tbl-responsive { overflow-x: auto; width: 100%; }
    table { width: 100%; border-collapse: collapse; min-width: 820px; text-align: left; }
    thead th { background: #f8fafc; color: #475569; padding: 12px 14px; font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1.5px solid #e2e8f0; white-space: nowrap; }
    tbody td { padding: 12px 14px; border-bottom: 1px solid #f1f5f9; font-size: 0.87rem; color: #334155; vertical-align: middle; }
    tbody tr:last-child td { border-bottom: none; }
    tbody tr:hover { background: #fafafa; }

    /* USER INFO CELL */
    .user-cell { display: flex; align-items: center; gap: 10px; }
    .user-avatar { width: 36px; height: 36px; border-radius: 10px; background: #e0f2fe; color: #0369a1; font-weight: 700; font-size: 0.8rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .user-avatar.is-admin { background: #fee2e2; color: #be123c; }
    .user-avatar.is-mech  { background: #fef3c7; color: #b45309; }
    .user-avatar.is-manager { background: #e0e7ff; color: #4338ca; }
    .user-name { font-weight: 700; color: #1e293b; font-size: 0.88rem; line-height: 1.2; }
    .user-sub { display: flex; align-items: center; gap: 6px; margin-top: 2px; }
    .user-id-tag { font-family: monospace; font-size: 0.75rem; color: #64748b; font-weight: 600; background: #f1f5f9; padding: 1px 6px; border-radius: 4px; }
    .tag-admin { background: #fee2e2; color: #991b1b; font-size: 0.68rem; font-weight: 700; padding: 1px 6px; border-radius: 4px; display: inline-flex; align-items: center; gap: 3px; }
    .tag-mech { background: #fef3c7; color: #b45309; font-size: 0.68rem; font-weight: 700; padding: 1px 6px; border-radius: 4px; display: inline-flex; align-items: center; gap: 3px; }

    /* UNIT BADGE */
    .badge-unit { background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 6px; font-size: 0.78rem; font-weight: 700; border: 1px solid #e2e8f0; display: inline-block; white-space: nowrap; }

    /* STATUS BADGE */
    .status-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 0.78rem; font-weight: 700; padding: 3px 9px; border-radius: 16px; white-space: nowrap; }
    .status-active { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
    .status-active .status-dot { width: 7px; height: 7px; background: #10b981; border-radius: 50%; display: inline-block; }
    .status-inactive { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
    .status-inactive .status-dot { width: 7px; height: 7px; background: #ef4444; border-radius: 50%; display: inline-block; }

    /* ROLE & HARVESTER CELL */
    .role-harvester-box { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .badge-role-user { font-size: 0.78rem; color: #94a3b8; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
    .badge-role-mgr { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; padding: 3px 8px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; white-space: nowrap; }
    
    .btn-hv-count { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; padding: 4px 9px; border-radius: 6px; font-size: 0.76rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; transition: all 0.15s; font-family: 'Sarabun', sans-serif; white-space: nowrap; }
    .btn-hv-count:hover { background: #bae6fd; transform: translateY(-1px); }
    .btn-hv-assign { background: #fff7ed; color: #c2410c; border: 1px dashed #fdba74; padding: 4px 9px; border-radius: 6px; font-size: 0.74rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; transition: all 0.15s; font-family: 'Sarabun', sans-serif; white-space: nowrap; }
    .btn-hv-assign:hover { background: #ffedd5; border-style: solid; }

    /* ACTION BUTTONS */
    .action-toolbar { display: flex; align-items: center; gap: 5px; }
    .btn-act { width: 32px; height: 32px; border-radius: 8px; border: 1px solid transparent; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; cursor: pointer; transition: all 0.15s; font-size: 0.82rem; }
    .btn-act-view { background: #f5f3ff; color: #7c3aed; border-color: #ddd6fe; }
    .btn-act-view:hover { background: #ede9fe; color: #6d28d9; transform: translateY(-1px); }
    .btn-act-edit { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
    .btn-act-edit:hover { background: #dbeafe; color: #1e40af; transform: translateY(-1px); }
    .btn-act-del  { background: #fff1f2; color: #e11d48; border-color: #fecaca; }
    .btn-act-del:hover  { background: #ffe4e6; color: #be123c; transform: translateY(-1px); }

    /* PAGINATION */
    .pagination-bar { display: flex; justify-content: space-between; align-items: center; padding: 14px 18px; border-top: 1px solid #f1f5f9; background: #fafafa; flex-wrap: wrap; gap: 10px; }
    .pg-info { font-size: 0.82rem; color: #64748b; font-weight: 600; }
    .pg-links { display: flex; gap: 4px; align-items: center; }
    .pg-link { padding: 6px 11px; border: 1px solid #e2e8f0; background: white; color: #334155; border-radius: 6px; text-decoration: none; font-size: 0.82rem; font-weight: 700; transition: all 0.15s; }
    .pg-link:hover { background: #f1f5f9; border-color: #cbd5e1; }
    .pg-link.active { background: #1e293b; color: white; border-color: #1e293b; }
    .pg-link.disabled { opacity: 0.4; pointer-events: none; }

    /* EMPTY STATE */
    .empty-box { text-align: center; padding: 50px 20px; color: #94a3b8; }
    .empty-box i { font-size: 2.5rem; display: block; margin-bottom: 10px; opacity: 0.6; }

    /* MOBILE CARDS VIEW */
    .mobile-cards { display: none; }
    .emp-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 14px; margin-bottom: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.03); }
    .emp-card-hd { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 10px; }
    .emp-card-body { display: flex; flex-direction: column; gap: 8px; font-size: 0.84rem; color: #475569; padding: 8px 0; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; margin-bottom: 10px; }
    .emp-card-row { display: flex; justify-content: space-between; align-items: center; }
    .emp-card-actions { display: flex; gap: 6px; justify-content: flex-end; }

    /* RESPONSIVE BREAKPOINTS */
    @media (max-width: 900px) {
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 768px) {
        .table-card { display: none; }
        .mobile-cards { display: block; }
        .page-container { padding: 0 12px 60px; }
        .f-group { min-width: 100%; }
        .f-btn-group { width: 100%; }
        .btn-filter, .btn-reset { flex: 1; justify-content: center; }
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
    .dark-mode .ph-text h1 {
        color: #f8fafc !important;
    }
    .dark-mode .ph-text p {
        color: #94a3b8 !important;
    }
    .dark-mode .stat-card {
        background: #131b2e !important;
        border-color: #1e293b !important;
    }
    .dark-mode .stat-val {
        color: #f8fafc !important;
    }
    .dark-mode .stat-lbl {
        color: #94a3b8 !important;
    }
    .dark-mode .filter-card {
        background: #131b2e !important;
        border-color: #1e293b !important;
    }
    .dark-mode .f-lbl {
        color: #cbd5e1 !important;
    }
    .dark-mode .f-input {
        background: #0b1120 !important;
        border-color: #1e293b !important;
        color: #f8fafc !important;
    }
    .dark-mode .f-input:focus {
        border-color: #e11d48 !important;
        background: #0f172a !important;
    }
    .dark-mode .btn-reset {
        background: #1e293b !important;
        border-color: #334155 !important;
        color: #cbd5e1 !important;
    }
    .dark-mode .btn-reset:hover {
        background: #334155 !important;
        color: #f8fafc !important;
    }
    .dark-mode .table-card {
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
    .dark-mode tbody tr:hover {
        background: #1e293b !important;
    }
    .dark-mode .user-name {
        color: #f8fafc !important;
    }
    .dark-mode .user-id-tag {
        background: #0b1120 !important;
        color: #94a3b8 !important;
    }
    .dark-mode .badge-unit {
        background: #0b1120 !important;
        border-color: #1e293b !important;
        color: #cbd5e1 !important;
    }
    .dark-mode .badge-role-user {
        color: #94a3b8 !important;
    }
    .dark-mode .btn-hv-count {
        background: rgba(2, 132, 199, 0.2) !important;
        border-color: #0284c7 !important;
        color: #38bdf8 !important;
    }
    .dark-mode .btn-hv-assign {
        background: rgba(245, 158, 11, 0.15) !important;
        border-color: #f59e0b !important;
        color: #fbbf24 !important;
    }
    .dark-mode .btn-act-view {
        background: rgba(124, 58, 237, 0.2) !important;
        border-color: #7c3aed !important;
        color: #c4b5fd !important;
    }
    .dark-mode .btn-act-edit {
        background: rgba(37, 99, 235, 0.2) !important;
        border-color: #2563eb !important;
        color: #93c5fd !important;
    }
    .dark-mode .btn-act-del {
        background: rgba(225, 29, 72, 0.2) !important;
        border-color: #e11d48 !important;
        color: #fca5a5 !important;
    }
    .dark-mode .pagination-bar {
        background: #0b1120 !important;
        border-color: #1e293b !important;
    }
    .dark-mode .pg-info {
        color: #94a3b8 !important;
    }
    .dark-mode .pg-link {
        background: #131b2e !important;
        border-color: #1e293b !important;
        color: #cbd5e1 !important;
    }
    .dark-mode .pg-link.active {
        background: #e11d48 !important;
        border-color: #e11d48 !important;
        color: #fff !important;
    }
    .dark-mode .emp-card {
        background: #131b2e !important;
        border-color: #1e293b !important;
    }
    .dark-mode .emp-card-body {
        border-color: #1e293b !important;
        color: #cbd5e1 !important;
    }
</style>

<div class="page-wrapper">
<?php include 'includes/nav_u_sidebar.php'; ?>
<div class="dash-wrap">
<div class="content-wrapper">

<div class="page-container">

    <!-- Header -->
    <div class="page-header">
        <div class="ph-title-box">
            <div class="ph-icon"><i class="fa-solid fa-users-gear"></i></div>
            <div class="ph-text">
                <h1>จัดการรายชื่อพนักงาน</h1>
                <p>จัดการบัญชีผู้ใช้งาน สิทธิ์การเข้าถึง และการผูกรถตัดอ้อยที่รับผิดชอบ</p>
            </div>
        </div>
        <a href="add_user.php" class="btn-add">
            <i class="fa-solid fa-user-plus"></i> เพิ่มพนักงานใหม่
        </a>
    </div>

    <!-- Alert Messages -->
    <?php if(!empty($flash_error)): ?>
    <div class="alert-box alert-error">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?php echo htmlspecialchars($flash_error); ?></span>
    </div>
    <?php endif; ?>
    <?php if(!empty($flash_success)): ?>
    <div class="alert-box alert-success">
        <i class="fa-solid fa-circle-check"></i>
        <span><?php echo htmlspecialchars($flash_success); ?></span>
    </div>
    <?php endif; ?>

    <!-- Stat Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-ico" style="background:#f1f5f9; color:#1e293b;"><i class="fa-solid fa-users"></i></div>
            <div>
                <div class="stat-val"><?php echo number_format($stat_total); ?></div>
                <div class="stat-lbl">พนักงานทั้งหมด</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-ico" style="background:#fef3c7; color:#d97706;"><i class="fa-solid fa-tractor"></i></div>
            <div>
                <div class="stat-val" style="color:#d97706;"><?php echo number_format($stat_managers); ?></div>
                <div class="stat-lbl">ผู้ดูแลรถตัด</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-ico" style="background:#d1fae5; color:#059669;"><i class="fa-solid fa-user-check"></i></div>
            <div>
                <div class="stat-val" style="color:#059669;"><?php echo number_format($stat_active); ?></div>
                <div class="stat-lbl">สถานะใช้งาน</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-ico" style="background:#fee2e2; color:#e11d48;"><i class="fa-solid fa-user-xmark"></i></div>
            <div>
                <div class="stat-val" style="color:#e11d48;"><?php echo number_format($stat_inactive); ?></div>
                <div class="stat-lbl">ปิดใช้งาน</div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="filter-card">
        <form method="GET" action="manage_users.php" class="filter-form">
            <div class="f-group f-group-search">
                <span class="f-lbl"><i class="fa-solid fa-magnifying-glass"></i> ค้นหาพนักงาน</span>
                <input type="text" name="q" id="search-input" class="f-input" placeholder="พิมพ์รหัส ชื่อ-สกุล หรือหน่วย..." value="<?php echo htmlspecialchars($search_q); ?>" oninput="filterTableInstant()">
            </div>

            <div class="f-group">
                <span class="f-lbl"><i class="fa-solid fa-location-dot"></i> หน่วยงาน</span>
                <select name="unit" class="f-input">
                    <option value="">ทุกหน่วยงาน</option>
                    <?php foreach($all_units as $u): ?>
                    <option value="<?php echo htmlspecialchars($u); ?>" <?php echo $filter_unit === $u ? 'selected' : ''; ?>><?php echo htmlspecialchars($u); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="f-group">
                <span class="f-lbl"><i class="fa-solid fa-id-badge"></i> บทบาท</span>
                <select name="role" class="f-input">
                    <option value="">ทุกบทบาท</option>
                    <option value="manager"  <?php echo $filter_role === 'manager'  ? 'selected' : ''; ?>>ผู้ดูแลรถตัด</option>
                    <option value="mechanic" <?php echo $filter_role === 'mechanic' ? 'selected' : ''; ?>>นายช่าง (Mechanic)</option>
                    <option value="admin"    <?php echo $filter_role === 'admin'    ? 'selected' : ''; ?>>ผู้ดูแลระบบ (Admin)</option>
                    <option value="normal"   <?php echo $filter_role === 'normal'   ? 'selected' : ''; ?>>พนักงานทั่วไป</option>
                </select>
            </div>

            <div class="f-group">
                <span class="f-lbl"><i class="fa-solid fa-toggle-on"></i> สถานะ</span>
                <select name="status" class="f-input">
                    <option value="">ทุกสถานะ</option>
                    <option value="1" <?php echo $filter_status === '1' ? 'selected' : ''; ?>>ใช้งาน</option>
                    <option value="0" <?php echo $filter_status === '0' ? 'selected' : ''; ?>>ปิดใช้งาน</option>
                </select>
            </div>

            <div class="f-btn-group">
                <button type="submit" class="btn-filter"><i class="fa-solid fa-filter"></i> ค้นหา</button>
                <?php if($search_q !== '' || $filter_unit !== '' || $filter_role !== '' || $filter_status !== ''): ?>
                <a href="manage_users.php" class="btn-reset" title="ล้างตัวกรอง"><i class="fa-solid fa-rotate-left"></i> รีเซ็ต</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Desktop Table View -->
    <div class="table-card" id="desktop-table">
        <div class="table-card-hd">
            <div class="table-card-hd-l">
                <i class="fa-solid fa-table-list"></i>
                <span>รายชื่อพนักงาน</span>
            </div>
            <span class="table-cnt-badge">พบ <?php echo number_format($total_filtered); ?> คน</span>
        </div>

        <?php if(empty($employees)): ?>
        <div class="empty-box">
            <i class="fa-solid fa-user-slash"></i>
            <div>ไม่พบรายชื่อพนักงานที่ตรงกับเงื่อนไข</div>
        </div>
        <?php else: ?>
        <div class="tbl-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width:50px; text-align:center;">#</th>
                        <th>ข้อมูลพนักงาน</th>
                        <th>หน่วยงาน</th>
                        <th>สถานะ</th>
                        <th>บทบาท & รถตัดที่ดูแล</th>
                        <th style="width:130px; text-align:center;">จัดการ</th>
                    </tr>
                </thead>
                <tbody id="emp-tbody">
                <?php foreach($employees as $i => $emp): 
                    $is_admin   = ($emp['emp_level'] === 'a');
                    $is_mech    = ($emp['emp_level'] === 'm');
                    $is_mgr     = ((int)$emp['is_harvester_manager'] === 1);
                    $hv_cnt     = (int)($emp['harvester_count'] ?? 0);
                    $initials   = mb_substr(trim($emp['emp_name']), 0, 2, 'UTF-8');
                    $avatar_cls = $is_admin ? 'is-admin' : ($is_mech ? 'is-mech' : ($is_mgr ? 'is-manager' : ''));
                ?>
                <tr class="emp-row"
                    data-name="<?php echo htmlspecialchars(strtolower($emp['emp_name'])); ?>"
                    data-id="<?php echo htmlspecialchars(strtolower($emp['emp_id'])); ?>"
                    data-unit="<?php echo htmlspecialchars(strtolower($emp['emp_unit'])); ?>">
                    
                    <td style="text-align:center; color:#94a3b8; font-size:0.8rem; font-weight:700;">
                        <?php echo $offset + $i + 1; ?>
                    </td>

                    <td>
                        <div class="user-cell">
                            <div class="user-avatar <?php echo $avatar_cls; ?>"><?php echo htmlspecialchars($initials); ?></div>
                            <div>
                                <div class="user-name"><?php echo htmlspecialchars($emp['emp_name']); ?></div>
                                <div class="user-sub">
                                    <span class="user-id-tag"><?php echo htmlspecialchars($emp['emp_id']); ?></span>
                                    <?php if($is_admin): ?>
                                    <span class="tag-admin"><i class="fa-solid fa-shield"></i> Admin</span>
                                    <?php elseif($is_mech): ?>
                                    <span class="tag-mech"><i class="fa-solid fa-wrench"></i> นายช่าง</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </td>

                    <td>
                        <span class="badge-unit"><?php echo htmlspecialchars($emp['emp_unit'] ?: '-'); ?></span>
                    </td>

                    <td>
                        <?php if((int)$emp['status'] === 1): ?>
                            <span class="status-badge status-active"><span class="status-dot"></span> ใช้งาน</span>
                        <?php else: ?>
                            <span class="status-badge status-inactive"><span class="status-dot"></span> ปิดใช้งาน</span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <div class="role-harvester-box">
                            <?php if($is_mgr): ?>
                                <span class="badge-role-mgr"><i class="fa-solid fa-truck-pickup"></i> ผู้ดูแล</span>
                                <?php if($hv_cnt > 0): ?>
                                    <a href="edit_user.php?id=<?php echo $emp['ID']; ?>" class="btn-hv-count"
                                       title="คลิกเพื่อแก้ไขรถตัดที่ดูแล">
                                        <i class="fa-solid fa-tractor"></i> ดูแล <strong><?php echo $hv_cnt; ?></strong> คัน
                                        <i class="fa-solid fa-pen" style="font-size:0.65rem; opacity:0.7;"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="edit_user.php?id=<?php echo $emp['ID']; ?>" class="btn-hv-assign"
                                       title="คลิกเพื่อผูกรถตัดให้พนักงาน">
                                        <i class="fa-solid fa-plus"></i> ผูกรถตัด
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge-role-user"><i class="fa-solid fa-user"></i> พนักงานทั่วไป</span>
                            <?php endif; ?>
                        </div>
                    </td>

                    <td>
                        <div class="action-toolbar" style="justify-content:center;">
                            <a href="view_user.php?id=<?php echo $emp['ID']; ?>" class="btn-act btn-act-view" title="ดูข้อมูล">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="edit_user.php?id=<?php echo $emp['ID']; ?>" class="btn-act btn-act-edit" title="แก้ไข">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <a href="delete_user.php?id=<?php echo $emp['ID']; ?>" class="btn-act btn-act-del" title="ลบ" onclick="return confirm('ยืนยันต้องการลบพนักงาน <?php echo addslashes($emp['emp_name']); ?> ?');">
                                <i class="fa-solid fa-trash-can"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Pagination Controls (20 คนต่อหน้า) -->
        <?php if ($total_pages > 1): 
            $build_pg_url = function($p) use ($search_q, $filter_unit, $filter_role, $filter_status) {
                $q = ['page' => $p];
                if ($search_q !== '')      $q['q'] = $search_q;
                if ($filter_unit !== '')   $q['unit'] = $filter_unit;
                if ($filter_role !== '')   $q['role'] = $filter_role;
                if ($filter_status !== '') $q['status'] = $filter_status;
                return '?' . http_build_query($q);
            };
        ?>
        <div class="pagination-bar">
            <div class="pg-info">
                แสดง <strong><?php echo $offset + 1; ?></strong> - <strong><?php echo min($offset + $limit, $total_filtered); ?></strong> จากทั้งหมด <strong><?php echo number_format($total_filtered); ?></strong> คน (หน้า <?php echo $page; ?>/<?php echo $total_pages; ?>)
            </div>
            <div class="pg-links">
                <a href="<?php echo $build_pg_url($page - 1); ?>" class="pg-link <?php echo ($page <= 1) ? 'disabled' : ''; ?>"><i class="fa-solid fa-chevron-left"></i> ก่อนหน้า</a>
                
                <?php 
                $start_p = max(1, $page - 2);
                $end_p   = min($total_pages, $page + 2);
                for ($p = $start_p; $p <= $end_p; $p++): 
                ?>
                    <a href="<?php echo $build_pg_url($p); ?>" class="pg-link <?php echo ($page == $p) ? 'active' : ''; ?>"><?php echo $p; ?></a>
                <?php endfor; ?>

                <a href="<?php echo $build_pg_url($page + 1); ?>" class="pg-link <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">ถัดไป <i class="fa-solid fa-chevron-right"></i></a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Mobile Cards View -->
    <div class="mobile-cards" id="mobile-cards">
        <?php foreach($employees as $emp): 
            $is_admin   = ($emp['emp_level'] === 'a');
            $is_mech    = ($emp['emp_level'] === 'm');
            $is_mgr     = ((int)$emp['is_harvester_manager'] === 1);
            $hv_cnt     = (int)($emp['harvester_count'] ?? 0);
            $initials   = mb_substr(trim($emp['emp_name']), 0, 2, 'UTF-8');
            $avatar_cls = $is_admin ? 'is-admin' : ($is_mech ? 'is-mech' : ($is_mgr ? 'is-manager' : ''));
        ?>
        <div class="emp-card mobile-row"
             data-name="<?php echo htmlspecialchars(strtolower($emp['emp_name'])); ?>"
             data-id="<?php echo htmlspecialchars(strtolower($emp['emp_id'])); ?>"
             data-unit="<?php echo htmlspecialchars(strtolower($emp['emp_unit'])); ?>">
            <div class="emp-card-hd">
                <div class="user-cell">
                    <div class="user-avatar <?php echo $avatar_cls; ?>"><?php echo htmlspecialchars($initials); ?></div>
                    <div>
                        <div class="user-name"><?php echo htmlspecialchars($emp['emp_name']); ?></div>
                        <div class="user-sub">
                            <span class="user-id-tag"><?php echo htmlspecialchars($emp['emp_id']); ?></span>
                            <?php if($is_admin): ?>
                                <span class="tag-admin"><i class="fa-solid fa-shield"></i> Admin</span>
                            <?php elseif($is_mech): ?>
                                <span class="tag-mech"><i class="fa-solid fa-wrench"></i> นายช่าง</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div>
                    <?php if((int)$emp['status'] === 1): ?>
                        <span class="status-badge status-active"><span class="status-dot"></span> ใช้งาน</span>
                    <?php else: ?>
                        <span class="status-badge status-inactive"><span class="status-dot"></span> ปิด</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="emp-card-body">
                <div class="emp-card-row">
                    <span style="color:#94a3b8; font-size:0.78rem;">หน่วยงาน:</span>
                    <span class="badge-unit"><?php echo htmlspecialchars($emp['emp_unit'] ?: '-'); ?></span>
                </div>
                <div class="emp-card-row">
                    <span style="color:#94a3b8; font-size:0.78rem;">บทบาท & รถตัด:</span>
                    <div>
                        <?php if($is_mgr): ?>
                            <a href="edit_user.php?id=<?php echo $emp['ID']; ?>" class="btn-hv-count" style="text-decoration:none;" title="แก้ไขรถตัดที่ดูแล">
                                <i class="fa-solid fa-tractor"></i> <?php echo $hv_cnt > 0 ? "ดูแล $hv_cnt คัน" : "+ ผูกรถตัด"; ?>
                                <i class="fa-solid fa-pen" style="font-size:0.65rem; opacity:0.7;"></i>
                            </a>
                        <?php else: ?>
                            <span class="badge-role-user">พนักงานทั่วไป</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="emp-card-actions">
                <a href="view_user.php?id=<?php echo $emp['ID']; ?>" class="btn-act btn-act-view" title="ดูข้อมูล"><i class="fa-solid fa-eye"></i></a>
                <a href="edit_user.php?id=<?php echo $emp['ID']; ?>" class="btn-act btn-act-edit" title="แก้ไข"><i class="fa-solid fa-pen-to-square"></i></a>
                <a href="delete_user.php?id=<?php echo $emp['ID']; ?>" class="btn-act btn-act-del" title="ลบ" onclick="return confirm('ยืนยันต้องการลบพนักงาน <?php echo addslashes($emp['emp_name']); ?> ?');"><i class="fa-solid fa-trash-can"></i></a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

</div>
</div>
</div>

<script>
function filterTableInstant() {
    const q = document.getElementById('search-input').value.toLowerCase().trim();
    document.querySelectorAll('.emp-row, .mobile-row').forEach(r => {
        const match = r.dataset.name.includes(q) || r.dataset.id.includes(q) || r.dataset.unit.includes(q);
        r.style.display = match ? '' : 'none';
    });
}

<?php if(!empty($flash_success)): ?>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof showToast === 'function') {
        showToast(<?php echo json_encode($flash_success); ?>, 'success');
    }
});
<?php endif; ?>

<?php if(!empty($flash_error)): ?>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof showToast === 'function') {
        showToast(<?php echo json_encode($flash_error); ?>, 'error');
    }
});
<?php endif; ?>
</script>
</body>
</html>