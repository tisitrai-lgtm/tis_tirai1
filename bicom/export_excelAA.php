<?php
session_start();
require_once 'vendor/autoload.php';
require_once 'db_connect.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// 1. รับค่าฟิลเตอร์และชื่อหน่วยงาน
$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date   = $_GET['end_date'] ?? date('Y-m-d');
$statn_filter = $_GET['statn_code'] ?? 'ALL';

$display_statn_name = "ทั้งหมด";
if ($statn_filter !== 'ALL') {
    $stmt_st = $pdo->prepare("SELECT statn_name FROM stations WHERE statn_code = ?");
    $stmt_st->execute([$statn_filter]);
    $st_row = $stmt_st->fetch();
    $display_statn_name = $st_row ? $st_row['statn_name'] : $statn_filter;
}

// 2. ดึงข้อมูลรายงาน
$sql = "SELECT r.*, s.statn_name FROM daily_truck_reports r 
        JOIN stations s ON r.statn_code = s.statn_code 
        WHERE r.report_date BETWEEN ? AND ?";
$params = [$start_date, $end_date];
if ($statn_filter !== 'ALL') {
    $sql .= " AND r.statn_code = ?";
    $params[] = $statn_filter;
}
$sql .= " ORDER BY r.report_date ASC, r.created_at ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('รายงานการเดินรถ');

// 3. กำหนดหัวข้อและโครงสร้างหัวตาราง 2 ชั้นตามรูปภาพ
$sheet->setCellValue('A1', 'รายงานรถบรรทุกที่ใส่อ้อยเต็มคันแล้ว');
$sheet->mergeCells('A1:G1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->setCellValue('A2', "ช่วงวันที่: $start_date ถึง $end_date | หน่วยงาน: $display_statn_name");
$sheet->mergeCells('A2:G2');
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// หัวตาราง Row 3-4
$sheet->setCellValue('A3', 'วัน/เดือน/ปี'); $sheet->mergeCells('A3:A4');
$sheet->setCellValue('B3', 'หน่วยงาน');   $sheet->mergeCells('B3:B4');
$sheet->setCellValue('C3', 'เบอร์รถตัด'); $sheet->mergeCells('C3:C4');
$sheet->setCellValue('D3', 'อ้อยท่อน');   $sheet->mergeCells('D3:E3');
$sheet->setCellValue('D4', 'ทะเบียนรถ');  $sheet->setCellValue('E4', 'ทะเบียนพ่วง');
$sheet->setCellValue('F3', 'อ้อยลำ');     $sheet->mergeCells('F3:G3');
$sheet->setCellValue('F4', 'ทะเบียนรถ');  $sheet->setCellValue('G4', 'ทะเบียนพ่วง');

$sheet->getStyle('A3:G4')->applyFromArray([
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E2E2']]
]);

// 4. ใส่ข้อมูลและนับยอดรวม
$rowNumber = 5;
$total_ton = 0; // สำหรับอ้อยท่อน
$total_lum = 0; // สำหรับอ้อยลำ

foreach ($reports as $row) {
    $sheet->setCellValue('A' . $rowNumber, $row['report_date']);
    $sheet->setCellValue('B' . $rowNumber, $row['statn_name']);
    $sheet->setCellValue('C' . $rowNumber, $row['harvester_code']);
    
    // ตรวจสอบประเภทอ้อย (สมมติใช้ฟิลด์ owner_name หรือปรับตามจริง)
    if ($row['owner_name'] === 'อ้อยท่อน') {
        $sheet->setCellValue('D' . $rowNumber, $row['main_truck_license']);
        $total_ton++; // นับแม่
        if (!empty($row['trailer_license'])) {
            $sheet->setCellValue('E' . $rowNumber, $row['trailer_license']);
            $total_ton++; // นับพ่วง
        }
    } else {
        $sheet->setCellValue('F' . $rowNumber, $row['main_truck_license']);
        $total_lum++; // นับแม่
        if (!empty($row['trailer_license'])) {
            $sheet->setCellValue('G' . $rowNumber, $row['trailer_license']);
            $total_lum++; // นับพ่วง
        }
    }
    
    $sheet->getStyle("A$rowNumber:G$rowNumber")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $rowNumber++;
}

// 5. แถว "ผลรวมทั้งหมด" แถวเดียวตามรูปภาพล่าสุด
$totalRow = $rowNumber;
$sheet->setCellValue('C' . $totalRow, 'ผลรวม');
$sheet->setCellValue('D' . $totalRow, $total_ton);
$sheet->mergeCells("D$totalRow:E$totalRow"); // ควบช่องอ้อยท่อน
$sheet->setCellValue('F' . $totalRow, $total_lum);
$sheet->mergeCells("F$totalRow:G$totalRow"); // ควบช่องอ้อยลำ

$sheet->getStyle("C$totalRow:G$totalRow")->getFont()->setBold(true);
$sheet->getStyle("C$totalRow:G$totalRow")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// 6. ตีเส้นขอบประและตั้งความกว้าง
$sheet->getStyle("A3:G$totalRow")->applyFromArray([
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_DASHED, 'color' => ['rgb' => 'CCCCCC']]]
]);

$widths = ['A'=>12, 'B'=>15, 'C'=>12, 'D'=>15, 'E'=>15, 'F'=>15, 'G'=>15];
foreach ($widths as $col => $w) { $sheet->getColumnDimension($col)->setWidth($w); }

// 7. ดาวน์โหลดไฟล์
$fileName = "รายงานรถบรรทุกใส่เต็มคันแล้ว" . date('Ymd_His') . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');
(new Xlsx($spreadsheet))->save('php://output');
exit;