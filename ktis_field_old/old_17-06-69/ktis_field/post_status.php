<?php
/**
 * post_status.php — Admin เปลี่ยนสถานะโพสต์ pending ↔ success
 */
require_once 'config.php';
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['emp_id']) || $_SESSION['emp_level'] !== 'a'){
    echo json_encode(['status'=>'error','message'=>'ไม่มีสิทธิ์']);
    exit;
}

$post_id    = intval($_POST['post_id'] ?? 0);
$job_status = $_POST['job_status'] ?? '';

if(!$post_id || !in_array($job_status, ['pending','success'])){
    echo json_encode(['status'=>'error','message'=>'ข้อมูลไม่ถูกต้อง']);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE posts SET job_status=:js WHERE post_id=:pid");
    $stmt->execute([':js'=>$job_status, ':pid'=>$post_id]);

    // บันทึก system_log
    $log = $conn->prepare("INSERT INTO system_logs (action_by,action_type,target_id,log_details) VALUES (:by,'CHANGE_STATUS',:tid,:det)");
    $log->execute([
        ':by'  => $_SESSION['emp_id'],
        ':tid' => $post_id,
        ':det' => "เปลี่ยนสถานะโพสต์ #{$post_id} เป็น {$job_status}"
    ]);

    echo json_encode(['status'=>'success','message'=>'อัปเดตสถานะเรียบร้อย','new_status'=>$job_status]);
} catch(Exception $e){
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}