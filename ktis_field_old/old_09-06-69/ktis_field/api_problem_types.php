<?php
/**
 * api_problem_types.php
 * GET    → ดึงรายการปัญหาทั้งหมด
 * POST   → เพิ่มปัญหาใหม่  (problem_name)
 * DELETE → ลบปัญหา         (problem_id)
 */
require_once 'config.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

// เฉพาะ admin เท่านั้นที่แก้ไขได้ (GET ทุกคนดูได้)
$is_admin = isset($_SESSION['emp_level']) && $_SESSION['emp_level'] === 'a';

// ──────────────────────────────────────
// GET — ดึงรายการทั้งหมด
// ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $rows = $conn->query("SELECT problem_id, problem_name FROM problem_types ORDER BY problem_id ASC")->fetchAll();
        echo json_encode(['status' => 'success', 'data' => $rows], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ──────────────────────────────────────
// POST — เพิ่มปัญหาใหม่
// ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$is_admin) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $name = trim($_POST['problem_name'] ?? '');
    if ($name === '') {
        echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกชื่อปัญหา'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    try {
        // ตรวจซ้ำ
        $chk = $conn->prepare("SELECT problem_id FROM problem_types WHERE problem_name = :n");
        $chk->execute([':n' => $name]);
        if ($chk->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'ปัญหานี้มีอยู่แล้ว'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $ins = $conn->prepare("INSERT INTO problem_types (problem_name) VALUES (:n)");
        $ins->execute([':n' => $name]);
        echo json_encode(['status' => 'success', 'message' => 'เพิ่มสำเร็จ', 'new_id' => $conn->lastInsertId()], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ──────────────────────────────────────
// DELETE — ลบปัญหา
// ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if (!$is_admin) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    parse_str(file_get_contents('php://input'), $del);
    $id = intval($del['problem_id'] ?? 0);
    if (!$id) {
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบ ID'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    try {
        $conn->prepare("DELETE FROM problem_types WHERE problem_id = :id")->execute([':id' => $id]);
        echo json_encode(['status' => 'success', 'message' => 'ลบสำเร็จ'], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);