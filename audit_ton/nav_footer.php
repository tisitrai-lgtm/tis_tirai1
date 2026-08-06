<?php
/**
 * nav_u_footer.php 
 * ระบบงานฝ่ายไร่ - บริษัท น้ำตาลไทยเอกลักษณ์ จำกัด
 * พัฒนาโดย: Supanat_27.
 */
?>

<footer class="main-footer-custom">
    <div class="footer-container">
        
        <div class="footer-left">
            <div class="vertical-divider"></div>
            <div class="company-info">
                <span class="company-name-th">บริษัท น้ำตาลไทยเอกลักษณ์ จำกัด</span>
                <span class="company-name-en">Thai Identity Sugar Factory Co., Ltd.</span>
                <span class="company-address">
                    42/1 หมู่ที่ 8 บ้านหาดเสือเต้น ตำบลคุ้งตะเภา อำเภอเมืองอุตรดิตถ์ จังหวัดอุตรดิตถ์
                </span>
            </div>
        </div>

        <div class="footer-right">
            <div class="department-info">
                ฝ่ายไร่ <span class="dot-separator">◆</span> แผนกเทคโนโลยีสารสนเทศ
            </div>
            <div class="dev-credit">
                ☁️ Supanat_SK.
            </div>
            <div class="version-info">
                Version 2.1.0 © <?php echo date("2026"); ?> All Rights Reserved
            </div>
        </div>

    </div>
</footer>

<style>
    /* 1. ตั้งค่าพื้นฐานให้ Body ยืดตัวเต็มหน้าจอเพื่อดัน Footer */
    html, body {
        height: 100%;
        margin: 0;
    }

    body {
        display: flex;
        flex-direction: column;
        min-height: 100vh; /* ความสูงขั้นต่ำเท่ากับหน้าจอ */
    }

    /* ⚠️ สำคัญมาก: 
       ในไฟล์หลักของเธอ (เช่น index.php) 
       ต้องหุ้มเนื้อหาหลัก (ตาราง/ข้อมูล) ด้วย <div class="content-wrapper"> 
    */
    .content-wrapper {
        flex: 1 0 auto; /* คำสั่งนี้จะดัน Footer ลงข้างล่างเสมอ */
    }

    /* 2. สไตล์หลักของ Footer */
    .main-footer-custom {
        flex-shrink: 0; /* ป้องกันไม่ให้ Footer โดนบีบจนแบน */
        background: linear-gradient(90deg, #312e81 0%, #1e3a8a 100%);
        color: white;
        padding: 30px 0;
        border-top: 4px solid #3b82f6;
        width: 100%;
        font-family: 'Sarabun', sans-serif; /* หรือฟอนต์ที่เธอใช้ในระบบ */
    }

    .footer-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 40px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }

    /* สไตล์ฝั่งซ้าย */
    .footer-left {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .vertical-divider {
        height: 60px;
        width: 1px;
        background: rgba(255,255,255,0.2);
    }

    .company-info {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .company-name-th {
        font-weight: 700;
        font-size: 1.1rem;
        letter-spacing: 0.5px;
    }

    .company-name-en {
        font-size: 0.85rem;
        color: #cbd5e1;
        font-weight: 500;
    }

    .company-address {
        font-size: 0.8rem;
        color: #94a3b8;
        line-height: 1.4;
    }

    /* สไตล์ฝั่งขวา */
    .footer-right {
        text-align: right;
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .department-info {
        font-weight: 600;
        font-size: 0.95rem;
    }

    .dot-separator {
        color: #60a5fa;
        margin: 0 8px;
    }

    .dev-credit {
        font-size: 0.85rem;
        color: #cbd5e1;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
    }

    .version-info {
        font-size: 0.75rem;
        color: #64748b;
        margin-top: 5px;
    }

    /* 3. Responsive สำหรับมือถือ */
    @media (max-width: 768px) {
        .footer-container {
            flex-direction: column;
            text-align: center;
            padding: 20px;
        }
        .footer-left {
            flex-direction: column;
            gap: 10px;
        }
        .vertical-divider {
            display: none; /* ซ่อนเส้นคั่นในมือถือ */
        }
        .footer-right {
            text-align: center;
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 15px;
            width: 100%;
        }
        .dev-credit {
            justify-content: center;
        }
    }
</style>