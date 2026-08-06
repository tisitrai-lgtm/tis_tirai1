<?php
/**
 * includes/feed_post_cards.php
 * แสดง feed-container + post cards + reply section
 * ต้องการตัวแปร: $posts, $conn, $_SESSION
 */
function get_avatar_name($full_name) {
    return mb_substr(trim($full_name), 0, 2, 'UTF-8');
}
$is_ajax = $is_ajax ?? false;
if (!$is_ajax): ?>
        <div class="feed-container" id="feed-items-container">
<?php endif; ?>
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
                            <?php
                            $gimgs = array_values(array_filter([
                                $post['post_image']   ?? '',
                                $post['post_image_2'] ?? '',
                                $post['post_image_3'] ?? '',
                            ]));
                            $gjson = htmlspecialchars(json_encode($gimgs, JSON_UNESCAPED_UNICODE), ENT_QUOTES);
                            foreach($gimgs as $gi => $gsrc): ?>
                            <img src="<?php echo htmlspecialchars($gsrc); ?>"
                                 class="post-img js-lightbox-img"
                                 data-gallery='<?php echo $gjson; ?>'
                                 data-index="<?php echo $gi; ?>">
                            <?php endforeach; ?>
                        </div>

                        <!-- Reaction Bar -->
                        <?php
                        $stmt_rc = $conn->prepare("SELECT reaction_type, COUNT(*) as cnt FROM post_reactions WHERE post_id=:pid GROUP BY reaction_type");
                        $stmt_rc->execute([':pid'=>$post['post_id']]);
                        $rc = ['like'=>0,'love'=>0,'wow'=>0];
                        foreach($stmt_rc->fetchAll() as $row){ $rc[$row['reaction_type']] = (int)$row['cnt']; }
                        $stmt_my = $conn->prepare("SELECT reaction_type FROM post_reactions WHERE post_id=:pid AND emp_id=:eid");
                        $stmt_my->execute([':pid'=>$post['post_id'], ':eid'=>$_SESSION['emp_id']]);
                        $my_rc = $stmt_my->fetchColumn() ?: null;
                        $total_rc = array_sum($rc);
                        $emo_map = ['like'=>['👍','ถูกใจ','rlike'],'love'=>['❤️','รัก','rlove'],'wow'=>['😮','ว้าว','rwow']];
                        ?>
                        <div style="display:flex;align-items:center;gap:5px;padding:8px 0;border-top:1px solid #f1f5f9;border-bottom:1px solid #f1f5f9;flex-wrap:wrap;">
                            <?php foreach($emo_map as $type=>[$icon,$label,$cls]): $act = ($my_rc===$type)?'background:#f1f5f9;font-weight:800;':''; ?>
                            <button onclick="doReaction(<?php echo $post['post_id']; ?>,'<?php echo $type; ?>')"
                                    id="rbtn-<?php echo $post['post_id']; ?>-<?php echo $type; ?>"
                                    style="display:inline-flex;align-items:center;gap:3px;padding:5px 11px;border-radius:18px;border:1.5px solid #e2e8f0;background:#fff;font-size:.82rem;font-weight:700;cursor:pointer;font-family:'Sarabun',sans-serif;transition:all .15s;<?php echo $act; ?>">
                                <?php echo $icon; ?> <?php echo $label; ?>
                                <span id="rcnt-<?php echo $post['post_id']; ?>-<?php echo $type; ?>" style="font-size:.75rem;color:#94a3b8;"><?php echo $rc[$type]>0?$rc[$type]:''; ?></span>
                            </button>
                            <?php endforeach; ?>
                            <?php if($total_rc>0): ?>
                            <span id="rtotal-<?php echo $post['post_id']; ?>" style="font-size:.75rem;color:#94a3b8;margin-left:auto;"><?php echo $total_rc; ?> คน</span>
                            <?php else: ?>
                            <span id="rtotal-<?php echo $post['post_id']; ?>" style="font-size:.75rem;color:#94a3b8;margin-left:auto;display:none;"></span>
                            <?php endif; ?>
                            <!-- ปุ่มไปหน้า detail -->
                            <a href="post_detail.php?id=<?php echo $post['post_id']; ?>"
                               style="display:inline-flex;align-items:center;gap:5px;margin-left:6px;padding:5px 13px;background:#1e293b;color:#fff;border-radius:18px;font-size:.82rem;font-weight:700;text-decoration:none;">
                                <i class="fa-solid fa-comments"></i> คอมเมนต์ (<?php
                                    $stmt_cnt = $conn->prepare("SELECT COUNT(*) FROM replies WHERE post_id=:pid");
                                    $stmt_cnt->execute([':pid'=>$post['post_id']]);
                                    echo (int)$stmt_cnt->fetchColumn();
                                ?>)
                            </a>
                        </div>

                        <div class="reply-section">
                            <div class="comments-list">
                                <?php
                                $stmt_replies_prep->execute([':post_id' => $post['post_id']]);
                                $replies = $stmt_replies_prep->fetchAll();
                                $total_replies = count($replies);

                                // โชว์แค่ comment ล่าสุด 1 อัน
                                $show_replies = $total_replies > 0 ? [end($replies)] : [];

                                $avatar_colors = ['avatar-green', 'avatar-orange', 'avatar-blue'];
                                $idx = 0;

                                foreach($show_replies as $reply):
                                    $current_color = $avatar_colors[$idx % 3]; $idx++;
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
                    <span class="edited-tag" onclick="showEditHistory(<?php echo $history_json; ?>)" title="คลิกเพื่อดูข้อความเดิม">แก้ไขแล้ว</span>
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
            <input type="text" id="edit-input-<?php echo $reply['reply_id']; ?>" value="<?php echo htmlspecialchars($reply['reply_text']); ?>" class="form-input" style="padding:6px; margin-bottom:5px; width:100%;">
            <div style="display:flex;align-items:center;gap:6px;margin-bottom:6px;flex-wrap:wrap;">
                <label style="display:inline-flex;align-items:center;gap:4px;font-size:.78rem;font-weight:700;color:#475569;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:4px 9px;cursor:pointer;">
                    <i class="fa-solid fa-camera" style="color:#e11d48;"></i> แนบรูป
                    <input type="file" id="edit-img-input-<?php echo $reply['reply_id']; ?>" accept="image/*" style="display:none;"
                           onchange="previewEditImg(this,<?php echo $reply['reply_id']; ?>)">
                </label>
                <?php if(!empty($reply['reply_image'])): ?>
                <button type="button" style="font-size:.78rem;font-weight:700;color:#e11d48;background:#fff1f2;border:1px solid #fecaca;border-radius:6px;padding:4px 9px;cursor:pointer;font-family:'Sarabun',sans-serif;"
                        onclick="markDeleteEditImg(<?php echo $reply['reply_id']; ?>)">
                    <i class="fa-solid fa-trash-can"></i> ลบรูป
                </button>
                <?php endif; ?>
                <span id="edit-img-name-<?php echo $reply['reply_id']; ?>" style="font-size:.72rem;color:#64748b;"></span>
                <input type="hidden" id="edit-del-img-<?php echo $reply['reply_id']; ?>" value="0">
            </div>
            <?php if(!empty($reply['reply_image'])): ?>
            <img id="edit-img-preview-<?php echo $reply['reply_id']; ?>"
                 src="<?php echo htmlspecialchars($reply['reply_image']); ?>"
                 style="display:block;max-width:180px;max-height:120px;object-fit:cover;border-radius:8px;margin-bottom:6px;border:1.5px solid #e2e8f0;">
            <?php else: ?>
            <img id="edit-img-preview-<?php echo $reply['reply_id']; ?>" src=""
                 style="display:none;max-width:180px;max-height:120px;object-fit:cover;border-radius:8px;margin-bottom:6px;border:1.5px solid #e2e8f0;">
            <?php endif; ?>
            <div style="display:flex; gap:5px;">
                <button type="button" class="tab-item tab-success active" style="border:none; cursor:pointer; padding:4px 10px;" onclick="saveEdit(<?php echo $reply['reply_id']; ?>)">บันทึก</button>
                <button type="button" class="tab-item tab-inactive" style="border:none; cursor:pointer; padding:4px 10px;" onclick="cancelEdit(<?php echo $reply['reply_id']; ?>)">ยกเลิก</button>
            </div>
        </div>

        <?php if(!empty($reply['reply_image'])): ?>
            <div id="reply-img-<?php echo $reply['reply_id']; ?>"><img src="<?php echo htmlspecialchars($reply['reply_image']); ?>" class="chat-embedded-img js-lightbox-img" data-gallery='["<?php echo addslashes($reply['reply_image']); ?>"]' data-index="0"></div>
        <?php endif; ?>
    </div>
</div>
                                <?php endforeach; ?>
                                <?php if($total_replies > 1): ?>
                                <a href="post_detail.php?id=<?php echo $post['post_id']; ?>"
                                   style="display:block;text-align:center;padding:7px;font-size:.8rem;font-weight:700;color:#64748b;text-decoration:none;background:#f8fafc;border-radius:8px;margin-top:6px;border:1px solid #e2e8f0;">
                                    <i class="fa-solid fa-angles-down"></i> ดูทั้งหมด <?php echo $total_replies; ?> ความคิดเห็น
                                </a>
                                <?php endif; ?>
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
                                                    <input type="file" name="reply_image" accept="image/*" class="hidden-file-input"
                                                           onchange="displayFileName(this,'file-preview-<?php echo $post['post_id']; ?>')">
                                                </label>
                                            </div>
                                            <button type="submit" class="btn-chat-send"><i class="fa-solid fa-paper-plane"></i></button>
                                        </div>
                                        <div class="file-status-preview" id="file-preview-<?php echo $post['post_id']; ?>" style="display:none;margin-top:6px;padding-left:48px;"></div>
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

<?php if (!$is_ajax): ?>
        </div> <!-- feed-container -->
        
        <?php if (isset($has_more) && $has_more): ?>
        <div class="load-more-wrapper" style="text-align: center; margin: 25px 0;">
            <button id="btn-load-more" class="btn-toggle-form" style="background-color: #1e293b; color: white; display: inline-flex; align-items: center; gap: 8px; border: none; padding: 10px 20px; border-radius: 20px; font-weight: 700; cursor: pointer;" onclick="loadMorePosts()">
                <i class="fa-solid fa-spinner fa-spin" id="load-more-spinner" style="display:none;"></i>
                <span>โหลดเพิ่มเติม</span>
            </button>
        </div>
        <?php endif; ?>

    </div> <!-- main-container -->
</div> <!-- content-wrapper -->
<?php endif; ?>