<?php
/**
 * index.php - เวอร์ชันเพิ่มระบบลบโพสต์แอดมิน + แก้ไขแชทคอมเมนต์พนักงาน + ประวัติ Log
 */
require_once 'config.php';
session_start();

if(!isset($_SESSION["emp_id"])){
    header("location: login.php");
    exit;
}

$search_date = isset($_GET['search_date']) ? $_GET['search_date'] : date('Y-m-d');
$status_tab = isset($_GET['status_tab']) ? $_GET['status_tab'] : 'all';

$sql = "SELECT p.*, e.emp_name 
        FROM posts p 
        JOIN employee e ON p.emp_id = e.emp_id 
        WHERE p.crop_year = :crop_year 
        AND DATE(p.created_at) = :search_date";

if ($status_tab == 'pending') { $sql .= " AND p.job_status = 'pending'"; } 
elseif ($status_tab == 'success') { $sql .= " AND p.job_status = 'success'"; }

$sql .= " ORDER BY p.created_at DESC";

$posts = [];
if($stmt = $conn->prepare($sql)){
    $stmt->bindParam(":crop_year", $_SESSION["crop_year"], PDO::PARAM_STR);
    $stmt->bindParam(":search_date", $search_date, PDO::PARAM_STR);
    $stmt->execute();
    $posts = $stmt->fetchAll();
}

$zones = [];
$sql_zones = "SELECT * FROM zones ORDER BY zone_id ASC";
if($stmt_zones = $conn->prepare($sql_zones)){
    $stmt_zones->execute();
    $zones = $stmt_zones->fetchAll();
}

include 'includes/nav_u_header.php';

function get_avatar_name($full_name) {
    return mb_substr(trim($full_name), 0, 2, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าแรกฟีด - KTIS SMART FIELD</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Sarabun', sans-serif; background-color: #f8fafc; margin: 0; }
        .main-container { max-width: 800px; margin: 20px auto; padding: 0 15px; padding-bottom: 60px; }
        
        .filter-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .filter-form { display: flex; gap: 15px; align-items: flex-end; }
        .form-group { flex: 1; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 6px; color: #1e293b; font-size: 0.95rem; }
        .form-input { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 1rem; }
        .btn-search { padding: 11px 24px; background-color: #1e293b; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; height: 44px; }
        
        .status-tabs { display: flex; gap: 8px; margin-top: 15px; border-top: 1px solid #f1f5f9; padding-top: 15px; overflow-x: auto; }
        .tab-item { padding: 8px 16px; border-radius: 20px; text-decoration: none; font-weight: 600; font-size: 0.85rem; }
        .tab-all.active, .tab-pending.active { background: #e11d48; color: white; }
        .tab-success.active { background: #10b981; color: white; }
        .tab-inactive { background: #e2e8f0; color: #475569; }

        .admin-action-zone { margin-bottom: 20px; text-align: right; }
        .btn-toggle-form { display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background-color: #e11d48; color: white; border: none; border-radius: 25px; font-weight: 700; cursor: pointer; }
        .admin-post-card { display: none; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); margin-top: 15px; text-align: left; border-top: 5px solid #e11d48; }
        
        .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        .image-upload-grid { display: grid; grid-template-columns: 1fr; gap: 10px; background: #f8fafc; padding: 12px; border-radius: 6px; border: 1px dashed #cbd5e1; }
        .btn-submit-post { width: 100%; padding: 12px; background-color: #1e293b; color: white; border: none; border-radius: 6px; font-weight: 700; cursor: pointer; }

        .feed-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .post-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; }
        .post-meta-badges { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 15px; }
        .meta-badge { background: #f1f5f9; color: #1e293b; padding: 6px 12px; border-radius: 6px; font-size: 0.85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        .problem-box { background: #fff1f2; border: 1px solid #ffe4e6; padding: 12px; border-radius: 8px; margin-bottom: 15px; }
        .post-image-gallery { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-bottom: 15px; }
        .post-img { width: 100%; height: 160px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0; cursor: pointer; }

        /* ปุ่มถังขยะลบโพสต์ */
        .btn-delete-post { background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 1.1rem; padding: 5px; transition: color 0.2s; }
        .btn-delete-post:hover { color: #ef4444; }
        /* ปุ่มเปลี่ยน status */
        .post-header { flex-wrap: wrap; gap: 8px; }

        /* 💬 โซนคอมเมนต์แชท */
        .reply-section { border-top: 1px solid #f1f5f9; padding-top: 20px; margin-top: 15px; }
        .comments-list { display: flex; flex-direction: column; gap: 16px; margin-bottom: 20px; }
        .chat-row { display: flex; gap: 12px; align-items: flex-start; width: 100%; }
        
        .chat-avatar { width: 38px; height: 38px; border-radius: 50%; color: white; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: 700; flex-shrink: 0; }
        .avatar-green { background-color: #10b981; }
        .avatar-orange { background-color: #f59e0b; }
        .avatar-blue { background-color: #3b82f6; }
        
        .chat-content-box { flex: 1; background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 12px 16px; border-radius: 0 16px 16px 16px; position: relative; }
        .chat-info-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
        .chat-user-name { font-weight: 700; color: #1e293b; font-size: 0.95rem; }
        .chat-user-unit { font-weight: normal; color: #94a3b8; font-size: 0.85rem; }
        
        /* สไตล์ไอคอนสามจุดกดแก้ไขข้อความคอมเมนต์ */
        .btn-edit-reply { background: none; border: none; color: #94a3b8; cursor: pointer; padding: 2px 6px; font-size: 0.9rem; border-radius: 4px; }
        .btn-edit-reply:hover { background: #e2e8f0; color: #475569; }

        .chat-text { color: #334155; font-size: 0.95rem; line-height: 1.5; margin: 0; }
        .chat-embedded-img { width: 100%; max-width: 320px; height: auto; border-radius: 8px; margin-top: 10px; border: 1px solid #cbd5e1; }
        
        /* สไตล์ป้ายประวัติบันทึก Log การแก้ไข */
        .edited-tag { font-size: 0.75rem; color: #94a3b8; margin-left: 6px; cursor: help; text-decoration: underline; font-style: italic; }
        .chat-timestamp { font-size: 0.8rem; color: #94a3b8; display: inline-flex; align-items: center; }

        .chat-input-wrapper { display: flex; gap: 10px; align-items: center; width: 100%; }
        .chat-input-container { flex: 1; display: flex; align-items: center; background-color: #f1f5f9; border-radius: 24px; padding: 4px 16px; border: 1px solid #e2e8f0; }
        .chat-main-field { flex: 1; background: transparent; border: none; padding: 10px 0; font-size: 0.95rem; outline: none; }
        
        .file-attach-label { color: #94a3b8; font-size: 1.15rem; cursor: pointer; padding: 8px; display: flex; }
        .hidden-file-input { display: none; }
        .btn-chat-send { width: 42px; height: 42px; border-radius: 12px; background-color: #e11d48; color: white; border: none; display: flex; align-items: center; justify-content: center; font-size: 1.05rem; cursor: pointer; }
        .file-status-preview { font-size: 0.8rem; color: #10b981; font-weight: 600; padding-left: 15px; margin-top: 4px; display: none; }
        /* ✨ เอฟเฟกต์ไฟกะพริบไฮไลท์สีเหลืองล้อมรอบโพสต์รถอ้อยที่ถูกเลือกส่อง ✨ */
        .highlight-target-post {
            animation: flashHighlight 2s ease-in-out;
            border: 3px solid #f59e0b !important;
            box-shadow: 0 0 20px rgba(245, 158, 11, 0.5) !important;
        }

        @keyframes flashHighlight {
            0% { background-color: #fffbeb; transform: scale(1); }
            30% { background-color: #fef08a; transform: scale(1.02); }
            60% { background-color: #fffbeb; transform: scale(1.01); }
            100% { background-color: white; transform: scale(1); }
        }
        @media (max-width: 600px) {
            .filter-form { flex-direction: column; align-items: stretch; gap: 10px; }
            .form-grid-2 { grid-template-columns: 1fr; gap: 10px; }
            .post-header { flex-direction: column; gap: 8px; }
            .post-image-gallery { grid-template-columns: 1fr; }
            .chat-embedded-img { max-width: 100%; }
        }
    </style>
</head>
<body>

<div class="content-wrapper">

    <div class="main-container">
        
        <div class="filter-card">
            <form method="GET" action="index.php" class="filter-form">
                <div class="form-group">
                    <label><i class="fa-solid fa-calendar-days"></i> เรียกดูข้อมูลประจำวันที่</label>
                    <input type="date" name="search_date" value="<?php echo $search_date; ?>" class="form-input">
                </div>
                <input type="hidden" name="status_tab" value="<?php echo $status_tab; ?>">
                <button type="submit" class="btn-search"><i class="fa-solid fa-magnifying-glass"></i> ค้นหา</button>
            </form>
            
            <div class="status-tabs">
                <a href="index.php?search_date=<?php echo $search_date; ?>&status_tab=all" class="tab-item <?php echo $status_tab == 'all' ? 'tab-all active' : 'tab-inactive'; ?>">ทั้งหมด</a>
                <a href="index.php?search_date=<?php echo $search_date; ?>&status_tab=pending" class="tab-item <?php echo $status_tab == 'pending' ? 'tab-pending active' : 'tab-inactive'; ?>">รอดำเนินการ</a>
                <a href="index.php?search_date=<?php echo $search_date; ?>&status_tab=success" class="tab-item <?php echo $status_tab == 'success' ? 'tab-success active' : 'tab-inactive'; ?>">ดำเนินการแล้ว</a>
            </div>
        </div>

        <?php if($_SESSION['emp_unit'] == 'ประจำออฟฟิตกลาง' && $_SESSION['emp_level'] == 'a'): ?>
            <div class="admin-action-zone">
                <button type="button" class="btn-toggle-form" onclick="togglePostForm()">
                    <i class="fa-solid fa-circle-plus" id="toggleIcon"></i> <span id="toggleText">แจ้งเรื่องรถอ้อยสกปรกเพิ่ม</span>
                </button>
                
                <div class="admin-post-card" id="adminPostForm">
                    <h3 style="margin-top:0; color:#1e293b; font-size:1.05rem;"><i class="fa-solid fa-file-pen" style="color:#e11d48;"></i> รายละเอียดข้อมูลการแจ้งเหตุ</h3>
                    <form id="uploadForm" enctype="multipart/form-data">
                        <div class="form-grid-2">
                            <div>
                                <label style="font-weight:600; font-size:0.9rem; color:#475569;">หน่วยส่งเสริมที่รับผิดชอบ</label>
                                <select name="target_unit" class="form-input" style="height: 44px;" required>
                                    <option value="">-- เลือกหน่วยส่งเสริม --</option>
                                    <?php foreach($zones as $zone): ?>
                                        <option value="<?php echo htmlspecialchars($zone['zone_id'] . ' ' . $zone['zone_name']); ?>">
                                            <?php echo htmlspecialchars($zone['zone_id'] . ' : ' . $zone['zone_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label style="font-weight:600; font-size:0.9rem; color:#475569;">ทะเบียนรถบรรทุกอ้อย</label>
                                <input type="text" name="truck_number" placeholder="เช่น 12-3456" class="form-input" required>
                            </div>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="font-weight:600; font-size:0.9rem; color:#475569;">ปัญหาที่พบ <span style="color:#94a3b8; font-weight:400;">(เลือกได้สูงสุด 3 รายการ)</span></label>
                            <div style="display:flex; flex-direction:column; gap:6px;" id="prob-selects-wrap">
                                <select name="problem_1" class="form-input prob-sel" style="height:42px;" required>
                                    <option value="">-- ปัญหาที่ 1 (บังคับ) --</option>
                                </select>
                                <select name="problem_2" class="form-input prob-sel" style="height:42px;">
                                    <option value="">-- ปัญหาที่ 2 (ถ้ามี) --</option>
                                </select>
                                <select name="problem_3" class="form-input prob-sel" style="height:42px;">
                                    <option value="">-- ปัญหาที่ 3 (ถ้ามี) --</option>
                                </select>
                            </div>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="font-weight:600; font-size:0.9rem; color:#475569;">รายละเอียดข้อความเพิ่มเติม (ถ้ามี)</label>
                            <textarea name="post_text" rows="3" placeholder="ระบุรายละเอียดเพิ่มเติม..." class="form-input"></textarea>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="font-weight:600; font-size:0.9rem; color:#475569;">
                                แนบรูปภาพหลักฐาน
                                <span style="font-weight:400; color:#94a3b8; font-size:0.8rem; margin-left:5px;"><i class="fa-solid fa-compress"></i> บีบอัดอัตโนมัติ 800px / 75%</span>
                            </label>
                            <div class="image-upload-grid">
                                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                                    <label style="cursor:pointer;display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:7px;font-size:0.85rem;font-weight:600;color:#475569;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                                        <i class="fa-solid fa-camera"></i> รูปที่ 1
                                        <input type="file" accept="image/*" style="display:none;" onchange="previewCompress(this,'prev1','img_b64_1')">
                                    </label>
                                    <span id="prev1" style="font-size:0.78rem;color:#10b981;font-weight:600;"></span>
                                </div>
                                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                                    <label style="cursor:pointer;display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:7px;font-size:0.85rem;font-weight:600;color:#475569;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                                        <i class="fa-solid fa-camera"></i> รูปที่ 2
                                        <input type="file" accept="image/*" style="display:none;" onchange="previewCompress(this,'prev2','img_b64_2')">
                                    </label>
                                    <span id="prev2" style="font-size:0.78rem;color:#10b981;font-weight:600;"></span>
                                </div>
                                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                                    <label style="cursor:pointer;display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:7px;font-size:0.85rem;font-weight:600;color:#475569;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                                        <i class="fa-solid fa-camera"></i> รูปที่ 3
                                        <input type="file" accept="image/*" style="display:none;" onchange="previewCompress(this,'prev3','img_b64_3')">
                                    </label>
                                    <span id="prev3" style="font-size:0.78rem;color:#10b981;font-weight:600;"></span>
                                </div>
                            </div>
                            <!-- hidden inputs เก็บ base64 ที่บีบแล้ว -->
                            <input type="hidden" name="img_b64_1" id="img_b64_1">
                            <input type="hidden" name="img_b64_2" id="img_b64_2">
                            <input type="hidden" name="img_b64_3" id="img_b64_3">
                        </div>
                        <button type="submit" class="btn-submit-post">ยืนยันการบันทึกข้อมูล</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

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

<script>
// --- ลบ comment (Admin only) ---
function deleteReply(replyId, postId) {
    if(!confirm('ยืนยันลบความคิดเห็นนี้ออกจากระบบ?')) return;
    const fd = new FormData();
    fd.append('reply_id', replyId);
    fd.append('action', 'delete');
    fetch('reply_action.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        if(data.status === 'success') {
            const row = document.getElementById('reply-row-' + replyId);
            if(row) {
                row.style.transition = 'opacity .25s, max-height .3s';
                row.style.overflow = 'hidden';
                row.style.maxHeight = row.offsetHeight + 'px';
                row.style.opacity = '0';
                setTimeout(() => { row.style.maxHeight = '0'; row.style.padding = '0'; row.style.margin = '0'; }, 50);
                setTimeout(() => row.remove(), 350);
            }
        } else { alert(data.message); }
    });
}

// --- เปลี่ยน status โพสต์ (Admin only) ---
function toggleStatus(postId, currentStatus) {
    const newStatus = currentStatus === 'pending' ? 'success' : 'pending';
    const confirmMsg = newStatus === 'success' ? 'ยืนยันว่าดำเนินการเรื่องนี้เรียบร้อยแล้ว?' : 'เปลี่ยนกลับเป็น "รอดำเนินการ"?';
    if(!confirm(confirmMsg)) return;
    const fd = new FormData();
    fd.append('post_id', postId);
    fd.append('job_status', newStatus);
    fetch('post_status.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        if(data.status === 'success') {
            // อัปเดต UI ไม่ต้อง reload
            const card = document.getElementById('post-card-' + postId);
            const btn  = document.getElementById('status-btn-' + postId);
            if(card) {
                card.style.borderLeftColor = newStatus === 'success' ? '#10b981' : '#e11d48';
                // อัปเดต badge สถานะ
                const badge = card.querySelector('[data-status-badge]');
                if(badge) {
                    badge.textContent = newStatus === 'success' ? 'ดำเนินการแล้ว' : 'รอดำเนินการ';
                    badge.style.background = newStatus === 'success' ? '#d1fae5' : '#fee2e2';
                    badge.style.color = newStatus === 'success' ? '#065f46' : '#991b1b';
                }
            }
            if(btn) {
                btn.style.background = newStatus === 'success' ? '#e11d48' : '#10b981';
                btn.dataset.status = newStatus;
                btn.setAttribute('onclick', "toggleStatus("+postId+", '"+newStatus+"')");
                btn.innerHTML = newStatus === 'success'
                    ? '<i class="fa-solid fa-rotate-left"></i> เปลี่ยนเป็นรอดำเนินการ'
                    : '<i class="fa-solid fa-circle-check"></i> ยืนยันดำเนินการแล้ว';
            }
        } else { alert(data.message); }
    });
}

// --- ส่วนสคริปต์ควบคุมการลบโพสต์แอดมินกลาง ---
function deletePost(postId) {
    if(confirm('พี่แน่ใจใช่ไหมครับว่าจะลบรายการรถอ้อยสกปรกรายการนี้ออกจากระบบ?')) {
        let formData = new FormData();
        formData.append('post_id', postId);
        
        fetch('post_delete.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if(data.status === 'success') {
                document.getElementById('post-card-' + postId).remove();
            }
        });
    }
}

// --- ส่วนสคริปต์สลับเปิด-ปิด กล่องฟอร์มแก้ไขคอมเมนต์ ---
function enableEditMode(replyId) {
    document.getElementById('reply-text-' + replyId).style.display = 'none';
    document.getElementById('edit-box-' + replyId).style.display = 'block';
}

function cancelEdit(replyId) {
    document.getElementById('reply-text-' + replyId).style.display = 'block';
    document.getElementById('edit-box-' + replyId).style.display = 'none';
}

// --- ส่งข้อมูลอัปเดตคำคอมเมนต์ใหม่ทาง AJAX ---
function saveEdit(replyId) {
    let textValue = document.getElementById('edit-input-' + replyId).value;
    if(textValue.trim() === "") { alert("กรุณากรอกข้อความด้วยครับพี่"); return; }
    
    let formData = new FormData();
    formData.append('reply_id', replyId);
    formData.append('reply_text', textValue);
    
    fetch('reply_edit.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if(data.status === 'success') { location.reload(); }
    });
}

function displayFileName(input) {
    let form = input.closest('form');
    let previewLabel = form.querySelector('.file-status-preview');
    if (input.files && input.files.length > 0) { previewLabel.style.display = 'block'; } 
    else { previewLabel.style.display = 'none'; }
}

function togglePostForm() {
    let formBox = document.getElementById('adminPostForm');
    let textBtn = document.getElementById('toggleText');
    let iconBtn = document.getElementById('toggleIcon');
    if (formBox.style.display === 'block') {
        formBox.style.display = 'none'; textBtn.innerText = 'แจ้งเรื่องรถอ้อยสกปรกเพิ่ม'; iconBtn.className = 'fa-solid fa-circle-plus';
    } else {
        formBox.style.display = 'block'; textBtn.innerText = 'ปิดกล่องฟอร์มกรอกข้อมูล'; iconBtn.className = 'fa-solid fa-circle-minus';
    }
}

// ══ compress image via canvas (800px, quality 0.75) ══
function compressImage(file, callback) {
    const MAX = 800;
    const QUALITY = 0.75;
    const reader = new FileReader();
    reader.onload = function(e) {
        const img = new Image();
        img.onload = function() {
            let w = img.width, h = img.height;
            if(w > MAX || h > MAX) {
                if(w > h) { h = Math.round(h * MAX / w); w = MAX; }
                else       { w = Math.round(w * MAX / h); h = MAX; }
            }
            const canvas = document.createElement('canvas');
            canvas.width = w; canvas.height = h;
            canvas.getContext('2d').drawImage(img, 0, 0, w, h);
            callback(canvas.toDataURL('image/jpeg', QUALITY));
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
}

function previewCompress(input, spanId, hiddenId) {
    if(!input.files || !input.files[0]) return;
    const file = input.files[0];
    document.getElementById(spanId).innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> กำลังบีบ...';
    compressImage(file, function(b64) {
        document.getElementById(hiddenId).value = b64;
        // แสดงขนาดโดยประมาณ
        const kb = Math.round(b64.length * 0.75 / 1024);
        document.getElementById(spanId).innerHTML = '<i class="fa-solid fa-check"></i> ' + file.name.substring(0,18) + ' (~' + kb + ' KB)';
    });
}

// โหลด problem options ลงทั้ง 3 select
function loadProblemOptions() {
    fetch('api_problem_types.php')
    .then(res => res.json())
    .then(data => {
        if(!data || data.status !== 'success') return;
        const sels = document.querySelectorAll('.prob-sel');
        sels.forEach((sel, idx) => {
            const placeholder = idx === 0 ? '-- ปัญหาที่ 1 (บังคับ) --' : '-- ปัญหาที่ ' + (idx+1) + ' (ถ้ามี) --';
            sel.innerHTML = '<option value="">' + placeholder + '</option>';
            data.data.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item.problem_name;
                opt.textContent = item.problem_name;
                sel.appendChild(opt);
            });
        });
    });
}

if(document.getElementById('uploadForm')) {
    document.getElementById('uploadForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        // เพิ่ม problem_detail รวม 3 ช่อง
        const p1 = formData.get('problem_1') || '';
        const p2 = formData.get('problem_2') || '';
        const p3 = formData.get('problem_3') || '';
        const combined = [p1, p2, p3].filter(v => v.trim() !== '').join(' / ');
        if(!combined) { alert('กรุณาเลือกปัญหาที่พบอย่างน้อย 1 รายการ'); return; }
        formData.set('problem_detail', combined);
        if(!formData.get('img_b64_1') || formData.get('img_b64_1') === '') {
            alert('กรุณาแนบรูปภาพอย่างน้อย 1 รูป'); return;
        }
        fetch('post_create.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => { alert(data.message); if(data.status === 'success') { location.reload(); } });
    });
}

document.querySelectorAll('.replyForm').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        fetch('reply_action.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => { alert(data.message); if(data.status === 'success') { location.reload(); } });
    });
});
// --- ฟังก์ชันเลื่อนหน้าจออัตโนมัติไปหาโพสต์อ้อยที่ถูกแท็กจากกระดิ่งแจ้งเตือน ---
function scrollToTargetPost(postId, notiId) {
    // 1. ปิดหน้าต่าง Dropdown แจ้งเตือนก่อน
    document.getElementById("notiDropdown").classList.remove("show");
    
    // 2. ส่ง AJAX ไปบอกหลังบ้านว่าคอมพิวเตอร์เปิดอ่านแจ้งเตือนนี้แล้วนะ
    let formData = new FormData();
    formData.append('noti_id', notiId);
    fetch('noti_read_action.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        // ลดตัวเลขแจ้งเตือนบนหน้ากากเว็บออโต้
        let badge = document.getElementById("notiBadgeCount");
        if(badge) {
            let currentCount = parseInt(badge.innerText);
            if(currentCount > 1) { badge.innerText = currentCount - 1; } 
            else { badge.remove(); }
        }
    });

    // 3. ตรวจสอบว่ากล่องโพสต์นั้นอยู่ในหน้าจอปัจจุบันไหม
    let postCard = document.getElementById('post-card-' + postId);
    
    if(postCard) {
        // 🚀 เลื่อนหน้าจอลงไปหาแบบนุ่มนวล (Smooth Scroll)
        postCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        // ⚡ สาดแสงสีเหลืองกะพริบไฮไลท์ตอกย้ำให้พนักงานเห็นชัดเจน
        postCard.classList.add('highlight-target-post');
        
        // เมื่อแอนิเมชันจบ ให้ลบคลาสออกเพื่อคืนสู่สภาพเดิม
        setTimeout(() => {
            postCard.classList.remove('highlight-target-post');
        }, 2000);
    } else {
        // กรณีที่โพสต์นั้นอยู่คนละวัน ให้ reload หน้าเดิมเพื่อให้ชันค้นหาวันเอง
        alert("โพสต์นี้อยู่วันอื่น กรุณาเลือกวันที่ถูกต้อง");
    }
}
</script>

<?php include 'includes/nav_u_footer.php'; ?>
</body>
</html>