<?php
session_start();
require("dbconnect.php");

// 1. ตรวจสอบสถานะการเข้าสู่ระบบ
if (!isset($_SESSION['emp_level']) || $_SESSION['emp_level'] != "u") {
    echo "<center>หน้าสำหรับผู้ใช้งานระบบ <a href=login.php>กรุณาเข้าสู่ระบบก่อน</a></center>";
    exit();
}

if (!isset($_SESSION["emp_id"]) || !$_SESSION["emp_id"]) {
    header("location:login.php");
    exit();
}

// 2. ดึงข้อมูลผู้ใช้งานที่ Login
$sqllogin = "SELECT * FROM employee WHERE emp_id='" . mysqli_real_escape_string($con, $_SESSION["emp_id"]) . "'";
$result_user_info = mysqli_query($con, $sqllogin);
$row_user_info = mysqli_fetch_assoc($result_user_info);

// ดึงปีที่เลือกจาก Session
$selected_year = isset($_SESSION['selected_year']) ? mysqli_real_escape_string($con, $_SESSION['selected_year']) : ""; 
$emp_id = mysqli_real_escape_string($con, $_SESSION['emp_id']);

define('BASE_IMAGE_ROOT_PATH', ''); 

// ฟังก์ชันสำหรับเติม 0 ให้ครบ 6 หลัก
function formatContract($number) {
    return str_pad($number, 6, "0", STR_PAD_LEFT);
}
?>

<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="icon/icon_login.png" type="image/x-icon"> 
    <title>ระบบจัดการข้อมูลการให้น้ำ - บันทึกข้อมูลการพิมพ์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <style>
        body { 
            background-color: #f4f7fc; 
            font-family: 'Sarabun', sans-serif; 
            padding-top: 85px; /* เผื่อที่สำหรับ fixed-top navbar */
        }
        @media (max-width: 991.98px) {
            body { padding-top: 75px; }
        }
        .container { 
            background-color: #fff; 
            border-radius: 12px; 
            padding: 30px; 
            box-shadow: 0 4px 12px rgba(63, 63, 63, 0.1); 
            margin-top: 30px; 
            margin-bottom: 30px;
        }
        .img-thumbnail { 
            width: 50px; 
            height: 50px; 
            object-fit: cover; 
            border-radius: 8px; 
            cursor: pointer; 
            transition: transform 0.2s;
        }
        .img-thumbnail:hover {
            transform: scale(1.05);
        }
        .no-image-text {
            color: #888;
            font-style: italic;
            font-size: 0.9em;
        }
        .btn-pdf-action { border-radius: 10px; font-weight: bold; }
        .table thead { background-color: rgb(59, 57, 57) !important; color: white !important; text-align: center; }
        .table td, .table th { vertical-align: middle; text-align: center; }
        .history-section { border-top: 2px dashed #ccc; margin-top: 40px; padding-top: 30px; }
        #modalPlotTable_filter { margin-bottom: 15px; }
        .dataTables_filter input { border: 1px solid #198754; border-radius: 5px; padding: 5px; }
        .modal-image-container { display: flex; justify-content: center; align-items: center; background-color: #000; border-radius: 8px; overflow: hidden; min-height: 300px; }
        #modalImage { max-width: 100%; max-height: 80vh; object-fit: contain; display: block; }
        .plot-item .plot-card { cursor: pointer; }
        .plot-card {
            padding: 6px 8px;
            font-size: 0.85rem;
            min-height: 38px;
            user-select: none;
            transition: background-color 0.15s ease, border-color 0.15s ease;
        }
        .plot-card:hover {
            background-color: #f8f9fa;
        }
        .plot-card.selected {
            background-color: #fff3cd;
            border-color: #ffc107 !important;
            border-width: 2px;
        }
        .plot-card-text {
            font-weight: 600;
        }
        .plot-card.selected .plot-card-text {
            color: #856404;
        }
    </style>
</head>

<body>
    <?php require("nav_u.php"); ?>
    
    <div class="content-wrapper">
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 text-center text-md-start">
                <div>
                    <h4 class="mb-0 text-success"><i class='bx bx-spreadsheet'></i> รายการแปลงข้อมูลครบ (ปี <?php echo $selected_year; ?>)</h4>
                    <small class="text-muted">ผู้ใช้งาน: <?php echo htmlspecialchars($row_user_info["emp_name"]); ?></small>
                </div>
                <button class="btn btn-primary btn-pdf-action w-100 w-md-auto mt-3 mt-md-0" data-bs-toggle="modal" data-bs-target="#pdfSelectionModal" style="max-width: 250px;">
                    <i class='bx bx-plus-circle'></i> เพิ่มข้อมูลการพิมพ์
                </button>
            </div>
            
            <hr>

           <div class="table-responsive">
    <table id="dataTable" class="table table-striped table-bordered nowrap" style="width:100%">
        <thead>
            <tr>
                <th>ปี</th>
                <th>ไอดี นสส.</th>
                <th>ไอดีแปลง</th>
                <th>เลขที่สัญญา</th>
                <th>ชนิดอ้อย</th>
                <th>โควต้า</th>
                <th>จำนวนไร่</th>
                <th>น้ำ1</th>
                <th>น้ำ2</th>
                <th>สถานะ</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql_data = "SELECT * FROM image_water 
                        WHERE emp_id = '$emp_id' 
                        AND year_rai = '$selected_year'
                        AND (water_image1 != '') AND (water_image2 != '') 
                        ORDER BY plot_id ASC";
            
            $result_data = mysqli_query($con, $sql_data);
            $pdf_items = [];

            while ($row_data = mysqli_fetch_assoc($result_data)) {
                $pdf_items[] = $row_data; 
                // แสดงเลขสัญญาแบบ 6 หลัก
                $formatted_contract = formatContract($row_data['contract_number']);

                echo "<tr>
                        <td>" . htmlspecialchars($row_data['year_rai']) . "</td>
                        <td>" . htmlspecialchars($row_data['emp_id']) . "</td>
                        <td>" . htmlspecialchars($row_data['plot_id']) . "</td>
                        <td class='fw-bold text-primary'>" . $formatted_contract . "</td>
                        <td>" . htmlspecialchars($row_data['suga_type']) . "</td>
                        <td>" . htmlspecialchars($row_data['quota']) . "</td>
                        <td>" . htmlspecialchars($row_data['area_rai']) . "</td>";

                $empId_s = htmlspecialchars(basename($row_data['emp_id']));
                $contract_s = htmlspecialchars(basename($row_data['contract_number']));
                $plotId_s = htmlspecialchars(basename($row_data['plot_id']));
                $base_path = "images/water/{$empId_s}/{$contract_s}/{$plotId_s}/";

                $image_cols = ['water_image1' => 'น้ำ1', 'water_image2' => 'น้ำ2'];
                foreach ($image_cols as $col => $alt) {
                    $img_name = $row_data[$col];
                    $full_path = $base_path . htmlspecialchars(basename($img_name));
                    echo "<td>";
                    if (!empty($img_name) && file_exists($full_path)) {
                        echo "<img src='$full_path' class='img-thumbnail' alt='$alt'>";
                    } else {
                        echo "<span class='no-image-text'></span>";
                    }
                    echo "</td>";
                }

                // --- สถานะเข้าร่วมโครงการ ---
                $join_status = $row_data['join_status'] ?? 'join';
                if ($join_status === 'notjoin') {
                    $status_badge = "<span class='badge bg-danger'>ไม่เข้าร่วม</span>";
                } else {
                    $status_badge = "<span class='badge bg-success'>เข้าร่วม</span>";
                }
                echo "<td>{$status_badge}</td>";

                echo "</tr>";
            }
            ?>   
        </tbody>
    </table>
</div>

        <div class="history-section">
    <h5 class="text-secondary mb-3"><i class='bx bx-history'></i> หลักฐานการบันทึกข้อมูลการพิมพ์ </h5>
    <div class="table-responsive">
        <table id="historyTable" class="table table-striped table-bordered nowrap">
            <thead class="text-center">
                <tr>
                    <th>ลำดับ</th>
                    <th>รหัสเอกสาร</th>
                    <th>วันที่/เวลาที่บันทึก</th>
                    <th>เลขสัญญา</th>
                    <th>จำนวนแปลง</th>
                    <th>การจัดการ</th>
                </tr>
            </thead>
            <tbody class="text-center">
                <?php
$sql_h = "SELECT h.print_round, h.print_date, w.contract_number, 
          COUNT(h.plot_id) as total_plots, 
          GROUP_CONCAT(h.plot_id SEPARATOR ', ') as all_plots
          FROM print_history h
          LEFT JOIN image_water w ON h.plot_id = w.plot_id COLLATE utf8mb4_general_ci AND w.year_rai COLLATE utf8mb4_general_ci = h.year_rai
          WHERE h.emp_id = '$emp_id' AND h.year_rai = '$selected_year'
          GROUP BY h.print_round, w.contract_number
          ORDER BY h.print_date DESC LIMIT 100";                $res_h = mysqli_query($con, $sql_h);
                $total_rows = mysqli_num_rows($res_h);
                $h_idx = $total_rows;

                while($row_h = mysqli_fetch_assoc($res_h)): 
                    $current_seq = $h_idx--;
                ?>
                    <tr>
                        <td><?php echo $current_seq; ?></td> 
                        <td class="fw-bold text-primary"><?php echo $row_h['print_round']; ?></td> 
                        
                        <td><?php echo date('d/m/Y H:i:s', strtotime($row_h['print_date'])); ?></td>
                        <td class="fw-bold text-success"><?php echo formatContract($row_h['contract_number'] ?? '0'); ?></td>
                        <td><span class="badge bg-primary"><?php echo $row_h['total_plots']; ?> แปลง</span></td>
                        <td>
                            <div class="btn-group">
                                <form action="generate_pdf.php" method="POST" target="_blank" class="me-1">
                                    <input type="hidden" name="print_seq" value="<?php echo $current_seq; ?>">
                                    <input type="hidden" name="print_round" value="<?php echo htmlspecialchars($row_h['print_round']); ?>">
                                    <?php 
                                    $plots_arr = explode(', ', $row_h['all_plots']);
                                    foreach(array_map('trim', $plots_arr) as $p_id) {
                                        echo "<input type='hidden' name='plot_ids[]' value='".htmlspecialchars($p_id)."'>";
                                    }
                                    ?>
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class='bx bxs-file-pdf'></i> พิมพ์
                                    </button>
                                </form>
                                <a href="user_register_money_edit.php?print_round=<?php echo $row_h['print_round']; ?>" class="btn btn-info btn-sm text-white">
                                    <i class='bx bx-edit'></i> แก้ไขไอดีแปลง
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

            <div class="modal fade" id="pdfSelectionModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title"><i class='bx bxs-search-alt-2'></i> ค้นหาเลขสัญญาและเลือกแปลง</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-2 mb-3">
                                <label class="form-label fw-bold">1. พิมพ์เลขที่สัญญาเพื่อค้นหาแปลง:</label>
                                <input type="text" id="plotSearchInput" class="form-control" placeholder="🔍 เช่น 166 หรือ 000166" autocomplete="off">
                            </div>

                            <div id="plotCountText" class="text-muted small mb-2">พิมพ์เลขที่สัญญาด้านบนเพื่อค้นหาแปลง</div>

                            <div id="plotCheckGrid" class="row g-1" style="max-height: 400px; overflow-y: auto; padding: 5px;">
                                <?php foreach ($pdf_items as $item): ?>
                                <div class="col-4 plot-item" style="display:none;" data-plotid="<?php echo htmlspecialchars($item["plot_id"]); ?>" data-contract="<?php echo formatContract($item["contract_number"]); ?>">
                                    <div class="plot-card border rounded d-flex align-items-center justify-content-center">
                                        <input class="pdf-check d-none" type="checkbox" 
                                               value="<?php echo htmlspecialchars($item["plot_id"]); ?>" 
                                               id="plot_<?php echo htmlspecialchars($item["plot_id"]); ?>">
                                        <span class="plot-card-text"><?php echo htmlspecialchars($item["plot_id"]); ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php if (empty($pdf_items)): ?>
                                <div class="col-12 text-center text-muted py-4">
                                    ไม่พบแปลงที่มีข้อมูลครบสำหรับปีนี้
                                </div>
                                <?php endif; ?>
                            </div>
                         </div>
                         <div class="modal-footer d-flex flex-column flex-md-row justify-content-between">
                             <span class="text-muted fw-bold mb-2 mb-md-0" id="selectedCountText">เลือกแล้ว: 0 แปลง</span>
                             <div class="d-flex w-100 w-md-auto gap-2">
                                 <button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="modal">ยกเลิก</button>
                                 <button type="button" class="btn btn-primary flex-fill" id="saveHistoryBtn" disabled>บันทึกข้อมูลการพิมพ์</button>
                             </div>
                         </div>
                     </div>
                 </div>
             </div>
        </div>
    </div>

    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="background: transparent; border: none;">
                <div class="modal-body p-0">
                    <div class="modal-image-container">
                        <img id="modalImage" src="" alt="ขยายรูป">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable({ responsive: true, order: [[2, 'asc']] });

            $('#historyTable').DataTable({ 
                pageLength: 5, 
                order: [],
                language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/th.json' }
            });

            var DISPLAY_LIMIT = 20; // จำกัดจำนวนการ์ดที่แสดงพร้อมกัน กันเบราว์เซอร์หนักตอนเจอเป็นร้อย
            var MAX_SELECT = 20;    // จำกัดจำนวนแปลงที่เลือกได้สูงสุดต่อรอบการพิมพ์

            // แสดงจำนวนแปลงที่พบ / ข้อความชี้นำ
            function updatePlotCount(hasSearch, visible, totalMatched) {
                if (!hasSearch) {
                    $('#plotCountText').text('พิมพ์เลขที่สัญญาด้านบนเพื่อค้นหาแปลง');
                } else if (visible === 0) {
                    $('#plotCountText').text('ไม่พบแปลงของเลขสัญญานี้');
                } else if (totalMatched > visible) {
                    $('#plotCountText').text('พบ ' + totalMatched + ' แปลง — แสดง ' + visible + ' แปลงแรก (พิมพ์เลขสัญญาให้เจาะจงขึ้นเพื่อดูแปลงอื่น)');
                } else {
                    $('#plotCountText').text('พบ ' + visible + ' แปลง');
                }
            }

            // เริ่มต้นเปิด modal: ซ่อนทุกแปลงไว้ก่อน รอพิมพ์เลขสัญญา
            $('#pdfSelectionModal').on('show.bs.modal', function() {
                $('.plot-item').hide();
                $('#plotSearchInput').val('');
                updatePlotCount(false, 0);
            });

            // ล้างค่าที่เลือกไว้ทั้งหมด ไม่ว่าจะปิด modal ด้วยวิธีไหน (กดยกเลิก, กด X, คลิกนอกกรอบ)
            $('#pdfSelectionModal').on('hidden.bs.modal', function() {
                $('.pdf-check').prop('checked', false);
                $('.plot-card').removeClass('selected');
                $('#selectedCountText').text('เลือกแล้ว: 0 แปลง');
                $('#saveHistoryBtn').prop('disabled', true);
            });

            // ต้องพิมพ์เลขที่สัญญาก่อน ถึงจะขึ้น ID แปลงของสัญญานั้น (จำกัดแสดงแค่ 20 แปลงแรกที่เจอ)
            $(document).on('keyup', '#plotSearchInput', function() {
                var val = $(this).val().trim().toLowerCase();
                var valNoZero = val.replace(/^0+/, '');

                if (val === '') {
                    $('.plot-item').hide();
                    updatePlotCount(false, 0);
                    return;
                }

                // หาแปลงที่ตรงกับคำค้นหาทั้งหมดก่อน (ยังไม่โชว์)
                var matched = [];
                $('.plot-item').each(function() {
                    var contract = ($(this).attr('data-contract') || '').toString().toLowerCase();
                    var contractNoZero = contract.replace(/^0+/, '');
                    var match = contract.includes(val) || (valNoZero !== '' && contractNoZero.includes(valNoZero));
                    if (match) matched.push(this);
                });

                // ซ่อนทุกอันก่อน แล้วโชว์แค่ DISPLAY_LIMIT อันแรกที่เจอ
                $('.plot-item').hide();
                matched.slice(0, DISPLAY_LIMIT).forEach(function(el) {
                    $(el).show();
                });

                updatePlotCount(true, Math.min(matched.length, DISPLAY_LIMIT), matched.length);
            });

            // คลิกที่กล่องทั้งใบเพื่อเลือก/ยกเลิกเลือกแปลง (แทน checkbox) — จำกัดเลือกได้สูงสุด MAX_SELECT แปลง
            $(document).on('click', '.plot-card', function() {
                var checkbox = $(this).find('.pdf-check');
                var isCurrentlyChecked = checkbox.prop('checked');

                // ถ้ากำลังจะ "เลือกเพิ่ม" (ไม่ใช่ยกเลิก) และเลือกครบ MAX_SELECT แล้ว ให้บล็อกไว้
                if (!isCurrentlyChecked) {
                    var selectedCount = $('.pdf-check:checked').length;
                    if (selectedCount >= MAX_SELECT) {
                        alert('เลือกได้สูงสุด ' + MAX_SELECT + ' แปลงต่อครั้งนะครับ กรุณายกเลิกบางแปลงก่อนถ้าจะเลือกเพิ่ม');
                        return;
                    }
                }

                var isChecked = !isCurrentlyChecked;
                checkbox.prop('checked', isChecked);
                $(this).toggleClass('selected', isChecked);
                checkbox.trigger('change');
            });

            $(document).on('change', '.pdf-check', function() {
                var count = $('.pdf-check:checked').length;
                $('#selectedCountText').text('เลือกแล้ว: ' + count + ' แปลง');
                $('#saveHistoryBtn').prop('disabled', count === 0);
            });

            $('#saveHistoryBtn').on('click', function() {
                var plots = [];
                $('.pdf-check:checked').each(function() {
                    plots.push($(this).val());
                });

                $.ajax({
                    url: 'save_print_history.php',
                    type: 'POST',
                    data: { 
                        plot_ids: plots,
                        year_rai: '<?php echo $selected_year; ?>',
                        print_round: Date.now() 
                    },
                    success: function() {
                        alert('✅ บันทึกข้อมูลการพิมพ์สำเร็จ');
                        location.reload();
                    }
                });
            });

            $(document).on('click', '.img-thumbnail', function() {
                $('#modalImage').attr('src', $(this).attr('src'));
                $('#imageModal').modal('show');
            });
        });
    </script>

    <?php include 'nav_u_footer.php'; ?>
</body>
</html>