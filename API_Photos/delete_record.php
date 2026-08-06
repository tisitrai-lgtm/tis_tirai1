<?php
// delete_record.php
require_once 'db_config.php';

if (isset($_POST['id'])) {
    $id = $_POST['id'];
    
    // 1. หาชื่อไฟล์รูปภาพก่อนลบ
    $stmt = $pdo->prepare("SELECT image_filename FROM plots_inspection WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    
    if ($row) {
        // 2. ลบไฟล์รูปออกจากโฟลเดอร์
        if (file_exists($row['image_filename'])) {
            unlink($row['image_filename']);
        }
        
        // 3. ลบข้อมูลจากฐานข้อมูล
        $del = $pdo->prepare("DELETE FROM plots_inspection WHERE id = ?");
        $del->execute([$id]);
        echo "success";
    }
}
?>