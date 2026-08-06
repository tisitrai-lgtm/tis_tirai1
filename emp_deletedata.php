<?php
session_start();
require("dbconnect.php");

// ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['emp_level']) || $_SESSION['emp_level'] !== "a") {
    echo "<center>หน้าสำหรับผู้ดูแลระบบ <a href='login.php'>กรุณาเข้าสู่ระบบก่อน</a></center>";
    exit();
}

// บังคับให้ต้องเป็น POST เท่านั้น (กัน CSRF ผ่านลิงก์ GET)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: emp_page.php');
    exit();
}

// ตรวจสอบว่ามี emp_id ส่งมาหรือไม่
if (!isset($_POST['emp_id'])) {
    header('Location: emp_page.php');
    exit();
}

$emp_id_to_delete = $_POST['emp_id'];
$message = "";
$icon = "";
$status_class = "";

// ป้องกันการลบตัวเอง
if ($emp_id_to_delete === $_SESSION['emp_id']) {
    $message = "ไม่สามารถลบบัญชีของตัวเองได้!";
    $icon = "bi-exclamation-triangle-fill";
    $status_class = "error-icon";
} else {
    // ใช้ Prepared Statement เพื่อความปลอดภัย
    $sql = "DELETE FROM employee WHERE emp_id = ?";
    $stmt = mysqli_prepare($con, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $emp_id_to_delete);
        if (mysqli_stmt_execute($stmt)) {
            $message = "ลบข้อมูลพนักงานเรียบร้อยแล้ว!";
            $icon = "bi-check-circle-fill";
            $status_class = "success-icon";
        } else {
            $message = "เกิดข้อผิดพลาดในการลบข้อมูล!";
            $icon = "bi-x-circle-fill";
            $status_class = "error-icon";
        }
        mysqli_stmt_close($stmt);
    } else {
        $message = "เกิดข้อผิดพลาดในการเตรียมคำสั่ง!";
        $icon = "bi-x-circle-fill";
        $status_class = "error-icon";
    }
}

mysqli_close($con);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สถานะการลบข้อมูล</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background: #f8f9fa; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .modal-content { border-radius: 15px; padding: 30px; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .status-icon { font-size: 48px; }
        .success-icon { color: #198754; }
        .error-icon { color: #dc3545; }
    </style>
</head>
<body>
    <div class="modal show d-block" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div>
                    <i class="bi <?php echo htmlspecialchars($icon); ?> status-icon <?php echo htmlspecialchars($status_class); ?>"></i>
                </div>
                <h5 class="mt-3"><?php echo htmlspecialchars($message); ?></h5>
                <p class="text-muted small">กำลังกลับไปหน้าจัดการพนักงาน...</p>
            </div>
        </div>
    </div>

    <script>
        setTimeout(function() {
            window.location.href = "emp_page.php";
        }, 2500);
    </script>
</body>
</html>