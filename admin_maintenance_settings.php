<?php
session_start();

// 🚨 หน้านี้เฉพาะแอดมินเท่านั้น (emp_level == 'a') ถ้าไม่ใช่ ไล่กลับไปหน้า login
if (!isset($_SESSION['emp_level']) || $_SESSION['emp_level'] !== 'a') {
    header("Location: login.php");
    exit;
}

$status_file = __DIR__ . '/maintenance_status.txt';

// รับค่าจากการกดปุ่ม (POST) เพื่อเปิด/ปิดโหมดปรับปรุง
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_maintenance'])) {
    $new_status = $_POST['toggle_maintenance'] === '1' ? '1' : '0';
    file_put_contents($status_file, $new_status);
    header("Location: admin_maintenance_settings.php?status=" . ($new_status === '1' ? 'on' : 'off'));
    exit;
}

// อ่านสถานะปัจจุบัน
$current_status = '0';
if (file_exists($status_file)) {
    $current_status = trim(file_get_contents($status_file)) === '1' ? '1' : '0';
}
$is_on = $current_status === '1';

$result_status = $_GET['status'] ?? null;
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ตั้งค่าโหมดปรับปรุงระบบ | TIS WaterSuga</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    :root {
        --primary-color: #312e81;
        --primary-light: #3b82f6;
        --accent-green: #10b981;
        --accent-red: #dc3545;
        --glass-bg: rgba(255, 255, 255, 0.9);
    }

    body {
        margin: 0;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Sarabun', 'Inter', sans-serif;
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
        padding: 20px;
    }

    .settings-box {
        background: var(--glass-bg);
        backdrop-filter: blur(16px);
        padding: 45px 40px;
        border-radius: 24px;
        box-shadow: 0 15px 40px rgba(31, 38, 135, 0.25);
        max-width: 480px;
        width: 100%;
        text-align: center;
        border: 1px solid rgba(255, 255, 255, 0.6);
    }

    .icon-wrap {
        width: 80px;
        height: 80px;
        margin: 0 auto 20px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.3s ease;
    }
    .icon-wrap.state-on { background: linear-gradient(135deg, var(--accent-red), #f87171); }
    .icon-wrap.state-off { background: linear-gradient(135deg, var(--accent-green), #34d399); }
    .icon-wrap i { font-size: 2.5rem; color: #fff; }

    h1 {
        font-size: 1.4rem;
        font-weight: 700;
        color: var(--primary-color);
        margin: 0 0 6px;
    }
    .subtitle-text {
        color: #64748b;
        font-size: 0.9rem;
        margin-bottom: 25px;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        font-size: 0.95rem;
        padding: 10px 20px;
        border-radius: 999px;
        margin-bottom: 25px;
    }
    .status-badge.state-on { background: rgba(220, 53, 69, 0.1); color: var(--accent-red); }
    .status-badge.state-off { background: rgba(16, 185, 129, 0.1); color: var(--accent-green); }
    .status-badge .dot { width: 8px; height: 8px; border-radius: 50%; }
    .status-badge.state-on .dot { background: var(--accent-red); }
    .status-badge.state-off .dot { background: var(--accent-green); }

    .btn-toggle {
        width: 100%;
        border: none;
        border-radius: 12px;
        padding: 14px;
        font-weight: 600;
        font-size: 1.05rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.25s ease;
        color: #fff;
    }
    .btn-toggle.turn-on { background: linear-gradient(135deg, var(--accent-red), #ef4444); box-shadow: 0 6px 15px rgba(220,53,69,0.3); }
    .btn-toggle.turn-off { background: linear-gradient(135deg, var(--accent-green), #059669); box-shadow: 0 6px 15px rgba(16,185,129,0.3); }
    .btn-toggle:hover { transform: translateY(-2px); filter: brightness(1.05); }

    .back-link {
        display: inline-block;
        margin-top: 20px;
        color: #64748b;
        font-size: 0.9rem;
        text-decoration: none;
    }
    .back-link:hover { color: var(--primary-color); }
  </style>
</head>
<body>

  <div class="settings-box">
    <div class="icon-wrap <?php echo $is_on ? 'state-on' : 'state-off'; ?>">
      <i class='bx <?php echo $is_on ? 'bx-cog' : 'bx-check-shield'; ?>'></i>
    </div>

    <h1>ตั้งค่าโหมดปรับปรุงระบบ</h1>
    <div class="subtitle-text">TIS WaterSuga — ควบคุมการเปิด/ปิดหน้ากำลังปรับปรุง</div>

    <div class="status-badge <?php echo $is_on ? 'state-on' : 'state-off'; ?>">
      <span class="dot"></span>
      สถานะปัจจุบัน: <?php echo $is_on ? 'กำลังปรับปรุงระบบ (User เข้าไม่ได้)' : 'ใช้งานปกติ'; ?>
    </div>

    <form method="post" id="toggleForm">
      <input type="hidden" name="toggle_maintenance" value="<?php echo $is_on ? '0' : '1'; ?>">
      <button type="submit" class="btn-toggle <?php echo $is_on ? 'turn-off' : 'turn-on'; ?>">
        <i class='bx <?php echo $is_on ? 'bx-power-off' : 'bx-error'; ?>'></i>
        <?php echo $is_on ? 'ปิดโหมดปรับปรุง (เปิดให้ user ใช้งานได้)' : 'เปิดโหมดปรับปรุง (บล็อก user ทั้งหมด)'; ?>
      </button>
    </form>

    <a href="admin_page.php" class="back-link"><i class='bx bx-arrow-back'></i> กลับหน้าแอดมิน</a>
  </div>

<script>
  const form = document.getElementById('toggleForm');
  const willTurnOn = <?php echo $is_on ? 'false' : 'true'; ?>;

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    Swal.fire({
      title: willTurnOn ? 'ยืนยันเปิดโหมดปรับปรุง?' : 'ยืนยันปิดโหมดปรับปรุง?',
      text: willTurnOn
        ? 'User ทั่วไปทุกคนจะเห็นหน้ากำลังปรับปรุง เข้าใช้งานระบบไม่ได้ทันที'
        : 'ระบบจะกลับมาใช้งานได้ตามปกติสำหรับ User ทุกคน',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'ยืนยัน',
      cancelButtonText: 'ยกเลิก',
      confirmButtonColor: willTurnOn ? '#dc3545' : '#10b981',
      heightAuto: false,
      customClass: { popup: 'rounded-4' }
    }).then((result) => {
      if (result.isConfirmed) {
        form.submit();
      }
    });
  });

  <?php if ($result_status === 'on'): ?>
    Swal.fire({
      title: 'เปิดโหมดปรับปรุงแล้ว',
      icon: 'success',
      timer: 1800,
      showConfirmButton: false,
      heightAuto: false,
      customClass: { popup: 'rounded-4' }
    });
  <?php elseif ($result_status === 'off'): ?>
    Swal.fire({
      title: 'ปิดโหมดปรับปรุงแล้ว',
      icon: 'success',
      timer: 1800,
      showConfirmButton: false,
      heightAuto: false,
      customClass: { popup: 'rounded-4' }
    });
  <?php endif; ?>
</script>

</body>
</html>
