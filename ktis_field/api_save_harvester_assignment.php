<?php
/**
 * api_save_harvester_assignment.php
 * เพิ่ม/ถอน รถตัดจากพนักงาน
 * POST: emp_id, harvester_id, action (add|remove)
 */
require_once 'config.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['emp_level']) || $_SESSION['emp_level'] != 'a') {
    echo json_encode(['status' => 'error', 'message' => 'unauthorized']);
    exit;
}

$emp_id       = intval($_POST['emp_id']       ?? 0);
$harvester_id = intval($_POST['harvester_id'] ?? 0);
$action       = $_POST['action'] ?? '';

if ($emp_id <= 0 || $harvester_id <= 0 || !in_array($action, ['add', 'remove'])) {
    echo json_encode(['status' => 'error', 'message' => 'invalid params']);
    exit;
}

try {
    if ($action === 'add') {
        // INSERT IGNORE เพื่อป้องกัน duplicate
        $stmt = $conn->prepare(
            "INSERT IGNORE INTO employee_harvester (emp_id, harvester_id) VALUES (?, ?)"
        );
        $stmt->execute([$emp_id, $harvester_id]);
    } else {
        $stmt = $conn->prepare(
            "DELETE FROM employee_harvester WHERE emp_id = ? AND harvester_id = ?"
        );
        $stmt->execute([$emp_id, $harvester_id]);
    }
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
