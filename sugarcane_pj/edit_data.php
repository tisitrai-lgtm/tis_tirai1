<?php
require_once 'db_connect.php';

function sanitizeFolderName($name) {
    if (empty($name)) return 'unspecified';
    // ปรับให้รองรับภาษาไทยครบถ้วนรวมถึงสระและวรรณยุกต์ (\p{M})
    $name = preg_replace('/[^\p{L}\p{M}\p{N}_-]/u', '', str_replace(' ', '-', $name));
    return trim($name, '-');
}

function getImagePath($filename, $production_year, $agency, $contract_number, $plot_id, $image_type_folder) {
    if (empty($filename)) return '';
    $py = sanitizeFolderName($production_year);
    $ag = sanitizeFolderName($agency);
    $cn = sanitizeFolderName($contract_number);
    $pi = sanitizeFolderName($plot_id);
    $image_type_folder = sanitizeFolderName($image_type_folder);
    $basePath = "uploads/{$py}/{$ag}/{$cn}/{$pi}/";
    return $basePath . $image_type_folder . '/' . $filename;
}

$id_to_edit = $_GET['id'] ?? null;
if (empty($id_to_edit)) {
    header("Location: dashboard.php");
    exit;
}

$sql = "SELECT * FROM soil_data WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_to_edit);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

if (!$data) {
    header("Location: dashboard.php");
    exit;
}

$selected_year = $data['production_year']; 
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อมูล | ID <?php echo htmlspecialchars($data['plot_id']); ?></title>
    <link rel="icon" href="icon/unnamed.png" type="image/png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="index.css">
    
    <style>
        .page-container { padding: 2rem 0 5rem; }
        .form-section-title {
            font-size: 1.3rem; font-weight: 700; margin: 2rem 0 1.2rem;
            display: flex; align-items: center; gap: 12px; color: var(--primary);
            border-bottom: 3px solid #e2e8f0; padding-bottom: 12px;
        }
        .form-label { font-weight: 700; color: #334155; font-size: 1rem; margin-bottom: 8px; }
        .form-control-premium { 
            border-radius: 12px; 
            border: 2px solid #cbd5e1; 
            padding: 12px 15px; 
            font-size: 1.05rem;
            transition: all 0.3s; 
        }
        .form-control-premium:focus { border-color: var(--primary); box-shadow: 0 0 0 5px rgba(30, 58, 138, 0.15); }
        
        .form-select-premium {
            border-radius: 12px;
            padding: 12px 15px;
            border: 2px solid #cbd5e1;
            font-size: 1.05rem;
        }
        
        .current-image-badge {
            background: rgba(var(--primary-rgb), 0.1);
            color: var(--primary);
            padding: 8px 12px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.85rem;
        }
        .delete-img-icon { cursor: pointer; color: #dc3545; transition: transform 0.2s; }
        .delete-img-icon:hover { transform: scale(1.2); }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .page-container { padding: 1rem 0 3rem; }
            .form-section-title { font-size: 1.2rem; margin: 1.5rem 0 1rem; padding-bottom: 8px; }
            .form-label { font-size: 1rem; margin-bottom: 5px; }
            .form-control-premium, .form-select-premium { padding: 10px 12px; font-size: 1rem; }
            .badge { font-size: 0.9rem !important; padding: 5px 10px !important; }
            .btn-premium, .btn-light { width: 100%; padding: 12px !important; }
            .d-flex.gap-3.justify-content-center { flex-direction: column; gap: 10px !important; }
            h2 { font-size: 1.6rem !important; }
            .glass-card-white .d-flex.justify-content-between { flex-direction: column; }
            .glass-card-white .text-end { text-align: left !important; margin-top: 15px; }
            .glass-card-white .text-end .badge { width: 100%; display: block; }
        }
    </style>
</head>
<body>
<?php require("nav.php");?>

<div class="container page-container">
    <form action="update_data.php" method="POST" enctype="multipart/form-data" class="fade-in">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($data['id']); ?>"> 
        <input type="hidden" name="original_plot_id" value="<?php echo htmlspecialchars($data['plot_id']); ?>"> 
        <input type="hidden" name="original_production_year" value="<?php echo htmlspecialchars($data['production_year']); ?>"> 
        <input type="hidden" name="original_contract_number" value="<?php echo htmlspecialchars($data['contract_number']); ?>"> 

        <div class="glass-card-white">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <h2 class="mb-1 fw-bold" style="font-size: 1.8rem;">แก้ไขข้อมูลแปลง</h2>
                    <p class="text-muted" style="font-size: 1.05rem;">ID แปลง: <span class="badge bg-primary px-3 py-2" style="font-size: 0.95rem;"><?php echo htmlspecialchars($data['plot_id']); ?></span></p>
                </div>
                <div class="text-end">
                    <span class="badge bg-white text-primary border-primary border px-3 py-2 shadow-sm" style="font-size: 1rem; border-width: 2px !important; border-radius: 12px;">ปีการผลิต <?php echo htmlspecialchars($data['production_year']); ?></span>
                </div>
            </div>

            <div class="form-section-title"><i class='bx bx-map-pin'></i> ข้อมูลพื้นฐาน</div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label">ID แปลง <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-premium" name="plot_id" value="<?php echo htmlspecialchars($data['plot_id']); ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">เลขสัญญา</label>
                    <input type="text" class="form-control form-control-premium" name="contract_number" value="<?php echo htmlspecialchars($data['contract_number']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">โควต้า</label>
                    <input type="text" class="form-control form-control-premium" name="quota" value="<?php echo htmlspecialchars($data['quota']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">หน่วยงาน</label>
                    <select class="form-select form-select-premium" name="agency">
                        <option value="">-- เลือกหน่วยงาน --</option>
                        <?php
                        $agencies = [
                            "ศรีนครใต้", "ชัยคีรี", "ท่าชัย", "ท่าชัยเหนือ", "คีรีมาศ", 
                            "ศรีสำโรง", "ทุ่งเสลี่ยม", "บางขลัง", "ศรีนครเหนือ", "ตลิ่งชัน", 
                            "เขาหลวง", "ศรีสัชนาลัย", "สวรรคโลก", "ตาก", "ท่าชัยใต้", 
                            "น้ำอ่าง", "บ่อทอง", "ชาติตระการ", "หนองตม", "พิชัย", 
                            "วัดโบสถ์", "พรหมพิราม", "เมือง", "น้ำปาด"
                        ];
                        foreach ($agencies as $agency):
                            $selected = ($data['agency'] == $agency) ? 'selected' : '';
                            echo "<option value=\"$agency\" $selected>$agency</option>";
                        endforeach;
                        ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">พื้นที่ (ไร่)</label>
                    <div class="input-group">
                        <input type="number" class="form-control form-control-premium" name="rai_area" step="any" value="<?php echo htmlspecialchars($data['rai_area']); ?>" required>
                        <span class="input-group-text bg-light border-0">ไร่</span>
                    </div>
                </div>
            </div>

            <div class="form-section-title"><i class='bx bx-leaf'></i> การประเมินคุณภาพ</div>

            <div class="row g-4">
                <?php
                $sections = [
                    ['soil_type', 'ชนิดดิน', 'soil_image', 'quality'],
                    ['soil_preparation_details', 'การเตรียมดิน', 'soil_preparation_image', 'quality'],
                    ['cane_variety', 'พันธุ์อ้อย', 'cane_variety_image', 'quality'],
                    ['planting_details', 'การปลูก', 'planting_image', 'planting'],
                    ['watering_details', 'การให้น้ำ', 'watering_image', 'watering'],
                    ['germination_percentage', 'เปอร์เซ็นต์งอก (%)', 'germination_image', 'germination']
                ];

                foreach ($sections as $s):
                    list($field, $label, $img_field, $type) = $s;
                ?>
                <div class="col-md-6">
                    <div class="p-4 bg-white border rounded-4 h-100 shadow-sm">
                        <label class="form-label fw-bold"><?php echo $label; ?></label>
                        
                        <?php if ($type == 'germination'): ?>
                            <div class="input-group mb-3">
                                <input type="number" class="form-control form-control-premium" name="<?php echo $field; ?>" value="<?php echo htmlspecialchars($data[$field]); ?>" min="0" max="100">
                                <span class="input-group-text">%</span>
                            </div>
                        <?php else: ?>
                            <select class="form-select form-select-premium mb-3" name="<?php echo $field; ?>">
                                <option value="">-- เลือก --</option>
                                <?php if ($type == 'quality'): ?>
                                    <option value="1" <?php echo ($data[$field] == '1') ? 'selected' : ''; ?>>ดีมาก</option>
                                    <option value="2" <?php echo ($data[$field] == '2') ? 'selected' : ''; ?>>ดี</option>
                                    <option value="3" <?php echo ($data[$field] == '3') ? 'selected' : ''; ?>>พอใช้</option>
                                <?php elseif ($type == 'planting'): ?>
                                    <option value="1" <?php echo ($data[$field] == '1') ? 'selected' : ''; ?>>มาตรฐาน</option>
                                    <option value="2" <?php echo ($data[$field] == '2') ? 'selected' : ''; ?>>ไม่ได้มาตรฐาน</option>
                                <?php elseif ($type == 'watering'): ?>
                                    <option value="1" <?php echo ($data[$field] == '1') ? 'selected' : ''; ?>>มี</option>
                                    <option value="2" <?php echo ($data[$field] == '2') ? 'selected' : ''; ?>>ไม่มี</option>
                                <?php endif; ?>
                            </select>
                        <?php endif; ?>

                        <div id="<?php echo $img_field; ?>_container">
                            <?php 
                            $img_path = getImagePath($data[$img_field], $data['production_year'], $data['agency'], $data['contract_number'], $data['plot_id'], $img_field);
                            if ($img_path && file_exists($img_path) && !empty($data[$img_field])): 
                            ?>
                                <div class="current-image-badge">
                                    <span class="text-truncate" style="max-width: 200px;"><i class='bx bx-image-alt'></i> <?php echo htmlspecialchars($data[$img_field]); ?></span>
                                    <i class='bx bxs-trash delete-img-icon' title="ลบภาพ" 
                                       data-id="<?php echo $data['id']; ?>" 
                                       data-type="<?php echo $img_field; ?>"
                                       data-py="<?php echo $data['production_year']; ?>"
                                       data-ag="<?php echo $data['agency']; ?>"
                                       data-cn="<?php echo $data['contract_number']; ?>"
                                       data-pi="<?php echo $data['plot_id']; ?>"></i>
                                </div>
                                <div class="file-input-wrapper" style="display: none;">
                                    <input type="file" name="<?php echo $img_field; ?>" class="form-control form-control-sm" accept="image/*">
                                </div>
                            <?php else: ?>
                                <input type="file" name="<?php echo $img_field; ?>" class="form-control form-control-sm" accept="image/*">
                            <?php endif; ?>
                            <input type="hidden" name="current_<?php echo $img_field; ?>" value="<?php echo htmlspecialchars($data[$img_field] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-4">
                <label class="form-label"><i class='bx bx-note'></i> หมายเหตุเพิ่มเติม</label>
                <textarea class="form-control form-control-premium" name="notes" rows="4"><?php echo htmlspecialchars($data['notes']); ?></textarea>
            </div>

            <div class="mt-5 d-flex gap-3 justify-content-center">
                <button type="submit" class="btn btn-premium px-5 py-3 shadow">
                    <i class='bx bx-check-circle'></i> บันทึกการแก้ไข
                </button>
                <a href="dashboard.php?year=<?php echo htmlspecialchars($selected_year); ?>" class="btn btn-light px-5 py-3 rounded-4">
                    ยกเลิก
                </a>
            </div>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Alert สำหรับการบันทึกแก้ไขข้อมูล
    $('form.fade-in').on('submit', function(e) {
        e.preventDefault();
        const form = this;
        Swal.fire({
            title: 'ยืนยันการบันทึก?',
            text: "คุณต้องการบันทึกการแก้ไขข้อมูลนี้ใช่หรือไม่?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0d6efd',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="bx bx-check-circle"></i> ยืนยันบันทึก',
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

    // Alert สำหรับการลบรูปภาพ
    $('.delete-img-icon').on('click', function() {
        const btn = $(this);
        const type = btn.data('type');
        
        Swal.fire({
            title: 'ต้องการลบรูปภาพ?',
            text: "ลบแล้วจะไม่สามารถกู้คืนได้!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ยืนยันลบ',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'delete_image_ajax.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        id: btn.data('id'),
                        image_type: type,
                        production_year: btn.data('py'),
                        agency: btn.data('ag'),
                        contract_number: btn.data('cn'),
                        plot_id: btn.data('pi')
                    },
                    success: function(resp) {
                        if (resp.success) {
                            Swal.fire('ลบแล้ว!', resp.message, 'success');
                            btn.closest('.current-image-badge').hide();
                            btn.closest('#'+type+'_container').find('.file-input-wrapper').show();
                            btn.closest('#'+type+'_container').find('input[type="hidden"]').val('');
                        } else {
                            Swal.fire('ผิดพลาด!', resp.message, 'error');
                        }
                    }
                });
            }
        });
    });
});
</script>
</body>
</html>
<?php $conn->close(); ?>