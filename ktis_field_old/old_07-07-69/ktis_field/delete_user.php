<?php
require_once 'config.php';
session_start();

// ตรวจสอบสิทธิ์ผู้ใช้ (เฉพาะแอดมินเท่านั้น)
if (!isset($_SESSION['emp_level']) || $_SESSION['emp_level'] != 'a') {
    die("คุณไม่มีสิทธิ์เข้าถึงหน้านี้ กรุณากลับหน้าหลัก");
}

// ตรวจสอบ ID ที่ส่งมา
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage_users.php?error=ไม่พบรหัสพนักงาน");
    exit;
}

$id_in_db = intval($_GET['id']);

try {
    // ดึงข้อมูลพนักงานก่อนลบ
    $stmt = $conn->prepare("SELECT emp_name, emp_id, emp_level FROM employee WHERE ID = ?");
    $stmt->execute([$id_in_db]);
    $employee = $stmt->fetch();

    if (!$employee) {
        header("Location: manage_users.php?error=ไม่พบพนักงานที่ต้องการลบ");
        exit;
    }

    $emp_id = $employee['emp_id'];

    // ป้องกันการลบแอดมินตัวเดียว
    if ($employee['emp_level'] == 'a') {
        $admin_count_stmt = $conn->query("SELECT COUNT(*) as count FROM employee WHERE emp_level = 'a'");
        $admin_count = $admin_count_stmt->fetch();
        if ($admin_count['count'] <= 1) {
            header("Location: manage_users.php?error=ไม่สามารถลบแอดมินคนสุดท้ายของระบบได้");
            exit;
        }
    }

    // เริ่ม Transaction เพื่อความปลอดภัยของข้อมูล
    $conn->beginTransaction();

    // 1. ลบข้อมูลในตาราง notifications
    $stmt1 = $conn->prepare("DELETE FROM notifications WHERE emp_id = ?");
    $stmt1->execute([$emp_id]);

    // 2. ลบข้อมูลในตาราง harvester_checks
    $stmt2 = $conn->prepare("DELETE FROM harvester_checks WHERE emp_id = ?");
    $stmt2->execute([$emp_id]);

    // 3. ลบข้อมูลในตาราง replies (ความคิดเห็น)
    // ก่อนลบ replies อาจต้องลบ reply_logs ที่อ้างอิง reply_id ก่อน
    $stmt_get_replies = $conn->prepare("SELECT reply_id FROM replies WHERE emp_id = ?");
    $stmt_get_replies->execute([$emp_id]);
    $reply_ids = $stmt_get_replies->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($reply_ids)) {
        $placeholders = implode(',', array_fill(0, count($reply_ids), '?'));
        $stmt3_logs = $conn->prepare("DELETE FROM reply_logs WHERE reply_id IN ($placeholders)");
        $stmt3_logs->execute($reply_ids);
    }
    
    $stmt3 = $conn->prepare("DELETE FROM replies WHERE emp_id = ?");
    $stmt3->execute([$emp_id]);

    // 4. ลบข้อมูลในตาราง posts (โพสต์ที่พนักงานคนนี้สร้าง)
    // หมายเหตุ: ถ้าลบ posts อาจต้องลบข้อมูลที่เชื่อมกับ post_id ในตารางอื่นด้วย เช่น replies, notifications
    // แต่ในที่นี้เราเน้นลบพนักงาน ถ้าพนักงานเป็นเจ้าของโพสต์ เราจะลบโพสต์นั้นๆ ออกไปด้วย
    $stmt_get_posts = $conn->prepare("SELECT post_id FROM posts WHERE emp_id = ?");
    $stmt_get_posts->execute([$emp_id]);
    $post_ids = $stmt_get_posts->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($post_ids)) {
        $placeholders = implode(',', array_fill(0, count($post_ids), '?'));
        
        // ลบ notifications ที่เกี่ยวกับโพสต์เหล่านี้
        $stmt_del_post_noti = $conn->prepare("DELETE FROM notifications WHERE post_id IN ($placeholders)");
        $stmt_del_post_noti->execute($post_ids);
        
        // ลบ replies ที่เกี่ยวกับโพสต์เหล่านี้
        // (ต้องลบ reply_logs ก่อน)
        $stmt_get_post_replies = $conn->prepare("SELECT reply_id FROM replies WHERE post_id IN ($placeholders)");
        $stmt_get_post_replies->execute($post_ids);
        $post_reply_ids = $stmt_get_post_replies->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($post_reply_ids)) {
            $reply_placeholders = implode(',', array_fill(0, count($post_reply_ids), '?'));
            $stmt_del_post_reply_logs = $conn->prepare("DELETE FROM reply_logs WHERE reply_id IN ($reply_placeholders)");
            $stmt_del_post_reply_logs->execute($post_reply_ids);
        }

        $stmt_del_post_replies = $conn->prepare("DELETE FROM replies WHERE post_id IN ($placeholders)");
        $stmt_del_post_replies->execute($post_ids);

        // ลบตัวโพสต์เอง
        $stmt4 = $conn->prepare("DELETE FROM posts WHERE emp_id = ?");
        $stmt4->execute([$emp_id]);
    }

    // 5. ลบข้อมูลในตาราง system_logs
    $stmt5 = $conn->prepare("DELETE FROM system_logs WHERE action_by = ?");
    $stmt5->execute([$emp_id]);

    // 6. สุดท้าย ลบข้อมูลพนักงาน
    $stmt6 = $conn->prepare("DELETE FROM employee WHERE ID = ?");
    $stmt6->execute([$id_in_db]);

    // ยืนยันการทำ Transaction
    $conn->commit();

    header("Location: manage_users.php?success=ลบพนักงานคุณ " . urlencode($employee['emp_name']) . " และข้อมูลที่เกี่ยวข้องทั้งหมดสำเร็จ");
    exit;

} catch (Exception $e) {
    // หากเกิดข้อผิดพลาด ให้ยกเลิกสิ่งที่ทำไปทั้งหมด (Rollback)
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    header("Location: manage_users.php?error=เกิดข้อผิดพลาดในการลบข้อมูล: " . urlencode($e->getMessage()));
    exit;
}
?>
