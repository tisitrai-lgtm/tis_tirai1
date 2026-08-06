<?php
// fetch_data.php
header('Content-Type: application/json; charset=utf-8');
error_reporting(0); // ปิด error output เพื่อไม่ให้ปนกับ JSON

// เชื่อมต่อฐานข้อมูล (ปรับค่าตามของคุณ)
$con = new mysqli("localhost", "root", "", "give_water");
if ($con->connect_errno) {
    echo json_encode([
        "error" => "Failed to connect to database: " . $con->connect_error
    ]);
    exit;
}
$con->set_charset("utf8");

// รับค่าจาก DataTables
$draw = intval($_POST['draw'] ?? 0);
$start = intval($_POST['start'] ?? 0);
$length = intval($_POST['length'] ?? 10);
$searchValue = $con->real_escape_string($_POST['search']['value'] ?? '');
$orderColumnIndex = intval($_POST['order'][0]['column'] ?? 0);
$orderDirection = $_POST['order'][0]['dir'] ?? 'asc';

// กำหนดชื่อคอลัมน์ตามลำดับที่ DataTables ส่งมา (แก้ให้ตรงกับตารางคุณ)
$columns = [
    'year_rai',
    'emp_id',
    'plot_id',
    'contract_number',
    'suga_type',
    'quota',
    'area_rai',
    'water_image1',
    'water_image2',
    'water_image3',
    'flood_image',
    'drought_image',
    'other_image'
];

// จัดการ order column ให้ปลอดภัย
$orderColumn = $columns[$orderColumnIndex] ?? $columns[0];
$orderDirection = $orderDirection === 'desc' ? 'DESC' : 'ASC';

// รับ filter ปี ถ้ามี
$yearFilter = $_POST['year_rai'] ?? '';
$yearFilterEscaped = $con->real_escape_string($yearFilter);

// สร้างเงื่อนไข WHERE เบื้องต้น
$where = [];
if ($yearFilterEscaped !== '') {
    $where[] = "year_rai = '$yearFilterEscaped'";
}

// เงื่อนไขค้นหาจาก search box
if ($searchValue !== '') {
    $searchArray = [];
    foreach ($columns as $col) {
        $searchArray[] = "$col LIKE '%$searchValue%'";
    }
    $where[] = '(' . implode(' OR ', $searchArray) . ')';
}

$whereSql = '';
if (count($where) > 0) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}


// นับจำนวน record ทั้งหมด (ไม่รวม filter)
$totalRecordsQuery = "SELECT COUNT(*) as total FROM image_water";
$totalRecordsResult = $con->query($totalRecordsQuery);
$totalRecords = $totalRecordsResult->fetch_assoc()['total'] ?? 0;

// นับจำนวน record หลัง filter
$totalFilteredQuery = "SELECT COUNT(*) as total FROM image_water $whereSql";
$totalFilteredResult = $con->query($totalFilteredQuery);
$totalFiltered = $totalFilteredResult->fetch_assoc()['total'] ?? 0;

// ดึงข้อมูลจริง
$dataQuery = "SELECT * FROM image_water $whereSql ORDER BY $orderColumn $orderDirection LIMIT $start, $length";
$dataResult = $con->query($dataQuery);

$data = [];
while ($row = $dataResult->fetch_assoc()) {
    $data[] = [
        'year_rai' => $row['year_rai'],
        'emp_id' => $row['emp_id'],
        'plot_id' => $row['plot_id'],
        'contract_number' => $row['contract_number'],
        'suga_type' => $row['suga_type'],
        'quota' => $row['quota'],
        'area_rai' => $row['area_rai'],
        'water_image1' => $row['water_image1'],
        'water_image2' => $row['water_image2'],
        'water_image3' => $row['water_image3'],
        'flood_image' => $row['flood_image'],
        'drought_image' => $row['drought_image'],
        'other_image' => $row['other_image'],
        'edit' => '',   // ใส่ปุ่มแก้ไขถ้าต้องการใน JS หรือที่นี่ก็ได้
        'delete' => ''  // ใส่ปุ่มลบถ้าต้องการใน JS หรือที่นี่ก็ได้
    ];
}

// สร้าง response ตาม DataTables format
$response = [
    "draw" => $draw,
    "recordsTotal" => intval($totalRecords),
    "recordsFiltered" => intval($totalFiltered),
    "data" => $data
];

// ล้าง output buffer (ถ้ามี)
if (ob_get_length()) {
    ob_end_clean();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
