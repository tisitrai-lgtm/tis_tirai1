<?php
session_start();
require("dbconnect.php");

// ตรวจสอบสิทธิ์ผู้ดูแลระบบ (เดิมไม่มีการเช็คเลย ใครก็เข้าดูฟอร์มนี้ได้โดยไม่ต้องล็อกอิน)
if (!isset($_SESSION['emp_level']) || $_SESSION['emp_level'] !== "a") {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>เพิ่มข้อมูลการให้น้ำ</title>
  <link rel="icon" href="icon/icon_login.png" type="image/x-icon"> 
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(to right, #cce5ff, #e6f2ff);
      font-family: 'Segoe UI', sans-serif;
    }
    .form-container {
      max-width: 1000px;
      margin: 30px auto;
      background-color: #ffffff;
      padding: 30px;
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }
    .form-title {
      font-size: 1.8rem;
      font-weight: bold;
      margin-bottom: 30px;
      color: #007bff;
      text-align: center;
    }
    .form-label i {
      color: #0d6efd;
      margin-right: 6px;
    }
    .preview-img {
      max-height: 140px;
      margin-top: 10px;
      border-radius: 8px;
      border: 1px solid #ddd;
    }
    .btn-primary {
      background-color: #007bff;
      border: none;
      padding: 12px 30px;
      font-size: 1.1rem;
      border-radius: 12px;
    }
    .btn-primary:hover {
      background-color: #0056b3;
    }
    .form-section-title {
      font-size: 1.25rem;
      color: #0d6efd;
      margin-top: 30px;
      margin-bottom: 15px;
    }
    @media (max-width: 576px) {
      .form-title {
        font-size: 1.5rem;
      }
      .form-container {
        padding: 20px;
        margin: 20px 10px;
      }
      .btn-primary {
        width: 100%;
        font-size: 1rem;
      }
      .preview-img {
        max-width: 100%;
        height: auto;
      }
    }
  </style>
</head>
<body>

<div class="form-container">
  <div class="form-title">
    <i class="bi bi-cloud-upload-fill"></i> เพิ่มข้อมูลการให้น้ำแปลงอ้อย
  </div>
  <form action="water_insertdata.php" method="POST" enctype="multipart/form-data">
    <div class="row g-3">
    <div class="col-md-6">
  <label class="form-label"><i class="bi bi-calendar-week"></i> ปีการให้ข้อมูล</label>
  <select name="year_rai" class="form-select" required>
    <option value="" selected disabled>-- กรุณาเลือกปี --</option>
    
    <option value="68-69">68-69</option>
    <option value="69-70">69-70</option>
    <option value="70-71">70-71</option>
  </select>
</div>
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-person-fill"></i> รหัส นสส</label>
        <input type="text" name="emp_id" class="form-control" required>
      </div>
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi bi-pin-fill"></i> ไอดีแปลง </label>
        <input type="text" name="plot_id" class="form-control" required>
      </div>
      
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-file-earmark-text"></i> เลขที่สัญญา</label>
        <input type="text" name="contract_number" class="form-control" required>
      </div>
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-person-badge"></i> โควต้า</label>
        <input type="text" name="quota" class="form-control" required>
      </div>
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-geo-fill"></i> จำนวนไร่</label>
        <input type="text" name="area_rai" class="form-control" required>
      </div>
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-tree-fill"></i> ชนิดอ้อย</label>
        <input type="text" name="suga_type" class="form-control" required>
      </div>
    </div>

    <div class="form-section-title">
      <i class="bi bi-person-vcard"></i> ข้อมูลเจ้าของแปลง / ที่อยู่
    </div>
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-credit-card-2-front"></i> เลขบัตรประชาชน</label>
        <input type="text" name="citizen_id" class="form-control" maxlength="13" pattern="\d{13}" inputmode="numeric" placeholder="กรอกเลข 13 หลัก">
      </div>
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-house-door"></i> บ้านเลขที่</label>
        <input type="text" name="house_no" class="form-control" maxlength="50">
      </div>
      <div class="col-md-4">
        <label class="form-label"><i class="bi bi-signpost-split"></i> ตำบล</label>
        <input type="text" name="sub_district" class="form-control" maxlength="100">
      </div>
      <div class="col-md-4">
        <label class="form-label"><i class="bi bi-signpost"></i> อำเภอ</label>
        <input type="text" name="district" class="form-control" maxlength="100">
      </div>
      <div class="col-md-4">
        <label class="form-label"><i class="bi bi-map"></i> จังหวัด</label>
        <input type="text" name="province" class="form-control" maxlength="100">
      </div>
      <div class="col-md-12">
        <label class="form-label"><i class="bi bi-droplet-half"></i> แหล่งน้ำ</label>
        <input type="text" name="water_source" class="form-control" maxlength="255" placeholder="เช่น คลองชลประทาน / บ่อบาดาล / แม่น้ำ ฯลฯ">
      </div>
    </div>

    <div class="form-section-title">
      <i class="bi bi-clock-history"></i> วิธีและวันที่ให้น้ำ
    </div>
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-droplet"></i> วิธีการให้น้ำ ครั้งที่ 1</label>
        <input type="text" name="water_method1" class="form-control" maxlength="50">
      </div>
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-calendar-check"></i> วันที่ให้น้ำ ครั้งที่ 1</label>
        <input type="date" name="water_date1" class="form-control">
      </div>
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-droplet"></i> วิธีการให้น้ำ ครั้งที่ 2</label>
        <input type="text" name="water_method2" class="form-control" maxlength="50">
      </div>
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-calendar-check"></i> วันที่ให้น้ำ ครั้งที่ 2</label>
        <input type="date" name="water_date2" class="form-control">
      </div>
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-droplet"></i> วิธีการให้น้ำ ครั้งที่ 3</label>
        <input type="text" name="water_method3" class="form-control" maxlength="50">
      </div>
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-calendar-check"></i> วันที่ให้น้ำ ครั้งที่ 3</label>
        <input type="date" name="water_date3" class="form-control">
      </div>
    </div>

    <div class="form-section-title">
      <i class="bi bi-images"></i> รูปภาพการให้น้ำและสภาพแปลง
    </div>
    <div class="row g-3">
      <?php
      $images = [
        "water_image1" => "การให้น้ำ ครั้งที่ 1",
        "water_image2" => "การให้น้ำ ครั้งที่ 2",
        "water_image3" => "การให้น้ำ ครั้งที่ 3",
        "flood_image" => "น้ำท่วม",
        "drought_image" => "แล้ง",
        "other_image" => "อื่นๆ"
      ];

      foreach ($images as $name => $label) {
        echo <<<HTML
        <div class="col-md-6">
          <label class="form-label"><i class="bi bi-camera"></i> $label</label>
          <input type="file" name="$name" class="form-control" accept="image/*" onchange="previewImage(this)">
          <img class="preview-img d-none" src="#" alt="Preview">
        </div>
        HTML;
      }
      ?>
    </div>

    <div class="text-center mt-4">
      <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-circle"></i> บันทึกข้อมูล
      </button>
      <a href="admin_page.php" class="btn btn-secondary ms-2">
        <i class="bi bi-arrow-left-circle"></i> กลับหน้าผู้ดูแลระบบ
      </a>
    </div>
  </form>
</div>

<script>
  function previewImage(input) {
    const file = input.files[0];
    if (file) {
      const reader = new FileReader();
      const preview = input.nextElementSibling;

      reader.onload = function (e) {
        preview.src = e.target.result;
        preview.classList.remove("d-none");
      }
      reader.readAsDataURL(file);
    }
  }
</script>

</body>
</html>