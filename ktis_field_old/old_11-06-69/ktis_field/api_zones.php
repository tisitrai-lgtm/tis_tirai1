<?php
/**
 * api_zones.php
 * GET    → ดึงหน่วยทั้งหมด
 * POST   → เพิ่มหน่วยใหม่  (zone_id, zone_name)
 * DELETE → ลบหน่วย         (zone_id)
 */
require_once 'config.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

$is_admin = isset($_SESSION['emp_level']) && $_SESSION['emp_level'] === 'a';

if($_SERVER['REQUEST_METHOD'] === 'GET') {
    $rows = $conn->query("SELECT zone_id, zone_name FROM zones ORDER BY zone_id ASC")->fetchAll();
    echo json_encode(['status'=>'success','data'=>$rows], JSON_UNESCAPED_UNICODE);
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!$is_admin){ echo json_encode(['status'=>'error','message'=>'ไม่มีสิทธิ์'], JSON_UNESCAPED_UNICODE); exit; }
    $zid   = trim($_POST['zone_id']   ?? '');
    $zname = trim($_POST['zone_name'] ?? '');
    if(!$zid || !$zname){ echo json_encode(['status'=>'error','message'=>'กรุณากรอกข้อมูลให้ครบ'], JSON_UNESCAPED_UNICODE); exit; }
    try {
        $chk = $conn->prepare("SELECT zone_id FROM zones WHERE zone_id = :id");
        $chk->execute([':id'=>$zid]);
        if($chk->rowCount()>0){ echo json_encode(['status'=>'error','message'=>'รหัสหน่วยนี้มีอยู่แล้ว'], JSON_UNESCAPED_UNICODE); exit; }
        $conn->prepare("INSERT INTO zones (zone_id, zone_name) VALUES (:id, :name)")->execute([':id'=>$zid,':name'=>$zname]);
        echo json_encode(['status'=>'success','message'=>'เพิ่มหน่วยสำเร็จ'], JSON_UNESCAPED_UNICODE);
    } catch(Exception $e){ echo json_encode(['status'=>'error','message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE); }
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if(!$is_admin){ echo json_encode(['status'=>'error','message'=>'ไม่มีสิทธิ์'], JSON_UNESCAPED_UNICODE); exit; }
    parse_str(file_get_contents('php://input'), $del);
    $zid = trim($del['zone_id'] ?? '');
    if(!$zid){ echo json_encode(['status'=>'error','message'=>'ไม่พบรหัสหน่วย'], JSON_UNESCAPED_UNICODE); exit; }
    try {
        $conn->prepare("DELETE FROM zones WHERE zone_id = :id")->execute([':id'=>$zid]);
        echo json_encode(['status'=>'success','message'=>'ลบสำเร็จ'], JSON_UNESCAPED_UNICODE);
    } catch(Exception $e){ echo json_encode(['status'=>'error','message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE); }
    exit;
}

echo json_encode(['status'=>'error','message'=>'Method not allowed'], JSON_UNESCAPED_UNICODE);