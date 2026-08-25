<?php
/**
 * post_detail.php — หน้าดูโพสต์แบบเต็ม พร้อม reaction และ comment ทั้งหมด
 */
date_default_timezone_set('Asia/Bangkok');
require_once 'config.php';
session_start();

if(!isset($_SESSION['emp_id'])){ header("location: login.php"); exit; }

$post_id = intval($_GET['id'] ?? 0);
if($post_id <= 0){ header("location: index.php"); exit; }

// ── ดึงข้อมูลโพสต์ ──
$stmt = $conn->prepare("SELECT p.*, e.emp_name FROM posts p JOIN employee e ON p.emp_id=e.emp_id WHERE p.post_id=:pid");
$stmt->execute([':pid'=>$post_id]);
$post = $stmt->fetch();
if(!$post){ header("location: index.php"); exit; }

// ── ดึง replies ──
$stmt_r = $conn->prepare("SELECT r.*, e.emp_name, e.emp_unit FROM replies r JOIN employee e ON r.emp_id=e.emp_id WHERE r.post_id=:pid ORDER BY r.created_at ASC");
$stmt_r->execute([':pid'=>$post_id]);
$replies = $stmt_r->fetchAll();

// ── ดึง reply logs ──
$stmt_log = $conn->prepare("SELECT old_text, old_created_at FROM reply_logs WHERE reply_id=:rid ORDER BY log_id DESC");

// ── นับ reactions ──
$stmt_rc = $conn->prepare("SELECT reaction_type, COUNT(*) as cnt FROM post_reactions WHERE post_id=:pid GROUP BY reaction_type");
$stmt_rc->execute([':pid'=>$post_id]);
$reaction_counts = ['like'=>0,'love'=>0,'wow'=>0];
foreach($stmt_rc->fetchAll() as $row){ $reaction_counts[$row['reaction_type']] = (int)$row['cnt']; }

// reaction ของ user ปัจจุบัน
$stmt_my = $conn->prepare("SELECT reaction_type FROM post_reactions WHERE post_id=:pid AND emp_id=:eid");
$stmt_my->execute([':pid'=>$post_id, ':eid'=>$_SESSION['emp_id']]);
$my_reaction = $stmt_my->fetchColumn() ?: null;

$is_admin = ($_SESSION['emp_level'] === 'a');
$thai_months=['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
$dt = strtotime($post['created_at']);
$date_th = (int)date('d',$dt).' '.$thai_months[(int)date('m',$dt)].' '.((int)date('Y',$dt)+543).' เวลา '.date('H:i',$dt).' น.';

function get_av($name){ return mb_substr(trim($name),0,2,'UTF-8'); }

include 'includes/nav_u_header.php';
?>
<title>รายละเอียดโพสต์ - TIS SMART FIELD</title>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
*{box-sizing:border-box;}
body{font-family:'Sarabun',sans-serif;background:#f1f5f9;margin:0;}
.pw{max-width:700px;margin:20px auto;padding:0 14px 80px;}

/* ── back button ── */
.btn-back{display:inline-flex;align-items:center;gap:6px;color:#64748b;text-decoration:none;font-weight:700;font-size:.88rem;margin-bottom:14px;padding:7px 13px;background:#fff;border-radius:8px;border:1px solid #e2e8f0;}
.btn-back:hover{background:#f8fafc;color:#1e293b;}

/* ── post card ── */
.post-card{background:#fff;border-radius:14px;border:.5px solid #e2e8f0;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,.06);margin-bottom:16px;}
.post-card-border{border-left:5px solid #e11d48;}
.post-card-border.success{border-left-color:#10b981;}
.post-header{padding:16px 18px 0;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;}
.post-author{font-weight:700;color:#1e293b;font-size:1rem;}
.post-author-sub{font-size:.8rem;color:#64748b;margin-top:2px;}
.post-time{font-size:.75rem;color:#94a3b8;margin-top:3px;}
.status-badge{padding:4px 12px;border-radius:12px;font-size:.78rem;font-weight:700;}
.status-pending{background:#fee2e2;color:#991b1b;}
.status-success{background:#d1fae5;color:#065f46;}

.post-body{padding:14px 18px;}
.meta-row{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;}
.meta-chip{background:#f1f5f9;color:#1e293b;padding:5px 11px;border-radius:6px;font-size:.82rem;font-weight:600;display:inline-flex;align-items:center;gap:5px;}
.problem-box{background:#fff1f2;border:1px solid #ffe4e6;padding:11px 14px;border-radius:9px;margin-bottom:12px;}
.problem-box strong{color:#991b1b;font-size:.88rem;}
.prob-tags{display:flex;flex-wrap:wrap;gap:6px;margin-top:6px;}
.prob-tag{padding:3px 10px;border-radius:5px;font-size:.82rem;font-weight:700;display:inline-flex;align-items:center;gap:4px;}
.prob-tag-1{background:#fee2e2;color:#991b1b;}
.prob-tag-2{background:#fff7ed;color:#9a3412;}
.prob-tag-3{background:#fef9c3;color:#713f12;}
.post-text-body{color:#334155;font-size:.95rem;line-height:1.6;margin-bottom:12px;}
.img-gallery{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:8px;margin-bottom:14px;}
.img-gallery img{width:100%;height:150px;object-fit:cover;border-radius:9px;border:1px solid #e2e8f0;cursor:pointer;transition:transform .15s;}
.img-gallery img:hover{transform:scale(1.03);}

/* ── reactions ── */
.reaction-bar{display:flex;align-items:center;gap:6px;padding:10px 18px;border-top:1px solid #f1f5f9;border-bottom:1px solid #f1f5f9;}
.reaction-btn{display:inline-flex;align-items:center;gap:4px;padding:7px 14px;border-radius:20px;border:1.5px solid #e2e8f0;background:#fff;font-size:.85rem;font-weight:700;cursor:pointer;font-family:'Sarabun',sans-serif;transition:all .15s;color:#64748b;}
.reaction-btn:hover{background:#f8fafc;}
.reaction-btn.active-like{background:#eff6ff;border-color:#93c5fd;color:#2563eb;}
.reaction-btn.active-love{background:#fff1f2;border-color:#fca5a5;color:#e11d48;}
.reaction-btn.active-wow{background:#fffbeb;border-color:#fcd34d;color:#d97706;}
.reaction-count{font-size:.75rem;color:#94a3b8;margin-left:auto;}

/* ── comment section ── */
.comments-section{padding:14px 18px;}
.comments-title{font-weight:700;font-size:.88rem;color:#1e293b;margin-bottom:14px;display:flex;align-items:center;gap:6px;}
.comments-title i{color:#94a3b8;}
.comments-list{display:flex;flex-direction:column;gap:14px;margin-bottom:18px;}

.chat-row{display:flex;gap:10px;align-items:flex-start;}
.chat-avatar{width:36px;height:36px;border-radius:50%;color:#fff;display:flex;align-items:center;justify-content:center;font-size:.82rem;font-weight:700;flex-shrink:0;}
.avatar-green{background:#10b981;} .avatar-orange{background:#f59e0b;} .avatar-blue{background:#3b82f6;}
.chat-content-box{flex:1;background:#f8fafc;border:1px solid #e2e8f0;padding:10px 14px;border-radius:0 14px 14px 14px;}
.chat-info-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:5px;flex-wrap:wrap;gap:4px;}
.chat-user-name{font-weight:700;color:#1e293b;font-size:.88rem;}
.chat-user-unit{font-weight:400;color:#94a3b8;font-size:.8rem;}
.chat-timestamp{font-size:.75rem;color:#94a3b8;display:inline-flex;align-items:center;gap:5px;flex-wrap:wrap;}
.btn-edit-reply{background:none;border:none;color:#94a3b8;cursor:pointer;padding:2px 5px;font-size:.85rem;border-radius:4px;}
.btn-edit-reply:hover{background:#e2e8f0;color:#475569;}
.chat-text{color:#334155;font-size:.9rem;line-height:1.5;margin:0;}
.chat-embedded-img{max-width:260px;width:100%;border-radius:8px;margin-top:8px;border:1px solid #e2e8f0;cursor:pointer;}
.edited-tag{font-size:.72rem;color:#94a3b8;cursor:help;text-decoration:underline;font-style:italic;}

/* edit box */
.edit-box{display:none;margin-top:8px;}
.edit-input{width:100%;padding:7px 10px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:.88rem;font-family:'Sarabun',sans-serif;margin-bottom:5px;}
.edit-input:focus{outline:none;border-color:#e11d48;}
.edit-actions{display:flex;gap:5px;flex-wrap:wrap;align-items:center;}
.btn-save-edit{padding:4px 12px;background:#10b981;color:#fff;border:none;border-radius:6px;font-size:.78rem;font-weight:700;cursor:pointer;font-family:'Sarabun',sans-serif;}
.btn-cancel-edit{padding:4px 12px;background:#f1f5f9;color:#64748b;border:none;border-radius:6px;font-size:.78rem;font-weight:700;cursor:pointer;font-family:'Sarabun',sans-serif;}
.edit-img-label{display:inline-flex;align-items:center;gap:4px;font-size:.75rem;font-weight:700;color:#475569;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:3px 8px;cursor:pointer;}
.btn-del-img{font-size:.75rem;font-weight:700;color:#e11d48;background:#fff1f2;border:1px solid #fecaca;border-radius:6px;padding:3px 8px;cursor:pointer;font-family:'Sarabun',sans-serif;}
.edit-img-preview{display:none;max-width:160px;max-height:110px;object-fit:cover;border-radius:7px;margin-top:5px;border:1.5px solid #e2e8f0;}

/* reply form */
.reply-form-wrap{border-top:1px solid #f1f5f9;padding-top:14px;}
.chat-input-wrapper{display:flex;gap:8px;align-items:center;}
.chat-input-container{flex:1;display:flex;align-items:center;background:#f1f5f9;border-radius:22px;padding:4px 14px;border:1px solid #e2e8f0;}
.chat-main-field{flex:1;background:transparent;border:none;padding:9px 0;font-size:.9rem;font-family:'Sarabun',sans-serif;outline:none;}
.file-attach-label{color:#94a3b8;font-size:1.1rem;cursor:pointer;padding:6px;display:flex;}
.hidden-file-input{display:none;}
.btn-chat-send{width:40px;height:40px;border-radius:11px;background:#e11d48;color:#fff;border:none;display:flex;align-items:center;justify-content:center;font-size:1rem;cursor:pointer;flex-shrink:0;}
.file-preview-wrap{margin-top:6px;display:none;}
.file-preview-wrap img{width:56px;height:56px;object-fit:cover;border-radius:7px;border:1.5px solid #e2e8f0;vertical-align:middle;}
.file-preview-name{font-size:.75rem;color:#10b981;font-weight:700;margin-left:6px;vertical-align:middle;}

/* admin controls */
.admin-bar{display:flex;align-items:center;gap:8px;padding:10px 18px;background:#f8fafc;border-top:1px solid #f1f5f9;flex-wrap:wrap;}
.btn-status{padding:6px 14px;border:none;border-radius:7px;font-size:.8rem;font-weight:700;cursor:pointer;font-family:'Sarabun',sans-serif;display:inline-flex;align-items:center;gap:5px;}
.btn-del-post{padding:6px 10px;background:#fee2e2;color:#e11d48;border:none;border-radius:7px;font-size:.8rem;cursor:pointer;font-family:'Sarabun',sans-serif;}

.no-comments{text-align:center;padding:24px;color:#94a3b8;font-size:.88rem;}

@media(max-width:600px){
    .img-gallery{grid-template-columns:1fr 1fr;}
    .reaction-bar{flex-wrap:wrap;}
}
</style>
<div class="content-wrapper">
<div class="pw">

    <a href="index.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> กลับหน้าหลัก</a>

    <div class="post-card post-card-border <?php echo $post['job_status']==='success'?'success':''; ?>">

        <!-- Header -->
        <div class="post-header">
            <div>
                <div class="post-author"><?php echo htmlspecialchars($post['emp_name']); ?> <span style="font-weight:400;color:#64748b;font-size:.85rem;">(ออฟฟิศกลาง)</span></div>
                <div class="post-time"><i class="fa-regular fa-clock"></i> <?php echo $date_th; ?></div>
            </div>
            <span class="status-badge <?php echo $post['job_status']==='success'?'status-success':'status-pending'; ?>" data-status-badge>
                <?php echo $post['job_status']==='success'?'ดำเนินการแล้ว':'รอดำเนินการ'; ?>
            </span>
        </div>

        <!-- Body -->
        <div class="post-body">
            <div class="meta-row">
                <span class="meta-chip"><i class="fa-solid fa-location-dot" style="color:#e11d48;"></i> <?php echo htmlspecialchars($post['target_unit']); ?></span>
                <span class="meta-chip"><i class="fa-solid fa-truck" style="color:#10b981;"></i> ทะเบียน: <?php echo htmlspecialchars($post['truck_number']); ?></span>
                <?php if(!empty($post['harvester_number'])): ?>
                <span class="meta-chip"><i class="fa-solid fa-tractor" style="color:#06b6d4;"></i> รถตัด: <?php echo htmlspecialchars($post['harvester_number']); ?></span>
                <?php endif; ?>
            </div>

            <div class="problem-box">
                <strong><i class="fa-solid fa-triangle-exclamation"></i> ปัญหาที่พบ:</strong>
                <div class="prob-tags">
                    <?php if(!empty($post['problem_detail'])): ?><span class="prob-tag prob-tag-1"><i class="fa-solid fa-circle-exclamation" style="font-size:.75rem;"></i><?php echo htmlspecialchars($post['problem_detail']); ?></span><?php endif; ?>
                    <?php if(!empty($post['problem_detail_2'])): ?><span class="prob-tag prob-tag-2"><i class="fa-solid fa-circle-exclamation" style="font-size:.75rem;"></i><?php echo htmlspecialchars($post['problem_detail_2']); ?></span><?php endif; ?>
                    <?php if(!empty($post['problem_detail_3'])): ?><span class="prob-tag prob-tag-3"><i class="fa-solid fa-circle-exclamation" style="font-size:.75rem;"></i><?php echo htmlspecialchars($post['problem_detail_3']); ?></span><?php endif; ?>
                </div>
            </div>

            <?php if(!empty($post['post_text'])): ?>
            <div class="post-text-body"><?php echo nl2br(htmlspecialchars($post['post_text'])); ?></div>
            <?php endif; ?>

            <div class="img-gallery">
                <?php
                $gallery_imgs = array_filter([$post['post_image']??'',$post['post_image_2']??'',$post['post_image_3']??'']);
                $gallery_json = json_encode(array_values($gallery_imgs), JSON_UNESCAPED_UNICODE);
                $gi = 0;
                foreach($gallery_imgs as $img):
                ?>
                <img src="<?php echo htmlspecialchars($img); ?>"
                     class="js-lightbox-img"
                     data-gallery='<?php echo htmlspecialchars($gallery_json); ?>'
                     data-index="<?php echo $gi++; ?>">
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Reaction Bar -->
        <div class="reaction-bar">
            <?php
            $emo = ['like'=>['icon'=>'👍','label'=>'ถูกใจ','cls'=>'active-like'],
                    'love'=>['icon'=>'❤️','label'=>'รัก',  'cls'=>'active-love'],
                    'wow' =>['icon'=>'😮','label'=>'ว้าว', 'cls'=>'active-wow']];
            foreach($emo as $type=>$e):
                $cnt  = $reaction_counts[$type];
                $act  = ($my_reaction===$type) ? $e['cls'] : '';
            ?>
            <button class="reaction-btn <?php echo $act; ?>" id="rbtn-<?php echo $type; ?>"
                    onclick="doReaction('<?php echo $type; ?>')">
                <?php echo $e['icon']; ?> <?php echo $e['label']; ?>
                <span id="rcnt-<?php echo $type; ?>"><?php echo $cnt>0?$cnt:''; ?></span>
            </button>
            <?php endforeach; ?>
            <?php $total_react = array_sum($reaction_counts); ?>
            <?php if($total_react>0): ?>
            <span class="reaction-count" id="total-react"><?php echo $total_react; ?> คน</span>
            <?php else: ?>
            <span class="reaction-count" id="total-react" style="display:none;"></span>
            <?php endif; ?>
        </div>

        <!-- Admin Bar -->
        <?php if($is_admin): ?>
        <div class="admin-bar">
            <button class="btn-status" id="status-btn-<?php echo $post_id; ?>"
                    style="background:<?php echo $post['job_status']==='pending'?'#10b981':'#e11d48'; ?>;color:#fff;"
                    onclick="toggleStatus(<?php echo $post_id; ?>,'<?php echo $post['job_status']; ?>')">
                <?php if($post['job_status']==='pending'): ?>
                    <i class="fa-solid fa-circle-check"></i> ยืนยันดำเนินการแล้ว
                <?php else: ?>
                    <i class="fa-solid fa-rotate-left"></i> เปลี่ยนเป็นรอดำเนินการ
                <?php endif; ?>
            </button>
            <button class="btn-del-post" onclick="deletePost(<?php echo $post_id; ?>)">
                <i class="fa-solid fa-trash-can"></i> ลบโพสต์
            </button>
        </div>
        <?php endif; ?>

        <!-- Comments -->
        <div class="comments-section">
            <div class="comments-title"><i class="fa-solid fa-comments"></i> ความคิดเห็น (<?php echo count($replies); ?> รายการ)</div>

            <div class="comments-list" id="comments-list">
            <?php
            $av_colors = ['avatar-green','avatar-orange','avatar-blue'];
            $idx = 0;
            foreach($replies as $reply):
                $av  = $av_colors[$idx % 3]; $idx++;
                $rid = $reply['reply_id'];
                $stmt_log->execute([':rid'=>$rid]);
                $logs      = $stmt_log->fetchAll();
                $is_edited = count($logs) > 0;
                $is_mine   = ((string)$reply['emp_id'] === (string)$_SESSION['emp_id']);
            ?>
            <div class="chat-row" id="reply-row-<?php echo $rid; ?>">
                <div class="chat-avatar <?php echo $av; ?>"><?php echo htmlspecialchars(get_av($reply['emp_name'])); ?></div>
                <div class="chat-content-box">
                    <div class="chat-info-header">
                        <div class="chat-user-name"><?php echo htmlspecialchars($reply['emp_name']); ?> <span class="chat-user-unit">(หน่วย<?php echo htmlspecialchars($reply['emp_unit']); ?>)</span></div>
                        <div class="chat-timestamp">
                            <?php echo date('H:i น.',strtotime($reply['created_at'])); ?>
                            <?php if($is_edited):
                                $h_lines = ["——— ประวัติข้อความเก่า ———"];
                                foreach($logs as $li=>$lg){ $h_lines[] = ($li+1).". ".$lg['old_text']." (".date('H:i น.',strtotime($lg['old_created_at'])).")"; }
                                $hj = json_encode(implode("\n",$h_lines), JSON_UNESCAPED_UNICODE);
                            ?><span class="edited-tag" onclick="showEditHistory(<?php echo $hj; ?>)">แก้ไขแล้ว</span><?php endif; ?>
                            <?php if($is_mine): ?>
                            <button class="btn-edit-reply" onclick="enableEditMode(<?php echo $rid; ?>)" title="แก้ไข"><i class="fa-solid fa-pen-to-square"></i></button>
                            <?php endif; ?>
                            <?php if($is_admin): ?>
                            <button class="btn-edit-reply" style="color:#e11d48;" onclick="deleteReply(<?php echo $rid; ?>,<?php echo $post_id; ?>)" title="ลบ"><i class="fa-solid fa-trash-can"></i></button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <p class="chat-text" id="reply-text-<?php echo $rid; ?>"><?php echo nl2br(htmlspecialchars($reply['reply_text'])); ?></p>

                    <!-- Edit Box -->
                    <div class="edit-box" id="edit-box-<?php echo $rid; ?>">
                        <input type="text" class="edit-input" id="edit-input-<?php echo $rid; ?>" value="<?php echo htmlspecialchars($reply['reply_text']); ?>">
                        <div class="edit-actions">
                            <label class="edit-img-label">
                                <i class="fa-solid fa-camera" style="color:#e11d48;"></i> แนบรูป
                                <input type="file" id="edit-img-input-<?php echo $rid; ?>" accept="image/*" style="display:none;" onchange="previewEditImg(this,<?php echo $rid; ?>)">
                            </label>
                            <?php if(!empty($reply['reply_image'])): ?>
                            <button class="btn-del-img" onclick="markDeleteEditImg(<?php echo $rid; ?>)"><i class="fa-solid fa-trash-can"></i> ลบรูป</button>
                            <?php endif; ?>
                            <input type="hidden" id="edit-del-img-<?php echo $rid; ?>" value="0">
                            <button class="btn-save-edit" onclick="saveEdit(<?php echo $rid; ?>)"><i class="fa-solid fa-floppy-disk"></i> บันทึก</button>
                            <button class="btn-cancel-edit" onclick="cancelEdit(<?php echo $rid; ?>)">ยกเลิก</button>
                        </div>
                        <?php if(!empty($reply['reply_image'])): ?>
                        <img id="edit-img-preview-<?php echo $rid; ?>" src="<?php echo htmlspecialchars($reply['reply_image']); ?>" class="edit-img-preview" style="display:block;">
                        <?php else: ?>
                        <img id="edit-img-preview-<?php echo $rid; ?>" src="" class="edit-img-preview">
                        <?php endif; ?>
                        <span id="edit-img-name-<?php echo $rid; ?>" style="font-size:.72rem;color:#64748b;display:block;margin-top:3px;"></span>
                    </div>

                    <?php if(!empty($reply['reply_image'])): ?>
                    <div id="reply-img-<?php echo $rid; ?>">
                        <img src="<?php echo htmlspecialchars($reply['reply_image']); ?>" class="chat-embedded-img js-lightbox-img" data-gallery='["<?php echo htmlspecialchars($reply['reply_image']); ?>"]' data-index="0" style="cursor:pointer;">
                    </div>
                    <?php else: ?>
                    <div id="reply-img-<?php echo $rid; ?>"></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if(empty($replies)): ?>
            <div class="no-comments"><i class="fa-regular fa-comment" style="font-size:1.8rem;display:block;margin-bottom:6px;"></i>ยังไม่มีความคิดเห็น</div>
            <?php endif; ?>
            </div>

            <!-- Reply Form -->
            <?php
            $is_assigned = (!empty($_SESSION['emp_unit']) && strpos($post['target_unit'], $_SESSION['emp_unit']) !== false);
            if($is_assigned || $is_admin):
                $my_av = $is_admin ? 'avatar-blue' : 'avatar-green';
            ?>
            <div class="reply-form-wrap">
                <form class="replyForm" id="replyForm">
                    <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                    <div class="chat-input-wrapper">
                        <div class="chat-avatar <?php echo $my_av; ?>" style="width:36px;height:36px;font-size:.8rem;"><?php echo htmlspecialchars(get_av($_SESSION['emp_name'])); ?></div>
                        <div class="chat-input-container">
                            <input type="text" name="reply_text" placeholder="พิมพ์รายงานความคืบหน้า..." class="chat-main-field" autocomplete="off" required>
                            <label class="file-attach-label" title="แนบรูป">
                                <i class="fa-solid fa-paperclip"></i>
                                <input type="file" name="reply_image" accept="image/*" class="hidden-file-input" onchange="displayFileName(this)">
                            </label>
                        </div>
                        <button type="submit" class="btn-chat-send"><i class="fa-solid fa-paper-plane"></i></button>
                    </div>
                    <div class="file-preview-wrap" id="file-preview-wrap"></div>
                </form>
            </div>
            <?php else: ?>
            <div style="font-size:.85rem;color:#94a3b8;font-style:italic;background:#f8fafc;padding:10px;border-radius:6px;border:1px dashed #e2e8f0;text-align:center;">
                <i class="fa-solid fa-lock"></i> เฉพาะพนักงานสังกัดหน่วย "<?php echo htmlspecialchars($post['target_unit']); ?>" เท่านั้น
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>
</div>

<?php include 'includes/nav_u_footer.php'; ?>

<script>
const POST_ID = <?php echo $post_id; ?>;
let myReaction = <?php echo json_encode($my_reaction); ?>;

// ── Toast / Confirm ──
function showToast(icon, title, timer=2800){
    Swal.fire({toast:true,position:'top-end',icon,title,showConfirmButton:false,timer,timerProgressBar:true,customClass:{popup:'sa2-th'}});
}
function showConfirm(title, text, confirmText, confirmColor='#e11d48'){
    return Swal.fire({title,text,icon:'warning',showCancelButton:true,confirmButtonText:confirmText,cancelButtonText:'ยกเลิก',confirmButtonColor:confirmColor,cancelButtonColor:'#64748b',reverseButtons:true,customClass:{popup:'sa2-th'}});
}

// ── Reactions ──
function doReaction(type){
    const fd = new FormData();
    fd.append('post_id', POST_ID);
    fd.append('reaction_type', type);
    fetch('post_reaction.php', {method:'POST', body:fd})
    .then(r=>r.json())
    .then(data=>{
        if(data.status !== 'success') return;
        // อัปเดต UI
        ['like','love','wow'].forEach(t=>{
            const btn  = document.getElementById('rbtn-'+t);
            const cnt  = document.getElementById('rcnt-'+t);
            const cls  = {like:'active-like',love:'active-love',wow:'active-wow'}[t];
            btn.classList.remove(cls);
            cnt.textContent = data.counts[t] > 0 ? data.counts[t] : '';
        });
        myReaction = data.my_reaction;
        if(myReaction){
            const cls = {like:'active-like',love:'active-love',wow:'active-wow'}[myReaction];
            document.getElementById('rbtn-'+myReaction).classList.add(cls);
        }
        const total = Object.values(data.counts).reduce((a,b)=>a+b,0);
        const totalEl = document.getElementById('total-react');
        if(total > 0){ totalEl.textContent = total+' คน'; totalEl.style.display=''; }
        else { totalEl.style.display='none'; }
    })
    .catch(()=>showToast('error','เกิดข้อผิดพลาด'));
}

// ── Edit Comment ──
function enableEditMode(rid){
    document.getElementById('reply-text-'+rid).style.display='none';
    document.getElementById('edit-box-'+rid).style.display='block';
    document.getElementById('edit-input-'+rid).focus();
}
function cancelEdit(rid){
    document.getElementById('reply-text-'+rid).style.display='';
    document.getElementById('edit-box-'+rid).style.display='none';
}
function saveEdit(rid){
    const val = document.getElementById('edit-input-'+rid).value;
    if(!val.trim()){ showToast('warning','กรุณากรอกข้อความก่อนบันทึก'); return; }
    const fd = new FormData();
    fd.append('reply_id', rid);
    fd.append('reply_text', val);
    const imgInput = document.getElementById('edit-img-input-'+rid);
    if(imgInput && imgInput.files[0]) fd.append('reply_image', imgInput.files[0]);
    const delFlag = document.getElementById('edit-del-img-'+rid);
    if(delFlag && delFlag.value==='1') fd.append('delete_image','1');

    fetch('reply_edit.php', {method:'POST', body:fd})
    .then(r=>r.json())
    .then(data=>{
        if(data.status==='success'){
            document.getElementById('reply-text-'+rid).innerHTML = data.updated_text || val.replace(/\n/g,'<br>');
            document.getElementById('reply-text-'+rid).style.display='';
            document.getElementById('edit-box-'+rid).style.display='none';
            const imgWrap = document.getElementById('reply-img-'+rid);
            if(imgWrap) imgWrap.innerHTML = data.img_html || '';
            const row = document.getElementById('reply-row-'+rid);
            if(row && !row.querySelector('.edited-tag')){
                const ts = row.querySelector('.chat-timestamp');
                if(ts){ const tag=document.createElement('span'); tag.className='edited-tag'; tag.textContent='แก้ไขแล้ว'; ts.appendChild(tag); }
            }
            showToast('success','บันทึกการแก้ไขแล้ว');
        } else { showToast('error', data.message||'บันทึกไม่สำเร็จ'); }
    })
    .catch(()=>showToast('error','ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้'));
}
function previewEditImg(input, rid){
    const prev = document.getElementById('edit-img-preview-'+rid);
    const name = document.getElementById('edit-img-name-'+rid);
    const del  = document.getElementById('edit-del-img-'+rid);
    if(del) del.value='0';
    if(!input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => { prev.src=e.target.result; prev.style.display='block'; if(name) name.textContent=input.files[0].name.substring(0,22)+' ('+Math.round(input.files[0].size/1024)+' KB)'; };
    reader.readAsDataURL(input.files[0]);
}
function markDeleteEditImg(rid){
    const del=document.getElementById('edit-del-img-'+rid);
    if(del) del.value='1';
    const prev=document.getElementById('edit-img-preview-'+rid);
    if(prev){ prev.src=''; prev.style.display='none'; }
    const inp=document.getElementById('edit-img-input-'+rid);
    if(inp) inp.value='';
    const name=document.getElementById('edit-img-name-'+rid);
    if(name) name.textContent='';
    showToast('info','จะลบรูปเมื่อกดบันทึก');
}

// ── Delete Reply ──
function deleteReply(rid, pid){
    showConfirm('ลบความคิดเห็น?','จะถูกลบถาวร','ลบเลย')
    .then(r=>{ if(!r.isConfirmed) return;
        const fd=new FormData(); fd.append('reply_id',rid); fd.append('action','delete');
        fetch('reply_action.php',{method:'POST',body:fd}).then(r=>r.json()).then(data=>{
            if(data.status==='success'){
                const row=document.getElementById('reply-row-'+rid);
                if(row){ row.style.transition='opacity .25s'; row.style.opacity='0'; setTimeout(()=>row.remove(),260); }
                showToast('success','ลบความคิดเห็นแล้ว');
            } else showToast('error',data.message||'เกิดข้อผิดพลาด');
        });
    });
}

// ── Delete Post ──
function deletePost(pid){
    showConfirm('ยืนยันลบโพสต์?','โพสต์และ comment ทั้งหมดจะถูกลบถาวร','ลบเลย')
    .then(r=>{ if(!r.isConfirmed) return;
        const fd=new FormData(); fd.append('post_id',pid);
        fetch('post_delete.php',{method:'POST',body:fd}).then(r=>r.json()).then(data=>{
            if(data.status==='success'){ showToast('success','ลบโพสต์แล้ว'); setTimeout(()=>location.href='index.php',1200); }
            else showToast('error',data.message||'ลบไม่สำเร็จ');
        });
    });
}

// ── Toggle Status ──
function toggleStatus(pid, current){
    const newSt = current==='pending'?'success':'pending';
    const msg   = newSt==='success'?'ยืนยันว่าดำเนินการเรียบร้อยแล้ว?':'เปลี่ยนกลับเป็นรอดำเนินการ?';
    showConfirm('เปลี่ยนสถานะ?',msg,newSt==='success'?'ยืนยัน':'เปลี่ยน',newSt==='success'?'#10b981':'#e11d48')
    .then(r=>{ if(!r.isConfirmed) return;
        const fd=new FormData(); fd.append('post_id',pid); fd.append('job_status',newSt);
        fetch('post_status.php',{method:'POST',body:fd}).then(r=>r.json()).then(data=>{
            if(data.status==='success'){
                const badge=document.querySelector('[data-status-badge]');
                if(badge){ badge.textContent=newSt==='success'?'ดำเนินการแล้ว':'รอดำเนินการ'; badge.style.background=newSt==='success'?'#d1fae5':'#fee2e2'; badge.style.color=newSt==='success'?'#065f46':'#991b1b'; }
                const btn=document.getElementById('status-btn-'+pid);
                if(btn){ btn.style.background=newSt==='success'?'#e11d48':'#10b981'; btn.setAttribute('onclick',"toggleStatus("+pid+",'"+newSt+"')"); btn.innerHTML=newSt==='success'?'<i class="fa-solid fa-rotate-left"></i> เปลี่ยนเป็นรอดำเนินการ':'<i class="fa-solid fa-circle-check"></i> ยืนยันดำเนินการแล้ว'; }
                showToast('success','เปลี่ยนสถานะแล้ว');
            } else showToast('error',data.message);
        });
    });
}

// ── Reply Form Submit ──
document.getElementById('replyForm').addEventListener('submit', function(e){
    e.preventDefault();
    const btn = this.querySelector('.btn-chat-send');
    if(btn) btn.disabled=true;
    const fd = new FormData(this);
    fetch('reply_action.php',{method:'POST',body:fd})
    .then(r=>r.json())
    .then(data=>{
        if(data.status==='success'){
            const list=document.getElementById('comments-list');
            const empty=list.querySelector('.no-comments');
            if(empty) empty.remove();
            if(data.html) list.insertAdjacentHTML('beforeend',data.html);
            this.querySelector('[name="reply_text"]').value='';
            const fi=this.querySelector('[name="reply_image"]'); if(fi) fi.value='';
            document.getElementById('file-preview-wrap').style.display='none';
            document.getElementById('file-preview-wrap').innerHTML='';
            // อัปเดตนับ
            const title=document.querySelector('.comments-title');
            if(title){ const rows=list.querySelectorAll('.chat-row'); title.innerHTML='<i class="fa-solid fa-comments"></i> ความคิดเห็น ('+rows.length+' รายการ)'; }
            showToast('success','ส่งความคิดเห็นแล้ว');
        } else showToast('error',data.message||'ส่งไม่สำเร็จ');
    })
    .catch(()=>showToast('error','ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้'))
    .finally(()=>{ if(btn) btn.disabled=false; });
});

// ── Display filename + preview ──
function displayFileName(input){
    const wrap=document.getElementById('file-preview-wrap');
    if(!input.files || !input.files[0]){ wrap.style.display='none'; wrap.innerHTML=''; return; }
    const reader=new FileReader();
    reader.onload=e=>{
        wrap.innerHTML=`<img src="${e.target.result}" style="width:56px;height:56px;object-fit:cover;border-radius:7px;border:1.5px solid #e2e8f0;vertical-align:middle;"> <span class="file-preview-name"><i class="fa-solid fa-check-circle"></i> ${input.files[0].name.substring(0,22)} (${Math.round(input.files[0].size/1024)} KB)</span>`;
        wrap.style.display='block';
    };
    reader.readAsDataURL(input.files[0]);
}

// ── Edit History ──
function showEditHistory(text){
    Swal.fire({title:'ประวัติการแก้ไข',html:'<pre style="text-align:left;font-family:Sarabun,sans-serif;font-size:.85rem;white-space:pre-wrap;line-height:1.7;">'+text.replace(/</g,'&lt;')+'</pre>',confirmButtonText:'ปิด',confirmButtonColor:'#1e293b',customClass:{popup:'sa2-th'}});
}
</script>
</body>
</html>
