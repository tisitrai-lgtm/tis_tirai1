<?php
session_start();
require_once 'db_connect.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>นำเข้าข้อมูลอ้อยจาก CSV | ระบบตรวจสอบแปลงอ้อย</title>
    <link rel="icon" href="icon/unnamed.png" type="image/png">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="index.css">
    
    <style>
        .import-container {
            max-width: 800px;
            margin: 3rem auto;
        }
        .upload-area {
            border: 3px dashed #e2e8f0;
            border-radius: 20px;
            padding: 3rem;
            text-align: center;
            background: #f8fafc;
            transition: all 0.3s;
            cursor: pointer;
        }
        .upload-area:hover {
            border-color: var(--primary);
            background: #f1f5f9;
        }
        .csv-instruction {
            background: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .instruction-table th { background: #fef3c7; color: #92400e; }

        @media (max-width: 768px) {
            .import-container { margin: 1rem auto; padding: 0 10px; }
            .upload-area { padding: 2rem 1rem; }
            .upload-area i { font-size: 3rem !important; }
            .upload-area h4 { font-size: 1.1rem; }
            .upload-area p { font-size: 0.85rem; }
            .csv-instruction { padding: 1rem; margin-bottom: 1.5rem; }
            .csv-instruction h5 { font-size: 1rem; }
            h2 { font-size: 1.5rem !important; }
        }
    </style>
</head>
<body>
<?php require("nav.php");?>

<div class="container import-container">
    <div class="glass-card-white fade-in">
        <h2 class="fw-bold mb-4"><i class='bx bxs-file-import text-primary'></i> นำเข้าข้อมูลจากไฟล์ CSV</h2>
        
        <div class="csv-instruction">
            <h5 class="fw-bold text-warning-emphasis"><i class='bx bx-info-circle'></i> คำแนะนำการเตรียมไฟล์</h5>
            <p class="mb-0 small">กรุณาเตรียมไฟล์ .csv โดยใช้หัวตาราง (Header) เป็นภาษาไทยดังนี้:</p>
            <div class="table-responsive mt-2">
                <table class="table table-sm table-bordered instruction-table mb-0" style="font-size: 0.75rem;">
                    <tr>
                        <th>ปีผลิต</th>
                        <th>หน่วยงาน</th>
                        <th>เลขสัญญา</th>
                        <th>โควต้า</th>
                        <th>ID แปลง</th>
                        <th>ไร่</th>
                        <th>ชนิดดิน</th>
                        <th>หมายเหตุ</th>
                    </tr>
                </table>
            </div>
            <p class="mt-2 mb-0 small text-danger"><i class='bx bx-error-circle'></i> <strong>สำคัญ:</strong> หากตรวจพบ ID แปลงและปีผลิตที่ตรงกับในระบบอยู่แล้ว ข้อมูลเดิมจะถูกเขียนทับทันที!</p>
        </div>

        <form action="import_csv_logic.php" method="POST" enctype="multipart/form-data" id="importForm">
            <div class="upload-area" onclick="document.getElementById('csvFile').click()">
                <i class='bx bxs-cloud-upload' style="font-size: 4rem; color: #cbd5e1;"></i>
                <h4 class="mt-3 fw-bold">คลิกเพื่อเลือกไฟล์ CSV</h4>
                <p class="text-muted">หรือลากไฟล์มาวางในพื้นที่นี้ (รองรับเฉพาะนามสกุล .csv)</p>
                <input type="file" name="csv_file" id="csvFile" accept=".csv" style="display: none;" onchange="updateFileName(this)">
                <div id="file-name-display" class="mt-3 fw-bold text-primary" style="display: none;"></div>
            </div>

            <div class="mt-4 d-grid">
                <button type="submit" class="btn btn-premium py-3" id="submitBtn" disabled>
                    <i class='bx bx-check-double'></i> ยืนยันการนำเข้าข้อมูล
                </button>
            </div>
        </form>

        <div class="mt-4 text-center">
            <a href="dashboard.php" class="text-muted text-decoration-none small"><i class='bx bx-left-arrow-alt'></i> กลับสู่แดชบอร์ด</a>
        </div>
    </div>
</div>

<script>
function updateFileName(input) {
    const display = document.getElementById('file-name-display');
    const submitBtn = document.getElementById('submitBtn');
    if (input.files && input.files[0]) {
        display.textContent = 'ไฟล์ที่เลือก: ' + input.files[0].name;
        display.style.display = 'block';
        submitBtn.disabled = false;
    } else {
        display.style.display = 'none';
        submitBtn.disabled = true;
    }
}

// Drag and drop logic
const uploadArea = document.querySelector('.upload-area');
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    uploadArea.addEventListener(eventName, e => {
        e.preventDefault();
        e.stopPropagation();
    }, false);
});

uploadArea.addEventListener('drop', e => {
    const dt = e.dataTransfer;
    const files = dt.files;
    const inputFile = document.getElementById('csvFile');
    if (files.length) {
        inputFile.files = files;
        updateFileName(inputFile);
    }
});

document.getElementById('importForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> กำลังนำเข้าข้อมูล...';
});

// Post-Import Feedback
<?php if (isset($_SESSION['import_status'])): ?>
    Swal.fire({
        icon: '<?php echo $_SESSION['import_status']; ?>',
        title: '<?php echo $_SESSION['import_status'] === 'success' ? 'สำเร็จ' : 'ผิดพลาด'; ?>',
        text: '<?php echo $_SESSION['import_message']; ?>',
        confirmButtonColor: 'var(--primary)'
    });
    <?php 
    unset($_SESSION['import_status']);
    unset($_SESSION['import_message']);
    ?>
<?php endif; ?>
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
