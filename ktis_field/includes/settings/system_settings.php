<?php /** includes/settings/system_settings.php — System Settings */ 
$m_mode_curr = '0';
$m_msg_curr  = 'ระบบกำลังปิดปรับปรุงชั่วคราว เพื่อพัฒนาและเพิ่มประสิทธิภาพการใช้งาน ขออภัยในความไม่สะดวก';
$m_until_curr = '';
foreach($settings as $s){
    if($s['setting_key'] === 'maintenance_mode') $m_mode_curr = $s['setting_value'];
    if($s['setting_key'] === 'maintenance_message') $m_msg_curr = $s['setting_value'];
    if($s['setting_key'] === 'maintenance_until') $m_until_curr = $s['setting_value'];
}
?>
<div class="card card-full">
    <div class="card-hd" style="border-bottom-color:#f59e0b;">
        <div class="card-hd-l"><i class="fa-solid fa-sliders" style="color:#f59e0b;"></i><span>ตั้งค่าระบบ (System Settings)</span></div>
        <button onclick="saveAllSettings()" style="background:#f59e0b;border:none;color:#fff;padding:6px 14px;border-radius:7px;font-weight:700;font-size:.82rem;font-family:'Sarabun',sans-serif;cursor:pointer;display:flex;align-items:center;gap:5px;" onmouseover="this.style.background='#d97706'" onmouseout="this.style.background='#f59e0b'">
            <i class="fa-solid fa-floppy-disk"></i> บันทึกทั้งหมด
        </button>
    </div>
    <div class="card-bd">
        <?php if(empty($settings)): ?>
        <div class="empty-list" style="padding:24px;">
            <i class="fa-solid fa-triangle-exclamation" style="color:#f59e0b;font-size:1.5rem;display:block;margin-bottom:8px;"></i>
            ยังไม่มีตาราง system_settings
        </div>
        <?php else: ?>

        <!-- ══════════════════════════════════════════════════ -->
        <!-- 🚧 แผงควบคุมโหมดปิดปรับปรุงระบบ (Maintenance Mode) -->
        <!-- ══════════════════════════════════════════════════ -->
        <div style="background:<?php echo ($m_mode_curr==='1')?'#fff7ed':'#f8fafc'; ?>;border:1.5px solid <?php echo ($m_mode_curr==='1')?'#fdba74':'#e2e8f0'; ?>;border-radius:14px;padding:18px 20px;margin-bottom:24px;transition:all .2s ease;">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:14px;padding-bottom:12px;border-bottom:1px solid <?php echo ($m_mode_curr==='1')?'#fed7aa':'#f1f5f9'; ?>;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:38px;height:38px;border-radius:10px;background:<?php echo ($m_mode_curr==='1')?'#ea580c':'#64748b'; ?>;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.1rem;box-shadow:0 3px 10px rgba(0,0,0,0.1);">
                        <i class="fa-solid fa-screwdriver-wrench"></i>
                    </div>
                    <div>
                        <div style="font-weight:800;font-size:.95rem;color:<?php echo ($m_mode_curr==='1')?'#9a3412':'#0f172a'; ?>;">
                            โหมดปิดปรับปรุงระบบ (Maintenance Mode)
                        </div>
                        <div style="font-size:.78rem;color:<?php echo ($m_mode_curr==='1')?'#c2410c':'#64748b'; ?>;margin-top:2px;">
                            เมื่อเปิดโหมดนี้ ผู้ใช้ทั่วไป (User) จะไม่สามารถเข้าใช้งานได้ และจะถูกนำไปยังหน้าแจ้งเตือนปิดปรับปรุง
                        </div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    <span id="maintenance-badge" style="padding:4px 12px;border-radius:999px;font-size:.78rem;font-weight:800;background:<?php echo ($m_mode_curr==='1')?'#ef4444':'#10b981'; ?>;color:#fff;">
                        <?php echo ($m_mode_curr==='1') ? '🔴 ปิดปรับปรุงอยู่ (User เข้าไม่ได้)' : '🟢 ระบบเปิดปกติ'; ?>
                    </span>
                    <button type="button" onclick="toggleMaintenanceQuick()" id="btn-toggle-maintenance" style="padding:7px 16px;border-radius:8px;border:none;font-weight:800;font-size:.82rem;font-family:inherit;cursor:pointer;background:<?php echo ($m_mode_curr==='1')?'#10b981':'#ea580c'; ?>;color:#fff;display:inline-flex;align-items:center;gap:6px;">
                        <i class="fa-solid fa-power-off"></i>
                        <span id="btn-toggle-m-text"><?php echo ($m_mode_curr==='1') ? 'เปิดระบบให้ใช้งาน' : 'สั่งปิดปรับปรุงระบบ'; ?></span>
                    </button>
                    <a href="maintenance.php?preview=1" target="_blank" style="padding:7px 12px;border-radius:8px;border:1px solid #cbd5e1;background:#fff;color:#475569;font-size:.82rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:5px;">
                        <i class="fa-solid fa-eye"></i> ดูตัวอย่างหน้า
                    </a>
                </div>
            </div>

            <input type="hidden" class="setting-input" data-key="maintenance_mode" id="inp-maintenance-mode" value="<?php echo htmlspecialchars($m_mode_curr); ?>">

            <div style="display:grid;grid-template-columns:1fr 240px;gap:12px;">
                <div>
                    <label style="font-size:.78rem;font-weight:700;color:#475569;margin-bottom:4px;display:block;">
                        ข้อความแจ้งเตือนผู้ใช้งานเมื่อปิดปรับปรุง
                    </label>
                    <input type="text" class="add-input setting-input" data-key="maintenance_message" id="inp-maintenance-msg" value="<?php echo htmlspecialchars($m_msg_curr); ?>" placeholder="เช่น ระบบกำลังปิดปรับปรุงชั่วคราว..." style="width:100%;">
                </div>
                <div>
                    <label style="font-size:.78rem;font-weight:700;color:#475569;margin-bottom:4px;display:block;">
                        เวลาคาดว่าจะเปิดใช้งาน (ไม่บังคับ)
                    </label>
                    <input type="text" class="add-input setting-input" data-key="maintenance_until" id="inp-maintenance-until" value="<?php echo htmlspecialchars($m_until_curr); ?>" placeholder="เช่น 16:00 น. หรือ 25 ส.ค." style="width:100%;">
                </div>
            </div>
        </div>

        <?php
        $groups  = ['company'=>'ข้อมูลบริษัท','system'=>'ตั้งค่าระบบอื่นๆ','general'=>'ทั่วไป'];
        $grouped = [];
        $skip_keys = ['maintenance_mode','maintenance_message','maintenance_until'];
        foreach($settings as $k=>$s) {
            if (in_array($s['setting_key'], $skip_keys)) continue;
            $grouped[$s['setting_group']][] = $s;
        }
        foreach($groups as $gkey=>$glabel):
            if(empty($grouped[$gkey])) continue; ?>
        <div style="margin-bottom:20px;">
            <div style="font-size:.78rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.8px;margin-bottom:10px;padding-bottom:6px;border-bottom:1px solid #f1f5f9;">
                <?php echo $glabel; ?>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:10px;">
            <?php foreach($grouped[$gkey] as $s): ?>
            <div style="display:flex;flex-direction:column;gap:4px;">
                <label style="font-size:.8rem;font-weight:700;color:#374151;"><?php echo htmlspecialchars($s['setting_label']); ?></label>
                <input type="text" class="add-input setting-input"
                       data-key="<?php echo htmlspecialchars($s['setting_key']); ?>"
                       value="<?php echo htmlspecialchars($s['setting_value']); ?>"
                       onfocus="this.style.borderColor='#f59e0b'" onblur="this.style.borderColor='#e2e8f0'">
                <span style="font-size:.68rem;color:#94a3b8;font-family:monospace;"><?php echo htmlspecialchars($s['setting_key']); ?></span>
            </div>
            <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>