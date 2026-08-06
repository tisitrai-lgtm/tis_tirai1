<?php
// insertData.php - จัดการการเพิ่มข้อมูลใหม่เข้าสู่ฐานข้อมูล
session_start();
require_once 'db_connect.php';

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

// ฟังก์ชันสำหรับจัดรูปชื่อโฟลเดอร์ให้ปลอดภัย (ภาษาไทยรองรับได้)
function sanitizeForPath($string) {
    if (empty($string)) return 'unspecified';
    // แทนที่อักขระที่ไม่ปลอดภัยด้วย underscore แต่ยังคงภาษาไทยไว้ได้โดยการตรวจจับด้วย regex แบบ Unicode
    // เพิ่ม \p{M} เพื่อให้รองรับ สระ และ วรรณยุกต์ ไทย (เช่น ี, ้, ิ)
    return preg_replace('/[^\p{L}\p{M}\p{N}_-]/u', '', str_replace(' ', '_', $string));
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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // รับค่าจากฟอร์ม
    $production_year = $_POST['production_year'] ?? '';
    $plot_id = $_POST['plot_id'] ?? '';
    $contract_number = $_POST['contract_number'] ?? '';
    $quota = $_POST['quota'] ?? '';
    $agency = $_POST['agency'] ?? '';
    $rai_area = $_POST['rai_area'] ?? 0;
    
    $soil_type = $_POST['soil_type'] ?? '';
    $soil_preparation_details = $_POST['soil_preparation_details'] ?? '';
    $cane_variety = $_POST['cane_variety'] ?? '';
    $planting_details = $_POST['planting_details'] ?? '';
    $watering_details = $_POST['watering_details'] ?? '';
    $germination_percentage = $_POST['germination_percentage'] ?? 0;
    $notes = $_POST['notes'] ?? '';

    // การตรวจสอบข้อมูลเบื้องต้น
    if (empty($plot_id) || empty($production_year)) {
        showAlert('กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน', 'warning');
    }

    // --- ตรวจสอบ Plot ID ซ้ำในปีเดียวกัน ---
    $checkSql = "SELECT COUNT(*) FROM soil_data WHERE plot_id = ? AND production_year = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ss", $plot_id, $production_year);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $rowCount = $checkResult->fetch_row()[0];
    $checkStmt->close();

    if ($rowCount > 0) {
        showAlert('มีข้อมูล ID แปลงนี้ในปีการผลิตนี้อยู่แล้ว ไม่สามารถเพิ่มซ้ำได้', 'error');
    }

    // กำหนด Base Directory สำหรับอัปโหลด
    $py = sanitizeForPath($production_year);
    $ag = sanitizeForPath($agency);
    $cn = sanitizeForPath($contract_number);
    $pi = sanitizeForPath($plot_id);
    
    $baseUploadPath = "uploads/{$py}/{$ag}/{$cn}/{$pi}/";

    // จัดการอัปโหลดไฟล์
    $soil_image = uploadImage($_FILES['soil_image'] ?? null, $baseUploadPath, 'soil_image', $plot_id);
    $soil_preparation_image = uploadImage($_FILES['soil_preparation_image'] ?? null, $baseUploadPath, 'soil_preparation_image', $plot_id);
    $cane_variety_image = uploadImage($_FILES['cane_variety_image'] ?? null, $baseUploadPath, 'cane_variety_image', $plot_id);
    $planting_image = uploadImage($_FILES['planting_image'] ?? null, $baseUploadPath, 'planting_image', $plot_id);
    $watering_image = uploadImage($_FILES['watering_image'] ?? null, $baseUploadPath, 'watering_image', $plot_id);
    $germination_image = uploadImage($_FILES['germination_image'] ?? null, $baseUploadPath, 'germination_image', $plot_id);

    // เตรียมคำสั่ง SQL สำหรับเพิ่มข้อมูล
    $sql = "INSERT INTO soil_data (
                production_year, plot_id, contract_number, quota, agency, rai_area,
                soil_type, soil_image, soil_preparation_details, soil_preparation_image,
                cane_variety, cane_variety_image, planting_details, planting_image,
                watering_details, watering_image, germination_percentage, germination_image,
                notes, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("sssssdsssssssssssss", 
            $production_year, $plot_id, $contract_number, $quota, $agency, $rai_area,
            $soil_type, $soil_image, $soil_preparation_details, $soil_preparation_image,
            $cane_variety, $cane_variety_image, $planting_details, $planting_image,
            $watering_details, $watering_image, $germination_percentage, $germination_image,
            $notes
        );

        if ($stmt->execute()) {
            showAlert('บันทึกข้อมูลเรียบร้อยแล้ว!', 'success', "dashboard.php?year=".urlencode($production_year));
        } else {
            showAlert('เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $stmt->error, 'error');
        }
        $stmt->close();
    } else {
        showAlert('เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL', 'error');
    }

    $conn->close();
} else {
    header("Location: index.php");
    exit;
}
?>