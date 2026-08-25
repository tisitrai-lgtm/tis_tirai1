<?php
/**
 * includes/settings/harvesters.php
 * จัดการรถตัด (harvesters)
 */

// ── ดึงข้อมูลรถตัด ──
$harvesters = [];
try {
    $harvesters = $conn->query("SELECT * FROM harvesters ORDER BY harvester_id ASC")->fetchAll();
} catch(Exception $e){}

$active_count   = count(array_filter($harvesters, fn($h)=>$h['is_active']));
$inactive_count = count($harvesters) - $active_count;
?>

<!-- ── จัดการรถตัดอ้อย ── -->
<div class="card card-full">
    <div class="card-hd" style="border-bottom-color:#06b6d4;">
        <div class="card-hd-l"><i class="fa-solid fa-tractor" style="color:#06b6d4;"></i><span>จัดการรถตัดอ้อย</span></div>
        <div style="display:flex;align-items:center;gap:8px;">
            <span class="cnt-badge"><?php echo $active_count; ?> คัน (ใช้งาน)</span>
            <?php if($inactive_count>0): ?><span class="cnt-badge" style="background:rgba(239,68,68,.2);color:#fca5a5;"><?php echo $inactive_count; ?> คัน (ปลดระวาง)</span><?php endif; ?>
        </div>
    </div>
    <div class="card-bd">
        <!-- เพิ่มรถใหม่ -->
        <div class="add-row" style="margin-bottom:14px;">
            <input type="text" id="new-hv-num" class="add-input" placeholder="เบอร์รถ เช่น รถตัดเบอร์ 51"
                   onfocus="this.style.borderColor='#06b6d4'" onblur="this.style.borderColor='#e2e8f0'">
            <input type="text" id="new-hv-name" class="add-input" placeholder="ชื่อเพิ่มเติม (ไม่บังคับ)"
                   onfocus="this.style.borderColor='#06b6d4'" onblur="this.style.borderColor='#e2e8f0'">
            <button class="btn-add" style="background:#06b6d4;" onmouseover="this.style.background='#0891b2'" onmouseout="this.style.background='#06b6d4'" onclick="addHarvester()">
                <i class="fa-solid fa-plus"></i> เพิ่ม
            </button>
        </div>
        <!-- รายการรถ -->
        <div id="hv-list" class="scroll-list-7">
            <?php foreach($harvesters as $hv): ?>
            <div class="list-item" id="hv-<?php echo $hv['harvester_id']; ?>" style="<?php echo $hv['is_active']?'':'opacity:.55;'; ?>">
                <div>
                    <div class="list-item-text">
                        <i class="fa-solid fa-tractor" style="color:<?php echo $hv['is_active']?'#06b6d4':'#94a3b8'; ?>;font-size:.75rem;margin-right:5px;"></i>
                        <?php echo htmlspecialchars($hv['harvester_number']); ?>
                        <?php if(!empty($hv['harvester_name'])): ?><span style="font-size:.75rem;color:#94a3b8;margin-left:5px;">(<?php echo htmlspecialchars($hv['harvester_name']); ?>)</span><?php endif; ?>
                    </div>
                    <div class="list-item-sub"><?php echo $hv['is_active']?'<span style="color:#10b981;">● ใช้งาน</span>':'<span style="color:#94a3b8;">○ ปลดระวาง</span>'; ?></div>
                </div>
                <div style="display:flex;gap:5px;">
                    <button class="btn-del" style="border-color:<?php echo $hv['is_active']?'#fde68a':'#a7f3d0'; ?>;color:<?php echo $hv['is_active']?'#d97706':'#10b981'; ?>;"
                            onclick="toggleHarvester(<?php echo $hv['harvester_id']; ?>,<?php echo $hv['is_active']?0:1; ?>)">
                        <i class="fa-solid fa-<?php echo $hv['is_active']?'ban':'check'; ?>"></i>
                        <?php echo $hv['is_active']?'ปลดระวาง':'เปิดใช้'; ?>
                    </button>
                    <button class="btn-del" onclick="deleteHarvester(<?php echo $hv['harvester_id']; ?>)">
                        <i class="fa-solid fa-trash-can"></i> ลบ
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if(empty($harvesters)): ?><div class="empty-list"><i class="fa-solid fa-inbox" style="font-size:1.5rem;display:block;margin-bottom:6px;"></i>ยังไม่มีข้อมูล</div><?php endif; ?>
        </div>
    </div>
</div>