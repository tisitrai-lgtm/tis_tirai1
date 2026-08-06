<?php
require('dbconnect.php');

if (!isset($_SESSION['selected_year'])) {
    $sql_default_year = "SELECT year_rai FROM image_water ORDER BY year_rai DESC LIMIT 1";
    $res_default = mysqli_query($con, $sql_default_year);
    if ($row_default = mysqli_fetch_assoc($res_default)) {
        $_SESSION['selected_year'] = $row_default['year_rai'];
    }
}
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* 🎨 ปรับพื้นหลัง Navbar ให้เป็นไล่เฉดสีแบบพรีเมียม (โทนพรีเมียมบูลเหมือนแอดมิน) */
    .navbar-custom {
        background: linear-gradient(135deg, #1e3a8a, #3b82f6); /* น้ำเงินเข้ม - ฟ้า */
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        padding: 0.8rem 1rem;
        border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        transition: background 0.3s ease, box-shadow 0.3s ease, padding 0.3s ease;
    }

    /* ✨ ตกแต่งชื่อระบบ */
    .navbar-brand {
        font-weight: 800;
        font-size: 1.5rem;
        letter-spacing: 0.5px;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
        color: white !important;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* 🔗 ตกแต่งเมนูลิงก์ */
    .nav-link {
        color: rgba(255, 255, 255, 0.9) !important;
        font-weight: 500;
        margin: 0 5px;
        padding: 0.6rem 1.2rem !important;
        border-radius: 10px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* 🔥 เอฟเฟกต์เมื่อ Hover เมนู */
    .nav-link:hover {
        color: #ffffff !important;
        background: rgba(255, 255, 255, 0.15);
        transform: translateY(-2px);
    }

    /* 📍 สไตล์สำหรับเมนูที่ Active */
    .nav-link.active {
        background: rgba(255, 255, 255, 0.2);
        color: #fff !important;
        box-shadow: inset 0 0 10px rgba(0,0,0,0.1);
    }

    /* 🚪 ปุ่มออกจากระบบ (Logout) แบบ Admin */
    .btn-logout-custom {
        background-color: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.5);
        color: white !important;
        font-weight: bold;
        border-radius: 10px;
        padding: 0.5rem 1.2rem;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .btn-logout-custom:hover {
        background-color: #ef4444; /* สีแดงสดเมื่อ Hover */
        border-color: #ef4444;
        color: white !important;
        transform: scale(1.05);
        box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3);
    }

    /* ปุ่มสมัครรับเงิน (ยังคงความเด่นไว้แต่ปรับให้เข้ากับธีม) */
    .btn-special-user {
        background: linear-gradient(135deg, #10b981, #059669) !important;
        color: white !important;
        border: none !important;
    }
    
    .btn-special-user:hover {
        background: linear-gradient(135deg, #059669, #047857) !important;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    }

    /* Dropdown Styling */
    .dropdown-menu {
        border: none;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        border-radius: 12px;
        padding: 0.5rem;
        background: #ffffff;
    }
    
    .dropdown-item {
        padding: 0.7rem 1rem;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 500;
        color: #334155;
        transition: all 0.2s;
    }
    
    .dropdown-item:hover {
        background: #f1f5f9;
        color: #1e3a8a;
        padding-left: 1.2rem;
    }

    /* User Zone Pill */
    .year-pill-custom {
        background: rgba(255, 255, 255, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white !important;
        border-radius: 10px;
    }

    /* 📱 ปรับแต่งสำหรับมือถือ */
    @media (max-width: 991.98px) {
        .navbar-collapse {
            margin-top: 15px; 
        }
        .mobile-menu-inner-user {
            background: rgba(0, 0, 0, 0.1); /* พื้นหลังเข้มโปร่งแสงแบบแอดมิน */
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .nav-link {
            margin-bottom: 5px;
            justify-content: flex-start;
        }
        .btn-logout-custom, .year-pill-custom {
            margin-top: 10px;
            justify-content: center;
        }
    }

    /* 🛡️ ป้องกันเนื้อหาโดน Navbar ทับ (เนื่องจากใช้ fixed-top) */
    body {
        padding-top: 75px; 
    }
    @media (max-width: 991.98px) {
        body { padding-top: 65px; }
    }
</style>

<nav class="navbar navbar-expand-lg navbar-custom fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand text-white" href="user_page.php">
            <i class='bx bxs-droplet' style="font-size: 1.8rem;"></i>
            <span>TIS : <small style="font-weight: normal; opacity: 0.8;">WaterSuga</small></span>
        </a>

        <button class="navbar-toggler text-white border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <i class='bx bx-menu-alt-right' style="font-size: 2rem;"></i>
        </button>

        <div class="collapse navbar-collapse" id="navMain">
            <div class="mobile-menu-inner-user w-100 d-lg-flex align-items-lg-center">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'user_page.php') ? 'active' : ''; ?>" href="user_page.php">
                            <i class='bx bxs-home-circle'></i> หน้าแรก
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link btn-special-user mx-lg-2 my-1 my-lg-0" href="user_register_water_money.php">
                            <i class='bx bxs-edit-location'></i> สมัครรับเงินให้น้ำ
                        </a>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="javascript:void(0);" data-bs-toggle="dropdown">
                            <i class='bx bxs-component'></i> จัดการข้อมูลแปลง
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="user_watergive.php">
                                    <i class='bx bx-water' style="color: #3b82f6;"></i> แปลงให้น้ำอ้อย
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="user_flood_image.php">
                                    <i class='bx bx-cloud-rain' style="color: #0ea5e9;"></i> รายงานน้ำท่วม
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="user_drought_image.php">
                                    <i class='bx bx-sun' style="color: #f59e0b;"></i> รายงานกระทบแล้ง
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="user_report_image_counts.php">
                                    <i class='bx bxs-pie-chart-alt-2' style="color: #10b981;"></i> สรุปภาพรวม (Dashboard)
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>

                <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center gap-2">
                    <div class="dropdown">
                        <button class="nav-link year-pill-custom dropdown-toggle border-0 w-100" type="button" data-bs-toggle="dropdown">
                            <i class='bx bx-calendar-event'></i> ปี <?php echo $_SESSION['selected_year']; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                            <li class="dropdown-header text-uppercase pb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">เลือกปีการผลิต</li>
                            <?php
                            $available_years = [ "69-70", "68-69"]; 
                            foreach ($available_years as $y) {
                                $isSelected = ($y == $_SESSION['selected_year']) ? "background: #f1f5f9; color: #1e3a8a; font-weight: bold;" : "";
                                echo "<li>
                                        <a class='dropdown-item' style='$isSelected' href='javascript:void(0);' onclick=\"event.preventDefault(); event.stopPropagation(); changeYear('$y');\">
                                            <i class='bx bx-check-circle' style='opacity: " . ($y == $_SESSION['selected_year'] ? "1" : "0") . "'></i> 
                                            ปีการผลิต $y
                                        </a>
                                      </li>";
                            }
                            ?>
                        </ul> 
                    </div>

                    <a href="javascript:void(0);" class="btn-logout-custom" onclick="confirmLogoutUser(event);">
                        <i class='bx bx-log-out-circle'></i> ออกจากระบบ
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>

<script>
    // AJAX เปลี่ยนปี (รองรับทั้ง Admin และ User - ใช้ Vanilla JS เพื่อความชัวร์)
    function changeYear(year) {
        fetch('update_session_year.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'year=' + encodeURIComponent(year)
        })
        .then(() => {
            location.reload();
        })
        .catch(err => console.error('Error changing year:', err));
    }

    // Scroll Effect (จัดการให้สมูทขึ้นและไม่กวน Mobile Menu)
    window.addEventListener('scroll', function() {
        const nav = document.querySelector('.navbar-custom');
        const navbarCollapse = document.getElementById('navMain');
        
        // ถ้าเมนูมือถือกางอยู่ ไม่ต้องเปลี่ยน Padding/Shadow ให้กวนสายตา
        if (navbarCollapse && navbarCollapse.classList.contains('show')) return;

        if (window.scrollY > 30) {
            nav.style.boxShadow = '0 8px 25px rgba(0,0,0,0.25)';
            nav.style.padding = '0.6rem 1rem';
        } else {
            nav.style.boxShadow = '0 4px 15px rgba(0, 0, 0, 0.2)';
            nav.style.padding = '0.8rem 1rem';
        }
    });

    // ปิดเมนูอัตโนมัติเมื่อกดลิงก์บนมือถือ
    document.addEventListener('DOMContentLoaded', function() {
        const navLinks = document.querySelectorAll('#navMain .nav-link:not(.dropdown-toggle)');
        const navbarCollapse = document.getElementById('navMain');

        if (navbarCollapse) {
            navLinks.forEach(function(link) {
                link.addEventListener('click', function() {
                    if (window.innerWidth < 992 && navbarCollapse.classList.contains('show')) {
                        const bsCollapse = new bootstrap.Collapse(navbarCollapse, { toggle: false });
                        bsCollapse.hide();
                    }
                });
            });
        }
    });

    // SweetAlert2 Confirmation for Logout
    function confirmLogoutUser(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        Swal.fire({
            title: 'ต้องการออกจากระบบใช่หรือไม่?',
            text: "หากกดตกลง ระบบจะทำการออกจากหน้าผู้ใช้งานทันที",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'ออกจากระบบ',
            cancelButtonText: 'ยกเลิก',
            reverseButtons: true,
            background: '#ffffff',
            customClass: {
                title: 'fs-5 fw-bold text-dark',
                confirmButton: 'fw-bold px-4 rounded-pill',
                cancelButton: 'fw-bold px-4 rounded-pill'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'logout.php';
            }
        });
    }
</script>