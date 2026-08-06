<?php /** includes/settings/check_items.php — รายการตรวจ (cut + field) */
/** @var array $problems */
/** @var array $zones */
/** @var array $field_items */
 /** @var array $cut_items */
 ?>

<!-- ชุดใบมีด/ตัด -->
<div class="card">
    <div class="card-hd blue">
        <div class="card-hd-l"><i class="fa-solid fa-fan"></i><span>รายการตรวจ: ชุดใบมีด/ตัด</span></div>
        <span class="cnt-badge" id="cut-count"><?php echo count($cut_items); ?> รายการ</span>
    </div>
    <div class="card-bd">
        <div class="add-row">
            <input type="text" id="new-cut" class="add-input" placeholder="เช่น ใบพัดสับท่อน"
                   onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
            <button class="btn-add" style="background:#3b82f6;" onmouseover="this.style.background='#2563eb'" onmouseout="this.style.background='#3b82f6'" onclick="addCutItem()">
                <i class="fa-solid fa-plus"></i> เพิ่ม
            </button>
        </div>
        <div id="cut-list" class="scroll-list-7">
            <?php foreach($cut_items as $c): ?>
            <div class="list-item" id="cut-<?php echo $c['item_id']; ?>">
                <div class="list-item-text">
                    <i class="fa-solid fa-screwdriver-wrench" style="color:#3b82f6;font-size:.75rem;margin-right:5px;"></i>
                    <?php echo htmlspecialchars($c['item_name_cut']); ?>
                </div>
                <div style="display:flex;gap:6px;">
                    <button class="btn-del" style="border-color:#bfdbfe;color:#3b82f6;" onclick="editCutItem(<?php echo $c['item_id']; ?>,'<?php echo addslashes(htmlspecialchars($c['item_name_cut'])); ?>')"><i class="fa-solid fa-pen"></i> แก้ไข</button>
                    <button class="btn-del" onclick="deleteCutItem(<?php echo $c['item_id']; ?>)"><i class="fa-solid fa-trash-can"></i> ลบ</button>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if(empty($cut_items)): ?><div class="empty-list"><i class="fa-solid fa-inbox" style="font-size:1.5rem;display:block;margin-bottom:6px;"></i>ยังไม่มีข้อมูล</div><?php endif; ?>
        </div>
    </div>
</div>

<!-- สภาพแปลงอ้อย -->
<div class="card">
    <div class="card-hd" style="border-bottom-color:#f59e0b;">
        <div class="card-hd-l"><i class="fa-solid fa-leaf" style="color:#f59e0b;"></i><span>รายการตรวจ: สภาพแปลงอ้อย</span></div>
        <span class="cnt-badge" id="field-count"><?php echo count($field_items); ?> รายการ</span>
    </div>
    <div class="card-bd">
        <div class="add-row">
            <input type="text" id="new-field" class="add-input" placeholder="เช่น อ้อยล้ม, หญ้ารก"
                   onfocus="this.style.borderColor='#f59e0b'" onblur="this.style.borderColor='#e2e8f0'">
            <button class="btn-add" style="background:#f59e0b;" onmouseover="this.style.background='#d97706'" onmouseout="this.style.background='#f59e0b'" onclick="addFieldItem()">
                <i class="fa-solid fa-plus"></i> เพิ่ม
            </button>
        </div>
        <div id="field-list" class="scroll-list-7">
            <?php foreach($field_items as $f): ?>
            <div class="list-item" id="field-<?php echo $f['item_id']; ?>">
                <div class="list-item-text">
                    <i class="fa-solid fa-seedling" style="color:#f59e0b;font-size:.75rem;margin-right:5px;"></i>
                    <?php echo htmlspecialchars($f['item_name_field']); ?>
                </div>
                <div style="display:flex;gap:6px;">
                    <button class="btn-del" style="border-color:#fde68a;color:#d97706;" onclick="editFieldItem(<?php echo $f['item_id']; ?>,'<?php echo addslashes(htmlspecialchars($f['item_name_field'])); ?>')"><i class="fa-solid fa-pen"></i> แก้ไข</button>
                    <button class="btn-del" onclick="deleteFieldItem(<?php echo $f['item_id']; ?>)"><i class="fa-solid fa-trash-can"></i> ลบ</button>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if(empty($field_items)): ?><div class="empty-list"><i class="fa-solid fa-inbox" style="font-size:1.5rem;display:block;margin-bottom:6px;"></i>ยังไม่มีข้อมูล</div><?php endif; ?>
        </div>
    </div>
</div>