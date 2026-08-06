<?php
require("dbconnect.php");

// ตรวจสอบว่ามีการส่งค่า ID มาหรือไม่
if (isset($_GET['id'])) {
    // ใช้ mysqli_real_escape_string และ PreparedStatement เพื่อป้องกัน SQL Injection
    $id = mysqli_real_escape_string($con, $_GET['id']);

    // ก่อนลบข้อมูล ควรจะดึง path รูปภาพที่เกี่ยวข้องมาลบจาก Server ด้วย
    // ค้นหา path รูปภาพทั้งหมดที่เกี่ยวข้องกับ plot_id นี้
    $sql_select_images = "SELECT water_image1, water_image2, water_image3, flood_image, drought_image, other_image FROM image_water WHERE plot_id = ?";
    $stmt_select_images = $con->prepare($sql_select_images);
    $stmt_select_images->bind_param("s", $id); // Assuming plot_id is string/varchar
    $stmt_select_images->execute();
    $result_images = $stmt_select_images->get_result();
    $image_paths = [];

    if ($result_images->num_rows > 0) {
        $row_images = $result_images->fetch_assoc();
        foreach ($row_images as $path) {
            if (!empty($path) && file_exists($path)) {
                $image_paths[] = $path;
            }
        }
    }
    $stmt_select_images->close();

    // เริ่ม HTML สำหรับแสดงหน้าจอโหลด/สถานะ
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="th">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>กำลังดำเนินการ...</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
          overflow: hidden; /* ป้องกัน scrollbar ในหน้าจอโหลด */
        }
        .status-container {
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background-color: rgba(0, 0, 0, 0.7); /* เพิ่ม opacity */
          color: white;
          display: flex;
          flex-direction: column; /* จัดองค์ประกอบแนวตั้ง */
          align-items: center;
          justify-content: center;
          z-index: 9999;
          font-size: 1.2rem;
          text-align: center;
        }
        .spinner-grow { /* ใช้ Bootstrap spinner */
            width: 3rem;
            height: 3rem;
            color: #fff;
            margin-bottom: 1rem;
        }
        /* เพิ่ม animation fade-out สำหรับ container เมื่อ redirect */
        .fade-out {
            animation: fadeOut 0.5s forwards;
        }
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }

        /* กล่องแสดงสถานะสำเร็จ/ผิดพลาด */
        .status-box {
          max-width: 450px;
          width: 100%;
          padding: 30px;
          text-align: center;
          background-color: #ffffff;
          border-radius: 15px;
          box-shadow: 0 5px 20px rgba(0,0,0,0.1);
          animation: fadeIn 0.8s ease-out;
          color: #333; /* สีข้อความ */
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
          margin-bottom: 10px;
        }
        .status-box p {
          font-size: 1.1rem;
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
    <div class="status-container" id="statusContainer">
        <div class="spinner-grow text-light" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        กำลังลบข้อมูล...
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    HTML;

    // ลบข้อมูลจากฐานข้อมูล (image_water)
    $sql_delete_record = "DELETE FROM image_water WHERE plot_id = ?";
    $stmt_delete_record = $con->prepare($sql_delete_record);
    $stmt_delete_record->bind_param("s", $id); // Assuming plot_id is string/varchar

    if ($stmt_delete_record->execute()) {
        // หากลบข้อมูลใน DB สำเร็จ ให้ลบไฟล์รูปภาพบน Server ด้วย
        foreach ($image_paths as $path) {
            // ดึงชื่อไดเรกทอรีของรูปภาพ
            $dir_path = dirname($path);
            if (file_exists($path)) {
                unlink($path); // ลบไฟล์รูปภาพ
            }
            // ตรวจสอบว่าโฟลเดอร์ว่างเปล่าหรือไม่ ถ้าว่างให้ลบ (Optional)
            if (is_dir($dir_path) && count(glob($dir_path . '/*')) === 0) {
                 rmdir($dir_path); // ลบโฟลเดอร์ว่างเปล่า
            }
        }

        // แสดงผลสำเร็จ
        echo <<<HTML
        <div class="status-box success">
          <i class='bx bx-check-circle'></i>
          <h4>ลบข้อมูลเรียบร้อย!</h4>
          <p>ระบบกำลังพาคุณกลับหน้าผู้ดูแลใน 2 วินาที...</p>
        </div>
        <script>
            // ซ่อนหน้าจอโหลด
            document.getElementById('statusContainer').classList.add('fade-out');
            setTimeout(function() {
                document.getElementById('statusContainer').style.display = 'none';
                window.location.href = 'admin_page.php'; // กลับไปที่หน้าผู้ดูแล
            }, 2000); // ตั้งเวลาล่าช้า 2 วินาที
        </script>
        </body></html>
        HTML;
    } else {
        // แสดงผลข้อผิดพลาด
        $error_message = "เกิดข้อผิดพลาดในการลบข้อมูล: " . mysqli_error($con);
        echo <<<HTML
        <div class="status-box error">
          <i class='bx bx-x-circle'></i>
          <h4>เกิดข้อผิดพลาด!</h4>
          <p>
            $error_message
          </p>
          <p>ระบบกำลังพาคุณกลับหน้าผู้ดูแลใน 5 วินาที...</p>
        </div>
        <script>
            // ซ่อนหน้าจอโหลด
            document.getElementById('statusContainer').classList.add('fade-out');
            setTimeout(function() {
                document.getElementById('statusContainer').style.display = 'none';
                window.location.href = 'admin_page.php'; // กลับไปที่หน้าผู้ดูแล
            }, 5000); // ตั้งเวลาล่าช้า 5 วินาที
        </script>
        </body></html>
        HTML;
    }

    $stmt_delete_record->close();
    $con->close();
} else {
    // กรณีไม่พบ ID ที่ส่งมา
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="th">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>ข้อมูลไม่ถูกต้อง</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
          color: #333;
        }
        .status-box.error {
          border: 1px solid #dc3545;
        }
        .status-box i.bx {
          font-size: 70px;
          margin-bottom: 20px;
          color: #dc3545;
        }
        .status-box h4 {
          font-size: 1.8rem;
          margin-bottom: 10px;
        }
        .status-box p {
          font-size: 1.1rem;
          word-break: break-word;
        }
        @keyframes fadeIn {
          from { opacity: 0; transform: translateY(-20px); }
          to { opacity: 1; transform: translateY(0); }
        }
      </style>
    </head>
    <body>
    <div class="status-box error">
      <i class='bx bx-info-circle'></i>
      <h4>ไม่พบข้อมูลที่ต้องการลบ</h4>
      <p>โปรดลองใหม่อีกครั้ง</p>
      <p>ระบบกำลังพาคุณกลับหน้าผู้ดูแลใน 3 วินาที...</p>
    </div>
    <script>
        setTimeout(function() {
            window.location.href = 'admin_page.php';
        }, 3000);
    </script>
    </body></html>
    HTML;
}
?>