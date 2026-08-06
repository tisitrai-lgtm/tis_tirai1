<?php
// dashboard.php (ปรับปรุง: ย้าย Infographic ลงด้านล่าง, DataTables สรุปหน่วยงาน, เรียงตาม "มีรูปภาพ" มากสุด)
session_start();
require("db_connect.php");
// require("nav.php"); // จะถูกเรียกในส่วน HTML

$selected_year = $_GET['year'] ?? '';

if (!$selected_year) {
    echo "กรุณาเลือกปีการผลิตก่อน <a href='index.php'>กลับไปหน้าเลือกปี</a>";
    exit;
}

// --- ส่วนสำหรับ Infographic: ดึงตัวเลือก Filter ---
$years = [];
$agencies = [];
$suga_types = [];

// ดึงปี
$sql_years = "SELECT DISTINCT production_year FROM cane_plot_data ORDER BY production_year DESC";
$result_years = $conn->query($sql_years);
while ($row = $result_years->fetch_assoc()) {
    $years[] = htmlspecialchars($row['production_year']);
}

// ดึงหน่วยส่งเสริม
$sql_agencies = "SELECT DISTINCT agency FROM cane_plot_data ORDER BY agency ASC";
$result_agencies = $conn->query($sql_agencies);
while ($row = $result_agencies->fetch_assoc()) {
    $agencies[] = htmlspecialchars($row['agency']);
}

// ดึงชนิดอ้อย
$sql_suga_types = "SELECT DISTINCT suga_type FROM cane_plot_data WHERE suga_type IS NOT NULL AND suga_type != '' ORDER BY suga_type ASC";
$result_suga_types = $conn->query($sql_suga_types);
while ($row = $result_suga_types->fetch_assoc()) {
    $suga_types[] = htmlspecialchars($row['suga_type']);
}

$default_year = $selected_year; 
$default_agency = ''; 
$default_suga_type = ''; 

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
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

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
        padding: 30px; 
        box-shadow: 0 4px 12px rgba(63,63,63,0.1); 
        margin-top: 30px;
        margin-bottom: 30px; 
    }
    /* 🚨 ปรับปรุง: Infographic Styles */
    .infographic-card {
        background-color: #ffffff;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        padding: 20px;
        margin-bottom: 20px;
        min-height: 400px; 
        display: flex;
        flex-direction: column;
    }
    /* 🚨 ปรับปรุง: กำหนดความสูงสูงสุดให้ Chart Canvas โดยเฉพาะ */
    #plotsPieChart {
        max-height: 300px; 
    }
    .filter-section {
        background-color: #e9ecef;
        padding: 15px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        margin-bottom: 20px;
    }
    .summary-box {
        background-color: #e6f3f0;
        border-left: 5px solid #00796b;
        padding: 15px;
        border-radius: 8px;
        margin-top: auto; 
        font-weight: 500;
    }
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.8);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 10;
        border-radius: 15px;
    }
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
        max-width: 90%;
    }
    .detail-card {
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        height: 100%; /* Ensure card fills height */
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
    }
    .data-value {
        color: #000;
    }
    .image-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
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
    /* ต้องเอา overflow: hidden ออกเพื่อให้ Modal ใหญ่ซ้อน Modal เล็กได้ */
    /* .modal-open {
        overflow: hidden !important; 
    } */
    #plotDetailsModal .modal-body {
        max-height: calc(100vh - 200px); 
        overflow-y: auto; 
    }
    @media (max-width: 991.98px) {
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
        <div class="d-flex align-items-center gap-2 mb-2 mb-md-0">
            <label for="imageStatusFilter" class="me-1 text-muted fw-bold">ตัวกรองรูปภาพ (ตาราง):</label>
            <select id="imageStatusFilter" class="form-select w-auto" style="min-width: 150px;">
                <option value="">-- สถานะทั้งหมด --</option>
                <option value="has_image">มีรูปภาพ</option>
                <option value="no_image">ไม่มีรูปภาพ</option>
            </select>
        </div>
        <div class="d-flex gap-2">
            <a href="insertForm.php?year=<?php echo urlencode($selected_year); ?>" class="btn btn-save-modern">
                <i class='bx bx-plus-circle me-1'></i> เพิ่มข้อมูลแปลงใหม่
            </a>
            <a href="export_excel.php?year=<?php echo urlencode($selected_year); ?>" class="btn btn-export-modern" target="_blank">
                <i class='bx bxs-file-export me-1'></i> ส่งออกข้อมูลปีนี้เป็น Excel
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
                    <th>รายละเอียด</th> 
                    <th>แก้ไข</th>
                </tr>
            </thead>
        </table>
    </div>

    <hr class="my-5">
    <h4 class="text-start text-success mb-3"><i class="fas fa-chart-pie"></i> ข้อมูลสรุปสถานะรูปภาพแปลงอ้อย</h4>
    
    <div class="filter-section row g-3 mb-4">
        <div class="col-md-4 col-sm-6">
            <label for="info_filter_year" class="form-label">ปีการผลิต</label>
            <select id="info_filter_year" class="form-select">
                <?php foreach ($years as $year): ?>
                    <option value="<?php echo $year; ?>" <?php echo ($year == $default_year) ? 'selected' : ''; ?>>
                        <?php echo $year; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 col-sm-6">
            <label for="info_filter_suga_type" class="form-label">ชนิดอ้อย</label>
            <select id="info_filter_suga_type" class="form-select">
                <option value="">-- ทั้งหมด --</option>
                <?php foreach ($suga_types as $type): ?>
                    <option value="<?php echo $type; ?>" <?php echo ($type == $default_suga_type) ? 'selected' : ''; ?>>
                        <?php echo $type; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 col-sm-12 d-flex align-items-end">
            <p class="mb-0 text-muted small">
                ข้อมูลสรุปนี้ถูกกรองตามปีการผลิตและชนิดอ้อยที่เลือก
            </p>
        </div>
    </div>
    
    <div class="row g-4 d-flex align-items-stretch" id="summary-result-container">
        <div class="text-center p-5 col-12" id="initial-loading">
            <div class="spinner-border text-success" role="status"><span class="visually-hidden">Loading...</span></div>
            <p class="mt-2 text-muted">กำลังโหลดข้อมูลสรุป...</p>
        </div>
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
let pieChart; 

// --- Infographic Functions ---
function loadInfographicData() {
    var year = $('#info_filter_year').val();
    var sugaType = $('#info_filter_suga_type').val();

    var container = $('#summary-result-container');
    
    // แสดง Spinner
    container.html(`
        <div class="col-12 position-relative" style="min-height: 200px;">
            <div class="loading-overlay">
                <div class="spinner-border text-success" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    `);
    
    $.ajax({
        url: 'admin_fetch_summary.php', 
        type: 'GET',
        dataType: 'json',
        data: {
            year: year,
            suga_type: sugaType,
            mode: 'agency_summary' 
        },
        success: function(response) {
            if (response.success) {
                displaySummary(response.data);
            } else {
                container.html('<div class="col-12"><div class="alert alert-danger text-center">เกิดข้อผิดพลาดในการดึงข้อมูลสรุป: ' + response.message + '</div></div>');
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX Error:", status, error, xhr.responseText);
            container.html('<div class="col-12"><div class="alert alert-danger text-center">ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์เพื่อดึงข้อมูลสถิติได้</div></div>');
        }
    });
}

// 🚨 ฟังก์ชันแสดงผลสรุป (Pie Chart และตาราง DataTables)
function displaySummary(data) {
    var container = $('#summary-result-container');
    container.empty();
    
    // ข้อมูลทั้งหมด (สำหรับ DataTables)
    const allAgencies = data.agency_summary; 
    
    // 1. ส่วน Pie Chart (HTML)
    var totalWithImage = data.total_summary.with_image;
    var totalNoImage = data.total_summary.total - totalWithImage;
    
    var chartHtml = `
        <div class="col-lg-6 col-md-12">
            <div class="infographic-card h-100"> 
                <h5 class="text-center mb-3 text-success"><i class="fas fa-chart-pie"></i> สัดส่วนภาพรวมแปลงทั้งหมด</h5>
                <div style="flex-grow: 1; position: relative;">
                    <canvas id="plotsPieChart"></canvas>
                </div>
                <div class="summary-box mt-3">
                    <p class="mb-0">รวมแปลงทั้งหมด: **${data.total_summary.total.toLocaleString()}** แปลง</p>
                    <p class="mb-0 text-success">มีรูปภาพ: **${totalWithImage.toLocaleString()}** แปลง</p>
                    <p class="mb-0 text-danger">ไม่มีรูปภาพ: **${totalNoImage.toLocaleString()}** แปลง</p>
                </div>
            </div>
        </div>
    `;
    
    // 2. ส่วนตารางสรุปหน่วยงาน (DataTables)
    
    var tableHtml = `
        <div class="col-lg-6 col-md-12">
            <div class="infographic-card h-100"> 
                <h5 class="text-center mb-3 text-success"><i class="fas fa-table"></i> สรุปสถานะรูปภาพแยกตามหน่วยส่งเสริม</h5>
                <div class="table-responsive" style="flex-grow: 1; overflow-y: auto;">
                    <table id="agencySummaryTable" class="table table-sm table-striped table-hover table-bordered" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th>หน่วย</th>
                                <th class="text-center">รวมแปลง</th>
                                <th class="text-center text-success">มีรูปภาพ</th>
                                <th class="text-center text-danger">ไม่มีรูปภาพ</th>
                                <th class="text-center">% มีรูป</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody> 
                    </table>
                </div>
                <div class="summary-box mt-3">
                    ${allAgencies.length > 0 ? `
                        <p class="mb-0">หน่วยงานที่มีรูปภาพน้อยที่สุด: **${allAgencies[0].agency} (${allAgencies[0].percent.toFixed(1)}%)**</p>
                        <p class="mb-0">หน่วยงานที่มีรูปภาพมากที่สุด: **${allAgencies[allAgencies.length - 1].agency} (${allAgencies[allAgencies.length - 1].percent.toFixed(1)}%)**</p>
                    ` : '<p class="mb-0 text-center">ไม่พบข้อมูลสำหรับสรุป</p>'}
                </div>
            </div>
        </div>
    `;
    
    container.html(chartHtml + tableHtml);
    
    // วาด Pie Chart 
    renderPieChart(totalWithImage, totalNoImage);

    // 2.1 จัดเตรียมข้อมูลสำหรับ DataTables
    const tableData = allAgencies.map(agency => {
        const percent = agency.percent.toFixed(1);
        const percentColor = percent < 50 ? 'text-danger fw-bold' : 'text-success';
        
        return [
            agency.agency,
            agency.total, 
            agency.with_image, 
            agency.missing_image, 
            `<span class="${percentColor}">${percent}%</span>` 
        ];
    });

    // 2.2 สร้าง DataTables
    $('#agencySummaryTable').DataTable({
        data: tableData,
        ordering: true, 
        paging: false, 
        searching: true, 
        info: false, 
        scrollY: "300px", 
        scrollCollapse: true,
        language: { url: "th.json" },
        columns: [
            { title: "หน่วย" },
            { title: "รวมแปลง", className: "text-center" },
            { title: "มีรูปภาพ", className: "text-center" }, 
            { title: "ไม่มีรูปภาพ", className: "text-center" },
            { title: "% มีรูป", className: "text-center" }
        ],
        // 📌 กำหนดการเรียงลำดับเริ่มต้น: คอลัมน์ที่ 2 (มีรูปภาพ), เรียงแบบ Descending (มากไปน้อย)
        order: [[ 2, 'desc' ]] 
    });
}


function renderPieChart(withImage, noImage) {
    const ctx = document.getElementById('plotsPieChart');

    if (pieChart) {
        pieChart.destroy(); 
    }
    
    pieChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['มีรูปภาพ', 'ไม่มีรูปภาพ'],
            datasets: [{
                data: [withImage, noImage],
                backgroundColor: [
                    '#4CAF50', 
                    '#F44336'  
                ],
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: {
                            family: 'Kanit, "Segoe UI"',
                            size: 14
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed !== null) {
                                const total = context.dataset.data.reduce((sum, value) => sum + value, 0);
                                const percentage = (context.parsed / total * 100).toFixed(1);

                                label += context.parsed.toLocaleString() + ' แปลง (' + percentage + '%)';
                            }
                            return label;
                        }
                    }
                },
                title: {
                    display: false
                }
            }
        }
    });
}

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
            // คอลัมน์สถานะรูปภาพ (ใช้การตรวจสอบข้อมูลที่ส่งมาจาก Server)
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
            // คอลัมน์ปุ่ม "รายละเอียด"
            {
                data: null, orderable: false, searchable: false,
                render: (data, type, row) => `<button class="btn btn-info btn-sm view-details-btn"><i class='bx bx-search'></i> รายละเอียด</button>`
            },
            // คอลัมน์ปุ่ม "แก้ไข"
            {
                data: null, orderable: false, searchable: false,
                render: (data, type, row) => `<a href="edit_data.php?plot_id=${row.plot_id}" class="btn btn-warning btn-sm"><i class='bx bx-edit'></i> แก้ไข</a>`
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

    // 4. Event Listener: Image Modals (คลิกภาพย่อเพื่อขยาย)
    $(document).on('click', '.plot-image-thumbnail', function() {
        // ไม่ต้องซ่อน Modal รายละเอียด: ให้ Modal รูปภาพขยายแสดงซ้อนทับไปเลย
        $('#modalImage').attr('src', $(this).attr('src'));
        const imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
        imageModal.show();
    });

    // 5. Event Listener: เมื่อ Modal รูปภาพถูกปิด (ไม่ต้องทำอะไร Modal รายละเอียดจะยังอยู่)
    // ลบส่วนที่เคยเปิด plotDetailsModal กลับมา เพราะมันไม่ได้ถูกซ่อนตั้งแต่แรก
    
    // 6. Load Infographic ครั้งแรก 
    loadInfographicData(); 

    // 7. Event Listener: เมื่อเลือกตัวกรอง Infographic
    $('#info_filter_year, #info_filter_suga_type').on('change', function() {
        loadInfographicData();
    });
});
</script>

</body>
</html>