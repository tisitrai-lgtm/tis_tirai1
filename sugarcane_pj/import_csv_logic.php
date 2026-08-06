<?php
session_start();
require_once 'db_connect.php';

// Check if file was uploaded
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['csv_file'])) {
    header("Location: import_csv.php");
    exit;
}

$file = $_FILES['csv_file'];

// Basic validation
if ($file['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['import_status'] = 'error';
    $_SESSION['import_message'] = 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์';
    header("Location: import_csv.php");
    exit;
}

// Check file extension
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
if (strtolower($ext) !== 'csv') {
    $_SESSION['import_status'] = 'error';
    $_SESSION['import_message'] = 'กรุณาอัปโหลดเฉพาะไฟล์นามสกุล .csv';
    header("Location: import_csv.php");
    exit;
}

// Open the file
if (($handle = fopen($file['tmp_name'], "r")) !== FALSE) {
    // Skip UTF-8 BOM if present
    $bom = fread($handle, 3);
    if ($bom !== "\xEF\xBB\xBF") {
        rewind($handle);
    }

    // Read the headers
    $headers = fgetcsv($handle, 1000, ",");
    if (!$headers) {
        fclose($handle);
        $_SESSION['import_status'] = 'error';
        $_SESSION['import_message'] = 'ไม่สามารถอ่านหัวตารางในไฟล์ CSV ได้';
        header("Location: import_csv.php");
        exit;
    }

    // Map Headers (Thai -> Database Columns)
    $header_map = [
        'ปีผลิต' => 'production_year',
        'หน่วยงาน' => 'agency',
        'เลขสัญญา' => 'contract_number',
        'โควต้า' => 'quota',
        'ID แปลง' => 'plot_id',
        'ไร่' => 'rai_area',
        'ชนิดดิน' => 'soil_type',
        'รูปดิน' => 'soil_image',
        'เตรียมดิน' => 'soil_preparation_details',
        'รูปเตรียมดิน' => 'soil_preparation_image',
        'พันธุ์อ้อย' => 'cane_variety',
        'รูปพันธุ์อ้อย' => 'cane_variety_image',
        'การปลูก' => 'planting_details',
        'รูปปลูก' => 'planting_image',
        'การให้น้ำ' => 'watering_details',
        'รูปให้น้ำ' => 'watering_image',
        'เปอร์เซ็นต์' => 'germination_percentage',
        'รูปเปอร์เซ็นต์' => 'germination_image',
        'หมายเหตุ' => 'notes',
        'วันที่บันทึก' => 'created_at'
    ];

    $col_index = [];
    foreach ($headers as $idx => $h) {
        $h = trim($h);
        if (isset($header_map[$h])) {
            $col_index[$header_map[$h]] = $idx;
        }
    }

    // Required columns validation
    if (!isset($col_index['production_year']) || !isset($col_index['plot_id'])) {
        fclose($handle);
        $_SESSION['import_status'] = 'error';
        $_SESSION['import_message'] = 'รูปแบบไฟล์ไม่ถูกต้อง: ต้องมีหัวตาราง "ปีผลิต" และ "ID แปลง"';
        header("Location: import_csv.php");
        exit;
    }

    $count_inserted = 0;
    $count_updated = 0;
    $count_errors = 0;

    // Process rows
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        // Skip empty rows
        if (empty(array_filter($data))) continue;

        $row_data = [];
        foreach ($col_index as $col_name => $idx) {
            $row_data[$col_name] = isset($data[$idx]) ? trim($data[$idx]) : '';
        }

        $prod_year = $row_data['production_year'];
        $plot_id = $row_data['plot_id'];

        if (empty($prod_year) || empty($plot_id)) {
            $count_errors++;
            continue;
        }

        // Check if exists
        $check_sql = "SELECT id FROM soil_data WHERE production_year = ? AND plot_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $prod_year, $plot_id);
        $check_stmt->execute();
        $check_res = $check_stmt->get_result();
        $existing = $check_res->fetch_assoc();
        $check_stmt->close();

        if ($existing) {
            // Update
            $update_parts = [];
            $params = [];
            $types = "";
            foreach ($row_data as $col => $val) {
                if ($col !== 'production_year' && $col !== 'plot_id') {
                    $update_parts[] = "$col = ?";
                    $params[] = $val;
                    $types .= "s";
                }
            }
            $sql = "UPDATE soil_data SET " . implode(", ", $update_parts) . " WHERE id = ?";
            $params[] = $existing['id'];
            $types .= "i";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            if ($stmt->execute()) {
                $count_updated++;
            } else {
                $count_errors++;
            }
            $stmt->close();
        } else {
            // Insert
            $cols = implode(", ", array_keys($row_data));
            $placeholders = implode(", ", array_fill(0, count($row_data), "?"));
            $types = str_repeat("s", count($row_data));
            $vals = array_values($row_data);

            $sql = "INSERT INTO soil_data ($cols) VALUES ($placeholders)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$vals);
            if ($stmt->execute()) {
                $count_inserted++;
            } else {
                $count_errors++;
            }
            $stmt->close();
        }
    }

    fclose($handle);

    $_SESSION['import_status'] = 'success';
    $_SESSION['import_message'] = "นำเข้าสำเร็จ: เพิ่มใหม่ $count_inserted รายการ, อัปเดต $count_updated รายการ" . ($count_errors > 0 ? ", ผิดพลาด $count_errors รายการ" : "");
} else {
    $_SESSION['import_status'] = 'error';
    $_SESSION['import_message'] = 'ไม่สามารถเปิดไฟล์เพื่อประมวลผลได้';
}

header("Location: import_csv.php");
exit;
