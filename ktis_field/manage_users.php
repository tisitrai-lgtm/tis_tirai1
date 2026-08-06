<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['emp_level']) || $_SESSION['emp_level'] != 'a') {
    die("คุณไม่มีสิทธิ์เข้าถึงหน้านี้ กรุณากลับหน้าหลัก");
}

// ดึงพนักงานพร้อมจำนวนรถที่ดูแล
$sql = "SELECT e.*,
        COUNT(eh.harvester_id) as harvester_count
        FROM employee e
        LEFT JOIN employee_harvester eh ON e.ID = eh.emp_id
        LEFT JOIN harvesters h ON eh.harvester_id = h.harvester_id AND h.is_active = 1
        GROUP BY e.ID
        ORDER BY e.emp_unit ASC, e.emp_id ASC";

$stmt = $conn->query($sql);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงรถตัดทั้งหมด (active)
$harvesters = $conn->query("SELECT harvester_id, harvester_number FROM harvesters WHERE is_active = 1 ORDER BY harvester_id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include 'includes/nav_u_header.php'; ?>
<style>
        * { box-sizing: border-box; }
        body { font-family: 'Sarabun', sans-serif; background-color: #f8fafc; margin: 0; }

        .content-wrapper { flex: 1 0 auto; }
        .page-container { max-width: 1150px; margin: 24px auto; padding: 0 16px 60px; }

        /* PAGE HEADER */
        .page-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; }
        .page-title { display: flex; align-items: center; gap: 10px; font-size: 1.15rem; font-weight: 700; color: #1e293b; border-left: 4px solid #e11d48; padding-left: 12px; }
        .btn-add { background-color: #10b981; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 700; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 8px; transition: background 0.2s; white-space: nowrap; }
        .btn-add:hover { background-color: #059669; }

        /* SEARCH BAR */
        .search-bar { background: white; border-radius: 10px; border: 0.5px solid #e2e8f0; padding: 12px 16px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; }
        .search-bar i { color: #94a3b8; font-size: 1rem; }
        .search-bar input { border: none; outline: none; font-family: 'Sarabun', sans-serif; font-size: 0.95rem; flex: 1; background: transparent; color: #1e293b; }
        .search-bar input::placeholder { color: #cbd5e1; }

        /* STAT CHIPS */
        .stat-row { display: flex; gap: 10px; margin-bottom: 18px; flex-wrap: wrap; }
        .stat-chip { background: white; border: 0.5px solid #e2e8f0; border-radius: 8px; padding: 8px 16px; font-size: 0.82rem; font-weight: 600; color: #475569; display: flex; align-items: center; gap: 6px; }
        .stat-chip span { font-size: 1rem; font-weight: 700; }
        .chip-total span { color: #1e293b; }
        .chip-admin span { color: #e11d48; }
        .chip-user span  { color: #10b981; }

        /* TABLE */
        .table-wrap { background: white; border-radius: 12px; border: 0.5px solid #e2e8f0; overflow: hidden; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 750px; }
        thead th { background: #1e293b; color: white; padding: 13px 16px; text-align: left; font-size: 0.85rem; font-weight: 700; white-space: nowrap; }
        tbody td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; color: #334155; vertical-align: middle; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #f8fafc; }

        .emp-id-cell { font-weight: 700; color: #1e293b; font-family: monospace; font-size: 0.95rem; }
        .badge-unit { background: #f1f5f9; color: #475569; padding: 3px 10px; border-radius: 20px; font-size: 0.78rem; font-weight: 600; display: inline-block; }

        /* STATUS */
        .badge-on  { color: #059669; font-weight: 700; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 4px; }
        .badge-off { color: #dc2626; font-weight: 700; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 4px; }

        /* ROLE */
        .badge-manager { background: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; white-space: nowrap; }
        .badge-normal   { color: #94a3b8; font-size: 0.8rem; }

        /* HARVESTER COUNT badge */
        .badge-cars { background: #e0f2fe; color: #0369a1; padding: 5px 10px; border-radius: 20px; font-size: 0.78rem; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; cursor: pointer; border: 1px solid #bae6fd; transition: background 0.15s; white-space: nowrap; }
        .badge-cars:hover { background: #bae6fd; }
        .badge-cars-empty { color: #cbd5e1; font-size: 0.8rem; }

        /* ACTION BUTTONS */
        .action-btn { font-weight: 600; font-size: 0.82rem; display: inline-flex; align-items: center; gap: 4px; padding: 5px 11px; border-radius: 7px; border: 1px solid; text-decoration: none; transition: background 0.15s; cursor: pointer; white-space: nowrap; font-family: 'Sarabun', sans-serif; background: transparent; }
        .btn-select-car { color: #92400e; border-color: #fcd34d; background: #fef3c7; }
        .btn-select-car:hover { background: #fde68a; }
        .btn-view  { color: #7c3aed; border-color: #ddd6fe; background: #f5f3ff; }
        .btn-view:hover { background: #ede9fe; }
        .btn-edit  { color: #1d4ed8; border-color: #bfdbfe; background: #eff6ff; }
        .btn-edit:hover { background: #dbeafe; }
        .btn-del   { color: #e11d48; border-color: #fecaca; background: #fff1f2; }
        .btn-del:hover { background: #ffe4e6; }

        .action-group { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }

        /* MOBILE CARDS */
        .mobile-cards { display: none; }
        .emp-card { background: white; border-radius: 12px; border: 0.5px solid #e2e8f0; padding: 14px 16px; margin-bottom: 10px; }
        .emp-card-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 10px; gap: 8px; }
        .emp-card-avatar { width: 42px; height: 42px; background: #1e293b; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 0.85rem; flex-shrink: 0; }
        .emp-card-avatar.is-admin { background: #e11d48; }
        .emp-card-info { flex: 1; }
        .emp-card-name { font-weight: 700; color: #1e293b; font-size: 0.95rem; margin-bottom: 2px; }
        .emp-card-id   { font-size: 0.78rem; color: #94a3b8; font-family: monospace; }
        .emp-card-meta { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px; }
        .emp-card-actions { display: flex; gap: 6px; flex-wrap: wrap; border-top: 1px solid #f1f5f9; padding-top: 10px; }

        /* EMPTY STATE */
        .empty-state { text-align: center; padding: 48px 20px; color: #94a3b8; }
        .empty-state i { font-size: 2.5rem; display: block; margin-bottom: 10px; }

        /* RESPONSIVE */
        @media (max-width: 700px) {
            .table-wrap { display: none; }
            .mobile-cards { display: block; }
            .page-title { font-size: 1rem; }
            .btn-add { padding: 9px 14px; font-size: 0.85rem; }
        }

        /* ═══════════════════════════════════
           HARVESTER POPUP MODAL
        ═══════════════════════════════════ */
        .modal-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(15,23,42,0.6);
            z-index: 9000;
            align-items: center; justify-content: center;
            padding: 20px;
        }
        .modal-overlay.open { display: flex; animation: fadeIn 0.2s ease; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        .modal-box {
            background: white;
            border-radius: 16px;
            width: 100%; max-width: 600px;
            max-height: 85vh;
            display: flex; flex-direction: column;
            box-shadow: 0 20px 60px rgba(0,0,0,0.25);
            animation: slideUp 0.25s cubic-bezier(0.34,1.56,0.64,1);
        }
        @keyframes slideUp { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: translateY(0); } }

        .modal-header {
            background: #1e293b;
            border-radius: 16px 16px 0 0;
            padding: 16px 20px;
            display: flex; align-items: center; justify-content: space-between;
            border-bottom: 3px solid #f59e0b;
        }
        .modal-header-left { display: flex; flex-direction: column; gap: 2px; }
        .modal-title { color: #f8fafc; font-weight: 700; font-size: 1rem; display: flex; align-items: center; gap: 8px; }
        .modal-sub   { color: #94a3b8; font-size: 0.8rem; }
        .modal-close { background: none; border: none; color: #94a3b8; font-size: 1.4rem; cursor: pointer; padding: 4px; border-radius: 6px; transition: color 0.15s, background 0.15s; line-height: 1; }
        .modal-close:hover { color: #f8fafc; background: rgba(255,255,255,0.1); }

        .modal-body { padding: 20px; overflow-y: auto; flex: 1; }

        .modal-search { width: 100%; padding: 9px 14px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; font-family: 'Sarabun', sans-serif; outline: none; margin-bottom: 16px; }
        .modal-search:focus { border-color: #f59e0b; }

        .modal-count-bar { font-size: 0.82rem; color: #64748b; margin-bottom: 12px; }
        .modal-count-bar strong { color: #f59e0b; }

        .harvester-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(115px, 1fr));
            gap: 8px;
        }
        .hv-chip {
            padding: 9px 8px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            font-size: 0.78rem;
            font-weight: 600;
            color: #475569;
            background: #f8fafc;
            transition: all 0.15s;
            display: flex; align-items: center; justify-content: center; gap: 5px;
            user-select: none;
        }
        .hv-chip:hover { border-color: #f59e0b; background: #fffbeb; color: #92400e; }
        .hv-chip.assigned {
            background: #fef3c7; border-color: #f59e0b;
            color: #92400e; font-weight: 700;
        }
        .hv-chip.assigned::before { content: '✓ '; }
        .hv-chip.saving { opacity: 0.5; pointer-events: none; }
        .hv-chip.hidden { display: none; }

        .modal-footer {
            padding: 14px 20px;
            border-top: 1px solid #f1f5f9;
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 8px;
        }
        .modal-footer-info { font-size: 0.8rem; color: #64748b; }
        .btn-modal-close { background: #1e293b; color: white; border: none; padding: 9px 22px; border-radius: 8px; font-weight: 700; font-size: 0.9rem; cursor: pointer; font-family: 'Sarabun', sans-serif; transition: background 0.15s; }
        .btn-modal-close:hover { background: #0f172a; }
</style>
<div class="page-wrapper" style="display:flex;min-height:100vh;">
<?php include 'includes/nav_u_sidebar.php'; ?>
<div class="dash-wrap" style="flex:1;padding:24px 28px;min-width:0;overflow-x:hidden;">
<div class="content-wrapper">


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
    $total  = count($employees);
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
                    <th>สถานะ</th>
                    <th>รถตัด</th>
                    <th>รถที่ดูแล</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody id="emp-tbody">
            <?php foreach($employees as $i => $emp): ?>
            <tr class="emp-row"
                data-name="<?php echo htmlspecialchars(strtolower($emp['emp_name'])); ?>"
                data-id="<?php echo htmlspecialchars(strtolower($emp['emp_id'])); ?>"
                data-unit="<?php echo htmlspecialchars(strtolower($emp['emp_unit'])); ?>">
                <td><?php echo $i+1; ?></td>
                <td><span class="emp-id-cell"><?php echo htmlspecialchars($emp['emp_id']); ?></span></td>
                <td><?php echo htmlspecialchars($emp['emp_name']); ?></td>
                <td><span class="badge-unit"><?php echo htmlspecialchars($emp['emp_unit']); ?></span></td>

                <!-- สถานะ -->
                <td>
                    <?php if($emp['status'] == 1): ?>
                        <span class="badge-on"><i class="fa-solid fa-circle-check"></i> ใช้งาน</span>
                    <?php else: ?>
                        <span class="badge-off"><i class="fa-solid fa-circle-xmark"></i> ไม่ใช้งาน</span>
                    <?php endif; ?>
                </td>

                <!-- รถตัด (ผู้ดูแล?) -->
                <td>
                    <?php if($emp['is_harvester_manager'] == 1): ?>
                        <span class="badge-manager"><i class="fa-solid fa-truck-pickup"></i> ผู้ดูแล</span>
                    <?php else: ?>
                        <span class="badge-normal">-</span>
                    <?php endif; ?>
                </td>

                <!-- รถที่ดูแล -->
                <td>
                    <?php if($emp['is_harvester_manager'] == 1 && $emp['harvester_count'] > 0): ?>
                        <button class="badge-cars"
                                onclick="openHarvesterModal(<?php echo $emp['ID']; ?>, '<?php echo addslashes(htmlspecialchars($emp['emp_name'])); ?>')">
                            <i class="fa-solid fa-tractor"></i> ดูแล <?php echo $emp['harvester_count']; ?> คัน
                        </button>
                    <?php elseif($emp['is_harvester_manager'] == 1): ?>
                        <span class="badge-cars-empty" style="font-style:italic; font-size:0.75rem; color:#cbd5e1;">ยังไม่มีรถ</span>
                    <?php else: ?>
                        <span class="badge-cars-empty">-</span>
                    <?php endif; ?>
                </td>

                <!-- จัดการ -->
                <td>
                    <div class="action-group">
                        <?php if($emp['is_harvester_manager'] == 1): ?>
                        <button class="action-btn btn-select-car"
                                onclick="openHarvesterModal(<?php echo $emp['ID']; ?>, '<?php echo addslashes(htmlspecialchars($emp['emp_name'])); ?>')">
                            <i class="fa-solid fa-truck-pickup"></i> เลือกรถ
                        </button>
                        <?php endif; ?>
                        <a href="view_user.php?id=<?php echo $emp['ID']; ?>" class="action-btn btn-view">
                            <i class="fa-solid fa-eye"></i> ดู
                        </a>
                        <a href="edit_user.php?id=<?php echo $emp['ID']; ?>" class="action-btn btn-edit">
                            <i class="fa-solid fa-pen-to-square"></i> แก้ไข
                        </a>
                        <a href="delete_user.php?id=<?php echo $emp['ID']; ?>" class="action-btn btn-del"
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
                <?php if($emp['is_harvester_manager'] == 1): ?>
                    <span class="badge-manager"><i class="fa-solid fa-truck-pickup"></i> ผู้ดูแล</span>
                <?php endif; ?>
            </div>
            <div class="emp-card-meta">
                <span class="badge-unit"><?php echo htmlspecialchars($emp['emp_unit']); ?></span>
                <?php if($emp['status'] == 1): ?>
                    <span class="badge-on" style="font-size:0.75rem;"><i class="fa-solid fa-circle" style="font-size:0.4rem;"></i> ใช้งาน</span>
                <?php else: ?>
                    <span class="badge-off" style="font-size:0.75rem;"><i class="fa-solid fa-circle" style="font-size:0.4rem;"></i> ปิด</span>
                <?php endif; ?>
                <?php if($emp['is_harvester_manager'] == 1 && $emp['harvester_count'] > 0): ?>
                    <span style="background:#e0f2fe; color:#0369a1; padding:2px 8px; border-radius:12px; font-size:0.72rem; font-weight:700;">
                        <i class="fa-solid fa-tractor"></i> <?php echo $emp['harvester_count']; ?> คัน
                    </span>
                <?php endif; ?>
            </div>
            <div class="emp-card-actions">
                <?php if($emp['is_harvester_manager'] == 1): ?>
                <button class="action-btn btn-select-car" style="flex:1;"
                        onclick="openHarvesterModal(<?php echo $emp['ID']; ?>, '<?php echo addslashes(htmlspecialchars($emp['emp_name'])); ?>')">
                    <i class="fa-solid fa-truck-pickup"></i> เลือกรถ
                </button>
                <?php endif; ?>
                <a href="view_user.php?id=<?php echo $emp['ID']; ?>" class="action-btn btn-view" style="flex:1; justify-content:center;">
                    <i class="fa-solid fa-eye"></i> ดู
                </a>
                <a href="edit_user.php?id=<?php echo $emp['ID']; ?>" class="action-btn btn-edit" style="flex:1; justify-content:center;">
                    <i class="fa-solid fa-pen-to-square"></i> แก้ไข
                </a>
                <a href="delete_user.php?id=<?php echo $emp['ID']; ?>" class="action-btn btn-del" style="flex:1; justify-content:center;"
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

</div></div><!-- dash-wrap / page-wrapper -->

<!-- ════════════════════════════════════
     HARVESTER ASSIGNMENT MODAL
════════════════════════════════════ -->
<div id="harvesterModal" class="modal-overlay">
    <div class="modal-box">
        <!-- Header -->
        <div class="modal-header">
            <div class="modal-header-left">
                <div class="modal-title">
                    <i class="fa-solid fa-truck-pickup" style="color:#f59e0b;"></i>
                    เลือกรถตัดที่ดูแล
                </div>
                <div class="modal-sub" id="modalEmpName">—</div>
            </div>
            <button class="modal-close" onclick="closeModal()" title="ปิด">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="modal-body">
            <input type="text" class="modal-search" id="modalSearch" placeholder="🔍 ค้นหาเบอร์รถ..." oninput="filterChips()">

            <div class="modal-count-bar">
                กดที่รถเพื่อ <strong>เพิ่ม/ถอน</strong> · รถที่เลือกอยู่แสดงด้วย <strong>✓</strong> สีเหลือง
                <span id="selectedCountBadge" style="margin-left:6px; background:#fef3c7; color:#92400e; padding:2px 8px; border-radius:10px; font-size:0.78rem; font-weight:700;"></span>
            </div>

            <div class="harvester-grid" id="harvesterGrid">
                <!-- chips injected by JS -->
            </div>
        </div>

        <!-- Footer -->
        <div class="modal-footer">
            <div class="modal-footer-info" id="modalFooterInfo">
                <i class="fa-solid fa-circle-info" style="color:#94a3b8;"></i>
                การเปลี่ยนแปลงจะบันทึกทันทีที่กด
            </div>
            <button class="btn-modal-close" onclick="closeModal()">
                <i class="fa-solid fa-check"></i> เสร็จแล้ว
            </button>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════
     JAVASCRIPT
════════════════════════════════════ -->
<script>
// --- data from PHP ---
const allHarvesters = <?php echo json_encode($harvesters, JSON_UNESCAPED_UNICODE); ?>;

let currentManagerId   = null;
let currentManagerName = '';
let assignedIds        = new Set();

/* ── Open Modal ── */
function openHarvesterModal(empId, empName) {
    currentManagerId   = empId;
    currentManagerName = empName;
    document.getElementById('modalEmpName').textContent = 'พนักงาน: ' + empName;
    document.getElementById('modalSearch').value = '';
    document.getElementById('harvesterModal').classList.add('open');
    document.body.style.overflow = 'hidden';
    loadAssigned(empId);
}

/* ── Close Modal ── */
function closeModal() {
    document.getElementById('harvesterModal').classList.remove('open');
    document.body.style.overflow = '';
    // refresh count badge in table without reload
    updateRowBadge(currentManagerId, assignedIds.size);
}

/* ── Click overlay to close ── */
document.getElementById('harvesterModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

/* ── Escape key ── */
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
});

/* ── Load assigned harvesters from server ── */
function loadAssigned(empId) {
    document.getElementById('harvesterGrid').innerHTML = '<div style="text-align:center; padding:30px; color:#94a3b8;"><i class="fa-solid fa-spinner fa-spin" style="font-size:1.5rem;"></i></div>';

    fetch('api_get_assigned_harvesters.php?emp_id=' + empId)
        .then(r => r.json())
        .then(data => {
            assignedIds = new Set(data.assigned.map(Number));
            renderChips();
        })
        .catch(() => {
            document.getElementById('harvesterGrid').innerHTML = '<div style="color:#e11d48; padding:20px;">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
        });
}

/* ── Render chips ── */
function renderChips() {
    const grid = document.getElementById('harvesterGrid');
    grid.innerHTML = '';
    allHarvesters.forEach(h => {
        const div = document.createElement('div');
        div.className = 'hv-chip' + (assignedIds.has(h.harvester_id) ? ' assigned' : '');
        div.dataset.id   = h.harvester_id;
        div.dataset.name = h.harvester_number.toLowerCase();
        div.innerHTML    = `<i class="fa-solid fa-tractor" style="font-size:0.7rem;"></i> ${h.harvester_number}`;
        div.addEventListener('click', () => toggleAssign(h.harvester_id, div));
        grid.appendChild(div);
    });
    updateSelectedCount();
}

/* ── Toggle assign ── */
function toggleAssign(hId, el) {
    el.classList.add('saving');
    const action = assignedIds.has(hId) ? 'remove' : 'add';
    const fd = new FormData();
    fd.append('emp_id', currentManagerId);
    fd.append('harvester_id', hId);
    fd.append('action', action);

    fetch('api_save_harvester_assignment.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                if (action === 'add')    assignedIds.add(hId);
                else                     assignedIds.delete(hId);
                el.classList.toggle('assigned', action === 'add');
                updateSelectedCount();
            }
        })
        .catch(() => {})
        .finally(() => el.classList.remove('saving'));
}

/* ── Update selected count badge ── */
function updateSelectedCount() {
    const cnt = assignedIds.size;
    document.getElementById('selectedCountBadge').textContent = 'เลือกอยู่ ' + cnt + ' คัน';
    document.getElementById('modalFooterInfo').innerHTML =
        '<i class="fa-solid fa-tractor" style="color:#f59e0b;"></i> ' +
        currentManagerName + ' ดูแลรถ <strong style="color:#92400e;">' + cnt + '</strong> คัน';
}

/* ── Update row badge in table after close ── */
function updateRowBadge(empId, count) {
    // Find all buttons with matching onclick empId
    document.querySelectorAll('.emp-row, .mobile-row').forEach(row => {
        const btn = row.querySelector('[onclick*="openHarvesterModal(' + empId + ',"]');
        if (!btn) return;
        if (btn.classList.contains('badge-cars')) {
            btn.innerHTML = '<i class="fa-solid fa-tractor"></i> ดูแล ' + count + ' คัน';
        }
        // also update select-car button row badge
        const badgeBtn = row.querySelector('.badge-cars');
        if (badgeBtn) {
            badgeBtn.innerHTML = '<i class="fa-solid fa-tractor"></i> ดูแล ' + count + ' คัน';
        }
    });
}

/* ── Filter chips by search ── */
function filterChips() {
    const q = document.getElementById('modalSearch').value.toLowerCase();
    document.querySelectorAll('.hv-chip').forEach(chip => {
        chip.classList.toggle('hidden', !chip.dataset.name.includes(q));
    });
}

/* ── Table search ── */
function filterTable() {
    const q = document.getElementById('search-input').value.toLowerCase().trim();

    const rows = document.querySelectorAll('.emp-row');
    let visibleDesktop = 0;
    rows.forEach(r => {
        const match = r.dataset.name.includes(q) || r.dataset.id.includes(q) || r.dataset.unit.includes(q);
        r.style.display = match ? '' : 'none';
        if (match) visibleDesktop++;
    });
    document.getElementById('empty-table').style.display = visibleDesktop === 0 ? 'block' : 'none';

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