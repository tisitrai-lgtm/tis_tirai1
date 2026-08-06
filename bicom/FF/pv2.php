<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['statn_code'])) { exit; }

// 1. ตั้งค่า Header สำหรับดาวน์โหลด Excel
$file_name = "report_cane_final_" . date('Ymd_His') . ".xls";
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$file_name\"");
header("Pragma: no-cache");
header("Expires: 0");

$statn_code = $_SESSION['statn_code'];
$statn_name = $_SESSION['statn_name'] ?? $_SESSION['statn_code'] ?? "ไม่ระบุหน่วย";
$start_date = $_GET['start_date'] ?? '';
$end_date   = $_GET['end_date'] ?? '';

// 2. Query ข้อมูล
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
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-type" content="text/html;charset=utf-8" />
    <style>
        table { border-collapse: collapse; }
        td, th { 
            border: 0.5pt solid #D3D3D3; 
            font-family: 'Sarabun', sans-serif; 
            font-size: 10pt;
            padding: 2px;
        }
        /* กำหนดให้ Excel มองคอลัมน์นี้เป็น Text เพื่อไม่ให้เลข 0 ข้างหน้าหาย */
        .text-mode { mso-number-format:"\@"; }
        .text-red { color: #FF0000; }
        .text-bold { font-weight: bold; }
    </style>
</head>
<body>
    <table>
        <tr>
            <?php for($i=0; $i<12; $i++) echo "<td></td>"; ?>
            <td colspan="5" align="center" class="text-bold" style="font-size: 12pt;">บริษัท น้ำตาลไทยเอกลักษณ์ จำกัด</td>
        </tr>
        <tr>
            <?php for($i=0; $i<8; $i++) echo "<td></td>"; ?>
            <td colspan="2" align="right">รายงานการรับอ้อยประจำวันที่</td>
            <td></td>
            <td align="center"><?= ($start_date) ? date('d/m/Y', strtotime($start_date)) : '-' ?></td>
            <td align="center">ถึงวันที่</td>
            <td></td>
            <td align="center"><?= ($end_date) ? date('d/m/Y', strtotime($end_date)) : '-' ?></td>
            <td></td>
            <td colspan="2" align="right">ตามพื้นที่ปลูกอ้อย หน่วย</td>
            <td colspan="2" align="left" class="text-bold"><?= $statn_name ?></td>
        </tr>

        <tr class="text-bold" style="background-color: #f2f2f2;">
            <th rowspan="2">เลขสัญญา</th>
            <th rowspan="2">ชื่อ-นามสกุล</th>
            <th rowspan="2">ใบรับอ้อย</th>
            <th rowspan="2">ทะเบียนรถ</th>
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
            <th rowspan="2">เจ้าของรถ</th>

            <th colspan="2">สัญญาขึ้นอ้อย</th>
            <th colspan="2">สัญญาตัดอ้อย</th>

            <th rowspan="2">วันที่ตัด</th>
            <th rowspan="2">เวลา</th>
            <th colspan="2">เวลาอยู่ในโรงงาน</th>
            <th colspan="2">รวมเวลาเข้าหีบ</th>
            <th rowspan="2">หมายเหตุ</th>
        </tr>
        <tr class="text-bold" style="background-color: #f2f2f2;">
            <th>ไอดี</th><th>ชื่อ</th>
            <th>ไอดี</th><th>ชื่อ</th>
            <th>ชั่วโมง</th><th>นาที</th><th>ชั่วโมง</th><th>นาที</th>
        </tr>

        <?php 
        if($data): 
            $curr_contract = null;
            $c_weight = 0; $c_count = 0; $c_fresh = 0; $c_burnt = 0;
            $g_total_weight = 0; $g_fresh_cane = 0; $g_burnt_cane = 0;
            $g_mcut_count = 0; $g_hol_count = 0; $ccs_sum = 0;

            foreach($data as $row): 
                // รวมยอดรายสัญญา
                if ($curr_contract !== null && $curr_contract !== $row['FARMR_CODE']): 
        ?>
            <tr class="text-bold" style="background-color: #f9f9f9;">
                <td colspan="2" align="right">รวมสัญญา</td>
                <td align="center"><?= $c_count ?></td>
                <td align="center">คัน</td>
                <td colspan="2"></td>
                <td align="right"><?= number_format($c_weight, 3) ?></td>
                <td align="center">ตัน</td>
                <td align="center">อ้อยสด</td>
                <td align="center"><?= number_format($c_burnt, 2) ?></td>
                <td align="center">ตัน</td>
                <td align="center">ไฟไหม้</td>
                <td align="right"><?= number_format($c_fresh, 2) ?></td>
                <td align="center">ตัน</td>
                <td colspan="15"></td>
            </tr>
        <?php 
                    $c_weight = 0; $c_count = 0; $c_fresh = 0; $c_burnt = 0;
                endif;

                $curr_contract = $row['FARMR_CODE'];
                $w = (float)$row['WEIGH_CANE'];
                $c_weight += $w; $g_total_weight += $w;
                $c_count++; $ccs_sum += (float)$row['SWEET_CANE'];
                
                if($row['CANE_TYPE'] == 'อ้อยสด' || $row['CANE_TYPE'] == '0') { 
                    $c_fresh += $w; $g_fresh_cane += $w; 
                } else { 
                    $c_burnt += $w; $g_burnt_cane += $w; 
                }
                
                if($row['CANE_TCCUT'] > 0) $g_mcut_count++;
                if($row['CANE_TCHOL'] > 0) $g_hol_count++;

                // --- ส่วนที่แก้ไข: เช็กค่าว่างก่อนเติม 0 ---
                $docc_formatted = (!empty($row['WEIGH_DOCC'])) ? str_pad($row['WEIGH_DOCC'], 6, "0", STR_PAD_LEFT) : "";
                $trck_formatted = (!empty($row['FARMR_TRCK'])) ? str_pad($row['FARMR_TRCK'], 4, "0", STR_PAD_LEFT) : "";
                $up_formatted   = (!empty($row['FARMR_UP']))   ? str_pad($row['FARMR_UP'], 6, "0", STR_PAD_LEFT)   : "";
                $cut_formatted  = (!empty($row['FARMR_CUT']))  ? str_pad($row['FARMR_CUT'], 6, "0", STR_PAD_LEFT)  : "";
        ?>
            <tr>
                <td align="center" class="text-mode"><?= $row['FARMR_CODE'] ?></td>
                <td><?= $row['FARMR_NAME'] ?></td>
                <td align="center" class="text-mode"><?= $docc_formatted ?></td>
                <td align="center"><?= $row['TRUCK_CODE'] ?></td>
                <td align="center" class="text-red"><?= $row['CANE_TCCUT'] ?></td>
                <td align="center"><?= $row['CANE_TCHOL'] ?></td>
                <td align="right"><?= number_format($w, 3) ?></td>
                <td align="right"><?= number_format((float)$row['TRUCK_RATE'], 0) ?></td>
                <td align="right"><?= number_format((float)$row['TRUCK_UP'], 0) ?></td>
                <td align="right"><?= number_format((float)$row['TRUCK_MCUT'], 0) ?></td>
                <td align="center"><?= number_format((float)$row['SWEET_CANE'], 2) ?></td>
                <td align="center"><?= number_format((float)$row['PURI_CANE'], 2) ?></td>
                <td align="center"><?= $row['CANE_TYPE'] ?></td>
                <td align="center"><?= $row['FLAG_CUT1'] ?></td>
                <td align="center"><?= $row['LAND_NUMB'] ?></td>
                <td align="center" class="text-red text-mode"><?= $row['LAND_ID'] ?></td>
                <td align="center" class="text-mode"><?= $trck_formatted ?></td>
                <td><?= $row['FARMR_TNAM'] ?></td>

                <td align="center" class="text-mode"><?= $up_formatted ?></td>
                <td><?= $row['FARMR_UNAM'] ?></td>

                <td align="center" class="text-mode"><?= $cut_formatted ?></td>
                <td><?= $row['FARMR_CNAM'] ?></td>

                <td align="center"><?= !empty($row['DATE_CUT']) ? date('d/m/Y', strtotime($row['DATE_CUT'])) : '-' ?></td>
                <td align="center" class="text-red"><?= $row['TIME_CUT'] ?></td>
                <td align="center"><?= $row['TIME_HFAC'] ?></td>
                <td align="center"><?= $row['TIME_MFAC'] ?></td>
                <td align="center"><?= $row['TIME_HTOTL'] ?></td>
                <td align="center"><?= $row['TIME_MTOTL'] ?></td>
                <td><?= $row['FLAG_ID'] ?></td>
            </tr>
        <?php endforeach; ?>

        <tr class="text-bold" style="background-color: #f9f9f9;">
            <td colspan="2" align="right">รวมสัญญา</td>
            <td align="center"><?= $c_count ?></td>
            <td align="center">คัน</td>
            <td colspan="2"></td>
            <td align="right"><?= number_format($c_weight, 3) ?></td>
            <td align="center">ตัน</td>
            <td align="center">อ้อยสด</td>
            <td align="center"><?= number_format($c_burnt, 2) ?></td>
            <td align="center">ตัน</td>
            <td align="center">ไฟไหม้</td>
            <td align="right"><?= number_format($c_fresh, 2) ?></td>
            <td align="center">ตัน</td>
            <td colspan="15"></td>
        </tr>

        <tr class="text-bold">
            <td colspan="2" align="center">รวมเขต <?= $statn_name ?></td>
            <td align="center"><?= count($data) ?></td>
            <td align="center">คัน</td>
            <td colspan="2" align="right">ตันอ้อย</td>
            <td align="right"><?= number_format($g_total_weight, 3) ?></td>
            <td align="center">ตัน</td>
            <td align="center">รถตัด</td>
            <td align="center"><?= $g_mcut_count ?></td>
            <td align="center">คัน</td>
            <td align="center">รถคีบ</td>
            <td align="center"><?= $g_hol_count ?></td>
            <td align="center">คัน</td>
            <td align="center">CCS</td>
            <td align="center"><?= number_format($ccs_sum / max(count($data),1), 2) ?></td>
            <td align="center">อ้อยสด</td>
            <td align="center"><?= number_format($g_fresh_cane, 2) ?></td>
            <td align="center">ตัน</td>
            <td align="center">ไฟไหม้</td>
            <td align="right"><?= number_format($g_burnt_cane, 2) ?></td>
            <td colspan="8" align="center">ตัน</td>
        </tr>
        <?php endif; ?>
    </table>
</body>
</html>