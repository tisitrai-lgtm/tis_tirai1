<style>
    /* --- สไตล์พื้นฐานของ Navbar --- */
    .navbar {
        background: #1a2a6c !important;
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        padding: 0.8rem 1.5rem; /* เพิ่มความหนาของแถบ Navbar */
    }

    /* --- ปรับแต่งปุ่มให้โค้งมนและมีระยะห่าง --- */
    .navbar .btn {
        border-radius: 50px !important; /* ทำให้โค้งมนเป็นวงรี (Pill shape) */
        padding: 8px 20px !important;  /* เพิ่มพื้นที่ในปุ่มให้ดูไม่อึดอัด */
        margin-left: 10px;             /* เพิ่มระยะห่างระหว่างปุ่ม */
        transition: all 0.3s ease;     /* เพิ่ม Animation เวลาเอาเมาส์ไปชี้ */
        border-width: 1.5px;           /* เส้นขอบหนาขึ้นนิดหน่อย */
    }

    /* เอฟเฟกต์เวลา Hover (เอาเมาส์ชี้) */
    .navbar .btn:hover {
        transform: translateY(-2px);   /* ปุ่มยกตัวขึ้นเล็กน้อย */
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    /* --- การปรับแต่งสำหรับมือถือ --- */
    @media (max-width: 768px) {
        .navbar {
            padding: 0.6rem 0.5rem;
        }
        .navbar .container-fluid {
            flex-wrap: nowrap;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .navbar .container-fluid::-webkit-scrollbar { display: none; }

        .navbar .btn {
            padding: 6px 15px !important;
            margin-left: 5px; /* ลดระยะห่างบนมือเพื่อไม่ให้ล้นเกินไป */
            font-size: 0.8rem !important;
        }
        
        .text-long { display: none; }
        .text-short { display: inline-block; }
    }

    /* --- การปรับแต่งสำหรับคอมพิวเตอร์ --- */
    @media (min-width: 769px) {
        .text-long { display: inline-block; }
        .text-short { display: none; }
    }
    .logo-hover:hover {
    transform: scale(1.3); /* ขยายใหญ่ขึ้น 1.3 เท่า */
    }
</style>

<nav class="navbar navbar-dark sticky-top">
    <div class="container-fluid px-md-4">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php" style="letter-spacing: 1px;">
            <img src="bg/v2.png" alt="Logo" class="me-2 logo-hover" style="width: 40px; height: 40px; object-fit: contain; transition: transform 0.3s ease;"> 
            <span style="font-size: 1.75rem;">KTIS&nbsp;<span class="d-none d-sm-inline">ระบบจัดการข้อมูลใบคอม </span></span>
        </a>

        <div class="d-flex align-items-center">
            
            <a href="manage_trucks.php" class="btn btn-outline-light d-flex align-items-center">
                <i data-lucide="truck" class="me-2" style="width:18px;"></i>
                <span class="text-long">รถเข้าหีบประจำวัน</span>
                <span class="text-short">รถเข้าหีบ</span>
            </a>

            <a href="report_daily.php" class="btn btn-outline-light d-flex align-items-center">
                <i data-lucide="bar-chart-3" class="me-2" style="width:18px;"></i>
                <span class="text-long">รายงานสรุป</span>
                <span class="text-short">รายงาน</span>
            </a>

            <div class="text-white mx-2 px-3 py-2 rounded-pill d-none d-lg-flex align-items-center" 
                 style="background: rgba(255,255,255,0.1); border: 1.5px solid rgba(255,255,255,0.2); font-size: 0.85rem; margin-left: 15px !important;">
                <i data-lucide="map-pin" class="me-2 text-info" style="width:16px;"></i>
                <span>หน่วย: <strong><?= htmlspecialchars($_SESSION['statn_name'] ?? '-') ?></strong></span>
            </div>

            <a href="login.php" class="btn btn-danger d-flex align-items-center ms-3">
                <i data-lucide="log-out" class="me-2" style="width:18px;"></i>
                <span class="d-none d-md-inline">ออกจากระบบ</span>
            </a>
            
        </div>
    </div>
</nav>

<script src="https://unpkg.com/lucide@latest"></script>
<script>
  lucide.createIcons();
</script>