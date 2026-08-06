<?php
require_once 'db_connect.php';

$selected_year = $_GET['year'] ?? '';
if (empty($selected_year)) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มข้อมูลตรวจสอบแปลงอ้อย | ปี <?php echo htmlspecialchars($selected_year); ?></title>
    <link rel="icon" href="icon/unnamed.png" type="image/png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="index.css">
    
    <style>
        .page-container {
            padding: 2rem 0 5rem;
        }

        .form-section-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin: 2rem 0 1.2rem;
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--primary);
            border-bottom: 3px solid #e2e8f0;
            padding-bottom: 12px;
        }

        .form-label {
            font-weight: 700;
            color: #334155;
            font-size: 1.1rem;
            margin-bottom: 8px;
        }

        .form-control-premium {
            border-radius: 12px;
            border: 2px solid #cbd5e1;
            padding: 12px 15px;
            font-size: 1.05rem;
            transition: all 0.3s;
        }

        .form-control-premium:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 5px rgba(30, 58, 138, 0.15);
        }

        .form-select-premium {
            border-radius: 12px;
            padding: 12px 15px;
            border: 2px solid #cbd5e1;
            font-size: 1.05rem;
        }

        .floating-action-bar {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: auto;
            z-index: 1000;
        }

        .image-preview-container {
            margin-top: 10px;
            display: none;
        }
        
        .image-preview {
            max-width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px dashed #ddd;
        }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .page-container {
                padding: 1rem 0 3rem;
            }

            .form-section-title {
                font-size: 1.2rem;
                margin: 1.5rem 0 1rem;
                padding-bottom: 8px;
            }

            .form-label {
                font-size: 1rem;
                margin-bottom: 5px;
            }

            .form-control-premium, .form-select-premium {
                padding: 10px 12px;
                font-size: 1rem;
            }

            .badge {
                font-size: 0.9rem !important;
                padding: 5px 10px !important;
            }

            .btn-premium {
                width: 100%;
                padding: 12px !important;
            }

            .d-flex.gap-3.justify-content-center {
                flex-direction: column;
                gap: 10px !important;
            }

            .btn-light {
                width: 100%;
                padding: 12px !important;
            }
        }
    </style>
</head>
<body>
<?php require("nav.php");?>

<div class="container page-container">
    <form action="insertData.php" method="POST" enctype="multipart/form-data" class="fade-in">
        <input type="hidden" name="production_year" value="<?php echo htmlspecialchars($selected_year); ?>">

        <div class="glass-card-white">
            <h2 class="mb-1 fw-bold" style="font-size: 2.2rem;">เพิ่มข้อมูลใหม่</h2>
            <p class="text-muted" style="font-size: 1.2rem;">ปีการผลิต: <span class="badge bg-primary px-3 py-2" style="font-size: 1.1rem;"><?php echo htmlspecialchars($selected_year); ?></span></p>

            <div class="form-section-title">
                <i class='bx bx-map-pin'></i> ข้อมูลแปลงทางกายภาพ
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label">ID แปลง <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class='bx bx-hash'></i></span>
                        <input type="text" class="form-control form-control-premium" name="plot_id" placeholder="ระบุรหัสแปลง" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">เลขสัญญา</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class='bx bx-file'></i></span>
                        <input type="text" class="form-control form-control-premium" name="contract_number" placeholder="ระบุเลขสัญญา">
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">โควต้า</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class='bx bx-user'></i></span>
                        <input type="text" class="form-control form-control-premium" name="quota" placeholder="ระบุโควต้า">
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">หน่วยงาน</label>
                    <select class="form-select form-select-premium" name="agency">
                        <option value="">-- เลือกหน่วยงาน --</option>
                        <option value="ศรีนครใต้">ศรีนครใต้</option>
                        <option value="ชัยคีรี">ชัยคีรี</option>
                        <option value="ท่าชัย">ท่าชัย</option>
                        <option value="ท่าชัยเหนือ">ท่าชัยเหนือ</option>
                        <option value="คีรีมาศ">คีรีมาศ</option>
                        <option value="ศรีสำโรง">ศรีสำโรง</option>
                        <option value="ทุ่งเสลี่ยม">ทุ่งเสลี่ยม</option>
                        <option value="บางขลัง">บางขลัง</option>
                        <option value="ศรีนครเหนือ">ศรีนครเหนือ</option>
                        <option value="ตลิ่งชัน">ตลิ่งชัน</option>
                        <option value="เขาหลวง">เขาหลวง</option>
                        <option value="ศรีสัชนาลัย">ศรีสัชนาลัย</option>
                        <option value="สวรรคโลก">สวรรคโลก</option>
                        <option value="ตาก">ตาก</option>
                        <option value="ท่าชัยใต้">ท่าชัยใต้</option>
                        <option value="น้ำอ่าง">น้ำอ่าง</option>
                        <option value="บ่อทอง">บ่อทอง</option>
                        <option value="ชาติตระการ">ชาติตระการ</option>
                        <option value="หนองตม">หนองตม</option>
                        <option value="พิชัย">พิชัย</option>
                        <option value="วัดโบสถ์">วัดโบสถ์</option>
                        <option value="พรหมพิราม">พรหมพิราม</option>
                        <option value="เมือง">เมือง</option>
                        <option value="น้ำปาด">น้ำปาด</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">พื้นที่ (ไร่)</label>
                    <div class="input-group">
                        <input type="number" class="form-control form-control-premium" name="rai_area" min="0" placeholder="0" required>
                        <span class="input-group-text bg-light border-0">ไร่</span>
                    </div>
                </div>
            </div>

            <div class="form-section-title">
                <i class='bx bx-leaf'></i> รายละเอียดและคุณภาพการผลิต
            </div>

            <div class="row g-4">
                <!-- Soil Section -->
                <div class="col-md-6">
                    <div class="p-4 bg-white border rounded-4 h-100 shadow-sm">
                        <label class="form-label fw-bold">ชนิดดิน</label>
                        <select class="form-select form-select-premium mb-3" name="soil_type">
                            <option value="">-- ประเมินคุณภาพ --</option>
                            <option value="1">ดีมาก</option>
                            <option value="2">ดี</option>
                            <option value="3">พอใช้</option>
                        </select>
                        <input type="file" name="soil_image" class="form-control form-control-sm" accept="image/*" onchange="previewImage(this)">
                        <div class="image-preview-container"><img class="image-preview" src="#"></div>
                    </div>
                </div>

                <!-- Preparation Section -->
                <div class="col-md-6">
                    <div class="p-4 bg-white border rounded-4 h-100 shadow-sm">
                        <label class="form-label fw-bold">การเตรียมดิน</label>
                        <select class="form-select form-select-premium mb-3" name="soil_preparation_details">
                            <option value="">-- ประเมินคุณภาพ --</option>
                            <option value="1">ดีมาก</option>
                            <option value="2">ดี</option>
                            <option value="3">พอใช้</option>
                        </select>
                        <input type="file" name="soil_preparation_image" class="form-control form-control-sm" accept="image/*" onchange="previewImage(this)">
                        <div class="image-preview-container"><img class="image-preview" src="#"></div>
                    </div>
                </div>

                <!-- Variety Section -->
                <div class="col-md-6">
                    <div class="p-4 bg-white border rounded-4 h-100 shadow-sm">
                        <label class="form-label fw-bold">พันธุ์อ้อย</label>
                        <select class="form-select form-select-premium mb-3" name="cane_variety">
                            <option value="">-- ประเมินคุณภาพ --</option>
                            <option value="1">ดีมาก</option>
                            <option value="2">ดี</option>
                            <option value="3">พอใช้</option>
                        </select>
                        <input type="file" name="cane_variety_image" class="form-control form-control-sm" accept="image/*" onchange="previewImage(this)">
                        <div class="image-preview-container"><img class="image-preview" src="#"></div>
                    </div>
                </div>

                <!-- Planting Section -->
                <div class="col-md-6">
                    <div class="p-4 bg-white border rounded-4 h-100 shadow-sm">
                        <label class="form-label fw-bold">การปลูก</label>
                        <select class="form-select form-select-premium mb-3" name="planting_details">
                            <option value="">-- มาตรฐาน --</option>
                            <option value="1">มาตรฐาน</option>
                            <option value="2">ไม่ได้มาตรฐาน</option>
                        </select>
                        <input type="file" name="planting_image" class="form-control form-control-sm" accept="image/*" onchange="previewImage(this)">
                        <div class="image-preview-container"><img class="image-preview" src="#"></div>
                    </div>
                </div>

                <!-- Watering Section -->
                <div class="col-md-6">
                    <div class="p-4 bg-white border rounded-4 h-100 shadow-sm">
                        <label class="form-label fw-bold">การให้น้ำ</label>
                        <select class="form-select form-select-premium mb-3" name="watering_details">
                            <option value="">-- สถานะ --</option>
                            <option value="1">มีระบบน้ำ</option>
                            <option value="2">ไม่มีระบบน้ำ</option>
                        </select>
                        <input type="file" name="watering_image" class="form-control form-control-sm" accept="image/*" onchange="previewImage(this)">
                        <div class="image-preview-container"><img class="image-preview" src="#"></div>
                    </div>
                </div>

                <!-- Germination Section -->
                <div class="col-md-6">
                    <div class="p-4 bg-white border rounded-4 h-100 shadow-sm">
                        <label class="form-label fw-bold">เปอร์เซ็นต์งอก (%)</label>
                        <div class="input-group mb-3">
                            <input type="number" class="form-control form-control-premium" name="germination_percentage" min="0" max="100" placeholder="0-100">
                            <span class="input-group-text">%</span>
                        </div>
                        <input type="file" name="germination_image" class="form-control form-control-sm" accept="image/*" onchange="previewImage(this)">
                        <div class="image-preview-container"><img class="image-preview" src="#"></div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <label class="form-label"><i class='bx bx-note'></i> หมายเหตุเพิ่มเติม</label>
                <textarea class="form-control form-control-premium" name="notes" rows="4" placeholder="ระบุรายละเอียดเพิ่มเติมที่มีความสำคัญ..."></textarea>
            </div>

            <div class="mt-5 d-flex gap-3 justify-content-center">
                <button type="submit" class="btn btn-premium px-5 py-3 shadow">
                    <i class='bx bx-save'></i> บันทึกข้อมูล
                </button>
                <a href="dashboard.php?year=<?php echo htmlspecialchars($selected_year); ?>" class="btn btn-light px-5 py-3 rounded-4">
                    ยกเลิก
                </a>
            </div>
        </div>
    </form>
</div>

<script>
function previewImage(input) {
    const container = input.nextElementSibling;
    const preview = container.querySelector('.image-preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            container.style.display = 'block';
        }
        reader.readAsDataURL(input.files[0]);
    } else {
        container.style.display = 'none';
    }
}
</script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    $('form.fade-in').on('submit', function(e) {
        e.preventDefault();
        const form = this;
        Swal.fire({
            title: 'ยืนยันการบันทึกข้อมูลใหม่?',
            text: "คุณตรวจสอบข้อมูลครบถ้วนแล้วใช่หรือไม่?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="bx bx-save"></i> ยืนยันบันทึก',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                // เปลี่ยนข้อความปุ่มและใส่ Loading
                Swal.fire({
                    title: 'กำลังบันทึกข้อมูล...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                form.submit();
            }
        });
    });
});
</script>
</body>
</html>
<?php $conn->close(); ?>

