<!doctype html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>บันทึกข้อมูลพนักงาน</title>
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
    <h2 class="text-center">เพิ่มข้อมูลพนักงาน</h2>
    <hr>
    <form action="insertdata_emp.php" method="POST">

      <div class="mb-3">
        <label for="emp_id">รหัสไอดี :</label>
        <input type="text" id="emp_id" name="emp_id" class="form-control" required
                maxlength="7"
                oninput="this.value = this.value.replace(/\s/g, '')">
      </div>

      <div class="mb-3">
        <label for="emp_pass">รหัสผ่าน :</label>
        <input type="password" id="emp_pass" name="emp_pass" class="form-control" required
                oninput="this.value = this.value.replace(/\s/g, '')">
      </div>

      <div class="mb-3">
        <label for="emp_name">ชื่อ :</label>
        <input type="text" id="emp_name" name="emp_name" class="form-control" required>
      </div>
      <div class="form-group mb-3">
                <label for="emp_unit">หน่วย</label>    
                <select class="form-control" id="emp_unit" name="emp_unit" placeholder="ยังไม่ได้กรอกข้อมูล"required>
                    <option value="" disabled selected>--Select--</option> 
                    <option value="บางขลัง">บางขลัง</option> 
                    <option value="ทุ่งเสลี่ยม">ทุ่งเสลี่ยม</option>
                    <option value="ตลิ่งชัน">ตลิ่งชัน</option>
                    <option value="ศรีสัชนาลัย">ศรีสัชนาลัย</option>
                    <option value="ท่าชัยใต้ 1">ท่าชัยใต้ 1</option>
                    <option value="ท่าชัยใต้ 2">ท่าชัยใต้ 2</option>
                    <option value="ท่าชัย">ท่าชัย</option>
                    <option value="ท่าชัยเหนือ">ท่าชัยเหนือ</option>
                    <option value="ศรีนครเหนือ">ศรีนครเหนือ</option>
                    <option value="ศรีนครใต้">ศรีนครใต้</option>
                    <option value="สวรรคโลก">สวรรคโลก</option>
                    <option value="ชัยคีรี">ชัยคีรี</option>
                    <option value="เขาหลวง 1">เขาหลวง 1</option>
                    <option value="เขาหลวง 2">เขาหลวง 2</option>
                    <option value="คีรีมาศ">คีรีมาศ</option>
                    <option value="ศรีสำโรง">ศรีสำโรง</option>
                    <option value="วัดโบสถ์">วัดโบสถ์</option>
                    <option value="พรหมพิราม">พรหมพิราม</option>
                    <option value="หนองตม">หนองตม</option>
                    <option value="พิชัย">พิชัย</option>
                    <option value="เมือง">เมือง</option>
                    <option value="ท่าปลา">ท่าปลา</option>
                    <option value="น้ำอ่าง">น้ำอ่าง</option>
                    <option value="ชาตตระการ">ชาตตระการ</option>
                    <option value="บ่อทอง">บ่อทอง</option>
                    <option value="แพร่">แพร่</option>
                    <option value="ตาก">ตาก</option>
                    <option value="น้ำปาด">น้ำปาด</option>
                </select>
       </div>
      <div class="mb-3">
        <label for="emp_level">ระดับผู้ใช้งาน :</label>
        <select id="emp_level" name="emp_level" class="form-control" required>
          <option value="" disabled selected>-- เลือกระดับผู้ใช้งาน --</option>
          <option value="a">ผู้ดูแลระบบ</option>
          <option value="u">ผู้ใช้งาน</option>
        </select>
      </div>

      <div class="btn-group">
        <input type="submit" value="บันทึกข้อมูล" class="btn btn-success">
        <input type="reset" value="ล้างข้อมูล" class="btn btn-danger">
        <a href="emp_page.php" class="btn btn-primary">กลับก่อนหน้า</a>
      </div>
    </form>
  </div>

</body>
</html>
