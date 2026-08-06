<?php
session_start();
$status = $_GET['status'] ?? null;

// 🌸 ดึงค่าจาก Cookie มาแสดงในช่อง Input ถ้าผู้ใช้เคยติ๊ก "จดจำรหัสผ่าน" ไว้
$remember_user = $_COOKIE['remember_user'] ?? '';
$remember_pass = $_COOKIE['remember_pass'] ?? '';
$remember_check = isset($_COOKIE['remember_user']) ? 'checked' : '';
?>

<!doctype html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>เข้าสู่ระบบการให้น้ำอ้อย</title>
  <link rel="icon" href="icon/icon_login.png" type="image/x-icon"> 
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <style>
    :root {
        --primary-color: #312e81; /* Dark blue from nav_u */
        --primary-light: #3b82f6; /* Lighter blue for accents */
        --accent-green: #10b981;  /* Elegant green from btn-special */
        --glass-bg: rgba(255, 255, 255, 0.85);
    }

    body {
        background: url('icon/BG_login2.png') no-repeat center center fixed;
        background-size: cover;
        min-height: 100vh;
        margin: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Inter', 'Sarabun', sans-serif;
    }

    .login-box {
        background: var(--glass-bg);
        backdrop-filter: blur(16px);
        padding: 40px;
        border-radius: 24px;
        box-shadow: 0 15px 40px rgba(31, 38, 135, 0.2);
        max-width: 450px;
        width: 90%;
        text-align: center;
        border: 1px solid rgba(255, 255, 255, 0.6);
        transition: transform 0.3s ease;
    }

    .login-box:hover {
        transform: translateY(-5px);
    }

    .login-box h3 {
        font-family: 'Sarabun', sans-serif;
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--primary-color);
        margin-bottom: 5px;
    }

    .subtitle-text {
        color: #64748b;
        font-size: 0.9rem;
        font-weight: 500;
        letter-spacing: 1px;
    }

    .gradient-line {
        width: 60px;
        height: 4px;
        background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
        border-radius: 10px;
        margin: 15px auto 25px auto;
    }

    .login-box .logo-img {
        width: 90px;
        height: auto;
        margin-bottom: 20px;
        filter: drop-shadow(0 8px 16px rgba(0,0,0,0.15));
    }

    .form-label {
        font-weight: 600;
        color: #334155;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .form-label i {
        color: var(--primary-light);
        font-size: 1.2rem;
    }

    .form-control, .form-select {
        border-radius: 12px;
        padding: 12px 16px;
        border: 1.5px solid #cbd5e1;
        font-family: 'Inter', 'Sarabun', sans-serif;
        background-color: rgba(255, 255, 255, 0.9);
        transition: all 0.3s ease;
    }

    .form-control:focus, .form-select:focus {
        border-color: var(--primary-light);
        box-shadow: 0 0 0 0.25rem rgba(59, 130, 246, 0.15);
        background-color: #fff;
    }

    .form-check-input {
        cursor: pointer;
    }
    .form-check-input:checked {
        background-color: var(--primary-light);
        border-color: var(--primary-light);
    }
    .form-check-label {
        cursor: pointer;
        font-size: 0.9rem;
        color: #475569;
        font-weight: 500;
    }

    .btn-login {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
        color: white;
        font-weight: 600;
        letter-spacing: 0.5px;
        border-radius: 12px;
        padding: 12px;
        border: none;
        margin-top: 15px;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.3s ease;
        box-shadow: 0 6px 15px rgba(59, 130, 246, 0.3);
    }

    .btn-login:hover {
        background: linear-gradient(135deg, #1e1b4b, var(--primary-color));
        color: white;
        transform: translateY(-2px) scale(1.02);
        box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
    }

    
  </style>
</head>
<body>

<div class="login-box">
  <img src="icon/icon_login.png" class="logo-img" alt="Logo">


 <div style="margin-bottom: 30px;">
    <h3>
         TIS <span style="color: var(--accent-green);">WaterSuga</span>
    </h3>
    <p class="subtitle-text">ระบบใส่รูปแปลงให้น้ำอ้อย</p>
    <div class="gradient-line"></div>
</div>
  

  <form action="chk.php" method="post">
    <div class="mb-3 text-start">
      <label class="form-label"><i class='bx bx-user-circle'></i> ไอดีพนักงาน</label>
      <input type="text" class="form-control" name="username" placeholder="กรอกเลข 4 หลัก" 
             maxlength="7" value="<?php echo htmlspecialchars($remember_user); ?>" required>
    </div>
    
    <div class="mb-3 text-start">
      <label class="form-label"><i class='bx bx-key'></i> รหัสผ่าน</label>
      <input type="password" class="form-control" name="password" placeholder="กรอกรหัสผ่าน" 
             value="<?php echo htmlspecialchars($remember_pass); ?>" required>
    </div>

    <div class="mb-3 text-start form-check">
      <input type="checkbox" class="form-check-input" name="remember" id="remember" <?php echo $remember_check; ?>>
      <label class="form-check-label" for="remember">จดจำรหัสผ่าน</label>
    </div>
    
    <div class="mb-4 text-start">
      <label class="form-label"><i class='bx bx-calendar'></i> ปีการผลิต</label>
      <select class="form-select" name="year_rai" required>
        <option value="">-- เลือกปีการผลิต --</option>
        <option value="69-70">69-70</option>
        <option value="68-69">68-69</option>
      </select>
    </div>
    
    <button type="submit" class="btn btn-login w-100">
      <i class='bx bx-droplet'></i> เข้าสู่ระบบ
    </button>
  </form>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // แสดง SweetAlert2 อัตโนมัติเมื่อมี Status
  <?php if ($status): ?>
    <?php if ($status == 'success_a' || $status == 'success_u'): ?>
      Swal.fire({
        title: 'เข้าสู่ระบบสำเร็จ',
        text: 'กำลังพาท่านเข้าสู่ระบบจัดการข้อมูล...',
        icon: 'success',
        timer: 1500,
        showConfirmButton: false,
        timerProgressBar: true,
        heightAuto: false,
        customClass: {
          popup: 'rounded-4'
        }
      }).then(() => {
        window.location.href = "<?php echo ($status == 'success_a') ? 'admin_page.php' : 'user_page.php'; ?>";
      });
    <?php elseif ($status == 'fail'): ?>
      Swal.fire({
        title: 'เข้าสู่ระบบไม่สำเร็จ',
        text: 'ไอดีพนักงานหรือรหัสผ่านไม่ถูกต้อง กรุณาลองใหม่',
        icon: 'error',
        confirmButtonText: 'ตกลง',
        confirmButtonColor: '#312e81',
        heightAuto: false,
        customClass: {
          popup: 'rounded-4'
        }
      });
    <?php endif; ?>
  <?php endif; ?>
</script>

</body>
</html>