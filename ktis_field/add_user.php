<?php
/**
 * add_user.php — เพิ่มพนักงานใหม่ (Admin)
 * TIS SMART FIELD - ฝ่ายไร่
 */
require_once 'config.php';
session_start();

if (!isset($_SESSION['emp_level']) || $_SESSION['emp_level'] !== 'a') {
    header("Location: login.php");
    exit;
}

$zones = [];
try {
    $zones = $conn->query("SELECT zone_id, zone_name FROM zones ORDER BY zone_id ASC")->fetchAll();
} catch(Exception $e){}

$message = "";
// ดึงรายชื่อรถตัดทั้งหมด
$all_harvesters = [];
try {
    $stmt_all_h = $conn->query("SELECT harvester_id, harvester_number, harvester_name FROM harvesters WHERE is_active = 1 ORDER BY harvester_id ASC");
    $all_harvesters = $stmt_all_h->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {}

$old = ['emp_id' => '', 'emp_name' => '', 'emp_unit' => '', 'emp_level' => 'u', 'is_harvester_manager' => 0, 'harvesters' => []];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $old = [
        'emp_id'               => htmlspecialchars($_POST['emp_id'] ?? ''),
        'emp_name'             => htmlspecialchars($_POST['emp_name'] ?? ''),
        'emp_unit'             => $_POST['emp_unit'] ?? '',
        'emp_level'            => $_POST['emp_level'] ?? 'u',
        'is_harvester_manager' => isset($_POST['is_harvester_manager']) ? 1 : 0,
        'harvesters'           => $_POST['harvesters'] ?? []
    ];
    
    $emp_id     = trim($_POST['emp_id'] ?? '');
    $name       = trim($_POST['emp_name'] ?? '');
    $unit       = trim($_POST['emp_unit'] ?? '');
    $level      = trim($_POST['emp_level'] ?? 'u');
    $pass_plain = trim($_POST['emp_pass'] ?? '');
    $is_mgr     = isset($_POST['is_harvester_manager']) ? 1 : 0;
    $selected_hvs = $_POST['harvesters'] ?? [];

    if (empty($emp_id) || empty($name) || empty($unit) || empty($pass_plain)) {
        $message = "error:กรุณากรอกข้อมูลให้ครบทุกช่อง";
    } else {
        try {
            $check = $conn->prepare("SELECT emp_id FROM employee WHERE emp_id = ?");
            $check->execute([$emp_id]);

            if ($check->rowCount() > 0) {
                $message = "error:รหัสพนักงาน \"$emp_id\" มีอยู่ในระบบแล้ว กรุณาใช้รหัสอื่น";
            } else {
                $password = password_hash($pass_plain, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("INSERT INTO employee (emp_id, emp_pass, emp_name, emp_unit, emp_level, is_harvester_manager, status) VALUES (?, ?, ?, ?, ?, ?, 1)");
                $stmt->execute([$emp_id, $password, $name, $unit, $level, $is_mgr]);
                $new_id = $conn->lastInsertId();

                if ($is_mgr && !empty($selected_hvs) && is_array($selected_hvs)) {
                    $stmt_ins_h = $conn->prepare("INSERT INTO employee_harvester (emp_id, harvester_id, assigned_at) VALUES (?, ?, NOW())");
                    foreach ($selected_hvs as $hid) {
                        $hid = intval($hid);
                        if ($hid > 0) {
                            $stmt_ins_h->execute([$new_id, $hid]);
                        }
                    }
                }
                
                $_SESSION['flash_msg'] = "เพิ่มพนักงานคุณ " . $name . " เข้าสู่ระบบเรียบร้อยแล้ว";
                header("Location: manage_users.php");
                exit();
            }
        } catch (PDOException $e) {
            $message = "error:เกิดข้อผิดพลาดจากฐานข้อมูล: " . $e->getMessage();
        }
    }
}

$msg_type = '';
$msg_text = '';
if ($message) {
    [$msg_type, $msg_text] = explode(':', $message, 2);
}

include 'includes/nav_u_header.php';
?>

<style>
* { box-sizing: border-box; }
body { font-family: 'Sarabun', sans-serif; background: #f8fafc; margin: 0; color: #1e293b; }

.page-wrapper { display: flex; min-height: 100vh; width: 100%; align-items: flex-start; }
.dash-wrap { flex: 1; padding: 24px 28px 60px; min-width: 0; overflow-x: hidden; }
.content-wrapper { max-width: 620px; margin: 0 auto; }

/* ── Breadcrumb ── */
.breadcrumb {
    display: flex; align-items: center; gap: 8px;
    font-size: 0.84rem; color: #64748b; margin-bottom: 20px;
}
.breadcrumb a { color: #3b82f6; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
.breadcrumb a:hover { text-decoration: underline; }
.breadcrumb i { font-size: 10px; color: #94a3b8; }

/* ── Card ── */
.form-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 20px rgba(0,0,0,0.04);
    overflow: hidden;
}
.card-header {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    padding: 20px 24px;
    display: flex; align-items: center; gap: 14px;
    border-bottom: 3px solid #10b981;
}
.card-header-icon {
    width: 44px; height: 44px;
    background: linear-gradient(135deg, #10b981, #059669);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 4px 10px rgba(16,185,129,0.3);
    flex-shrink: 0;
}
.card-header-icon i { color: white; font-size: 1.25rem; }
.card-header-title { color: #f8fafc; font-weight: 800; font-size: 1.1rem; }
.card-header-sub { color: #94a3b8; font-size: 0.78rem; margin-top: 2px; }

.card-body { padding: 26px; }

/* ── Alert ── */
.alert {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 12px 16px; border-radius: 10px; margin-bottom: 20px;
    font-size: 0.88rem; font-weight: 600;
}
.alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.alert i { margin-top: 2px; flex-shrink: 0; }

/* ── Form Controls ── */
.form-group { margin-bottom: 18px; }
.form-label {
    display: block; font-weight: 700; font-size: 0.84rem;
    color: #374151; margin-bottom: 7px;
}
.form-label .req { color: #e11d48; margin-left: 2px; }
.form-label .hint { font-weight: 400; color: #94a3b8; font-size: 0.78rem; margin-left: 4px; }

.form-input {
    width: 100%; padding: 11px 14px;
    border: 1.5px solid #e2e8f0; border-radius: 10px;
    background: #f8fafc; font-size: 0.92rem;
    font-family: 'Sarabun', sans-serif; color: #1e293b;
    transition: all 0.15s; outline: none;
}
.form-input:focus {
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16,185,129,.12);
    background: white;
}
select.form-input {
    appearance: none; cursor: pointer;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 14px center; padding-right: 36px;
}

/* Password Toggle */
.pw-wrap { position: relative; }
.pw-wrap .form-input { padding-right: 44px; }
.pw-toggle {
    position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    background: none; border: none; cursor: pointer; color: #94a3b8; padding: 6px;
}
.pw-toggle:hover { color: #475569; }

/* Level Radio Cards */
.level-selector { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; }
.level-option { display: none; }
.level-label {
    display: flex; flex-direction: column; align-items: center; gap: 6px;
    padding: 14px 10px; border-radius: 12px; border: 2px solid #e2e8f0;
    cursor: pointer; transition: all 0.15s; text-align: center; background: #f8fafc;
}
.level-label i { font-size: 1.35rem; color: #94a3b8; }
.level-label .lv-title { font-weight: 700; font-size: 0.88rem; color: #475569; }
.level-label .lv-desc  { font-size: 0.74rem; color: #94a3b8; line-height: 1.3; }

input#lv-u:checked + label { border-color: #10b981; background: #f0fdf4; }
input#lv-u:checked + label i { color: #10b981; }
input#lv-u:checked + label .lv-title { color: #065f46; }

input#lv-m:checked + label { border-color: #f59e0b; background: #fffbeb; }
input#lv-m:checked + label i { color: #f59e0b; }
input#lv-m:checked + label .lv-title { color: #b45309; }

input#lv-a:checked + label { border-color: #e11d48; background: #fef2f2; }
input#lv-a:checked + label i { color: #e11d48; }
input#lv-a:checked + label .lv-title { color: #991b1b; }

/* Switch Box */
.toggle-box {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 16px; background: #f8fafc; border: 1.5px solid #e2e8f0;
    border-radius: 12px; margin-top: 10px;
}
.toggle-info { display: flex; flex-direction: column; gap: 2px; }
.toggle-title { font-weight: 700; font-size: 0.86rem; color: #1e293b; }
.toggle-sub { font-size: 0.75rem; color: #64748b; }

.switch { position: relative; display: inline-block; width: 44px; height: 24px; }
.switch input { opacity: 0; width: 0; height: 0; }
.slider {
    position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
    background-color: #cbd5e1; transition: .3s; border-radius: 24px;
}
.slider:before {
    position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px;
    background-color: white; transition: .3s; border-radius: 50%;
}
input:checked + .slider { background-color: #0284c7; }
input:checked + .slider:before { transform: translateX(20px); }

/* Harvester Selection Card */
.hv-selection-card {
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px;
    margin-top: 10px;
    animation: fadeIn 0.2s ease;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
}
.hv-sel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
    flex-wrap: wrap;
    gap: 8px;
}
.hv-sel-title { font-weight: 700; font-size: 0.88rem; color: #1e293b; display: flex; align-items: center; gap: 6px; }
.hv-sel-counter {
    background: #fef3c7; color: #92400e; padding: 3px 10px;
    border-radius: 12px; font-size: 0.75rem; font-weight: 700;
}

.hv-sel-tools {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
    flex-wrap: wrap;
}
.hv-search-input {
    flex: 1;
    min-width: 160px;
    padding: 8px 12px;
    border: 1.5px solid #cbd5e1;
    border-radius: 8px;
    font-size: 0.84rem;
    font-family: 'Sarabun', sans-serif;
    outline: none;
    background: white;
}
.hv-search-input:focus { border-color: #f59e0b; }
.hv-tool-btns { display: flex; gap: 6px; }
.btn-hv-tool {
    padding: 6px 10px;
    background: white;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 700;
    color: #475569;
    font-family: 'Sarabun', sans-serif;
    cursor: pointer;
    transition: all 0.15s;
}
.btn-hv-tool:hover { background: #f1f5f9; color: #1e293b; }

.hv-chips-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
    gap: 6px;
    max-height: 240px;
    overflow-y: auto;
    padding: 4px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
}
.hv-select-chip {
    padding: 8px 10px;
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.78rem;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    user-select: none;
    transition: all 0.15s;
}
.hv-select-chip .check-icon { display: none; font-size: 0.7rem; }
.hv-select-chip:hover { border-color: #f59e0b; background: #fffbeb; }
.hv-select-chip.selected {
    background: #fef3c7;
    border-color: #f59e0b;
    color: #92400e;
    font-weight: 700;
}
.hv-select-chip.selected .check-icon { display: inline-block; color: #d97706; }

/* Buttons */
.btn-row { display: flex; gap: 10px; margin-top: 24px; }
.btn-submit {
    flex: 2; padding: 12px 20px; background: #10b981; color: white;
    border: none; border-radius: 10px; font-weight: 700; font-size: 0.92rem;
    font-family: 'Sarabun', sans-serif; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    box-shadow: 0 4px 12px rgba(16,185,129,0.3); transition: all 0.15s;
}
.btn-submit:hover { background: #059669; transform: translateY(-1px); }
.btn-cancel {
    flex: 1; padding: 12px 16px; background: #f1f5f9; color: #475569;
    border: 1px solid #e2e8f0; border-radius: 10px; font-weight: 700;
    font-size: 0.92rem; font-family: 'Sarabun', sans-serif; cursor: pointer;
    text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 6px;
    transition: all 0.15s;
}
.btn-cancel:hover { background: #e2e8f0; color: #1e293b; }

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
.dark-mode .form-card {
    background: #131b2e !important;
    border-color: #1e293b !important;
}
.dark-mode .form-label {
    color: #cbd5e1 !important;
}
.dark-mode .form-input,
.dark-mode .form-select {
    background: #0b1120 !important;
    color: #f8fafc !important;
    border-color: #1e293b !important;
}
.dark-mode .form-input:focus,
.dark-mode .form-select:focus {
    background: #0f172a !important;
    border-color: #10b981 !important;
}
.dark-mode .level-label {
    background: #0b1120 !important;
    border-color: #1e293b !important;
}
.dark-mode .toggle-box {
    background: #0b1120 !important;
    border-color: #1e293b !important;
}
.dark-mode .toggle-title {
    color: #f8fafc !important;
}
.dark-mode .toggle-sub {
    color: #94a3b8 !important;
}
.dark-mode .hv-selection-card {
    background: #0b1120 !important;
    border-color: #1e293b !important;
}
.dark-mode .hv-sel-title {
    color: #f8fafc !important;
}
.dark-mode .hv-search-input {
    background: #131b2e !important;
    border-color: #1e293b !important;
    color: #f8fafc !important;
}
.dark-mode .btn-hv-tool {
    background: #131b2e !important;
    border-color: #1e293b !important;
    color: #cbd5e1 !important;
}
.dark-mode .btn-hv-tool:hover {
    background: #1e293b !important;
    color: #f8fafc !important;
}
.dark-mode .hv-chips-grid {
    background: #070b14 !important;
    border-color: #1e293b !important;
}
.dark-mode .hv-select-chip {
    background: #131b2e !important;
    border-color: #1e293b !important;
    color: #cbd5e1 !important;
}
.dark-mode .hv-select-chip:hover {
    border-color: #f59e0b !important;
    background: rgba(245, 158, 11, 0.15) !important;
    color: #f59e0b !important;
}
.dark-mode .hv-select-chip.selected {
    background: rgba(245, 158, 11, 0.2) !important;
    border-color: #f59e0b !important;
    color: #fbbf24 !important;
}
.dark-mode .btn-cancel {
    background: #1e293b !important;
    border-color: #334155 !important;
    color: #cbd5e1 !important;
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
                <a href="manage_users.php"><i class="fa-solid fa-users-gear"></i> จัดการพนักงาน</a>
                <i class="fa-solid fa-chevron-right"></i>
                <span>เพิ่มพนักงานใหม่</span>
            </div>

            <!-- Form Card -->
            <div class="form-card">
                <div class="card-header">
                    <div class="card-header-icon">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                    <div>
                        <div class="card-header-title">เพิ่มพนักงานใหม่</div>
                        <div class="card-header-sub">กรอกข้อมูลเพื่อสร้างบัญชีผู้ใช้งานในระบบ</div>
                    </div>
                </div>

                <div class="card-body">
                    <?php if ($message): ?>
                    <div class="alert alert-<?php echo $msg_type; ?>">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <div><?php echo htmlspecialchars($msg_text); ?></div>
                    </div>
                    <?php endif; ?>

                    <form method="POST" action="add_user.php" autocomplete="off">
                        <!-- รหัสพนักงาน -->
                        <div class="form-group">
                            <label class="form-label" for="emp_id">
                                รหัสพนักงาน <span class="req">*</span>
                                <span class="hint">(เช่น TIS-111 หรือ admin02)</span>
                            </label>
                            <input type="text" id="emp_id" name="emp_id" class="form-input"
                                   placeholder="เช่น TIS-111" required
                                   value="<?php echo $old['emp_id']; ?>">
                        </div>

                        <!-- ชื่อ-นามสกุล -->
                        <div class="form-group">
                            <label class="form-label" for="emp_name">
                                ชื่อ - นามสกุล <span class="req">*</span>
                            </label>
                            <input type="text" id="emp_name" name="emp_name" class="form-input"
                                   placeholder="เช่น นายสมชาย ใจดี" required
                                   value="<?php echo $old['emp_name']; ?>">
                        </div>

                        <!-- หน่วยส่งเสริม -->
                        <div class="form-group">
                            <label class="form-label" for="emp_unit">
                                หน่วยส่งเสริม / ประจำสังกัด <span class="req">*</span>
                            </label>
                            <select id="emp_unit" name="emp_unit" class="form-input" required>
                                <option value="">-- เลือกหน่วยส่งเสริม --</option>
                                <?php foreach ($zones as $zone): ?>
                                    <?php
                                    $unit_val = $zone['zone_id'] === '000'
                                        ? $zone['zone_name']
                                        : $zone['zone_id'] . ' ' . $zone['zone_name'];
                                    ?>
                                    <option value="<?php echo htmlspecialchars($unit_val); ?>"
                                        <?php echo $old['emp_unit'] === $unit_val ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($unit_val); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- รหัสผ่าน -->
                        <div class="form-group">
                            <label class="form-label" for="emp_pass">
                                รหัสผ่านเริ่มต้น <span class="req">*</span>
                            </label>
                            <div class="pw-wrap">
                                <input type="password" id="emp_pass" name="emp_pass" class="form-input"
                                       placeholder="กำหนดรหัสผ่านเข้าใช้งาน" required>
                                <button type="button" class="pw-toggle" onclick="togglePw()" title="แสดง/ซ่อนรหัสผ่าน">
                                    <i class="fa-regular fa-eye" id="pw-icon"></i>
                                </button>
                            </div>
                        </div>

                        <!-- สิทธิ์การใช้งาน -->
                        <div class="form-group">
                            <label class="form-label">สิทธิ์การใช้งานระบบ <span class="req">*</span></label>
                            <div class="level-selector">
                                <input type="radio" id="lv-u" name="emp_level" value="u" class="level-option"
                                    <?php echo $old['emp_level'] === 'u' ? 'checked' : ''; ?>>
                                <label for="lv-u" class="level-label">
                                    <i class="fa-solid fa-user"></i>
                                    <span class="lv-title">พนักงานทั่วไป (User)</span>
                                    <span class="lv-desc">บันทึกข้อมูลและดูรายการในระบบ</span>
                                </label>

                                <input type="radio" id="lv-m" name="emp_level" value="m" class="level-option"
                                    <?php echo $old['emp_level'] === 'm' ? 'checked' : ''; ?>>
                                <label for="lv-m" class="level-label">
                                    <i class="fa-solid fa-wrench"></i>
                                    <span class="lv-title">นายช่าง (Mechanic)</span>
                                    <span class="lv-desc">ดูแดชบอร์ดและรายงานตรวจเช็กรถตัดได้</span>
                                </label>

                                <input type="radio" id="lv-a" name="emp_level" value="a" class="level-option"
                                    <?php echo $old['emp_level'] === 'a' ? 'checked' : ''; ?>>
                                <label for="lv-a" class="level-label">
                                    <i class="fa-solid fa-shield-halved"></i>
                                    <span class="lv-title">ผู้ดูแลระบบ (Admin)</span>
                                    <span class="lv-desc">เข้าถึงทุกเมนู จัดการข้อมูลและตั้งค่า</span>
                                </label>
                            </div>
                        </div>

                        <!-- สิทธิ์ผู้ดูแลรถตัด -->
                        <div class="form-group">
                            <div class="toggle-box">
                                <div class="toggle-info">
                                    <span class="toggle-title"><i class="fa-solid fa-tractor" style="color:#0284c7; margin-right:4px;"></i> ผู้ดูแลรถตัด (Harvester Manager)</span>
                                    <span class="toggle-sub">เปิดใช้งานเพื่อให้พนักงานสามารถบันทึกตรวจเช็กรถตัดได้</span>
                                </div>
                                <label class="switch">
                                    <input type="checkbox" id="is_harvester_manager" name="is_harvester_manager" value="1" 
                                           <?php echo $old['is_harvester_manager'] ? 'checked' : ''; ?>
                                           onchange="toggleHarvesterBlock(this.checked)">
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <!-- กล่องเลือกรถตัดที่ดูแล (เปิดเมื่อเปิดสิทธิ์ผู้ดูแลรถตัด) -->
                            <div id="harvesterSelectionBlock" class="hv-selection-card" style="<?php echo $old['is_harvester_manager'] ? 'display:block;' : 'display:none;'; ?>">
                                <div class="hv-sel-header">
                                    <div class="hv-sel-title">
                                        <i class="fa-solid fa-tractor" style="color:#f59e0b;"></i> เลือกรถตัดที่รับผิดชอบ
                                    </div>
                                    <div class="hv-sel-counter" id="hvSelectedCount">
                                        เลือกอยู่ <strong><?php echo count($old['harvesters']); ?></strong> คัน
                                    </div>
                                </div>

                                <div class="hv-sel-tools">
                                    <input type="text" id="hvSearchInput" class="hv-search-input" placeholder="🔍 ค้นหาเบอร์รถตัด..." oninput="filterHvChips(this.value)">
                                    <div class="hv-tool-btns">
                                        <button type="button" class="btn-hv-tool" onclick="selectAllHv(true)">เลือกทั้งหมด</button>
                                        <button type="button" class="btn-hv-tool" onclick="selectAllHv(false)">ล้างทั้งหมด</button>
                                    </div>
                                </div>

                                <div class="hv-chips-grid" id="hvChipsGrid">
                                    <?php if(!empty($all_harvesters)): ?>
                                        <?php foreach($all_harvesters as $h): 
                                            $is_sel = in_array($h['harvester_id'], $old['harvesters'] ?? []);
                                        ?>
                                        <div class="hv-select-chip <?php echo $is_sel ? 'selected' : ''; ?>" 
                                             data-num="<?php echo strtolower(htmlspecialchars($h['harvester_number'])); ?>"
                                             onclick="toggleHvChip(this)">
                                            <input type="checkbox" name="harvesters[]" value="<?php echo $h['harvester_id']; ?>" 
                                                   class="hv-chk" <?php echo $is_sel ? 'checked' : ''; ?> style="display:none;">
                                            <i class="fa-solid fa-tractor" style="font-size:0.75rem;"></i>
                                            <span><?php echo htmlspecialchars($h['harvester_number']); ?></span>
                                            <i class="fa-solid fa-check check-icon"></i>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div style="color:#94a3b8; font-size:0.82rem; padding:10px;">ไม่พบรายการรถตัดในระบบ</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="btn-row">
                            <a href="manage_users.php" class="btn-cancel">
                                <i class="fa-solid fa-arrow-left"></i> ยกเลิก
                            </a>
                            <button type="submit" class="btn-submit">
                                <i class="fa-solid fa-user-plus"></i> บันทึกข้อมูล
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function togglePw() {
    const input = document.getElementById('emp_pass');
    const icon  = document.getElementById('pw-icon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fa-regular fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fa-regular fa-eye';
    }
}

function toggleHarvesterBlock(show) {
    const block = document.getElementById('harvesterSelectionBlock');
    if (block) {
        block.style.display = show ? 'block' : 'none';
    }
}

function toggleHvChip(chip) {
    const chk = chip.querySelector('.hv-chk');
    if (!chk) return;
    chk.checked = !chk.checked;
    chip.classList.toggle('selected', chk.checked);
    updateHvCounter();
}

function selectAllHv(select) {
    document.querySelectorAll('.hv-select-chip').forEach(chip => {
        if (chip.style.display !== 'none') {
            const chk = chip.querySelector('.hv-chk');
            if (chk) {
                chk.checked = select;
                chip.classList.toggle('selected', select);
            }
        }
    });
    updateHvCounter();
}

function updateHvCounter() {
    const totalSelected = document.querySelectorAll('.hv-chk:checked').length;
    const counterEl = document.getElementById('hvSelectedCount');
    if (counterEl) {
        counterEl.innerHTML = `เลือกอยู่ <strong>${totalSelected}</strong> คัน`;
    }
}

function filterHvChips(q) {
    q = (q || '').toLowerCase().trim();
    document.querySelectorAll('.hv-select-chip').forEach(chip => {
        const num = chip.getAttribute('data-num') || '';
        chip.style.display = num.includes(q) ? 'inline-flex' : 'none';
    });
}
</script>
</body>
</html>