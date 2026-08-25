<?php
/**
 * export_harvester_excel.php — ส่งออกรายงานผลการตรวจเช็กรถตัดอ้อยเป็นไฟล์ Excel (.xls)
 * รองรับทั้งรายวันและเลือกช่วงวันที่ (Date Range)
 * TIS SMART FIELD - ฝ่ายไร่
 */
require_once 'config.php';
session_start();

if (!isset($_SESSION['emp_id'])) {
    die("Access Denied");
}

$crop_year        = $_GET['crop_year'] ?? ($_SESSION['crop_year'] ?? '69/70');
$filter_date      = trim($_GET['date'] ?? ($_GET['date_from'] ?? date('Y-m-d')));
$filter_date_end  = trim($_GET['date_end'] ?? ($_GET['date_to'] ?? $filter_date));
$filter_unit      = trim($_GET['unit'] ?? '');
$filter_harvester = trim($_GET['harvester'] ?? '');

// ตรวจสอบและสลับวันถ้าวันสิ้นสุดน้อยกว่าวันเริ่มต้น
if ($filter_date_end < $filter_date) {
    [$filter_date, $filter_date_end] = [$filter_date_end, $filter_date];
}
$is_range = ($filter_date !== $filter_date_end);

// ดึงรายการตรวจเช็ค 19 ข้อ
$all_cut_items = [];
try {
    $all_cut_items = $conn->query("SELECT * FROM check_items_cut ORDER BY item_id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// สร้าง WHERE เงื่อนไข
if ($is_range) {
    $where  = "WHERE cs.crop_year=:cy AND DATE(cs.checked_at) BETWEEN :dt AND :dt_end";
    $params = [':cy' => $crop_year, ':dt' => $filter_date, ':dt_end' => $filter_date_end];
} else {
    $where  = "WHERE cs.crop_year=:cy AND DATE(cs.checked_at)=:dt";
    $params = [':cy' => $crop_year, ':dt' => $filter_date];
}

if ($filter_unit !== '') {
    $where .= " AND e.emp_unit=:unit";
    $params[':unit'] = $filter_unit;
}
if ($filter_harvester !== '') {
    $where .= " AND (cs.harvester_number = :hn_exact OR cs.harvester_number = :hn_full OR cs.harvester_number = :hn_short OR cs.harvester_number LIKE :hn_like)";
    $h_short = str_replace('รถตัดเบอร์ ', '', $filter_harvester);
    $h_full  = !str_contains($filter_harvester, 'รถตัดเบอร์') ? "รถตัดเบอร์ " . $filter_harvester : $filter_harvester;
    $params[':hn_exact'] = $filter_harvester;
    $params[':hn_full']  = $h_full;
    $params[':hn_short'] = $h_short;
    $params[':hn_like']  = '%' . $h_short . '%';
}

$sql = "
    SELECT cs.*, e.emp_name, e.emp_unit,
           COUNT(cr.result_id) AS total_items,
           SUM(cr.pass) AS pass_count
    FROM check_sessions cs
    JOIN employee e ON cs.emp_id=e.emp_id
    LEFT JOIN check_results cr ON cs.session_id=cr.session_id
    $where
    GROUP BY cs.session_id
    ORDER BY cs.checked_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงรายละเอียดข้อที่ไม่ผ่าน
$stmt_detail = $conn->prepare("
    SELECT cr.item_id, cr.pass, cr.note, ci.item_name_cut 
    FROM check_results cr
    LEFT JOIN check_items_cut ci ON cr.item_id = ci.item_id
    WHERE cr.session_id = :sid AND cr.pass = 0
");

foreach ($rows as &$r) {
    $stmt_detail->execute([':sid' => $r['session_id']]);
    $r['fails'] = $stmt_detail->fetchAll(PDO::FETCH_ASSOC);
}
unset($r);

// คำนวณสถิติ
$total_checked = count($rows);
$total_pass = 0;
$total_fail = 0;
foreach ($rows as $r) {
    $t = (int)($r['total_items'] ?? 0);
    $p = (int)($r['pass_count'] ?? 0);
    if ($t > 0 && $p == $t) $total_pass++;
    elseif ($t > 0) $total_fail++;
}

// ข้อความแสดงช่วงเวลา
$period_text = $is_range 
    ? "ช่วงวันที่ " . date('d/m/Y', strtotime($filter_date)) . " ถึง " . date('d/m/Y', strtotime($filter_date_end))
    : "ประจำวันที่ " . date('d/m/Y', strtotime($filter_date));

// ตั้งค่า Header สำหรับดาวน์โหลด Excel
$filename = "Harvester_Report_" . $filter_date . ($is_range ? "_to_" . $filter_date_end : "") . "_" . date('His') . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// UTF-8 BOM
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
    th { background-color: #1e293b; color: #ffffff; font-weight: bold; text-align: center; }
    .header-box { text-align: center; margin-bottom: 20px; font-size: 16px; font-weight: bold; }
    .stat-box { margin-bottom: 15px; font-size: 13px; }
    .pass-tag { color: #16a34a; font-weight: bold; }
    .fail-tag { color: #dc2626; font-weight: bold; }
    .text-center { text-align: center; }
    .text-left { text-align: left; }
    .bg-summary { background-color: #f1f5f9; font-weight: bold; }
    .fail-note { color: #b91c1c; font-size: 11px; }
</style>
</head>
<body>

<div class="header-box">
    <h2>บริษัท น้ำตาลไทยเอกลักษณ์ จำกัด (ฝ่ายไร่)</h2>
    <h3>รายงานผลการตรวจเช็กรถตัดอ้อย (TIS SMART FIELD)</h3>
    <p><?php echo $period_text; ?> | ปีการผลิต: <?php echo htmlspecialchars($crop_year); ?> | หน่วยส่งเสริม: <?php echo htmlspecialchars($filter_unit ?: 'ทุกหน่วยงาน'); ?></p>
</div>

<table style="margin-bottom: 15px;">
    <tr class="bg-summary">
        <td style="padding: 10px;">บันทึกทั้งหมด: <strong><?php echo $total_checked; ?></strong> คัน</td>
        <td style="padding: 10px; color: #16a34a;">ผ่านเกณฑ์สมบูรณ์: <strong><?php echo $total_pass; ?></strong> คัน</td>
        <td style="padding: 10px; color: #dc2626;">พบจุดบกพร่อง / ต้องแก้ไข: <strong><?php echo $total_fail; ?></strong> คัน</td>
        <td style="padding: 10px;">วันที่ส่งออกข้อมูล: <?php echo date('d/m/Y H:i:s'); ?> น.</td>
    </tr>
</table>

<table>
    <thead>
        <tr>
            <th style="width: 40px;">#</th>
            <th style="width: 120px;">วันที่/เวลาตรวจ</th>
            <th style="width: 160px;">ผู้บันทึก (พนักงาน)</th>
            <th style="width: 120px;">หน่วยส่งเสริม</th>
            <th style="width: 120px;">เบอร์รถตัด</th>
            <th style="width: 120px;">สภาพแปลง</th>
            <th style="width: 110px;">ผลการตรวจ</th>
            <th style="width: 90px;">คะแนนตรวจ</th>
            <th style="width: 250px;">รายการที่ไม่ผ่าน / หมายเหตุ</th>
            <th style="width: 140px;">พิกัด GPS</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($rows)): ?>
        <tr>
            <td colspan="10" class="text-center" style="padding: 20px; color: #64748b;">ไม่พบข้อมูลการตรวจเช็กรถตัดในเงื่อนไขที่เลือก</td>
        </tr>
        <?php else: ?>
        <?php foreach ($rows as $i => $r): 
            $is_pass = ((int)$r['total_items'] > 0 && (int)$r['pass_count'] == (int)$r['total_items']);
            $score_text = ((int)$r['pass_count']) . '/' . ((int)$r['total_items']);
            $gps_text = (!empty($r['latitude']) && !empty($r['longitude'])) ? $r['latitude'] . ', ' . $r['longitude'] : '-';
        ?>
        <tr style="<?php echo !$is_pass ? 'background-color: #fff1f2;' : ''; ?>">
            <td class="text-center"><?php echo $i + 1; ?></td>
            <td class="text-center"><?php echo date('d/m/Y H:i', strtotime($r['checked_at'])); ?> น.</td>
            <td class="text-left"><?php echo htmlspecialchars($r['emp_name']); ?></td>
            <td class="text-center"><?php echo htmlspecialchars($r['emp_unit'] ?: '-'); ?></td>
            <td class="text-center" style="font-weight: bold;"><?php echo htmlspecialchars($r['harvester_number']); ?></td>
            <td class="text-center"><?php echo htmlspecialchars($r['field_condition'] ?: 'ปกติ'); ?></td>
            <td class="text-center">
                <?php if ($is_pass): ?>
                    <span class="pass-tag">✓ ผ่านทั้งหมด</span>
                <?php else: ?>
                    <span class="fail-tag">✗ พบจุดบกพร่อง</span>
                <?php endif; ?>
            </td>
            <td class="text-center" style="font-weight: bold;"><?php echo $score_text; ?></td>
            <td class="text-left">
                <?php if (!empty($r['fails'])): ?>
                    <ul style="margin: 0; padding-left: 15px;" class="fail-note">
                        <?php foreach ($r['fails'] as $f): ?>
                            <li><?php echo htmlspecialchars($f['item_name_cut']); ?><?php echo !empty($f['note']) ? ' (' . htmlspecialchars($f['note']) . ')' : ''; ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <span style="color: #64748b;">- สมบูรณ์ -</span>
                <?php endif; ?>
            </td>
            <td class="text-center" style="font-family: monospace;"><?php echo $gps_text; ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
