<?php
/**
 * api_harvesters.php
 * GET -> ค้นหารายการรถตัดที่ใช้งานอยู่ (is_active = 1) ตามเบอร์รถ
 */
require_once 'config.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['emp_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

$q = trim($_GET['q'] ?? '');
$assigned = isset($_GET['assigned']) && $_GET['assigned'] == '1';
$emp_id = $_SESSION['emp_id'];

try {
    if ($assigned) {
        if ($q === '') {
            $stmt = $conn->prepare("
                SELECT h.harvester_id, h.harvester_number, h.harvester_name 
                FROM employee_harvester eh
                JOIN harvesters h ON eh.harvester_id = h.harvester_id
                JOIN employee e ON eh.emp_id = e.ID
                WHERE e.emp_id = :emp_id AND h.is_active = 1
                ORDER BY h.harvester_id ASC LIMIT 10
            ");
            $stmt->execute([':emp_id' => $emp_id]);
        } else {
            $stmt = $conn->prepare("
                SELECT h.harvester_id, h.harvester_number, h.harvester_name 
                FROM employee_harvester eh
                JOIN harvesters h ON eh.harvester_id = h.harvester_id
                JOIN employee e ON eh.emp_id = e.ID
                WHERE e.emp_id = :emp_id AND h.is_active = 1 AND h.harvester_number LIKE :q
                ORDER BY h.harvester_number ASC LIMIT 15
            ");
            $searchParam = "%" . $q . "%";
            $stmt->bindParam(':emp_id', $emp_id, PDO::PARAM_STR);
            $stmt->bindParam(':q', $searchParam, PDO::PARAM_STR);
            $stmt->execute();
        }
    } else {
        if ($q === '') {
            // ถ้ายังไม่ได้พิมพ์อะไรเลย ให้ดึงมาแสดง 10 คันแรกเผื่อให้เลือกเล่นๆ
            $stmt = $conn->prepare("SELECT harvester_id, harvester_number, harvester_name FROM harvesters WHERE is_active = 1 ORDER BY harvester_id ASC LIMIT 10");
            $stmt->execute();
        } else {
            // ค้นหาตามที่พิมพ์
            $stmt = $conn->prepare("SELECT harvester_id, harvester_number, harvester_name FROM harvesters WHERE is_active = 1 AND harvester_number LIKE :q ORDER BY harvester_number ASC LIMIT 15");
            $searchParam = "%" . $q . "%";
            $stmt->bindParam(':q', $searchParam, PDO::PARAM_STR);
            $stmt->execute();
        }
    }
    
    $rows = $stmt->fetchAll();
    echo json_encode(['status' => 'success', 'data' => $rows], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
exit;
