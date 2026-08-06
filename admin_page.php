<?php
session_start();
require("dbconnect.php");

// ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['emp_level']) || $_SESSION['emp_level'] !== "a") {
  echo "<div style='text-align:center; margin-top:50px;'><h4>เฉพาะผู้ดูแลระบบเท่านั้น <a href='login.php' class='btn btn-primary'>กรุณาเข้าสู่ระบบ</a></h4></div>";
  exit();
}

$emp_id = $_SESSION["emp_id"];
$sqllogin = "SELECT * FROM employee WHERE emp_id = '$emp_id'";
$result = mysqli_query($con, $sqllogin);
$row = mysqli_fetch_assoc($result);

// ปีที่เลือกไว้ใน session หรือค่าเริ่มต้น
$selected_year = $_SESSION['selected_year'] ?? '68-69';
?>

<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Dashboard | ระบบการให้น้ำอ้อย</title>
  <link rel="icon" href="icon/icon_login.png" type="image/x-icon"> 
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">

  <style>
    :root {
      --primary-color: #1e3a8a;
      --secondary-color: #3b82f6;
      --bg-light: #f8fafc;
    }

    body {
      background-color: var(--bg-light);
      font-family: 'Sarabun', sans-serif;
    }

    /* สไตล์ Card สรุปข้อมูล */
    .stat-card {
      background: white;
      border-radius: 15px;
      padding: 20px;
      border: none;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease;
    }
    .stat-card:hover { transform: translateY(-5px); }
    .icon-box {
      width: 50px;
      height: 50px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      margin-bottom: 15px;
    }

    .main-container {
      background-color: #fff;
      border-radius: 20px;
      padding: 30px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
      margin-top: -50px; /* ขยับขึ้นไปทับ Header เล็กน้อยให้ดูมีมิติ */
      position: relative;
      z-index: 10;
    }

    .page-header-bg {
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      height: 200px;
      padding-top: 40px;
      color: white;
      text-align: center;
    }

    /* ตกแต่งตาราง */
    .table thead {
      background-color: #f1f5f9;
      color: #334155;
      text-transform: uppercase;
      font-size: 0.85rem;
      letter-spacing: 0.5px;
    }
    .img-thumbnail {
      width: 45px;
      height: 45px;
      object-fit: cover;
      border-radius: 8px;
      cursor: zoom-in;
      transition: all 0.2s;
    }
    .img-thumbnail:hover { opacity: 0.8; transform: scale(1.1); }

    .btn-action { border-radius: 8px; padding: 6px 12px; }
  </style>
</head>
<body>

<?php require("nav_a.php"); ?>

<div class="page-header-bg">
    <h2 class="fw-bold"><i class='bx bxs-dashboard'></i> แผงควบคุมผู้ดูแลระบบ</h2>
    <p class="opacity-75">จัดการข้อมูลแปลงอ้อยและการให้น้ำ ประจำปีการผลิต <?php echo htmlspecialchars($selected_year); ?></p>
</div>

<div class="container main-container mb-5">
    
    <div class="row mb-4 align-items-center">
        <div class="col-md-6 text-center text-md-start">
            <h4 class="mb-1">สวัสดีคุณ, <strong><?php echo htmlspecialchars($row["emp_name"]); ?></strong></h4>
            <span class="badge bg-primary-subtle text-primary px-3 py-2">
                <i class='bx bxs-id-card'></i> ID: <?php echo htmlspecialchars($row["emp_id"]); ?> | หน่วย: <?php echo htmlspecialchars($row["emp_unit"]); ?>
            </span>
        </div>
        <div class="col-md-6 mt-3 mt-md-0 text-md-end text-center">
            <span class="badge bg-info-subtle text-info px-3 py-2" style="font-size: 0.9rem;">
                <i class='bx bx-calendar-check'></i> ปีการผลิตปัจจุบัน: <?php echo htmlspecialchars($selected_year); ?>
            </span>
        </div>
    </div>

    <hr class="my-4 opacity-25">

    <div class="d-flex gap-2 flex-wrap mb-4">
        <a href="water_insertForm.php" class="btn btn-primary btn-action">
            <i class='bx bx-plus-circle'></i> เพิ่มข้อมูลรายแปลง
        </a>
        <button type="button" class="btn btn-outline-success btn-action" data-bs-toggle="modal" data-bs-target="#importExcelModal">
            <i class='bx bx-file-blank'></i> นำเข้า Excel
        </button>
        <a href="export_excel.php" class="btn btn-outline-secondary btn-action">
            <i class="bx bx-download"></i> ส่งออก Excel
        </a>
    </div>

    <div class="table-responsive">
        <table id="dataTable" class="table table-hover align-middle nowrap w-100">
            <thead>
                <tr>
                    <th>ปี</th>
                    <th>ไอดี นสส.</th>
                    <th>ไอดีแปลง</th>
                    <th>เลขสัญญา</th>
                    <th>ชนิดอ้อย</th>
                    <th>โควต้า</th>
                    <th>ไร่</th>
                    <th>น้ำ 1</th>
                    <th>น้ำ 2</th>
                    <th>น้ำ 3</th>
                    <th>ท่วม</th>
                    <th>แล้ง</th>
                    <th>อื่นๆ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-primary"><i class='bx bx-image-alt'></i> ดูภาพขยาย</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <img src="" id="modalImage" class="img-fluid rounded shadow" alt="Large View">
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="importExcelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="import_excel.php" method="POST" enctype="multipart/form-data">
            <div class="modal-content border-0">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class='bx bx-upload'></i> นำเข้าข้อมูลจาก Excel</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info small">
                        <i class='bx bx-info-circle'></i> กรุณาตรวจสอบรูปแบบไฟล์ Excel ให้ถูกต้องตามแม่แบบก่อนอัปโหลด
                    </div>
                    <label class="form-label fw-bold">เลือกไฟล์ (.xlsx, .xls)</label>
                    <input type="file" name="excel" class="form-control" accept=".xlsx,.xls" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-success px-4">เริ่มนำเข้า</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

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
        order: [[2, "desc"]], // เรียงตามไอดีแปลงล่าสุด
        columns: [
            { data: 'year_rai' },
            { data: 'emp_id' },
            { data: 'plot_id' },
            { data: 'contract_number' },
            { data: 'suga_type' },
            { data: 'quota' },
            { data: 'area_rai' },
            { data: 'water_image1', render: imgRenderer },
            { data: 'water_image2', render: imgRenderer },
            { data: 'water_image3', render: imgRenderer },
            { data: 'flood_image', render: imgRenderer },
            { data: 'drought_image', render: imgRenderer },
            { data: 'other_image', render: imgRenderer },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                    return `
                        <div class="btn-group">
                            <a href="water_edit_data.php?plot_id=${encodeURIComponent(row.plot_id)}&year_rai=${encodeURIComponent(row.year_rai)}" class="btn btn-warning btn-sm btn-action">
                                <i class='bx bx-edit-alt'></i>
                            </a>
                            <a href="water_delete_data.php?id=${encodeURIComponent(row.plot_id)}&year_rai=${encodeURIComponent(row.year_rai)}" class="btn btn-danger btn-sm btn-action" onclick="return confirm('ลบข้อมูลนี้?');">
                                <i class='bx bx-trash'></i>
                            </a>
                        </div>`;
                }
            }
        ],
        language: {
            url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/th.json"
        }
    });

    // ฟังก์ชันช่วยแสดงรูป
    function imgRenderer(data) {
        return data ? `<img src="${data}" class="img-thumbnail" />` : '<span class="text-muted small">-</span>';
    }

    // คลิกที่รูปเพื่อขยาย (ใช้ Event Delegation)
    $(document).on('click', '.img-thumbnail', function() {
        const src = $(this).attr('src');
        if(src !== "-") {
            $('#modalImage').attr('src', src);
            const myModal = new bootstrap.Modal(document.getElementById('imageModal'));
            myModal.show();
        }
    });
});
</script>

</body>
</html>