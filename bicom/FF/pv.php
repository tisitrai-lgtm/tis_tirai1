<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['statn_code'])) { exit; }

// --- ตัวแปลงรหัสหน่วย ---
$statn_names = [
    "01" => "พรหมพิราม", "14" => "พรหมพิราม", "02" => "พิษณุโลก", "217" => "เมือง", "213" => "วัดโบสถ์",
    "203" => "บ่อทอง", "202" => "น้ำอ่าง", "204" => "ชาติตระการ", "205" => "หนองตม", "111" => "บางขลัง",
    "110" => "ทุ่งเสลี่ยม", "115" => "ศรีสัชนาลัย", "116" => "สวรรคโลก", "102" => "ชัยคีรี",
    "112" => "ศรีนครเหนือ", "101" => "ศรีนครใต้", "206" => "พิชัย", "109" => "ศรีสำโรง",
    "113" => "ตลิ่งชัน", "106" => "ท่าชัย", "114" => "เขาหลวง", "108" => "คีรีมาศ",
    "121" => "ท่าชัยใต้", "107" => "ท่าชัยเหนือ", "219" => "น้ำปาด", "220" => "แพร่", "117" => "ตาก"
];

$statn_code = $_SESSION['statn_code'];
$statn_name = $statn_names[$statn_code] ?? "หน่วย ($statn_code)";
$start_date = $_GET['start_date'] ?? '';
$end_date   = $_GET['end_date'] ?? '';

// ตั้งค่าดาวน์โหลด Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"report_cane_compact.xls\"");
header("Pragma: no-cache");
header("Expires: 0");

// Query ข้อมูล
$sql = "SELECT * FROM conversion_logs WHERE STATN_CODE = ? ";
$params = [$statn_code];
if ($start_date && $end_date) {
    $sql .= " AND WORK_DATE BETWEEN ? AND ?";
    $params[] = $start_date; $params[] = $end_date;
}
$sql .= " ORDER BY FARMR_CODE ASC, WEIGH_DOCC ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-type" content="text/html;charset=utf-8" />
    <x:HorizontalResolution>600</x:HorizontalResolution>
                            <x:VerticalResolution>600</x:VerticalResolution>
                        </x:Print>
                        <x:Selected/>
                        <x:DoNotDisplayGridlines/>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
    <style>
        @page {
            mso-page-orientation: landscape;
            margin: 0.1in 0.1in 0.1in 0.1in;
            size: 14in 8.5in;
        }
        table { border-collapse: collapse; table-layout: fixed; width: 100%; }
        
        /* 1. เปลี่ยนเป็นเส้นประ | 3. ฟอนต์ AngsanaUPC ขนาด 11 */
        td, th { 
            border: 0.5pt dashed #888888; 
            font-family: "AngsanaUPC", serif; 
            font-size: 11pt; 
            padding: 0px 1px;
            white-space: nowrap;
            overflow: hidden;
            vertical-align: middle;
        }
        
        th { border: 0.5pt solid #000000; background-color: #f2f2f2; font-weight: bold; }
        
        .text-mode { mso-number-format:"\@"; }
        .text-red { color: #FF0000; }
        .text-bold { font-weight: bold; }
       
    </style>
</head>
<body>
    <table>
        <tr>
            <td colspan="29" align="center" class="text-bold" style="font-size: 14pt; border:none;">บริษัท น้ำตาลไทยเอกลักษณ์ จำกัด</td>
        </tr>
        <tr>
            <td colspan="29" align="center" style="border:none; font-size: 11pt;">
                รายงานการรับอ้อยวันที่: <?= ($start_date) ? date('d/m/Y', strtotime($start_date)) : '-' ?> ถึง <?= ($end_date) ? date('d/m/Y', strtotime($end_date)) : '-' ?> | หน่วย: <?= $statn_name ?>
            </td>
        </tr>

        <tr class="text-bold">
            <th rowspan="2"  style="width: 50px;">เลขสัญญา</th>
            <th rowspan="2"  style="width: 100px;">ชื่อ-นามสกุล</th>
            <th rowspan="2" style="width: 45px;">ใบรับอ้อย</th>
            <th rowspan="2" style="width: 65px;">ทะเบียนรถ</th>
            <th rowspan="2" style="width: 30px;">รถตัด</th>
            <th rowspan="2" style="width: 30px;">รถคีบ</th>
            <th rowspan="2" style="width: 35px;">ตันอ้อย</th>
            <th rowspan="2" style="width: 45px;">ค่า<br>บรรทุก</th>
            <th rowspan="2" style="width: 30px;">ค่าขึ้น</th>
            <th rowspan="2" style="width: 30px;">ค่าตัด</th>
            <th rowspan="2" style="width: 35px;">CCS</th>
            <th rowspan="2" style="width: 42px;">PURITY</th>
            <th rowspan="2" style="width: 38px;">ประเภท<br>อ้อย</th>
            <th rowspan="2" style="width: 52px; ">ตัดราคา<br>อ้อยไฟไหม้</th>
            <th rowspan="2" style="width: 50px;">เลขกำ<br>กับแปลง</th>
            <th rowspan="2" style="width: 50px;">เลข ID</th>
            <th rowspan="2" style="width: 40px;">อก.</th>
            <th rowspan="2" style="width: 100px;">เจ้าของรถ</th>
            <th colspan="2"style="width: 100px;">สัญญาขึ้น</th>
            <th colspan="2"style="width: 100px;">สัญญาตัด</th>
            <th rowspan="2" style="width: 60px;">วันที่ตัด</th>
            <th rowspan="2" style="width: 35px;">เวลา</th>
            <th colspan="2">เวลาโรงงาน</th>
            <th colspan="2">เวลาหีบ</th>
            <th rowspan="2" style="width: 40px;">หมาย<br>เหตุ</th>
        </tr>
        <tr class="text-bold">
            <th style="width: 50px;">ไอดี</th><th style="width: 50px;">ชื่อ</th>
            <th style="width: 50px;">ไอดี</th><th style="width: 50px;">ชื่อ</th>
            <th style="width: 30px;">ชม.</th><th style="width: 30px;">น.</th>
            <th style="width: 30px;">ชม.</th><th style="width: 30px;">น.</th>
        </tr>

        <?php 
        if($data): 
            $curr_contract = null;
            $c_weight = 0; $c_count = 0; 

            foreach($data as $row): 
                // ส่วนรวมรายสัญญา
                if ($curr_contract !== null && $curr_contract !== $row['FARMR_CODE']): 
        ?>
            <tr class="text-bold" style="background-color: #fafafa;">
                <td colspan="2" align="right">รวมสัญญา</td>
                <td align="center"><?= $c_count ?></td>
                <td colspan="3"></td>
                <td align="right"><?= number_format($c_weight, 3) ?></td>
                <td colspan="22"></td>
            </tr>
        <?php 
                    $c_weight = 0; $c_count = 0;
                endif;

                $curr_contract = $row['FARMR_CODE'];
                $w = (float)$row['WEIGH_CANE'];
                $c_weight += $w;
                $c_count++;
                
                $up_id = (!empty($row['FARMR_UP'])) ? str_pad($row['FARMR_UP'], 6, "0", STR_PAD_LEFT) : "";
                $cut_id = (!empty($row['FARMR_CUT'])) ? str_pad($row['FARMR_CUT'], 6, "0", STR_PAD_LEFT) : "";
        ?>
            <tr>
                <td align="center" class="text-mode"><?= $row['FARMR_CODE'] ?></td>
                <td><?= $row['FARMR_NAME'] ?></td>
                <td align="center" class="text-mode"><?= str_pad($row['WEIGH_DOCC'], 6, "0", STR_PAD_LEFT) ?></td>
                <td align="center"><?= $row['TRUCK_CODE'] ?></td>
                <td align="center" class="text-red"><?= $row['CANE_TCCUT'] ?></td>
                <td align="center"><?= $row['CANE_TCHOL'] ?></td>
                <td align="right" class="text-bold"><?= number_format($w, 3) ?></td>
                <td align="right"><?= number_format($row['TRUCK_RATE'], 0) ?></td>
                <td align="right"><?= number_format($row['TRUCK_UP'], 0) ?></td>
                <td align="right"><?= number_format($row['TRUCK_MCUT'], 0) ?></td>
                <td align="center"><?= number_format($row['SWEET_CANE'], 2) ?></td>
                <td align="center"><?= number_format($row['PURI_CANE'], 2) ?></td>
                <td align="center"><?= $row['CANE_TYPE'] ?></td>
                <td align="center"><?= $row['FLAG_CUT1'] ?></td>
                <td align="center"><?= $row['LAND_NUMB'] ?></td>
                <td align="center" class="text-red text-mode"><?= $row['LAND_ID'] ?></td>
                <td align="center" class="text-mode"><?= str_pad($row['FARMR_TRCK'], 4, "0", STR_PAD_LEFT) ?></td>
                <td><?= $row['FARMR_TNAM'] ?></td>
                <td align="center" class="text-mode"><?= $up_id ?></td>
                <td><?= $row['FARMR_UNAM'] ?></td>
                <td align="center" class="text-mode"><?= $cut_id ?></td>
                <td><?= $row['FARMR_CNAM'] ?></td>
                <td align="center"><?= date('d/m/Y', strtotime($row['DATE_CUT'])) ?></td>
                <td align="center" ><?= $row['TIME_CUT'] ?></td>
                <td align="center"><?= $row['TIME_HFAC'] ?></td>
                <td align="center"><?= $row['TIME_MFAC'] ?></td>
                <td align="center"><?= $row['TIME_HTOTL'] ?></td>
                <td align="center"><?= $row['TIME_MTOTL'] ?></td>
                <td><?= $row['FLAG_ID'] ?></td> 
            </tr>
        <?php endforeach; ?>

            <tr class="text-bold" style="background-color: #fafafa;">
                <td colspan="2" align="right">รวมสัญญา</td>
                <td align="center"><?= $c_count ?></td>
                <td colspan="3"></td>
                <td align="right"><?= number_format($c_weight, 3) ?></td>
                <td colspan="22"></td>
            </tr>
        <?php endif; ?>
    </table>
</body>
</html>