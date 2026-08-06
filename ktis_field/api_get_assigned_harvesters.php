<?php
/**
 * api_get_assigned_harvesters.php
 * คืน JSON รายการรถตัดที่ assign ให้พนักงาน
 */
require_once 'config.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['emp_level']) || $_SESSION['emp_level'] != 'a') {
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

$emp_id = intval($_GET['emp_id'] ?? 0);
if ($emp_id <= 0) {
    echo json_encode(['assigned' => []]);
    exit;
}

$stmt = $conn->prepare(
    "SELECT harvester_id FROM employee_harvester WHERE emp_id = ?"
);
$stmt->execute([$emp_id]);
$rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode(['assigned' => array_map('intval', $rows)]);
