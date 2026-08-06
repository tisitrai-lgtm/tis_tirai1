<?php
// delete_data_ajax.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db_connect.php'; // ตรวจสอบพาธ

function sanitizeFolderName($name) {
    if (empty($name)) {
        return '';
    }
    // ปรับให้รองรับภาษาไทยครบถ้วนรวมถึงสระและวรรณยุกต์ (\p{M})
    $name = preg_replace('/[^\p{L}\p{M}\p{N}_-]/u', '', str_replace(' ', '-', $name));
    return trim($name, '-');
}

function getBaseImagePath($production_year, $agency, $contract_number, $plot_id) {
    $sanitized_production_year = sanitizeFolderName($production_year);
    $sanitized_agency = sanitizeFolderName($agency);
    $sanitized_contract_number = sanitizeFolderName($contract_number);
    $sanitized_plot_id = sanitizeFolderName($plot_id);

    return "uploads/{$sanitized_production_year}/{$sanitized_agency}/{$sanitized_contract_number}/{$sanitized_plot_id}/";
}

header('Content-Type: application/json'); // กำหนดให้ response เป็น JSON

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];

    // รับ ID ที่ส่งมาผ่าน POST
    $id_to_delete = $_POST['id'] ?? null; 
    // หากต้องการใช้ plot_id แทน: $plot_id_to_delete = $_POST['plot_id'] ?? null; 

    if (!$id_to_delete) { // หรือ !$plot_id_to_delete
        $response['message'] = 'ไม่พบ ID ที่ต้องการลบ.';
        echo json_encode($response);
        exit;
    }

    // ดึงข้อมูลเพื่อลบไฟล์
    $sql_select_images = "SELECT production_year, agency, contract_number, plot_id, soil_image, soil_preparation_image, cane_variety_image, planting_image, watering_image, germination_image FROM soil_data WHERE id = ?";
    $stmt_select = $conn->prepare($sql_select_images);
    if ($stmt_select === false) {
        error_log("Prepare select images failed: " . $conn->error);
        $response['message'] = 'ข้อผิดพลาดในการเตรียมการดึงข้อมูลรูปภาพเพื่อลบไฟล์: ' . $conn->error;
        echo json_encode($response);
        exit;
    }
    $stmt_select->bind_param("i", $id_to_delete); // หรือ "s" สำหรับ plot_id
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();
    $row_data = $result_select->fetch_assoc();
    $stmt_select->close();

    if (!$row_data) {
        $response['message'] = 'ไม่พบข้อมูลสำหรับ ID นี้.';
        echo json_encode($response);
        exit;
    }

    // ลบไฟล์และโฟลเดอร์
    $base_folder_path = getBaseImagePath($row_data['production_year'], $row_data['agency'], $row_data['contract_number'], $row_data['plot_id']);
    $image_columns = [
        'soil_image', 'soil_preparation_image', 'cane_variety_image', 
        'planting_image', 'watering_image', 'germination_image'
    ];

    foreach ($image_columns as $col) {
        if (!empty($row_data[$col])) {
            $image_path = $base_folder_path . $col . '/' . $row_data[$col];
            if (file_exists($image_path)) {
                if (!unlink($image_path)) {
                    error_log("Failed to delete image: " . $image_path);
                }
            }
        }
    }

    foreach ($image_columns as $col) {
        $type_folder_path = $base_folder_path . $col;
        if (is_dir($type_folder_path) && count(scandir($type_folder_path)) == 2) {
            if (!rmdir($type_folder_path)) {
                error_log("Failed to delete empty image type folder: " . $type_folder_path);
            }
        }
    }

    $plot_id_folder = rtrim($base_folder_path, '/');
    if (is_dir($plot_id_folder) && count(scandir($plot_id_folder)) == 2) {
        if (!rmdir($plot_id_folder)) {
            error_log("Failed to delete empty plot_id folder: " . $plot_id_folder);
        }
    }

    $contract_number_folder = dirname($plot_id_folder);
    if (is_dir($contract_number_folder) && count(scandir($contract_number_folder)) == 2) {
        if (!rmdir($contract_number_folder)) {
            error_log("Failed to delete empty contract_number folder: " . $contract_number_folder);
        }
    }

    $production_year_folder = dirname($contract_number_folder);
    if (is_dir($production_year_folder) && count(scandir($production_year_folder)) == 2) {
        if (!rmdir($production_year_folder)) {
            error_log("Failed to delete empty production_year folder: " . $production_year_folder);
        }
    }


    // ลบข้อมูลจากฐานข้อมูล
    $sql_delete = "DELETE FROM soil_data WHERE id = ?"; // หรือ WHERE plot_id = ?
    $stmt_delete = $conn->prepare($sql_delete);
    if ($stmt_delete === false) {
        error_log("Prepare delete failed: " . $conn->error);
        $response['message'] = 'ข้อผิดพลาดในการเตรียมการลบข้อมูล: ' . $conn->error;
        echo json_encode($response);
        exit;
    }
    $stmt_delete->bind_param("i", $id_to_delete); // หรือ "s" สำหรับ plot_id

    if ($stmt_delete->execute()) {
        if ($stmt_delete->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = 'ลบข้อมูลสำเร็จ';
        } else {
            $response['message'] = 'ไม่พบข้อมูลที่ต้องการลบ (อาจถูกลบไปแล้ว)';
        }
    } else {
        $response['message'] = 'เกิดข้อผิดพลาดในการลบข้อมูล: ' . $stmt_delete->error;
    }
    $stmt_delete->close();

    echo json_encode($response);
    exit;

} else {
    // หากไม่ใช่ POST request
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$conn->close();
?>