<?php
session_start();
require("dbconnect.php"); // ตรวจสอบให้แน่ใจว่า dbconnect.php อยู่ในโฟลเดอร์เดียวกันหรือมีการกำหนด path ที่ถูกต้อง

// ตรวจสอบสถานะการเข้าสู่ระบบและระดับผู้ใช้งาน
// หน้านี้สำหรับผู้ดูแลระบบ (emp_level = 'a') เท่านั้น
if (!isset($_SESSION['emp_level']) || $_SESSION['emp_level'] != "a") {
    echo "<center>หน้าสำหรับผู้ดูแลระบบ <a href=login.php>กรุณาเข้าสู่ระบบก่อน</a></center>";
    exit();
}

if (!isset($_SESSION["emp_id"]) || !$_SESSION["emp_id"]) {
    header("location:login.php");
    exit(); // ต้องมี exit() หลังจาก header เพื่อหยุดการทำงานของสคริปต์
}

// ดึงข้อมูลผู้ใช้งานที่เข้าสู่ระบบ (admin)
$sqllogin = "SELECT * FROM employee WHERE emp_id='" . mysqli_real_escape_string($con, $_SESSION["emp_id"]) . "'";
$result_admin_info = mysqli_query($con, $sqllogin);
$row_admin_info = mysqli_fetch_assoc($result_admin_info);

// ตรวจสอบให้แน่ใจว่ามีการเชื่อมต่อฐานข้อมูล (จาก dbconnect.php)
if (!isset($con) || !$con) {
    die("Error: Database connection not established. Check dbconnect.php");
}

// รับค่าปีที่เลือกจาก Session
$selected_year = isset($_SESSION['selected_year']) ? $_SESSION['selected_year'] : '68-69';
$year_clean = mysqli_real_escape_string($con, $selected_year);

// สร้าง Array เพื่อเก็บผลลัพธ์การนับ
$image_counts = [
    'water_image1' => 0,
    'water_image2' => 0,
    'water_image3' => 0,
    'flood_image'   => 0,
    'drought_image' => 0,
    'other_image'   => 0,
];

// Loop ผ่านแต่ละคอลัมน์รูปภาพเพื่อนับจำนวนจากทุกแปลงในระบบ กรองตามปีที่เลือก
foreach ($image_counts as $column => &$count) {
    $sql_count = "SELECT COUNT(DISTINCT plot_id) AS total_plots 
                    FROM image_water 
                    WHERE (`{$column}` IS NOT NULL AND `{$column}` != '')
                    AND year_rai = '$year_clean'";
    
    $result_count = mysqli_query($con, $sql_count);
    
    if (!$result_count) {
        die("Query Error for {$column}: " . mysqli_error($con));
    }
    
    $row_count = mysqli_fetch_assoc($result_count);
    $count = $row_count['total_plots'];
}

// ดึงจำนวนผู้ใช้งานทั้งหมดในระบบ
$sql_total_users = "SELECT COUNT(emp_id) AS total_users FROM employee WHERE emp_level = 'u'";
$result_total_users = mysqli_query($con, $sql_total_users);
$row_total_users = mysqli_fetch_assoc($result_total_users);
$total_users = $row_total_users['total_users'];

// ปิดการเชื่อมต่อฐานข้อมูล
mysqli_close($con);
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ระบบการให้น้ำอ้อย - รายงานสำหรับผู้ดูแลระบบ</title>
    <link rel="icon" href="icon/icon_login.png" type="image/x-icon"> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f4f7fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container {
            background-color: #fff;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
            margin-bottom: 30px;
        }

        .admin-header {
            font-size: 1.5rem;
            font-weight: bold;
            color: rgb(51, 50, 50);
        }

        .welcome-msg {
            font-size: 1rem;
            color: #555;
        }

        .card-custom {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            text-align: center;
        }

        .card-custom h5 {
            color: #343a40;
            margin-bottom: 15px;
        }

        .card-custom .count {
            font-size: 2.5rem;
            font-weight: bold;
        }
        .card-custom .count.water1 { color: #007bff; } /* น้ำ1 */
        .card-custom .count.water2 { color: #28a745; } /* น้ำ2 (เขียว) */
        .card-custom .count.water3 { color: #ffc107; } /* น้ำ3 (เหลือง) */
        .card-custom .count.flood { color: #dc3545; }  /* น้ำท่วม (แดง) */
        .card-custom .count.drought { color: #fd7e14; } /* แล้ง (ส้ม) */
        .card-custom .count.other { color: #6c757d; }  /* อื่นๆ (เทา) */
        .card-custom .count.total-users { color: #8a2be2; } /* ผู้ใช้ทั้งหมด (ม่วง) */


        .card-custom .icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <?php require("nav_a.php"); ?>
    <div class="container mt-5">
        <div class="text-center admin-header">
            <i class='bx bxs-bar-chart-alt-2 bx-flashing'></i> รายงานภาพรวมระบบ (สำหรับผู้ดูแลระบบ)
        </div>
        <p class="text-center welcome-msg">
            <i class='bx bx-user-circle'></i> สวัสดีคุณผู้ดูแลระบบ <strong><?php echo htmlspecialchars($row_admin_info["emp_name"]); ?></strong> (ID: <?php echo htmlspecialchars($row_admin_info["emp_id"]); ?>), หน่วย: <strong><?php echo htmlspecialchars($row_admin_info["emp_unit"]); ?></strong>
        </p>

        <hr>
        <h5 class="text-center">
            สรุปข้อมูลรูปภาพแปลงอ้อยทั่วทั้งระบบ ประจำปีการผลิต <?php echo htmlspecialchars($selected_year); ?>
        </h5>
        <hr>
        
        <div class="row">
            <div class="col-md-4 col-sm-6 mb-4">
                <div class="card-custom">
                    <i class='bx bx-water icon' style="color: #007bff;"></i>
                    <h5>แปลงที่มีรูปภาพ การให้น้ำ 1</h5>
                    <div class="count water1"><?php echo $image_counts['water_image1']; ?></div>
                    <p>แปลง</p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6 mb-4">
                <div class="card-custom">
                    <i class='bx bx-water icon' style="color: #28a745;"></i>
                    <h5>แปลงที่มีรูปภาพ การให้น้ำ 2</h5>
                    <div class="count water2"><?php echo $image_counts['water_image2']; ?></div>
                    <p>แปลง</p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6 mb-4">
                <div class="card-custom">
                    <i class='bx bx-water icon' style="color: #ffc107;"></i>
                    <h5>แปลงที่มีรูปภาพ การให้น้ำ 3</h5>
                    <div class="count water3"><?php echo $image_counts['water_image3']; ?></div>
                    <p>แปลง</p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6 mb-4">
                <div class="card-custom">
                    <i class='bx bx-cloud-rain icon' style="color: #dc3545;"></i>
                    <h5>แปลงที่มีรูปภาพ น้ำท่วม</h5>
                    <div class="count flood"><?php echo $image_counts['flood_image']; ?></div>
                    <p>แปลง</p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6 mb-4">
                <div class="card-custom">
                    <i class='bx bx-sun icon' style="color: #fd7e14;"></i>
                    <h5>แปลงที่มีรูปภาพ กระทบแล้ง</h5>
                    <div class="count drought"><?php echo $image_counts['drought_image']; ?></div>
                    <p>แปลง</p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6 mb-4">
                <div class="card-custom">
                    <i class='bx bx-image icon' style="color: #6c757d;"></i>
                    <h5>แปลงที่มีรูปภาพ อื่นๆ</h5>
                    <div class="count other"><?php echo $image_counts['other_image']; ?></div>
                    <p>แปลง</p>
                </div>
            </div>
        </div>
        <div class="text-center mt-4">
    <a href="admin_pdf_logs.php" class="btn btn-lg btn-warning shadow">
        <i class='bx bxs-file-find'></i> ตรวจสอบประวัติการสร้าง PDF (เช็คคนเหลี่ยม) 🕵️‍♂️
    </a>
</div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>