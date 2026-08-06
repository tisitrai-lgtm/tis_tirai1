<?php
session_start();
require_once 'db_connect.php';
date_default_timezone_set('Asia/Bangkok');
$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date   = $_GET['end_date'] ?? date('Y-m-d');
$statn_filter = $_GET['statn_code'] ?? 'ALL';

// 1. รายชื่อสถานี
$stmt_all_stations = $pdo->query("SELECT statn_code, statn_name FROM stations ORDER BY statn_code ASC");
$stations_list = $stmt_all_stations->fetchAll(PDO::FETCH_ASSOC);

// 2. สรุปยอดรวม
$sql_total = "SELECT COUNT(*) as total_trucks FROM daily_truck_reports WHERE report_date BETWEEN ? AND ?";
$params_total = [$start_date, $end_date];
if ($statn_filter !== 'ALL') {
    $sql_total .= " AND statn_code = ?";
    $params_total[] = $statn_filter;
}
$stmt_total = $pdo->prepare($sql_total);
$stmt_total->execute($params_total);
$summary = $stmt_total->fetch(PDO::FETCH_ASSOC);

// 3. ข้อมูลกราฟรายวัน
$sql_daily = "SELECT report_date, COUNT(*) as daily_total FROM daily_truck_reports WHERE report_date BETWEEN ? AND ?";
if ($statn_filter !== 'ALL') { $sql_daily .= " AND statn_code = '$statn_filter'"; }
$sql_daily .= " GROUP BY report_date ORDER BY report_date ASC";
$stmt_daily = $pdo->prepare($sql_daily);
$stmt_daily->execute([$start_date, $end_date]);
$daily_stats = $stmt_daily->fetchAll(PDO::FETCH_ASSOC);

// 4. ข้อมูลตาราง (ดึง owner_name มาแสดงเป็นประเภทอ้อย)
$sql_details = "SELECT r.*, s.statn_name FROM daily_truck_reports r JOIN stations s ON r.statn_code = s.statn_code WHERE r.report_date BETWEEN ? AND ?";
$params_details = [$start_date, $end_date];
if ($statn_filter !== 'ALL') { $sql_details .= " AND r.statn_code = ?"; $params_details[] = $statn_filter; }
$sql_details .= " ORDER BY r.report_date DESC, r.created_at DESC";
$stmt_details = $pdo->prepare($sql_details);
$stmt_details->execute($params_details);
$all_reports = $stmt_details->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Dashboard รายงาน</title>
    <link rel="icon" href="bg/v2.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
    <style>
        body { background-color: #dde9faff; font-family: 'Kanit', sans-serif; }
        .filter-section { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .summary-circle {
            width: 200px; height: 200px; border-radius: 50%;
            border: 15px solid #0d6efd; display: flex;
            flex-direction: column; align-items: center; justify-content: center;
            background: white; margin: 0 auto; box-shadow: 0 10px 20px rgba(13, 110, 253, 0.1);
        }
        .report-card { background: white; border-radius: 20px; overflow: hidden; border: none; }
        .table-head-custom { background: #f8f9fa; color: #6c757d; font-weight: 600; font-size: 0.85rem; }
        .chart-container { background: white; border-radius: 20px; padding: 20px; }
        .btn-custom { border-radius: 10px; padding: 10px 25px; font-weight: 600; }
        .data-row:hover { background-color: #f8faff; cursor: pointer; }
    </style>
</head>
<body>
<?php include 'nvb_report.php'; ?>

<div class="container py-4">
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="filter-section mb-4 text-center">
                <h5 class="fw-bold mb-4 text-start">ตัวกรองรายงาน</h5>
                <form method="GET">
                    <div class="mb-3 text-start">
                        <label class="small text-muted">เลือกหน่วยงาน</label>
                        <select name="statn_code" class="form-select border-0 bg-light p-3">
                            <option value="ALL">หน่วยทั้งหมด</option>
                            <?php foreach($stations_list as $st): ?>
                                <option value="<?= $st['statn_code'] ?>" <?= $statn_filter == $st['statn_code'] ? 'selected' : '' ?>><?= $st['statn_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6 text-start">
                            <label class="small text-muted">เริ่มวันที่</label>
                            <input type="date" name="start_date" class="form-control border-0 bg-light" value="<?= $start_date ?>">
                        </div>
                        <div class="col-6 text-start">
                            <label class="small text-muted">สิ้นสุดวันที่</label>
                            <input type="date" name="end_date" class="form-control border-0 bg-light" value="<?= $end_date ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 btn-custom mb-2 shadow-sm">ประมวลผลข้อมูล</button>
                    <button type="button" class="btn btn-success w-100 btn-custom" 
                        onclick="location.href='export_excelAA.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&statn_code=<?= $statn_filter ?>'">
                        <i class="bi bi-file-earmark-excel me-2"></i>ส่งออกข้อมูลเป็น Excel
                    </button>
                </form>
            </div>

            <div class="filter-section text-center shadow-sm">
                <h5 class="fw-bold mb-4 text-start">สรุปยอดรวมเข้างาน</h5>
                <div class="summary-circle mt-3">
                    <span class="text-muted small">รวมทั้งสิ้น</span>
                    <h1 class="fw-bold text-primary mb-0"><?= number_format($summary['total_trucks']) ?></h1>
                    <span class="text-muted small">คัน</span>
                </div>
                <p class="mt-4 text-muted small">อ้างอิงข้อมูลในช่วงเวลาที่เลือก</p>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="chart-container mb-4 shadow-sm border">
                <h6 class="fw-bold mb-4 text-primary">สถิติรถบรรทุกรายวัน</h6>
                <div style="height: 300px;"><canvas id="truckChart"></canvas></div>
            </div>

            <div class="report-card shadow-sm border">
                <div class="p-3 d-flex justify-content-between align-items-center bg-white border-bottom">
                    <h6 class="fw-bold mb-0">รายการเดินรถล่าสุด</h6>
                    <div class="d-flex gap-2">
                        <input type="text" id="masterSearch" class="form-control form-control-sm rounded-pill" placeholder="ค้นหาด่วน...">
                        <select id="pageSize" class="form-select form-select-sm rounded-pill w-auto">
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead class="table-head-custom">
                            <tr>
                                <th class="ps-4">วัน/เวลา</th>
                                <th>หน่วย</th>
                                <th>ทะเบียนรถ</th>
                                <th>ทะเบียนพ่วง</th>
                                <th>ประเภทอ้อย</th>
                                <th class="text-center">เบอร์ตัด</th>
                            </tr>
                        </thead>
                        <tbody id="reportTableBody" class="bg-white"></tbody>
                    </table>
                </div>
                <div class="p-3 bg-light d-flex justify-content-between align-items-center">
                    <div id="infoText" class="small fw-bold text-muted"></div>
                    <nav><ul class="pagination pagination-sm mb-0 shadow-sm" id="pagination"></ul></nav>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// กราฟ
const dailyData = <?= json_encode($daily_stats) ?>;
const ctx = document.getElementById('truckChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: dailyData.map(d => d.report_date),
        datasets: [{
            label: 'คัน',
            data: dailyData.map(d => d.daily_total),
            backgroundColor: 'rgba(13, 110, 253, 0.8)',
            borderRadius: 8,
            barThickness: 30
        }]
    },
    options: {
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { borderDash: [5, 5] } },
            x: { grid: { display: false } }
        }
    }
});

// ตาราง
const allData = <?= json_encode($all_reports) ?>;
let filteredData = [...allData];
let currentPage = 1;
let rowsPerPage = 10;

function renderTable() {
    const start = (currentPage - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    const items = filteredData.slice(start, end);

    document.getElementById('reportTableBody').innerHTML = items.map(row => `
        <tr class="data-row border-bottom">
            <td class="ps-4">
                <div class="fw-bold text-dark">${row.report_date}</div>
                <div class="text-muted small">${row.created_at.split(' ')[1].substring(0,5)} น.</div>
            </td>
            <td><span class="text-primary small"><i class="bi bi-geo-alt me-1"></i>${row.statn_name}</span></td>
            <td><span class="fw-bold">${row.main_truck_license}</span></td>
            <td>${row.trailer_license || '<span class="text-muted">-</span>'}</td>
            <td>
                <span class="badge ${row.owner_name === 'อ้อยท่อน' ? 'bg-primary' : 'bg-success'} px-2 rounded-pill small">
                    ${row.owner_name || 'ไม่ระบุ'}
                </span>
            </td>
            <td class="text-center"><span class="badge bg-secondary-subtle text-secondary px-3">${row.harvester_code}</span></td>
        </tr>
    `).join('');

    document.getElementById('infoText').innerText = `แสดง ${start + 1} - ${Math.min(end, filteredData.length)} จากทั้งหมด ${filteredData.length}`;
    updatePagination();
}

// ฟังก์ชันส่งออก Excel
function exportToExcel() {
    const dataToExport = filteredData.map(row => ({
        "วันที่": row.report_date,
        "เวลา": row.created_at.split(' ')[1].substring(0,5),
        "หน่วยงาน": row.statn_name,
        "ทะเบียนรถ": row.main_truck_license,
        "ทะเบียนพ่วง": row.trailer_license || '-',
        "ประเภทอ้อย": row.owner_name || 'ไม่ระบุ',
        "เบอร์รถตัด": row.harvester_code
    }));

    const worksheet = XLSX.utils.json_to_sheet(dataToExport);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "รายงานการเดินรถ");
    
    // ตั้งชื่อไฟล์ตามวันที่ที่เลือก
    const fileName = `รายงานการเดินรถ_${"<?= $start_date ?>"}_ถึง_${"<?= $end_date ?>"}.xlsx`;
    XLSX.writeFile(workbook, fileName);
}

function updatePagination() {
    const pageCount = Math.ceil(filteredData.length / rowsPerPage);
    let html = '';
    for (let i = 1; i <= pageCount; i++) {
        html += `<li class="page-item ${i === currentPage ? 'active' : ''}"><a class="page-link" href="#" onclick="currentPage=${i};renderTable();return false;">${i}</a></li>`;
    }
    document.getElementById('pagination').innerHTML = html;
}

document.getElementById('masterSearch').addEventListener('input', (e) => {
    const t = e.target.value.toLowerCase();
    filteredData = allData.filter(d => 
        d.main_truck_license.toLowerCase().includes(t) || 
        d.statn_name.toLowerCase().includes(t) ||
        (d.owner_name && d.owner_name.toLowerCase().includes(t))
    );
    currentPage = 1; renderTable();
});

document.getElementById('pageSize').addEventListener('change', (e) => {
    rowsPerPage = parseInt(e.target.value);
    currentPage = 1; renderTable();
});

renderTable();
</script>
</body>
</html>