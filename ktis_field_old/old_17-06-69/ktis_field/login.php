<?php
date_default_timezone_set('Asia/Bangkok');
require_once 'config.php';
session_start();

if(isset($_SESSION["emp_id"])){ header("location: index.php"); exit; }

$error = "";
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $emp_id   = trim($_POST["emp_id"]);
    $emp_pass = trim($_POST["emp_pass"]);
    $crop_year = trim($_POST["crop_year"]);

    if(!empty($emp_id) && !empty($emp_pass) && !empty($crop_year)){
        $sql = "SELECT emp_id, emp_name, emp_unit, emp_level, emp_pass FROM employee WHERE emp_id = :emp_id";
        if($stmt = $conn->prepare($sql)){
            $stmt->bindParam(":emp_id", $emp_id, PDO::PARAM_STR);
            if($stmt->execute() && $stmt->rowCount() == 1){
                $row = $stmt->fetch();
                $pass_ok = false;
                if(password_verify($emp_pass, $row['emp_pass'])) {
                    $pass_ok = true;
                } elseif($row['emp_pass'] === md5($emp_pass)) {
                    $pass_ok = true;
                    $new_hash = password_hash($emp_pass, PASSWORD_DEFAULT);
                    $conn->prepare("UPDATE employee SET emp_pass = ? WHERE emp_id = ?")->execute([$new_hash, $emp_id]);
                }
                if($pass_ok) {
                    session_regenerate_id(true);
                    $_SESSION["emp_id"]    = $row["emp_id"];
                    $_SESSION["emp_name"]  = $row["emp_name"];
                    $_SESSION["emp_unit"]  = $row["emp_unit"];
                    $_SESSION["emp_level"] = $row["emp_level"];
                    $_SESSION["crop_year"] = $crop_year;
                    header("location: index.php"); exit;
                } else { $error = "รหัสผ่านไม่ถูกต้อง"; }
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

// ถ้าปีใน DB ไม่อยู่ใน list ให้เพิ่มไว้บนสุด
if($active_year && !in_array($active_year, $crop_years)){
    array_unshift($crop_years, $active_year);
}

// default = ปีจาก DB ถ้ามี ไม่งั้นใช้ base_year
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
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'Sarabun', sans-serif;
            background: #0f172a;
            min-height: 100vh; margin: 0;
            display: flex; flex-direction: column; align-items: center;
            position: relative; overflow-x: hidden;
        }

        /* ── พื้นหลัง animated ── */
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

        /* ── PWA Banner ── */
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

        /* ── Wrapper ── */
        .login-wrap {
            position: relative; z-index: 1;
            width: 100%; max-width: 420px;
            padding: 40px 16px 60px;
            display: flex; flex-direction: column; align-items: center;
        }

        /* ── Logo area ── */
        .logo-area {
            text-align: center; margin-bottom: 28px; color: white;
        }
        .logo-icon {
            width: 68px; height: 68px;
            background: linear-gradient(135deg, #e11d48, #be123c);
            border-radius: 18px;
            display: inline-flex; align-items: center; justify-content: center;
            margin-bottom: 14px;
            box-shadow: 0 8px 24px rgba(225,29,72,.35);
        }
        .logo-icon i { font-size: 2rem; color: white; }
        .logo-title {
            font-size: 1.55rem; font-weight: 700; color: #f8fafc;
            letter-spacing: .02em; margin-bottom: 4px;
        }
        .logo-title span { color: #e11d48; }
        .logo-sub { font-size: 0.82rem; color: #94a3b8; }

        /* ── Card ── */
        .login-card {
            background: rgba(255,255,255,.97);
            border-radius: 18px;
            box-shadow: 0 20px 50px rgba(0,0,0,.35), 0 0 0 1px rgba(255,255,255,.05);
            width: 100%;
            overflow: hidden;
        }
        .card-top-bar {
            height: 5px;
            background: linear-gradient(90deg, #e11d48, #f43f5e, #10b981);
        }
        .card-body { padding: 30px 28px 28px; }

        .card-heading {
            font-size: 1.05rem; font-weight: 700; color: #1e293b;
            margin-bottom: 22px; display: flex; align-items: center; gap: 8px;
        }
        .card-heading i { color: #e11d48; font-size: .9rem; }

        /* ── Alert ── */
        .alert {
            background: #fef2f2; border: 1px solid #fecaca;
            color: #b91c1c; padding: 10px 13px; border-radius: 8px;
            margin-bottom: 18px; font-size: .85rem; font-weight: 600;
            display: flex; align-items: center; gap: 8px;
        }
        .alert i { flex-shrink: 0; }

        /* ── Form ── */
        .form-group { margin-bottom: 16px; }
        .form-label {
            display: block; margin-bottom: 6px;
            font-weight: 700; font-size: 0.82rem; color: #374151;
        }
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

        /* password toggle */
        .input-wrap { position: relative; }
        .input-wrap .form-control { padding-right: 42px; }
        .toggle-pass {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: #94a3b8; cursor: pointer;
            font-size: .9rem; padding: 4px;
        }
        .toggle-pass:hover { color: #475569; }

        /* remember me */
        .remember-row {
            display: flex; align-items: center; gap: 8px;
            font-size: 0.83rem; color: #475569; margin-bottom: 20px;
            cursor: pointer; user-select: none;
        }
        .remember-row input[type="checkbox"] {
            width: 15px; height: 15px; accent-color: #e11d48; cursor: pointer;
        }

        /* submit */
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

        /* footer note */
        .login-footer {
            margin-top: 18px; text-align: center;
            font-size: .75rem; color: #64748b;
        }

        @media(max-width:420px){
            .card-body { padding: 24px 18px 22px; }
            .logo-title { font-size: 1.35rem; }
        }
    </style>
</head>
<body>

<div class="bg-pattern">
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
</div>

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

    <!-- Card -->
    <div class="login-card">
        <div class="card-top-bar"></div>
        <div class="card-body">

            <div class="card-heading">
                <i class="fa-solid fa-right-to-bracket"></i> เข้าสู่ระบบ
            </div>

            <?php if(!empty($error)): ?>
            <div class="alert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <form action="login.php" method="POST" onsubmit="saveUser()">

                <div class="form-group">
                    <label class="form-label"><i class="fa-solid fa-id-badge"></i>รหัสพนักงาน</label>
                    <input type="text" name="emp_id" id="emp_id" class="form-control"
                           placeholder="กรอกรหัสพนักงาน" required autocomplete="username">
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

                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-right-to-bracket"></i> เข้าสู่ระบบ
                </button>
            </form>
        </div>
    </div>

    <div class="login-footer">
        TIS SMART FIELD &copy; <?php echo date('Y'); ?> · ฝ่ายไร่
    </div>

</div>

<script>
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
</script>
</body>
</html>