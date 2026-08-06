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

// ดึงข้อมูลผู้ใช้ที่ล็อกอินอยู่ (แสดงข้อความต้อนรับ)
$emp_id = $_SESSION["emp_id"];
$stmt_login = mysqli_prepare($con, "SELECT * FROM employee WHERE emp_id = ?");
mysqli_stmt_bind_param($stmt_login, "s", $emp_id);
mysqli_stmt_execute($stmt_login);
$result_login = mysqli_stmt_get_result($stmt_login);
$row = mysqli_fetch_assoc($result_login);
mysqli_stmt_close($stmt_login);

// ดึงพนักงานทั้งหมดมาครั้งเดียว แล้วให้ DataTables จัดการค้นหา/เรียง/แบ่งหน้าเองฝั่ง browser
$sql_all = "SELECT * FROM employee ORDER BY emp_id ASC";
$result_data = mysqli_query($con, $sql_all);
?>

<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>หน้าผู้ดูแลระบบ</title>
    <link rel="icon" href="icon/icon_login.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #e9ecef;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .admin-container {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-top: 30px;
            margin-bottom: 30px;
        }
        .admin-header {
            font-weight: 700;
            font-size: 1.8rem;
            margin-bottom: 15px;
            color: #007bff;
        }
        .table th, .table td {
            vertical-align: middle;
            text-align: center;
        }
        .filter-row {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            background-color: #f8f9fa;
        }
    </style>
</head>

<body>
    <?php require("nav_a.php"); ?>

    <div class="container">
        <div class="admin-container mx-auto">
            <div class="text-center mb-4">
                <h1 class="admin-header">
                    <i class='bx bxs-dashboard bx-flashing me-2'></i> แผงควบคุมผู้ดูแลระบบ
                </h1>
                <p class="welcome-msg">
                    <i class='bx bx-user-circle me-1'></i> สวัสดีคุณ <?php echo htmlspecialchars($row["emp_name"]); ?> (ID: <?php echo htmlspecialchars($row["emp_id"]); ?>), หน่วย: <?php echo htmlspecialchars($row["emp_unit"]); ?>
                </p>
            </div>

            <hr class="my-4">

            <div class="text-start mb-3">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                    <i class='bx bx-user-plus me-1'></i> เพิ่มข้อมูลพนักงาน
                </button>
            </div>

            <!-- ตัวกรองระดับผู้ใช้งาน (กรองเพิ่มจากช่องค้นหาของ DataTables) -->
            <div class="filter-row">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <label for="emp_level_filter" class="form-label">กรองตามระดับผู้ใช้งาน</label>
                        <select id="emp_level_filter" class="form-select">
                            <option value="">ทุกระดับ</option>
                            <option value="ผู้ดูแลระบบ">ผู้ดูแลระบบ</option>
                            <option value="ผู้ใช้งาน">ผู้ใช้งาน</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="table-responsive mt-4">
                <table id="empTable" class="table table-striped table-hover table-bordered align-middle w-100">
                    <thead class="table-primary">
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">ชื่อ</th>
                            <th scope="col">หน่วย</th>
                            <th scope="col">ระดับผู้ใช้งาน</th>
                            <th scope="col">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row_data = mysqli_fetch_assoc($result_data)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row_data["emp_id"]); ?></td>
                                <td><?php echo htmlspecialchars($row_data["emp_name"]); ?></td>
                                <td><?php echo htmlspecialchars($row_data["emp_unit"]); ?></td>
                                <td>
                                    <?php
                                        $level_text = ($row_data["emp_level"] === "a") ? "ผู้ดูแลระบบ" : "ผู้ใช้งาน";
                                        $level_class = ($row_data["emp_level"] === "a") ? "badge bg-danger" : "badge bg-info";
                                    ?>
                                    <span class="<?php echo $level_class; ?>"><?php echo $level_text; ?></span>
                                </td>
                                <td class="d-flex justify-content-center gap-2">
                                    <button type="button" class="btn btn-warning btn-sm edit-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editEmployeeModal"
                                        data-id="<?php echo htmlspecialchars($row_data['emp_id']); ?>"
                                        data-name="<?php echo htmlspecialchars($row_data['emp_name']); ?>"
                                        data-unit="<?php echo htmlspecialchars($row_data['emp_unit']); ?>"
                                        data-level="<?php echo htmlspecialchars($row_data['emp_level']); ?>"
                                        title="แก้ไขข้อมูล">
                                        <i class='bx bx-edit-alt'></i> แก้ไข
                                    </button>
                                    <form action="emp_deletedata.php" method="POST" class="d-inline" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าจะลบผู้ใช้งานคนนี้? การกระทำนี้ไม่สามารถย้อนกลับได้!');">
                                        <input type="hidden" name="emp_id" value="<?php echo htmlspecialchars($row_data["emp_id"]); ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" title="ลบข้อมูล">
                                            <i class='bx bx-trash'></i> ลบ
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Employee Modal -->
    <div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-labelledby="addEmployeeModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="addEmployeeModalLabel"><i class='bx bx-user-plus me-2'></i>เพิ่มข้อมูลพนักงาน</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="addEmployeeForm" action="insertdata_emp.php" method="POST">
              <div class="mb-3">
                <label for="add_emp_id" class="form-label">รหัสไอดี :</label>
                <input type="text" id="add_emp_id" name="emp_id" class="form-control" required maxlength="7" oninput="this.value = this.value.replace(/\s/g, '')">
              </div>
              <div class="mb-3">
                <label for="add_emp_pass" class="form-label">รหัสผ่าน :</label>
                <input type="password" id="add_emp_pass" name="emp_pass" class="form-control" required oninput="this.value = this.value.replace(/\s/g, '')">
              </div>
              <div class="mb-3">
                <label for="add_emp_name" class="form-label">ชื่อ :</label>
                <input type="text" id="add_emp_name" name="emp_name" class="form-control" required>
              </div>
              <div class="mb-3">
                <label for="add_emp_unit" class="form-label">หน่วย</label>
                <select class="form-select" id="add_emp_unit" name="emp_unit" required>
                    <option value="" disabled selected>--เลือกหน่วย--</option>
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
                    <option value="ประจำออฟฟิตกลาง">ประจำออฟฟิตกลาง</option>
                </select>
              </div>
              <div class="mb-3">
                <label for="add_emp_level" class="form-label">ระดับผู้ใช้งาน :</label>
                <select id="add_emp_level" name="emp_level" class="form-select" required>
                  <option value="" disabled selected>-- เลือกระดับ --</option>
                  <option value="a">ผู้ดูแลระบบ</option>
                  <option value="u">ผู้ใช้งาน</option>
                </select>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
            <button type="submit" form="addEmployeeForm" class="btn btn-primary">บันทึกข้อมูล</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Employee Modal -->
    <div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-labelledby="editEmployeeModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editEmployeeModalLabel"><i class='bx bx-edit-alt me-2'></i>แก้ไขข้อมูลพนักงาน</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="editEmployeeForm" action="emp_updatedata.php" method="POST">
              <div class="mb-3">
                <label for="edit_emp_id" class="form-label">รหัสไอดี :</label>
                <input type="text" id="edit_emp_id" name="emp_id" class="form-control" readonly>
              </div>
              <div class="mb-3">
                <label for="edit_emp_pass" class="form-label">รหัสผ่านใหม่ (ถ้าต้องการเปลี่ยน) :</label>
                <input type="password" id="edit_emp_pass" name="emp_pass" class="form-control" placeholder="ปล่อยว่างไว้หากไม่ต้องการเปลี่ยน">
              </div>
              <div class="mb-3">
                <label for="edit_emp_name" class="form-label">ชื่อ :</label>
                <input type="text" id="edit_emp_name" name="emp_name" class="form-control" required>
              </div>
              <div class="mb-3">
                <label for="edit_emp_unit" class="form-label">หน่วย</label>
                <select class="form-select" id="edit_emp_unit" name="emp_unit" required>
                    <option value="">--เลือกหน่วย--</option>
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
                    <option value="ประจำออฟฟิตกลาง">ประจำออฟฟิตกลาง</option>
                </select>
              </div>
              <div class="mb-3">
                <label for="edit_emp_level" class="form-label">ระดับผู้ใช้งาน :</label>
                <select id="edit_emp_level" name="emp_level" class="form-select" required>
                  <option value="a">ผู้ดูแลระบบ</option>
                  <option value="u">ผู้ใช้งาน</option>
                </select>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
            <button type="submit" form="editEmployeeForm" class="btn btn-primary">บันทึกการเปลี่ยนแปลง</button>
          </div>
        </div>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {

            // --- ตั้งค่า DataTables ---
            var table = $('#empTable').DataTable({
                order: [[0, 'asc']],
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                columnDefs: [
                    { orderable: false, targets: 4 } // ปิดการเรียงคอลัมน์ "จัดการ"
                ],
                language: {
                    search: "ค้นหา:",
                    searchPlaceholder: "ค้นหาจาก ID, ชื่อ, หน่วย ฯลฯ",
                    lengthMenu: "แสดง _MENU_ แถวต่อหน้า",
                    info: "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                    infoEmpty: "ไม่มีข้อมูล",
                    infoFiltered: "(กรองจากทั้งหมด _MAX_ รายการ)",
                    zeroRecords: "ไม่พบข้อมูลที่ค้นหา",
                    paginate: { previous: "ก่อนหน้า", next: "ถัดไป" }
                }
            });

            // --- ตัวกรองระดับผู้ใช้งาน (กรองคอลัมน์ที่ 3) ---
            $('#emp_level_filter').on('change', function() {
                var val = $(this).val();
                table.column(3).search(val ? val : '', false, false).draw();
            });

            // --- Handle Edit Modal ---
            const editModal = document.getElementById('editEmployeeModal');
            editModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;

                const id = button.getAttribute('data-id');
                const name = button.getAttribute('data-name');
                const unit = button.getAttribute('data-unit');
                const level = button.getAttribute('data-level');

                const modalTitle = editModal.querySelector('.modal-title');
                const idInput = editModal.querySelector('#edit_emp_id');
                const nameInput = editModal.querySelector('#edit_emp_name');
                const unitInput = editModal.querySelector('#edit_emp_unit');
                const levelInput = editModal.querySelector('#edit_emp_level');
                const passInput = editModal.querySelector('#edit_emp_pass');

                modalTitle.textContent = 'แก้ไขข้อมูลพนักงาน: ' + name;
                idInput.value = id;
                nameInput.value = name;
                unitInput.value = unit;
                levelInput.value = level;
                passInput.value = '';
            });

            // --- Handle Add Form Submission (AJAX) ---
            $('#addEmployeeForm').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    type: 'POST',
                    url: 'insertdata_emp.php',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            $('#addEmployeeModal').modal('hide');
                            location.reload();
                        } else {
                            alert('เกิดข้อผิดพลาด: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์');
                    }
                });
            });

            // --- Handle Edit Form Submission (AJAX) ---
            $('#editEmployeeForm').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    type: 'POST',
                    url: 'emp_updatedata.php',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            $('#editEmployeeModal').modal('hide');
                            location.reload();
                        } else {
                            alert('เกิดข้อผิดพลาด: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์');
                    }
                });
            });

            // Clear add form when modal is hidden
            const addModal = document.getElementById('addEmployeeModal');
            addModal.addEventListener('hidden.bs.modal', function (event) {
                $('#addEmployeeForm')[0].reset();
            });
        });
    </script>
</body>
</html>

<?php
mysqli_close($con);
?>