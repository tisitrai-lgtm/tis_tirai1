<?php
// fetch_summary_counts.php - ดึงข้อมูลสรุปจำนวนแปลงทั้งหมดและแปลงที่มีรูปภาพ

// เริ่มต้น session (ถ้าจำเป็น)
// session_start(); 
require("db_connect.php"); 

header('Content-Type: application/json');

$selected_year = $_POST['year'] ?? '';
$selected_agency = $_POST['agency'] ?? '';
// 🚨 NEW: รับค่าประเภท Dashboard ที่ส่งมาจาก AJAX
$data_type = $_POST['data_type'] ?? 'estimate'; 

// ตรวจสอบค่าที่ส่งมา
if (empty($selected_year) || empty($selected_agency)) {
    echo json_encode(["success" => false, "message" => "Missing required parameters (year or agency)."]);
    exit;
}

// ------------------------------------------------------------------
// 🚨 กำหนดคอลัมน์รูปภาพที่จะใช้ในการเช็คตามประเภท Dashboard
// ------------------------------------------------------------------
$image_cols = [];
if ($data_type == 'evaluate') {
    // สำหรับ user_dashboard_evaluate.php (2 คอลัมน์)
    $image_cols = ['evaluate_ton_1', 'evaluate_ton_2'];
} elseif ($data_type == 'remaining') {
    // สำหรับ user_dashboard_remaining.php (6 คอลัมน์)
    $image_cols = [
        'remaining_cane_1_img_1', 'remaining_cane_1_img_2', 
        'remaining_cane_2_img_1', 'remaining_cane_2_img_2', 
        'remaining_cane_3_img_1', 'remaining_cane_3_img_2'
    ];
} else {
    // สำหรับ user_dashboard.php (Estimate) หรือ Default (2 คอลัมน์)
    $image_cols = ['estimate_ton_1', 'estimate_ton_2'];
}

// ------------------------------------------------------------------
// 🚨 สร้างเงื่อนไข SQL สำหรับการนับแปลงที่มีรูปภาพจาก $image_cols
// ------------------------------------------------------------------
$image_conditions = [];
foreach ($image_cols as $col) {
    // (col IS NOT NULL AND col != '')
    $image_conditions[] = "(`$col` IS NOT NULL AND `$col` != '')";
}
// ใช้ OR เพื่อเชื่อมเงื่อนไขทั้งหมด: มีรูปภาพในคอลัมน์ A OR B OR C...
$image_filter_condition = !empty($image_conditions) ? "(" . implode(' OR ', $image_conditions) . ")" : "1=0";

// ------------------------------------------------------------------

$base_where_clause = " WHERE production_year = ? AND agency = ?";
$params = [$selected_year, $selected_agency];
$types = "ss";

try {
    // 1. นับจำนวนแปลงทั้งหมด (ไม่กรองตามรูปภาพ)
    $sql_total = "SELECT COUNT(id) AS total FROM cane_plot_data" . $base_where_clause;
    $stmt_total = $conn->prepare($sql_total);
    $stmt_total->bind_param($types, ...$params);
    $stmt_total->execute();
    $result_total = $stmt_total->get_result();
    $total_plots = $result_total->fetch_assoc()['total'] ?? 0;
    $stmt_total->close();

    // 2. นับจำนวนแปลงที่มีรูปภาพ (กรองตาม $image_filter_condition ที่สร้างจาก $data_type)
    $sql_with_images = "SELECT COUNT(id) AS count_with_images FROM cane_plot_data" 
                     . $base_where_clause 
                     . " AND " . $image_filter_condition; // 🚨 ใช้ Dynamic Condition
                     
    $stmt_images = $conn->prepare($sql_with_images);
    $stmt_images->bind_param($types, ...$params);
    $stmt_images->execute();
    $result_images = $stmt_images->get_result();
    $plots_with_images = $result_images->fetch_assoc()['count_with_images'] ?? 0;
    $stmt_images->close();

    $conn->close();

    // ส่งผลลัพธ์กลับในรูปแบบ JSON
    echo json_encode([
        "success" => true,
        "total_plots" => (int)$total_plots,
        "plots_with_images" => (int)$plots_with_images
    ]);

} catch (Exception $e) {
    // หากเกิดข้อผิดพลาดในการเชื่อมต่อหรือคิวรี
    error_log("Database error in fetch_summary_counts: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Database Error."]);
}
?>