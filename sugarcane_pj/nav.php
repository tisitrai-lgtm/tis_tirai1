<?php
// nav.php - Modernized Navigation with Glassmorphism
$selected_year = $selected_year ?? $_GET['year'] ?? null;
?>
<!-- Include SweetAlert2 and Boxicons -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<link rel="stylesheet" href="index.css">

<style>
    .navbar-custom {
        background: #1e3a8a !important; /* Blue background */
        padding: 1rem 1.5rem;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        border-bottom: none;
    }
    
    .navbar-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 700;
        font-size: 1.4rem;
        color: #ffffff !important;
    }

    .nav-icon {
        width: 50px;
        height: 50px;
    }

    .nav-link {
        font-weight: 600 !important;
        font-size: 1.15rem;
        margin: 0 10px;
        color: rgba(255, 255, 255, 0.9) !important;
        transition: color 0.2s;
    }

    .nav-link:hover {
        color: #ffffff !important;
        text-decoration: none;
        opacity: 0.8;
    }

    /* Dropdown Styling */
    .dropdown-menu-premium {
        background: #ffffff;
        border: none;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        padding: 10px;
        min-width: 220px;
        margin-top: 15px !important;
    }

    .dropdown-item-premium {
        padding: 12px 15px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 500;
        color: #1e293b;
        transition: all 0.2s;
    }

    .dropdown-item-premium:hover {
        background: #f1f5f9;
        color: #1e3a8a;
    }

    .dropdown-item-premium i {
        font-size: 1.25rem;
    }

    .year-container {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .year-label {
        color: rgba(255, 255, 255, 0.6);
        font-weight: 500;
        font-size: 0.95rem;
    }

    .year-badge {
        background: #ffffff;
        color: #1e3a8a;
        padding: 6px 16px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 1rem;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        border: none;
    }

    @media (max-width: 991px) {
        .navbar-brand span { font-size: 1.1rem; }
        .nav-icon { width: 35px; height: 35px; }
        .navbar-nav { padding: 1rem 0; }
        .nav-item { width: 100%; text-align: center; margin: 8px 0; }
        
        .year-container {
            flex-direction: column;
            background: rgba(255, 255, 255, 0.08);
            padding: 15px;
            border-radius: 20px;
            margin: 10px 0 !important;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .year-label {
            margin-bottom: 6px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.5);
        }

        .year-badge { 
            margin: 0;
            width: auto;
            min-width: 100px;
        }
    }
</style>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php<?php echo !empty($selected_year) ? '?year=' . htmlspecialchars($selected_year) : ''; ?>">
            <img src="icon/unnamed.png" alt="Sugarcane Icon" class="nav-icon">
            <span>ตรวจสอบแปลงอ้อย</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"
            aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-end" id="navbarContent">
            <ul class="navbar-nav align-items-center">
                <?php if ($selected_year): ?>
                    <li class="nav-item year-container me-lg-3">
                        <span class="year-label">ปีการผลิต</span>
                        <span class="year-badge"><?php echo htmlspecialchars($selected_year); ?></span>
                    </li>
                <?php endif; ?>

                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php<?php echo !empty($selected_year) ? '?year=' . htmlspecialchars($selected_year) : ''; ?>">
                        <i class='bx bxs-dashboard'></i> แดชบอร์ด
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="report.php<?php echo !empty($selected_year) ? '?year=' . htmlspecialchars($selected_year) : ''; ?>">
                        <i class='bx bxs-bar-chart-alt-2'></i> รายงานภาพรวม
                    </a>
                </li>

                <!-- New Report Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="reportDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class='bx bxs-file-doc'></i> Report
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-premium" aria-labelledby="reportDropdown">
                        <li>
                            <a class="dropdown-item dropdown-item-premium" href="#" data-bs-toggle="modal" data-bs-target="#exportGlobalModal">
                                <i class='bx bxs-file-export' style="color: #10b981;"></i> ส่งออกเป็น Excel
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item dropdown-item-premium" href="import_csv.php">
                                <i class='bx bxs-file-import' style="color: #3b82f6;"></i> นำเข้าข้อมูล (.CSV)
                            </a>
                        </li>
                    </ul>
                </li>
                
                <li class="nav-item ms-lg-2">
                    <a class="btn btn-glass btn-sm px-3" href="index.php">
                        <i class='bx bx-refresh'></i> เปลี่ยนปี
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Global Export Modal -->
<div class="modal fade" id="exportGlobalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 25px 50px rgba(0,0,0,0.3);">
            <div class="modal-header" style="background: #1e3a8a; color: white;">
                <h5 class="modal-title fw-bold">ส่งออกข้อมูลรายปี</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-4">
                    <label class="form-label fw-bold">เลือกปีการผลิตที่ต้องการส่งออก:</label>
                    <select class="form-select" id="export_global_year_select" style="border-radius: 12px; padding: 12px; border: 2px solid #e2e8f0;"></select>
                </div>
                <button type="button" class="btn btn-premium w-100 py-3" id="confirmGlobalExportBtn">
                    <i class='bx bx-cloud-download'></i> เริ่มดาวน์โหลด
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Navbar auto-collapse for mobile
    const navbarCollapse = document.getElementById('navbarContent');
    if (navbarCollapse) {
        const navLinks = navbarCollapse.querySelectorAll('.nav-link:not(.dropdown-toggle)');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 992 && navbarCollapse.classList.contains('show')) {
                    bootstrap.Collapse.getInstance(navbarCollapse).hide();
                }
            });
        });
    }

    // Export Modal Logic
    const exportModal = document.getElementById('exportGlobalModal');
    if (exportModal) {
        exportModal.addEventListener('show.bs.modal', function () {
            const select = document.getElementById('export_global_year_select');
            select.innerHTML = '<option value="">กำลังโหลด...</option>';
            
            fetch('fetch_years.php')
                .then(response => response.json())
                .then(years => {
                    select.innerHTML = '';
                    if (years.length) {
                        years.forEach(y => {
                            const option = document.createElement('option');
                            option.value = y;
                            option.textContent = 'ปีการผลิต ' + y;
                            select.appendChild(option);
                        });
                    } else {
                        const option = document.createElement('option');
                        option.value = '';
                        option.textContent = 'ไม่พบข้อมูลปี';
                        select.appendChild(option);
                    }
                })
                .catch(err => {
                    console.error('Error fetching years:', err);
                    select.innerHTML = '<option value="">เกิดข้อผิดพลาด</option>';
                });
        });
    }

    const confirmExportBtn = document.getElementById('confirmGlobalExportBtn');
    if (confirmExportBtn) {
        confirmExportBtn.addEventListener('click', function() {
            const year = document.getElementById('export_global_year_select').value;
            if (year) {
                window.location.href = 'export_excel.php?year=' + encodeURIComponent(year);
                bootstrap.Modal.getInstance(exportModal).hide();
            } else {
                Swal.fire({
                    icon: 'info',
                    title: 'กรุณาเลือกปี',
                    text: 'กรุณาเลือกปีการผลิตที่ต้องการส่งออก'
                });
            }
        });
    }
});
</script>