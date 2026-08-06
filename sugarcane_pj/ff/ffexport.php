<?php
// export_excel.php

require 'vendor/autoload.php';
require_once 'db_connect.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Font;

$selected_year = $_GET['year'] ?? '';

if (empty($selected_year)) {
    header('Location: dashboard.php?export_status=error&message=' . urlencode('กรุณาเลือกปีการผลิตที่ต้องการดาวน์โหลด') . '&year=' . urlencode($_GET['year'] ?? ''));
    exit;
}

// --- Dynamic Base URL for Project Root ---
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$script_path = dirname($_SERVER['PHP_SELF']);
$script_path = rtrim($script_path, '/');
$project_base_url = $protocol . '://' . $host . ($script_path ? $script_path . '/' : '/');
// --- End Dynamic Base URL for Project Root ---


// SQL query to select all data for the chosen production year
$sql = "SELECT 
            production_year,
            agency,
            contract_number,
            quota,
            plot_id,
            rai_area,
            soil_type,
            soil_image,
            soil_preparation_details,
            soil_preparation_image,
            cane_variety,
            cane_variety_image,
            planting_details,
            planting_image,
            watering_details,
            watering_image,
            germination_percentage,
            germination_image,
            notes,
            created_at
        FROM soil_data 
        WHERE production_year = ? 
        ORDER BY id DESC";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("s", $selected_year);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: dashboard.php?export_status=info&message=' . urlencode('ไม่พบข้อมูลสำหรับปีการผลิต ' . htmlspecialchars($selected_year) . ' ที่จะส่งออก') . '&year=' . urlencode($selected_year));
    exit;
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('ข้อมูลแปลงอ้อย ' . $selected_year);

$headers = [
    'ปีผลิต', 'หน่วยงาน', 'เลขสัญญา', 'โควต้า', 'ID แปลง', 'ไร่', 'ชนิดดิน',
    'รูปดิน', 'เตรียมดิน', 'รูปเตรียมดิน', 'พันธุ์อ้อย', 'รูปพันธุ์อ้อย',
    'การปลูก', 'รูปปลูก', 'การให้น้ำ', 'รูปให้น้ำ', 'เปอร์เซ็นต์', 'รูปเปอร์เซ็นต์',
    'หมายเหตุ', 'วันที่บันทึก'
];

$colIdx = 0;
foreach ($headers as $header) {
    $colChar = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1); 
    $sheet->setCellValue($colChar . '1', $header);
    
    $sheet->getStyle($colChar . '1')->getFont()->setBold(true);
    $sheet->getStyle($colChar . '1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle($colChar . '1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFA0A0A0');
    $sheet->getStyle($colChar . '1')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $colIdx++;
}

$lastHeaderColumnChar = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));

$rowNum = 2;
while ($row = $result->fetch_assoc()) {
    $colIdx = 0; 

    // --- Start: Reconstruct path components (consistent with your upload logic) ---
    // Ensure this function (or your actual sanitization function) is available
    if (!function_exists('sanitizeForPath')) {
        function sanitizeForPath($string) {
            // This is a basic example. Ensure it matches your actual sanitization from upload script.
            return preg_replace('/[^a-zA-Z0-9\._-]/', '', str_replace(' ', '_', $string));
        }
    }
    
    $sanitized_production_year = sanitizeForPath($row['production_year']);
    $sanitized_agency = sanitizeForPath($row['agency']);
    $sanitized_contract_number = sanitizeForPath($row['contract_number']);
    $sanitized_plot_id = sanitizeForPath($row['plot_id']);
    
    // Base part of the relative path, e.g., 'uploads/68-69/002411/10555/'
    $base_plot_image_path = "uploads/{$sanitized_production_year}/{$sanitized_agency}/{$sanitized_contract_number}/{$sanitized_plot_id}/";
    // --- End: Reconstruct path components ---

    $sheet->setCellValueByColumnAndRow($colIdx + 1, $rowNum, $row['production_year']); $colIdx++;
    $sheet->setCellValueByColumnAndRow($colIdx + 1, $rowNum, $row['agency']); $colIdx++;
    $sheet->setCellValueByColumnAndRow($colIdx + 1, $rowNum, $row['contract_number']); $colIdx++;
    $sheet->setCellValueByColumnAndRow($colIdx + 1, $rowNum, $row['quota']); $colIdx++;
    $sheet->setCellValueByColumnAndRow($colIdx + 1, $rowNum, $row['plot_id']); $colIdx++;
    $sheet->setCellValueByColumnAndRow($colIdx + 1, $rowNum, $row['rai_area']); $colIdx++;
    $sheet->setCellValueByColumnAndRow($colIdx + 1, $rowNum, $row['soil_type']); $colIdx++;
    
    // --- soil_image (with hyperlink) ---
    if (!empty($row['soil_image'])) {
        // Concatenate: project_base_url + base_plot_image_path + 'soil_image/' + image_filename
        $full_image_url = $project_base_url . $base_plot_image_path . 'soil_image/' . $row['soil_image'];
        $sheet->setCellValueByColumnAndRow($colIdx + 1, $rowNum, 'ดูรูป'); 
        $sheet->getCellByColumnAndRow($colIdx + 1, $rowNum)->getHyperlink()->setUrl($full_image_url);
        $sheet->getStyleByColumnAndRow($colIdx + 1, $rowNum)->getFont()->setColor(new Color(Color::COLOR_BLUE));
        $sheet->getStyleByColumnAndRow($colIdx + 1, $rowNum)->getFont()->setUnderline(Font::UNDERLINE_SINGLE);
    } else {
        $sheet->setCellValueByColumnAndRow($colIdx + 1, $rowNum, '');
    }
    $colIdx++;
    // --- End soil_image ---

    $sheet->setCellValueByColumnAndRow($colIdx + 1, $rowNum, $row['soil_preparation_details']); $colIdx++;
    
    // --- soil_preparation_image (with hyperlink) ---
    if (!empty($row['soil_preparation_image'])) {
        $full_image_url = $project_base_url . $base_plot_image_path . 'soil_preparation_image/' . $row['soil_preparation_image'];
        $sheet->setCellValueByColumnAndRow($colIdx + 1, $rowNum, 'ดูรูป'); 
        $sheet->getCellByColumnAndRow($colIdx + 1, $rowNum)->getHyperlink()->setUrl($full_image_url);
        $sheet->getStyleByColumnAndRow($colIdx + 1, $rowNum)->getFont()->setColor(new Color(Color::COLOR_BLUE));
        $sheet->getStyleByColumnAndRow($colIdx + 1, $rowNum)->getFont()->setUnderline(Font::UNDERLINE_SINGLE);
    } else {
        $sheet->setCellValueByColumnAndRow($colIdx + 1, $rowNum, '');
    }
    $colIdx++;
    // --- End soil_preparation_image ---

    $sheet->setCellValueByColumnAndRow($colIdx + 1, $rowNum, $row['cane_variety']); $colIdx++;
    
    // --- cane_variety_image (with hyperlink) ---
    if (!empty($row['cane_variety_image'])) {
        $full_image_url = $project_base_url . $base_plot_image_path . 'cane_variety_image/' . $row['cane_variety_image'];
        $sheet->setCellValueByColumnAndRow($colIdx + 1, $rowNum, 'ดูรูป'); 
        $sheet->getCellByColumnAndRow($colIdx + 1, $rowNum)->getHyperlink()->setUrl($full_image_url);
        $sheet->getStyleByColumnAndRow($colIdx + 1, $rowNum)->getFont()->setColor(new Color(Color::COLOR_BLUE));
        $sheet->getStyleByColumnAndRow($colIdx + 1, $rowNum)->getFont()->setUnderline(Font::UNDERLINE_SINGLE);
    } else {
        $sheet->setCellValueByColumnAndRow($colIdx + 1, $rowNum, '');
    }
    $colIdx++;
    // --- End cane_variety_image ---

    $sheet->setCellValueByColumnAndRow($colIdx + 1, $rowNum, $row['planting_details']); $colIdx++;
    
    // --- planting_image (with hyperlink) ---
    if (!empty($row['planting_image'])) {
        $full_image_url = $project_base_url . $base_plot_image_path . 'planting_image/' . $row['planting_image'];
        $sheet->setCellValueByColumnAndRow($colIdx + 1, $rowNum, 'ดูรูป'); 
        $sheet->getCellByColumnAndRow($colIdx + 1, $rowNum)->getHyperlink()->setUrl($full_image_url);
        $sheet->getStyleByColumnAndRow($colIdx + 1, $rowNum)->getFont()->setColor(new Color(Color::COLOR_BLUE));
        $sheet->getStyleByColumnAndRow($colIdx + 1, $rowNum)->getFont()->setUnderline(Font::UNDERLINE_SINGLE);
    } else {
        $sheet->setCellValueByColumnAndRow($colIdx + 1, $rowNum, '');
    }
    $colIdx++;
    // --- End planting_image ---

    $sheet->setCellValueByColumnAndRow($colIdx + 1, $rowNum, $row['watering_details']); $colIdx++;
    
    // --- watering_image (with hyperlink) ---
    if (!empty($row['watering_image'])) {
        $full_image_url = $project_base_url . $base_plot_image_path . 'watering_image/' . $row['watering_image'];
        $sheet->setCellValueByColumnAndRow($colIdx + 1, $rowNum, 'ดูรูป'); 
        $sheet->getCellByColumnAndRow($colIdx + 1, $rowNum)->getHyperlink()->setUrl($full_image_url);
        $sheet->getStyleByColumnAndRow($colIdx + 1, $rowNum)->getFont()->setColor(new Color(Color::COLOR_BLUE));
        $sheet->getStyleByColumnAndRow($colIdx + 1, $rowNum)->getFont()->setUnderline(Font::UNDERLINE_SINGLE);
    } else {
        $sheet->setCellValueByColumnAndRow($colIdx + 1, $rowNum, '');
    }
    $colIdx++;
    // --- End watering_image ---

    $sheet->setCellValueByColumnAndRow($colIdx + 1, $rowNum, $row['germination_percentage']); $colIdx++;
    
    // --- germination_image (with hyperlink) ---
    if (!empty($row['germination_image'])) {
        $full_image_url = $project_base_url . $base_plot_image_path . 'germination_image/' . $row['germination_image'];
        $sheet->setCellValueByColumnAndRow($colIdx + 1, $rowNum, 'ดูรูป'); 
        $sheet->getCellByColumnAndRow($colIdx + 1, $rowNum)->getHyperlink()->setUrl($full_image_url);
        $sheet->getStyleByColumnAndRow($colIdx + 1, $rowNum)->getFont()->setColor(new Color(Color::COLOR_BLUE));
        $sheet->getStyleByColumnAndRow($colIdx + 1, $rowNum)->getFont()->setUnderline(Font::UNDERLINE_SINGLE);
    } else {
        $sheet->setCellValueByColumnAndRow($colIdx + 1, $rowNum, '');
    }
    $colIdx++;
    // --- End germination_image ---

    $sheet->setCellValueByColumnAndRow($colIdx + 1, $rowNum, $row['notes']); $colIdx++;
    $sheet->setCellValueByColumnAndRow($colIdx + 1, $rowNum, $row['created_at']); $colIdx++;

    // Apply general cell styling for data rows
    $sheet->getStyle('A' . $rowNum . ':' . $lastHeaderColumnChar . $rowNum)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $sheet->getStyle('A' . $rowNum . ':' . $lastHeaderColumnChar . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    // Override alignment for the 'notes' column to be left-aligned
    $notesColChar = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(array_search('หมายเหตุ', $headers) + 1);
    $sheet->getStyle($notesColChar . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); 

    $rowNum++; 
}

// Auto-size all columns
foreach (range('A', $lastHeaderColumnChar) as $columnID) { 
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}

// Set HTTP headers for file download
$filename = 'ข้อมูลแปลงอ้อย_' . $selected_year . '_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Create XLSX writer and save to output
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

$stmt->close();
$conn->close();

exit;
?>