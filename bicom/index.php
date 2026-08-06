<?php
session_start();
require_once 'db_connect.php';

// ตรวจสอบการ Login
if (!isset($_SESSION['statn_code'])) { 
    header("Location: login.php"); 
    exit; 
}

$statn_code = $_SESSION['statn_code'];

// --- เพิ่มส่วนการระบุชื่อหน่วย ---
$statn_names = [
    "01" => "พรหมพิราม",
    "14" => "พรหมพิราม", 
    "0901" => "พรหมพิราม",
    "02" => "พิษณุโลก",

    "217" => "เมือง",
    "213" => "วัดโบสถ์",
    "214" => "พรหมพิราม",
    "203" => "บ่อทอง",
    "202" => "น้ำอ่าง",
    "204" => "ชาติตระการ",
    "205" => "หนองตม",
    "111" => "บางขลัง",
    "110" => "ทุ่งเสลี่ยม",
    "115" => "ศรีสัชนาลัย",
    "116" => "สวรรคโลก",
    "102" => "ชัยคีรี",
    "112" => "ศรีนครเหนือ",
    "101" => "ศรีนครใต้",
    "206" => "พิชัย",
    "109" => "ศรีสำโรง",
    "113" => "ตลิ่งชัน",
    "106" => "ท่าชัย",
    "114" => "เขาหลวง",
    "108" => "คีรีมาศ",
    "121" => "ท่าชัยใต้",
    "107" => "ท่าชัยเหนือ",
    "219" => "น้ำปาด",
    "220" => "แพร่",
    "117" => "ตาก"
];
$statn_name = $statn_names[$statn_code] ?? "หน่วยรหัส ($statn_code)";
// ----------------------------

// 1. รับค่าวันที่เลือก
$start_date = $_GET['start_date'] ?? '';
$end_date   = $_GET['end_date'] ?? '';
$limit      = isset($_GET['limit']) ? (int)$_GET['limit'] : 50; 
$page       = isset($_GET['page']) ? (int)$_GET['page'] : 1;    
$offset     = ($page - 1) * $limit;

// 2. สร้าง SQL เงื่อนไข
$where = "WHERE STATN_CODE = ?";
$params = [$statn_code];

if ($start_date && $end_date) {
    $where .= " AND WORK_DATE BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
}

// 3. นับจำนวนเพื่อทำแบ่งหน้า
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM conversion_logs $where");
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// 4. ดึงข้อมูล (เรียงตามวันที่และเวลาล่าสุด)
$sql = "SELECT * FROM conversion_logs $where ORDER BY WORK_DATE DESC, TIME_QUE DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cols = [
    'STATN_CODE', 'WORK_DATE', 'WEIGH_DOCC', 'FARMR_CODE', 'FARMR_NAME', 
    'WEIGH_QUE', 'SWEET_CANE', 'PURI_CANE', 'BRIX_CANE', 'POL_CANE', 
    'WEIGH_CANE', 'CANE_TYPE', 'TRUCK_CODE', 'CANE_TCCUT', 'CANE_TCHOL', 
    'TRUCK_RATE', 'TRUCK_UP', 'TRUCK_MCUT', 'FARMR_TRCK', 'FARMR_TNAM', 
    'FARMR_UP', 'FARMR_UNAM', 'FARMR_CUT', 'FARMR_CNAM', 'FLAG_CUT1', 
    'TRUCK_DUCK', 'DATE_CUT', 'TIME_CUT', 'LAND_NUMB', 'LAND_ID', 
    'DATE_QUE', 'TIME_QUE', 'WEIGH_DTIN', 'WEIGH_DTOU', 'WEIGH_TMIN', 
    'WEIGH_TMOU', 'TIME_HFAC', 'TIME_MFAC', 'TIME_HTOTL', 'TIME_MTOTL', 'FLAG_ID'
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>BAI COM - หน่วย <?= $statn_name ?></title>
    <link rel="icon" href="bg/v2.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f1f5f9; }
        .card-custom { border-radius: 12px; border: none; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .table-responsive { max-height: 60vh; border-radius: 8px; }
        .table thead th { position: sticky; top: 0; background: #1a2a6c; color: white; font-size: 11px; white-space: nowrap; z-index: 10; }
        .sticky-col { position: sticky; left: 0; background: #f8fafc !important; z-index: 5; border-right: 2px solid #cbd5e1 !important; }
        .highlight-date { background: #fff9db !important; font-weight: 600; color: #d97706; }
        .statn-badge { background: #e0e7ff; color: #4338ca; padding: 4px 12px; border-radius: 20px; font-weight: 600; font-size: 14px; }
    </style>
</head>
<body>

<?php include 'nvb.php'; ?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold">ระบบจัดการข้อมูลการรับอ้อย</h4>
        <div class="statn-badge">
            <i data-lucide="map-pin" size="16" class="me-1"></i> หน่วย: <?= $statn_name ?>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card card-custom p-3 h-100">
                <form action="process.php" method="POST" enctype="multipart/form-data">
                    <label class="form-label fw-bold small text-primary">นำเข้าไฟล์ .dbf</label>
                    <div class="input-group input-group-sm">
                        <input type="file" name="dbf_file" class="form-control" accept=".dbf" required>
                        <button class="btn btn-primary fw-bold" type="submit">บันทึก</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card card-custom p-3 h-100">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="small text-muted">ตั้งแต่วันที่</label>
                        <input type="date" name="start_date" class="form-control form-control-sm" value="<?= $start_date ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="small text-muted">ถึงวันที่</label>
                        <input type="date" name="end_date" class="form-control form-control-sm" value="<?= $end_date ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="small text-muted">แถว</label>
                        <select name="limit" class="form-select form-select-sm">
                            <?php foreach([50, 100, 250, 500] as $l): ?>
                                <option value="<?= $l ?>" <?= $limit == $l ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex gap-1">
                        <button type="submit" class="btn btn-dark btn-sm flex-fill fw-bold">ค้นหา</button>
                        
                        <?php if($start_date && $end_date): ?>
                            <a href="preview_report.php?<?= http_build_query($_GET) ?>" target="_blank" class="btn btn-primary btn-sm flex-fill fw-bold">
                                <i data-lucide="file-spreadsheet" class="me-1" style="width:14px;"></i> กดดูข้อมมูล Excel
                            </a>
                        <?php else: ?>
                            <button type="button" onclick="alert('กรุณาเลือกช่วงวันที่ก่อนดูข้อมมูลครับ')" class="btn btn-outline-secondary btn-sm flex-fill fw-bold">
                                <i data-lucide="eye" class="me-1" style="width:14px;"></i> ดูข้อมมูล
                            </button>
                        <?php endif; ?>
                        
                        <a href="index.php" class="btn btn-light btn-sm border flex-fill">ล้าง</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card card-custom overflow-hidden">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white">
            <span class="small fw-bold text-muted">
                พบข้อมูลหน่วย <?= $statn_name ?>: <span class="text-primary"><?= number_format($total_records) ?></span> รายการ 
            </span>
            
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page-1])) ?>"><i data-lucide="chevron-left" size="14"></i></a>
                    </li>
                    <li class="page-item active"><a class="page-link"><?= $page ?></a></li>
                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page+1])) ?>"><i data-lucide="chevron-right" size="14"></i></a>
                    </li>
                </ul>
            </nav>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0" style="min-width: 5000px;">
                <thead>
                    <tr>
                        <th class="sticky-col">ลำดับ</th>
                        <?php foreach($cols as $h): ?><th><?= $h ?></th><?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if($reports): $n = $offset + 1; foreach($reports as $row): ?>
                        <tr>
                            <td class="sticky-col text-center"><?= $n++ ?></td>
                            <?php foreach($cols as $c): 
                                $val = $row[$c] ?? '-';
                                $class = "";
                                if($c == 'WORK_DATE') {
                                    $class = "highlight-date";
                                    $val = ($val && $val != '0000-00-00') ? date('d/m/Y', strtotime($val)) : '-';
                                }
                                echo "<td class='$class'>" . htmlspecialchars($val) . "</td>";
                            endforeach; ?>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="42" class="text-center py-5">กรุณาเลือกวันที่หรืออัปโหลดข้อมูลสำหรับหน่วย <?= $statn_name ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>lucide.createIcons();</script>
</body>
</html>