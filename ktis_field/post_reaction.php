<?php
/**
 * post_reaction.php — API รับ/ส่ง reaction (like, love, wow)
 */
require_once 'config.php';
session_start();

header('Content-Type: application/json');

if(!isset($_SESSION['emp_id'])){
    echo json_encode(['status'=>'error','message'=>'กรุณาล็อกอินก่อน']);
    exit;
}

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    echo json_encode(['status'=>'error','message'=>'method not allowed']);
    exit;
}

$post_id  = intval($_POST['post_id'] ?? 0);
$reaction = $_POST['reaction_type'] ?? 'like';
$emp_id   = $_SESSION['emp_id'];

if($post_id <= 0 || !in_array($reaction, ['like','love','wow'])){
    echo json_encode(['status'=>'error','message'=>'ข้อมูลไม่ถูกต้อง']);
    exit;
}

try {
    // ดึง reaction เดิมของ user นี้
    $stmt = $conn->prepare("SELECT reaction_id, reaction_type FROM post_reactions WHERE post_id=:pid AND emp_id=:eid");
    $stmt->execute([':pid'=>$post_id, ':eid'=>$emp_id]);
    $existing = $stmt->fetch();

    if($existing){
        if($existing['reaction_type'] === $reaction){
            // กด reaction เดิม = ยกเลิก
            $conn->prepare("DELETE FROM post_reactions WHERE reaction_id=:rid")->execute([':rid'=>$existing['reaction_id']]);
            $action = 'removed';
        } else {
            // เปลี่ยน reaction
            $conn->prepare("UPDATE post_reactions SET reaction_type=:rt WHERE reaction_id=:rid")->execute([':rt'=>$reaction, ':rid'=>$existing['reaction_id']]);
            $action = 'changed';
        }
    } else {
        // เพิ่มใหม่
        $conn->prepare("INSERT INTO post_reactions (post_id, emp_id, reaction_type) VALUES (:pid,:eid,:rt)")->execute([':pid'=>$post_id,':eid'=>$emp_id,':rt'=>$reaction]);
        $action = 'added';
    }

    // นับ reaction แต่ละแบบ
    $stmt2 = $conn->prepare("SELECT reaction_type, COUNT(*) as cnt FROM post_reactions WHERE post_id=:pid GROUP BY reaction_type");
    $stmt2->execute([':pid'=>$post_id]);
    $counts = ['like'=>0,'love'=>0,'wow'=>0];
    foreach($stmt2->fetchAll() as $row){ $counts[$row['reaction_type']] = (int)$row['cnt']; }

    // reaction ปัจจุบันของ user
    $stmt3 = $conn->prepare("SELECT reaction_type FROM post_reactions WHERE post_id=:pid AND emp_id=:eid");
    $stmt3->execute([':pid'=>$post_id, ':eid'=>$emp_id]);
    $my_reaction = $stmt3->fetchColumn() ?: null;

    echo json_encode(['status'=>'success','action'=>$action,'counts'=>$counts,'my_reaction'=>$my_reaction]);

} catch(Exception $e){
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
}