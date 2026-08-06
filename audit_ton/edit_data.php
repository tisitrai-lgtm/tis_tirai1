<?php
/**
 * edit_data.php - ฟอร์มแก้ไขข้อมูลแปลงอ้อย (Premium Modern UI)
 */
require_once 'db_connect.php'; 

function sanitizeFolderName($name) {
    if (empty($name)) return '';
    $name = trim($name);
    $name = str_replace(' ', '-', $name);
    $name = preg_replace('/[^\p{L}\p{N}_-]/u', '', $name); 
    $name = preg_replace('/-+/', '-', $name);
    return trim($name, '-');
}

$record_id = $_GET['id'] ?? null; 
if (empty($record_id)) {
    header("Location: index.php");
    exit;
}

$sql = "SELECT * FROM cane_plot_data WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $record_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

if (!$data) {
    echo "ไม่พบข้อมูลแปลงที่ต้องการแก้ไข";
    exit;
}

$selected_year = $data['production_year'];
$image_base_url = "ton_aoi/"; 
$sanitized_year = sanitizeFolderName($data['production_year']);
$sanitized_agency = sanitizeFolderName($data['agency']);
$sanitized_contract = sanitizeFolderName($data['contract_number']);
$sanitized_plot = sanitizeFolderName($data['plot_id']);
$basePath = "{$image_base_url}uploads/{$sanitized_year}/{$sanitized_agency}/{$sanitized_contract}/{$sanitized_plot}/";

function createImageUploadsEdit($title, $images, $currentData, $basePath, $recordId, $iconClass = 'bx-camera') {
    echo '<div class="sub-card mb-4">';
    echo '  <h5 class="fw-bold mb-3 d-flex align-items-center gap-2" style="color: #444;"><i class="bx ' . $iconClass . ' text-primary"></i> ' . $title . '</h5>';
    echo '  <div class="row g-3">'; 
    foreach ($images as $name => $details) {
        $label = $details['label'];
        $subfolder = $details['subfolder'];
        $currentImageName = $currentData[$name] ?? '';
        $isImagePresent = !empty($currentImageName);
        $currentImagePath = $isImagePresent ? $basePath . $subfolder . '/' . $currentImageName : '';

        $delete_data_attributes = 'data-id="' . htmlspecialchars($recordId) . '" ' .
                                  'data-image-type="' . htmlspecialchars($name) . '" ' .
                                  'data-production-year="' . htmlspecialchars($currentData['production_year']) . '" ' .
                                  'data-contract-number="' . htmlspecialchars($currentData['contract_number']) . '" ' .
                                  'data-plot-id="' . htmlspecialchars($currentData['plot_id']) . '"';

        $image_present_style = $isImagePresent ? 'block' : 'none';
        $file_input_style = $isImagePresent ? 'none' : 'block';

        echo <<<HTML
        <div class="col-md-6" id="{$name}_container">
            <div class="upload-box h-100 p-3">
                <label class="form-label small text-muted mb-2">$label</label>

                <div class="image-present-section" style="display: {$image_present_style};">
                    <div class="position-relative d-inline-block">
                        <img class="preview-img rounded" src="{$currentImagePath}" alt="Current Image" style="height: 120px; width: 100%; object-fit: cover;">
                        <button type="button" class="btn btn-danger btn-sm delete-image-btn position-absolute top-0 end-0 m-1 rounded-circle"
                            {$delete_data_attributes} style="width: 25px; height: 25px; padding: 0; display: flex; align-items: center; justify-content: center;">
                            <i class="bx bx-trash"></i>
                        </button>
                    </div>
                    <input type="hidden" name="existing_{$name}" value="{$currentImageName}">
                </div>

                <div class="file-input-section" style="display: {$file_input_style};">
                    <input type="file" name="$name" id="$name" class="form-control form-control-sm" accept="image/*" onchange="previewImage(this)">
                    <div class="mt-2 text-center">
                        <img id="preview-$name" class="preview-img d-none rounded" src="#" alt="Preview" style="height: 120px; width: 100%; object-fit: cover;">
                    </div>
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
    <title>แก้ไขข้อมูลแปลงอ้อย: <?php echo htmlspecialchars($data['plot_id']); ?></title>
    <link rel="icon" href="icon/2.png" type="image/png">
    
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
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

        .container { max-width: 1000px; }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .card-header-premium {
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
            color: #333;
            padding: 2.5rem 2rem;
            text-align: center;
        }

        .card-header-premium h2 {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            margin-bottom: 0.5rem;
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

        .form-label { font-weight: 600; color: #444; margin-bottom: 0.5rem; font-size: 0.95rem; }

        .form-control {
            border-radius: 12px; padding: 0.75rem 1rem; border: 1px solid #dee2e6;
            background: rgba(255, 255, 255, 0.9); transition: all 0.3s;
        }

        .form-control[readonly] { background-color: rgba(233, 236, 239, 0.7); }

        .upload-box {
            background: #fff; border: 1px solid #eee; border-radius: 15px;
            text-align: center; transition: all 0.3s;
        }

        .sub-card {
            background: #ffffff; border-radius: 18px; padding: 1.5rem;
            border: 1px solid #f0f0f0; box-shadow: 0 5px 15px rgba(0,0,0,0.02);
        }

        .btn-premium {
            padding: 1rem 2.5rem; border-radius: 15px; font-weight: 700;
            font-family: 'Outfit', sans-serif; text-transform: uppercase;
            letter-spacing: 1px; transition: all 0.3s;
        }

        .btn-premium:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }

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
    <div class="glass-card">
        <div class="card-header-premium">
            <h2 class="mb-0"><i class='bx bxs-edit-alt me-2'></i>แก้ไขข้อมูลแปลงอ้อย</h2>
            <p class="mb-0 fw-bold">ID แปลง: <?php echo htmlspecialchars($data['plot_id']); ?></p>
        </div>
        
        <div class="card-body p-4 p-md-5">
            <form action="update_data.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" id="agency_hidden" value="<?php echo htmlspecialchars($data['agency']); ?>">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($data['id']); ?>">
                <input type="hidden" name="production_year" value="<?php echo htmlspecialchars($selected_year); ?>">
                <input type="hidden" name="old_plot_id" value="<?php echo htmlspecialchars($data['plot_id']); ?>">

                <div class="section-title">
                    <i class='bx bxs-info-circle'></i> ข้อมูลพื้นฐานแปลงอ้อย
                </div>
                
                <div class="row g-4 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">ID แปลง</label>
                        <input type="text" name="plot_id" class="form-control" value="<?php echo htmlspecialchars($data['plot_id']); ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">เลขสัญญา</label>
                        <input type="text" name="contract_number" class="form-control" value="<?php echo htmlspecialchars($data['contract_number']); ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">โควต้า</label>
                        <input type="text" name="quota" class="form-control" value="<?php echo htmlspecialchars($data['quota']); ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">หน่วยส่งเสริม</label>
                        <input type="text" name="agency" class="form-control" id="agency" value="<?php echo htmlspecialchars($data['agency']); ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">นักส่งเสริม</label>
                        <input type="text" name="emp_number" class="form-control" value="<?php echo htmlspecialchars($data['emp_number']); ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">ชนิดอ้อย</label>
                        <input type="text" name="suga_type" class="form-control" value="<?php echo htmlspecialchars($data['suga_type']); ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">พื้นที่ (ไร่)</label>
                        <input type="text" name="rai_area" class="form-control" value="<?php echo htmlspecialchars($data['rai_area']); ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label for="ton_rai" class="form-label text-primary">ตันต่อ (ไร่) *</label>
                        <input type="number" class="form-control border-primary" id="ton_rai" name="ton_rai" min="0" step="0.01" value="<?php echo htmlspecialchars($data['ton_rai']); ?>" required>
                    </div>
                </div>
                
                <div class="section-title">
                    <i class='bx bxs-camera'></i> รูปภาพการประเมิน
                </div>
                
                <?php
                $section1_images = [
                    "estimate_ton_1" => ["label" => "1 ประมาณตันอ้อยก่อนเปิดหีบ รูปหมุด", "subfolder" => "estimate_ton"],
                    "estimate_ton_2" => ["label" => "2 ประมาณตันอ้อยก่อนเปิดหีบ รูปอ้อย", "subfolder" => "estimate_ton"],
                ];
                createImageUploadsEdit("การประมาณตันอ้อยก่อนเปิดหีบ", $section1_images, $data, $basePath, $data['id'], 'bx-map-pin');
                ?>
                
                <div class="mt-4">
                    <label for="notes" class="form-label fw-bold"><i class='bx bx-edit-alt me-2'></i>หมายเหตุเพิ่มเติม</label>
                    <textarea class="form-control" name="notes" rows="4"><?php echo htmlspecialchars($data['notes'] ?? ''); ?></textarea>
                </div>

                <div class="mt-5 d-flex gap-3 justify-content-center flex-wrap">
                    <button type="submit" class="btn btn-warning btn-premium px-5">
                        <i class="bx bxs-save me-2"></i> บันทึกการแก้ไข
                    </button>
                    <button type="button" id="cancelBtn" class="btn btn-light btn-premium text-secondary">
                        <i class="bx bx-arrow-back me-2"></i> ยกเลิก
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script> 
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.getElementById('cancelBtn').addEventListener('click', function() {
        Swal.fire({
            title: 'ยกเลิกการแก้ไข?',
            text: "ข้อมูลที่คุณแก้ไขจะไม่ถูกบันทึก",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#6c757d',
            cancelButtonColor: '#ffc107',
            confirmButtonText: 'ใช่, ทิ้งการแก้ไข',
            cancelButtonText: 'แก้ไขต่อ',
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

    $(document).ready(function() {
        $(document).on('click', '.delete-image-btn', function() {
            var button = $(this);
            var id = button.data('id'); 
            var imageType = button.data('image-type'); 
            var productionYear = button.data('production-year');
            var contractNumber = button.data('contract-number');
            var plotId = button.data('plot-id');
            var agency = $('#agency').val(); 

            if (confirm('คุณแน่ใจหรือไม่ว่าต้องการลบรูปภาพนี้?')) {
                $.ajax({
                    url: 'delete_image_ajax.php', 
                    type: 'POST',
                    dataType: 'json', 
                    data: { id: id, image_type: imageType, production_year: productionYear, contract_number: contractNumber, plot_id: plotId, agency: agency },
                    success: function(response) {
                        if (response.success) {
                            var container = button.closest('[id$="_container"]');
                            container.find('.image-present-section').hide();
                            container.find('.file-input-section').show();
                            container.find('input[type="hidden"][name^="existing_"]').val('');
                        } else {
                            alert('เกิดข้อผิดพลาด: ' + response.message);
                        }
                    }
                });
            }
        });
    });
</script>
</body>
</html>
<?php if (isset($conn) && $conn) $conn->close(); ?>