<?php
session_start();
require("db_connect.php");

$selected_year = $_GET['year'] ?? '';

if (!$selected_year) {
    echo "กรุณาเลือกปีการผลิตก่อน <a href='index.php'>กลับไปหน้าเลือกปี</a>";
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

        /* === NEW CSS FOR MODERN MODAL === */
        
        .custom-modal-header {
            background: linear-gradient(90deg, #007bff, #00c6ff);
            color: white; padding: 1rem 1.5rem; border-bottom: none;
            border-top-left-radius: 0.5rem; border-top-right-radius: 0.5rem;
        }
        .custom-modal-header .btn-close { filter: invert(1); box-shadow: none; }

        .detail-card {
            border: 1px solid #e0e0e0; border-radius: 10px; margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); padding: 15px;
        }
        .detail-card h6 {
            font-weight: bold; color: #007bff; border-bottom: 2px solid #007bff30;
            padding-bottom: 5px; margin-bottom: 10px; display: flex; align-items: center;
        }
        .detail-card h6 i { font-size: 1.2rem; margin-right: 8px; }
        
        .data-row {
            display: flex; justify-content: space-between; padding: 5px 0;
            border-bottom: 1px dotted #f0f0f0;
        }
        .data-row:last-child { border-bottom: none; }
        .data-label { font-weight: 500; color: #6c757d; }
        .data-value { font-weight: bold; color: #343a40; text-align: right; }
        .highlight-value { color: #28a745; font-size: 1.1rem; }

        /* ปรับ Image Grid */
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); 
            gap: 15px;
            margin-top: 10px;
        }
        .plot-image-thumbnail {
            width: 100%;
            height: 120px; 
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #ccc;
            cursor: pointer;
            transition: border-color 0.2s;
        }
        .plot-image-thumbnail:hover { border-color: #007bff; }

        .btn-modern-close {
            background-color: #6c757d; border-color: #6c757d; color: white;
            transition: all 0.2s ease;
        }
        .btn-modern-close:hover { background-color: #5a6268; border-color: #545b62; }
        
        /* Custom size for image modal (Max 600px wide) */
        .modal-image-custom {
            max-width: 600px; 
        }
        .modal-image-custom .modal-content {
            max-height: 600px; 
        }
        .modal-image-custom .modal-body .img-fluid {
            max-height: 500px; 
            width: auto;
            object-fit: contain;
        }
    </style>
</head>
<body>
<?php require("nav.php"); ?>

<div class="container">
    <hr>
    <h5 class="text-center">
        <i class='bx bx-droplet'></i> ข้อมูลแปลงอ้อย ประมาณตันอ้อย
    </h5>

    <h6 class="text-center text-primary">
        <i class='bx bx-calendar'></i> ปีการผลิต: <strong><?php echo htmlspecialchars($selected_year); ?></strong>
    </h6>
    
    <div class="row justify-content-center mb-4 mt-4">
        <div class="col-lg-4 col-md-6 col-sm-12 mb-3">
            <div class="card text-white bg-primary shadow-sm h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="card-title fw-light"><i class="bi bi-box-seam me-2"></i> จำนวนแปลงอ้อยทั้งหมด</h6>
                        <p class="card-text display-4 fw-bold mb-0" id="total_plots">กำลังโหลด...</p>
                    </div>
                    <i class="bi bi-map fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 col-md-6 col-sm-12 mb-3">
            <div class="card text-white bg-success shadow-sm h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="card-title fw-light"><i class="bi bi-check-circle me-2"></i> แปลงที่มีรูปภาพแล้ว</h6>
                        <p class="card-text display-4 fw-bold mb-0" id="plots_with_image">กำลังโหลด...</p>
                    </div>
                    <i class="bi bi-image fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 col-md-6 col-sm-12 mb-3">
            <div class="card text-white bg-danger shadow-sm h-100">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="card-title fw-light"><i class="bi bi-x-circle me-2"></i> แปลงที่ยังไม่มีรูปภาพ</h6>
                        <p class="card-text display-4 fw-bold mb-0" id="plots_without_image">กำลังโหลด...</p>
                    </div>
                    <i class="bi bi-camera-off fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end mb-3">
        <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#exportYearModal">
            <i class="bi bi-file-earmark-spreadsheet"></i> Export Excel
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
                    <th>สถานะรูปภาพ</th> 
                    <th>รายละเอียด</th> 
                    <th>แก้ไข</th>
                </tr>
            </thead>
        </table>
    </div>

<div class="modal fade" id="plotDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header custom-modal-header">
                <h5 class="modal-title"><i class="bi bi-info-circle-fill me-2"></i> รายละเอียดแปลงอ้อย: <span id="modalPlotId"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="detail-card h-100">
                            <h6><i class="bi bi-card-list"></i> ข้อมูลพื้นฐาน</h6>
                            <div class="data-row">
                                <span class="data-label">ปีการผลิต:</span>
                                <span class="data-value" id="detailYear"></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">นักส่งเสริม:</span>
                                <span class="data-value" id="detailEmpNumber"></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">หน่วยงาน:</span>
                                <span class="data-value" id="detailAgency"></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">เลขที่สัญญา:</span>
                                <span class="data-value" id="detailContractNumber"></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">โควต้า:</span>
                                <span class="data-value" id="detailQuota"></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">พื้นที่ (ไร่):</span>
                                <span class="data-value" id="detailRaiArea"></span>
                            </div>
                            <div class="data-row">
                                <span class="data-label">ชนิดอ้อย:</span>
                                <span class="data-value" id="detailSugaType"></span>
                            </div>
                            <hr class="mt-3 mb-2">
                        </div>
                    </div>
                    
                    <div class="col-lg-6">
                        <div class="detail-card h-100">
                            <h6><i class="bi bi-pencil-square"></i> หมายเหตุ</h6>
                            <p id="detailNotes" class="text-start"></p>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-12">
                        <div class="detail-card">
                            <h6><i class="bi bi-image-fill"></i> ข้อมูลรูปภาพทั้งหมด (คลิกเพื่อขยาย)</h6>
                            <div class="image-grid" id="detailImageGrid">
                                </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-modern-close" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-image-custom"> <div class="modal-content">
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

<div class="modal fade" id="exportYearModal" tabindex="-1" aria-labelledby="exportYearModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="exportYearModalLabel"><i class="bi bi-file-earmark-excel"></i> เลือกปีสำหรับ Export ข้อมูล</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>กรุณาเลือกปีการผลิตที่ต้องการ Export ข้อมูลทั้งหมด</p>
                <select class="form-select" id="export_year_select"></select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-success" id="confirmExportBtn">ยืนยันการ Export</button>
            </div>
        </div>
    </div>
</div>

</div>

<script>
// ฟังก์ชันสำหรับ Load ข้อมูลและเปิด Modal
function loadPlotDetails(plotData) {
    // 1. นำข้อมูลมาใส่ใน Modal Header
    $('#modalPlotId').text(plotData.plot_id);

    // 2. นำข้อมูลพื้นฐานมาใส่ใน Modal Body
    $('#detailYear').text(plotData.production_year);
    $('#detailEmpNumber').text(plotData.emp_number);
    $('#detailAgency').text(plotData.agency); 
    $('#detailContractNumber').text(plotData.contract_number);
    $('#detailQuota').text(plotData.quota);
    $('#detailRaiArea').text(plotData.rai_area + ' ไร่');
    $('#detailSugaType').text(plotData.suga_type);

    // 3. หมายเหตุ
    const formattedNotes = (plotData.notes || ' - ').replace(/\n/g, '<br>');
    $('#detailNotes').html(formattedNotes);

    // 4. รูปภาพทั้งหมด (ดึงข้อมูลจาก RowData และแสดงเป็น Thumbnail)
    const imageGrid = $('#detailImageGrid');
    imageGrid.empty(); 

    // รายการคอลัมน์รูปภาพทั้งหมด (ต้องสอดคล้องกับ fetch_data.php)
    const imageColumns = [
        { data: 'estimate_ton_1', label: 'ประมาณตัน (หมุด)' }, 
        { data: 'estimate_ton_2', label: 'ประมาณตัน (อ้อย)' }, 
        { data: 'evaluate_ton_1', label: 'ประเมินตัน (รูป 1)' },
        { data: 'evaluate_ton_2', label: 'ประเมินตัน (รูป 2)' },
        { data: 'remaining_cane_1_img_1', label: 'คงเหลือ 1 (รูป 1)' },
        { data: 'remaining_cane_1_img_2', label: 'คงเหลือ 1 (รูป 2)' },
        { data: 'remaining_cane_2_img_1', label: 'คงเหลือ 2 (รูป 1)' },
        { data: 'remaining_cane_2_img_2', label: 'คงเหลือ 2 (รูป 2)' },
        { data: 'remaining_cane_3_img_1', label: 'คงเหลือ 3 (รูป 1)' },
        { data: 'remaining_cane_3_img_2', label: 'คงเหลือ 3 (รูป 2)' },
    ];
    
    let hasImage = false;
    imageColumns.forEach(col => {
        const url = plotData[col.data];
        if (url) {
            hasImage = true;
            const imgHtml = `
                <div class="text-center">
                    <img src="${url}" 
                          class="plot-image-thumbnail" 
                          alt="${col.label}"> 
                    <small class="text-muted d-block mt-1">${col.label}</small>
                </div>
            `;
            imageGrid.append(imgHtml);
        }
    });

    if (!hasImage) {
        imageGrid.html('<div class="col-12 text-center text-muted py-3">ไม่มีรูปภาพในแปลงนี้</div>');
    }

    // 5. แสดง Modal
    new bootstrap.Modal(document.getElementById('plotDetailsModal')).show();
}

// 🚨 NEW: ฟังก์ชันสำหรับดึงข้อมูลสถิติ Stats Cards
function fetchStats() {
    const year = '<?php echo htmlspecialchars($selected_year); ?>';
    
    // แสดง loading state ขณะรอข้อมูล
    $('#total_plots').text('...');
    $('#plots_with_image').text('...');
    $('#plots_without_image').text('...');

    $.ajax({
        url: 'fetch_stats.php', // เรียกไฟล์ที่เราสร้างใหม่
        type: 'GET',
        data: { year: year },
        dataType: 'json',
        success: function(data) {
            if (data.error) {
                console.error("Error fetching stats:", data.error);
                $('#total_plots').text('N/A');
                $('#plots_with_image').text('N/A');
                $('#plots_without_image').text('N/A');
                return;
            }
            // อัปเดต Stats Cards ด้วยตัวเลขจริง
            $('#total_plots').text(data.total_plots.toLocaleString());
            $('#plots_with_image').text(data.plots_with_image.toLocaleString());
            $('#plots_without_image').text(data.plots_without_image.toLocaleString());
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error fetching stats:", status, error);
            $('#total_plots').text('Error');
            $('#plots_with_image').text('Error');
            $('#plots_without_image').text('Error');
        }
    });
}


$(document).ready(function () {
    // 🚨 NEW: เรียกใช้ฟังก์ชันดึงสถิติเมื่อหน้าเว็บโหลดเสร็จ
    fetchStats();
    
    const table = $('#dataTable').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
            url: 'fetch_data.php', // ใช้ไฟล์ที่คุณส่งมา
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
            // สถานะรูปภาพ
            { 
                data: null, 
                orderable: false, 
                searchable: false,
                render: function(data, type, row) {
                    const imageFields = [
                        'estimate_ton_1', 'estimate_ton_2', 
                        'evaluate_ton_1', 'evaluate_ton_2', 
                        'remaining_cane_1_img_1', 'remaining_cane_1_img_2', 
                        'remaining_cane_2_img_1', 'remaining_cane_2_img_2', 
                        'remaining_cane_3_img_1', 'remaining_cane_3_img_2'
                    ];
                    const hasImage = imageFields.some(field => row[field]);
                    
                    if (hasImage) {
                        return '<span class="badge bg-success"><i class="bx bx-check-circle"></i> มีรูปภาพ</span>';
                    } else {
                        return '<span class="badge bg-danger"><i class="bx bx-x-circle"></i> ไม่มีรูปภาพ</span>';
                    }
                }
            },
            {
                data: null, orderable: false, searchable: false,
                render: (data, type, row) => `<button class="btn btn-info btn-sm view-details-btn" data-plot-id="${row.plot_id}"><i class='bx bx-search'></i> รายละเอียด</button>`
            },
            {
                data: null, orderable: false, searchable: false,
                render: (data, type, row) => `<a href="edit_data.php?plot_id=${row.plot_id}" class="btn btn-warning btn-sm"><i class='bx bx-edit'></i> แก้ไข</a>`
            },
            
        ],
        language: { url: "th.json" }
    });

    
    // Event: เปิด Modal รายละเอียดแปลงอ้อย
    $('#dataTable').on('click', '.view-details-btn', function() {
        const rowData = table.row($(this).parents('tr')).data();
        loadPlotDetails(rowData);
    });


    // 🚨 FIX: เปิดดูรูปภาพ (สำหรับ element ที่สร้างแบบ Dynamic และทำงานขณะ Modal อื่นเปิดอยู่)
    $(document).on('click', '.plot-image-thumbnail', function() {
        // 1. ตั้งค่า Source ของรูปภาพขยาย
        $('#modalImage').attr('src', $(this).attr('src'));
        
        // 2. ซ่อน Modal รายละเอียดชั่วคราว (เพื่อแก้ปัญหา Z-index และการ Focus)
        $('#plotDetailsModal').modal('hide'); 

        // 3. เปิด Modal รูปภาพ
        const imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
        imageModal.show();
    });

    // 🚨 FIX: Event ที่จะเปิด Modal รายละเอียดกลับคืนมาเมื่อ Modal รูปภาพถูกปิด
    $('#imageModal').on('hidden.bs.modal', function () {
        // เปิด Modal รายละเอียดกลับคืนมา (ใช้ setTimeout เพื่อให้แน่ใจว่า Modal รูปภาพปิดสมบูรณ์แล้ว)
        setTimeout(() => {
            $('#plotDetailsModal').modal('show');
        }, 300);
    });

    // ส่วนของ Export (ใช้โค้ดที่คุณมี)
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