<?php
// เรียกไฟล์ autoloader ของ Composer เพื่อใช้งานไลบรารี
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

// เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล
include 'db_connect.php';

// ตรวจสอบว่ามีการอัปโหลดไฟล์มาหรือไม่
if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == UPLOAD_ERR_OK) {
    $file_tmp_name = $_FILES['csv_file']['tmp_name'];
    $file_extension = pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION);

    // ตรวจสอบชนิดของไฟล์
    $allowed_extensions = ['csv', 'xlsx'];
    if (!in_array(strtolower($file_extension), $allowed_extensions)) {
        echo "Error: Only CSV and XLSX files are allowed.";
        exit;
    }

    try {
        // โหลดไฟล์ด้วย IOFactory ของ PhpSpreadsheet
        $spreadsheet = IOFactory::load($file_tmp_name);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows_data = $worksheet->toArray();
        
        // ข้ามบรรทัดแรกที่เป็น Header
        array_shift($rows_data);

        // สร้างคำสั่ง SQL สำหรับเตรียมการนำเข้าข้อมูล
        $sql = "INSERT INTO sugar_contracts (plot_id, quota_name, contract_number, sugar_type, promotion_unit, promoter_area) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if ($stmt === FALSE) {
            echo "Error preparing statement: " . $conn->error;
            exit;
        }

        $rows_inserted = 0;
        
        // อ่านข้อมูลจากอาเรย์ทีละแถว
        foreach ($rows_data as $data) {
            // ตรวจสอบว่ามีข้อมูลในแถวหรือไม่
            if (empty(array_filter($data))) {
                continue; // ข้ามแถวที่ว่างเปล่า
            }

            // กำหนดค่าจากแต่ละคอลัมน์ใน Excel/CSV ให้ตรงกับตัวแปร
            // (อ้างอิงจากตำแหน่งคอลัมน์ในไฟล์ Excel/CSV ที่เริ่มจาก 0)
            $plot_id = $data[5] ?? null;      
            $quota_name = $data[4] ?? null;    
            $contract_number = $data[3] ?? null; 
            $sugar_type = $data[1] ?? null;     
            $promotion_unit = $data[0] ?? null; 
            $promoter_area = $data[2] ?? null;  
            
            // นำเข้าข้อมูล
            if ($stmt->execute([$plot_id, $quota_name, $contract_number, $sugar_type, $promotion_unit, $promoter_area])) {
                $rows_inserted++;
            }
        }
        
        $conn = null;
        echo "นำเข้าข้อมูลสำเร็จแล้ว! $rows_inserted แถวถูกเพิ่ม.";

    } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
        echo "Error loading file: " . $e->getMessage();
    }
} else {
    echo "กรุณาเลือกไฟล์ CSV หรือ Excel เพื่ออัปโหลด";
}
?>