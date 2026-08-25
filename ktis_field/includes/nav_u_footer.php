<?php
/**
 * nav_u_footer.php 
 * ระบบงานฝ่ายไร่ - บริษัท น้ำตาลไทยเอกลักษณ์ จำกัด
 * พัฒนาโดย: Supanat_27.
 */
$stmt = $conn->query("SELECT setting_key, setting_value FROM system_settings");
$sys_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<footer class="main-footer-custom">
    <div class="footer-container">
        <div class="footer-left">
            <div class="vertical-divider"></div>
            <div class="company-info">
                <span class="company-name-th"><?php echo $sys_settings['company_name_th'] ?? 'บริษัท น้ำตาลไทยเอกลักษณ์ จำกัด'; ?></span>
                <span class="company-name-en"><?php echo $sys_settings['company_name_en'] ?? 'Thai Identity Sugar Factory Co., Ltd.'; ?></span>
                <span class="company-address"><?php echo $sys_settings['company_address'] ?? '42/1 หมู่ที่ 8 ตำบลคุ้งตะเภา อำเภอเมืองอุตรดิตถ์'; ?></span>
            </div>
        </div>
        <div class="footer-right">
            <div class="department-info">ฝ่ายไร่<span class="dot-separator">◆</span>แผนกเทคโนโลยีสารสนเทศ<span class="dot-separator">◆</span>ทีม IT ฝ่ายไร่</div>
            <div class="dev-credit">🌱 LINE : <?php echo $sys_settings['developer_credit'] ?? 'Supanat_SK.'; ?></div>
            <div class="version-info">Version <?php echo $sys_settings['system_version'] ?? '1.1.0'; ?> © <?php echo date("Y"); ?> All Rights Reserved</div>
        </div>
    </div>
</footer>

<style>
    html { min-height: 100%; margin: 0; }
    body { display: flex; flex-direction: column; min-height: 100vh; margin: 0; background-color: #f8fafc; }
    .content-wrapper { flex: 1 0 auto; }
    
   .main-footer-custom {
    flex-shrink: 0; 
    background: linear-gradient(90deg, #1e293b 0%, #0f172a 100%);
    color: white; 
    padding: 25px 0; 
    border-top: 4px solid #e11d48; 
    width: 100%; 
    font-family: 'Sarabun', sans-serif;
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
    
    .footer-left { display: flex; align-items: center; gap: 20px; }
    .vertical-divider { height: 50px; width: 1px; background: rgba(255,255,255,0.15); }
    .company-info { display: flex; flex-direction: column; gap: 4px; }
    
    .company-name-th { font-weight: 700; font-size: 1.05rem; letter-spacing: 0.5px; color: #f8fafc; }
    .company-name-en { font-size: 0.85rem; color: #cbd5e1; font-weight: 500; }
    .company-address { font-size: 0.8  rem; color: #94a3b8; line-height: 1.4; }
    
    .footer-right { text-align: right; display: flex; flex-direction: column; gap: 5px; }
    .department-info { font-weight: 600; font-size: 0.95rem; color: #f1f5f9; }
    .dot-separator { color: #10b981; margin: 0 6px; }
    .dev-credit { font-size: 0.85rem; color: #cbd5e1; display: flex; align-items: center; justify-content: flex-end; gap: 8px; }
    .version-info { font-size: 0.75rem; color: #64748b; margin-top: 3px; }

    /* 📱 รองรับการแสดงผลบนหน้าจอโทรศัพท์มือถือและแท็บเล็ต */
    @media (max-width: 768px) {
        .main-footer-custom { padding: 20px 0; }
        .footer-container { flex-direction: column; text-align: center; padding: 0 20px; gap: 15px; }
        .footer-left { flex-direction: column; gap: 6px; width: 100%; }
        .vertical-divider { display: none; } /* ซ่อนเส้นตั้งบนมือถือ */
        
        .company-name-th { font-size: 0.95rem; }
        .company-name-en { font-size: 0.8rem; }
        .company-address { font-size: 0.75rem; padding: 0 10px; word-break: break-word; }
        
        .footer-right { 
            text-align: center; 
            border-top: 1px solid rgba(255,255,255,0.1); 
            padding-top: 12px; 
            width: 100%; 
            gap: 4px;
        }
        .department-info { font-size: 0.85rem; }
        .dev-credit { justify-content: center; font-size: 0.8rem; }
        .version-info { font-size: 0.7rem; margin-top: 2px; }
    }
</style>