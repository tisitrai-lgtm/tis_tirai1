<?php
/**
 * includes/settings/harvesters.php
 * จัดการรถตัด (harvesters) + ผูกพนักงานกับรถตัด (employee_harvester)
 */

// ── ดึงข้อมูลรถตัด ──
$harvesters = [];
try {
    $harvesters = $conn->query("SELECT * FROM harvesters ORDER BY harvester_id ASC")->fetchAll();
} catch(Exception $e){}

// ── ดึงพนักงานทุกคน ──
$employees = [];
try {
    $employees = $conn->query("SELECT emp_id, emp_name, emp_unit FROM employee ORDER BY emp_name ASC")->fetchAll();
} catch(Exception $e){}

// ── ดึงการ assign ──
$assigns = [];
try {
    $rows = $conn->query("SELECT eh.*, e.emp_name, e.emp_unit, h.harvester_number FROM employee_harvester eh JOIN employee e ON eh.emp_id=e.emp_id JOIN harvesters h ON eh.harvester_id=h.harvester_id ORDER BY h.harvester_id ASC, e.emp_name ASC")->fetchAll();
    foreach($rows as $r) $assigns[] = $r;
} catch(Exception $e){}

$active_count   = count(array_filter($harvesters, fn($h)=>$h['is_active']));
$inactive_count = count($harvesters) - $active_count;
?>

<!-- ── รถตัด ── -->
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

<!-- ── ผูกพนักงานกับรถตัด ── -->
<div class="card card-full">
    <div class="card-hd" style="border-bottom-color:#8b5cf6;">
        <div class="card-hd-l"><i class="fa-solid fa-link" style="color:#8b5cf6;"></i><span>ผูกพนักงานกับรถตัด</span></div>
        <span class="cnt-badge"><?php echo count($assigns); ?> รายการ</span>
    </div>
    <div class="card-bd">
        <!-- ฟอร์ม assign -->
        <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:8px;margin-bottom:14px;" class="assign-grid">
            <select id="assign-emp" class="add-input" onfocus="this.style.borderColor='#8b5cf6'" onblur="this.style.borderColor='#e2e8f0'">
                <option value="">-- เลือกพนักงาน --</option>
                <?php foreach($employees as $emp): ?>
                <option value="<?php echo htmlspecialchars($emp['emp_id']); ?>">
                    <?php echo htmlspecialchars($emp['emp_name']); ?> (<?php echo htmlspecialchars($emp['emp_unit']); ?>)
                </option>
                <?php endforeach; ?>
            </select>
            <select id="assign-hv" class="add-input" onfocus="this.style.borderColor='#8b5cf6'" onblur="this.style.borderColor='#e2e8f0'">
                <option value="">-- เลือกรถตัด --</option>
                <?php foreach($harvesters as $hv): if(!$hv['is_active']) continue; ?>
                <option value="<?php echo $hv['harvester_id']; ?>"><?php echo htmlspecialchars($hv['harvester_number']); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn-add" style="background:#8b5cf6;" onmouseover="this.style.background='#7c3aed'" onmouseout="this.style.background='#8b5cf6'" onclick="addAssign()">
                <i class="fa-solid fa-link"></i> ผูก
            </button>
        </div>
        <!-- รายการ assign -->
        <div id="assign-list" class="scroll-list-7">
            <?php if(empty($assigns)): ?>
            <div class="empty-list"><i class="fa-solid fa-inbox" style="font-size:1.5rem;display:block;margin-bottom:6px;"></i>ยังไม่มีการผูก</div>
            <?php else: ?>
            <?php
            // จัดกลุ่มตามรถตัด
            $grouped = [];
            foreach($assigns as $a) $grouped[$a['harvester_number']][] = $a;
            foreach($grouped as $hvnum => $list):
            ?>
            <div style="margin-bottom:10px;">
                <div style="font-size:.75rem;font-weight:700;color:#06b6d4;margin-bottom:5px;display:flex;align-items:center;gap:5px;">
                    <i class="fa-solid fa-tractor"></i> <?php echo htmlspecialchars($hvnum); ?>
                </div>
                <?php foreach($list as $a): ?>
                <div class="list-item" id="assign-<?php echo $a['id']; ?>" style="margin-left:12px;margin-bottom:4px;">
                    <div class="list-item-text">
                        <i class="fa-solid fa-user" style="color:#8b5cf6;font-size:.75rem;margin-right:5px;"></i>
                        <?php echo htmlspecialchars($a['emp_name']); ?>
                        <span style="font-size:.72rem;color:#94a3b8;margin-left:4px;">(<?php echo htmlspecialchars($a['emp_unit']); ?>)</span>
                    </div>
                    <button class="btn-del" onclick="deleteAssign(<?php echo $a['id']; ?>)"><i class="fa-solid fa-unlink"></i> ยกเลิก</button>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
@media(max-width:600px){
    .assign-grid { grid-template-columns:1fr 1fr; grid-template-rows:auto auto; }
    .assign-grid button { grid-column:1/-1; }
}
</style>