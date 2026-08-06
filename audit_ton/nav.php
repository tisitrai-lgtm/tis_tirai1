<?php
// nav.php - Navigation bar with modern notifications
$selected_year = $_GET['year'] ?? null;
$current_page = basename($_SERVER['SCRIPT_NAME']);
?>
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
        --glass-bg: rgba(255, 255, 255, 0.1);
        --glass-border: rgba(255, 255, 255, 0.2);
        --accent-color: #00d2ff;
    }

    body {
        font-family: 'Kanit', 'Outfit', sans-serif;
        background-attachment: fixed;
    }

    .navbar-custom {
        background: #ffffff !important;
        border-bottom: 1px solid #eaeaea;
        padding: 0.8rem 1rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    .navbar-brand {
        font-family: 'Outfit', sans-serif;
        font-weight: 700;
        font-size: 1.6rem;
        color: #2b2b2b !important;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .navbar-brand img {
        width: 35px; height: 35px;
        transition: transform 0.3s;
    }

    .navbar-brand:hover img { transform: rotate(10deg) scale(1.1); }

    .nav-link {
        color: #6c757d !important;
        padding: 0.6rem 1.2rem !important;
        transition: all 0.3s ease;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
        margin: 0 4px;
    }

    .nav-link:hover {
        color: #0d6efd !important;
        background: rgba(13, 110, 253, 0.05);
        transform: translateY(-1px);
    }

    .nav-link.active-page {
        color: #0d6efd !important;
        background: rgba(13, 110, 253, 0.1);
        font-weight: 600;
    }

    .year-badge {
        background: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
        padding: 0.4rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        border: 1px solid rgba(13, 110, 253, 0.2);
        display: flex;
        align-items: center;
        gap: 8px;
        margin-right: 15px;
    }

    @media (max-width: 991.98px) {
        .navbar-collapse {
            background: #ffffff;
            margin-top: 10px;
            margin-bottom: -0.8rem;
            padding: 1.5rem;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            border-top: 1px solid #eaeaea;
        }
        
        .collapsing {
            transition: height 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            will-change: height;
        }

        .nav-link { 
            margin: 8px 0; 
            background: #f8f9fa; 
            padding: 0.8rem 1rem !important;
        }
        .year-badge { margin: 0 0 1rem 0; justify-content: center; }
    }
</style>

<nav class="navbar navbar-expand-lg navbar-light navbar-custom">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php?year=<?php echo htmlspecialchars($selected_year); ?>">
            <img src="icon/2.png" alt="Logo">
            <span style="color: #0d6efd;">Audit</span> Ton
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" style="border:none;">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-end" id="navbarContent">
            <ul class="navbar-nav align-items-center">
                <?php if ($selected_year): ?>
                    <li class="nav-item">
                        <div class="year-badge">
                            <i class='bx bxs-calendar text-warning'></i>
                            ปีการผลิต: <?php echo htmlspecialchars($selected_year); ?>
                        </div>
                    </li>
                <?php endif; ?>
                
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page === 'dashboard.php') ? 'active-page' : ''; ?>" 
                        href="dashboard.php?year=<?php echo urlencode($selected_year); ?>">
                        <i class="bx bxs-dashboard"></i> แดชบอร์ด
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#" id="changeYearBtn">
                        <i class="bx bx-refresh"></i> เปลี่ยนปี
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 📢 ระบบยันยันการเปลี่ยนปี
    const changeYearBtn = document.getElementById('changeYearBtn');
    if (changeYearBtn) {
        changeYearBtn.addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'ต้องการเปลี่ยนปีการผลิต?',
                text: "ข้อมูลที่คุณกำลังดูอยู่จะถูกปิดลง",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ใช่, เปลี่ยนปี',
                cancelButtonText: 'ยกเลิก',
                background: 'rgba(255, 255, 255, 0.95)',
                backdrop: `rgba(0, 0, 0, 0.4) blur(4px)`
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'index.php?msg=logout';
                }
            });
        });
    }

    // ฟังก์ชันปิด Navbar บนมือถืออัตโนมัติ
    const navLinks = document.querySelectorAll('#navbarContent .nav-link');
    const navbarCollapse = document.getElementById('navbarContent');
    if (navbarCollapse) {
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 992 && navbarCollapse.classList.contains('show')) {
                    bootstrap.Collapse.getInstance(navbarCollapse).hide();
                }
            });
        });
    }
});
</script>