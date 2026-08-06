<?php
// add_queue.php
include 'db_connect.php'; 
date_default_timezone_set('Asia/Bangkok');
session_start(); // เพิ่มบรรทัดนี้ไว้บนสุดของไฟล์ add_queue.php
$ui_size = $_SESSION['ui_size'] ?? 'normal';
// --- Logic ส่วนการจัดการข้อมูล ---
$max_queue_limit = 850;
$thai_now = date('Y-m-d H:i:s'); 

function getActiveRoundInfo($conn) {
    $sql = "SELECT r.round_id, r.round_number FROM rounds r WHERE r.is_active = 1 ORDER BY r.round_number DESC LIMIT 1";
    $result = $conn->query($sql);
    return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
}
$current_round_info = getActiveRoundInfo($conn);

// ส่วนดึงทะเบียนรถไม่ซ้ำกันสำหรับ Datalist
function getUniquePlates($conn) {
    $sql = "SELECT DISTINCT plate FROM (
                SELECT tractor_plate AS plate FROM queue_entries
                UNION 
                SELECT trailer_plate AS plate FROM queue_entries WHERE trailer_plate IS NOT NULL
            ) AS combined WHERE plate != '' ORDER BY plate ASC";
    $result = $conn->query($sql);
    $plates = [];
    while ($row = $result->fetch_assoc()) { $plates[] = $row['plate']; }
    return $plates;
}
$existing_plates = getUniquePlates($conn);

// --- เพิ่มเติม: ดึงคู่ทะเบียนรถหัวและพ่วงล่าสุดมาทำ Auto-fill ---
function getPlatePairs($conn) {
    $sql = "SELECT tractor_plate, trailer_plate FROM (
                SELECT tractor_plate, trailer_plate, created_at 
                FROM queue_entries 
                WHERE trailer_plate IS NOT NULL AND trailer_plate != ''
                ORDER BY created_at DESC
            ) AS tmp GROUP BY tractor_plate";
    $result = $conn->query($sql);
    $pairs = [];
    while ($row = $result->fetch_assoc()) {
        $pairs[strtoupper($row['tractor_plate'])] = strtoupper($row['trailer_plate']);
    }
    return $pairs;
}
$plate_pairs = getPlatePairs($conn);

// --- การจัดการ CRUD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'start_new_round') {
            $last_round = $conn->query("SELECT MAX(round_number) AS max_num FROM rounds")->fetch_assoc();
            $new_round_number = ($last_round['max_num'] ?? 0) + 1;
            $conn->query("INSERT INTO rounds (round_number, start_time, max_queue, is_active) VALUES ($new_round_number, '$thai_now', $max_queue_limit, 1)");
            header("Location: add_queue.php?view_round=$new_round_number&msg=สร้างรอบใหม่สำเร็จ&type=success"); exit;
        } 
        elseif ($_POST['action'] === 'delete_entry') {
            $conn->query("DELETE FROM queue_entries WHERE entry_id = " . (int)$_POST['entry_id']);
            header("Location: add_queue.php?view_round=".$_POST['view_round']."&msg=ลบคิวเรียบร้อย&type=danger"); exit;
        }
        elseif ($_POST['action'] === 'update_entry') {
            $u_trailer = empty($_POST['trailer_plate']) ? "NULL" : "'".strtoupper(trim($_POST['trailer_plate']))."'";
            $sql_update = "UPDATE queue_entries SET queue_number='{$_POST['manual_queue_number']}', tractor_plate='".strtoupper(trim($_POST['tractor_plate']))."', trailer_plate=$u_trailer, created_at='$thai_now' WHERE entry_id=".(int)$_POST['entry_id'];
            $conn->query($sql_update);
            header("Location: add_queue.php?view_round=".$_POST['current_round_number']."&msg=แก้ไขและดันคิวขึ้นบนสุดแล้ว&type=success"); exit;
        }
    } 
    elseif (isset($_POST['tractor_plate']) && !isset($_POST['entry_id'])) {
        $res_round = $conn->query("SELECT round_id FROM rounds WHERE round_number = " . (int)$_POST['current_round_number']);
        $round_id = $res_round->fetch_assoc()['round_id'];
        $u_trailer = empty($_POST['trailer_plate']) ? "NULL" : "'".strtoupper(trim($_POST['trailer_plate']))."'";
        $conn->query("INSERT INTO queue_entries (round_id, tractor_plate, trailer_plate, queue_number, created_at) VALUES ($round_id, '".strtoupper(trim($_POST['tractor_plate']))."', $u_trailer, '{$_POST['manual_queue_number']}', '$thai_now')");
        header("Location: add_queue.php?view_round=".$_POST['current_round_number']."&msg=บันทึกคิวสำเร็จ&type=success"); exit;
    }
}

$edit_entry_id = $_GET['entry_id'] ?? null;
$edit_data = $edit_entry_id ? $conn->query("SELECT * FROM queue_entries WHERE entry_id = " . (int)$edit_entry_id)->fetch_assoc() : null;
$view_round_num = $_GET['view_round'] ?? ($current_round_info['round_number'] ?? 0);

$current_queue_entries = [];
if ($view_round_num > 0) {
    $res = $conn->query("SELECT q.* FROM queue_entries q JOIN rounds r ON q.round_id = r.round_id WHERE r.round_number = $view_round_num ORDER BY q.created_at DESC");
    while ($row = $res->fetch_assoc()) { $current_queue_entries[] = $row; }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการคิวรถบรรทุกอ้อย</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #3b82f6; --success: #10b981; --danger: #ef4444; --warning: #f59e0b; --dark: #1e293b; --bg: #f8fafc; }
        
        * { transition: all 0.2s ease-in-out; box-sizing: border-box; }
        body { font-family: 'Sarabun', sans-serif; background: var(--bg); margin: 0; padding: 0px; color: #334155; }
        .container { 
        max-width: 1400px; /* ปรับให้เท่ากับ Navbar/Footer */
        width: 95%;        /* เผื่อขอบไว้เล็กน้อยสำหรับจอเล็ก */
        margin: 0 auto; 
    }

        /* Animation */
        @keyframes fadeInDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes rowIn { from { opacity: 0; transform: translateX(-5px); } to { opacity: 1; transform: translateX(0); } }

        /* Header & Control */
        .header-box { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; background: var(--dark); padding: 20px; border-radius: 20px; color: white; margin-bottom: 25px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .select-round { padding: 10px 15px; border-radius: 12px; border: none; font-family: 'Sarabun'; font-weight: bold; cursor: pointer; background: white; color: var(--dark); outline: none; }
        .select-round:hover { transform: scale(1.02); background: #f1f5f9; }

        /* Section Cards */
.card { 
        background: white; 
        padding: 30px;      /* เพิ่มพื้นที่ว่างภายใน Card */
        border-radius: 24px; 
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); 
        margin-bottom: 30px; 
        border: 1px solid #e2e8f0; 
        animation: fadeInDown 0.4s ease-out; 
    }        h2 { margin-top: 0; font-size: 1.25rem; display: flex; align-items: center; gap: 10px; }

        /* Inputs & Buttons */
        .input-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 0.9rem; color: #64748b; }
        input[type="text"] { width: 100%; padding: 14px; border: 2px solid #e2e8f0; border-radius: 12px; outline: none; font-family: 'Sarabun'; }
        input[type="text"]:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
        .edit-mode { border-color: var(--warning) !important; background: #fffbeb !important; }

        .btn { padding: 14px 24px; border: none; border-radius: 12px; font-weight: bold; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-family: 'Sarabun'; }
        .btn-primary { background: var(--primary); color: white; width: 100%; justify-content: center; }
        .btn-primary:hover { filter: brightness(1.1); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); }
        .btn-success { background: var(--success); color: white; }

        /* Table Style */
        .table-responsive { overflow-x: auto; }
table { 
        width: 100%; 
        border-collapse: separate; 
        border-spacing: 0 12px; /* เพิ่มระยะห่างระหว่างแถว */
    }        th { padding: 15px; color: #94a3b8; font-weight: 500; text-align: left; font-size: 0.85rem; text-transform: uppercase; }
        td { padding: 20px 15px; background: white; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; }
        tr td:first-child { border-left: 1px solid #f1f5f9; border-radius: 12px 0 0 12px; }
        tr td:last-child { border-right: 1px solid #f1f5f9; border-radius: 0 12px 12px 0; }
        
        .q-row { animation: rowIn 0.3s ease-out; }
        .q-row:hover td { background: #f8fafc; cursor: default; }

        .badge { padding: 6px 12px; border-radius: 8px; font-weight: bold; font-size: 0.9rem; }
        .badge-blue { background: #eff6ff; color: #1e40af; }
        .badge-gray { background: #f1f5f9; color: #475569; }

        /* Pagination */
        .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 25px; }
        .p-item { padding: 10px 16px; background: white; border: 1px solid #e2e8f0; border-radius: 10px; cursor: pointer; text-decoration: none; color: #64748b; font-weight: 500; }
        .p-item:hover:not(.dots) { background: #f1f5f9; color: var(--primary); }
        .p-item.active { background: var(--primary); color: white; border-color: var(--primary); }

        /* Toast Message */
        #toast-wrap { position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%); z-index: 1000; }
        .toast { padding: 15px 30px; border-radius: 50px; color: white; box-shadow: 0 10px 25px rgba(0,0,0,0.2); margin-bottom: 10px; animation: fadeInUp 0.4s ease; font-weight: 500; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    <?php if($ui_size == 'large'): ?>
        /* ขยายฟอนต์และตารางให้ใหญ่ขึ้น */
        body { font-size: 1.2rem; }
        .container { max-width: 98% !important; }
        input[type="text"] { font-size: 1.5rem !important; padding: 20px !important; }
        .btn { font-size: 1.3rem !important; padding: 20px !important; }
        th { font-size: 1.1rem !important; }
        td { font-size: 1.4rem !important; padding: 25px 15px !important; }
        .badge { font-size: 1.25rem !important; padding: 10px 20px !important; }
        h1 { font-size: 2.2rem !important; }
        h2 { font-size: 1.8rem !important; }
    <?php endif; ?>
    /* --- UI Large Mode --- */
body.ui-large { font-size: 1.25rem; }
body.ui-large .container { max-width: 98% !important; }
body.ui-large input[type="text"] { font-size: 1.6rem !important; padding: 20px !important; }
body.ui-large td { font-size: 1.5rem !important; padding: 25px 15px !important; }
body.ui-large .badge { font-size: 1.3rem !important; }
body.ui-large h1 { font-size: 2.5rem !important; }

/* --- Dark Mode --- */
body.dark-mode { background-color: #0f172a !important; color: #f1f5f9 !important; }
body.dark-mode .card { background: #1e293b !important; border-color: #334155 !important; color: white !important; }
body.dark-mode td { background: #1e293b !important; border-color: #334155 !important; color: #cbd5e1 !important; }
body.dark-mode input[type="text"] { background: #0f172a !important; border-color: #334155 !important; color: white !important; }
body.dark-mode .badge-blue { background: #1e40af !important; color: #dbeafe !important; }
body.dark-mode .badge-gray { background: #334155 !important; color: #cbd5e1 !important; }
body.dark-mode .q-row:hover td { background: #334155 !important; }
body.dark-mode h2, body.dark-mode label { color: #f1f5f9 !important; }
    </style>
</head>
<body>
<?php include 'nvb.php'; ?>

<div id="toast-wrap"></div>

<datalist id="plateSuggestions">
    <?php foreach ($existing_plates as $plate): ?>
        <option value="<?php echo htmlspecialchars($plate); ?>">
    <?php endforeach; ?>
</datalist>

<div class="container">
    <div class="header-box">
        <div style="display:flex; align-items:center; gap:15px;">
            <h1 style="margin:0; font-size:1.5rem;">🚜รถเข้า</h1>
            <form method="GET" id="roundForm">
                <select name="view_round" class="select-round" onchange="document.getElementById('roundForm').submit()">
                    <?php
                    $rounds = $conn->query("SELECT round_number FROM rounds ORDER BY round_number DESC");
                    while($r = $rounds->fetch_assoc()){
                        $selected = ($r['round_number'] == $view_round_num) ? 'selected' : '';
                        echo "<option value='{$r['round_number']}' $selected>รอบที่ {$r['round_number']}</option>";
                    }
                    ?>
                </select>
            </form>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="start_new_round">
            <button type="submit" class="btn btn-success">➕ เริ่มรอบใหม่</button>
        </form>
    </div>

    <div class="card">
        <h2><?php echo $edit_entry_id ? '✏️ แก้ไขข้อมูลคิว' : '📝 ลงทะเบียนคิวรถ'; ?></h2>
        <form method="POST">
            <input type="hidden" name="current_round_number" value="<?php echo $view_round_num; ?>">
            <?php if($edit_entry_id): ?>
                <input type="hidden" name="action" value="update_entry">
                <input type="hidden" name="entry_id" value="<?php echo $edit_entry_id; ?>">
            <?php endif; ?>
            
            <div class="input-grid">
                <div class="form-group">
                    <label>หมายเลขคิว</label>
                    <input type="text" name="manual_queue_number" required value="<?php echo $edit_data['queue_number'] ?? ''; ?>" class="<?php echo $edit_entry_id ? 'edit-mode' : ''; ?>" placeholder="เช่น 001">
                </div>
                <div class="form-group">
                    <label>ทะเบียนรถหัว</label>
                    <input type="text" name="tractor_plate" id="tractor_plate" list="plateSuggestions" required value="<?php echo $edit_data['tractor_plate'] ?? ''; ?>" class="<?php echo $edit_entry_id ? 'edit-mode' : ''; ?>" placeholder="พิมพ์เลขทะเบียน..." autocomplete="off">
                </div>
                <div class="form-group">
                    <label>ทะเบียนรถพ่วง</label>
                    <input type="text" name="trailer_plate" id="trailer_plate" list="plateSuggestions" value="<?php echo $edit_data['trailer_plate'] ?? ''; ?>" class="<?php echo $edit_entry_id ? 'edit-mode' : ''; ?>" placeholder="ถ้ามี..." autocomplete="off">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                <?php echo $edit_entry_id ? '💾 บันทึกการเปลี่ยนแปลง' : '✅ ยืนยันการลงทะเบียน'; ?>
            </button>
            <?php if($edit_entry_id): ?>
                <a href="add_queue.php?view_round=<?php echo $view_round_num; ?>" style="display:block; text-align:center; margin-top:10px; color:#64748b; text-decoration:none; font-size:0.9rem;">❌ ยกเลิกการแก้ไข</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:15px;">
            <h2>📋 รายการคิวรอบที่ <?php echo $view_round_num; ?></h2>
            <div style="position:relative; width:300px;">
                <input type="text" id="tableSearch" onkeyup="doSearch()" placeholder="🔍 ค้นหาคิวหรือทะเบียน..." style="padding:10px 15px 10px 40px; border-radius:10px;">
                <span style="position:absolute; left:15px; top:12px; opacity:0.3;">🔍</span>
            </div>
        </div>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ลำดับคิว</th>
                        <th>ทะเบียนหัว</th>
                        <th>ทะเบียนพ่วง</th>
                        <th>เวลาลงทะเบียน</th>
                        <th style="text-align:center;">เครื่องมือ</th>
                    </tr>
                </thead>
                <tbody id="queueTable">
                    <?php if(empty($current_queue_entries)): ?>
                        <tr><td colspan="5" style="text-align:center; color:#94a3b8; padding:40px;">ยังไม่มีข้อมูลในรอบนี้</td></tr>
                    <?php endif; ?>
                    <?php foreach($current_queue_entries as $row): ?>
                    <tr class="q-row">
                        <td style="font-weight:700; color:var(--primary); font-size:1.1rem;"><?php echo $row['queue_number']; ?></td>
                        <td><span class="badge badge-blue"><?php echo $row['tractor_plate']; ?></span></td>
                        <td><?php echo $row['trailer_plate'] ? '<span class="badge badge-gray">'.$row['trailer_plate'].'</span>' : '<span style="color:#cbd5e1">-</span>'; ?></td>
                        <td style="color:#64748b; font-size:0.85rem; line-height: 1.4;">
                            <?php 
                                $t = strtotime($row['created_at']);
                                $thai_months = [1 => "ม.ค.", 2 => "ก.พ.", 3 => "มี.ค.", 4 => "เม.ย.", 5 => "พ.ค.", 6 => "มิ.ย.", 7 => "ก.ค.", 8 => "ส.ค.", 9 => "ก.ย.", 10 => "ต.ค.", 11 => "พ.ย.", 12 => "ธ.ค."];
                                $d = date('j', $t); $m = $thai_months[(int)date('n', $t)]; $y = date('Y', $t) + 543; $time = date('H:i', $t);
                            ?>
                            <div style="color: #1e293b; font-weight: bold;">📅 <?php echo "$d $m $y"; ?></div>
                            <div style="font-size: 0.9rem; color: var(--primary); font-weight: 500;">🕒 <?php echo $time; ?> น.</div>
                        </td>
                        <td style="text-align:center;">
                            <div style="display:flex; justify-content:center; gap:10px;">
                                <a href="add_queue.php?action=edit&entry_id=<?php echo $row['entry_id']; ?>&view_round=<?php echo $view_round_num; ?>" class="btn" style="background:#f1f5f9; padding:8px; border-radius:8px;">✏️</a>
                                <form method="POST" onsubmit="return confirm('คุณต้องการลบคิวนี้ใช่หรือไม่?')">
                                    <input type="hidden" name="action" value="delete_entry">
                                    <input type="hidden" name="entry_id" value="<?php echo $row['entry_id']; ?>">
                                    <input type="hidden" name="view_round" value="<?php echo $view_round_num; ?>">
                                    <button type="submit" class="btn" style="background:#fef2f2; padding:8px; border-radius:8px; color:var(--danger);">🗑️</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div id="pagination" class="pagination"></div>
    </div>
</div>

<script>
    // --- ระบบ Auto-fill ทะเบียนพ่วง ---
    const platePairs = <?php echo json_encode($plate_pairs); ?>;
    const tractorInput = document.getElementById('tractor_plate');
    const trailerInput = document.getElementById('trailer_plate');

    if (tractorInput && trailerInput) {
        ['input', 'change'].forEach(evt => {
            tractorInput.addEventListener(evt, function() {
                const val = this.value.toUpperCase().trim();
                if (platePairs[val]) {
                    trailerInput.value = platePairs[val];
                    // เพิ่ม effect สีเขียวอ่อนเพื่อให้รู้ว่าระบบเติมให้
                    trailerInput.style.backgroundColor = '#ecfdf5';
                    setTimeout(() => { trailerInput.style.backgroundColor = ''; }, 1000);
                }
            });
        });
    }

    // --- ส่วนเดิม ---
    let currentPage = 1;
    const recordsPerPage = 10;

    function doSearch() {
        const q = document.getElementById("tableSearch").value.toUpperCase();
        const rows = document.querySelectorAll(".q-row");
        rows.forEach(r => {
            const text = r.innerText.toUpperCase();
            r.classList.toggle("hidden-search", !text.includes(q));
        });
        currentPage = 1;
        updateTable();
    }

    function updateTable() {
        const rows = Array.from(document.querySelectorAll(".q-row:not(.hidden-search)"));
        const totalPages = Math.ceil(rows.length / recordsPerPage);
        document.querySelectorAll(".q-row").forEach(r => { r.style.display = "none"; r.style.opacity = "0"; });
        const start = (currentPage - 1) * recordsPerPage;
        const end = start + recordsPerPage;
        rows.slice(start, end).forEach((r, index) => {
            r.style.display = "";
            setTimeout(() => { r.style.opacity = "1"; }, index * 50);
        });
        renderPagination(totalPages);
    }

    function renderPagination(totalPages) {
        const container = document.getElementById("pagination");
        container.innerHTML = "";
        if (totalPages <= 1) return;
        const makeBtn = (p, label, active = false) => {
            const btn = document.createElement("a");
            btn.innerText = label;
            btn.className = `p-item ${active ? 'active' : ''}`;
            if(label !== '...') {
                btn.onclick = (e) => { e.preventDefault(); currentPage = p; updateTable(); window.scrollTo({top: 500, behavior: 'smooth'}); };
            } else { btn.classList.add('dots'); }
            container.appendChild(btn);
        };
        if(currentPage > 1) makeBtn(currentPage - 1, "‹");
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                makeBtn(i, i, i === currentPage);
            } else if (i === currentPage - 2 || i === currentPage + 2) {
                makeBtn(i, "...");
            }
        }
        if(currentPage < totalPages) makeBtn(currentPage + 1, "›");
    }

    function showToast(msg, type) {
        if(!msg) return;
        const wrap = document.getElementById("toast-wrap");
        const t = document.createElement("div");
        t.className = `toast`;
        t.style.background = type === 'success' ? '#10b981' : (type === 'danger' ? '#ef4444' : '#f59e0b');
        t.innerText = msg;
        wrap.appendChild(t);
        setTimeout(() => { 
            t.style.opacity = "0"; 
            t.style.transform = "translateY(20px)";
            setTimeout(() => t.remove(), 400);
        }, 3000);
    }

    window.onload = () => {
        updateTable();
        const p = new URLSearchParams(window.location.search);
        if(p.get('msg')) showToast(p.get('msg'), p.get('type'));
    };
</script>
<?php include 'footer.php'; ?>
</body>
</html>