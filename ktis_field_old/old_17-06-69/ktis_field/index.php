<?php
/**
 * index.php — หน้าหลัก Dashboard Feed
 * TIS SMART FIELD - ฝ่ายไร่
 */
require_once 'config.php';
session_start();

if(!isset($_SESSION["emp_id"])){
    header("location: login.php");
    exit;
}

// ── Filter params ──
$search_date = isset($_GET['search_date']) ? $_GET['search_date'] : date('Y-m-d');
$status_tab  = isset($_GET['status_tab'])  ? $_GET['status_tab']  : 'all';

// ── ดึงโพสต์ตามวันที่และสถานะ ──
$sql = "SELECT p.*, e.emp_name
        FROM posts p
        JOIN employee e ON p.emp_id = e.emp_id
        WHERE p.crop_year = :crop_year
        AND DATE(p.created_at) = :search_date";
if ($status_tab == 'pending')      { $sql .= " AND p.job_status = 'pending'"; }
elseif ($status_tab == 'success')  { $sql .= " AND p.job_status = 'success'"; }
$sql .= " ORDER BY p.created_at DESC";

$posts = [];
if($stmt = $conn->prepare($sql)){
    $stmt->bindParam(":crop_year",   $_SESSION["crop_year"], PDO::PARAM_STR);
    $stmt->bindParam(":search_date", $search_date,           PDO::PARAM_STR);
    $stmt->execute();
    $posts = $stmt->fetchAll();
}

// ── ดึงหน่วยส่งเสริมสำหรับ dropdown ──
$zones = [];
if($stmt_zones = $conn->prepare("SELECT * FROM zones ORDER BY zone_id ASC")){
    $stmt_zones->execute();
    $zones = $stmt_zones->fetchAll();
}

include 'includes/nav_u_header.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
<link rel="icon" type="image/jpeg" href="icon/iconweb.png">   
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าแรกฟีด - TIS SMART FIELD</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าแรกฟีด - TIS SMART FIELD</title>
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

</head>
<body>

<div class="content-wrapper">
    <div class="main-container">

        <?php include 'includes/feed_filter_card.php'; ?>
        <?php include 'includes/feed_post_form.php'; ?>
        <?php include 'includes/feed_post_cards.php'; ?>

    </div>
</div>

<?php include 'includes/feed_scripts.php'; ?>
<?php include 'includes/nav_u_footer.php'; ?>
</body>
</html>