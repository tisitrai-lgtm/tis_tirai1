<style>
    :root {
        --main-purple: #6c5ce7;
        --soft-bg: #f4f7fe;
    }

    .navbar {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        padding: 15px 25px;
        border-radius: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        box-shadow: 0 8px 32px rgba(108, 92, 231, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.3);
        position: sticky;
        top: 15px;
        z-index: 9999;
    }

    .nav-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        color: var(--main-purple);
        font-weight: 800;
        font-size: 1.3rem;
        letter-spacing: -0.5px;
    }

    /* สไตล์โลโก้ */
    .brand-logo {
        width: 40px; 
        height: 40px; 
        object-fit: contain;
        /* เอา border-radius ออกถ้าอยากให้รูปทรงตามไฟล์จริง */
        border-radius: 8px; 
    }

    .nav-links {
        display: flex;
        gap: 15px;
        align-items: center;
    }

    .nav-item {
        text-decoration: none;
        color: #636e72;
        font-weight: 600;
        font-size: 0.95rem;
        padding: 10px 18px;
        border-radius: 14px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .nav-item:hover {
        background: rgba(108, 92, 231, 0.08);
        color: var(--main-purple);
        transform: translateY(-2px);
    }

    .nav-item.active {
        background: var(--main-purple);
        color: #fff !important;
        box-shadow: 0 4px 15px rgba(108, 92, 231, 0.3);
    }

    @media (max-width: 768px) {
        .navbar {
            padding: 12px 15px;
            border-radius: 15px;
            top: 10px;
        }
        .nav-brand span { display: none; } 
        .nav-item { padding: 8px 12px; font-size: 0.85rem; }
        /* สไตล์โลโก้ */
    .brand-logo {
        width: 60px;   /* ปรับจาก 40px เป็น 60px หรือตามใจชอบ */
        height: 60px;  /* ปรับจาก 40px เป็น 60px หรือตามใจชอบ */
        object-fit: contain;
        border-radius: 8px; 
    }
    .navbar {
        /* ... */
        padding: 10px 25px; /* เลขตัวหน้า (10px) คือ ระยะห่างบน-ล่าง ถ้าเพิ่มเป็น 15px-20px จะทำให้แถบ Navbar หนาขึ้น */
        /* ... */
    }
    }
</style>

<nav class="navbar">
    <a href="index.php" class="nav-brand">
        <img src="icon/KTIS-1.png" alt="Logo" class="brand-logo">
        <span>TIS แปลง UTM to Lat&Log</span>
    </a>

    <div class="nav-links">
        <a href="index.php" class="nav-item <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : '' ?>">
            🏠 <span>หน้าหลัก</span>
        </a>
        <a href="map-all.php" class="nav-item <?= (basename($_SERVER['PHP_SELF']) == 'map-all.php') ? 'active' : '' ?>">
            🗺️ <span>แผนที่</span>
        </a>
        <?php if(basename($_SERVER['PHP_SELF']) == 'index.php'): ?>
        <a href="javascript:void(0)" onclick="openHistoryModal()" class="nav-item" style="background: #f1f2f6; border: 1px solid #ddd;">
            📜 <span>ประวัติ</span>
        </a>
        <?php endif; ?>
    </div>
</nav>