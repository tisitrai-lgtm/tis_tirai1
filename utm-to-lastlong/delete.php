<?php
include_once 'db_config.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // เริ่มต้นแสดงผลโครงสร้างหน้าเว็บเพื่อให้ JS ทำงานได้
    ?>
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <title>กำลังลบข้อมูล...</title>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <style>
            body { font-family: 'Sarabun', sans-serif; background-color: #f4f7f6; }
        </style>
    </head>
    <body>
    <?php
    
    $sql = "DELETE FROM conversion_logs WHERE id = $id";
    
    if ($conn->query($sql)) {
        echo "<script>
            Swal.fire({
                title: 'ลบข้อมูลเรียบร้อย!',
                text: 'รายการพิกัดถูกลบออกจากระบบแล้ว',
                icon: 'success',
                confirmButtonColor: '#d33',
                confirmButtonText: 'ตกลง'
            }).then((result) => {
                window.location.href = 'index.php';
            });
        </script>";
    } else {
        echo "<script>
            Swal.fire({
                title: 'เกิดข้อผิดพลาด!',
                text: 'ไม่สามารถลบได้: " . addslashes($conn->error) . "',
                icon: 'error',
                confirmButtonText: 'กลับไปหน้าหลัก'
            }).then(() => {
                window.location.href = 'index.php';
            });
        </script>";
    }
    ?>
    </body>
    </html>
    <?php
} else {
    header("Location: index.php");
    exit;
}
?>