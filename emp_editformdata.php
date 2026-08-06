<?php
session_start();
require('dbconnect.php');

// ตรวจสอบสิทธิ์ Admin ก่อนแสดงผลใดๆ ทั้งสิ้น
if (!isset($_SESSION['emp_level']) || $_SESSION['emp_level'] !== "a") {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['emp_id'])) {
    header("Location: emp_page.php");
    exit();
}

// ป้องกัน SQL Injection ด้วย prepared statement
$emp_id = $_GET['emp_id'];
$sql = "SELECT * FROM employee WHERE emp_id = ?";
$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, "s", $emp_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    header("Location: emp_page.php");
    exit();
}

$row = mysqli_fetch_assoc($result);
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>แก้ไขข้อมูลพนักงาน</title>
  <link rel="icon" href="icon/icon_login.png" type="image/x-icon"> 
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f0f8ff;
      color: #333;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .container {
      background-color: #ffffff;
      padding: 40px;
      border-radius: 15px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
      max-width: 600px;
      width: 100%;
    }

    h2 {
      color: #000;
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 25px;
    }

    label {
      font-weight: 600;
      margin-bottom: 5px;
    }

    .form-control:focus {
      border-color: #007bff;
      box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
    }

    .btn-group {
      display: flex;
      justify-content: space-between;
      gap: 10px;
      margin-top: 25px;
    }

    .btn {
      width: 100%;
    }
  </style>
</head>
<body>

  <div class="container">
    <h2 class="text-center">แก้ไขข้อมูลพนักงาน</h2>
    <hr>

    <form action="emp_updatedata.php" method="POST">

      <input type="hidden" name="ID" value="<?php echo htmlspecialchars($row['ID']); ?>">

      <div class="mb-3">
        <label for="emp_id">รหัสไอดี :</label>
        <input type="text" id="emp_id" name="emp_id" class="form-control" value="<?php echo htmlspecialchars($row['emp_id']); ?>" required maxlength="7" readonly>
      </div>

      <div class="mb-3">
        <label for="emp_pass">รหัสผ่าน :</label>
        <input type="password" id="emp_pass" name="emp_pass" class="form-control" placeholder="กรุณากรอกรหัสผ่านใหม่ถ้าต้องการเปลี่ยน">
      </div>

      <div class="mb-3">
        <label for="emp_name">ชื่อ :</label>
        <input type="text" id="emp_name" name="emp_name" class="form-control" value="<?php echo htmlspecialchars($row['emp_name']); ?>" required>
      </div>

    <div class="form-group mb-3">
         <?php
                $selected_promotion_unit = $row["emp_unit"]; 
         ?>
    <label for="emp_unit">หน่วยส่งเสริม</label>    
    <select class="form-control" id="emp_unit" name="emp_unit">
        <option value="">--Select--</option> 
        <option value="บางขลัง" <?= $selected_promotion_unit == "บางขลัง" ? "selected" : "" ?>>บางขลัง</option>
        <option value="ทุ่งเสลี่ยม" <?= $selected_promotion_unit == "ทุ่งเสลี่ยม" ? "selected" : "" ?>>ทุ่งเสลี่ยม</option>
        <option value="ตลิ่งชัน" <?= $selected_promotion_unit == "ตลิ่งชัน" ? "selected" : "" ?>>ตลิ่งชัน</option>
        <option value="ศรีสัชนาลัย" <?= $selected_promotion_unit == "ศรีสัชนาลัย" ? "selected" : "" ?>>ศรีสัชนาลัย</option>
        <option value="ท่าชัยใต้ 1" <?= $selected_promotion_unit == "ท่าชัยใต้" ? "selected" : "" ?>>ท่าชัยใต้ 1</option>
        <option value="ท่าชัยใต้ 2" <?= $selected_promotion_unit == "ท่าชัยใต้" ? "selected" : "" ?>>ท่าชัยใต้ 2</option>
        <option value="ท่าชัย" <?= $selected_promotion_unit == "ท่าชัย" ? "selected" : "" ?>>ท่าชัย</option>
        <option value="ท่าชัยเหนือ" <?= $selected_promotion_unit == "ท่าชัยเหนือ" ? "selected" : "" ?>>ท่าชัยเหนือ</option>
        <option value="ศรีนครเหนือ" <?= $selected_promotion_unit == "ศรีนครเหนือ" ? "selected" : "" ?>>ศรีนครเหนือ</option>
        <option value="ศรีนครใต้" <?= $selected_promotion_unit == "ศรีนครใต้" ? "selected" : "" ?>>ศรีนครใต้</option>
        <option value="สวรรคโลก" <?= $selected_promotion_unit == "สวรรคโลก" ? "selected" : "" ?>>สวรรคโลก</option>
        <option value="ชัยคีรี" <?= $selected_promotion_unit == "ชัยคีรี" ? "selected" : "" ?>>ชัยคีรี</option>
        <option value="เขาหลวง 1" <?= $selected_promotion_unit == "เขาหลวง" ? "selected" : "" ?>>เขาหลวง 1</option>
        <option value="เขาหลวง 2" <?= $selected_promotion_unit == "เขาหลวง" ? "selected" : "" ?>>เขาหลวง 2</option>
        <option value="คีรีมาศ" <?= $selected_promotion_unit == "คีรีมาศ" ? "selected" : "" ?>>คีรีมาศ</option>
        <option value="ศรีสำโรง" <?= $selected_promotion_unit == "ศรีสำโรง" ? "selected" : "" ?>>ศรีสำโรง</option>
        <option value="วัดโบสถ์" <?= $selected_promotion_unit == "วัดโบสถ์" ? "selected" : "" ?>>วัดโบสถ์</option>
        <option value="พรหมพิราม" <?= $selected_promotion_unit == "พรหมพิราม" ? "selected" : "" ?>>พรหมพิราม</option>
        <option value="หนองตม" <?= $selected_promotion_unit == "หนองตม" ? "selected" : "" ?>>หนองตม</option>
        <option value="พิชัย" <?= $selected_promotion_unit == "พิชัย" ? "selected" : "" ?>>พิชัย</option>
        <option value="เมือง" <?= $selected_promotion_unit == "เมือง" ? "selected" : "" ?>>เมือง</option>
        <option value="ท่าปลา" <?= $selected_promotion_unit == "ท่าปลา" ? "selected" : "" ?>>ท่าปลา</option>
        <option value="น้ำอ่าง" <?= $selected_promotion_unit == "น้ำอ่าง" ? "selected" : "" ?>>น้ำอ่าง</option>
        <option value="ชาตตระการ" <?= $selected_promotion_unit == "ชาตตระการ" ? "selected" : "" ?>>ชาตตระการ</option>
        <option value="บ่อทอง" <?= $selected_promotion_unit == "บ่อทอง" ? "selected" : "" ?>>บ่อทอง</option>
        <option value="แพร่" <?= $selected_promotion_unit == "แพร่" ? "selected" : "" ?>>แพร่</option>
        <option value="แพร่" <?= $selected_promotion_unit == "ตาก" ? "selected" : "" ?>>ตาก</option>
        <option value="แพร่" <?= $selected_promotion_unit == "น้ำปาด" ? "selected" : "" ?>>น้ำปาด</option>
        </select>
       </div>

      <div class="mb-3">
        <label for="emp_level">ระดับผู้ใช้งาน :</label>
        <select id="emp_level" name="emp_level" class="form-control" required>
          <option value="a" <?php echo ($row['emp_level'] == 'a') ? 'selected' : ''; ?>>ผู้ดูแลระบบ</option>
          <option value="u" <?php echo ($row['emp_level'] == 'u') ? 'selected' : ''; ?>>ผู้ใช้งาน</option>
        </select>
      </div>

      <div class="btn-group">
        <input type="submit" value="บันทึกข้อมูล" class="btn btn-success">        
        <!-- ปุ่มยกเลิก -->
        <button type="button" class="btn btn-secondary" onclick="window.location.href='emp_page.php';">ยกเลิก</button>
      </div>
    </form>
  </div>

</body>
</html>