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
$sql .= " ORDER BY p.created_at DESC LIMIT 11";

$posts = [];
$has_more = false;
if($stmt = $conn->prepare($sql)){
    $stmt->bindParam(":crop_year",   $_SESSION["crop_year"], PDO::PARAM_STR);
    $stmt->bindParam(":search_date", $search_date,           PDO::PARAM_STR);
    $stmt->execute();
    $posts = $stmt->fetchAll();
    
    if (count($posts) > 10) {
        $has_more = true;
        array_pop($posts); // เอาโพสต์ที่ 11 ออกเพื่อแสดงแค่ 10 โพสต์แรก
    }
}

// ── ดึงหน่วยส่งเสริมสำหรับ dropdown ──
$zones = [];
if($stmt_zones = $conn->prepare("SELECT * FROM zones ORDER BY zone_id ASC")){
    $stmt_zones->execute();
    $zones = $stmt_zones->fetchAll();
}

include 'includes/nav_u_header.php';
?>
<title>TIS SMART FIELD</title>
    <link rel="icon" type="image/png" href="icon/iconweb.png">

<!-- Initial Loader สำหรับหน้า index.php (Modern Glassmorphism Card) -->
<div id="initial-loader" class="modern-loader-backdrop">
    <div class="modern-loader-card">
        <div class="modern-loader-icon-wrap">
            <div class="modern-loader-icon-glow"></div>
            <div class="modern-loader-icon">
                <i class="fa-solid fa-tractor"></i>
            </div>
        </div>

        <div class="modern-loader-title">
            TIS <span class="highlight-red">SMART FIELD</span>
        </div>
        <div class="modern-loader-subtitle">
            กำลังโหลดข้อมูลฟีดระบบ<span class="dot-typing">...</span>
        </div>

        <div class="modern-loader-bar-wrap">
            <div class="modern-loader-bar-track">
                <div class="modern-loader-bar-fill"></div>
            </div>
        </div>

        <div class="modern-loader-footer">
            <span class="live-dot"></span>
            <span>ฝ่ายส่งเสริมและพัฒนาอ้อย</span>
        </div>
    </div>
</div>

<style>
/* ── Modern Card Loader Styles ── */
#initial-loader.modern-loader-backdrop {
    position: fixed; inset: 0; z-index: 99999;
    display: flex; align-items: center; justify-content: center;
    background: radial-gradient(circle at center, rgba(248, 250, 252, 0.92) 0%, rgba(241, 245, 249, 0.98) 100%);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    transition: opacity 0.45s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.45s;
}
.dark-mode #initial-loader.modern-loader-backdrop {
    background: radial-gradient(circle at center, rgba(15, 23, 42, 0.92) 0%, rgba(15, 23, 42, 0.98) 100%) !important;
}

.modern-loader-card {
    position: relative; width: 90%; max-width: 320px;
    padding: 32px 28px 24px;
    background: rgba(255, 255, 255, 0.92);
    border: 1px solid rgba(226, 232, 240, 0.85);
    border-radius: 24px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08), 0 0 30px rgba(225, 29, 72, 0.06);
    display: flex; flex-direction: column; align-items: center; text-align: center;
    animation: loaderCardIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
.dark-mode .modern-loader-card {
    background: rgba(30, 41, 59, 0.85) !important;
    border-color: rgba(255, 255, 255, 0.12) !important;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 30px rgba(225, 29, 72, 0.18) !important;
}

@keyframes loaderCardIn {
    0% { opacity: 0; transform: scale(0.92) translateY(15px); }
    100% { opacity: 1; transform: scale(1) translateY(0); }
}

.modern-loader-icon-wrap {
    position: relative; width: 64px; height: 64px; margin-bottom: 18px;
}
.modern-loader-icon-glow {
    position: absolute; inset: -4px; border-radius: 20px;
    background: linear-gradient(135deg, #e11d48, #10b981);
    opacity: 0.6; filter: blur(10px);
    animation: glowPulse 2.5s ease-in-out infinite alternate;
}
.modern-loader-icon {
    position: relative; width: 100%; height: 100%;
    background: linear-gradient(135deg, #e11d48, #be123c);
    border-radius: 18px; display: flex; align-items: center; justify-content: center;
    color: #ffffff; font-size: 1.75rem;
    box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.3), 0 8px 20px rgba(225, 29, 72, 0.35);
    animation: iconBob 2s ease-in-out infinite alternate;
}
@keyframes iconBob { 0% { transform: translateY(0); } 100% { transform: translateY(-4px); } }
@keyframes glowPulse { 0% { opacity: 0.35; transform: scale(0.95); } 100% { opacity: 0.75; transform: scale(1.08); } }

.modern-loader-title {
    font-size: 1.15rem; font-weight: 800; color: #0f172a;
    letter-spacing: 0.04em; margin-bottom: 4px;
}
.dark-mode .modern-loader-title { color: #f8fafc !important; }
.modern-loader-title .highlight-red { color: #e11d48; }

.modern-loader-subtitle {
    font-size: 0.85rem; color: #64748b; margin-bottom: 22px; font-weight: 500;
}
.dark-mode .modern-loader-subtitle { color: #94a3b8 !important; }

.modern-loader-bar-wrap { width: 100%; margin-bottom: 16px; }
.modern-loader-bar-track {
    position: relative; width: 100%; height: 6px;
    background: rgba(226, 232, 240, 0.9); border-radius: 999px; overflow: hidden;
}
.dark-mode .modern-loader-bar-track {
    background: rgba(148, 163, 184, 0.18) !important;
}

.modern-loader-bar-fill {
    position: absolute; top: 0; left: 0; height: 100%; width: 45%;
    border-radius: 999px;
    background: linear-gradient(90deg, #e11d48, #f43f5e, #10b981);
    background-size: 200% 100%;
    animation: loaderBeam 1.4s ease-in-out infinite;
}
@keyframes loaderBeam {
    0% { left: -45%; width: 35%; }
    50% { width: 55%; }
    100% { left: 100%; width: 35%; }
}

.modern-loader-footer {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 0.72rem; color: #94a3b8; font-weight: 600; letter-spacing: 0.02em;
}
.dark-mode .modern-loader-footer { color: #64748b !important; }

.modern-loader-footer .live-dot {
    width: 6px; height: 6px; background: #10b981; border-radius: 50%;
    box-shadow: 0 0 8px #10b981; animation: pulseDot 1.5s infinite;
}
@keyframes pulseDot { 0%, 100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.4); opacity: 0.5; } }

.dot-typing { display: inline-block; animation: dotBlink 1.4s infinite; }
@keyframes dotBlink { 0%, 20% { opacity: 0; } 50% { opacity: 1; } 100% { opacity: 0; } }
</style>
<script>
window.addEventListener('load', function() {
    const loader = document.getElementById('initial-loader');
    if(loader) {
        setTimeout(function() {
            loader.style.opacity = '0';
            setTimeout(() => loader.remove(), 450);
        }, 600);
    }
});
</script>


<style>
    /* ── Typography & Global Reset ── */
    *, *::before, *::after {
        box-sizing: border-box;
    }
    body, input, button, select, textarea, p, span, div, h1, h2, h3, h4, h5, h6, a, label {
        font-family: 'Sarabun', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    /* Protect Font Awesome Icons */
    .fa, .fas, .far, .fal, .fad, .fab, .fa-solid, .fa-regular, .fa-brands, [class*="fa-"], [class*="fa-"]::before, [class*="fa-"]::after {
        font-family: "Font Awesome 6 Free", "Font Awesome 5 Free", "FontAwesome" !important;
    }

    body {
        background-color: #f8fafc;
        color: #1e293b;
        margin: 0;
        -webkit-font-smoothing: antialiased;
    }
    .page-wrapper {
        display: flex;
        width: 100%;
        min-height: 100vh;
        align-items: flex-start;
    }
    .content-wrapper {
        flex: 1;
        min-width: 0;
        min-height: 100vh;
    }
    .main-container {
        max-width: 820px;
        margin: 24px auto;
        padding: 0 16px 80px;
    }

    /* ── Filter Card ── */
    .filter-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 20px 24px;
        box-shadow: 0 4px 20px -4px rgba(0, 0, 0, 0.05);
        margin-bottom: 24px;
        transition: all 0.2s ease;
    }
    .filter-form {
        display: flex;
        gap: 14px;
        align-items: flex-end;
    }
    .filter-label-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px;
        flex-wrap: wrap;
        gap: 8px;
    }
    .filter-label-row label {
        font-weight: 700;
        font-size: 0.92rem;
        color: #334155;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .date-shortcuts {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.78rem;
    }
    .date-link {
        color: #64748b;
        text-decoration: none;
        font-weight: 700;
        padding: 3px 9px;
        border-radius: 8px;
        background: #f1f5f9;
        transition: all 0.15s ease;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .date-link:hover {
        color: #e11d48;
        background: #fee2e2;
    }
    .date-link-today {
        color: #e11d48;
        font-weight: 800;
        text-decoration: none;
        padding: 3px 9px;
        border-radius: 8px;
        background: #fff1f2;
        border: 1px solid #fecaca;
        transition: all 0.15s ease;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .date-link-today:hover {
        background: #e11d48;
        color: #ffffff;
        border-color: #e11d48;
    }
    .form-group {
        flex: 1;
        min-width: 0;
    }
    .form-input {
        width: 100%;
        padding: 10px 14px;
        border: 1.5px solid #e2e8f0;
        border-radius: 12px;
        font-size: 0.95rem;
        font-weight: 600;
        color: #1e293b;
        background: #f8fafc;
        outline: none;
        transition: all 0.2s ease;
        -webkit-appearance: none !important;
        appearance: none !important;
        min-width: 0 !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
        display: block;
        height: 46px;
    }
    .form-input::-webkit-date-and-time-value {
        text-align: left !important;
        min-height: 1.3em;
    }
    .form-input:focus {
        border-color: #e11d48;
        background: #ffffff;
        box-shadow: 0 0 0 3.5px rgba(225, 29, 72, 0.12);
    }
    .btn-search {
        padding: 11px 22px;
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        color: #ffffff;
        border: none;
        border-radius: 12px;
        font-weight: 800;
        font-size: 0.95rem;
        cursor: pointer;
        height: 46px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
        white-space: nowrap;
        flex-shrink: 0;
    }
    @media (max-width: 640px) {
        .filter-form {
            flex-direction: column;
            align-items: stretch;
            gap: 12px;
        }
        .btn-search {
            width: 100%;
            justify-content: center;
        }
    }
    .btn-search:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.25);
    }
    .btn-search:active {
        transform: translateY(0);
    }

    /* Status Tabs */
    .status-tabs {
        display: flex;
        gap: 8px;
        margin-top: 18px;
        padding-top: 16px;
        border-top: 1px solid #f1f5f9;
        overflow-x: auto;
    }
    .tab-item {
        padding: 8px 18px;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 700;
        font-size: 0.88rem;
        color: #64748b;
        background: #f1f5f9;
        border: 1px solid transparent;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
        white-space: nowrap;
    }
    .tab-item:hover {
        color: #1e293b;
        background: #e2e8f0;
    }
    .tab-item.active {
        background: #1e293b;
        color: #ffffff;
        box-shadow: 0 3px 10px rgba(30, 41, 59, 0.2);
    }
    .tab-item.tab-pending.active {
        background: linear-gradient(135deg, #e11d48, #be123c);
        color: #ffffff;
        box-shadow: 0 3px 12px rgba(225, 29, 72, 0.3);
    }
    .tab-item.tab-success.active {
        background: linear-gradient(135deg, #10b981, #059669);
        color: #ffffff;
        box-shadow: 0 3px 12px rgba(16, 185, 129, 0.3);
    }

    /* ── Feed Post Card ── */
    .feed-card {
        background: #ffffff;
        border-radius: 20px;
        padding: 22px 24px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 20px -4px rgba(0, 0, 0, 0.05);
        margin-bottom: 24px;
        position: relative;
        overflow: hidden;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .feed-card:hover {
        box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.08);
    }
    .post-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
        flex-wrap: wrap;
        gap: 12px;
    }
    .post-user-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .post-user-avatar {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: linear-gradient(135deg, #e11d48, #be123c);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 0.95rem;
        box-shadow: 0 3px 10px rgba(225, 29, 72, 0.2);
        flex-shrink: 0;
    }
    .post-user-name {
        font-size: 1.05rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.3;
    }
    .post-user-role {
        font-size: 0.8rem;
        color: #64748b;
        font-weight: 600;
        margin-left: 4px;
    }
    .post-time {
        font-size: 0.78rem;
        color: #94a3b8;
        display: flex;
        align-items: center;
        gap: 4px;
        margin-top: 2px;
        font-weight: 600;
    }
    .status-badge-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 14px;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 800;
    }
    .status-badge-chip.success {
        background: #dcfce7;
        color: #15803d;
        border: 1px solid #bbf7d0;
    }
    .status-badge-chip.pending {
        background: #ffe4e6;
        color: #be123c;
        border: 1px solid #fecdd3;
    }
    .btn-delete-post {
        background: none;
        border: none;
        color: #94a3b8;
        cursor: pointer;
        font-size: 1.05rem;
        padding: 6px 8px;
        border-radius: 8px;
        transition: all 0.15s ease;
    }
    .btn-delete-post:hover {
        color: #ef4444;
        background: #fee2e2;
    }
    .btn-status-toggle {
        border: none;
        color: #ffffff;
        padding: 6px 14px;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 800;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s ease;
    }
    .btn-toggle-confirm {
        background: linear-gradient(135deg, #10b981, #059669);
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.25);
    }
    .btn-toggle-confirm:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.35);
    }
    .btn-toggle-revert {
        background: linear-gradient(135deg, #e11d48, #be123c);
        box-shadow: 0 2px 8px rgba(225, 29, 72, 0.25);
    }
    .btn-toggle-revert:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(225, 29, 72, 0.35);
    }

    /* Meta Badges */
    .post-meta-badges {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }
    .meta-badge {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #334155;
        padding: 6px 12px;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: background 0.15s;
    }
    .meta-badge:hover {
        background: #f1f5f9;
    }

    /* Problem Box */
    .problem-box {
        background: #fff5f5;
        border: 1px solid #fed7d7;
        padding: 14px 16px;
        border-radius: 14px;
        margin-bottom: 16px;
    }
    .problem-tag {
        background: #ffffff;
        border: 1px solid #fecaca;
        color: #b91c1c;
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 0.83rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .problem-tag.tag-2 {
        border-color: #fed7aa;
        color: #c2410c;
    }
    .problem-tag.tag-3 {
        border-color: #fef08a;
        color: #a16207;
    }

    /* Post Main Text */
    .post-main-text {
        color: #334155;
        font-size: 0.98rem;
        line-height: 1.6;
        margin: 0 0 16px 0;
        word-break: break-word;
    }

    /* Image Gallery */
    .post-image-gallery {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 12px;
        margin-bottom: 16px;
    }
    .post-img {
        width: 100%;
        height: 170px;
        object-fit: cover;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .post-img:hover {
        transform: scale(1.02);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
    }

    /* Reaction Bar */
    .reaction-bar {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 0;
        border-top: 1px solid #f1f5f9;
        border-bottom: 1px solid #f1f5f9;
        flex-wrap: wrap;
    }
    .btn-reaction {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 13px;
        border-radius: 999px;
        border: 1.5px solid #e2e8f0;
        background: #ffffff;
        font-size: 0.85rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.15s ease;
        color: #475569;
    }
    .btn-reaction:hover {
        border-color: #cbd5e1;
        background: #f8fafc;
        transform: scale(1.05);
    }
    .btn-reaction.active {
        background: #fff1f2;
        border-color: #fecaca;
        color: #e11d48;
    }
    .react-count {
        font-size: 0.78rem;
        color: #94a3b8;
        font-weight: 800;
    }
    .btn-reaction.active .react-count {
        color: #e11d48;
    }
    .react-total-text {
        font-size: 0.78rem;
        color: #94a3b8;
        margin-left: auto;
        font-weight: 600;
    }
    .btn-comment-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-left: 6px;
        padding: 6px 15px;
        background: #1e293b;
        color: #ffffff;
        border-radius: 999px;
        font-size: 0.84rem;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.15s ease;
    }
    .btn-comment-link:hover {
        background: #0f172a;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.2);
    }
    .comment-count-chip {
        background: rgba(255, 255, 255, 0.2);
        padding: 1px 7px;
        border-radius: 999px;
        font-size: 0.75rem;
    }

    /* ── Reply / Chat Section ── */
    .reply-section {
        border-top: 1px solid #f1f5f9;
        padding-top: 18px;
        margin-top: 14px;
    }
    .comments-list {
        display: flex;
        flex-direction: column;
        gap: 14px;
        margin-bottom: 18px;
    }
    .chat-row {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        width: 100%;
    }
    .chat-avatar {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        font-weight: 800;
        flex-shrink: 0;
    }
    .avatar-green { background: linear-gradient(135deg, #10b981, #059669); }
    .avatar-orange { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .avatar-blue { background: linear-gradient(135deg, #3b82f6, #2563eb); }

    .chat-content-box {
        flex: 1;
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 12px 16px;
        border-radius: 0 16px 16px 16px;
        position: relative;
    }
    .chat-info-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 6px;
        gap: 8px;
    }
    .chat-user-name {
        font-weight: 800;
        color: #1e293b;
        font-size: 0.95rem;
    }
    .chat-user-unit {
        font-weight: normal;
        color: #94a3b8;
        font-size: 0.85rem;
        margin-left: 3px;
    }
    .chat-timestamp {
        font-size: 0.78rem;
        color: #94a3b8;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-weight: 600;
    }
    .btn-edit-reply {
        background: none;
        border: none;
        color: #94a3b8;
        cursor: pointer;
        padding: 2px 6px;
        font-size: 0.9rem;
        border-radius: 4px;
        transition: all 0.15s ease;
    }
    .btn-edit-reply:hover {
        background: #e2e8f0;
        color: #475569;
    }
    .edited-tag {
        font-size: 0.75rem;
        color: #94a3b8;
        margin-left: 4px;
        cursor: help;
        text-decoration: underline;
        font-style: italic;
    }
    .chat-text {
        color: #334155;
        font-size: 0.95rem;
        line-height: 1.5;
        margin: 0;
        word-break: break-word;
    }
    .chat-embedded-img {
        width: 100%;
        max-width: 320px;
        height: auto;
        border-radius: 10px;
        margin-top: 10px;
        border: 1px solid #cbd5e1;
        cursor: pointer;
    }

    /* Chat Input Form */
    .chat-input-wrapper {
        display: flex;
        gap: 10px;
        align-items: center;
        width: 100%;
    }
    .chat-input-container {
        flex: 1;
        display: flex;
        align-items: center;
        background-color: #f1f5f9;
        border-radius: 999px;
        padding: 4px 16px;
        border: 1.5px solid #e2e8f0;
        transition: all 0.2s ease;
    }
    .chat-input-container:focus-within {
        background-color: #ffffff;
        border-color: #e11d48;
        box-shadow: 0 0 0 3px rgba(225, 29, 72, 0.1);
    }
    .chat-main-field {
        flex: 1;
        background: transparent;
        border: none;
        padding: 10px 0;
        font-size: 0.95rem;
        font-weight: 600;
        color: #1e293b;
        outline: none;
    }
    .file-attach-label {
        color: #94a3b8;
        font-size: 1.15rem;
        cursor: pointer;
        padding: 6px;
        display: flex;
        transition: color 0.15s ease;
    }
    .file-attach-label:hover {
        color: #e11d48;
    }
    .hidden-file-input {
        display: none;
    }
    .btn-chat-send {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        background: linear-gradient(135deg, #e11d48, #be123c);
        color: white;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.05rem;
        cursor: pointer;
        box-shadow: 0 3px 10px rgba(225, 29, 72, 0.25);
        transition: all 0.15s ease;
        flex-shrink: 0;
    }
    .btn-chat-send:hover {
        transform: scale(1.05);
        box-shadow: 0 5px 15px rgba(225, 29, 72, 0.35);
    }
    .btn-chat-send:active {
        transform: scale(0.98);
    }
    .file-status-preview {
        font-size: 0.8rem;
        color: #10b981;
        font-weight: 700;
        padding-left: 15px;
        margin-top: 4px;
    }

    /* Load more button */
    .btn-load-more {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        color: white;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: none;
        padding: 11px 26px;
        border-radius: 999px;
        font-weight: 800;
        font-size: 0.92rem;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.2);
        transition: all 0.2s ease;
    }
    .btn-load-more:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(15, 23, 42, 0.3);
    }

    /* Empty state */
    .empty-feed-card {
        text-align: center;
        padding: 50px 20px;
        background: #ffffff;
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 20px -4px rgba(0, 0, 0, 0.05);
    }
    .empty-icon-wrap {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: #fee2e2;
        color: #e11d48;
        font-size: 1.8rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 16px;
    }
    .empty-title {
        margin: 0 0 6px 0;
        font-size: 1.15rem;
        font-weight: 800;
        color: #1e293b;
    }
    .empty-subtitle {
        margin: 0;
        font-size: 0.9rem;
        color: #64748b;
        font-weight: 600;
    }

    /* Highlight flash */
    .highlight-target-post {
        animation: flashHighlight 2s ease-in-out;
        border: 3px solid #f59e0b !important;
        box-shadow: 0 0 24px rgba(245, 158, 11, 0.45) !important;
    }
    @keyframes flashHighlight {
        0% { background-color: #fffbeb; transform: scale(1); }
        30% { background-color: #fef08a; transform: scale(1.02); }
        60% { background-color: #fffbeb; transform: scale(1.01); }
        100% { background-color: white; transform: scale(1); }
    }

    @media (max-width: 600px) {
        .filter-form { flex-direction: column; align-items: stretch; gap: 10px; }
        .filter-label-row { flex-direction: column; align-items: flex-start; }
        .btn-search { width: 100%; justify-content: center; }
        .post-header { flex-direction: column; align-items: flex-start; gap: 10px; }
        .post-image-gallery { grid-template-columns: 1fr; }
        .chat-embedded-img { max-width: 100%; }
        .main-container { padding: 0 12px 60px; }
    }

    /* ── Dark Mode Support ── */
    .dark-mode body {
        background-color: #0f172a !important;
        color: #f8fafc !important;
    }
    .dark-mode .filter-card,
    .dark-mode .feed-card,
    .dark-mode .empty-feed-card {
        background: #1e293b !important;
        border-color: #334155 !important;
        color: #f8fafc !important;
        box-shadow: 0 4px 25px -4px rgba(0, 0, 0, 0.3) !important;
    }
    .dark-mode .filter-label-row label {
        color: #e2e8f0 !important;
    }
    .dark-mode .form-input {
        background: #0f172a !important;
        border-color: #475569 !important;
        color: #f8fafc !important;
    }
    .dark-mode .form-input:focus {
        background: #16202e !important;
        border-color: #e11d48 !important;
    }
    .dark-mode .date-link {
        background: #334155 !important;
        color: #94a3b8 !important;
    }
    .dark-mode .date-link:hover {
        background: rgba(225, 29, 72, 0.25) !important;
        color: #f43f5e !important;
    }
    .dark-mode .date-link-today {
        background: rgba(225, 29, 72, 0.2) !important;
        border-color: #e11d48 !important;
        color: #f43f5e !important;
    }
    .dark-mode .tab-item {
        background: #0f172a !important;
        color: #94a3b8 !important;
    }
    .dark-mode .tab-item:hover {
        color: #f8fafc !important;
        background: #334155 !important;
    }
    .dark-mode .tab-item.active {
        background: #334155 !important;
        color: #ffffff !important;
    }
    .dark-mode .tab-item.tab-pending.active {
        background: linear-gradient(135deg, #e11d48, #be123c) !important;
        color: #ffffff !important;
    }
    .dark-mode .tab-item.tab-success.active {
        background: linear-gradient(135deg, #10b981, #059669) !important;
        color: #ffffff !important;
    }
    .dark-mode .post-user-name {
        color: #f8fafc !important;
    }
    .dark-mode .meta-badge {
        background: #0f172a !important;
        border-color: #334155 !important;
        color: #cbd5e1 !important;
    }
    .dark-mode .problem-box {
        background: rgba(153, 27, 27, 0.15) !important;
        border-color: rgba(225, 29, 72, 0.3) !important;
    }
    .dark-mode .problem-tag {
        background: #1e293b !important;
        border-color: #7f1d1d !important;
        color: #fca5a5 !important;
    }
    .dark-mode .problem-tag.tag-2 {
        border-color: #7c2d12 !important;
        color: #fdba74 !important;
    }
    .dark-mode .problem-tag.tag-3 {
        border-color: #713f12 !important;
        color: #fde047 !important;
    }
    .dark-mode .post-main-text {
        color: #e2e8f0 !important;
    }
    .dark-mode .post-img {
        border-color: #334155 !important;
    }
    .dark-mode .reaction-bar,
    .dark-mode .reply-section {
        border-color: #334155 !important;
    }
    .dark-mode .btn-reaction {
        background: #0f172a !important;
        border-color: #334155 !important;
        color: #94a3b8 !important;
    }
    .dark-mode .btn-reaction:hover {
        background: #334155 !important;
        color: #f8fafc !important;
    }
    .dark-mode .btn-reaction.active {
        background: rgba(225, 29, 72, 0.2) !important;
        border-color: #e11d48 !important;
        color: #f43f5e !important;
    }
    .dark-mode .chat-content-box {
        background: #0f172a !important;
        border-color: #334155 !important;
    }
    .dark-mode .chat-user-name {
        color: #f8fafc !important;
    }
    .dark-mode .chat-text {
        color: #e2e8f0 !important;
    }
    .dark-mode .chat-input-container {
        background: #0f172a !important;
        border-color: #334155 !important;
    }
    .dark-mode .chat-main-field {
        color: #f8fafc !important;
    }
    .dark-mode .empty-title {
        color: #f8fafc !important;
    }
    .dark-mode .empty-icon-wrap {
        background: rgba(225, 29, 72, 0.2) !important;
        color: #f43f5e !important;
    }

    body.loading-mode {
        overflow: hidden !important;
        height: 100vh !important;
    }
</style>

<div class="page-wrapper">
    <?php include 'includes/nav_u_sidebar.php'; ?>
    <div class="content-wrapper">
        <div class="main-container">
            <?php include 'includes/feed_filter_card.php'; ?>
            <?php include 'includes/feed_post_form.php'; ?>
            <?php include 'includes/feed_post_cards.php'; ?>
        </div>
    </div>
</div>

<script>
    const globalSearchDate = "<?php echo $search_date; ?>";
    const globalStatusTab = "<?php echo $status_tab; ?>";
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php include 'includes/feed_scripts.php'; ?>
<?php include 'includes/nav_u_footer.php'; ?>
<link rel="stylesheet" href="global_smoothness.css">
</body>
</html>
