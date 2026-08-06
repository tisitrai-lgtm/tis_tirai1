<?php
require('dbconnect.php');

if (!isset($_SESSION['selected_year'])) {
    $sql_default_year = "SELECT year_rai FROM image_water ORDER BY year_rai DESC LIMIT 1";
    $res_default = mysqli_query($con, $sql_default_year);
    if ($row_default = mysqli_fetch_assoc($res_default)) {
        $_SESSION['selected_year'] = $row_default['year_rai'];
    } else {
        $_SESSION['selected_year'] = "68-69"; // fallback
    }
}
?>
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
  /* 🎨 ปรับพื้นหลัง Navbar ให้เป็นไล่เฉดสีแบบพรีเมียม (โทน Admin) */
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

  /* 📍 ขีดเส้นใต้สำหรับเมนูที่ Active */
  .nav-link.active {
    background: rgba(255, 255, 255, 0.2);
    color: #fff !important;
    box-shadow: inset 0 0 10px rgba(0,0,0,0.1);
  }

  /* 🚪 ปุ่มออกจากระบบ (Logout) */
  .btn-logout {
    background-color: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.5);
    color: white;
    font-weight: bold;
    border-radius: 10px;
    padding: 0.5rem 1.2rem;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .btn-logout:hover {
    background-color: #ef4444; /* สีแดงสดเมื่อ Hover */
    border-color: #ef4444;
    color: white;
    transform: scale(1.05);
    box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3);
  }

  /* 📱 ปรับแต่งสำหรับมือถือ */
  @media (max-width: 991.98px) {
    .navbar-collapse {
      /* ปิดการใช้งาน padding/margin ที่ collapse โดยตรงเพื่อแก้แอนิเมชันกระตุก */
      margin-top: 15px; 
    }
    .mobile-menu-inner-admin {
      background: rgba(0, 0, 0, 0.05); /* กล่องดำโปร่งๆ แบบ Admin */
      backdrop-filter: blur(10px);
      border-radius: 15px;
      padding: 15px;
      border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .btn-logout {
      margin-top: 10px;
      justify-content: center;
    }
    .nav-link {
      margin-bottom: 5px;
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
    <a class="navbar-brand text-white d-flex align-items-center" href="admin_page.php">
      <i class='bx bxs-shield-quarter' style="font-size: 1.8rem; margin-right: 8px;"></i>
      <span>ADMIN <small style="font-size: 0.7rem; font-weight: normal; opacity: 0.8;">System</small></span>
    </a>

    <!-- ปีการผลิต (แสดงบนมือถือข้างปุ่ม Hamburger ถ้าจอเล็กมากอาจจะยุบเข้าเมนู) -->
    <div class="d-lg-none ms-auto me-2">
        <span class="badge bg-white text-primary px-2 py-1" style="font-size: 0.8rem;">
            ปี <?php echo $_SESSION['selected_year']; ?>
        </span>
    </div>

    <button class="navbar-toggler text-white border-0" type="button" data-bs-toggle="collapse"
      data-bs-target="#navbarNavAdmin" 
      aria-controls="navbarNavAdmin" aria-expanded="false" aria-label="Toggle navigation">
      <i class='bx bx-menu-alt-right' style="font-size: 2rem;"></i>
    </button>

    <div class="collapse navbar-collapse" id="navbarNavAdmin">
      <div class="mobile-menu-inner-admin w-100 d-lg-flex align-items-lg-center"> <!-- Wrapper ป้องกันเมนูกระตุก -->
          <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <li class="nav-item">
              <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_page.php') ? 'active' : ''; ?>" href="admin_page.php">
                <i class='bx bx-home-alt-2'></i> หน้าแรก
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'emp_page.php') ? 'active' : ''; ?>" href="emp_page.php">
                <i class='bx bx-group'></i> ข้อมูลพนักงาน
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_report_image_counts.php') ? 'active' : ''; ?>" href="admin_report_image_counts.php">
                <i class='bx bx-bar-chart-square'></i> รายงานภาพรวม
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_maintenance_settings.php') ? 'active' : ''; ?>" href="admin_maintenance_settings.php">
                <i class='bx bx-wrench'></i> ตั้งค่าปรับปรุงระบบ
              </a>
            </li>
          </ul>

          <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center gap-2">
            <!-- ตัวเลือกปีการผลิตสำหรับ Admin -->
            <div class="dropdown">
                <button class="nav-link dropdown-toggle border-0 w-100" style="background: rgba(255,255,255,0.1); border-radius: 10px;" type="button" data-bs-toggle="dropdown">
                    <i class='bx bx-calendar-event'></i> ปีการผลิต <?php echo $_SESSION['selected_year']; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                    <li class="dropdown-header text-uppercase pb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">เลือกปีการผลิต</li>
                    <?php
                    $available_years = ["69-70", "68-69"]; 
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

            <a href="javascript:void(0);" class="btn btn-logout" onclick="confirmLogoutAdmin(event)">
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
    const navbarCollapse = document.getElementById('navbarNavAdmin');
    
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

  // Script สำหรับยุบเมนูอัตโนมัติบนมือถือเมื่อกดลิงก์ (เหมือน User)
  document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('#navbarNavAdmin .nav-link:not(.dropdown-toggle)');
    const navbarCollapse = document.getElementById('navbarNavAdmin');

    if (navbarCollapse) {
      navLinks.forEach(function(link) {
        link.addEventListener('click', function() {
          if (navbarCollapse.classList.contains('show')) {
            const bsCollapse = new bootstrap.Collapse(navbarCollapse, { toggle: false });
            bsCollapse.hide();
          }
        });
      });
    }
  });

  // SweetAlert2 Confirmation for Admin Logout
  function confirmLogoutAdmin(e) {
      if (e) {
          e.preventDefault(); 
          e.stopPropagation();
      }
      Swal.fire({
          title: 'ออกจากระบบผู้ดูแล?',
          text: "ระบบจะนำคุณกลับไปยังหน้าเข้าสู่ระบบ",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#ef4444',
          cancelButtonColor: '#4b5563', // สีเทาเข้มแบบลุค Admin
          confirmButtonText: 'ยืนยันการออก',
          cancelButtonText: 'ยกเลิก',
          reverseButtons: true,
          background: '#1e293b', // พื้นหลังสีมืดเข้ากับ Admin
          color: '#ffffff', // ตัวอักษรสีขาว
          customClass: {
              title: 'fs-5 fw-bold',
              confirmButton: 'fw-bold px-4 rounded',
              cancelButton: 'fw-bold px-4 rounded'
          }
      }).then((result) => {
          if (result.isConfirmed) {
              window.location.href = 'logout.php';
          }
      })
  }
</script>