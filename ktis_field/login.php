<?php
date_default_timezone_set('Asia/Bangkok');
require_once 'config.php';
session_start();

if(isset($_SESSION["emp_id"])){ header("location: index.php"); exit; }

// ── ตรวจสอบโหมดปิดปรับปรุงระบบ ──
$m_mode_active = false;
$m_msg_text = 'ระบบกำลังปิดปรับปรุงชั่วคราว เพื่อพัฒนาและเพิ่มประสิทธิภาพการใช้งาน ขออภัยในความไม่สะดวก';
$m_until_text = '';
try {
    $stmt_m = $conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('maintenance_mode', 'maintenance_message', 'maintenance_until')");
    $m_settings = $stmt_m->fetchAll(PDO::FETCH_KEY_PAIR);
    if (isset($m_settings['maintenance_mode']) && $m_settings['maintenance_mode'] === '1') {
        $m_mode_active = true;
    }
    if (!empty($m_settings['maintenance_message'])) $m_msg_text = $m_settings['maintenance_message'];
    if (!empty($m_settings['maintenance_until'])) $m_until_text = $m_settings['maintenance_until'];
} catch(Exception $e) {}

$show_maintenance_popup = false;
$error = "";
$login_success = false;
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $emp_id   = trim($_POST["emp_id"]);
    $emp_pass = trim($_POST["emp_pass"]);
    $crop_year = trim($_POST["crop_year"]);

    if(!empty($emp_id) && !empty($emp_pass) && !empty($crop_year)){
        // ค้นหารหัสพนักงานโดยไม่สนใจตัวพิมพ์เล็ก/ใหญ่ (Case-Insensitive) และตัดช่องว่างอัตโนมัติ
        $sql = "SELECT emp_id, emp_name, emp_unit, emp_level, emp_pass, status FROM employee WHERE LOWER(TRIM(emp_id)) = LOWER(TRIM(:emp_id)) LIMIT 1";
        if($stmt = $conn->prepare($sql)){
            $stmt->bindValue(":emp_id", $emp_id, PDO::PARAM_STR);
            if($stmt->execute() && $stmt->rowCount() == 1){
                $row = $stmt->fetch();
                if (isset($row['status']) && (int)$row['status'] === 0) {
                    $error = "บัญชีผู้ใช้นี้ถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ";
                } else {
                    $pass_ok = false;
                    if(password_verify($emp_pass, $row['emp_pass'])) {
                        $pass_ok = true;
                    } elseif($row['emp_pass'] === md5($emp_pass)) {
                        $pass_ok = true;
                        $new_hash = password_hash($emp_pass, PASSWORD_DEFAULT);
                        $conn->prepare("UPDATE employee SET emp_pass = ? WHERE emp_id = ?")->execute([$new_hash, $row['emp_id']]);
                    }
                    if($pass_ok) {
                        if ($m_mode_active && $row['emp_level'] !== 'a') {
                            $show_maintenance_popup = true;
                        } else {
                            session_regenerate_id(true);
                            $_SESSION["emp_id"]    = $row["emp_id"];
                            $_SESSION["emp_name"]  = $row["emp_name"];
                            $_SESSION["emp_unit"]  = $row["emp_unit"];
                            $_SESSION["emp_level"] = $row["emp_level"];
                            $_SESSION["crop_year"] = $crop_year;

                            $login_success = true;
                        }
                    } else { $error = "รหัสผ่านไม่ถูกต้อง"; }
                }
            } else { $error = "ไม่พบรหัสพนักงานนี้ในระบบ"; }
        }
    } else { $error = "กรุณากรอกข้อมูลให้ครบถ้วน"; }
}

// ── ดึงปีผลิต active จาก system_settings ──
$active_year = '';
try {
    $s = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'active_crop_year'");
    $active_year = $s ? ($s->fetchColumn() ?: '') : '';
} catch(Exception $e) {}

// ── fallback: คำนวณจากปีปัจจุบัน ──
$now_month   = (int)date('n');
$now_year_th = (int)date('Y') + 543 - 2500;
$base_year   = ($now_month >= 4) ? $now_year_th : $now_year_th - 1;

$crop_years = [];
for($i = 1; $i >= -1; $i--){
    $y = $base_year + $i;
    $crop_years[] = $y . '/' . ($y + 1);
}

if($active_year && !in_array($active_year, $crop_years)){
    array_unshift($crop_years, $active_year);
}

$default_year = $active_year ?: ($base_year . '/' . ($base_year + 1));
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="manifest" href="manifest.json">
    <link rel="icon" type="image/png" href="icon/iconweb.png">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="TIS Field">
    <link rel="apple-touch-icon" href="icon/iconweb.png">
    <title>Login - TIS SMART FIELD</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 สำหรับ popup เข้าสู่ระบบสำเร็จ -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'Sarabun', sans-serif;
            background: #0f172a;
            min-height: 100vh; margin: 0;
            display: flex; flex-direction: column; align-items: center;
            position: relative; overflow-x: hidden;
        }

        .bg-pattern {
            position: fixed; inset: 0; z-index: 0; overflow: hidden;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #0f2027 100%);
        }
        .bg-circle {
            position: absolute; border-radius: 50%;
            background: radial-gradient(circle, rgba(225,29,72,.18) 0%, transparent 70%);
            animation: floatBg 8s ease-in-out infinite;
        }
        .bg-circle:nth-child(1){ width:500px; height:500px; top:-100px; right:-150px; animation-delay:0s; }
        .bg-circle:nth-child(2){ width:350px; height:350px; bottom:-80px; left:-100px; animation-delay:3s; background: radial-gradient(circle, rgba(16,185,129,.12) 0%, transparent 70%); }
        .bg-circle:nth-child(3){ width:200px; height:200px; top:40%; left:30%; animation-delay:5s; background: radial-gradient(circle, rgba(59,130,246,.1) 0%, transparent 70%); }
        @keyframes floatBg { 0%,100%{transform:scale(1) translate(0,0);} 50%{transform:scale(1.08) translate(10px,-10px);} }

        #installBanner {
            display: none; position: relative; z-index: 10;
            width: 100%; background: #e11d48; color: white;
            padding: 12px 16px; text-align: center; font-size: 0.88rem;
            font-weight: 600;
        }
        #btnInstall {
            background: white; color: #e11d48; border: none;
            padding: 5px 14px; border-radius: 6px; font-weight: 700;
            cursor: pointer; margin-left: 10px; font-family: 'Sarabun', sans-serif;
        }

        .login-wrap {
            position: relative; z-index: 1;
            width: 100%; max-width: 420px;
            padding: 40px 16px 60px;
            display: flex; flex-direction: column; align-items: center;
        }

        .logo-area { text-align: center; margin-bottom: 28px; color: white; }
        .logo-icon {
            width: 68px; height: 68px;
            background: linear-gradient(135deg, #e11d48, #be123c);
            border-radius: 18px;
            display: inline-flex; align-items: center; justify-content: center;
            margin-bottom: 14px;
            box-shadow: 0 8px 24px rgba(225,29,72,.35);
        }
        .logo-icon i { font-size: 2rem; color: white; }
        .logo-title { font-size: 1.55rem; font-weight: 700; color: #f8fafc; letter-spacing: .02em; margin-bottom: 4px; }
        .logo-title span { color: #e11d48; }
        .logo-sub { font-size: 0.82rem; color: #94a3b8; }

        .login-card {
            background: rgba(255,255,255,.97);
            border-radius: 18px;
            box-shadow: 0 20px 50px rgba(0,0,0,.35), 0 0 0 1px rgba(255,255,255,.05);
            width: 100%;
            overflow: hidden;
        }
        .card-top-bar { height: 5px; background: linear-gradient(90deg, #e11d48, #f43f5e, #10b981); }
        .card-body { padding: 30px 28px 28px; }

        .card-heading {
            font-size: 1.05rem; font-weight: 700; color: #1e293b;
            margin-bottom: 22px; display: flex; align-items: center; gap: 8px;
        }
        .card-heading i { color: #e11d48; font-size: .9rem; }

        .alert {
            background: #fef2f2; border: 1px solid #fecaca;
            color: #b91c1c; padding: 10px 13px; border-radius: 8px;
            margin-bottom: 18px; font-size: .85rem; font-weight: 600;
            display: flex; align-items: center; gap: 8px;
            animation: slideDownFade 0.4s ease-out forwards;
        }
        .alert i { flex-shrink: 0; }
        @keyframes slideDownFade { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

        /* ── Modern Card Loader ── */
        #initial-loader {
            position: fixed; inset: 0; z-index: 99999;
            display: flex; align-items: center; justify-content: center;
            background: radial-gradient(circle at center, rgba(15, 23, 42, 0.88) 0%, rgba(15, 23, 42, 0.97) 100%);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            transition: opacity 0.45s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.45s;
        }
        .modern-loader-card {
            position: relative; width: 90%; max-width: 320px;
            padding: 32px 28px 24px;
            background: rgba(30, 41, 59, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 30px rgba(225, 29, 72, 0.18);
            display: flex; flex-direction: column; align-items: center; text-align: center;
            animation: loaderCardIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
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
            font-size: 1.15rem; font-weight: 800; color: #f8fafc;
            letter-spacing: 0.04em; margin-bottom: 4px;
        }
        .modern-loader-title .highlight-red { color: #e11d48; }
        .modern-loader-subtitle {
            font-size: 0.85rem; color: #94a3b8; margin-bottom: 22px; font-weight: 500;
        }
        .modern-loader-bar-wrap { width: 100%; margin-bottom: 16px; }
        .modern-loader-bar-track {
            position: relative; width: 100%; height: 6px;
            background: rgba(148, 163, 184, 0.18); border-radius: 999px; overflow: hidden;
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
            font-size: 0.72rem; color: #64748b; font-weight: 600; letter-spacing: 0.02em;
        }
        .modern-loader-footer .live-dot {
            width: 6px; height: 6px; background: #10b981; border-radius: 50%;
            box-shadow: 0 0 8px #10b981; animation: pulseDot 1.5s infinite;
        }
        @keyframes pulseDot { 0%, 100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.4); opacity: 0.5; } }
        .dot-typing { display: inline-block; animation: dotBlink 1.4s infinite; }
        @keyframes dotBlink { 0%, 20% { opacity: 0; } 50% { opacity: 1; } 100% { opacity: 0; } }

        .form-group { margin-bottom: 16px; }
        .form-label { display: block; margin-bottom: 6px; font-weight: 700; font-size: 0.82rem; color: #374151; }
        .form-label i { color: #94a3b8; margin-right: 4px; font-size: .78rem; }
        .form-control {
            width: 100%; padding: 11px 13px;
            border: 1.5px solid #e2e8f0; border-radius: 9px;
            font-size: 0.95rem; font-family: 'Sarabun', sans-serif;
            background: #f8fafc; color: #1e293b; outline: none;
            transition: border-color .15s, background .15s;
        }
        .form-control:focus { border-color: #e11d48; background: white; }
        select.form-control { cursor: pointer; appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 12px center; padding-right: 32px;
        }

        .input-wrap { position: relative; }
        .input-wrap .form-control { padding-right: 42px; }
        .toggle-pass {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: #94a3b8; cursor: pointer;
            font-size: .9rem; padding: 4px;
        }
        .toggle-pass:hover { color: #475569; }

        .remember-row {
            display: flex; align-items: center; gap: 8px;
            font-size: 0.83rem; color: #475569; margin-bottom: 20px;
            cursor: pointer; user-select: none;
        }
        .remember-row input[type="checkbox"] { width: 15px; height: 15px; accent-color: #e11d48; cursor: pointer; }

        .btn-submit {
            width: 100%; padding: 12px;
            background: linear-gradient(135deg, #e11d48, #be123c);
            color: white; border: none; border-radius: 9px;
            font-size: 1rem; font-weight: 700; font-family: 'Sarabun', sans-serif;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            gap: 8px; transition: opacity .2s, transform .1s;
            box-shadow: 0 4px 14px rgba(225,29,72,.35);
        }
        .btn-submit:hover  { opacity: .9; }
        .btn-submit:active { transform: scale(.98); }
        .btn-submit:disabled { opacity: .7; cursor: not-allowed; }

        .login-footer { margin-top: 18px; text-align: center; font-size: .75rem; color: #64748b; }

        /* ── Maintenance Modal Popup on Login ── */
        .m-modal-overlay {
            position: fixed; inset: 0; z-index: 999999;
            background: rgba(15, 23, 42, 0.82);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            display: flex; align-items: center; justify-content: center;
            padding: 20px 16px;
            animation: mFadeIn 0.3s ease-out;
        }
        @keyframes mFadeIn { from { opacity: 0; } to { opacity: 1; } }

        .m-modal-card {
            background: rgba(30, 41, 59, 0.92);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 28px;
            padding: 36px 26px 30px;
            max-width: 440px; width: 100%;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7), 0 0 35px rgba(225, 29, 72, 0.15);
            animation: mSlideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            position: relative;
        }
        @keyframes mSlideUp {
            from { opacity: 0; transform: translateY(25px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .m-icon-box {
            position: relative; width: 84px; height: 84px;
            margin: 0 auto 18px; display: flex; align-items: center; justify-content: center;
        }
        .m-gear-big {
            position: absolute; font-size: 4.5rem; color: rgba(245, 158, 11, 0.15);
            animation: mRotate 18s linear infinite;
        }
        .m-gear-small {
            position: absolute; font-size: 2.5rem; color: rgba(225, 29, 72, 0.2);
            top: -6px; right: -6px; animation: mRotateRev 12s linear infinite;
        }
        .m-icon-center {
            position: relative; width: 62px; height: 62px;
            background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);
            border-radius: 18px; display: flex; align-items: center; justify-content: center;
            box-shadow: 0 8px 20px rgba(225, 29, 72, 0.4); border: 2px solid rgba(255, 255, 255, 0.2);
        }
        .m-icon-center i { font-size: 1.7rem; color: #ffffff; animation: mWrench 2.5s ease-in-out infinite; }

        @keyframes mRotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        @keyframes mRotateRev { from { transform: rotate(0deg); } to { transform: rotate(-360deg); } }
        @keyframes mWrench {
            0%, 100% { transform: rotate(0deg); }
            20% { transform: rotate(-15deg); }
            40% { transform: rotate(10deg); }
            60% { transform: rotate(-5deg); }
            80% { transform: rotate(5deg); }
        }

        .m-badge {
            display: inline-flex; align-items: center; gap: 7px;
            background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.35);
            color: #fbbf24; padding: 5px 14px; border-radius: 999px;
            font-size: 0.8rem; font-weight: 700; margin-bottom: 14px;
        }
        .m-dot {
            width: 7px; height: 7px; background: #f59e0b; border-radius: 50%;
            box-shadow: 0 0 8px #f59e0b; animation: mPulse 1.5s infinite;
        }
        @keyframes mPulse { 0%, 100% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.4); opacity: 0.5; } }

        .m-title { font-size: 1.4rem; font-weight: 800; color: #ffffff; margin-bottom: 10px; }
        .m-title span { color: #f43f5e; }
        .m-desc { font-size: 0.9rem; color: #cbd5e1; line-height: 1.6; margin-bottom: 20px; font-weight: 400; }

        .m-time-box {
            background: rgba(15, 23, 42, 0.6); border: 1px dashed rgba(245, 158, 11, 0.4);
            border-radius: 12px; padding: 10px 14px; margin-bottom: 22px;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            font-size: 0.85rem; color: #fde68a; font-weight: 600;
        }
        .m-time-box i { color: #f59e0b; }

        .m-btn-close {
            width: 100%; padding: 13px 18px;
            background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);
            color: #ffffff; border: none; border-radius: 12px;
            font-size: 0.95rem; font-weight: 700; font-family: inherit;
            cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
            box-shadow: 0 4px 14px rgba(225, 29, 72, 0.35); transition: all 0.2s ease;
        }
        .m-btn-close:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(225, 29, 72, 0.45); }

        /* ── Login Success Theme Modal ── */
        .success-screen-overlay {
            position: fixed; inset: 0; z-index: 999999;
            background: radial-gradient(circle at center, rgba(15, 23, 42, 0.88) 0%, rgba(15, 23, 42, 0.98) 100%);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            display: flex; align-items: center; justify-content: center;
            padding: 20px 16px;
            animation: sFadeIn 0.35s ease-out;
        }
        @keyframes sFadeIn { from { opacity: 0; } to { opacity: 1; } }

        .success-card {
            background: rgba(30, 41, 59, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 28px;
            padding: 36px 28px 26px;
            max-width: 420px; width: 100%;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7), 0 0 35px rgba(16, 185, 129, 0.2);
            animation: sSlideUp 0.45s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            position: relative;
        }
        @keyframes sSlideUp {
            from { opacity: 0; transform: translateY(25px) scale(0.94); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .success-icon-wrap {
            position: relative; width: 78px; height: 78px;
            margin: 0 auto 16px; display: flex; align-items: center; justify-content: center;
        }
        .success-icon-glow {
            position: absolute; inset: -4px; border-radius: 50%;
            background: radial-gradient(circle, #10b981 0%, rgba(16, 185, 129, 0) 70%);
            filter: blur(10px);
            animation: sPulseGlow 2s infinite alternate;
        }
        @keyframes sPulseGlow {
            0% { transform: scale(0.9); opacity: 0.5; }
            100% { transform: scale(1.3); opacity: 0.9; }
        }

        .success-icon {
            position: relative; width: 66px; height: 66px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            box-shadow: 0 8px 24px rgba(16, 185, 129, 0.45);
            border: 3px solid rgba(255, 255, 255, 0.3);
            animation: sCheckPop 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }
        @keyframes sCheckPop {
            0% { transform: scale(0); }
            70% { transform: scale(1.15); }
            100% { transform: scale(1); }
        }
        .success-icon i { font-size: 1.85rem; color: #ffffff; }

        .success-badge {
            display: inline-flex; align-items: center; gap: 7px;
            background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.35);
            color: #34d399; padding: 4px 14px; border-radius: 999px;
            font-size: 0.8rem; font-weight: 700; margin-bottom: 12px;
        }
        .success-dot {
            width: 7px; height: 7px; background: #10b981; border-radius: 50%;
            box-shadow: 0 0 8px #10b981; animation: sDotPulse 1.5s infinite;
        }
        @keyframes sDotPulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.4); } }

        .success-title {
            font-size: 1.25rem; font-weight: 800; color: #ffffff; margin-bottom: 16px;
        }
        .success-title .highlight-red { color: #f43f5e; }

        .success-user-box {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px; padding: 12px 16px; margin-bottom: 20px;
            display: flex; align-items: center; gap: 12px; text-align: left;
        }
        .success-avatar {
            width: 44px; height: 44px; border-radius: 12px;
            background: linear-gradient(135deg, #e11d48, #be123c);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 1.1rem; flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(225, 29, 72, 0.3);
        }
        .success-user-info { flex-grow: 1; }
        .success-user-info .user-name {
            font-size: 0.95rem; font-weight: 800; color: #f8fafc; margin-bottom: 2px;
        }
        .success-user-info .user-unit {
            font-size: 0.78rem; font-weight: 600; color: #94a3b8; display: flex; align-items: center; gap: 4px;
        }
        .success-user-info .user-unit i { color: #e11d48; }

        .success-bar-wrap { width: 100%; margin-bottom: 12px; }
        .success-bar-track {
            height: 6px; width: 100%; background: rgba(255, 255, 255, 0.1);
            border-radius: 999px; overflow: hidden; margin-bottom: 8px;
        }
        .success-bar-fill {
            height: 100%; width: 0%;
            background: linear-gradient(90deg, #10b981, #34d399);
            border-radius: 999px;
            animation: sProgressFill 1.3s ease-in-out forwards;
        }
        @keyframes sProgressFill {
            0% { width: 0%; }
            100% { width: 100%; }
        }
        .success-bar-label {
            font-size: 0.8rem; color: #cbd5e1; font-weight: 600;
            display: flex; align-items: center; justify-content: center; gap: 6px;
        }
        .success-bar-label i { color: #10b981; }

        .success-footer {
            margin-top: 16px; font-size: 0.72rem; color: #64748b;
            border-top: 1px solid rgba(255, 255, 255, 0.08); padding-top: 12px;
        }
    </style>
    <link rel="stylesheet" href="global_smoothness.css">
</head>
<body>

<div class="bg-pattern">
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
</div>

<!-- Initial Loading Screen -->
<?php if(!$login_success): ?>
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
            กำลังโหลดข้อมูลระบบ<span class="dot-typing">...</span>
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
<?php endif; ?>

<!-- PWA Banner -->
<div id="installBanner">
    <i class="fa-solid fa-mobile-screen"></i>
    ใช้งาน TIS Field ได้สะดวกยิ่งขึ้น!
    <button id="btnInstall"><i class="fa-solid fa-download"></i> ติดตั้งแอป</button>
</div>

<div class="login-wrap">

    <!-- Logo -->
    <div class="logo-area">
        <div class="logo-icon"><i class="fa-solid fa-tractor"></i></div>
        <div class="logo-title">TIS <span>SMART FIELD</span></div>
        <div class="logo-sub">ระบบบริหารจัดการงานไร่ · ฝ่ายส่งเสริม</div>
    </div>

    <!-- Card: ฟอร์ม login -->
    <div class="login-card">
        <div class="card-top-bar"></div>
        <div class="card-body">

            <div class="card-heading">
                <i class="fa-solid fa-right-to-bracket"></i> เข้าสู่ระบบ
            </div>

            <?php if(!empty($error)): ?>
            <div class="alert">
                <i class="fa-solid fa-circle-xmark"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <form action="login.php" method="POST" onsubmit="saveUser()" id="loginForm">

                <div class="form-group">
                    <label class="form-label"><i class="fa-solid fa-id-badge"></i>รหัสพนักงาน</label>
                    <input type="text" name="emp_id" id="emp_id" class="form-control"
                           placeholder="กรอกรหัสพนักงาน (ตัวเล็ก/ใหญ่ก็ได้)" required autocomplete="username" autocapitalize="none" spellcheck="false">
                </div>

                <div class="form-group">
                    <label class="form-label"><i class="fa-solid fa-lock"></i>รหัสผ่าน</label>
                    <div class="input-wrap">
                        <input type="password" name="emp_pass" id="emp_pass" class="form-control"
                               placeholder="กรอกรหัสผ่าน" required autocomplete="current-password">
                        <button type="button" class="toggle-pass" onclick="togglePassword()" tabindex="-1">
                            <i class="fa-solid fa-eye" id="eye-icon"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label"><i class="fa-solid fa-calendar-days"></i>ปีการผลิต</label>
                    <select name="crop_year" class="form-control">
                        <?php foreach($crop_years as $yr): ?>
                        <option value="<?php echo htmlspecialchars($yr); ?>"
                            <?php echo ($yr === $default_year) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($yr); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <label class="remember-row">
                    <input type="checkbox" id="rememberMe"> จำรหัสพนักงานของฉัน
                </label>

                <button type="submit" class="btn-submit" id="loginBtn">
                    <i class="fa-solid fa-right-to-bracket"></i> เข้าสู่ระบบ
                </button>
            </form>
        </div>
    </div>

    <div class="login-footer">
        TIS SMART FIELD &copy; <?php echo date('Y'); ?> · ฝ่ายไร่
    </div>

</div>

<?php if (!empty($show_maintenance_popup)): ?>
<!-- ══════════════════════════════════════════ -->
<!-- 🚧 Popup แจ้งเตือนปิดปรับปรุงระบบสำหรับ User -->
<!-- ══════════════════════════════════════════ -->
<div class="m-modal-overlay" id="mModalOverlay" onclick="closeMaintenanceModal()">
    <div class="m-modal-card" onclick="event.stopPropagation()">
        <div class="m-icon-box">
            <i class="fa-solid fa-gear m-gear-big"></i>
            <i class="fa-solid fa-gear m-gear-small"></i>
            <div class="m-icon-center">
                <i class="fa-solid fa-screwdriver-wrench"></i>
            </div>
        </div>

        <div class="m-badge">
            <span class="m-dot"></span>
            <span>กำลังปิดปรับปรุงระบบชั่วคราว</span>
        </div>

        <div class="m-title">TIS <span>SMART FIELD</span></div>
        <div class="m-desc">
            <?php echo nl2br(htmlspecialchars($m_msg_text)); ?>
        </div>

        <?php if(!empty($m_until_text)): ?>
        <div class="m-time-box">
            <i class="fa-solid fa-clock"></i>
            <span>คาดว่าจะเปิดให้บริการ: <strong><?php echo htmlspecialchars($m_until_text); ?></strong></span>
        </div>
        <?php endif; ?>

        <button type="button" class="m-btn-close" onclick="closeMaintenanceModal()">
            <i class="fa-solid fa-check"></i> รับทราบ
        </button>

        <div class="m-footer">
            บริษัท น้ำตาลไทยเอกลักษณ์ จำกัด · ฝ่ายไร่
        </div>
    </div>
</div>
<?php endif; ?>

<?php if($login_success): ?>
<!-- ══════════════════════════════════════════ -->
<!-- 🟢 หน้าต่างแจ้งเตือนเข้าสู่ระบบสำเร็จตรงธีม -->
<!-- ══════════════════════════════════════════ -->
<div class="success-screen-overlay">
    <div class="success-card">
        <div class="success-icon-wrap">
            <div class="success-icon-glow"></div>
            <div class="success-icon">
                <i class="fa-solid fa-check"></i>
            </div>
        </div>

        <div class="success-badge">
            <span class="success-dot"></span>
            <span>ยืนยันตัวตนสำเร็จ</span>
        </div>

        <div class="success-title">
            ยินดีต้อนรับสู่ TIS <span class="highlight-red">SMART FIELD</span>
        </div>

        <div class="success-user-box">
            <div class="success-avatar">
                <i class="fa-solid fa-user"></i>
            </div>
            <div class="success-user-info">
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['emp_name'] ?? ''); ?></div>
                <div class="user-unit"><i class="fa-solid fa-location-dot"></i> หน่วยส่งเสริม: <?php echo htmlspecialchars($_SESSION['emp_unit'] ?? ''); ?></div>
            </div>
        </div>

        <div class="success-bar-wrap">
            <div class="success-bar-track">
                <div class="success-bar-fill"></div>
            </div>
            <div class="success-bar-label">
                <i class="fa-solid fa-spinner fa-spin"></i> กำลังเข้าสู่หน้าหลัก...
            </div>
        </div>

        <div class="success-footer">
            TIS SMART FIELD · ฝ่ายไร่
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    function closeMaintenanceModal(){
        const modal = document.getElementById('mModalOverlay');
        if(modal){
            modal.style.opacity = '0';
            modal.style.transition = 'opacity 0.25s ease';
            setTimeout(() => modal.remove(), 250);
        }
    }
    // PWA Install
    let deferredPrompt;
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        document.getElementById('installBanner').style.display = 'block';
    });
    document.getElementById('btnInstall').addEventListener('click', () => {
        if(deferredPrompt){
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then(r => {
                if(r.outcome === 'accepted') document.getElementById('installBanner').style.display = 'none';
                deferredPrompt = null;
            });
        }
    });

    // Remember Me
    window.onload = function() {
        const saved = localStorage.getItem("rememberedUser");
        if(saved){
            document.getElementById("emp_id").value = saved;
            document.getElementById("rememberMe").checked = true;
        }
    };
    function saveUser() {
        const empId = document.getElementById("emp_id").value;
        if(document.getElementById("rememberMe").checked)
            localStorage.setItem("rememberedUser", empId);
        else
            localStorage.removeItem("rememberedUser");
    }

    // Toggle Password
    function togglePassword() {
        const input = document.getElementById('emp_pass');
        const icon  = document.getElementById('eye-icon');
        if(input.type === 'password'){
            input.type = 'text';
            icon.className = 'fa-solid fa-eye-slash';
        } else {
            input.type = 'password';
            icon.className = 'fa-solid fa-eye';
        }
    }

    // ปุ่ม submit: โชว์สถานะกำลังเข้าสู่ระบบ
    document.getElementById('loginForm').addEventListener('submit', function(){
        const btn = document.getElementById('loginBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> กำลังเข้าสู่ระบบ...';
    });

    // เอา Loader ออกเมื่อโหลดหน้าเสร็จ (เฉพาะตอนไม่ได้ login สำเร็จ)
    window.addEventListener('load', function() {
        const loader = document.getElementById('initial-loader');
        if(loader) {
            setTimeout(function() {
                loader.style.opacity = '0';
                setTimeout(() => loader.remove(), 500);
            }, 700);
        }
    });

    // ── เปลี่ยนหน้าอัตโนมัติเมื่อเข้าสู่ระบบสำเร็จ ──
    <?php if($login_success): ?>
    document.addEventListener('DOMContentLoaded', function(){
        setTimeout(() => {
            window.location.href = 'index.php';
        }, 1300);
    });
    <?php endif; ?>
</script>
</body>
</html>
