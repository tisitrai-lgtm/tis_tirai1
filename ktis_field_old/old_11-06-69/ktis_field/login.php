<?php
require_once 'config.php';
session_start();

if(isset($_SESSION["emp_id"])){ header("location: index.php"); exit; }

$error = "";
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $emp_id = trim($_POST["emp_id"]);
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
                    $_SESSION["emp_id"] = $row["emp_id"];
                    $_SESSION["emp_name"] = $row["emp_name"];
                    $_SESSION["emp_unit"] = $row["emp_unit"];
                    $_SESSION["emp_level"] = $row["emp_level"];
                    $_SESSION["crop_year"] = $crop_year;
                    header("location: index.php"); exit;
                } else { $error = "รหัสผ่านไม่ถูกต้อง"; }
            } else { $error = "ไม่พบรหัสพนักงานนี้ในระบบ"; }
        }
    } else { $error = "กรุณากรอกข้อมูลให้ครบถ้วน"; }
}
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
    <meta name="apple-mobile-web-app-title" content="KTIS Field">
    <link rel="apple-touch-icon" href="icon/iconweb.png">

    <title>Login - KTIS SMART FIELD</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); display: flex; flex-direction: column; align-items: center; min-height: 100vh; margin: 0; color: #f8fafc; }
        /* Banner สไตล์ PWA */
        #installBanner { display: none; width: 100%; background: #e11d48; color: white; padding: 15px; text-align: center; font-size: 0.9rem; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        #btnInstall { background: white; color: #e11d48; border: none; padding: 5px 12px; border-radius: 5px; font-weight: bold; cursor: pointer; margin-left: 10px; }
        
        .login-card { background: rgba(255, 255, 255, 0.95); padding: 40px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); width: 100%; max-width: 380px; border-top: 6px solid #e11d48; color: #1e293b; margin-top: 50px; }
        .login-title { text-align: center; font-size: 1.6rem; font-weight: 700; color: #1e293b; margin-bottom: 5px; }
        .login-subtitle { text-align: center; color: #64748b; font-size: 0.85rem; margin-bottom: 25px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.9rem; }
        .form-control { width: 100%; padding: 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; }
        .btn-submit { width: 100%; padding: 12px; background: #e11d48; color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; transition: 0.3s; }
        .btn-submit:hover { background: #be123c; }
        .checkbox-group { font-size: 0.85rem; display: flex; align-items: center; gap: 8px; margin-bottom: 20px; color: #475569; }
        .alert { background: #fee2e2; color: #b91c1c; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 0.85rem; text-align: center; }
    </style>
</head>
<body>

    <div id="installBanner">
        ใช้งาน KTIS Field ได้สะดวกยิ่งขึ้น!
        <button id="btnInstall">ติดตั้งแอป</button>
    </div>

    <div class="login-card">
        <div class="login-title">KTIS SMART FIELD</div>
        <div class="login-subtitle">ระบบบริหารจัดการงานไร่</div>
        <?php if(!empty($error)): ?><div class="alert"><?php echo $error; ?></div><?php endif; ?>
        <form action="login.php" method="POST" onsubmit="saveUser()">
            <div class="form-group">
                <label>รหัสพนักงาน</label>
                <input type="text" name="emp_id" id="emp_id" class="form-control" required>
            </div>
            <div class="form-group">
                <label>รหัสผ่าน</label>
                <input type="password" name="emp_pass" class="form-control" required>
            </div>
            <div class="form-group">
                <label>ปีการผลิต</label>
                <select name="crop_year" class="form-control">
                    <option value="69/70">69/70</option>
                    <option value="70/71">70/71</option>
                </select>
            </div>
            <div class="checkbox-group">
                <input type="checkbox" id="rememberMe"> จำรหัสพนักงานของฉัน
            </div>
            <button type="submit" class="btn-submit">เข้าสู่ระบบ</button>
        </form>
    </div>

    <script>
        // PWA Install Logic
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            document.getElementById('installBanner').style.display = 'block';
        });

        document.getElementById('btnInstall').addEventListener('click', () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') document.getElementById('installBanner').style.display = 'none';
                    deferredPrompt = null;
                });
            }
        });

        // Remember User
        window.onload = function() {
            if(localStorage.getItem("rememberedUser")) {
                document.getElementById("emp_id").value = localStorage.getItem("rememberedUser");
                document.getElementById("rememberMe").checked = true;
            }
        };
        function saveUser() {
            let empId = document.getElementById("emp_id").value;
            if(document.getElementById("rememberMe").checked) localStorage.setItem("rememberedUser", empId);
            else localStorage.removeItem("rememberedUser");
        }
    </script>
</body>
</html>