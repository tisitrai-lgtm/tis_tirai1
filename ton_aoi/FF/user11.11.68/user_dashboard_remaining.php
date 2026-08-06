<?php
session_start();
require("db_connect.php");

$selected_year = $_GET['year'] ?? '';
$selected_agency = $_GET['agency'] ?? ''; // 🚨 รับค่า agency

if (!$selected_year || !$selected_agency) { // 🚨 ตรวจสอบทั้งปีและ agency
    echo "กรุณาเลือกปีการผลิตและหน่วยงาน/นักส่งเสริมก่อน <a href='user_index.php'>กลับไปหน้าเลือกปี</a>";
    exit;
}

?>

<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ระบบรูปใส่แปลงประมาณตัน</title>
    <link rel="icon" href="icon/2.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"> 

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

        /* 🚨 NEW: สไตล์สำหรับ Dashboard Cards */
        .card-body { padding: 1rem; }
        .card-title { font-size: 1.1rem; }
        .card-text { margin: 0; }
    </style>
</head>
<body>
<?php require("user_nav.php"); ?>

<div class="container">
    <hr>
    <h5 class="text-center">
        <i class='bx bx-droplet'></i> ข้อมูลแปลงอ้อย คงเหลือ
    </h5>

    <h6 class="text-center text-primary">
        <i class='bx bx-calendar'></i> ปีการผลิต: <strong><?php echo htmlspecialchars($selected_year); ?></strong>
        <span class="mx-2">|</span> 
        <i class="bi bi-person-badge"></i> หน่วยนักส่งเสริม: <strong><?php echo htmlspecialchars($selected_agency); ?></strong>
    </h6>

    <hr>
    
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-info mb-3 shadow">
                <div class="card-body">
                    <h5 class="card-title"><i class='bx bx-chart'></i> จำนวนแปลงทั้งหมด</h5>
                    <p class="card-text display-4" id="totalPlotsCount">0</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success mb-3 shadow">
                <div class="card-body">
                    <h5 class="card-title"><i class='bx bx-check-square'></i> แปลงที่มีรูปภาพ</h5>
                    <p class="card-text display-4" id="plotsWithImagesCount">0</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-danger mb-3 shadow">
                <div class="card-body">
                    <h5 class="card-title"><i class='bx bx-x-octagon'></i> แปลงที่ยังไม่มีรูปภาพ</h5>
                    <p class="card-text display-4" id="plotsWithoutImagesCount">0</p>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex justify-content-start mb-3">
        <button id="filterImageBtn" class="btn btn-sm btn-outline-primary" data-filtering="0">
            <i class="bi bi-image"></i> แสดงเฉพาะแปลงที่มีรูปภาพ
        </button>
        <button id="resetFilterBtn" class="btn btn-sm btn-outline-secondary ms-2" style="display:none;">
            <i class="bi bi-x-circle"></i> ยกเลิกตัวกรอง
        </button>
    </div>
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
                    <th>อ้อยคงเหลือ 1 (รูปหมุด)</th>
                    <th>อ้อยคงเหลือ 1 (รูปอ้อย)</th>
                    <th>อ้อยคงเหลือ 2 (รูปหมุด)</th>
                    <th>อ้อยคงเหลือ 2 (รูปอ้อย)</th>
                    <th>อ้อยคงเหลือ 3 (รูปหมุด)</th>
                    <th>อ้อยคงเหลือ 3 (รูปอ้อย)</th>
                    <th>หมายเหตุ</th>
                    <th>แก้ไข</th>
                </tr>
            </thead>
        </table>
    </div>


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

</div>

<script>

// 🚨 NEW FUNCTION: ดึงข้อมูลสรุป Dashboard (เหมือนกับไฟล์ประมาณตัน)
function fetchSummaryCounts() {
    $.ajax({
        url: 'fetch_summary_counts.php', // 🚨 เรียกใช้ไฟล์ใหม่
        type: 'POST',
        dataType: 'json',
        data: {
            year: '<?php echo htmlspecialchars($selected_year); ?>',
            agency: '<?php echo htmlspecialchars($selected_agency); ?>'
        },
        success: function(response) {
            if (response.success) {
                const total = parseInt(response.total_plots);
                const withImages = parseInt(response.plots_with_images);
                const withoutImages = total - withImages;
                
                $('#totalPlotsCount').text(total.toLocaleString());
                $('#plotsWithImagesCount').text(withImages.toLocaleString());
                $('#plotsWithoutImagesCount').text(withoutImages.toLocaleString());
            } else {
                console.error("Failed to fetch summary counts:", response.message);
                $('#totalPlotsCount, #plotsWithImagesCount, #plotsWithoutImagesCount').text('N/A');
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error fetching summary counts:", status, error);
            $('#totalPlotsCount, #plotsWithImagesCount, #plotsWithoutImagesCount').text('ERR');
        }
    });
}


$(document).ready(function () {
    
    fetchSummaryCounts(); // 🚨 NEW: เรียกใช้เมื่อโหลดหน้าจอ

    const table = $('#dataTable').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
            url: 'fetch_data.php',
            type: 'POST',
            data: function (d) {
                // 🚨 ปรับปรุง: ส่งค่าตัวกรองรูปภาพไป fetch_data.php ด้วย
                d.year = '<?php echo htmlspecialchars($selected_year); ?>';
                d.agency = '<?php echo htmlspecialchars($selected_agency); ?>'; 
                d.filter_image = $('#filterImageBtn').data('filtering'); // ส่ง 0 หรือ 1
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
            { data: 'remaining_cane_1_img_1', render: d => d ? `<img src="${d}" class="img-thumbnail" alt="">` : '' },
            { data: 'remaining_cane_1_img_2', render: d => d ? `<img src="${d}" class="img-thumbnail" alt="">` : '' },
            { data: 'remaining_cane_2_img_1', render: d => d ? `<img src="${d}" class="img-thumbnail" alt="">` : '' },
            { data: 'remaining_cane_2_img_2', render: d => d ? `<img src="${d}" class="img-thumbnail" alt="">` : '' },
            { data: 'remaining_cane_3_img_1', render: d => d ? `<img src="${d}" class="img-thumbnail" alt="">` : '' },
            { data: 'remaining_cane_3_img_2', render: d => d ? `<img src="${d}" class="img-thumbnail" alt="">` : '' },
            { data: 'notes', render: (data) => data && data.length > 25 ? `<span>${data.substring(0,25)}...</span> <a href="#" class="view-notes-btn" data-notes="${encodeURIComponent(data)}">ดูเพิ่มเติม</a>` : data },
            {
                data: null, orderable: false, searchable: false,
                render: (data, type, row) => `<a href="user_edit_data_remaining.php?plot_id=${row.plot_id}" class="btn btn-warning btn-sm"><i class='bx bx-edit'></i> แก้ไข</a>`
            },
            
        ],
        language: { url: "th.json" }
    });

   // 🚨 ฟังก์ชันจัดการปุ่มกรองรูปภาพ (เหมือนกับไฟล์ประมาณตัน)
    $('#filterImageBtn').on('click', function() {
        const isFiltering = $(this).data('filtering');
        
        if (isFiltering === 0) {
            // เปิดตัวกรอง
            $(this).data('filtering', 1);
            $(this).removeClass('btn-outline-primary').addClass('btn-primary');
            $(this).html('<i class="bi bi-images"></i> กำลังกรอง: แปลงที่มีรูปภาพ');
            $('#resetFilterBtn').show();
        } 
        
        // โหลดข้อมูลตารางใหม่
        table.ajax.reload(); 
    });

    // 🚨 ฟังก์ชันยกเลิกตัวกรอง (เหมือนกับไฟล์ประมาณตัน)
    $('#resetFilterBtn').on('click', function() {
        $('#filterImageBtn').data('filtering', 0);
        $('#filterImageBtn').removeClass('btn-primary').addClass('btn-outline-primary');
        $('#filterImageBtn').html('<i class="bi bi-image"></i> แสดงเฉพาะแปลงที่มีรูปภาพ');
        $(this).hide();
        
        // โหลดข้อมูลตารางใหม่
        table.ajax.reload();
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

    // Export Year (omitted for brevity)
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