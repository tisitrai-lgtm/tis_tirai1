<style>
    :root {
        --nav-bg: #1a2a6c;
        --sidebar-bg: #1e293b;
        --accent-blue: #38bdf8;
    }

    /* --- สไตล์ Sidebar ใหม่ --- */
    #sidebar {
        width: 280px; height: 100vh; position: fixed; left: -280px; top: 0;
        background: var(--sidebar-bg); color: white; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 1060; box-shadow: 10px 0 30px rgba(0,0,0,0.3);
        border-top-right-radius: 20px;
        border-bottom-right-radius: 20px;
    }
    #sidebar.active { left: 0; }
    
    #sidebar .sidebar-header { 
        padding: 25px; 
        background: rgba(0,0,0,0.2); 
        border-bottom: 1px solid rgba(255,255,255,0.05);
        border-top-right-radius: 20px;
    }

    #sidebar .nav-link {
        color: #94a3b8; padding: 12px 20px; margin: 8px 15px;
        border-radius: 15px; /* ความโค้งมนของเมนูข้าง */
        display: flex; align-items: center; transition: 0.3s;
    }
    #sidebar .nav-link:hover { background: rgba(56, 189, 248, 0.1); color: var(--accent-blue); transform: translateX(5px); }
    #sidebar .nav-link.active { background: var(--accent-blue); color: #0f172a; font-weight: 700; box-shadow: 0 4px 12px rgba(56, 189, 248, 0.3); }
    #sidebar .nav-link i { margin-right: 15px; width: 20px; }

    /* --- สไตล์ Top Navbar (ทำให้คล้าย nvb.php) --- */
    .top-navbar {
        background: var(--nav-bg) !important;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        padding: 0.8rem 1.5rem;
        z-index: 1040;
    }

    .top-navbar .btn-toggle {
        background: rgba(255,255,255,0.1);
        border-radius: 12px;
        padding: 8px;
        margin-right: 15px;
        transition: 0.3s;
    }
    .top-navbar .btn-toggle:hover { background: rgba(255,255,255,0.2); }

    .sidebar-overlay {
        display: none; position: fixed; width: 100vw; height: 100vh;
        background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); z-index: 1050; top: 0; left: 0;
    }
    .sidebar-overlay.active { display: block; }

    /* ปุ่มออกจากระบบแบบโค้งมน */
    .btn-logout {
        border-radius: 50px !important;
        padding: 8px 18px !important;
        font-weight: 600;
        transition: 0.3s;
    }
    .btn-logout:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3); }
</style>

<nav class="navbar navbar-dark sticky-top top-navbar">
    <div class="container-fluid px-2 px-md-4">
        <div class="d-flex align-items-center">
            <button class="btn btn-link text-white btn-toggle shadow-none" id="menuToggle">
                <i data-lucide="menu"></i>
            </button>
            <a class="navbar-brand fw-bold d-flex align-items-center" href="executive_report.php">
                <i data-lucide="database" class="me-2 text-warning" style="width:24px;"></i> 
                <span style="letter-spacing: 0.5px;">รายงาน<span class="d-none d-sm-inline">ผู้บริหาร</span></span>
            </a>
        </div>

        <div class="d-flex align-items-center">
            
            <a href="login.php" class="btn btn-danger btn-sm btn-logout d-flex align-items-center">
                <i data-lucide="log-out" class="me-1" style="width:16px;"></i> 
                <span class="d-none d-md-inline"></span>
            </a>
        </div>
    </div>
</nav>

<div class="sidebar-overlay" id="menuOverlay"></div>

<nav id="sidebar">
    <div class="sidebar-header d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <i data-lucide="layout-grid" class="text-accent-blue me-2" style="color: var(--accent-blue);"></i>
            <h5 class="mb-0 fw-bold">เมนูหลัก</h5>
        </div>
        <button class="btn btn-link text-white p-0 shadow-none" id="closeSidebar"><i data-lucide="x"></i></button>
    </div>
    
    <div class="py-4">
        <ul class="nav flex-column">
            <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
            <li>
                <a href="executive_report.php" class="nav-link <?= $current_page == 'executive_report.php' ? 'active' : '' ?>">
                    <i data-lucide="truck"></i>&nbsp;รายงานรถบรรทุกเข้า
                </a>
            </li>
            <li>
                <a href="report_daily2.php" class="nav-link <?= $current_page == 'report_daily2.php' ? 'active' : '' ?>">
                    <i data-lucide="bar-chart-3"></i>&nbsp;สรุปข้อมูลอ้อย
                </a>
            </li>
            <li>
                <a href="report_daily.php" class="nav-link <?= $current_page == 'report_daily.php' ? 'active' : '' ?>">
                    <i data-lucide="layout-dashboard"></i>&nbsp;รายงานละเอียด
                </a>
            </li>
            <li>
                <a href="executive_slide.php" class="nav-link <?= $current_page == 'executive_slide.php' ? 'active' : '' ?>">
                    <i data-lucide="monitor"></i>&nbsp;มอนิเตอร์สไลด์
                </a>
            </li>
            <hr class="mx-4 opacity-10">
            <li>
                <a href="login.php" class="nav-link">
                    <i data-lucide="arrow-left-circle"></i>&nbsp;กลับหน้าล็อกอิน
                </a>
            </li>
        </ul>
    </div>
</nav>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
    // เริ่มทำงานไอคอน Lucide
    lucide.createIcons();

    // จัดการการเปิด-ปิด Sidebar
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('menuOverlay');
    const btnOpen = document.getElementById('menuToggle');
    const btnClose = document.getElementById('closeSidebar');

    function toggleSidebar() {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
    }

    btnOpen.addEventListener('click', toggleSidebar);
    btnClose.addEventListener('click', toggleSidebar);
    overlay.addEventListener('click', toggleSidebar);
</script>