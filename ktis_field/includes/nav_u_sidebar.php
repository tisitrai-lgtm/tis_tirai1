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
<?php if(isset($_SESSION['emp_level']) && in_array($_SESSION['emp_level'], ['a', 'm'])): ?>
<style>
/* ══════════════════════════════════════
   KTIS SMART FIELD — Admin/Mechanic Sidebar
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
    top: 0px;
    width: 208px;
    height: 100vh;
    background: #1e293b;
    border-right: 1px solid #e2e8f0;
    box-shadow: 2px 0 12px rgba(15,23,42,.05);
    padding: 16px 10px 20px;
    flex-shrink: 0;
    overflow-y: auto;
    z-index: 100;
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
    overflow-x: hidden;
}

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

/* ── Responsive: ซ่อน sidebar บนมือถือ ── */
@media (max-width: 900px) {
    .admin-sidebar   { display: none; }
    .page-wrapper    { display: block; }
}
</style>

<?php
$is_admin = ($_SESSION['emp_level'] ?? '') === 'a';
$is_mech  = ($_SESSION['emp_level'] ?? '') === 'm';

// เมนูหลักสำหรับ Admin และ นายช่าง
$menu_main = [
    ['index.php',                  'fa-house',            'หน้าแรกฟีด'],
    ['harvester_daily_dashboard.php','fa-gauge-high',      'การเช็ครถตัดประจำวัน'],
    ['harvester_map.php',          'fa-map-location-dot', 'แผนที่พิกัดรถตัด'],
    ['harvester_admin.php',        'fa-tractor',          'ผลตรวจเช็กรถตัด'],
];

if ($is_admin) {
    $menu_main[] = ['dashboard.php', 'fa-chart-area', 'สรุปปัญหาฝ่ายไร่'];
}

$menu_admin = [
    ['manage_users.php', 'fa-users-gear',    'จัดการพนักงาน'],
    ['report_all.php',   'fa-chart-line',    'รายงานภาพรวม'],
    ['setting_system.php','fa-sliders',      'ตั้งค่าระบบ'],
];
?>

<div class="admin-sidebar">

    <!-- Main menu -->
    <div class="sb-section"><?php echo $is_mech ? 'เมนูนายช่าง' : 'เมนูหลัก'; ?></div>
    <?php foreach($menu_main as [$href, $icon, $label]):
        $active = ($current_page === $href) ? 'active' : '';
    ?>
    <a href="<?php echo $href; ?>" class="sb-link <?php echo $active; ?>">
        <i class="fa-solid <?php echo $icon; ?>"></i> <?php echo $label; ?>
    </a>
    <?php endforeach; ?>

    <?php if($is_admin): ?>
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
    <?php endif; ?>

</div>
<?php endif; ?>