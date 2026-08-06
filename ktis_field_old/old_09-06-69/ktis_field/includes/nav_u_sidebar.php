<?php
/**
 * includes/nav_u_sidebar.php
 * Sidebar สำหรับ Admin — sticky ด้านซ้าย
 * ใช้งาน: วางไว้ใน includes/ แล้วเรียก <?php include 'includes/nav_u_sidebar.php'; ?>
 * ต้องห่อเนื้อหาหลักด้วย <div class="main-content">...</div>
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$current_page = basename($_SERVER['PHP_SELF']);
?>
<?php if(isset($_SESSION['emp_level']) && $_SESSION['emp_level'] == 'a'): ?>
<style>
/* ══════════════════════════════════════
   KTIS SMART FIELD — Admin Sidebar
   ══════════════════════════════════════ */
.page-wrapper {
    display: flex;
    width: 100%;
    min-height: 100vh;
    align-items: flex-start;
}

/* ── Sidebar ── */
.admin-sidebar {
    position: sticky;
    top: 56px;
    width: 240px;
    height: calc(100vh - 56px);
    background: #1e293b;
    border-right: 1px solid rgba(255,255,255,.07);
    padding: 16px 12px 24px;
    flex-shrink: 0;
    overflow-y: auto;
    z-index: 10;
    display: flex;
    flex-direction: column;
    gap: 6px;
    scrollbar-width: thin;
    scrollbar-color: #334155 transparent;
}
.admin-sidebar::-webkit-scrollbar { width: 4px; }
.admin-sidebar::-webkit-scrollbar-thumb { background: #334155; border-radius: 4px; }

/* ── Main content ── */
.main-content {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    margin-left: 240px; /* เท่ากับความกว้าง sidebar */
}

/* ── Profile box ── */
.sb-profile {
    background: linear-gradient(135deg, #334155, #1e293b);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 10px;
    padding: 14px;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.sb-avatar {
    width: 38px; height: 38px;
    background: #e11d48;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .85rem; font-weight: 700; color: #fff;
    flex-shrink: 0;
}
.sb-name  { font-weight: 700; font-size: .85rem; color: #f8fafc; line-height: 1.3; }
.sb-unit  { font-size: .72rem; color: #64748b; margin-top: 1px; }
.sb-badge {
    display: inline-flex; align-items: center; gap: 3px;
    background: rgba(225,29,72,.15); color: #f43f5e;
    font-size: .68rem; font-weight: 700;
    padding: 1px 7px; border-radius: 10px; margin-top: 3px;
    border: 1px solid rgba(225,29,72,.2);
}

/* ── Crop year chip ── */
.sb-crop {
    display: flex; align-items: center; justify-content: space-between;
    background: rgba(16,185,129,.1); border: 1px solid rgba(16,185,129,.2);
    border-radius: 8px; padding: 8px 12px; margin-bottom: 4px;
}
.sb-crop-label { font-size: .75rem; color: #64748b; }
.sb-crop-val   { font-size: .82rem; font-weight: 700; color: #10b981; }

/* ── Section label ── */
.sb-section {
    font-size: .68rem; font-weight: 700;
    color: #475569; letter-spacing: .8px;
    text-transform: uppercase; padding: 10px 8px 4px;
}

/* ── Menu items ── */
.sb-link {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 12px; border-radius: 8px;
    color: #94a3b8; text-decoration: none;
    font-size: .88rem; font-weight: 600;
    transition: background .13s, color .13s;
    white-space: nowrap;
}
.sb-link i { width: 18px; text-align: center; font-size: .9rem; flex-shrink: 0; }
.sb-link:hover  { background: rgba(255,255,255,.07); color: #f1f5f9; }
.sb-link.active { background: rgba(225,29,72,.12); color: #f43f5e; border-left: 3px solid #e11d48; padding-left: 9px; }
.sb-link.active i { color: #e11d48; }

/* ── Divider ── */
.sb-divider { height: 1px; background: rgba(255,255,255,.07); margin: 6px 0; }

/* ── Logout ── */
.sb-logout {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 12px; border-radius: 8px;
    color: #f43f5e; text-decoration: none;
    font-size: .88rem; font-weight: 700;
    transition: background .13s;
    margin-top: auto;
}
.sb-logout:hover { background: rgba(244,63,94,.1); }

/* ── Responsive: ซ่อน sidebar บนมือถือ ── */
@media (max-width: 900px) {
    .admin-sidebar   { display: none; }
    .page-wrapper    { display: block; }
}
</style>

<?php
$initials = mb_substr(trim($_SESSION['emp_name'] ?? 'A'), 0, 2, 'UTF-8');

// เมนูรายการ [href, icon, label]
$menu_main = [
    ['index.php',        'fa-house',         'หน้าแรกฟีด'],
    ['harvester_admin.php','fa-tractor',      'ตรวจเช็กรถตัด'],
];
$menu_admin = [
    ['manage_users.php', 'fa-users-gear',    'จัดการพนักงาน'],
    ['report_all.php',   'fa-chart-line',    'รายงานภาพรวม'],
    ['setting_system.php','fa-sliders',      'ตั้งค่าระบบ'],
];
?>

<div class="admin-sidebar">

    <!-- Profile -->
    <div class="sb-profile">
        <div class="sb-avatar"><?php echo htmlspecialchars($initials); ?></div>
        <div>
            <div class="sb-name"><?php echo htmlspecialchars($_SESSION['emp_name'] ?? ''); ?></div>
            <div class="sb-unit"><?php echo htmlspecialchars($_SESSION['emp_unit'] ?? ''); ?></div>
            <div class="sb-badge"><i class="fa-solid fa-shield-halved" style="font-size:.62rem;"></i> Admin</div>
        </div>
    </div>

    <!-- Crop year -->
    <div class="sb-crop">
        <span class="sb-crop-label"><i class="fa-solid fa-calendar"></i> ปีการผลิต</span>
        <span class="sb-crop-val"><?php echo htmlspecialchars($_SESSION['crop_year'] ?? '-'); ?></span>
    </div>

    <!-- Main menu -->
    <div class="sb-section">เมนูหลัก</div>
    <?php foreach($menu_main as [$href, $icon, $label]):
        $active = ($current_page === $href) ? 'active' : '';
    ?>
    <a href="<?php echo $href; ?>" class="sb-link <?php echo $active; ?>">
        <i class="fa-solid <?php echo $icon; ?>"></i> <?php echo $label; ?>
    </a>
    <?php endforeach; ?>

    <div class="sb-divider"></div>

    <!-- Admin menu -->
    <div class="sb-section">ผู้ดูแลระบบ</div>
    <?php foreach($menu_admin as [$href, $icon, $label]):
        $active = ($current_page === $href) ? 'active' : '';
    ?>
    <a href="<?php echo $href; ?>" class="sb-link <?php echo $active; ?>">
        <i class="fa-solid <?php echo $icon; ?>"></i> <?php echo $label; ?>
    </a>
    <?php endforeach; ?>

    <div class="sb-divider"></div>

    <!-- Logout -->
    <a href="logout.php" class="sb-logout">
        <i class="fa-solid fa-right-from-bracket"></i> ออกจากระบบ
    </a>

</div>
<?php endif; ?>