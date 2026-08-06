<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['statn_code'])) { exit("โปรดเข้าสู่ระบบ"); }
date_default_timezone_set('Asia/Bangkok');
$statn_code = $_SESSION['statn_code'];
// ปรับให้รับค่าเป็นช่วงวันที่ (เริ่ม - สิ้นสุด)
$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// 1. Query ข้อมูลแบบช่วงวันที่
$sql = "SELECT * FROM conversion_logs 
        WHERE STATN_CODE = ? AND WORK_DATE BETWEEN ? AND ?
        ORDER BY WORK_DATE DESC, WEIGH_DOCC ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$statn_code, $start_date, $end_date]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

//$total_weight = 0; // สร้างตัวแปรไว้เก็บผลรวมน้ำหนักอ้อย เริ่มต้นที่ 0
//$total_ccs = 0;    // สร้างตัวแปรไว้เก็บผลรวมค่าความหวาน (CCS) เริ่มต้นที่ 0
//$count = count($data); // นับจำนวนแถวข้อมูล (จำนวนรถ) ทั้งหมดที่ดึงมาได้

//foreach ($data as $row) {
   // $total_weight += (float)$row['WEIGH_CANE']; // เอาน้ำหนักรถคันนี้ บวกเพิ่มเข้าไปในผลรวมน้ำหนัก
    //$total_ccs += (float)$row['SWEET_CANE'];    // เอาค่า CCS รถคันนี้ บวกเพิ่มเข้าไปในผลรวมความหวาน
//}
//$avg_ccs = $count > 0 ? ($total_ccs / $count) : 0;
//-----------------------------------------------------------//
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
//-------------------------------------------------------//

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายงานรถเข้า - <?= $start_date ?> ถึง <?= $end_date ?></title>
    <link rel="icon" href="bg/v2.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f8f9fa; }
        .table-container { background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow: hidden; padding: 20px; }
        .table thead { background: #3c4f9cff; color: white; }
        .btn-view { cursor: pointer; color: #445596ff; text-decoration: underline; }
        .total-bar { background: #e9ecef; border-radius: 8px; }
        
        /* สไตล์ Modal ของคุณ */
        .detail-item { background: #fdfdfd; border: 1px solid #eee; border-radius: 8px; padding: 10px 15px; height: 100%; }
        .detail-label { color: #888; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; display: block; }
        .detail-value { color: #333; font-weight: 700; font-size: 1rem; word-break: break-word; }
        .section-header { background: linear-gradient(90deg, #3b52adff); color: white; padding: 8px 15px; border-radius: 5px; margin-top: 20px; margin-bottom: 15px; font-size: 0.95rem; display: flex; align-items: center; }
        .section-header i { width: 18px; margin-right: 10px; }
        .val-number { font-family: 'Courier New', Courier, monospace; color: #d63384; }

        /* ปรับแต่ง DataTables ให้เข้ากับธีม */
        .dataTables_length select { border-radius: 20px; padding: 5px 10px; }
        .dataTables_filter input { border-radius: 20px; padding: 5px 15px; border: 1px solid #ddd; }
        .pagination .page-link { border-radius: 50%; margin: 0 2px; color: #2740a3ff; }
        .pagination .active .page-link { background-color: #ffffffff; border-color: #142774ff; }
        /* --- สไตล์สำหรับหน้าจอมือถือ (Responsive) --- */
    @media (max-width: 768px) {
        .container { padding-left: 10px; padding-right: 10px; }
        
        /* ปรับส่วนหัวให้เรียงแนวตั้ง */
        .d-flex.justify-content-between.align-items-center.mb-4 {
            flex-direction: column;
            align-items: flex-start !important;
        }

        /* ปรับฟอร์มค้นหาให้เต็มจอ */
        form.d-flex {
            width: 100%;
            flex-direction: column;
            gap: 10px !important;
        }
        form.d-flex input[type="date"] {
            width: 100%;
        }
        form.d-flex button {
            width: 100%;
            border-radius: 10px !important;
        }

        /* ทำให้ตารางเลื่อนซ้าย-ขวาได้ และไม่บีบตัวหนังสือ */
        .table-container {
            padding: 10px;
            overflow-x: auto;
        }
        #caneTable {
            min-width: 700px; /* บังคับความกว้างขั้นต่ำของตาราง */
        }

        /* ปรับแต่ง Modal ให้เหมาะกับนิ้วมือ */
        .modal-dialog {
            margin: 0.5rem;
        }
        .detail-item {
            margin-bottom: 5px;
        }
        .section-header {
            font-size: 0.85rem;
            padding: 10px;
        }

        /* ปรับแต่งช่อง Search ของ DataTables */
        .dataTables_filter {
            text-align: left !important;
            margin-top: 10px;
        }
        .dataTables_filter input {
            width: 100% !important;
            margin-left: 0 !important;
        }
        
        /* ปรับแถบน้ำหนักรวมให้ตัวอักษรพอดีจอ */
        .total-bar .h4 {
            font-size: 1.2rem;
        }
    }
    </style>
</head>
<body>
    <?php include 'nvb.php'; ?>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <h4 class="fw-bold"><i data-lucide="truck" class="me-2"></i>รายงานทะเบียนรถ หน่วย: <?= htmlspecialchars($_SESSION['statn_name']) ?></h4>
            <form class="d-flex gap-2 bg-white p-2 rounded shadow-sm align-items-center">
                <small class="text-muted">เริ่ม:</small>
                <input type="date" name="start_date" class="form-control border-0 bg-light" value="<?= $start_date ?>">
                <small class="text-muted">ถึง:</small>
                <input type="date" name="end_date" class="form-control border-0 bg-light" value="<?= $end_date ?>">
                <button type="submit" class="btn btn-primary px-4 rounded-pill">ค้นหา</button>
            </form>
        </div>

        <?php if ($count > 0): ?>
        <div class="row g-3 mb-4 text-center">
            <div class="col-md-6">
                <div class="p-3 total-bar border">
                    <small class="text-muted d-block">น้ำหนักรวมทั้งหมด (เฉพาะวันที่เลือก)</small>
                    <span class="h4 fw-bold text-success"><?= number_format($total_weight, 3) ?> ตัน</span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 total-bar border">
                    <small class="text-muted d-block">ความหวานเฉลี่ย (CCS)</small>
                    <span class="h4 fw-bold text-primary"><?= number_format($avg_ccs, 2) ?></span>
                </div>
            </div>
        </div>

        <div class="table-container shadow-sm">
            <table id="caneTable" class="table table-hover align-middle mb-0" style="width:100%">
                <thead>
                    <tr class="">
                        <th>ลำดับ</th>
                        <th>เลขสัญญา</th>
                        <th>ทะเบียนรถ</th>
                        <th>เลขที่ใบรับ</th>
                        <th>(ตัน)</th>
                        <th>(CCS)</th>
                        <th>ดูข้อมูล</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $idx => $row): ?>
                    <tr class="">
                        <td><?= $idx + 1 ?></td>
                        <td class="fw-bold"><?= $row['FARMR_CODE'] ?></td>
                        <td><span class="badge bg-light text-dark border"><?= $row['TRUCK_CODE'] ?></span></td>
                        <td class="fw-bold"><?= $row['WEIGH_DOCC'] ?></td>
                        <td><?= number_format($row['WEIGH_CANE'], 3) ?></td>
                        <td class="text-primary fw-bold"><?= number_format($row['SWEET_CANE'], 2) ?></td>
                        <td>
                            <button class="btn btn-sm btn-info text-white rounded-pill px-3 shadow-sm" 
                                    onclick='showDetails(<?= json_encode($row) ?>)'>
                                <i data-lucide="eye" style="width:14px;"></i> รายละเอียด
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="alert alert-light text-center shadow-sm py-5 border">
                <i data-lucide="search-x" class="mb-2 text-muted" style="width:48px; height:48px;"></i>
                <p class="mb-0">ไม่พบข้อมูลในช่วงวันที่เลือก โปรดตรวจสอบวันที่อีกครั้ง</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background: #1a2a6c;">
                    <h5 class="modal-title"><i data-lucide="info" class="me-2"></i>รายละเอียดข้อมูลอ้อย</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="modalBody"></div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
    $(document).ready(function() {
        // เริ่มทำงาน Lucide Icons
        lucide.createIcons();

        // ตั้งค่า DataTables
        $('#caneTable').DataTable({
            "pageLength": 10,
            "lengthMenu": [10, 20, 50, 100],
            "language": {
                "lengthMenu": "แสดง _MENU_ รายการต่อหน้า",
                "zeroRecords": "ไม่พบข้อมูลที่ค้นหา",
                "info": "หน้า _PAGE_ จาก _PAGES_",
                "infoEmpty": "ไม่มีข้อมูล",
                "infoFiltered": "(กรองจากทั้งหมด _MAX_ รายการ)",
                "search": "ค้นหาด่วน:",
                "paginate": {
                    "first": "หน้าแรก",
                    "last": "หน้าสุดท้าย",
                    "next": "ถัดไป",
                    "previous": "ก่อนหน้า"
                }
            },
            "order": [[0, "asc"]],
            "dom": '<"d-flex justify-content-between align-items-center mb-3"lf>rt<"d-flex justify-content-between align-items-center mt-3"ip>'
        });
    });

    // ฟังก์ชัน ShowDetails (ใช้แบบของคุณที่คุณจัดไว้)
    function showDetails(row) {
        let html = `
            <div class="container-fluid">
                <div class="section-header"><i data-lucide="user"></i> 1.ข้อมูลรถบรรทุกและชาวไร่</div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="detail-item border-start border-primary border-4">
                            <span class="detail-label">เลขที่ใบรับอ้อย</span>
                            <span class="detail-value text-primary">${row.WEIGH_DOCC}</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="detail-item">
                            <span class="detail-label">เลขสัญญา</span>
                            <span class="detail-value">${row.FARMR_CODE}</span>
                        </div>
                    </div>
                     <div class="col-md-4">
                        <div class="detail-item">
                            <span class="detail-label">ชื่อชาวไร่</span>
                            <span class="detail-value">${row.FARMR_NAME}</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="detail-item">
                            <span class="detail-label">ทะเบียนรถ</span>
                            <span class="detail-value">${row.TRUCK_CODE}</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="detail-item">
                            <span class="detail-label">ประเภทอ้อย</span>
                            <span class="detail-value"> ${row.CANE_TYPE}</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="detail-item">
                            <span class="detail-label">เบอร์รถตัด</span>
                            <span class="detail-value text-danger">${row.CANE_TCCUT}</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="detail-item">
                            <span class="detail-label">เบอร์รถคีบ</span>
                            <span class="detail-value text-danger ">${row.CANE_TCHOL}</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="detail-item">
                            <span class="detail-label">อก.</span>
                            <span class="detail-value ">${row.FARMR_TRCK}</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="detail-item">
                            <span class="detail-label">เจ้าของรถ</span>
                            <span class="detail-value ">${row.FARMR_TNAM}</span>
                        </div>
                    </div>
                </div>

                <div class="section-header"><i data-lucide="microscope"></i> 2. ข้อมูลน้ำหนักและคุณภาพอ้อย</div>
                <div class="row g-3">
                <div class="col-md-3">
                        <div class="detail-item bg-light text-center border-bottom border-warning">
                            <span class="detail-label">(CCS)</span>
                            <span class="detail-value h4 text-success">${row.SWEET_CANE}</span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="detail-item text-center bg-light border-bottom border-warning">
                            <span class="detail-label">น้ำหนัก(ตัน)</span>
                            <span class="detail-value h4 text-success">${parseFloat(row.WEIGH_CANE).toFixed(3)}</span>
                        </div>
                    </div>
                    
                    <div class="col-md-2">
                        <div class="detail-item text-center  bg-light">
                            <span class="detail-label">PURITY</span>
                            <span class="detail-value">${row.PURI_CANE}</span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="detail-item text-center  bg-light">
                            <span class="detail-label">BRIX</span>
                            <span class="detail-value">${row.BRIX_CANE}</span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="detail-item text-center  bg-light">
                            <span class="detail-label">POL</span>
                            <span class="detail-value">${row.POL_CANE}</span>
                        </div>
                    </div>
                </div>

                <div class="section-header"><i data-lucide="clock"></i> 3. ข้อมูลเวลารวมเวลาในโรงงาน</div>
                <div class="row g-3 text-center">
                    <div class="col-md-3">
                        <div class="detail-item">
                            <span class="detail-label">เวลาตัดอ้อย</span>
                            <span class="detail-value small ">วันที่ ${row.DATE_CUT} <br> เวลา ${row.TIME_CUT}</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="detail-item">
                            <span class="detail-label">เวลาเข้าคิว</span>
                            <span class="detail-value small text-success">วันที่ ${row.DATE_QUE} <br> เวลา ${row.TIME_QUE}</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="detail-item">
                            <span class="detail-label">เวลาชั่ง (เข้า/ออก)</span>
                            <span class="detail-value small text-success ">เข้า ${row.WEIGH_TMIN} <br> 
                            <span class="detail-value small text-danger "> ออก ${row.WEIGH_TMOU}
                            </span>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="detail-item text-white">
                            <span class="detail-label text-black-50">รวมเวลาในโรงงาน</span>
                            <span class="detail-value text-dark">เวลา ${row.TIME_HTOTL}:${row.TIME_MTOTL} ชม.</span>
                        </div>
                    </div>
                </div>

                <div class="section-header"><i data-lucide="calculator"></i> 4. สัญญาและค่าบริการ</div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <div class="p-2 border rounded">
                            <span class="detail-label text-primary">สัญญาตัดอ้อย</span>
                            <span class="detail-value small d-block">ID ${row.FARMR_CUT} | ชื่อ ${row.FARMR_CNAM}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-2 border rounded">
                            <span class="detail-label text-primary">สัญญาขึ้นอ้อย</span>
                            <span class="detail-value small d-block">ID ${row.FARMR_UP} | ชื่อ ${row.FARMR_UNAM}</span>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="p-2 text-center border-end">
                            <span class="detail-label">ค่าบรรทุก</span>
                            <span class="detail-value text-danger">${row.TRUCK_RATE}</span>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="p-2 text-center border-end">
                            <span class="detail-label">ค่าขึ้น</span>
                            <span class="detail-value text-danger">${row.TRUCK_UP}</span>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="p-2 text-center border-end">
                            <span class="detail-label">ค่าตัด</span>
                            <span class="detail-value text-danger">${row.TRUCK_MCUT}</span>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="p-2 text-center text-danger">
                            <span class="detail-label">หักไฟไหม้</span>
                            <span class="detail-value text-danger">${row.FLAG_CUT1}</span>
                        </div>
                    </div>
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