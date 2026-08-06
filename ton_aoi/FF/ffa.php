<?php
// เริ่มต้น session (ถ้าจำเป็น)
session_start();

// เชื่อมต่อฐานข้อมูล
require("db_connect.php"); // ตรวจสอบให้แน่ใจว่าไฟล์นี้ถูกต้อง

// ตั้งค่า Header ให้เป็น JSON เพื่อให้ DataTables รู้ว่านี่คือข้อมูล JSON
header('Content-Type: application/json');

// รับค่าจาก DataTables
$draw = $_POST['draw'] ?? 1;
$start = $_POST['start'] ?? 0;
$length = $_POST['length'] ?? 10;
$searchValue = $_POST['search']['value'] ?? '';

$orderColumnIndex = $_POST['order'][0]['column'] ?? 0;
$orderColumnName = $_POST['columns'][$orderColumnIndex]['data'] ?? 'id';
$orderDir = $_POST['order'][0]['dir'] ?? 'desc';

// รับค่าปีการผลิตที่ส่งมาจาก dashboard.php
$selected_year = $_POST['year'] ?? '';
// 🚨 ลบ: $selected_agency = $_POST['agency'] ?? ''; // ลบการรับค่า agency ออก

// ตรวจสอบว่ามีค่าจำเป็นครบถ้วนหรือไม่
if (empty($selected_year)) {
    echo json_encode([
        "draw" => (int)$draw,
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => []
    ]);
    exit;
}


// ฟังก์ชันทำความสะอาดชื่อเพื่อใช้ใน Path (ใช้เหมือนใน water_insertdata.php)
if (!function_exists('sanitizeForPath')) {
    function sanitizeForPath($string) {
        // ใช้ urlencode เพื่อความปลอดภัยขั้นพื้นฐาน
        return urlencode(str_replace(' ', '_', $string));
    }
}


// ------------------------------------------------------------------
// --- 1. ดึงจำนวนข้อมูลทั้งหมดโดยไม่กรอง (สำหรับ recordsTotal) ---
$totalRecordsSql = "SELECT COUNT(id) AS total FROM cane_plot_data";
$totalRecordsStmt = $conn->prepare($totalRecordsSql);
$totalRecordsStmt->execute();
$totalRecordsResult = $totalRecordsStmt->get_result();
$totalRecords = $totalRecordsResult->fetch_assoc()['total'];
$totalRecordsStmt->close();

// ------------------------------------------------------------------
// --- 2. สร้าง SQL Query สำหรับดึงข้อมูล (พร้อมการค้นหาและกรองปี) ---
// ------------------------------------------------------------------
$sql = "SELECT * FROM cane_plot_data WHERE 1=1";
$params = [];
$types = "";

// 🚨 แก้ไข: ใช้เฉพาะเงื่อนไขปีการผลิตที่เลือก
if (!empty($selected_year)) {
    $sql .= " AND production_year = ? ";
    $params[] = $selected_year;
    $types .= "s"; // s สำหรับ year เท่านั้น
}

// 🚨 แก้ไข: ปรับปรุงเงื่อนไขการค้นหาให้ตรงกับคอลัมน์ของ cane_plot_data
if (!empty($searchValue)) {
    $sql .= " AND (
        production_year LIKE ? OR
        agency LIKE ? OR
        emp_number LIKE ? OR
        contract_number LIKE ? OR
        quota LIKE ? OR
        plot_id LIKE ? OR
        suga_type LIKE ? OR
        notes LIKE ?
    )";
    $searchParam = "%" . $searchValue . "%";
    
    // 8 พารามิเตอร์ค้นหา
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ssssssss"; // 8 's'
}

// ------------------------------------------------------------------
// --- 3. ดึงจำนวนข้อมูลหลังจากกรอง (สำหรับ recordsFiltered) ---
// ------------------------------------------------------------------
$filteredRecordsSql = "SELECT COUNT(id) AS total FROM cane_plot_data WHERE 1=1";
$filteredParams = [];
$filteredTypes = "";

// 🚨 แก้ไข: ใช้เฉพาะเงื่อนไขปีการผลิตที่เลือกใน filteredRecordsSql
if (!empty($selected_year)) {
    $filteredRecordsSql .= " AND production_year = ? ";
    $filteredParams[] = $selected_year;
    $filteredTypes .= "s"; // s สำหรับ year เท่านั้น
}

// 🚨 UPDATE: เพิ่มเงื่อนไขการค้นหาใน filteredRecordsSql
if (!empty($searchValue)) {
    $filteredRecordsSql .= " AND (
        production_year LIKE ? OR
        agency LIKE ? OR
        emp_number LIKE ? OR
        contract_number LIKE ? OR
        quota LIKE ? OR
        plot_id LIKE ? OR
        suga_type LIKE ? OR
        notes LIKE ?
    )";
    $searchParam = "%" . $searchValue . "%";
    // 8 พารามิเตอร์ค้นหา
    $filteredParams[] = $searchParam;
    $filteredParams[] = $searchParam;
    $filteredParams[] = $searchParam;
    $filteredParams[] = $searchParam;
    $filteredParams[] = $searchParam;
    $filteredParams[] = $searchParam;
    $filteredParams[] = $searchParam;
    $filteredParams[] = $searchParam;
    $filteredTypes .= "ssssssss"; 
}

$filteredRecordsStmt = $conn->prepare($filteredRecordsSql);

if (!empty($filteredParams)) {
    $filteredRecordsStmt->bind_param($filteredTypes, ...$filteredParams);
}
$filteredRecordsStmt->execute();
$filteredRecordsResult = $filteredRecordsStmt->get_result();
$filteredRecords = $filteredRecordsResult->fetch_assoc()['total'];
$filteredRecordsStmt->close();


// ------------------------------------------------------------------
// --- 4. เพิ่มการเรียงลำดับและการจำกัดจำนวน (สำหรับ data) ---
// ------------------------------------------------------------------
$allowedOrderColumns = [
    'id', 'production_year', 'emp_number','agency', 'contract_number', 'quota', 'plot_id',
    'rai_area', 'suga_type', 'notes', 'created_at' // คอลัมน์ที่อนุญาตให้เรียง
];
if (!in_array($orderColumnName, $allowedOrderColumns)) {
    $orderColumnName = 'id';
}

$sql .= " ORDER BY " . $orderColumnName . " " . $orderDir;
$sql .= " LIMIT ?, ?";

$limitParams = [$start, $length];
$limitTypes = "ii";

$finalParams = array_merge($params, $limitParams);
$finalTypes = $types . $limitTypes;

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    // ส่ง Error Response
    echo json_encode(["error" => "Prepare failed: " . $conn->error]);
    exit;
}

if (!empty($finalParams)) {
    // ต้องตรวจสอบว่า types และ params มีจำนวนตรงกัน!
    if (strlen($finalTypes) != count($finalParams)) {
        // กรณีผิดพลาดในการ Bind 
        error_log("Binding parameter mismatch in fetch_data_admin.php: Types length=" . strlen($finalTypes) . ", Params count=" . count($finalParams));
        echo json_encode(["error" => "Binding parameters count mismatch."]);
        exit;
    }
    $stmt->bind_param($finalTypes, ...$finalParams);
}

$stmt->execute();
$result = $stmt->get_result();

$data = [];
$image_base_url = "ton_aoi/"; // Base URL ของโปรเจกต์ (ถ้าคุณรันจาก root)

while ($row = $result->fetch_assoc()) {
    
    // 🚨 การสร้าง Base Path ตามโครงสร้างใหม่: ton_aoi/uploads/{year}/{agency}/{contract}/{plot}/
    // ใช้ฟังก์ชัน sanitizeForPath เพื่อให้ URL ปลอดภัยและตรงกับที่บันทึกไว้ใน water_insertdata.php
    $sanitized_year = sanitizeForPath($row['production_year']);
    $sanitized_agency = sanitizeForPath($row['agency']);
    $sanitized_contract = sanitizeForPath($row['contract_number']);
    $sanitized_plot = sanitizeForPath($row['plot_id']);

    $basePath = "{$image_base_url}uploads/{$sanitized_year}/{$sanitized_agency}/{$sanitized_contract}/{$sanitized_plot}/";

    // 🚨 สร้าง URL เต็มสำหรับรูปภาพ (ตามที่คุณกำหนดไว้)
    
    // กลุ่ม Estimate Ton
    $row['estimate_ton_1'] = !empty($row['estimate_ton_1']) ? $basePath . 'estimate_ton/' . $row['estimate_ton_1'] : '';
    $row['estimate_ton_2'] = !empty($row['estimate_ton_2']) ? $basePath . 'estimate_ton/' . $row['estimate_ton_2'] : '';
    
    // กลุ่ม Evaluate Ton
    $row['evaluate_ton_1'] = !empty($row['evaluate_ton_1']) ? $basePath . 'evaluate_ton/' . $row['evaluate_ton_1'] : '';
    $row['evaluate_ton_2'] = !empty($row['evaluate_ton_2']) ? $basePath . 'evaluate_ton/' . $row['evaluate_ton_2'] : '';
    
    // กลุ่ม Remaining Cane 1
    $row['remaining_cane_1_img_1'] = !empty($row['remaining_cane_1_img_1']) ? $basePath . 'remaining_cane_1/' . $row['remaining_cane_1_img_1'] : '';
    $row['remaining_cane_1_img_2'] = !empty($row['remaining_cane_1_img_2']) ? $basePath . 'remaining_cane_1/' . $row['remaining_cane_1_img_2'] : '';
    
    // กลุ่ม Remaining Cane 2
    $row['remaining_cane_2_img_1'] = !empty($row['remaining_cane_2_img_1']) ? $basePath . 'remaining_cane_2/' . $row['remaining_cane_2_img_1'] : '';
    $row['remaining_cane_2_img_2'] = !empty($row['remaining_cane_2_img_2']) ? $basePath . 'remaining_cane_2/' . $row['remaining_cane_2_img_2'] : '';
    
    // กลุ่ม Remaining Cane 3
    $row['remaining_cane_3_img_1'] = !empty($row['remaining_cane_3_img_1']) ? $basePath . 'remaining_cane_3/' . $row['remaining_cane_3_img_1'] : '';
    $row['remaining_cane_3_img_2'] = !empty($row['remaining_cane_3_img_2']) ? $basePath . 'remaining_cane_3/' . $row['remaining_cane_3_img_2'] : '';
    // 🚨 สิ้นสุดการสร้าง Path รูปภาพ

    $data[] = $row;
}

$stmt->close();
$conn->close();

// ส่งออกข้อมูลในรูปแบบ JSON
echo json_encode([
    "draw" => (int)$draw,
    "recordsTotal" => (int)$totalRecords,
    "recordsFiltered" => (int)$filteredRecords,
    "data" => $data
]);

?>