<?php
session_start();
require("dbconnect.php");

// ตรวจสอบสิทธิ์ผู้ดูแลระบบ
if (!isset($_SESSION['emp_level']) || $_SESSION['emp_level'] !== "a") {
  echo "<center>หน้าสำหรับผู้ดูแลระบบ <a href='login.php'>กรุณาเข้าสู่ระบบก่อน</a></center>";
  exit();
}

if (!isset($_SESSION["emp_id"])) {
  header("location:login.php");
  exit();
}

// ดึงข้อมูลผู้ใช้
$emp_id = $_SESSION["emp_id"];
$sqllogin = "SELECT * FROM employee WHERE emp_id = '$emp_id'";
$result = mysqli_query($con, $sqllogin);
$row = mysqli_fetch_assoc($result);
?>

<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>หน้าผู้ดูแลระบบ</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .admin-container {
      background-color: #ffffff;
      border-radius: 15px;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
      padding: 30px;
      margin-top: 50px;
    }

    .admin-header {
      font-weight: bold;
      font-size: 1.5rem;
      margin-bottom: 20px;
      color: #343a40;
    }

    .btn-add {
      margin-top: 20px;
    }

    .table td, .table th {
      vertical-align: middle;
      text-align: center;
    }

    .welcome-msg {
      font-size: 1.1rem;
    }
  </style>
</head>

<body>
  <div class="container">
    <div class="admin-container mx-auto">
      <?php require("nav_a.php"); ?>
      <div class="admin-header text-center">
        <i class='bx bxs-user-account bx-flashing'></i> ยินดีต้อนรับผู้ดูแลระบบ
      </div>

      <p class="welcome-msg text-center">
        <i class='bx bx-user-circle'></i> สวัสดีคุณ 
        <strong><?php echo $row["emp_name"]; ?></strong> (ID: <?php echo $row["emp_id"]; ?>), หน่วย: <strong><?php echo $row["emp_unit"]; ?></strong>
      </p>

      <hr>

      <table class="table table-hover table-bordered align-middle">
        <thead class="table-dark">
          <tr>
            <th>ID</th>
            <th>ชื่อ</th>
            <th>หน่วย</th>
            <th>ระดับผู้ใช้งาน</th>
            <th>จัดการ</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><?php echo $row["emp_id"]; ?></td>
            <td><?php echo $row["emp_name"]; ?></td>
            <td><?php echo $row["emp_unit"]; ?></td>
            <td><?php echo ($row["emp_level"] === "a") ? "ผู้ดูแลระบบ" : "ผู้ใช้งาน"; ?></td>
            <td>
              <a href="emp_editformdata.php?emp_id=<?php echo $row["emp_id"]; ?>" class="btn btn-warning btn-sm">
                <i class='bx bx-edit-alt'></i> แก้ไข
              </a>
            </td>
          </tr>
        </tbody>
      </table>

      <div class="text-center btn-add">
        <a href="insertform_emp.php" class="btn btn-success">
          <i class='bx bx-user-plus'></i> เพิ่มข้อมูลพนักงาน
        </a>
      </div>
    </div>
  </div>
</body>
</html>
