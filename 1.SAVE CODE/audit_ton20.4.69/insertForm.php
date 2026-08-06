<?php
// insertForm.php - ฟอร์มเพิ่มข้อมูล
// ตรวจสอบให้แน่ใจว่ามีไฟล์ db_connect.php และเชื่อมต่อฐานข้อมูลได้ถูกต้อง
require_once 'db_connect.php'; 

$selected_year = $_GET['year'] ?? '';
if (empty($selected_year)) {
    // ใช้วิธีการ echo แบบปกติสำหรับโค้ด HTML สั้นๆ เพื่อลดความเสี่ยง
    echo '<!DOCTYPE html>
          <html lang="th">
          <head><meta charset="UTF-8"><title>Error</title>
          <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
          <body class="d-flex justify-content-center align-items-center vh-100">
          <div class="alert alert-danger text-center shadow p-4">
              <h2><i class="bi bi-x-octagon-fill me-2"></i> กรุณาระบุปีการผลิต</h2>
              <a href="index.php" class="btn btn-primary mt-3"><i class="bi bi-arrow-left me-2"></i> กลับไปหน้าเลือกปี</a>
          </div></body></html>';
    exit;
}

/**
 * ฟังก์ชันสำหรับสร้างส่วนหัวข้อและช่องอัปโหลดรูปภาพ 2 รูปต่อแถวในแนวนอน
 * @param string $title หัวข้อของส่วน
 * @param array $images อาร์เรย์ของชื่อฟิลด์ => ลาเบล
 * @param string $icon Bootstrap Icon class
 */
function createImageUploads($title, $images, $icon = 'bi-image-fill') {
    echo '<div class="card bg-light-subtle mb-4 shadow-sm">';
    echo '  <div class="card-header card-header-light-blue"><h5 class="mb-0"><i class="bi ' . $icon . ' me-2"></i>' . $title . '</h5></div>';
    echo '  <div class="card-body">';
    echo '  <div class="row g-4">'; // ใช้ row เพื่อจัดรูปภาพให้อยู่ในแนวนอน
    foreach ($images as $name => $label) {
        // ใช้ Heredoc โดยระมัดระวัง ไม่ให้มีช่องว่างนำหน้า "HTML;"
        echo <<<HTML
        <div class="col-md-6 d-flex flex-column"> 
            <label for="$name" class="form-label text-muted small mb-1"><i class="bi bi-camera-fill me-1"></i> $label</label>
            <input type="file" name="$name" id="$name" class="form-control form-control-sm" accept="image/*" onchange="previewImage(this)">
            <img id="preview-$name" class="preview-img mt-2 d-none img-fluid rounded border shadow-sm" src="#" alt="Preview">
        </div>
HTML;
    }
    echo '  </div>'; // ปิด row
    echo ' </div>'; // ปิด card-body
    echo '</div>'; // ปิด card
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มข้อมูลแปลงอ้อย: ปี <?php echo htmlspecialchars($selected_year); ?></title>
    <link rel="icon" href="icon/unnamed.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"> 
    
    <style>
        /* พื้นหลังหลัก พร้อมรูปภาพ bg.jpg */
        body {
            background-color: #f4f7fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start; 
            padding-top: 50px;
            padding-bottom: 50px;
            background-image: url('icon/bg.jpg'); 
            background-size: cover; 
            background-position: center center; 
            background-attachment: fixed; 
            background-repeat: no-repeat; 
        }

        /* Container และ Card หลัก */
        .main-content .card {
            border-radius: 15px;
            max-width: 1000px;
            margin: 0 auto;
        }

        .main-content .card-header {
        background-color: #ffffffff !important; /* สีฟ้าอ่อนมาก ๆ */
        color: #0d6efd !important; /* สีตัวอักษรฟ้าเข้มปานกลาง */
        border-bottom: 1px solid #b3e5fc;
        }

        .main-content .card-body {
            background-color: #ffffff;
        }

        /* สไตล์สำหรับส่วนการอัปโหลดรูปภาพ */
        .card-header h6 {
            font-weight: 600;
        }

        /* สไตล์ฟอร์ม input */
        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        /* ปุ่มหลัก */
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
            transition: background-color 0.3s ease, transform 0.1s ease;
        }

       

        .btn-secondary {
            transition: background-color 0.3s ease;
        }

        /* Responsive ปรับขนาด Container เล็กน้อยบนจอเล็ก */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            .main-content .card {
                margin-left: 15px;
                margin-right: 15px;
            }
        }
        
        /* สไตล์สำหรับรูปภาพตัวอย่างในฟอร์ม */
        .preview-img {
            max-width: 100%;
            height: 150px; /* กำหนดความสูงคงที่ */
            object-fit: cover; /* ครอบคลุมพื้นที่แต่คงอัตราส่วน */
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        .preview-img:hover {
            transform: scale(1.02);
        }
        .form-label {
            font-weight: 500;
        }
        .card-header-light-blue {
        background-color: #6ec4fdff !important; /* สีฟ้าอ่อนมาก ๆ */
        color: #0d6efd !important; /* สีตัวอักษรฟ้าเข้มปานกลาง */
        border-bottom: 1px solid #b3e5fc;
    }
    </style>
</head>
<body>
<div class="main-content">
    <div class="container py-5">
        <div class="card shadow-lg border-0">
            <div class="card-header bg-primary text-white text-center py-4 rounded-top">
                <h2 class="mb-1"><i class="bi bi-clipboard-plus-fill me-2"></i> เพิ่มข้อมูลแปลงอ้อย</h2>
                <p class="mb-0 fs-5">ปีการผลิต: <strong><?php echo htmlspecialchars($selected_year); ?></strong></p>
            </div>
            <div class="card-body p-4 p-md-5">
                <form action="insertData.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="production_year" value="<?php echo htmlspecialchars($selected_year); ?>">

                    <h4 class="text-primary border-bottom pb-2 mb-4"><i class="bi bi-info-circle-fill me-2"></i> ข้อมูลพื้นฐานแปลง</h4>
                    <div class="row g-4 mb-4">
                        <div class="col-md-6"><label for="plot_id" class="form-label">ID แปลง <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="plot_id" name="plot_id" required placeholder="">
                        </div>
                        <div class="col-md-6"><label for="contract_number" class="form-label">เลขสัญญา</label>
                            <input type="text" class="form-control" id="contract_number" name="contract_number" placeholder="">
                        </div>
                        <div class="col-md-6"><label for="quota" class="form-label">โควต้า</label>
                            <input type="text" class="form-control" id="quota" name="quota">
                        </div>
                        <div class="col-md-6"><label for="agency" class="form-label">หน่วยส่งเสริม</label>
                            <input type="text" class="form-control" id="agency" name="agency" placeholder="รหัสหน่วยส่งเสริม">
                        </div>
                        <div class="col-md-6"><label for="emp_number" class="form-label">นักส่งเสริม</label>
                            <input type="text" class="form-control" id="emp_number" name="emp_number" placeholder="รหัสนักส่งเสริม">
                        </div>
                        <div class="col-md-6"><label class="form-label">ชนิดอ้อย <span class="text-danger">*</span></label>
                            <input type="text" name="suga_type" class="form-control" required placeholder="เช่น LK92-11">
                        </div>
                        <div class="col-md-6"><label for="rai_area" class="form-label">พื้นที่ (ไร่) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="rai_area" name="rai_area" min="0" step="0.01" required placeholder="ระบุเป็นตัวเลข">
                        </div>
                    </div>
                    
                    <h4 class="text-primary border-bottom pb-2 mt-5 mb-4"><i class="bi bi-bar-chart-fill me-2"></i> ข้อมูลการประเมินอ้อย</h4>
                    
                    <?php
                    $section1_images = [
                        "estimate_ton_1" => "ประมาณตันอ้อยก่อนเปิดหีบ (หมุด)",
                        "estimate_ton_2" => "ประมาณตันอ้อยก่อนเปิดหีบ (แปลง)",
                    ];
                    createImageUploads("ประมาณตันอ้อยก่อนเปิดหีบ", $section1_images, 'bi-pin-map-fill');
                    
                    ?>
                    <div class="col-md-6"><label for="ton_rai" class="form-label">ตันต่อ (ไร่) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="ton_rai" name="ton_rai" min="0" step="0.01" required placeholder="ระบุเป็นตัวเลข">
                    </div>
                    
                    <div class="mt-5 mb-4">
                        <label for="notes" class="form-label fs-5 text-secondary"><i class="bi bi-pencil-square me-2"></i> หมายเหตุเพิ่มเติม</label>
                        <textarea class="form-control" name="notes" rows="4" placeholder="บันทึกข้อมูลอื่น ๆ ที่เกี่ยวข้องกับแปลงนี้"></textarea>
                    </div>

                    <div class="mt-5 text-center">
                        <button type="submit" class="btn btn-primary btn-lg shadow-sm me-3" style="min-width: 150px;">
                            <i class="bi bi-save-fill me-2"></i> บันทึกข้อมูล
                        </button>
                        <a href="dashboard.php?year=<?php echo htmlspecialchars($selected_year); ?>" class="btn btn-secondary btn-lg shadow-sm" style="min-width: 150px;">
                            <i class="bi bi-x-circle-fill me-2"></i> ยกเลิก
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    /**
     * JavaScript สำหรับแสดงตัวอย่างรูปภาพเมื่อมีการเลือกไฟล์
     */
    function previewImage(input) {
        // หาองค์ประกอบของรูปภาพตัวอย่างโดยใช้ ID
        const preview = document.getElementById(`preview-${input.id}`); 
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();

            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('d-none'); // แสดงรูปภาพ
            }

            reader.readAsDataURL(input.files[0]);
        } else {
            preview.src = "#";
            preview.classList.add('d-none'); // ซ่อนรูปภาพ
        }
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php 
// ตรวจสอบว่า $conn ถูกกำหนดค่าก่อนเรียกใช้ close()
// **สำคัญ:** บรรทัดนี้ต้องเป็นบรรทัดสุดท้ายของไฟล์ และไม่มีช่องว่างหรือบรรทัดใหม่ตามมา
if (isset($conn) && $conn) {
    $conn->close(); 
}
// หากมีปัญหาอักขระพิเศษอีก ให้แน่ใจว่าแท็กปิด PHP นี้เป็นอักขระสุดท้ายในไฟล์
?>