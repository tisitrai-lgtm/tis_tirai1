<?php
// export_excel.php - ใช้ PhpSpreadsheet สำหรับโปรเจกต์ ton_aoi

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. การตั้งค่าเริ่มต้นและการเชื่อมต่อฐานข้อมูล
// Path vendor ที่ถูกต้อง
require_once '../vendor/autoload.php'; 
require_once 'db_connect.php'; 

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Color;

// ตั้งค่าสำหรับ PHP
ini_set('memory_limit', '256M'); 
set_time_limit(600); 

if (!$conn) {
    die("Error: Database connection not established. Check db_connect.php");
}

// 2. รับค่าตัวกรองจาก URL (Year & Agency)
$selected_year = $_GET['year'] ?? '';
$selected_agency = $_GET['agency'] ?? ''; 

if (empty($selected_year)) {
    header('Location: dashboard.php?export_status=error&message=' . urlencode('กรุณาเลือกปีการผลิตที่ต้องการดาวน์โหลด'));
    exit;
}

// --- การกำหนด Static URL Base และ Path รูปภาพ ---
// 🚨 แก้ไขจุดที่ 1: กำหนด Base Domain
$project_base_url = 'https://givewatersugar.unaux.com/'; 

// 🚨 แก้ไขจุดที่ 2: ใช้ Path ซ้ำตามที่คุณระบุ เพื่อให้ได้ URL ที่ถูกต้องบนเซิร์ฟเวอร์
$image_base_prefix = "ton_aoi/ton_aoi/uploads/"; 
// --- End Static URL Base ---

// 3. ฟังก์ชันสำหรับ clean ชื่อ Path (ใช้ urlencode)
if (!function_exists('sanitizeForPath')) {
    function sanitizeForPath($string) {
        $string = str_replace(' ', '_', $string);
        return urlencode($string); 
    }
}

// 4. กำหนด SQL Query และตัวกรอง
$sql = "SELECT 
            id, production_year, agency, contract_number, quota, plot_id, rai_area, emp_number, suga_type, notes, created_at,
            estimate_ton_1, estimate_ton_2, 
            evaluate_ton_1, evaluate_ton_2, 
            remaining_cane_1_img_1, remaining_cane_1_img_2, 
            remaining_cane_2_img_1, remaining_cane_2_img_2, 
            remaining_cane_3_img_1, remaining_cane_3_img_2 
        FROM 
            cane_plot_data 
        WHERE 
            production_year = ? 
        ";

$params = [$selected_year];
$types = "s";

if (!empty($selected_agency)) {
    $sql .= " AND agency = ?";
    $params[] = $selected_agency;
    $types .= "s";
}

$sql .= " ORDER BY contract_number ASC, plot_id ASC";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    header('Location: dashboard.php?export_status=error&message=' . urlencode('เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: ' . $conn->error));
    exit;
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: dashboard.php?export_status=info&message=' . urlencode('ไม่พบข้อมูลสำหรับปีการผลิต ' . htmlspecialchars($selected_year) . ' ที่จะส่งออก'));
    exit;
}

// 5. สร้าง Excel Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('ข้อมูลอ้อยคงเหลือ');

// 6. กำหนด Header ของตาราง
$headers = [
    "ปีผลิต", "หน่วยงาน", "เลขสัญญา", "โควต้า", "ID แปลง", "นักส่งเสริม", 
    "พื้นที่ (ไร่)", "ชนิดอ้อย",
    "ประมาณตัน 1 (รูป)", "ประมาณตัน 2 (รูป)",
    "ประเมิน (1) (รูป)", "ประเมิน (2) (รูป)",
    "อ้อยคงเหลือ 1 (รูปหมุด)", "อ้อยคงเหลือ 1 (รูปอ้อย)",
    "อ้อยคงเหลือ 2 (รูปหมุด)", "อ้อยคงเหลือ 2 (รูปอ้อย)",
    "อ้อยคงเหลือ 3 (รูปหมุด)", "อ้อยคงเหลือ 3 (รูปอ้อย)",
    "หมายเหตุ", "วันที่บันทึก"
];

$colIndex = 0;
foreach ($headers as $header) {
    $col = Coordinate::stringFromColumnIndex($colIndex + 1);
    $sheet->setCellValue("{$col}1", $header);
    $sheet->getStyle("{$col}1")->getFont()->setBold(true);
    $sheet->getColumnDimension($col)->setAutoSize(true);
    $colIndex++;
}

// 7. Map ฟิลด์รูปภาพทั้งหมดและ Sub-folders
$images_map = [
    'estimate_ton_1' => ['col' => 'I', 'folder' => 'estimate_ton_1'], 
    'estimate_ton_2' => ['col' => 'J', 'folder' => 'estimate_ton_2'],
    'evaluate_ton_1' => ['col' => 'K', 'folder' => 'evaluate_ton_1'],
    'evaluate_ton_2' => ['col' => 'L', 'folder' => 'evaluate_ton_2'],
    'remaining_cane_1_img_1' => ['col' => 'M', 'folder' => 'remaining_cane_1'],
    'remaining_cane_1_img_2' => ['col' => 'N', 'folder' => 'remaining_cane_1'],
    'remaining_cane_2_img_1' => ['col' => 'O', 'folder' => 'remaining_cane_2'],
    'remaining_cane_2_img_2' => ['col' => 'P', 'folder' => 'remaining_cane_2'],
    'remaining_cane_3_img_1' => ['col' => 'Q', 'folder' => 'remaining_cane_3'],
    'remaining_cane_3_img_2' => ['col' => 'R', 'folder' => 'remaining_cane_3'],
];

// 8. เติมข้อมูลและใส่ลิงก์รูปภาพ
$rowNum = 2;
while ($row = $result->fetch_assoc()) {
    
    // กำหนดค่าเซลล์ข้อมูลที่ไม่ใช่รูปภาพ (คอลัมน์ A-H และ S-T)
    $sheet->setCellValue('A' . $rowNum, $row['production_year']);
    $sheet->setCellValue('B' . $rowNum, $row['agency']);
    $sheet->setCellValue('C' . $rowNum, $row['contract_number']);
    $sheet->setCellValue('D' . $rowNum, $row['quota']);
    $sheet->setCellValue('E' . $rowNum, $row['plot_id']);
    $sheet->setCellValue('F' . $rowNum, $row['emp_number']);
    $sheet->setCellValue('G' . $rowNum, $row['rai_area']);
    $sheet->setCellValue('H' . $rowNum, $row['suga_type']); 
    
    $sheet->setCellValue('S' . $rowNum, $row['notes']); 
    $sheet->setCellValue('T' . $rowNum, $row['created_at']); 

    // เตรียมค่าสำหรับ Path
    $sanitized_production_year = sanitizeForPath($row['production_year']);
    $sanitized_agency = sanitizeForPath($row['agency']);
    $sanitized_contract_number = sanitizeForPath($row['contract_number']);
    $sanitized_plot_id = sanitizeForPath($row['plot_id']);
    
    // Base Path รูปภาพ: ton_aoi/ton_aoi/uploads/ปี/หน่วยงาน/สัญญา/แปลง/
    $base_plot_image_path = "{$image_base_prefix}{$sanitized_production_year}/{$sanitized_agency}/{$sanitized_contract_number}/{$sanitized_plot_id}/";

    // สร้าง URL รูปภาพสำหรับฟิลด์ทั้งหมด (คอลัมน์ I-R)
    foreach ($images_map as $field_name => $data) {
        $imgFile = $row[$field_name] ?? '';
        $column = $data['col'];
        $sub_folder = $data['folder'];

        if (!empty($imgFile)) {
            // Full URL: https://givewatersugar.unaux.com/ton_aoi/ton_aoi/uploads/year/agency/contract/plot/subfolder/filename
            $fullImageUrl = $project_base_url . $base_plot_image_path . $sub_folder . '/' . urlencode($imgFile);

            // ใช้ Hyperlink Object
            $sheet->setCellValue($column . $rowNum, "ดูรูป"); 
            $sheet->getCell($column . $rowNum)->getHyperlink()->setUrl($fullImageUrl);
            $sheet->getStyle($column . $rowNum)->getFont()->setColor(new Color(Color::COLOR_BLUE));
            $sheet->getStyle($column . $rowNum)->getFont()->setUnderline(true);
        } else {
            $sheet->setCellValue($column . $rowNum, "");
        }
    }

    $rowNum++;
}

// 9. การจัดการ Output Buffer และส่งออก Excel
if (ob_get_length()) {
    ob_end_clean();
}

$filename = "ข้อมูลอ้อยคงเหลือ_" . $selected_year . ($selected_agency ? "_" . $selected_agency : "") . '_' . date('Ymd_His') . '.xlsx';
header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Cache-Control: max-age=0");

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");

// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();

exit;
?>