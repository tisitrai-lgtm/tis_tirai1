<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'vendor/autoload.php';
require 'dbconnect.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// ตรวจสอบให้แน่ใจว่ามีการเชื่อมต่อฐานข้อมูล
if (!isset($con) || !$con) {
    die("Error: Database connection not established. Check dbconnect.php");
}

// *** สำคัญมาก: เปลี่ยน 'http://your-website.com/' ให้เป็น URL หลักของเว็บไซต์ของคุณ
//     ที่สามารถเข้าถึงโฟลเดอร์ 'images/' ได้
//     ตัวอย่าง: ถ้าเว็บคุณคือ 'https://www.myfarm.com/' และรูปภาพอยู่ใต้ 'https://www.myfarm.com/images/...'
//     ให้กำหนด BASE_WEB_ROOT เป็น 'https://www.myfarm.com/'
define('BASE_WEB_ROOT', 'https://givewatersugar.unaux.com/'); // <<< เปลี่ยนตรงนี้ !!!

// ดึงข้อมูลจากฐานข้อมูล
$sql = "SELECT * FROM image_water ORDER BY year_rai, plot_id";
$result = mysqli_query($con, $sql);

if (!$result) {
    die("Query Error: " . mysqli_error($con));
}

// สร้าง Excel Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('ข้อมูลแปลงอ้อย');

// เขียนหัวตาราง
$headers = [
    "ปี", "เลข นสส", "idแปลง", "เลขสัญญา", "ชนิดอ้อย", "โควตา", "จำนวนไร่",
    "น้ำ 1", "น้ำ 2", "น้ำ 3", "น้ำท่วม", "แล้ง", "อื่นๆ"
];

$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue("{$col}1", $header);
    $sheet->getColumnDimension($col)->setAutoSize(true);
    $col++;
}

// เติมข้อมูลและใส่ลิงก์รูปภาพ
$rowNum = 2;
while ($row = mysqli_fetch_assoc($result)) {
    // กำหนดค่าเซลล์ข้อมูล
    $sheet->setCellValue("A$rowNum", $row['year_rai']);
    $sheet->setCellValue("B$rowNum", $row['emp_id']);
    $sheet->setCellValue("C$rowNum", $row['plot_id']);
    $sheet->setCellValue("D$rowNum", $row['contract_number']);
    $sheet->setCellValue("E$rowNum", $row['suga_type']);
    $sheet->setCellValue("F$rowNum", $row['quota']);
    $sheet->setCellValue("G$rowNum", $row['area_rai']);

    // ทำความสะอาดค่าเพื่อใช้ใน URL Path (ป้องกันปัญหาด้านความปลอดภัย)
    // ใช้ urlencode เพื่อจัดการอักขระพิเศษใน URL
    $empId = urlencode(basename($row['emp_id']));
    // ต้องแน่ใจว่า $row['contract_number'] ถูกอ่านจากฐานข้อมูล และทำการ encode ด้วย
    $contract_number_sanitized = urlencode(basename($row['contract_number']));
    $plotId = urlencode(basename($row['plot_id']));

    // กำหนดช่องรูปภาพและตำแหน่งคอลัมน์
    $imageFields = [
        'water_image1'  => 'H',
        'water_image2'  => 'I',
        'water_image3'  => 'J',
        'flood_image'   => 'K',
        'drought_image' => 'L',
        'other_image'   => 'M',
    ];

    foreach ($imageFields as $field => $column) {
        $imgFile = $row[$field];

        if (!empty($imgFile)) {
            // สร้าง Full URL ของรูปภาพให้ถูกต้อง
            // ตัวอย่าง: https://givewatersugar.unaux.com/images/water/EMP001/CONTRACT_NUM/PLOT123/image1.jpg
            // แก้ไข: ลบ 'https://givewatersugar.unaux.com/' ที่ซ้ำซ้อนออก
            $fullImageUrl = BASE_WEB_ROOT . "images/water/{$empId}/{$contract_number_sanitized}/{$plotId}/" . urlencode(basename($imgFile));

            $sheet->setCellValue($column . $rowNum, "ดูรูปภาพ");
            $sheet->getCell($column . $rowNum)->getHyperlink()->setUrl($fullImageUrl);
            $sheet->getStyle($column . $rowNum)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLUE));
            $sheet->getStyle($column . $rowNum)->getFont()->setUnderline(true);
        } else {
            $sheet->setCellValue($column . $rowNum, "");
        }
    }

    $rowNum++;
}

// การจัดการ Output Buffer และส่งออก Excel
if (ob_get_length()) {
    ob_end_clean();
}

$filename = "ข้อมูลแปลงอ้อย_" . date("Ymd_His") . ".xlsx";
header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: max-age=0");

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");

// ปิดการเชื่อมต่อฐานข้อมูล
mysqli_close($con);

exit;
?>