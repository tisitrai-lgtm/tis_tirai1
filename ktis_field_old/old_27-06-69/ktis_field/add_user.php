<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['emp_level']) || $_SESSION['emp_level'] != 'a') {
    die("คุณไม่มีสิทธิ์เข้าถึงหน้านี้");
}

$zones = $conn->query("SELECT zone_id, zone_name FROM zones ORDER BY zone_id ASC")->fetchAll();

$message = "";
$old = ['emp_id'=>'','emp_name'=>'','emp_unit'=>'','emp_level'=>'u'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $old = [
        'emp_id'    => htmlspecialchars($_POST['emp_id'] ?? ''),
        'emp_name'  => htmlspecialchars($_POST['emp_name'] ?? ''),
        'emp_unit'  => $_POST['emp_unit'] ?? '',
        'emp_level' => $_POST['emp_level'] ?? 'u',
    ];
    try {
        $emp_id   = trim($_POST['emp_id']);
        $name     = trim($_POST['emp_name']);
        $unit     = $_POST['emp_unit'];
        $level    = $_POST['emp_level'];
        $password = password_hash($_POST['emp_pass'], PASSWORD_BCRYPT);

        $check = $conn->prepare("SELECT emp_id FROM employee WHERE emp_id = ?");
        $check->execute([$emp_id]);

        if ($check->rowCount() > 0) {
            $message = "error:รหัสพนักงาน \"$emp_id\" มีอยู่ในระบบแล้ว กรุณาใช้รหัสอื่น";
        } else {
            $stmt = $conn->prepare("INSERT INTO employee (emp_id, emp_pass, emp_name, emp_unit, emp_level) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$emp_id, $password, $name, $unit, $level]);
            header("Location: manage_users.php?status=success");
            exit();
        }
    } catch (PDOException $e) {
        $message = "error:เกิดข้อผิดพลาดจากฐานข้อมูล: " . $e->getMessage();
    }
}

$msg_type = '';
$msg_text = '';
if ($message) {
    [$msg_type, $msg_text] = explode(':', $message, 2);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <link rel="icon" type="image/jpeg" href="icon/iconweb.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มพนักงานใหม่ - TIS SMART FIELD</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Sarabun', sans-serif; background: #f8fafc; margin: 0; }
        .content-wrapper { flex: 1 0 auto; }

        .page-container {
            max-width: 520px;
            margin: 28px auto;
            padding: 0 16px 60px;
        }

        /* ── Breadcrumb ── */
        .breadcrumb {
            display: flex; align-items: center; gap: 6px;
            font-size: 0.82rem; color: #94a3b8; margin-bottom: 18px;
        }
        .breadcrumb a { color: #64748b; text-decoration: none; }
        .breadcrumb a:hover { color: #1e293b; }
        .breadcrumb i { font-size: 10px; }

        /* ── Card ── */
        .form-card {
            background: white;
            border-radius: 14px;
            border: 0.5px solid #e2e8f0;
            box-shadow: 0 4px 20px rgba(30,41,59,.06);
            overflow: hidden;
        }
        .card-header {
            background: #1e293b;
            padding: 18px 22px;
            display: flex; align-items: center; gap: 12px;
            border-bottom: 3px solid #10b981;
        }
        .card-header-icon {
            width: 40px; height: 40px;
            background: #10b981;
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
        }
        .card-header-icon i { color: white; font-size: 1.1rem; }
        .card-header-title { color: #f8fafc; font-weight: 700; font-size: 1rem; }
        .card-header-sub { color: #94a3b8; font-size: 0.75rem; margin-top: 2px; }

        .card-body { padding: 22px; }

        /* ── Alert ── */
        .alert {
            display: flex; align-items: flex-start; gap: 10px;
            padding: 12px 14px; border-radius: 8px; margin-bottom: 18px;
            font-size: 0.88rem; font-weight: 600;
        }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .alert i { margin-top: 1px; flex-shrink: 0; }

        /* ── Form ── */
        .form-group { margin-bottom: 16px; }
        .form-label {
            display: block; font-weight: 700; font-size: 0.83rem;
            color: #374151; margin-bottom: 7px;
        }
        .form-label .req { color: #e11d48; margin-left: 2px; }
        .form-label .hint { font-weight: 400; color: #94a3b8; font-size: 0.77rem; margin-left: 4px; }

        .form-input {
            width: 100%; padding: 10px 13px;
            border: 1.5px solid #e2e8f0; border-radius: 8px;
            background: #f8fafc; font-size: 0.92rem;
            font-family: 'Sarabun', sans-serif; color: #1e293b;
            transition: border-color .15s, box-shadow .15s;
            outline: none;
        }
        .form-input:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16,185,129,.12);
            background: white;
        }
        select.form-input { appearance: none; cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 13px center; padding-right: 36px;
        }

        /* รหัสผ่าน toggle */
        .pw-wrap { position: relative; }
        .pw-wrap .form-input { padding-right: 42px; }
        .pw-toggle {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer; color: #94a3b8; padding: 4px;
        }
        .pw-toggle:hover { color: #475569; }

        /* Level pills */
        .level-selector { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .level-option { display: none; }
        .level-label {
            display: flex; flex-direction: column; align-items: center; gap: 6px;
            padding: 14px 10px; border-radius: 9px; border: 2px solid #e2e8f0;
            cursor: pointer; transition: all .15s; text-align: center;
        }
        .level-label i { font-size: 1.3rem; }
        .level-label .lv-title { font-weight: 700; font-size: 0.88rem; color: #475569; }
        .level-label .lv-desc  { font-size: 0.75rem; color: #94a3b8; line-height: 1.3; }
        .level-option[value="u"]:checked ~ label[for="lv-user"],
        .level-option[value="a"]:checked ~ label[for="lv-admin"] { border-color: #10b981; background: #f0fdf4; }
        input#lv-u:checked + label { border-color: #10b981; background: #f0fdf4; }
        input#lv-a:checked + label { border-color: #e11d48; background: #fef2f2; }
        input#lv-u:checked + label .lv-title { color: #059669; }
        input#lv-a:checked + label .lv-title { color: #e11d48; }
        input#lv-u:checked + label i { color: #10b981; }
        input#lv-a:checked + label i { color: #e11d48; }

        /* Divider */
        .form-divider {
            display: flex; align-items: center; gap: 10px;
            margin: 18px 0; color: #cbd5e1; font-size: 0.78rem;
        }
        .form-divider::before, .form-divider::after {
            content: ''; flex: 1; height: 1px; background: #f1f5f9;
        }

        /* Buttons */
        .btn-row { display: flex; gap: 10px; margin-top: 20px; }
        .btn-cancel {
            flex: 1; padding: 12px; background: #f1f5f9; color: #64748b;
            border: none; border-radius: 8px; font-weight: 700; font-size: 0.9rem;
            font-family: 'Sarabun', sans-serif; cursor: pointer; text-decoration: none;
            display: flex; align-items: center; justify-content: center; gap: 6px;
            transition: background .15s;
        }
        .btn-cancel:hover { background: #e2e8f0; }
        .btn-submit {
            flex: 2; padding: 12px; background: #10b981; color: white;
            border: none; border-radius: 8px; font-weight: 700; font-size: 0.95rem;
            font-family: 'Sarabun', sans-serif; cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 7px;
            transition: background .15s;
        }
        .btn-submit:hover { background: #059669; }

        /* Mobile */
        @media (max-width: 560px) {
            .page-container { margin: 16px auto; }
            .card-body { padding: 16px; }
            .btn-row { flex-direction: column; }
            .btn-cancel { flex: unset; }
            .btn-submit { flex: unset; }
        }
    </style>
    <link rel="stylesheet" href="global_smoothness.css">
</head>
<body>
<div class="content-wrapper">

<?php include 'includes/nav_u_header.php'; ?>

<div class="page-container">

    <div class="breadcrumb">
        <a href="manage_users.php"><i class="fa-solid fa-users-gear"></i> จัดการพนักงาน</a>
        <i class="fa-solid fa-chevron-right"></i>
        <span>เพิ่มพนักงานใหม่</span>
    </div>

    <div class="form-card">
        <div class="card-header">
            <div class="card-header-icon"><i class="fa-solid fa-user-plus"></i></div>
            <div>
                <div class="card-header-title">เพิ่มพนักงานใหม่</div>
                <div class="card-header-sub">กรอกข้อมูลให้ครบถ้วนก่อนบันทึก</div>
            </div>
        </div>

        <div class="card-body">

            <?php if ($msg_type === 'error'): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($msg_text); ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">

                <div class="form-group">
                    <label class="form-label">รหัสพนักงาน <span class="req">*</span></label>
                    <input type="text" name="emp_id" class="form-input"
                           placeholder="เช่น TIS-001, field123"
                           value="<?php echo $old['emp_id']; ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">ชื่อ-นามสกุล <span class="req">*</span></label>
                    <input type="text" name="emp_name" class="form-input"
                           placeholder="ชื่อเต็ม เช่น นายสมชาย ใจดี"
                           value="<?php echo $old['emp_name']; ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">สังกัดหน่วยงาน <span class="req">*</span></label>
                    <select name="emp_unit" class="form-input" required>
                        <?php foreach($zones as $zone):
                            $val = $zone['zone_id'] . ' ' . $zone['zone_name'];
                            $sel = ($old['emp_unit'] === $val) ? 'selected' : '';
                        ?>
                        <option value="<?php echo htmlspecialchars($val); ?>" <?php echo $sel; ?>>
                            <?php echo htmlspecialchars($zone['zone_id'] . ' : ' . $zone['zone_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">ระดับสิทธิ์ <span class="req">*</span></label>
                    <div class="level-selector">
                        <div>
                            <input type="radio" name="emp_level" id="lv-u" value="u"
                                   <?php echo ($old['emp_level'] != 'a') ? 'checked' : ''; ?> style="display:none;">
                            <label for="lv-u" class="level-label">
                                <i class="fa-solid fa-user"></i>
                                <span class="lv-title">พนักงาน</span>
                                <span class="lv-desc">ดูฟีด ตอบกลับ<br>หน่วยของตนเอง</span>
                            </label>
                        </div>
                        <div>
                            <input type="radio" name="emp_level" id="lv-a" value="a"
                                   <?php echo ($old['emp_level'] == 'a') ? 'checked' : ''; ?> style="display:none;">
                            <label for="lv-a" class="level-label">
                                <i class="fa-solid fa-shield-halved"></i>
                                <span class="lv-title">แอดมิน</span>
                                <span class="lv-desc">สร้าง/แก้ไข/ลบ<br>โพสต์ทั้งหมด</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-divider">รหัสผ่าน</div>

                <div class="form-group">
                    <label class="form-label">
                        รหัสผ่าน <span class="req">*</span>
                        <span class="hint">(เข้ารหัสอัตโนมัติ bcrypt)</span>
                    </label>
                    <div class="pw-wrap">
                        <input type="password" name="emp_pass" id="pw-field" class="form-input"
                               placeholder="อย่างน้อย 6 ตัวอักษร" required>
                        <button type="button" class="pw-toggle" onclick="togglePw()" aria-label="แสดง/ซ่อนรหัสผ่าน">
                            <i class="fa-solid fa-eye" id="pw-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="btn-row">
                    <a href="manage_users.php" class="btn-cancel">
                        <i class="fa-solid fa-arrow-left"></i> ยกเลิก
                    </a>
                    <button type="submit" class="btn-submit">
                        <i class="fa-solid fa-floppy-disk"></i> บันทึกพนักงานใหม่
                    </button>
                </div>

            </form>
        </div>
    </div>

</div>
</div>

<?php include 'includes/nav_u_footer.php'; ?>

<script>
function togglePw() {
    const f = document.getElementById('pw-field');
    const i = document.getElementById('pw-eye');
    if (f.type === 'password') { f.type = 'text'; i.className = 'fa-solid fa-eye-slash'; }
    else { f.type = 'password'; i.className = 'fa-solid fa-eye'; }
}
</script>
</body>
</html>