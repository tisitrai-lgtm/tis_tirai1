<?php
// ###########################################################
// ไฟล์: nav.php - Modern & Luxury Minimal Version
// ###########################################################

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$display_unit_name = $_SESSION['selected_unit_name'] ?? 'ไม่ได้เลือกหน่วยงาน';
$display_production_year = $_SESSION['selected_production_year_label'] ?? 'ไม่ได้เลือกปี';
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap');

    .custom-navbar {
        background: linear-gradient(135deg, #1a237e 0%, #0d47a1 100%); /* โทนน้ำเงินหรู */
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        padding: 0.8rem 1rem;
        font-family: 'Sarabun', sans-serif;
    }

    .navbar-brand {
        font-weight: 600;
        letter-spacing: 1px;
        color: #fff !important;
        border-left: 4px solid #ffd700; /* ขีดสีทองด้านข้างเพิ่มความหรู */
        padding-left: 15px;
    }

    .nav-info-box {
        background: rgba(255, 255, 255, 0.1); /* Effect กระจก */
        border-radius: 50px;
        padding: 5px 20px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #f8f9fa;
        font-size: 0.95rem;
    }

    .nav-info-box strong {
        color: #ffd700; /* สีทองสำหรับเน้นข้อมูลสำคัญ */
        font-weight: 400;
    }

    .nav-link.active {
        font-weight: 500;
        position: relative;
    }

    .nav-link.active::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 8px;
        right: 8px;
        height: 2px;
        background: #ffd700;
        border-radius: 2px;
    }

    .btn-logout {
        background: rgba(220, 53, 69, 0.15);
        color: #ff8a80 !important;
        border: 1px solid rgba(220, 53, 69, 0.4);
        border-radius: 8px;
        transition: all 0.3s ease;
        font-weight: 400;
    }

    .btn-logout:hover {
        background: #dc3545;
        color: white !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
    }

    /* ปรับแต่ง Navbar Toggler สำหรับมือถือ */
    .navbar-toggler {
        border: none;
        outline: none;
    }
</style>

<nav class="navbar navbar-expand-lg navbar-dark custom-navbar sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="land_info_display.php">
            <i class="bi bi-geo-alt-fill me-2"></i> ระบบรังวัดพื้นที่
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                <li class="nav-item">
                    <a class="nav-link active" href="land_info_display.php">หน้าหลัก</a>
                </li>
            </ul>

            <div class="d-flex align-items-center flex-column flex-lg-row">
                <div class="nav-info-box me-lg-3 mb-2 mb-lg-0">
                    <i class="bi bi-building me-1"></i> 
                    หน่วยงาน: <strong><?php echo htmlspecialchars($display_unit_name); ?></strong> 
                    <span class="mx-2 text-white-50">|</span>
                    <i class="bi bi-calendar3 me-1"></i> 
                    ปีการผลิต: <strong><?php echo htmlspecialchars($display_production_year); ?></strong>
                </div>
                
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link btn-logout px-3" href="index.php?action=logout">
                            <i class="bi bi-box-arrow-right me-1"></i> ออกจากระบบ
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>