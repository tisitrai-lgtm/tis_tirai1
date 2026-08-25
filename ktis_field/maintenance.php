<?php
/**
 * maintenance.php — หน้าแจ้งเตือนปิดปรับปรุงระบบ
 * KTIS SMART FIELD - ฝ่ายไร่
 */
require_once 'config.php';
session_start();

// ดึงการตั้งค่าปิดปรับปรุงจากฐานข้อมูล
$m_mode = '1';
$m_msg  = 'ระบบกำลังปิดปรับปรุงชั่วคราว เพื่อพัฒนาและเพิ่มประสิทธิภาพการใช้งาน ขออภัยในความไม่สะดวก';
$m_until = '';
$company_name = 'บริษัท น้ำตาลไทยเอกลักษณ์ จำกัด';

try {
    $stmt = $conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('maintenance_mode','maintenance_message','maintenance_until','company_name_th')");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    if (isset($settings['maintenance_mode'])) $m_mode = $settings['maintenance_mode'];
    if (!empty($settings['maintenance_message'])) $m_msg = $settings['maintenance_message'];
    if (!empty($settings['maintenance_until'])) $m_until = $settings['maintenance_until'];
    if (!empty($settings['company_name_th'])) $company_name = $settings['company_name_th'];
} catch (Exception $e) {}

// ถ้าโหมดปรับปรุงถูกปิดแล้ว และไม่ใช่ Admin เข้ามาดู ให้เด้งกลับไปหน้าหลัก
if ($m_mode === '0' && (!isset($_GET['preview']) || $_GET['preview'] !== '1')) {
    header("Location: index.php");
    exit;
}

$is_admin = isset($_SESSION['emp_level']) && $_SESSION['emp_level'] === 'a';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบกำลังปิดปรับปรุงชั่วคราว - TIS SMART FIELD</title>
    <link rel="icon" type="image/png" href="icon/iconweb.png">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            color: #f8fafc;
            padding: 20px 16px;
            overflow-x: hidden;
            position: relative;
        }

        /* Animated Background Particles */
        .bg-glow-1 {
            position: absolute;
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, rgba(225, 29, 72, 0.25) 0%, rgba(225, 29, 72, 0) 70%);
            top: -50px;
            left: -50px;
            border-radius: 50%;
            filter: blur(40px);
            animation: pulseGlow 8s infinite alternate;
        }

        .bg-glow-2 {
            position: absolute;
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, rgba(245, 158, 11, 0.2) 0%, rgba(245, 158, 11, 0) 70%);
            bottom: -50px;
            right: -50px;
            border-radius: 50%;
            filter: blur(40px);
            animation: pulseGlow 10s infinite alternate-reverse;
        }

        @keyframes pulseGlow {
            0% { transform: scale(1) translate(0, 0); opacity: 0.7; }
            100% { transform: scale(1.3) translate(30px, 30px); opacity: 1; }
        }

        /* Maintenance Card */
        .maintenance-card {
            position: relative;
            z-index: 10;
            background: rgba(30, 41, 59, 0.75);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 28px;
            padding: 44px 32px 36px;
            max-width: 520px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6), 0 0 40px rgba(225, 29, 72, 0.1);
            animation: cardAppear 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes cardAppear {
            0% { opacity: 0; transform: translateY(20px) scale(0.96); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Icon Wrapper with Rotating Gear Animation */
        .icon-box-wrap {
            position: relative;
            width: 100px;
            height: 100px;
            margin: 0 auto 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .icon-gear-big {
            position: absolute;
            font-size: 5.5rem;
            color: rgba(245, 158, 11, 0.15);
            animation: rotateGear 18s linear infinite;
        }

        .icon-gear-small {
            position: absolute;
            font-size: 3rem;
            color: rgba(225, 29, 72, 0.2);
            top: -10px;
            right: -10px;
            animation: rotateGearRev 12s linear infinite;
        }

        .icon-center-circle {
            position: relative;
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);
            border-radius: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px rgba(225, 29, 72, 0.4);
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .icon-center-circle i {
            font-size: 2rem;
            color: #ffffff;
            animation: wrenchShake 2.5s ease-in-out infinite;
        }

        @keyframes rotateGear {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes rotateGearRev {
            from { transform: rotate(0deg); }
            to { transform: rotate(-360deg); }
        }

        @keyframes wrenchShake {
            0%, 100% { transform: rotate(0deg); }
            20% { transform: rotate(-15deg); }
            40% { transform: rotate(10deg); }
            60% { transform: rotate(-5deg); }
            80% { transform: rotate(5deg); }
        }

        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.35);
            color: #fbbf24;
            padding: 6px 16px;
            border-radius: 999px;
            font-size: 0.84rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
        }

        .status-pulse {
            width: 8px;
            height: 8px;
            background: #f59e0b;
            border-radius: 50%;
            box-shadow: 0 0 10px #f59e0b;
            animation: pulseDot 1.5s infinite;
        }

        @keyframes pulseDot {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.5); opacity: 0.5; }
        }

        /* Titles & Text */
        .title {
            font-size: 1.6rem;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.3;
            margin-bottom: 12px;
        }

        .title span {
            color: #f43f5e;
        }

        .description {
            font-size: 0.96rem;
            color: #94a3b8;
            line-height: 1.65;
            margin-bottom: 24px;
            font-weight: 400;
            padding: 0 10px;
        }

        /* Time Estimate Box */
        .time-box {
            background: rgba(15, 23, 42, 0.6);
            border: 1px dashed rgba(245, 158, 11, 0.4);
            border-radius: 16px;
            padding: 14px 18px;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 0.92rem;
            color: #fde68a;
            font-weight: 600;
        }

        .time-box i {
            color: #f59e0b;
            font-size: 1.1rem;
        }

        /* Action Buttons */
        .actions-wrap {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .btn-refresh {
            width: 100%;
            padding: 14px 20px;
            background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);
            color: #ffffff;
            border: none;
            border-radius: 14px;
            font-size: 0.98rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 16px rgba(225, 29, 72, 0.35);
            transition: all 0.2s ease;
        }

        .btn-refresh:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 22px rgba(225, 29, 72, 0.5);
        }

        .btn-admin {
            width: 100%;
            padding: 12px 20px;
            background: rgba(255, 255, 255, 0.06);
            color: #cbd5e1;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            font-size: 0.88rem;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .btn-admin:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.25);
        }

        .footer-credit {
            margin-top: 26px;
            font-size: 0.78rem;
            color: #64748b;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 18px;
        }

        @media (max-width: 480px) {
            .maintenance-card {
                padding: 34px 20px 28px;
            }
            .title {
                font-size: 1.35rem;
            }
            .description {
                font-size: 0.88rem;
                padding: 0;
            }
        }
    </style>
</head>
<body>

    <div class="bg-glow-1"></div>
    <div class="bg-glow-2"></div>

    <div class="maintenance-card">
        <div class="icon-box-wrap">
            <i class="fa-solid fa-gear icon-gear-big"></i>
            <i class="fa-solid fa-gear icon-gear-small"></i>
            <div class="icon-center-circle">
                <i class="fa-solid fa-screwdriver-wrench"></i>
            </div>
        </div>

        <div class="status-badge">
            <span class="status-pulse"></span>
            <span>กำลังปิดปรับปรุงระบบชั่วคราว</span>
        </div>

        <h1 class="title">TIS <span>SMART FIELD</span></h1>
        <p class="description">
            <?php echo nl2br(htmlspecialchars($m_msg)); ?>
        </p>

        <?php if(!empty($m_until)): ?>
        <div class="time-box">
            <i class="fa-solid fa-clock"></i>
            <span>คาดว่าจะเปิดให้บริการ: <strong><?php echo htmlspecialchars($m_until); ?></strong></span>
        </div>
        <?php endif; ?>

        <div class="actions-wrap">
            <button class="btn-refresh" onclick="location.reload()">
                <i class="fa-solid fa-rotate-right"></i> ตรวจสอบสถานะอีกครั้ง
            </button>

            <?php if($is_admin): ?>
            <a href="index.php" class="btn-admin" style="background:rgba(16,185,129,0.15);border-color:#10b981;color:#34d399;">
                <i class="fa-solid fa-shield-halved"></i> คุณคือผู้ดูแลระบบ (เข้าใช้งานระบบ)
            </a>
            <a href="setting_system.php" class="btn-admin" style="background:rgba(245,158,11,0.15);border-color:#f59e0b;color:#fbbf24;">
                <i class="fa-solid fa-sliders"></i> ไปที่หน้าตั้งค่าเพื่อเปิดระบบ
            </a>
            <?php endif; ?>
        </div>

        <div class="footer-credit">
            <?php echo htmlspecialchars($company_name); ?> · ฝ่ายไร่
        </div>
    </div>

</body>
</html>
