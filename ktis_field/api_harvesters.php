<?php
/**
 * api_harvesters.php
 * GET  -> ค้นหารายการรถตัดที่ใช้งานอยู่ (is_active = 1) ตามเบอร์รถ
 * POST -> จัดการรถตัด (add, toggle, delete, assign, unassign) สำหรับ Admin
 */
require_once 'config.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['emp_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

$is_admin = isset($_SESSION['emp_level']) && $_SESSION['emp_level'] === 'a';

// ──────────────────────────────────────────────────
// GET: ค้นหารถตัดสำหรับ User / Auto-complete
// ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
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
                    JOIN employee e ON (eh.emp_id = e.ID OR eh.emp_id = e.emp_id)
                    WHERE e.emp_id = :emp_id AND h.is_active = 1
                    ORDER BY h.harvester_id ASC LIMIT 10
                ");
                $stmt->execute([':emp_id' => $emp_id]);
            } else {
                $stmt = $conn->prepare("
                    SELECT h.harvester_id, h.harvester_number, h.harvester_name 
                    FROM employee_harvester eh
                    JOIN harvesters h ON eh.harvester_id = h.harvester_id
                    JOIN employee e ON (eh.emp_id = e.ID OR eh.emp_id = e.emp_id)
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
                $stmt = $conn->prepare("SELECT harvester_id, harvester_number, harvester_name FROM harvesters WHERE is_active = 1 ORDER BY harvester_id ASC LIMIT 10");
                $stmt->execute();
            } else {
                $stmt = $conn->prepare("SELECT harvester_id, harvester_number, harvester_name FROM harvesters WHERE is_active = 1 AND harvester_number LIKE :q ORDER BY harvester_number ASC LIMIT 15");
                $searchParam = "%" . $q . "%";
                $stmt->bindParam(':q', $searchParam, PDO::PARAM_STR);
                $stmt->execute();
            }
        }
        
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $rows], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ──────────────────────────────────────────────────
// POST: ฟังก์ชันจัดการสำหรับ Admin (setting_system.php)
// ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$is_admin) {
        echo json_encode(['status' => 'error', 'message' => 'คุณไม่มีสิทธิ์ในการจัดการ'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $action = trim($_POST['action'] ?? '');

    try {
        // 1. เพิ่มรถตัดใหม่
        if ($action === 'add') {
            $num  = trim($_POST['harvester_number'] ?? '');
            $name = trim($_POST['harvester_name'] ?? '');

            if ($num === '') {
                echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกเบอร์รถตัด'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // เช็คซ้ำ
            $chk = $conn->prepare("SELECT harvester_id FROM harvesters WHERE harvester_number = :num");
            $chk->execute([':num' => $num]);
            if ($chk->rowCount() > 0) {
                echo json_encode(['status' => 'error', 'message' => 'มีรถตัดเบอร์นี้ในระบบแล้ว'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $stmt = $conn->prepare("INSERT INTO harvesters (harvester_number, harvester_name, is_active) VALUES (:num, :name, 1)");
            $stmt->execute([':num' => $num, ':name' => $name]);
            $new_id = $conn->lastInsertId();

            echo json_encode(['status' => 'success', 'message' => 'เพิ่มรถตัดสำเร็จ', 'new_id' => $new_id], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 2. ปลดระวาง / เปิดใช้งานรถตัด
        if ($action === 'toggle') {
            $id     = intval($_POST['harvester_id'] ?? 0);
            $active = intval($_POST['is_active'] ?? 0);

            if (!$id) {
                echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลรถตัด'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $stmt = $conn->prepare("UPDATE harvesters SET is_active = :active WHERE harvester_id = :id");
            $stmt->execute([':active' => $active, ':id' => $id]);

            echo json_encode(['status' => 'success', 'message' => 'อัปเดตสถานะสำเร็จ'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 3. ลบรถตัด
        if ($action === 'delete') {
            $id = intval($_POST['harvester_id'] ?? 0);
            if (!$id) {
                echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลรถตัด'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // ลบการผูกพนักงานออกก่อน
            $conn->prepare("DELETE FROM employee_harvester WHERE harvester_id = :id")->execute([':id' => $id]);
            // ลบรถตัด
            $conn->prepare("DELETE FROM harvesters WHERE harvester_id = :id")->execute([':id' => $id]);

            echo json_encode(['status' => 'success', 'message' => 'ลบรถตัดเรียบร้อย'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 4. ผูกพนักงานกับรถตัด
        if ($action === 'assign') {
            $emp_id = intval($_POST['emp_id'] ?? 0);
            $hv_id  = intval($_POST['harvester_id'] ?? 0);

            if (!$emp_id || !$hv_id) {
                echo json_encode(['status' => 'error', 'message' => 'กรุณาเลือกพนักงานและรถตัด'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // เช็คว่าเคยผูกไว้แล้วหรือไม่
            $chk = $conn->prepare("SELECT id FROM employee_harvester WHERE emp_id = :eid AND harvester_id = :hid");
            $chk->execute([':eid' => $emp_id, ':hid' => $hv_id]);
            if ($chk->rowCount() > 0) {
                echo json_encode(['status' => 'error', 'message' => 'พนักงานท่านนี้ได้รับการผูกกับรถตัดคันนี้อยู่แล้ว'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $stmt = $conn->prepare("INSERT INTO employee_harvester (emp_id, harvester_id, assigned_at) VALUES (:eid, :hid, NOW())");
            $stmt->execute([':eid' => $emp_id, ':hid' => $hv_id]);

            // อัปเดตสิทธิ์ให้เป็นผู้ดูแลรถตัด
            $conn->prepare("UPDATE employee SET is_harvester_manager = 1 WHERE ID = :eid")->execute([':eid' => $emp_id]);

            echo json_encode(['status' => 'success', 'message' => 'ผูกรถตัดเรียบร้อย'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 5. ยกเลิกการผูกพนักงานกับรถตัด
        if ($action === 'unassign') {
            $id = intval($_POST['id'] ?? 0);
            if (!$id) {
                echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูล'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $stmt = $conn->prepare("DELETE FROM employee_harvester WHERE id = :id");
            $stmt->execute([':id' => $id]);

            echo json_encode(['status' => 'success', 'message' => 'ยกเลิกการผูกเรียบร้อย'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode(['status' => 'error', 'message' => 'Action not recognized'], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
