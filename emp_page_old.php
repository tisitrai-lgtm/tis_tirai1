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

// ดึงข้อมูลผู้ใช้
$emp_id = $_SESSION["emp_id"];
$sqllogin = "SELECT * FROM employee WHERE emp_id = '$emp_id'";
$result = mysqli_query($con, $sqllogin);
$row = mysqli_fetch_assoc($result);

// --- การจัดการการค้นหาและแบ่งหน้า (สำคัญ: ปรับใช้ GET เพื่อให้ Pagination ทำงาน) ---

// ดึงค่าจาก GET/POST โดยให้ POST มีความสำคัญกว่า (สำหรับการ Submit ฟอร์ม)
$search = "";
$emp_level_search = "";
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ดึงค่าจาก POST เมื่อมีการกดปุ่ม "ค้นหา" หรือเลือก Dropdown
    $search = isset($_POST['search']) ? $_POST['search'] : '';
    $emp_level_search = isset($_POST['emp_level_search']) ? $_POST['emp_level_search'] : '';
    $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 10;
    // เมื่อมีการค้นหาใหม่ ให้กลับไปหน้า 1
    $page = 1; 
} else {
    // ดึงค่าจาก GET สำหรับการแบ่งหน้า (Pagination)
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $emp_level_search = isset($_GET['emp_level_search']) ? $_GET['emp_level_search'] : '';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
}

$offset = ($page - 1) * $limit;

// --- 🛡️ ปรับปรุงให้ใช้ Prepared Statements เพื่อความปลอดภัย ---
$sql_where_clauses = [];
$params = [];
$param_types = "";

if ($search !== "") {
    $sql_where_clauses[] = "(emp_id LIKE ? OR emp_unit LIKE ? OR emp_name LIKE ?)";
    $search_param = "%" . $search . "%";
    array_push($params, $search_param, $search_param, $search_param);
    $param_types .= "sss";
}

if ($emp_level_search !== "") {
    $sql_where_clauses[] = "emp_level = ?";
    $params[] = $emp_level_search;
    $param_types .= "s";
}

$sql_where = "";
if (!empty($sql_where_clauses)) {
    $sql_where = "WHERE " . implode(" AND ", $sql_where_clauses);
}

// หาจำนวนแถวทั้งหมด (แบบปลอดภัย)
$sql_count = "SELECT COUNT(*) as total FROM employee $sql_where";
$stmt_count = mysqli_prepare($con, $sql_count);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt_count, $param_types, ...$params);
}
mysqli_stmt_execute($stmt_count);
$count_result = mysqli_stmt_get_result($stmt_count);
$count_row = mysqli_fetch_assoc($count_result);
$total_records = $count_row['total'] ?? 0;
$total_pages = ceil($total_records / $limit);
mysqli_stmt_close($stmt_count);

// --- ดึงข้อมูลพนักงานสำหรับแสดงผล (แบบปลอดภัย) ---
$sql_data_query = "SELECT * FROM employee $sql_where ORDER BY emp_id ASC LIMIT ?, ?";
$stmt_data = mysqli_prepare($con, $sql_data_query);

// เพิ่ม limit และ offset เข้าไปใน parameters สำหรับ data query
$data_params = $params;
$data_param_types = $param_types . "ii";
array_push($data_params, $offset, $limit);

// Bind parameters โดยใช้ spread operator
mysqli_stmt_bind_param($stmt_data, $data_param_types, ...$data_params);

mysqli_stmt_execute($stmt_data);
$result_data = mysqli_stmt_get_result($stmt_data);

// ตรวจสอบว่าไม่มีข้อมูลในกรณีที่ผลลัพธ์ไม่พบ
$no_results = ($total_records == 0);

// --- เตรียม URL Parameters สำหรับ Pagination และลิงก์อื่นๆ ---
// ใช้ urlencode() เพื่อให้แน่ใจว่าค่าที่ถูกส่งผ่าน URL จะไม่ถูกตีความผิด
$url_search = urlencode($search);
$url_level = urlencode($emp_level_search);
$base_url_params = "&limit={$limit}&search={$url_search}&emp_level_search={$url_level}";
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
    <style>
        body {
            background-color: #e9ecef; /* พื้นหลังสีเทาอ่อน */
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
        
        /* สไตล์สำหรับ Toast */
        .toast-container {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1050;
        }
        .toast {
            background-color: #dc3545;
            color: #ffffff;
            border-radius: 10px;
            padding: 15px;
            font-size: 1.1rem;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.3);
        }

        /* ปรับตำแหน่งฟอร์มค้นหาและตัวเลือกจำนวนแถวให้อยู่ในแถวเดียวกัน */
        .search-form-row {
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
                    <i class='bx bx-user-circle me-1'></i> สวัสดีคุณ <?php echo $row["emp_name"]; ?> (ID: <?php echo $row["emp_id"]; ?>), หน่วย: <?php echo $row["emp_unit"]; ?>
                </p>
            </div>

            <hr class="my-4">

            <div class="text-start mb-3">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                    <i class='bx bx-user-plus me-1'></i> เพิ่มข้อมูลพนักงาน
                </button>
            </div>
            
            <form method="post" class="search-form-row">
                <div class="row align-items-end">
                    <div class="col-md-3 mb-3">
                        <label for="limit" class="form-label">แสดงต่อหน้า</label>
                        <select name="limit" id="limit" class="form-select" onchange="this.form.submit()">
                            <option value="10" <?php echo ($limit == 10) ? 'selected' : ''; ?>>10 แถว</option>
                            <option value="25" <?php echo ($limit == 25) ? 'selected' : ''; ?>>25 แถว</option>
                            <option value="50" <?php echo ($limit == 50) ? 'selected' : ''; ?>>50 แถว</option>
                            <option value="100" <?php echo ($limit == 100) ? 'selected' : ''; ?>>100 แถว</option>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="emp_level_search" class="form-label">ระดับผู้ใช้งาน</label>
                        <select name="emp_level_search" id="emp_level_search" class="form-select" onchange="this.form.submit()">
                            <option value="" <?php echo ($emp_level_search == "") ? 'selected' : ''; ?>>ทุกระดับ</option>
                            <option value="a" <?php echo ($emp_level_search == "a") ? 'selected' : ''; ?>>ผู้ดูแลระบบ</option>
                            <option value="u" <?php echo ($emp_level_search == "u") ? 'selected' : ''; ?>>ผู้ใช้งาน</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="search" class="form-label">ค้นหาข้อมูล</label>
                        <input type="text" class="form-control" name="search" id="search" placeholder="ค้นหาจาก ชื่อ, หน่วย, ID" value="<?php echo htmlspecialchars($search); ?>">
                    </div>

                    <div class="col-md-2 mb-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class='bx bx-search-alt-2 me-1'></i> ค้นหา
                        </button>
                    </div>
                </div>
            </form>

            <div class="table-responsive mt-4">
                <table class="table table-striped table-hover table-bordered align-middle">
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
                        <?php if ($no_results): ?>
                            <tr><td colspan="5" class="text-center text-danger fw-bold">ไม่พบข้อมูลที่ค้นหา</td></tr>
                        <?php else: ?>
                            <?php while($row_data = mysqli_fetch_assoc($result_data)): ?>
                                <tr>
                                    <td><?php echo $row_data["emp_id"]; ?></td>
                                    <td><?php echo $row_data["emp_name"]; ?></td>
                                    <td><?php echo $row_data["emp_unit"]; ?></td>
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
                                        <a href="emp_deletedata.php?emp_id=<?php echo $row_data["emp_id"]; ?>" class="btn btn-danger btn-sm" title="ลบข้อมูล" onclick="return confirm('คุณแน่ใจหรือไม่ว่าจะลบผู้ใช้งานคนนี้? การกระทำนี้ไม่สามารถย้อนกลับได้!');">
                                            <i class='bx bx-trash'></i> ลบ
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="row mt-3 align-items-center">
                <div class="col-md-6 text-start text-muted">
                    แสดงข้อมูล<?php echo min($offset + 1, $total_records); ?>ถึง <?php echo min($offset + $limit, $total_records); ?> จาก <?php echo $total_records; ?>รายการ
                </div>
                <div class="col-md-6">
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-end mb-0">
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $base_url_params; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span> ก่อนหน้า
                                </a>
                            </li>
                            <?php 
                                // แสดงเลขหน้าแบบสั้นๆ 
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                if ($start_page > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="?page=1' . $base_url_params . '">1</a></li>';
                                    if ($start_page > 2) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                }

                                for ($i = $start_page; $i <= $end_page; $i++): 
                            ?>
                                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $base_url_params; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; 
                                
                                if ($end_page < $total_pages) {
                                    if ($end_page < $total_pages - 1) {
                                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                    }
                                    echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . $base_url_params . '">' . $total_pages . '</a></li>';
                                }
                            ?>
                            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $base_url_params; ?>" aria-label="Next">
                                    ถัดไป <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
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

    <?php if ($no_results): ?>
        <div class="toast-container">
            <div class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
                <div class="toast-body d-flex align-items-center">
                    <i class='bx bxs-error-alt bx-tada-hover me-2 fs-4'></i> ไม่พบข้อมูลที่ค้นหา!
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>    
    <script>
        $(document).ready(function() {
            // แสดง Toast ถ้าไม่มีข้อมูล
            <?php if ($no_results && (!empty($search) || !empty($emp_level_search) || $limit != 10)): ?>
                var toastEl = document.querySelector('.toast');
                var toast = new bootstrap.Toast(toastEl);
                toast.show();
            <?php endif; ?>

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