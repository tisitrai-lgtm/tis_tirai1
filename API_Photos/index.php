<?php
require_once 'db_config.php'; 

// --- 1. ระบบล้างข้อมูลและรูปภาพเก่าเกิน 60 วันอัตโนมัติ ---
try {
    $sql_old = "SELECT image_filename FROM plots_inspection WHERE created_at < NOW() - INTERVAL 60 DAY";
    $stmt_old = $pdo->query($sql_old);
    while ($old_row = $stmt_old->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($old_row['image_filename']) && file_exists($old_row['image_filename'])) {
            unlink($old_row['image_filename']);
        }
    }
    $pdo->exec("DELETE FROM plots_inspection WHERE created_at < NOW() - INTERVAL 60 DAY");
} catch (PDOException $e) { }

// --- 2. ดึงข้อมูล (เรียงล่าสุดขึ้นก่อน เพื่อให้วันที่ปัจจุบันอยู่บนสุด) ---
try {
    $sql = "SELECT * FROM plots_inspection ORDER BY created_at DESC"; 
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
        .upload-card:hover { background: #f0fff4; transform: scale(1.01); }
        .table-card { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        
        /* สไตล์รูปภาพพร้อมปุ่มกากบาทลบแถว */
        .img-container { position: relative; width: 65px; height: 48px; margin: 0 auto; }
        .img-preview-sm { width: 100%; height: 100%; object-fit: cover; border-radius: 8px; cursor: pointer; border: 1px solid #dee2e6; }
        .btn-delete-x { 
            position: absolute; top: -8px; right: -8px; 
            background: #ff4d4d; color: white; border: none; 
            border-radius: 50%; width: 22px; height: 22px; 
            font-size: 11px; display: flex; align-items: center; justify-content: center; 
            cursor: pointer; z-index: 10; box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transition: 0.2s;
        }
        .btn-delete-x:hover { background: #cc0000; transform: scale(1.15); }
        
        #loadingOverlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255, 255, 255, 0.8); z-index: 9999; backdrop-filter: blur(5px);
            align-items: center; justify-content: center;
        }
        .dataTables_paginate .paginate_button {
    border-radius: 50% !important; /* ทำปุ่มเป็นวงกลม */
    margin: 0 2px !important;
    border: none !important;
    transition: 0.3s;
}

/* สีปุ่มตอนที่เลือกอยู่ */
.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: #28a745 !important; /* สีเขียวเดียวกับธีมหลัก */
    color: white !important;
    border: none !important;
}

/* สีปุ่มตอนเอาเมาส์ไปวาง */
.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background: #e9ecef !important;
    color: #28a745 !important;
}

/* จัดระยะห่างของส่วนเลือกหน้า */
.dataTables_paginate {
    margin-top: 15px !important;
    padding-top: 10px !important;
    display: flex;
    justify-content: center; /* จัดให้อยู่กลางหน้าจอ */
    align-items: center;
}
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div id="loadingOverlay">
    <div class="text-center p-5 bg-white rounded-4 shadow">
        <div class="spinner-border text-success" style="width: 3rem; height: 3rem;"></div>
        <h5 class="mt-3 fw-bold text-success">กำลังประมวลผล AI...</h5>
    </div>
</div>

<div class="container py-2">
    <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
        <div class="d-flex align-items-center">
            <div class="me-3"><i class="bi bi-robot fs-2 text-success"></i></div>
            <div>
                <h6 class="fw-bold mb-1">📢 หมายเหตุระบบ</h6>
                <div class="small text-dark">
                    • ระบบใช้ AI ในการวิเคราะห์ ความแม่นยำจะเพิ่มขึ้นตามจำนวนรูปที่เพิ่มและแก้ไขค่าที่ถูกต้อง<br>
                    • ข้อมูลเก่าและรูปภาพที่ **เกิน 60 วันจะถูกลบอัตโนมัติ** เพื่อเพิ่มพื้นที่จัดเก็บ
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center mb-5">
        <div class="col-md-6 text-center upload-card" onclick="document.getElementById('imageInput').click()">
            <i class="bi bi-camera-fill text-success" style="font-size: 3rem;"></i>
            <h4 class="fw-bold mt-2">ถ่ายรูปหรือเลือกรูปตรวจแปลง</h4>
            <input type="file" id="imageInput" accept="image/*" capture="environment" style="display: none;">
        </div>
    </div>

    <div class="table-card">
        <div class="d-md-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold m-0 text-success"><i class="bi bi-list-stars"></i> ประวัติการสำรวจ</h5>
            <div class="d-flex gap-2">
                <input type="text" id="tableSearch" class="form-control form-control-sm" style="max-width: 200px;" placeholder="🔍 ค้นหา...">
            </div>
        </div>
        
        <div class="table-responsive">
            <table id="mainTable" class="table table-hover align-middle w-100">
                <thead class="table-light">
                    <tr>
                        <th width="80">แก้ไข</th>
                        <th class="text-center">รูปภาพ</th>
                        <th class="text-center">ใบแห้ง (%)</th>
                        <th>เลขสัญญา</th>
                        <th>ID แปลง</th>
                        <th>ชื่อผู้ปลูก</th>
                        <th>วัน/เวลา</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($results as $row): ?>
                    <tr>
                        <td>
                            <button class="btn btn-sm btn-outline-secondary" 
                                onclick="openFullEdit('<?= $row['id'] ?>', '<?= htmlspecialchars($row['contract_number']) ?>', '<?= htmlspecialchars($row['plot_id']) ?>', '<?= htmlspecialchars($row['owner_name']) ?>', '<?= $row['trash_percentage'] ?>')">
                                ✏️ แก้ไข
                            </button>
                        </td>
                        <td class="text-center">
                            <div class="img-container">
                                <img src="<?= $row['image_filename'] ?>" class="img-preview-sm" onclick="window.open(this.src)">
                                <button class="btn-delete-x" onclick="deleteRecord('<?= $row['id'] ?>')" title="ลบข้อมูล">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </td>
                        <td class="text-center fw-bold">
                            <span class="<?= $row['trash_percentage'] > 20 ? 'text-danger' : 'text-success' ?>">
                                <?= (int)$row['trash_percentage'] ?>%
                            </span>
                        </td>
                        <td class="fw-bold text-success"><?= htmlspecialchars($row['contract_number']) ?></td>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['plot_id']) ?></span></td>
                        <td class="small"><?= htmlspecialchars($row['owner_name']) ?></td>
                        <td class="small text-muted"><?= date('d/m/y H:i', strtotime($row['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="dataModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="saveForm" action="process_save.php" method="POST" enctype="multipart/form-data">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">📊 ผลวิเคราะห์ AI</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-5 text-center border-end">
                            <img id="previewImg" src="" class="img-fluid rounded mb-2 shadow-sm" style="max-height: 250px;">
                            <div class="h2 fw-bold text-danger"><span id="displayPercent">0</span>%</div>
                            <input type="hidden" name="trash_percentage" id="inputPercent">
                            <input type="file" name="sugarcane_image" id="hiddenFile" style="display:none;">
                        </div>
                        <div class="col-md-7">
                            <div class="row g-2">
                                <div class="col-12"><label class="small fw-bold">เลขที่สัญญา *</label><input type="text" name="contract_number" class="form-control" ></div>
                                <div class="col-6"><label class="small fw-bold">ID แปลง</label><input type="text" name="plot_id" class="form-control"></div>
                                <div class="col-6"><label class="small fw-bold">พื้นที่ (ไร่)</label><input type="number" step="0.01" name="area_rai" class="form-control"></div>
                                <div class="col-12"><label class="small fw-bold">ชื่อเจ้าของโควตา</label><input type="text" name="owner_name" class="form-control"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold" onclick="handleSave()">💾 บันทึกข้อมูล</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="fullEditModal" tabindex="-1">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">📝 แก้ไขข้อมูล (สอน AI)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="edit_id_full">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-danger">ใบแห้งที่ถูกต้อง (%)</label>
                    <input type="number" id="edit_val_full" class="form-control form-control-lg text-danger fw-bold border-danger">
                    <div class="form-text text-primary">* ระบบจะสำรองรูปนี้ไว้พัฒนา AI โดยอัตโนมัติ</div>
                </div>
                <div class="mb-3"><label class="form-label small">เลขสัญญา</label><input type="text" id="edit_contract" class="form-control"></div>
                <div class="mb-3"><label class="form-label small">ID แปลง</label><input type="text" id="edit_plot" class="form-control"></div>
                <div class="mb-3"><label class="form-label small">ชื่อผู้ปลูก</label><input type="text" id="edit_owner" class="form-control"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary w-100 py-2 fw-bold" onclick="saveFullEdit()">💾 บันทึกและสอน AI</button>
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
   var table = $('#mainTable').DataTable({
    "dom": "rtp",
    "pageLength": 10,
    "pagingType": "full_numbers", // หรือใช้ "simple_numbers" เพื่อความสะอาดตา
    "language": {
        "paginate": {
            "first": "หน้าแรก",
            "last": "หน้าสุดท้าย",
            "next": "ถัดไป",
            "previous": "ก่อนหน้า"
        }
    }
});
    $('#tableSearch').on('keyup', function() { table.search(this.value).draw(); });

    // ระบบอัปโหลดรูปและย่อขนาดก่อนส่งไป AI
    $('#imageInput').change(function() {
        let file = this.files[0];
        if (file) {
            $('#loadingOverlay').css('display', 'flex'); 
            let reader = new FileReader();
            reader.onload = function(e) {
                let img = new Image();
                img.onload = function() {
                    let canvas = document.createElement('canvas');
                    let maxWidth = 1000;
                    let scale = maxWidth / img.width;
                    canvas.width = maxWidth;
                    canvas.height = img.height * scale;
                    let ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                    canvas.toBlob(function(blob) {
                        let resizedFile = new File([blob], file.name, { type: 'image/jpeg' });
                        let formData = new FormData();
                        formData.append('image', resizedFile);
                        $.ajax({
                            url: 'http://127.0.0.1:5050/analyze',
                            type: 'POST',
                            data: formData, processData: false, contentType: false,
                            success: function(res) {
                                $('#loadingOverlay').hide();
                                $('#displayPercent').text(Math.round(res.trash_percentage));
                                $('#inputPercent').val(Math.round(res.trash_percentage));
                                $('#previewImg').attr('src', canvas.toDataURL('image/jpeg'));
                                const dt = new DataTransfer(); dt.items.add(resizedFile);
                                document.getElementById('hiddenFile').files = dt.files;
                                new bootstrap.Modal(document.getElementById('dataModal')).show();
                            },
                            error: function() { $('#loadingOverlay').hide(); alert('ติดต่อ Server AI ไม่ได้!'); }
                        });
                    }, 'image/jpeg', 0.8);
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
});

function deleteRecord(id) {
    if (confirm('ยืนยันลบข้อมูลและรูปภาพ?')) {
        $('#loadingOverlay').css('display', 'flex');
        $.post('delete_record.php', { id: id }, function() { location.reload(); });
    }
}

function openFullEdit(id, contract, plot, owner, trash) {
    $('#edit_id_full').val(id);
    $('#edit_contract').val(contract);
    $('#edit_plot').val(plot);
    $('#edit_owner').val(owner);
    $('#edit_val_full').val(trash);
    new bootstrap.Modal(document.getElementById('fullEditModal')).show();
}

function saveFullEdit() {
    let data = {
        id: $('#edit_id_full').val(),
        contract_number: $('#edit_contract').val(),
        plot_id: $('#edit_plot').val(),
        owner_name: $('#edit_owner').val(),
        trash_percentage: $('#edit_val_full').val()
    };
    $('#loadingOverlay').css('display', 'flex');
    $.post('update_details.php', data, function() { location.reload(); });
}

function handleSave() { if($('input[name="contract_number"]').val()) $('#loadingOverlay').css('display', 'flex'); }
</script>
</body>
</html>