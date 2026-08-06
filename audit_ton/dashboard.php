<?php
// dashboard.php (ปรับปรุง: รองรับมือถือ, ระบบขยายรูปภาพ)
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
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"> 
    
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
    :root {
        --glass-bg: rgba(255, 255, 255, 0.7);
        --glass-border: rgba(255, 255, 255, 0.4);
        --primary-blue: #0d6efd;
        --accent-blue: #00d2ff;
    }

    body { 
        font-family: 'Kanit', sans-serif; 
        min-height: 100vh;
        background-image: url('icon/bg.jpg'); 
        background-size: cover; 
        background-position: center center; 
        background-attachment: fixed; 
        background-repeat: no-repeat; 
    }

    body::before {
        content: ''; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(244, 247, 246, 0.85); backdrop-filter: blur(8px); z-index: -1;
    }

    .container-main { 
        padding: 2rem; 
        max-width: 1400px; 
        margin: 0 auto; 
        flex: 1 0 auto; 
        width: 100%; 
        max-width: 100vw; 
        overflow-x: hidden; 
    }

    .glass-card {
        background: #ffffff;
        border: 1px solid #eaeaea; border-radius: 16px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03); padding: 1.5rem; margin-bottom: 2rem;
    }

    .stat-card {
        padding: 1.25rem; border-radius: 12px; display: flex; align-items: center; gap: 1.25rem;
        color: #2b2b2b; height: 100%; box-shadow: 0 4px 12px rgba(0,0,0,0.03); background: #ffffff;
        border: 1px solid #eaeaea; border-left-width: 4px;
    }
    .stat-card.blue { border-left-color: #0d6efd; }
    .stat-card.green { border-left-color: #198754; }
    .stat-card.orange { border-left-color: #fd7e14; }
    .stat-card.purple { border-left-color: #6f42c1; }
    
    .stat-card.blue .stat-icon { color: #0d6efd; }
    .stat-card.green .stat-icon { color: #198754; }
    .stat-card.orange .stat-icon { color: #fd7e14; }
    .stat-card.purple .stat-icon { color: #6f42c1; }

    .stat-icon { font-size: 2.5rem; }
    .stat-info h3 { margin: 0; font-weight: 700; font-family: 'Outfit', sans-serif; color: #212529; }

    .btn-action { width: 38px; height: 38px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; transition: 0.3s; }
    .btn-action:hover { transform: scale(1.1); }

    .modal-content { background: #ffffff; border-radius: 16px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
    .custom-modal-header { background: #f8f9fa; color: #2b2b2b; border-bottom: 1px solid #eaeaea; border-radius: 16px 16px 0 0; }
    .custom-modal-header .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }

    .plot-image-thumbnail {
        width: 100%; height: 140px; object-fit: cover; border-radius: 12px; cursor: pointer; transition: 0.2s;
    }
    .plot-image-thumbnail:hover { transform: scale(1.05); }

    /* Mobile Responsive Adjustments */
    @media (max-width: 768px) {
        .container-main { padding: 0.8rem; width: 100%; max-width: 100%; overflow-x: hidden; box-sizing: border-box; }
        .glass-card { padding: 1.2rem; margin-bottom: 1rem; border-radius: 12px; }
        
        /* Make headers stack and center on mobile */
        .glass-card .d-flex.justify-content-between { flex-direction: column; text-align: center; gap: 1rem !important; align-items: stretch !important; }
        .glass-card .btn-primary { width: 100%; padding: 0.8rem; justify-content: center; }
        #imageStatusFilter { width: 100% !important; }
        
        h2.fw-bold { font-size: 1.3rem; }
        h4.fw-bold { font-size: 1.2rem; }
        
        /* Stat Cards: Stack icon and text, change border from left to top */
        .stat-card { 
            padding: 1rem 0.5rem; 
            flex-direction: column; 
            text-align: center; 
            justify-content: center; 
            gap: 0.5rem; 
            border-left-width: 0; 
            border-top-width: 4px; 
            border-top-style: solid;
        }
        .stat-card.blue { border-top-color: #0d6efd; }
        .stat-card.green { border-top-color: #198754; }
        .stat-card.orange { border-top-color: #fd7e14; }
        .stat-card.purple { border-top-color: #6f42c1; }
        
        .stat-icon { font-size: 1.8rem; }
        .stat-info h6 { font-size: 0.75rem; margin-bottom: 0.2rem; }
        .stat-info h3 { font-size: 1.2rem; }
        
        .btn-action { width: 32px; height: 32px; }
    }
    </style>
</head>
<body>
<?php require("nav.php"); ?>

<div class="container-main">
    <div class="glass-card">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h2 class="fw-bold text-dark mb-1"><i class='bx bxs-pie-chart-alt-2 text-primary'></i> ระบบวิเคราะห์ข้อมูลแปลงอ้อย</h2>
                <p class="text-muted mb-0"><i class='bx bx-calendar'></i> ปีการผลิต: <strong><?php echo htmlspecialchars($selected_year); ?></strong></p>
            </div>
            <a href="insertForm.php?year=<?php echo urlencode($selected_year); ?>" class="btn btn-primary rounded-pill px-4">
                <i class='bx bx-plus-circle me-1'></i> เพิ่มแปลงใหม่
            </a>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3"><div class="stat-card blue"><div class="stat-icon"><i class='bx bx-map-alt'></i></div><div class="stat-info"><h6>แปลงทั้งหมด</h6><h3 id="statTotalPlots">-</h3></div></div></div>
            <div class="col-6 col-md-3"><div class="stat-card green"><div class="stat-icon"><i class='bx bx-area'></i></div><div class="stat-info"><h6>พื้นที่รวม (ไร่)</h6><h3 id="statTotalArea">-</h3></div></div></div>
            <div class="col-6 col-md-3"><div class="stat-card orange"><div class="stat-icon"><i class='bx bx-trending-up'></i></div><div class="stat-info"><h6>เฉลี่ย ตัน/ไร่</h6><h3 id="statAvgTon">-</h3></div></div></div>
            <div class="col-6 col-md-3"><div class="stat-card purple"><div class="stat-icon"><i class='bx bx-images'></i></div><div class="stat-info"><h6>แปลงที่มีรูป</h6><h3 id="statPhotoCount">-</h3></div></div></div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="p-2 border rounded-3 bg-white shadow-sm" style="height: 200px;">
                    <canvas id="typeChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="glass-card">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <h4 class="fw-bold mb-0">รายการแปลงอ้อย</h4>
            <select id="imageStatusFilter" class="form-select w-auto">
                <option value="">-- กรองรูปภาพ --</option>
                <option value="has_image">มีรูป</option>
                <option value="no_image">ไม่มีรูป</option>
            </select>
        </div>
        <div class="table-responsive">
            <table id="dataTable" class="table table-striped table-bordered w-100 text-nowrap">
                <thead>
                    <tr><th>จัดการ</th><th>รูป</th><th>ปีผลิต</th><th>นักส่งเสริม</th><th>หน่วยงาน</th><th>สัญญา</th><th>ID แปลง</th><th>พื้นที่</th></tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Modal Plot Details -->
<div class="modal fade" id="plotDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header custom-modal-header">
                <h5 class="modal-title">ID แปลง :  <span id="modalPlotId"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <div class="col-md-5">
                        <div class="p-3 border rounded bg-light" id="plotBasics"></div>
                        <div class="mt-3 p-3 border rounded bg-white shadow-sm">
                            <h6 class="fw-bold">หมายเหตุ</h6>
                            <p id="detailNotes" class="mb-0"></p>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <h6 class="fw-bold mb-3"><i class="bx bx-images me-1"></i> รูปภาพประกอบ (คลิกเพื่อขยาย)</h6>
                        <div class="row g-2" id="detailImageGrid"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Image Fullscreen -->
<div class="modal fade" id="imageFullModal" tabindex="-1" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-dark">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-2">
                <img src="" id="enlargedImage" class="img-fluid rounded-3" style="max-height: 80vh;">
            </div>
        </div>
    </div>
</div>

<?php require("nav_footer.php"); ?>

<script>
function loadDashboardSummary() {
    $.ajax({
        url: 'fetch_dashboard_summary.php', data: { year: '<?php echo $selected_year; ?>' }, dataType: 'json',
        success: function(res) {
            if (res.success) {
                $('#statTotalPlots').text(res.summary.total_plots);
                $('#statTotalArea').text(res.summary.total_area);
                $('#statAvgTon').text(res.summary.avg_ton_rai);
                $('#statPhotoCount').text(res.summary.plots_with_images);
                renderTypeChart(res.chart_data);
            }
        }
    });
}

function renderTypeChart(data) {
    const ctx = document.getElementById('typeChart').getContext('2d');
    if (window.myChart) window.myChart.destroy();
    window.myChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(i => i.suga_type),
            datasets: [
                { 
                    label: 'แปลงทั้งหมด', 
                    data: data.map(i => i.total_count), 
                    backgroundColor: 'rgba(13, 110, 253, 0.15)', 
                    borderRadius: 5,
                    barPercentage: 0.8,
                    categoryPercentage: 0.9
                },
                { 
                    label: 'เก็บรูปแล้ว', 
                    data: data.map(i => i.count_with_images), 
                    backgroundColor: 'rgba(40, 167, 69, 0.9)', 
                    borderRadius: 5,
                    barPercentage: 0.8,
                    categoryPercentage: 0.9
                }
            ]
        },
        options: { 
            indexAxis: 'y', 
            maintainAspectRatio: false, 
            plugins: { 
                legend: { position: 'top', labels: { boxWidth: 12, font: { size: 10 } } },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.raw + ' แปลง';
                        }
                    }
                }
            },
            scales: {
                x: { stacked: false, grid: { display: false } },
                y: { stacked: false, grid: { display: false } }
            }
        }
    });
}

function loadPlotDetails(row) {
    $('#modalPlotId').text(row.plot_id);
    $('#plotBasics').html(`
        <div class="d-flex justify-content-between mb-2 border-bottom pb-1"><span>นักส่งเสริม:</span> <strong>${row.emp_number}</strong></div>
        <div class="d-flex justify-content-between mb-2 border-bottom pb-1"><span>หน่วยงาน:</span> <strong>${row.agency}</strong></div>
        <div class="d-flex justify-content-between mb-2 border-bottom pb-1"><span>พื้นที่:</span> <strong>${row.rai_area} ไร่</strong></div>
        <div class="d-flex justify-content-between"><span>ชนิดอ้อย:</span> <strong>${row.suga_type}</strong></div>
    `);
    $('#detailNotes').text(row.notes || '-');
    
    const grid = $('#detailImageGrid').empty();
    const fields = ['estimate_ton_1', 'estimate_ton_2', 'evaluate_ton_1', 'evaluate_ton_2'];
    let hasImg = false;
    fields.forEach(f => {
        if (row[f]) {
            hasImg = true;
            grid.append(`<div class="col-4 col-md-3"><img src="${row[f]}" class="plot-image-thumbnail view-full-img"></div>`);
        }
    });
    if(!hasImg) grid.html('<p class="text-muted text-center py-4">ไม่พบรูปภาพ</p>');
    new bootstrap.Modal(document.getElementById('plotDetailsModal')).show();
}

$(document).ready(function() {
    loadDashboardSummary();

    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');
    const success = urlParams.get('success');
    const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });

    if (msg === 'login') Toast.fire({ icon: 'success', title: 'เข้าสู่ระบบสำเร็จ' });
    else if (success === 'insert') Toast.fire({ icon: 'success', title: 'เพิ่มข้อมูลสำเร็จ' });
    else if (success === 'update') Toast.fire({ icon: 'success', title: 'ปรับปรุงข้อมูลสำเร็จ' });

    const table = $('#dataTable').DataTable({
        serverSide: true, processing: true,
        ajax: {
            url: 'fetch_data_admin.php', type: 'POST',
            data: d => { d.year = '<?php echo $selected_year; ?>'; d.image_status_filter = $('#imageStatusFilter').val(); }
        },
        columns: [
            { data: null, render: r => `
                <div class="d-flex gap-1">
                    <button class="btn btn-info btn-sm btn-action view-btn"><i class="bx bx-search text-white"></i></button>
                    <a href="edit_data.php?id=${r.id}" class="btn btn-warning btn-sm btn-action"><i class="bx bx-edit text-white"></i></a>
                    <button class="btn btn-danger btn-sm btn-action del-btn" data-id="${r.id}"><i class="bx bx-trash"></i></button>
                </div>
            `},
            { data: null, render: r => r.estimate_ton_1 ? '<i class="bx bxs-check-circle text-success fs-4"></i>' : '<i class="bx bxs-x-circle text-danger fs-4"></i>' },
            { data: 'production_year' }, { data: 'emp_number' }, { data: 'agency' }, { data: 'contract_number' }, { data: 'plot_id' }, { data: 'rai_area' }
        ],
        language: { url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/th.json" }
    });

    $('#imageStatusFilter').change(() => table.ajax.reload());
    $('#dataTable').on('click', '.view-btn', function() { loadPlotDetails(table.row($(this).closest('tr')).data()); });
    
    // Zoom image logic
    $(document).on('click', '.view-full-img', function() {
        $('#enlargedImage').attr('src', $(this).attr('src'));
        new bootstrap.Modal(document.getElementById('imageFullModal')).show();
    });

    $('#dataTable').on('click', '.del-btn', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'ยืนยันการลบ?', icon: 'warning', showCancelButton: true, confirmButtonText: 'ลบ', cancelButtonText: 'ยกเลิก'
        }).then(res => {
            if (res.isConfirmed) {
                $.ajax({
                    url: 'delete_data_ajax.php', type: 'POST', data: { id: id },
                    success: () => { table.ajax.reload(); Toast.fire({ icon: 'success', title: 'ลบสำเร็จ' }); }
                });
            }
        });
    });
});
</script>
</body>
</html>