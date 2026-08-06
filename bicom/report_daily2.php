<?php
session_start();
require_once 'db_connect.php';
date_default_timezone_set('Asia/Bangkok');
// ปรับปรุง: ไม่จำกัดหน่วย รับค่า 'ALL' ได้
$statn_code = $_GET['statn_code'] ?? 'ALL';
$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// ดึงรายชื่อสถานีสำหรับ Dropdown
$stations = $pdo->query("SELECT statn_code, statn_name FROM stations ORDER BY statn_code ASC")->fetchAll();

// 1. Query ข้อมูลแบบช่วงวันที่ และรองรับหน่วย
$sql = "SELECT c.*, s.STATN_NAME as ST_NAME 
        FROM conversion_logs c 
        LEFT JOIN stations s ON c.STATN_CODE = s.STATN_CODE
        WHERE c.WORK_DATE BETWEEN :sd AND :ed ";
$params = ['sd' => $start_date, 'ed' => $end_date];

if ($statn_code !== 'ALL') {
    $sql .= " AND c.STATN_CODE = :sc ";
    $params['sc'] = $statn_code;
}
$sql .= " ORDER BY c.WORK_DATE DESC, c.WEIGH_DOCC ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// คำนวณสรุป
$total_weight = 0; // สร้างตัวแปรไว้เก็บผลรวมน้ำหนักอ้อย เริ่มต้นที่ 0
$sum_weighted_ccs = 0; // ตัวแปรสำหรับเก็บผลรวมของ (น้ำหนัก x CCS)
$count = count($data); // นับจำนวนแถวข้อมูล (จำนวนรถ) ทั้งหมดที่ดึงมาได้

foreach ($data as $row) {
    $weight = (float)$row['WEIGH_CANE'];
   $ccs = (float)$row['SWEET_CANE'];
    
   $total_weight += $weight;
    // นำ (น้ำหนัก x CCS) ของแต่ละคันมาบวกสะสมกันตามสูตรในรูปภาพ
   $sum_weighted_ccs += ($weight * $ccs);
}

// คำนวณค่าเฉลี่ยโดยเอา ผลรวมถ่วงน้ำหนัก หารด้วย น้ำหนักรวมทั้งหมด
$avg_ccs = $total_weight > 0 ? ($sum_weighted_ccs / $total_weight) : 0;

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายงานทะเบียนรถ (Global)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f8f9fa; }
        .table-container { background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); padding: 20px; }
        .filter-bar { background: white; padding: 15px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        
        /* สไตล์สำหรับ Modal รายละเอียด */
        .section-header { 
            background: #f1f5f9; padding: 8px 15px; border-radius: 8px; 
            font-weight: bold; margin: 15px 0 10px 0; color: #1e293b;
            display: flex; align-items: center; gap: 8px;
        }
        .detail-item { 
            padding: 10px; background: #fff; border: 1px solid #e2e8f0; 
            border-radius: 8px; height: 100%; 
        }
        .detail-label { font-size: 0.75rem; color: #64748b; display: block; margin-bottom: 2px; }
        .detail-value { font-size: 0.95rem; font-weight: 600; color: #1e293b; }
        .modal-lg { max-width: 900px; }
    </style>
</head>
<body>
    <?php include 'nvb_report.php'; ?>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <h4 class="fw-bold"><i data-lucide="bar-chart-big" class="me-2"></i>รายงานสรุปข้อมูลอ้อย (Global)</h4>
            
            <form class="filter-bar d-flex gap-2 align-items-center shadow-sm border">
                <select name="statn_code" class="form-select border-0 bg-light rounded-pill px-3">
                    <option value="ALL">หน่วย</option>
                    <?php foreach($stations as $st): ?>
                        <option value="<?= $st['statn_code'] ?>" <?= $statn_code == $st['statn_code'] ? 'selected' : '' ?>><?= $st['statn_name'] ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="date" name="start_date" class="form-control border-0 bg-light rounded-pill" value="<?= $start_date ?>">
                <input type="date" name="end_date" class="form-control border-0 bg-light rounded-pill" value="<?= $end_date ?>">
                <button type="submit" class="btn btn-primary px-4 rounded-pill shadow-sm">ค้นหา</button>
            </form>
        </div>

        <div class="row g-3 mb-4 text-center">
            <div class="col-md-6">
                <div class="p-3 bg-white rounded-4 border shadow-sm">
                    <small class="text-muted">น้ำหนักรวม</small>
                    <h3 class="fw-bold text-success mb-0"><?= number_format($total_weight, 3) ?> <small>ตัน</small></h3>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 bg-white rounded-4 border shadow-sm">
                    <small class="text-muted">CCS เฉลี่ย</small>
                    <h3 class="fw-bold text-primary mb-0"><?= number_format($avg_ccs, 2) ?></h3>
                </div>
            </div>
        </div>

        <div class="table-container shadow-sm border">
            <table id="caneTable" class="table table-hover" style="width:100%">
                <thead class="table-dark">
                    <tr>
                        <th>สถานี</th>
                        <th>เลขสัญญา</th>
                        <th>ทะเบียนรถ</th>
                        <th>ใบรับ</th>
                        <th>น้ำหนัก</th>
                        <th>CCS</th>
                        <th class="text-center">รายละเอียด</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $row): ?>
                    <tr>
                        <td><span class="badge bg-info text-dark"><?= $row['ST_NAME'] ?></span></td>
                        <td><?= $row['FARMR_CODE'] ?></td>
                        <td><?= $row['TRUCK_CODE'] ?></td>
                        <td><?= $row['WEIGH_DOCC'] ?></td>
                        <td><?= number_format($row['WEIGH_CANE'], 3) ?></td>
                        <td class="text-primary fw-bold"><?= $row['SWEET_CANE'] ?></td>
                        <td class="text-center"> 
                            <button class="btn btn-sm btn-info text-white rounded-pill px-3 shadow-sm" 
                                    onclick='showDetails(<?= json_encode($row) ?>)'>
                                <i class="bi bi-eye"></i> รายละเอียด
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background: #1a2a6c;">
                    <h5 class="modal-title"><i class="bi bi-info-circle me-2"></i>รายละเอียดข้อมูลอ้อย</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="modalBody">
                    </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
    $(document).ready(function() {
        lucide.createIcons();

        $('#caneTable').DataTable({
            "pageLength": 10,
            "language": {
                "lengthMenu": "แสดง _MENU_ รายการ",
                "search": "ค้นหาด่วน:",
                "paginate": { "next": "ถัดไป", "previous": "ก่อนหน้า" },
                "info": "แสดง _START_ ถึง _END_ จากทั้งหมด _TOTAL_ รายการ",
                "zeroRecords": "ไม่พบข้อมูล"
            },
            "order": [[0, "asc"]]
        });
    });

    function showDetails(row) {
        let html = `
            <div class="container-fluid">
                <div class="section-header"><i data-lucide="user" style="width:18px"></i> 1. ข้อมูลรถบรรทุกและชาวไร่</div>
                <div class="row g-2">
                    <div class="col-md-4"><div class="detail-item"><span class="detail-label">เลขที่ใบรับอ้อย</span><span class="detail-value text-primary">${row.WEIGH_DOCC}</span></div></div>
                    <div class="col-md-4"><div class="detail-item"><span class="detail-label">เลขสัญญา</span><span class="detail-value">${row.FARMR_CODE}</span></div></div>
                    <div class="col-md-4"><div class="detail-item"><span class="detail-label">ชื่อชาวไร่</span><span class="detail-value">${row.FARMR_NAME}</span></div></div>
                    <div class="col-md-4"><div class="detail-item"><span class="detail-label">ทะเบียนรถ</span><span class="detail-value">${row.TRUCK_CODE}</span></div></div>
                    <div class="col-md-4"><div class="detail-item"><span class="detail-label">ประเภทอ้อย</span><span class="detail-value">${row.CANE_TYPE}</span></div></div>
                    <div class="col-md-4"><div class="detail-item"><span class="detail-label">เบอร์รถตัด</span><span class="detail-value text-danger">${row.CANE_TCCUT || '-'}</span></div></div>
                </div>

                <div class="section-header"><i data-lucide="microscope" style="width:18px"></i> 2. ข้อมูลน้ำหนักและคุณภาพอ้อย</div>
                <div class="row g-2">
                    <div class="col-md-3"><div class="detail-item bg-light text-center"><span class="detail-label">CCS</span><span class="detail-value h4 text-success">${row.SWEET_CANE}</span></div></div>
                    <div class="col-md-3"><div class="detail-item bg-light text-center"><span class="detail-label">น้ำหนัก(ตัน)</span><span class="detail-value h4 text-primary">${parseFloat(row.WEIGH_CANE).toFixed(3)}</span></div></div>
                    <div class="col-md-2"><div class="detail-item text-center"><span class="detail-label">PURITY</span><span class="detail-value">${row.PURI_CANE}</span></div></div>
                    <div class="col-md-2"><div class="detail-item text-center"><span class="detail-label">BRIX</span><span class="detail-value">${row.BRIX_CANE}</span></div></div>
                    <div class="col-md-2"><div class="detail-item text-center"><span class="detail-label">POL</span><span class="detail-value">${row.POL_CANE}</span></div></div>
                </div>

                <div class="section-header"><i data-lucide="clock" style="width:18px"></i> 3. ข้อมูลเวลา</div>
                <div class="row g-2 text-center">
                    <div class="col-md-3"><div class="detail-item"><span class="detail-label">วันที่ชั่ง</span><span class="detail-value small">${row.WORK_DATE}</span></div></div>
                    <div class="col-md-3"><div class="detail-item"><span class="detail-label">เวลาชั่งเข้า</span><span class="detail-value text-success">${row.WEIGH_TMIN}</span></div></div>
                    <div class="col-md-3"><div class="detail-item"><span class="detail-label">เวลาชั่งออก</span><span class="detail-value text-danger">${row.WEIGH_TMOU}</span></div></div>
                    <div class="col-md-3"><div class="detail-item"><span class="detail-label">รวมเวลาในโรงงาน</span><span class="detail-value">${row.TIME_HTOTL}:${row.TIME_MTOTL} ชม.</span></div></div>
                </div>

                <div class="section-header"><i data-lucide="calculator" style="width:18px"></i> 4. ค่าบริการและหักเงิน</div>
                <div class="row g-2">
                    <div class="col-md-3 col-6"><div class="detail-item text-center"><span class="detail-label">ค่าบรรทุก</span><span class="detail-value">${row.TRUCK_RATE}</span></div></div>
                    <div class="col-md-3 col-6"><div class="detail-item text-center"><span class="detail-label">ค่าขึ้น</span><span class="detail-value">${row.TRUCK_UP}</span></div></div>
                    <div class="col-md-3 col-6"><div class="detail-item text-center"><span class="detail-label">ค่าตัด</span><span class="detail-value">${row.TRUCK_MCUT}</span></div></div>
                    <div class="col-md-3 col-6"><div class="detail-item text-center border-danger"><span class="detail-label text-danger">หักไฟไหม้</span><span class="detail-value text-danger">${row.FLAG_CUT1}</span></div></div>
                </div>
            </div>
        `;
        document.getElementById('modalBody').innerHTML = html;
        lucide.createIcons();
        var myModal = new bootstrap.Modal(document.getElementById('detailModal'));
        myModal.show();
    }
    </script>
</body>
</html>