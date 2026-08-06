<?php
// delete_image_ajax.php - สคริปต์สำหรับจัดการการลบรูปภาพผ่าน AJAX
require_once 'db_connect.php'; // ไฟล์เชื่อมต่อฐานข้อมูล

// -----------------------------------------------------------------
// 1. ฟังก์ชัน Sanitize (ต้องเหมือนกับที่ใช้ใน update_data.php)
// -----------------------------------------------------------------
function sanitizeFolderName($name) {
    if (empty($name)) return '';
    $name = trim($name);
    $name = str_replace(' ', '-', $name);
    // รองรับภาษาไทยและตัวอักษร Unicode
    $name = preg_replace('/[^\p{L}\p{N}_-]/u', '', $name); 
    $name = preg_replace('/-+/', '-', $name);
    return trim($name, '-');
}

// -----------------------------------------------------------------
// 2. ฟังก์ชันส่ง Response กลับไปที่ AJAX
// -----------------------------------------------------------------
function sendResponse($success, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

// -----------------------------------------------------------------
// 3. เริ่มต้นการทำงาน
// -----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, "Invalid request method.");
}

// รับค่าจาก AJAX
$id = $_POST['id'] ?? null;
$image_type = $_POST['image_type'] ?? null; // ชื่อคอลัมน์ใน DB (e.g., estimate_ton_1)
$production_year = $_POST['production_year'] ?? null;
$contract_number = $_POST['contract_number'] ?? null;
$plot_id = $_POST['plot_id'] ?? null;
$agency = $_POST['agency'] ?? null;

if (empty($id) || empty($image_type) || empty($production_year) || empty($plot_id)) {
    sendResponse(false, "Missing required parameters for deletion.");
}

// ตรวจสอบความถูกต้องของชื่อคอลัมน์ (เพื่อป้องกัน SQL Injection)
$allowed_image_fields = [
    "estimate_ton_1", "estimate_ton_2", "evaluate_ton_1", "evaluate_ton_2",
    "remaining_cane_1_img_1", "remaining_cane_1_img_2", "remaining_cane_2_img_1", 
    "remaining_cane_2_img_2", "remaining_cane_3_img_1", "remaining_cane_3_img_2"
];

if (!in_array($image_type, $allowed_image_fields)) {
    sendResponse(false, "Invalid image field name.");
}

$db_column = $image_type;

try {
    // ---------------------------------------------------------
    // A. ดึงชื่อไฟล์ปัจจุบันและข้อมูล Path จากฐานข้อมูล
    // ---------------------------------------------------------
    $sql_fetch = "SELECT {$db_column}, contract_number, agency, production_year, plot_id 
                  FROM cane_plot_data WHERE id = ?";
    $stmt_fetch = $conn->prepare($sql_fetch);
    $stmt_fetch->bind_param("i", $id);
    $stmt_fetch->execute();
    $result = $stmt_fetch->get_result();
    $row = $result->fetch_assoc();
    $stmt_fetch->close();

    if (!$row) {
        sendResponse(false, "Record not found in database.");
    }

    $filename_to_delete = $row[$db_column];

    if (empty($filename_to_delete)) {
        // ไฟล์ใน DB เป็น NULL อยู่แล้ว ไม่ต้องดำเนินการต่อ
        sendResponse(true, "Image field is already empty. No file deleted.");
    }
    
    // ---------------------------------------------------------
    // B. ลบชื่อไฟล์ออกจากฐานข้อมูล
    // ---------------------------------------------------------
    $sql_update = "UPDATE cane_plot_data SET {$db_column} = NULL WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("i", $id);

    if ($stmt_update->execute()) {
        $stmt_update->close();
        
        // -----------------------------------------------------
        // C. ลบไฟล์จริงออกจากเซิร์ฟเวอร์
        // -----------------------------------------------------
        
        // กำหนด Subfolder (ตามชื่อคอลัมน์)
        $subfolder_map = [
            "estimate_ton_1" => "estimate_ton", "estimate_ton_2" => "estimate_ton", 
            "evaluate_ton_1" => "evaluate_ton", "evaluate_ton_2" => "evaluate_ton", 
            "remaining_cane_1_img_1" => "remaining_cane_1", "remaining_cane_1_img_2" => "remaining_cane_1", 
            "remaining_cane_2_img_1" => "remaining_cane_2", "remaining_cane_2_img_2" => "remaining_cane_2", 
            "remaining_cane_3_img_1" => "remaining_cane_3", "remaining_cane_3_img_2" => "remaining_cane_3", 
        ];
        $image_type_folder = $subfolder_map[$db_column] ?? null;

        // สร้าง Path ของไฟล์
        $image_base_url = "ton_aoi/"; 
        $sanitized_year = sanitizeFolderName($row['production_year']);
        $sanitized_agency = sanitizeFolderName($row['agency']);
        $sanitized_contract = sanitizeFolderName($row['contract_number']);
        $sanitized_plot = sanitizeFolderName($row['plot_id']);
        
        $basePath = "{$image_base_url}uploads/{$sanitized_year}/{$sanitized_agency}/{$sanitized_contract}/{$sanitized_plot}/";
        $filePath = $basePath . $image_type_folder . '/' . $filename_to_delete;

        if (file_exists($filePath) && is_file($filePath)) {
            if (unlink($filePath)) {
                sendResponse(true, "ลบรูปภาพและข้อมูลในฐานข้อมูลสำเร็จแล้ว");
            } else {
                // ข้อมูลใน DB ถูกลบแล้ว แต่ลบไฟล์ไม่สำเร็จ
                sendResponse(false, "ลบข้อมูลในฐานข้อมูลสำเร็จ แต่ลบไฟล์รูปภาพบนเซิร์ฟเวอร์ไม่สำเร็จ");
            }
        } else {
            // ข้อมูลใน DB ถูกลบแล้ว แต่ไม่พบไฟล์บนเซิร์ฟเวอร์ (ถือว่าสำเร็จ)
            sendResponse(true, "ลบข้อมูลในฐานข้อมูลสำเร็จ แต่ไม่พบไฟล์บนเซิร์ฟเวอร์");
        }

    } else {
        $stmt_update->close();
        sendResponse(false, "Failed to update database: " . $stmt_update->error);
    }

} catch (Exception $e) {
    sendResponse(false, "An error occurred: " . $e->getMessage());
}

$conn->close();
?>