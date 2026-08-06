<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); // เริ่ม session ถ้ายังไม่ได้เริ่ม
// ตรวจสอบสิทธิ์ผู้ดูแลระบบ (หากหน้านี้สำหรับ Admin เท่านั้น)
if (!isset($_SESSION['emp_level']) || $_SESSION['emp_level'] !== "a") {
    // สามารถ redirect หรือแสดงข้อความเตือนแทน
    echo "<center>หน้านี้สำหรับผู้ดูแลระบบเท่านั้น <a href='login.php'>กรุณาเข้าสู่ระบบก่อน</a></center>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ini_set('max_execution_time', 1200); // 20 นาที
    ini_set('memory_limit', '2048M');   // 2 GB

    // ตรวจสอบว่าคุณติดตั้ง SpreadsheetReader ผ่าน Composer หรือไม่
    // ถ้าติดตั้งผ่าน Composer ให้ใช้บรรทัดนี้:
    require_once 'SpreadsheetReader.php';
    require_once 'SpreadsheetReader_XLSX.php';
    require_once 'SpreadsheetReader_CSV.php';
    require_once 'SpreadsheetReader_XLS.php';

    require("dbconnect.php"); // ตรวจสอบว่า $con (หรือ $conn) ถูกสร้างและเชื่อมต่อเรียบร้อยแล้ว

    if (!isset($con) || $con->connect_error) {
        echo "<script>alert('เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: " . $con->connect_error . "'); window.location = 'admin_page.php';</script>";
        exit;
    }

    if (isset($_FILES['excel']['name']) && $_FILES['excel']['error'] === UPLOAD_ERR_OK) {
        $file_name = $_FILES['excel']['name'];
        $file_tmp = $_FILES['excel']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // ตรวจสอบประเภทไฟล์ที่อนุญาต
        $allowed_ext = ['xls', 'xlsx', 'csv'];
        if (!in_array($file_ext, $allowed_ext)) {
            echo "<script>alert('ประเภทไฟล์ไม่รองรับ กรุณาอัปโหลดไฟล์ .xls, .xlsx หรือ .csv เท่านั้น'); window.location = 'admin_page.php';</script>";
            exit;
        }

        if (!is_dir('uploads')) {
            // ใช้สิทธิ์ที่เหมาะสม เช่น 0755
            if (!mkdir('uploads', 0755, true)) {
                echo "<script>alert('ไม่สามารถสร้างโฟลเดอร์ uploads ได้ กรุณาตรวจสอบสิทธิ์ของเว็บเซิร์ฟเวอร์'); window.location = 'admin_page.php';</script>";
                exit;
            }
        }

        // สร้างชื่อไฟล์ที่ไม่ซ้ำกัน
        $new_file_name = uniqid() . '.' . $file_ext;
        $target_path = 'uploads/' . $new_file_name;

        if (move_uploaded_file($file_tmp, $target_path)) {
            $reader = null;
            try {
                // ตรวจสอบประเภทไฟล์ที่ถูกต้องอีกครั้งเพื่อสร้าง Reader ที่เหมาะสม
                if ($file_ext === 'xlsx') {
                    $reader = new SpreadsheetReader_XLSX($target_path);
                } elseif ($file_ext === 'xls') {
                    $reader = new SpreadsheetReader_XLS($target_path);
                } elseif ($file_ext === 'csv') {
                    $reader = new SpreadsheetReader_CSV($target_path);
                } else {
                    // ควรถูกดักจับก่อนหน้านี้แล้ว แต่เพื่อความปลอดภัย
                    echo "<script>alert('ประเภทไฟล์ไม่รองรับ'); window.location = 'admin_page.php';</script>";
                    unlink($target_path); // ลบไฟล์ที่อัปโหลดไปแล้ว
                    exit;
                }
            } catch (Exception $e) {
                echo "<script>alert('เกิดข้อผิดพลาดในการอ่านไฟล์: " . $e->getMessage() . "'); window.location = 'admin_page.php';</script>";
                unlink($target_path);
                exit;
            }
            

            $inserted = 0;
            $skipped = 0;
            $errors = []; // สำหรับเก็บข้อผิดพลาดโดยละเอียด

            // เตรียม Prepared Statements ล่วงหน้า
            $check_stmt = $con->prepare("SELECT plot_id FROM image_water WHERE plot_id = ?");
            $insert_stmt = $con->prepare("INSERT INTO image_water (year_rai, emp_id, plot_id, contract_number, suga_type, quota, area_rai) VALUES (?, ?, ?, ?, ?, ?, ?)");

            if ($check_stmt === false || $insert_stmt === false) {
                echo "<script>alert('เกิดข้อผิดพลาดในการเตรียม Statement: " . $con->error . "'); window.location = 'admin_page.php';</script>";
                unlink($target_path);
                exit;
            }

            foreach ($reader as $key => $row) {
                if ($key == 0) continue; // ข้ามหัวตาราง

                // ตรวจสอบว่า $row มีค่าเพียงพอหรือไม่
                if (count($row) < 7) { 
                    $skipped++;
                    $errors[] = "แถวที่ " . ($key + 1) . ": ข้อมูลไม่ครบถ้วน (ต้องมี 7 คอลัมน์)";
                    continue;
                }

                $year_rai = trim($row[0]);
                $plot_id = trim($row[1]);
                $emp_id = trim($row[2]);
                $contract_number = trim($row[3]);
                $quota = trim($row[4]);
                $area_rai = trim($row[5]);
                $suga_type = trim($row[6]);

                if (empty($plot_id)) {
                    $skipped++;
                    $errors[] = "แถวที่ " . ($key + 1) . ": plot_id ว่างเปล่า";
                    continue;
                }

                // ตรวจสอบว่า plot_id ซ้ำหรือไม่ (ด้วย Prepared Statement)
                $check_stmt->bind_param("s", $plot_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();

                if ($check_result->num_rows > 0) {
                    $skipped++;
                    $errors[] = "แถวที่ " . ($key + 1) . ": plot_id '$plot_id' ซ้ำกันในฐานข้อมูล";
                    continue;
                }

                // แทรกข้อมูล (ด้วย Prepared Statement)
                $insert_stmt->bind_param("sssssss", $year_rai, $emp_id, $plot_id, $contract_number, $suga_type, $quota, $area_rai);
                if ($insert_stmt->execute()) {
                    $inserted++;
                } else {
                    $skipped++;
                    $errors[] = "แถวที่ " . ($key + 1) . ": เกิดข้อผิดพลาดในการเพิ่มข้อมูล: " . $insert_stmt->error;
                }
            }

            $check_stmt->close();
            $insert_stmt->close();
            $con->close(); // ปิดการเชื่อมต่อฐานข้อมูล

            unlink($target_path); // ลบไฟล์ที่อัปโหลดหลังประมวลผลเสร็จ

            $error_message = '';
            if (!empty($errors)) {
                $error_message = "\\n\\nข้อผิดพลาดที่พบ:\\n" . implode("\\n", array_slice($errors, 0, 5)); // แสดงแค่ 5 ข้อแรก
                if (count($errors) > 5) {
                    $error_message .= "\\n...และอีก " . (count($errors) - 5) . " รายการ (ตรวจสอบ Log สำหรับรายละเอียดเพิ่มเติม)";
                }
            }

            echo "<script>
                alert('นำเข้าข้อมูลเสร็จสิ้น:\\nเพิ่มข้อมูลสำเร็จ: $inserted รายการ\\nข้ามข้อมูล: $skipped รายการ" . $error_message . "');
                window.location = 'admin_page.php';
            </script>";
            exit;

        } else {
            // ข้อผิดพลาดในการย้ายไฟล์หลังจากอัปโหลด
            echo "<script>alert('ไม่สามารถอัปโหลดไฟล์ได้: " . $_FILES['excel']['error'] . "'); window.location = 'admin_page.php';</script>";
        }
    } else {
        // กรณีไม่มีไฟล์อัปโหลด หรือมีข้อผิดพลาดในการอัปโหลดตั้งแต่แรก
        if (isset($_FILES['excel']['error'])) {
            $upload_error_code = $_FILES['excel']['error'];
            $error_msg = 'ไม่พบไฟล์ที่เลือก'; // UPLOAD_ERR_NO_FILE
            switch ($upload_error_code) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error_msg = 'ไฟล์มีขนาดใหญ่เกินกว่าที่กำหนด';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error_msg = 'ไฟล์ถูกอัปโหลดไม่สมบูรณ์';
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $error_msg = 'ไม่พบโฟลเดอร์ชั่วคราวสำหรับจัดเก็บไฟล์';
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $error_msg = 'ไม่สามารถเขียนไฟล์ลงดิสก์ได้';
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $error_msg = 'การอัปโหลดไฟล์ถูกหยุดโดยส่วนขยาย PHP';
                    break;
            }
            echo "<script>alert('เกิดข้อผิดพลาดในการอัปโหลดไฟล์: " . $error_msg . " (Code: " . $upload_error_code . ")'); window.location = 'admin_page.php';</script>";
        } else {
            // กรณีอื่นๆ ที่ไม่ได้ระบุ
            echo "<script>alert('เกิดข้อผิดพลาดที่ไม่ทราบสาเหตุในการอัปโหลดไฟล์'); window.location = 'admin_page.php';</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>นำเข้าข้อมูลแปลงอ้อย</title>
  <style>
    body { font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: #f4f4f4; margin: 0; }
    .container { background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
    h2 { color: #333; margin-bottom: 20px; }
    form { margin-top: 20px; }
    input[type="file"] { border: 1px solid #ddd; padding: 10px; border-radius: 5px; margin-bottom: 15px; width: calc(100% - 22px); }
    button[type="submit"] { background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
    button[type="submit"]:hover { background-color: #0056b3; }

    #loader {
      display: none;
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(255, 255, 255, 0.8);
      z-index: 9999;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
    }
    .spinner {
      border: 8px solid #f3f3f3; /* Light grey */
      border-top: 8px solid #3498db; /* Blue */
      border-radius: 50%;
      width: 80px;
      height: 80px;
      animation: spin 1s linear infinite;
      margin-bottom: 20px;
    }
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    #loader p {
      color: #333;
      font-size: 1.2em;
      font-weight: bold;
    }
  </style>
</head>
<body>

<div class="container">
    <h2>นำเข้าข้อมูลจาก Excel</h2>
    <form method="post" enctype="multipart/form-data" onsubmit="return validateAndShowLoader()">
      <input type="file" name="excel" id="excelFile" accept=".xls,.xlsx,.csv" required>
      <button type="submit">อัปโหลด</button>
    </form>
</div>

<div id="loader">
  <div class="spinner"></div>
  <p>กำลังนำเข้าข้อมูล... กรุณารอสักครู่</p>
</div>

<script>
  function validateAndShowLoader() {
    const fileInput = document.getElementById('excelFile');
    const file = fileInput.files[0];

    if (!file) {
      alert('กรุณาเลือกไฟล์ Excel หรือ CSV ที่ต้องการอัปโหลด');
      return false; // หยุดการ submit form
    }

    const allowedExtensions = ['xls', 'xlsx', 'csv'];
    const fileExtension = file.name.split('.').pop().toLowerCase();

    if (!allowedExtensions.includes(fileExtension)) {
      alert('ประเภทไฟล์ไม่รองรับ กรุณาอัปโหลดไฟล์ .xls, .xlsx หรือ .csv เท่านั้น');
      fileInput.value = ''; // ล้างไฟล์ที่เลือก
      return false; // หยุดการ submit form
    }

    // แสดง loader หลังจากตรวจสอบผ่าน
    document.getElementById("loader").style.display = "flex";
    return true; // อนุญาตให้ submit form
  }
</script>

</body>
</html>