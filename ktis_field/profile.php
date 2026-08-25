<?php
/**
 * profile.php — โปรไฟล์ของฉันและเปลี่ยนรหัสผ่าน (My Profile)
 * TIS SMART FIELD - ฝ่ายไร่
 */
require_once 'config.php';
session_start();

if (!isset($_SESSION['emp_id'])) {
    header("Location: login.php");
    exit;
}

$session_emp_id = $_SESSION['emp_id'];

// ดึงข้อมูลพนักงานปัจจุบัน
$stmt = $conn->prepare("SELECT * FROM employee WHERE emp_id = ?");
$stmt->execute([$session_emp_id]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    header("Location: logout.php");
    exit;
}

$flash_msg = '';
$flash_type = '';

// ─────────────────────────────────────────────────────────────
// ประมวลผลการแก้ไขข้อมูลส่วนตัว / เปลี่ยนรหัสผ่าน
// ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. อัปเดตข้อมูลทั่วไป (ชื่อ-นามสกุล)
    if ($action === 'update_profile') {
        $new_name = trim($_POST['emp_name'] ?? '');
        if (empty($new_name)) {
            $flash_msg  = 'กรุณากรอกชื่อ-นามสกุล';
            $flash_type = 'error';
        } else {
            try {
                $stmt_up = $conn->prepare("UPDATE employee SET emp_name = ?, updated_at = NOW() WHERE emp_id = ?");
                $stmt_up->execute([$new_name, $session_emp_id]);

                // อัปเดตค่าใน SESSION
                $_SESSION['emp_name'] = $new_name;
                $employee['emp_name'] = $new_name;

                $flash_msg  = 'บันทึกข้อมูลส่วนตัวเรียบร้อยแล้ว';
                $flash_type = 'success';
            } catch (Exception $e) {
                $flash_msg  = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage();
                $flash_type = 'error';
            }
        }
    }

    // 2. เปลี่ยนรหัสผ่าน
    elseif ($action === 'change_password') {
        $old_pass  = $_POST['old_password'] ?? '';
        $new_pass  = $_POST['new_password'] ?? '';
        $conf_pass = $_POST['confirm_password'] ?? '';

        if (empty($old_pass) || empty($new_pass) || empty($conf_pass)) {
            $flash_msg  = 'กรุณากรอกข้อมูลรหัสผ่านให้ครบทุกช่อง';
            $flash_type = 'error';
        } elseif ($new_pass !== $conf_pass) {
            $flash_msg  = 'รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน';
            $flash_type = 'error';
        } elseif (strlen($new_pass) < 4) {
            $flash_msg  = 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 4 ตัวอักษร';
            $flash_type = 'error';
        } else {
            // ตรวจสอบรหัสผ่านเดิม (รองรับทั้ง password_verify หรือ plain text เดิม)
            $db_pass = $employee['emp_pass'];
            $pass_valid = false;

            if (password_verify($old_pass, $db_pass)) {
                $pass_valid = true;
            } elseif ($old_pass === $db_pass) {
                $pass_valid = true;
            }

            if (!$pass_valid) {
                $flash_msg  = 'รหัสผ่านเดิมไม่ถูกต้อง';
                $flash_type = 'error';
            } else {
                try {
                    $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);
                    $stmt_pw = $conn->prepare("UPDATE employee SET emp_pass = ?, updated_at = NOW() WHERE emp_id = ?");
                    $stmt_pw->execute([$hashed_password, $session_emp_id]);

                    $employee['emp_pass'] = $hashed_password;
                    $flash_msg  = 'เปลี่ยนรหัสผ่านสำเร็จเรียบร้อยแล้ว';
                    $flash_type = 'success';
                } catch (Exception $e) {
                    $flash_msg  = 'เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน: ' . $e->getMessage();
                    $flash_type = 'error';
                }
            }
        }
    }
}

// ─────────────────────────────────────────────────────────────
// ดึงข้อมูลรถตัดที่พนักงานคนนี้ดูแล
// ─────────────────────────────────────────────────────────────
$assigned_harvesters = [];
try {
    $stmt_h = $conn->prepare("
        SELECT h.harvester_id, h.harvester_number, h.harvester_name, eh.assigned_at
        FROM employee_harvester eh
        JOIN harvesters h ON eh.harvester_id = h.harvester_id
        WHERE (eh.emp_id = ? OR eh.emp_id = ?) AND h.is_active = 1
        ORDER BY h.harvester_id ASC
    ");
    $stmt_h->execute([$employee['ID'], $employee['emp_id']]);
    $assigned_harvesters = $stmt_h->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$initials = mb_substr(trim($employee['emp_name'] ?: 'U'), 0, 2, 'UTF-8');
$role_badge = ($employee['emp_level'] === 'a') 
    ? '<span class="badge-role badge-admin"><i class="fa-solid fa-shield-halved"></i> ผู้ดูแลระบบ (Admin)</span>' 
    : (($employee['emp_level'] === 'm')
        ? '<span class="badge-role badge-mech"><i class="fa-solid fa-wrench"></i> นายช่าง (Mechanic)</span>'
        : '<span class="badge-role badge-user"><i class="fa-solid fa-user-tie"></i> พนักงานฝ่ายไร่</span>');

include 'includes/nav_u_header.php';
?>

<style>
* { box-sizing: border-box; }
body { font-family: 'Sarabun', sans-serif; background: #f8fafc; margin: 0; color: #1e293b; }

.page-wrapper { display: flex; min-height: 100vh; width: 100%; align-items: flex-start; }
.dash-wrap { flex: 1; padding: 24px 28px 60px; min-width: 0; overflow-x: hidden; }
.content-wrapper { max-width: 740px; margin: 0 auto; }

/* ── Breadcrumb ── */
.breadcrumb {
    display: flex; align-items: center; gap: 8px;
    font-size: 0.84rem; color: #64748b; margin-bottom: 20px;
}
.breadcrumb a { color: #3b82f6; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
.breadcrumb a:hover { text-decoration: underline; }
.breadcrumb i { font-size: 10px; color: #94a3b8; }

/* ── Profile Header Card ── */
.profile-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 20px rgba(0,0,0,0.04);
    overflow: hidden;
    margin-bottom: 20px;
}
.profile-top-banner {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    padding: 24px 26px;
    color: white;
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
    position: relative;
}
.avatar-circle {
    width: 68px; height: 68px;
    background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);
    color: white; font-size: 1.5rem; font-weight: 800;
    border-radius: 16px; display: flex; align-items: center; justify-content: center;
    border: 3px solid rgba(255,255,255,0.2);
    box-shadow: 0 8px 16px rgba(225,29,72,0.3);
    flex-shrink: 0;
}
.profile-info { flex: 1; min-width: 200px; }
.profile-info h1 { margin: 0 0 6px; font-size: 1.25rem; font-weight: 800; color: white; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.profile-sub { color: #94a3b8; font-size: 0.84rem; display: flex; gap: 14px; flex-wrap: wrap; }
.profile-sub span { display: inline-flex; align-items: center; gap: 5px; }

.badge-role {
    font-size: 0.72rem; font-weight: 700; padding: 3px 10px; border-radius: 20px;
    display: inline-flex; align-items: center; gap: 5px;
}
.badge-admin { background: #fee2e2; color: #be123c; }
.badge-mech  { background: #fef3c7; color: #b45309; }
.badge-user  { background: #e0f2fe; color: #0369a1; }

/* ── Section Cards ── */
.form-section-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 10px rgba(0,0,0,0.03);
    padding: 24px;
    margin-bottom: 20px;
}
.sec-hd {
    display: flex; align-items: center; gap: 10px;
    margin-bottom: 18px; padding-bottom: 12px;
    border-bottom: 1.5px solid #f1f5f9;
}
.sec-hd-icon {
    width: 34px; height: 34px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.95rem;
}
.sec-hd-title { font-size: 0.95rem; font-weight: 800; color: #0f172a; margin: 0; }
.sec-hd-desc { font-size: 0.76rem; color: #64748b; margin: 2px 0 0; }

.f-group { margin-bottom: 16px; }
.f-label { display: block; font-size: 0.8rem; font-weight: 700; color: #334155; margin-bottom: 6px; }
.f-input {
    width: 100%; padding: 10px 14px;
    border: 1.5px solid #e2e8f0; border-radius: 10px;
    font-size: 0.88rem; font-family: 'Sarabun', sans-serif;
    color: #1e293b; background: #f8fafc; outline: none;
    transition: all 0.15s ease;
}
.f-input:focus { border-color: #0284c7; background: white; box-shadow: 0 0 0 3px rgba(2,132,199,0.1); }
.f-input:disabled { background: #f1f5f9; color: #94a3b8; cursor: not-allowed; border-color: #e2e8f0; }

.pass-wrapper { position: relative; }
.pass-toggle-btn {
    position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    background: none; border: none; color: #94a3b8; font-size: 0.85rem;
    cursor: pointer; padding: 4px;
}
.pass-toggle-btn:hover { color: #334155; }

.btn-submit-save {
    padding: 10px 22px; background: #0f172a; color: white; border: none;
    border-radius: 10px; font-weight: 700; font-size: 0.86rem;
    font-family: 'Sarabun', sans-serif; cursor: pointer;
    display: inline-flex; align-items: center; gap: 8px;
    transition: all 0.15s ease;
}
.btn-submit-save:hover { background: #1e293b; transform: translateY(-1px); }

/* Harvester Chips */
.hv-chip-grid { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 6px; }
.hv-chip {
    background: #f0fdf4; border: 1.5px solid #bbf7d0; border-radius: 10px;
    padding: 6px 12px; font-size: 0.82rem; font-weight: 700; color: #166534;
    display: inline-flex; align-items: center; gap: 6px;
}

/* ══════════════════════════════════════════
   DARK MODE OVERRIDES
   ══════════════════════════════════════════ */
.dark-mode body,
html.dark-mode body {
    background: #090d16 !important;
    color: #f1f5f9 !important;
}
.dark-mode .dash-wrap {
    background: #090d16 !important;
}
.dark-mode .form-section-card,
.dark-mode .profile-card {
    background: #131b2e !important;
    border-color: #1e293b !important;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3) !important;
}
.dark-mode .sec-hd {
    border-bottom-color: #1e293b !important;
}
.dark-mode .sec-hd-title {
    color: #f8fafc !important;
}
.dark-mode .sec-hd-desc {
    color: #94a3b8 !important;
}
.dark-mode .f-label {
    color: #cbd5e1 !important;
}
.dark-mode .f-input {
    background: #0b1120 !important;
    color: #f8fafc !important;
    border-color: #1e293b !important;
}
.dark-mode .f-input:focus {
    background: #0f172a !important;
    border-color: #0284c7 !important;
    box-shadow: 0 0 0 3px rgba(2,132,199,0.2) !important;
}
.dark-mode .f-input:disabled {
    background: #070b14 !important;
    color: #64748b !important;
    border-color: #1e293b !important;
}
.dark-mode .pass-toggle-btn {
    color: #64748b !important;
}
.dark-mode .pass-toggle-btn:hover {
    color: #cbd5e1 !important;
}
.dark-mode .hv-chip {
    background: rgba(16, 185, 129, 0.12) !important;
    border-color: rgba(16, 185, 129, 0.3) !important;
    color: #34d399 !important;
}
.dark-mode .breadcrumb {
    color: #64748b !important;
}
.dark-mode .breadcrumb a {
    color: #38bdf8 !important;
}
</style>

<div class="page-wrapper">
    <?php include 'includes/nav_u_sidebar.php'; ?>

    <div class="dash-wrap">
        <div class="content-wrapper">

            <!-- Breadcrumb -->
            <div class="breadcrumb">
                <a href="index.php"><i class="fa-solid fa-house"></i> หน้าแรก</a>
                <i class="fa-solid fa-chevron-right"></i>
                <span>โปรไฟล์ของฉัน</span>
            </div>

            <!-- Profile Top Header -->
            <div class="profile-card">
                <div class="profile-top-banner">
                    <div class="avatar-circle">
                        <?php echo htmlspecialchars($initials); ?>
                    </div>
                    <div class="profile-info">
                        <h1>
                            <?php echo htmlspecialchars($employee['emp_name']); ?>
                            <?php echo $role_badge; ?>
                        </h1>
                        <div class="profile-sub">
                            <span><i class="fa-solid fa-id-badge"></i> รหัส: <strong><?php echo htmlspecialchars($employee['emp_id']); ?></strong></span>
                            <span><i class="fa-solid fa-building"></i> หน่วยงาน: <strong><?php echo htmlspecialchars($employee['emp_unit'] ?: '-'); ?></strong></span>
                            <span><i class="fa-solid fa-calendar"></i> ปีการผลิต: <strong><?php echo htmlspecialchars($_SESSION['crop_year'] ?? '-'); ?></strong></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 1. ข้อมูลส่วนตัว (Personal Info Form) -->
            <div class="form-section-card">
                <div class="sec-hd">
                    <div class="sec-hd-icon" style="background:#e0f2fe; color:#0284c7;">
                        <i class="fa-solid fa-user-pen"></i>
                    </div>
                    <div>
                        <h2 class="sec-hd-title">ข้อมูลส่วนตัวและตำแหน่งงาน</h2>
                        <p class="sec-hd-desc">ดูข้อมูลสังกัดและแก้ไขชื่อ-นามสกุลของคุณ</p>
                    </div>
                </div>

                <form method="POST" action="profile.php">
                    <input type="hidden" name="action" value="update_profile">

                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:14px;">
                        <div class="f-group">
                            <label class="f-label">รหัสพนักงาน</label>
                            <input type="text" value="<?php echo htmlspecialchars($employee['emp_id']); ?>" class="f-input" disabled>
                        </div>
                        <div class="f-group">
                            <label class="f-label">หน่วยงาน / สังกัด</label>
                            <input type="text" value="<?php echo htmlspecialchars($employee['emp_unit'] ?: '-'); ?>" class="f-input" disabled>
                        </div>
                    </div>

                    <div class="f-group">
                        <label class="f-label">ชื่อ-นามสกุล <span style="color:#e11d48;">*</span></label>
                        <input type="text" name="emp_name" value="<?php echo htmlspecialchars($employee['emp_name']); ?>" class="f-input" required>
                    </div>

                    <?php if (!empty($assigned_harvesters)): ?>
                    <div class="f-group" style="margin-top:14px;">
                        <label class="f-label"><i class="fa-solid fa-tractor" style="color:#10b981;"></i> รถตัดอ้อยที่อยู่ในความดูแลของคุณ</label>
                        <div class="hv-chip-grid">
                            <?php foreach ($assigned_harvesters as $hv): ?>
                            <div class="hv-chip">
                                <i class="fa-solid fa-tractor"></i>
                                <span><?php echo htmlspecialchars($hv['harvester_number']); ?><?php echo $hv['harvester_name'] ? ' (' . htmlspecialchars($hv['harvester_name']) . ')' : ''; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div style="margin-top:20px; text-align:right;">
                        <button type="submit" class="btn-submit-save">
                            <i class="fa-solid fa-floppy-disk"></i> บันทึกข้อมูลส่วนตัว
                        </button>
                    </div>
                </form>
            </div>

            <!-- 2. เปลี่ยนรหัสผ่าน (Change Password Form) -->
            <div class="form-section-card">
                <div class="sec-hd">
                    <div class="sec-hd-icon" style="background:#fee2e2; color:#e11d48;">
                        <i class="fa-solid fa-key"></i>
                    </div>
                    <div>
                        <h2 class="sec-hd-title">เปลี่ยนรหัสผ่านเข้าใช้งาน (Security)</h2>
                        <p class="sec-hd-desc">ตั้งรหัสผ่านใหม่เพื่อความปลอดภัยในการเข้าใช้งานระบบ</p>
                    </div>
                </div>

                <form method="POST" action="profile.php" onsubmit="return validatePasswordForm()">
                    <input type="hidden" name="action" value="change_password">

                    <div class="f-group">
                        <label class="f-label">รหัสผ่านปัจจุบัน <span style="color:#e11d48;">*</span></label>
                        <div class="pass-wrapper">
                            <input type="password" name="old_password" id="old_password" class="f-input" required placeholder="กรอกรหัสผ่านเดิม">
                            <button type="button" class="pass-toggle-btn" onclick="togglePass('old_password', this)"><i class="fa-solid fa-eye"></i></button>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:14px;">
                        <div class="f-group">
                            <label class="f-label">รหัสผ่านใหม่ <span style="color:#e11d48;">*</span></label>
                            <div class="pass-wrapper">
                                <input type="password" name="new_password" id="new_password" class="f-input" required placeholder="อย่างน้อย 4 ตัวอักษร">
                                <button type="button" class="pass-toggle-btn" onclick="togglePass('new_password', this)"><i class="fa-solid fa-eye"></i></button>
                            </div>
                        </div>

                        <div class="f-group">
                            <label class="f-label">ยืนยันรหัสผ่านใหม่ <span style="color:#e11d48;">*</span></label>
                            <div class="pass-wrapper">
                                <input type="password" name="confirm_password" id="confirm_password" class="f-input" required placeholder="กรอกรหัสใหม่อีกครั้ง">
                                <button type="button" class="pass-toggle-btn" onclick="togglePass('confirm_password', this)"><i class="fa-solid fa-eye"></i></button>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top:20px; text-align:right;">
                        <button type="submit" class="btn-submit-save" style="background:#e11d48;">
                            <i class="fa-solid fa-shield-check"></i> บันทึกรหัสผ่านใหม่
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<script>
// Toggle Password Visibility
function togglePass(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Client-side Password Validation
function validatePasswordForm() {
    const newPass = document.getElementById('new_password').value;
    const confPass = document.getElementById('confirm_password').value;

    if (newPass.length < 4) {
        Swal.fire({
            icon: 'warning',
            title: 'รหัสผ่านสั้นเกินไป',
            text: 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 4 ตัวอักษร',
            confirmButtonText: 'ตกลง',
            confirmButtonColor: '#e11d48'
        });
        return false;
    }

    if (newPass !== confPass) {
        Swal.fire({
            icon: 'error',
            title: 'รหัสผ่านไม่ตรงกัน',
            text: 'รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน กรุณาตรวจสอบอีกครั้ง',
            confirmButtonText: 'ตกลง',
            confirmButtonColor: '#e11d48'
        });
        return false;
    }
    return true;
}

// SweetAlert Flash Message
<?php if (!empty($flash_msg)): ?>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon: '<?php echo $flash_type; ?>',
        title: '<?php echo ($flash_type === "success") ? "บันทึกข้อมูลสำเร็จ!" : "แจ้งเตือนข้อผิดพลาด"; ?>',
        text: <?php echo json_encode($flash_msg, JSON_UNESCAPED_UNICODE); ?>,
        confirmButtonText: 'ตกลง',
        confirmButtonColor: '<?php echo ($flash_type === "success") ? "#10b981" : "#e11d48"; ?>',
        timer: <?php echo ($flash_type === "success") ? "3500" : "null"; ?>,
        timerProgressBar: <?php echo ($flash_type === "success") ? "true" : "false"; ?>,
        customClass: {
            popup: 'sa2-th'
        }
    });
});
<?php endif; ?>
</script>
<style>
.sa2-th { font-family: 'Sarabun', sans-serif !important; border-radius: 16px !important; }
</style>
</body>
</html>
