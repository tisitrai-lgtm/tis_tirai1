<?php
session_start();
require("db_connect.php");

$selected_year = $_GET['year'] ?? '';
$selected_agency = $_GET['agency'] ?? '';

if (!$selected_year || !$selected_agency) {
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
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <style>
        /* BASE STYLES & FONT */
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
            margin-bottom: 30px; /* เพิ่ม Margin ด้านล่าง */
        }

        /* TABLE & DATATABLES */
        .table thead { background-color: #1a4d7c; color: #fff; text-align: center; } /* เปลี่ยนสี Header ให้เข้ากับระบบ */
        .table td, .table th { text-align: center; vertical-align: middle; }
        
        .dataTables_wrapper .dataTables_paginate .pagination {
            justify-content: center; /* จัดหน้า Pagination ให้อยู่ตรงกลางบนมือถือ */
        }

        .img-thumbnail {
            width: 30px; 
            height: 30px; 
            object-fit: cover; border-radius: 8px;
            cursor: pointer; transition: transform 0.2s;
        }
        .img-thumbnail:hover { transform: scale(1.05); }

        /* SUMMARY CARDS */
        .summary-card {
            cursor: pointer; 
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .summary-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        .summary-card.selected {
            border: 3px solid #ffc107; 
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.25) !important;
            transform: scale(1.02);
        }
        .card-body { padding: 1rem; }
        .card-title { font-size: 1.1rem; }
        .card-text { margin: 0; }
        
        /* Modal Style */
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

        /* ----------------------------------------------------------- */
        /* 📱 MOBILE RESPONSIVE ADJUSTMENTS (SM - Small screens)        */
        /* ----------------------------------------------------------- */
        @media (max-width: 767.98px) {
            .container {
                padding: 15px; /* ลด Padding ของ Container บนมือถือ */
                margin-top: 15px;
                margin-bottom: 15px;
            }

            .system-title h5 {
                font-size: 1.25rem; /* ลดขนาดหัวข้อหลัก */
            }

            /* 🚨 ปรับ Card Summary ให้อยู่ในคอลัมน์เดียวบนมือถือ */
            .row.mb-4 > .col-md-4 {
                flex: 0 0 100%;
                max-width: 100%;
            }
            
            .summary-card {
                margin-bottom: 15px !important;
            }
            .card-title { 
                font-size: 1rem; /* ลดขนาดหัวข้อ Card */
            }
            .card-text.display-4 {
                font-size: 2.5rem; /* ลดขนาดตัวเลขสถิติ */
            }

            /* 🚨 ปรับปรุง DataTables ให้เหมาะกับมือถือ */
            .table-responsive {
                overflow-x: auto; 
            }
            #dataTable {
                width: 100% !important; /* บังคับความกว้างให้ DataTables */
                min-width: 800px; /* กำหนดความกว้างขั้นต่ำเพื่อให้ Scroll ได้ */
                font-size: 0.8rem; /* ลดขนาด Font ในตาราง */
            }
            .table th, .table td {
                padding: 0.5rem; /* ลด Padding ในตาราง */
            }

            /* ซ่อนบางคอลัมน์บนมือถือ (Optional: ต้องกำหนดใน DataTables Column Definitions) */
            /* ตัวอย่าง: ถ้าต้องการซ่อน 'ปีผลิต' และ 'หน่วยงาน' ใน CSS */
            /*
            #dataTable th:nth-child(1), #dataTable td:nth-child(1),
            #dataTable th:nth-child(3), #dataTable td:nth-child(3) {
                display: none;
            }
            */
            
            /* ปรับขนาดปุ่ม 'แก้ไข' */
            .btn-sm {
                padding: 0.3rem 0.5rem;
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>
<?php require("user_nav.php"); ?>

<div class="container">
    <hr>
    <h5 class="text-center">
        <i class='bx bx-droplet'></i> ข้อมูลแปลงอ้อย ประมาณตันอ้อย
    </h5>

    <h6 class="text-center text-primary mb-4">
        <i class='bx bx-calendar'></i> ปีการผลิต: <strong><?php echo htmlspecialchars($selected_year); ?></strong>
        <span class="mx-2">|</span> 
        <i class="bi bi-person-badge"></i> หน่วยนักส่งเสริม: <strong><?php echo htmlspecialchars($selected_agency); ?></strong>
    </h6>

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
                    <h5 class="card-title"><i class='bx bx-check-square'></i> แปลงที่มีรูปภาพ</h5>
                    <p class="card-text display-4" id="plotsWithImagesCount">0</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-danger mb-3 shadow summary-card" data-filter-type="no_image">
                <div class="card-body">
                    <h5 class="card-title"><i class='bx bx-x-octagon'></i> แปลงที่ยังไม่มีรูปภาพ</h5>
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
                    <th>ประมาณตัน รูปหมุด</th>
                    <th>ประมาณตัน รูปอ้อย</th>
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

// ฟังก์ชันดึงข้อมูลสรุป Dashboard
function fetchSummaryCounts() {
    $.ajax({
        url: 'fetch_summary_counts.php', 
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
            }
        },
        scrollX: true, // 🚨 สำคัญ: เปิด Scroll X
        // 🚨 Responsive: กำหนดว่าคอลัมน์ใดสำคัญมากน้อยเพียงใด
        // (R1: High priority, R2: Medium, R3: Low)
        columnDefs: [
            { targets: 0, responsivePriority: 3 }, // ปีผลิต
            { targets: 1, responsivePriority: 2 }, // นักส่งเสริม (สำคัญบนมือถือ)
            { targets: 2, responsivePriority: 3 }, // หน่วยงาน
            { targets: 3, responsivePriority: 1 }, // เลขสัญญา (สำคัญที่สุด)
            { targets: 4, responsivePriority: 4 }, // โควต้า
            { targets: 5, responsivePriority: 2 }, // ID แปลง
            { targets: 6, responsivePriority: 4 }, // พื้นที่ (ไร่)
            { targets: 7, responsivePriority: 4 }, // ชนิดอ้อย
            { targets: 8, responsivePriority: 1 }, // รูปหมุด (สำคัญ)
            { targets: 9, responsivePriority: 1 }, // รูปอ้อย (สำคัญ)
            { targets: 10, responsivePriority: 5 }, // หมายเหตุ
            { targets: 11, responsivePriority: 1 }, // แก้ไข (ปุ่มสำคัญ)
        ],
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
            { data: 'estimate_ton_1', render: d => d ? `<img src="${d}" class="img-thumbnail" alt="">` : '' },
            { data: 'estimate_ton_2', render: d => d ? `<img src="${d}" class="img-thumbnail" alt="">` : '' },
            { data: 'notes', render: (data) => data && data.length > 25 ? `<span>${data.substring(0,25)}...</span> <a href="#" class="view-notes-btn" data-notes="${encodeURIComponent(data)}">ดูเพิ่มเติม</a>` : data },
            {
                data: null, orderable: false, searchable: false,
                render: (data, type, row) => `<a href="user_edit_data.php?plot_id=${row.plot_id}" class="btn btn-warning btn-sm"><i class='bx bx-edit'></i> แก้ไข</a>`
            },
            
        ],
        language: { url: "th.json" }
    });
    
    // **หมายเหตุ:** ถึงแม้จะมีการกำหนด responsivePriority แต่เนื่องจากเราใช้ `scrollX: true` 
    // DataTables จะเลือกแสดงผลแบบ Scroll แนวนอนแทนการซ่อนคอลัมน์โดยอัตโนมัติ 
    // ซึ่งมักจะให้ประสบการณ์ที่ดีกว่าสำหรับการแสดงข้อมูลจำนวนมากบนมือถือ

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

});
</script>

</body>
</html>