<?php
// user_edit_data.php - ฟอร์มแก้ไขข้อมูล
require_once 'db_connect.php'; 

// 🚨 ฟังก์ชันสำหรับ sanitize ชื่อโฟลเดอร์ (ใช้ตามที่ผู้ใช้กำหนด)
function sanitizeFolderName($name) {
    if (empty($name)) {
        return '';
    }
    // ใช้ mb_convert_encoding เพื่อจัดการอักขระไทยให้ถูกต้อง
    if (function_exists('mb_convert_encoding')) {
        $name = mb_convert_encoding($name, 'UTF-8', 'auto');
    }
    
    $name = trim($name);
    $name = str_replace(' ', '-', $name);
    // อนุญาตเฉพาะตัวอักษร ตัวเลข ขีดล่าง และขีดกลาง
    $name = preg_replace('/[^\p{L}\p{N}_-]/u', '', $name); 
    $name = preg_replace('/-+/', '-', $name);
    $name = trim($name, '-');
    return $name;
}

// โค้ดส่วนดึงข้อมูล...
$plot_id_to_edit = $_GET['plot_id'] ?? null; 

if (empty($plot_id_to_edit)) {
    // โค้ดจัดการ Error
    echo '<!DOCTYPE html>
          <html lang="th">
          <head><meta charset="UTF-8"><title>Error</title>
          <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
          <body class="d-flex justify-content-center align-items-center vh-100">
          <div class="alert alert-danger text-center">
            <h2>ไม่พบ ID แปลงที่ต้องการแก้ไข</h2>
            <a href="index.php" class="btn btn-primary mt-3">กลับหน้าหลัก</a>
          </div></body></html>';
    exit;
}

// 1. ดึงข้อมูลปัจจุบันของแปลงที่ต้องการแก้ไข
$sql = "SELECT * FROM cane_plot_data WHERE plot_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $plot_id_to_edit);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

if (!$data) {
    // โค้ดจัดการ Error
    echo '<!DOCTYPE html>
          <html lang="th">
          <head><meta charset="UTF-8"><title>Error</title>
          <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
          <body class="d-flex justify-content-center align-items-center vh-100">
          <div class="alert alert-warning text-center">
            <h2>ไม่พบข้อมูลสำหรับ ID แปลง: ' . htmlspecialchars($plot_id_to_edit) . '</h2>
            <a href="index.php" class="btn btn-primary mt-3">กลับหน้าหลัก</a>
          </div></body></html>';
    exit;
}

$selected_year = $data['production_year'];
$selected_agency = $data['agency']; // ดึงค่า Agency สำหรับใช้ในการ Redirect

// สร้าง Base Path สำหรับรูปภาพเดิม
$image_base_url = "ton_aoi/"; // ตรวจสอบว่า Path ส่วนต้นนี้ถูกต้อง
$sanitized_year = sanitizeFolderName($data['production_year']);
$sanitized_agency = sanitizeFolderName($data['agency']);
$sanitized_contract = sanitizeFolderName($data['contract_number']);
$sanitized_plot = sanitizeFolderName($data['plot_id']);

$basePath = "{$image_base_url}uploads/{$sanitized_year}/{$sanitized_agency}/{$sanitized_contract}/{$sanitized_plot}/";


/**
 * ฟังก์ชันสำหรับสร้างส่วนหัวข้อและช่องอัปโหลดรูปภาพ 2 รูปต่อแถว (ปรับใช้ Card Style)
 */
function createImageUploadsEdit($title, $images, $currentData, $basePath, $recordId, $iconClass = 'bi-camera-fill') {
    echo '<div class="card shadow-sm mb-4 border-success-subtle">';
    // ใช้ bg-success-subtle/text-success สำหรับหัว Card ให้เข้ากับ Dashboard สีเขียว
    echo '  <div class="card-header bg-success-subtle text-success"><h5 class="mb-0 fw-bold"><i class="bi ' . $iconClass . ' me-2"></i>' . $title . '</h5></div>';
    echo '  <div class="card-body">';
    echo '      <div class="row g-4">'; 
    foreach ($images as $name => $details) {
        $label = $details['label'];
        $subfolder = $details['subfolder'];
        $currentImageName = $currentData[$name] ?? '';
        $currentImagePath = '';
        
        $isImagePresent = !empty($currentImageName);
        if ($isImagePresent) {
            $currentImagePath = $basePath . $subfolder . '/' . $currentImageName;
        }

        $delete_data_attributes = 'data-id="' . htmlspecialchars($recordId) . '" ' .
                                  'data-image-type="' . htmlspecialchars($name) . '" ' .
                                  'data-production-year="' . htmlspecialchars($currentData['production_year']) . '" ' .
                                  'data-contract-number="' . htmlspecialchars($currentData['contract_number']) . '" ' .
                                  'data-plot-id="' . htmlspecialchars($currentData['plot_id']) . '"';

        $image_present_style = $isImagePresent ? 'block' : 'none';
        $file_input_style = $isImagePresent ? 'none' : 'block';

        echo <<<HTML
        <div class="col-12 col-md-6 d-flex flex-column" id="{$name}_container">
            <label for="$name" class="form-label text-secondary small mb-1"><i class="bi bi-upload me-1"></i> $label</label>

            <div class="image-present-section p-3 border rounded bg-light" style="display: {$image_present_style};">
                <div class="position-relative d-inline-block image-wrapper">
                    <img class="preview-img img-fluid rounded shadow-sm" src="{$currentImagePath}" alt="Current Image" onclick="window.open(this.src)">
                    <button type="button" class="btn btn-danger btn-sm delete-image-btn position-absolute top-0 end-0"
                        {$delete_data_attributes}>
                        <i class="bi bi-trash-fill"></i>
                    </button>
                </div>
                <input type="hidden" name="existing_{$name}" value="{$currentImageName}">
            </div>

            <div class="file-input-section" style="display: {$file_input_style};">
                <input type="file" name="$name" id="$name" class="form-control form-control-sm" accept="image/*" onchange="previewImage(this)">
                <img id="preview-$name" class="preview-img d-none img-fluid rounded" src="#" alt="Preview">
            </div>
        </div>
        HTML;
    }
    echo '      </div>'; // ปิด row
    echo '  </div>'; // ปิด card-body
    echo '</div>'; // ปิด card
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อมูลแปลงอ้อย: <?php echo htmlspecialchars($data['plot_id']); ?></title>
    <link rel="icon" href="icon/2.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"> 
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* ----------------------------------------------------------- */
        /* BASE STYLES & FONT */
        /* ----------------------------------------------------------- */
        body { 
            background-color: #f0f4f8; /* สีพื้นหลังอ่อน ๆ */
            font-family: 'Kanit', sans-serif; 
            min-height: 100vh;
            background-image: url('icon/bg.jpg'); 
            background-size: cover; 
            background-position: center center; 
            background-attachment: fixed; 
            background-repeat: no-repeat; 

        }
        .main-card {
            background-color: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            margin-bottom: 50px;
        }
        h2 { font-weight: 700; color: #1a4d7c; }
        .form-label { font-weight: 500; color: #333; }
        
        /* ----------------------------------------------------------- */
        /* SECTION TITLES */
        /* ----------------------------------------------------------- */
        .section-title {
            color: #0d6efd; /* สีน้ำเงินหลัก */
            border-bottom: 3px solid #198754; /* เส้นขีดใต้สีเขียว */
            padding-bottom: 5px;
            margin-top: 30px;
            margin-bottom: 25px;
            font-weight: 600;
        }

        /* ----------------------------------------------------------- */
        /* IMAGE & DELETE BUTTON STYLES */
        /* ----------------------------------------------------------- */
        .preview-img {
            max-width: 250px; 
            max-height: 250px; 
            width: auto; 
            height: auto; 
            margin-top: 5px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            object-fit: cover; 
            display: block;
            transition: transform 0.2s;
            cursor: pointer;
        }
        .preview-img:hover {
             transform: scale(1.03);
        }
        .image-wrapper {
            max-width: 250px; 
            width: 100%;
        }

        .delete-image-btn {
            z-index: 10;
            width: 30px; 
            height: 30px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50% !important; 
            margin: 5px; 
            font-size: 0.9rem;
            line-height: 1;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }
        .form-control[readonly] {
            background-color: #e9ecef; /* สีเทาอ่อนสำหรับช่องที่อ่านอย่างเดียว */
            border-style: dashed;
        }
        
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="main-card">
        <h2 class="mb-5 text-center text-primary">
            <i class="bi bi-person-fill-gear me-2"></i> แก้ไขข้อมูลแปลงอ้อย
        </h2>
        
        <div class="alert alert-info text-center py-2 mb-4 fw-bold">
            ID แปลง: **<?php echo htmlspecialchars($data['plot_id']); ?>** (ปี <?php echo htmlspecialchars($selected_year); ?> | หน่วยส่งเสริม: <?php echo htmlspecialchars($data['agency']); ?>)
        </div>

        <form id="editForm" action="user_update_data.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($data['id']); ?>">
            <input type="hidden" name="production_year" value="<?php echo htmlspecialchars($selected_year); ?>">
            <input type="hidden" name="old_plot_id" value="<?php echo htmlspecialchars($data['plot_id']); ?>">


            <h4 class="section-title">
                <i class="bi bi-info-circle-fill me-2"></i> ข้อมูลพื้นฐานแปลงอ้อย (ไม่สามารถแก้ไขได้)
            </h4>
            <div class="row g-3 mb-5">
                <div class="col-12 col-md-6">
                    <label for="plot_id" class="form-label">ID แปลง</label>
                    <input type="text" class="form-control" id="plot_id" name="plot_id" value="<?php echo htmlspecialchars($data['plot_id']); ?>" required readonly>
                </div>
                <div class="col-12 col-md-6">
                    <label for="contract_number" class="form-label">เลขสัญญา</label>
                    <input type="text" class="form-control" id="contract_number" name="contract_number" value="<?php echo htmlspecialchars($data['contract_number']); ?>" readonly>
                </div>
                <div class="col-12 col-md-6">
                    <label for="quota" class="form-label">โควต้า</label>
                    <input type="text" class="form-control" id="quota" name="quota" value="<?php echo htmlspecialchars($data['quota']); ?>" readonly>
                </div>
                <div class="col-12 col-md-6">
                    <label for="agency" class="form-label">หน่วยส่งเสริม</label>
                    <input type="text" class="form-control" id="agency" name="agency" value="<?php echo htmlspecialchars($data['agency']); ?>" readonly>
                </div>
                <div class="col-12 col-md-6">
                    <label for="emp_number" class="form-label">นักส่งเสริม</label>
                    <input type="text" class="form-control" id="emp_number" name="emp_number" value="<?php echo htmlspecialchars($data['emp_number']); ?>" readonly>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label"><i class="bi bi-tree-fill"></i> ชนิดอ้อย</label>
                    <input type="text" name="suga_type" class="form-control" value="<?php echo htmlspecialchars($data['suga_type']); ?>" required readonly>
                </div>
                <div class="col-12 col-md-6">
                    <label for="rai_area" class="form-label">พื้นที่ (ไร่)</label>
                    <input type="number" class="form-control" id="rai_area" name="rai_area" min="0" value="<?php echo htmlspecialchars($data['rai_area']); ?>" required readonly>
                </div>
            </div>
            
            <h4 class="section-title">
                <i class="bi bi-camera-fill me-2"></i> ข้อมูลรูปภาพที่สามารถแก้ไขได้
            </h4>
            
            <?php
            $section1_images = [
                "estimate_ton_1" => ["label" => "ประมาณตันอ้อย รูปหมุด (1/2)", "subfolder" => "estimate_ton"],
                "estimate_ton_2" => ["label" => "ประมาณตันอ้อย รูปอ้อย (2/2)", "subfolder" => "estimate_ton"],
            ];
            createImageUploadsEdit("ประมาณตันอ้อยก่อนเปิดหีบ", $section1_images, $data, $basePath, $data['id'], 'bi-bar-chart-fill');
            ?>
            
            <div class="mt-5 mb-5 p-4 border rounded bg-light">
                <label for="notes" class="form-label fs-5 text-secondary">
                    <i class="bi bi-chat-dots-fill me-1"></i> หมายเหตุเพิ่มเติม
                </label>
                <textarea class="form-control" name="notes" rows="4" placeholder="บันทึกข้อมูลอื่น ๆ ที่เกี่ยวข้องกับแปลงนี้"><?php echo htmlspecialchars($data['notes'] ?? ''); ?></textarea>
            </div>

            <div class="mt-4 mb-3 text-center">
                <button type="submit" class="btn btn-success btn-lg shadow-sm me-3 fw-bold" style="min-width: 180px;" id="submit-btn">
                    <i class="bi bi-save-fill me-2"></i> บันทึกการแก้ไข
                </button>
                <a href="user_dashboard.php?year=<?php echo htmlspecialchars($selected_year); ?>&agency=<?php echo htmlspecialchars($selected_agency); ?>" class="btn btn-secondary btn-lg shadow-sm" style="min-width: 180px;">
                    <i class="bi bi-arrow-left-circle-fill me-2"></i> ยกเลิก / กลับ
                </a>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script> 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    /**
     * JavaScript สำหรับแสดงตัวอย่างรูปภาพเมื่อมีการเลือกไฟล์
     */
    function previewImage(input) {
        // หาองค์ประกอบของรูปภาพตัวอย่างที่อยู่ถัดไป
        const preview = $(input).closest('.file-input-section').find('.preview-img').get(0); 

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

$(document).ready(function() {
    
    // ==============================================
    // AJAX SUBMISSION: จัดการการส่งฟอร์มเพื่อแสดงลูกเล่น
    // ==============================================
    var $form = $('#editForm'); 
    var $submitBtn = $('#submit-btn'); 
    var originalBtnText = $submitBtn.html();

    $form.on('submit', function(e) {
        e.preventDefault();
        
        // 1. แสดงสถานะกำลังบันทึก
        $submitBtn.attr('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> กำลังบันทึก...'); 
        $submitBtn.removeClass('btn-success').addClass('btn-primary'); 

        var formData = new FormData(this);
        
        // 2. ส่งข้อมูลด้วย AJAX
        $.ajax({
            // 🚨 ต้องใช้ URL ที่ไฟล์ user_update_data.php ส่ง JSON Response กลับมา
            url: 'user_update_data.php', 
            type: 'POST',
            data: formData,
            dataType: 'json',
            contentType: false, 
            processData: false, 
            
            success: function(response) {
                if (response.success) {
                    // 3. แสดงสถานะบันทึกสำเร็จ (เครื่องหมายถูก)
                    $submitBtn.html('<i class="bi bi-check-circle-fill me-1"></i> บันทึกสำเร็จ!').removeClass('btn-primary').addClass('btn-success'); 
                    
                    // 4. Redirect ไปยัง dashboard.php พร้อมปีการผลิตและ Agency 
                    var redirectUrl = 'user_dashboard.php?year=' + encodeURIComponent(response.year) + 
                                      '&agency=' + encodeURIComponent(response.agency) + 
                                      '&status=success'; // เปลี่ยน error เป็น status=success
                    setTimeout(function() {
                        window.location.href = redirectUrl;
                    }, 1500); 
                    
                } else {
                    // 3. แสดงสถานะ Error
                    alert('บันทึกไม่สำเร็จ: ' + response.message);
                    $submitBtn.attr('disabled', false).html(originalBtnText).removeClass('btn-primary').addClass('btn-danger'); 
                    setTimeout(function() {
                        $submitBtn.removeClass('btn-danger').addClass('btn-success'); 
                    }, 3000); 
                }
            },
            error: function(xhr, status, error) {
                // 3. แสดงสถานะ Error ทางเทคนิค
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์ กรุณาลองใหม่.');
                console.error("AJAX Error:", status, error, xhr.responseText);
                $submitBtn.attr('disabled', false).html(originalBtnText).removeClass('btn-primary').addClass('btn-success'); 
            }
        });
    });


    // ==============================================
    // ฟังก์ชันเดิม: จัดการการลบรูปภาพ (ใช้ AJAX เดิม)
    // ==============================================
    $(document).on('click', '.delete-image-btn', function() {
        var button = $(this);
        var id = button.data('id'); 
        var imageType = button.data('image-type'); 
        var productionYear = button.data('production-year');
        var contractNumber = button.data('contract-number');
        var plotId = button.data('plot-id');
        
        var agency = $('#agency').val(); 
        if (!agency) {
            alert('ไม่สามารถลบรูปภาพได้: ไม่พบข้อมูลหน่วยส่งเสริม (Agency).');
            return;
        }

        if (confirm('คุณแน่ใจหรือไม่ว่าต้องการลบรูปภาพนี้?')) {
            $.ajax({
                url: 'delete_image_ajax.php', 
                type: 'POST',
                dataType: 'json', 
                data: {
                    id: id,
                    image_type: imageType,
                    production_year: productionYear,
                    contract_number: contractNumber,
                    plot_id: plotId,
                    agency: agency 
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        // ซ่อน/แสดงส่วนต่างๆ
                        button.closest('.image-present-section').hide();
                        var container = button.closest('[id$="_container"]');
                        container.find('.file-input-section').show();
                        container.find('input[type="hidden"][name^="existing_"]').val('');
                        container.find('input[type="file"]').val('');
                         // ซ่อน preview image ที่เคยแสดงไว้จากการเลือกไฟล์
                         container.find('img[id^="preview-"]').addClass('d-none');

                    } else {
                        alert('เกิดข้อผิดพลาด: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error: ", status, error, xhr.responseText);
                    alert('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์ กรุณาลองใหม่.');
                }
            });
        }
    });
});

</script>
</body>
</html>
<?php 
if (isset($conn) && $conn) {
    $conn->close();
} 
?>