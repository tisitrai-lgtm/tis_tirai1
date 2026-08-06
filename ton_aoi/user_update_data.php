<?php
// user_update_data.php - สคริปต์สำหรับจัดการการอัปเดตข้อมูลและรูปภาพจาก edit_data.php
session_start();
require_once 'db_connect.php'; // ตรวจสอบให้แน่ใจว่าไฟล์นี้ถูกต้อง

// 🚨 ฟังก์ชันส่ง Response กลับไปที่ AJAX
// 🚨 UPDATE: เพิ่ม $agency เป็นพารามิเตอร์
function sendResponse($success, $message, $year = null, $agency = null) { 
    header('Content-Type: application/json');
    // 🚨 UPDATE: ส่ง agency กลับไปด้วย
    echo json_encode(['success' => $success, 'message' => $message, 'year' => $year, 'agency' => $agency]); 
    exit;
}

// 🚨 ฟังก์ชัน Sanitize ชื่อโฟลเดอร์
function sanitizeFolderName($name) {
    if (empty($name)) {
        return '';
    }
    // ใช้ preg_replace เพื่อลบอักขระที่ไม่ใช่ a-z, A-Z, 0-9, ขีดล่าง, และขีดกลาง
    // หมายเหตุ: โค้ดนี้ไม่รองรับภาษาไทยในชื่อโฟลเดอร์ แต่ยังคงตามโค้ดเดิมของคุณ
    $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $name); 
    $name = preg_replace('/-+/', '-', $name);
    $name = trim($name, '-');
    return $name;
}

// 🚨 ฟังก์ชันสำหรับจัดการการอัปโหลดรูปภาพ พร้อมปรับขนาด (Max 600x600)
function uploadImage($file, $baseDir, $imageTypeFolder) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ''; // ไม่มีไฟล์อัปโหลด หรือมีข้อผิดพลาด
    }

    $uploadDir = $baseDir . $imageTypeFolder . '/';

    // ตรวจสอบและสร้างโฟลเดอร์ถ้ายังไม่มี
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) { // recursive true เพื่อสร้างโฟลเดอร์ย่อยทั้งหมด
            error_log("Failed to create directory: " . $uploadDir);
            return ''; // สร้างโฟลเดอร์ไม่สำเร็จ
        }
    }

    $fileName = basename($file['name']);
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    // สร้างชื่อไฟล์ที่ไม่ซ้ำกันเพื่อป้องกันการทับซ้อน
    $newFileName = uniqid() . '-' . time() . '.' . $ext;
    
    $targetFilePath = $uploadDir . $newFileName;
    $sourceFilePath = $file['tmp_name']; // ไฟล์ชั่วคราวที่ PHP สร้างขึ้น

    // 1. ตรวจสอบชนิดไฟล์และโหลดรูปภาพ
    $image = false;
    if ($ext == 'jpg' || $ext == 'jpeg') {
        $image = @imagecreatefromjpeg($sourceFilePath);
    } elseif ($ext == 'png') {
        $image = @imagecreatefrompng($sourceFilePath);
    } 
    // ถ้าไม่ใช่ JPG/PNG หรือโหลดไม่สำเร็จ ให้อัปโหลดไฟล์เดิมโดยไม่ปรับขนาด
    if ($image === false) {
        if (move_uploaded_file($sourceFilePath, $targetFilePath)) {
            return $newFileName;
        }
        error_log("Unsupported image format or failed to load image: " . $ext);
        return '';
    }

    // 2. กำหนดขนาดใหม่ (Max 600x600)
    $originalWidth = imagesx($image);
    $originalHeight = imagesy($image);
    $maxWidth = 600;
    $maxHeight = 600;

    $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
    
    // ถ้าภาพมีขนาดเล็กกว่าหรือเท่ากับ 600x600 ไม่ต้องปรับขนาด
    if ($ratio >= 1) {
        $newWidth = $originalWidth;
        $newHeight = $originalHeight;
    } else {
        $newWidth = (int)($originalWidth * $ratio);
        $newHeight = (int)($originalHeight * $ratio);
    }
    
    // 3. สร้างรูปภาพใหม่และปรับขนาด
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // สำหรับ PNG: ต้องจัดการความโปร่งใส
    if ($ext == 'png') {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
    }

    imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
    
    // 4. บันทึกรูปภาพที่ปรับขนาดแล้วไปยัง Target Path
    $saveSuccess = false;
    if ($ext == 'jpg' || $ext == 'jpeg') {
        // ใช้คุณภาพ 80% เพื่อรักษาสมดุลระหว่างขนาดและคุณภาพ
        $saveSuccess = imagejpeg($newImage, $targetFilePath, 80); 
    } elseif ($ext == 'png') {
        // ใช้การบีบอัดระดับ 9 (สูงสุด) สำหรับ PNG
        $saveSuccess = imagepng($newImage, $targetFilePath, 9); 
    }

    // 5. ล้างหน่วยความจำ
    imagedestroy($image);
    imagedestroy($newImage);

    if ($saveSuccess) {
        // เนื่องจากเราประมวลผลและบันทึกไปที่ $targetFilePath โดยตรง จึงสำเร็จ
        return $newFileName; // คืนเฉพาะชื่อไฟล์ใหม่ที่บันทึกใน DB
    } else {
        error_log("Failed to save resized image to: " . $targetFilePath);
        return '';
    }
}

// 🚨 ฟังก์ชันสำหรับลบไฟล์รูปภาพ
function deleteImageFile($filename, $production_year, $agency, $contract_number, $plot_id, $image_type_folder) {
    if (empty($filename)) {
        return;
    }
    
    $sanitized_production_year = sanitizeFolderName($production_year);
    $sanitized_agency = sanitizeFolderName($agency);
    $sanitized_contract_number = sanitizeFolderName($contract_number);
    $sanitized_plot_id = sanitizeFolderName($plot_id);

    $basePath = "ton_aoi/uploads/{$sanitized_production_year}/{$sanitized_agency}/{$sanitized_contract_number}/{$sanitized_plot_id}/";
    $filePath = $basePath . $image_type_folder . '/' . $filename;

    if (file_exists($filePath) && is_file($filePath)) {
        unlink($filePath); // ลบไฟล์
    }
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // 🚨 UPDATE: เพิ่ม null, null เพื่อรองรับ agency
    sendResponse(false, "Invalid request method.", null, null);
}

// -----------------------------------------------------------
// 1. รับค่าตัวแปรจากฟอร์ม edit_data.php
// -----------------------------------------------------------
$record_id = $_POST['id'] ?? null; // Primary Key ของแถว
$old_plot_id = $_POST['old_plot_id'] ?? null; // ID แปลงเดิม (สำหรับอ้างอิง)

$plot_id = $_POST['plot_id'] ?? null;
$contract_number = $_POST['contract_number'] ?? null;
$quota = $_POST['quota'] ?? null;
$agency = $_POST['agency'] ?? null; // 👈 รับค่า agency
$suga_type = $_POST['suga_type'] ?? null;
$rai_area = $_POST['rai_area'] ?? null;
$notes = $_POST['notes'] ?? null;
$production_year = $_POST['production_year'] ?? null;

// เพิ่ม $emp_number ที่เคยมีในฟอร์ม แต่ไม่มีในโค้ด PHP เดิม
$emp_number = $_POST['emp_number'] ?? null; 


if (!$record_id) {
    // 🚨 UPDATE: เพิ่ม null, null เพื่อรองรับ agency
    sendResponse(false, "Missing record ID.", null, null);
}

// -----------------------------------------------------------
// 2. สร้าง Path พื้นฐานสำหรับอัปโหลด
// -----------------------------------------------------------
$image_base_url = "ton_aoi/"; 
$sanitized_year = sanitizeFolderName($production_year);
$sanitized_agency = sanitizeFolderName($agency);
$sanitized_contract = sanitizeFolderName($contract_number);
$sanitized_plot = sanitizeFolderName($plot_id);

// Base Path สำหรับการอัปโหลดไฟล์ใหม่ ( Path นี้จะถูกส่งไปให้ uploadImage() )
$baseUploadDir = "{$image_base_url}uploads/{$sanitized_year}/{$sanitized_agency}/{$sanitized_contract}/{$sanitized_plot}/";


// -----------------------------------------------------------
// 3. กำหนดรายการรูปภาพทั้งหมดและจัดการการอัปโหลด/ลบ
// -----------------------------------------------------------
$image_fields = [
    // [ชื่อคอลัมน์] => [โฟลเดอร์ย่อย]
    "estimate_ton_1" => "estimate_ton",
    "estimate_ton_2" => "estimate_ton",
    "evaluate_ton_1" => "evaluate_ton",
    "evaluate_ton_2" => "evaluate_ton",
    "remaining_cane_1_img_1" => "remaining_cane_1",
    "remaining_cane_1_img_2" => "remaining_cane_1",
    "remaining_cane_2_img_1" => "remaining_cane_2",
    "remaining_cane_2_img_2" => "remaining_cane_2",
    "remaining_cane_3_img_1" => "remaining_cane_3",
    "remaining_cane_3_img_2" => "remaining_cane_3",
];

$update_images = [];
$upload_errors = [];

foreach ($image_fields as $db_column => $subfolder) {
    $existing_name = $_POST['existing_' . $db_column] ?? null;
    $new_file_uploaded = isset($_FILES[$db_column]) && $_FILES[$db_column]['error'] === UPLOAD_ERR_OK;
    
    // A. มีการอัปโหลดไฟล์ใหม่
    if ($new_file_uploaded) {
        $new_filename = uploadImage($_FILES[$db_column], $baseUploadDir, $subfolder);
        
        if (empty($new_filename)) {
            $upload_errors[] = "Failed to upload new file for " . $db_column;
            $update_images[$db_column] = $existing_name; // ใช้ชื่อเดิมถ้าอัปโหลดล้มเหลว
        } else {
            $update_images[$db_column] = $new_filename;
            
            // ลบไฟล์เก่าถ้ามี
            if (!empty($existing_name)) {
                deleteImageFile(
                    $existing_name, 
                    $production_year, 
                    $agency, 
                    $contract_number, 
                    $old_plot_id, // ใช้ ID แปลงเดิม เพราะไฟล์เดิมถูกเก็บใน Path เดิม
                    $subfolder
                );
            }
        }
    } 
    // B. ไม่มีไฟล์ใหม่ถูกอัปโหลด
    else {
        // ตรวจสอบจาก existing_name ที่ถูกส่งมาจากฟอร์ม
        $update_images[$db_column] = !empty($existing_name) ? $existing_name : null;
    }
}

// ถ้ามี error ในการอัปโหลดไฟล์ ควรหยุดการทำงาน
if (!empty($upload_errors)) {
    // 🚨 UPDATE: เพิ่ม agency ใน response
    sendResponse(false, "File Upload Errors: " . implode(", ", $upload_errors), $production_year, $agency);
}


// -----------------------------------------------------------
// 4. เตรียมและรัน SQL UPDATE
// -----------------------------------------------------------
// เพิ่ม emp_number ใน SQL ด้วย
$sql = "UPDATE cane_plot_data SET 
             plot_id = ?,
             contract_number = ?,
             quota = ?,
             agency = ?,
             emp_number = ?, 
             suga_type = ?,
             rai_area = ?,
             notes = ?,
             estimate_ton_1 = ?,
             estimate_ton_2 = ?,
             evaluate_ton_1 = ?,
             evaluate_ton_2 = ?,
             remaining_cane_1_img_1 = ?,
             remaining_cane_1_img_2 = ?,
             remaining_cane_2_img_1 = ?,
             remaining_cane_2_img_2 = ?,
             remaining_cane_3_img_1 = ?,
             remaining_cane_3_img_2 = ? 
         WHERE id = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    // 🚨 UPDATE: เพิ่ม agency ใน response
    sendResponse(false, "Prepare failed: " . $conn->error, $production_year, $agency);
}

// Bind Parameters (ประเภท: ssssssissisissssssi - s = string, i = integer)
$params = [
    $plot_id,
    $contract_number,
    $quota,
    $agency,
    $emp_number, // เพิ่ม: นักส่งเสริม
    $suga_type,
    $rai_area,
    $notes,
    $update_images['estimate_ton_1'],
    $update_images['estimate_ton_2'],
    $update_images['evaluate_ton_1'],
    $update_images['evaluate_ton_2'],
    $update_images['remaining_cane_1_img_1'],
    $update_images['remaining_cane_1_img_2'],
    $update_images['remaining_cane_2_img_1'],
    $update_images['remaining_cane_2_img_2'],
    $update_images['remaining_cane_3_img_1'],
    $update_images['remaining_cane_3_img_2'],
    $record_id // <-- ตัวสุดท้าย
];

// จำนวนพารามิเตอร์เพิ่มขึ้นจาก 18 เป็น 19 ตัว (เพราะเพิ่ม emp_number)
$types = "ssssssisssssssssssi"; // s(plot_id), s(contract), s(quota), s(agency), s(emp_number), s(suga_type), i(rai_area), s(notes), s*10 (images), i(record_id)
// *** สมมติว่า rai_area เป็น integer (i) และ emp_number เป็น string (s) ***

$stmt->bind_param($types, ...$params);


if ($stmt->execute()) {
    $stmt->close();
    
    // 5. กรณีที่ Plot ID ถูกเปลี่ยน ต้องย้ายโฟลเดอร์รูปภาพหลัก
    if ($old_plot_id !== $plot_id) {
        $old_sanitized_plot = sanitizeFolderName($old_plot_id);
        
        // สร้าง Path โดยใช้ข้อมูลเดิมและข้อมูลใหม่ที่ถูก Sanitize แล้ว
        $old_base_dir = "{$image_base_url}uploads/{$sanitized_year}/{$sanitized_agency}/{$sanitized_contract}/{$old_sanitized_plot}";
        $new_base_dir = "{$image_base_url}uploads/{$sanitized_year}/{$sanitized_agency}/{$sanitized_contract}/{$sanitized_plot}";

        // ตรวจสอบและย้ายโฟลเดอร์หลัก
        if (is_dir($old_base_dir) && $old_base_dir !== $new_base_dir) {
            // ใช้ rename() เพื่อย้าย/เปลี่ยนชื่อโฟลเดอร์
            if (!rename($old_base_dir, $new_base_dir)) {
                error_log("Failed to rename directory from {$old_base_dir} to {$new_base_dir}");
            }
        }
    }

    // 🚨 UPDATE: ส่ง JSON Response กลับไปที่ AJAX พร้อม agency
    sendResponse(true, "บันทึกข้อมูลแปลง {$plot_id} เรียบร้อยแล้ว", $production_year, $agency);
    
} else {
    $stmt->close();
    // 🚨 UPDATE: ส่ง Error Response พร้อม agency
    sendResponse(false, "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $stmt->error, $production_year, $agency);
}

$conn->close();
?>