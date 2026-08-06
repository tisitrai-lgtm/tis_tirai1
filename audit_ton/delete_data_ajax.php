<?php
// delete_data_ajax.php - สคริปต์สำหรับจัดการการลบข้อมูลแปลงทั้งหมดผ่าน AJAX
require_once 'db_connect.php'; // ไฟล์เชื่อมต่อฐานข้อมูล

// -----------------------------------------------------------------
// 1. ฟังก์ชัน Sanitize (ต้องเหมือนกับที่ใช้ใน update_data.php และ edit_data.php)
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
// 2. ฟังก์ชันสำหรับลบโฟลเดอร์และไฟล์ย่อยทั้งหมด (Recursive Delete)
// -----------------------------------------------------------------
function deleteDirectory($dir) {
    if (!is_dir($dir)) {
        return true; // ถ้าไม่มีโฟลเดอร์อยู่แล้ว ถือว่าลบสำเร็จ
    }
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        (is_dir("$dir/$file")) ? deleteDirectory("$dir/$file") : unlink("$dir/$file");
    }
    return rmdir($dir);
}

// -----------------------------------------------------------------
// 3. ฟังก์ชันส่ง Response กลับไปที่ AJAX
// -----------------------------------------------------------------
function sendResponse($success, $message) {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// -----------------------------------------------------------------
// 4. เริ่มต้นการทำงาน
// -----------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, "Invalid request method.");
}

// รับค่าจาก AJAX (เราใช้ ID เป็น Primary Key ในการลบ)
$id = $_POST['id'] ?? null;

if (empty($id)) {
    sendResponse(false, "Missing record ID.");
}

try {
    // ---------------------------------------------------------
    // A. ดึงข้อมูลที่จำเป็นสำหรับสร้าง Path รูปภาพ ก่อนลบแถว
    // ---------------------------------------------------------
    $sql_fetch = "SELECT production_year, agency, contract_number, plot_id 
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

    // ---------------------------------------------------------
    // B. ลบข้อมูลแถวออกจากฐานข้อมูล
    // ---------------------------------------------------------
    $sql_delete = "DELETE FROM cane_plot_data WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $id);

    if ($stmt_delete->execute()) {
        $stmt_delete->close();
        
        // -----------------------------------------------------
        // C. ลบโฟลเดอร์รูปภาพหลักที่เกี่ยวข้อง
        // -----------------------------------------------------
        
        $image_base_url = "ton_aoi/"; 
        $sanitized_year = sanitizeFolderName($row['production_year']);
        $sanitized_agency = sanitizeFolderName($row['agency']);
        $sanitized_contract = sanitizeFolderName($row['contract_number']);
        $sanitized_plot = sanitizeFolderName($row['plot_id']);
        
        // Path ของโฟลเดอร์หลักที่ต้องการลบ
        $baseDeletePath = "{$image_base_url}uploads/{$sanitized_year}/{$sanitized_agency}/{$sanitized_contract}/{$sanitized_plot}";
        
        if (is_dir($baseDeletePath)) {
            if (deleteDirectory($baseDeletePath)) {
                sendResponse(true, "ลบข้อมูลและโฟลเดอร์รูปภาพสำเร็จ");
            } else {
                // ข้อมูลใน DB ถูกลบแล้ว แต่ลบโฟลเดอร์ไม่สำเร็จ
                sendResponse(false, "ลบข้อมูลในฐานข้อมูลสำเร็จ แต่ลบโฟลเดอร์รูปภาพบนเซิร์ฟเวอร์ไม่สำเร็จ (กรุณาลบด้วยตนเอง: {$baseDeletePath})");
            }
        } else {
            // ไม่พบโฟลเดอร์รูปภาพ (ถือว่าสำเร็จ)
            sendResponse(true, "ลบข้อมูลในฐานข้อมูลสำเร็จ");
        }

    } else {
        $stmt_delete->close();
        sendResponse(false, "Failed to delete record: " . $stmt_delete->error);
    }

} catch (Exception $e) {
    sendResponse(false, "An error occurred: " . $e->getMessage());
}

$conn->close();
?>