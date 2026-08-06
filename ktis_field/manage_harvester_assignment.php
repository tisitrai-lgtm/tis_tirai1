<?php
require_once 'config.php';
session_start();

// ดึงรายชื่อผู้ดูแล
// ดึงรายชื่อผู้ดูแล (จากตาราง employee เดิมของคุณ)
$stmt = $conn->query("SELECT ID, emp_name FROM employee WHERE is_harvester_manager = 1 ORDER BY emp_name ASC");
$managers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงรถตัดทั้งหมดจากตารางของคุณ
$harvesters = $conn->query("SELECT harvester_id, harvester_number FROM harvesters ORDER BY harvester_id ASC")->fetchAll(PDO::FETCH_ASSOC);?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการผู้ดูแลรถตัด - TIS SMART FIELD</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun'; display: flex; padding: 20px; gap: 20px; }
        .manager-list { width: 300px; border-right: 1px solid #ccc; }
        .manager-item { padding: 10px; cursor: pointer; border-bottom: 1px solid #eee; }
        .manager-item:hover { background: #f0f0f0; }
        .harvester-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; padding: 20px; }
        .chip { padding: 10px; border: 1px solid #3b82f6; border-radius: 20px; text-align: center; cursor: pointer; }
        .chip.active { background: #3b82f6; color: white; }
    </style>
</head>
<body>

<div class="manager-list">
    <h3>รายชื่อผู้ดูแล</h3>
    <?php foreach($managers as $m): ?>
        <div class="manager-item" onclick="loadAssignment(<?php echo $m['ID']; ?>)">
            <?php echo $m['emp_name']; ?>
        </div>
    <?php endforeach; ?>
</div>

<div class="harvester-grid" id="harvester-area">
    <h3>กรุณาเลือกชื่อผู้ดูแลทางซ้าย</h3>
</div>

<script>
let currentManagerId = null;

function loadAssignment(managerId) {
    currentManagerId = managerId;
    fetch(`get_assignment.php?manager_id=${managerId}`)
        .then(res => res.json())
        .then(data => {
            let html = '<h3>เลือกรถตัด:</h3>';
            // ในส่วน fetch ภายในฟังก์ชัน loadAssignment
            data.all.forEach(h => {
            // เปลี่ยน h.id เป็น h.harvester_id และ h.harvester_no เป็น h.harvester_number
             const active = data.assigned.includes(h.harvester_id) ? 'active' : '';
             html += `<div class="chip ${active}" onclick="toggleAssign(${h.harvester_id}, this)">${h.harvester_number}</div>`;
            });
            document.getElementById('harvester-area').innerHTML = html;
        });
}

function toggleAssign(hId, el) {
    fetch('save_assignment.php', {
        method: 'POST',
        body: new URLSearchParams(`manager_id=${currentManagerId}&harvester_id=${hId}`)
    }).then(() => el.classList.toggle('active'));
}
</script>
</body>
</html>