<?php
session_start();
require("db_connect.php");

$selected_year = $_GET['year'] ?? '';

if (!$selected_year) {
    echo "กรุณาเลือกปีการผลิตก่อน <a href='index.php'>กลับไปหน้าเลือกปี</a>";
    exit;
}

// ดึงข้อมูลจากตาราง cane_plot_data
$sql = "SELECT * FROM cane_plot_data WHERE production_year = ? ORDER BY id DESC";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("s", $selected_year);
$stmt->execute();
?>

<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ระบบรูปใส่แปลงประมาณตัน [Admin] </title>
    <link rel="icon" href="icon/2.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <style>
        body { background-color: #f4f7fc; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar-custom { background: linear-gradient(90deg, #007bff, #00c6ff); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .navbar-brand, .nav-link { color: white !important; font-weight: bold; }
        .nav-link:hover { color: #ffd700 !important; transform: translateY(-2px); }

        .container {
            background-color: #fff; border-radius: 15px; padding: 30px;
            box-shadow: 0 4px 12px rgba(63,63,63,0.1); margin-top: 30px;
        }

        .table thead { background-color: rgb(59,57,57); color: #fff; text-align: center; }
        .table td, .table th { text-align: center; vertical-align: middle; }

        .img-thumbnail {
            width: 50px; height: 50px; object-fit: cover; border-radius: 8px;
            cursor: pointer; transition: transform 0.2s;
        }
        .img-thumbnail:hover { transform: scale(1.05); }

        .notes-preview { display: block; margin-bottom: 5px; }
        .view-notes-btn { font-size: 0.85em; text-decoration: none; color: #007bff; }
        #modalNotesContent { white-space: pre-wrap; word-wrap: break-word; }
    </style>
</head>
<body>
<?php require("nav.php"); ?>

<div class="container">
    <hr>
    <h5 class="text-center">
        <i class='bx bx-droplet'></i> ข้อมูลแปลงอ้อย [Admin]
    </h5>

    <h6 class="text-center text-primary">
        <i class='bx bx-calendar'></i> ปีการผลิต: <strong><?php echo htmlspecialchars($selected_year); ?></strong>
    </h6>
    <hr>

    <div class="table-responsive">
        <table id="dataTable" class="table table-striped table-bordered nowrap" style="width:100%">
            <thead>
                <tr>
                    <th>ปีผลิต</th>
                    <th>นักส่งเสริม</th>
                    <th>หน่วยงาน</th>
                    <th>เลขสัญญา</th>
                    <th>โควต้า</th>
                    <th>ID แปลง</th>
                    <th>พื้นที่ (ไร่)</th>
                    <th>ชนิดอ้อย</th>
                    <th>ประเมินตัน (1)</th>
                    <th>ประเมินตัน (2)</th>
                    <th>ประมาณตัน (1)</th>
                    <th>ประมาณตัน (2)</th>
                    <th>อ้อยคงเหลือ 1 (1)</th>
                    <th>อ้อยคงเหลือ 1 (2)</th>
                    <th>อ้อยคงเหลือ 2 (1)</th>
                    <th>อ้อยคงเหลือ 2 (2)</th>
                    <th>อ้อยคงเหลือ 3 (1)</th>
                    <th>อ้อยคงเหลือ 3 (2)</th>
                    <th>หมายเหตุ</th>
                    <th>วันที่บันทึก</th>
                    <th>แก้ไข</th>
                    <th>ลบ</th>
                </tr>
            </thead>
        </table>
    </div>

    <div class="mb-3">
        <div class="d-flex gap-2 flex-wrap">
            <a href="insertForm.php?year=<?php echo htmlspecialchars($selected_year); ?>" class="btn btn-success">
                <i class='bx bx-plus-medical'></i> เพิ่มข้อมูลใหม่
            </a>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#exportYearModal">
                <i class="bx bx-download"></i> ดาวน์โหลดข้อมูล Excel
            </button>
        </div>
    </div>
</div>

<!-- Modal: ดูภาพขยาย -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ดูภาพขยาย</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img src="" id="modalImage" class="img-fluid" alt="ภาพขยาย">
            </div>
        </div>
    </div>
</div>

<!-- Modal: หมายเหตุ -->
<div class="modal fade" id="notesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">รายละเอียดหมายเหตุ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="modalNotesContent" class="text-start"></p>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Export Year -->
<div class="modal fade" id="exportYearModal" tabindex="-1" aria-labelledby="exportYearModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportYearModalLabel">เลือกปีการผลิตสำหรับดาวน์โหลด Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="export_year_select" class="form-label">ปีการผลิต:</label>
                    <select class="form-select" id="export_year_select"></select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="confirmExportBtn">
                    <i class="bx bx-download"></i> ดาวน์โหลด
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    const table = $('#dataTable').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
            url: 'fetch_data_admin.php',
            type: 'POST',
            data: function (d) {
                d.year = '<?php echo htmlspecialchars($selected_year); ?>';
            }
        },
        scrollX: true,
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        columns: [
            { data: 'production_year' },
            { data: 'emp_number' },
            { data: 'agency' },
            { data: 'contract_number' },
            { data: 'quota' },
            { data: 'plot_id' },
            { data: 'rai_area' },
            { data: 'suga_type' },
            { data: 'evaluate_ton_1', render: d => d ? `<img src="${d}" class="img-thumbnail" alt="">` : '' },
            { data: 'evaluate_ton_2', render: d => d ? `<img src="${d}" class="img-thumbnail" alt="">` : '' },
            { data: 'estimate_ton_1', render: d => d ? `<img src="${d}" class="img-thumbnail" alt="">` : '' },
            { data: 'estimate_ton_2', render: d => d ? `<img src="${d}" class="img-thumbnail" alt="">` : '' },
            { data: 'remaining_cane_1_img_1', render: d => d ? `<img src="${d}" class="img-thumbnail" alt="">` : '' },
            { data: 'remaining_cane_1_img_2', render: d => d ? `<img src="${d}" class="img-thumbnail" alt="">` : '' },
            { data: 'remaining_cane_2_img_1', render: d => d ? `<img src="${d}" class="img-thumbnail" alt="">` : '' },
            { data: 'remaining_cane_2_img_2', render: d => d ? `<img src="${d}" class="img-thumbnail" alt="">` : '' },
            { data: 'remaining_cane_3_img_1', render: d => d ? `<img src="${d}" class="img-thumbnail" alt="">` : '' },
            { data: 'remaining_cane_3_img_2', render: d => d ? `<img src="${d}" class="img-thumbnail" alt="">` : '' },
            { data: 'notes', render: (data) => data && data.length > 25 ? `<span>${data.substring(0,25)}...</span> <a href="#" class="view-notes-btn" data-notes="${encodeURIComponent(data)}">ดูเพิ่มเติม</a>` : data },
            { data: 'created_at' },
            {
                data: null, orderable: false, searchable: false,
                render: (data, type, row) => `<a href="edit_data.php?plot_id=${row.plot_id}" class="btn btn-warning btn-sm"><i class='bx bx-edit'></i> แก้ไข</a>`
            },
            {
                data: null, orderable: false, searchable: false,
                render: (data, type, row) => `<button class="btn btn-danger btn-sm delete-btn" data-id="${row.id}"><i class='bx bx-trash'></i> ลบ</button>`
            }
        ],
        language: { url: "th.json" }
    });

    // ลบข้อมูล
   $(document).ready(function() {
    // 🚨 เปลี่ยนจาก .delete-record-btn เป็น .delete-btn ตามที่คุณใช้
    $(document).on('click', '.delete-btn', function() {
        var button = $(this);
        // ดึงค่า ID จาก data-id ที่คุณกำหนดในปุ่ม
        var recordId = button.data('id'); 
        var rowElement = button.closest('tr'); // หาแถวทั้งหมดที่ต้องการลบ

        if (confirm('คุณแน่ใจหรือไม่ว่าต้องการลบข้อมูลแปลงนี้ถาวร? การดำเนินการนี้จะลบข้อมูลและรูปภาพทั้งหมดที่เกี่ยวข้อง!')) {
            $.ajax({
                url: 'delete_data_ajax.php', 
                type: 'POST',
                dataType: 'json',
                data: { id: recordId }, // ส่ง ID ไปให้ delete_data_ajax.php
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        // ลบแถวออกจากตารางทันที
                        rowElement.fadeOut(500, function() {
                            $(this).remove();
                        });
                        
                        // 🚨 สำคัญ: ถ้าใช้ DataTable ต้องเรียกฟังก์ชันวาดตารางใหม่
                        // สมมติว่า DataTable ของคุณมี ID คือ #cane_data_table
                        // ถ้า DataTable มีการกำหนด ID ให้ตาราง:
                        // $('#cane_data_table').DataTable().ajax.reload();
                        // หรือถ้าไม่ต้องการ reload ทั้งหมด แค่ลบ rowElement ก็พอ
                        
                    } else {
                        alert('เกิดข้อผิดพลาดในการลบ: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error: ", status, error, xhr.responseText);
                    alert('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์ กรุณาลองใหม่.');
                }
            });
        }
    });
});

    // เปิดดูรูปภาพ
    $(document).on('click', '.img-thumbnail', function() {
        $('#modalImage').attr('src', $(this).attr('src'));
        new bootstrap.Modal(document.getElementById('imageModal')).show();
    });

    // หมายเหตุ
    $(document).on('click', '.view-notes-btn', function(e) {
        e.preventDefault();
        const notes = decodeURIComponent($(this).data('notes')).replace(/\n/g, '<br>');
        $('#modalNotesContent').html(notes);
        new bootstrap.Modal(document.getElementById('notesModal')).show();
    });

    // Export Year
    $('#exportYearModal').on('show.bs.modal', function() {
        $('#export_year_select').empty();
        $.getJSON('fetch_years.php', function(years) {
            if (years && years.length > 0) {
                years.forEach(y => $('#export_year_select').append(new Option(y, y)));
            } else {
                $('#export_year_select').append(new Option('ไม่พบปีการผลิต', ''));
                $('#confirmExportBtn').prop('disabled', true);
            }
        });
    });

    $('#confirmExportBtn').on('click', function() {
        const selected = $('#export_year_select').val();
        if (selected) {
            window.location.href = 'export_excel.php?year=' + selected;
        } else {
            alert('กรุณาเลือกปีการผลิต');
        }
        $('#exportYearModal').modal('hide');
    });
});
</script>

</body>
</html>
