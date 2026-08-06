<?php
// user_edit_data.php - ฟอร์มแก้ไขข้อมูล
require_once 'db_connect.php'; // ตรวจสอบให้แน่ใจว่าไฟล์นี้ถูกต้องและอยู่ในพาธที่เข้าถึงได้

// 🚨 ฟังก์ชันสำหรับ sanitize ชื่อโฟลเดอร์ (ใช้ตามที่ผู้ใช้กำหนด)
function sanitizeFolderName($name) {
    if (empty($name)) {
        return '';
    }
    // ใช้ mb_convert_encoding เพื่อจัดการอักขระไทยให้ถูกต้อง
    $name = mb_convert_encoding($name, 'UTF-8', 'auto');
    $name = str_replace(' ', '-', $name);
    // อนุญาตเฉพาะตัวอักษร ตัวเลข ขีดล่าง และขีดกลาง
    $name = preg_replace('/[^\p{L}\p{N}_-]/u', '', $name); 
    $name = preg_replace('/-+/', '-', $name);
    $name = trim($name, '-');
    return $name;
}

// ฟังก์ชันสำหรับสร้างพาธรูปภาพเต็ม (ไม่ถูกเรียกใช้โดยตรง แต่ใช้แนวคิดในการสร้าง $basePath)
function getImagePath($filename, $production_year, $contract_number, $plot_id, $image_type_folder) {
    if (empty($filename)) {
        return '';
    }
    $sanitized_production_year = sanitizeFolderName($production_year);
    $sanitized_contract_number = sanitizeFolderName($contract_number);
    $sanitized_plot_id = sanitizeFolderName($plot_id);
    
    $basePath = "uploads/{$sanitized_production_year}/{$sanitized_contract_number}/{$sanitized_plot_id}/";
    return $basePath . $image_type_folder . '/' . $filename;
}


$plot_id_to_edit = $_GET['plot_id'] ?? null; // รับ plot_id จาก URL (ส่งมาจาก dashboard.php)

if (empty($plot_id_to_edit)) {
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

// สร้าง Base Path สำหรับรูปภาพเดิมโดยใช้ฟังก์ชัน sanitizeFolderName
$image_base_url = "ton_aoi/"; // ตรวจสอบว่า Path ส่วนต้นนี้ถูกต้อง
$sanitized_year = sanitizeFolderName($data['production_year']);
$sanitized_agency = sanitizeFolderName($data['agency']);
$sanitized_contract = sanitizeFolderName($data['contract_number']);
$sanitized_plot = sanitizeFolderName($data['plot_id']);

$basePath = "{$image_base_url}uploads/{$sanitized_year}/{$sanitized_agency}/{$sanitized_contract}/{$sanitized_plot}/";


/**
 * ฟังก์ชันสำหรับสร้างส่วนหัวข้อและช่องอัปโหลดรูปภาพ 2 รูปต่อแถว
 * ในฟอร์มแก้ไขต้องแสดงรูปภาพเดิม พร้อมปุ่มลบรูปภาพ
 * @param string $title หัวข้อของส่วน
 * @param array $images อาร์เรย์ของชื่อฟิลด์ => ลาเบล => โฟลเดอร์ย่อย
 * @param array $currentData ข้อมูลแถวปัจจุบันจากฐานข้อมูล
 * @param string $basePath Path พื้นฐานของรูปภาพ
 * @param int $recordId ID ของ record ใน DB (primary key)
 */
function createImageUploadsEdit($title, $images, $currentData, $basePath, $recordId) {
    echo '<h5 class="text-secondary mt-4 mb-3" style="border-left: 5px solid #0d6efd; padding-left: 10px;">' . $title . '</h5>';
    echo '<div class="row g-3">'; 
    foreach ($images as $name => $details) {
        $label = $details['label'];
        $subfolder = $details['subfolder'];
        $currentImageName = $currentData[$name] ?? ''; // ชื่อไฟล์รูปภาพจากฐานข้อมูล
        $currentImagePath = '';
        
        $isImagePresent = !empty($currentImageName);
        if ($isImagePresent) {
            $currentImagePath = $basePath . $subfolder . '/' . $currentImageName;
        }

        // กำหนดค่า data-* attribute สำหรับปุ่มลบ
        $delete_data_attributes = 'data-id="' . htmlspecialchars($recordId) . '" ' .
                                  'data-image-type="' . htmlspecialchars($name) . '" ' . // ใช้ชื่อคอลัมน์เป็น image-type
                                  'data-production-year="' . htmlspecialchars($currentData['production_year']) . '" ' .
                                  'data-contract-number="' . htmlspecialchars($currentData['contract_number']) . '" ' .
                                  'data-plot-id="' . htmlspecialchars($currentData['plot_id']) . '"';


        // ใช้ตัวแปรเพื่อกำหนด style display แทนการใช้ ternary op ใน string โดยตรง
        $image_present_style = $isImagePresent ? 'block' : 'none';
        $file_input_style = $isImagePresent ? 'none' : 'block';

        echo <<<HTML
        <div class="col-md-6 d-flex flex-column" id="{$name}_container">
            <label for="$name" class="form-label">$label</label>

            <div class="image-present-section" style="display: {$image_present_style};">
                <div class="position-relative d-inline-block">
                    <img class="preview-img" src="{$currentImagePath}" alt="Current Image">
                    <button type="button" class="btn btn-danger btn-sm delete-image-btn position-absolute top-0 end-0" style="margin: 5px; line-height: 1; padding: 5px 8px;"
                        {$delete_data_attributes}>
                        &times;
                    </button>
                </div>
                <input type="hidden" name="existing_{$name}" value="{$currentImageName}">
            </div>

            <div class="file-input-section" style="display: {$file_input_style};">
                <input type="file" name="$name" id="$name" class="form-control" accept="image/*" onchange="previewImage(this)">
                <img id="preview-$name" class="preview-img d-none" src="#" alt="Preview">
            </div>
        </div>
        HTML;
    }
    echo '</div>'; // ปิด row
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อรูปภาพประมานาณตัน</title>
    <link rel="icon" href="icon/2.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="style_form.css" rel="stylesheet">
    <style>
        .preview-img {
            max-width: 100%;
            height: auto;
            margin-top: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
    .preview-img {
        /* ขนาดสูงสุดที่ต้องการ */
        max-width: 200px;
        max-height: 200px; 
        width: auto; /* รักษาอัตราส่วนของรูปภาพ */
        height: auto; /* รักษาอัตราส่วนของรูปภาพ */
        
        margin-top: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        /* เพื่อให้ภาพที่โหลดใหม่ไม่ยืดออกเมื่อยังไม่มี src */
        object-fit: contain; 
    }
    /* กำหนดให้ส่วนที่แสดงรูปภาพเดิมอยู่ด้านบนของ input file */
    .image-present-section {
        margin-bottom: 10px; 
    }
    /* 🚨 ปรับปุ่มลบให้อยู่ในตำแหน่งที่เหมาะสมบนมือถือ */
        .image-present-section .position-relative {
            max-width: 100%; /* ทำให้ส่วนของรูปภาพไม่เกินขอบ */
        }
        .delete-image-btn {
        z-index: 10;
        
        /* กำหนดขนาดคงที่ */
        width: 30px; 
        height: 30px;
        padding: 0; /* ลบ padding เดิมออก */
        
        /* จัดข้อความตรงกลาง (ใช้ flexbox) */
        display: flex;
        align-items: center;
        justify-content: center;
        
        /* ทำให้เป็นวงกลม */
        border-radius: 50% !important; 
        
        /* ปรับตำแหน่งใหม่ */
        margin: 5px; 
        font-size: 1.2rem; /* ทำให้สัญลักษณ์ X ใหญ่ขึ้น */
        line-height: 2; /* เพื่อให้จัดกึ่งกลางได้สมบูรณ์ */
        }

    </style>
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4 text-center">แก้ไขข้อมูลแปลงอ้อย ID: **<?php echo htmlspecialchars($data['plot_id']); ?>** (ปี <?php echo htmlspecialchars($selected_year); ?>)</h2>
        <form id="editForm" action="user_update_data.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($data['id']); ?>">
        <input type="hidden" name="production_year" value="<?php echo htmlspecialchars($selected_year); ?>">
        <input type="hidden" name="old_plot_id" value="<?php echo htmlspecialchars($data['plot_id']); ?>">


      <h4 class="text-primary">ข้อมูลแปลง</h4>
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <label for="plot_id" class="form-label">ID แปลง</label>
        <input type="text" class="form-control bg-light" id="plot_id" name="plot_id" value="<?php echo htmlspecialchars($data['plot_id']); ?>" required readonly>
    </div>
    <div class="col-md-6">
        <label for="contract_number" class="form-label">เลขสัญญา</label>
        <input type="text" class="form-control bg-light" id="contract_number" name="contract_number" value="<?php echo htmlspecialchars($data['contract_number']); ?>" readonly>
    </div>
    <div class="col-md-6">
        <label for="quota" class="form-label">โควต้า</label>
        <input type="text" class="form-control bg-light" id="quota" name="quota" value="<?php echo htmlspecialchars($data['quota']); ?>" readonly>
    </div>
    <div class="col-md-6">
        <label for="agency" class="form-label">หน่วยส่งเสริม</label>
        <input type="text" class="form-control bg-light" id="agency" name="agency" value="<?php echo htmlspecialchars($data['agency']); ?>" readonly>
    </div>
    <div class="col-md-6">
        <label for="emp_number" class="form-label">นักส่งเสริม</label>
        <input type="text" class="form-control bg-light" id="emp_number" name="emp_number" value="<?php echo htmlspecialchars($data['emp_number']); ?>" readonly>
    </div>
    <div class="col-md-6">
        <label class="form-label"><i class="bi bi-tree-fill"></i> ชนิดอ้อย</label>
        <input type="text" name="suga_type" class="form-control bg-light" value="<?php echo htmlspecialchars($data['suga_type']); ?>" required readonly>
    </div>
    <div class="col-md-6">
        <label for="rai_area" class="form-label">พื้นที่ (ไร่)</label>
        <input type="number" class="form-control bg-light" id="rai_area" name="rai_area" min="0" value="<?php echo htmlspecialchars($data['rai_area']); ?>" required readonly>
    </div>
</div>
        
        <h4 class="text-primary mt-5">ข้อมูลการประเมินตันอ้อย</h4>
        <?php
        $section2_images = [
            "evaluate_ton_1" => ["label" => "ประเมินตันอ้อย รูปที่ 1", "subfolder" => "evaluate_ton"],
            "evaluate_ton_2" => ["label" => "ประเมินตันอ้อย รูปที่ 2", "subfolder" => "evaluate_ton"],
        ];
        createImageUploadsEdit("1. ประเมินตันอ้อยเบื่องต้น", $section2_images, $data, $basePath, $data['id']);
        ?>
        
        <?php
        $section1_images = [
            "estimate_ton_1" => ["label" => "ประมาณตันอ้อย รูปที่ 1", "subfolder" => "estimate_ton"],
            "estimate_ton_2" => ["label" => "ประมาณตันอ้อย รูปที่ 2", "subfolder" => "estimate_ton"],
        ];
        createImageUploadsEdit("2. ประมาณตันอ้อยก่อนเปิดหีบ", $section1_images, $data, $basePath, $data['id']);
        ?>
        
        

        <hr class="mt-4 mb-3">
        <h4 class="text-primary">3. อ้อยคงเหลือ</h4>

        <?php
        $section3_1_images = [
            "remaining_cane_1_img_1" => ["label" => "อ้อยคงเหลือ ครั้งที่ 1 รูปที่ 1", "subfolder" => "remaining_cane_1"],
            "remaining_cane_1_img_2" => ["label" => "อ้อยคงเหลือ ครั้งที่ 1 รูปที่ 2", "subfolder" => "remaining_cane_1"],
        ];
        createImageUploadsEdit("อ้อยคงเหลือ ครั้งที่ 1", $section3_1_images, $data, $basePath, $data['id']);
        ?>
        
        <?php
        $section3_2_images = [
            "remaining_cane_2_img_1" => ["label" => "อ้อยคงเหลือ ครั้งที่ 2 รูปที่ 1", "subfolder" => "remaining_cane_2"],
            "remaining_cane_2_img_2" => ["label" => "อ้อยคงเหลือ ครั้งที่ 2 รูปที่ 2", "subfolder" => "remaining_cane_2"],
        ];
        createImageUploadsEdit("อ้อยคงเหลือ ครั้งที่ 2", $section3_2_images, $data, $basePath, $data['id']);
        ?>
        
        <?php
        $section3_3_images = [
            "remaining_cane_3_img_1" => ["label" => "อ้อยคงเหลือ ครั้งที่ สุดท้าย รูปที่ 1", "subfolder" => "remaining_cane_3"],
            "remaining_cane_3_img_2" => ["label" => "อ้อยคงเหลือ ครั้งที่ สุดท้าย รูปที่ 2", "subfolder" => "remaining_cane_3"],
        ];
        createImageUploadsEdit("อ้อยคงเหลือ ครั้งที่ สุดท้าย", $section3_3_images, $data, $basePath, $data['id']);
        ?>
        
        <div class="mt-5">
            <label for="notes" class="form-label">หมายเหตุเพิ่มเติม</label>
            <textarea class="form-control" name="notes" rows="3"><?php echo htmlspecialchars($data['notes'] ?? ''); ?></textarea>
        </div>

        <div class="mt-4 mb-5 text-center">
            <button type="submit" class="btn btn-warning btn-lg" id="submit-btn">อัปเดตข้อมูล 🔄</button>
            <a href="user_dashboard.php?year=<?php echo htmlspecialchars($selected_year); ?>&agency=<?php echo htmlspecialchars($selected_agency); ?>" class="btn btn-secondary btn-lg">ยกเลิก</a>
        </div>
    </form>
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
    // 🚨 AJAX SUBMISSION: จัดการการส่งฟอร์มเพื่อแสดงลูกเล่น
    // ==============================================
    var $form = $('#editForm'); 
    var $submitBtn = $('#submit-btn'); 
    var originalBtnText = $submitBtn.html();

    $form.on('submit', function(e) {
        e.preventDefault();
        
        // 1. แสดงสถานะกำลังบันทึก
        $submitBtn.attr('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> กำลังบันทึก...'); 
        $submitBtn.removeClass('btn-success btn-danger').addClass('btn-primary'); // รีเซ็ตสีเป็นสีหลัก/สีน้ำเงิน

        var formData = new FormData(this);
        
        // 2. ส่งข้อมูลด้วย AJAX
        $.ajax({
            // 🚨 ต้องใช้ URL ที่ไฟล์ user_update_data.php ส่ง JSON Response กลับมา
            url: 'user_update_data.php', 
            type: 'POST',
            data: formData,
            dataType: 'json',
            contentType: false, // สำคัญสำหรับ FormData
            processData: false, // สำคัญสำหรับ FormData
            
            success: function(response) {
                if (response.success) {
                    // 3. แสดงสถานะบันทึกสำเร็จ (เครื่องหมายถูก)
                    $submitBtn.html('<i class="fas fa-check"></i> บันทึกสำเร็จ!').removeClass('btn-primary').addClass('btn-success'); 
                    
                    // 4. Redirect ไปยัง dashboard.php พร้อมปีการผลิตและ Agency
                    var redirectUrl = 'user_dashboard.php?year=' + encodeURIComponent(response.year) + 
                                  '<?php if (!empty($selected_agency)): ?>' + '&agency=' + encodeURIComponent(response.agency) + '<?php endif; ?>' + 
                                  '&success=update';
                    setTimeout(function() {
                        window.location.href = redirectUrl;
                    }, 1500); // หน่วงเวลา 1.5 วินาที
                    
                } else {
                    // 3. แสดงสถานะ Error
                    alert('บันทึกไม่สำเร็จ: ' + response.message);
                    $submitBtn.attr('disabled', false).html(originalBtnText).removeClass('btn-success').addClass('btn-danger'); 
                    setTimeout(function() {
                        $submitBtn.removeClass('btn-danger').addClass('btn-primary');
                    }, 3000); // เปลี่ยนสีกลับใน 3 วิ
                }
            },
            error: function(xhr, status, error) {
                // 3. แสดงสถานะ Error ทางเทคนิค
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์ กรุณาลองใหม่.');
                console.error("AJAX Error:", xhr.responseText);
                $submitBtn.attr('disabled', false).html(originalBtnText).removeClass('btn-success').addClass('btn-primary'); 
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
        
        var agency = $('#agency').val(); // 👈 ดึงค่า agency จาก input field
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
                    agency: agency // 👈 ส่ง agency ไปด้วย
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
// ตรวจสอบว่า $conn ถูกกำหนดค่าก่อนเรียกใช้ close()
if (isset($conn) && $conn) {
    $conn->close();   
} 
?>