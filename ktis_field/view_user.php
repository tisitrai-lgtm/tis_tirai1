<?php
/**
 * view_user.php — ข้อมูลและโปรไฟล์พนักงาน (Admin)
 * TIS SMART FIELD - ฝ่ายไร่
 */
require_once 'config.php';
session_start();

if (!isset($_SESSION['emp_level']) || $_SESSION['emp_level'] !== 'a') {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_error'] = "ไม่พบรหัสพนักงาน";
    header("Location: manage_users.php");
    exit;
}

$emp_id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM employee WHERE ID = ?");
$stmt->execute([$emp_id]);
$employee = $stmt->fetch();

if (!$employee) {
    $_SESSION['flash_error'] = "ไม่พบพนักงานที่ต้องการดู";
    header("Location: manage_users.php");
    exit;
}

// ข้อมูลเพิ่มเติม
$created_date = !empty($employee['created_at']) ? date('d/m/Y H:i', strtotime($employee['created_at'])) : '-';
$updated_date = !empty($employee['updated_at']) ? date('d/m/Y H:i', strtotime($employee['updated_at'])) : '-';

// ดึงรถตัดที่ดูแล
$assigned_harvesters = [];
try {
    $stmt_h = $conn->prepare("
        SELECT h.harvester_id, h.harvester_number, h.harvester_name, eh.assigned_at
        FROM employee_harvester eh
        JOIN harvesters h ON eh.harvester_id = h.harvester_id
        WHERE (eh.emp_id = ? OR eh.emp_id = ?) AND h.is_active = 1
        ORDER BY h.harvester_id ASC
    ");
    $stmt_h->execute([$emp_id, $employee['emp_id']]);
    $assigned_harvesters = $stmt_h->fetchAll();
} catch(Exception $e) {}

// สถิติการปฏิบัติงานของพนักงานคนนี้
$stat_posts_cnt = 0;
$stat_checks_cnt = 0;
$recent_posts = [];

try {
    $stmt_p = $conn->prepare("SELECT COUNT(*) FROM posts WHERE emp_id = ?");
    $stmt_p->execute([$employee['emp_id']]);
    $stat_posts_cnt = (int)$stmt_p->fetchColumn();

    $stmt_c = $conn->prepare("SELECT COUNT(*) FROM check_sessions WHERE emp_id = ?");
    $stmt_c->execute([$employee['emp_id']]);
    $stat_checks_cnt = (int)$stmt_c->fetchColumn();

    $stmt_rp = $conn->prepare("SELECT * FROM posts WHERE emp_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt_rp->execute([$employee['emp_id']]);
    $recent_posts = $stmt_rp->fetchAll();
} catch(Exception $e) {}

include 'includes/nav_u_header.php';
?>

<style>
* { box-sizing: border-box; }
body { font-family: 'Sarabun', sans-serif; background: #f8fafc; margin: 0; color: #1e293b; }

.page-wrapper { display: flex; min-height: 100vh; width: 100%; align-items: flex-start; }
.dash-wrap { flex: 1; padding: 24px 28px 60px; min-width: 0; overflow-x: hidden; }
.content-wrapper { max-width: 780px; margin: 0 auto; }

/* ── Breadcrumb ── */
.breadcrumb {
    display: flex; align-items: center; gap: 8px;
    font-size: 0.84rem; color: #64748b; margin-bottom: 20px;
}
.breadcrumb a { color: #3b82f6; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
.breadcrumb a:hover { text-decoration: underline; }
.breadcrumb i { font-size: 10px; color: #94a3b8; }

/* ── Profile Header Card ── */
.profile-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 20px rgba(0,0,0,0.04);
    overflow: hidden;
    margin-bottom: 20px;
}
.profile-top-banner {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    padding: 24px;
    color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    border-bottom: 3px solid <?php echo $employee['emp_level'] === 'a' ? '#e11d48' : ($employee['emp_level'] === 'm' ? '#f59e0b' : '#3b82f6'); ?>;
}
.profile-user-info { display: flex; align-items: center; gap: 18px; }
.profile-avatar {
    width: 64px; height: 64px;
    background: linear-gradient(135deg, <?php echo $employee['emp_level'] === 'a' ? '#e11d48, #be123c' : ($employee['emp_level'] === 'm' ? '#f59e0b, #d97706' : '#3b82f6, #1d4ed8'); ?>);
    border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.8rem; color: white; font-weight: 800;
    box-shadow: 0 6px 16px rgba(0,0,0,0.2);
    flex-shrink: 0;
}
.profile-name { font-size: 1.3rem; font-weight: 800; margin: 0 0 4px 0; }
.profile-code { font-size: 0.85rem; color: #94a3b8; font-family: monospace; }

.profile-actions { display: flex; gap: 8px; }
.btn-edit-prof {
    padding: 8px 16px; background: white; color: #0f172a;
    border-radius: 10px; font-weight: 700; font-size: 0.85rem;
    text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
    transition: all 0.15s;
}
.btn-edit-prof:hover { background: #f1f5f9; transform: translateY(-1px); }

/* ── Badges ── */
.badge-chip {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 20px; font-size: 0.76rem; font-weight: 700;
}
.badge-admin { background: rgba(225,29,72,0.2); color: #fca5a5; border: 1px solid rgba(225,29,72,0.3); }
.badge-mech  { background: rgba(245,158,11,0.2); color: #fcd34d; border: 1px solid rgba(245,158,11,0.3); }
.badge-user  { background: rgba(16,185,129,0.2); color: #6ee7b7; border: 1px solid rgba(16,185,129,0.3); }

/* ── Quick Stats Grid ── */
.stats-grid-3 {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;
    padding: 18px 24px; background: #f8fafc; border-bottom: 1px solid #f1f5f9;
}
.pstat-item {
    background: white; border: 1px solid #e2e8f0; border-radius: 12px;
    padding: 14px; text-align: center;
}
.pstat-val { font-size: 1.4rem; font-weight: 800; color: #1e293b; line-height: 1; }
.pstat-lbl { font-size: 0.74rem; font-weight: 700; color: #64748b; margin-top: 4px; }

/* ── Details Section ── */
.info-section { padding: 24px; }
.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
.info-box {
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;
    padding: 14px 16px;
}
.info-lbl { font-size: 0.75rem; font-weight: 700; color: #64748b; margin-bottom: 4px; display: flex; align-items: center; gap: 6px; }
.info-val { font-size: 0.95rem; font-weight: 700; color: #1e293b; }

/* ── Harvesters Box ── */
.hv-list-box {
    background: #f0fdf4; border: 1.5px solid #bbf7d0; border-radius: 12px;
    padding: 16px; margin-bottom: 20px;
}
.hv-list-title { font-size: 0.86rem; font-weight: 700; color: #166534; margin-bottom: 10px; display: flex; align-items: center; gap: 6px; }
.hv-chips-wrap { display: flex; flex-wrap: wrap; gap: 8px; }
.hv-chip {
    background: white; border: 1px solid #86efac; color: #15803d;
    padding: 6px 12px; border-radius: 8px; font-weight: 700; font-size: 0.82rem;
    display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);
}

/* ── Recent Activity ── */
.recent-box { border-top: 1px solid #f1f5f9; padding-top: 20px; }
.recent-title { font-size: 0.9rem; font-weight: 800; color: #1e293b; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }
.recent-item {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 12px; background: #f8fafc; border: 1px solid #f1f5f9;
    border-radius: 10px; margin-bottom: 8px; font-size: 0.84rem;
}

@media(max-width: 640px) {
    .dash-wrap { padding: 14px; }
    .stats-grid-3, .info-grid { grid-template-columns: 1fr; }
    .profile-top-banner { flex-direction: column; align-items: flex-start; }
}

/* ══════════════════════════════════════════
   DARK MODE OVERRIDES
   ══════════════════════════════════════════ */
.dark-mode body,
html.dark-mode body {
    background: #090d16 !important;
    color: #f1f5f9 !important;
}
.dark-mode .dash-wrap {
    background: #090d16 !important;
}
.dark-mode .profile-card {
    background: #131b2e !important;
    border-color: #1e293b !important;
}
.dark-mode .stat-card {
    background: #131b2e !important;
    border-color: #1e293b !important;
}
.dark-mode .stat-num {
    color: #f8fafc !important;
}
.dark-mode .stat-lbl {
    color: #94a3b8 !important;
}
.dark-mode .info-box {
    background: #0b1120 !important;
    border-color: #1e293b !important;
}
.dark-mode .info-val {
    color: #f8fafc !important;
}
.dark-mode .info-lbl {
    color: #94a3b8 !important;
}
.dark-mode .hv-list-box {
    background: rgba(16, 185, 129, 0.08) !important;
    border-color: rgba(16, 185, 129, 0.25) !important;
}
.dark-mode .hv-list-title {
    color: #34d399 !important;
}
.dark-mode .hv-chip {
    background: #131b2e !important;
    border-color: rgba(16, 185, 129, 0.4) !important;
    color: #34d399 !important;
}
.dark-mode .recent-title {
    color: #f8fafc !important;
}
.dark-mode .recent-item {
    background: #0b1120 !important;
    border-color: #1e293b !important;
    color: #cbd5e1 !important;
}
.dark-mode .breadcrumb {
    color: #64748b !important;
}
.dark-mode .breadcrumb a {
    color: #38bdf8 !important;
}
</style>

<div class="page-wrapper">
    <?php include 'includes/nav_u_sidebar.php'; ?>

    <div class="dash-wrap">
        <div class="content-wrapper">

            <!-- Breadcrumb -->
            <div class="breadcrumb">
                <a href="manage_users.php"><i class="fa-solid fa-users-gear"></i> จัดการพนักงาน</a>
                <i class="fa-solid fa-chevron-right"></i>
                <span><?php echo htmlspecialchars($employee['emp_name']); ?></span>
            </div>

            <!-- Main Profile Card -->
            <div class="profile-card">
                <!-- Top Banner -->
                <div class="profile-top-banner">
                    <div class="profile-user-info">
                        <div class="profile-avatar">
                            <?php echo mb_substr($employee['emp_name'], 0, 1, 'UTF-8'); ?>
                        </div>
                        <div>
                            <h1 class="profile-name"><?php echo htmlspecialchars($employee['emp_name']); ?></h1>
                            <div class="profile-code">
                                <span>รหัส: <?php echo htmlspecialchars($employee['emp_id']); ?></span>
                                <span style="margin: 0 6px;">•</span>
                                <?php if ($employee['emp_level'] === 'a'): ?>
                                <span class="badge-chip badge-admin"><i class="fa-solid fa-shield-halved"></i> ผู้ดูแลระบบ (Admin)</span>
                                <?php elseif ($employee['emp_level'] === 'm'): ?>
                                <span class="badge-chip badge-mech"><i class="fa-solid fa-wrench"></i> นายช่าง (Mechanic)</span>
                                <?php else: ?>
                                <span class="badge-chip badge-user"><i class="fa-solid fa-user"></i> พนักงานทั่วไป</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="profile-actions">
                        <a href="edit_user.php?id=<?php echo $employee['ID']; ?>" class="btn-edit-prof">
                            <i class="fa-solid fa-user-pen"></i> แก้ไขข้อมูล
                        </a>
                    </div>
                </div>

                <!-- Stats 3 Grid -->
                <div class="stats-grid-3">
                    <div class="pstat-item">
                        <div class="pstat-val" style="color:#e11d48;"><?php echo number_format($stat_posts_cnt); ?></div>
                        <div class="pstat-lbl">รายงานปัญหาที่สร้าง</div>
                    </div>
                    <div class="pstat-item">
                        <div class="pstat-val" style="color:#0284c7;"><?php echo number_format($stat_checks_cnt); ?></div>
                        <div class="pstat-lbl">การตรวจเช็กรถตัด</div>
                    </div>
                    <div class="pstat-item">
                        <div class="pstat-val" style="color:#059669;"><?php echo count($assigned_harvesters); ?></div>
                        <div class="pstat-lbl">รถตัดที่ดูแล (คัน)</div>
                    </div>
                </div>

                <!-- Info Details -->
                <div class="info-section">
                    <div class="info-grid">
                        <div class="info-box">
                            <div class="info-lbl"><i class="fa-solid fa-location-dot" style="color:#3b82f6;"></i> หน่วยส่งเสริม / สังกัด</div>
                            <div class="info-val"><?php echo htmlspecialchars($employee['emp_unit'] ?: '-'); ?></div>
                        </div>

                        <div class="info-box">
                            <div class="info-lbl"><i class="fa-solid fa-tractor" style="color:#0284c7;"></i> สถานะผู้ดูแลรถตัด</div>
                            <div class="info-val">
                                <?php if ($employee['is_harvester_manager'] == 1): ?>
                                <span style="color:#059669;"><i class="fa-solid fa-circle-check"></i> เป็นผู้ดูแลรถตัด</span>
                                <?php else: ?>
                                <span style="color:#94a3b8;"><i class="fa-solid fa-circle-xmark"></i> พนักงานทั่วไป</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="info-box">
                            <div class="info-lbl"><i class="fa-solid fa-calendar-plus" style="color:#64748b;"></i> วันที่ลงทะเบียน</div>
                            <div class="info-val"><?php echo $created_date; ?></div>
                        </div>

                        <div class="info-box">
                            <div class="info-lbl"><i class="fa-solid fa-clock-rotate-left" style="color:#64748b;"></i> ปรับปรุงล่าสุด</div>
                            <div class="info-val"><?php echo $updated_date; ?></div>
                        </div>
                    </div>

                    <!-- รถตัดที่ดูแล -->
                    <?php if ($employee['is_harvester_manager'] == 1): ?>
                    <div class="hv-list-box">
                        <div class="hv-list-title">
                            <i class="fa-solid fa-tractor"></i> รายการรถตัดที่อยู่ในความรับผิดชอบ (<?php echo count($assigned_harvesters); ?> คัน)
                        </div>
                        <?php if (empty($assigned_harvesters)): ?>
                        <div style="color:#64748b; font-size:0.82rem; font-style:italic;">ยังไม่ได้ผูกกับรถตัดคันใด (สามารถผูกได้ที่หน้าจัดการพนักงาน)</div>
                        <?php else: ?>
                        <div class="hv-chips-wrap">
                            <?php foreach ($assigned_harvesters as $ah): ?>
                            <div class="hv-chip">
                                <i class="fa-solid fa-tractor"></i>
                                <span><?php echo htmlspecialchars($ah['harvester_number']); ?></span>
                                <?php if(!empty($ah['harvester_name'])): ?>
                                <span style="font-size:0.72rem; color:#166534; font-weight:400;">(<?php echo htmlspecialchars($ah['harvester_name']); ?>)</span>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- รายการปัญหาที่รายงานล่าสุด -->
                    <?php if (!empty($recent_posts)): ?>
                    <div class="recent-box">
                        <div class="recent-title"><i class="fa-solid fa-list-check" style="color:#e11d48;"></i> รายงานปัญหาล่าสุดโดยพนักงานคนนี้</div>
                        <?php foreach ($recent_posts as $rp): ?>
                        <div class="recent-item">
                            <div>
                                <strong style="color:#1e293b;"><?php echo htmlspecialchars($rp['problem_detail'] ?: 'ปัญหาไร่'); ?></strong>
                                <div style="font-size:0.75rem; color:#64748b; margin-top:2px;">
                                    <span>ทะเบียน: <?php echo htmlspecialchars($rp['truck_number']); ?></span>
                                    <span> • โซน: <?php echo htmlspecialchars($rp['target_unit']); ?></span>
                                    <span> • <?php echo date('d/m/Y H:i', strtotime($rp['created_at'])); ?></span>
                                </div>
                            </div>
                            <span style="font-size:0.75rem; font-weight:700; padding:2px 8px; border-radius:10px; <?php echo $rp['job_status']==='success'?'background:#d1fae5;color:#065f46;':'background:#fff7ed;color:#c2410c;'; ?>">
                                <?php echo $rp['job_status'] === 'success' ? 'เสร็จสิ้น' : 'รอดำเนินการ'; ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>