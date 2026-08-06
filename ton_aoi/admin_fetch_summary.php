<?php
// admin_fetch_summary.php - จัดการการดึงข้อมูลสถิติสำหรับ Infographic (แก้ไขตรรกะ)
require_once 'db_connect.php'; 

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'เกิดข้อผิดพลาดไม่ทราบสาเหตุ', 'data' => []];

// 1. รับค่าตัวกรอง
$year = $_GET['year'] ?? '';
$agency = $_GET['agency'] ?? ''; // ใช้เป็นตัวกรองเสริม
$suga_type = $_GET['suga_type'] ?? '';
$mode = $_GET['mode'] ?? 'agency_summary'; // Mode ที่ต้องการสรุป

// 2. สร้างเงื่อนไข WHERE clause
$where_clauses = ["1 = 1"];
$params = [];
$param_types = "";

if (!empty($year)) {
    $where_clauses[] = "production_year = ?";
    $params[] = $year;
    $param_types .= "s";
}

// 🚨 ไม่ต้องกรองตาม agency ที่นี่ เพราะต้องการสรุป agency ทั้งหมดในตารางสรุป
// แต่จะใช้ agency เป็นตัวกรองเสริมได้ หากผู้ใช้เลือก
if (!empty($agency)) {
     $where_clauses[] = "agency = ?";
     $params[] = $agency;
     $param_types .= "s";
}

if (!empty($suga_type)) {
    $where_clauses[] = "suga_type = ?";
    $params[] = $suga_type;
    $param_types .= "s";
}

$where_sql = "WHERE " . implode(" AND ", $where_clauses);

// 3. เงื่อนไขการนับว่ามีรูปภาพอย่างน้อย 1 รูป
$has_image_condition = "(
    estimate_ton_1 IS NOT NULL OR estimate_ton_2 IS NOT NULL OR 
    evaluate_ton_1 IS NOT NULL OR evaluate_ton_2 IS NOT NULL OR
    remaining_cane_1_img_1 IS NOT NULL OR remaining_cane_1_img_2 IS NOT NULL OR 
    remaining_cane_2_img_1 IS NOT NULL OR remaining_cane_2_img_2 IS NOT NULL OR 
    remaining_cane_3_img_1 IS NOT NULL OR remaining_cane_3_img_2 IS NOT NULL
)";


try {
    // 4. สรุปภาพรวมทั้งหมด (สำหรับ Pie Chart)
    $sql_total = "SELECT COUNT(id) AS total, 
                         SUM(CASE WHEN {$has_image_condition} THEN 1 ELSE 0 END) AS with_image
                  FROM cane_plot_data {$where_sql}";
    
    $stmt_total = $conn->prepare($sql_total);
    if (!empty($params)) {
        $stmt_total->bind_param($param_types, ...$params);
    }
    $stmt_total->execute();
    $total_summary = $stmt_total->get_result()->fetch_assoc();
    $stmt_total->close();
    
    $summary_data = [
        'total_summary' => [
            'total' => (int)$total_summary['total'],
            'with_image' => (int)$total_summary['with_image']
        ],
        'agency_summary' => []
    ];
    
    // 5. สรุปแยกตามหน่วยงาน (สำหรับตาราง)
    // 🚨 ต้องไม่รวม agency ใน WHERE clause สำหรับการสรุปในขั้นตอนนี้! 
    // เราต้องใช้ parameter set เดิม (ไม่รวม agency filter)
    
    // 5.1 สร้าง WHERE clause ใหม่ที่ยกเว้น agency filter
    $agency_summary_where_clauses = ["1 = 1"];
    $agency_summary_params = [];
    $agency_summary_param_types = "";
    
    if (!empty($year)) {
        $agency_summary_where_clauses[] = "production_year = ?";
        $agency_summary_params[] = $year;
        $agency_summary_param_types .= "s";
    }
    if (!empty($suga_type)) {
        $agency_summary_where_clauses[] = "suga_type = ?";
        $agency_summary_params[] = $suga_type;
        $agency_summary_param_types .= "s";
    }
    $agency_summary_where_sql = "WHERE " . implode(" AND ", $agency_summary_where_clauses);

    $sql_agency = "SELECT agency, 
                          COUNT(id) AS total, 
                          SUM(CASE WHEN {$has_image_condition} THEN 1 ELSE 0 END) AS with_image
                   FROM cane_plot_data {$agency_summary_where_sql}
                   GROUP BY agency
                   ORDER BY agency ASC";

    $stmt_agency = $conn->prepare($sql_agency);
    if (!empty($agency_summary_params)) {
        $stmt_agency->bind_param($agency_summary_param_types, ...$agency_summary_params);
    }
    $stmt_agency->execute();
    $result_agency = $stmt_agency->get_result();
    
    while ($row = $result_agency->fetch_assoc()) {
        $total = (int)$row['total'];
        $with_image = (int)$row['with_image'];
        $missing_image = $total - $with_image;
        $percent = $total > 0 ? ($with_image / $total) * 100 : 0;
        
        $summary_data['agency_summary'][] = [
            'agency' => htmlspecialchars($row['agency']),
            'total' => $total,
            'with_image' => $with_image,
            'missing_image' => $missing_image,
            'percent' => $percent
        ];
    }
    $stmt_agency->close();

    $response['success'] = true;
    $response['message'] = 'ดึงข้อมูลสำเร็จ';
    $response['data'] = $summary_data;
    
} catch (\Exception $e) {
    $response['message'] = 'SQL Error: ' . $e->getMessage();
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
    echo json_encode($response);
}
?>