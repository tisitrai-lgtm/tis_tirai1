<?php
/**
 * api_check_items.php — CRUD สำหรับ check_items_cut และ check_items_field
 * รับ ?table=cut หรือ ?table=field
 */
require_once 'config.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

// เฉพาะ admin
if(!isset($_SESSION['emp_id']) || $_SESSION['emp_level'] !== 'a') {
    echo json_encode(['status'=>'error','message'=>'ไม่มีสิทธิ์']);
    exit;
}

$table = $_GET['table'] ?? '';

// กำหนดชื่อตารางและ column ตาม ?table=
if($table === 'cut') {
    $tbl       = 'check_items_cut';
    $col_name  = 'item_name_cut';
} elseif($table === 'field') {
    $tbl       = 'check_items_field';
    $col_name  = 'item_name_field';
} else {
    echo json_encode(['status'=>'error','message'=>'ไม่พบตารางที่ระบุ']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    // ── เพิ่ม ──
    if($action === 'add') {
        $name = trim($_POST['item_name'] ?? '');
        if(empty($name)) {
            echo json_encode(['status'=>'error','message'=>'กรุณากรอกชื่อรายการ']);
            exit;
        }
        // ตรวจซ้ำ
        $chk = $conn->prepare("SELECT COUNT(*) FROM `$tbl` WHERE `$col_name` = :name");
        $chk->execute([':name' => $name]);
        if($chk->fetchColumn() > 0) {
            echo json_encode(['status'=>'error','message'=>'มีรายการนี้อยู่แล้ว']);
            exit;
        }
        $stmt = $conn->prepare("INSERT INTO `$tbl` (`$col_name`) VALUES (:name)");
        $stmt->execute([':name' => $name]);
        $new_id = $conn->lastInsertId();
        echo json_encode(['status'=>'success','new_id'=>$new_id]);
        exit;
    }

    // ── แก้ไข ──
    if($action === 'edit') {
        $id   = intval($_POST['item_id'] ?? 0);
        $name = trim($_POST['item_name'] ?? '');
        if(!$id || empty($name)) {
            echo json_encode(['status'=>'error','message'=>'ข้อมูลไม่ครบ']);
            exit;
        }
        // ตรวจซ้ำ (ยกเว้น id ตัวเอง)
        $chk = $conn->prepare("SELECT COUNT(*) FROM `$tbl` WHERE `$col_name` = :name AND item_id != :id");
        $chk->execute([':name' => $name, ':id' => $id]);
        if($chk->fetchColumn() > 0) {
            echo json_encode(['status'=>'error','message'=>'มีชื่อนี้อยู่แล้ว']);
            exit;
        }
        $stmt = $conn->prepare("UPDATE `$tbl` SET `$col_name` = :name WHERE item_id = :id");
        $stmt->execute([':name' => $name, ':id' => $id]);
        echo json_encode(['status'=>'success']);
        exit;
    }

    // ── ลบ ──
    if($action === 'delete') {
        $id = intval($_POST['item_id'] ?? 0);
        if(!$id) {
            echo json_encode(['status'=>'error','message'=>'ไม่พบ ID']);
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM `$tbl` WHERE item_id = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode(['status'=>'success']);
        exit;
    }

    echo json_encode(['status'=>'error','message'=>'action ไม่ถูกต้อง']);

} catch(Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}