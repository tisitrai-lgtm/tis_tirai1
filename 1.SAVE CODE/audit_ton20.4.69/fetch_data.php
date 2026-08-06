<?php
// เริ่มต้น session (ถ้าจำเป็น)
session_start();

// เชื่อมต่อฐานข้อมูล
require("db_connect.php");

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

// รับค่าปีการผลิตและ Agency ที่ส่งมาจาก dashboard
$selected_year = $_POST['year'] ?? '';
$selected_agency = $_POST['agency'] ?? ''; 
$filter_type = $_POST['filter_type'] ?? 'all'; // 'all', 'has_image', 'no_image'
// 🚨 NEW: รับค่าประเภทข้อมูล: 'estimate', 'evaluate', 'remaining'
$data_type = $_POST['data_type'] ?? 'estimate'; 


// ตรวจสอบว่ามีค่าจำเป็นครบถ้วนหรือไม่
if (empty($selected_year) || empty($selected_agency)) {
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
// 🚨 NEW: 1. กำหนดคอลัมน์รูปภาพที่จะใช้ในการกรองตาม $data_type
// ------------------------------------------------------------------
$image_cols_for_filter = [];
if ($data_type == 'evaluate') {
    // สำหรับ user_dashboard_evaluate.php
    $image_cols_for_filter = ['evaluate_ton_1', 'evaluate_ton_2'];
} elseif ($data_type == 'remaining') {
    // สำหรับ user_dashboard_remaining.php
    $image_cols_for_filter = [
        'remaining_cane_1_img_1', 'remaining_cane_1_img_2', 
        'remaining_cane_2_img_1', 'remaining_cane_2_img_2', 
        'remaining_cane_3_img_1', 'remaining_cane_3_img_2'
    ];
} else {
    // สำหรับ user_dashboard.php (Estimate) หรือ Default
    $image_cols_for_filter = ['estimate_ton_1', 'estimate_ton_2'];
}

$image_filter_condition = '';

// ------------------------------------------------------------------
// 🚨 NEW: 2. สร้างเงื่อนไข SQL สำหรับตัวกรองรูปภาพโดยใช้ $image_cols_for_filter
// ------------------------------------------------------------------

if (!empty($image_cols_for_filter)) {
    if ($filter_type == 'has_image') {
        // เงื่อนไขสำหรับ "มีรูปภาพ": มีค่าในคอลัมน์ใดคอลัมน์หนึ่ง (OR)
        $conditions = [];
        foreach ($image_cols_for_filter as $col) {
            $conditions[] = "(`$col` IS NOT NULL AND `$col` != '')";
        }
        $image_filter_condition = " AND (" . implode(' OR ', $conditions) . ")";

    } elseif ($filter_type == 'no_image') {
        // เงื่อนไขสำหรับ "ไม่มีรูปภาพ": ทุกคอลัมน์ต้องเป็น NULL หรือว่าง (AND)
        $conditions = [];
        foreach ($image_cols_for_filter as $col) {
            $conditions[] = "(`$col` IS NULL OR `$col` = '')";
        }
        $image_filter_condition = " AND (" . implode(' AND ', $conditions) . ")";
    }
}
// ถ้า $filter_type == 'all', $image_filter_condition จะเป็นสตริงว่าง ไม่ต้องกรองเพิ่มเติม


// ------------------------------------------------------------------
// --- 1. ดึงจำนวนข้อมูลทั้งหมดโดยไม่กรอง (สำหรับ recordsTotal) ---
// ส่วนนี้ไม่ต้องการการกรองปี/Agency/รูปภาพ
$totalRecordsSql = "SELECT COUNT(id) AS total FROM cane_plot_data";
$totalRecordsStmt = $conn->prepare($totalRecordsSql);
$totalRecordsStmt->execute();
$totalRecordsResult = $totalRecordsStmt->get_result();
$totalRecords = $totalRecordsResult->fetch_assoc()['total'];
$totalRecordsStmt->close();

// --- 2. สร้าง SQL Query สำหรับดึงข้อมูล (พร้อมการค้นหาและกรองปี/Agency/รูปภาพ) ---
$sql = "SELECT * FROM cane_plot_data WHERE 1=1";
$params = [];
$types = "";

// 🚨 UPDATE: เพิ่มเงื่อนไขปีการผลิตและ Agency ที่เลือก
if (!empty($selected_year) && !empty($selected_agency)) {
    $sql .= " AND production_year = ? AND agency = ?";
    $params[] = $selected_year;
    $params[] = $selected_agency; 
    $types .= "ss"; 
}

// 🚨 NEW: เพิ่มเงื่อนไขกรองรูปภาพ (ที่สร้างแบบ Dynamic ด้านบน)
$sql .= $image_filter_condition;


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
    $params[] = $searchParam; $params[] = $searchParam;
    $params[] = $searchParam; $params[] = $searchParam;
    $params[] = $searchParam; $params[] = $searchParam;
    $params[] = $searchParam; $params[] = $searchParam;
    $types .= "ssssssss"; 
}

// --- 3. ดึงจำนวนข้อมูลหลังจากกรอง (สำหรับ recordsFiltered) ---
// เราสามารถใช้ $sql หลัก แต่ลบ ORDER BY และ LIMIT ออก
$filteredRecordsSql = "SELECT COUNT(id) AS total FROM cane_plot_data WHERE 1=1";
$filteredParams = [];
$filteredTypes = "";

// 🚨 UPDATE: เพิ่มเงื่อนไขปีการผลิตและ Agency ที่เลือกใน filteredRecordsSql
if (!empty($selected_year) && !empty($selected_agency)) {
    $filteredRecordsSql .= " AND production_year = ? AND agency = ?";
    $filteredParams[] = $selected_year;
    $filteredParams[] = $selected_agency;
    $filteredTypes .= "ss";
}

// 🚨 NEW: เพิ่มเงื่อนไขกรองรูปภาพในนับรวม
$filteredRecordsSql .= $image_filter_condition;


// 🚨 UPDATE: เพิ่มเงื่อนไขการค้นหาใน filteredRecordsSql
if (!empty($searchValue)) {
    $filteredRecordsSql .= " AND (
        production_year LIKE ? OR agency LIKE ? OR emp_number LIKE ? OR
        contract_number LIKE ? OR quota LIKE ? OR plot_id LIKE ? OR
        suga_type LIKE ? OR notes LIKE ?
    )";
    $searchParam = "%" . $searchValue . "%";
    // 8 พารามิเตอร์ค้นหา
    $filteredParams[] = $searchParam; $filteredParams[] = $searchParam;
    $filteredParams[] = $searchParam; $filteredParams[] = $searchParam;
    $filteredParams[] = $searchParam; $filteredParams[] = $searchParam;
    $filteredParams[] = $searchParam; $filteredParams[] = $searchParam;
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


// --- 4. เพิ่มการเรียงลำดับและการจำกัดจำนวน (สำหรับ data) ---
$allowedOrderColumns = [
    'id', 'production_year', 'emp_number','agency', 'contract_number', 'quota', 'plot_id',
    'rai_area', 'suga_type', 'notes', 'created_at' 
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
    error_log("Prepare failed on main query: " . $conn->error . " SQL: " . $sql);
    echo json_encode(["error" => "Prepare failed on main query: " . $conn->error]);
    exit;
}

if (!empty($finalParams)) {
    if (strlen($finalTypes) != count($finalParams)) {
         error_log("Binding parameters count mismatch. Expected: " . strlen($finalTypes) . ", Got: " . count($finalParams));
         echo json_encode(["error" => "Binding parameters count mismatch for main query."]);
         exit;
    }
    // ใช้ call_user_func_array แทน bind_param เพื่อจัดการ array_merge ได้ง่ายขึ้น
    $stmt->bind_param($finalTypes, ...$finalParams);
}

$stmt->execute();
$result = $stmt->get_result();

$data = [];
$image_base_url = "ton_aoi/"; 

while ($row = $result->fetch_assoc()) {
    
    // การสร้าง Base Path ตามโครงสร้างใหม่: ton_aoi/uploads/{year}/{agency}/{contract}/{plot}/
    $sanitized_year = sanitizeForPath($row['production_year']);
    $sanitized_agency = sanitizeForPath($row['agency']);
    $sanitized_contract = sanitizeForPath($row['contract_number']);
    $sanitized_plot = sanitizeForPath($row['plot_id']);

    $basePath = "{$image_base_url}uploads/{$sanitized_year}/{$sanitized_agency}/{$sanitized_contract}/{$sanitized_plot}/";

    // สร้าง URL เต็มสำหรับรูปภาพ 10 คอลัมน์ (ส่วนนี้ใช้โค้ดเดิมของคุณ)
    
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