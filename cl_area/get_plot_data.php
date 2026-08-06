<?php
// get_plot_data.php
include 'db_connect.php';

$plot_id = $_GET['plot_id'] ?? '';

$response = ['found' => false];

if ($plot_id) {
    $stmt = $conn->prepare("
        SELECT contract_number, quota_name, promotion_unit, promoter_area, sugar_type
        FROM sugar_contracts
        WHERE plot_id = ?
        LIMIT 1
    ");
    $stmt->execute([$plot_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $response = [
            'found' => true,
            'contract_number' => $row['contract_number'],
            'quota_name' => $row['quota_name'],
            'promotion_unit' => $row['promotion_unit'], // ✅ ต้องตรงกับ DB
            'promoter_area' => $row['promoter_area'],
            'sugar_type' => $row['sugar_type']  // ✅ ต้องตรงกับ select option
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($response);
