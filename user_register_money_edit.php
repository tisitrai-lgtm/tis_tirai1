<?php
session_start();
require("dbconnect.php");

// 1. ตรวจสอบสถานะและรับค่า print_round
if (!isset($_GET['print_round'])) {
    header("location:user_register_water_money.php");
    exit();
}

$print_round = mysqli_real_escape_string($con, $_GET['print_round']);
$emp_id = mysqli_real_escape_string($con, $_SESSION['emp_id']);
$selected_year = $_SESSION['selected_year'] ?? '';

// 2. ดึงข้อมูลแปลงที่เคยเลือกไว้เดิม
$sql_old = "SELECT plot_id FROM print_history WHERE print_round = '$print_round' AND emp_id = '$emp_id'";
$res_old = mysqli_query($con, $sql_old);
$old_plots = [];

while($r = mysqli_fetch_assoc($res_old)) {
    $old_plots[] = $r['plot_id'];
}

// 3. ดึงเลขสัญญาปัจจุบันเพื่อโชว์หัวข้อ
$sql_info = "SELECT w.contract_number 
             FROM print_history h 
             JOIN image_water w ON h.plot_id COLLATE utf8mb4_general_ci = w.plot_id COLLATE utf8mb4_general_ci
             WHERE h.print_round = '$print_round' LIMIT 1";
$res_info = mysqli_query($con, $sql_info);
$info = mysqli_fetch_assoc($res_info);
$contract_display = str_pad($info['contract_number'] ?? '', 6, "0", STR_PAD_LEFT);

function formatContract($number) {
    return str_pad($number, 6, "0", STR_PAD_LEFT);
}
?>

<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>แก้ไขข้อมูลการพิมพ์ - เลขสัญญา <?php echo $contract_display; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-blue: #0066cc;
            --soft-bg: #f8fafc;
            --border-color: #e2e8f0;
            --text-main: #334155;
        }

        body { 
            background-color: var(--soft-bg);
            font-family: 'Sarabun', sans-serif; 
            color: var(--text-main);
            min-height: 100vh;
            padding-top: 85px; /* เผื่อที่สำหรับ fixed-top navbar */
        }

        @media (max-width: 991.98px) {
            body { padding-top: 75px; }
        }

        .edit-container { 
            background: white;
            border-radius: 12px; 
            padding: 25px; 
            margin-top: 20px; 
            margin-bottom: 100px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
        }

        @media (max-width: 768px) {
            .container { padding: 0; }
            .edit-container {
                padding: 15px;
                border-radius: 0;
                margin-top: 0;
                border-left: none;
                border-right: none;
                box-shadow: none;
            }
        }

        .sticky-bottom-bar { 
            position: fixed; 
            bottom: 0; 
            left: 0; 
            right: 0; 
            background: white;
            padding: 12px 15px; 
            box-shadow: 0 -4px 12px rgba(0,0,0,0.05); 
            z-index: 1000; 
            border-top: 1px solid var(--border-color);
        }

        .alert-info-custom {
            background: #f1f5f9;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 15px;
        }

        #updateBtn {
            background-color: var(--primary-blue);
            border: none;
            border-radius: 8px;
            padding: 8px 24px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.2s;
        }

        #updateBtn:hover {
            background-color: #0052a3;
            transform: translateY(-1px);
        }

        /* ===== Plot card grid (คลิกทั้งกล่องเพื่อเลือก) ===== */
        #plotCheckGrid {
            min-height: 200px;
            padding: 5px;
        }
        .plot-card {
            cursor: pointer;
            user-select: none;
            padding: 6px 8px;
            font-size: 0.85rem;
            min-height: 38px;
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
        #paginationControls .page-link {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php require("nav_u.php"); ?>

    <div class="container">
        <div class="edit-container">
            <div class="mb-4">
                <h5 class="fw-bold mb-1">แก้ไขรายการแปลง</h5>
                <p class="text-muted small mb-0">รหัสเอกสาร: <strong><?php echo htmlspecialchars($print_round); ?></strong></p>
            </div>

            <div class="alert-info-custom d-flex flex-column flex-md-row align-items-center mb-4">
                <i class='bx bx-info-circle fs-3 mb-2 mb-md-0 me-md-3 text-secondary'></i>
                <div class="text-center text-md-start">
                    <span class="me-3">สัญญา: <strong><?php echo $contract_display; ?></strong></span>
                    <span class="me-3">ปีการผลิต: <strong><?php echo htmlspecialchars($selected_year); ?></strong></span>
                </div>
            </div>

            <div class="row g-2 mb-2">
                <label class="form-label fw-bold mb-1">ค้นหาไอดีแปลง:</label>
                <input type="text" id="plotSearchInput" class="form-control" placeholder="🔍 พิมพ์ไอดีแปลงเพื่อกรอง..." autocomplete="off">
            </div>

            <p class="text-muted small mb-2">
                <i class='bx bx-hand-pointer'></i> คลิกที่กล่องเพื่อเลือก/ยกเลิกแปลง (กล่องสีเหลือง = เลือกอยู่)
                &nbsp;|&nbsp; <span id="plotCountText"></span>
            </p>

            <div class="row g-2" id="plotCheckGrid">
                <?php
                $c_num = $info['contract_number'];
                $sql_all = "SELECT * FROM image_water 
                            WHERE contract_number = '$c_num' 
                            AND year_rai = '$selected_year' 
                            AND water_image1 != '' AND water_image2 != ''";
                $res_all = mysqli_query($con, $sql_all);
                $plot_total = 0;
                while($row = mysqli_fetch_assoc($res_all)):
                    $plot_total++;
                    $is_checked = in_array($row['plot_id'], $old_plots);
                ?>
                <div class="col-4 col-md-3 plot-item" data-plotid="<?php echo htmlspecialchars(strtolower($row['plot_id'])); ?>">
                    <div class="plot-card border rounded d-flex align-items-center justify-content-center<?php echo $is_checked ? ' selected' : ''; ?>">
                        <input type="checkbox" class="d-none edit-check" 
                               value="<?php echo htmlspecialchars($row['plot_id']); ?>" 
                               <?php echo $is_checked ? 'checked' : ''; ?>>
                        <span class="plot-card-text"><?php echo htmlspecialchars($row['plot_id']); ?></span>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <div id="noResultText" class="col-12 text-center text-muted py-4" style="display:none;">
                ไม่พบไอดีแปลงที่ค้นหา
            </div>

            <nav class="mt-3">
                <ul class="pagination justify-content-center flex-wrap" id="paginationControls"></ul>
            </nav>

            <?php if ($plot_total === 0): ?>
            <div class="col-12 text-center text-muted py-4">
                ไม่พบแปลงของเลขสัญญานี้
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="sticky-bottom-bar">
        <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center text-center text-md-start">
            <span class="fw-bold text-muted mb-2 mb-md-0" id="countText">เลือกอยู่: <?php echo count($old_plots); ?> แปลง (รหัสเอกสาร: <?php echo htmlspecialchars($print_round); ?>)</span>
            <button type="button" class="btn btn-success" id="updateBtn">
                <i class='bx bx-save'></i> อัปเดตข้อมูลการพิมพ์
            </button>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            var pageSize = 20;
            var currentPage = 1;

            function updateSelectedCount() {
                var count = $('.edit-check:checked').length;
                $('#countText').html('<i class="bx bxs-check-square"></i> เลือกอยู่: <span class="text-primary">' + count + '</span> แปลง (รหัสเอกสาร: <?php echo htmlspecialchars($print_round); ?>)');
            }

            function getFilteredItems() {
                var val = $('#plotSearchInput').val().trim().toLowerCase();
                if (val === '') return $('.plot-item');
                return $('.plot-item').filter(function() {
                    return ($(this).attr('data-plotid') || '').includes(val);
                });
            }

            // แสดงเฉพาะรายการของหน้าปัจจุบัน (สถานะติ๊กเลือกไม่ถูกแตะต้องเลย แค่ซ่อน/โชว์)
            function renderPage() {
                var filtered = getFilteredItems();
                var total = filtered.length;
                var totalPages = Math.max(1, Math.ceil(total / pageSize));
                if (currentPage > totalPages) currentPage = totalPages;
                if (currentPage < 1) currentPage = 1;

                $('.plot-item').hide();

                if (total === 0) {
                    $('#noResultText').show();
                } else {
                    $('#noResultText').hide();
                    var start = (currentPage - 1) * pageSize;
                    var end = start + pageSize;
                    filtered.slice(start, end).show();
                }

                $('#plotCountText').text('พบทั้งหมด ' + total + ' แปลง (หน้า ' + currentPage + '/' + totalPages + ')');
                renderPagination(totalPages);
            }

            function renderPagination(totalPages) {
                if (totalPages <= 1) {
                    $('#paginationControls').html('');
                    return;
                }
                var html = '';
                html += '<li class="page-item' + (currentPage === 1 ? ' disabled' : '') + '"><span class="page-link" data-page="' + (currentPage - 1) + '">ก่อนหน้า</span></li>';
                for (var i = 1; i <= totalPages; i++) {
                    html += '<li class="page-item' + (i === currentPage ? ' active' : '') + '"><span class="page-link" data-page="' + i + '">' + i + '</span></li>';
                }
                html += '<li class="page-item' + (currentPage === totalPages ? ' disabled' : '') + '"><span class="page-link" data-page="' + (currentPage + 1) + '">ถัดไป</span></li>';
                $('#paginationControls').html(html);
            }

            $(document).on('click', '#paginationControls .page-link', function() {
                var page = parseInt($(this).data('page'));
                if (!isNaN(page) && page >= 1) {
                    currentPage = page;
                    renderPage();
                    $('html, body').animate({ scrollTop: $('#plotCheckGrid').offset().top - 100 }, 200);
                }
            });

            $(document).on('keyup', '#plotSearchInput', function() {
                currentPage = 1;
                renderPage();
            });

            // คลิกที่กล่องทั้งใบเพื่อเลือก/ยกเลิกเลือกแปลง (ไม่กระทบ pagination/search)
            $(document).on('click', '.plot-card', function() {
                var checkbox = $(this).find('.edit-check');
                var isChecked = !checkbox.prop('checked');
                checkbox.prop('checked', isChecked);
                $(this).toggleClass('selected', isChecked);
                updateSelectedCount();
            });

            $('#updateBtn').on('click', function() {
                var plots = [];
                $('.edit-check:checked').each(function() {
                    plots.push($(this).val());
                });

                if(plots.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'กรุณาเลือกแปลง',
                        text: 'ต้องเลือกอย่างน้อย 1 แปลง',
                        confirmButtonText: 'ตกลง',
                        confirmButtonColor: '#3085d6',
                        heightAuto: false,
                        customClass: { popup: 'rounded-4' }
                    });
                    return;
                }

                Swal.fire({
                    title: 'ยืนยันการแก้ไข?',
                    text: 'ต้องการอัปเดตข้อมูลเอกสารรหัส ' + '<?php echo htmlspecialchars($print_round); ?>' + ' ใช่ไหมคะ?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'อัปเดต',
                    cancelButtonText: 'ยกเลิก',
                    heightAuto: false,
                    customClass: { popup: 'rounded-4' }
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'กำลังบันทึก...',
                            allowOutsideClick: false,
                            didOpen: () => { Swal.showLoading(); }
                        });

                        $.ajax({
                            url: 'update_print_history.php',
                            type: 'POST',
                            data: {
                                print_round: '<?php echo htmlspecialchars($print_round); ?>',
                                plot_ids: plots,
                                year_rai: '<?php echo htmlspecialchars($selected_year); ?>'
                            },
                            success: function(response) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'แก้ไขสำเร็จ!',
                                    text: 'บันทึกข้อมูลเรียบร้อยแล้ว',
                                    timer: 2000,
                                    showConfirmButton: false,
                                    heightAuto: false,
                                    customClass: { popup: 'rounded-4' }
                                }).then(() => {
                                    window.location.href = 'user_register_water_money.php';
                                });
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'เกิดข้อผิดพลาด',
                                    text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้ค่ะ',
                                    confirmButtonText: 'ตกลง',
                                    heightAuto: false,
                                    customClass: { popup: 'rounded-4' }
                                });
                            }
                        });
                    }
                });
            });

            // เริ่มต้นแสดงหน้าแรก
            renderPage();
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>