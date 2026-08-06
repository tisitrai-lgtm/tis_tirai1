<?php
if (file_exists('db_connect.php')) {
    include 'db_connect.php';
} else {
    die("Error: 'db_connect.php' file not found.");
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Datarai</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        body { background: #f8f9fa; }
        .dashboard-header h1 { font-weight: bold; color: #0d6efd; }
        .summary-cards { display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 2rem; }
        .summary-cards .card {
            flex: 1;
            min-width: 200px;
            text-align: center;
            padding: 1rem;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .summary-cards h3 { font-size: 1.1rem; color: #6c757d; margin-bottom: .5rem; }
        .summary-cards p { font-size: 1.5rem; font-weight: bold; color: #0d6efd; margin: 0; }
        table.dataTable thead th { background: #0d6efd; color: white; }
        .filter-controls select { max-width: 250px; }
    </style>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="container my-4">
    <header class="dashboard-header mb-4">
        <h1>ฐานข้อมูลเลขสัญญา</h1>
    </header>

    <?php
    $stmt_summary = $conn->prepare("
        SELECT 
            COUNT(DISTINCT contract_number) AS total_contracts,
            COUNT(DISTINCT plot_id) AS total_plots,
            COUNT(DISTINCT promotion_unit) AS total_units,
            COUNT(DISTINCT promoter_area) AS total_areas
        FROM sugar_contracts
    ");
    $stmt_summary->execute();
    $summary = $stmt_summary->fetch(PDO::FETCH_ASSOC);
    ?>

    <div class="summary-cards">
        <div class="card">
            <h3>จำนวนสัญญา</h3>
            <p><?= number_format($summary['total_contracts'] ?? 0) ?></p>
        </div>
        <div class="card">
            <h3>จำนวนแปลงทั้งหมด</h3>
            <p><?= number_format($summary['total_plots'] ?? 0) ?></p>
        </div>
        <div class="card">
            <h3>จำนวนหน่วยส่งเสริม</h3>
            <p><?= number_format($summary['total_units'] ?? 0) ?></p>
        </div>
        <div class="card">
            <h3>จำนวนพื้นที่ส่งเสริม</h3>
            <p><?= number_format($summary['total_areas'] ?? 0) ?></p>
        </div>
    </div>

    <div class="data-section mb-3">
        <h2 class="mb-3">ข้อมูลสัญญา</h2>
        <div class="filter-controls d-flex flex-wrap gap-2 mb-3">
            <select id="area-filter" class="form-select">
                <option value="">หน่วยส่งเสริม</option>
                <?php
                $stmt_units = $conn->prepare("SELECT DISTINCT promotion_unit FROM sugar_contracts ORDER BY promotion_unit");
                $stmt_units->execute();
                foreach ($stmt_units as $unit) {
                    echo '<option value="'.htmlspecialchars($unit['promotion_unit']).'">'.htmlspecialchars($unit['promotion_unit']).'</option>';
                }
                ?>
            </select>
            <select id="sugar-type-filter" class="form-select">
                <option value="">ชนิดอ้อย</option>
                <?php
                $stmt_types = $conn->prepare("SELECT DISTINCT sugar_type FROM sugar_contracts ORDER BY sugar_type");
                $stmt_types->execute();
                foreach ($stmt_types as $type) {
                    echo '<option value="'.htmlspecialchars($type['sugar_type']).'">'.htmlspecialchars($type['sugar_type']).'</option>';
                }
                ?>
            </select>
        </div>

        <table id="contracts-table" class="table table-bordered table-striped w-100">
            <thead>
                <tr>
                    <th>ไอดีแปลง</th>
                    <th>เลขสัญญา</th>
                    <th>ชื่อโควต้า</th>
                    <th>ชนิดอ้อย</th>
                    <th>หน่วยส่งเสริม</th>
                    <th>เขตนักส่งเสริม</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt_data = $conn->prepare("SELECT plot_id, contract_number, quota_name, sugar_type, promotion_unit, promoter_area FROM sugar_contracts");
                $stmt_data->execute();
                foreach ($stmt_data as $row) {
                    echo "<tr>
                        <td>".htmlspecialchars($row['plot_id'])."</td>
                        <td>".htmlspecialchars($row['contract_number'])."</td>
                        <td>".htmlspecialchars($row['quota_name'])."</td>
                        <td>".htmlspecialchars($row['sugar_type'])."</td>
                        <td>".htmlspecialchars($row['promotion_unit'])."</td>
                        <td>".htmlspecialchars($row['promoter_area'])."</td>
                    </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ✅ Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function () {
    let contractsTable = $('#contracts-table').DataTable({
        paging: true,
        searching: true,
        pageLength: 25,
        lengthMenu: [25, 50, 75, 100],
        language: {
            lengthMenu: "แสดง _MENU_ รายการ",
            zeroRecords: "ไม่พบข้อมูล",
            info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
            infoEmpty: "ไม่พบรายการ",
            infoFiltered: "(กรองจากทั้งหมด _MAX_ รายการ)",
            search: "ค้นหา:",
            paginate: {
                first: "หน้าแรก",
                last: "หน้าสุดท้าย",
                next: "ถัดไป",
                previous: "ก่อนหน้า"
            }
        }
    });

    $('#area-filter').on('change', function () {
        contractsTable.column(4).search(this.value).draw();
    });

    $('#sugar-type-filter').on('change', function () {
        contractsTable.column(3).search(this.value).draw();
    });
});
</script>
</body>
</html>
