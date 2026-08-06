<?php
require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $contract = $_POST['contract_number'];
    $plot = $_POST['plot_id'];
    $owner = $_POST['owner_name'];
    $trash = $_POST['trash_percentage'];

    try {
        // --- 1. ส่วนที่เพิ่ม: ระบบ Backup รูปภาพที่ถูกแก้ไขเพื่อใช้สอน AI (Fine-tuning) ---
        // ดึงชื่อไฟล์รูปภาพปัจจุบันจากฐานข้อมูล
        $stmt_img = $pdo->prepare("SELECT image_filename FROM plots_inspection WHERE id = ?");
        $stmt_img->execute([$id]);
        $row = $stmt_img->fetch(PDO::FETCH_ASSOC);

        if ($row && !empty($row['image_filename']) && file_exists($row['image_filename'])) {
            $trainDir = 'training_set'; // โฟลเดอร์สำหรับเก็บชุดข้อมูลสอน AI
            if (!is_dir($trainDir)) {
                mkdir($trainDir, 0777, true); // สร้างโฟลเดอร์ถ้ายังไม่มี
            }
            
            $oldPath = $row['image_filename'];
            $filename = basename($oldPath);
            // ตั้งชื่อไฟล์ใหม่โดยระบุค่า % ที่มนุษย์แก้ไขแล้ว เพื่อให้ AI รู้ว่าค่าที่ถูกต้องคืออะไร
            $newPath = $trainDir . "/corrected_" . $trash . "pct_" . $filename;
            
            copy($oldPath, $newPath); // ก๊อปปี้ไฟล์
        }

        // --- 2. ส่วนการอัปเดตฐานข้อมูลตามที่คุณส่งมา ---
        $sql = "UPDATE plots_inspection SET 
                contract_number = ?, 
                plot_id = ?, 
                owner_name = ?, 
                trash_percentage = ? 
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$contract, $plot, $owner, $trash, $id]);
        
        echo "success";
    } catch (PDOException $e) {
        echo "error: " . $e->getMessage();
    }
}
?>