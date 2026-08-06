<?php
session_start();
require("dbconnect.php");

// บังคับการเชื่อมต่อเป็น UTF-8
mysqli_set_charset($con, "utf8mb4");

// ฟังก์ชันสำหรับบีบอัดและปรับขนาดรูปภาพ
function resizeAndSaveImage($file_tmp, $file_path, $width = 1200, $quality = 75) {
    $info = getimagesize($file_tmp);
    $mime = $info['mime'];

    switch ($mime) {
        case 'image/jpeg': $image = imagecreatefromjpeg($file_tmp); break;
        case 'image/png': $image = imagecreatefrompng($file_tmp); break;
        case 'image/webp': $image = imagecreatefromwebp($file_tmp); break;
        default: return false;
    }

    $orig_w = imagesx($image);
    $orig_h = imagesy($image);
    $height = ($orig_h / $orig_w) * $width;

    $new_image = imagecreatetruecolor($width, $height);
    
    // รักษาความโปร่งใส (ถ้ามี)
    if ($mime == 'image/png' || $mime == 'image/webp') {
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
    }

    imagecopyresampled($new_image, $image, 0, 0, 0, 0, $width, $height, $orig_w, $orig_h);
    
    // บันทึกไฟล์
    $result = imagejpeg($new_image, $file_path, $quality);
    
    imagedestroy($image);
    imagedestroy($new_image);
    return $result;
}

// ตรวจสอบรูปแบบวันที่ (yyyy-mm-dd) ก่อนบันทึก กันค่าพังจากฟอร์ม
function isValidDate($dateStr) {
    if (empty($dateStr)) return false;
    $d = DateTime::createFromFormat('Y-m-d', $dateStr);
    return $d && $d->format('Y-m-d') === $dateStr;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $plot_id = mysqli_real_escape_string($con, $_POST['plot_id']);
    $year_rai = mysqli_real_escape_string($con, $_POST['year_rai']);
    $emp_id = mysqli_real_escape_string($con, $_POST['emp_id']);
    $contract_number = mysqli_real_escape_string($con, $_POST['contract_number']);
    $quota = mysqli_real_escape_string($con, $_POST['quota']);
    $area_rai = mysqli_real_escape_string($con, $_POST['area_rai']);
    $suga_type = mysqli_real_escape_string($con, $_POST['suga_type']);
    
    $posted_status = isset($_POST['join_status']) ? trim($_POST['join_status']) : 'join';
    $join_status = ($posted_status === 'notjoin') ? 'notjoin' : 'join';

    $image_fields = ['water_image1', 'water_image2', 'water_image3', 'flood_image', 'drought_image', 'other_image'];
    $uploaded_images = [];
    $image_errors = []; // เก็บชื่อรูปที่บันทึกไม่สำเร็จ เพื่อแจ้งผู้ใช้ตรงๆ แทนการเงียบ

    foreach ($image_fields as $image_field) {
        if (isset($_FILES[$image_field]) && $_FILES[$image_field]['error'] == 0) {
            $upload_dir = "images/water/$emp_id/$contract_number/$plot_id/";

            $dir_ready = is_dir($upload_dir);
            if (!$dir_ready) {
                // เช็คผลลัพธ์ของ mkdir จริงๆ แทนการเรียกเฉยๆ แล้วเดินหน้าต่อ
                $dir_ready = mkdir($upload_dir, 0777, true);
            }

            if (!$dir_ready) {
                // สร้างโฟลเดอร์ไม่สำเร็จ (สิทธิ์ไฟล์บนโฮสต์ไม่พอ) -> ไม่ต้องพยายามเขียนไฟล์ต่อ
                $uploaded_images[$image_field] = $_POST[$image_field] ?? null;
                $image_errors[] = $image_field;
                continue;
            }

            // ตั้งชื่อไฟล์ใหม่เพื่อป้องกันปัญหาไฟล์ซ้ำหรือชื่ออ่านไม่ออก
            $file_ext = pathinfo($_FILES[$image_field]['name'], PATHINFO_EXTENSION);
            $new_file_name = $image_field . '_' . time() . '.jpg'; 
            $file_path = $upload_dir . $new_file_name;
            
            // เรียกใช้ฟังก์ชันบีบอัดรูป
            if (resizeAndSaveImage($_FILES[$image_field]['tmp_name'], $file_path)) {
                $uploaded_images[$image_field] = $file_path;
            } else {
                $uploaded_images[$image_field] = $_POST[$image_field] ?? null;
                $image_errors[] = $image_field; // บันทึกรูปไม่สำเร็จ (นามสกุลไฟล์ไม่รองรับ หรือเขียนไฟล์ไม่ได้)
            }
        } else {
            $uploaded_images[$image_field] = $_POST[$image_field] ?? null; 
        }
    }

    // ส่วนการ Update ข้อมูลยังคงเดิม...
    $sql_base = "UPDATE image_water SET 
        year_rai = '$year_rai',
        emp_id = '$emp_id',
        contract_number = '$contract_number',
        quota = '$quota',
        area_rai = '$area_rai',
        suga_type = '$suga_type'";

    foreach ($image_fields as $image_field) {
        if (!empty($uploaded_images[$image_field])) { 
            $sql_base .= ", $image_field = '" . mysqli_real_escape_string($con, $uploaded_images[$image_field]) . "'";
        }
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
            $sql_base .= ", $field = '$value_safe'";
        }
    }

    // --- ➕ ฟิลด์ใหม่: วิธีและวันที่ให้น้ำ (ครั้งที่ 1-3) ---
    for ($i = 1; $i <= 3; $i++) {
        $method_field = "water_method$i";
        $date_field = "water_date$i";

        if (isset($_POST[$method_field]) && trim($_POST[$method_field]) !== '') {
            $method_safe = mysqli_real_escape_string($con, mb_substr(trim($_POST[$method_field]), 0, 50));
            $sql_base .= ", $method_field = '$method_safe'";
        }

        if (isset($_POST[$date_field]) && trim($_POST[$date_field]) !== '') {
            $date_value = trim($_POST[$date_field]);
            if (isValidDate($date_value)) {
                $date_safe = mysqli_real_escape_string($con, $date_value);
                $sql_base .= ", $date_field = '$date_safe'";
            }
            // ถ้ารูปแบบวันที่ไม่ถูกต้อง จะข้ามไปเฉยๆ ไม่บันทึก ป้องกันค่าพังลง DB
        }
    }

    // สำคัญ: ต้องกรองด้วย plot_id + year_rai คู่กันเสมอ เพราะ plot_id เพียงอย่างเดียวไม่ใช่ค่าที่ไม่ซ้ำ
    // (แปลงเดียวกันมีข้อมูลได้หลายปีการผลิต) ถ้ากรองแค่ plot_id จะไปอัปเดตข้อมูลปีอื่นด้วยโดยไม่ตั้งใจ
    $sql_base .= " WHERE plot_id = '$plot_id' AND year_rai = '$year_rai'";
    
    $res_base = mysqli_query($con, $sql_base);
    $sql_status = "UPDATE image_water SET join_status = '$join_status' WHERE plot_id = '$plot_id' AND year_rai = '$year_rai'";
    $res_status = mysqli_query($con, $sql_status);

    // แสดงผลด้วย SweetAlert (ถ้าเซ็ตติ้งฐานข้อมูลทั่วไปผ่าน ก็นับว่าสำเร็จจ้ะ)
    echo '<!DOCTYPE html>
    <html lang="th">
    <head>
      <meta charset="UTF-8">
      <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
      <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600&display=swap" rel="stylesheet">
      <style>body { font-family: "Sarabun", sans-serif; }</style>
    </head>
    <body style="background-color: #f0f2f5;">';

    if ($res_base && !empty($image_errors)) {
        // ข้อมูลอื่นบันทึกได้ แต่มีรูปบางส่วนบันทึกไม่สำเร็จ (เช่น สร้างโฟลเดอร์บนโฮสต์ไม่ได้)
        $failed_list = implode(', ', $image_errors);
        echo "<script>
            Swal.fire({
                title: 'บันทึกข้อมูลบางส่วนสำเร็จ',
                text: 'ข้อมูลอื่นบันทึกแล้ว แต่รูปภาพนี้บันทึกไม่สำเร็จ: $failed_list กรุณาลองอัปโหลดรูปนี้ใหม่อีกครั้ง',
                icon: 'warning',
                confirmButtonText: 'ตกลง',
                heightAuto: false,
                customClass: { popup: 'rounded-4' }
            }).then(() => {
                window.location.href = 'user_page.php';
            });
        </script>";
    } else if ($res_base) {
        echo "<script>
            Swal.fire({
                title: 'บันทึกข้อมูลสำเร็จ!',
                text: 'ระบบเคลียร์สถานะให้เรียบร้อยแล้วค่ะ',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false,
                timerProgressBar: true,
                heightAuto: false,
                customClass: { popup: 'rounded-4' }
            }).then(() => {
                window.location.href = 'user_page.php';
            });
        </script>";
    } else {
        $error_msg = mysqli_error($con);
        echo "<script>
            Swal.fire({
                title: 'เกิดข้อผิดพลาด!',
                text: 'ไม่สามารถบันทึกข้อมูลได้: $error_msg',
                icon: 'error',
                confirmButtonText: 'ตกลง',
                heightAuto: false,
                customClass: { popup: 'rounded-4' }
            }).then(() => {
                window.location.href = 'user_water_edit_data.php?plot_id=$plot_id&year_rai=$year_rai';
            });
        </script>";
    }
    echo '</body></html>';
    $con->close();
    exit();
}
?>