<?php
require_once 'config.php';
header('Content-Type: application/json');

$mId = isset($_GET['manager_id']) ? $_GET['manager_id'] : 0;

try {
    // 1. ดึงรายการรถทั้งหมด
    $stmt_all = $conn->query("SELECT harvester_id, harvester_number FROM harvesters ORDER BY harvester_id ASC");
    $all = $stmt_all->fetchAll(PDO::FETCH_ASSOC);

    // 2. ดึงรายการรถที่ผูกไว้กับพนักงานคนนี้ (ใช้ emp_id แทน manager_id)
    $stmt_assigned = $conn->prepare("SELECT harvester_id FROM employee_harvester WHERE emp_id = ?");
    $stmt_assigned->execute([$mId]);
    $assigned = $stmt_assigned->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode(['all' => $all, 'assigned' => $assigned]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>