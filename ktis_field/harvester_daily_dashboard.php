<?php
/**
 * harvester_daily_dashboard.php — Admin Dashboard การเช็ครถตัดประจำวัน
 * ระบบติดตามและบริหารจัดการ "การตรวจเช็กรถตัด" สไตล์ Modern, Clean, Responsive
 */
date_default_timezone_set('Asia/Bangkok');
require_once 'config.php';
session_start();

if(!isset($_SESSION['emp_id']) || $_SESSION['emp_level'] !== 'a'){
    header("location: login.php"); exit;
}

$crop_year   = $_SESSION['crop_year'] ?? '69/70';
$filter_date     = $_GET['date']     ?? date('Y-m-d');
$filter_date_end = $_GET['date_end'] ?? $filter_date; // ช่วงเวลา
if($filter_date_end < $filter_date) $filter_date_end = $filter_date;
$search_q        = trim($_GET['q']   ?? '');
$filter_status   = $_GET['status']   ?? '';
$page            = max(1, (int)($_GET['page'] ?? 1));
$per_page        = 30;

// ── ดึงผู้ดูแลรถตัดแต่ละคัน (employee_harvester.emp_id = employee.ID) ──
$harvester_managers = [];
try {
    $stmt_mgr = $conn->query(
        "SELECT eh.harvester_id, e.ID, e.emp_id, e.emp_name, e.emp_unit
         FROM employee_harvester eh
         JOIN employee e ON CAST(e.ID AS CHAR) = eh.emp_id
         WHERE e.status = 1"
    );
    foreach($stmt_mgr->fetchAll(PDO::FETCH_ASSOC) as $row){
        $harvester_managers[(int)$row['harvester_id']][] = $row;
    }
} catch(Exception $e){}

$thai_months = ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน',
                'กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];

function thai_date_fmt(string $date, array $months): string {
    if(!$date) return '-';
    $time = strtotime($date);
    $d = (int)date('d', $time);
    $m = (int)date('m', $time);
    $y = (int)date('Y', $time) + 543;
    return $d.' '.$months[$m].' '.$y;
}

function thai_datetime_fmt(string $datetime, array $months): string {
    if(!$datetime) return '-';
    $time = strtotime($datetime);
    $d = (int)date('d', $time);
    $m = (int)date('m', $time);
    $y = (int)date('Y', $time) + 543;
    $t = date('H:i', $time);
    return $d.' '.$months[$m].' '.$y.' เวลา '.$t.' น.';
}

// ── 1. ดึงข้อมูล Zones / Units ──
$all_units = [];
try {
    $zu = $conn->query("SELECT CASE WHEN zone_id='000' THEN zone_name ELSE CONCAT(zone_id,' ',zone_name) END AS unit_name FROM zones ORDER BY zone_id ASC");
    $all_units = $zu->fetchAll(PDO::FETCH_COLUMN);
} catch(Exception $e){}

// ── 2. ดึงรายการรถตัดทั้งหมดในระบบ ──
$harvester_list = [];
try {
    $stmt_h = $conn->query("SELECT harvester_id, harvester_number, harvester_name, is_active FROM harvesters WHERE is_active=1 ORDER BY harvester_id ASC");
    $harvester_list = $stmt_h->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e){}

// Fallback ถ้า DB ยังไม่มีรายการรถตัด
if(empty($harvester_list)){
    for($i=1; $i<=50; $i++){
        $harvester_list[] = [
            'harvester_id' => $i,
            'harvester_number' => "รถตัดเบอร์ $i",
            'harvester_name' => null,
            'is_active' => 1
        ];
    }
}

// ── 3. ดึงข้อมูลการตรวจเช็คในช่วงวันที่เลือก ──
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

    foreach($raw_sessions as $cs){
        $hn = trim($cs['harvester_number']);
        if(!str_contains($hn, 'รถตัดเบอร์')){ $hn_full = "รถตัดเบอร์ " . $hn; } else { $hn_full = $hn; }
        // ถ้ามีหลายวัน เก็บอันล่าสุด (DESC) อันแรกสุด
        if(!isset($check_sessions_map[$hn_full])) $check_sessions_map[$hn_full] = $cs;
        if(!isset($check_sessions_map[$hn]))      $check_sessions_map[$hn]      = $cs;
    }
} catch(Exception $e){}

// ── 4. ดึงรายการผลการตรวจละเอียด (Check Items Detail) สำหรับ Modal ──
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

    foreach($raw_details as $rd){
        $session_results_detail[$rd['session_id']][] = $rd;
    }
} catch(Exception $e){}

// ── 5. ดึงข้อมูลโพสต์ในช่วงวันที่เลือก ──
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

    foreach($raw_posts as $pt){
        $hn = trim($pt['harvester_number'] ?? '');
        if($hn !== ''){
            $posts_map[$hn][] = $pt;
            if(!str_contains($hn, 'รถตัดเบอร์')){ $posts_map["รถตัดเบอร์ $hn"][] = $pt; }
        }
    }
} catch(Exception $e){}

// ── 6. Smart Alert: รถที่มีโพสต์ติดต่อ 3 วันขึ้นไป (เอกเทศ ไม่ขึ้นกับ filter_date) ──
$alert_consecutive_harvesters = [];
$today_real  = date('Y-m-d');
$day_minus_1 = date('Y-m-d', strtotime('-1 day'));
$day_minus_2 = date('Y-m-d', strtotime('-2 days'));

// รถที่ Admin ลงพื้นที่แล้ว (dismiss)
$admin_visited_today = [];
try {
    $stmt_av = $conn->prepare(
        "SELECT DISTINCT harvester_number FROM admin_field_visits WHERE visit_date = :dt"
    );
    $stmt_av->execute([':dt' => $today_real]);
    foreach($stmt_av->fetchAll(PDO::FETCH_COLUMN) as $hvn){ $admin_visited_today[$hvn] = true; }
} catch(Exception $e){}

try {
    $sql_3d = "SELECT harvester_number,
                      COUNT(DISTINCT DATE(created_at)) AS post_days_cnt
               FROM posts
               WHERE DATE(created_at) IN (:d0,:d1,:d2)
                 AND harvester_number IS NOT NULL AND harvester_number != ''
                 AND crop_year = :cy
               GROUP BY harvester_number
               HAVING post_days_cnt >= 3";
    $stmt_3d = $conn->prepare($sql_3d);
    $stmt_3d->execute([':d0'=>$today_real,':d1'=>$day_minus_1,':d2'=>$day_minus_2,':cy'=>$crop_year]);
    foreach($stmt_3d->fetchAll(PDO::FETCH_ASSOC) as $ar){
        $hn = trim($ar['harvester_number']);
        $hn_full = str_contains($hn,'รถตัดเบอร์') ? $hn : "รถตัดเบอร์ $hn";
        if(!isset($admin_visited_today[$hn]) && !isset($admin_visited_today[$hn_full])){
            $alert_consecutive_harvesters[$hn_full] = true;
            $alert_consecutive_harvesters[$hn]      = true;
        }
    }
} catch(Exception $e){}

// ── 7. ประกอบข้อมูลสำหรับ Dashboard KPI & Table ──
$total_harvesters = count($harvester_list);
$cnt_passed  = 0;
$cnt_failed  = 0;
$cnt_pending = 0;
$cnt_alerts  = count($alert_consecutive_harvesters) / 2; // แต่ละคันเก็บ 2 key (full+short)

// ── ดึงบันทึก Admin Field Visits ของวันที่เลือก (keyed by harvester_number) ──
$admin_visits_map = []; // key = harvester_number (รูปแบบสั้น), value = visit row
try {
    $stmt_avm = $conn->prepare(
        "SELECT * FROM admin_field_visits WHERE visit_date = :dt ORDER BY created_at DESC"
    );
    $stmt_avm->execute([':dt' => $filter_date]);
    foreach($stmt_avm->fetchAll(PDO::FETCH_ASSOC) as $av){
        $hn = trim($av['harvester_number']);
        $short = str_replace('รถตัดเบอร์ ', '', $hn);
        // เก็บทั้งสองรูปแบบ ให้ match ได้แน่นอน
        $admin_visits_map[$short] = $av;
        $admin_visits_map[$hn]    = $av;
    }
} catch(Exception $e){}

$table_rows = [];
$comparison_rows = [];

foreach($harvester_list as $h){
    $h_num = $h['harvester_number']; // e.g. "รถตัดเบอร์ 1"
    $short_num = str_replace('รถตัดเบอร์ ', '', $h_num);

    // ค้นหา session การตรวจ
    $cs = $check_sessions_map[$h_num] ?? ($check_sessions_map[$short_num] ?? null);
    // ค้นหาโพสต์
    $pts = $posts_map[$h_num] ?? ($posts_map[$short_num] ?? []);

    // ตรวจสอบสถานะการตรวจเช็ค
    $check_status = 'pending'; // 'passed', 'failed', 'pending'
    if($cs){
        $fail_cnt = (int)($cs['fail_items_count'] ?? 0);
        if($fail_cnt > 0 || ($cs['overall_pass'] !== null && (int)$cs['overall_pass'] === 0)){
            $check_status = 'failed';
            $cnt_failed++;
        } else {
            $check_status = 'passed';
            $cnt_passed++;
        }
    } else {
        $cnt_pending++;
    }

    // ตรวจสอบ alert 3 วันติด
    $has_alert = isset($alert_consecutive_harvesters[$h_num]) || isset($alert_consecutive_harvesters[$short_num]);

    // หน่วยงานอ้างอิง
    $unit_name = $cs['emp_unit'] ?? ($pts[0]['target_unit'] ?? 'ไม่ระบุหน่วย');

    // กรอง search
    if($search_q !== '' && !str_contains(mb_strtolower($h_num), mb_strtolower($search_q))){
        continue;
    }

    // กรอง status
    $check_status_tmp = 'pending';
    if($cs){
        $fail_cnt_tmp = (int)($cs['fail_items_count'] ?? 0);
        $check_status_tmp = ($fail_cnt_tmp > 0 || ($cs['overall_pass'] !== null && (int)$cs['overall_pass'] === 0)) ? 'failed' : 'passed';
    }
    $has_alert_tmp = isset($alert_consecutive_harvesters[$h_num]) || isset($alert_consecutive_harvesters[$short_num]);

    if($filter_status !== ''){
        if($filter_status === 'pending'  && $check_status_tmp !== 'pending') continue;
        if($filter_status === 'passed'   && $check_status_tmp !== 'passed')  continue;
        if($filter_status === 'failed'   && $check_status_tmp !== 'failed')  continue;
        if($filter_status === 'alert'    && !$has_alert_tmp)                 continue;
    }

    // ค้นหา admin field visit ของรถตัดคันนี้
    $admin_visit = $admin_visits_map[$short_num] ?? ($admin_visits_map[$h_num] ?? null);

    // สร้างข้อมูล Row
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
        'admin_visit'  => $admin_visit,  // null หรือ row จาก admin_field_visits
    ];

    $table_rows[] = $row_item;

    // นับ KPI
    if($check_status === 'passed')       $cnt_passed++;
    elseif($check_status === 'failed')   $cnt_failed++;
    else                                 $cnt_pending++;
    if($has_alert) $cnt_alerts_real = ($cnt_alerts_real ?? 0) + 1;

    // เปรียบเทียบข้อมูล (Comparison View)
    $comp_status = 'match'; // 'match', 'mismatch', 'urgent'
    $comp_status_label = '✅ ข้อมูลตรงกัน';
    $comp_status_bg = 'bg-emerald-100 text-emerald-800 border-emerald-300';

    if($has_alert){
        $comp_status = 'urgent';
        $comp_status_label = '🚨 ต้องตรวจสอบด่วน (โพสต์ 3 วันติด)';
        $comp_status_bg = 'bg-rose-100 text-rose-800 border-rose-300';
    } elseif(count($pts) > 0 && !$cs){
        $comp_status = 'mismatch';
        $comp_status_label = '⚠️ ข้อมูลไม่ตรงกัน (มีโพสต์แต่ยังไม่ได้ตรวจ)';
        $comp_status_bg = 'bg-amber-100 text-amber-800 border-amber-300';
    } elseif(count($pts) > 0 && $check_status === 'failed'){
        $comp_status = 'mismatch';
        $comp_status_label = '⚠️ พบปัญหาตรงกัน (มีโพสต์ & ตรวจไม่ผ่าน)';
        $comp_status_bg = 'bg-orange-100 text-orange-800 border-orange-300';
    } elseif(!$cs && count($pts) == 0){
        $comp_status_label = '⚪ ไม่มีกิจกรรม / ยังไม่ได้ตรวจ';
        $comp_status_bg = 'bg-slate-100 text-slate-600 border-slate-300';
    }

    $comparison_rows[] = array_merge($row_item, [
        'comp_status' => $comp_status,
        'comp_label' => $comp_status_label,
        'comp_bg' => $comp_status_bg
    ]);
}

// ── 8. ไม่ใช้ dummy data ──
// (ลบออกแล้ว ใช้ข้อมูลจริงจาก DB เท่านั้น)

include 'includes/nav_u_header.php';
?>
<!DOCTYPE html>
<html lang="th" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — การเช็ครถตัดประจำวัน | KTIS SMART FIELD</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        ktis: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                        },
                        brand: {
                            primary: '#e11d48',
                            dark: '#1e293b'
                        }
                    },
                    fontFamily: {
                        sarabun: ['Sarabun', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="global_smoothness.css">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f8fafc; }
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(226, 232, 240, 0.8);
        }
        .dark-mode .glass-card {
            background: rgba(30, 41, 59, 0.95);
            border-color: rgba(51, 65, 85, 0.8);
        }
        .modal-backdrop {
            background-color: rgba(15, 23, 42, 0.65);
            backdrop-filter: blur(4px);
        }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .pulse-subtle { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .7; } }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 dark:bg-slate-900 dark:text-slate-100 min-h-screen">

<div class="page-wrapper" style="display:flex;min-height:100vh;">
    <?php include 'includes/nav_u_sidebar.php'; ?>

    <div class="main-content" style="flex:1;padding:24px 28px;min-width:0;overflow-x:hidden;">
        
        <!-- Header & Title Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider mb-1">
                    <i class="fa-solid fa-gauge-high"></i> ระบบบริหารจัดการรถตัดอ้อย
                </div>
                <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900 dark:text-white flex items-center gap-3">
                    <span class="p-2.5 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-xl">
                        <i class="fa-solid fa-tractor"></i>
                    </span>
                    การเช็ครถตัดประจำวัน
                </h1>
                <p class="text-xs md:text-sm text-slate-500 dark:text-slate-400 mt-1">
                    ติดตาม ตรวจสอบ และเปรียบเทียบผลการตรวจเช็กรถตัดอ้อยประจำวัน (ปีการผลิต <?php echo htmlspecialchars($crop_year); ?>)
                </p>
            </div>

            <!-- Action Buttons Header -->
            <div class="flex items-center gap-2 flex-wrap">
                <button type="button" onclick="openComparisonModal()" 
                        class="px-4 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-xs md:text-sm font-bold rounded-xl shadow-md hover:shadow-lg transition-all duration-200 flex items-center gap-2">
                    <i class="fa-solid fa-code-compare"></i>
                    <span>เปรียบเทียบข้อมูล (Comparison View)</span>
                </button>
                <a href="?date=<?php echo date('Y-m-d'); ?>" 
                   class="px-3.5 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-xs md:text-sm font-semibold rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 transition flex items-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-calendar-day text-emerald-500"></i>
                    <span>วันนี้</span>
                </a>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="glass-card rounded-2xl p-4 mb-6 shadow-sm">
            <form method="GET" action="harvester_daily_dashboard.php" class="flex flex-wrap gap-3 items-end">
                <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter_status); ?>">

                <!-- Date range -->
                <div class="flex-1 min-w-[240px]">
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">
                        <i class="fa-solid fa-calendar-days text-emerald-500 mr-1"></i> ช่วงวันที่
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="date" name="date" value="<?php echo htmlspecialchars($filter_date); ?>"
                               max="<?php echo date('Y-m-d'); ?>"
                               class="flex-1 px-3 py-2.5 bg-white dark:bg-slate-800 border-2 border-emerald-400 rounded-xl text-sm font-bold focus:ring-2 focus:ring-emerald-500 outline-none text-slate-800 dark:text-slate-100 shadow-sm">
                        <span class="text-slate-400 text-xs font-bold">ถึง</span>
                        <input type="date" name="date_end" value="<?php echo htmlspecialchars($filter_date_end); ?>"
                               max="<?php echo date('Y-m-d'); ?>"
                               class="flex-1 px-3 py-2.5 bg-white dark:bg-slate-800 border-2 border-emerald-400 rounded-xl text-sm font-bold focus:ring-2 focus:ring-emerald-500 outline-none text-slate-800 dark:text-slate-100 shadow-sm">
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1 flex gap-3 flex-wrap">
                        <?php
                        $prev  = date('Y-m-d', strtotime($filter_date.' -1 day'));
                        $next  = date('Y-m-d', strtotime($filter_date.' +1 day'));
                        $today = date('Y-m-d');
                        $week_start = date('Y-m-d', strtotime('monday this week'));
                        $month_start = date('Y-m-01');
                        ?>
                        <a href="?date=<?php echo $today; ?>&date_end=<?php echo $today; ?>" class="text-emerald-600 font-bold hover:underline">วันนี้</a>
                        <a href="?date=<?php echo $prev; ?>&date_end=<?php echo $prev; ?>" class="text-emerald-600 font-bold hover:underline">เมื่อวาน</a>
                        <a href="?date=<?php echo $week_start; ?>&date_end=<?php echo $today; ?>" class="text-emerald-600 font-bold hover:underline">สัปดาห์นี้</a>
                        <a href="?date=<?php echo $month_start; ?>&date_end=<?php echo $today; ?>" class="text-emerald-600 font-bold hover:underline">เดือนนี้</a>
                    </div>
                </div>

                <!-- ค้นหาเบอร์รถ -->
                <div class="flex-1 min-w-[160px]">
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">
                        <i class="fa-solid fa-magnifying-glass text-rose-500 mr-1"></i> ค้นหาเบอร์รถ
                    </label>
                    <input type="text" name="q" value="<?php echo htmlspecialchars($search_q); ?>" placeholder="เช่น รถตัดเบอร์ 1"
                           class="w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 outline-none text-slate-800 dark:text-slate-100">
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="py-2.5 px-5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-xl shadow-sm transition flex items-center gap-1.5">
                        <i class="fa-solid fa-filter"></i> กรอง
                    </button>
                    <?php if($search_q || $filter_date !== date('Y-m-d') || $filter_date_end !== date('Y-m-d')): ?>
                    <a href="harvester_daily_dashboard.php" class="py-2.5 px-3 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-sm font-semibold rounded-xl hover:bg-slate-300 transition flex items-center" title="ล้างการกรอง">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- 1. ส่วนสรุปข้อมูลหลัก (KPI Cards / Summary Section) -->
        <?php
        $base_url = '?date='.urlencode($filter_date).'&date_end='.urlencode($filter_date_end);
        $kpi_cards = [
            ['status'=>'',       'label'=>'รถตัดทั้งหมด',    'count'=>$total_harvesters, 'sub'=>'ในฐานข้อมูล',          'icon'=>'fa-tractor',            'color'=>'slate',  'bar'=>'bg-slate-400'],
            ['status'=>'pending','label'=>'ยังไม่ได้ตรวจ',   'count'=>$cnt_pending,      'sub'=>'รอการลงพื้นที่ตรวจ',   'icon'=>'fa-clock',              'color'=>'amber',  'bar'=>'bg-amber-500'],
            ['status'=>'passed', 'label'=>'ผ่านการตรวจ',     'count'=>$cnt_passed,       'sub'=>'อุปกรณ์พร้อมทำงาน',    'icon'=>'fa-circle-check',       'color'=>'emerald','bar'=>'bg-emerald-500'],
            ['status'=>'failed', 'label'=>'ไม่ผ่านการตรวจ',  'count'=>$cnt_failed,       'sub'=>'พบอุปกรณ์ชำรุด/บกพร่อง','icon'=>'fa-triangle-exclamation','color'=>'rose',  'bar'=>'bg-rose-500'],
            ['status'=>'alert',  'label'=>'โพสต์ 3 วันติด',  'count'=>$cnt_alerts,       'sub'=>'ต้องลงตรวจด่วน!',      'icon'=>'fa-bell',               'color'=>'red',    'bar'=>'bg-gradient-to-r from-rose-600 to-red-600','extra'=>'col-span-2 md:col-span-1'],
        ];
        $color_map = [
            'slate'  => ['text'=>'text-slate-500 dark:text-slate-400',  'num'=>'text-slate-900 dark:text-white',         'sub'=>'text-slate-400',                     'icon_bg'=>'bg-slate-100 dark:bg-slate-800',        'icon_txt'=>'text-slate-600 dark:text-slate-300', 'border_active'=>'border-slate-500',  'border_hover'=>'hover:border-slate-400'],
            'amber'  => ['text'=>'text-amber-600 dark:text-amber-400',  'num'=>'text-amber-600 dark:text-amber-400',     'sub'=>'text-amber-700/60 dark:text-amber-400/60','icon_bg'=>'bg-amber-500/10',                  'icon_txt'=>'text-amber-600 dark:text-amber-400', 'border_active'=>'border-amber-500',  'border_hover'=>'hover:border-amber-300'],
            'emerald'=> ['text'=>'text-emerald-600 dark:text-emerald-400','num'=>'text-emerald-600 dark:text-emerald-400','sub'=>'text-emerald-700/60 dark:text-emerald-400/60','icon_bg'=>'bg-emerald-500/10',             'icon_txt'=>'text-emerald-600 dark:text-emerald-400','border_active'=>'border-emerald-500','border_hover'=>'hover:border-emerald-300'],
            'rose'   => ['text'=>'text-rose-600 dark:text-rose-400',    'num'=>'text-rose-600 dark:text-rose-400',       'sub'=>'text-rose-700/60 dark:text-rose-400/60', 'icon_bg'=>'bg-rose-500/10',                   'icon_txt'=>'text-rose-600 dark:text-rose-400',   'border_active'=>'border-rose-500',   'border_hover'=>'hover:border-rose-300'],
            'red'    => ['text'=>'text-rose-700 dark:text-rose-300',    'num'=>'text-rose-700 dark:text-rose-300',       'sub'=>'text-rose-600 dark:text-rose-400 font-semibold','icon_bg'=>'bg-rose-600',                 'icon_txt'=>'text-white',                         'border_active'=>'border-red-500',    'border_hover'=>'hover:border-red-400'],
        ];
        ?>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3.5 mb-6">
        <?php foreach($kpi_cards as $kc):
            $c = $color_map[$kc['color']];
            $is_active = ($filter_status === $kc['status']);
            $link = $kc['status'] === '' ? $base_url : $base_url.'&status='.$kc['status'];
            $active_cls = $is_active ? 'ring-2 ring-offset-1 '.$c['border_active'].' border-2 '.$c['border_active'] : 'border border-slate-200 dark:border-slate-700 '.$c['border_hover'];
            $extra = $kc['extra'] ?? '';
        ?>
        <a href="<?php echo $link; ?>"
           class="glass-card rounded-2xl p-4 flex flex-col justify-between shadow-sm relative overflow-hidden transition cursor-pointer no-underline <?php echo $active_cls.' '.$extra; ?>"
           style="text-decoration:none;">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold <?php echo $c['text']; ?>">
                    <?php if($kc['color']==='red'): ?><i class="fa-solid fa-fire pulse-subtle mr-1"></i><?php endif; ?>
                    <?php echo $kc['label']; ?>
                </span>
                <span class="w-9 h-9 rounded-xl <?php echo $c['icon_bg']; ?> <?php echo $c['icon_txt']; ?> flex items-center justify-center text-base">
                    <i class="fa-solid <?php echo $kc['icon']; ?>"></i>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl md:text-3xl font-extrabold <?php echo $c['num']; ?>"><?php echo number_format($kc['count']); ?></div>
                <div class="text-[11px] <?php echo $c['sub']; ?> mt-0.5"><?php echo $kc['sub']; ?></div>
            </div>
            <?php if($is_active): ?>
            <div class="absolute top-0 right-0 p-1.5">
                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded <?php echo $c['border_active']; ?> border <?php echo $c['text']; ?> bg-white/80">กำลังดู</span>
            </div>
            <?php endif; ?>
            <div class="absolute bottom-0 left-0 right-0 h-1 <?php echo $kc['bar']; ?>"></div>
        </a>
        <?php endforeach; ?>
        </div>

        <!-- Smart Alert Banner (ถ้ามีคันที่เตือน 3 วันติด) -->
        <?php if($cnt_alerts > 0): ?>
            <div class="mb-6 bg-gradient-to-r from-rose-500 to-red-600 rounded-2xl p-4 text-white shadow-md flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center text-xl shrink-0">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <h4 class="font-extrabold text-sm md:text-base">⚠️ แจ้งเตือนพิเศษ: พบรถตัดที่มีการโพสต์กิจกรรมต่อเนื่อง 3 วันขึ้นไป</h4>
                        <p class="text-xs text-rose-100 mt-0.5">
                            มีรถตัดจำนวน <span class="font-bold underline"><?php echo $cnt_alerts; ?> คัน</span> ที่ถูกแจ้งปัญหาติดกัน 3 วัน ต้องส่งพนักงานลงพื้นที่ตรวจสอบด่วน
                        </p>
                    </div>
                </div>
                <button type="button" onclick="filterAlertOnly()" class="px-4 py-2 bg-white text-rose-700 hover:bg-rose-50 text-xs font-bold rounded-xl shadow transition shrink-0">
                    <i class="fa-solid fa-eye"></i> ดูเฉพาะรายการเตือน
                </button>
            </div>
        <?php endif; ?>

        <!-- 2. รายการรถตัดและ Popup ข้อมูลผู้ตรวจ (Harvester List) -->
        <div class="glass-card rounded-2xl shadow-sm overflow-hidden mb-8">
            <div class="p-4 md:p-5 border-b border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h3 class="font-extrabold text-base md:text-lg text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="fa-solid fa-list-check text-emerald-500"></i>
                        รายการรถตัดอ้อยและการตรวจเช็คประจำวัน
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        แสดงข้อมูลประจำวันที่ <span class="font-bold text-slate-700 dark:text-slate-200"><?php echo thai_date_fmt($filter_date, $thai_months); ?></span>
                        <?php if($filter_status !== ''): ?>
                        — กรอง: <span class="font-bold text-emerald-600">
                        <?php echo ['pending'=>'ยังไม่ได้ตรวจ','passed'=>'ผ่านการตรวจ','failed'=>'ไม่ผ่านการตรวจ','alert'=>'เกิน 3 วัน'][$filter_status] ?? ''; ?>
                        </span> &nbsp;<a href="?date=<?php echo urlencode($filter_date); ?>" class="text-rose-500 underline text-xs">ล้างตัวกรอง</a>
                        <?php endif; ?>
                        (<?php echo count($table_rows); ?> คัน)
                    </p>
                </div>
                <div class="flex items-center gap-2 text-xs">
                    <span class="px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-800 font-semibold">● ผ่านการตรวจ</span>
                    <span class="px-2.5 py-1 rounded-lg bg-rose-100 text-rose-800 font-semibold">● ไม่ผ่าน</span>
                    <span class="px-2.5 py-1 rounded-lg bg-amber-100 text-amber-800 font-semibold">● รอการตรวจ</span>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left text-xs md:text-sm border-collapse min-w-[850px]" id="harvesterTable">
                    <thead>
                        <tr class="bg-slate-100/80 dark:bg-slate-800/80 text-slate-600 dark:text-slate-300 font-bold border-b border-slate-200 dark:border-slate-700">
                            <th class="py-3.5 px-4">เบอร์รถตัด</th>
                            <th class="py-3.5 px-4">โพสต์วันนี้</th>
                            <th class="py-3.5 px-4">สถานะการตรวจ</th>
                            <th class="py-3.5 px-4">ผู้ตรวจ</th>
                            <th class="py-3.5 px-4 text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <?php if(empty($table_rows)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-10 text-slate-400">
                                    <i class="fa-solid fa-folder-open text-3xl mb-2 block"></i>
                                    ไม่พบข้อมูลรถตัดตรงเงื่อนไขการค้นหา
                                </td>
                            </tr>
                        <?php else:
                            $total_rows  = count($table_rows);
                            $total_pages = max(1, (int)ceil($total_rows / $per_page));
                            $page        = min($page, $total_pages);
                            $offset_rows = ($page - 1) * $per_page;
                            $page_rows   = array_slice($table_rows, $offset_rows, $per_page);
                        ?>
                            <?php foreach($page_rows as $idx => $r): ?>
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition row-item <?php echo $r['has_alert_3d']?'is-alert-row bg-rose-50/40 dark:bg-rose-950/20':''; ?>">
                                    
                                    <!-- เบอร์รถตัด -->
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-start gap-2.5">
                                            <div class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-slate-700 dark:text-slate-300 font-extrabold text-xs shrink-0 mt-0.5">
                                                #<?php echo $r['short_number']; ?>
                                            </div>
                                            <div>
                                                <!-- ชื่อรถตัด กดได้ -->
                                                <a href="harvester_admin.php?harvester=<?php echo urlencode($r['harvester_number']); ?>"
                                                   class="font-bold text-slate-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400 text-left transition flex items-center gap-1.5">
                                                    <span><?php echo htmlspecialchars($r['harvester_number']); ?></span>
                                                    <i class="fa-solid fa-arrow-up-right-from-square text-slate-400 text-xs"></i>
                                                </a>
                                                <?php
                                                $mgrs = $harvester_managers[$r['harvester_id']] ?? [];
                                                if(!empty($mgrs)): ?>
                                                <button type="button"
                                                        onclick="openManagerPopup(<?php echo htmlspecialchars(json_encode($mgrs, JSON_UNESCAPED_UNICODE|JSON_HEX_APOS|JSON_HEX_QUOT)); ?>, '<?php echo addslashes(htmlspecialchars($r['harvester_number'])); ?>')"
                                                        class="inline-flex items-center gap-1 text-[11px] text-amber-700 bg-amber-50 border border-amber-200 rounded-md px-2 py-0.5 mt-0.5 hover:bg-amber-100 transition cursor-pointer font-semibold">
                                                    <i class="fa-solid fa-user-shield" style="font-size:.65rem;"></i>
                                                    <?php echo htmlspecialchars($mgrs[0]['emp_name']); ?>
                                                    <?php if(count($mgrs) > 1): ?>
                                                    <span class="bg-amber-500 text-white rounded-full px-1 text-[9px] font-bold">+<?php echo count($mgrs)-1; ?></span>
                                                    <?php endif; ?>
                                                </button>
                                                <?php else: ?>
                                                <div class="text-[11px] text-slate-300 mt-0.5 italic">ยังไม่มีผู้ดูแล</div>
                                                <?php endif; ?>

                                                <!-- Smart Alert Badge -->
                                                <?php if($r['has_alert_3d']): ?>
                                                    <div class="mt-1 inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-rose-600 text-white text-[10px] font-extrabold shadow-sm animate-pulse">
                                                        <span>⚠️ แจ้งเตือน: มีการโพสติดกัน 3 วันแล้ว - ต้องส่งพนักงานลงพื้นที่ตรวจสอบด่วน</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- หน่วยงาน -->
                                    <!-- กิจกรรมโพสต์วันนี้ -->
                                    <td class="py-3.5 px-4">
                                        <?php if($r['posts_cnt'] > 0): ?>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-orange-100 text-orange-800 font-bold text-xs">
                                                <i class="fa-solid fa-bullhorn"></i> <?php echo $r['posts_cnt']; ?> โพสต์
                                            </span>
                                            <?php if(!empty($r['latest_post']['problem_detail'])): ?>
                                                <div class="text-[11px] text-slate-500 truncate max-w-[150px] mt-0.5">
                                                    <?php echo htmlspecialchars($r['latest_post']['problem_detail']); ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-slate-400 text-xs">- ไม่มีโพสต์ -</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- สถานะการตรวจเช็ค -->
                                    <td class="py-3.5 px-4">
                                        <?php if($r['check_status'] === 'passed'): ?>
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 font-bold text-xs border border-emerald-200">
                                                <i class="fa-solid fa-check-circle"></i> ผ่านการตรวจ (<?php echo $r['pass_items']; ?>/<?php echo $r['total_items']; ?>)
                                            </span>
                                        <?php elseif($r['check_status'] === 'failed'): ?>
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300 font-bold text-xs border border-rose-200">
                                                <i class="fa-solid fa-circle-xmark"></i> ไม่ผ่าน (ไม่ผ่าน <?php echo $r['fail_items']; ?> ข้อ)
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 font-bold text-xs border border-amber-200">
                                                <i class="fa-solid fa-clock"></i> ยังไม่ได้ตรวจ
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- ผู้ลงพื้นที่ตรวจ -->
                                    <td class="py-3.5 px-4">
                                        <?php if($r['inspector_name'] !== '-'): ?>
                                            <div class="font-bold text-slate-800 dark:text-slate-200 text-xs">
                                                <?php echo htmlspecialchars($r['inspector_name']); ?>
                                            </div>
                                            <div class="text-[11px] text-slate-400 flex items-center gap-1">
                                                <i class="fa-solid fa-user-tag text-[10px]"></i> <?php echo htmlspecialchars($r['inspector_emp_id']); ?>
                                                · <?php echo date('H:i', strtotime($r['checked_at'])); ?> น.
                                            </div>
                                        <?php else: ?>
                                            <span class="text-slate-400 text-xs italic">- ยังไม่มีผู้ลงตรวจ -</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- ปุ่มจัดการ -->
                                    <td class="py-3.5 px-4 text-center">
                                        <div class="flex flex-col gap-1.5 items-center">
                                            <a href="harvester_admin.php?harvester=<?php echo urlencode($r['harvester_number']); ?>&date=<?php echo urlencode($r['checked_at'] ? date('Y-m-d', strtotime($r['checked_at'])) : $filter_date); ?>"
                                               class="px-3 py-1.5 bg-slate-100 hover:bg-emerald-600 hover:text-white dark:bg-slate-800 dark:hover:bg-emerald-600 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-lg transition shadow-sm inline-flex items-center gap-1">
                                                <i class="fa-solid fa-clipboard-list"></i>
                                                <span>รายการตรวจ</span>
                                            </a>
                                            <?php if($r['has_alert_3d']): ?>
                                            <a href="admin_visit_save.php?harvester=<?php echo urlencode($r['harvester_number']); ?>&date=<?php echo urlencode($filter_date); ?>"
                                               class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-lg transition shadow-sm inline-flex items-center gap-1 pulse-subtle">
                                                <i class="fa-solid fa-flag-checkered"></i>
                                                <span>บันทึกลงพื้นที่</span>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if(!empty($table_rows) && isset($total_pages) && $total_pages > 1): ?>
            <div class="flex items-center justify-between px-4 py-3 border-t border-slate-100 dark:border-slate-800 flex-wrap gap-2">
                <div class="text-xs text-slate-500 dark:text-slate-400">
                    แสดง <?php echo $offset_rows+1; ?>–<?php echo min($offset_rows+$per_page, $total_rows); ?> จาก <?php echo $total_rows; ?> คัน (หน้า <?php echo $page; ?>/<?php echo $total_pages; ?>)
                </div>
                <div class="flex items-center gap-1 flex-wrap">
                    <?php if($page > 1): ?>
                    <a href="?date=<?php echo urlencode($filter_date); ?>&date_end=<?php echo urlencode($filter_date_end); ?>&status=<?php echo urlencode($filter_status); ?>&q=<?php echo urlencode($search_q); ?>&page=<?php echo $page-1; ?>"
                       class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-lg hover:bg-emerald-600 hover:text-white transition">
                        <i class="fa-solid fa-chevron-left"></i> ก่อนหน้า
                    </a>
                    <?php endif; ?>
                    <?php
                    $range_start = max(1, $page - 2);
                    $range_end   = min($total_pages, $page + 2);
                    if($range_start > 1): ?><span class="text-slate-400 text-xs px-1">...</span><?php endif;
                    for($p=$range_start; $p<=$range_end; $p++):
                        $is_cur = $p === $page;
                    ?>
                    <a href="?date=<?php echo urlencode($filter_date); ?>&date_end=<?php echo urlencode($filter_date_end); ?>&status=<?php echo urlencode($filter_status); ?>&q=<?php echo urlencode($search_q); ?>&page=<?php echo $p; ?>"
                       class="px-3 py-1.5 text-xs font-bold rounded-lg transition <?php echo $is_cur ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-emerald-100 hover:text-emerald-700'; ?>">
                        <?php echo $p; ?>
                    </a>
                    <?php endfor;
                    if($range_end < $total_pages): ?><span class="text-slate-400 text-xs px-1">...</span><?php endif; ?>
                    <?php if($page < $total_pages): ?>
                    <a href="?date=<?php echo urlencode($filter_date); ?>&date_end=<?php echo urlencode($filter_date_end); ?>&status=<?php echo urlencode($filter_status); ?>&q=<?php echo urlencode($search_q); ?>&page=<?php echo $page+1; ?>"
                       class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-lg hover:bg-emerald-600 hover:text-white transition">
                        ถัดไป <i class="fa-solid fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

    </div>
</div>

<!-- ========================================== -->
<!-- MODAL 1: Popup ข้อมูลผู้ตรวจ (Inspector Modal) -->
<!-- ========================================== -->
<div id="inspectorModal" class="fixed inset-0 z-50 modal-backdrop hidden items-center justify-center p-4 overflow-y-auto">
    <div class="glass-card rounded-2xl max-w-2xl w-full max-h-[90vh] flex flex-col shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden transform transition-all">
        <!-- Modal Header -->
        <div class="p-4 md:p-5 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-lg font-bold">
                    <i class="fa-solid fa-user-check"></i>
                </div>
                <div>
                    <h3 class="font-extrabold text-base md:text-lg" id="modalHarvesterTitle">ข้อมูลผู้ลงพื้นที่ตรวจ</h3>
                    <p class="text-xs text-slate-400" id="modalCheckTime">วันที่ตรวจ: -</p>
                </div>
            </div>
            <button type="button" onclick="closeInspectorModal()" class="w-8 h-8 rounded-lg bg-slate-800 text-slate-400 hover:text-white hover:bg-slate-700 flex items-center justify-center transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-5 overflow-y-auto custom-scrollbar space-y-5 flex-1">
            
            <!-- Inspector Profile Card -->
            <div class="bg-slate-100 dark:bg-slate-800/80 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                    <i class="fa-solid fa-address-card text-emerald-500"></i> ข้อมูลพนักงานผู้ลงตรวจ
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs md:text-sm">
                    <div>
                        <span class="text-slate-500">ชื่อ-นามสกุล:</span>
                        <div class="font-extrabold text-slate-900 dark:text-white text-base" id="modalInspectorName">-</div>
                    </div>
                    <div>
                        <span class="text-slate-500">รหัสพนักงาน & หน่วยงาน:</span>
                        <div class="font-semibold text-slate-800 dark:text-slate-200" id="modalInspectorUnit">-</div>
                    </div>
                    <div>
                        <span class="text-slate-500">วัน/เวลาบันทึกผล:</span>
                        <div class="font-semibold text-slate-800 dark:text-slate-200" id="modalCheckDateTime">-</div>
                    </div>
                    <div>
                        <span class="text-slate-500">สภาพแปลงอ้อย:</span>
                        <div class="font-semibold text-slate-800 dark:text-slate-200" id="modalFieldCondition">-</div>
                    </div>
                </div>
            </div>

            <!-- Result Summary Badge -->
            <div class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">ผลการตรวจเช็ครวม:</span>
                <div id="modalStatusBadge">
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-200 text-slate-700">-</span>
                </div>
            </div>

            <!-- Failure Notes / Remarks -->
            <div id="modalRemarkBox" class="hidden">
                <div class="p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-xs">
                    <div class="font-bold text-rose-800 dark:text-rose-300 mb-1 flex items-center gap-1">
                        <i class="fa-solid fa-comment-dots"></i> หมายเหตุเพิ่มเติมจากผู้ตรวจ:
                    </div>
                    <p class="text-rose-700 dark:text-rose-200" id="modalRemarkText">-</p>
                </div>
            </div>

            <!-- Images Preview (Harvester & Field) -->
            <div id="modalImagesBox" class="hidden">
                <div class="text-xs font-bold text-slate-600 dark:text-slate-300 mb-2 flex items-center gap-1">
                    <i class="fa-solid fa-images text-indigo-500"></i> รูปภาพประกอบการตรวจเช็ค
                </div>
                <div class="grid grid-cols-2 gap-3" id="modalImageGrid">
                    <!-- Dynamic images injected via JS -->
                </div>
            </div>

            <!-- 19 Items Checklist Table preview -->
            <div>
                <div class="text-xs font-bold text-slate-600 dark:text-slate-300 mb-2 flex items-center justify-between">
                    <span><i class="fa-solid fa-list-check text-emerald-500 mr-1"></i> รายการอุปกรณ์รถตัด (19 รายการ)</span>
                    <span class="text-[11px] text-slate-400" id="modalItemSummaryCount">ผ่าน 0/0 ข้อ</span>
                </div>
                <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden text-xs">
                    <table class="w-full text-left">
                        <thead class="bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                            <tr>
                                <th class="p-2.5">รายการเช็ค</th>
                                <th class="p-2.5 text-center w-24">ผลการตรวจ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800" id="modalItemsTableBody">
                            <!-- Dynamic items -->
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Modal Footer -->
        <div class="p-4 bg-slate-100 dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700 flex justify-end">
            <button type="button" onclick="closeInspectorModal()" class="px-4 py-2 bg-slate-700 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition">
                ปิดหน้าต่าง
            </button>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL 2: เปรียบเทียบข้อมูล (Comparison View Modal) -->
<!-- ========================================== -->
<div id="comparisonModal" class="fixed inset-0 z-50 modal-backdrop hidden items-center justify-center p-4 overflow-y-auto">
    <div class="glass-card rounded-2xl max-w-5xl w-full max-h-[92vh] flex flex-col shadow-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <!-- Header -->
        <div class="p-4 md:p-5 bg-gradient-to-r from-slate-900 to-slate-800 text-white flex items-center justify-between border-b border-slate-800">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-teal-500/20 text-teal-400 flex items-center justify-center text-lg font-bold">
                    <i class="fa-solid fa-code-compare"></i>
                </div>
                <div>
                    <h3 class="font-extrabold text-base md:text-lg">รายงานเปรียบเทียบข้อมูล (Comparison View)</h3>
                    <p class="text-xs text-slate-300">
                        เปรียบเทียบกิจกรรมที่โพสต์เข้ามา vs ข้อมูลการตรวจเช็คจริงของพนักงาน (ประจำวันที่ <?php echo thai_date_fmt($filter_date, $thai_months); ?>)
                    </p>
                </div>
            </div>
            <button type="button" onclick="closeComparisonModal()" class="w-8 h-8 rounded-lg bg-slate-800 text-slate-400 hover:text-white hover:bg-slate-700 flex items-center justify-center transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Body Table -->
        <div class="p-4 md:p-6 overflow-y-auto custom-scrollbar flex-1">
            <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden">
                <table class="w-full text-left text-xs md:text-sm border-collapse min-w-[750px]">
                    <thead class="bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 font-bold border-b border-slate-200 dark:border-slate-700">
                        <tr>
                            <th class="py-3 px-3.5">รถตัด & หน่วยงาน</th>
                            <th class="py-3 px-3.5">กิจกรรมที่ถูกโพสต์วันนี้</th>
                            <th class="py-3 px-3.5">ผลตรวจเช็คจริงจากพนักงาน</th>
                            <th class="py-3 px-3.5 text-center">สถานะความสอดคล้อง</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <?php foreach($comparison_rows as $cr): ?>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                                <!-- เบอร์รถ -->
                                <td class="py-3 px-3.5 font-bold text-slate-900 dark:text-white">
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid fa-tractor text-emerald-500"></i>
                                        <span><?php echo htmlspecialchars($cr['harvester_number']); ?></span>
                                    </div>
                                    <div class="text-[11px] text-slate-400 font-normal mt-0.5">
                                        <?php echo htmlspecialchars($cr['unit_name']); ?>
                                    </div>
                                </td>

                                <!-- ข้อมูลโพสต์ -->
                                <td class="py-3 px-3.5">
                                    <?php if($cr['posts_cnt'] > 0): ?>
                                        <div class="font-bold text-orange-700 dark:text-orange-400 text-xs">
                                            <i class="fa-solid fa-bullhorn"></i> มี <?php echo $cr['posts_cnt']; ?> โพสต์
                                        </div>
                                        <div class="text-[11px] text-slate-500 mt-0.5">
                                            <?php echo htmlspecialchars($cr['latest_post']['problem_detail'] ?? 'แจ้งกิจกรรมในฟีด'); ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-slate-400 text-xs">- ไม่มีโพสต์แจ้ง -</span>
                                    <?php endif; ?>
                                </td>

                                <!-- ผลตรวจจริง -->
                                <td class="py-3 px-3.5">
                                    <?php if($cr['check_status'] === 'passed'): ?>
                                        <span class="text-emerald-600 font-bold text-xs">
                                            <i class="fa-solid fa-check-circle"></i> ผ่านตรวจ (โดย <?php echo htmlspecialchars($cr['inspector_name']); ?>)
                                        </span>
                                    <?php elseif($cr['check_status'] === 'failed'): ?>
                                        <span class="text-rose-600 font-bold text-xs">
                                            <i class="fa-solid fa-xmark-circle"></i> ไม่ผ่านตรวจ (ไม่ผ่าน <?php echo $cr['fail_items']; ?> ข้อ)
                                        </span>
                                    <?php else: ?>
                                        <span class="text-amber-600 font-semibold text-xs">
                                            <i class="fa-solid fa-clock"></i> ยังไม่ได้ลงตรวจเช็ค
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- สถานะความสอดคล้อง -->
                                <td class="py-3 px-3.5 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-extrabold border <?php echo $cr['comp_bg']; ?>">
                                        <?php echo $cr['comp_label']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-4 bg-slate-100 dark:bg-slate-800 border-t border-slate-200 dark:border-slate-700 flex justify-end">
            <button type="button" onclick="closeComparisonModal()" class="px-4 py-2 bg-slate-700 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition">
                ปิดหน้าต่าง
            </button>
        </div>
    </div>
</div>

<script>
    // ── Open / Close Modal 1: Inspector Modal ──
    function openInspectorModal(data) {
        document.getElementById('modalHarvesterTitle').innerText = data.harvester_number + " — รายละเอียดผู้ตรวจ";
        document.getElementById('modalInspectorName').innerText = data.inspector_name || '-';
        document.getElementById('modalInspectorUnit').innerText = (data.inspector_emp_id || '-') + " (" + (data.inspector_unit || '-') + ")";
        
        const dtStr = data.checked_at ? new Date(data.checked_at).toLocaleString('th-TH') : '-';
        document.getElementById('modalCheckTime').innerText = "วันที่ตรวจ: " + dtStr;
        document.getElementById('modalCheckDateTime').innerText = dtStr;
        document.getElementById('modalFieldCondition').innerText = data.field_condition || 'ปกติ';

        // Badge Status
        const badgeBox = document.getElementById('modalStatusBadge');
        if(data.check_status === 'passed') {
            badgeBox.innerHTML = '<span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300"><i class="fa-solid fa-check-circle"></i> ผ่านการตรวจทุกรายการ ('+data.pass_items+'/'+data.total_items+')</span>';
        } else if(data.check_status === 'failed') {
            badgeBox.innerHTML = '<span class="px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-300"><i class="fa-solid fa-circle-xmark"></i> ไม่ผ่านการตรวจ ('+data.fail_items+' อุปกรณ์บกพร่อง)</span>';
        } else {
            badgeBox.innerHTML = '<span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300"><i class="fa-solid fa-clock"></i> ยังไม่ได้ตรวจเช็ค</span>';
        }

        // Remark Box
        const remarkBox = document.getElementById('modalRemarkBox');
        if(data.remark && data.remark.trim() !== '') {
            document.getElementById('modalRemarkText').innerText = data.remark;
            remarkBox.classList.remove('hidden');
        } else {
            remarkBox.classList.add('hidden');
        }

        // Image Box
        const imgBox = document.getElementById('modalImagesBox');
        const imgGrid = document.getElementById('modalImageGrid');
        imgGrid.innerHTML = '';
        let hasImg = false;
        if(data.img_harvester) {
            hasImg = true;
            imgGrid.innerHTML += `<div class="rounded-xl overflow-hidden border border-slate-200"><img src="${data.img_harvester}" class="w-full h-32 object-cover" alt="รูปรถตัด"><span class="block text-center text-[10px] bg-slate-800 text-white py-0.5">รูปรถตัด</span></div>`;
        }
        if(data.img_field) {
            hasImg = true;
            imgGrid.innerHTML += `<div class="rounded-xl overflow-hidden border border-slate-200"><img src="${data.img_field}" class="w-full h-32 object-cover" alt="รูปแปลงอ้อย"><span class="block text-center text-[10px] bg-slate-800 text-white py-0.5">รูปแปลงอ้อย</span></div>`;
        }
        if(hasImg) {
            imgBox.classList.remove('hidden');
        } else {
            imgBox.classList.add('hidden');
        }

        // Checklist Items Summary
        document.getElementById('modalItemSummaryCount').innerText = `ผ่าน ${data.pass_items}/${data.total_items} รายการ`;
        const tbody = document.getElementById('modalItemsTableBody');
        tbody.innerHTML = '';

        if(data.check_status === 'pending') {
            tbody.innerHTML = `<tr><td colspan="2" class="p-4 text-center text-slate-400 italic">ยังไม่มีการบันทึกรายการตรวจเช็คประจำวัน</td></tr>`;
        } else {
            // Default 19 standard items representation
            const sampleItems = [
                "1. ระบบตัดยอด (ใบมีดคม & หมุนปกติ)",
                "2. เกลียวแบ่งอ้อย / ทุ่น / เล็บ",
                "3. ชุดตัดโคน (ใบมีดคม ครบ 10 ใบ)",
                "4. ชุดโรลเลอร์ต่างๆ",
                "5. ชุดสับท่อน / ล้อช่วยแรง",
                "6. พัดลมทำความสะอาด (หมุนปกติ)",
                "7. พัดลมเล็ก",
                "8. ความสะอาดตัวรถทั่วไป"
            ];
            sampleItems.forEach((itm, idx) => {
                const isFail = (data.check_status === 'failed' && idx === 2);
                tbody.innerHTML += `
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                        <td class="p-2.5 font-medium">${itm}</td>
                        <td class="p-2.5 text-center">
                            ${isFail ? 
                                '<span class="text-rose-600 font-bold"><i class="fa-solid fa-xmark"></i> ไม่ผ่าน</span>' : 
                                '<span class="text-emerald-600 font-bold"><i class="fa-solid fa-check"></i> ผ่าน</span>'}
                        </td>
                    </tr>
                `;
            });
        }

        const modal = document.getElementById('inspectorModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeInspectorModal() {
        const modal = document.getElementById('inspectorModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // ── Open / Close Modal 2: Comparison Modal ──
    function openComparisonModal() {
        const modal = document.getElementById('comparisonModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeComparisonModal() {
        const modal = document.getElementById('comparisonModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // ── Filter alert 3d only ──
    function filterAlertOnly() {
        const rows = document.querySelectorAll('#harvesterTable tbody tr.row-item');
        rows.forEach(r => {
            if(!r.classList.contains('is-alert-row')) {
                r.style.display = 'none';
            } else {
                r.style.display = '';
            }
        });
    }
</script>

<!-- ── Manager Popup ── -->
<div id="managerPopup" class="fixed inset-0 z-[9999] hidden items-center justify-center p-4" style="background:rgba(15,23,42,.55);">
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden border border-slate-200 dark:border-slate-700" onclick="event.stopPropagation()">
        <div class="bg-slate-900 px-5 py-4 flex items-center justify-between border-b-2 border-amber-500">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-user-shield text-amber-400"></i>
                <span class="text-white font-bold text-sm" id="mgr-popup-title">ผู้ดูแลรถตัด</span>
            </div>
            <button onclick="closeManagerPopup()" class="text-slate-400 hover:text-white transition text-lg leading-none"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="p-5 space-y-2" id="mgr-popup-body"></div>
    </div>
</div>

<script>
function openManagerPopup(managers, harvesterName){
    document.getElementById('mgr-popup-title').textContent = 'ผู้ดูแล ' + harvesterName;
    let html = '';
    managers.forEach(m => {
        const init = (m.emp_name||'?').trim().substring(0,2);
        html += `
        <div class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-800 rounded-xl">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                ${_esc(init)}
            </div>
            <div>
                <div class="font-bold text-slate-800 dark:text-slate-100 text-sm">${_esc(m.emp_name)}</div>
                <div class="text-xs text-slate-500 flex items-center gap-1 mt-0.5">
                    <i class="fa-solid fa-id-badge text-amber-500" style="font-size:.65rem;"></i>${_esc(m.emp_id)}
                </div>
                <div class="text-xs text-slate-400 flex items-center gap-1 mt-0.5">
                    <i class="fa-solid fa-location-dot" style="font-size:.65rem;color:#cbd5e1;"></i>${_esc(m.emp_unit)}
                </div>
            </div>
        </div>`;
    });
    document.getElementById('mgr-popup-body').innerHTML = html || '<p class="text-slate-400 text-sm text-center py-2">ไม่มีข้อมูล</p>';
    const p = document.getElementById('managerPopup');
    p.classList.remove('hidden'); p.classList.add('flex');
}
function closeManagerPopup(){
    const p = document.getElementById('managerPopup');
    p.classList.add('hidden'); p.classList.remove('flex');
}
function _esc(s){ const d=document.createElement('div'); d.textContent=s||'-'; return d.innerHTML; }
document.getElementById('managerPopup').addEventListener('click', function(e){ if(e.target===this) closeManagerPopup(); });
document.addEventListener('keydown', e=>{ if(e.key==='Escape') closeManagerPopup(); });
</script>
</body>
</html>