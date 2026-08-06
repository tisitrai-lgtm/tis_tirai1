<?php
// water_insertdata.php

// 1. การตั้งค่าเริ่มต้นและการเชื่อมต่อฐานข้อมูล
session_start(); 

// ตรวจสอบการเชื่อมต่อฐานข้อมูล (ใช้ค่าเดิมที่ผู้ใช้กำหนด)
$conn = new mysqli("localhost", "root", "", "ton_aoi"); // ตรวจสอบชื่อฐานข้อมูล
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4"); 

// ฟังก์ชันทำความสะอาดชื่อเพื่อใช้ใน Path
if (!function_exists('sanitizeForPath')) {
    function sanitizeForPath($string) {
        // ใช้ urlencode เพื่อความปลอดภัยขั้นพื้นฐาน
        return urlencode(str_replace(' ', '_', $string));
    }
}

// 2. ฟังก์ชันอัปโหลดรูปภาพ
// รับค่า Path หลัก + โฟลเดอร์ย่อย ($sub_folder)
function uploadImage($fileInputName, $production_year, $agency, $contract_number, $plot_id, $sub_folder) {
    if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
        
        // 1. ทำความสะอาดค่า
        $sanitized_year = sanitizeForPath($production_year);
        $sanitized_agency = sanitizeForPath($agency);
        $sanitized_contract = sanitizeForPath($contract_number);
        $sanitized_plot = sanitizeForPath($plot_id);

        // 2. โครงสร้าง Path ที่ต้องการ: ton_aoi/uploads/{year}/{agency}/{contract_number}/{plot_id}/sub_folder/
        $baseDir = "ton_aoi/uploads/{$sanitized_year}/{$sanitized_agency}/{$sanitized_contract}/{$sanitized_plot}/";
        $targetDir = $baseDir . $sub_folder . "/"; // เพิ่ม sub_folder

        // ตรวจสอบและสร้างโฟลเดอร์ถ้ายังไม่มี
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0755, true)) {
                error_log("Failed to create directory: " . $targetDir);
                return null;
            }
        }

        $fileName = basename($_FILES[$fileInputName]["name"]);
        $fileToDB = time() . "_" . $fileName; 
        $filePath = $targetDir . $fileToDB;

        if (move_uploaded_file($_FILES[$fileInputName]["tmp_name"], $filePath)) {
            // คืนค่าเฉพาะชื่อไฟล์เพื่อบันทึกในฐานข้อมูล
            return $fileToDB; 
        } else {
            error_log("Failed to move uploaded file: " . $filePath . " Error Code: " . $_FILES[$fileInputName]["error"]);
            return null;
        }
    }
    return null;
}

// 3. การประมวลผลข้อมูล POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับค่าจากฟอร์มทั้งหมด
    $production_year = $_POST['production_year'] ?? ''; // ปีการผลิต
    $plot_id = $_POST['plot_id'] ?? '';
    $contract_number = $_POST['contract_number'] ?? '';
    $quota = $_POST['quota'] ?? '';
    $emp_number = $_POST['emp_number'] ?? '';
    $agency = $_POST['agency'] ?? ''; 
    $suga_type = $_POST['suga_type'] ?? '';
    $rai_area = $_POST['rai_area'] ?? null; 
    $notes = $_POST['notes'] ?? '';

    // -----------------------------------------------------------------
    // **ตรวจสอบ ID แปลงซ้ำ (Duplicate Plot ID Check)**
    // -----------------------------------------------------------------
    $sql_check = "SELECT COUNT(*) AS count FROM cane_plot_data WHERE plot_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    
    if ($stmt_check === false) {
        die("เกิดข้อผิดพลาดในการเตรียม Statement ตรวจสอบ ID ซ้ำ: " . $conn->error);
    }
    
    $stmt_check->bind_param("s", $plot_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $row_check = $result_check->fetch_assoc();
    $stmt_check->close();

    if ($row_check['count'] > 0) {
        // หากพบ ID ซ้ำ ให้แสดงข้อความแจ้งเตือน 
        $plot_id_html = htmlspecialchars($plot_id);

        echo '<!DOCTYPE html>';
        echo '<html lang="th">';
        echo '<head>';
        echo '    <meta charset="UTF-8">';
        echo '    <title>บันทึกไม่สำเร็จ</title>';
        echo '    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">';
        echo '    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"> ';
        echo '    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;600&display=swap" rel="stylesheet">';
        echo '    <style>';
        echo '        body { font-family: \'Kanit\', sans-serif; background-color: #f8f9fa; }';
        echo '        .center-box {';
        echo '            max-width: 500px;';
        echo '            padding: 30px;';
        echo '            border-radius: 10px;';
        echo '            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);';
        echo '            margin: 50px auto;';
        echo '            background-color: white;';
        echo '        }';
        echo '    </style>';
        echo '</head>';
        echo '<body class="d-flex justify-content-center align-items-center vh-100">';
        echo '    <div class="center-box text-center">';
        echo '        <div class="alert alert-danger" role="alert">';
        echo '            <h3 class="alert-heading fw-bold"><i class="bi bi-x-octagon-fill me-2"></i> บันทึกไม่สำเร็จ!</h3>';
        echo '            <hr>';
        echo '            <p class="mb-0"><strong>ID แปลง (<span class="text-decoration-underline">' . $plot_id_html . '</span>) นี้มีอยู่แล้วในระบบ</strong></p>';
        echo '            <p class="mt-2">กรุณาตรวจสอบ ID แปลงที่กรอก หรือกลับไปแก้ไขข้อมูลเดิม.</p>';
        echo '        </div>';
        echo '        <button onclick="window.history.back()" class="btn btn-danger btn-lg mt-3 fw-bold">';
        echo '            <i class="bi bi-arrow-left-circle-fill me-2"></i> ย้อนกลับเพื่อแก้ไข';
        echo '        </button>';
        echo '    </div>';
        echo '</body>';
        echo '</html>';
        exit; 
    }
    // -----------------------------------------------------------------
    // **สิ้นสุดการตรวจสอบ ID แปลงซ้ำ**
    // -----------------------------------------------------------------

    // อัปโหลดรูปภาพ 10 รูป 
    $estimate_ton_1 = uploadImage("estimate_ton_1", $production_year, $agency, $contract_number, $plot_id, "estimate_ton");
    $estimate_ton_2 = uploadImage("estimate_ton_2", $production_year, $agency, $contract_number, $plot_id, "estimate_ton");
    
    $evaluate_ton_1 = uploadImage("evaluate_ton_1", $production_year, $agency, $contract_number, $plot_id, "evaluate_ton");
    $evaluate_ton_2 = uploadImage("evaluate_ton_2", $production_year, $agency, $contract_number, $plot_id, "evaluate_ton");
    
    $remaining_cane_1_img_1 = uploadImage("remaining_cane_1_img_1", $production_year, $agency, $contract_number, $plot_id, "remaining_cane_1");
    $remaining_cane_1_img_2 = uploadImage("remaining_cane_1_img_2", $production_year, $agency, $contract_number, $plot_id, "remaining_cane_1");
    
    $remaining_cane_2_img_1 = uploadImage("remaining_cane_2_img_1", $production_year, $agency, $contract_number, $plot_id, "remaining_cane_2");
    $remaining_cane_2_img_2 = uploadImage("remaining_cane_2_img_2", $production_year, $agency, $contract_number, $plot_id, "remaining_cane_2");
    
    $remaining_cane_3_img_1 = uploadImage("remaining_cane_3_img_1", $production_year, $agency, $contract_number, $plot_id, "remaining_cane_3");
    $remaining_cane_3_img_2 = uploadImage("remaining_cane_3_img_2", $production_year, $agency, $contract_number, $plot_id, "remaining_cane_3");

    // 4. การเตรียมคำสั่ง SQL
    $sql = "INSERT INTO cane_plot_data (
        plot_id, production_year, contract_number, emp_number, quota, agency, suga_type, rai_area,
        estimate_ton_1, estimate_ton_2, evaluate_ton_1, evaluate_ton_2,
        remaining_cane_1_img_1, remaining_cane_1_img_2, remaining_cane_2_img_1, remaining_cane_2_img_2,
        remaining_cane_3_img_1, remaining_cane_3_img_2, notes
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"; 

    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        // บันทึกไม่สำเร็จ: SQL Statement Error
        $error_message = htmlspecialchars("เกิดข้อผิดพลาดในการเตรียม Statement: " . $conn->error);
        
        echo '<!DOCTYPE html>';
        echo '<html lang="th">';
        echo '<head>';
        echo '    <meta charset="UTF-8">';
        echo '    <title>ข้อผิดพลาด SQL</title>';
        echo '    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">';
        echo '    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"> ';
        echo '    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;600&display=swap" rel="stylesheet">';
        echo '    <style>';
        echo '        body { font-family: \'Kanit\', sans-serif; background-color: #f8f9fa; }';
        echo '        .center-box {';
        echo '            max-width: 600px;';
        echo '            padding: 30px;';
        echo '            border-radius: 10px;';
        echo '            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);';
        echo '            margin: 50px auto;';
        echo '            background-color: white;';
        echo '        }';
        echo '    </style>';
        echo '</head>';
        echo '<body class="d-flex justify-content-center align-items-center vh-100">';
        echo '    <div class="center-box text-center">';
        echo '        <div class="alert alert-danger" role="alert">';
        echo '            <h3 class="alert-heading fw-bold"><i class="bi bi-bug-fill me-2"></i> ข้อผิดพลาด SQL Statement!</h3>';
        echo '            <hr>';
        echo '            <p class="text-start">โปรดดูข้อความด้านล่างนี้เพื่อระบุปัญหา:</p>';
        echo '            <pre class="bg-light p-3 border rounded text-danger text-start small"><strong>ข้อความผิดพลาด:</strong> ' . $error_message . '</pre>';
        echo '        </div>';
        echo '        <button onclick="window.history.back()" class="btn btn-secondary btn-lg mt-3 fw-bold">';
        echo '            <i class="bi bi-arrow-left-circle-fill me-2"></i> ย้อนกลับ';
        echo '        </button>';
        echo '    </div>';
        echo '</body>';
        echo '</html>';
        exit;
    }
    
    // 5. การผูกตัวแปร
    // s: string, d: double/float (สำหรับ rai_area)
    $stmt->bind_param("sssssssdsssssssssss",
        $plot_id, $production_year, $contract_number, $emp_number, $quota, $agency, $suga_type, $rai_area,
        $estimate_ton_1, $estimate_ton_2, 
        $evaluate_ton_1, $evaluate_ton_2,
        $remaining_cane_1_img_1, $remaining_cane_1_img_2, 
        $remaining_cane_2_img_1, $remaining_cane_2_img_2, 
        $remaining_cane_3_img_1, $remaining_cane_3_img_2, 
        $notes
    );

   // 6. การรันคำสั่งและแสดงผล
    if ($stmt->execute()) {
        
        // บันทึกสำเร็จ
        $encoded_year = urlencode($production_year); 
        $plot_id_html = htmlspecialchars($plot_id);
        $redirect_url = 'dashboard.php?year=' . $encoded_year . '&success=insert'; // URL ที่จะ Redirect ไป

        echo '<!DOCTYPE html>';
        echo '<html lang="th">';
        echo '<head>';
        echo '    <meta charset="UTF-8">';
        echo '    <title>บันทึกข้อมูลสำเร็จ</title>';
        // 🚨 แก้ไข: ใช้ Meta Refresh Redirection เพื่อให้มั่นใจว่าจะถูก Redirect ใน 2 วินาที
        echo '    <meta http-equiv="refresh" content="2; url=' . $redirect_url . '">';
        echo '    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">';
        echo '    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"> ';
        echo '    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;600;700&display=swap" rel="stylesheet">';
        echo '    <style>';
        echo '        body { font-family: \'Kanit\', sans-serif; background-color: #f0f4f8; }';
        echo '        .success-box {';
        echo '            max-width: 450px;';
        echo '            padding: 30px;';
        echo '            border-radius: 10px;';
        echo '            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);';
        echo '            background-color: white;';
        echo '            border-left: 5px solid #198754; /* Green border for success */';
        echo '        }';
        echo '    </style>';
        echo '</head>';
        echo '<body class="d-flex justify-content-center align-items-center vh-100">';
        echo '    <div class="success-box text-center">';
        echo '        <div class="text-success mb-3">';
        echo '            <i class="bi bi-check-circle-fill" style="font-size: 3rem;"></i>';
        echo '        </div>';
        echo '        <h2 class="fw-bold text-success">บันทึกข้อมูลเรียบร้อยแล้ว</h2>';
        echo '        <p class="mb-4 text-secondary">';
        echo '            ข้อมูลแปลง <strong>' . $plot_id_html . '</strong> ถูกบันทึกเข้าสู่ระบบแล้ว';
        echo '        </p>';
        echo '        <div class="spinner-border spinner-border-sm text-success me-2" role="status">';
        echo '            <span class="visually-hidden">Loading...</span>';
        echo '        </div>';
        echo '        <small class="text-muted">กำลังกลับไปหน้า Dashboard...</small>';
        echo '    </div>';
        // **ลบส่วน JavaScript Redirect ออก**
        echo '</body>';
        echo '</html>';

    } else {
        // บันทึกไม่สำเร็จ: แสดงข้อผิดพลาดที่แท้จริง
        $sql_error_message = htmlspecialchars($stmt->error);
        $sql_error_code = htmlspecialchars($stmt->errno);
        
        echo '<!DOCTYPE html>';
        echo '<html lang="th">';
        echo '<head>';
        echo '    <meta charset="UTF-8">';
        echo '    <title>บันทึกไม่สำเร็จ</title>';
        echo '    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">';
        echo '    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"> ';
        echo '    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;600&display=swap" rel="stylesheet">';
        echo '    <style>';
        echo '        body { font-family: \'Kanit\', sans-serif; background-color: #f8f9fa; }';
        echo '        .center-box {';
        echo '            max-width: 600px;';
        echo '            padding: 30px;';
        echo '            border-radius: 10px;';
        echo '            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);';
        echo '            margin: 50px auto;';
        echo '            background-color: white;';
        echo '        }';
        echo '    </style>';
        echo '</head>';
        echo '<body class="d-flex justify-content-center align-items-center vh-100">';
        echo '    <div class="center-box text-center">';
        echo '        <div class="alert alert-danger" role="alert">';
        echo '            <h3 class="alert-heading fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i> บันทึกไม่สำเร็จ!</h3>';
        echo '            <hr>';
        echo '            <p class="text-start">เกิดข้อผิดพลาดในการบันทึกข้อมูลลงฐานข้อมูล:</p>';
        echo '            <p class="text-start">';
        echo '                <strong>MySQL Error Code:</strong> <span class="badge bg-danger">' . $sql_error_code . '</span>';
        echo '            </p>';
        echo '            <pre class="bg-light p-3 border rounded text-danger text-start small mt-2"><strong>SQL Error:</strong> ' . $sql_error_message . '</pre>';
        echo '        </div>';
        echo '        <button onclick="window.history.back()" class="btn btn-secondary btn-lg mt-3 fw-bold">';
        echo '            <i class="bi bi-arrow-left-circle-fill me-2"></i> ย้อนกลับ';
        echo '        </button>';
        echo '    </div>';
        echo '</body>';
        echo '</html>';
    }

    $stmt->close();
} else {
    echo "ไม่พบข้อมูลที่ส่งมา.";
}

if (isset($conn) && $conn) {
    $conn->close();
} 

// ไม่มีแท็กปิด ?>