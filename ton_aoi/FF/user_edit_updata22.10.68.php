<?php
// user_update_data.php - สคริปต์สำหรับจัดการการอัปเดตข้อมูลและรูปภาพจาก edit_data.php
session_start();
require_once 'db_connect.php'; // ตรวจสอบให้แน่ใจว่าไฟล์นี้ถูกต้อง

// 🚨 ฟังก์ชันส่ง Response กลับไปที่ AJAX
function sendResponse($success, $message, $year = null) {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message, 'year' => $year]);
    exit;
}

// 🚨 ฟังก์ชัน Sanitize ชื่อโฟลเดอร์ (ปรับให้สอดคล้องกับตรรกะใน deleteImageFile ของคุณ)
function sanitizeFolderName($name) {
    if (empty($name)) {
        return '';
    }
    // ใช้ preg_replace เพื่อลบอักขระที่ไม่ใช่ a-z, A-Z, 0-9, ขีดล่าง, และขีดกลาง
    $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $name); 
    $name = preg_replace('/-+/', '-', $name);
    $name = trim($name, '-');
    return $name;
}

// 🚨 ฟังก์ชันสำหรับจัดการการอัปโหลดรูปภาพ 
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
    // สร้างชื่อไฟล์ที่ไม่ซ้ำกันเพื่อป้องกันการทับซ้อน
    $ext = pathinfo($fileName, PATHINFO_EXTENSION);
    $newFileName = uniqid() . '-' . time() . '.' . $ext;
    
    $targetFilePath = $uploadDir . $newFileName;

    if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
        return $newFileName; // คืนเฉพาะชื่อไฟล์ใหม่ที่บันทึกใน DB
    } else {
        error_log("Failed to move uploaded file to: " . $targetFilePath);
        return ''; // ย้ายไฟล์ไม่สำเร็จ
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
    sendResponse(false, "Invalid request method.");
}

// -----------------------------------------------------------
// 1. รับค่าตัวแปรจากฟอร์ม edit_data.php
// -----------------------------------------------------------
$record_id = $_POST['id'] ?? null; // Primary Key ของแถว
$old_plot_id = $_POST['old_plot_id'] ?? null; // ID แปลงเดิม (สำหรับอ้างอิง)

$plot_id = $_POST['plot_id'] ?? null;
$contract_number = $_POST['contract_number'] ?? null;
$quota = $_POST['quota'] ?? null;
$agency = $_POST['agency'] ?? null;
$suga_type = $_POST['suga_type'] ?? null;
$rai_area = $_POST['rai_area'] ?? null;
$notes = $_POST['notes'] ?? null;
$production_year = $_POST['production_year'] ?? null;

if (!$record_id) {
    sendResponse(false, "Missing record ID.");
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
    sendResponse(false, "File Upload Errors: " . implode(", ", $upload_errors));
}


// -----------------------------------------------------------
// 4. เตรียมและรัน SQL UPDATE
// -----------------------------------------------------------
$sql = "UPDATE cane_plot_data SET 
            plot_id = ?,
            contract_number = ?,
            quota = ?,
            agency = ?,
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
    sendResponse(false, "Prepare failed: " . $conn->error);
}

// Bind Parameters (ประเภท: sssssisssssssssssi - s = string, i = integer)
$params = [
    $plot_id,
    $contract_number,
    $quota,
    $agency,
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

$types = "sssssisssssssssssi"; // 18 ตัวอักษร

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

    // 🚨 NEW: ส่ง JSON Response กลับไปที่ AJAX แทนการ Redirect
    sendResponse(true, "บันทึกข้อมูลแปลง {$plot_id} เรียบร้อยแล้ว", $production_year);
    
} else {
    $stmt->close();
    // 🚨 NEW: ส่ง Error Response
    sendResponse(false, "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $stmt->error, $production_year);
}

$conn->close();
?>