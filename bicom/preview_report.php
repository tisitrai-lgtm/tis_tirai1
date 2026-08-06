<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['statn_code'])) { exit; }

// --- ตัวแปลงรหัสหน่วยเป็นชื่อภาษาไทย ---
$statn_names = [
    "01" => "พรหมพิราม", "14" => "พรหมพิราม", "0901" => "พรหมพิราม", "214" => "พรหมพิราม",
    "02" => "พิษณุโลก", "217" => "เมือง", "213" => "วัดโบสถ์", "203" => "บ่อทอง",
    "202" => "น้ำอ่าง", "204" => "ชาติตระการ", "205" => "หนองตม", "111" => "บางขลัง",
    "110" => "ทุ่งเสลี่ยม", "115" => "ศรีสัชนาลัย", "116" => "สวรรคโลก", "102" => "ชัยคีรี",
    "112" => "ศรีนครเหนือ", "101" => "ศรีนครใต้", "206" => "พิชัย", "109" => "ศรีสำโรง",
    "113" => "ตลิ่งชัน", "106" => "ท่าชัย", "114" => "เขาหลวง", "108" => "คีรีมาศ",
    "121" => "ท่าชัยใต้", "107" => "ท่าชัยเหนือ", "219" => "น้ำปาด", "220" => "แพร่", "117" => "ตาก"
];

$statn_code = $_SESSION['statn_code'];
$statn_name = $statn_names[$statn_code] ?? "หน่วยรหัส ($statn_code)";

$start_date = $_GET['start_date'] ?? '';
$end_date   = $_GET['end_date'] ?? '';

$sql = "SELECT * FROM conversion_logs WHERE STATN_CODE = ?";
$params = [$statn_code];
if ($start_date && $end_date) {
    $sql .= " AND WORK_DATE BETWEEN ? AND ?";
    $params[] = $start_date; 
    $params[] = $end_date;
}
$sql .= " ORDER BY FARMR_CODE ASC, WEIGH_DOCC ASC";


$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$g_weighted_ccs_sum = 0;
$g_total_weight = 0; 
$g_fresh_cane = 0; 
$g_burnt_cane = 0;
$g_truck_count = count($data);
$g_mcut_count = 0; 
$g_hol_count = 0; 
$ccs_sum = 0;

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายงานการรับอ้อย - <?= htmlspecialchars($statn_name) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #525659; padding: 15px; }
        .excel-page { background: white; width: 100%; max-width: 2500px; margin: 0 auto; padding: 20px; box-shadow: 0 0 15px rgba(0,0,0,0.3); overflow-x: auto; }
        .report-header { text-align: center; margin-bottom: 10px; }
        .excel-table { width: 100%; border-collapse: collapse; font-size: 10px; border: 1px solid #000; }
        .excel-table th, .excel-table td { border: 0.5px solid #000; padding: 2px 3px; }
        .excel-table th { background: #f2f2f2; text-align: center; vertical-align: middle; height: 35px; }
        .summary-contract { background-color: #ffffff; font-weight: bold; }
        .summary-zone { background-color: #ffffff; font-weight: bold; border-top: 2px solid #000; }
        .text-red { color: #d93025; font-weight: bold; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .controls { position: fixed; top: 10px; right: 10px; z-index: 100; }
        @media print { .controls { display: none; } }
        /* กำหนดขนาดช่องสัญญาให้เท่ากัน */
        .col-contract-id { width: 60px; }
        .col-contract-name { width: 80px; }
    </style>
</head>
<body>

<div class="controls">
    <a href="index.php" class="btn btn-dark btn-sm">กลับหน้าหลัก</a>
    <a href="export_excel.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="btn btn-success btn-sm">ส่งออกไฟล์ Excel</a>
</div>

<div class="excel-page">
    <div class="report-header">
        <div style="font-size: 18px; font-weight: bold;">บริษัท น้ำตาลไทยเอกลักษณ์ จำกัด</div>
        <div style="font-size: 13px;">
            รายงานการรับอ้อยประจำวันที่ <?= ($start_date) ? date('d/m/Y', strtotime($start_date)) : '-' ?> 
            ถึงวันที่ <?= ($end_date) ? date('d/m/Y', strtotime($end_date)) : '-' ?> 
            ตามพื้นที่ปลูกอ้อย <strong>หน่วย <?= htmlspecialchars($statn_name) ?></strong>
        </div>
    </div>

    <table class="excel-table">
        <thead>
            <tr>
                <th rowspan="2">เลขสัญญา</th>
                <th rowspan="2" style="width: 100px;">ชื่อ-นามสกุล</th>
                <th rowspan="2">ใบรับอ้อย</th>
                <th rowspan="2"style="width: 80px;">ทะเบียนรถ</th>
                <th rowspan="2">รถตัด</th>
                <th rowspan="2">รถคีบ</th>
                <th rowspan="2">ตันอ้อย</th>
                <th rowspan="2">ค่าบรรทุก</th>
                <th rowspan="2">ค่าขึ้น</th>
                <th rowspan="2">ค่าตัด</th>
                <th rowspan="2">CCS</th>
                <th rowspan="2">PURITY</th>
                <th rowspan="2">ประเภทอ้อย</th>
                <th rowspan="2">ตัดราคาอ้อยไฟไหม้</th>
                <th rowspan="2">เลขกำกับแปลง</th>
                <th rowspan="2">เลข ID</th>
                <th rowspan="2">อก.</th>
                <th rowspan="2" style="width: 100px;">เจ้าของรถ</th>

                <th colspan="2">สัญญาขึ้นอ้อย</th>
                <th colspan="2">สัญญาตัดอ้อย</th>

                <th rowspan="2">วันที่ตัด</th>
                <th rowspan="2">เวลา</th>
                <th colspan="2">เวลาอยู่ในโรงงาน</th>
                <th colspan="2">รวมเวลาเข้าหีบ</th>
                <th rowspan="2">หมายเหตุ</th>
            </tr>
            <tr>
                <th class="col-contract-id">ไอดี</th><th class="col-contract-name">ชื่อ</th>
                <th class="col-contract-id">ไอดี</th><th class="col-contract-name">ชื่อ</th>
                
                <th>ชั่วโมง</th><th>นาที</th><th>ชั่วโมง</th><th>นาที</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if($data): 
                $curr_contract = null;
                $c_weight = 0; $c_count = 0; $c_fresh = 0; $c_burnt = 0;

                foreach($data as $row): 
                    if ($curr_contract !== null && $curr_contract !== $row['FARMR_CODE']): 
            ?>
                <tr class="summary-contract">
                    <td colspan="2" class="text-center">รวมสัญญา</td>
                    <td class="text-center"><?= $c_count ?></td>
                    <td class="text-center">คัน</td>
                    <td colspan="2"></td>
                    <td class="text-end"><?= number_format($c_weight, 3) ?> ตัน</td>
                    <td class="text-center">อ้อยสด</td>
                    <td class="text-end"><?= number_format($c_burnt, 2) ?> ตัน</td>
                    <td class="text-center">ไฟไหม้</td>
                    <td class="text-end"><?= number_format($c_fresh, 2) ?> ตัน</td>
                    <td colspan="17"></td> </tr>
            <?php 
                        $c_weight = 0; $c_count = 0; $c_fresh = 0; $c_burnt = 0;
                    endif;

                    $curr_contract = $row['FARMR_CODE'];
                    $w = (float)$row['WEIGH_CANE'];
                    $c_weight += $w; $g_total_weight += $w;
                    $c_count++;
                    $g_weighted_ccs_sum += ((float)$row['WEIGH_CANE'] * (float)$row['SWEET_CANE']);
                    
                    if(trim($row['CANE_TYPE']) == 'อ้อยสด' || $row['CANE_TYPE'] == '0') { 
                        $c_fresh += $w; $g_fresh_cane += $w; 
                    } else { 
                        $c_burnt += $w; $g_burnt_cane += $w; 
                    }

                    if($row['CANE_TCCUT'] > 0) $g_mcut_count++;
                    if($row['CANE_TCHOL'] > 0) $g_hol_count++;
            ?>
                <tr>
                    <td class="text-center"><?= $row['FARMR_CODE'] ?></td>
                    <td><?= $row['FARMR_NAME'] ?></td>
                    <td class="text-center"><?= str_pad($row['WEIGH_DOCC'], 6, "0", STR_PAD_LEFT) ?></td>
                    <td class="text-center"><?= $row['TRUCK_CODE'] ?></td>
                    <td class="text-center text-red"><?= $row['CANE_TCCUT'] ?></td>
                    <td class="text-center"><?= $row['CANE_TCHOL'] ?></td>
                    <td class="text-end fw-bold"><?= number_format($w, 3) ?></td>
                    <td class="text-end"><?= number_format($row['TRUCK_RATE'], 0) ?></td>
                    <td class="text-end"><?= number_format($row['TRUCK_UP'], 0) ?></td>
                    <td class="text-end"><?= number_format($row['TRUCK_MCUT'], 0) ?></td>
                    <td class="text-center"><?= number_format($row['SWEET_CANE'], 2) ?></td>
                    <td class="text-center"><?= number_format($row['PURI_CANE'], 2) ?></td>
                    <td class="text-center"><?= $row['CANE_TYPE'] ?></td>
                    <td class="text-center"><?= $row['FLAG_CUT1'] ?></td>
                    <td class="text-center"><?= $row['LAND_NUMB'] ?></td>
                    <td class="text-center text-red"><?= $row['LAND_ID'] ?></td>
                    <td class="text-center"><?= str_pad($row['FARMR_TRCK'], 4, "0", STR_PAD_LEFT) ?></td>
                    <td><?= $row['FARMR_TNAM'] ?></td>
                    
                    <td class="text-center"><?= $row['FARMR_UP'] ?></td>
                    <td><?= $row['FARMR_UNAM'] ?></td>
                    
                    <td class="text-center"><?= $row['FARMR_CUT'] ?></td>
                    <td><?= $row['FARMR_CNAM'] ?></td>

                    <td class="text-center"><?= date('d/m/Y', strtotime($row['DATE_CUT'])) ?></td>
                    <td class="text-center text-red"><?= $row['TIME_CUT'] ?></td>
                    <td class="text-center"><?= $row['TIME_HFAC'] ?></td>
                    <td class="text-center"><?= $row['TIME_MFAC'] ?></td>
                    <td class="text-center"><?= $row['TIME_HTOTL'] ?></td>
                    <td class="text-center"><?= $row['TIME_MTOTL'] ?></td>
                    <td><?= $row['FLAG_ID'] ?></td> 
                </tr>
            <?php endforeach; ?>

            <tr class="summary-contract">
                <td colspan="2" class="text-center">รวมสัญญา</td>
                <td class="text-center"><?= $c_count ?></td>
                <td class="text-center">คัน</td>
                <td colspan="2"></td>
                <td class="text-end"><?= number_format($c_weight, 3) ?> ตัน</td>
                <td class="text-center">อ้อยสด</td>
                <td class="text-end"><?= number_format($c_burnt, 2) ?> ตัน</td>
                <td class="text-center">ไฟไหม้</td>
                <td class="text-end"><?= number_format($c_fresh, 2) ?> ตัน</td>
                <td colspan="17"></td>
            </tr>

            <tr class="summary-zone">
                <td colspan="2" class="text-center">รวมหน่วย <?= htmlspecialchars($statn_name) ?></td>
                <td class="text-center"><?= $g_truck_count ?></td>
                <td class="text-center">คัน</td>
                <td colspan="2" class="text-end">ตันอ้อย</td>
                <td class="text-end"><?= number_format($g_total_weight, 3) ?></td>
                <td class="text-center">ตัน</td>
                <td class="text-center">รถตัด</td>
                <td class="text-center"><?= $g_mcut_count ?></td>
                <td class="text-center">คัน</td>
                <td class="text-center">รถคีบ</td>
                <td class="text-center"><?= $g_hol_count ?></td>
                <td class="text-center">คัน</td>
                <td class="text-center">CCS</td>
                <td class="text-center"><?= $g_total_weight > 0 ? number_format($g_weighted_ccs_sum / $g_total_weight, 2) : '0.00' ?></td>
                <td class="text-center">อ้อยสด</td>
                <td class="text-end"><?= number_format($g_burnt_cane, 2) ?></td>
                <td class="text-center">ตัน</td>
                <td class="text-center">ไฟไหม้</td>
                <td class="text-end"><?= number_format($g_fresh_cane, 2) ?></td>
                <td colspan="8" class="text-center">ตัน</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>