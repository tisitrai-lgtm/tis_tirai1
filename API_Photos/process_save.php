<?php
require_once 'db_config.php';

// 1. รับข้อมูลจาก Modal (อนุญาตให้ว่างได้ตามโจทย์)
// ใช้ ?? เพื่อกำหนดค่าเริ่มต้นกรณีผู้ใช้ปล่อยว่าง
$contract_num   = !empty($_POST['contract_number']) ? trim($_POST['contract_number']) : "Unspecified";
$plot_id        = !empty($_POST['plot_id']) ? trim($_POST['plot_id']) : "Unknown_Plot";
$owner_name     = !empty($_POST['owner_name']) ? trim($_POST['owner_name']) : "ไม่ได้ระบุชื่อ";
$nss_name       = !empty($_POST['nss_name']) ? trim($_POST['nss_name']) : "ไม่ได้ระบุชื่อ นสส.";
$area_rai       = !empty($_POST['area_rai']) ? floatval($_POST['area_rai']) : 0;

// รับค่าเปอร์เซ็นต์ที่คำนวณมาแล้วจาก Modal
$trash_percent  = isset($_POST['trash_percentage']) ? floatval($_POST['trash_percentage']) : 0;

// 2. จัดการเรื่องโฟลเดอร์ (uploads / เลขสัญญา / ID แปลง)
$base_dir = "uploads";

// ทำความสะอาดชื่อโฟลเดอร์เพื่อป้องกัน Error ของระบบไฟล์
$safe_contract = preg_replace('/[^A-Za-z0-9_\-]/', '', $contract_num);
$safe_plot     = preg_replace('/[^A-Za-z0-9_\-]/', '', $plot_id);

$plot_dir = $base_dir . "/" . $safe_contract . "/" . $safe_plot;

// สร้างโฟลเดอร์แบบ Recursive (สร้างโฟลเดอร์ย่อยให้ครบในทีเดียว)
if (!is_dir($plot_dir)) {
    mkdir($plot_dir, 0777, true);
}

// 3. จัดการไฟล์รูปภาพ
if (isset($_FILES["sugarcane_image"]) && $_FILES["sugarcane_image"]["error"] == 0) {
    $file_ext = pathinfo($_FILES["sugarcane_image"]["name"], PATHINFO_EXTENSION);
    $new_filename = time() . "." . $file_ext; 
    $target_path = $plot_dir . "/" . $new_filename;

    if (move_uploaded_file($_FILES["sugarcane_image"]["tmp_name"], $target_path)) {
        
        // 4. บันทึกข้อมูลลงฐานข้อมูล (ไม่ต้องเรียก API ซ้ำแล้ว)
        try {
            $sql = "INSERT INTO plots_inspection 
                    (plot_id, contract_number, owner_name, nss_name, area_rai, trash_percentage, image_filename) 
                    VALUES (:plot_id, :contract_num, :owner_name, :nss_name, :area_rai, :trash_percent, :image_filename)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':plot_id'        => $plot_id,
                ':contract_num'   => $contract_num,
                ':owner_name'     => $owner_name,
                ':nss_name'       => $nss_name,
                ':area_rai'       => $area_rai,
                ':trash_percent'  => $trash_percent,
                ':image_filename' => $target_path
            ]);

            echo "<script>
                    alert('บันทึกข้อมูลเรียบร้อยแล้ว');
                    window.location.href = 'index.php';
                  </script>";

        } catch (PDOException $e) {
            // หาก DB ล้มเหลว ให้ลบไฟล์ที่เพิ่งเก็บไปทิ้ง
            if(file_exists($target_path)) unlink($target_path);
            die("เกิดข้อผิดพลาดในการบันทึก DB: " . $e->getMessage());
        }

    } else {
        echo "<script>alert('ขออภัย! ไม่สามารถย้ายไฟล์ไปยังโฟลเดอร์ได้'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('ไม่พบไฟล์รูปภาพที่ส่งมาจาก Modal'); window.history.back();</script>";
}
?>