<?php
session_start();
require("db_connect.php");

$selected_year = $_GET['year'] ?? '';

if (!$selected_year) {
    header("Location: index.php");
    exit;
}

$sql = "SELECT * FROM soil_data WHERE production_year = ? ORDER BY id DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $selected_year);
$stmt->execute();
$result = $stmt->get_result();
?>

<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ระบบตรวจสอบแปลงอ้อย | แดชบอร์ด</title>
    <link rel="icon" href="icon/unnamed.png" type="image/png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="index.css">

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%) !important;
        }

        .container-premium {
            margin-top: 2rem;
            margin-bottom: 3rem;
        }

        .header-section {
            margin-bottom: 2rem;
            text-align: center;
        }

        .header-title {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .header-subtitle {
            background: #ffffff;
            color: var(--primary);
            padding: 8px 25px;
            border-radius: 15px;
            display: inline-block;
            font-weight: 700;
            font-size: 1.2rem;
            border: 2px solid var(--primary);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .eval-section {
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 2rem;
            color: var(--text-main);
            font-size: 1.1rem;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(0,0,0,0.02);
        }

        .eval-badge {
            background: #f1f5f9;
            color: var(--primary);
            padding: 5px 12px;
            border-radius: 8px;
            margin-right: 15px;
            border: 1px solid #cbd5e1;
            display: inline-block;
        }

        .img-thumbnail-premium {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid white;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .img-thumbnail-premium:hover {
            transform: scale(1.2) rotate(3deg);
            z-index: 10;
        }

        /* Modal Transitions */
        .modal-content {
            border-radius: 20px;
            border: none;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
        }

        .modal-header {
            background: var(--primary);
            color: white;
            border: none;
        }

        .modal-footer {
            border: none;
        }

        #modalImage {
            border-radius: 12px;
            max-height: 80vh;
            object-fit: contain;
        }

        .action-btns {
            display: flex;
            gap: 1.5rem;
            margin-top: 2rem;
            justify-content: center;
        }

        /* Detailed View Modal Styling */
        .detail-row {
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
        }
        .detail-label {
            font-weight: 700;
            color: #64748b;
            width: 160px;
            flex-shrink: 0;
            font-size: 0.95rem;
        }
        .detail-value {
            color: #1e293b;
            font-weight: 600;
            font-size: 1.05rem;
        }
        .detail-image-card {
            background: #f8fafc;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 20px;
            position: relative;
            border: 1px solid #e2e8f0;
            transition: all 0.3s;
        }
        .detail-image-card:hover {
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            transform: translateY(-5px);
        }
        .detail-image-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 10px;
            display: block;
        }
        .detail-img-wrapper {
            position: relative;
            width: 100%;
            height: 180px;
            border-radius: 10px;
            overflow: hidden;
        }
        .detail-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            cursor: pointer;
        }
        .delete-img-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(239, 68, 68, 0.9);
            color: white;
            border: none;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            backdrop-filter: blur(4px);
            z-index: 5;
        }
        .delete-img-btn:hover {
            background: #dc2626;
            transform: scale(1.1);
        }
        .empty-img-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f5f9;
            color: #94a3b8;
            font-size: 0.85rem;
            border: 2px dashed #cbd5e1;
            border-radius: 10px;
        }
        
        
        @media (max-width: 768px) {
            .header-title { font-size: 1.5rem; }
            .header-subtitle { font-size: 0.9rem; padding: 4px 15px; }
            .eval-section { padding: 10px; }
            .eval-badge { display: block; margin: 5px 0; font-size: 0.75rem; }
            .action-btns { flex-direction: column; gap: 10px; }
            .action-btns .btn { width: 100%; border-radius: 12px; }
            .detail-label { width: 120px; font-size: 0.85rem; }
            .detail-value { font-size: 0.95rem; }
        }
    </style>
</head>
<body>
 <?php require("nav.php");?>

<div class="container container-premium">
    <div class="header-section fade-in">
        <h1 class="header-title">ฐานข้อมูลแปลงอ้อย</h1>
        <div class="header-subtitle">
            <i class='bx bx-calendar-event'></i> ปีการผลิต: <?php echo htmlspecialchars($selected_year); ?>
        </div>
    </div>

    <div class="eval-section text-center fade-in">
        <span class="eval-badge">คุณภาพ: ดีมาก=1, ดี=2, พอใช้=3</span>
        <span class="eval-badge">การปลูก: มาตรฐาน=1, ต่ำกว่ามาตรฐาน=2</span>
        <span class="eval-badge">การให้น้ำ: มี=1, ไม่มี=2</span>
    </div>

    <div class="glass-card-white fade-in" style="overflow: hidden;">
        <div class="table-responsive">
            <table id="dataTable" class="table table-hover nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>จัดการ</th>
                        <th>ปีผลิต</th>
                        <th>หน่วยงาน</th>
                        <th>เลขสัญญา</th>
                        <th>โควต้า</th>
                        <th>ID แปลง</th>
                        <th>ไร่</th>
                        <th>ชนิดดิน</th>
                        <th>รูปดิน</th>
                        <th>เตรียมดิน</th>
                        <th>รูปเตรียมดิน</th>
                        <th>พันธุ์อ้อย</th>
                        <th>รูปพันธุ์อ้อย</th>
                        <th>การปลูก</th>
                        <th>รูปปลูก</th>
                        <th>การให้น้ำ</th>
                        <th>รูปให้น้ำ</th>
                        <th>% งอก</th>
                        <th>รูปงอก</th>
                        <th>หมายเหตุ</th>
                    </tr>
                </thead>
            </table>
        </div>
        
        <div class="action-btns pt-4 border-top">
            <a href="insertForm.php?year=<?php echo htmlspecialchars($selected_year); ?>" class="btn btn-premium">
                <i class='bx bx-plus-circle'></i> เพิ่มข้อมูลใหม่
            </a>
        </div>
    </div>
</div>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg"> 
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ดูข้อมูลภาพถ่าย</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <img src="" id="modalImage" class="img-fluid" alt="ขยายภาพ">
            </div>
        </div>
    </div>
</div>

<!-- Full Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
            <div class="modal-header bg-primary text-white p-4" style="border-bottom: none;">
                <div class="d-flex align-items-center">
                    <div class="bg-white p-2 rounded-3 me-3" style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">
                        <i class='bx bx-search-alt text-primary fs-3'></i>
                    </div>
                    <div>
                        <h4 class="modal-title fw-bold mb-0">รายละเอียดข้อมูลแปลงอ้อย</h4>
                        <p class="mb-0 opacity-75 small">ID แปลง: <span id="view-plot-id"></span> | ปีผลิต: <span id="view-production-year"></span></p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="container-fluid py-4">
                    <div class="row g-4">
                        <!-- Info Section -->
                        <div class="col-lg-5">
                            <div class="card border-0 bg-light rounded-4 h-100">
                                <div class="card-body p-4">
                                    <h5 class="fw-bold mb-4 d-flex align-items-center">
                                        <i class='bx bx-info-circle me-2 text-primary'></i> ข้อมูลทั่วไป
                                    </h5>
                                    <div class="detail-row"><span class="detail-label">หน่วยงาน:</span> <span class="detail-value" id="view-agency"></span></div>
                                    <div class="detail-row"><span class="detail-label">เลขสัญญา:</span> <span class="detail-value" id="view-contract-number"></span></div>
                                    <div class="detail-row"><span class="detail-label">เลขโควต้า:</span> <span class="detail-value" id="view-quota"></span></div>
                                    <div class="detail-row"><span class="detail-label">พื้นที่ (ไร่):</span> <span class="detail-value" id="view-rai-area"></span></div>
                                    <div class="detail-row"><span class="detail-label">ชนิดดิน:</span> <span class="detail-value" id="view-soil-type"></span></div>
                                    <div class="detail-row"><span class="detail-label">พันธุ์อ้อย:</span> <span class="detail-value" id="view-cane-variety"></span></div>
                                    <div class="detail-row"><span class="detail-label">การเตรียมดิน:</span> <span class="detail-value" id="view-soil-prep"></span></div>
                                    <div class="detail-row"><span class="detail-label">การปลูก:</span> <span class="detail-value" id="view-planting"></span></div>
                                    <div class="detail-row"><span class="detail-label">การให้น้ำ:</span> <span class="detail-value" id="view-watering"></span></div>
                                    <div class="detail-row"><span class="detail-label">การงอก (%):</span> <span class="detail-value" id="view-germination"></span></div>
                                    <div class="detail-row border-0"><span class="detail-label">หมายเหตุ:</span> <div class="detail-value mt-1" id="view-notes"></div></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Image Gallery Section -->
                        <div class="col-lg-7">
                            <h5 class="fw-bold mb-4 d-flex align-items-center px-2">
                                <i class='bx bx-images me-2 text-primary'></i> แกลเลอรีรูปภาพ
                            </h5>
                            <div class="row g-3" id="view-image-container">
                                <!-- Images will be injected here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light p-4" style="border-top: none; border-radius: 0 0 24px 24px;">
                <button type="button" class="btn btn-outline-secondary px-4 rounded-pill fw-bold" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
                <div class="ms-auto d-flex gap-2">
                    <a href="#" id="view-edit-link" class="btn btn-warning px-4 rounded-pill fw-bold text-white shadow-sm">
                        <i class='bx bx-edit-alt'></i> แก้ไขข้อมูล
                    </a>
                    <button type="button" id="view-delete-record-btn" class="btn btn-danger px-4 rounded-pill fw-bold shadow-sm">
                        <i class='bx bx-trash'></i> ลบข้อมูลแปลงนี้
                    </button>
                </div>
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
            url: 'fetch_data.php',
            type: 'POST',
            data: d => { d.year = '<?php echo htmlspecialchars($selected_year); ?>'; }
        },
        scrollX: true,
        pageLength: 10,
        columns: [
            {
                data: null,
                render: (data, type, row) => `
                    <div class="btn-group">
                        <button class="btn btn-outline-primary btn-sm view-detail-btn" data-id="${row.id}" title="ดูรายละเอียด" style="padding: 10px 12px; font-size: 1.2rem; border-radius: 8px;">
                            <i class='bx bx-search-alt'></i>
                        </button>
                        <a href="edit_data.php?id=${row.id}" class="btn btn-outline-warning btn-sm" title="แก้ไข" style="padding: 10px 12px; font-size: 1.2rem; border-radius: 8px; margin-left: 5px;">
                            <i class='bx bx-edit-alt'></i>
                        </a>
                    </div>`
            },
            { data: 'production_year' },
            { data: 'agency' },
            { data: 'contract_number' },
            { data: 'quota' },
            { data: 'plot_id' },
            { data: 'rai_area' },
            { data: 'soil_type' },
            {
                data: 'soil_image',
                render: data => data ? `<img src="${data}" class="img-thumbnail-premium" />` : '-'
            },
            { data: 'soil_preparation_details' },
            {
                data: 'soil_preparation_image',
                render: data => data ? `<img src="${data}" class="img-thumbnail-premium" />` : '-'
            },
            { data: 'cane_variety' },
            {
                data: 'cane_variety_image',
                render: data => data ? `<img src="${data}" class="img-thumbnail-premium" />` : '-'
            },
            { data: 'planting_details' },
            {
                data: 'planting_image',
                render: data => data ? `<img src="${data}" class="img-thumbnail-premium" />` : '-'
            },
            { data: 'watering_details' },
            {
                data: 'watering_image',
                render: data => data ? `<img src="${data}" class="img-thumbnail-premium" />` : '-'
            },
            { data: 'germination_percentage' },
            {
                data: 'germination_image',
                render: data => data ? `<img src="${data}" class="img-thumbnail-premium" />` : '-'
            },
            {
                data: 'notes',
                render: (data) => {
                    if (data && data.length > 20) {
                        return `${data.substring(0, 20)}...`;
                    }
                    return data || '-';
                }
            }
        ],
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/th.json"
        }
    });

    // Handle Detail Modal Opening
    $('#dataTable tbody').on('click', '.view-detail-btn', function() {
        const rowData = table.row($(this).closest('tr')).data();
        populateDetailModal(rowData);
        new bootstrap.Modal(document.getElementById('detailModal')).show();
    });

    function populateDetailModal(data) {
        // Set basic info
        $('#view-plot-id').text(data.plot_id);
        $('#view-production-year').text(data.production_year);
        $('#view-agency').text(data.agency);
        $('#view-contract-number').text(data.contract_number);
        $('#view-quota').text(data.quota);
        $('#view-rai-area').text(data.rai_area);
        $('#view-soil-type').text(data.soil_type);
        $('#view-cane-variety').text(data.cane_variety);
        $('#view-soil-prep').text(data.soil_preparation_details);
        $('#view-planting').text(data.planting_details);
        $('#view-watering').text(data.watering_details);
        $('#view-germination').text(data.germination_percentage + ' %');
        $('#view-notes').html((data.notes || '-').replace(/\n/g, '<br>'));
        
        // Set Action Buttons
        $('#view-edit-link').attr('href', 'edit_data.php?id=' + data.id);
        $('#view-delete-record-btn').data('id', data.id);

        // Load Images
        const imageConfig = [
            { key: 'soil_image', label: 'ภาพตัวอย่างดิน' },
            { key: 'soil_preparation_image', label: 'ภาพการเตรียมดิน' },
            { key: 'cane_variety_image', label: 'ภาพพันธุ์อ้อย' },
            { key: 'planting_image', label: 'ภาพการปลูก' },
            { key: 'watering_image', label: 'ภาพการให้น้ำ' },
            { key: 'germination_image', label: 'ภาพการงอก' }
        ];

        let imgHtml = '';
        imageConfig.forEach(cfg => {
            const imgSrc = data[cfg.key];
            imgHtml += `
                <div class="col-6 col-md-4">
                    <div class="detail-image-card">
                        <span class="detail-image-title">${cfg.label}</span>
                        <div class="detail-img-wrapper" id="container-${cfg.key}-${data.id}">
                            ${imgSrc ? `
                                <img src="${imgSrc}" class="detail-img img-thumbnail-premium" alt="${cfg.label}">
                                <button class="delete-img-btn" onclick="deleteIndividualImage(${data.id}, '${cfg.key}', '${data.production_year}', '${data.agency}', '${data.contract_number}', '${data.plot_id}')" title="ลบเฉพาะรูปนี้">
                                    <i class='bx bx-trash'></i>
                                </button>
                            ` : `
                                <div class="empty-img-placeholder">ไม่มีรูปภาพ</div>
                            `}
                        </div>
                    </div>
                </div>
            `;
        });
        $('#view-image-container').html(imgHtml);
    }

    // Global Delete Image Function (called from modal)
    window.deleteIndividualImage = function(id, type, py, ag, cn, pi) {
        Swal.fire({
            title: 'ลบรูปภาพนี้?',
            text: "ต้องการลบเฉพาะรูปภาพนี้ใช่หรือไม่?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'ใช่, ต้องการลบ!',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'delete_image_ajax.php',
                    type: 'POST',
                    dataType: 'json',
                    data: { id: id, image_type: type, production_year: py, agency: ag, contract_number: cn, plot_id: pi },
                    success: function(resp) {
                        if (resp.success) {
                            Swal.fire('ลบรูปภาพแล้ว!', resp.message, 'success');
                            $(`#container-${type}-${id}`).html('<div class="empty-img-placeholder">ไม่มีรูปภาพ</div>');
                            table.ajax.reload(null, false); // Reload table data without resetting pagination
                        } else {
                            Swal.fire('ผิดพลาด!', resp.message, 'error');
                        }
                    },
                    error: () => Swal.fire('ผิดพลาด!', 'ส่งข้อมูลล้มเหลว', 'error')
                });
            }
        });
    };

    // Handle AJAX Record Delete (from Modal)
    $('#view-delete-record-btn').on('click', function() {
        const id = $(this).data('id');

        Swal.fire({
            title: 'ยืนยันการลบข้อมูลทั้งหมดของแปลงนี้?',
            text: "ข้อมูลและรูปภาพทั้งหมดจะถูกลบถาวร!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'ใช่, ต้องการลบข้อมูลทั้งหมด!',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'delete_data_ajax.php',
                    type: 'POST',
                    dataType: 'json',
                    data: { id: id },
                    success: function(resp) {
                        if (resp.success) {
                            Swal.fire('ลบข้อมูลสำเร็จ!', resp.message, 'success');
                            bootstrap.Modal.getInstance(document.getElementById('detailModal')).hide();
                            table.ajax.reload(null, false);
                        } else {
                            Swal.fire('ผิดพลาด!', resp.message, 'error');
                        }
                    },
                    error: () => Swal.fire('ผิดพลาด!', 'ส่งข้อมูลล้มเหลว', 'error')
                });
            }
        });
    });

    // Image Modal Handling (Zooming with Modal Switching)
    $(document).on('click', '.img-thumbnail-premium', function() {
        const imgSrc = $(this).attr('src');
        const detailModalEl = document.getElementById('detailModal');
        const imageModalEl = document.getElementById('imageModal');
        
        // หา Instance ของ Detail Modal (ถ้าเปิดอยู่)
        const detailModal = bootstrap.Modal.getInstance(detailModalEl);
        
        if (detailModal && detailModalEl.classList.contains('show')) {
            // กรณีเปิดจากในหน้าต่างรายละเอียด
            detailModal.hide(); // ซ่อนตัวหน้าต่างรายละเอียดก่อน
            
            $('#modalImage').attr('src', imgSrc);
            const imageModal = new bootstrap.Modal(imageModalEl);
            imageModal.show();
            
            // เมื่อปิดหน้าต่างรูป ให้เปิดหน้าต่างรายละเอียดคืนมา
            $(imageModalEl).one('hidden.bs.modal', function() {
                detailModal.show();
            });
        } else {
            // กรณีเปิดจากตารางโดยตรง (ถ้ามี)
            $('#modalImage').attr('src', imgSrc);
            new bootstrap.Modal(imageModalEl).show();
        }
    });

});
</script>
</body>
</html>