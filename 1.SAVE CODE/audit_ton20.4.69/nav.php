<?php
// ตรวจสอบว่า selected_year ถูกส่งมาหรือไม่ (จากหน้าหลักที่เรียก nav.php)
// ถ้าไม่ถูกส่งมา หรือต้องการให้มีค่าเริ่มต้นอื่น ให้ปรับตามต้องการ
$selected_year = $_GET['year'] ?? null;
$current_page = basename($_SERVER['SCRIPT_NAME']); // ดึงชื่อไฟล์ปัจจุบัน (เช่น user_dashboard.php)
?>
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<style>
    /* ----------------------------------------------------------- */
    /* GLOBAL / FONT */
    /* ----------------------------------------------------------- */
    body {
        font-family: 'Kanit', sans-serif;
    }
    
    
    /* ----------------------------------------------------------- */
    /* NAVBAR DESIGN */
    /* ----------------------------------------------------------- */
    .navbar-custom {
        /* เปลี่ยนสีพื้นหลังให้ดูพรีเมียมขึ้น */
        background: linear-gradient(100deg, #055fb4ff 0%, #6d9cceff 100%); 
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.25);
    }

    .navbar-brand {
        font-weight: 700; /* หนาขึ้น */
        font-size: 1.5rem; /* ใหญ่ขึ้นเล็กน้อย */
        color: white; 
        transition: color 0.3s;
    }
    .navbar-brand:hover {
        color: #ffcc00; /* สีทองอ่อนๆ */
    }

    /* สไตล์สำหรับลิงก์ทั่วไป */
    .nav-link {
        color: white !important;
        padding: 0.5rem 1rem;
        transition: all 0.3s ease;
        border-radius: 8px; /* เพิ่มความโค้งมน */
        display: flex; /* จัดไอคอนและข้อความ */
        align-items: center;
        gap: 8px; /* ช่องว่างระหว่างไอคอนกับข้อความ */
    }

    .nav-link:hover {
        color: #ffcc00 !important;
        background-color: rgba(255, 255, 255, 0.1);
        transform: none; /* ยกเลิกการยกตัว (ให้ดูนิ่งและเป็นระเบียบ) */
    }

    /* 🌟 สไตล์สำหรับลิงก์ที่ผู้ใช้อยู่ (Active Link) 🌟 */
    .nav-link.active-page {
        color: #ffcc00 !important; /* สีเหลืองทอง */
        font-weight: 600;
        background-color: rgba(255, 255, 255, 0.2); /* พื้นหลังเข้มขึ้น */
        border-bottom: 3px solid #ffcc00; /* ขีดเส้นใต้สีทอง */
        padding-bottom: 0.5rem;
    }
    
    /* สไตล์สำหรับป้ายปีการผลิต/หน่วยงาน */
    .nav-info-badge {
        color: white;
        background-color: rgba(255, 255, 255, 0.1);
        padding: 0.4rem 1rem;
        margin-right: 15px; /* เว้นระยะห่างจากเมนูอื่น */
        border-radius: 5px;
        font-size: 0.9rem;
    }
    
    /* ----------------------------------------------------------- */
    /* LOGOUT BUTTON (สำหรับอนาคต) */
    /* ----------------------------------------------------------- */
    .btn-logout {
        background-color: #dc3545; /* สีแดง */
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        margin-top: 0.5rem;
        transition: all 0.3s ease;
    }
    .btn-logout:hover {
        background-color: #c82333;
        color: white;
    }
    
    /* ----------------------------------------------------------- */
    /* ICON */
    /* ----------------------------------------------------------- */
    .card-icon {
        width: 40px; /* เล็กลงเล็กน้อย */
        height: 40px;
        margin-right: 10px;
        object-fit: contain;
    } 

    /* ----------------------------------------------------------- */
    /* 📱 MOBILE RESPONSIVE ADJUSTMENTS */
    /* ----------------------------------------------------------- */
    @media (max-width: 991.98px) {
        .navbar-nav {
            padding-top: 10px;
            padding-bottom: 10px;
        }
        
        /* บนมือถือ เมนูจะเรียงลงมาและเว้นช่องไฟ */
        .nav-item {
            margin-bottom: 5px; 
        }

        .nav-link {
            padding: 0.7rem 1rem;
            border-bottom: none !important;
        }

        /* ปรับตำแหน่งของข้อมูลปีบนมือถือ */
        .nav-info-badge {
            display: block; /* แสดงเป็น block */
            margin-bottom: 10px;
            margin-right: 0;
            text-align: center;
            font-size: 1rem;
            font-weight: 500;
        }
        
        /* ทำให้ปุ่ม Logout ดูดีบนมือถือ */
        .btn-logout {
            width: 100%;
            margin-top: 10px;
        }
    }
</style>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
    <div class="container-fluid justify-content-between">
        <div class="navbar-header-group">
            <img src="icon/2.png" alt="Sugarcane Icon" class="card-icon">
            <a class="navbar-brand" href="dashboard.php?year=<?php echo htmlspecialchars($selected_year); ?>">ประมาณตัน Audit</a>
        </div>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"
            aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-end" id="navbarContent">
            <ul class="navbar-nav">
                <?php if ($selected_year): ?>
                    <li class="nav-item">
                        <span class="nav-link active" style="color: white !important;">ปีการผลิต: <?php echo htmlspecialchars($selected_year); ?></span>
                    </li>
                <?php endif; ?>
                <?php 
                $dashboard_url = "dashboard.php?year=" . htmlspecialchars($selected_year) ;
                ?> 
                <?php 
                $admin_summary_infographic_url = "admin_summary_infographic.php?year=" . htmlspecialchars($selected_year) ;
                ?>
                
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'dashboard.php' || $current_page === 'user_edit_data.php') ? 'active-page' : ''; ?>" 
                        href="<?php echo $dashboard_url; ?>">
                        <i class="bx bx-bar-chart-alt-2"></i> หน้าหลัก
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'index.php') ? 'active-page' : ''; ?>" 
                        href="index.php">
                        <i class="fas fa-sync-alt"></i> เปลี่ยนปี
                    </a>
                </li>
                </li>
            </ul>
        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ต้องแน่ใจว่าได้รวม bootstrap.bundle.min.js แล้ว
    const navLinks = document.querySelectorAll('#navbarContent .nav-link:not(.nav-info-badge)');
    const navbarCollapse = document.getElementById('navbarContent');

    if (navbarCollapse) {
        navLinks.forEach(function(link) {
            
            // 🚨 เพิ่ม Event Listener สำหรับลิงก์ทั้งหมดที่อยู่ในเมนู
            link.addEventListener('click', function() {
                // ตรวจสอบว่า Navbar กำลังแสดงอยู่ (คือเปิดอยู่) และอยู่ในโหมด Mobile (น้อยกว่า lg)
                if (window.innerWidth < 992 && navbarCollapse.classList.contains('show')) {
                    // สร้าง Bootstrap Collapse instance และสั่ง hide
                    if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
                        const bsCollapse = new bootstrap.Collapse(navbarCollapse, {
                            toggle: false
                        });
                        // ให้รอ 100ms ก่อนปิด เพื่อให้ผู้ใช้เห็นว่าคลิกไปแล้ว
                        setTimeout(() => {
                            bsCollapse.hide(); 
                        }, 100); 
                    }
                }
            });
        });
    }
});
</script>