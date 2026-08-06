<?php
// fetch_summary_counts.php - ดึงข้อมูลสรุปจำนวนแปลงทั้งหมดและแปลงที่มีรูปภาพ

// เชื่อมต่อฐานข้อมูล
require("db_connect.php"); 

header('Content-Type: application/json');

$selected_year = $_POST['year'] ?? '';
$selected_agency = $_POST['agency'] ?? '';

// ตรวจสอบค่าที่ส่งมา
if (empty($selected_year) || empty($selected_agency)) {
    echo json_encode(["success" => false, "message" => "Missing required parameters (year or agency)."]);
    exit;
}

// 🚨 เงื่อนไข SQL สำหรับการนับแปลงที่มีรูปภาพ (เช็คทั้ง 10 คอลัมน์)
$image_filter_condition = "
    (
        estimate_ton_1 IS NOT NULL AND estimate_ton_1 != ''
        OR estimate_ton_2 IS NOT NULL AND estimate_ton_2 != ''
        OR evaluate_ton_1 IS NOT NULL AND evaluate_ton_1 != ''
        OR evaluate_ton_2 IS NOT NULL AND evaluate_ton_2 != ''
        OR remaining_cane_1_img_1 IS NOT NULL AND remaining_cane_1_img_1 != ''
        OR remaining_cane_1_img_2 IS NOT NULL AND remaining_cane_1_img_2 != ''
        OR remaining_cane_2_img_1 IS NOT NULL AND remaining_cane_2_img_1 != ''
        OR remaining_cane_2_img_2 IS NOT NULL AND remaining_cane_2_img_2 != ''
        OR remaining_cane_3_img_1 IS NOT NULL AND remaining_cane_3_img_1 != ''
        OR remaining_cane_3_img_2 IS NOT NULL AND remaining_cane_3_img_2 != ''
    )
";

$base_where_clause = " WHERE production_year = ? AND agency = ?";
$params = [$selected_year, $selected_agency];
$types = "ss";

try {
    // 1. นับจำนวนแปลงทั้งหมด
    $sql_total = "SELECT COUNT(id) AS total FROM cane_plot_data" . $base_where_clause;
    $stmt_total = $conn->prepare($sql_total);
    $stmt_total->bind_param($types, ...$params);
    $stmt_total->execute();
    $result_total = $stmt_total->get_result();
    $total_plots = $result_total->fetch_assoc()['total'] ?? 0;
    $stmt_total->close();

    // 2. นับจำนวนแปลงที่มีรูปภาพ
    $sql_with_images = "SELECT COUNT(id) AS count_with_images FROM cane_plot_data" 
                     . $base_where_clause 
                     . " AND " . $image_filter_condition;
                     
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
    echo json_encode(["success" => false, "message" => "Database Error."]);
}
?>