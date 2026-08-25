<?php
/**
 * includes/mobile_bottom_nav.php — Mobile Bottom Navigation Bar (Role-Based)
 * ออกแบบสำหรับโทรศัพท์มือถือและแท็บเล็ต แยกเมนูตาม 3 สิทธิ์ผู้ใช้งาน
 */
if (!isset($_SESSION['emp_id'])) {
    return; // ไม่แสดงหากยังไม่ได้เข้าสู่ระบบ
}

$cur_page = basename($_SERVER['PHP_SELF']);
$emp_lvl  = $_SESSION['emp_level'] ?? 'u';

// กำหนดเมนูตามแต่ละบทบาท (User / Mechanic / Admin)
$tabs = [];

if ($emp_lvl === 'a') {
    // 🛡️ Admin (5 เมนู)
    $tabs = [
        [
            'url'    => 'index.php',
            'title'  => 'หน้าแรก',
            'icon'   => 'fa-house',
            'active' => in_array($cur_page, ['index.php', 'post_detail.php', 'post_create.php']),
        ],
        [
            'url'    => 'harvester_map.php',
            'title'  => 'แผนที่',
            'icon'   => 'fa-map-location-dot',
            'active' => ($cur_page === 'harvester_map.php'),
        ],
        [
            'url'    => 'harvester_daily_dashboard.php',
            'title'  => 'ตรวจรถตัด',
            'icon'   => 'fa-tractor',
            'active' => in_array($cur_page, ['harvester_daily_dashboard.php', 'harvester_admin.php', 'harvester.php']),
            'is_center' => true,
        ],
        [
            'url'    => 'manage_users.php',
            'title'  => 'จัดการคน',
            'icon'   => 'fa-users-gear',
            'active' => in_array($cur_page, ['manage_users.php', 'add_user.php', 'edit_user.php', 'view_user.php']),
        ],
        [
            'url'    => 'profile.php',
            'title'  => 'โปรไฟล์',
            'icon'   => 'fa-user-circle',
            'active' => ($cur_page === 'profile.php'),
        ],
    ];
} elseif ($emp_lvl === 'm') {
    // 🔧 Mechanic (4 เมนู)
    $tabs = [
        [
            'url'    => 'index.php',
            'title'  => 'หน้าแรก',
            'icon'   => 'fa-house',
            'active' => in_array($cur_page, ['index.php', 'post_detail.php', 'post_create.php']),
        ],
        [
            'url'    => 'harvester_daily_dashboard.php',
            'title'  => 'ตรวจเช็กรถ',
            'icon'   => 'fa-tractor',
            'active' => in_array($cur_page, ['harvester_daily_dashboard.php', 'harvester_admin.php']),
            'is_center' => true,
        ],
        [
            'url'    => 'harvester_map.php',
            'title'  => 'แผนที่',
            'icon'   => 'fa-map-location-dot',
            'active' => ($cur_page === 'harvester_map.php'),
        ],
        [
            'url'    => 'profile.php',
            'title'  => 'โปรไฟล์',
            'icon'   => 'fa-user-circle',
            'active' => ($cur_page === 'profile.php'),
        ],
    ];
} else {
    // 🧑‍🌾 User (3 เมนู)
    $tabs = [
        [
            'url'    => 'index.php',
            'title'  => 'หน้าแรก',
            'icon'   => 'fa-house',
            'active' => in_array($cur_page, ['index.php', 'post_detail.php', 'post_create.php']),
        ],
        [
            'url'    => 'harvester.php',
            'title'  => 'ตรวจสอบรถ',
            'icon'   => 'fa-tractor',
            'active' => in_array($cur_page, ['harvester.php', 'harvester_record.php']),
            'is_center' => true,
        ],
        [
            'url'    => 'profile.php',
            'title'  => 'โปรไฟล์',
            'icon'   => 'fa-user-circle',
            'active' => ($cur_page === 'profile.php'),
        ],
    ];
}
?>
<style>
.mobile-bottom-nav {
    display: none;
    position: fixed !important;
    bottom: 0 !important;
    left: 0 !important;
    right: 0 !important;
    width: 100vw !important;
    max-width: 100% !important;
    z-index: 2147483647 !important;
    background: rgba(255, 255, 255, 0.96) !important;
    backdrop-filter: blur(20px) !important;
    -webkit-backdrop-filter: blur(20px) !important;
    border-top: 1px solid rgba(226, 232, 240, 0.9) !important;
    box-shadow: 0 -4px 25px rgba(0, 0, 0, 0.12) !important;
    padding-bottom: max(6px, env(safe-area-inset-bottom)) !important;
    pointer-events: auto !important;
    transform: none !important;
    opacity: 1 !important;
    margin: 0 !important;
}

@media (max-width: 1024px) {
    .mobile-bottom-nav {
        display: block !important;
    }
    body,
    .page-wrapper,
    .dash-wrap,
    .content-wrapper,
    .main-content,
    .pw,
    .page-container,
    .page-wrap,
    .main-container {
        padding-bottom: 85px !important;
    }
}

html.dark-mode .mobile-bottom-nav,
.dark-mode .mobile-bottom-nav {
    background: rgba(15, 23, 42, 0.96) !important;
    border-top: 1px solid rgba(30, 41, 59, 0.9) !important;
    box-shadow: 0 -4px 30px rgba(0, 0, 0, 0.5) !important;
}
</style>

<nav class="mobile-bottom-nav" aria-label="เมนูหลักด้านล่าง">
    <div class="mobile-bottom-nav-inner">
        <?php foreach ($tabs as $t): 
            $is_act = !empty($t['active']);
            $is_ctr = !empty($t['is_center']);
        ?>
            <a href="<?php echo htmlspecialchars($t['url']); ?>" 
               class="mbn-item <?php echo $is_act ? 'active' : ''; ?> <?php echo $is_ctr ? 'mbn-center' : ''; ?>"
               title="<?php echo htmlspecialchars($t['title']); ?>">
                <div class="mbn-icon-wrap">
                    <i class="fa-solid <?php echo htmlspecialchars($t['icon']); ?>"></i>
                </div>
                <span class="mbn-label"><?php echo htmlspecialchars($t['title']); ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</nav>
