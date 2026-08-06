<?php
session_start();
require("dbconnect.php");

if (!isset($_SESSION['emp_level']) || $_SESSION['emp_level'] != "a") {
    header("location:login.php");
    exit();
}

// ดึงสรุป: 1 รหัสเอกสาร ต่อ 1 แถว พร้อมดึงชื่อพนักงานและหน่วยงานมาโชว์ด้วย
$sql = "SELECT 
            l.print_round, 
            l.emp_id, 
            e.emp_name,
            e.emp_unit,
            l.contract_number,
            MIN(l.created_at) as first_time, 
            MAX(l.created_at) as last_time,
            COUNT(l.id) as total_count
        FROM pdf_export_logs l
        LEFT JOIN employee e ON l.emp_id = e.emp_id
        GROUP BY l.print_round
        ORDER BY last_time DESC";
$result = mysqli_query($con, $sql);
?>

<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ระบบตรวจสอบการออกเอกสาร - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #1e3a8a;
            --accent-color: #fbbf24;
        }
        body { 
            background-color: #f1f5f9; 
            font-family: 'Sarabun', sans-serif; 
        }
        .main-content {
            padding-top: 2rem;
            padding-bottom: 3rem;
        }
        .card-log { 
            border-radius: 20px; 
            border: none; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            background: #ffffff;
        }
        .header-section {
            border-bottom: 2px solid #f8fafc;
            margin-bottom: 1.5rem;
        }
        .header-title { 
            color: var(--primary-color); 
            font-weight: 700; 
            border-left: 6px solid var(--accent-color);
            padding-left: 15px;
        }
        .table thead { 
            background: var(--primary-color);
            color: white;
        }
        .table thead th {
            font-weight: 500;
            border: none;
        }
        .badge-round {
            background-color: #fff1f2;
            color: #e11d48;
            font-family: 'Courier New', monospace;
            padding: 0.5em 0.8em;
            border-radius: 6px;
            border: 1px solid #fecdd3;
        }
        .btn-timeline {
            background-color: #0ea5e9;
            border: none;
            transition: all 0.2s;
        }
        .btn-timeline:hover {
            background-color: #0284c7;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
        }
    </style>
</head>
<body>
    
    <?php include("nav_a.php"); ?>

    <div class="container main-content">
        <div class="card card-log p-4 p-md-5">
            <div class="header-section d-flex justify-content-between align-items-center pb-3">
                <h3 class="header-title m-0">
                    <i class='bx bxs-file-pdf me-2'></i>ประวัติการสร้างเอกสาร PDF
                </h3>
                <span class="badge bg-light text-dark border p-2">
                    <i class='bx bx-info-circle me-1'></i> ข้อมูลอัปเดตแบบ Real-time
                </span>
            </div>

            <div class="table-responsive">
                <table id="logTable" class="table table-hover align-middle w-100">
                    <thead>
                        <tr class="text-center">
                            <th>รหัสเอกสาร</th>
                            <th>เลขสัญญา</th>
                            <th>ผู้จัดทำ / หน่วย</th>
                            <th>สร้างครั้งแรก</th>
                            <th>แก้ไขล่าสุด</th>
                            <th>จำนวน</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($result)) { ?>
                        <tr class="text-center">
                            <td>
                                <span class="badge-round small fw-bold">
                                    <?php echo $row['print_round']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-primary px-3 py-2 fs-6">
                                    <i class='bx bx-hash me-1'></i><?php echo str_pad($row['contract_number'], 6, "0", STR_PAD_LEFT); ?>
                                </span>
                            </td>
                            <td class="text-start">
                                <div class="fw-bold"><?php echo $row['emp_name']; ?></div>
                                <div class="small text-muted text-uppercase"><?php echo $row['emp_id']; ?> | <?php echo $row['emp_unit']; ?></div>
                            </td>
                            <td class="small">
                                <i class='bx bx-calendar-plus text-muted me-1'></i><?php echo date('d/m/Y H:i', strtotime($row['first_time'])); ?>
                            </td>
                            <td>
                                <div class="text-success fw-bold small">
                                    <i class='bx bx-edit-alt me-1'></i><?php echo date('d/m/Y H:i', strtotime($row['last_time'])); ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge rounded-pill bg-warning text-dark px-3">
                                    <?php echo $row['total_count']; ?> ครั้ง
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-timeline btn-sm text-white px-3 view-detail" data-round="<?php echo $row['print_round']; ?>">
                                    <i class='bx bx-history me-1'></i> Timeline
                                </button>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalTimeline" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
                <div class="modal-header bg-dark text-white" style="border-radius: 15px 15px 0 0;">
                    <h5 class="modal-title font-weight-bold">
                        <i class='bx bx-time-five me-2'></i>ประวัติการเข้าถึงรหัสเอกสาร
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="timelineContent">
                    </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            // เรียกใช้งาน DataTables ภาษาไทย
            $('#logTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/th.json"
                },
                "order": [[4, "desc"]], // เรียงตามแก้ไขล่าสุด
                "pageLength": 10
            });

            // ดึงข้อมูล Timeline รายละเอียด
            $(document).on('click', '.view-detail', function() {
                let round = $(this).data('round');
                $('#modalTimeline').modal('show');
                $('#timelineContent').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 small text-muted">กำลังโหลดข้อมูล...</div></div>');
                
                $.post('admin_get_log_details.php', { print_round: round }, function(data) {
                    $('#timelineContent').hide().html(data).fadeIn();
                });
            });
        });
    </script>
</body>
</html>