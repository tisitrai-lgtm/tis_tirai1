<?php /** includes/settings/system_settings.php — System Settings */ ?>
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
            ยังไม่มีตาราง system_settings — กรุณารัน <code>create_system_settings.sql</code> ก่อน
        </div>
        <?php else:
        $groups  = ['company'=>'ข้อมูลบริษัท','system'=>'ตั้งค่าระบบ','general'=>'ทั่วไป'];
        $grouped = [];
        foreach($settings as $k=>$s) $grouped[$s['setting_group']][] = $s;
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