<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/XBase/Table.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// ฟังก์ชันสำหรับจัดรูปแบบวันที่ (แก้ไขแล้ว)
function formatDbfDate($val) {
    $val = trim((string)$val);
    if (empty($val) || $val == '00000000') return '';
    
    // ล้างค่าให้เหลือแต่ตัวเลข
    $digits = preg_replace('/[^0-9]/', '', $val);
    
    if (strlen($digits) === 8) {
        $y = substr($digits, 0, 4); 
        $m = substr($digits, 4, 2); 
        $d = substr($digits, 6, 2);
        
        if (is_numeric($y) && (int)$y > 1900 && checkdate((int)$m, (int)$d, (int)$y)) {
            return "$d/$m/$y";
        }
    }
    return $val;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['dbf_file'])) {
    require 'db_connect.php'; 
    $file_tmp = $_FILES['dbf_file']['tmp_name'];
    try {
        $table = new \XBase\Table($file_tmp);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // รายชื่อหัวตารางมาตรฐาน 41 ฟิลด์
        $fixedHeaders = [
            'STATN_CODE', 'WORK_DATE', 'WEIGH_DOCC', 'FARMR_CODE', 'FARMR_NAME', 
            'WEIGH_QUE', 'SWEET_CANE', 'PURI_CANE', 'BRIX_CANE', 'POL_CANE', 
            'WEIGH_CANE', 'CANE_TYPE', 'TRUCK_CODE', 'CANE_TCCUT', 'CANE_TCHOL', 
            'TRUCK_RATE', 'TRUCK_UP', 'TRUCK_MCUT', 'FARMR_TRCK', 'FARMR_TNAM', 
            'FARMR_UP', 'FARMR_UNAM', 'FARMR_CUT', 'FARMR_CNAM', 'FLAG_CUT1', 
            'TRUCK_DUCK', 'DATE_CUT', 'TIME_CUT', 'LAND_NUMB', 'LAND_ID', 
            'DATE_QUE', 'TIME_QUE', 'WEIGH_DTIN', 'WEIGH_DTOU', 'WEIGH_TMIN', 
            'WEIGH_TMOU', 'TIME_HFAC', 'TIME_MFAC', 'TIME_HTOTL', 'TIME_MTOTL', 'FLAG_ID'
        ];

        $dateFields = ['WORK_DATE', 'DATE_CUT', 'DATE_QUE', 'WEIGH_DTIN', 'WEIGH_DTOU'];

        $sheet->fromArray($fixedHeaders, NULL, 'A1');
        $rowCount = 2;

        while ($record = $table->nextRecord()) {
            $rowData = [];
            for ($i = 0; $i < count($fixedHeaders); $i++) {
                $val = $record->getByIndex($i);
                $colName = $fixedHeaders[$i];

                if (in_array($colName, $dateFields)) {
                    $val = formatDbfDate($val);
                } else if (is_string($val)) {
                    $val = iconv('CP874', 'UTF-8//IGNORE', $val);
                }
                $rowData[] = $val;
            }

            // บันทึก MySQL
            $placeholders = implode(',', array_fill(0, count($rowData), '?'));
            $sql = "INSERT INTO conversion_logs (" . implode(',', $fixedHeaders) . ") VALUES ($placeholders)";
            $pdo->prepare($sql)->execute($rowData);

            // เขียนลง Excel
            $sheet->fromArray($rowData, NULL, 'A' . $rowCount);
            $rowCount++;
        }
        $table->close();
        
        // ปรับ Format ให้เป็น Text ทั้งหมดเพื่อป้องกันเลขเพี้ยน
        $lastCol = $sheet->getHighestColumn();
        $sheet->getStyle("A1:{$lastCol}{$rowCount}")->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

        // ส่งไฟล์ให้ดาวน์โหลด
        ob_clean(); // ล้าง Output buffer ป้องกัน Warning ก่อนหน้ามาขวาง
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Sugar_Data_'.date('Ymd_His').'.xlsx"');
        header('Cache-Control: max-age=0');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    } catch (Exception $e) { 
        die("Error: " . $e->getMessage()); 
    }
}