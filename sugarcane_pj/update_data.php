<?php
// water_update_data.php - จัดการการอัปเดตข้อมูล
session_start();
require_once 'db_connect.php'; // ตรวจสอบให้แน่ใจว่าไฟล์นี้ถูกต้องและอยู่ในพาธที่เข้าถึงได้

// ฟังก์ชันแสดง SweetAlert2
function showAlert($message, $icon = 'success', $redirectUrl = null) {
    echo "<!DOCTYPE html><html><head><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body style='background:#f4f7f6;'>";
    echo "<script>";
    echo "document.addEventListener('DOMContentLoaded', function() {";
    echo "Swal.fire({ icon: '$icon', title: '$message', showConfirmButton: false, timer: 1500 }).then(() => {";
    if ($redirectUrl) {
        echo "window.location.href = '$redirectUrl';";
    } else {
        echo "window.history.back();";
    }
    echo "});";
    echo "});";
    echo "</script></body></html>";
    exit;
}

// ฟังก์ชันสำหรับจัดการการอัปโหลดรูปภาพ บีบอัด และใส่ลายน้ำ
function uploadImage($file, $baseDir, $imageTypeFolder, $plot_id = '') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ''; 
    }

    $uploadDir = $baseDir . $imageTypeFolder . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = basename($file['name']);
    $newFileName = time() . '_' . uniqid() . '.jpg'; // บังคับเป็น jpg
    $targetFilePath = $uploadDir . $newFileName;
    $tmpPath = $file['tmp_name'];

    $mime = mime_content_type($tmpPath);
    $sourceImage = null;
    
    if ($mime == 'image/jpeg') {
        $sourceImage = @imagecreatefromjpeg($tmpPath);
    } elseif ($mime == 'image/png') {
        $sourceImage = @imagecreatefrompng($tmpPath);
    } elseif ($mime == 'image/webp') {
        $sourceImage = @imagecreatefromwebp($tmpPath);
    }

    if ($sourceImage) {
        // ย่อขนาดสูงสุด 1200px
        $origWidth = imagesx($sourceImage);
        $origHeight = imagesy($sourceImage);
        $maxWidth = 1200;
        $maxHeight = 1200;
        
        $newWidth = $origWidth;
        $newHeight = $origHeight;

        if ($origWidth > $maxWidth || $origHeight > $maxHeight) {
            $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight);
            $newWidth = round($origWidth * $ratio);
            $newHeight = round($origHeight * $ratio);
        }

        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        $white = imagecolorallocate($newImage, 255, 255, 255);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $white);
        imagecopyresampled($newImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

        // ลายน้ำ
        $watermarkText = "Plot ID: " . ($plot_id ? $plot_id : 'N/A') . " | Date: " . date('Y-m-d H:i');
        $fontSize = 5;
        $fontWidth = imagefontwidth($fontSize);
        $fontHeight = imagefontheight($fontSize);
        $textWidth = $fontWidth * strlen($watermarkText);
        
        $padding = 10;
        $boxX = $newWidth - $textWidth - ($padding * 2);
        $boxY = $newHeight - $fontHeight - ($padding * 2);
        if ($boxX < 0) $boxX = 0;
        
        $bgColor = imagecolorallocatealpha($newImage, 0, 0, 0, 50); // ดำโปร่งแสง
        imagefilledrectangle($newImage, $boxX, $boxY, $newWidth, $newHeight, $bgColor);
        
        $textColor = imagecolorallocate($newImage, 255, 255, 0); // สีเหลือง
        imagestring($newImage, $fontSize, $boxX + $padding, $boxY + $padding, $watermarkText, $textColor);

        // บันทึกคุณภาพ 80%
        imagejpeg($newImage, $targetFilePath, 80);
        imagedestroy($sourceImage);
        imagedestroy($newImage);
        
        return $newFileName;
    } else {
        // Fallback
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        $fallbackName = time() . '_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($tmpPath, $uploadDir . $fallbackName)) {
            return $fallbackName;
        }
    }
    return '';
}

// ฟังก์ชันสำหรับลบไฟล์รูปภาพ
function deleteImageFile($filename, $production_year, $agency, $contract_number, $plot_id, $image_type_folder) {
    if (empty($filename)) {
        return;
    }
    // ฟังก์ชันช่วยในการคลีนชื่อสำหรับใช้เป็น Path (รวมสระและวรรณยุกต์ไทย)
    if (!function_exists('sanitizeForPath')) {
        function sanitizeForPath($string) {
            if (empty($string)) return 'unspecified';
            return preg_replace('/[^\p{L}\p{M}\p{N}_-]/u', '', str_replace(' ', '_', $string));
        }
    }

    $sanitized_production_year = sanitizeForPath($production_year);
    $sanitized_agency = sanitizeForPath($agency);
    $sanitized_contract_number = sanitizeForPath($contract_number);
    $sanitized_plot_id = sanitizeForPath($plot_id);

    $basePath = "uploads/{$sanitized_production_year}/{$sanitized_agency}/{$sanitized_contract_number}/{$sanitized_plot_id}/";
    $filePath = $basePath . $image_type_folder . '/' . $filename;

    if (file_exists($filePath) && is_file($filePath)) {
        unlink($filePath); // ลบไฟล์
        error_log("Deleted file: " . $filePath); // บันทึกใน log เพื่อการดีบัก
    } else {
        error_log("File not found for deletion: " . $filePath);
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ดึงข้อมูล ID และ Original Data สำหรับการอัปเดตและการจัดการไฟล์
    $id = $_POST['id'] ?? null;
    $original_plot_id = $_POST['original_plot_id'] ?? null;
    $original_production_year = $_POST['original_production_year'] ?? null;

    if (empty($id) || empty($original_plot_id) || empty($original_production_year)) {
        showAlert('ข้อมูลไม่ครบถ้วนสำหรับการแก้ไข.', 'warning');
    }

    // ดึงข้อมูลฟอร์มที่ผู้ใช้แก้ไข
    $production_year = $_POST['production_year'] ?? $original_production_year; // อาจมีการแก้ไขปี (แต่ควรแก้ไข id แปลงให้เป็นของปีนั้นๆ)
    $plot_id = $_POST['plot_id'] ?? null;
    $contract_number = $_POST['contract_number'] ?? null;
    $quota = $_POST['quota'] ?? null;
    $agency = $_POST['agency'] ?? null;
    $rai_area = $_POST['rai_area'] ?? null;
    $soil_type = $_POST['soil_type'] ?? null;
    $soil_preparation_details = $_POST['soil_preparation_details'] ?? null;
    $cane_variety = $_POST['cane_variety'] ?? null;
    $planting_details = $_POST['planting_details'] ?? null;
    $watering_details = $_POST['watering_details'] ?? null;
    $germination_percentage = $_POST['germination_percentage'] ?? null;
    $notes = $_POST['notes'] ?? null;

    // ดึงชื่อไฟล์รูปภาพปัจจุบันจาก Hidden Field
    $current_soil_image = $_POST['current_soil_image'] ?? '';
    $current_soil_preparation_image = $_POST['current_soil_preparation_image'] ?? '';
    $current_cane_variety_image = $_POST['current_cane_variety_image'] ?? '';
    $current_planting_image = $_POST['current_planting_image'] ?? '';
    $current_watering_image = $_POST['current_watering_image'] ?? '';
    $current_germination_image = $_POST['current_germination_image'] ?? '';

    // ตรวจสอบว่ามีการขอให้ลบรูปภาพหรือไม่
    $delete_soil_image = isset($_POST['delete_soil_image']);
    $delete_soil_preparation_image = isset($_POST['delete_soil_preparation_image']);
    $delete_cane_variety_image = isset($_POST['delete_cane_variety_image']);
    $delete_planting_image = isset($_POST['delete_planting_image']);
    $delete_watering_image = isset($_POST['delete_watering_image']);
    $delete_germination_image = isset($_POST['delete_germination_image']);


    // --- ตรวจสอบ Plot ID ซ้ำกันในปีการผลิตเดียวกัน (กรณีมีการเปลี่ยนแปลง Plot ID หรือ Production Year) ---
    // ตรวจสอบเฉพาะเมื่อ plot_id หรือ production_year มีการเปลี่ยนแปลงจากค่าเดิม
    if ($plot_id !== $original_plot_id || $production_year !== $original_production_year) {
        $checkSql = "SELECT COUNT(*) FROM soil_data WHERE plot_id = ? AND production_year = ? AND id != ?";
        $checkStmt = $conn->prepare($checkSql);
        if ($checkStmt === false) {
            error_log("Prepare failed: " . $conn->error);
            showAlert('ข้อผิดพลาดในการตรวจสอบข้อมูล (Prepare Failed).', 'error');
        }
        $checkStmt->bind_param("ssi", $plot_id, $production_year, $id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $row = $checkResult->fetch_row();
        $count = $row[0];
        $checkStmt->close();

        if ($count > 0) {
            showAlert('ไม่สามารถบันทึกข้อมูลได้: ID แปลง "' . $plot_id . '" มีอยู่แล้วสำหรับปีการผลิต "' . $production_year . '"', 'error');
        }
    }
    // --- สิ้นสุดการตรวจสอบ Plot ID ซ้ำ ---


    // กำหนด Base Directory สำหรับรูปภาพของ Plot นี้
    // ใช้ original_production_year, original_agency, original_contract_number, original_plot_id
    // เพื่อหาพาธของโฟลเดอร์เดิมที่อาจมีรูปภาพเก่าอยู่
    // ถ้ามีการเปลี่ยน plot_id หรือ production_year การจัดการโฟลเดอร์จะต้องซับซ้อนขึ้น
    // ในที่นี้จะยังคงใช้โฟลเดอร์เดิมในการค้นหารูปภาพเก่า ถ้ามีการเปลี่ยน plot_id/year
    // แนะนำว่าถ้ามีการเปลี่ยน plot_id/year ควรย้ายโฟลเดอร์รูปภาพด้วย
    // หรือแจ้งให้ผู้ใช้ทราบว่ารูปภาพเก่าจะไม่ถูกย้ายตาม
    
    // ดึงข้อมูล agency และ contract_number จาก DB ของ record เดิม
    $prev_data_sql = "SELECT production_year, agency, contract_number, plot_id FROM soil_data WHERE id = ?";
    $prev_data_stmt = $conn->prepare($prev_data_sql);
    $prev_data_stmt->bind_param("i", $id);
    $prev_data_stmt->execute();
    $prev_data_result = $prev_data_stmt->get_result();
    $prev_data_row = $prev_data_result->fetch_assoc();
    $prev_data_stmt->close();

    $prev_production_year = $prev_data_row['production_year'] ?? $original_production_year;
    $prev_agency = $prev_data_row['agency'] ?? null;
    $prev_contract_number = $prev_data_row['contract_number'] ?? null;
    $prev_plot_id = $prev_data_row['plot_id'] ?? $original_plot_id;


    // ฟังก์ชันช่วยในการคลีนชื่อสำหรับใช้เป็น Path (รวมสระและวรรณยุกต์ไทย)
    if (!function_exists('sanitizeForPath')) {
        function sanitizeForPath($string) {
            if (empty($string)) return 'unspecified';
            return preg_replace('/[^\p{L}\p{M}\p{N}_-]/u', '', str_replace(' ', '_', $string));
        }
    }

    // สร้าง base path สำหรับโฟลเดอร์เดิม
    $baseOldUploadPath = "uploads/" . sanitizeForPath($prev_production_year) . "/" .
                         sanitizeForPath($prev_agency) . "/" .
                         sanitizeForPath($prev_contract_number) . "/" .
                         sanitizeForPath($prev_plot_id) . "/";

    // สร้าง base path สำหรับโฟลเดอร์ใหม่ (หากมีการเปลี่ยนแปลง)
    $baseNewUploadPath = "uploads/" . sanitizeForPath($production_year) . "/" .
                         sanitizeForPath($agency) . "/" .
                         sanitizeForPath($contract_number) . "/" .
                         sanitizeForPath($plot_id) . "/";


    // --- จัดการรูปภาพแต่ละประเภท ---
    $new_soil_image_name = $current_soil_image; // ตั้งค่าเริ่มต้นเป็นชื่อไฟล์ปัจจุบัน
    if ($delete_soil_image) {
        deleteImageFile($current_soil_image, $prev_production_year, $prev_agency, $prev_contract_number, $prev_plot_id, 'soil_image');
        $new_soil_image_name = ''; // เคลียร์ชื่อไฟล์ใน DB
    }
    if (isset($_FILES['soil_image']) && $_FILES['soil_image']['error'] === UPLOAD_ERR_OK) {
        if (!empty($current_soil_image)) { // ถ้ามีรูปเก่า ให้ลบก่อนอัปโหลดรูปใหม่
            deleteImageFile($current_soil_image, $prev_production_year, $prev_agency, $prev_contract_number, $prev_plot_id, 'soil_image');
        }
        $new_soil_image_name = uploadImage($_FILES['soil_image'], $baseNewUploadPath, 'soil_image', $plot_id);
    }

    $new_soil_preparation_image_name = $current_soil_preparation_image;
    if ($delete_soil_preparation_image) {
        deleteImageFile($current_soil_preparation_image, $prev_production_year, $prev_agency, $prev_contract_number, $prev_plot_id, 'soil_preparation_image');
        $new_soil_preparation_image_name = '';
    }
    if (isset($_FILES['soil_preparation_image']) && $_FILES['soil_preparation_image']['error'] === UPLOAD_ERR_OK) {
        if (!empty($current_soil_preparation_image)) {
            deleteImageFile($current_soil_preparation_image, $prev_production_year, $prev_agency, $prev_contract_number, $prev_plot_id, 'soil_preparation_image');
        }
        $new_soil_preparation_image_name = uploadImage($_FILES['soil_preparation_image'], $baseNewUploadPath, 'soil_preparation_image', $plot_id);
    }

    $new_cane_variety_image_name = $current_cane_variety_image;
    if ($delete_cane_variety_image) {
        deleteImageFile($current_cane_variety_image, $prev_production_year, $prev_agency, $prev_contract_number, $prev_plot_id, 'cane_variety_image');
        $new_cane_variety_image_name = '';
    }
    if (isset($_FILES['cane_variety_image']) && $_FILES['cane_variety_image']['error'] === UPLOAD_ERR_OK) {
        if (!empty($current_cane_variety_image)) {
            deleteImageFile($current_cane_variety_image, $prev_production_year, $prev_agency, $prev_contract_number, $prev_plot_id, 'cane_variety_image');
        }
        $new_cane_variety_image_name = uploadImage($_FILES['cane_variety_image'], $baseNewUploadPath, 'cane_variety_image', $plot_id);
    }

    $new_planting_image_name = $current_planting_image;
    if ($delete_planting_image) {
        deleteImageFile($current_planting_image, $prev_production_year, $prev_agency, $prev_contract_number, $prev_plot_id, 'planting_image');
        $new_planting_image_name = '';
    }
    if (isset($_FILES['planting_image']) && $_FILES['planting_image']['error'] === UPLOAD_ERR_OK) {
        if (!empty($current_planting_image)) {
            deleteImageFile($current_planting_image, $prev_production_year, $prev_agency, $prev_contract_number, $prev_plot_id, 'planting_image');
        }
        $new_planting_image_name = uploadImage($_FILES['planting_image'], $baseNewUploadPath, 'planting_image', $plot_id);
    }

    $new_watering_image_name = $current_watering_image;
    if ($delete_watering_image) {
        deleteImageFile($current_watering_image, $prev_production_year, $prev_agency, $prev_contract_number, $prev_plot_id, 'watering_image');
        $new_watering_image_name = '';
    }
    if (isset($_FILES['watering_image']) && $_FILES['watering_image']['error'] === UPLOAD_ERR_OK) {
        if (!empty($current_watering_image)) {
            deleteImageFile($current_watering_image, $prev_production_year, $prev_agency, $prev_contract_number, $prev_plot_id, 'watering_image');
        }
        $new_watering_image_name = uploadImage($_FILES['watering_image'], $baseNewUploadPath, 'watering_image', $plot_id);
    }

    $new_germination_image_name = $current_germination_image;
    if ($delete_germination_image) {
        deleteImageFile($current_germination_image, $prev_production_year, $prev_agency, $prev_contract_number, $prev_plot_id, 'germination_image');
        $new_germination_image_name = '';
    }
    if (isset($_FILES['germination_image']) && $_FILES['germination_image']['error'] === UPLOAD_ERR_OK) {
        if (!empty($current_germination_image)) {
            deleteImageFile($current_germination_image, $prev_production_year, $prev_agency, $prev_contract_number, $prev_plot_id, 'germination_image');
        }
        $new_germination_image_name = uploadImage($_FILES['germination_image'], $baseNewUploadPath, 'germination_image', $plot_id);
    }

    // --- เตรียม SQL UPDATE Statement ---
    $sql = "UPDATE soil_data SET
                production_year = ?,
                plot_id = ?,
                contract_number = ?,
                quota = ?,
                agency = ?,
                rai_area = ?,
                soil_type = ?,
                soil_image = ?,
                soil_preparation_details = ?,
                soil_preparation_image = ?,
                cane_variety = ?,
                cane_variety_image = ?,
                planting_details = ?,
                planting_image = ?,
                watering_details = ?,
                watering_image = ?,
                germination_percentage = ?,
                germination_image = ?,
                notes = ?
            WHERE id = ?"; // อัปเดตตาม id

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        showAlert('ข้อผิดพลาดในการเตรียมการอัปเดตข้อมูล (Prepare Failed).', 'error');
    }

    // Bind parameters
    $stmt->bind_param("sssssdsssssssssssssi", // s:string, d:double/float, i:integer
        $production_year,
        $plot_id,
        $contract_number,
        $quota,
        $agency,
        $rai_area,
        $soil_type,
        $new_soil_image_name, // ชื่อไฟล์ใหม่/ที่อัปเดต
        $soil_preparation_details,
        $new_soil_preparation_image_name,
        $cane_variety,
        $new_cane_variety_image_name,
        $planting_details,
        $new_planting_image_name,
        $watering_details,
        $new_watering_image_name,
        $germination_percentage,
        $new_germination_image_name,
        $notes,
        $id // id ของแถวที่จะอัปเดต
    );

    if ($stmt->execute()) {
        showAlert('บันทึกการแก้ไขข้อมูลสำเร็จ!', 'success', "dashboard.php?year={$production_year}");
    } else {
        error_log("Execute failed: " . $stmt->error);
        showAlert('เกิดข้อผิดพลาดในการบันทึกการแก้ไข: ' . $stmt->error, 'error');
    }

    $stmt->close();
    $conn->close();

} else {
    // หากเข้าถึงหน้านี้โดยตรงโดยไม่ผ่านฟอร์ม POST
    header("Location: index.php");
    exit;
}
?>