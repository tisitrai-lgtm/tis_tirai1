<?php
session_start();
require("dbconnect.php");

// ตรวจสอบสถานะการเข้าสู่ระบบและระดับผู้ใช้งาน
if (!isset($_SESSION['emp_level']) || $_SESSION['emp_level'] != "u") {
    echo "<center>หน้าสำหรับผู้ใช้งานระบบ <a href=login.php>กรุณาเข้าสู่ระบบก่อน</a></center>";
    exit();
}

if (!isset($_SESSION["emp_id"]) || !$_SESSION["emp_id"]) {
    header("location:login.php");
    exit();
}

// ดึงข้อมูลผู้ใช้งานที่เข้าสู่ระบบ
$sqllogin = "SELECT * FROM employee WHERE emp_id='" . $_SESSION["emp_id"] . "'";
$result_user_info = mysqli_query($con, $sqllogin);
$row_user_info = mysqli_fetch_assoc($result_user_info);

// --- ➕ ส่วนที่เพิ่มเข้าไป: รับค่าปีการผลิตจาก Session ---
$selected_year = isset($_SESSION['selected_year']) ? $_SESSION['selected_year'] : '';
// --------------------------------------------------

if (!isset($con) || !$con) {
    die("Error: Database connection not established. Check dbconnect.php");
}

$emp_id_current = mysqli_real_escape_string($con, $_SESSION['emp_id']);
$year_safe = mysqli_real_escape_string($con, $selected_year);

// สร้าง Array เพื่อเก็บผลลัพธ์การนับ
$image_counts = [
    'water_image1' => 0,
    'water_image2' => 0,
    'water_image3' => 0,
    'flood_image'  => 0,
    'drought_image' => 0,
    'other_image'  => 0,
];

// Loop ผ่านแต่ละคอลัมน์รูปภาพเพื่อนับจำนวน (กรองตามปีที่เลือกด้วย)
foreach ($image_counts as $column => &$count) {
    $sql_count = "SELECT COUNT(DISTINCT plot_id) AS total_plots 
                  FROM image_water 
                  WHERE emp_id = '{$emp_id_current}' 
                  AND year_rai = '{$year_safe}'
                  AND (`{$column}` IS NOT NULL AND `{$column}` != '')";
    
    $result_count = mysqli_query($con, $sql_count);
    $row_count = mysqli_fetch_assoc($result_count);
    $count = $row_count['total_plots'];
}

mysqli_close($con);
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TIS WaterSuga - รายงานจำนวนรูปภาพ</title>
    <link rel="icon" href="icon/icon_login.png" type="image/x-icon"> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        body { background-color: #f4f7fc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .container { background-color: #fff; border-radius: 15px; padding: 30px; box-shadow: 0 4px 12px rgba(63, 63, 63, 0.1); margin-top: 30px; margin-bottom: 30px; }
        .admin-header { font-size: 1.5rem; font-weight: bold; color: rgb(51, 50, 50); }
        .welcome-msg { font-size: 1rem; color: #555; }
        .card-custom { background-color: #f8f9fa; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); text-align: center; transition: 0.3s; }
        .card-custom:hover { transform: translateY(-5px); }
        .card-custom h5 { color: #343a40; margin-bottom: 15px; font-size: 1rem; }
        .card-custom .count { font-size: 2.5rem; font-weight: bold; }
        .count.water1 { color: #007bff; } 
        .count.water2 { color: #28a745; } 
        .count.water3 { color: #ffc107; } 
        .count.flood { color: #dc3545; }  
        .count.drought { color: #fd7e14; } 
        .count.other { color: #6c757d; }
        .card-custom .icon { font-size: 2.5rem; margin-bottom: 10px; }
        .year-display { background: #17a2b8; color: white; padding: 2px 12px; border-radius: 20px; font-size: 0.9rem; display: inline-block; margin-top: 5px; }
    </style>
</head>

<body>
    <?php require("nav_u.php"); ?>
<div class="content-wrapper">
    <div class="container mt-5">
        <div class="text-center admin-header">
            <i class='bx bxs-bar-chart-alt-2 bx-flashing'></i> รายงานจำนวนรูปภาพแปลงอ้อย
        </div>
        <p class="text-center welcome-msg">
            <i class='bx bx-user-circle'></i> สวัสดีคุณ <strong><?php echo htmlspecialchars($row_user_info["emp_name"]); ?></strong> (ID: <?php echo htmlspecialchars($row_user_info["emp_id"]); ?>), หน่วย: <strong><?php echo htmlspecialchars($row_user_info["emp_unit"]); ?></strong>
            <br>
            <span class="year-display">ข้อมูลปีการผลิต: <?php echo htmlspecialchars($selected_year); ?></span>
        </p>

        <hr>
        <h5 class="text-center">สรุปข้อมูลรูปภาพแปลงอ้อยตามหมวดหมู่</h5>
        <hr>

        <div class="row">
            <div class="col-md-4 col-sm-6 mb-4">
                <div class="card-custom">
                    <i class='bx bx-water icon' style="color: #007bff;"></i>
                    <h5>การให้น้ำ 1</h5>
                    <div class="count water1"><?php echo $image_counts['water_image1']; ?></div>
                    <p>แปลง</p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6 mb-4">
                <div class="card-custom">
                    <i class='bx bx-water icon' style="color: #28a745;"></i>
                    <h5>การให้น้ำ 2</h5>
                    <div class="count water2"><?php echo $image_counts['water_image2']; ?></div>
                    <p>แปลง</p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6 mb-4">
                <div class="card-custom">
                    <i class='bx bx-water icon' style="color: #ffc107;"></i>
                    <h5>การให้น้ำ 3</h5>
                    <div class="count water3"><?php echo $image_counts['water_image3']; ?></div>
                    <p>แปลง</p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6 mb-4">
                <div class="card-custom">
                    <i class='bx bx-cloud-rain icon' style="color: #dc3545;"></i>
                    <h5>น้ำท่วม</h5>
                    <div class="count flood"><?php echo $image_counts['flood_image']; ?></div>
                    <p>แปลง</p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6 mb-4">
                <div class="card-custom">
                    <i class='bx bx-sun icon' style="color: #fd7e14;"></i>
                    <h5>กระทบแล้ง</h5>
                    <div class="count drought"><?php echo $image_counts['drought_image']; ?></div>
                    <p>แปลง</p>
                </div>
            </div>
            <div class="col-md-4 col-sm-6 mb-4">
                <div class="card-custom">
                    <i class='bx bx-image icon' style="color: #6c757d;"></i>
                    <h5>อื่นๆ</h5>
                    <div class="count other"><?php echo $image_counts['other_image']; ?></div>
                    <p>แปลง</p>
                </div>
            </div>
        </div>
    </div>
  </div>  
    <?php include 'nav_u_footer.php'; ?>
</body>
</html>