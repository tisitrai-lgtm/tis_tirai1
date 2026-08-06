<?php
session_start();
require("dbconnect.php");

// ตรวจสอบสิทธิ์ผู้ดูแลระบบ (ไฟล์นี้ไม่เคยมีการเช็คสิทธิ์มาก่อนเลย)
if (!isset($_SESSION['emp_level']) || $_SESSION['emp_level'] !== "a") {
    header("Location: login.php");
    exit();
}

// รายชื่อคอลัมน์รูปภาพที่อนุญาตให้ลบได้เท่านั้น (whitelist) — กัน SQL Injection ผ่านชื่อคอลัมน์
// mysqli_real_escape_string ป้องกันได้แค่ค่าที่อยู่ใน quote เท่านั้น ป้องกันชื่อคอลัมน์ไม่ได้
$allowed_image_fields = ['water_image1', 'water_image2', 'water_image3', 'flood_image', 'drought_image', 'other_image'];

// ตรวจสอบรูปแบบวันที่ (yyyy-mm-dd) ก่อนบันทึก กันค่าพังจากฟอร์ม
function isValidDate($dateStr) {
    if (empty($dateStr)) return false;
    $d = DateTime::createFromFormat('Y-m-d', $dateStr);
    return $d && $d->format('Y-m-d') === $dateStr;
}

// ฟังก์ชันลบภาพและล้าง path จากฐานข้อมูล
if (isset($_GET['delete_image']) && isset($_GET['plot_id']) && isset($_GET['year_rai']) && in_array($_GET['delete_image'], $allowed_image_fields, true)) {
    $field = $_GET['delete_image']; // ผ่าน whitelist แล้ว ใช้เป็นชื่อคอลัมน์ได้อย่างปลอดภัย
    $plot_id = mysqli_real_escape_string($con, $_GET['plot_id']);
    $year_rai = mysqli_real_escape_string($con, $_GET['year_rai']);

    // ค้นหา path ภาพในฐานข้อมูล (กรองด้วย plot_id + year_rai คู่กัน)
    $sql = "SELECT $field FROM image_water WHERE plot_id = '$plot_id' AND year_rai = '$year_rai'";
    $res = mysqli_query($con, $sql);
    $row = mysqli_fetch_assoc($res);

    if ($row && !empty($row[$field]) && file_exists($row[$field])) {
        unlink($row[$field]); // ลบไฟล์จริง
    }

    // อัปเดตฐานข้อมูลให้ path ว่าง (กรองด้วย plot_id + year_rai คู่กัน)
    $update = "UPDATE image_water SET $field = '' WHERE plot_id = '$plot_id' AND year_rai = '$year_rai'";
    if (mysqli_query($con, $update)) {
        // กลับไปหน้าแก้ไขพร้อมสถานะสำเร็จ
        header("Location: water_edit_data.php?plot_id=$plot_id&year_rai=$year_rai&status=image_deleted");
    } else {
        // กลับไปหน้าแก้ไขพร้อมสถานะผิดพลาด
        header("Location: water_edit_data.php?plot_id=$plot_id&year_rai=$year_rai&status=delete_error");
    }
    exit();
}

// ส่วนอัปเดตข้อมูลจากฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Escape string เพื่อป้องกัน SQL Injection
    $plot_id = mysqli_real_escape_string($con, $_POST['plot_id']);
    $year_rai = mysqli_real_escape_string($con, $_POST['year_rai']);
    // ปีเดิมก่อนแก้ไข (มาจาก hidden field) ใช้สำหรับระบุแถวที่จะอัปเดตเท่านั้น
    // ต้องแยกจาก $year_rai ด้านบนซึ่งเป็นค่าใหม่ที่ผู้ใช้อาจเปลี่ยนในฟอร์ม
    $original_year_rai = mysqli_real_escape_string($con, $_POST['original_year_rai'] ?? $_POST['year_rai']);
    $emp_id = mysqli_real_escape_string($con, $_POST['emp_id']);
    $contract_number = mysqli_real_escape_string($con, $_POST['contract_number']);
    $quota = mysqli_real_escape_string($con, $_POST['quota']);
    $area_rai = mysqli_real_escape_string($con, $_POST['area_rai']);
    $suga_type = mysqli_real_escape_string($con, $_POST['suga_type']);

    // สถานะการเข้าร่วมโครงการ (จำกัดค่าที่ยอมรับให้เหลือแค่ join/notjoin เท่านั้น)
    $posted_status = isset($_POST['join_status']) ? trim($_POST['join_status']) : 'join';
    $join_status = ($posted_status === 'notjoin') ? 'notjoin' : 'join';

    // เตรียม path โฟลเดอร์เก็บรูป
    $base_path = "images/water/{$emp_id}/{$contract_number}/{$plot_id}/";
    if (!file_exists($base_path)) {
        // สร้างโฟลเดอร์แบบ recursive และตั้งค่า permission
        if (!mkdir($base_path, 0777, true)) {
            // จัดการข้อผิดพลาดถ้าสร้างโฟลเดอร์ไม่ได้
            $error_message = "ไม่สามารถสร้างโฟลเดอร์สำหรับรูปภาพได้: " . $base_path;
            goto display_error; // กระโดดไปแสดงผลข้อผิดพลาด
        }
    }

    // ชื่อฟิลด์รูปภาพ
    $image_fields = [
        "water_image1", "water_image2", "water_image3",
        "flood_image", "drought_image", "other_image"
    ];

    $update_image_parts = []; // เก็บส่วนของ SQL ที่จะอัปเดต path รูปภาพ

    // อัปโหลดรูปใหม่ถ้ามีการแนบ
    foreach ($image_fields as $field) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === 0) {
            $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
            $filename = $field . "_" . time() . "." . $ext; // เพิ่ม timestamp เพื่อป้องกันชื่อซ้ำ
            $filepath = $base_path . $filename;

            // ลบของเก่าถ้ามี (ควรลบตาม path ที่เก็บใน DB ไม่ใช่ชื่อไฟล์ที่ตั้งไว้)
            $sql_old = "SELECT $field FROM image_water WHERE plot_id = '$plot_id' AND year_rai = '$original_year_rai'";
            $res_old = mysqli_query($con, $sql_old);
            $row_old = mysqli_fetch_assoc($res_old);
            if ($row_old && !empty($row_old[$field]) && file_exists($row_old[$field])) {
                unlink($row_old[$field]);
            }

            if (move_uploaded_file($_FILES[$field]['tmp_name'], $filepath)) {
                $update_image_parts[] = "$field = '" . mysqli_real_escape_string($con, $filepath) . "'";
            } else {
                // จัดการข้อผิดพลาดการอัปโหลดไฟล์
                $error_message = "ไม่สามารถอัปโหลดไฟล์รูปภาพ '$field' ได้";
                goto display_error;
            }
        } else {
            // ถ้าไม่มีการอัปโหลดรูปใหม่สำหรับฟิลด์นี้
            // ให้คงค่าเดิมไว้ (ถ้าไม่ได้ลบไปก่อนหน้า หรือถ้าไม่มี)
            // หรืออาจจะรับค่าจาก hidden field ในฟอร์ม ถ้ามี
            // ในที่นี้ เราจะไม่อัปเดตฟิลด์รูปภาพถ้าไม่มีการอัปโหลดใหม่
            // เพราะถ้าไม่มีการอัปโหลด ค่าจะยังคงเป็นค่าเดิมใน DB อยู่แล้ว
        }
    }

    // สร้าง query UPDATE
    $sql = "UPDATE image_water SET 
        year_rai = '$year_rai',
        emp_id = '$emp_id',
        contract_number = '$contract_number',
        quota = '$quota',
        area_rai = '$area_rai',
        suga_type = '$suga_type',
        join_status = '$join_status'";

    if (!empty($update_image_parts)) {
        $sql .= ", " . implode(", ", $update_image_parts);
    }

    // --- ➕ ฟิลด์ใหม่: ข้อมูลเจ้าของแปลง / ที่อยู่ ---
    // บันทึกเฉพาะฟิลด์ที่มีการส่งค่ามาจากฟอร์มเท่านั้น (ถ้าหน้าไหนยังไม่มีช่องกรอกพวกนี้
    // จะไม่ไปเขียนทับข้อมูลเดิมในฐานข้อมูลด้วยค่าว่าง)
    $text_fields = [
        'citizen_id'   => 13,   // เลขบัตรประชาชน 13 หลัก
        'house_no'     => 50,
        'sub_district' => 100,
        'district'     => 100,
        'province'     => 100,
        'water_source' => 255,
    ];

    foreach ($text_fields as $field => $max_len) {
        if (isset($_POST[$field]) && trim($_POST[$field]) !== '') {
            $value = mb_substr(trim($_POST[$field]), 0, $max_len);
            $value_safe = mysqli_real_escape_string($con, $value);
            $sql .= ", $field = '$value_safe'";
        }
    }

    // --- ➕ ฟิลด์ใหม่: วิธีและวันที่ให้น้ำ (ครั้งที่ 1-3) ---
    for ($i = 1; $i <= 3; $i++) {
        $method_field = "water_method$i";
        $date_field = "water_date$i";

        if (isset($_POST[$method_field]) && trim($_POST[$method_field]) !== '') {
            $method_safe = mysqli_real_escape_string($con, mb_substr(trim($_POST[$method_field]), 0, 50));
            $sql .= ", $method_field = '$method_safe'";
        }

        if (isset($_POST[$date_field]) && trim($_POST[$date_field]) !== '') {
            $date_value = trim($_POST[$date_field]);
            if (isValidDate($date_value)) {
                $date_safe = mysqli_real_escape_string($con, $date_value);
                $sql .= ", $date_field = '$date_safe'";
            }
            // ถ้ารูปแบบวันที่ไม่ถูกต้อง จะข้ามไปเฉยๆ ไม่บันทึก ป้องกันค่าพังลง DB
        }
    }

    // สำคัญ: ต้องกรองด้วย plot_id + "ปีเดิม" คู่กันเสมอ เพราะ plot_id เพียงอย่างเดียวไม่ใช่ค่าที่ไม่ซ้ำ
    // (แปลงเดียวกันมีข้อมูลได้หลายปีการผลิต) ใช้ $original_year_rai (ปีก่อนแก้ไข) ไม่ใช่ $year_rai (ปีใหม่ที่อาจถูกเปลี่ยน)
    // เพื่อระบุแถวที่ต้องการอัปเดตให้ตรงตัวจริงๆ
    $sql .= " WHERE plot_id = '$plot_id' AND year_rai = '$original_year_rai'";

    // ครอบด้วย try/catch เพราะ mysqli ในระบบนี้ throw exception เวลา query ผิดพลาด (เช่น เปลี่ยนปีไปชนกับแถวที่มีอยู่แล้ว)
    // ถ้าไม่ครอบไว้ จะกลายเป็น Fatal error ทั้งหน้าแทนที่จะแจ้งข้อความที่เข้าใจได้
    $query_ok = false;
    try {
        $query_ok = mysqli_query($con, $sql);
    } catch (mysqli_sql_exception $e) {
        $query_ok = false;
        $duplicate_key_error = strpos($e->getMessage(), 'Duplicate entry') !== false
            ? "มีข้อมูลของแปลงนี้ในปี '$year_rai' อยู่แล้ว ไม่สามารถเปลี่ยนไปเป็นปีที่ซ้ำกันได้"
            : $e->getMessage();
    }

    if ($query_ok) {
        // SUCCESS HTML
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="th">
        <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>บันทึกข้อมูลสำเร็จ</title>
        <meta http-equiv="refresh" content="2;url=admin_page.php"> <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
        <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600&display=swap" rel="stylesheet">
        <style>
          body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
          }
          .status-box {
            max-width: 450px;
            width: 100%;
            padding: 30px;
            text-align: center;
            background-color: #ffffff;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            animation: fadeIn 0.8s ease-out;
          }
          .status-box.success {
            border: 1px solid #d4edda;
          }
          .status-box.error {
            border: 1px solid #dc3545;
          }
          .status-box i.bx {
            font-size: 70px;
            margin-bottom: 20px;
          }
          .status-box .bx-check-circle {
            color: #28a745;
            animation: bounceIn 0.8s ease-out;
          }
          .status-box .bx-x-circle {
            color: #dc3545;
            animation: shake 0.5s;
          }
          .status-box h4 {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 10px;
          }
          .status-box p {
            font-size: 1.1rem;
            color: #666;
            word-break: break-word;
          }

          /* Animations */
          @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
          }
          @keyframes bounceIn {
            0%, 20%, 40%, 60%, 80%, 100% {
              transition-timing-function: cubic-bezier(0.215, 0.610, 0.355, 1.000);
            }
            0% { opacity: 0; transform: scale3d(0.3, 0.3, 0.3); }
            20% { transform: scale3d(1.1, 1.1, 1.1); }
            40% { transform: scale3d(0.9, 0.9, 0.9); }
            60% { opacity: 1; transform: scale3d(1.03, 1.03, 1.03); }
            80% { transform: scale3d(0.97, 0.97, 0.97); }
            100% { opacity: 1; transform: scale3d(1, 1, 1); }
          }
          @keyframes shake {
            0% { transform: translateX(0); }
            20% { transform: translateX(-10px); }
            40% { transform: translateX(10px); }
            60% { transform: translateX(-10px); }
            80% { transform: translateX(10px); }
            100% { transform: translateX(0); }
          }
        </style>
        </head>
        <body>
        <div class="status-box success">
          <i class='bx bx-check-circle'></i>
          <h4>บันทึกการแก้ไขเรียบร้อย</h4>
          <p>ระบบกำลังพาคุณกลับหน้าผู้ดูแลใน 2 วินาที...</p>
        </div>
        </body>
        </html>
        HTML;
    } else {
        $error_message = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . ($duplicate_key_error ?? mysqli_error($con));
        // กระโดดมาที่นี่หากเกิดข้อผิดพลาดในการ Query
        display_error: 
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="th">
        <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>เกิดข้อผิดพลาด</title>
        <meta http-equiv="refresh" content="5;url=admin_page.php"> <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
        <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600&display=swap" rel="stylesheet">
        <style>
          body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
          }
          .status-box {
            max-width: 450px;
            width: 100%;
            padding: 30px;
            text-align: center;
            background-color: #ffffff;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            animation: fadeIn 0.8s ease-out;
          }
          .status-box.success {
            border: 1px solid #d4edda;
          }
          .status-box.error {
            border: 1px solid #dc3545;
          }
          .status-box i.bx {
            font-size: 70px;
            margin-bottom: 20px;
          }
          .status-box .bx-check-circle {
            color: #28a745;
            animation: bounceIn 0.8s ease-out;
          }
          .status-box .bx-x-circle {
            color: #dc3545;
            animation: shake 0.5s;
          }
          .status-box h4 {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 10px;
          }
          .status-box p {
            font-size: 1.1rem;
            color: #666;
            word-break: break-word;
          }

          /* Animations */
          @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
          }
          @keyframes bounceIn {
            0%, 20%, 40%, 60%, 80%, 100% {
              transition-timing-function: cubic-bezier(0.215, 0.610, 0.355, 1.000);
            }
            0% { opacity: 0; transform: scale3d(0.3, 0.3, 0.3); }
            20% { transform: scale3d(1.1, 1.1, 1.1); }
            40% { transform: scale3d(0.9, 0.9, 0.9); }
            60% { opacity: 1; transform: scale3d(1.03, 1.03, 1.03); }
            80% { transform: scale3d(0.97, 0.97, 0.97); }
            100% { opacity: 1; transform: scale3d(1, 1, 1); }
          }
          @keyframes shake {
            0% { transform: translateX(0); }
            20% { transform: translateX(-10px); }
            40% { transform: translateX(10px); }
            60% { transform: translateX(-10px); }
            80% { transform: translateX(10px); }
            100% { transform: translateX(0); }
          }
        </style>
        </head>
        <body>
        <div class="status-box error">
          <i class='bx bx-x-circle'></i>
          <h4>เกิดข้อผิดพลาด!</h4>
          <p>
            เกิดข้อผิดพลาดในการบันทึกข้อมูล: <br> 
            <strong>
            <?php echo htmlspecialchars($error_message); ?>
            </strong>
          </p>
          <p>ระบบกำลังพาคุณกลับหน้าผู้ดูแลใน 5 วินาที...</p>
        </div>
        </body>
        </html>
        HTML;
    }
    exit();
}
?>