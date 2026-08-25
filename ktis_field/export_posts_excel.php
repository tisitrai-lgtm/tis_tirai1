<?php
/**
 * export_posts_excel.php — ส่งออกรายงานปัญหาอ้อยสกปรก / การแจ้งเหตุภาคสนามเป็นไฟล์ Excel (.xls)
 * รองรับทั้งรายวันและเลือกช่วงวันที่ (Date Range)
 * TIS SMART FIELD - ฝ่ายไร่
 */
require_once 'config.php';
session_start();

if (!isset($_SESSION['emp_id'])) {
    die("Access Denied");
}

$crop_year       = $_GET['crop_year'] ?? ($_SESSION['crop_year'] ?? '69/70');
$filter_date     = trim($_GET['date'] ?? ($_GET['search_date'] ?? ($_GET['date_from'] ?? '')));
$filter_date_end = trim($_GET['date_end'] ?? ($_GET['date_to'] ?? $filter_date));
$status_tab      = trim($_GET['status_tab'] ?? 'all');
$target_unit     = trim($_GET['unit'] ?? ($_GET['target_unit'] ?? ''));

if ($filter_date !== '' && $filter_date_end !== '' && $filter_date_end < $filter_date) {
    [$filter_date, $filter_date_end] = [$filter_date_end, $filter_date];
}
$is_range = ($filter_date !== '' && $filter_date_end !== '' && $filter_date !== $filter_date_end);

$where = [];
$params = [];

if ($crop_year !== 'all') {
    $where[] = "p.crop_year = :crop_year";
    $params[':crop_year'] = $crop_year;
}

if ($is_range) {
    $where[] = "DATE(p.created_at) BETWEEN :dt AND :dt_end";
    $params[':dt'] = $filter_date;
    $params[':dt_end'] = $filter_date_end;
} elseif ($filter_date !== '') {
    $where[] = "DATE(p.created_at) = :dt";
    $params[':dt'] = $filter_date;
}

if ($status_tab === 'pending') {
    $where[] = "p.job_status = 'pending'";
} elseif ($status_tab === 'success') {
    $where[] = "p.job_status = 'success'";
}

if (!empty($target_unit)) {
    $where[] = "p.target_unit = :t_unit";
    $params[':t_unit'] = $target_unit;
}

$where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$sql = "
    SELECT p.*, e.emp_name, e.emp_unit as creator_unit,
           (SELECT COUNT(*) FROM replies r WHERE r.post_id = p.post_id) as comment_count
    FROM posts p
    JOIN employee e ON p.emp_id = e.emp_id
    $where_sql
    ORDER BY p.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// สถิติ
$total_posts = count($rows);
$total_pending = 0;
$total_success = 0;
foreach ($rows as $r) {
    if ($r['job_status'] === 'success') $total_success++;
    else $total_pending++;
}

// ข้อความแสดงช่วงเวลา
if ($is_range) {
    $period_text = "ช่วงวันที่ " . date('d/m/Y', strtotime($filter_date)) . " ถึง " . date('d/m/Y', strtotime($filter_date_end));
} elseif ($filter_date !== '') {
    $period_text = "ประจำวันที่ " . date('d/m/Y', strtotime($filter_date));
} else {
    $period_text = "ทุกช่วงเวลา";
}

// ตั้งค่า Header สำหรับดาวน์โหลด Excel
$filename = "Field_Problem_Report_" . ($filter_date ?: 'all') . ($is_range ? "_to_" . $filter_date_end : "") . "_" . date('His') . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

echo "\xEF\xBB\xBF";
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: 'Sarabun', Tahoma, sans-serif; font-size: 13px; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #94a3b8; padding: 8px 10px; vertical-align: middle; }
    th { background-color: #be123c; color: #ffffff; font-weight: bold; text-align: center; }
    .header-box { text-align: center; margin-bottom: 20px; font-size: 16px; font-weight: bold; }
    .stat-box { margin-bottom: 15px; font-size: 13px; }
    .tag-pending { color: #dc2626; font-weight: bold; }
    .tag-success { color: #16a34a; font-weight: bold; }
    .text-center { text-align: center; }
    .text-left { text-align: left; }
    .bg-summary { background-color: #f1f5f9; font-weight: bold; }
</style>
</head>
<body>

<div class="header-box">
    <h2>บริษัท น้ำตาลไทยเอกลักษณ์ จำกัด (ฝ่ายไร่)</h2>
    <h3>รายงานปัญหาอ้อยสกปรกและการแจ้งเหตุภาคสนาม (TIS SMART FIELD)</h3>
    <p>
        <?php echo $period_text; ?> | 
        ปีการผลิต: <?php echo htmlspecialchars($crop_year === 'all' ? 'ทั้งหมด' : $crop_year); ?> | 
        หน่วยส่งเสริม: <?php echo htmlspecialchars($target_unit ?: 'ทุกหน่วยงาน'); ?> |
        สถานะ: <?php echo $status_tab === 'pending' ? 'รอดำเนินการ' : ($status_tab === 'success' ? 'แก้ไขแล้ว' : 'ทั้งหมด'); ?>
    </p>
</div>

<table style="margin-bottom: 15px;">
    <tr class="bg-summary">
        <td style="padding: 10px;">รายการแจ้งเหตุทั้งหมด: <strong><?php echo $total_posts; ?></strong> รายการ</td>
        <td style="padding: 10px; color: #dc2626;">รอดำเนินการแก้ไข: <strong><?php echo $total_pending; ?></strong> รายการ</td>
        <td style="padding: 10px; color: #16a34a;">แก้ไขเสร็จสิ้น: <strong><?php echo $total_success; ?></strong> รายการ</td>
        <td style="padding: 10px;">วันที่ส่งออกข้อมูล: <?php echo date('d/m/Y H:i:s'); ?> น.</td>
    </tr>
</table>

<table>
    <thead>
        <tr>
            <th style="width: 40px;">#</th>
            <th style="width: 120px;">วันที่/เวลาแจ้ง</th>
            <th style="width: 150px;">ผู้แจ้งเหตุ</th>
            <th style="width: 130px;">หน่วยที่รับผิดชอบ</th>
            <th style="width: 120px;">ทะเบียนรถบรรทุก</th>
            <th style="width: 110px;">เบอร์รถตัด</th>
            <th style="width: 140px;">ปัญหาที่ 1</th>
            <th style="width: 140px;">ปัญหาที่ 2</th>
            <th style="width: 140px;">ปัญหาที่ 3</th>
            <th style="width: 220px;">รายละเอียดเพิ่มเติม</th>
            <th style="width: 110px;">สถานะ</th>
            <th style="width: 80px;">ตอบกลับ</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($rows)): ?>
        <tr>
            <td colspan="12" class="text-center" style="padding: 20px; color: #64748b;">ไม่พบรายการแจ้งเหตุในเงื่อนไขที่เลือก</td>
        </tr>
        <?php else: ?>
        <?php foreach ($rows as $i => $r): 
            $is_done = ($r['job_status'] === 'success');
        ?>
        <tr style="<?php echo !$is_done ? 'background-color: #fff1f2;' : ''; ?>">
            <td class="text-center"><?php echo $i + 1; ?></td>
            <td class="text-center"><?php echo date('d/m/Y H:i', strtotime($r['created_at'])); ?> น.</td>
            <td class="text-left"><?php echo htmlspecialchars($r['emp_name']); ?> (<?php echo htmlspecialchars($r['creator_unit'] ?: '-'); ?>)</td>
            <td class="text-center" style="font-weight: bold;"><?php echo htmlspecialchars($r['target_unit']); ?></td>
            <td class="text-center"><?php echo htmlspecialchars($r['truck_number'] ?: '-'); ?></td>
            <td class="text-center" style="font-weight: bold;"><?php echo htmlspecialchars($r['harvester_number'] ?: '-'); ?></td>
            <td class="text-left"><?php echo htmlspecialchars($r['problem_detail'] ?? ($r['problem_1'] ?? '-')); ?></td>
            <td class="text-left"><?php echo htmlspecialchars($r['problem_detail_2'] ?? ($r['problem_2'] ?? '-')); ?></td>
            <td class="text-left"><?php echo htmlspecialchars($r['problem_detail_3'] ?? ($r['problem_3'] ?? '-')); ?></td>
            <td class="text-left"><?php echo htmlspecialchars($r['post_text'] ?? ($r['detail'] ?? '-')); ?></td>
            <td class="text-center">
                <?php if ($is_done): ?>
                    <span class="tag-success">✓ แก้ไขแล้ว</span>
                <?php else: ?>
                    <span class="tag-pending">⏳ รอดำเนินการ</span>
                <?php endif; ?>
            </td>
            <td class="text-center"><?php echo (int)$r['comment_count']; ?> ข้อความ</td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
