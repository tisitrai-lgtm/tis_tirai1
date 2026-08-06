<?php /** includes/settings/zones.php — หน่วยส่งเสริม */ 
/** @var array $problems */
/** @var array $zones */
/** @var array $field_items */
 /** @var array $cut_items */
?>
<div class="card">
    <div class="card-hd green">
        <div class="card-hd-l"><i class="fa-solid fa-location-dot"></i><span>หน่วยส่งเสริม (Zones)</span></div>
        <span class="cnt-badge" id="zone-count"><?php echo count($zones); ?> หน่วย</span>
    </div>
    <div class="card-bd">
        <div class="zone-add-row">
            <input type="text" id="new-zone-id" class="add-input green" placeholder="รหัส เช่น 235">
            <input type="text" id="new-zone-name" class="add-input green" placeholder="ชื่อหน่วย เช่น วังทอง">
            <button class="btn-add green" onclick="addZone()"><i class="fa-solid fa-plus"></i> เพิ่ม</button>
        </div>
        <div id="zone-list" class="scroll-list-7">
            <?php foreach($zones as $z): ?>
            <div class="list-item" id="zone-<?php echo htmlspecialchars($z['zone_id']); ?>">
                <div class="list-item-text">
                    <span style="background:#e0f2fe;color:#0369a1;padding:1px 7px;border-radius:4px;font-size:.75rem;font-weight:700;margin-right:6px;"><?php echo htmlspecialchars($z['zone_id']); ?></span>
                    <?php echo htmlspecialchars($z['zone_name']); ?>
                </div>
                <button class="btn-del" onclick="deleteZone('<?php echo htmlspecialchars($z['zone_id']); ?>')"><i class="fa-solid fa-trash-can"></i> ลบ</button>
            </div>
            <?php endforeach; ?>
            <?php if(empty($zones)): ?><div class="empty-list">ยังไม่มีข้อมูล</div><?php endif; ?>
        </div>
    </div>
</div>