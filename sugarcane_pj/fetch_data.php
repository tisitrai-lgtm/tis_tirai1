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

// --- 1. ดึงจำนวนข้อมูลทั้งหมดโดยไม่กรอง (สำหรับ recordsTotal) ---
$totalRecordsSql = "SELECT COUNT(id) AS total FROM soil_data";
$totalRecordsStmt = $conn->prepare($totalRecordsSql);
$totalRecordsStmt->execute();
$totalRecordsResult = $totalRecordsStmt->get_result();
$totalRecords = $totalRecordsResult->fetch_assoc()['total'];
$totalRecordsStmt->close();

// --- 2. สร้าง SQL Query สำหรับดึงข้อมูล (พร้อมการค้นหาและกรองปี) ---
$sql = "SELECT * FROM soil_data WHERE 1=1";
$params = [];
$types = "";

// เพิ่มเงื่อนไขปีการผลิตที่เลือก
if (!empty($selected_year)) {
    $sql .= " AND production_year = ?";
    $params[] = $selected_year;
    $types .= "s";
}

// เพิ่มเงื่อนไขการค้นหา (Global search)
if (!empty($searchValue)) {
    $sql .= " AND (
        production_year LIKE ? OR
        agency LIKE ? OR
        contract_number LIKE ? OR
        plot_id LIKE ? OR
        soil_type LIKE ? OR
        soil_preparation_details LIKE ? OR
        cane_variety LIKE ? OR
        planting_details LIKE ? OR
        watering_details LIKE ? OR
        notes LIKE ?
    )";
    $searchParam = "%" . $searchValue . "%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ssssssssss";
}

// --- 3. ดึงจำนวนข้อมูลหลังจากกรอง (สำหรับ recordsFiltered) ---
$filteredRecordsSql = "SELECT COUNT(id) AS total FROM ($sql) AS filtered_data";
$filteredRecordsStmt = $conn->prepare($filteredRecordsSql);

if (!empty($params)) {
    $filteredRecordsStmt->bind_param($types, ...$params);
}
$filteredRecordsStmt->execute();
$filteredRecordsResult = $filteredRecordsStmt->get_result();
$filteredRecords = $filteredRecordsResult->fetch_assoc()['total'];
$filteredRecordsStmt->close();

// --- 4. เพิ่มการเรียงลำดับและการจำกัดจำนวน (สำหรับ data) ---
$allowedOrderColumns = [
    'id', 'production_year', 'agency', 'contract_number', 'quota', 'plot_id',
    'rai_area', 'soil_type', 'soil_preparation_details', 'cane_variety',
    'planting_details', 'watering_details', 'germination_percentage',
    'notes', 'created_at'
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

if (!empty($finalParams)) {
    $stmt->bind_param($finalTypes, ...$finalParams);
}

$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    // ฟังก์ชันช่วยในการคลีนชื่อสำหรับใช้เป็น Path (รวมสระและวรรณยุกต์ไทย)
    if (!function_exists('sanitizeForPath')) {
        function sanitizeForPath($string) {
            if (empty($string)) return 'unspecified';
            return preg_replace('/[^\p{L}\p{M}\p{N}_-]/u', '', str_replace(' ', '_', $string));
        }
    }

    $basePath = "uploads/" . sanitizeForPath($row['production_year']) . "/" . 
                sanitizeForPath($row['agency']) . "/" . 
                sanitizeForPath($row['contract_number']) . "/" . 
                sanitizeForPath($row['plot_id']) . "/";

    $row['soil_image'] = !empty($row['soil_image']) ? $basePath . 'soil_image/' . $row['soil_image'] : '';
    $row['soil_preparation_image'] = !empty($row['soil_preparation_image']) ? $basePath . 'soil_preparation_image/' . $row['soil_preparation_image'] : '';
    $row['cane_variety_image'] = !empty($row['cane_variety_image']) ? $basePath . 'cane_variety_image/' . $row['cane_variety_image'] : '';
    $row['planting_image'] = !empty($row['planting_image']) ? $basePath . 'planting_image/' . $row['planting_image'] : '';
    $row['watering_image'] = !empty($row['watering_image']) ? $basePath . 'watering_image/' . $row['watering_image'] : '';
    $row['germination_image'] = !empty($row['germination_image']) ? $basePath . 'germination_image/' . $row['germination_image'] : '';

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