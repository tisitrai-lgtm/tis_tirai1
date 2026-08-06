<?php
// export_excel.php

// เชื่อมต่อฐานข้อมูล
require_once 'db_connect.php';

// โหลดไฟล์ไลบรารี SimpleXLSXGen.php จากโฟลเดอร์ที่ถูกต้อง
// ต้องแน่ใจว่าโฟลเดอร์ "simplexlsxgen-master" ถูกอัปโหลดขึ้นไปในที่เดียวกับไฟล์นี้
require_once 'simplexlsxgen-master/src/SimpleXLSXGen.php';

// ตั้งค่าสำหรับ PHP เพื่อรองรับการทำงานที่ใช้ทรัพยากรมาก
ini_set('memory_limit', '256M'); 
set_time_limit(600); 

// รับค่าปีการผลิตจาก URL
$selected_year = $_GET['year'] ?? '';

// ถ้าไม่ได้เลือกปี ให้ redirect กลับไปหน้า dashboard พร้อมข้อความแจ้งเตือน
if (empty($selected_year)) {
    header('Location: dashboard.php?export_status=error&message=' . urlencode('กรุณาเลือกปีการผลิตที่ต้องการดาวน์โหลด'));
    exit;
}

// --- สร้าง Dynamic Base URL สำหรับ Project Root ---
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$script_path = dirname($_SERVER['PHP_SELF']);
$script_path = rtrim($script_path, '/');
$project_base_url = $protocol . '://' . $host . ($script_path ? $script_path . '/' : '/');
// --- End Dynamic Base URL ---

// คำสั่ง SQL เพื่อดึงข้อมูลทั้งหมดตามปีที่เลือก
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
    header('Location: dashboard.php?export_status=error&message=' . urlencode('เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: ' . $conn->error));
    exit;
}
$stmt->bind_param("s", $selected_year);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: dashboard.php?export_status=info&message=' . urlencode('ไม่พบข้อมูลสำหรับปีการผลิต ' . htmlspecialchars($selected_year) . ' ที่จะส่งออก'));
    exit;
}

// สร้างอาร์เรย์สำหรับเก็บข้อมูลที่จะ Export
$data = [];

// กำหนด Header ของตาราง
// ใช้ <b>...</b> เพื่อให้ข้อความเป็นตัวหนา
$headers = [
    '<b>ปีผลิต</b>', '<b>หน่วยงาน</b>', '<b>เลขสัญญา</b>', '<b>โควต้า</b>', '<b>ID แปลง</b>', '<b>ไร่</b>', '<b>ชนิดดิน</b>',
    '<b>รูปดิน</b>', '<b>เตรียมดิน</b>', '<b>รูปเตรียมดิน</b>', '<b>พันธุ์อ้อย</b>', '<b>รูปพันธุ์อ้อย</b>',
    '<b>การปลูก</b>', '<b>รูปปลูก</b>', '<b>การให้น้ำ</b>', '<b>รูปให้น้ำ</b>', '<b>เปอร์เซ็นต์</b>', '<b>รูปเปอร์เซ็นต์</b>',
    '<b>หมายเหตุ</b>', '<b>วันที่บันทึก</b>'
];
$data[] = $headers;

// วนลูปเพื่อดึงข้อมูลจากฐานข้อมูลและจัดเรียงลงในอาร์เรย์
while ($row = $result->fetch_assoc()) {
    // ฟังก์ชันสำหรับ clean ชื่อ Path (เหมือนกับโค้ดตอนอัปโหลด)
    if (!function_exists('sanitizeForPath')) {
        function sanitizeForPath($string) {
            if (empty($string)) return 'unspecified';
            return preg_replace('/[^\p{L}\p{M}\p{N}_-]/u', '', str_replace(' ', '_', $string));
        }
    }
    
    $sanitized_production_year = sanitizeForPath($row['production_year']);
    $sanitized_agency = sanitizeForPath($row['agency']);
    $sanitized_contract_number = sanitizeForPath($row['contract_number']);
    $sanitized_plot_id = sanitizeForPath($row['plot_id']);
    
    $base_plot_image_path = "uploads/{$sanitized_production_year}/{$sanitized_agency}/{$sanitized_contract_number}/{$sanitized_plot_id}/";
    
    // สร้าง URL รูปภาพที่สมบูรณ์ในรูปแบบ Excel Hyperlink
    // ใช้ `=HYPERLINK(...)` เพื่อสร้างลิงก์ที่คลิกได้ใน Excel
    $soil_image_url = !empty($row['soil_image']) ? "=HYPERLINK(\"{$project_base_url}{$base_plot_image_path}soil_image/{$row['soil_image']}\", \"ดูรูป\")" : '';
    $soil_prep_image_url = !empty($row['soil_preparation_image']) ? "=HYPERLINK(\"{$project_base_url}{$base_plot_image_path}soil_preparation_image/{$row['soil_preparation_image']}\", \"ดูรูป\")" : '';
    $cane_image_url = !empty($row['cane_variety_image']) ? "=HYPERLINK(\"{$project_base_url}{$base_plot_image_path}cane_variety_image/{$row['cane_variety_image']}\", \"ดูรูป\")" : '';
    $planting_image_url = !empty($row['planting_image']) ? "=HYPERLINK(\"{$project_base_url}{$base_plot_image_path}planting_image/{$row['planting_image']}\", \"ดูรูป\")" : '';
    $watering_image_url = !empty($row['watering_image']) ? "=HYPERLINK(\"{$project_base_url}{$base_plot_image_path}watering_image/{$row['watering_image']}\", \"ดูรูป\")" : '';
    $germination_image_url = !empty($row['germination_image']) ? "=HYPERLINK(\"{$project_base_url}{$base_plot_image_path}germination_image/{$row['germination_image']}\", \"ดูรูป\")" : '';

    // เพิ่มข้อมูลลงในอาร์เรย์
    $data[] = [
        $row['production_year'],
        $row['agency'],
        $row['contract_number'],
        $row['quota'],
        $row['plot_id'],
        $row['rai_area'],
        $row['soil_type'],
        $soil_image_url,
        $row['soil_preparation_details'],
        $soil_prep_image_url,
        $row['cane_variety'],
        $cane_image_url,
        $row['planting_details'],
        $planting_image_url,
        $row['watering_details'],
        $watering_image_url,
        $row['germination_percentage'],
        $germination_image_url,
        $row['notes'],
        $row['created_at']
    ];
}

$stmt->close();
$conn->close();

// สร้างไฟล์ Excel
$xlsx = \Shuchkin\SimpleXLSXGen::fromArray($data);

// ตั้งค่า Header สำหรับการดาวน์โหลด
$filename = 'ข้อมูลแปลงอ้อย_' . $selected_year . '_' . date('Ymd_His') . '.xlsx';
$xlsx->downloadAs($filename); 

exit;
// ไม่ต้องใส่ ?> ปิดท้าย