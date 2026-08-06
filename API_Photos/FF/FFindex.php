<?php
// --- 1. ส่วนเชื่อมต่อฐานข้อมูล ---
require_once 'db_config.php'; 

try {
    $sql = "SELECT * FROM plots_inspection ORDER BY id DESC"; 
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $results = [];
    $db_error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบสำรวจแปลงอ้อย - วิเคราะห์ใบแห้ง</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { background-color: #f4f7f6; font-family: 'Sarabun', sans-serif; color: #333; }
        .upload-card { background: white; border-radius: 20px; padding: 40px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.08); margin-top: 30px; border: 1px solid #e0e0e0; }
        .btn-upload { background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%); color: white; padding: 18px 35px; font-size: 1.3rem; border-radius: 50px; cursor: pointer; border: none; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3); }
        .btn-upload:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4); filter: brightness(1.1); }
        #loading { display: none; margin-top: 25px; }
        .report-section { margin-top: 50px; margin-bottom: 60px; }
        .table-card { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); border: 1px solid #eee; }
        .img-preview-sm { width: 70px; height: 50px; object-fit: cover; border-radius: 8px; cursor: pointer; transition: transform 0.2s; border: 1px solid #ddd; }
        .img-preview-sm:hover { transform: scale(1.1); }
        .table thead th { background-color: #f8f9fa; color: #555; font-weight: 700; border-top: none; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px; }
        .badge-percent { font-size: 1rem; padding: 5px 12px; border-radius: 50px; display: inline-flex; align-items: center; }
        .status-pill { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 8px; }
        .btn-edit-inline { background: none; border: none; color: inherit; cursor: pointer; padding: 0 5px; opacity: 0.6; transition: 0.2s; }
        .btn-edit-inline:hover { opacity: 1; transform: scale(1.2); }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-7 upload-card">
            <h1 class="fw-bold text-success mb-3">📸 เริ่มการสำรวจแปลงอ้อย</h1>
            <p class="text-muted mb-4">ถ่ายรูปหรือเลือกรูปภาพใบอ้อย เพื่อให้ AI คำนวณเปอร์เซ็นต์ใบแห้งอัตโนมัติ</p>
            <input type="file" id="imageInput" accept="image/*" style="display: none;">
            <button class="btn-upload mb-2" onclick="document.getElementById('imageInput').click()">📷 ถ่ายรูป / เลือกรูปภาพ</button>
            <div id="loading">
                <div class="spinner-grow text-success" role="status"></div>
                <p class="mt-3 fw-bold text-success">ระบบกำลังประมวลผลรูปภาพ...</p>
            </div>
            <?php if(isset($db_error)) echo "<div class='alert alert-danger mt-3'>เชื่อมต่อฐานข้อมูลไม่ได้: $db_error</div>"; ?>
        </div>
    </div>

    <div class="row report-section">
        <div class="col-12 table-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0 fw-bold text-dark border-start border-success border-4 ps-3">📋 ประวัติการสำรวจ (สอน AI)</h4>
                <button class="btn btn-sm btn-light border rounded-pill px-3" onclick="location.reload()">🔄 รีเฟรชรายการ</button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>วัน/เวลา</th>
                            <th>เลขสัญญา</th>
                            <th>ID แปลง</th>
                            <th>เจ้าของโควตา</th>
                            <th>ผู้ตรวจ (NSS)</th>
                            <th class="text-center">ใบแห้ง (%)</th>
                            <th class="text-center">รูปภาพ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($results)): ?>
                            <?php foreach($results as $row): ?>
                            <tr id="row-<?php echo $row['id']; ?>">
                                <td class="small text-muted"><?php echo isset($row['created_at']) ? date('d/m/y H:i', strtotime($row['created_at'])) : 'N/A'; ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($row['contract_number'] ?? 'N/A'); ?></td>
                                <td><span class="badge bg-light text-dark border px-3 rounded-pill"><?php echo htmlspecialchars($row['plot_id'] ?? '-'); ?></span></td>
                                <td><?php echo htmlspecialchars($row['owner_name'] ?? 'ไม่ระบุ'); ?></td>
                                <td><?php echo htmlspecialchars($row['nss_name'] ?? '-'); ?></td>
                                <td class="text-center">
                                    <?php 
                                        $p = $row['trash_percentage'];
                                        $color_class = ($p > 20) ? 'text-danger' : 'text-success';
                                        $bg_class = ($p > 20) ? 'bg-danger' : 'bg-success';
                                    ?>
                                    <span class="badge <?php echo $bg_class; ?> bg-opacity-10 <?php echo $color_class; ?> badge-percent">
                                        <span class="status-pill <?php echo $bg_class; ?>"></span>
                                        <span class="percent-value" id="val-<?php echo $row['id']; ?>"><?php echo number_format($p, 0); ?></span>%
                                        <button class="btn-edit-inline" onclick="openEditModal(<?php echo $row['id']; ?>, <?php echo $p; ?>)" title="แก้ไขเพื่อสอน AI">✏️</button>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if(!empty($row['image_filename']) && file_exists($row['image_filename'])): ?>
                                        <a href="<?php echo $row['image_filename']; ?>" target="_blank">
                                            <img src="<?php echo $row['image_filename']; ?>" class="img-preview-sm shadow-sm">
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center text-muted py-5">ยังไม่มีข้อมูล</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="dataModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <form id="saveForm" action="process_save.php" method="POST" enctype="multipart/form-data">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header bg-success text-white" style="border-radius: 20px 20px 0 0;">
                    <h5 class="modal-title fw-bold">📊 ผลการวิเคราะห์และบันทึกข้อมูล</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="text-center mb-4 pb-3 border-bottom">
                        <img id="previewImg" src="" class="img-fluid rounded-4 shadow-sm mb-3" style="max-height: 280px; width: 100%; object-fit: contain; background: #f8f9fa;">
                        <div class="bg-light p-3 rounded-4 d-inline-block px-5">
                            <span class="text-muted d-block">AI ตรวจพบใบแห้ง</span>
                            <h2 class="mb-0 fw-bold text-danger"><span id="displayPercent">0</span>%</h2>
                        </div>
                        <input type="hidden" name="trash_percentage" id="inputPercent">
                        <input type="file" name="sugarcane_image" id="hiddenFile" style="display:none;">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label fw-bold">เลขที่สัญญา</label><input type="text" name="contract_number" class="form-control rounded-3"></div>
                        <div class="col-md-6"><label class="form-label fw-bold">ID แปลง</label><input type="text" name="plot_id" class="form-control rounded-3"></div>
                        <div class="col-md-6"><label class="form-label fw-bold">ชื่อเจ้าของโควตา</label><input type="text" name="owner_name" class="form-control rounded-3"></div>
                        <div class="col-md-6"><label class="form-label fw-bold">ชื่อ นสส. ผู้ตรวจ</label><input type="text" name="nss_name" class="form-control rounded-3"></div>
                        <div class="col-md-12"><label class="form-label fw-bold">จำนวนพื้นที่ (ไร่)</label><input type="number" step="0.01" name="area_rai" class="form-control rounded-3"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 shadow-sm fw-bold">💾 ยืนยันและบันทึกข้อมูล</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-body p-4 text-center">
                <h6 class="fw-bold mb-3">แก้ไขค่าเพื่อสอน AI</h6>
                <input type="number" id="edit_val" class="form-control form-control-lg text-center mb-3" min="0" max="100">
                <input type="hidden" id="edit_id">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-primary rounded-pill" onclick="saveEdit()">บันทึกการแก้ไข</button>
                    <button type="button" class="btn btn-light rounded-pill btn-sm" data-bs-dismiss="modal">ยกเลิก</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // ส่วนของการวิเคราะห์รูปภาพใหม่
    $('#imageInput').change(function() {
        let file = this.files[0];
        if (file) {
            $('#loading').show();
            let formData = new FormData();
            formData.append('image', file);
            $.ajax({
                url: 'http://127.0.0.1:5050/analyze', 
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $('#loading').hide();
                    let cleanPercent = Math.round(response.trash_percentage);
                    $('#displayPercent').text(cleanPercent);
                    $('#inputPercent').val(cleanPercent);
                    let reader = new FileReader();
                    reader.onload = function(e) { $('#previewImg').attr('src', e.target.result); }
                    reader.readAsDataURL(file);
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    document.getElementById('hiddenFile').files = dataTransfer.files;
                    new bootstrap.Modal(document.getElementById('dataModal')).show();
                },
                error: function() { $('#loading').hide(); alert('ไม่สามารถติดต่อ API ได้'); }
            });
        }
    });

    // ส่วนของระบบ Feedback สอน AI (เปิด Modal แก้ไข)
    function openEditModal(id, currentVal) {
        $('#edit_id').val(id);
        $('#edit_val').val(currentVal);
        new bootstrap.Modal(document.getElementById('editModal')).show();
    }

    // บันทึกค่าที่แก้ไขลง Database (สอน AI)
    function saveEdit() {
        let id = $('#edit_id').val();
        let newVal = $('#edit_val').val();
        
        $.ajax({
            url: 'update_trash.php', // คุณต้องสร้างไฟล์นี้เพื่อ Update DB
            type: 'POST',
            data: { id: id, trash_percentage: newVal },
            success: function(res) {
                $('#val-' + id).text(newVal);
                bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
                // เปลี่ยนสี Badge ตามค่าใหม่
                let badge = $('#val-' + id).parent();
                if(newVal > 20) {
                    badge.removeClass('bg-success text-success').addClass('bg-danger text-danger');
                    badge.find('.status-pill').removeClass('bg-success').addClass('bg-danger');
                } else {
                    badge.removeClass('bg-danger text-danger').addClass('bg-success text-success');
                    badge.find('.status-pill').removeClass('bg-danger').addClass('bg-success');
                }
            }
        });
    }
</script>
</body>
</html>