<nav style="
    background: linear-gradient(90deg, #312e81 0%, #1e3a8a 100%); 
    padding: 15px 0; 
    box-shadow: 0 4px 15px rgba(0,0,0,0.3); 
    position: sticky;      /* ทำให้เกาะติด */
    top: 0;               /* เกาะที่ขอบบนสุด */
    z-index: 1000;        /* ให้อยู่เหนือเลเยอร์อื่นตลอดเวลา */
    width: 100%;
">
    <div style="max-width: 100%; margin: 0 auto; padding: 0 40px; display: flex; justify-content: space-between; align-items: center;">
        
        <div style="display: flex; align-items: center; gap: 20px;">
            <div style="background: white; padding: 10px; border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <span style="font-size: 1.8rem;">🚜</span>
            </div>
            <div style="display: flex; flex-direction: column;">
                <span style="color: white; font-weight: 800; font-size: 1.4rem; letter-spacing: 0.5px; line-height: 1.2;">
                    ระบบบริหารจัดการคิวรถบรรทุกอ้อย
                </span>
                <span style="color: rgba(255,255,255,0.8); font-size: 0.85rem; font-weight: 400;">
                    สำหรับการใช้งานภายในเขตรับผิดชอบ | Truck Queue Management System
                </span>
            </div>
        </div>

        <div style="display: flex; gap: 15px; align-items: center;">
            <a href="add_queue.php" class="nav-btn <?php echo basename($_SERVER['PHP_SELF']) == 'add_queue.php' ? 'active' : ''; ?>">
                📍 ลงทะเบียนคิว
            </a>
            
            <div style="position: relative; display: inline-block;">
                <button onclick="toggleUIPanel()" class="nav-btn" style="cursor: pointer; background: rgba(255,255,255,0.2);">
                    🖥️ ปรับแต่งจอ
                </button>
                
                <div id="ui-panel" style="display: none; position: absolute; right: 0; top: 55px; background: white; min-width: 220px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); border-radius: 15px; padding: 15px; z-index: 2000; border: 1px solid #e2e8f0;">
                    <div style="color: #1e293b; font-weight: bold; margin-bottom: 12px; border-bottom: 1px solid #eee; padding-bottom: 8px; font-family: 'Sarabun', sans-serif;">การแสดงผล</div>
                    
                    <div style="margin-bottom: 15px;">
                        <label style="font-size: 0.85rem; color: #64748b; display: block; margin-bottom: 8px; font-family: 'Sarabun', sans-serif;">ขนาดตัวอักษร</label>
                        <div style="display: flex; gap: 5px;">
                            <button onclick="changeUISize('normal')" style="flex: 1; padding: 6px; cursor: pointer; border-radius: 6px; border: 1px solid #ddd; font-family: 'Sarabun';">ปกติ</button>
                            <button onclick="changeUISize('large')" style="flex: 1; padding: 6px; cursor: pointer; border-radius: 6px; border: 1px solid #ddd; font-family: 'Sarabun';">ใหญ่</button>
                        </div>
                    </div>

                    <div>
                        <label style="font-size: 0.85rem; color: #64748b; display: block; margin-bottom: 8px; font-family: 'Sarabun', sans-serif;">โหมดใช้งาน</label>
                        <button id="dark-mode-toggle" onclick="handleDarkToggle()" style="width: 100%; padding: 10px; cursor: pointer; border-radius: 8px; border: none; background: #1e293b; color: white; font-family: 'Sarabun'; font-weight: bold;">
                            🌙 โหมดมืด
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<style>
    .nav-btn {
        color: white;
        text-decoration: none;
        padding: 10px 20px;
        border-radius: 12px;
        font-size: 0.95rem;
        font-weight: 600;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: inline-flex;
        align-items: center;
        font-family: 'Sarabun', sans-serif;
    }
    
    .nav-btn:hover {
        background: rgba(255, 255, 255, 0.15);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .nav-btn.active {
        background: #3b82f6;
        border-color: #60a5fa;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
    }

    body {
        background-color: #f1f5f9;
        margin: 0;
        transition: background-color 0.3s, color 0.3s;
    }
</style>

<script>
// ฟังก์ชันเปิด-ปิดเมนู
function toggleUIPanel() {
    const panel = document.getElementById('ui-panel');
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}

// คลิกที่อื่นให้เมนูปิด
window.addEventListener('click', function(e) {
    const panel = document.getElementById('ui-panel');
    if (!e.target.closest('#ui-panel') && !e.target.closest('button')) {
        panel.style.display = 'none';
    }
});

// จัดการเรื่องขนาด
function changeUISize(size) {
    if(size === 'large') {
        document.body.classList.add('ui-large');
        localStorage.setItem('pref-size', 'large');
    } else {
        document.body.classList.remove('ui-large');
        localStorage.setItem('pref-size', 'normal');
    }
}

// จัดการโหมดมืด
function handleDarkToggle() {
    const isDark = document.body.classList.toggle('dark-mode');
    localStorage.setItem('pref-dark', isDark);
    refreshDarkBtn();
}

function refreshDarkBtn() {
    const btn = document.getElementById('dark-mode-toggle');
    if(document.body.classList.contains('dark-mode')) {
        btn.innerHTML = '☀️ โหมดสว่าง';
        btn.style.background = '#f1f5f9';
        btn.style.color = '#1e293b';
    } else {
        btn.innerHTML = '🌙 โหมดมืด';
        btn.style.background = '#1e293b';
        btn.style.color = 'white';
    }
}

// โหลดค่าจากหน่วยความจำเมื่อเปิดเว็บ
window.addEventListener('DOMContentLoaded', () => {
    if(localStorage.getItem('pref-size') === 'large') changeUISize('large');
    if(localStorage.getItem('pref-dark') === 'true') {
        document.body.classList.add('dark-mode');
        refreshDarkBtn();
    }
});
</script>