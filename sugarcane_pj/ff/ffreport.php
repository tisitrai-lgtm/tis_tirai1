<?php
require_once 'db_connect.php'; // Include your database connection

// Initialize variables for selected year, agency, contract, and plot
$selected_year = $_GET['year'] ?? '';
$selected_agency = $_GET['agency'] ?? '';
$selected_contract = $_GET['contract'] ?? '';
$selected_plot = $_GET['plot_id'] ?? ''; // For single plot detail

// Get distinct production years for the dropdown
$years = [];
$stmt_years = $conn->prepare("SELECT DISTINCT production_year FROM soil_data ORDER BY production_year DESC");
$stmt_years->execute();
$result_years = $stmt_years->get_result();
while ($row = $result_years->fetch_assoc()) {
    $years[] = $row['production_year'];
}
$stmt_years->close();

// Define mappings from numbers to labels
$quality_map = [
    1 => 'ดีมาก',
    2 => 'ดี',
    3 => 'พอใช้',
];
$planting_map = [
    1 => 'มาตรฐาน',
    2 => 'ไม่ได้มาตรฐาน',
];
$watering_map = [
    1 => 'มี',
    2 => 'ไม่มี',
];

// --- Function to get counts based on criteria (remains the same) ---
function getCounts($conn, $year, $agency = null, $contract = null, $quality_map, $planting_map, $watering_map) {
    $conditions = ["production_year = ?"];
    $params = [$year];
    $param_types = "s";

    if ($agency) {
        $conditions[] = "agency = ?";
        $params[] = $agency;
        $param_types .= "s";
    }
    if ($contract) {
        $conditions[] = "contract_number = ?";
        $params[] = $contract;
        $param_types .= "s";
    }

    $where_clause = implode(' AND ', $conditions);

    $counts = [
        'soil_type' => ['ดีมาก' => 0, 'ดี' => 0, 'พอใช้' => 0],
        'soil_preparation' => ['ดีมาก' => 0, 'ดี' => 0, 'พอใช้' => 0],
        'cane_variety' => ['ดีมาก' => 0, 'ดี' => 0, 'พอใช้' => 0],
        'planting' => ['มาตรฐาน' => 0, 'ไม่ได้มาตรฐาน' => 0],
        'watering' => ['มี' => 0, 'ไม่มี' => 0],
    ];

    // SELECT notes here to ensure it's available for other uses if needed,
    // though getCounts itself only counts the mapped fields.
    $sql = "SELECT soil_type, soil_preparation_details, cane_variety, planting_details, watering_details, notes FROM soil_data WHERE " . $where_clause;
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $soil_type_label = $quality_map[$row['soil_type']] ?? null;
        if ($soil_type_label && isset($counts['soil_type'][$soil_type_label])) {
            $counts['soil_type'][$soil_type_label]++;
        }

        $soil_preparation_label = $quality_map[$row['soil_preparation_details']] ?? null;
        if ($soil_preparation_label && isset($counts['soil_preparation'][$soil_preparation_label])) {
            $counts['soil_preparation'][$soil_preparation_label]++;
        }

        $cane_variety_label = $quality_map[$row['cane_variety']] ?? null;
        if ($cane_variety_label && isset($counts['cane_variety'][$cane_variety_label])) {
            $counts['cane_variety'][$cane_variety_label]++;
        }

        $planting_label = $planting_map[$row['planting_details']] ?? null;
        if ($planting_label && isset($counts['planting'][$planting_label])) {
            $counts['planting'][$planting_label]++;
        }

        $watering_label = $watering_map[$row['watering_details']] ?? null;
        if ($watering_label && isset($counts['watering'][$watering_label])) {
            $counts['watering'][$watering_label]++;
        }
    }
    $stmt->close();
    return $counts;
}


// NEW FUNCTION: To get single plot details (without images)
function getPlotDetails($conn, $year, $agency, $contract, $plot_id_val, $quality_map, $planting_map, $watering_map) {
    $plot_data = null;

    // Fetch plot basic details
    // ตรวจสอบให้แน่ใจว่าคุณ SELECT ทุกคอลัมน์ที่ต้องการแสดงในหน้ารายละเอียดแปลง
    $sql_plot = "SELECT * FROM soil_data WHERE production_year = ? AND agency = ? AND contract_number = ? AND plot_id = ?";
    $stmt_plot = $conn->prepare($sql_plot);
    $stmt_plot->bind_param("ssss", $year, $agency, $contract, $plot_id_val);
    $stmt_plot->execute();
    $result_plot = $stmt_plot->get_result();

    if ($result_plot->num_rows > 0) {
        $plot_data_raw = $result_plot->fetch_assoc();

        // Convert numeric values to their labels for display
        $plot_data = $plot_data_raw; // Start with raw data
        $plot_data['soil_type_label'] = $quality_map[$plot_data_raw['soil_type']] ?? $plot_data_raw['soil_type'];
        $plot_data['soil_preparation_details_label'] = $quality_map[$plot_data_raw['soil_preparation_details']] ?? $plot_data_raw['soil_preparation_details'];
        $plot_data['cane_variety_label'] = $quality_map[$plot_data_raw['cane_variety']] ?? $plot_data_raw['cane_variety'];
        $plot_data['planting_details_label'] = $planting_map[$plot_data_raw['planting_details']] ?? $plot_data_raw['planting_details'];
        $plot_data['watering_details_label'] = $watering_map[$plot_data_raw['watering_details']] ?? $plot_data_raw['watering_details'];
    }
    $stmt_plot->close();

    return ['plot_data' => $plot_data]; // No 'images' key
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานข้อมูลแปลงอ้อย</title>
      <link rel="icon" href="icon/unnamed.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa; /* Light gray background */
        }
        .navbar-custom {
            background: linear-gradient(90deg, #007bff, #00c6ff);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .navbar-brand, .nav-link {
            color: white !important;
            font-weight: bold;
        }
        .nav-link:hover {
            color: #ffd700 !important;
            transform: translateY(-2px);
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-top: 30px; /* Space from navbar */
            margin-bottom: 30px;
        }
        h1, h2, h3 {
            color: #2c3e50;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-top: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        select.form-select { /* Use Bootstrap's form-select class */
            padding: 10px 15px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 1rem;
            margin-right: 10px;
            width: auto; /* Allow select to size itself */
            display: inline-block; /* To align with label */
        }
        .summary-box {
            display: flex;
            flex-wrap: wrap; /* Keep wrapping as per original, but content within item will be inline */
            gap: 15px;
            margin-top: 20px;
        }
        .summary-item {
            flex: 1 1 calc(33.33% - 15px); /* 3 items per row on wider screens */
            background-color: #e9f7ef;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #d4ede0;
            min-width: 280px; /* Ensure items don't become too small */
        }
        .summary-item h4 {
            margin-top: 0;
            color: #28a745; /* Bootstrap success green */
            font-size: 1.25rem;
            margin-bottom: 10px;
        }
        /* Style for the inline content within summary-item */
        .summary-item .inline-summary {
            font-size: 1rem;
            margin-bottom: 0; /* Remove bottom margin from the div holding inline content */
            display: block; /* Make it a block to contain the spans nicely */
        }
        .summary-item .inline-summary span {
            /* No specific style needed for spans, they are inline by default */
        }
        table.table {
            margin-top: 20px;
            font-size: 0.95rem;
        }
        table.table thead th {
            background-color: #f2f2f2;
            color: #555;
            vertical-align: middle;
            text-align: center;
        }
        table.table tbody td {
            vertical-align: middle;
            text-align: center;
        }
        td a {
            color: #007bff;
            text-decoration: none;
        }
        td a:hover {
            text-decoration: underline;
        }
        .back-button {
            margin-top: 25px;
            display: inline-block;
            padding: 10px 20px;
            background-color: #6c757d; /* Bootstrap secondary gray */
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .back-button:hover {
            background-color: #5a6268;
        }
        .no-data {
            text-align: center;
            color: #888;
            margin-top: 30px;
            padding: 20px;
            background-color: #fff3cd; /* Light warning yellow */
            border: 1px solid #ffeeba;
            border-radius: 5px;
        }
        .plot-detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        .plot-detail-item {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            padding: 15px;
            word-wrap: break-word; /* This will wrap long words */
            overflow-wrap: break-word; /* Modern equivalent */
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .summary-item {
                flex: 1 1 100%; /* Stack items on small screens */
            }
        }

        /* --- Custom styles for 'notes' truncation (within table cell) --- */
        .notes-cell {
            position: relative; /* Needed for absolute positioning of buttons */
            /* max-width: 150px; Remove if DataTables handling width */
            vertical-align: top; /* Align content to top if text wraps */
            padding-right: 20px; /* Space for the "..." button if needed */
        }

        .truncated-content {
            display: inline; /* Keep text inline */
        }

        .truncated-content .full-text {
            display: none; /* Hidden by default */
        }

        .truncated-content.expanded .short-text {
            display: none; /* Hide short text when expanded */
        }

        .truncated-content.expanded .full-text {
            display: inline; /* Show full text when expanded */
        }

        .toggle-notes {
            display: inline-block; /* Make the "..." / "ซ่อน" button inline */
            margin-left: 5px;
            cursor: pointer;
            color: #007bff;
            text-decoration: underline;
            font-size: 0.85em; /* Smaller font for the button */
        }
        .toggle-notes:hover {
            color: #0056b3;
        }
        .toggle-notes.hide-toggle {
            display: none; /* Hide if not needed */
        }
        .toggle-notes.show-toggle {
             display: inline-block;
        }
        /* Make read-less red */
        .toggle-notes.read-less {
            color: #dc3545; /* Bootstrap danger red */
        }
        .toggle-notes.read-less:hover {
            color: #bd2130;
        }
        /* Ensure text in modal body wraps */
        .modal-body {
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; // Include the Navbar ?>

    <div class="container">
        <h1 class="mb-4">รายงานข้อมูลแปลงอ้อย</h1>

        <div class="form-group">
            <form method="GET" action="report.php" class="d-flex align-items-center">
                <label for="year" class="form-label me-2 mb-0">เลือกปีการผลิต:</label>
                <select name="year" id="year" class="form-select w-auto" onchange="this.form.submit()">
                    <option value="">-- เลือกปี --</option>
                    <?php foreach ($years as $year_option): ?>
                        <option value="<?php echo htmlspecialchars($year_option); ?>" <?php echo ($selected_year == $year_option) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($year_option); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($selected_year) && !empty($selected_agency)): ?>
                    <input type="hidden" name="agency" value="<?php echo htmlspecialchars($selected_agency); ?>">
                <?php endif; ?>
                <?php if (!empty($selected_year) && !empty($selected_agency) && !empty($selected_contract)): ?>
                    <input type="hidden" name="contract" value="<?php echo htmlspecialchars($selected_contract); ?>">
                <?php endif; ?>
                <?php if (!empty($selected_year) && !empty($selected_agency) && !empty($selected_contract) && !empty($selected_plot)): // Keep plot_id if drilling down further ?>
                    <input type="hidden" name="plot_id" value="<?php echo htmlspecialchars($selected_plot); ?>">
                <?php endif; ?>
            </form>
        </div>

        <?php if (!empty($selected_year)): ?>
            <?php
            // --- Main Report (Year Summary) ---
            if (empty($selected_agency) && empty($selected_contract) && empty($selected_plot)) {
                echo "<h2>รายงานสรุปประจำปี " . htmlspecialchars($selected_year) . "</h2>";
                $summary_counts = getCounts($conn, $selected_year, null, null, $quality_map, $planting_map, $watering_map);
            ?>
                <div class="summary-box">
                    <div class="summary-item">
                        <h4>ชนิดดิน</h4>
                        <div class="inline-summary">
                            <span>ดีมาก: <?php echo $summary_counts['soil_type']['ดีมาก']; ?></span> |
                            <span>ดี: <?php echo $summary_counts['soil_type']['ดี']; ?></span> |
                            <span>พอใช้: <?php echo $summary_counts['soil_type']['พอใช้']; ?></span>
                        </div>
                    </div>
                    <div class="summary-item">
                        <h4>การเตรียมดิน</h4>
                        <div class="inline-summary">
                            <span>ดีมาก: <?php echo $summary_counts['soil_preparation']['ดีมาก']; ?></span> |
                            <span>ดี: <?php echo $summary_counts['soil_preparation']['ดี']; ?></span> |
                            <span>พอใช้: <?php echo $summary_counts['soil_preparation']['พอใช้']; ?></span>
                        </div>
                    </div>
                    <div class="summary-item">
                        <h4>พันธุ์อ้อย</h4>
                        <div class="inline-summary">
                            <span>ดีมาก: <?php echo $summary_counts['cane_variety']['ดีมาก']; ?></span> |
                            <span>ดี: <?php echo $summary_counts['cane_variety']['ดี']; ?></span> |
                            <span>พอใช้: <?php echo $summary_counts['cane_variety']['พอใช้']; ?></span>
                        </div>
                    </div>
                    <div class="summary-item">
                        <h4>การปลูก</h4>
                        <div class="inline-summary">
                            <span>มาตรฐาน: <?php echo $summary_counts['planting']['มาตรฐาน']; ?></span> |
                            <span>ไม่ได้มาตรฐาน: <?php echo $summary_counts['planting']['ไม่ได้มาตรฐาน']; ?></span>
                        </div>
                    </div>
                    <div class="summary-item">
                        <h4>การให้น้ำ</h4>
                        <div class="inline-summary">
                            <span>มี: <?php echo $summary_counts['watering']['มี']; ?></span> |
                            <span>ไม่มี: <?php echo $summary_counts['watering']['ไม่มี']; ?></span>
                        </div>
                    </div>
                </div>

                <h3 class="mt-4">สรุปข้อมูลตามหน่วยงาน</h3>
                <?php
                $stmt_agencies = $conn->prepare("SELECT agency, COUNT(id) as total_plots FROM soil_data WHERE production_year = ? GROUP BY agency ORDER BY agency");
                $stmt_agencies->bind_param("s", $selected_year);
                $stmt_agencies->execute();
                $result_agencies = $stmt_agencies->get_result();

                if ($result_agencies->num_rows > 0) {
                    echo "<table id='agencyTable' class='table table-striped table-hover table-bordered'>";
                    echo "<thead><tr><th>หน่วยงาน</th><th>จำนวนแปลง</th><th>ดูรายละเอียด</th></tr></thead>";
                    echo "<tbody>";
                    while ($row_agency = $result_agencies->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row_agency['agency']) . "</td>";
                        echo "<td>" . htmlspecialchars($row_agency['total_plots']) . "</td>";
                        echo "<td><a href=\"report.php?year=" . urlencode($selected_year) . "&agency=" . urlencode($row_agency['agency']) . "\">ดูรายงานหน่วยงาน</a></td>";
                        echo "</tr>";
                    }
                    echo "</tbody>";
                    echo "</table>";
                } else {
                    echo "<p class='no-data'>ไม่พบข้อมูลหน่วยงานสำหรับปีนี้</p>";
                }
                $stmt_agencies->close();
            }
            // --- End Main Report ---

            // --- Agency Report (Summary by Agency) ---
            elseif (!empty($selected_agency) && empty($selected_contract) && empty($selected_plot)) {
                echo "<h2>รายงานหน่วยงาน: " . htmlspecialchars($selected_agency) . " (ปี " . htmlspecialchars($selected_year) . ")</h2>";
                $summary_counts = getCounts($conn, $selected_year, $selected_agency, null, $quality_map, $planting_map, $watering_map);
            ?>
                <div class="summary-box">
                    <div class="summary-item">
                        <h4>ชนิดดิน</h4>
                        <div class="inline-summary">
                            <span>ดีมาก: <?php echo $summary_counts['soil_type']['ดีมาก']; ?></span> |
                            <span>ดี: <?php echo $summary_counts['soil_type']['ดี']; ?></span> |
                            <span>พอใช้: <?php echo $summary_counts['soil_type']['พอใช้']; ?></span>
                        </div>
                    </div>
                    <div class="summary-item">
                        <h4>การเตรียมดิน</h4>
                        <div class="inline-summary">
                            <span>ดีมาก: <?php echo $summary_counts['soil_preparation']['ดีมาก']; ?></span> |
                            <span>ดี: <?php echo $summary_counts['soil_preparation']['ดี']; ?></span> |
                            <span>พอใช้: <?php echo $summary_counts['soil_preparation']['พอใช้']; ?></span>
                        </div>
                    </div>
                    <div class="summary-item">
                        <h4>พันธุ์อ้อย</h4>
                        <div class="inline-summary">
                            <span>ดีมาก: <?php echo $summary_counts['cane_variety']['ดีมาก']; ?></span> |
                            <span>ดี: <?php echo $summary_counts['cane_variety']['ดี']; ?></span> |
                            <span>พอใช้: <?php echo $summary_counts['cane_variety']['พอใช้']; ?></span>
                        </div>
                    </div>
                    <div class="summary-item">
                        <h4>การปลูก</h4>
                        <div class="inline-summary">
                            <span>มาตรฐาน: <?php echo $summary_counts['planting']['มาตรฐาน']; ?></span> |
                            <span>ไม่ได้มาตรฐาน: <?php echo $summary_counts['planting']['ไม่ได้มาตรฐาน']; ?></span>
                        </div>
                    </div>
                    <div class="summary-item">
                        <h4>การให้น้ำ</h4>
                        <div class="inline-summary">
                            <span>มี: <?php echo $summary_counts['watering']['มี']; ?></span> |
                            <span>ไม่มี: <?php echo $summary_counts['watering']['ไม่มี']; ?></span>
                        </div>
                    </div>
                </div>

                <h3 class="mt-4">สรุปข้อมูลตามเลขสัญญาในหน่วยงาน "<?php echo htmlspecialchars($selected_agency); ?>"</h3>
                <?php
                $stmt_contracts = $conn->prepare("SELECT contract_number, COUNT(id) as total_plots FROM soil_data WHERE production_year = ? AND agency = ? GROUP BY contract_number ORDER BY contract_number");
                $stmt_contracts->bind_param("ss", $selected_year, $selected_agency);
                $stmt_contracts->execute();
                $result_contracts = $stmt_contracts->get_result();

                if ($result_contracts->num_rows > 0) {
                    echo "<table id='contractTable' class='table table-striped table-hover table-bordered'>";
                    echo "<thead><tr><th>เลขสัญญา</th><th>จำนวนแปลง</th><th>ดูรายละเอียด</th></tr></thead>";
                    echo "<tbody>";
                    while ($row_contract = $result_contracts->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row_contract['contract_number']) . "</td>";
                        echo "<td>" . htmlspecialchars($row_contract['total_plots']) . "</td>";
                        echo "<td><a href=\"report.php?year=" . urlencode($selected_year) . "&agency=" . urlencode($selected_agency) . "&contract=" . urlencode($row_contract['contract_number']) . "\">ดูรายงานสัญญา</a></td>";
                        echo "</tr>";
                    }
                    echo "</tbody>";
                    echo "</table>";
                } else {
                    echo "<p class='no-data'>ไม่พบข้อมูลเลขสัญญาสำหรับหน่วยงานนี้</p>";
                }
                $stmt_contracts->close();
            }
            // --- End Agency Report ---

            // --- Contract Report (Summary by Contract - now includes link to plot detail) ---
            elseif (!empty($selected_agency) && !empty($selected_contract) && empty($selected_plot)) {
                echo "<h2>รายงานเลขสัญญา: " . htmlspecialchars($selected_contract) . " (หน่วยงาน " . htmlspecialchars($selected_agency) . ", ปี " . htmlspecialchars($selected_year) . ")</h2>";
                $summary_counts = getCounts($conn, $selected_year, $selected_agency, $selected_contract, $quality_map, $planting_map, $watering_map);
            ?>
                <div class="summary-box">
                    <div class="summary-item">
                        <h4>ชนิดดิน</h4>
                        <div class="inline-summary">
                            <span>ดีมาก: <?php echo $summary_counts['soil_type']['ดีมาก']; ?></span> |
                            <span>ดี: <?php echo $summary_counts['soil_type']['ดี']; ?></span> |
                            <span>พอใช้: <?php echo $summary_counts['soil_type']['พอใช้']; ?></span>
                        </div>
                    </div>
                    <div class="summary-item">
                        <h4>การเตรียมดิน</h4>
                        <div class="inline-summary">
                            <span>ดีมาก: <?php echo $summary_counts['soil_preparation']['ดีมาก']; ?></span> |
                            <span>ดี: <?php echo $summary_counts['soil_preparation']['ดี']; ?></span> |
                            <span>พอใช้: <?php echo $summary_counts['soil_preparation']['พอใช้']; ?></span>
                        </div>
                    </div>
                    <div class="summary-item">
                        <h4>พันธุ์อ้อย</h4>
                        <div class="inline-summary">
                            <span>ดีมาก: <?php echo $summary_counts['cane_variety']['ดีมาก']; ?></span> |
                            <span>ดี: <?php echo $summary_counts['cane_variety']['ดี']; ?></span> |
                            <span>พอใช้: <?php echo $summary_counts['cane_variety']['พอใช้']; ?></span>
                        </div>
                    </div>
                    <div class="summary-item">
                        <h4>การปลูก</h4>
                        <div class="inline-summary">
                            <span>มาตรฐาน: <?php echo $summary_counts['planting']['มาตรฐาน']; ?></span> |
                            <span>ไม่ได้มาตรฐาน: <?php echo $summary_counts['planting']['ไม่ได้มาตรฐาน']; ?></span>
                        </div>
                    </div>
                    <div class="summary-item">
                        <h4>การให้น้ำ</h4>
                        <div class="inline-summary">
                            <span>มี: <?php echo $summary_counts['watering']['มี']; ?></span> |
                            <span>ไม่มี: <?php echo $summary_counts['watering']['ไม่มี']; ?></span>
                        </div>
                    </div>
                </div>

                <h3 class="mt-4">รายการแปลงในสัญญา "<?php echo htmlspecialchars($selected_contract); ?>"</h3>
                <?php
                // แก้ไข: เพิ่ม 'notes' ใน SELECT statement
                $stmt_plots = $conn->prepare("SELECT plot_id, rai_area, notes FROM soil_data WHERE production_year = ? AND agency = ? AND contract_number = ? ORDER BY plot_id");
                $stmt_plots->bind_param("sss", $selected_year, $selected_agency, $selected_contract);
                $stmt_plots->execute();
                $result_plots = $stmt_plots->get_result();

                if ($result_plots->num_rows > 0) {
                    echo "<table id='plotListTable' class='table table-striped table-hover table-bordered'>";
                    echo "<thead><tr><th>ID แปลง</th><th>พื้นที่ (ไร่)</th><th>หมายเหตุ</th><th>ดูรายละเอียดแปลง</th></tr></thead>";
                    echo "<tbody>";
                    while ($row_plot = $result_plots->fetch_assoc()) {
                        $full_notes = htmlspecialchars($row_plot['notes'] ?? '');
                        $truncated_length = 10; // กำหนดความยาวที่ต้องการตัด
                        $unique_id = 'notes-' . htmlspecialchars($row_plot['plot_id']); // ID เฉพาะสำหรับแต่ละหมายเหตุ

                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row_plot['plot_id']) . "</td>";
                        echo "<td>" . htmlspecialchars($row_plot['rai_area']) . "</td>";
                        echo "<td class='notes-cell' id='" . $unique_id . "'>";
                        if (mb_strlen($full_notes, 'UTF-8') > $truncated_length) {
                            $short_notes = mb_substr($full_notes, 0, $truncated_length, 'UTF-8');
                            echo "<span class='truncated-content'>";
                            echo "<span class='short-text'>" . $short_notes . "...</span>";
                            echo "<span class='full-text'>" . $full_notes . "</span>";
                            echo "</span>"; // Close truncated-content
                            echo "<a href='#' class='toggle-notes show-toggle read-more' data-target-id='" . $unique_id . "'>ดูเพิ่มเติม</a>";
                            echo "<a href='#' class='toggle-notes read-less hide-toggle' data-target-id='" . $unique_id . "'>ซ่อน</a>";
                        } else {
                            echo $full_notes;
                        }
                        echo "</td>";
                        // Link to plot detail report
                        echo "<td><a href=\"report.php?year=" . urlencode($selected_year) . "&agency=" . urlencode($selected_agency) . "&contract=" . urlencode($selected_contract) . "&plot_id=" . urlencode($row_plot['plot_id']) . "\" class='btn btn-sm btn-info'><i class='bx bx-search'></i> ดู</a></td>";
                        echo "</tr>";
                    }
                    echo "</tbody>";
                    echo "</table>";
                } else {
                    echo "<p class='no-data'>ไม่พบข้อมูลแปลงสำหรับสัญญานี้</p>";
                }
                $stmt_plots->close();
            }
            // --- End Contract Report ---

            // --- NEW: Plot Detail Report (without images) ---
            elseif (!empty($selected_agency) && !empty($selected_contract) && !empty($selected_plot)) {
                // Call getPlotDetails without image_upload_base_url
                $plot_data_container = getPlotDetails($conn, $selected_year, $selected_agency, $selected_contract, $selected_plot, $quality_map, $planting_map, $watering_map);
                $plot_data = $plot_data_container['plot_data'];

                if ($plot_data) {
                    echo "<h2>รายละเอียดแปลง: " . htmlspecialchars($plot_data['plot_id']) . "</h2>";
                    echo "<h4>สัญญา: " . htmlspecialchars($plot_data['contract_number']) . " (หน่วยงาน: " . htmlspecialchars($plot_data['agency']) . ", ปี: " . htmlspecialchars($plot_data['production_year']) . ")</h4>";
                ?>
                    <div class="plot-detail-grid">
                        <div class="plot-detail-item"><strong>ชื่อหน่วยงาน:</strong> <?php echo htmlspecialchars($plot_data['agency']); ?></div>
                        <div class="plot-detail-item"><strong>พื้นที่ (ไร่):</strong> <?php echo htmlspecialchars($plot_data['rai_area']); ?></div>
                        <div class="plot-detail-item"><strong>ชนิดดิน:</strong> <?php echo htmlspecialchars($plot_data['soil_type_label']); ?></div>
                        <div class="plot-detail-item"><strong>การเตรียมดิน:</strong> <?php echo htmlspecialchars($plot_data['soil_preparation_details_label']); ?></div>
                        <div class="plot-detail-item"><strong>พันธุ์อ้อย:</strong> <?php echo htmlspecialchars($plot_data['cane_variety_label']); ?></div>
                        <div class="plot-detail-item"><strong>การปลูก:</strong> <?php echo htmlspecialchars($plot_data['planting_details_label']); ?></div>
                        <div class="plot-detail-item"><strong>การให้น้ำ:</strong> <?php echo htmlspecialchars($plot_data['watering_details_label']); ?></div>
                        <div class="plot-detail-item"><strong>เวลาบันทึก:</strong> <?php echo htmlspecialchars($plot_data['created_at']); ?></div>
                        <div class="plot-detail-item"><strong>หมายเหตุ:</strong> <?php echo htmlspecialchars($plot_data['notes']); ?></div>
                    </div>
                  

                <?php
                } else {
                    echo "<p class='no-data'>ไม่พบข้อมูลแปลงที่ระบุ</p>";
                }
            }
            // --- End Plot Detail Report ---
            ?>

            <?php
            // Back button logic (adjusted for the new plot detail level)
            echo "<div class='mt-4'>";
            if (!empty($selected_plot)) {
                echo "<a href=\"report.php?year=" . urlencode($selected_year) . "&agency=" . urlencode($selected_agency) . "&contract=" . urlencode($selected_contract) . "\" class=\"btn btn-secondary back-button\"><i class='bx bx-arrow-back'></i> กลับไปรายงานสัญญา</a>";
            }
            // If we are in the Contract Report (agency and contract are set, but plot is empty)
            elseif (!empty($selected_agency) && !empty($selected_contract) && empty($selected_plot)) {
                echo "<a href=\"report.php?year=" . urlencode($selected_year) . "&agency=" . urlencode($selected_agency) . "\" class=\"btn btn-secondary back-button\"><i class='bx bx-arrow-back'></i> กลับไปรายงานหน่วยงาน</a>";
            }
            elseif (!empty($selected_agency) && empty($selected_contract)) {
                echo "<a href=\"report.php?year=" . urlencode($selected_year) . "\" class=\"btn btn-secondary back-button\"><i class='bx bx-arrow-back'></i> กลับไปรายงานสรุปปี</a>";
            }
            echo "</div>";
            ?>

        <?php else: ?>
            <p class="no-data">กรุณาเลือกปีการผลิตเพื่อดูรายงาน</p>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

    <script>
    $(document).ready(function() {
        // Initialize DataTables for each table if it exists on the page
        if ($('#agencyTable').length) {
            $('#agencyTable').DataTable({
                "language": {
                    "url": "https://cdn.datatables.net/plug-ins/1.11.5/i18n/th.json" // Thai language pack
                },
                "paging": true, // Enable pagination
                "info": true,   // Enable info
                "lengthMenu": [[10, 20, 50, 100, -1], [10, 20, 50, 100, "All"]] // Show entries options
            });
        }

        if ($('#contractTable').length) {
            $('#contractTable').DataTable({
                "language": {
                    "url": "https://cdn.datatables.net/plug-ins/1.11.5/i18n/th.json"
                },
                "paging": true, // Enable pagination
                "info": true,   // Enable info
                "lengthMenu": [[10, 20, 50, 100, -1], [10, 20, 50, 100, "All"]] // Show entries options
            });
        }

        if ($('#plotListTable').length) {
            $('#plotListTable').DataTable({
                "language": {
                    "url": "https://cdn.datatables.net/plug-ins/1.11.5/i18n/th.json"
                },
                "columnDefs": [
                    { "orderable": false, "targets": [2, 3] }, // Disable sorting on "หมายเหตุ" and "ดูรายละเอียดแปลง"
                    { "width": "150px", "targets": 2 } // Set a preferred width for the notes column
                ],
                "paging": true, // Enable pagination
                "info": true,   // Enable info
                "lengthMenu": [[10, 20, 50, 100, -1], [10, 20, 50, 100, "All"]] // Show entries options
            });
        }

        // --- JavaScript for Read More/Read Less functionality within the table cell ---
        // ใช้ Event Delegation เพื่อให้ทำงานกับ DataTables ด้วย (ที่โหลดเนื้อหาแบบไดนามิกได้)
        $('#plotListTable tbody').on('click', '.toggle-notes', function(e) {
            e.preventDefault(); // ป้องกันการกระโดดขึ้นด้านบนของหน้า
            var targetId = $(this).data('target-id');
            var $notesCell = $('#' + targetId);

            if ($notesCell.find('.truncated-content').hasClass('expanded')) {
                // Currently expanded, so collapse
                $notesCell.find('.truncated-content').removeClass('expanded');
                $notesCell.find('.read-more').removeClass('hide-toggle').addClass('show-toggle');
                $notesCell.find('.read-less').removeClass('show-toggle').addClass('hide-toggle');
            } else {
                // Currently collapsed, so expand
                $notesCell.find('.truncated-content').addClass('expanded');
                $notesCell.find('.read-more').removeClass('hide-toggle').addClass('show-toggle');
                $notesCell.find('.read-less').removeClass('show-toggle').addClass('hide-toggle');
            }
        });
    });
    </script>
</body>
</html>
<?php $conn->close(); ?>