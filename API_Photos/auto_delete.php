<?php
require_once 'db_config.php';

try {
    // 1. ดึงรายชื่อไฟล์รูปภาพของข้อมูลที่เก่ากว่า 60 วันมาเก็บไว้ก่อน
    $sql_select = "SELECT image_filename FROM plots_inspection WHERE created_at < NOW() - INTERVAL 60 DAY";
    $stmt = $pdo->query($sql_select);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $file = $row['image_filename'];
        if (file_exists($file)) {
            unlink($file); // ลบไฟล์รูปออกจากโฟลเดอร์
        }
    }

    // 2. ลบข้อมูลออกจากฐานข้อมูล
    $sql_delete = "DELETE FROM plots_inspection WHERE created_at < NOW() - INTERVAL 60 DAY";
    $pdo->exec($sql_delete);

    echo "ล้างข้อมูลเก่าเกิน 60 วันเรียบร้อยแล้ว";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>