<?php
/**
 * nav_u_header.php - เวอร์ชันสะอาดตา แยก Logic, CSS, JS ออกจากกัน
 * @var string $current_page
 */
require_once 'includes/header_data.php';

$is_admin = isset($_SESSION['emp_level']) && $_SESSION['emp_level'] === 'a';

// หน้าที่ทำให้ admin dropdown active
$admin_pages = ['manage_users.php','harvester_admin.php','setting_system.php','problem_reports_admin.php'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
        <link rel="icon" type="image/png" href="icon/iconweb.png">

    <script>
        (function() {
            const theme = localStorage.getItem('theme');
            if (theme === 'dark') {
                document.documentElement.classList.add('dark-mode');
            }
            document.addEventListener('DOMContentLoaded', function() {
                const btn = document.getElementById('darkModeToggleBtn');
                if(theme === 'dark') {
                    btn.classList.add('on');
                }
                btn.addEventListener('click', function() {
                    const isOn = btn.classList.toggle('on');
                    document.documentElement.classList.toggle('dark-mode', isOn);
                    localStorage.setItem('theme', isOn ? 'dark' : 'light');
                });
            });
        })();
    </script>
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

        <!-- Admin Dropdown -->
        <?php if($is_admin): ?>
        <div class="nav-admin-group" id="adminNavGroup">
            <button class="nav-admin-toggle <?php echo in_array($current_page,$admin_pages)?'active':''; ?>" id="adminToggle">
                <i class="fa-solid fa-gears"></i> จัดการ
                <i class="fa-solid fa-chevron-down arrow-icon"></i>
            </button>

            <div class="nav-admin-dropdown">

                <span class="nav-dropdown-label">สรุปภาพรวม</span>
                <a href="harvester_admin.php" class="nav-dropdown-item <?php echo ($current_page=='harvester_admin.php')?'active':''; ?>">
                    <div class="item-icon"><i class="fa-solid fa-tractor"></i></div>
                    ผลตรวจเช็กรถตัด
                </a>
                <a href="dashboard.php" class="nav-dropdown-item <?php echo ($current_page=='dashboard.php')?'active':''; ?>">
                    <div class="item-icon"><i class="fa-solid fa-chart-area"></i></div>
                    สรุปปัญหาฝ่ายไร่
                </a>

                <hr class="nav-dropdown-divider">
                <span class="nav-dropdown-label">รับเรื่อง</span>
                <a href="problem_reports_admin.php" class="nav-dropdown-item <?php echo ($current_page=='problem_reports_admin.php')?'active':''; ?>">
                    <div class="item-icon"><i class="fa-solid fa-circle-exclamation"></i></div>
                    รับรายงานปัญหา
                    <?php
                    // badge จำนวน pending
                    try {
                        $cnt_pending = $conn->query("SELECT COUNT(*) FROM problem_reports WHERE status='pending'")->fetchColumn();
                        if($cnt_pending > 0): ?>
                        <span style="margin-left:auto;background:#e11d48;color:#fff;font-size:.65rem;font-weight:700;padding:1px 6px;border-radius:10px;"><?php echo $cnt_pending; ?></span>
                        <?php endif;
                    } catch(Exception $e){}
                    ?>
                </a>

                <hr class="nav-dropdown-divider">
                <span class="nav-dropdown-label">พนักงาน</span>
                <a href="manage_users.php" class="nav-dropdown-item <?php echo ($current_page=='manage_users.php')?'active':''; ?>">
                    <div class="item-icon"><i class="fa-solid fa-users-gear"></i></div>
                    จัดการพนักงาน
                </a>

                <hr class="nav-dropdown-divider">
                <span class="nav-dropdown-label">ระบบ</span>
                <a href="setting_system.php" class="nav-dropdown-item <?php echo ($current_page=='setting_system.php')?'active':''; ?>">
                    <div class="item-icon"><i class="fa-solid fa-sliders"></i></div>
                    ตั้งค่าระบบ
                </a>

            </div>
        </div>
        <?php endif; ?>

        <!-- Mobile Profile (แสดงใน hamburger เท่านั้น) -->
        <div class="mobile-only-profile">
            <div class="mobile-user-info">
                <i class="fa-solid fa-user-circle"></i>
                <?php echo htmlspecialchars($_SESSION['emp_name'] ?? 'ผู้ใช้งาน'); ?>
                <br>
                <span style="color:#94a3b8;font-size:0.8rem;">
                    <i class="fa-solid fa-building"></i>
                    <?php echo htmlspecialchars($_SESSION['emp_unit'] ?? '-'); ?>
                </span>
            </div>
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
            <div class="user-profile-info">
                <i class="fa-solid fa-circle-user" style="font-size:1.1rem;color:#10b981;"></i>
                <span><?php echo htmlspecialchars($_SESSION['emp_name'] ?? 'Guest'); ?></span>
                <span style="color:#94a3b8;font-size:.82rem;">(<?php echo htmlspecialchars($_SESSION['emp_unit'] ?? '-'); ?>)</span>
            </div>
            <a href="logout.php" class="btn-logout" title="ออกจากระบบ">
                <i class="fa-solid fa-right-from-bracket"></i> ออกจากระบบ
            </a>
        </div>

        <!-- Hamburger Toggle (Mobile) -->
        <button class="nav-toggle-btn" id="navToggle" aria-label="เปิดเมนู">
            <i class="fa-solid fa-bars" id="navToggleIcon"></i>
        </button>

    </div><!-- /.nav-right-zone -->

</nav>

<script src="includes/nav_script.js?v=<?php echo filemtime('includes/nav_script.js'); ?>"></script>