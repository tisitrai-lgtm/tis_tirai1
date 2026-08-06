<?php
/**
 * insertForm.php - ฟอร์มเพิ่มข้อมูลแปลงอ้อย (Premium Modern UI)
 */
require_once 'db_connect.php'; 

$selected_year = $_GET['year'] ?? '';
if (empty($selected_year)) {
    echo '<!DOCTYPE html>
          <html lang="th">
          <head><meta charset="UTF-8"><title>Error</title>
          <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
          <body class="d-flex justify-content-center align-items-center vh-100" style="background:#f8f9fa;">
          <div class="alert alert-danger text-center shadow-lg border-0 p-5" style="border-radius:20px;">
              <h2 class="fw-bold mb-3">กรุณาระบุปีการผลิต</h2>
              <p class="text-muted mb-4">ไม่พบข้อมูลปีการผลิตที่ต้องการเพิ่มข้อมูล</p>
              <a href="index.php" class="btn btn-primary px-4 py-2 rounded-pill">กลับไปหน้าเลือกปี</a>
          </div></body></html>';
    exit;
}

/**
 * ฟังก์ชันสำหรับสร้างส่วนหัวข้อและช่องอัปโหลดรูปภาพ
 */
function createImageUploads($title, $images, $icon = 'bx-image') {
    echo '<div class="sub-card mb-4">';
    echo '  <h5 class="fw-bold mb-3 d-flex align-items-center gap-2" style="color: #444;"><i class="bx ' . $icon . ' text-primary"></i> ' . $title . '</h5>';
    echo '  <div class="row g-3">';
    foreach ($images as $name => $label) {
        echo <<<HTML
        <div class="col-md-6"> 
            <div class="upload-box">
                <label for="$name" class="form-label small text-muted mb-2">$label</label>
                <input type="file" name="$name" id="$name" class="form-control form-control-sm" accept="image/*" onchange="previewImage(this)">
                <div class="mt-2 text-center">
                    <img id="preview-$name" class="preview-img d-none" src="#" alt="Preview">
                </div>
            </div>
        </div>
HTML;
    }
    echo '  </div>';
    echo '</div>';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มข้อมูลแปลงอ้อย: ปี <?php echo htmlspecialchars($selected_year); ?></title>
    <link rel="icon" href="icon/unnamed.png" type="image/png">
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"> 
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.85);
            --glass-border: rgba(255, 255, 255, 0.4);
            --primary-blue: #0d6efd;
            --accent-blue: #00d2ff;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Kanit', sans-serif;
            min-height: 100vh;
            background-image: url('icon/bg.jpg'); 
            background-size: cover; 
            background-position: center center; 
            background-attachment: fixed; 
            background-repeat: no-repeat; 
            padding: 2rem 0;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            z-index: -1;
        }

        .container {
            max-width: 1000px;
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .card-header-premium {
            background: linear-gradient(135deg, #0d6efd 0%, #00d2ff 100%);
            color: white;
            padding: 2.5rem 2rem;
            border-bottom: none;
            text-align: center;
        }

        .card-header-premium h2 {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .section-title {
            font-family: 'Outfit', sans-serif;
            color: var(--primary-blue);
            font-weight: 700;
            border-left: 5px solid var(--primary-blue);
            padding-left: 15px;
            margin: 2.5rem 0 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.25rem;
        }

        .form-label {
            font-weight: 600;
            color: #444;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-control {
            border-radius: 12px;
            padding: 0.75rem 1rem;
            border: 1px solid #dee2e6;
            background: rgba(255, 255, 255, 0.9);
            transition: all 0.3s;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
            border-color: var(--primary-blue);
        }

        .upload-box {
            background: #fff;
            border: 2px dashed #dee2e6;
            border-radius: 15px;
            padding: 1rem;
            text-align: center;
            transition: all 0.3s;
            height: 100%;
        }

        .upload-box:hover {
            border-color: var(--primary-blue);
            background: rgba(13, 110, 253, 0.02);
        }

        .preview-img {
            width: 100%;
            height: 160px;
            object-fit: cover;
            border-radius: 12px;
            margin-top: 10px;
            border: 1px solid #eee;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }

        .btn-premium {
            padding: 1rem 2.5rem;
            border-radius: 15px;
            font-weight: 700;
            font-family: 'Outfit', sans-serif;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .btn-premium:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
        }

        .sub-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 1.5rem;
            border: 1px solid #f0f0f0;
            box-shadow: 0 5px 15px rgba(0,0,0,0.02);
        }

        @media (max-width: 768px) {
            body { padding: 1rem 0; }
            .container { padding: 10px; }
            .card-header-premium { padding: 1.5rem 1rem; }
            .card-header-premium h2 { font-size: 1.5rem; }
            .section-title { font-size: 1.1rem; margin: 1.5rem 0 1rem; }
            .sub-card { padding: 1rem; }
            .upload-box { padding: 0.8rem; }
            .preview-img { height: 120px; }
            .btn-premium { width: 100%; padding: 0.8rem; }
            .form-control { padding: 0.6rem 0.8rem; font-size: 14px; }
            .form-label { font-size: 14px; }
        }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="main-content">
        <div class="glass-card">
            <div class="card-header-premium">
                <h2 class="mb-0"><i class='bx bxs-file-plus me-2'></i>เพิ่มข้อมูลแปลงอ้อย</h2>
                <p class="mb-0 opacity-75">ปีการผลิต: <strong><?php echo htmlspecialchars($selected_year); ?></strong></p>
            </div>
            
            <div class="card-body p-4 p-md-5">
                <form action="insertData.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="production_year" value="<?php echo htmlspecialchars($selected_year); ?>">

                    <div class="section-title">
                        <i class='bx bxs-info-circle'></i> ข้อมูลพื้นฐานแปลง
                    </div>
                    
                    <div class="row g-4 mb-3">
                        <div class="col-md-4">
                            <label for="plot_id" class="form-label">ID แปลง <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="plot_id" name="plot_id" required placeholder="ระบุ ID แปลง">
                        </div>
                        <div class="col-md-4">
                            <label for="contract_number" class="form-label">เลขสัญญา</label>
                            <input type="text" class="form-control" id="contract_number" name="contract_number" placeholder="เลขที่สัญญา">
                        </div>
                        <div class="col-md-4">
                            <label for="quota" class="form-label">โควต้า</label>
                            <input type="text" class="form-control" id="quota" name="quota" placeholder="รหัสโควต้า">
                        </div>
                        <div class="col-md-4">
                            <label for="agency" class="form-label">หน่วยส่งเสริม</label>
                            <input type="text" class="form-control" id="agency" name="agency" placeholder="รหัสหน่วยงาน">
                        </div>
                        <div class="col-md-4">
                            <label for="emp_number" class="form-label">นักส่งเสริม</label>
                            <input type="text" class="form-control" id="emp_number" name="emp_number" placeholder="รหัสนักส่งเสริม">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">ชนิดอ้อย <span class="text-danger">*</span></label>
                            <input type="text" name="suga_type" class="form-control" required placeholder="เช่น LK92-11">
                        </div>
                        <div class="col-md-4">
                            <label for="rai_area" class="form-label">พื้นที่ (ไร่) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="rai_area" name="rai_area" min="0" step="0.01" required placeholder="0.00">
                        </div>
                        <div class="col-md-4">
                            <label for="ton_rai" class="form-label">ตันต่อ (ไร่) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="ton_rai" name="ton_rai" min="0" step="0.01" required placeholder="0.00">
                        </div>
                    </div>
                    
                    <div class="section-title">
                        <i class='bx bxs-camera'></i> ข้อมูลการประเมินและรูปภาพ
                    </div>
                    
                    <?php
                    $section1_images = [
                        "estimate_ton_1" => "ประมาณตันอ้อยก่อนเปิดหีบ (หมุด)",
                        "estimate_ton_2" => "ประมาณตันอ้อยก่อนเปิดหีบ (แปลง)",
                    ];
                    createImageUploads("ประมาณตันอ้อยก่อนเปิดหีบ", $section1_images, 'bx-map-pin');
                    ?>
                    
                    <div class="mt-4">
                        <label for="notes" class="form-label fw-bold"><i class='bx bx-edit-alt me-2'></i>หมายเหตุเพิ่มเติม</label>
                        <textarea class="form-control" name="notes" rows="4" placeholder="บันทึกข้อมูลอื่น ๆ..."></textarea>
                    </div>

                    <div class="mt-5 d-flex gap-3 justify-content-center flex-wrap">
                        <button type="submit" class="btn btn-primary btn-premium">
                            <i class="bx bxs-save me-2"></i> บันทึกข้อมูลแปลง
                        </button>
                        <button type="button" id="cancelBtn" class="btn btn-light btn-premium text-secondary">
                            <i class="bx bx-x-circle me-2"></i> ยกเลิก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('cancelBtn').addEventListener('click', function() {
        Swal.fire({
            title: 'ต้องการยกเลิกการเพิ่มข้อมูล?',
            text: "ข้อมูลที่คุณกรอกไว้จะหายไป",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#6c757d',
            cancelButtonColor: '#0d6efd',
            confirmButtonText: 'ยืนยัน, กลับหน้าแดชบอร์ด',
            cancelButtonText: 'กรอกข้อมูลต่อ',
            background: 'rgba(255, 255, 255, 0.95)',
            backdrop: `rgba(0, 0, 0, 0.4) blur(4px)`
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'dashboard.php?year=<?php echo urlencode($selected_year); ?>';
            }
        });
    });

    function previewImage(input) {
        const preview = document.getElementById(`preview-${input.id}`); 
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('d-none');
            }
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.src = "#";
            preview.classList.add('d-none');
        }
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php 
if (isset($conn) && $conn) {
    $conn->close(); 
}
?>