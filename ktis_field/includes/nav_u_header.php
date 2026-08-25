<?php
/**
 * nav_u_header.php - เวอร์ชันสะอาดตา แยก Logic, CSS, JS ออกจากกัน
 * @var string $current_page
 */
require_once 'includes/header_data.php';

$is_admin = isset($_SESSION['emp_level']) && $_SESSION['emp_level'] === 'a';
$is_mech  = isset($_SESSION['emp_level']) && $_SESSION['emp_level'] === 'm';

// หน้าที่ทำให้ admin/mechanic dropdown active
$admin_pages = ['manage_users.php','harvester_admin.php','harvester_daily_dashboard.php','harvester_map.php','setting_system.php','problem_reports_admin.php','report_all.php'];
$mech_pages  = ['harvester_daily_dashboard.php','harvester_map.php','harvester_admin.php'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <link rel="icon" type="image/png" href="icon/iconweb.png">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#e11d48">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <script>
        (function() {
            const theme = localStorage.getItem('theme');
            if (theme === 'dark') {
                document.documentElement.classList.add('dark-mode');
            }
            document.addEventListener('DOMContentLoaded', function() {
                const btn = document.getElementById('darkModeToggleBtn');
                if(btn) {
                    if(theme === 'dark') {
                        btn.classList.add('on');
                    }
                    btn.addEventListener('click', function() {
                        const isOn = btn.classList.toggle('on');
                        document.documentElement.classList.toggle('dark-mode', isOn);
                        localStorage.setItem('theme', isOn ? 'dark' : 'light');
                    });
                }
            });

            // Register PWA Service Worker
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function() {
                    navigator.serviceWorker.register('sw.js').then(reg => {
                        console.log('PWA Service Worker registered:', reg.scope);
                    }).catch(err => {
                        console.warn('PWA Service Worker registration failed:', err);
                    });
                });
            }
        })();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="offline_sync.js"></script>
    <style>
        .ios-toggle {
            position: relative;
            width: 46px;
            height: 24px;
            background: #18181b;
            border-radius: 14px;
            cursor: pointer;
            border: none;
            padding: 0;
            display: flex;
            align-items: center;
            flex-shrink: 0;
        }
        .ios-toggle .knob {
            position: absolute;
            top: 2px;
            left: 2px;
            width: 20px;
            height: 20px;
            background: white;
            border-radius: 50%;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 2;
        }
        .ios-toggle.on .knob {
            transform: translateX(22px);
        }
        .ios-toggle .track-icon {
            position: relative;
            z-index: 1;
            font-size: 0.65rem;
            color: #fff;
            flex: 1;
            text-align: center;
        }
        .ios-toggle .icon-sun {
            margin-left: 1px;
        }
        .ios-toggle .icon-moon {
            margin-right: 1px;
        }
    </style>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="includes/nav_custom.css?v=<?php echo filemtime('includes/nav_custom.css'); ?>">
</head>
<body>

<nav class="main-navbar">

    <!-- Logo -->
    <a href="index.php" class="nav-logo" style="text-decoration:none;color:inherit;">
        <i class="fa-solid fa-tractor" style="color:#10b981;"></i>
        <span class="logo-full">TIS <span style="color:#e11d48;">SMART FIELD</span></span>
        <span class="logo-short" style="display:none;color:#e11d48;">TIS FIELD</span>
    </a>

    <!-- Main Menu (Desktop + Hamburger) -->
    <div class="nav-menu" id="navMenu">

        <!-- หน้าแรก -->
        <a href="index.php" class="nav-item <?php echo ($current_page=='index.php')?'active':''; ?>">
            <i class="fa-solid fa-house"></i> หน้าแรก
        </a>

        <!-- รายงานรถตัด (User เท่านั้น) -->
        <?php if(!$is_admin): ?>
        <a href="harvester.php" class="nav-item <?php echo ($current_page=='harvester.php')?'active':''; ?>">
            <i class="fa-solid fa-tractor"></i> รายงานรถตัดประจำวัน
        </a>
        <?php endif; ?>

        <!-- รายงานปัญหา: User เห็นลิงก์เดียว / Admin เห็นใน dropdown -->
        <?php if(!$is_admin): ?>
        <a href="report_problem.php" class="nav-item <?php echo ($current_page=='report_problem.php')?'active':''; ?>">
            <i class="fa-solid fa-circle-exclamation"></i> รายงานปัญหา
        </a>
        <?php endif; ?>

        <!-- Mechanic Dropdown -->
        <?php if($is_mech): ?>
        <div class="nav-admin-group" id="adminNavGroup">
            <button class="nav-admin-toggle <?php echo in_array($current_page,$mech_pages)?'active':''; ?>" id="adminToggle" style="background: rgba(245,158,11,0.15); color: #d97706; border-color: rgba(245,158,11,0.3);">
                <i class="fa-solid fa-wrench"></i> เมนูนายช่าง
                <i class="fa-solid fa-chevron-down arrow-icon"></i>
            </button>

            <div class="nav-admin-dropdown">
                <div class="nav-category-group open">
                    <button type="button" class="nav-category-header" onclick="toggleNavCategory(event, this)">
                        <div class="cat-title">
                            <span class="cat-dot" style="background:#f59e0b;"></span>
                            <i class="fa-solid fa-tractor cat-icon" style="color:#f59e0b;"></i>
                            <span>งานรถตัด & การตรวจเช็ค</span>
                        </div>
                        <i class="fa-solid fa-chevron-down cat-arrow"></i>
                    </button>
                    <div class="nav-category-content">
                        <a href="harvester_daily_dashboard.php" class="nav-dropdown-item <?php echo ($current_page=='harvester_daily_dashboard.php')?'active':''; ?>">
                            <div class="item-icon" style="background:#fef3c7; color:#b45309;"><i class="fa-solid fa-gauge-high"></i></div>
                            <span>การเช็ครถตัดประจำวัน</span>
                        </a>
                        <a href="harvester_map.php" class="nav-dropdown-item <?php echo ($current_page=='harvester_map.php')?'active':''; ?>">
                            <div class="item-icon" style="background:#e0f2fe; color:#0284c7;"><i class="fa-solid fa-map-location-dot"></i></div>
                            <span>แผนที่พิกัดรถตัด</span>
                        </a>
                        <a href="harvester_admin.php" class="nav-dropdown-item <?php echo ($current_page=='harvester_admin.php')?'active':''; ?>">
                            <div class="item-icon" style="background:#f0fdf4; color:#16a34a;"><i class="fa-solid fa-tractor"></i></div>
                            <span>ผลตรวจเช็กรถตัด</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Admin Dropdown -->
        <?php if($is_admin): 
            $cnt_pending_reports = 0;
            try {
                $cnt_pending_reports = (int)$conn->query("SELECT COUNT(*) FROM problem_reports WHERE status='pending'")->fetchColumn();
            } catch(Exception $e){}
            
            $cat_overview_pages = ['report_all.php','harvester_daily_dashboard.php','harvester_map.php','harvester_admin.php','dashboard.php'];
            $cat_user_pages     = ['manage_users.php','add_user.php','edit_user.php'];
            $cat_report_pages   = ['problem_reports_admin.php'];
            $cat_system_pages   = ['setting_system.php'];
        ?>
        <div class="nav-admin-group" id="adminNavGroup">
            <button class="nav-admin-toggle <?php echo in_array($current_page,$admin_pages)?'active':''; ?>" id="adminToggle">
                <i class="fa-solid fa-gears"></i> จัดการ
                <i class="fa-solid fa-chevron-down arrow-icon"></i>
            </button>

            <div class="nav-admin-dropdown">

                <!-- 1. หมวดสรุปภาพรวม -->
                <div class="nav-category-group <?php echo in_array($current_page, $cat_overview_pages)?'open':''; ?>">
                    <button type="button" class="nav-category-header" onclick="toggleNavCategory(event, this)">
                        <div class="cat-title">
                            <span class="cat-dot" style="background:#0284c7;"></span>
                            <i class="fa-solid fa-chart-pie cat-icon" style="color:#0284c7;"></i>
                            <span>สรุปภาพรวม</span>
                        </div>
                        <i class="fa-solid fa-chevron-down cat-arrow"></i>
                    </button>
                    <div class="nav-category-content">
                        <a href="report_all.php" class="nav-dropdown-item <?php echo ($current_page=='report_all.php')?'active':''; ?>">
                            <div class="item-icon"><i class="fa-solid fa-chart-line"></i></div>
                            <span>รายงานภาพรวมระบบ</span>
                        </a>
                        <a href="harvester_daily_dashboard.php" class="nav-dropdown-item <?php echo ($current_page=='harvester_daily_dashboard.php')?'active':''; ?>">
                            <div class="item-icon"><i class="fa-solid fa-gauge-high"></i></div>
                            <span>การเช็ครถตัดประจำวัน</span>
                        </a>
                        <a href="harvester_map.php" class="nav-dropdown-item <?php echo ($current_page=='harvester_map.php')?'active':''; ?>">
                            <div class="item-icon"><i class="fa-solid fa-map-location-dot"></i></div>
                            <span>แผนที่พิกัดรถตัด</span>
                        </a>
                        <a href="harvester_admin.php" class="nav-dropdown-item <?php echo ($current_page=='harvester_admin.php')?'active':''; ?>">
                            <div class="item-icon"><i class="fa-solid fa-tractor"></i></div>
                            <span>ผลตรวจเช็กรถตัด</span>
                        </a>
                        <a href="dashboard.php" class="nav-dropdown-item <?php echo ($current_page=='dashboard.php')?'active':''; ?>">
                            <div class="item-icon"><i class="fa-solid fa-chart-area"></i></div>
                            <span>สรุปปัญหาฝ่ายไร่</span>
                        </a>
                    </div>
                </div>

                <!-- 2. หมวดรับเรื่อง -->
                <div class="nav-category-group <?php echo in_array($current_page, $cat_report_pages)?'open':''; ?>">
                    <button type="button" class="nav-category-header" onclick="toggleNavCategory(event, this)">
                        <div class="cat-title">
                            <span class="cat-dot" style="background:#e11d48;"></span>
                            <i class="fa-solid fa-inbox cat-icon" style="color:#e11d48;"></i>
                            <span>รับเรื่อง & ปัญหา</span>
                        </div>
                        <?php if($cnt_pending_reports > 0): ?>
                        <span class="cat-badge-alert"><?php echo $cnt_pending_reports; ?></span>
                        <?php endif; ?>
                        <i class="fa-solid fa-chevron-down cat-arrow"></i>
                    </button>
                    <div class="nav-category-content">
                        <a href="problem_reports_admin.php" class="nav-dropdown-item <?php echo ($current_page=='problem_reports_admin.php')?'active':''; ?>">
                            <div class="item-icon"><i class="fa-solid fa-circle-exclamation"></i></div>
                            <span>รับรายงานปัญหา</span>
                            <?php if($cnt_pending_reports > 0): ?>
                            <span class="badge-count"><?php echo $cnt_pending_reports; ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>

                <!-- 3. หมวดพนักงาน -->
                <div class="nav-category-group <?php echo in_array($current_page, $cat_user_pages)?'open':''; ?>">
                    <button type="button" class="nav-category-header" onclick="toggleNavCategory(event, this)">
                        <div class="cat-title">
                            <span class="cat-dot" style="background:#10b981;"></span>
                            <i class="fa-solid fa-users-gear cat-icon" style="color:#10b981;"></i>
                            <span>พนักงาน</span>
                        </div>
                        <i class="fa-solid fa-chevron-down cat-arrow"></i>
                    </button>
                    <div class="nav-category-content">
                        <a href="manage_users.php" class="nav-dropdown-item <?php echo in_array($current_page, $cat_user_pages)?'active':''; ?>">
                            <div class="item-icon"><i class="fa-solid fa-users"></i></div>
                            <span>จัดการพนักงาน</span>
                        </a>
                    </div>
                </div>

                <!-- 4. หมวดระบบ -->
                <div class="nav-category-group <?php echo in_array($current_page, $cat_system_pages)?'open':''; ?>">
                    <button type="button" class="nav-category-header" onclick="toggleNavCategory(event, this)">
                        <div class="cat-title">
                            <span class="cat-dot" style="background:#8b5cf6;"></span>
                            <i class="fa-solid fa-sliders cat-icon" style="color:#8b5cf6;"></i>
                            <span>ระบบ & การตั้งค่า</span>
                        </div>
                        <i class="fa-solid fa-chevron-down cat-arrow"></i>
                    </button>
                    <div class="nav-category-content">
                        <a href="setting_system.php" class="nav-dropdown-item <?php echo ($current_page=='setting_system.php')?'active':''; ?>">
                            <div class="item-icon"><i class="fa-solid fa-sliders"></i></div>
                            <span>ตั้งค่าระบบ</span>
                        </a>
                    </div>
                </div>

            </div>
        </div>
        <?php endif; ?>

        <!-- Mobile Profile (แสดงใน hamburger เท่านั้น) -->
        <div class="mobile-only-profile">
            <a href="profile.php" class="mobile-user-info" style="text-decoration:none; color:inherit; display:block;">
                <i class="fa-solid fa-user-circle"></i>
                <?php echo htmlspecialchars($_SESSION['emp_name'] ?? 'ผู้ใช้งาน'); ?>
                <br>
                <span style="color:#94a3b8;font-size:0.8rem;">
                    <i class="fa-solid fa-building"></i>
                    <?php echo htmlspecialchars($_SESSION['emp_unit'] ?? '-'); ?>
                </span>
                <span style="display:block; font-size:0.72rem; color:#38bdf8; margin-top:2px;">
                    <i class="fa-solid fa-gear"></i> ดูโปรไฟล์ / เปลี่ยนรหัสผ่าน
                </span>
            </a>
            <a href="logout.php" class="mobile-logout">
                <i class="fa-solid fa-right-from-bracket"></i> ออกจากระบบ
            </a>
        </div>

    </div><!-- /.nav-menu -->

    <!-- Right Zone -->
    <div class="nav-right-zone">

        <div class="badge-crop">
            ปีผลิต <?php echo htmlspecialchars($_SESSION['crop_year'] ?? '-'); ?>
        </div>

        <!-- Dark Mode Toggle (iOS Style) -->
        <button type="button" class="ios-toggle" id="darkModeToggleBtn" title="สลับโหมดมืด/สว่าง" aria-label="สลับโหมดมืด/สว่าง">
            <i class="fa-solid fa-sun track-icon icon-sun"></i>
            <i class="fa-solid fa-moon track-icon icon-moon"></i>
            <span class="knob"></span>
        </button>

        <!-- Notification Bell -->
        <button class="noti-bell-btn" id="notiBellBtn" title="การแจ้งเตือน">
            <i class="fa-solid fa-bell"></i>
            <?php if($noti_count > 0): ?>
                <span class="noti-count-badge"><?php echo $noti_count; ?></span>
            <?php endif; ?>
        </button>

        <!-- Notification Dropdown -->
        <div class="noti-dropdown-box" id="notiBox">
            <div class="noti-header">
                <span><i class="fa-solid fa-bell" style="color:#e11d48;margin-right:5px;"></i>การแจ้งเตือนล่าสุด</span>
                <?php if($noti_count > 0): ?>
                    <a href="notification_api.php" style="color:#10b981;font-size:.75rem;text-decoration:none;font-weight:600;">
                        <i class="fa-solid fa-check-double"></i> อ่านทั้งหมด
                    </a>
                <?php endif; ?>
            </div>
            <div class="noti-list-wrapper">
                <?php if(empty($notifications)): ?>
                    <div class="noti-empty">
                        <i class="fa-regular fa-bell-slash" style="font-size:1.5rem;display:block;margin-bottom:6px;"></i>
                        ไม่มีการแจ้งเตือนใหม่
                    </div>
                <?php else: ?>
                    <?php foreach($notifications as $noti): ?>
                    <div class="noti-item-row <?php echo $noti['is_read']==0?'unread-row':''; ?>"
                         onclick="location.href='notification_api.php?post_id=<?php echo (int)$noti['post_id']; ?>'">
                        <div class="noti-icon">
                            <i class="fa-solid <?php echo $noti['is_read']==0?'fa-envelope':'fa-envelope-open'; ?>"></i>
                        </div>
                        <div class="noti-info-text">
                            <div class="noti-main-text <?php echo $noti['is_read']==0?'bold':''; ?>">
                                <?php echo htmlspecialchars($noti['noti_text']); ?>
                            </div>
                            <?php if(!empty($noti['problem_detail'])): ?>
                            <div class="noti-problem-preview">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                <?php echo htmlspecialchars(mb_substr($noti['problem_detail'],0,50,'UTF-8')); ?>
                            </div>
                            <?php endif; ?>
                            <div class="noti-time-stamp">
                                <i class="fa-regular fa-clock"></i>
                                <?php echo date('d/m/Y H:i', strtotime($noti['created_at'])); ?> น.
                            </div>
                        </div>
                        <button class="btn-dismiss-noti"
                                onclick="deleteNotification(event, <?php echo (int)$noti['noti_id']; ?>)"
                                title="ลบการแจ้งเตือน">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- PC Profile -->
        <div class="pc-profile-zone">
            <a href="profile.php" class="user-profile-info" style="text-decoration:none; color:inherit; cursor:pointer;" title="คลิกเพื่อดูโปรไฟล์และเปลี่ยนรหัสผ่าน">
                <i class="fa-solid fa-circle-user" style="font-size:1.1rem;color:#10b981;"></i>
                <span><?php echo htmlspecialchars($_SESSION['emp_name'] ?? 'Guest'); ?></span>
                <span style="color:#94a3b8;font-size:.82rem;">(<?php echo htmlspecialchars($_SESSION['emp_unit'] ?? '-'); ?>)</span>
            </a>
            <a href="logout.php" class="btn-logout" title="ออกจากระบบ">
                <i class="fa-solid fa-right-from-bracket"></i> ออกจากระบบ
            </a>
        </div>

        <!-- Hamburger Toggle (Mobile) -->
        <button class="nav-toggle-btn" id="navToggle" aria-label="เปิดเมนู">
            <i class="fa-solid fa-bars" id="navToggleIcon"></i>
</nav>

<?php if (!empty($is_maintenance) && !empty($is_admin)): ?>
<div style="background:linear-gradient(90deg, #d97706 0%, #b45309 100%);color:#ffffff;padding:8px 16px;text-align:center;font-size:0.84rem;font-weight:700;display:flex;align-items:center;justify-content:center;gap:10px;box-shadow:0 2px 8px rgba(0,0,0,0.15);position:relative;z-index:998;flex-wrap:wrap;">
    <div style="display:flex;align-items:center;gap:6px;">
        <i class="fa-solid fa-triangle-exclamation" style="font-size:0.95rem;color:#fde68a;"></i>
        <span>ระบบกำลังอยู่ในโหมดปิดปรับปรุง (ผู้ใช้ทั่วไปไม่สามารถเข้าใช้งานได้)</span>
    </div>
    <div style="display:flex;gap:6px;">
        <a href="maintenance.php?preview=1" target="_blank" style="background:rgba(255,255,255,0.2);color:#ffffff;padding:2px 8px;border-radius:6px;text-decoration:none;font-size:0.75rem;font-weight:700;display:inline-flex;align-items:center;gap:4px;">
            <i class="fa-solid fa-eye"></i> ดูหน้าปิดปรับปรุง
        </a>
        <a href="setting_system.php" style="background:#ffffff;color:#92400e;padding:2px 10px;border-radius:6px;text-decoration:none;font-size:0.75rem;font-weight:800;display:inline-flex;align-items:center;gap:4px;">
            <i class="fa-solid fa-sliders"></i> ตั้งค่าเปิดระบบ
        </a>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/mobile_bottom_nav.php'; ?>

<script src="includes/nav_script.js?v=<?php echo filemtime('includes/nav_script.js'); ?>"></script>