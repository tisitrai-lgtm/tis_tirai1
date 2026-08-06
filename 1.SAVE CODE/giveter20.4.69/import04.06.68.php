<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ini_set('max_execution_time', 600); // 10 นาที
    ini_set('memory_limit', '1024M');   // 1 GB

    require_once 'SpreadsheetReader.php';
    require_once 'SpreadsheetReader_XLSX.php';
    require_once 'SpreadsheetReader_CSV.php';
    require_once 'SpreadsheetReader_XLS.php';
    require("dbconnect.php");

    if (isset($_FILES['excel']['name'])) {
        $file_name = $_FILES['excel']['name'];
        $file_tmp = $_FILES['excel']['tmp_name'];

        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }

        $target_path = 'uploads/' . $file_name;

        if (move_uploaded_file($file_tmp, $target_path)) {
            $reader = new SpreadsheetReader($target_path);
            $inserted = 0;
            $skipped = 0;

            foreach ($reader as $key => $row) {
                if ($key == 0) continue; // ข้ามหัวตาราง

                $year_rai = trim($row[0] ?? '');
                $plot_id = trim($row[1] ?? '');
                $emp_id = trim($row[2] ?? '');
                $contract_number = trim($row[3] ?? '');
                $quota = trim($row[4] ?? '');
                $area_rai = trim($row[5] ?? '');
                $suga_type = trim($row[6] ?? '');

                if (empty($plot_id)) {
                    $skipped++;
                    continue;
                }

                $check = mysqli_query($con, "SELECT plot_id FROM image_water WHERE plot_id = '$plot_id'");
                if (mysqli_num_rows($check) > 0) {
                    $skipped++;
                    continue;
                }

                $sql = "INSERT INTO image_water (year_rai, emp_id, plot_id, contract_number, suga_type, quota, area_rai)
                        VALUES ('$year_rai', '$emp_id', '$plot_id', '$contract_number', '$suga_type', '$quota', '$area_rai')";
                if (mysqli_query($con, $sql)) {
                    $inserted++;
                } else {
                    $skipped++;
                }
            }

            echo "<script>
                alert('นำเข้าสำเร็จ: เพิ่ม $inserted รายการ | ข้าม $skipped รายการ');
                window.location = 'admin_page.php';
            </script>";
            exit;
        } else {
            echo "<script>alert('ไม่สามารถอัปโหลดไฟล์ได้');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>นำเข้าข้อมูลแปลงอ้อย</title>
  <style>
    #loader {
      display: none;
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(255, 255, 255, 0.8);
      z-index: 9999;
    }
    .spinner {
      position: absolute;
      top: 50%; left: 50%;
      transform: translate(-50%, -50%);
      border: 6px solid #f3f3f3;
      border-top: 6px solid #3498db;
      border-radius: 50%;
      width: 60px; height: 60px;
      animation: spin 1s linear infinite;
    }
    @keyframes spin {
      0% { transform: translate(-50%, -50%) rotate(0deg); }
      100% { transform: translate(-50%, -50%) rotate(360deg); }
    }
  </style>
</head>
<body>

<h2>นำเข้าข้อมูลจาก Excel</h2>
<form method="post" enctype="multipart/form-data" onsubmit="showLoader()">
  <input type="file" name="excel" accept=".xls,.xlsx,.csv" required>
  <button type="submit">อัปโหลด</button>
</form>

<div id="loader">
  <div class="spinner"></div>
  <p style="text-align:center; font-weight:bold;">กำลังนำเข้าข้อมูล... กรุณารอสักครู่</p>
</div>

<script>
  function showLoader() {
    document.getElementById("loader").style.display = "block";
  }
</script>

</body>
</html>
