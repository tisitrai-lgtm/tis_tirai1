<?php
// dashboard.php (ปรับปรุง: รองรับมือถือ, ลบส่วน Infographic ทั้งหมด)
session_start();
require("db_connect.php");
// require("nav.php"); // จะถูกเรียกในส่วน HTML

$selected_year = $_GET['year'] ?? '';

if (!$selected_year) {
    echo "กรุณาเลือกปีการผลิตก่อน <a href='index.php'>กลับไปหน้าเลือกปี</a>";
    exit;
}

// 📌 ลบโค้ด PHP ส่วน Infographic: ดึงตัวเลือก Filter (ไม่จำเป็นแล้ว)
// $years = [];
// $agencies = [];
// $suga_types = [];
// ... (โค้ดดึงข้อมูลปี, หน่วยงาน, ชนิดอ้อยถูกลบออก)
// $default_year = $selected_year; 
// $default_agency = ''; 
// $default_suga_type = ''; 

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <style>
    /* -------------------------------------------------------------------------- */
    /* GLOBAL & DESKTOP STYLES */
    /* -------------------------------------------------------------------------- */
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
    .container {
        background-color: #fff; 
        border-radius: 15px; 
        padding: 20px; /* 📌 ปรับลด Padding สำหรับมือถือ */
        box-shadow: 0 4px 12px rgba(63,63,63,0.1); 
        margin-top: 20px; /* 📌 ปรับลด Margin สำหรับมือถือ */
        margin-bottom: 20px; /* 📌 ปรับลด Margin สำหรับมือถือ */
    }
    /* 🚨 ลบ Infographic Styles ที่ไม่ใช้แล้ว */
    /* .infographic-card, #plotsPieChart, .filter-section, .summary-box, .loading-overlay ถูกลบออก */
    
    /* DataTables Custom Styles */
    #dataTable_filter input {
        border-radius: 0.5rem;
        border: 1px solid #ced4da;
        padding: 0.375rem 0.75rem;
        margin-left: 0.5rem;
    }
    .btn-save-modern {
        background-color: #28a745;
        color: white;
        border: none;
        border-radius: 0.5rem;
        padding: 0.5rem 1rem;
        transition: background-color 0.3s;
    }
    .btn-save-modern:hover {
        background-color: #218838;
        color: white;
    }
    .btn-export-modern {
        background-color: #17a2b8;
        color: white;
        border: none;
        border-radius: 0.5rem;
        padding: 0.5rem 1rem;
        transition: background-color 0.3s;
    }
    .btn-export-modern:hover {
        background-color: #138496;
        color: white;
    }
    /* Modal Styles */
    .custom-modal-header {
        background-color: #055fb4ff ;
        color: white;
        border-top-left-radius: calc(0.5rem - 1px);
        border-top-right-radius: calc(0.5rem - 1px);
    }
    .modal-image-custom {
        /* 📌 ปรับให้ Modal รูปภาพไม่ใหญ่เกินไปบนมือถือ */
        max-width: 95% !important; 
        margin: 1.75rem auto;
    }
    .detail-card {
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        height: 100%; 
    }
    .data-row {
        display: flex;
        justify-content: space-between;
        padding: 5px 0;
        border-bottom: 1px dashed #dee2e6;
    }
    .data-label {
        font-weight: 600;
        color: #495057;
        /* 📌 ปรับให้แสดงชื่อคอลัมน์ได้ดีขึ้นบนมือถือ */
        flex-shrink: 0; 
        margin-right: 10px;
    }
    .data-value {
        color: #000;
        text-align: right;
        word-break: break-word; /* 📌 ป้องกันข้อความยาวเกิน */
    }
    .image-grid {
        display: grid;
        /* 📌 ปรับให้แสดงรูปภาพ 2-3 คอลัมน์บนมือถือ */
        grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); 
        gap: 10px;
        padding: 10px;
        border: 1px solid #ced4da;
        border-radius: 8px;
    }
    .plot-image-thumbnail {
        width: 100%;
        height: 80px;
        object-fit: cover;
        cursor: pointer;
        border-radius: 5px;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .plot-image-thumbnail:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }
    #plotDetailsModal .modal-body {
        max-height: calc(100vh - 120px); /* 📌 ปรับลดความสูงสำหรับมือถือ */
        overflow-y: auto; 
    }
    
    /* -------------------------------------------------------------------------- */
    /* MOBILE STYLES */
    /* -------------------------------------------------------------------------- */
    @media (max-width: 767.98px) {
        .container {
            padding: 15px;
            margin-top: 15px;
            margin-bottom: 15px;
        }
        h3, h5, h4 {
            font-size: 1.25rem; /* ปรับขนาดหัวข้อ */
        }
        .d-flex.justify-content-between.flex-wrap > div,
        .d-flex.justify-content-between.flex-wrap > .d-flex {
            width: 100%;
            margin-bottom: 10px;
        }
        .form-select.w-auto {
            width: 100% !important;
            min-width: unset !important;
        }
        .btn-save-modern, .btn-export-modern {
            flex-grow: 1; /* ทำให้ปุ่มเต็มความกว้าง */
        }
    }

    @media (max-width: 991.98px) {
        /* (สไตล์เดิมสำหรับ Navbar ถูกคงไว้) */
        .navbar-nav {
            padding-top: 10px;
            padding-bottom: 10px;
        }
        .nav-item {
            margin-bottom: 5px; 
        }
        .nav-link {
            padding: 0.7rem 1rem;
            border-bottom: none !important;
        }
        .nav-info-badge {
            display: block; 
            margin-bottom: 10px;
            margin-right: 0;
            text-align: center;
            font-size: 1rem;
            font-weight: 500;
        }
        .btn-logout {
            width: 100%;
            margin-top: 10px;
        }
    }
    </style>
</head>
<body>
<?php require("nav.php"); ?>

<div class="container">
    <h3 class="text-center text-primary mb-4">
        <i class='bx bx-bar-chart-alt-2'></i> ข้อมูลแปลงอ้อย 
    </h3>

    <h5 class="text-center text-success mb-4">
        <i class='bx bx-calendar'></i> ปีการผลิต: <strong><?php echo htmlspecialchars($selected_year); ?></strong>
    </h5>
    
    <hr class="my-4">
    <h4 class="text-start text-primary mb-3"><i class='bx bx-droplet'></i> รายการแปลงอ้อยทั้งหมด</h4>

    <div class="d-flex justify-content-between mb-3 flex-wrap"> 
        <div class="d-flex align-items-center gap-2 mb-2 mb-md-0 w-100 w-md-auto">
            <label for="imageStatusFilter" class="me-1 text-muted fw-bold">ตัวกรองรูปภาพ:</label>
            <select id="imageStatusFilter" class="form-select w-auto" style="min-width: 150px;">
                <option value="">-- สถานะทั้งหมด --</option>
                <option value="has_image">มีรูปภาพ</option>
                <option value="no_image">ไม่มีรูปภาพ</option>
            </select>
        </div>
        <br>
        <br>
        <br>
        <div class="d-flex gap-2 w-100 w-md-auto mt-2 mt-md-0">
            <a href="insertForm.php?year=<?php echo urlencode($selected_year); ?>" class="btn btn-save-modern">
                <i class='bx bx-plus-circle me-1'></i> เพิ่มแปลง
            </a>
            <a href="export_excel.php?year=<?php echo urlencode($selected_year); ?>" class="btn btn-export-modern" target="_blank">
                <i class='bx bxs-file-export me-1'></i> Excel
            </a>
        </div>
    </div>
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
                    <th class="text-center">จัดการ</th> </tr>
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
                                 <div class="data-row">
                                    <span class="data-label">ตันต่อ (ไร่):</span>
                                    <span class="data-value" id="detailTonRai"></span>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-image-custom"> 
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

</div> 
<script>
// 📌 ลบ let pieChart; ออก

// 🚨 ลบ Infographic Functions ทั้งหมดออก (loadInfographicData, displaySummary, renderPieChart)

// --- Dashboard DataTables Functions (ส่วนแก้ไขพาธรูปภาพ) ---
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
    $('#detailTonRai').text(plotData.ton_rai + ' ไร่');
    const formattedNotes = (plotData.notes || ' - ').replace(/\n/g, '<br>');
    $('#detailNotes').html(formattedNotes);

    // 3. รูปภาพทั้งหมด (ดึง URL ที่สมบูรณ์จาก JSON โดยตรง)
    const imageGrid = $('#detailImageGrid');
    imageGrid.empty(); 

    // รายการคอลัมน์รูปภาพทั้งหมด (ต้องสอดคล้องกับ fetch_data_admin.php ที่สร้าง URL ไว้แล้ว)
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
        // 📌 ดึง URL ที่สมบูรณ์จาก JSON (คาดว่า Server-Side สร้างมาให้แล้ว)
        const url = plotData[col.data]; 
        
        if (url && url.trim() !== '' && url.indexOf('no_image.png') === -1) { // ตรวจสอบ URL ที่ถูกต้องและไม่ใช่ no_image
            hasImage = true;
            const imgHtml = `
                <div class="text-center">
                    <img src="${url}" 
                            class="plot-image-thumbnail" 
                            alt="${col.label}"
                            title="${col.label}"
                            onerror="this.onerror=null;this.src='icon/no_image.png';"> 
                    <small class="text-muted d-block mt-1">${col.label}</small>
                </div>
            `;
            imageGrid.append(imgHtml);
        }
    });

    if (!hasImage) {
        imageGrid.html('<div class="col-12 text-center text-muted py-3">ไม่พบรูปภาพในแปลงนี้</div>');
    }

    // 4. แสดง Modal
    new bootstrap.Modal(document.getElementById('plotDetailsModal')).show();
}

$(document).ready(function () {
    // 1. DataTables Setup
    const table = $('#dataTable').DataTable({
        serverSide: true,
        processing: true,
        ajax: {
            url: 'fetch_data_admin.php', 
            type: 'POST',
            data: function (d) {
                d.year = '<?php echo htmlspecialchars($selected_year); ?>';
                d.image_status_filter = $('#imageStatusFilter').val(); 
            }
        },
        scrollX: true, // 📌 สำคัญมากสำหรับมือถือ เพื่อให้ตารางเลื่อนในแนวนอนได้
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
            // คอลัมน์สถานะรูปภาพ
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
                    // ตรวจสอบว่ามีข้อมูล URL ในคอลัมน์รูปภาพใด ๆ หรือไม่
                    const hasImage = imageFields.some(field => row[field] && row[field].trim() !== '');
                    
                    if (hasImage) {
                        return '<span class="badge bg-success"><i class="bx bx-check-circle"></i> มีรูปภาพ</span>';
                    } else {
                        return '<span class="badge bg-danger"><i class="bx bx-x-circle"></i> ไม่มีรูปภาพ</span>';
                    }
                }
            },
            // คอลัมน์จัดการ (รวม รายละเอียด, แก้ไข, ลบ)
            { 
                data: null, 
                orderable: false, 
                searchable: false,
                className: 'text-center',
                render: function(data, type, row) {
                    const plotId = encodeURIComponent(row.plot_id);
                    // 📌 สมมติว่า fetch_data_admin.php ส่ง Primary Key กลับมาในชื่อ 'id'
                    const dbId = row.id; 
                    
                    // 📌 ปรับปุ่มให้สั้นลงสำหรับมือถือ
                    const detailBtn = `<button class="btn btn-info btn-sm view-details-btn me-1" title="รายละเอียด"><i class='bx bx-search'></i></button>`;
                    const editBtn = `<a href="edit_data.php?plot_id=${plotId}" class="btn btn-warning btn-sm me-1" title="แก้ไข"><i class='bx bx-edit'></i></a>`;
                    const deleteBtn = `<button class="btn btn-danger btn-sm delete-btn" data-id="${dbId}" data-plot-id="${plotId}" title="ลบ"><i class='bx bx-trash'></i></button>`;

                    return detailBtn + editBtn + deleteBtn;
                }
            },
        ],
        language: { url: "th.json" }
    });

    // 2. Event Listener: DataTables filter
    $('#imageStatusFilter').on('change', function() {
        table.ajax.reload();
    });
    
    // 3. Event Listener: DataTables details button
    $('#dataTable').on('click', '.view-details-btn', function() {
        // ใช้ .closest('tr') เพื่อหาแถวที่ถูกต้อง
        const rowData = table.row($(this).closest('tr')).data();
        if (rowData) {
              loadPlotDetails(rowData);
        } else {
            console.error("ไม่พบข้อมูลแถว!");
        }
    });

    // 4. Event Listener: ปุ่มลบข้อมูล (Delete button)
    $('#dataTable').on('click', '.delete-btn', function() {
        // ดึง Primary Key 'id' จาก Data Attribute
        const dbId = $(this).data('id');
        const plotId = $(this).data('plot-id'); // ใช้แสดงในข้อความยืนยัน

        if (!dbId) {
            alert("ไม่พบรหัสบันทึก (Primary Key ID) สำหรับการลบ");
            return;
        }

        if (confirm(`คุณต้องการลบข้อมูลแปลง ${plotId} ใช่หรือไม่? การกระทำนี้จะลบข้อมูลทั้งหมดรวมถึงโฟลเดอร์รูปภาพที่เกี่ยวข้อง และไม่สามารถย้อนกลับได้`)) {
            
            $.ajax({
                url: 'delete_data_ajax.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    id: dbId // ส่ง Primary Key ไปยัง delete_data_ajax.php
                },
                success: function(response) {
                    if (response.success) {
                        alert('ลบข้อมูลสำเร็จ: ' + response.message);
                        // Reload DataTables โดยคงหน้าเดิมไว้
                        table.ajax.reload(null, false); 
                    } else {
                        alert('เกิดข้อผิดพลาดในการลบ: ' + (response.message || 'ไม่ทราบสาเหตุ'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error: ", status, error, xhr.responseText);
                    alert('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์ กรุณาลองใหม่.');
                }
            });
        }
    });
    
    // 5. Event Listener: Image Modals (คลิกภาพย่อเพื่อขยาย)
    $(document).on('click', '.plot-image-thumbnail', function() {
        // ไม่ต้องซ่อน Modal รายละเอียด: ให้ Modal รูปภาพขยายแสดงซ้อนทับไปเลย
        $('#modalImage').attr('src', $(this).attr('src'));
        const imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
        imageModal.show();
    });

    // 🚨 6. ลบ: loadInfographicData() ออก

    // 🚨 7. ลบ Event Listener ของตัวกรอง Infographic ออก
    // $('#info_filter_year, #info_filter_suga_type').on('change', function() {
    //     loadInfographicData();
    // });
});
</script>

</body>
</html>