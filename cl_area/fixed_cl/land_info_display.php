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
    // ใช้ Prepared Statement เพื่อป้องกัน SQL Injection
    $stmt = $conn->prepare("SELECT * FROM land_info WHERE promotion_unit = :promotion_unit AND production_year = :production_year");
    $stmt->bindParam(':promotion_unit', $current_unit_name);
    $stmt->bindParam(':production_year', $current_production_year_label);
    $stmt->execute();
    $land_info_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

     // --- เพิ่มโค้ด Debug ตรงนี้ ---
    //echo "<pre style='background-color:#d4edda; color:#155724; padding:10px; border-radius:5px;'>";
    //echo "<b>DEBUG: Database Fetch Result</b>\n";
    //echo "Number of rows fetched from database: <b>" . count($land_info_data) . "</b>\n";
    // If you want to see the actual data (be careful with large datasets)
    // echo "Fetched Data: " . print_r($land_info_data, true) . "\n";
    //echo "</pre>";
    // ----------------------------

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
<body>
    <?php include_once 'nav.php'; // เรียกใช้ Navbar ?>

    <div class="container-fluid main-container">
        <h1 class="text-center">ตารางข้อมูลรังวัดพื้นที่</h1>
        
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
                        <td><?php echo htmlspecialchars($row["plot_id"]); ?></td>
                        <td><?php echo htmlspecialchars($row["plcontract_number"]); ?></td>
                        <td><?php echo htmlspecialchars($row["sugar_type"]); ?></td>
                        <td><?php echo htmlspecialchars($row["quota_name"]); ?></td>
                        <td><?php echo htmlspecialchars($row["promotion_unit"]); ?></td>
                        <td><?php echo htmlspecialchars($row["promoter_area"]); ?></td>
                        <td><?php echo htmlspecialchars($row["village"]); ?></td>
                        <td><?php echo htmlspecialchars($row["district_sub"]); ?></td>
                        <td><?php echo htmlspecialchars($row["district"]); ?></td>
                        <td><?php echo htmlspecialchars($row["province"]); ?></td>
                        <td><?php echo htmlspecialchars($row["square_meters"]); ?></td>
                        <td><?php echo htmlspecialchars($row["rai"]); ?></td>
                        <td><?php echo htmlspecialchars($row["ngan"]); ?></td>
                        <td><?php echo htmlspecialchars($row["wah"]); ?></td>
                        <td><?php echo htmlspecialchars($row["rai_adjusted"]); ?></td>
                        <td><button class="btn btn-danger btn-sm delete-btn" data-id="<?php echo $row['id']; ?>"> ลบข้อมูล</button></td>
                        <td><a target="_blank" href="exportPDF.php?id=<?php echo htmlspecialchars($row['id']); ?>" class="btn btn-primary btn-sm">Print</a></td>
                    </tr>
                    <?php 
                        }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-end gap-3 mt-4">
            <button type="button" class="btn btn-modern-green" id="openInsertFormModalBtn" data-bs-toggle="modal" data-bs-target="#insertFormModal">บันทึกข้อมูลใหม่</button>
        </div>
    </div>


    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.datatables.net/2.1.6/js/dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // DataTables setup
    var myDataTable = $('#myTable').DataTable({ 
        "language": {
            "decimal":  "",
            "emptyTable": "ยังไม่มีข้อมูลสำรังวัดพื้นที่หน่วยงานและปีที่เลือก",
            "info": "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
            "infoEmpty":  "แสดง 0 ถึง 0 จากทั้งหมด 0 รายการ",
            "infoFiltered": "(กรองจากทั้งหมด _MAX_ รายการ)",
            "infoPostFix": "",
            "thousands": ",",
            "lengthMenu": "แสดง _MENU_ รายการ",
            "loadingRecords": "กำลังโหลด...",
            "processing":"กำลังประมวลผล...",
            "search": "ค้นหา:",
            "zeroRecords":"ไม่พบข้อมูลที่ตรงกับการค้นหา",
            "paginate": {
                "first": "หน้าแรก",
                "last": "หน้าสุดท้าย",
                "next": "ถัดไป",
                "previous":"ก่อนหน้า"
            },
            "aria": {
                "sortAscending": ": เรียงจากน้อยไปมาก",
                "sortDescending": ": เรียงจากมากไปน้อย"
            }
        }
    });

    $('#promotion_unit').select2({
        placeholder: 'ค้นหาหน่วยส่งเสริม',
        allowClear: true,
        disabled: true 
    });

    $('#excelUploadForm').on('submit', function(e) {
        e.preventDefault(); 
        var formData = new FormData(this);

        $.ajax({
            url: 'import_excel.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                alert(response); 
                $('#uploadExcelModal').modal('hide');
                $('#excelUploadForm')[0].reset();
                location.reload();
            },
            error: function() {
                alert("เกิดข้อผิดพลาดในการอัปโหลดไฟล์");
            }
        });
    });

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
        const raiModalElem = document.getElementById("rai_modal_hidden");
        const nganModalElem = document.getElementById("ngan_modal_hidden");
        const wahModalElem = document.getElementById("wah_modal_hidden");
        const raiAdjustedModalElem = document.getElementById("rai_adjusted_modal_hidden");

        if (isNaN(squareMeters) || squareMeters < 0) {
            if (conversionResultElem) conversionResultElem.innerHTML = "โปรดกรอกจำนวนตารางเมตรที่ถูกต้อง";
            if (adjustedConversionResultElem) adjustedConversionResultElem.innerHTML = "โปรดกรอกจำนวนตารางเมตรที่ถูกต้อง";
            if (raiModalElem) raiModalElem.value = "";
            if (nganModalElem) nganModalElem.value = "";
            if (wahModalElem) wahModalElem.value = "";
            if (raiAdjustedModalElem) raiAdjustedModalElem.value = "";
            return;
        }

        const rai_normal = Math.floor(squareMeters / 1600);
        const remainder1600 = squareMeters % 1600;
        const ngan_normal = Math.floor(remainder1600 / 400);
        const remainder400 = remainder1600 % 400;
        const wah_converted = remainder400 / 4;

        if (conversionResultElem) conversionResultElem.innerHTML =
            rai_normal + " ไร่ " + ngan_normal + " งาน " + wah_converted.toFixed(2) + " ตารางวา ";

        if (raiModalElem) raiModalElem.value = rai_normal;
        if (nganModalElem) nganModalElem.value = ngan_normal;
        if (wahModalElem) wahModalElem.value = wah_converted.toFixed(2);

        let rai_adjusted_final;
        if (ngan_normal >= 3) {
            rai_adjusted_final = rai_normal + 1;
        } else {
            rai_adjusted_final = rai_normal;
        }

        if (adjustedConversionResultElem) adjustedConversionResultElem.innerHTML =
            rai_adjusted_final + " ไร่ ";

        if (raiAdjustedModalElem) raiAdjustedModalElem.value = rai_adjusted_final;
    }

    window.submitInsertForm = function() {
    if (!validateFormModal()) {
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
                // ✅ แจ้งเตือนแบบ toast
                Toastify({
                    text: "✅ " + result.message,
                    duration: 3000,
                    close: true,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#4CAF50"
                }).showToast();

                // ✅ ปิด modal
                $('#insertFormModal').modal('hide');

                // ✅ เพิ่มแถวใหม่
                if (result.data) {
                    myDataTable.row.add([
                        result.data.production_year || '',
                        result.data.plot_id || '',
                        result.data.plcontract_number || '',
                        result.data.sugar_type || '',
                        result.data.quota_name || '',
                        result.data.promotion_unit || '',
                        result.data.promoter_area || '',
                        result.data.village || '',
                        result.data.district_sub || '',
                        result.data.district || '',
                        result.data.province || '',
                        result.data.square_meters || '',
                        result.data.rai || '',
                        result.data.ngan || '',
                        result.data.wah || '',
                        result.data.rai_adjusted || '',
                        `<button type="button" class="btn btn-danger btn-sm delete-btn" data-id="${result.data.id}">ลบข้อมูล</button>`,
                        `<a target="_blank" href="exportPDF.php?id=${result.data.id}" class="btn btn-primary btn-sm">Print</a>`
                    ]).draw(false);
                }

                // ✅ รีเซ็ตฟอร์ม
                $('#insertDataForm')[0].reset();
                if (typeof convertSquareMetersModal === 'function') {
                    convertSquareMetersModal();
                }

            } else {
                alert("❌ เกิดข้อผิดพลาด: " + result.message);
            }
        },
        error: function(xhr, status, error) {
            alert("❌ ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้: " + error);
        }
    });
};
});
</script>
<!-- ✅ Toastify CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

<!-- ✅ Toastify JS -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

<div id="toastSuccess" class="toast-success" style="display:none;">
  <div class="toast-icon">✅</div>
  <div class="toast-text">บันทึกสำเร็จ</div>
</div>

<style>
.toast-success {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background-color: #4CAF50;
    color: white;
    padding: 15px 20px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    font-size: 16px;
    z-index: 9999;
    animation: fadein 0.5s, fadeout 0.5s 2.5s;
}
.toast-icon {
    font-size: 22px;
}
@keyframes fadein {
  from {bottom: 10px; opacity: 0;}
  to {bottom: 30px; opacity: 1;}
}
@keyframes fadeout {
  from {bottom: 30px; opacity: 1;}
  to {bottom: 10px; opacity: 0;}
}
</style>
<style>
/* คุณมี CSS นี้อยู่แล้ว */
.toast-success {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background-color: #4CAF50; /* สีเขียวสำหรับ Success */
    color: white;
    padding: 15px 20px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    font-size: 16px;
    z-index: 9999;
    animation: fadein 0.5s, fadeout 0.5s 2.5s;
}
.toast-icon {
    font-size: 22px;
}
@keyframes fadein {
    from {bottom: 10px; opacity: 0;}
    to {bottom: 30px; opacity: 1;}
}
@keyframes fadeout {
    from {bottom: 30px; opacity: 1;}
    to {bottom: 10px; opacity: 0;}
}
</style>
<script>
$(document).on('click', '.delete-btn', function () {
    const id = $(this).data('id');

    if (!id) {
        Toastify({
            text: "❌ ไม่พบ ID ที่ต้องการลบ",
            duration: 3000,
            gravity: "top",
            position: "right",
            backgroundColor: "#dc3545"
        }).showToast();
        return;
    }

    if (!confirm(`คุณแน่ใจหรือไม่ว่าต้องการลบข้อมูล`)) {
        return;
    }

    $.ajax({
        url: 'deleteQueryString.php', // ✅ แก้ไขตรงนี้!
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function (res) {
            if (res.status === 'success') {
                Toastify({
                    text: "✅ " + res.message,
                    duration: 3000,
                    close: true,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#4CAF50"
                }).showToast();
                location.reload();
            } else {
                Toastify({
                    text: "❌ " + res.message,
                    duration: 3000,
                    close: true,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#dc3545"
                }).showToast();
            }
        },
        error: function (xhr, status, error) {
            let errorMessage = "❌ ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้";
            if (xhr.status === 404) {
                errorMessage = "❌ ไม่พบไฟล์ที่ร้องขอ (404 Not Found) - ตรวจสอบชื่อไฟล์และพาธ";
            } else if (xhr.status === 500) {
                errorMessage = "❌ เกิดข้อผิดพลาดภายในเซิร์ฟเวอร์ (500 Internal Server Error)";
            } else if (xhr.responseText) {
                try {
                    const errorRes = JSON.parse(xhr.responseText);
                    if (errorRes.message) {
                        errorMessage = "❌ ข้อผิดพลาด: " + errorRes.message;
                    }
                } catch (e) {
                    // Not JSON, display generic message
                }
            }
            Toastify({
                text: errorMessage,
                duration: 5000,
                close: true,
                gravity: "top",
                position: "right",
                backgroundColor: "#dc3545"
            }).showToast();
            console.error("AJAX Error Details:", xhr, status, error);
        }
    });
});
</script>

</body>
</html>