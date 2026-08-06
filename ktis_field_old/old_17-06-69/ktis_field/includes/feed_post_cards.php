<?php
/**
 * includes/feed_post_cards.php
 * แสดง feed-container + post cards + reply section
 * ต้องการตัวแปร: $posts, $conn, $_SESSION
 */
function get_avatar_name($full_name) {
    return mb_substr(trim($full_name), 0, 2, 'UTF-8');
}
?>
        <div class="feed-container">
            <?php if(empty($posts)): ?>
                <div style="text-align:center; padding:40px; background:white; border-radius:12px; color:#64748b;">
                    <p style="margin:0; font-weight:600;">ไม่มีรายการแจ้งเหตุรถอ้อยในวันที่เลือก</p>
                </div>
            <?php else:
                // เตรียม Prepared Statement สำหรับ replies และ logs ไว้นอก loop แก้ปัญหา N+1 Query
                $stmt_replies_prep = $conn->prepare(
                    "SELECT r.*, e.emp_name, e.emp_unit FROM replies r
                     JOIN employee e ON r.emp_id = e.emp_id
                     WHERE r.post_id = :post_id ORDER BY r.created_at ASC"
                );
                $stmt_log_prep = $conn->prepare(
                    "SELECT old_text, old_created_at FROM reply_logs WHERE reply_id = :reply_id ORDER BY log_id DESC"
                );
            ?>
                <?php foreach($posts as $post): ?>
                    <div class="feed-card" id="post-card-<?php echo $post['post_id']; ?>" style="border-left: 5px solid <?php echo $post['job_status'] == 'success' ? '#10b981' : '#e11d48'; ?>;">
                        
                        <div class="post-header">
                            <div>
                                <strong style="color:#1e293b; font-size:1.05rem;"><?php echo htmlspecialchars($post['emp_name']); ?></strong> 
                                <span style="font-size:0.85rem; color:#64748b;">(ออฟฟิศกลาง)</span>
                                <div style="font-size:0.8rem; color:#94a3b8; margin-top:2px;"><i class="fa-regular fa-clock"></i> <?php echo date('H:i น.', strtotime($post['created_at'])); ?></div>
                            </div>
                            <div style="display:flex; align-items:center; gap:12px;">
                                <span data-status-badge style="padding:4px 12px; border-radius:12px; font-size:0.8rem; font-weight:700; <?php echo $post['job_status'] == 'success' ? 'background:#d1fae5; color:#065f46;' : 'background:#fee2e2; color:#991b1b;'; ?>">
                                    <?php echo $post['job_status'] == 'success' ? 'ดำเนินการแล้ว' : 'รอดำเนินการ'; ?>
                                </span>
                                <?php if($_SESSION['emp_level'] == 'a'): ?>
                                    <button class="btn-delete-post" onclick="deletePost(<?php echo $post['post_id']; ?>)" title="ลบรายการนี้"><i class="fa-solid fa-trash-can"></i></button>
                                <?php endif; ?>
                                <?php if($_SESSION['emp_level'] == 'a'): ?>
                                <button onclick="toggleStatus(<?php echo $post['post_id']; ?>, '<?php echo $post['job_status']; ?>')"
                                    title="<?php echo $post['job_status']=='pending' ? 'กดเพื่อยืนยันดำเนินการแล้ว' : 'กดเพื่อเปลี่ยนกลับเป็นรอดำเนินการ'; ?>"
                                    style="background:<?php echo $post['job_status']=='pending' ? '#10b981' : '#e11d48'; ?>;border:none;color:#fff;padding:5px 12px;border-radius:7px;font-size:0.78rem;font-weight:700;cursor:pointer;font-family:'Sarabun',sans-serif;display:flex;align-items:center;gap:4px;"
                                    id="status-btn-<?php echo $post['post_id']; ?>">
                                    <?php if($post['job_status']=='pending'): ?>
                                        <i class="fa-solid fa-circle-check"></i> ยืนยันดำเนินการแล้ว
                                    <?php else: ?>
                                        <i class="fa-solid fa-rotate-left"></i> เปลี่ยนเป็นรอดำเนินการ
                                    <?php endif; ?>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="post-meta-badges">
                            <span class="meta-badge"><i class="fa-solid fa-location-dot" style="color:#e11d48;"></i> หน่วยส่งเสริม: <?php echo htmlspecialchars($post['target_unit']); ?></span>
                            <span class="meta-badge"><i class="fa-solid fa-truck" style="color:#10b981;"></i> ทะเบียนรถ: <?php echo htmlspecialchars($post['truck_number']); ?></span>
                        </div>

                        <div class="problem-box">
                            <strong style="color:#991b1b; font-size:0.9rem;"><i class="fa-solid fa-triangle-exclamation"></i> ปัญหาที่พบ:</strong>
                            <div style="margin:6px 0 0 0; display:flex; flex-wrap:wrap; gap:6px;">
                                <?php if(!empty($post['problem_detail'])): ?>
                                <span style="background:#fee2e2;color:#991b1b;padding:3px 10px;border-radius:5px;font-size:0.85rem;font-weight:700;display:inline-flex;align-items:center;gap:4px;">
                                    <i class="fa-solid fa-circle-exclamation" style="font-size:0.75rem;"></i>
                                    <?php echo htmlspecialchars($post['problem_detail']); ?>
                                </span>
                                <?php endif; ?>
                                <?php if(!empty($post['problem_detail_2'])): ?>
                                <span style="background:#fff7ed;color:#9a3412;padding:3px 10px;border-radius:5px;font-size:0.85rem;font-weight:700;display:inline-flex;align-items:center;gap:4px;">
                                    <i class="fa-solid fa-circle-exclamation" style="font-size:0.75rem;"></i>
                                    <?php echo htmlspecialchars($post['problem_detail_2']); ?>
                                </span>
                                <?php endif; ?>
                                <?php if(!empty($post['problem_detail_3'])): ?>
                                <span style="background:#fef9c3;color:#713f12;padding:3px 10px;border-radius:5px;font-size:0.85rem;font-weight:700;display:inline-flex;align-items:center;gap:4px;">
                                    <i class="fa-solid fa-circle-exclamation" style="font-size:0.75rem;"></i>
                                    <?php echo htmlspecialchars($post['problem_detail_3']); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if(!empty($post['post_text'])): ?>
                            <p style="color:#334155; font-size:0.95rem; line-height:1.5; margin:0 0 15px 0;"><?php echo nl2br(htmlspecialchars($post['post_text'])); ?></p>
                        <?php endif; ?>

                        <div class="post-image-gallery">
                            <?php if(!empty($post['post_image'])): ?><img src="<?php echo htmlspecialchars($post['post_image']); ?>" class="post-img" onclick="window.open(this.src)"><?php endif; ?>
                            <?php if(!empty($post['post_image_2'])): ?><img src="<?php echo htmlspecialchars($post['post_image_2']); ?>" class="post-img" onclick="window.open(this.src)"><?php endif; ?>
                            <?php if(!empty($post['post_image_3'])): ?><img src="<?php echo htmlspecialchars($post['post_image_3']); ?>" class="post-img" onclick="window.open(this.src)"><?php endif; ?>
                        </div>

                        <div class="reply-section">
                            <div class="comments-list">
                                <?php
                                // ใช้ Prepared Statement ที่เตรียมไว้ก่อนหน้า loop แทน
                                $stmt_replies_prep->execute([':post_id' => $post['post_id']]);
                                $replies = $stmt_replies_prep->fetchAll();
                                
                                $avatar_colors = ['avatar-green', 'avatar-orange', 'avatar-blue'];
                                $idx = 0;

                                foreach($replies as $reply):
                                    $current_color = $avatar_colors[$idx % 3]; $idx++;
                                    
                                 // ใช้ stmt ที่ Prepare ไว้แล้วนอก loop เพื่อประสิทธิภาพ
$stmt_log_prep->execute([':reply_id' => $reply['reply_id']]);
$logs = $stmt_log_prep->fetchAll();
$is_edited = (count($logs) > 0);
?>
<div class="chat-row" id="reply-row-<?php echo $reply['reply_id']; ?>">
    <div class="chat-avatar <?php echo $current_color; ?>">
        <?php echo htmlspecialchars(get_avatar_name($reply['emp_name'])); ?>
    </div>
    <div class="chat-content-box">
        <div class="chat-info-header">
            <div class="chat-user-name">
                <?php echo htmlspecialchars($reply['emp_name']); ?> 
                <span class="chat-user-unit">(หน่วย<?php echo htmlspecialchars($reply['emp_unit']); ?>)</span>
            </div>
            <div class="chat-timestamp">
                <?php echo date('H:i น.', strtotime($reply['created_at'])); ?>
                
                <?php if($is_edited): ?>
                    <?php
                    // บันทึกประวัติเป็น Array แล้วใช้ json_encode() แทนการ echo ตรงๆ ป้องกัน XSS
                    $history_lines = ["——— ประวัติการพิมพ์ข้อความเก่า ———"];
                    foreach($logs as $l_idx => $log_item) {
                        $display_time = !empty($log_item['old_created_at']) ? date('H:i น.', strtotime($log_item['old_created_at'])) : 'ไม่ระบุเวลา';
                        $history_lines[] = ($l_idx+1) . ". " . $log_item['old_text'] . " (" . $display_time . ")";
                    }
                    $history_json = json_encode(implode("\n", $history_lines), JSON_UNESCAPED_UNICODE);
                    ?>
                    <span class="edited-tag" onclick="alert(<?php echo $history_json; ?>)" title="คลิกเพื่อดูข้อความเดิม">แก้ไขแล้ว</span>
                <?php endif; ?>

                <?php if($reply['emp_id'] == $_SESSION['emp_id']): ?>
                    <button class="btn-edit-reply" onclick="enableEditMode(<?php echo $reply['reply_id']; ?>)" title="แก้ไขข้อความ"><i class="fa-solid fa-pen-to-square"></i></button>
                <?php endif; ?>
                <?php if($_SESSION['emp_level'] == 'a'): ?>
                    <button class="btn-edit-reply" style="color:#e11d48;" onclick="deleteReply(<?php echo $reply['reply_id']; ?>, <?php echo $post['post_id']; ?>)" title="ลบความคิดเห็นนี้"><i class="fa-solid fa-trash-can"></i></button>
                <?php endif; ?>
            </div>
        </div>
        
        <p class="chat-text" id="reply-text-<?php echo $reply['reply_id']; ?>"><?php echo nl2br(htmlspecialchars($reply['reply_text'])); ?></p>
        
        <div id="edit-box-<?php echo $reply['reply_id']; ?>" style="display:none; margin-top:8px;">
            <input type="text" id="edit-input-<?php echo $reply['reply_id']; ?>" value="<?php echo htmlspecialchars($reply['reply_text']); ?>" class="form-input" style="padding:6px; margin-bottom:5px;">
            <div style="display:flex; gap:5px;">
                <button type="button" class="tab-item tab-success active" style="border:none; cursor:pointer; padding:4px 10px;" onclick="saveEdit(<?php echo $reply['reply_id']; ?>)">บันทึก</button>
                <button type="button" class="tab-item tab-inactive" style="border:none; cursor:pointer; padding:4px 10px;" onclick="cancelEdit(<?php echo $reply['reply_id']; ?>)">ยกเลิก</button>
            </div>
        </div>

        <?php if(!empty($reply['reply_image'])): ?>
            <div><img src="<?php echo htmlspecialchars($reply['reply_image']); ?>" class="chat-embedded-img" onclick="window.open(this.src)"></div>
        <?php endif; ?>
    </div>
</div>
                                <?php endforeach; ?>
                            </div>

                            <div class="reply-form-container">
                                <?php 
                                $is_assigned_unit = (!empty($_SESSION['emp_unit']) && strpos($post['target_unit'], $_SESSION['emp_unit']) !== false);
                                if($is_assigned_unit || $_SESSION['emp_level'] == 'a'): 
                                    $my_avatar_color = ($_SESSION['emp_level'] == 'a') ? 'avatar-blue' : 'avatar-green';
                                ?>
                                    <form class="replyForm">
                                        <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                        <div class="chat-input-wrapper">
                                            <div class="chat-avatar <?php echo $my_avatar_color; ?>" style="width:36px; height:36px; font-size:0.8rem;">
                                                <?php echo htmlspecialchars(get_avatar_name($_SESSION['emp_name'])); ?>
                                            </div>
                                            <div class="chat-input-container">
                                                <input type="text" name="reply_text" placeholder="พิมพ์รายงานความคืบหน้า..." class="chat-main-field" required autocomplete="off">
                                                <label class="file-attach-label">
                                                    <i class="fa-solid fa-paperclip"></i>
                                                    <input type="file" name="reply_image" accept="image/*" class="hidden-file-input" onchange="displayFileName(this)">
                                                </label>
                                            </div>
                                            <button type="submit" class="btn-chat-send"><i class="fa-solid fa-paper-plane"></i></button>
                                        </div>
                                        <div class="file-status-preview"><i class="fa-solid fa-image"></i> แนบรูปภาพรายงานเรียบร้อยแล้ว</div>
                                    </form>
                                <?php else: ?>
                                    <div style="font-size:0.85rem; color:#94a3b8; font-style:italic; background:#f8fafc; padding:10px; border-radius:6px; border:1px dashed #e2e8f0; text-align:center;">
                                        <i class="fa-solid fa-lock"></i> เฉพาะพนักงานสังกัดหน่วย "<?php echo htmlspecialchars($post['target_unit']); ?>" เท่านั้นที่มีสิทธิ์รายงานความคืบหน้ากลับคืน
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</div>