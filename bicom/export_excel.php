<?php
session_start();
require_once 'vendor/autoload.php'; 
require_once 'db_connect.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

// --- ฟังก์ชันสำหรับวาดแถว "รวมสัญญา" ---
function drawSubtotal($sheet, $rowNum, $count, $weight, $fresh, $burnt) {
    $sheet->setCellValue('A'.$rowNum, 'รวมสัญญา');
    $sheet->setCellValue('C'.$rowNum, $count);
    $sheet->setCellValue('D'.$rowNum, 'คัน');
    $sheet->setCellValue('G'.$rowNum, number_format($weight, 3));
    $sheet->setCellValue('H'.$rowNum, 'อ้อยสด');
    $sheet->setCellValue('I'.$rowNum, number_format($burnt, 2)); // แก้ไขลำดับให้ตรง (สด)
    $sheet->setCellValue('J'.$rowNum, 'ตัน');
    $sheet->setCellValue('K'.$rowNum, 'ไฟไหม้');
    $sheet->setCellValue('L'.$rowNum, number_format($fresh, 2)); // แก้ไขลำดับให้ตรง (ไหม้)
    $sheet->setCellValue('M'.$rowNum, 'ตัน');

    $styleArray = [
        'font' => ['bold' => true],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['argb' => 'FFF9F9F9'],
        ],
    ];
    $sheet->getStyle("A$rowNum:AC$rowNum")->applyFromArray($styleArray);
}

if (!isset($_SESSION['statn_code'])) { exit; }

$statn_code = $_SESSION['statn_code'];
$start_date = $_GET['start_date'] ?? '';
$end_date   = $_GET['end_date'] ?? '';
$statn_name = $_SESSION['statn_name'] ?? "หน่วย $statn_code";

// 1. Query ข้อมูล
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

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
$sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_LEGAL);
$sheet->getPageSetup()->setFitToPage(true);
$sheet->getPageSetup()->setFitToWidth(1);
$sheet->getPageSetup()->setFitToHeight(0);
$spreadsheet->getDefaultStyle()->getFont()->setName('AngsanaUPC')->setSize(14);

// 4. หัวรายงาน
$sheet->setCellValue('A1', 'บริษัท น้ำตาลไทยเอกลักษณ์ จำกัด');
$sheet->mergeCells('A1:AC1');
$sheet->getStyle('A1')->getFont()->setSize(16)->setBold(true);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$date_title = "รายงานการรับอ้อยประจำวันที่ " . date('d/m/Y', strtotime($start_date)) . " ถึงวันที่ " . date('d/m/Y', strtotime($end_date)) . " ตามพื้นที่ปลูกอ้อย หน่วย $statn_name";
$sheet->setCellValue('A2', $date_title);
$sheet->mergeCells('A2:AC2');
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// 5. หัวตาราง
$headers = [
    'A3' => 'เลขสัญญา', 'B3' => 'ชื่อ-นามสกุล', 'C3' => 'ใบรับอ้อย', 'D3' => 'ทะเบียนรถ', 
    'E3' => "รถ\nตัด", 'F3' => "รถ\nคีบ", 'G3' => 'ตันอ้อย', 'H3' => "ค่า\nบรรทุก", 
    'I3' => 'ค่าขึ้น', 'J3' => 'ค่าตัด', 'K3' => 'CCS', 'L3' => 'PURITY', 
    'M3' => "ประเภท\nอ้อย", 'N3' => "ตัดราคาอ้อย\nไฟไหม้", 'O3' => "เลขกำ\nกับแปลง", 'P3' => 'เลข ID', 
    'Q3' => 'อก.', 'R3' => 'เจ้าของรถ', 'S3' => 'สัญญาขึ้นอ้อย', 'U3' => 'สัญญาตัดอ้อย',
    'W3' => 'วันที่ตัด', 'X3' => 'เวลา', 'Y3' => "เวลาอยู่ในโรงงาน", 'AA3' => "รวมเวลาเข้าหีบ", 'AC3' => 'หมายเหตุ'
];
foreach ($headers as $cell => $value) { $sheet->setCellValue($cell, $value); }
$sheet->setCellValue('S4', 'ไอดี'); $sheet->setCellValue('T4', 'ชื่อ');
$sheet->setCellValue('U4', 'ไอดี'); $sheet->setCellValue('V4', 'ชื่อ');
$sheet->setCellValue('Y4', 'ชั่วโมง'); $sheet->setCellValue('Z4', 'นาที');
$sheet->setCellValue('AA4', 'ชั่วโมง'); $sheet->setCellValue('AB4', 'นาที');

$mergeList = ['A3:A4','B3:B4','C3:C4','D3:D4','E3:E4','F3:F4','G3:G4','H3:H4','I3:I4','J3:J4','K3:K4','L3:L4','M3:M4','N3:N4','O3:O4','P3:P4','Q3:Q4','R3:R4','S3:T3','U3:V3','W3:W4','X3:X4','Y3:Z3','AA3:AB3','AC3:AC4'];
foreach ($mergeList as $range) { $sheet->mergeCells($range); }

$sheet->getStyle('A3:AC4')->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getStyle('A3:AC4')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

// บีบความกว้างคอลัมน์
$sheet->getColumnDimension('A')->setWidth(8);
$sheet->getColumnDimension('B')->setWidth(20);
$sheet->getColumnDimension('D')->setWidth(13);
$sheet->getColumnDimension('E')->setWidth(4);
$sheet->getColumnDimension('F')->setWidth(4);
$sheet->getColumnDimension('N')->setWidth(11);
$sheet->getColumnDimension('R')->setWidth(20);
// 6. วนลูปข้อมูล
$i = 5;
if ($data) {
    $curr_contract = $data[0]['FARMR_CODE'];
    $sub_weight = 0; $sub_count = 0; $sub_fresh = 0; $sub_burnt = 0;
    
    $total_weight = 0; $total_count = 0; $total_mcut = 0; $total_mgrab = 0;
    $total_fresh = 0; $total_burnt = 0; //$ccs_sum = 0; 
    $weighted_ccs_sum = 0; 

    foreach ($data as $index => $row) {
        if ($curr_contract != $row['FARMR_CODE']) {
            drawSubtotal($sheet, $i, $sub_count, $sub_weight, $sub_fresh, $sub_burnt);
            $i++;
            $sub_weight = 0; $sub_count = 0; $sub_fresh = 0; $sub_burnt = 0;
            $curr_contract = $row['FARMR_CODE'];
        }

        // ใส่ข้อมูลแถวปกติ (ดึงให้ครบทุกคอลัมน์)
        $sheet->setCellValue('A'.$i, $row['FARMR_CODE']);
        $sheet->setCellValue('B'.$i, $row['FARMR_NAME']);
        $sheet->setCellValue('C'.$i, $row['WEIGH_DOCC']);
        $sheet->setCellValue('D'.$i, $row['TRUCK_CODE']);
        $sheet->setCellValue('E'.$i, $row['CANE_TCCUT']);
        $sheet->setCellValue('F'.$i, $row['CANE_TCHOL']);
        $sheet->setCellValue('G'.$i, $row['WEIGH_CANE']);
        $sheet->setCellValue('H'.$i, $row['TRUCK_RATE']);
        $sheet->setCellValue('I'.$i, $row['TRUCK_UP']);
        $sheet->setCellValue('J'.$i, $row['TRUCK_MCUT']);
        $sheet->setCellValue('K'.$i, $row['SWEET_CANE']);
        $sheet->setCellValue('L'.$i, $row['PURI_CANE']);
        $sheet->setCellValue('M'.$i, $row['CANE_TYPE']);
        $sheet->setCellValue('N'.$i, $row['FLAG_CUT1']);
        $sheet->setCellValue('O'.$i, $row['LAND_NUMB']); // เลขกำกับแปลง
        $sheet->setCellValue('P'.$i, $row['LAND_ID']);   // เลข ID
        $sheet->setCellValue('Q'.$i, $row['FARMR_TRCK']); // อก.
        $sheet->setCellValue('R'.$i, $row['FARMR_TNAM']); // เจ้าของรถ
        $sheet->setCellValue('S'.$i, $row['FARMR_UP']);   // สัญญาขึ้นอ้อย (ไอดี)
        $sheet->setCellValue('T'.$i, $row['FARMR_UNAM']); // สัญญาขึ้นอ้อย (ชื่อ)
        $sheet->setCellValue('U'.$i, $row['FARMR_CUT']);  // สัญญาตัดอ้อย (ไอดี)
        $sheet->setCellValue('V'.$i, $row['FARMR_CNAM']); // สัญญาตัดอ้อย (ชื่อ)
        $sheet->setCellValue('W'.$i, date('d/m/Y', strtotime($row['DATE_CUT'])));
        $sheet->setCellValue('X'.$i, $row['TIME_CUT']);
        $sheet->setCellValue('Y'.$i, $row['TIME_HFAC']);
        $sheet->setCellValue('Z'.$i, $row['TIME_MFAC']);
        $sheet->setCellValue('AA'.$i, $row['TIME_HTOTL']);
        $sheet->setCellValue('AB'.$i, $row['TIME_MTOTL']);
        $sheet->setCellValue('AC'.$i, $row['FLAG_ID']);   // หมายเหตุ

        $sheet->getStyle("A$i:AC$i")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_DASHED);
        
        // เก็บสะสมยอด
        $w = (float)$row['WEIGH_CANE'];
        $sub_weight += $w; $sub_count++;
        $total_weight += $w; $total_count++;
        if($row['CANE_TCCUT'] > 0) $total_mcut++;
        if($row['CANE_TCHOL'] > 0) $total_mgrab++;
        // สูตร: (น้ำหนัก x CCS) แล้วสะสมยอด
        $weighted_ccs_sum += ((float)$row['WEIGH_CANE'] * (float)$row['SWEET_CANE']);
        
        if($row['CANE_TYPE'] == 'อ้อยสด') { $sub_fresh += $w; $total_fresh += $w; } 
        else { $sub_burnt += $w; $total_burnt += $w; }

        $i++;

        if ($index == count($data) - 1) {
            drawSubtotal($sheet, $i, $sub_count, $sub_weight, $sub_fresh, $sub_burnt);
            $i++;
        }
    }

    // 7. รวมหน่วย
    $sheet->setCellValue('A'.$i, "รวมหน่วย $statn_name");
    $sheet->setCellValue('C'.$i, $total_count);
    $sheet->setCellValue('D'.$i, 'คัน');
    $sheet->setCellValue('E'.$i, 'ตันอ้อย');
    $sheet->setCellValue('G'.$i, number_format($total_weight, 3));
    $sheet->setCellValue('H'.$i, 'ตัน');
    $sheet->setCellValue('I'.$i, 'รถตัด');
    $sheet->setCellValue('J'.$i, $total_mcut);
    $sheet->setCellValue('K'.$i, 'คัน');
    $sheet->setCellValue('L'.$i, 'รถคีบ');
    $sheet->setCellValue('M'.$i, $total_mgrab);
    $sheet->setCellValue('N'.$i, 'คัน');
    $sheet->setCellValue('O'.$i, 'CCS');
    // สูตรเฉลี่ยถ่วงน้ำหนัก: ผลรวม(น้ำหนัก x CCS) / น้ำหนักรวมทั้งหมด
    $avg_ccs_weighted = ($total_weight > 0) ? ($weighted_ccs_sum / $total_weight) : 0;
    $sheet->setCellValue('P'.$i, number_format($avg_ccs_weighted, 2));
    $sheet->setCellValue('Q'.$i, 'อ้อยสด');
    $sheet->setCellValue('R'.$i, number_format($total_burnt, 2));
    $sheet->setCellValue('S'.$i, 'ไฟไหม้');
    $sheet->setCellValue('T'.$i, number_format($total_fresh, 2));
    $sheet->setCellValue('U'.$i, 'ตัน');

    $sheet->getStyle("A$i:AC$i")->getFont()->setBold(true);
    $sheet->getStyle("A$i:AC$i")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
}

// 8. Download
$writer = new Xlsx($spreadsheet);
$filename = "รายงงานใบคอม" . date('Ymd_His') . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
$writer->save('php://output');
exit;