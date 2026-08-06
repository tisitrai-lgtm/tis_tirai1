<?php
session_start();

// ตรวจสอบ Login
if (!isset($_SESSION['statn_code'])) {
    header("Location: login.php");
    exit;
}

$statn_code = $_SESSION['statn_code'];
$statn_name = $_SESSION['statn_name'];
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Sugar System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f0f2f5; min-height: 100vh; }
        .navbar { background: #1a2a6c !important; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .nav-info { background: rgba(255, 255, 255, 0.1); padding: 5px 15px; border-radius: 50px; font-size: 0.9rem; border: 1px solid rgba(255, 255, 255, 0.2); }
        
        .upload-container { max-width: 900px; margin: 50px auto; }
        .upload-area { 
            background: white; border: 2px dashed #d1d5db; border-radius: 20px; 
            padding: 60px 20px; text-align: center; transition: 0.3s; cursor: pointer; 
        }
        .upload-area:hover, .upload-area.dragover { border-color: #1a2a6c; background: #f8fafc; }
        
        .icon-circle { 
            width: 80px; height: 80px; background: #eef2ff; color: #1a2a6c; 
            border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; 
        }
        
        .btn-convert { 
            background: #1a2a6c; color: white; border-radius: 12px; padding: 15px 40px; 
            font-weight: 600; border: none; transition: 0.3s; font-size: 1.1rem; 
        }
        .btn-convert:hover { background: #28a745; transform: translateY(-2px); color: white; }
        
        .badge-excel { background-color: #1d6f42; color: #fff; font-weight: bold; padding: 4px 12px; border-radius: 6px; }
        
        /* Table Preview Style */
        .table-preview { font-size: 0.75rem; }
        .modal-xl { max-width: 95%; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="#">
            <i data-lucide="layout-dashboard" class="me-2"></i> Sugar System
        </a>
        <div class="d-flex align-items-center">
            <div class="nav-info text-white me-3">
                <i data-lucide="map-pin" style="width: 14px;"></i>
                สถานี: <span class="fw-bold"><?= htmlspecialchars($statn_code) ?> — <?= htmlspecialchars($statn_name) ?></span>
            </div>
            <a href="login.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                <i data-lucide="log-out" style="width: 14px;"></i> ออกจากระบบ
            </a>
        </div>
    </div>
</nav>

<div class="container upload-container">
    <div class="text-center mb-5">
        <h2 class="fw-bold text-dark">
            ระบบแปลงไฟล์ข้อมูลอ้อย <span class="text-secondary">.DBF</span> 
            <i data-lucide="chevrons-right" class="d-inline-block"></i> 
            <span class="badge-excel">EXCEL (.xlsx)</span>
        </h2>
        <p class="text-muted">อัปโหลดไฟล์ DBF เพื่อตรวจสอบข้อมูล 41 ฟิลด์ก่อนบันทึกลงระบบ</p>
    </div>

    <div class="card shadow-sm border-0 p-4 rounded-4">
        <form id="uploadForm" enctype="multipart/form-data">
            <div class="upload-area" id="dropZone" onclick="document.getElementById('fileInput').click()">
                <div class="icon-circle">
                    <i data-lucide="file-spreadsheet" size="40"></i>
                </div>
                <h5 class="fw-bold">ลากไฟล์ .DBF มาวางที่นี่ หรือคลิกเพื่อเลือกไฟล์</h5>
                <p class="text-muted small">ระบบจะทำการ Preview ข้อมูลให้ตรวจสอบก่อนการบันทึกจริง</p>
                <div id="fileInfo" class="mt-3 fw-bold text-success"></div>
                <input type="file" name="dbf_file" id="fileInput" class="d-none" accept=".dbf" required>
            </div>

            <div class="mt-4 text-center">
                <button type="submit" class="btn btn-convert shadow">
                    ตรวจสอบข้อมูลก่อนแปลง <i data-lucide="search" class="ms-2" style="width: 18px;"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="previewModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i data-lucide="table" class="me-2"></i> ตรวจสอบข้อมูล 41 ฟิลด์ (10 แถวแรก)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <div id="previewContent">
                    </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                <form action="process.php" method="POST" enctype="multipart/form-data" id="finalForm">
                    <button type="button" id="confirmBtn" class="btn btn-success px-4">
                        <i data-lucide="download" class="me-1"></i> ข้อมูลถูกต้อง บันทึกและดาวน์โหลด Excel
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    lucide.createIcons();

    const uploadForm = document.getElementById('uploadForm');
    const fileInput = document.getElementById('fileInput');
    const fileInfo = document.getElementById('fileInfo');
    const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
    const confirmBtn = document.getElementById('confirmBtn');

    // จัดการการเลือกไฟล์
    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            fileInfo.innerHTML = `<i data-lucide="check-circle" class="d-inline-block" style="width:16px;"></i> พร้อมตรวจสอบ: ${e.target.files[0].name}`;
            lucide.createIcons();
        }
    });

    // เมื่อกดปุ่ม "ตรวจสอบข้อมูลก่อนแปลง"
    uploadForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        document.getElementById('previewContent').innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary"></div><p class="mt-2">กำลังโหลดข้อมูลตัวอย่าง...</p></div>';
        previewModal.show();

        fetch('preview.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(html => {
            document.getElementById('previewContent').innerHTML = html;
            lucide.createIcons();
        })
        .catch(err => {
            document.getElementById('previewContent').innerHTML = '<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
        });
    });

    // เมื่อกดยืนยันจาก Modal
    confirmBtn.addEventListener('click', () => {
        const finalForm = document.getElementById('finalForm');
        // คัดลอกไฟล์จาก input หลักมาใส่ input ของฟอร์มยืนยัน
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(fileInput.files[0]);
        
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'file';
        hiddenInput.name = 'dbf_file';
        hiddenInput.files = dataTransfer.files;
        hiddenInput.style.display = 'none';
        
        finalForm.appendChild(hiddenInput);
        
        confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> กำลังบันทึก...';
        confirmBtn.disabled = true;
        
        finalForm.submit();
    });
</script>

</body>
</html>