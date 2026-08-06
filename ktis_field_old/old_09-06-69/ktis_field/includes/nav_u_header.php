<?php
/**
 * nav_u_header.php - เวอร์ชันเพิ่มหน้าต่าง Popup แจ้งเตือนด่วน + รองรับสิทธิ์ตรวจสอบหน่วยส่งเสริมย่อย
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

// 1. ดึงจำนวนการแจ้งเตือนและรายการแจ้งเตือนตามสิทธิ์ของพนักงาน/หน่วยส่งเสริม
$noti_count = 0;
$notifications = [];

if(isset($_SESSION['emp_id']) && isset($_SESSION['emp_unit'])) {
    $my_unit = $_SESSION['emp_unit'];       // เช่น "บางขลับ"
    $my_emp_id = $_SESSION['emp_id'];
    $my_level = $_SESSION['emp_level'] ?? 'u'; // ระดับพนักงาน 'a' = admin, 'u' = user

    try {
        // --- ส่วนที่ 1: นับจำนวน Unread ที่ยังไม่ได้อ่าน (ดูเฉพาะของตัวเอง) ---
        $stmt_count = $conn->prepare("SELECT COUNT(*) AS unread FROM notifications WHERE is_read = 0 AND emp_id = :emp_id");
        $stmt_count->execute([':emp_id' => $my_emp_id]);
        $noti_count = $stmt_count->fetch()['unread'];

        // --- ส่วนที่ 2: ดึงรายการแจ้งเตือนล่าสุด 8 รายการ พร้อม problem_detail จากตาราง posts ---
        $stmt_list = $conn->prepare(
            "SELECT n.*, p.problem_detail, DATE(p.created_at) AS post_date 
             FROM notifications n
             LEFT JOIN posts p ON n.post_id = p.post_id
             WHERE n.emp_id = :emp_id 
             ORDER BY n.noti_id DESC LIMIT 8"
        );
        $stmt_list->execute([':emp_id' => $my_emp_id]);
        $notifications = $stmt_list->fetchAll();

    } catch (Exception $e) {
        error_log("Notification System Error: " . $e->getMessage());
    }
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        .main-navbar { 
            background-color: #1e293b; color: white; padding: 12px 24px; 
            display: flex; justify-content: space-between; align-items: center; 
            font-family: 'Sarabun', sans-serif; box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            position: relative; z-index: 999;  top: 0; width: 100%;
        }
        .nav-logo { font-weight: 700; font-size: 1.25rem; display: flex; align-items: center; gap: 8px; white-space: nowrap; }
        .nav-logo span { color: #e11d48; }
        
        .nav-menu { display: flex; align-items: center; gap: 20px; }
        .nav-item { color: #cbd5e1; text-decoration: none; font-weight: 600; font-size: 0.95rem; transition: color 0.2s; display: flex; align-items: center; gap: 6px; }
        .nav-item:hover, .nav-item.active { color: white; }
        
        .nav-right-zone { display: flex; align-items: center; gap: 18px; position: relative; }
        .badge-crop { background-color: #10b981; color: white; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; white-space: nowrap; }
        
        /* ปุ่มกระดิ่งแก้ไขให้เป็นปุ่มกด */
        .noti-bell-btn { background: none; border: none; position: relative; cursor: pointer; color: #cbd5e1; font-size: 1.2rem; transition: color 0.2s; display: flex; align-items: center; padding: 5px; }
        .noti-bell-btn:hover { color: white; }
        .noti-count-badge { position: absolute; top: -5px; right: -6px; background-color: #e11d48; color: white; font-size: 0.65rem; font-weight: 700; padding: 1px 5px; border-radius: 50%; border: 2px solid #1e293b; }
        
        .user-profile-info { font-size: 0.9rem; color: #f1f5f9; display: flex; align-items: center; gap: 6px; }
        .btn-logout { color: #f43f5e; text-decoration: none; font-size: 0.9rem; font-weight: 600; margin-left: 5px; display: inline-flex; align-items: center; gap: 4px; }
        .nav-toggle-btn { display: none; background: none; border: none; color: #cbd5e1; font-size: 1.35rem; cursor: pointer; padding: 5px; }

        /* 🔔 กล่องหน้าต่างแจ้งเตือนด่วน Dropdown Modal */
        .noti-dropdown-box {
            display: none; position: absolute; top: 45px; right: 120px; 
            background: white; width: 360px; border-radius: 12px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.15); border: 1px solid #e2e8f0;
            color: #334155; overflow: hidden; z-index: 1000;
        }
        .noti-dropdown-box.show { display: block; animation: fadeInNoti 0.2s ease-out; }
        @keyframes fadeInNoti { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        
        .noti-header { background: #f8fafc; padding: 12px 16px; font-weight: 700; font-size: 0.9rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .noti-list-wrapper { max-height: 360px; overflow-y: auto; }
        
        /* สไตล์แต่ละ row */
        .noti-item-row { 
            padding: 10px 14px; border-bottom: 1px solid #f1f5f9; 
            display: flex; gap: 10px; align-items: flex-start;
            color: #334155; cursor: pointer; transition: background 0.15s;
            position: relative;
        }
        .noti-item-row:hover { background-color: #f8fafc; }
        .noti-item-row.unread-row { background-color: #f0fdf4; }
        
        .noti-icon { width: 34px; height: 34px; border-radius: 50%; background: #fee2e2; color: #e11d48; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 0.85rem; margin-top: 2px; }
        .noti-item-row.unread-row .noti-icon { background: #d1fae5; color: #10b981; }
        
        .noti-info-text { flex: 1; font-size: 0.83rem; line-height: 1.45; min-width: 0; }
        .noti-main-text { font-size: 0.85rem; color: #1e293b; margin-bottom: 3px; }
        .noti-main-text.bold { font-weight: 700; }
        
        /* ตัวอย่างปัญหา: กล่องเล็กสีเหลืองอ่อน */
        .noti-problem-preview {
            background: #fff7ed; border-left: 3px solid #f59e0b;
            color: #92400e; font-size: 0.78rem; padding: 4px 8px;
            border-radius: 0 4px 4px 0; margin: 4px 0;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            max-width: 100%;
        }
        
        /* timestamp: วันที่ + เวลา */
        .noti-time-stamp { font-size: 0.75rem; color: #94a3b8; margin-top: 4px; display: flex; align-items: center; gap: 4px; }
        
        /* ปุ่ม X ปิดกระดิ่ง */
        .btn-dismiss-noti {
            flex-shrink: 0; background: none; border: none;
            color: #cbd5e1; font-size: 0.85rem; cursor: pointer;
            padding: 2px 4px; border-radius: 4px;
            transition: color 0.15s, background 0.15s;
            margin-top: 1px; align-self: flex-start;
        }
        .btn-dismiss-noti:hover { color: #ef4444; background: #fee2e2; }
        
        .noti-empty { padding: 30px; text-align: center; color: #94a3b8; font-size: 0.85rem; }
        .noti-footer-link { display: block; text-align: center; padding: 10px; font-size: 0.82rem; color: #64748b; border-top: 1px solid #f1f5f9; text-decoration: none; font-weight: 600; }
        .noti-footer-link:hover { background: #f8fafc; color: #1e293b; }

        @media (max-width: 991px) {
            .nav-toggle-btn { display: block; }
            .nav-menu { 
                display: none; flex-direction: column; width: 100%; position: absolute; 
                top: 100%; left: 0; background-color: #1e293b; padding: 15px 24px; 
                box-shadow: 0 4px 10px rgba(0,0,0,0.15); border-top: 1px solid rgba(255,255,255,0.08); gap: 12px; align-items: flex-start;
            }
            .nav-menu.show-menu { display: flex; }
            .nav-item { width: 100%; padding: 8px 0; font-size: 1rem; border-bottom: 1px solid rgba(255,255,255,0.05); }
            .mobile-profile-block { width: 100%; margin-top: 8px; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.15); display: flex; flex-direction: column; gap: 10px; }
            .pc-profile-zone { display: none !important; }
            .noti-dropdown-box { right: 15px; top: 50px; width: 290px; }
        }
        /* ซ่อนส่วนมือถือใน Desktop และแสดงใน Mobile */
    .mobile-user-info { color: #f1f5f9; font-size: 0.95rem; margin-bottom: 10px; }
    .mobile-logout { color: #f43f5e; text-decoration: none; font-weight: 600; font-size: 0.9rem; }
    /* ซ่อนโปรไฟล์มือถือบน Desktop */
.mobile-only-profile {display: none;}
.mobile-user-info {color: #f1f5f9;font-size: 0.95rem;margin-bottom: 10px;}
.mobile-logout {color: #f43f5e;text-decoration: none;font-weight: 600;font-size: 0.9rem;}

/* Responsive สำหรับมือถือ */
@media (max-width: 991px) {.main-navbar { padding: 10px 12px;gap: 10px;}
.nav-logo {font-size: 1rem; min-width: 0;}

    .nav-right-zone {
        gap: 10px;
    }

    .badge-crop {
        font-size: 0.7rem;
        padding: 3px 8px;
    }

    .nav-toggle-btn {
        display: block;
    }

    .nav-menu {
        display: none;
        flex-direction: column;
        width: 100%;
        position: absolute;
        top: 100%;
        left: 0;
        background-color: #1e293b;
        padding: 15px 20px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        border-top: 1px solid rgba(255,255,255,0.08);
        gap: 12px;
        align-items: flex-start;
        box-sizing: border-box;
    }

    .nav-menu.show-menu {
        display: flex;
    }

    .nav-item {
        width: 100%;
        padding: 8px 0;
        font-size: 1rem;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }

    .mobile-only-profile {
        display: block;
        width: 100%;
        margin-top: 8px;
        padding-top: 12px;
        border-top: 1px solid rgba(255,255,255,0.15);
    }

    .pc-profile-zone {
        display: none !important;
    }

    .noti-dropdown-box {
        position: fixed;
        top: 58px;
        right: 10px;
        left: 10px;
        width: auto;
        max-width: none;
    }
}

@media (max-width: 420px) {
    .badge-crop {
        display: none;
    }

    .nav-logo {
        font-size: 0.9rem;
    }
}
   
    </style>
</head>
<body>

<nav class="main-navbar">
    <div class="nav-logo">
        <i class="fa-solid fa-tractor" style="color: #10b981;"></i> KTIS <span>SMART FIELD</span>
    </div>
   
    <div class="nav-menu" id="navMenu">
        <a href="index.php" class="nav-item <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>"><i class="fa-solid fa-house"></i> หน้าแรกฟีด</a>
        <?php if(isset($_SESSION['emp_level']) && $_SESSION['emp_level'] == 'a'): ?>
            <a href="harvester_admin.php" class="nav-item <?php echo ($current_page == 'harvester_admin.php') ? 'active' : ''; ?>"><i class="fa-solid fa-chart-bar"></i> จัดการตรวจเช็กรถตัด</a>
            <a href="manage_users.php" class="nav-item <?php echo ($current_page == 'manage_users.php') ? 'active' : ''; ?>"><i class="fa-solid fa-users-gear"></i> จัดการผู้ใช้</a>
        <?php else: ?>
            <a href="harvester.php" class="nav-item <?php echo ($current_page == 'harvester.php') ? 'active' : ''; ?>"><i class="fa-solid fa-screwdriver-wrench"></i> ตรวจเช็กรถตัด</a>
        <?php endif; ?>

        <div class="mobile-only-profile">
            <div class="mobile-user-info">
                <i class="fa-solid fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['emp_name'] ?? 'ผู้ใช้งาน'); ?>
                <br><span style="color:#94a3b8; font-size:0.8rem;"><i class="fa-solid fa-building"></i> <?php echo htmlspecialchars($_SESSION['emp_unit'] ?? '-'); ?></span>
            </div>
            <a href="logout.php" class="mobile-logout"><i class="fa-solid fa-right-from-bracket"></i> ออกจากระบบ</a>
        </div>
    </div>
    <!-- /.nav-menu -->
    
    <div class="nav-right-zone">
        <div class="badge-crop">ปีผลิต <?php echo htmlspecialchars($_SESSION['crop_year'] ?? '-'); ?></div>
        
        <button type="button" class="noti-bell-btn" onclick="toggleNotiDropdown(event)" title="การแจ้งเตือน">
            <i class="fa-solid fa-bell"></i>
            <?php if($noti_count > 0): ?>
                <span class="noti-count-badge" id="notiBadgeCount"><?php echo $noti_count; ?></span>
            <?php endif; ?>
        </button>
        
        <div class="noti-dropdown-box" id="notiDropdown">
            <div class="noti-header">
                <span><i class="fa-solid fa-bell" style="color:#e11d48;"></i> แจ้งเตือนล่าสุด</span>
                <?php if($noti_count > 0): ?>
                    <span style="font-size:0.75rem; background:#fee2e2; color:#e11d48; padding:2px 8px; border-radius:10px;">ใหม่ <?php echo $noti_count; ?> รายการ</span>
                <?php endif; ?>
            </div>
            <div class="noti-list-wrapper" id="notiListWrapper">
                <?php if(empty($notifications)): ?>
                    <div class="noti-empty"><i class="fa-regular fa-bell-slash"></i><br>ไม่มีรายการแจ้งเตือนในขณะนี้</div>
                <?php else: ?>
                    <?php foreach($notifications as $noti): 
                        $is_unread = ($noti['is_read'] == 0);
                        $target_post_id = isset($noti['post_id']) ? $noti['post_id'] : 0;
                        $post_date = isset($noti['post_date']) ? $noti['post_date'] : '';
                        $problem_preview = isset($noti['problem_detail']) && !empty($noti['problem_detail'])
                            ? mb_substr($noti['problem_detail'], 0, 45, 'UTF-8') . (mb_strlen($noti['problem_detail'], 'UTF-8') > 45 ? '...' : '')
                            : null;
                        // แสดงวันที่เป็นภาษาไทย
                        $noti_date_thai = date('j M Y', strtotime($noti['created_at']));
                        $noti_time_thai = date('H:i น.', strtotime($noti['created_at']));
                    ?>
                        <div class="noti-item-row <?php echo $is_unread ? 'unread-row' : ''; ?>" 
                             id="noti-row-<?php echo $noti['noti_id']; ?>"
                             onclick="scrollToTargetPost(<?php echo $target_post_id; ?>, <?php echo $noti['noti_id']; ?>, '<?php echo $post_date; ?>')">
                            <div class="noti-icon">
                                <i class="fa-solid <?php echo $is_unread ? 'fa-envelope' : 'fa-envelope-open'; ?>"></i>
                            </div>
                            <div class="noti-info-text">
                                <div class="noti-main-text <?php echo $is_unread ? 'bold' : ''; ?>">
                                    <?php echo htmlspecialchars($noti['noti_text']); ?>
                                </div>
                                <?php if($problem_preview): ?>
                                <div class="noti-problem-preview">
                                    <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($problem_preview); ?>
                                </div>
                                <?php endif; ?>
                                <div class="noti-time-stamp">
                                    <i class="fa-regular fa-calendar"></i> <?php echo $noti_date_thai; ?>
                                    &nbsp;<i class="fa-regular fa-clock"></i> <?php echo $noti_time_thai; ?>
                                </div>
                            </div>
                            <button class="btn-dismiss-noti" 
                                    onclick="dismissNoti(event, <?php echo $noti['noti_id']; ?>)"
                                    title="ปิดการแจ้งเตือนนี้">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="user-profile-info pc-profile-zone">
            <i class="fa-solid fa-user-circle"></i> 
            <?php echo htmlspecialchars($_SESSION['emp_name'] ?? 'ผู้ใช้งาน'); ?> 
            <span style="color:#94a3b8; font-size:0.85rem; margin-left:2px;">(<?php echo htmlspecialchars($_SESSION['emp_unit'] ?? '-'); ?>)</span>
            <a href="logout.php" class="btn-logout" title="ออกจากระบบ"><i class="fa-solid fa-right-from-bracket"></i> ออกระบบ</a>
        </div>

        <button class="nav-toggle-btn" onclick="toggleMobileMenu()" aria-label="เปิดเมนู">
            <i class="fa-solid fa-bars" id="menuIcon"></i>
        </button>
    </div><!-- /.nav-right-zone -->
</nav>

<script>
function toggleMobileMenu() {
    var menu = document.getElementById("navMenu");
    var icon = document.getElementById("menuIcon");
    if (menu.classList.contains("show-menu")) {
        menu.classList.remove("show-menu"); icon.className = "fa-solid fa-bars";
    } else {
        menu.classList.add("show-menu"); icon.className = "fa-solid fa-xmark";
    }
}

// เปิด-ปิด กล่องพรีวิวแจ้งเตือน
function toggleNotiDropdown(event) {
    event.stopPropagation();
    document.getElementById("notiDropdown").classList.toggle("show");
}

// คลิกข้างนอกกล่องให้ปิด Popup อัตโนมัติ
document.addEventListener("click", function(e) {
    var dropdown = document.getElementById("notiDropdown");
    if(dropdown && !dropdown.contains(e.target)) {
        dropdown.classList.remove("show");
    }
});

// ✨ ปิด/ซ่อนกระดิ่งทีละรายการ — AJAX ส่งไป mark as read แล้วซ่อน row
function dismissNoti(event, notiId) {
    event.stopPropagation(); // ไม่ให้การ click บุปขึ้นไปเปิด scrollToTargetPost
    
    let row = document.getElementById('noti-row-' + notiId);
    if(!row) return;

    // เอฟเฟคต์ fade out ก่อนลบ
row.style.transition = 'opacity 0.25s ease, max-height 0.3s ease, padding 0.3s ease';
    row.style.overflow = 'hidden';
    row.style.opacity = '0';
    row.style.maxHeight = row.offsetHeight + 'px';

    setTimeout(() => {
        row.style.maxHeight = '0';
        row.style.paddingTop = '0';
        row.style.paddingBottom = '0';
        row.style.borderBottom = 'none';
    }, 50);

    setTimeout(() => { row.remove(); }, 380);

    // ส่ง AJAX อัปเดต is_read = 1
    let fd = new FormData();
    fd.append('noti_id', notiId);
    fd.append('action', 'dismiss');
    fetch('noti_read_action.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        // ลดตัวเลขบนปุ่มกระดิ่ง
        let badge = document.getElementById("notiBadgeCount");
        if(badge) {
            let currentCount = parseInt(badge.innerText);
            if(currentCount > 1) { badge.innerText = currentCount - 1; }
            else { badge.remove(); }
        }
    });
}
</script>
