<?php
require("dbconnect.php");

// รายชื่อคอลัมน์รูปภาพที่อนุญาตให้ลบได้เท่านั้น (whitelist) — กัน SQL Injection ผ่านชื่อคอลัมน์
// เพราะ mysqli_real_escape_string ป้องกันได้แค่ค่าที่อยู่ใน quote ไม่ได้ป้องกันชื่อคอลัมน์/identifier
$allowed_image_fields = ['water_image1', 'water_image2', 'water_image3', 'flood_image', 'drought_image', 'other_image'];

// ตรวจสอบว่ามีการส่งค่า delete_image, plot_id และ year_rai มาหรือไม่
if (isset($_GET['delete_image']) && isset($_GET['plot_id']) && isset($_GET['year_rai']) && in_array($_GET['delete_image'], $allowed_image_fields, true)) {
    $field_name = $_GET['delete_image']; // ผ่าน whitelist แล้ว ใช้เป็นชื่อคอลัมน์ได้อย่างปลอดภัย
    $plot_id = mysqli_real_escape_string($con, $_GET['plot_id']); // ID ของแปลง
    $year_rai = mysqli_real_escape_string($con, $_GET['year_rai']); // ปีการผลิต (ต้องคู่กับ plot_id เสมอ)

    // 1. ดึง path รูปภาพปัจจุบันจากฐานข้อมูล (กรองด้วย plot_id + year_rai คู่กัน)
    $sql_get_path = "SELECT $field_name FROM image_water WHERE plot_id = '$plot_id' AND year_rai = '$year_rai'";
    $result_get_path = mysqli_query($con, $sql_get_path);
    $row_get_path = mysqli_fetch_assoc($result_get_path);
    $current_image_path = '';

    if ($row_get_path && !empty($row_get_path[$field_name])) {
        $current_image_path = $row_get_path[$field_name];
    }

    // เริ่มต้นแสดง HTML สำหรับหน้าจอโหลด/สถานะ
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="th">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>กำลังดำเนินการ...</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
      <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600&display=swap" rel="stylesheet">
      <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
      <style>
        body {
          font-family: 'Sarabun', sans-serif;
          background-color: #f0f2f5;
          height: 100vh;
          margin: 0;
          display: flex;
          align-items: center;
          justify-content: center;
        }
      </style>
    </head>
    <body>
    <script>
      // แสดงสถานะกำลังโหลด
      Swal.fire({
        title: 'กำลังจัดการ...',
        text: 'กรุณารอสักครู่',
        allowOutsideClick: false,
        heightAuto: false,
        didOpen: () => {
          Swal.showLoading();
        },
        customClass: {
          popup: 'rounded-4'
        }
      });
    </script>
    HTML;

    $delete_success = false;
    $error_message = '';

    // 2. ลบไฟล์จากเซิร์ฟเวอร์ (ถ้ามีและ path ถูกต้อง)
    if (!empty($current_image_path) && file_exists($current_image_path)) {
        if (!unlink($current_image_path)) {
            $error_message = "ไม่สามารถลบไฟล์รูปภาพจากเซิร์ฟเวอร์ได้";
        }
    } else {
        // หากไม่มีไฟล์อยู่จริง หรือ path ว่างเปล่า ก็ไม่ต้องแจ้งข้อผิดพลาดเรื่องลบไฟล์
        // ถือว่าดำเนินการส่วนนี้ "สำเร็จ" ในแง่ที่ไม่ต้องลบอะไร
    }

    // 3. อัปเดตฐานข้อมูลให้ field นั้นเป็น NULL (กรองด้วย plot_id + year_rai คู่กัน ป้องกันไปลบรูปปีอื่นของแปลงเดียวกัน)
    if (empty($error_message)) { // ถ้าไม่มีข้อผิดพลาดจากการลบไฟล์
        $sql_update_db = "UPDATE image_water SET $field_name = NULL WHERE plot_id = ? AND year_rai = ?";
        $stmt_update_db = $con->prepare($sql_update_db);
        $stmt_update_db->bind_param("ss", $plot_id, $year_rai);

        if ($stmt_update_db->execute()) {
            $delete_success = true;
        } else {
            $error_message = "เกิดข้อผิดพลาดในการอัปเดตฐานข้อมูล: " . mysqli_error($con);
        }
        $stmt_update_db->close();
    }


    // 4. แสดงผลลัพธ์และ Redirect
    if ($delete_success) {
        echo <<<HTML
        <script>
            Swal.fire({
                title: 'ลบรูปภาพเรียบร้อย!',
                text: 'จัดการให้เรียบร้อย',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false,
                timerProgressBar: true,
                heightAuto: false,
                customClass: {
                  popup: 'rounded-4'
                }
            }).then(() => {
                window.location.href = 'user_water_edit_data.php?plot_id=$plot_id&year_rai=$year_rai';
            });
        </script>
        </body></html>
        HTML;
    } else {
        echo <<<HTML
        <script>
            Swal.fire({
                title: 'เกิดข้อผิดพลาด!',
                text: '$error_message',
                icon: 'error',
                confirmButtonText: 'ตกลง',
                heightAuto: false,
                customClass: {
                  popup: 'rounded-4'
                }
            }).then(() => {
                window.location.href = 'user_water_edit_data.php?plot_id=$plot_id&year_rai=$year_rai';
            });
        </script>
        </body></html>
        HTML;
    }

    $con->close(); // ปิด Connection หลังจากใช้งานเสร็จสิ้น

} else {
    // กรณีไม่พบข้อมูลที่ต้องการลบ หรือ id ไม่ถูกต้อง
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="th">
    <head>
      <meta charset="UTF-8">
      <title>ข้อมูลไม่ถูกต้อง</title>
      <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
      <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600&display=swap" rel="stylesheet">
      <style>
        body {
          font-family: 'Sarabun', sans-serif;
          background-color: #f0f2f5;
          height: 100vh;
          margin: 0;
          display: flex;
          align-items: center;
          justify-content: center;
        }
      </style>
    </head>
    <body>
    <script>
        Swal.fire({
            title: 'ข้อมูลไม่ถูกต้อง',
            text: 'ไม่พบรูปภาพหรือ ID ของแปลงที่ต้องการลบ โปรดลองใหม่อีกครั้งนะจ๊ะที่รัก',
            icon: 'info',
            confirmButtonText: 'กลับหน้าแรก',
            heightAuto: false,
            customClass: {
                popup: 'rounded-4'
            }
        }).then(() => {
            window.location.href = 'user_page.php';
        });
    </script>
    </body></html>
HTML;
}
?>