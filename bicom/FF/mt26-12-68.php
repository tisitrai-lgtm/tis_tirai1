<?php
session_start();
require_once 'db_connect.php';
date_default_timezone_set('Asia/Bangkok');
// รับค่าจากรหัสหน่วยและวันที่
$my_statn = $_SESSION['statn_code'] ?? 101; 
$target_date = $_GET['report_date'] ?? date('Y-m-d');

// --- เพิ่มส่วน: ดึงชื่อหน่วยงานจากตาราง stations ---
$stmt_station = $pdo->prepare("SELECT statn_name FROM stations WHERE statn_code = ?");
$stmt_station->execute([$my_statn]);
$station_row = $stmt_station->fetch();
$my_statn_name = $station_row ? $station_row['statn_name'] : "ไม่ระบุชื่อหน่วย";

// 1. ดึงประวัติทะเบียน (Suggest Box)
$stmt_history = $pdo->prepare("
    SELECT main_truck_license, trailer_license, harvester_code 
    FROM daily_truck_reports 
    WHERE statn_code = ? 
    GROUP BY main_truck_license, trailer_license, harvester_code
    ORDER BY id DESC LIMIT 200
");
$stmt_history->execute([$my_statn]);
$history = $stmt_history->fetchAll(PDO::FETCH_ASSOC);

// 2. ดึงข้อมูลตารางสรุปทั้งหมด
$stmt_table = $pdo->prepare("
    SELECT * FROM daily_truck_reports 
    WHERE statn_code = ? AND report_date = ?
    ORDER BY created_at DESC
");
$stmt_table->execute([$my_statn, $target_date]);
$reports = $stmt_table->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="bg/v2.png" type="image/png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บันทึกรถเข้าหีบ - <?= $my_statn_name ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root { --p-purple: #1a2a6c; --d-purple: #1a2a6c; --l-purple: #f8f4ff; --accent: #1a2a6c; }
        body { background-color: #f4f5f7; font-family: 'Sarabun', sans-serif; }
        
        .card-premium { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); position: relative; }
        
        .header-gradient { background: linear-gradient(45deg, #1a2a6c, #b21f1f, #fdbb2d); color: white; padding: 1.5rem; }
        .form-control-custom { border-radius: 12px; border: 2px solid #e0e0e0; padding: 12px 18px; transition: 0.3s; }
        .form-control-custom:focus { border-color: var(--p-purple); outline: none; box-shadow: 0 0 15px rgba(26,42,108,0.1); }
        
        .suggest-box { 
            position: absolute; 
            width: 100%; 
            z-index: 9999 !important; 
            background: white; 
            border-radius: 15px; 
            display: none; 
            box-shadow: 0 10px 40px rgba(0,0,0,0.15); 
            margin-top: 8px; 
            border: 1px solid #eee;
            max-height: 250px;
            overflow-y: auto;
        }
        
        .suggest-item { padding: 12px 20px; cursor: pointer; border-bottom: 1px solid #f8f9fa; }
        .suggest-item:hover { background: var(--l-purple); }
        .btn-save { background: var(--p-purple); color: white; border-radius: 12px; font-weight: 600; border:none; }
        .btn-save:hover:not(:disabled) { opacity: 0.9; transform: translateY(-2px); }
        .text-purple { color: var(--p-purple); }
        .pagination .page-link { color: var(--p-purple); border-radius: 8px; margin: 0 3px; border: none; background: #eee; }
        .pagination .page-item.active .page-link { background-color: var(--p-purple); color: white; }
        
        /* สไตล์ปุ่มลบ */
        .btn-delete { color: #dc3545; background: #fff5f5; border: 1px solid #ffebeb; border-radius: 8px; padding: 5px 10px; transition: 0.2s; }
        .btn-delete:hover { background: #dc3545; color: white; }

        .table-responsive { overflow: visible !important; }
        /* ปรับแต่งพิเศษสำหรับหน้าจอขนาดเล็ก (Mobile) */
@media (max-width: 768px) {
    .container {
        padding-left: 10px;
        padding-right: 10px;
    }
    /* ซ่อนคอลัมน์สถานะ (คอลัมน์ที่ 5) บนมือถือ */
    #reportTable th:nth-child(5), 
    #reportTable td:nth-child(5) {
        display: none;
    }

    /* ขยายความกว้างของคอลัมน์ทะเบียนให้เต็มที่ */
    .text-purple {
        font-size: 1rem;
    }

    .card-header .row {
        flex-direction: column; /* เรียง Search กับ Dropdown เป็นบนลงล่าง */
    }
    
    .card-header .col-md-5, .card-header .col-md-3 {
        width: 100%;
        margin-top: 10px;
    }
    .input-group {
        width: 100%; /* ช่องค้นหาเต็มความกว้างจอ */
    }
    
    #rowLimit {
        width: 100% !important; /* ตัวเลือกจำนวนแถวเต็มความกว้างจอ */
        text-align: center;
    }
    /* 1. ทำให้ wrapper ของตารางเลื่อนได้ */
    .table-responsive {
        border-radius: 15px;
        overflow-x: auto !important; /* ยอมให้เลื่อนซ้ายขวา */
        -webkit-overflow-scrolling: touch;
    }

    /* 2. บังคับความกว้างขั้นต่ำเพื่อให้คอลัมน์ไม่บีบตัว */
    .table {
        min-width: 600px; 
    }

    /* 3. ปรับขนาดตัวอักษรในตารางให้เล็กลงนิดหน่อยเพื่อให้เห็นข้อมูลเยอะขึ้น */
    .table th, .table td {
        padding: 12px 8px !important;
        font-size: 0.85rem;
    }

    /* 4. ปรับขนาดปุ่มลบให้ใหญ่ขึ้นเพื่อให้กดด้วยนิ้วง่ายขึ้น */
    .btn-delete {
        padding: 8px 12px !important;
    }

    /* ปรับหัว Header ให้ตัวอักษรเล็กลงหน่อยและชิดขอบน้อยลง */
    .header-gradient, .d-flex.justify-content-between.text-white {
        flex-direction: column; /* เรียงเป็นแนวตั้ง */
        align-items: flex-start !important;
        padding: 1rem !important;
    }
    
    .d-flex.justify-content-between.text-white .bg-white {
        margin-top: 15px;
        width: 100%; /* ให้ช่องเลือกวันที่เต็มจอ */
    }

    /* ปรับระยะห่างของการ์ดบันทึกข้อมูล */
    .card-body {
        padding: 1.5rem !important;
    }

    /* ปรับปุ่มลบในตารางให้กดง่ายขึ้น (นิ้วจิ้มสะดวก) */
    .btn-delete {
        padding: 10px 15px;
        width: 100%;
        margin-bottom: 5px;
    }

    /* บังคับให้ตารางตัวหนังสือไม่เล็กจนเกินไป */
    .table {
        min-width: 600px; /* ยอมให้ scroll แนวนอนได้ในส่วนตาราง เพื่อไม่ให้ข้อมูลเบียดกัน */
    }
    
    .suggest-box {
        position: fixed; /* ให้กล่องแนะนำเต็มจอด้านล่างเมื่ออยู่บนมือถือ */
        bottom: 0;
        left: 0;
        width: 100%;
        margin-top: 0;
        border-radius: 20px 20px 0 0;
        max-height: 50vh;
    }
}
    </style>
</head>
<body>

<?php include 'nvb.php'; ?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-end mb-4 text-white p-4 rounded-4 shadow-sm" style="background: var(--p-purple);">
        <div>
            <h2 class="fw-bold mb-1"><i class="bi bi-truck me-2"></i><?= $my_statn_name ?></h2>
            <p class="mb-0 opacity-75">รหัสหน่วย: <?= $my_statn ?> | บันทึกข้อมูลรถบรรทุก</p>
        </div>
        <div class="bg-white p-2 rounded-3">
            <input type="date" class="form-control border-0 fw-bold text-dark" value="<?= $target_date ?>" onchange="location.href='?report_date='+this.value">
        </div>
    </div>

    <div class="card card-premium mb-5">
        <div class="card-body p-4">
            <form action="save_report.php" method="POST" autocomplete="off">
                <input type="hidden" name="report_date" value="<?= $target_date ?>">
                <div class="row g-4">
                   <div class="row g-3"> <div class="col-12 col-md-4 position-relative">
                        <label class="form-label fw-bold">ทะเบียน (สท.xx-xxxx)</label>
                        <input type="text" name="main_license" id="main_license" class="form-control-custom w-100" placeholder="อต.xx-xxxx" maxlength="10" required>
                        <div id="suggest_box" class="suggest-box"></div>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label fw-bold">ทะเบียนลูกพ่วง</label>
                        <input type="text" name="trailer_license" id="trailer_license" class="form-control-custom w-100" placeholder="อต.xx-xxxx" maxlength="10">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label fw-bold text-center d-block">เลขรถตัด</label>
                        <input type="text" name="h_code" id="h_code" class="form-control-custom w-100 text-center fw-bold text-danger" placeholder="0" required>
                    </div>
                    <div class="col-6 col-md-3 d-grid">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" id="btn_save" class="btn btn-save btn-lg" disabled><i class="bi bi-check-circle me-2"></i>บันทึกข้อมูล</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-premium shadow-sm">
        <div class="card-header bg-white p-4 border-0">
            <div class="row g-3 align-items-center">
                <div class="col-md-4">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-table me-2"></i>รายการวันนี้</h5>
                </div>
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="bi bi-search"></i></span>
                        <input type="text" id="tableSearch" class="form-control bg-light border-0" placeholder="ค้นหาทะเบียน หรือ เบอร์รถตัด...">
                    </div>
                </div>
                <div class="col-md-3 text-end">
                    <select id="rowLimit" class="form-select form-select-sm d-inline-block w-auto border-0 bg-light fw-bold">
                        <option value="15"> 15 </option>
                        <option value="25"> 25 </option>
                        <option value="50"> 50 </option>
                    </select>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="reportTable">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4 text-center" style="width: 80px;">จัดการ</th>
                        <th>เบอร์รถตัด</th>
                        <th>ทะเบียน</th>
                        <th>ทะเบียนพ่วง</th>
                        <th class="text-center">สถานะ</th>
                    </tr>
                </thead>
                <tbody id="tableBody"></tbody>
            </table>
        </div>
        <div class="card-footer bg-white p-4 border-0">
            <nav><ul class="pagination justify-content-center mb-0" id="pagination"></ul></nav>
        </div>
    </div>
</div>

<script>
const reports = <?= json_encode($reports) ?>;
const historyData = <?= json_encode($history) ?>;
// 1. ปรับ Regex ให้รองรับอังกฤษ A-Z ด้วย
const plateRegex = /^[ก-ฮA-Z]{1,2}\.[0-9]{2}\-[0-9]{4}$/;

const mainInp = document.getElementById('main_license');
const trailInp = document.getElementById('trailer_license');
const hCodeInp = document.getElementById('h_code');
const btnSave = document.getElementById('btn_save');
const box = document.getElementById('suggest_box');
const tableBody = document.getElementById('tableBody');
const searchInp = document.getElementById('tableSearch');
const rowLimitSelect = document.getElementById('rowLimit');
const paginationUl = document.getElementById('pagination');

let currentPage = 1;
let rowsPerPage = 15;
let filteredData = [...reports];

// --- ฟังก์ชันจัดการ Mask โดยใช้ mainInp ตรงๆ ---
function applyStrictMask() {
    // รับค่าเฉพาะ ไทย, อังกฤษ, ตัวเลข
    let val = mainInp.value.replace(/[^ก-ฮa-zA-Z0-9]/g, '');
    let formatted = "";
    
    // แยกส่วนตัวอักษร (ก-ฮ หรือ a-z)
    let charMatch = val.match(/[ก-ฮa-zA-Z]+/);
    if (charMatch) {
        // จำกัดแค่ 2 ตัวแรก และแปลงเป็นตัวพิมพ์ใหญ่
        let chars = charMatch[0].substring(0, 2).toUpperCase();
        formatted += chars;
        // ลบตัวอักษรออกจากชุดข้อมูล เพื่อจัดการตัวเลขต่อ
        val = val.replace(charMatch[0], '');
    }

    // ใส่จุดและจัดรูปแบบตัวเลข (00-0000)
    if (formatted.length > 0 && val.length > 0) {
        formatted += ".";
        if (val.length >= 2) {
            formatted += val.substring(0, 2) + "-" + val.substring(2, 6);
        } else {
            formatted += val;
        }
    }
    mainInp.value = formatted;
}

// ฟังก์ชันเสริมสำหรับทะเบียนพ่วง (ถ้ายังอยากให้พิมพ์ได้เหมือนกัน)
function applyTrailMask(input) {
    let val = input.value.replace(/[^ก-ฮa-zA-Z0-9]/g, '');
    let formatted = "";
    let charMatch = val.match(/[ก-ฮa-zA-Z]+/);
    if (charMatch) {
        let chars = charMatch[0].substring(0, 2).toUpperCase();
        formatted += chars;
        val = val.replace(charMatch[0], '');
    }
    if (formatted.length > 0 && val.length > 0) {
        formatted += ".";
        if (val.length >= 2) { formatted += val.substring(0, 2) + "-" + val.substring(2, 6); } 
        else { formatted += val; }
    }
    input.value = formatted;
}

// --- Event Listeners ---

mainInp.addEventListener('input', function() {
    applyStrictMask(); // เรียกใช้ฟังก์ชันที่ผูกกับ mainInp โดยตรง
    
    // ตรวจสอบความถูกต้องเพื่อเปิดปุ่มบันทึก
    btnSave.disabled = !plateRegex.test(mainInp.value);

    // Suggest Box Logic
    if (mainInp.value.length >= 1) {
        const matches = historyData.filter(h => h.main_truck_license.includes(mainInp.value)).slice(0, 10);
        if (matches.length > 0) {
            box.innerHTML = matches.map(m => `
                <div class="suggest-item d-flex justify-content-between" onclick="selectItem('${m.main_truck_license}', '${m.trailer_license}', '${m.harvester_code}')">
                    <span><b>${m.main_truck_license}</b> <small class="ms-2">ลูก: ${m.trailer_license || '-'}</small></span>
                    <span class="badge bg-light text-dark">#${m.harvester_code}</span>
                </div>
            `).join('');
            box.style.display = 'block';
        } else { box.style.display = 'none'; }
    } else { box.style.display = 'none'; }
});

trailInp.addEventListener('input', function() { 
    applyTrailMask(this); 
});

// --- โค้ดส่วนจัดการตาราง (คงเดิม) ---
window.deleteRecord = (id) => {
    if (confirm('ยืนยันการลบรายการนี้?')) {
        window.location.href = `delete_report.php?id=${id}&report_date=<?= $target_date ?>`;
    }
};

function renderTable() {
    const start = (currentPage - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    const paginatedItems = filteredData.slice(start, end);

    tableBody.innerHTML = paginatedItems.length > 0 ? paginatedItems.map(row => `
        <tr>
            <td class="ps-4 text-center">
                <button class="btn btn-delete btn-sm" onclick="deleteRecord(${row.id})">
                    <i class="bi bi-trash3"></i>
                </button>
            </td>
            <td><span class="badge bg-light text-dark border px-3">#${row.harvester_code}</span></td>
            <td class="fw-bold text-purple">${row.main_truck_license}</td>
            <td class="text-secondary">${row.trailer_license || '-'}</td>
            <td class="text-center text-success small"><i class="bi bi-dot"></i> สำเร็จ</td>
        </tr>
    `).join('') : `<tr><td colspan="5" class="text-center py-5 text-muted">ไม่พบข้อมูล</td></tr>`;
    renderPagination();
}

function renderPagination() {
    const pageCount = Math.ceil(filteredData.length / rowsPerPage);
    let html = '';
    for (let i = 1; i <= pageCount; i++) {
        html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
                 </li>`;
    }
    paginationUl.innerHTML = html;
}

window.changePage = (page) => { currentPage = page; renderTable(); };

searchInp.addEventListener('input', (e) => {
    const term = e.target.value.toLowerCase();
    filteredData = reports.filter(r => r.main_truck_license.includes(term) || r.harvester_code.toString().includes(term));
    currentPage = 1;
    renderTable();
});

rowLimitSelect.addEventListener('change', (e) => {
    rowsPerPage = parseInt(e.target.value);
    currentPage = 1;
    renderTable();
});

window.selectItem = (m, t, h) => {
    mainInp.value = m; trailInp.value = t || ''; hCodeInp.value = h;
    box.style.display = 'none'; btnSave.disabled = false;
};

document.addEventListener('click', (e) => { if (e.target !== mainInp) box.style.display = 'none'; });

renderTable();
</script>
<?php include 'alert_script.php'; ?>
</body>
</html>