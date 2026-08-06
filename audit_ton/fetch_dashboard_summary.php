<?php
// fetch_dashboard_summary.php - สรุปข้อมูลสำหรับ Dashboard Analytics
require("db_connect.php");

header('Content-Type: application/json');

$selected_year = $_GET['year'] ?? '';

if (empty($selected_year)) {
    echo json_encode(["success" => false, "message" => "Missing year parameter."]);
    exit;
}

try {
    // 1. ข้อมูลสรุปภาพรวม (Summary Cards)
    $summary_sql = "SELECT 
                        COUNT(id) AS total_plots,
                        SUM(rai_area) AS total_area,
                        AVG(ton_rai) AS avg_ton_rai
                    FROM cane_plot_data 
                    WHERE production_year = ?";
    
    $stmt = $conn->prepare($summary_sql);
    $stmt->bind_param("s", $selected_year);
    $stmt->execute();
    $summary_result = $stmt->get_result()->fetch_assoc();
    
    // 2. นับแปลงที่มีรูปภาพ
    $image_fields = [
        'estimate_ton_1', 'estimate_ton_2', 'evaluate_ton_1', 'evaluate_ton_2', 
        'remaining_cane_1_img_1', 'remaining_cane_1_img_2', 'remaining_cane_2_img_1', 
        'remaining_cane_2_img_2', 'remaining_cane_3_img_1', 'remaining_cane_3_img_2'
    ];
    $image_conditions = [];
    foreach ($image_fields as $field) {
        $image_conditions[] = "($field IS NOT NULL AND $field != '')";
    }
    $image_check_sql = "(" . implode(" OR ", $image_conditions) . ")";
    
    $image_sql = "SELECT COUNT(id) AS plots_with_images FROM cane_plot_data WHERE production_year = ? AND $image_check_sql";
    $stmt_img = $conn->prepare($image_sql);
    $stmt_img->bind_param("s", $selected_year);
    $stmt_img->execute();
    $image_result = $stmt_img->get_result()->fetch_assoc();
    
    // 3. ข้อมูลสำหรับกราฟ (Sugarcane Type Distribution with Image Progress)
    $type_sql = "SELECT 
                    suga_type, 
                    COUNT(id) AS total_count,
                    SUM(CASE WHEN $image_check_sql THEN 1 ELSE 0 END) AS count_with_images
                 FROM cane_plot_data 
                 WHERE production_year = ? 
                 GROUP BY suga_type 
                 ORDER BY total_count DESC 
                 LIMIT 5";
    $stmt_type = $conn->prepare($type_sql);
    $stmt_type->bind_param("s", $selected_year);
    $stmt_type->execute();
    $type_result = $stmt_type->get_result();
    $types_data = [];
    while ($row = $type_result->fetch_assoc()) {
        $types_data[] = [
            "suga_type" => $row['suga_type'],
            "total_count" => (int)$row['total_count'],
            "count_with_images" => (int)$row['count_with_images']
        ];
    }

    echo json_encode([
        "success" => true,
        "summary" => [
            "total_plots" => (int)$summary_result['total_plots'],
            "total_area" => round((float)$summary_result['total_area'], 2),
            "avg_ton_rai" => round((float)$summary_result['avg_ton_rai'], 2),
            "plots_with_images" => (int)$image_result['plots_with_images'],
            "image_percent" => $summary_result['total_plots'] > 0 
                ? round(($image_result['plots_with_images'] / $summary_result['total_plots']) * 100, 1) 
                : 0
        ],
        "chart_data" => $types_data
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

if (isset($conn)) $conn->close();
?>
