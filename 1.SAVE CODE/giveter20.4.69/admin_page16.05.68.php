<?php
session_start();
require("dbconnect.php");

if (!isset($_SESSION['emp_level']) || $_SESSION['emp_level'] !== "a") {
  echo "<center>หน้าสำหรับผู้ดูแลระบบ <a href='login.php'>กรุณาเข้าสู่ระบบก่อน</a></center>";
  exit();
}

if (!isset($_SESSION["emp_id"])) {
  header("location:login.php");
  exit();
}

$emp_id = $_SESSION["emp_id"];
$sqllogin = "SELECT * FROM employee WHERE emp_id = '$emp_id'";
$result = mysqli_query($con, $sqllogin);
$row = mysqli_fetch_assoc($result);

// ปีที่เลือกไว้ใน session
$selected_year = $_SESSION['year_rai'] ?? '68-69';

// ถ้ามีการเลือกปีใหม่จากฟอร์ม
if (isset($_POST['change_year'])) {
  $selected_year = $_POST['change_year'];
  $_SESSION['year_rai'] = $selected_year;
}
?>

<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Page</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">

  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

  <style>
    body {
      background-color: #f4f7fc;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .container {
      background-color: #fff;
      border-radius: 15px;
      padding: 30px;
      box-shadow: 0 4px 12px rgba(63, 63, 63, 0.1);
      margin-top: 30px;
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

    .table thead {
      background-color: rgb(59, 57, 57);
      color: #fff;
      text-align: center;
    }

    .table td,
    .table th {
      text-align: center;
      vertical-align: middle;
    }

    .img-thumbnail {
      width: 50px;
      height: 50px;
      object-fit: cover;
      border-radius: 8px;
      cursor: pointer;
      transition: transform 0.2s;
    }

    .img-thumbnail:hover {
      transform: scale(1.05);
    }

    @media (max-width: 768px) {
      .img-thumbnail {
        width: 60px;
        height: 60px;
      }
    }
  </style>
</head>
<body>

<div class="container">
  <?php require("nav_a.php"); ?>

  <div class="text-center admin-header">
    <i class='bx bxs-user-account bx-flashing'></i> ยินดีต้อนรับผู้ดูแลระบบ
  </div>
  <p class="text-center welcome-msg">
    <i class='bx bx-user-circle'></i> สวัสดีคุณ <strong><?php echo $row["emp_name"]; ?></strong> (ID: <?php echo $row["emp_id"]; ?>), หน่วย: <strong><?php echo $row["emp_unit"]; ?></strong>
  </p>

  <hr>
  <h5 class="text-center">
    <i class='bx bx-droplet'></i> ข้อมูลแปลงอ้อย
  </h5>

  <h6 class="text-center text-primary">
    <i class='bx bx-calendar'></i> ปีการผลิต: <strong><?php echo htmlspecialchars($selected_year); ?></strong>
  </h6>

  <hr>

  <!-- ฟอร์มเปลี่ยนปีการผลิต -->
  <form method="post" class="text-end mb-3">
    <label for="change_year" class="me-2">ปีการผลิต:</label>
    <select name="change_year" id="change_year" class="form-select d-inline-block w-auto">
      <option value="68-69" <?= $selected_year == '68-69' ? 'selected' : '' ?>>68-69</option>
      <option value="68-67" <?= $selected_year == '68-67' ? 'selected' : '' ?>>68-67</option>
    </select>
    <button type="submit" class="btn btn-primary ms-2">เปลี่ยน</button>
  </form>
  <div class="table-responsive">
    <table id="dataTable" class="table table-striped table-bordered nowrap" style="width:100%">
      <thead>
        <tr>
          <th>ปี</th>
          <th>ไอดี นสส.</th>
          <th>ไอดีแปลง</th>
          <th>เลขที่สัญญา</th>
          <th>ชนิดอ้อย</th>
          <th>โควต้า</th>
          <th>จำนวนไร่</th>
          <th>การให้น้ำ1</th>
          <th>การให้น้ำ2</th>
          <th>การให้น้ำ3</th>
          <th>น้ำท่วม</th>
          <th>กระทบแล้ง</th>
          <th>อื่นๆ</th>
          <th>แก้ไข</th>
          <th>ลบ</th>
        </tr>
      </thead>
    </table>
  </div>
  <div class="mb-3">
  <div class="d-flex gap-2 flex-wrap">
    <a href="water_insertForm.php" class="btn btn-success">
      <i class='bx bx-plus-medical'></i> เพิ่มข้อมูลการให้น้ำ
    </a>
    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importExcelModal">
      <i class='bx bx-plus-medical'></i> นำเข้าข้อมูลจาก Excel
    </button>
  </div>
</div>
</div>

<!-- Modal สำหรับแสดงภาพขนาดใหญ่ -->
<img src="https://via.placeholder.com/150" class="img-thumbnail show-image" data-bs-toggle="modal" data-bs-target="#imageModal" data-imgsrc="https://via.placeholder.com/600" alt="ภาพตัวอย่าง" style="cursor:pointer;">

  <!-- Modal สำหรับแสดงภาพขนาดใหญ่ -->
  <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-body text-center">
          <img id="modalImage" src="" class="img-fluid rounded" alt="ภาพขยาย">
        </div>
      </div>
    </div>
  </div>

<!-- Modal สำหรับอัปโหลด Excel -->
<div class="modal fade" id="importExcelModal" tabindex="-1" aria-labelledby="importExcelModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form action="import_excel.php" method="POST" enctype="multipart/form-data">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="importExcelModalLabel">นำเข้าข้อมูลจาก Excel</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="excel" class="form-label">เลือกไฟล์ Excel (.xlsx, .xls)</label>
            <input type="file" name="excel" id="excel" class="form-control" accept=".xlsx,.xls" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">นำเข้า</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
$(document).ready(function () {
  const table = $('#dataTable').DataTable({
    serverSide: true,
    processing: true,
    ajax: {
      url: 'fetch_data.php',
      type: 'POST'
    },
    scrollX: true,
    pageLength: 10,
    lengthMenu: [10, 25, 50, 100],
    columns: [
      { data: 'year_rai' },
      { data: 'emp_id' },
      { data: 'plot_id' },
      { data: 'contract_number' },
      { data: 'suga_type' },
      { data: 'quota' },
      { data: 'area_rai' },
      {
        data: 'water_image1',
        render: function (data) {
          return data ? `<img src="${data}" class="img-thumbnail" alt="น้ำ1" />` : '';
        }
      },
      {
        data: 'water_image2',
        render: function (data) {
          return data ? `<img src="${data}" class="img-thumbnail" alt="น้ำ2" />` : '';
        }
      },
      {
        data: 'water_image3',
        render: function (data) {
          return data ? `<img src="${data}" class="img-thumbnail" alt="น้ำ3" />` : '';
        }
      },
      {
        data: 'flood_image',
        render: function (data) {
          return data ? `<img src="${data}" class="img-thumbnail" alt="น้ำท่วม" />` : '';
        }
      },
      {
        data: 'drought_image',
        render: function (data) {
          return data ? `<img src="${data}" class="img-thumbnail" alt="แล้ง" />` : '';
        }
      },
      {
        data: 'other_image',
        render: function (data) {
          return data ? `<img src="${data}" class="img-thumbnail" alt="อื่นๆ" />` : '';
        }
      },
      {
        data: 'edit',
        orderable: false,
        searchable: false,
        render: function (data, type, row) {
          return `<a href="water_edit_data.php?plot_id=${row.plot_id}" class="btn btn-warning btn-sm">
                    <i class='bx bx-edit'></i> แก้ไข
                  </a>`;
        }
      },
      {
        data: 'delete',
        orderable: false,
        searchable: false,
        render: function (data, type, row) {
          return `<button class="btn btn-danger btn-sm btn-delete" data-id="${row.plot_id}">
                    <i class='bx bx-trash'></i> ลบ
                  </button>`;
        }
      }
    ],
    language: {
      url: "th.json" // เปลี่ยนจาก CDN เป็น path ในเครื่องคุณ
    }
  });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    $(document).on('click', '#dataTable .img-thumbnail', function() {
  var src = $(this).attr('src');
  $('#modalImage').attr('src', src);
  var modal = new bootstrap.Modal(document.getElementById('imageModal'));
  modal.show();
});
  </script>

</body>
</html>