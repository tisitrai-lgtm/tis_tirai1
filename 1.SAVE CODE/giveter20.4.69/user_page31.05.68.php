<?php
session_start();
require("dbconnect.php");

if ($_SESSION['emp_level'] != "u") {
  echo "<center>หน้าสำหรับผู้ใช้งานระบบ <a href=login.php>กรุณาเข้าสู่ระบบก่อน</a></center>";
  exit();
}

if (!$_SESSION["emp_id"]) {
  header("location:login.php");
} else {
  $sqllogin = "SELECT * FROM employee WHERE emp_id='" . $_SESSION["emp_id"] . "'";
  $result = mysqli_query($con, $sqllogin);
  $row = mysqli_fetch_assoc($result);
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title> ระบบการให้น้ำอ้อย </title>
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
  <div class="container mt-5">
    <?php require("nav_u.php"); ?>

    <div class="text-center admin-header">
      <i class='bx bxs-user-account bx-flashing'></i> ยินดีต้อนรับผู้ใช้งาน
    </div>
    <p class="text-center welcome-msg">
      <i class='bx bx-user-circle'></i> สวัสดีคุณ <strong><?php echo $row["emp_name"]; ?></strong> (ID: <?php echo $row["emp_id"]; ?>), หน่วย: <strong><?php echo $row["emp_unit"]; ?></strong>
    </p>

    <hr>
    <h5 class="text-center">
      <i class='bx bx-droplet'></i> ข้อมูลแปลงอ้อย
    </h5>
    
    <hr>

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
          </tr>
        </thead>
        <tbody>
          <?php
          // ปรับ SQL ให้กรองข้อมูลตาม emp_id ของผู้ใช้งาน
          $sql = "SELECT * FROM image_water WHERE emp_id = '" . $_SESSION['emp_id'] . "'";
          $result = mysqli_query($con, $sql);
          while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>
                    <td>{$row['year_rai']}</td>
                    <td>{$row['emp_id']}</td>
                    <td>{$row['plot_id']}</td>
                    <td>{$row['contract_number']}</td>
                    <td>{$row['suga_type']}</td>
                    <td>{$row['quota']}</td>
                    <td>{$row['area_rai']}</td>
                    <td><img src='{$row['water_image1']}' class='img-thumbnail' alt='น้ำ1'></td>
                    <td><img src='{$row['water_image2']}' class='img-thumbnail' alt='น้ำ2'></td>
                    <td><img src='{$row['water_image3']}' class='img-thumbnail' alt='น้ำ3'></td>
                    <td><img src='{$row['flood_image']}' class='img-thumbnail' alt='น้ำท่วม'></td>
                    <td><img src='{$row['drought_image']}' class='img-thumbnail' alt='แล้ง'></td>
                    <td><img src='{$row['other_image']}' class='img-thumbnail' alt='อื่นๆ'></td>
                    <td>
                      <a href='user_water_edit_data.php?plot_id={$row['plot_id']}' class='btn btn-warning btn-sm'><i class='bx bx-edit-alt'></i></a>
                    </td>
                  </tr>";
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>

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

  <script>
    $(document).ready(function () {
      $('#dataTable').DataTable({
        responsive: true,
        language: {
          search: "ค้นหา:",
          lengthMenu: "แสดง _MENU_ รายการ",
          info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
          paginate: {
            next: "ถัดไป",
            previous: "ก่อนหน้า"
          }
        }
      });

      // แสดงภาพใหญ่เมื่อคลิกที่ภาพเล็ก
      $(document).on('click', '.img-thumbnail', function () {
        const src = $(this).attr('src');
        $('#modalImage').attr('src', src);
        $('#imageModal').modal('show');
      });
    });
  </script>

</body>

<?php  } ?>

</html>
