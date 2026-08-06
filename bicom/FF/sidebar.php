<style>
    :root {
        --sidebar-bg: #0f172a;
        --sidebar-hover: #1e293b;
        --accent-color: #38bdf8;
    }
    #sidebar {
        width: 280px; height: 100vh; position: fixed; left: -280px; top: 0;
        background: var(--sidebar-bg); color: white; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 1060; box-shadow: 15px 0 30px rgba(0,0,0,0.2);
    }
    #sidebar.active { left: 0; }
    #sidebar .sidebar-header { padding: 30px 25px; border-bottom: 1px solid rgba(255,255,255,0.05); }
    #sidebar .nav-link {
        color: #94a3b8; padding: 12px 25px; border-radius: 12px; margin: 8px 15px;
        display: flex; align-items: center; transition: 0.2s; font-weight: 400;
    }
    #sidebar .nav-link i { font-size: 1.3rem; margin-right: 15px; width: 25px; text-align: center; }
    #sidebar .nav-link:hover { background: var(--sidebar-hover); color: var(--accent-color); transform: translateX(5px); }
    #sidebar .nav-link.active { background: var(--accent-color); color: var(--sidebar-bg); font-weight: 600; box-shadow: 0 4px 15px rgba(56, 189, 248, 0.3); }
    
    .sidebar-overlay {
        display: none; position: fixed; width: 100vw; height: 100vh;
        background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1050; top: 0; left: 0;
    }
    .sidebar-overlay.active { display: block; }
    
    .toggle-btn {
        position: fixed; top: 20px; left: 20px; z-index: 1000;
        background: white; border: none; width: 45px; height: 45px;
        border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        display: flex; align-items: center; justify-content: center; transition: 0.3s;
    }
    .toggle-btn:hover { background: #f8fafc; transform: scale(1.05); }
</style>

<button class="toggle-btn" id="menuToggle">
    <i class="bi bi-list fs-4"></i>
</button>

<div class="sidebar-overlay" id="menuOverlay"></div>

<nav id="sidebar">
    <div class="sidebar-header d-flex align-items-center justify-content-between">
        <div>
            <h4 class="mb-0 fw-bold text-white">FLEET <span style="color: var(--accent-color);">PRO</span></h4>
            <small class="text-muted">Management System</small>
        </div>
        <button class="btn btn-link text-white p-0" id="closeSidebar"><i class="bi bi-x-lg"></i></button>
    </div>

    <div class="mt-4">
        <label class="px-4 mb-2 small text-uppercase fw-bold text-muted" style="font-size: 0.7rem; letter-spacing: 1px;">Menu Overview</label>
        <ul class="nav flex-column">
            <li><a href="executive_report.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'executive_report.php' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> แผงควบคุม (Dashboard)</a>
            </li>
            <li><a href="daily_entry.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'daily_entry.php' ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-plus"></i> บันทึกรถเข้างาน</a>
            </li>
            <li><a href="executive_slide.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'executive_slide.php' ? 'active' : '' ?>">
                <i class="bi bi-display"></i> โหมดสไลด์โชว์</a>
            </li>
            <li><a href="station_setup.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'station_setup.php' ? 'active' : '' ?>">
                <i class="bi bi-geo-alt"></i> จัดการสถานี</a>
            </li>
        </ul>

        <label class="px-4 mt-5 mb-2 small text-uppercase fw-bold text-muted" style="font-size: 0.7rem; letter-spacing: 1px;">Account</label>
        <ul class="nav flex-column">
            <li><a href="logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i> ออกจากระบบ</a></li>
        </ul>
    </div>
</nav>

<script>
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