<?php
session_start();
require("dbconnect.php");

// ตรวจสอบสถานะการเข้าสู่ระบบและระดับผู้ใช้งาน
if (!isset($_SESSION['emp_level']) || $_SESSION['emp_level'] != "u") {
    echo "<center>หน้าสำหรับผู้ใช้งานระบบ <a href=login.php>กรุณาเข้าสู่ระบบก่อน</a></center>";
    exit();
}

if (!isset($_SESSION["emp_id"]) || !$_SESSION["emp_id"]) {
    header("location:login.php");
    exit(); // ต้องมี exit() หลังจาก header เพื่อหยุดการทำงานของสคริปต์
}

// ดึงข้อมูลผู้ใช้งานที่เข้าสู่ระบบ
$sqllogin = "SELECT * FROM employee WHERE emp_id='" . $_SESSION["emp_id"] . "'";
$result_user_info = mysqli_query($con, $sqllogin);
$row_user_info = mysqli_fetch_assoc($result_user_info);

// --- ➕ ส่วนที่เพิ่มเข้าไป: รับค่าปีการผลิตจาก Session ---
$selected_year = isset($_SESSION['selected_year']) ? $_SESSION['selected_year'] : '';
// --------------------------------------------------

// *** สำคัญมาก: กำหนด BASE_IMAGE_ROOT_PATH
define('BASE_IMAGE_ROOT_PATH', ''); // <<< ปรับค่านี้ให้ถูกต้องตามโครงสร้างโฟลเดอร์ของคุณ


// ตรวจสอบให้แน่ใจว่ามีการเชื่อมต่อฐานข้อมูล (จาก dbconnect.php)
if (!isset($con) || !$con) {
    die("Error: Database connection not established. Check dbconnect.php");
}

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="icon/icon_login.png" type="image/x-icon"> 
    <title>ระบบการให้น้ำอ้อย - รูปภาพการให้น้ำ</title>
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
            margin-bottom: 30px; /* เพิ่มระยะห่างด้านล่าง */
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

        /* เพิ่ม styling สำหรับข้อความ 'ไม่มีรูป' */
        .no-image-text {
            color: #888;
            font-style: italic;
            font-size: 0.9em;
        }
        
        /* ➕ เพิ่มสไตล์แสดงปีที่เลือก */
        .year-display {
            background: #28a745;
            color: white;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
            <?php require("nav_u.php"); // ตรวจสอบว่าไฟล์ nav_u.php มีอยู่จริงและสามารถ include ได้ ?>
<div class="content-wrapper">
    <div class="container mt-5">

        <div class="text-center admin-header">
            <i class='bx bxs-user-account bx-flashing'></i> ยินดีต้อนรับผู้ใช้งาน
        </div>
        <p class="text-center welcome-msg">
            <i class='bx bx-user-circle'></i> สวัสดีคุณ <strong><?php echo htmlspecialchars($row_user_info["emp_name"]); ?></strong> (ID: <?php echo htmlspecialchars($row_user_info["emp_id"]); ?>), หน่วย: <strong><?php echo htmlspecialchars($row_user_info["emp_unit"]); ?></strong>
            <br>
            <span class="year-display">ปีการผลิตที่เลือก: <?php echo htmlspecialchars($selected_year); ?></span>
        </p>

        <hr>
        <h5 class="text-center">
            <i class='bx bx-camera'></i> ข้อมูลแปลงอ้อยที่มีรูปภาพ
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
                    // Array ของคอลัมน์รูปภาพในฐานข้อมูล
                    $image_columns_db = [
                        'water_image1',
                        'water_image2',
                        'water_image3',
                        'flood_image',
                        'drought_image',
                        'other_image'
                    ];

                    // สร้างเงื่อนไข WHERE สำหรับ SQL เพื่อตรวจสอบว่ามีรูปภาพอย่างน้อย 1 รูป
                    $image_conditions = [];
                    foreach ($image_columns_db as $col) {
                        $image_conditions[] = "`{$col}` IS NOT NULL AND `{$col}` != ''";
                    }
                    $where_image_clause = "(" . implode(" OR ", $image_conditions) . ")";

                    // --- ➕ ปรับ SQL ให้กรองตาม emp_id และ year_rai ที่เลือกมาจาก Session ---
                    $emp_id_safe = mysqli_real_escape_string($con, $_SESSION['emp_id']);
                    $year_safe = mysqli_real_escape_string($con, $selected_year);

                    $sql_data = "SELECT * FROM image_water 
                                 WHERE emp_id = '$emp_id_safe' 
                                 AND year_rai = '$year_safe' 
                                 AND {$where_image_clause}
                                 ORDER BY plot_id";
                    // ------------------------------------------------------------------

                    $result_data = mysqli_query($con, $sql_data);

                    if (!$result_data) {
                        die("Query Error: " . mysqli_error($con));
                    }
                    while ($row_data = mysqli_fetch_assoc($result_data)) {

                    // --- แก้ไขจุดนี้: ทำเลขสัญญาให้เป็น 6 หลัก ---
                        $formatted_contract = str_pad($row_data['contract_number'], 6, "0", STR_PAD_LEFT);
                        // ---------------------------------------
                        echo "<tr>
                            <td>" . htmlspecialchars($row_data['year_rai']) . "</td>
                            <td>" . htmlspecialchars($row_data['emp_id']) . "</td>
                            <td>" . htmlspecialchars($row_data['plot_id']) . "</td>
                            <td class='fw-bold text-primary'>" . htmlspecialchars($formatted_contract) . "</td>
                            <td>" . htmlspecialchars($row_data['suga_type']) . "</td>
                            <td>" . htmlspecialchars($row_data['quota']) . "</td>
                            <td>" . htmlspecialchars($row_data['area_rai']) . "</td>";

                        // Array ของคอลัมน์รูปภาพในฐานข้อมูลและคำอธิบายสำหรับแสดงผล
                        $image_columns_display = [
                            'water_image1'  => 'น้ำ1',
                            'water_image2'  => 'น้ำ2',
                            'water_image3'  => 'น้ำ3',
                            'flood_image'   => 'น้ำท่วม',
                            'drought_image' => 'แล้ง',
                            'other_image'   => 'อื่นๆ'
                        ];

                        // สร้าง Base Path สำหรับรูปภาพของแต่ละรายการ
                        $empId_sanitized = htmlspecialchars(basename($row_data['emp_id']));
                        $contract_number_sanitized = htmlspecialchars(basename($row_data['contract_number']));
                        $plotId_sanitized = htmlspecialchars(basename($row_data['plot_id']));

                        $base_image_folder_path = BASE_IMAGE_ROOT_PATH . "images/water/{$empId_sanitized}/{$contract_number_sanitized}/{$plotId_sanitized}/";

                        foreach ($image_columns_display as $col_name => $alt_text) {
                            $image_file_name_from_db = $row_data[$col_name];
                            $full_image_server_path = ''; 
                            $full_image_url = ''; 

                            if (!empty($image_file_name_from_db)) {
                                $full_image_server_path = $base_image_folder_path . basename($image_file_name_from_db);
                                $full_image_url = BASE_IMAGE_ROOT_PATH . "images/water/{$empId_sanitized}/{$contract_number_sanitized}/{$plotId_sanitized}/" . htmlspecialchars(basename($image_file_name_from_db));
                            }

                            echo "<td>";
                            if (!empty($full_image_server_path) && file_exists($full_image_server_path)) {
                                echo "<img src='" . $full_image_url . "' class='img-thumbnail' alt='" . htmlspecialchars($alt_text) . "'>";
                            } else {
                                echo "<span class='no-image-text'></span>"; 
                            }
                            echo "</td>";
                        }

                        echo "<td>
                                <a href='user_water_edit_data.php?plot_id=" . urlencode($row_data['plot_id']) . "&year_rai=" . urlencode($row_data['year_rai']) . "' class='btn btn-warning btn-sm'><i class='bx bx-edit-alt'></i></a>
                            </td>
                        </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <img id="modalImage" src="" class="img-fluid rounded" alt="ภาพขยาย">
                </div>
            </div>
        </div>
    </div>
</div>
    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable({
                responsive: true,
                language: {
                    search: "ค้นหา:",
                    lengthMenu: "แสดง _MENU_ รายการ",
                    info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
                    paginate: {
                        next: "ถัดไป",
                        previous: "ก่อนหน้า"
                    },
                    zeroRecords: "ไม่พบข้อมูลรูปภาพในปีการผลิตที่เลือก", 
                    infoEmpty: "ไม่พบข้อมูล", 
                    infoFiltered: "(กรองจากทั้งหมด _MAX_ รายการ)" 
                }
            });

            // แสดงภาพใหญ่เมื่อคลิกที่ภาพเล็ก
            $(document).on('click', '.img-thumbnail', function() {
                const src = $(this).attr('src');
                if (src) {
                    $('#modalImage').attr('src', src);
                    $('#imageModal').modal('show');
                }
            });
        });
    </script>
<?php include 'nav_u_footer.php'; ?>
</body>
</html>