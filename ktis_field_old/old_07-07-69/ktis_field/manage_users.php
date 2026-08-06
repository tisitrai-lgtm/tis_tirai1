<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['emp_level']) || $_SESSION['emp_level'] != 'a') {
    die("คุณไม่มีสิทธิ์เข้าถึงหน้านี้ กรุณากลับหน้าหลัก");
}

$stmt = $conn->query("SELECT * FROM employee ORDER BY emp_unit ASC, emp_id ASC");
$employees = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <link rel="icon" type="image/jpeg" href="icon/iconweb.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้ - TIS SMART FIELD</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Sarabun', sans-serif; background-color: #f8fafc; margin: 0; }

        /* ======= WRAPPER ======= */
        .content-wrapper { flex: 1 0 auto; }
        .page-container {
            max-width: 1100px;
            margin: 24px auto;
            padding: 0 16px 60px;
        }

        /* ======= PAGE HEADER ======= */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }
        .page-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.15rem;
            font-weight: 700;
            color: #1e293b;
            border-left: 4px solid #e11d48;
            padding-left: 12px;
        }
        .btn-add {
            background-color: #10b981;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
            white-space: nowrap;
        }
        .btn-add:hover { background-color: #059669; }

        /* ======= SEARCH BAR ======= */
        .search-bar {
            background: white;
            border-radius: 10px;
            border: 0.5px solid #e2e8f0;
            padding: 12px 16px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .search-bar i { color: #94a3b8; font-size: 1rem; }
        .search-bar input {
            border: none;
            outline: none;
            font-family: 'Sarabun', sans-serif;
            font-size: 0.95rem;
            flex: 1;
            background: transparent;
            color: #1e293b;
        }
        .search-bar input::placeholder { color: #cbd5e1; }

        /* ======= STAT CHIPS ======= */
        .stat-row {
            display: flex;
            gap: 10px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }
        .stat-chip {
            background: white;
            border: 0.5px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #475569;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .stat-chip span { font-size: 1rem; font-weight: 700; }
        .chip-total span { color: #1e293b; }
        .chip-admin span { color: #e11d48; }
        .chip-user span  { color: #10b981; }

        /* ======= TABLE (desktop) ======= */
        .table-wrap {
            background: white;
            border-radius: 12px;
            border: 0.5px solid #e2e8f0;
            overflow: hidden;
        }
        table { width: 100%; border-collapse: collapse; }
        thead th {
            background: #1e293b;
            color: white;
            padding: 13px 16px;
            text-align: left;
            font-size: 0.85rem;
            font-weight: 700;
            white-space: nowrap;
        }
        tbody td {
            padding: 13px 16px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
            color: #334155;
            vertical-align: middle;
        }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #f8fafc; }

        .emp-id-cell { font-weight: 700; color: #1e293b; font-family: monospace; font-size: 0.95rem; }
        .badge-unit {
            background: #f1f5f9;
            color: #475569;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
            display: inline-block;
        }
        .badge-admin {
            background: #fee2e2; color: #e11d48;
            padding: 3px 10px; border-radius: 20px;
            font-size: 0.78rem; font-weight: 700;
            display: inline-flex; align-items: center; gap: 4px;
        }
        .badge-user {
            background: #d1fae5; color: #059669;
            padding: 3px 10px; border-radius: 20px;
            font-size: 0.78rem; font-weight: 700;
            display: inline-flex; align-items: center; gap: 4px;
        }
        .action-edit {
            color: #3b82f6; text-decoration: none;
            font-weight: 600; font-size: 0.85rem;
            display: inline-flex; align-items: center; gap: 4px;
            padding: 5px 10px; border-radius: 6px; border: 1px solid #bfdbfe;
            background: #eff6ff; transition: background 0.15s;
        }
        .action-edit:hover { background: #dbeafe; }
        .action-del {
            color: #e11d48; text-decoration: none;
            font-weight: 600; font-size: 0.85rem;
            display: inline-flex; align-items: center; gap: 4px;
            padding: 5px 10px; border-radius: 6px; border: 1px solid #fecaca;
            background: #fff1f2; transition: background 0.15s;
        }
        .action-del:hover { background: #ffe4e6; }
        .action-view {
            color: #8b5cf6; text-decoration: none;
            font-weight: 600; font-size: 0.85rem;
            display: inline-flex; align-items: center; gap: 4px;
            padding: 5px 10px; border-radius: 6px; border: 1px solid #ddd6fe;
            background: #f5f3ff; transition: background 0.15s;
        }
        .action-view:hover { background: #ede9fe; }

        /* ======= MOBILE CARDS ======= */
        .mobile-cards { display: none; }
        .emp-card {
            background: white;
            border-radius: 12px;
            border: 0.5px solid #e2e8f0;
            padding: 14px 16px;
            margin-bottom: 10px;
        }
        .emp-card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 10px;
            gap: 8px;
        }
        .emp-card-avatar {
            width: 42px; height: 42px;
            background: #1e293b;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 700; font-size: 0.85rem;
            flex-shrink: 0;
        }
        .emp-card-avatar.is-admin { background: #e11d48; }
        .emp-card-info { flex: 1; }
        .emp-card-name { font-weight: 700; color: #1e293b; font-size: 0.95rem; margin-bottom: 2px; }
        .emp-card-id { font-size: 0.78rem; color: #94a3b8; font-family: monospace; }
        .emp-card-meta {
            display: flex; gap: 8px; flex-wrap: wrap;
            margin-bottom: 12px;
        }
        .emp-card-actions {
            display: flex; gap: 8px;
            border-top: 1px solid #f1f5f9;
            padding-top: 10px;
        }
        .emp-card-actions a {
            flex: 1; text-align: center;
            padding: 8px 0; border-radius: 7px;
            font-weight: 700; font-size: 0.85rem;
            text-decoration: none;
            display: flex; align-items: center; justify-content: center; gap: 5px;
        }

        /* ======= EMPTY STATE ======= */
        .empty-state {
            text-align: center; padding: 48px 20px;
            color: #94a3b8;
        }
        .empty-state i { font-size: 2.5rem; display: block; margin-bottom: 10px; }

        /* ======= RESPONSIVE ======= */
        @media (max-width: 700px) {
            .table-wrap { display: none; }
            .mobile-cards { display: block; }
            .page-title { font-size: 1rem; }
            .btn-add { padding: 9px 14px; font-size: 0.85rem; }
        }
    </style>
    <link rel="stylesheet" href="global_smoothness.css">
</head>
<body>
<div class="content-wrapper">

<?php include 'includes/nav_u_header.php'; ?>

<div class="page-container">

    <div class="page-header">
        <div class="page-title">
            <i class="fa-solid fa-users-gear" style="color:#e11d48;"></i>
            จัดการรายชื่อพนักงาน
        </div>
        <a href="add_user.php" class="btn-add">
            <i class="fa-solid fa-user-plus"></i> เพิ่มพนักงานใหม่
        </a>
    </div>

    <?php
    $total = count($employees);
    $admins = count(array_filter($employees, fn($e) => $e['emp_level'] == 'a'));
    $users  = $total - $admins;
    ?>
    <div class="stat-row">
        <div class="stat-chip chip-total"><i class="fa-solid fa-users" style="color:#1e293b;"></i> ทั้งหมด <span><?php echo $total; ?></span> คน</div>
        <div class="stat-chip chip-admin"><i class="fa-solid fa-shield-halved" style="color:#e11d48;"></i> แอดมิน <span><?php echo $admins; ?></span> คน</div>
        <div class="stat-chip chip-user"><i class="fa-solid fa-user" style="color:#10b981;"></i> พนักงาน <span><?php echo $users; ?></span> คน</div>
    </div>

    <div class="search-bar">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="search-input" placeholder="ค้นหารหัส ชื่อ หรือหน่วยงาน..." oninput="filterTable()">
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div style="background:#d1fae5; color:#065f46; padding:12px; border-radius:8px; margin-bottom:16px; border:1px solid #6ee7b7; display:flex; align-items:center; gap:10px;">
            <i class="fa-solid fa-circle-check"></i>
            <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div style="background:#fee2e2; color:#991b1b; padding:12px; border-radius:8px; margin-bottom:16px; border:1px solid #fca5a5; display:flex; align-items:center; gap:10px;">
            <i class="fa-solid fa-circle-xmark"></i>
            <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <!-- ===== Desktop Table ===== -->
    <div class="table-wrap" id="desktop-table">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>รหัสพนักงาน</th>
                    <th>ชื่อ-นามสกุล</th>
                    <th>หน่วยงาน</th>
                    <th>สิทธิ์</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody id="emp-tbody">
                <?php foreach($employees as $i => $emp): ?>
                <tr class="emp-row"
                    data-name="<?php echo htmlspecialchars(strtolower($emp['emp_name'])); ?>"
                    data-id="<?php echo htmlspecialchars(strtolower($emp['emp_id'])); ?>"
                    data-unit="<?php echo htmlspecialchars(strtolower($emp['emp_unit'])); ?>">
                    <td style="color:#94a3b8; font-size:0.82rem;"><?php echo $i+1; ?></td>
                    <td><span class="emp-id-cell"><?php echo htmlspecialchars($emp['emp_id']); ?></span></td>
                    <td><?php echo htmlspecialchars($emp['emp_name']); ?></td>
                    <td><span class="badge-unit"><?php echo htmlspecialchars($emp['emp_unit']); ?></span></td>
                    <td>
                        <?php if($emp['emp_level'] == 'a'): ?>
                            <span class="badge-admin"><i class="fa-solid fa-shield-halved"></i> แอดมิน</span>
                        <?php else: ?>
                            <span class="badge-user"><i class="fa-solid fa-user"></i> พนักงาน</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display:flex; gap:6px; flex-wrap:wrap;">
                            <a href="view_user.php?id=<?php echo $emp['ID']; ?>" class="action-view">
                                <i class="fa-solid fa-eye"></i> ดู
                            </a>
                            <a href="edit_user.php?id=<?php echo $emp['ID']; ?>" class="action-edit">
                                <i class="fa-solid fa-pen-to-square"></i> แก้ไข
                            </a>
                            <a href="delete_user.php?id=<?php echo $emp['ID']; ?>" class="action-del"
                               onclick="return confirm('ยืนยันการลบพนักงานคุณ <?php echo addslashes(htmlspecialchars($emp['emp_name'])); ?> หรือไม่?')">
                                <i class="fa-solid fa-trash"></i> ลบ
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div id="empty-table" class="empty-state" style="display:none;">
            <i class="fa-solid fa-user-slash"></i>
            ไม่พบพนักงานที่ค้นหา
        </div>
    </div>

    <!-- ===== Mobile Cards ===== -->
    <div class="mobile-cards" id="mobile-cards">
        <?php foreach($employees as $emp):
            $initials = mb_substr(trim($emp['emp_name']), 0, 2, 'UTF-8');
            $is_admin = ($emp['emp_level'] == 'a');
        ?>
        <div class="emp-card mobile-row"
             data-name="<?php echo htmlspecialchars(strtolower($emp['emp_name'])); ?>"
             data-id="<?php echo htmlspecialchars(strtolower($emp['emp_id'])); ?>"
             data-unit="<?php echo htmlspecialchars(strtolower($emp['emp_unit'])); ?>">
            <div class="emp-card-top">
                <div class="emp-card-avatar <?php echo $is_admin ? 'is-admin' : ''; ?>">
                    <?php echo htmlspecialchars($initials); ?>
                </div>
                <div class="emp-card-info">
                    <div class="emp-card-name"><?php echo htmlspecialchars($emp['emp_name']); ?></div>
                    <div class="emp-card-id"><?php echo htmlspecialchars($emp['emp_id']); ?></div>
                </div>
                <?php if($is_admin): ?>
                    <span class="badge-admin"><i class="fa-solid fa-shield-halved"></i> แอดมิน</span>
                <?php else: ?>
                    <span class="badge-user"><i class="fa-solid fa-user"></i> พนักงาน</span>
                <?php endif; ?>
            </div>
            <div class="emp-card-meta">
                <span class="badge-unit"><i class="fa-solid fa-location-dot" style="color:#e11d48; margin-right:3px;"></i><?php echo htmlspecialchars($emp['emp_unit']); ?></span>
            </div>
            <div class="emp-card-actions">
                <a href="view_user.php?id=<?php echo $emp['ID']; ?>" class="action-view">
                    <i class="fa-solid fa-eye"></i> ดู
                </a>
                <a href="edit_user.php?id=<?php echo $emp['ID']; ?>" class="action-edit">
                    <i class="fa-solid fa-pen-to-square"></i> แก้ไข
                </a>
                <a href="delete_user.php?id=<?php echo $emp['ID']; ?>" class="action-del"
                   onclick="return confirm('ยืนยันการลบพนักงานคุณ <?php echo addslashes(htmlspecialchars($emp['emp_name'])); ?> หรือไม่?')">
                    <i class="fa-solid fa-trash"></i> ลบ
                </a>
            </div>
        </div>
        <?php endforeach; ?>
        <div id="empty-cards" class="empty-state" style="display:none;">
            <i class="fa-solid fa-user-slash"></i>
            ไม่พบพนักงานที่ค้นหา
        </div>
    </div>

</div><!-- /page-container -->
</div><!-- /content-wrapper -->

<?php include 'includes/nav_u_footer.php'; ?>

<script>
function filterTable() {
    const q = document.getElementById('search-input').value.toLowerCase().trim();

    // Desktop rows
    const rows = document.querySelectorAll('.emp-row');
    let visibleDesktop = 0;
    rows.forEach(r => {
        const match = r.dataset.name.includes(q) || r.dataset.id.includes(q) || r.dataset.unit.includes(q);
        r.style.display = match ? '' : 'none';
        if (match) visibleDesktop++;
    });
    document.getElementById('empty-table').style.display = visibleDesktop === 0 ? 'block' : 'none';

    // Mobile cards
    const cards = document.querySelectorAll('.mobile-row');
    let visibleMobile = 0;
    cards.forEach(c => {
        const match = c.dataset.name.includes(q) || c.dataset.id.includes(q) || c.dataset.unit.includes(q);
        c.style.display = match ? '' : 'none';
        if (match) visibleMobile++;
    });
    document.getElementById('empty-cards').style.display = visibleMobile === 0 ? 'block' : 'none';
}
</script>

</body>
</html>