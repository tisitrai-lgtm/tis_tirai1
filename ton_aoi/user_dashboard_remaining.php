<?php
session_start();
require("db_connect.php");

$selected_year = $_GET['year'] ?? '';
$selected_agency = $_GET['agency'] ?? '';

if (!$selected_year || !$selected_agency) {
    echo "กรุณาเลือกปีการผลิตและหน่วยงาน/นักส่งเสริมก่อน <a href='user_index.php'>กลับไปหน้าเลือกปี</a>";
    exit;
}

// 🚨 กำหนดประเภทข้อมูลที่หน้านี้แสดง
$data_type = 'remaining'; 
?>

<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ระบบรูปใส่แปลงอ้อยคงเหลือ</title>
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
        body { 
        background-color: #f4f7fc; 
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        font-size: 16px; 
        /* 📌 โค้ดพื้นหลัง */
        background-image: url('icon/bg.jpg'); 
        background-size: cover; 
        background-position: center center; 
        background-attachment: fixed; 
        background-repeat: no-repeat; 
    }
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
            width: 30px; 
            height: 30px; 
            object-fit: cover; border-radius: 8px;
            cursor: pointer; transition: transform 0.2s;
        }
        .img-thumbnail:hover { transform: scale(1.05); }

        .notes-preview { display: block; margin-bottom: 5px; }
        .view-notes-btn { font-size: 0.85em; text-decoration: none; color: #007bff; }
        #modalNotesContent { white-space: pre-wrap; word-wrap: break-word; }

        /* 🚨 NEW: สไตล์สำหรับ Dashboard Cards (การคลิกและการเลือก) */
        .summary-card {
            cursor: pointer; 
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .summary-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        /* สไตล์ของการ์ดที่ถูกเลือก */
        .summary-card.selected {
            border: 3px solid #ffc107; 
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.25) !important;
            transform: scale(1.02);
        }
        .card-body { padding: 1rem; }
        .card-title { font-size: 1.1rem; }
        .card-text { margin: 0; }
        
        /* แก้ไข Modal รูปภาพไม่ให้ล้นในกรณีที่รูปภาพสูงมาก */
        .modal-image-custom .modal-content {
            max-height: 90vh; 
            height: auto; 
            overflow-y: auto; 
        }
        .modal-image-custom .modal-body {
            padding-top: 1rem;
            padding-bottom: 1rem;
        }
        .modal-image-custom .modal-body .img-fluid {
            max-height: 80vh; 
            width: auto;
            object-fit: contain;
            display: block; 
            margin: auto; 
        }
    </style>
</head>
<body>
<?php require("user_nav.php"); ?>

<div class="container">
    <hr>
    <h5 class="text-center">
        <i class='bx bx-leaf'></i> ข้อมูลแปลงอ้อย คงเหลือ (Remaining Cane)
    </h5>

    <h6 class="text-center text-primary">
        <i class='bx bx-calendar'></i> ปีการผลิต: <strong><?php echo htmlspecialchars($selected_year); ?></strong>
        <span class="mx-2">|</span> 
        <i class="bi bi-person-badge"></i> หน่วยนักส่งเสริม: <strong><?php echo htmlspecialchars($selected_agency); ?></strong>
    </h6>

    <hr>
    
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-info mb-3 shadow summary-card selected" data-filter-type="all">
                <div class="card-body">
                    <h5 class="card-title"><i class='bx bx-chart'></i> จำนวนแปลงทั้งหมด</h5>
                    <p class="card-text display-4" id="totalPlotsCount">0</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success mb-3 shadow summary-card" data-filter-type="has_image">
                <div class="card-body">
                    <h5 class="card-title"><i class='bx bx-check-square'></i> แปลงที่มีรูปภาพ (Remaining)</h5>
                    <p class="card-text display-4" id="plotsWithImagesCount">0</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-danger mb-3 shadow summary-card" data-filter-type="no_image">
                <div class="card-body">
                    <h5 class="card-title"><i class='bx bx-x-octagon'></i> แปลงที่ยังไม่มีรูปภาพ (Remaining)</h5>
                    <p class="card-text display-4" id="plotsWithoutImagesCount">0</p>
                </div>
            </div>
        </div>
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
    <div class="modal-dialog modal-dialog-centered modal-lg modal-image-custom">
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
// ตัวแปร Global สำหรับเก็บสถานะตัวกรองปัจจุบัน
let currentFilterType = 'all'; 

// 🚨 FUNCTION: ดึงข้อมูลสรุป Dashboard (ส่ง data_type: 'remaining' ไป)
function fetchSummaryCounts() {
    $.ajax({
        url: 'fetch_summary_counts.php', 
        type: 'POST',
        dataType: 'json',
        data: {
            year: '<?php echo htmlspecialchars($selected_year); ?>',
            agency: '<?php echo htmlspecialchars($selected_agency); ?>',
            data_type: '<?php echo $data_type; ?>' // 🚨 ส่งประเภทข้อมูลไป
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
    
    fetchSummaryCounts(); 

    const table = $('#dataTable').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
            url: 'fetch_data.php',
            type: 'POST',
            data: function (d) {
                // ส่งค่าตัวกรองใหม่
                d.year = '<?php echo htmlspecialchars($selected_year); ?>';
                d.agency = '<?php echo htmlspecialchars($selected_agency); ?>'; 
                d.filter_type = currentFilterType; // ส่ง 'all', 'has_image', หรือ 'no_image'
                d.data_type = '<?php echo $data_type; ?>'; // 🚨 ส่งประเภทข้อมูลไป
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
            // 🚨 คอลัมน์รูปภาพ 6 คอลัมน์สำหรับ Remaining Cane
            { data: 'remaining_cane_1_img_1', render: d => d ? `<img src="${d}" class="img-thumbnail" alt="">` : '' },
            { data: 'remaining_cane_1_img_2', render: d => d ? `<img src="${d}" class="img-thumbnail" alt="">` : '' },
            { data: 'remaining_cane_2_img_1', render: d => d ? `<img src="${d}" class="img-thumbnail" alt="">` : '' },
            { data: 'remaining_cane_2_img_2', render: d => d ? `<img src="${d}" class="img-thumbnail" alt="">` : '' },
            { data: 'remaining_cane_3_img_1', render: d => d ? `<img src="${d}" class="img-thumbnail" alt="">` : '' },
            { data: 'remaining_cane_3_img_2', render: d => d ? `<img src="${d}" class="img-thumbnail" alt="">` : '' },
            { data: 'notes', render: (data) => data && data.length > 25 ? `<span>${data.substring(0,25)}...</span> <a href="#" class="view-notes-btn" data-notes="${encodeURIComponent(data)}">ดูเพิ่มเติม</a>` : data },
            {
                data: null, orderable: false, searchable: false,
                // 🚨 ลิงก์ไปยังหน้าแก้ไขสำหรับ Remaining Cane
                render: (data, type, row) => `<a href="user_edit_data_remaining.php?plot_id=${row.plot_id}" class="btn btn-warning btn-sm"><i class='bx bx-edit'></i> แก้ไข</a>`
            },
            
        ],
        language: { url: "th.json" }
    });

    // Event Listener สำหรับคลิกที่ Dashboard Cards
    $('.summary-card').on('click', function() {
        const newFilterType = $(this).data('filter-type');
        
        // 1. อัปเดตสถานะตัวกรอง
        currentFilterType = newFilterType;

        // 2. จัดการ Style (เลือก/ไม่เลือก)
        $('.summary-card').removeClass('selected');
        $(this).addClass('selected');
        
        // 3. โหลดข้อมูลตารางใหม่
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

    // ลบ logic สำหรับปุ่ม 'filterImageBtn' และ 'resetFilterBtn' เดิมออก เพราะใช้ Card แทนแล้ว

    // Omitted Export Year logic for cleaner code
    // ...

});
</script>

</body>
</html>