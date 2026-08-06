<?php
include_once 'db_config.php';

// ป้องกันหน้าขาวโดยการเช็ค Method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ux   = $_POST['utm_x'];
    $uy   = $_POST['utm_y'];
    $ax   = $_POST['adj_x'];
    $ay   = $_POST['adj_y'];
    $lat  = $_POST['lat'];
    $lng  = $_POST['lng'];
    $note = $conn->real_escape_string($_POST['note']);

    $sql = "INSERT INTO conversion_logs (utm_x, utm_y, adj_x, adj_y, latitude, longitude, note) 
            VALUES ('$ux', '$uy', '$ax', '$ay', '$lat', '$lng', '$note')";

    // เริ่มต้นแสดงผล HTML เพื่อให้ JavaScript ทำงานได้
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>กำลังบันทึกข้อมูล...</title>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <style>
            body { font-family: 'Sarabun', sans-serif; background-color: #f4f7f6; }
        </style>
    </head>
    <body>
    <?php
    if ($conn->query($sql)) {
        echo "<script>
            Swal.fire({
                title: 'บันทึกสำเร็จ!',
                text: 'พิกัดของคุณถูกเก็บลงฐานข้อมูลแล้ว',
                icon: 'success',
                confirmButtonColor: '#28a745',
                confirmButtonText: 'ตกลง'
            }).then((result) => {
                window.location.href = 'index.php';
            });
        </script>";
    } else {
        echo "<script>
            Swal.fire({
                title: 'เกิดข้อผิดพลาด!',
                text: 'ไม่สามารถบันทึกได้: " . addslashes($conn->error) . "',
                icon: 'error',
                confirmButtonText: 'กลับไปแก้ไข'
            }).then(() => {
                window.history.back();
            });
        </script>";
    }
    ?>
    </body>
    </html>
    <?php
} else {
    // ถ้าไม่ได้มาด้วย POST ให้ดีดกลับหน้าหลัก
    header("Location: index.php");
    exit;
}
?>