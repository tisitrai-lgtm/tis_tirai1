<?php
/**
 * insertData.php - สคริปต์สำหรับจัดการการเพิ่มข้อมูลแปลงอ้อย (Premium Modern UI)
 */
session_start(); 
require_once 'db_connect.php'; 

// ฟังก์ชัน Sanitize ชื่อโฟลเดอร์ (รองรับภาษาไทย)
if (!function_exists('sanitizeFolderName')) {
    function sanitizeFolderName($name) {
        if (empty($name)) return '';
        $name = trim($name);
        $name = str_replace(' ', '-', $name);
        $name = preg_replace('/[^\p{L}\p{N}_-]/u', '', $name); 
        $name = preg_replace('/-+/', '-', $name);
        return trim($name, '-');
    }
}

// ฟังก์ชันอัปโหลดรูปภาพ
function uploadImage($fileInputName, $production_year, $agency, $contract_number, $plot_id, $sub_folder) {
    if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
        $sanitized_year = sanitizeFolderName($production_year);
        $sanitized_agency = sanitizeFolderName($agency);
        $sanitized_contract = sanitizeFolderName($contract_number);
        $sanitized_plot = sanitizeFolderName($plot_id);

        $baseDir = "ton_aoi/uploads/{$sanitized_year}/{$sanitized_agency}/{$sanitized_contract}/{$sanitized_plot}/";
        $targetDir = $baseDir . $sub_folder . "/";

        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0777, true)) {
                error_log("Failed to create directory: " . $targetDir);
                return null;
            }
        }

        $fileName = basename($_FILES[$fileInputName]["name"]);
        $fileToDB = time() . "_" . $fileName; 
        $filePath = $targetDir . $fileToDB;

        if (move_uploaded_file($_FILES[$fileInputName]["tmp_name"], $filePath)) {
            return $fileToDB; 
        }
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $production_year = $_POST['production_year'] ?? '';
    $plot_id = $_POST['plot_id'] ?? '';
    $contract_number = $_POST['contract_number'] ?? '';
    $quota = $_POST['quota'] ?? '';
    $emp_number = $_POST['emp_number'] ?? '';
    $agency = $_POST['agency'] ?? ''; 
    $suga_type = $_POST['suga_type'] ?? '';
    $rai_area = $_POST['rai_area'] ?? 0; 
    $notes = $_POST['notes'] ?? '';
    $ton_rai = $_POST['ton_rai'] ?? 0;
    
    // ตรวจสอบ ID แปลงซ้ำ
    $sql_check = "SELECT COUNT(*) AS count FROM cane_plot_data WHERE plot_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $plot_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    $row_check = $result_check->fetch_assoc();
    $stmt_check->close();

    if ($row_check['count'] > 0) {
        renderFeedbackPage('error', 'บันทึกไม่สำเร็จ', "ID แปลง <strong>".htmlspecialchars($plot_id)."</strong> นี้มีอยู่แล้วในระบบ");
        exit;
    }

    // อัปโหลดรูปภาพ
    $images = [
        "estimate_ton_1" => "estimate_ton", "estimate_ton_2" => "estimate_ton",
        "evaluate_ton_1" => "evaluate_ton", "evaluate_ton_2" => "evaluate_ton",
        "remaining_cane_1_img_1" => "remaining_cane_1", "remaining_cane_1_img_2" => "remaining_cane_1",
        "remaining_cane_2_img_1" => "remaining_cane_2", "remaining_cane_2_img_2" => "remaining_cane_2",
        "remaining_cane_3_img_1" => "remaining_cane_3", "remaining_cane_3_img_2" => "remaining_cane_3"
    ];

    $uploaded = [];
    foreach ($images as $field => $folder) {
        $uploaded[$field] = uploadImage($field, $production_year, $agency, $contract_number, $plot_id, $folder);
    }

    $sql = "INSERT INTO cane_plot_data (
        plot_id, production_year, contract_number, emp_number, quota, agency, suga_type, rai_area,
        estimate_ton_1, estimate_ton_2, evaluate_ton_1, evaluate_ton_2,
        remaining_cane_1_img_1, remaining_cane_1_img_2, remaining_cane_2_img_1, remaining_cane_2_img_2,
        remaining_cane_3_img_1, remaining_cane_3_img_2, notes, ton_rai
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"; 

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("sssssssdsssssssssssd",
            $plot_id, $production_year, $contract_number, $emp_number, $quota, $agency, $suga_type, $rai_area,
            $uploaded['estimate_ton_1'], $uploaded['estimate_ton_2'], 
            $uploaded['evaluate_ton_1'], $uploaded['evaluate_ton_2'],
            $uploaded['remaining_cane_1_img_1'], $uploaded['remaining_cane_1_img_2'], 
            $uploaded['remaining_cane_2_img_1'], $uploaded['remaining_cane_2_img_2'], 
            $uploaded['remaining_cane_3_img_1'], $uploaded['remaining_cane_3_img_2'], 
            $notes, $ton_rai
        );

        if ($stmt->execute()) {
            $redirect = 'dashboard.php?year=' . urlencode($production_year) . '&success=insert';
            renderFeedbackPage('success', 'บันทึกเรียบร้อย!', "ข้อมูลแปลง <strong>".htmlspecialchars($plot_id)."</strong> ถูกบันทึกแล้ว", $redirect);
        } else {
            renderFeedbackPage('error', 'บันทึกไม่สำเร็จ', "เกิดข้อผิดพลาด: " . htmlspecialchars($stmt->error));
        }
        $stmt->close();
    } else {
        renderFeedbackPage('error', 'ข้อผิดพลาดระบบ', "ไม่สามารถเตรียมคำสั่ง SQL ได้");
    }
}

function renderFeedbackPage($type, $title, $message, $redirect = null) {
    $icon = ($type === 'success') ? "bx-check-circle" : "bx-error-circle";
    $color = ($type === 'success') ? "#198754" : "#dc3545";
    $btn_class = ($type === 'success') ? "btn-success" : "btn-danger";
    ?>
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <?php if ($redirect): ?> <meta http-equiv="refresh" content="2; url=<?php echo $redirect; ?>"> <?php endif; ?>
        <title><?php echo $title; ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
        <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
        <style>
            body {
                background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
                font-family: 'Kanit', sans-serif;
                height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0;
            }
            .glass-card {
                background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px);
                border-radius: 24px; padding: 3rem; box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
                text-align: center; max-width: 500px; width: 90%; border: 1px solid rgba(255, 255, 255, 0.3);
            }
            .icon-box { font-size: 5rem; color: <?php echo $color; ?>; margin-bottom: 1.5rem; }
            <?php if ($type === 'success'): ?>
            @keyframes bounce { from { transform: translateY(0); } to { transform: translateY(-10px); } }
            .icon-box { animation: bounce 1s infinite alternate; }
            <?php endif; ?>
        </style>
    </head>
    <body>
        <div class="glass-card">
            <div class="icon-box"><i class='bx <?php echo $icon; ?>'></i></div>
            <h2 class="fw-bold mb-3" style="color: <?php echo $color; ?>;"><?php echo $title; ?></h2>
            <p class="text-muted mb-4"><?php echo $message; ?></p>
            <?php if ($redirect): ?>
                <div class="spinner-border spinner-border-sm text-success me-2" role="status"></div>
                <small class="text-muted">กำลังกลับไปหน้า Dashboard...</small>
            <?php else: ?>
                <button onclick="window.history.back()" class="btn <?php echo $btn_class; ?> px-5 py-3 rounded-pill fw-bold">
                    <i class='bx bx-arrow-back me-2'></i> ย้อนกลับ
                </button>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
}
if (isset($conn)) $conn->close();
?>