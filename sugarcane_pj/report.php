<?php
require_once 'db_connect.php'; 

// Initialize variables
$selected_year = $_GET['year'] ?? '';
$selected_agency = $_GET['agency'] ?? '';
$selected_contract = $_GET['contract'] ?? '';
$selected_plot = $_GET['plot_id'] ?? '';

// Get distinct production years
$years = [];
$stmt_years = $conn->prepare("SELECT DISTINCT production_year FROM soil_data ORDER BY production_year DESC");
$stmt_years->execute();
$result_years = $stmt_years->get_result();
while ($row = $result_years->fetch_assoc()) {
    $years[] = $row['production_year'];
}
$stmt_years->close();

$quality_map = [1 => 'ดีมาก', 2 => 'ดี', 3 => 'พอใช้'];
$planting_map = [1 => 'มาตรฐาน', 2 => 'ไม่ได้มาตรฐาน'];
$watering_map = [1 => 'มี', 2 => 'ไม่มี'];

function getCounts($conn, $year, $agency = null, $contract = null, $quality_map, $planting_map, $watering_map) {
    $conditions = ["production_year = ?"];
    $params = [$year];
    $param_types = "s";

    if ($agency) { $conditions[] = "agency = ?"; $params[] = $agency; $param_types .= "s"; }
    if ($contract) { $conditions[] = "contract_number = ?"; $params[] = $contract; $param_types .= "s"; }

    $where_clause = implode(' AND ', $conditions);
    $counts = [
        'soil_type' => ['ดีมาก' => 0, 'ดี' => 0, 'พอใช้' => 0],
        'soil_preparation' => ['ดีมาก' => 0, 'ดี' => 0, 'พอใช้' => 0],
        'cane_variety' => ['ดีมาก' => 0, 'ดี' => 0, 'พอใช้' => 0],
        'planting' => ['มาตรฐาน' => 0, 'ไม่ได้มาตรฐาน' => 0],
        'watering' => ['มี' => 0, 'ไม่มี' => 0],
    ];

    $sql = "SELECT soil_type, soil_preparation_details, cane_variety, planting_details, watering_details FROM soil_data WHERE " . $where_clause;
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $soil_type_label = $quality_map[$row['soil_type']] ?? null;
        if ($soil_type_label && isset($counts['soil_type'][$soil_type_label])) $counts['soil_type'][$soil_type_label]++;

        $soil_prep_label = $quality_map[$row['soil_preparation_details']] ?? null;
        if ($soil_prep_label && isset($counts['soil_preparation'][$soil_prep_label])) $counts['soil_preparation'][$soil_prep_label]++;

        $variety_label = $quality_map[$row['cane_variety']] ?? null;
        if ($variety_label && isset($counts['cane_variety'][$variety_label])) $counts['cane_variety'][$variety_label]++;

        $planting_label = $planting_map[$row['planting_details']] ?? null;
        if ($planting_label && isset($counts['planting'][$planting_label])) $counts['planting'][$planting_label]++;

        $watering_label = $watering_map[$row['watering_details']] ?? null;
        if ($watering_label && isset($counts['watering'][$watering_label])) $counts['watering'][$watering_label]++;
    }
    $stmt->close();
    return $counts;
}

function getPlotDetails($conn, $year, $agency, $contract, $plot_id_val, $quality_map, $planting_map, $watering_map) {
    $plot_data = null;
    $sql_plot = "SELECT * FROM soil_data WHERE production_year = ? AND agency = ? AND contract_number = ? AND plot_id = ?";
    $stmt_plot = $conn->prepare($sql_plot);
    $stmt_plot->bind_param("ssss", $year, $agency, $contract, $plot_id_val);
    $stmt_plot->execute();
    $result_plot = $stmt_plot->get_result();

    if ($result_plot->num_rows > 0) {
        $plot_data = $result_plot->fetch_assoc();
        $plot_data['soil_type_label'] = $quality_map[$plot_data['soil_type']] ?? $plot_data['soil_type'];
        $plot_data['soil_prep_label'] = $quality_map[$plot_data['soil_preparation_details']] ?? $plot_data['soil_preparation_details'];
        $plot_data['cane_variety_label'] = $quality_map[$plot_data['cane_variety']] ?? $plot_data['cane_variety'];
        $plot_data['planting_label'] = $planting_map[$plot_data['planting_details']] ?? $plot_data['planting_details'];
        $plot_data['watering_label'] = $watering_map[$plot_data['watering_details']] ?? $plot_data['watering_details'];
    }
    $stmt_plot->close();
    return $plot_data;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานข้อมูลแปลงอ้อย | ระบบตรวจสอบ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="index.css">
    <style>
        .report-header { margin-bottom: 2rem; }
        .summary-card {
            background: #f5f5f5ff;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            border: 2px solid #e2e8f0;
            border-left: 8px solid var(--primary);
            box-shadow: 0 4px 10px rgba(0,0,0,0.02);
            transition: all 0.3s;
        }
        .summary-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .summary-title { font-weight: 700; font-size: 1.25rem; color: var(--primary); margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
        .stat-badge { background: #f1f5f9; padding: 8px 15px; border-radius: 12px; font-size: 1.1rem; font-weight: 600; border: 2px solid #e2e8f0; color: #334155; }
        .stat-value { font-weight: 800; color: var(--primary); margin-left: 8px; font-size: 1.2rem; }
        .detail-item { background: #ffffff; padding: 20px; border-radius: 15px; margin-bottom: 15px; border: 2px solid #e2e8f0; }
        .detail-label { color: #64748b; font-size: 1rem; font-weight: 600; display: block; margin-bottom: 5px; }
        .detail-val { font-weight: 700; color: var(--text-main); font-size: 1.15rem; }
        
        @media print {
            .navbar, .btn, .no-print { display: none !important; }
            body { background: white; }
            .glass-card-white { box-shadow: none; border: 1px solid #ccc; }
        }

        @media (max-width: 768px) {
            .summary-card { padding: 1rem; border-radius: 15px; }
            .summary-title { font-size: 1rem; }
            .stat-badge { font-size: 0.8rem; padding: 4px 8px; }
            h2.fw-bold { font-size: 1.5rem !important; }
            .breadcrumb { font-size: 0.8rem; }
            .detail-item { padding: 10px; }
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div class="container py-4">
        <div class="glass-card-white fade-in">
            <div class="d-flex justify-content-between align-items-center report-header flex-wrap gap-3">
                <h2 class="mb-0 fw-bold" style="font-size: 2.2rem; color: var(--primary);"><i class='bx bxs-file-find'></i> รายงานสรุปผล</h2>
                
                <form method="GET" action="report.php" class="d-flex align-items-center no-print">
                    <select name="year" class="form-select form-select-premium" onchange="this.form.submit()">
                        <option value="">-- เลือกปีการผลิต --</option>
                        <?php foreach ($years as $y): ?>
                            <option value="<?php echo htmlspecialchars($y); ?>" <?php echo ($selected_year == $y) ? 'selected' : ''; ?>>
                                ปี <?php echo htmlspecialchars($y); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <?php if (!empty($selected_year)): ?>
                <?php if (empty($selected_agency) && empty($selected_contract) && empty($selected_plot)): ?>
                    <h4 class="mb-4 text-muted">ประจำปี <?php echo htmlspecialchars($selected_year); ?></h4>
                    <?php $counts = getCounts($conn, $selected_year, null, null, $quality_map, $planting_map, $watering_map); ?>
                    
                    <div class="row g-3 mb-5">
                        <div class="col-md-4">
                            <div class="summary-card h-100">
                                <div class="summary-title"><i class='bx bx-cube'></i> ชนิดดิน</div>
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="stat-badge">ดีมาก <span class="stat-value"><?php echo $counts['soil_type']['ดีมาก']; ?></span></span>
                                    <span class="stat-badge">ดี <span class="stat-value"><?php echo $counts['soil_type']['ดี']; ?></span></span>
                                    <span class="stat-badge">พอใช้ <span class="stat-value"><?php echo $counts['soil_type']['พอใช้']; ?></span></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="summary-card h-100">
                                <div class="summary-title"><i class='bx bx-landscape'></i> การเตรียมดิน</div>
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="stat-badge">ดีมาก <span class="stat-value"><?php echo $counts['soil_preparation']['ดีมาก']; ?></span></span>
                                    <span class="stat-badge">ดี <span class="stat-value"><?php echo $counts['soil_preparation']['ดี']; ?></span></span>
                                    <span class="stat-badge">พอใช้ <span class="stat-value"><?php echo $counts['soil_preparation']['พอใช้']; ?></span></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="summary-card h-100">
                                <div class="summary-title"><i class='bx bx-sun'></i> พันธุ์อ้อย</div>
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="stat-badge">ดีมาก <span class="stat-value"><?php echo $counts['cane_variety']['ดีมาก']; ?></span></span>
                                    <span class="stat-badge">ดี <span class="stat-value"><?php echo $counts['cane_variety']['ดี']; ?></span></span>
                                    <span class="stat-badge">พอใช้ <span class="stat-value"><?php echo $counts['cane_variety']['พอใช้']; ?></span></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h5 class="fw-bold mb-3">สรุปตามรายหน่วยงาน</h5>
                    <div class="table-responsive">
                        <table class="table table-hover datatable-modern">
                            <thead class="table-light"><tr><th>หน่วยงาน</th><th>จำนวนแปลง</th><th class="no-print">ปฏิบัติ</th></tr></thead>
                            <tbody>
                                <?php
                                $st = $conn->prepare("SELECT agency, COUNT(id) as total FROM soil_data WHERE production_year = ? GROUP BY agency ORDER BY agency");
                                $st->bind_param("s", $selected_year); $st->execute(); $res = $st->get_result();
                                while ($row = $res->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['agency']); ?></td>
                                        <td><span class="badge bg-light text-dark border"><?php echo $row['total']; ?> แปลง</span></td>
                                        <td class="no-print"><a href="report.php?year=<?php echo urlencode($selected_year); ?>&agency=<?php echo urlencode($row['agency']); ?>" class="btn btn-sm btn-outline-primary"><i class='bx bx-search-alt'></i> ดูรายละเอียด</a></td>
                                    </tr>
                                <?php endwhile; $st->close(); ?>
                            </tbody>
                        </table>
                    </div>

                <?php elseif (!empty($selected_agency) && empty($selected_contract)): ?>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="report.php?year=<?php echo urlencode($selected_year); ?>">ปี <?php echo htmlspecialchars($selected_year); ?></a></li>
                            <li class="breadcrumb-item active">หน่วยงาน: <?php echo htmlspecialchars($selected_agency); ?></li>
                        </ol>
                    </nav>
                    <h3 class="fw-bold mb-4">รายงานหน่วยงาน: <?php echo htmlspecialchars($selected_agency); ?></h3>
                    
                    <h5 class="fw-bold mb-3">สรุปตามรายเลขสัญญา</h5>
                    <div class="table-responsive">
                        <table class="table table-hover datatable-modern">
                            <thead class="table-light"><tr><th>เลขสัญญา</th><th>จำนวนแปลง</th><th class="no-print">ปฏิบัติ</th></tr></thead>
                            <tbody>
                                <?php
                                $st = $conn->prepare("SELECT contract_number, COUNT(id) as total FROM soil_data WHERE production_year = ? AND agency = ? GROUP BY contract_number ORDER BY contract_number");
                                $st->bind_param("ss", $selected_year, $selected_agency); $st->execute(); $res = $st->get_result();
                                while ($row = $res->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['contract_number']); ?></td>
                                        <td><span class="badge bg-light text-dark border"><?php echo $row['total']; ?> แปลง</span></td>
                                        <td class="no-print"><a href="report.php?year=<?php echo urlencode($selected_year); ?>&agency=<?php echo urlencode($selected_agency); ?>&contract=<?php echo urlencode($row['contract_number']); ?>" class="btn btn-sm btn-outline-primary"><i class='bx bx-search-alt'></i> ดูรายละเอียด</a></td>
                                    </tr>
                                <?php endwhile; $st->close(); ?>
                            </tbody>
                        </table>
                    </div>

                <?php elseif (!empty($selected_contract) && empty($selected_plot)): ?>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="report.php?year=<?php echo urlencode($selected_year); ?>">ปี <?php echo htmlspecialchars($selected_year); ?></a></li>
                            <li class="breadcrumb-item"><a href="report.php?year=<?php echo urlencode($selected_year); ?>&agency=<?php echo urlencode($selected_agency); ?>">หน่วยงาน: <?php echo htmlspecialchars($selected_agency); ?></a></li>
                            <li class="breadcrumb-item active">สัญญา: <?php echo htmlspecialchars($selected_contract); ?></li>
                        </ol>
                    </nav>
                    <h3 class="fw-bold mb-4">รายการรายแปลงในสัญญา: <?php echo htmlspecialchars($selected_contract); ?></h3>
                    <div class="table-responsive">
                        <table class="table table-hover datatable-modern">
                            <thead class="table-light"><tr><th>ID แปลง</th><th>พื้นที่ (ไร่)</th><th class="no-print">ปฏิบัติ</th></tr></thead>
                            <tbody>
                                <?php
                                $st = $conn->prepare("SELECT plot_id, rai_area FROM soil_data WHERE production_year = ? AND agency = ? AND contract_number = ? ORDER BY plot_id");
                                $st->bind_param("sss", $selected_year, $selected_agency, $selected_contract); $st->execute(); $res = $st->get_result();
                                while ($row = $res->fetch_assoc()): ?>
                                    <tr>
                                        <td><span class="fw-bold"><?php echo htmlspecialchars($row['plot_id']); ?></span></td>
                                        <td><?php echo number_format($row['rai_area'], 2); ?></td>
                                        <td class="no-print"><a href="report.php?year=<?php echo urlencode($selected_year); ?>&agency=<?php echo urlencode($selected_agency); ?>&contract=<?php echo urlencode($selected_contract); ?>&plot_id=<?php echo urlencode($row['plot_id']); ?>" class="btn btn-sm btn-info text-white"><i class='bx bx-show'></i> ดูข้อมูลแปลง</a></td>
                                    </tr>
                                <?php endwhile; $st->close(); ?>
                            </tbody>
                        </table>
                    </div>

                <?php elseif (!empty($selected_plot)): ?>
                    <?php $plot = getPlotDetails($conn, $selected_year, $selected_agency, $selected_contract, $selected_plot, $quality_map, $planting_map, $watering_map); ?>
                    <nav aria-label="breadcrumb" class="no-print">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="report.php?year=<?php echo urlencode($selected_year); ?>">ปี <?php echo htmlspecialchars($selected_year); ?></a></li>
                            <li class="breadcrumb-item"><a href="report.php?year=<?php echo urlencode($selected_year); ?>&agency=<?php echo urlencode($selected_agency); ?>">หน่วงงาน: <?php echo htmlspecialchars($selected_agency); ?></a></li>
                            <li class="breadcrumb-item"><a href="report.php?year=<?php echo urlencode($selected_year); ?>&agency=<?php echo urlencode($selected_agency); ?>&contract=<?php echo urlencode($selected_contract); ?>">สัญญา: <?php echo htmlspecialchars($selected_contract); ?></a></li>
                            <li class="breadcrumb-item active">แปลง: <?php echo htmlspecialchars($selected_plot); ?></li>
                        </ol>
                    </nav>
                    
                    <?php if ($plot): ?>
                        <div class="row g-4 mt-2">
                            <div class="col-md-4">
                                <div class="detail-item h-100 shadow-sm border-0">
                                    <span class="detail-label"><i class='bx bx-id-card'></i> ข้อมูลพื้นฐาน</span>
                                    <div class="mt-2">
                                        <div class="mb-2">ID แปลง: <span class="detail-val"><?php echo htmlspecialchars($plot['plot_id']); ?></span></div>
                                        <div class="mb-2">สัญญา: <span class="detail-val"><?php echo htmlspecialchars($plot['contract_number']); ?></span></div>
                                        <div class="mb-2">หน่วยงาน: <span class="detail-val"><?php echo htmlspecialchars($plot['agency']); ?></span></div>
                                        <div>พื้นที่: <span class="detail-val badge bg-success"><?php echo number_format($plot['rai_area'], 2); ?> ไร่</span></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="glass-card h-100 border-0" style="background: rgba(0,0,0,0.02); color: #333;">
                                    <span class="detail-label"><i class='bx bx-check-square'></i> ผลการประเมิน</span>
                                    <div class="row mt-3 g-3">
                                        <div class="col-sm-6">
                                            <div class="stat-badge w-100 p-2">ชนิดดิน: <span class="stat-value text-primary"><?php echo $plot['soil_type_label']; ?></span></div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="stat-badge w-100 p-2">การเตรียมดิน: <span class="stat-value text-primary"><?php echo $plot['soil_prep_label']; ?></span></div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="stat-badge w-100 p-2">พันธุ์อ้อย: <span class="stat-value text-primary"><?php echo $plot['cane_variety_label']; ?></span></div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="stat-badge w-100 p-2">การปลูก: <span class="stat-value text-primary"><?php echo $plot['planting_label']; ?></span></div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="stat-badge w-100 p-2">การให้น้ำ: <span class="stat-value text-primary"><?php echo $plot['watering_label']; ?></span></div>
                                        </div>
                                    </div>
                                    <div class="mt-4 p-3 bg-white rounded-3 border">
                                        <span class="detail-label">หมายเหตุ</span>
                                        <div class="mt-1"><?php echo !empty($plot['notes']) ? nl2br(htmlspecialchars($plot['notes'])) : '<span class="text-muted small">- ไม่มีหมายเหตุ -</span>'; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mt-4">ไม่พบข้อมูลแปลงที่ระบุ</div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="mt-5 no-print d-flex gap-2">
                    <button onclick="window.print()" class="btn btn-glass" style="color: #666; border-color: #ccc;"><i class='bx bx-printer'></i> พิมพ์รายงาน</button>
                    <a href="dashboard.php?year=<?php echo urlencode($selected_year); ?>" class="btn btn-outline-secondary rounded-3 px-4">กลับแดชบอร์ด</a>
                </div>

            <?php else: ?>
                <div class="p-5 text-center text-muted">
                    <i class='bx bx-search p-3 bg-light rounded-circle' style="font-size: 3rem;"></i>
                    <p class="mt-3">กรุณาเลือกปีการผลิตจากตัวเลือกด้านบน</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

    <script>
    $(document).ready(function() {
        $('.datatable-modern').DataTable({
            "language": { "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/th.json" },
            "paging": true,
            "info": true,
            "responsive": true
        });
    });
    </script>
</body>
</html>
<?php $conn->close(); ?>
