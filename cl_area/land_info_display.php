<?php
// ###########################################################
// ไฟล์: land_info_display.php
// สำหรับแสดงตารางข้อมูลรังวัดพื้นที่ (หน้าหลัก)
// ###########################################################

// เริ่มต้นใช้งาน session
session_start();

// เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล
include_once 'db_connect.php'; // $conn object จะถูกสร้างขึ้นที่นี่

// ###########################################################
// ตรวจสอบว่าผู้ใช้ได้เลือกหน่วยงานและปีมาแล้วหรือไม่ใน Session
// ถ้ายังไม่ได้เลือก ให้ redirect กลับไปหน้าเลือกหน่วยงานและปี
// ###########################################################
if (!isset($_SESSION['selected_unit_id']) || !isset($_SESSION['selected_unit_name']) || !isset($_SESSION['selected_production_year_label'])) {
    header("Location: index.php");
    exit();
}

// ดึงค่าจาก Session
$current_unit_id = $_SESSION['selected_unit_id'];
$current_unit_name = $_SESSION['selected_unit_name'];
$current_production_year_label = $_SESSION['selected_production_year_label'];
//echo "<pre style='background-color:#f8d7da; color:#721c24; padding:10px; border-radius:5px;'>";
//echo "<b>DEBUG: Session Data Used for Filtering</b>\n";
//echo "Selected Unit ID: " . (isset($_SESSION['selected_unit_id']) ? htmlspecialchars($_SESSION['selected_unit_id']) : 'NOT SET') . "\n";
//echo "Selected Unit Name: <b>" . (isset($_SESSION['selected_unit_name']) ? htmlspecialchars($_SESSION['selected_unit_name']) : 'NOT SET') . "</b>\n";
//echo "Selected Production Year: <b>" . (isset($_SESSION['selected_production_year_label']) ? htmlspecialchars($_SESSION['selected_production_year_label']) : 'NOT SET') . "</b>\n";
//echo "</pre>";

// ดึงข้อมูล `land_info` โดยกรองตามหน่วยงานและปีที่เลือกจาก Session
$land_info_data = [];
try {
    // แก้ไขคำสั่ง SQL โดยเติม ORDER BY id DESC ต่อท้าย
    $stmt = $conn->prepare("SELECT * FROM land_info WHERE promotion_unit = :promotion_unit AND production_year = :production_year ORDER BY id DESC");
    $stmt->bindParam(':promotion_unit', $current_unit_name);
    $stmt->bindParam(':production_year', $current_production_year_label);
    $stmt->execute();
    $land_info_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("เกิดข้อผิดพลาดในการดึงข้อมูล Land Info: " . $e->getMessage());
}

// ดึงรายการหน่วยงานทั้งหมดสำหรับ dropdown ในหน้าปัจจุบัน (ใช้สำหรับแสดงรายการทั้งหมดใน Select2)
$all_units = [];
try {
    $stmt_all_units = $conn->query("SELECT unit_name FROM units ORDER BY unit_name ASC");
    $all_units = $stmt_all_units->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching all units: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตารางข้อมูลรังวัดพื้นที่</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.6/css/dataTables.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="icon/GGaa.ico" type="image/x-icon"> 
    <link rel="stylesheet" href="style_land.css"> 
</head>
<style>
  :root {
            --primary-dark: #1a237e;
            --accent-gold: #ffd700;
            --bg-light: #f8f9fa;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f0f2f5;
            color: #333;
        }

        /* Container & Header */
        .main-container {
            padding: 2rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            margin-top: 20px;
            margin-bottom: 40px;
        }

        h2.text-center {
            font-weight: 600;
            color: var(--primary-dark);
            letter-spacing: 1px;
            margin-bottom: 1.5rem;
            position: relative;
        }

        h1.text-center::after {
            content: '';
            display: block;
            width: 50px;
            height: 4px;
            background: var(--accent-gold);
            margin: 10px auto;
            border-radius: 2px;
        }

        /* Info Display Box */
        .info-display {
            background: linear-gradient(135deg, var(--primary-dark) 0%, #0d47a1 100%);
            color: white !important;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(26, 35, 126, 0.2);
            font-weight: 300;
        }

        .info-display strong {
            color: var(--accent-gold);
            font-weight: 600;
            margin: 0 5px;
        }

        /* Table Styling */
        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e0e0e0;
        }

        #myTable {
            border: none;
        }

        #myTable therapeutic thead th {
            background-color: #f8f9fa;
            color: var(--primary-dark);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            padding: 15px;
            border-bottom: 2px solid #eee;
        }

        #myTable tbody tr {
            transition: all 0.2s;
        }

        #myTable tbody tr:hover {
            background-color: rgba(255, 215, 0, 0.05) !important;
            transform: scale(1.002);
        }

        /* Buttons */
        .btn-modern-green {
            background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.2);
        }

        .btn-modern-green:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(46, 125, 50, 0.3);
            color: white;
        }

        .btn-primary.btn-sm {
            background-color: var(--primary-dark);
            border: none;
            border-radius: 6px;
        }

        .btn-danger.btn-sm {
            background-color: #ff8a80;
            border: none;
            color: #b71c1c;
            border-radius: 6px;
        }
        
        .btn-danger.btn-sm:hover {
            background-color: #d32f2f;
            color: white;
        }

        /* DataTables Customization */
        .dt-search input {
            border-radius: 20px !important;
            padding: 5px 15px !important;
            border: 1px solid #ddd !important;
        }
    /* ปรับส่วนการแสดงผลตาราง */
    .table-responsive {
        border-radius: 12px;
        overflow-x: auto; /* เปิดให้เลื่อนซ้ายขวาได้ */
        border: 1px solid #e0e0e0;
        background: white;
    }
    #myTable {
        width: 100% !important;
        min-width: 1600px; /* บังคับความกว้างตารางไม่ให้เล็กลงตามจอ */
        margin-bottom: 0;
        white-space: nowrap; /* ป้องกันข้อความตัดบรรทัด ทำให้ตารางดูเป็นระเบียบ */
    }

    /* ปรับเส้นคั่นระหว่างแถวและคอลัมน์ให้จางลง */
    #myTable {
        width: 100% !important;
        min-width: 1600px; /* บังคับความกว้างตารางไม่ให้เล็กลงตามจอ */
        margin-bottom: 0;
        white-space: nowrap; /* ป้องกันข้อความตัดบรรทัด ทำให้ตารางดูเป็นระเบียบ */
    } 
    #myTable tbody td {
        /* ใช้สีเทาอ่อนมากและโปร่งแสง */
        border-bottom: 1px solid rgba(0, 0, 0, 0.03) !important; 
        border-right: 1px solid rgba(0, 0, 0, 0.02) !important;
        padding: 12px 10px;
    }

    /* เอาเส้นขอบขวาสุดของคอลัมน์สุดท้ายออกเพื่อให้ดูโปร่ง */
    #myTable thead th:last-child, 
    #myTable tbody td:last-child {
        border-right: none !important;
    }

    /* ปรับสีพื้นหลังของหัวตารางให้จางลงด้วย */
    #myTable thead th {
        background-color: #fafbfc; 
        color: var(--primary-dark);
        font-weight: 600;
        border-bottom: 2px solid rgba(0, 0, 0, 0.05) !important;
    }

    /* ปรับ Effect ตอน Hover ให้ดูนุ่มนวลขึ้น */
    #myTable tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.01) !important; 
        transition: background-color 0.2s ease;
    }
</style>
<body>
    <?php include_once 'nav.php'; // เรียกใช้ Navbar ?>

    <div class="container-fluid main-container">
        <h2 class="text-center">ตารางข้อมูลรังวัดพื้นที่</h2>
        
        <div class="modal fade" id="uploadExcelModal" tabindex="-1" aria-labelledby="uploadExcelModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="excelUploadForm" enctype="multipart/form-data">
                        <div class="modal-header">
                            <h5 class="modal-title" id="uploadExcelModalLabel">นำเข้าไฟล์ Excel</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="excel_file" class="form-label">เลือกไฟล์ .xlsx</label>
                                <input type="file" class="form-control" name="excel_file" id="excel_file" accept=".xlsx" required>
                                <div class="form-text">รองรับเฉพาะไฟล์ .xlsx เท่านั้น</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                            <button type="submit" class="btn btn-primary">อัปโหลด</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="insertFormModal" tabindex="-1" aria-labelledby="insertFormModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg"> <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="insertFormModalLabel">บันทึกข้อมูลรังวัดที่ดินใหม่</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                    </div>
                    <div class="modal-body" id="insertFormModalBody">
                        กำลังโหลดฟอร์ม...
                    </div>
                    </div>
            </div>
        </div>

        <hr>

        <div class="mb-4">
            <span class="navbar-text me-3 text-black d-flex justify-content-center w-100 info-display">
                ข้อมูลหน่วย : <strong><?php echo htmlspecialchars($current_unit_name); ?></strong> ( ปี: <strong><?php echo htmlspecialchars($current_production_year_label); ?></strong> )
            </span>
            <small class="form-text text-muted"></small>
        </div>
        <div class="d-flex justify-end gap-3 mt-4">
            <button type="button" class="btn btn-modern-green" id="openInsertFormModalBtn" data-bs-toggle="modal" data-bs-target="#insertFormModal">เพิ่มข้อมูลใหม่</button>
        </div>
         <br>
        <div class="table-responsive">
            <table id="myTable" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ปีการผลิต</th>
                        <th>ไอดีแปลง</th>
                        <th>เลขสัญญา</th>
                        <th>ชนิดอ้อย</th>
                        <th>ชื่อโควต้า</th>
                        <th>หน่วยส่งเสริม</th>
                        <th>เขต นักส่งเสริม</th>
                        <th>หมู่ที่</th>
                        <th>ตำบล</th>
                        <th>อำเภอ</th>
                        <th>จังหวัด</th>
                        <th>ตารางเมตร</th>
                        <th>ไร่</th>
                        <th>งาน</th>
                        <th>ตารางวา</th>
                        <th>ส่งเสริม(ไร่)</th>
                        <th>ลบข้อมูล</th>
                        <th>Options</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach($land_info_data as $row) {
                    ?>
                    <tr data-district="<?php echo htmlspecialchars($row["district"]); ?>">
                        <td><?php echo htmlspecialchars($row["production_year"]); ?></td>
                            <td class="fw-bold text-primary"><?php echo htmlspecialchars($row["plot_id"]); ?></td>
                            <td><?php echo htmlspecialchars($row["plcontract_number"]); ?></td>
                            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($row["sugar_type"]); ?></span></td>
                            <td><?php echo htmlspecialchars($row["quota_name"]); ?></td>
                            <td><?php echo htmlspecialchars($row["promotion_unit"]); ?></td>
                            <td><?php echo htmlspecialchars($row["promoter_area"]); ?></td>
                            <td><?php echo htmlspecialchars($row["village"]); ?></td>
                            <td><?php echo htmlspecialchars($row["district_sub"]); ?></td>
                            <td><?php echo htmlspecialchars($row["district"]); ?></td>
                            <td><?php echo htmlspecialchars($row["province"]); ?></td>
                            <td><?php echo number_format($row["square_meters"]); ?></td>
                            <td class="text-center"><?php echo $row["rai"]; ?></td>
                            <td class="text-center"><?php echo $row["ngan"]; ?></td>
                            <td class="text-center"><?php echo $row["wah"]; ?></td>
                            <td class="fw-bold text-success"><?php echo $row["rai_adjusted"]; ?></td>
                        <td><button class="btn btn-danger btn-sm delete-btn" data-id="<?php echo $row['id']; ?>"> ลบข้อมูล</button></td>
                        <td><a target="_blank" href="exportPDF.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="btn btn-primary btn-sm">Print</a></td>
                    </tr>
                    <?php 
                        }
                    ?>
                </tbody>
            </table>
        </div>

        
    </div>


    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.6/js/dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // ✅ 1. ตั้งค่า DataTable ให้ถูกต้อง
    var myDataTable = $('#myTable').DataTable({ 
        "order": [], // ปล่อยว่างเพื่อให้เรียงตาม SQL (DESC) ที่เราทำไว้
        "language": {
            "decimal":  "",
            "emptyTable": "ยังไม่มีข้อมูลสำรังวัดพื้นที่หน่วยงานและปีที่เลือก",
            "info": "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
            "infoEmpty":  "แสดง 0 ถึง 0 จากทั้งหมด 0 รายการ",
            "infoFiltered": "(กรองจากทั้งหมด _MAX_ รายการ)",
            "search": "ค้นหา:",
            "paginate": {
                "first": "หน้าแรก", "last": "หน้าสุดท้าย", "next": "ถัดไป", "previous":"ก่อนหน้า"
            }
        }
    });

    // Select2 setup
    $('#promotion_unit').select2({
        placeholder: 'ค้นหาหน่วยส่งเสริม',
        allowClear: true,
        disabled: true 
    });

    // ส่วนของ Modal และฟังก์ชันคำนวณพื้นที่ (คงเดิมไว้)
    $('#insertFormModal').on('show.bs.modal', function (event) {
        $.ajax({
            url: 'insertForm.php',
            type: 'GET',
            success: function(response) {
                $('#insertFormModalBody').html(response);
                initializeInsertFormModal(); 
            },
            error: function() {
                $('#insertFormModalBody').html('<p class="text-danger">ไม่สามารถโหลดฟอร์มได้ กรุณาลองใหม่อีกครั้ง</p>');
            }
        });
    });

    function initializeInsertFormModal() {
        if (typeof convertSquareMetersModal === 'function') {
            convertSquareMetersModal(); 
        }
        $('#square_meters_modal').on('input', function() {
            if (typeof convertSquareMetersModal === 'function') {
                convertSquareMetersModal();
            }
        });
    }

    window.convertSquareMetersModal = function() {
        const squareMetersInput = document.getElementById("square_meters_modal");
        if (!squareMetersInput) return;
        const squareMeters = parseFloat(squareMetersInput.value);
        const conversionResultElem = document.getElementById("conversion_result_modal");
        const adjustedConversionResultElem = document.getElementById("adjusted_conversion_result_modal");
        
        if (isNaN(squareMeters) || squareMeters < 0) return;

        const rai_normal = Math.floor(squareMeters / 1600);
        const remainder1600 = squareMeters % 1600;
        const ngan_normal = Math.floor(remainder1600 / 400);
        const remainder400 = remainder1600 % 400;
        const wah_converted = remainder400 / 4;

        if (conversionResultElem) conversionResultElem.innerHTML = rai_normal + " ไร่ " + ngan_normal + " งาน " + wah_converted.toFixed(2) + " ตารางวา ";
        
        // เก็บค่าลง Hidden Input
        document.getElementById("rai_modal_hidden").value = rai_normal;
        document.getElementById("ngan_modal_hidden").value = ngan_normal;
        document.getElementById("wah_modal_hidden").value = wah_converted.toFixed(2);

        let rai_adjusted_final = (ngan_normal >= 3) ? rai_normal + 1 : rai_normal;
        if (adjustedConversionResultElem) adjustedConversionResultElem.innerHTML = rai_adjusted_final + " ไร่ ";
        document.getElementById("rai_adjusted_modal_hidden").value = rai_adjusted_final;
    }

    // ✅ 2. ฟังก์ชันบันทึกข้อมูล พร้อมลูกเล่น SweetAlert2
    window.submitInsertForm = function() {
        if (typeof validateFormModal === 'function' && !validateFormModal()) {
            return;
        }

        var formData = $('#insertDataForm').serialize();

        $.ajax({
            url: 'insertData.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(result) {
                if (result.status === 'success') {
                    // ปิด Modal
                    $('#insertFormModal').modal('hide');

                    // แสดงลูกเล่นเด้งกลางหน้าจอ
                    Swal.fire({
                        title: 'บันทึกสำเร็จ!',
                        text: result.message,
                        icon: 'success',
                        timer: 2000,
                        timerProgressBar: true,
                        confirmButtonColor: '#2e7d32',
                        confirmButtonText: 'ตกลง'
                    }).then(() => {
                        // รีโหลดเพื่อให้ SQL ORDER BY id DESC ทำงาน (ข้อมูลใหม่อยู่บนสุด)
                        location.reload();
                    });

                } else {
                    Swal.fire({
                        title: 'เกิดข้อผิดพลาด!',
                        text: result.message,
                        icon: 'error',
                        confirmButtonText: 'รับทราบ'
                    });
                }
            },
            error: function(xhr, status, error) {
                Swal.fire('ผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'warning');
            }
        });
    };
});
</script>
<script>
$(document).on('click', '.delete-btn', function () {
    const id = $(this).data('id');

    if (!id) {
        Swal.fire('ผิดพลาด', 'ไม่พบ ID ที่ต้องการลบ', 'error');
        return;
    }

    // ✅ ใช้ SweetAlert2 ถามยืนยันการลบแบบสวยๆ
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "คุณจะไม่สามารถกู้คืนข้อมูลนี้ได้!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33', // สีแดงสำหรับการลบ
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            // ส่ง AJAX ไปลบข้อมูล
            $.ajax({
                url: 'deleteQueryString.php',
                type: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function (res) {
                    if (res.status === 'success') {
                        // ✅ แจ้งเตือนลบสำเร็จ
                        Swal.fire({
                            title: 'ลบข้อมูลแล้ว!',
                            text: res.message,
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload(); // รีโหลดหน้าจอ
                        });
                    } else {
                        Swal.fire('เกิดข้อผิดพลาด', res.message, 'error');
                    }
                },
                error: function (xhr, status, error) {
                    Swal.fire('ผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
                    console.error("AJAX Error Details:", xhr, status, error);
                }
            });
        }
    });
});
</script>

</body>
</html>