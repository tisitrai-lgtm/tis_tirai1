<?php
/**
 * api_settings.php
 * POST → บันทึกค่า setting (admin only)
 * GET  → ดึงค่า setting ทั้งหมด
 */
require_once 'config.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

if(!isset($_SESSION['emp_id']) || $_SESSION['emp_level'] !== 'a'){
    echo json_encode(['status'=>'error','message'=>'ไม่มีสิทธิ์'], JSON_UNESCAPED_UNICODE); exit;
}

if($_SERVER['REQUEST_METHOD'] === 'GET'){
    $rows = $conn->query("SELECT * FROM system_settings ORDER BY setting_group, setting_key")->fetchAll();
    echo json_encode(['status'=>'success','data'=>$rows], JSON_UNESCAPED_UNICODE); exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $key   = trim($_POST['setting_key']   ?? '');
    $value = trim($_POST['setting_value'] ?? '');
    if(!$key){ echo json_encode(['status'=>'error','message'=>'ไม่พบ key'], JSON_UNESCAPED_UNICODE); exit; }
    try {
        $stmt = $conn->prepare("UPDATE system_settings SET setting_value=:v WHERE setting_key=:k");
        $stmt->execute([':v'=>$value, ':k'=>$key]);

        // log
        $conn->prepare("INSERT INTO system_logs (action_by,action_type,target_id,log_details) VALUES (:by,'EDIT_SETTING',:tid,:det)")
             ->execute([':by'=>$_SESSION['emp_id'],':tid'=>$key,':det'=>"แก้ไขค่า {$key} = {$value}"]);

        echo json_encode(['status'=>'success','message'=>'บันทึกเรียบร้อย'], JSON_UNESCAPED_UNICODE);
    } catch(Exception $e){
        echo json_encode(['status'=>'error','message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}
echo json_encode(['status'=>'error','message'=>'Method not allowed'], JSON_UNESCAPED_UNICODE);