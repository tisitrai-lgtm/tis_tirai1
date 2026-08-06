<?php
session_start();
require("dbconnect.php");

// ตรวจสอบสิทธิ์ผู้ดูแลระบบ (ไฟล์นี้ไม่เคยมีการเช็คสิทธิ์มาก่อนเลย)
if (!isset($_SESSION['emp_level']) || $_SESSION['emp_level'] !== "a") {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['plot_id']) || !isset($_GET['year_rai'])) {
  echo "<div class='text-center mt-5 text-danger'>ไม่พบข้อมูลที่ต้องการแก้ไข</div>";
  exit();
}

$plot_id = $_GET['plot_id'];
$year_rai_get = $_GET['year_rai'];

// ใช้ Prepared Statement แทนการแปะ $_GET ลง SQL ตรงๆ (ของเดิมเสี่ยง SQL Injection)
// และกรองด้วย plot_id + year_rai คู่กันเสมอ เพราะ plot_id เพียงอย่างเดียวไม่ใช่ค่าที่ไม่ซ้ำ
$sql = "SELECT * FROM image_water WHERE plot_id = ? AND year_rai = ?";
$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, "ss", $plot_id, $year_rai_get);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if (mysqli_num_rows($result) == 0) {
  echo "<div class='text-center mt-5 text-danger'>ไม่พบข้อมูลที่ต้องการแก้ไข</div>";
  exit();
}
$data = mysqli_fetch_assoc($result);
$current_status = $data['join_status'] ?? 'join';

// แปลงวันที่จาก DB (Y-m-d H:i:s หรือ Y-m-d) ให้เป็นรูปแบบที่ input type=date ใช้ได้ (yyyy-mm-dd)
function toDateInputValue($dateStr) {
  if (empty($dateStr) || $dateStr === '0000-00-00') return '';
  $ts = strtotime($dateStr);
  return $ts ? date('Y-m-d', $ts) : '';
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>แก้ไขข้อมูลการให้น้ำ</title>
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
    .preview-img, .existing-img {
      max-height: 140px;
      margin-top: 10px;
      border-radius: 8px;
      border: 1px solid #ddd;
    }
    .btn-delete-img {
      margin-top: 5px;
      font-size: 0.9rem;
    }
    .form-section-title {
      font-size: 1.25rem;
      color: #0d6efd;
      margin-top: 30px;
      margin-bottom: 15px;
    }
    .join-status-box {
      background-color: #f8f9fa;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 15px 20px;
    }
    .join-status-box .form-check-input {
      width: 1.3em;
      height: 1.3em;
      cursor: pointer;
    }
    .join-status-box .form-check-label {
      cursor: pointer;
      font-weight: 600;
    }
  </style>
</head>
<body>

<div class="form-container">
  <div class="form-title">
    <i class="bi bi-pencil-square"></i> แก้ไขข้อมูลการให้น้ำแปลงอ้อย
  </div>

  <form action="water_updatedata.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="plot_id" value="<?= htmlspecialchars($data['plot_id']) ?>">
    <input type="hidden" name="original_year_rai" value="<?= htmlspecialchars($data['year_rai']) ?>">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-calendar-week"></i> ปีการให้ข้อมูล</label>
        <select name="year_rai" class="form-select" required>
          <option <?= $data['year_rai'] == "68-69" ? "selected" : "" ?>>68-69</option>
          <option <?= $data['year_rai'] == "69-70" ? "selected" : "" ?>>69-70</option>
          <option <?= $data['year_rai'] == "70-71" ? "selected" : "" ?>>70-71</option>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-person-fill"></i> รหัส นสส</label>
        <input type="text" name="emp_id" class="form-control" value="<?= htmlspecialchars($data['emp_id']) ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-pin-fill"></i> ไอดีแปลง</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($data['plot_id']) ?>" required disabled>
      </div>
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-file-earmark-text"></i> เลขที่สัญญา</label>
        <input type="text" name="contract_number" class="form-control" value="<?= htmlspecialchars($data['contract_number']) ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-person-badge"></i> โควต้า</label>
        <input type="text" name="quota" class="form-control" value="<?= htmlspecialchars($data['quota']) ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-geo-fill"></i> จำนวนไร่</label>
        <input type="text" name="area_rai" class="form-control" value="<?= htmlspecialchars($data['area_rai']) ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-tree-fill"></i> ชนิดอ้อย</label>
        <input type="text" name="suga_type" class="form-control" value="<?= htmlspecialchars($data['suga_type']) ?>" required>
      </div>
    </div>

    <div class="form-section-title">
      <i class="bi bi-check2-square"></i> สถานะการเข้าร่วมโครงการ
    </div>
    <div class="join-status-box">
      <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="join_status" id="radioJoin" value="join" <?= $current_status === 'join' ? 'checked' : '' ?>>
        <label class="form-check-label" for="radioJoin">เข้าร่วม</label>
      </div>
      <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="join_status" id="radioNotJoin" value="notjoin" <?= $current_status === 'notjoin' ? 'checked' : '' ?>>
        <label class="form-check-label" for="radioNotJoin">ไม่เข้าร่วม</label>
      </div>
    </div>

    <div class="form-section-title">
      <i class="bi bi-person-vcard"></i> ข้อมูลเจ้าของแปลง / ที่อยู่
    </div>
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-credit-card-2-front"></i> เลขบัตรประชาชน</label>
        <input type="text" name="citizen_id" class="form-control" maxlength="13" pattern="\d{13}" inputmode="numeric" value="<?= htmlspecialchars($data['citizen_id'] ?? '') ?>" placeholder="กรอกเลข 13 หลัก">
      </div>
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-house-door"></i> บ้านเลขที่</label>
        <input type="text" name="house_no" class="form-control" maxlength="50" value="<?= htmlspecialchars($data['house_no'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label"><i class="bi bi-signpost-split"></i> ตำบล</label>
        <input type="text" name="sub_district" class="form-control" maxlength="100" value="<?= htmlspecialchars($data['sub_district'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label"><i class="bi bi-signpost"></i> อำเภอ</label>
        <input type="text" name="district" class="form-control" maxlength="100" value="<?= htmlspecialchars($data['district'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label"><i class="bi bi-map"></i> จังหวัด</label>
        <input type="text" name="province" class="form-control" maxlength="100" value="<?= htmlspecialchars($data['province'] ?? '') ?>">
      </div>
      <div class="col-md-12">
        <label class="form-label"><i class="bi bi-droplet-half"></i> แหล่งน้ำ</label>
        <input type="text" name="water_source" class="form-control" maxlength="255" value="<?= htmlspecialchars($data['water_source'] ?? '') ?>" placeholder="เช่น คลองชลประทาน / บ่อบาดาล / แม่น้ำ ฯลฯ">
      </div>
    </div>

    <div class="form-section-title">
      <i class="bi bi-clock-history"></i> วิธีและวันที่ให้น้ำ
    </div>
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-droplet"></i> วิธีการให้น้ำ ครั้งที่ 1</label>
        <input type="text" name="water_method1" class="form-control" maxlength="50" value="<?= htmlspecialchars($data['water_method1'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-calendar-check"></i> วันที่ให้น้ำ ครั้งที่ 1</label>
        <input type="date" name="water_date1" class="form-control" value="<?= toDateInputValue($data['water_date1'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-droplet"></i> วิธีการให้น้ำ ครั้งที่ 2</label>
        <input type="text" name="water_method2" class="form-control" maxlength="50" value="<?= htmlspecialchars($data['water_method2'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-calendar-check"></i> วันที่ให้น้ำ ครั้งที่ 2</label>
        <input type="date" name="water_date2" class="form-control" value="<?= toDateInputValue($data['water_date2'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-droplet"></i> วิธีการให้น้ำ ครั้งที่ 3</label>
        <input type="text" name="water_method3" class="form-control" maxlength="50" value="<?= htmlspecialchars($data['water_method3'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label"><i class="bi bi-calendar-check"></i> วันที่ให้น้ำ ครั้งที่ 3</label>
        <input type="date" name="water_date3" class="form-control" value="<?= toDateInputValue($data['water_date3'] ?? '') ?>">
      </div>
    </div>

    <div class="form-section-title">
      <i class="bi bi-images"></i> แก้ไขรูปภาพแปลงอ้อย
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
        $img_path = $data[$name];
        echo <<<HTML
        <div class="col-md-6">
          <label class="form-label"><i class="bi bi-camera"></i> $label</label>
          <input type="file" name="$name" class="form-control" accept="image/*" onchange="previewImage(this)">
HTML;
        if (!empty($img_path) && file_exists($img_path)) {
          $img_path_safe = htmlspecialchars($img_path);
          $plot_id_safe = htmlspecialchars($data['plot_id'], ENT_QUOTES);
          $year_rai_safe = htmlspecialchars($data['year_rai'], ENT_QUOTES);
          $name_safe = htmlspecialchars($name, ENT_QUOTES);
          echo <<<HTML
            <img class="existing-img" src="$img_path_safe"><br>
            <a href="water_updatedata.php?delete_image=$name_safe&plot_id=$plot_id_safe&year_rai=$year_rai_safe" 
               class="btn btn-outline-danger btn-sm btn-delete-img"
               onclick="return confirm('คุณแน่ใจว่าต้องการลบภาพนี้?')">
              <i class="bi bi-trash"></i> ลบรูปภาพนี้
            </a>
HTML;
        }
        echo '<img class="preview-img d-none" src="#" alt="Preview">';
        echo '</div>';
      }
      ?>
    </div>

    <div class="text-center mt-4">
      <button type="submit" class="btn btn-primary">
        <i class="bi bi-save2"></i> บันทึกการแก้ไข
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
      const preview = input.parentElement.querySelector('.preview-img');

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