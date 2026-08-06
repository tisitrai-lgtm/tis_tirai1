<?php
session_start();
require('dbconnect.php');

// ตรวจสอบสิทธิ์ Admin ก่อนแสดงผลใดๆ ทั้งสิ้น
if (!isset($_SESSION['emp_level']) || $_SESSION['emp_level'] !== "a") {
    header("Location: login.php");
    exit();
}

$emp_data = $_POST["emp_data"] ?? '';

// ป้องกัน SQL Injection ด้วย prepared statement
$sql = "SELECT * FROM employee WHERE emp_name LIKE ? OR emp_surname LIKE ? ORDER BY emp_name ASC";
$stmt = mysqli_prepare($con, $sql);
$like = "%{$emp_data}%";
mysqli_stmt_bind_param($stmt, "ss", $like, $like);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$count = mysqli_num_rows($result);
$order = 1;
?>

<!doctype html>
<html lang="en">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-F3w7mX95PdgyTmZZMECAngseQB83DfGTowi0iMjiWaeVhAn4FJkqJByhZMI3AhiU" crossorigin="anonymous">

  <title>รายชื่อพนักงานทั้งหมด</title>
</head>

<body>
  <div class="container">
    <h1 class="text-center mt-3">รายชื่อพนักงานที่ค้นหา</h1>
    <form action="emp_searchdata.php" class="form-group my-3" method="POST">
      <div class="row">
        <div class="col-6">
          <input type="text" placeholder="กรอกชื่อหรือนามสกุลที่ต้องการค้น" class="form-control" name="emp_data" value="<?php echo htmlspecialchars($emp_data); ?>" required>
        </div>
        <div class="col-6">
          <input type="submit" value="ค้นหาข้อมูล" class="btn btn-info">
        </div>
      </div>

    </form>
    <?php if ($count > 0) { ?>
      <table class="table table-bordered">
        <thead class="table-dark">
          <tr>
            <th>#</th>
            <th>คำนำหน้า</th>
            <th>ชื่อ</th>
            <th>สกุล</th>
            <th>วันเกิด</th>
            <th>ที่อยู่ปัจจุบัน</th>
            <th>ทักษะความสามารถ</th>
            <th>เบอร์โทรศัพท์</th>
            <th>แก้ไข</th>
            <th>ลบ</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = mysqli_fetch_assoc($result)) {
          ?>
            <tr>
              <td><?php echo $order++; ?></td>
              <td><?php echo htmlspecialchars($row["emp_title"] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($row["emp_name"] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($row["emp_surname"] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($row["emp_birthday"] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($row["emp_adr"] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($row["emp_skill"] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($row["emp_tel"] ?? ''); ?></td>
              <!--แก้ไขข้อมูล-->
              <td><a href="emp_editformdata.php?emp_id=<?php echo urlencode($row["emp_id"]) ?>" class="btn btn-success">แก้ไข</a></td>

              <!--ลบข้อมูล-->
              <td><a href="emp_deletedata.php?emp_id=<?php echo urlencode($row["emp_id"]) ?>" class="btn btn-danger" onclick="return confirm('ยืนยันการลบข้อมูล')">ลบ</a></td>

            </tr>
          <?php } ?>
        </tbody>
      </table>
    <?php } else { ?>
      <div class="alert alert-danger">
        <b>ไม่พบข้อมูลพนักงาน!!</b>
      </div>
    <?php } ?>
    <a href="admin_page.php" class="btn btn-success">กลับหน้าแรก</a>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-/bQdsTh/da6pkI1MST/rWKFNjaCP5gBSY4sEBT38Q/9RBh9AH40zEOg7Hlq2THRZ" crossorigin="anonymous"></script>
</body>

</html>