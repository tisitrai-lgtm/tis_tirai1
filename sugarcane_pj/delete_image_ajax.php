<?php
// delete_image_ajax.php

// เปิด Error Reporting สำหรับการ Debugging (ควรถอดออกใน Production Environment)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db_connect.php'; // ตรวจสอบให้แน่ใจว่าไฟล์นี้ถูกต้องและอยู่ในพาธที่เข้าถึงได้

// ฟังก์ชันสำหรับ sanitize ชื่อโฟลเดอร์ (ต้องเหมือนกับที่ใช้ในการสร้างโฟลเดอร์ทั้งหมด)
function sanitizeFolderName($name) {
    if (empty($name)) {
        return '';
    }
    // ปรับให้รองรับภาษาไทยครบถ้วนรวมถึงสระและวรรณยุกต์ (\p{M})
    $name = preg_replace('/[^\p{L}\p{M}\p{N}_-]/u', '', str_replace(' ', '-', $name));
    return trim($name, '-');
}

// ฟังก์ชันสำหรับสร้างพาธรูปภาพเต็ม (ต้องเหมือนกับที่ใช้ทั้งหมด)
// โครงสร้างโฟลเดอร์: uploads/ปีการผลิต/เลขสัญญา/ID แปลง/ประเภทรูปภาพ/ชื่อไฟล์รูปภาพ.jpg
function getImagePath($filename, $production_year, $agency, $contract_number, $plot_id, $image_type_folder) {
    if (empty($filename)) {
        return '';
    }
    $sanitized_production_year = sanitizeFolderName($production_year);
    $sanitized_agency = sanitizeFolderName($agency);
    $sanitized_contract_number = sanitizeFolderName($contract_number);
    $sanitized_plot_id = sanitizeFolderName($plot_id);
    
    $basePath = "uploads/{$sanitized_production_year}/{$sanitized_agency}/{$sanitized_contract_number}/{$sanitized_plot_id}/";
    return $basePath . $image_type_folder . '/' . $filename;
}


header('Content-Type: application/json'); // กำหนดให้ response เป็น JSON

// ตรวจสอบว่าเป็น request แบบ POST เท่านั้น
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];

    // ดึงข้อมูลที่ส่งมาจาก AJAX
    $id = $_POST['id'] ?? null;
    $image_type = $_POST['image_type'] ?? null; // เช่น 'soil_image', 'planting_image'
    $production_year = $_POST['production_year'] ?? null;
    $agency = $_POST['agency'] ?? null;
    $contract_number = $_POST['contract_number'] ?? null;
    $plot_id = $_POST['plot_id'] ?? null;

    // รายการชื่อคอลัมน์รูปภาพที่อนุญาตให้ลบได้ (Whitelist เพื่อความปลอดภัย)
    $allowed_image_types = [
        'soil_image', 
        'soil_preparation_image', 
        'cane_variety_image', 
        'planting_image', 
        'watering_image', 
        'germination_image'
    ];

    // ตรวจสอบข้อมูลที่จำเป็น
    if (!$id || !$image_type || !in_array($image_type, $allowed_image_types) || !$production_year || !$agency || !$contract_number || !$plot_id) {
        $response['message'] = 'ข้อมูลไม่ครบถ้วนหรือไม่ถูกต้องสำหรับการลบรูปภาพ';
        echo json_encode($response);
        exit;
    }

    // ดึงชื่อไฟล์รูปภาพปัจจุบันจากฐานข้อมูล
    // เนื่องจากชื่อคอลัมน์ ($image_type) ไม่สามารถ bind โดยตรงได้ใน Prepared Statement
    // เราจะสร้าง SQL string โดยตรง แต่ปลอดภัยเพราะ $image_type ถูกตรวจสอบด้วย Whitelist แล้ว
    $sql_select = "SELECT `" . $conn->real_escape_string($image_type) . "` FROM soil_data WHERE id = ?";
    $stmt = $conn->prepare($sql_select);
    if ($stmt === false) {
        error_log("Prepare select failed: " . $conn->error);
        $response['message'] = 'ข้อผิดพลาดในการเตรียมการดึงข้อมูลรูปภาพ: ' . $conn->error;
        echo json_encode($response);
        exit;
    }
    $stmt->bind_param("i", $id); 
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();

    if (!$data || empty($data[$image_type])) {
        $response['message'] = 'ไม่พบรูปภาพที่ต้องการลบในฐานข้อมูล';
        echo json_encode($response);
        exit;
    }

    $filename_to_delete = $data[$image_type];
    $full_path_to_delete = getImagePath($filename_to_delete, $production_year, $agency, $contract_number, $plot_id, $image_type);

    // ลบไฟล์ออกจากเซิร์ฟเวอร์
    // ตรวจสอบว่าไฟล์มีอยู่จริงบนเซิร์ฟเวอร์ก่อนทำการลบ
    if (file_exists($full_path_to_delete)) {
        if (unlink($full_path_to_delete)) {
            // ลบไฟล์สำเร็จ, ดำเนินการอัปเดตฐานข้อมูล
            $response['file_deleted_from_server'] = true; // สำหรับ debugging
        } else {
            // ลบไฟล์ไม่สำเร็จ แต่จะพยายามอัปเดต DB ต่อไป (อาจเป็นเพราะสิทธิ์)
            $response['message'] = 'ไม่สามารถลบไฟล์รูปภาพออกจากเซิร์ฟเวอร์ได้ (อาจเป็นปัญหาเรื่องสิทธิ์) แต่จะอัปเดตฐานข้อมูล.';
            $response['file_deleted_from_server'] = false; // สำหรับ debugging
        }
    } else {
        $response['message'] = 'ไม่พบไฟล์รูปภาพบนเซิร์ฟเวอร์ (อาจถูกลบไปแล้ว) จะทำการอัปเดตฐานข้อมูล.';
        $response['file_deleted_from_server'] = false; // สำหรับ debugging
    }

    // อัปเดตฐานข้อมูล (ตั้งค่าคอลัมน์รูปภาพเป็น NULL)
    $update_sql = "UPDATE soil_data SET `" . $conn->real_escape_string($image_type) . "` = NULL WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    if ($update_stmt === false) {
        error_log("Prepare update failed: " . $conn->error);
        $response['message'] = 'ข้อผิดพลาดในการเตรียมการอัปเดตฐานข้อมูล: ' . $conn->error;
        echo json_encode($response);
        exit;
    }
    $update_stmt->bind_param("i", $id); // ผูกแค่ ID เพราะชื่อคอลัมน์อยู่ใน query แล้ว
    
    if ($update_stmt->execute()) {
        $response['success'] = true;
        // หากไฟล์ถูกลบจาก server และอัปเดต DB สำเร็จ
        if ($response['file_deleted_from_server']) {
            $response['message'] = 'ลบรูปภาพและอัปเดตข้อมูลสำเร็จ';
        } else {
            // หากไฟล์ไม่พบ/ลบไม่ได้บน server แต่อัปเดต DB สำเร็จ
            $response['message'] = 'ไม่พบไฟล์บนเซิร์ฟเวอร์หรือลบไม่ได้ แต่อัปเดตข้อมูลในฐานข้อมูลสำเร็จ';
        }
    } else {
        $response['message'] = 'เกิดข้อผิดพลาดในการอัปเดตฐานข้อมูล: ' . $update_stmt->error;
    }
    $update_stmt->close();

    echo json_encode($response);
    exit;

} else {
    // ไม่อนุญาตให้เข้าถึงโดยตรง (ต้องเป็น POST request เท่านั้น)
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
}

$conn->close();
?>