<?php

// fetch_data.php
header('Content-Type: application/json; charset=utf-8');
error_reporting(0); // ปิดเพื่อไม่ให้ Error Message หลุดไปทำลายโครงสร้าง JSON
session_start();

// เชื่อมต่อฐานข้อมูล (ใช้ dbconnect.php เหมือนไฟล์อื่นในระบบ แทนการ hardcode ค่า local)
require("dbconnect.php");

// รับค่าจาก DataTables
$draw = intval($_POST['draw'] ?? 0);
$start = intval($_POST['start'] ?? 0);
$length = intval($_POST['length'] ?? 10);
$searchValue = $con->real_escape_string($_POST['search']['value'] ?? '');

// กำหนดลำดับคอลัมน์ให้ตรงกับ DataTables (ตรวจสอบว่าชื่อตรงกับใน DB เป๊ะๆ)
$columns = [
    0 => 'year_rai',
    1 => 'emp_id',
    2 => 'plot_id',
    3 => 'contract_number',
    4 => 'suga_type',
    5 => 'quota',
    6 => 'area_rai',
    7 => 'water_image1',
    8 => 'water_image2',
    9 => 'water_image3',
    10 => 'flood_image',
    11 => 'drought_image',
    12 => 'other_image'
];

// จัดการเรื่องการเรียงลำดับ
$orderColumnIndex = intval($_POST['order'][0]['column'] ?? 2);
$orderDir = (isset($_POST['order'][0]['dir']) && $_POST['order'][0]['dir'] === 'asc') ? 'ASC' : 'DESC';
$orderColumn = $columns[$orderColumnIndex] ?? 'plot_id';

// ดึงปีการผลิตจาก Session
$yearFilter = $_SESSION['selected_year'] ?? ''; 
$yearFilterEscaped = $con->real_escape_string($yearFilter);

$whereClause = [];

// 1. Filter ตามปีที่เลือก
if ($yearFilterEscaped !== '') {
    $whereClause[] = "year_rai = '$yearFilterEscaped'";
}

// 2. Filter จากช่องค้นหา (แก้ไข: ตัด farmer_name ออกเพราะในรูป DB ไม่มีคอลัมน์นี้)
if ($searchValue !== '') {
    $searchFields = ['emp_id', 'plot_id', 'contract_number', 'quota', 'suga_type'];
    $searchConditions = [];
    foreach ($searchFields as $field) {
        $searchConditions[] = "$field LIKE '%$searchValue%'";
    }
    $whereClause[] = '(' . implode(' OR ', $searchConditions) . ')';
}

$whereSql = count($whereClause) > 0 ? 'WHERE ' . implode(' AND ', $whereClause) : '';

// --- ดึงข้อมูล ---

// 1. นับจำนวนทั้งหมด (เดิมเธอนับรวมทั้ง DB)
// ให้เปลี่ยนเป็นนับเฉพาะปีที่เลือก เพื่อให้ _MAX_ ในหน้าบ้านเป็นยอดของปีนั้นๆ
$countSql = "SELECT COUNT(*) as total FROM image_water";
if ($yearFilterEscaped !== '') {
    $countSql .= " WHERE year_rai = '$yearFilterEscaped'";
}
$resTotal = $con->query($countSql);
$totalRecords = $resTotal->fetch_assoc()['total'] ?? 0;

// 2. ส่วน recordsFiltered ยังคงใช้ $whereSql เหมือนเดิม (เพราะรวมการ Search ด้วย)
$resFiltered = $con->query("SELECT COUNT(*) as total FROM image_water $whereSql");
$totalFiltered = $resFiltered->fetch_assoc()['total'] ?? 0;

// ดึงข้อมูลจริง
$dataQuery = "SELECT * FROM image_water $whereSql ORDER BY $orderColumn $orderDir LIMIT $start, $length";
$dataResult = $con->query($dataQuery);

$data = [];
if ($dataResult) {
    while ($row = $dataResult->fetch_assoc()) {
        $data[] = [
            'year_rai'        => $row['year_rai'],
            'emp_id'          => $row['emp_id'],
            'plot_id'         => $row['plot_id'],
            'contract_number' => $row['contract_number'],
            'suga_type'       => $row['suga_type'],
            'quota'           => $row['quota'],
            'area_rai'        => $row['area_rai'],
            'water_image1'    => $row['water_image1'],
            'water_image2'    => $row['water_image2'],
            'water_image3'    => $row['water_image3'],
            'flood_image'     => $row['flood_image'],
            'drought_image'   => $row['drought_image'],
            'other_image'     => $row['other_image']
        ];
    }
}

// เตรียม Response
$response = [
    "draw"            => $draw,
    "recordsTotal"    => intval($totalRecords),
    "recordsFiltered" => intval($totalFiltered),
    "data"            => $data
];

// ล้าง Output Buffer เพื่อความปลอดภัย (ป้องกันช่องว่างหรือ Error อื่นๆ หลุดไป)
if (ob_get_length()) ob_end_clean();

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;