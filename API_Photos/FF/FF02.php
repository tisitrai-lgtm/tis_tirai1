<?php
require_once 'db_config.php'; 
try {
    $sql = "SELECT * FROM plots_inspection ORDER BY id DESC"; 
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $results = []; }
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SugarAI - ระบบวิเคราะห์ใบอ้อย</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <style>
        body { background-color: #f8faf9; font-family: 'Sarabun', sans-serif; }
        .upload-card { background: white; border-radius: 20px; padding: 30px; border: 2px dashed #28a745; cursor: pointer; transition: 0.3s; }
        .upload-card:hover { background: #f0fff4; transform: scale(1.02); }
        .table-card { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .img-preview-sm { width: 60px; height: 45px; object-fit: cover; border-radius: 6px; cursor: pointer; }
        
        /* หน้าจอ Loading แบบ Blur */
        #loadingOverlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255, 255, 255, 0.8);
            z-index: 9999;
            backdrop-filter: blur(5px);
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }
        .loader-box {
            text-align: center;
            background: white;
            padding: 30px 50px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        /* แก้ไขปัญหาการเลื่อนหน้าจอ */
        body:not(.modal-open) { overflow: auto !important; padding-right: 0 !important; }
        .modal-backdrop.show { opacity: 0.5; }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>
<div id="loadingOverlay">
    <div class="loader-box">
        <div class="spinner-border text-success" style="width: 3rem; height: 3rem;" role="status"></div>
        <h5 class="mt-3 fw-bold">AI กำลังวิเคราะห์ใบอ้อย...</h5>
        <p class="text-muted small">กรุณารอซักครู่ ระบบกำลังคำนวณและลดขนาดภาพ</p>
    </div>
</div>

<div class="container py-4">
    <div class="row justify-content-center mb-5">
        <div class="col-md-6 text-center upload-card" onclick="document.getElementById('imageInput').click()">
            <i class="bi bi-cloud-arrow-up-fill text-success" style="font-size: 3rem;"></i>
            <h4 class="fw-bold mt-2">คลิกเพื่อเพิ่มรูปตรวจแปลง</h4>
            <p class="text-muted small">ระบบจะย่อขนาดภาพอัตโนมัติก่อนส่งวิเคราะห์</p>
            <input type="file" id="imageInput" accept="image/*" style="display: none;">
        </div>
    </div>

    <div class="table-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold m-0">📋 ประวัติการสำรวจ</h5>
            <input type="text" id="tableSearch" class="form-control form-control-sm" style="width: 250px;" placeholder="🔍 ค้นหา...">
        </div>
        
        <table id="mainTable" class="table table-hover align-middle w-100">
            <thead class="table-light">
                <tr>
                    <th>วัน/เวลา</th>
                    <th>เลขสัญญา</th>
                    <th>ID แปลง</th>
                    <th>ชื่อผู้ปลูก</th>
                    <th class="text-center">ใบแห้ง (%)</th>
                    <th class="text-center">รูป</th>
                    <th>เครื่องมือ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($results as $row): ?>
                <tr>
                    <td class="small"><?= date('d/m/y H:i', strtotime($row['created_at'])) ?></td>
                    <td class="fw-bold"><?= htmlspecialchars($row['contract_number']) ?></td>
                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['plot_id']) ?></span></td>
                    <td class="small"><?= htmlspecialchars($row['owner_name']) ?></td>
                    <td class="text-center">
                        <span class="fw-bold <?= $row['trash_percentage'] > 20 ? 'text-danger' : 'text-success' ?>">
                            <?= (int)$row['trash_percentage'] ?>%
                        </span>
                    </td>
                    <td class="text-center">
                        <img src="<?= $row['image_filename'] ?>" class="img-preview-sm border" onclick="window.open(this.src)">
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary" onclick="openEditModal(<?= $row['id'] ?>, <?= $row['trash_percentage'] ?>)">✏️ สอน</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="dataModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="saveForm" action="process_save.php" method="POST" enctype="multipart/form-data">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">📊 ผลการวิเคราะห์จาก AI</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-5 text-center border-end">
                            <img id="previewImg" src="" class="img-fluid rounded mb-3 shadow-sm" style="max-height: 250px;">
                            <div class="h3 fw-bold text-danger"><span id="displayPercent">0</span>%</div>
                            <input type="hidden" name="trash_percentage" id="inputPercent">
                            <input type="file" name="sugarcane_image" id="hiddenFile" style="display:none;">
                        </div>
                        <div class="col-md-7">
                            <div class="row g-2">
                                <div class="col-12"><label class="small fw-bold">เลขที่สัญญา *</label><input type="text" name="contract_number" class="form-control" ></div>
                                <div class="col-6"><label class="small fw-bold">ID แปลง</label><input type="text" name="plot_id" class="form-control"></div>
                                <div class="col-6"><label class="small fw-bold">พื้นที่ (ไร่)</label><input type="number" step="0.01" name="area_rai" class="form-control"></div>
                                <div class="col-12"><label class="small fw-bold">ชื่อเจ้าของโควตา</label><input type="text" name="owner_name" class="form-control"></div>
                                <div class="col-12"><label class="small fw-bold">ผู้ตรวจ (NSS)</label><input type="text" name="nss_name" class="form-control"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold" onclick="handleSave()">💾 ยืนยันและบันทึกข้อมูล</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <h6 class="fw-bold">แก้ไขค่าใบแห้ง</h6>
                <input type="number" id="edit_val" class="form-control text-center my-3">
                <input type="hidden" id="edit_id">
                <button class="btn btn-dark w-100" onclick="saveEdit()">ตกลง</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // 1. ตั้งค่า DataTable (ล้างค่าเก่าก่อนเพื่อป้องกัน Error)
    if ($.fn.DataTable.isDataTable('#mainTable')) {
        $('#mainTable').DataTable().destroy();
    }
    var table = $('#mainTable').DataTable({
        "dom": "rtp",
        "pageLength": 10,
        "language": { "paginate": { "next": "ถัดไป", "previous": "ก่อนหน้า" } }
    });

    // 2. ค้นหาในตาราง
    $('#tableSearch').on('keyup', function() {
        table.search(this.value).draw();
    });

    // 3. ระบบอัปโหลด + ย่อรูป + เรียก AI
    $('#imageInput').change(function() {
        let file = this.files[0];
        if (file) {
            $('#loadingOverlay').css('display', 'flex'); 

            let reader = new FileReader();
            reader.onload = function(e) {
                let img = new Image();
                img.onload = function() {
                    // --- ส่วนย่อรูป (1200px) ---
                    let maxWidth = 1200;
                    let scaleSize = maxWidth / img.width;
                    let canvas = document.createElement('canvas');
                    canvas.width = maxWidth;
                    canvas.height = img.height * scaleSize;
                    let ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                    // บีบอัดรูป
                    canvas.toBlob(function(blob) {
                        let resizedFile = new File([blob], file.name, { type: 'image/jpeg' });
                        let formData = new FormData();
                        formData.append('image', resizedFile);

                        // ส่งไป AI
                        $.ajax({
                            url: 'http://127.0.0.1:5050/analyze',
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function(response) {
                                $('#loadingOverlay').hide();
                                let p = Math.round(response.trash_percentage);
                                $('#displayPercent').text(p);
                                $('#inputPercent').val(p);
                                $('#previewImg').attr('src', canvas.toDataURL('image/jpeg'));

                                // ยัดรูปใส่ hidden input สำหรับ PHP
                                const dataTransfer = new DataTransfer();
                                dataTransfer.items.add(resizedFile);
                                document.getElementById('hiddenFile').files = dataTransfer.files;

                                new bootstrap.Modal(document.getElementById('dataModal')).show();
                            },
                            error: function() {
                                $('#loadingOverlay').hide();
                                alert('ติดต่อ AI ไม่ได้! รัน api.py หรือยัง?');
                            }
                        });
                    }, 'image/jpeg', 0.8);
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });

    // 4. ล้างหน้าจอเมื่อ Modal ปิด
    $('#dataModal, #editModal').on('hidden.bs.modal', function () {
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('overflow', 'auto');
        if(this.id === 'dataModal') $('#saveForm')[0].reset();
    });
});

// ฟังก์ชันบันทึก
function handleSave() {
    // 1. ตรวจสอบก่อนว่ากรอกเลขสัญญาหรือยัง (ถ้าใช้ required)
    if (!$('input[name="contract_number"]').val()) {
        return; // ถ้ายังไม่กรอก ไม่ต้องโชว์ loading
    }

    // 2. ปิด Modal และลบเงา
    var modalElement = document.getElementById('dataModal');
    var modalInstance = bootstrap.Modal.getInstance(modalElement);
    if (modalInstance) {
        modalInstance.hide();
    }
    $('.modal-backdrop').remove();

    // 3. แสดงหน้าจอหมุน
    $('#loadingOverlay').css('display', 'flex');
    $('.loader-box h5').text('กำลังบันทึกข้อมูลลงคอมพิวเตอร์...');
}

// ฟังก์ชันแก้ไข (สอน AI)
function openEditModal(id, val) {
    $('#edit_id').val(id);
    $('#edit_val').val(val);
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function saveEdit() {
    let id = $('#edit_id').val();
    let newVal = $('#edit_val').val();
    $.post('update_trash.php', { id: id, trash_percentage: newVal }, function() {
        location.reload();
    });
}
</script>

</body>
</html>