<?php /** includes/settings/problems.php — ประเภทปัญหาที่พบ */ 
/** @var array $problems */
/** @var array $zones */
/** @var array $field_items */
 /** @var array $cut_items */
?>
<div class="card">
    <div class="card-hd">
        <div class="card-hd-l"><i class="fa-solid fa-triangle-exclamation"></i><span>ประเภทปัญหาที่พบ</span></div>
        <span class="cnt-badge" id="prob-count"><?php echo count($problems); ?> รายการ</span>
    </div>
    <div class="card-bd">
        <div class="add-row">
            <input type="text" id="new-prob" class="add-input" placeholder="ชื่อปัญหาใหม่ เช่น อ้อยสกปรก">
            <button class="btn-add" onclick="addProblem()"><i class="fa-solid fa-plus"></i> เพิ่ม</button>
        </div>
        <div id="prob-list" class="scroll-list-7">
            <?php foreach($problems as $p): ?>
            <div class="list-item" id="prob-<?php echo $p['problem_id']; ?>">
                <div class="list-item-text"><i class="fa-solid fa-circle-exclamation" style="color:#e11d48;font-size:.75rem;margin-right:5px;"></i><?php echo htmlspecialchars($p['problem_name']); ?></div>
                <button class="btn-del" onclick="deleteProblem(<?php echo $p['problem_id']; ?>)"><i class="fa-solid fa-trash-can"></i> ลบ</button>
            </div>
            <?php endforeach; ?>
            <?php if(empty($problems)): ?><div class="empty-list"><i class="fa-solid fa-inbox" style="font-size:1.5rem;display:block;margin-bottom:6px;"></i>ยังไม่มีข้อมูล</div><?php endif; ?>
        </div>
    </div>
</div>