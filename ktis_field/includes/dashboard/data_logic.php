<?php
/**
 * includes/dashboard/data_logic.php — Data Logic & Queries for Dashboard
 */
date_default_timezone_set('Asia/Bangkok');
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../functions.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['emp_id']) || !in_array($_SESSION['emp_level'] ?? 'u', ['a', 'm'])) {
    header("location: login.php");
    exit;
}

$thai_months = ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];

$crop_year       = $_SESSION['crop_year'] ?? '69/70';
$filter_date     = $_GET['date'] ?? date('Y-m-d');
$filter_date_end = $filter_date;
$search_q        = trim($_GET['q'] ?? '');
$filter_status   = $_GET['status'] ?? '';
$page            = max(1, (int)($_GET['page'] ?? 1));
$per_page        = 20;

// ดึงผู้ดูแลรถตัดแต่ละคัน
$harvester_managers = [];
try {
    $stmt_mgr = $conn->query(
        "SELECT eh.harvester_id, e.ID, e.emp_id, e.emp_name, e.emp_unit
         FROM employee_harvester eh
         JOIN employee e ON CAST(e.ID AS CHAR) = eh.emp_id
         WHERE e.status = 1"
    );
    foreach ($stmt_mgr->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $harvester_managers[(int)$row['harvester_id']][] = $row;
    }
} catch (Exception $e) {}

// ดึงข้อมูล Zones / Units
$all_units = [];
try {
    $zu = $conn->query("SELECT CASE WHEN zone_id='000' THEN zone_name ELSE CONCAT(zone_id,' ',zone_name) END AS unit_name FROM zones ORDER BY zone_id ASC");
    $all_units = $zu->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {}

// ดึงรายการรถตัดทั้งหมดในระบบ
$harvester_list = [];
try {
    $stmt_h = $conn->query("SELECT harvester_id, harvester_number, harvester_name, is_active FROM harvesters WHERE is_active=1 ORDER BY harvester_id ASC");
    $harvester_list = $stmt_h->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

if (empty($harvester_list)) {
    for ($i = 1; $i <= 50; $i++) {
        $harvester_list[] = [
            'harvester_id' => $i,
            'harvester_number' => "รถตัดเบอร์ $i",
            'harvester_name' => null,
            'is_active' => 1
        ];
    }
}

// ดึงข้อมูลการตรวจเช็คในช่วงวันที่เลือก
$check_sessions_map = [];
try {
    $sql_cs = "SELECT cs.*, e.emp_name, e.emp_unit, e.emp_level,
                    COUNT(cr.result_id) AS total_checked_items,
                    SUM(CASE WHEN cr.pass = 1 THEN 1 ELSE 0 END) AS pass_items_count,
                    SUM(CASE WHEN cr.pass = 0 THEN 1 ELSE 0 END) AS fail_items_count
               FROM check_sessions cs
               JOIN employee e ON cs.emp_id = e.emp_id
               LEFT JOIN check_results cr ON cs.session_id = cr.session_id
               WHERE DATE(cs.checked_at) BETWEEN :dt_start AND :dt_end AND cs.crop_year = :cy
               GROUP BY cs.session_id
               ORDER BY cs.checked_at DESC";
    $stmt_cs = $conn->prepare($sql_cs);
    $stmt_cs->execute([':dt_start' => $filter_date, ':dt_end' => $filter_date_end, ':cy' => $crop_year]);
    $raw_sessions = $stmt_cs->fetchAll(PDO::FETCH_ASSOC);

    foreach ($raw_sessions as $cs) {
        $hn = trim($cs['harvester_number']);
        $hn_full = !str_contains($hn, 'รถตัดเบอร์') ? "รถตัดเบอร์ " . $hn : $hn;
        if (!isset($check_sessions_map[$hn_full])) $check_sessions_map[$hn_full] = $cs;
        if (!isset($check_sessions_map[$hn]))      $check_sessions_map[$hn]      = $cs;
    }
} catch (Exception $e) {}

// ดึงรายการผลการตรวจละเอียด สำหรับ Modal
$session_results_detail = [];
try {
    $sql_crd = "SELECT cr.*, ci.item_name_cut, ci.section_label, ci.section_no
                FROM check_results cr
                JOIN check_items_cut ci ON cr.item_id = ci.item_id
                WHERE cr.session_id IN (
                    SELECT session_id FROM check_sessions WHERE DATE(checked_at) = :dt AND crop_year = :cy
                )
                ORDER BY ci.section_no ASC, ci.item_id ASC";
    $stmt_crd = $conn->prepare($sql_crd);
    $stmt_crd->execute([':dt' => $filter_date, ':cy' => $crop_year]);
    $raw_details = $stmt_crd->fetchAll(PDO::FETCH_ASSOC);

    foreach ($raw_details as $rd) {
        $session_results_detail[$rd['session_id']][] = $rd;
    }
} catch (Exception $e) {}

// ดึงข้อมูลโพสต์ในช่วงวันที่เลือก
$posts_map = [];
try {
    $sql_posts = "SELECT p.*, e.emp_name, e.emp_unit
                  FROM posts p
                  JOIN employee e ON p.emp_id = e.emp_id
                  WHERE DATE(p.created_at) BETWEEN :dt_start AND :dt_end AND p.crop_year = :cy
                  ORDER BY p.created_at DESC";
    $stmt_p = $conn->prepare($sql_posts);
    $stmt_p->execute([':dt_start' => $filter_date, ':dt_end' => $filter_date_end, ':cy' => $crop_year]);
    $raw_posts = $stmt_p->fetchAll(PDO::FETCH_ASSOC);

    foreach ($raw_posts as $pt) {
        $hn = trim($pt['harvester_number'] ?? '');
        if ($hn !== '') {
            $posts_map[$hn][] = $pt;
            if (!str_contains($hn, 'รถตัดเบอร์')) { $posts_map["รถตัดเบอร์ $hn"][] = $pt; }
        }
    }
} catch (Exception $e) {}

// Smart Alert: รถที่มีโพสต์ติดต่อ 3 วันขึ้นไป
$alert_consecutive_harvesters = [];
$ref_date    = $filter_date ?? date('Y-m-d');
$day_minus_1 = date('Y-m-d', strtotime($ref_date . ' -1 day'));
$day_minus_2 = date('Y-m-d', strtotime($ref_date . ' -2 days'));

$admin_visited_today = [];
try {
    $stmt_av = $conn->prepare("SELECT DISTINCT harvester_number FROM admin_field_visits WHERE visit_date = :dt");
    $stmt_av->execute([':dt' => $ref_date]);
    foreach ($stmt_av->fetchAll(PDO::FETCH_COLUMN) as $hvn) { 
        $hvn_clean = trim($hvn);
        $admin_visited_today[$hvn_clean] = true;
        $admin_visited_today[str_replace('รถตัดเบอร์ ', '', $hvn_clean)] = true;
        $admin_visited_today[!str_contains($hvn_clean, 'รถตัดเบอร์') ? "รถตัดเบอร์ $hvn_clean" : $hvn_clean] = true;
    }
} catch (Exception $e) {}

try {
    $sql_3d = "SELECT harvester_number, COUNT(DISTINCT DATE(created_at)) AS post_days_cnt
               FROM posts
               WHERE DATE(created_at) IN (:d0,:d1,:d2)
                 AND harvester_number IS NOT NULL AND harvester_number != ''
                 AND crop_year = :cy
               GROUP BY harvester_number
               HAVING post_days_cnt >= 3";
    $stmt_3d = $conn->prepare($sql_3d);
    $stmt_3d->execute([':d0'=>$ref_date, ':d1'=>$day_minus_1, ':d2'=>$day_minus_2, ':cy'=>$crop_year]);
    foreach ($stmt_3d->fetchAll(PDO::FETCH_ASSOC) as $ar) {
        $hn = trim($ar['harvester_number']);
        $hn_full  = !str_contains($hn,'รถตัดเบอร์') ? "รถตัดเบอร์ $hn" : $hn;
        $hn_short = str_replace('รถตัดเบอร์ ', '', $hn);
        if (!isset($admin_visited_today[$hn]) && !isset($admin_visited_today[$hn_full]) && !isset($admin_visited_today[$hn_short])) {
            $alert_consecutive_harvesters[$hn_full]  = true;
            $alert_consecutive_harvesters[$hn]       = true;
            $alert_consecutive_harvesters[$hn_short] = true;
        }
    }
} catch (Exception $e) {}

// Admin Field Visits
$admin_visits_map = [];
try {
    $stmt_avm = $conn->prepare("SELECT * FROM admin_field_visits WHERE visit_date = :dt ORDER BY created_at DESC");
    $stmt_avm->execute([':dt' => $filter_date]);
    foreach ($stmt_avm->fetchAll(PDO::FETCH_ASSOC) as $av) {
        $hn = trim($av['harvester_number']);
        $short = str_replace('รถตัดเบอร์ ', '', $hn);
        $admin_visits_map[$short] = $av;
        $admin_visits_map[$hn]    = $av;
    }
} catch (Exception $e) {}

// คำนวณ KPI & Table Rows
$total_harvesters = count($harvester_list);
$cnt_passed  = 0;
$cnt_failed  = 0;
$cnt_pending = 0;
$cnt_alerts  = 0;

foreach ($harvester_list as $h) {
    $h_num     = $h['harvester_number'];
    $short_num = str_replace('รถตัดเบอร์ ', '', $h_num);
    $cs        = $check_sessions_map[$h_num] ?? ($check_sessions_map[$short_num] ?? null);
    if ($cs) {
        $fail_cnt = (int)($cs['fail_items_count'] ?? 0);
        if ($fail_cnt > 0 || ($cs['overall_pass'] !== null && (int)$cs['overall_pass'] === 0)) {
            $cnt_failed++;
        } else {
            $cnt_passed++;
        }
    } else {
        $cnt_pending++;
    }

    if (isset($alert_consecutive_harvesters[$h_num]) || isset($alert_consecutive_harvesters[$short_num])) {
        $cnt_alerts++;
    }
}

$table_rows = [];
$comparison_rows = [];

foreach ($harvester_list as $h) {
    $h_num     = $h['harvester_number'];
    $short_num = str_replace('รถตัดเบอร์ ', '', $h_num);
    $cs        = $check_sessions_map[$h_num] ?? ($check_sessions_map[$short_num] ?? null);
    $pts       = $posts_map[$h_num] ?? ($posts_map[$short_num] ?? []);

    $check_status = 'pending';
    if ($cs) {
        $fail_cnt = (int)($cs['fail_items_count'] ?? 0);
        $check_status = ($fail_cnt > 0 || ($cs['overall_pass'] !== null && (int)$cs['overall_pass'] === 0)) ? 'failed' : 'passed';
    }

    $has_alert = isset($alert_consecutive_harvesters[$h_num]) || isset($alert_consecutive_harvesters[$short_num]);
    $unit_name = $cs['emp_unit'] ?? ($pts[0]['target_unit'] ?? 'ไม่ระบุหน่วย');

    if ($search_q !== '' && !str_contains(mb_strtolower($h_num), mb_strtolower($search_q))) continue;

    if ($filter_status !== '') {
        if ($filter_status === 'pending' && $check_status !== 'pending') continue;
        if ($filter_status === 'passed'  && $check_status !== 'passed')  continue;
        if ($filter_status === 'failed'  && $check_status !== 'failed')  continue;
        if ($filter_status === 'alert'   && !$has_alert)                 continue;
    }

    $admin_visit = $admin_visits_map[$short_num] ?? ($admin_visits_map[$h_num] ?? null);

    $row_item = [
        'harvester_id' => $h['harvester_id'],
        'harvester_number' => $h_num,
        'short_number' => $short_num,
        'unit_name' => $unit_name,
        'check_status' => $check_status,
        'session_id' => $cs['session_id'] ?? null,
        'inspector_name' => $cs['emp_name'] ?? '-',
        'inspector_unit' => $cs['emp_unit'] ?? '-',
        'inspector_emp_id' => $cs['emp_id'] ?? '-',
        'checked_at' => $cs['checked_at'] ?? null,
        'field_condition' => $cs['field_condition'] ?? '-',
        'total_items' => $cs['total_checked_items'] ?? 0,
        'pass_items' => $cs['pass_items_count'] ?? 0,
        'fail_items' => $cs['fail_items_count'] ?? 0,
        'img_harvester' => $cs['img_harvester'] ?? null,
        'img_field' => $cs['img_field'] ?? null,
        'remark' => $cs['remark'] ?? '',
        'posts_cnt'    => count($pts),
        'latest_post'  => $pts[0] ?? null,
        'has_alert_3d' => $has_alert,
        'admin_visit'  => $admin_visit,
    ];

    $table_rows[] = $row_item;

    $comp_status = 'match';
    $comp_status_label = '✅ ข้อมูลตรงกัน';
    $comp_status_bg = 'bg-emerald-100 text-emerald-800 border-emerald-300';

    if ($has_alert) {
        $comp_status = 'urgent';
        $comp_status_label = '🚨 ต้องตรวจสอบด่วน (โพสต์ 3 วันติด)';
        $comp_status_bg = 'bg-rose-100 text-rose-800 border-rose-300';
    } elseif (count($pts) > 0 && !$cs) {
        $comp_status = 'mismatch';
        $comp_status_label = '⚠️ ข้อมูลไม่ตรงกัน (มีโพสต์แต่ยังไม่ได้ตรวจ)';
        $comp_status_bg = 'bg-amber-100 text-amber-800 border-amber-300';
    } elseif (count($pts) > 0 && $check_status === 'failed') {
        $comp_status = 'mismatch';
        $comp_status_label = '⚠️ พบปัญหาตรงกัน (มีโพสต์ & ตรวจไม่ผ่าน)';
        $comp_status_bg = 'bg-orange-100 text-orange-800 border-orange-300';
    } elseif (!$cs && count($pts) == 0) {
        $comp_status_label = '⚪ ไม่มีกิจกรรม / ยังไม่ได้ตรวจ';
        $comp_status_bg = 'bg-slate-100 text-slate-600 border-slate-300';
    }

    $comparison_rows[] = array_merge($row_item, [
        'comp_status' => $comp_status,
        'comp_label' => $comp_status_label,
        'comp_bg' => $comp_status_bg
    ]);
}
?>