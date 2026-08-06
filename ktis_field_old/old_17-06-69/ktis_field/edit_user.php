<?php
require_once 'config.php';
session_start();

// ตรวจสอบสิทธิ์ผู้ใช้ (เฉพาะแอดมินเท่านั้น)
if (!isset($_SESSION['emp_level']) || $_SESSION['emp_level'] != 'a') {
    die("คุณไม่มีสิทธิ์เข้าถึงหน้านี้ กรุณากลับหน้าหลัก");
}

// ตรวจสอบ ID ที่ส่งมา
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage_users.php?error=ไม่พบรหัสพนักงาน");
    exit;
}

$emp_id = intval($_GET['id']);

// ดึงข้อมูลพนักงาน
$stmt = $conn->prepare("SELECT * FROM employee WHERE ID = ?");
$stmt->execute([$emp_id]);
$employee = $stmt->fetch();

if (!$employee) {
    header("Location: manage_users.php?error=ไม่พบพนักงานที่ต้องการแก้ไข");
    exit;
}

// ดึงรายชื่อหน่วยส่งเสริมจาก zones
$zones = [];
try {
    $stmt_zones = $conn->query("SELECT zone_id, zone_name FROM zones ORDER BY zone_id ASC");
    $zones = $stmt_zones->fetchAll();
} catch(Exception $e) {}

$success_msg = '';
$error_msg = '';

// ประมวลผลการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $emp_name = trim($_POST['emp_name'] ?? '');
    $emp_id_new = trim($_POST['emp_id'] ?? '');
    $emp_unit = trim($_POST['emp_unit'] ?? '');
    $emp_level = trim($_POST['emp_level'] ?? 'u');
    $emp_pass = trim($_POST['emp_pass'] ?? '');

    // ตรวจสอบข้อมูล
    if (empty($emp_name)) {
        $error_msg = "กรุณากรอกชื่อ-นามสกุล";
    } elseif (empty($emp_id_new)) {
        $error_msg = "กรุณากรอกรหัสพนักงาน";
    } elseif (empty($emp_unit)) {
        $error_msg = "กรุณากรอกหน่วยงาน";
    } else {
        try {
            // ตรวจสอบว่ารหัสพนักงานซ้ำหรือไม่ (ยกเว้นตัวเอง)
            $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM employee WHERE emp_id = ? AND ID != ?");
            $check_stmt->execute([$emp_id_new, $emp_id]);
            $check_result = $check_stmt->fetch();

            if ($check_result['count'] > 0) {
                $error_msg = "รหัสพนักงานนี้มีในระบบแล้ว";
            } else {
                // อัปเดตข้อมูล
                if (!empty($emp_pass)) {
                    // ถ้ามีการเปลี่ยนรหัสผ่าน
                    $hashed_pass = password_hash($emp_pass, PASSWORD_BCRYPT);
                    $update_stmt = $conn->prepare("UPDATE employee SET emp_name = ?, emp_id = ?, emp_unit = ?, emp_level = ?, emp_pass = ? WHERE ID = ?");
                    $update_stmt->execute([$emp_name, $emp_id_new, $emp_unit, $emp_level, $hashed_pass, $emp_id]);
                } else {
                    // ไม่เปลี่ยนรหัสผ่าน
                    $update_stmt = $conn->prepare("UPDATE employee SET emp_name = ?, emp_id = ?, emp_unit = ?, emp_level = ? WHERE ID = ?");
                    $update_stmt->execute([$emp_name, $emp_id_new, $emp_unit, $emp_level, $emp_id]);
                }

                $success_msg = "อัปเดตข้อมูลพนักงานสำเร็จ";
                // รีโหลดข้อมูล
                $stmt = $conn->prepare("SELECT * FROM employee WHERE ID = ?");
                $stmt->execute([$emp_id]);
                $employee = $stmt->fetch();
            }
        } catch (PDOException $e) {
            $error_msg = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <link rel="icon" type="image/jpeg" href="icon/iconweb.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขพนักงาน - TIS SMART FIELD</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: 'Sarabun', sans-serif; 
            background-color: #f8fafc; 
            margin: 0;
            padding: 20px;
        }

        .content-wrapper { flex: 1 0 auto; }
        .page-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            border: 0.5px solid #e2e8f0;
            padding: 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .page-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #f1f5f9;
        }

        .page-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1e293b;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-family: 'Sarabun', sans-serif;
            font-size: 0.95rem;
            color: #1e293b;
            transition: border-color 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-group input::placeholder {
            color: #cbd5e1;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.95rem;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid #f1f5f9;
        }

        .btn {
            flex: 1;
            padding: 11px 16px;
            border: none;
            border-radius: 8px;
            font-family: 'Sarabun', sans-serif;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.2s;
            text-align: center;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #475569;
        }

        .btn-secondary:hover {
            background: #cbd5e1;
        }

        .help-text {
            font-size: 0.8rem;
            color: #94a3b8;
            margin-top: 4px;
        }

        .badge-level {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 700;
            margin-top: 4px;
        }

        .badge-admin {
            background: #fee2e2;
            color: #e11d48;
        }

        .badge-user {
            background: #d1fae5;
            color: #059669;
        }

        @media (max-width: 600px) {
            .page-container {
                padding: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .page-title {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
<div class="content-wrapper">

<?php include 'includes/nav_u_header.php'; ?>

<div class="page-container">

    <div class="page-header">
        <i class="fa-solid fa-pen-to-square" style="color:#3b82f6; font-size:1.3rem;"></i>
        <div class="page-title">แก้ไขข้อมูลพนักงาน</div>
    </div>

    <?php if ($success_msg): ?>
    <div class="alert alert-success">
        <i class="fa-solid fa-circle-check"></i>
        <?php echo htmlspecialchars($success_msg); ?>
    </div>
    <?php endif; ?>

    <?php if ($error_msg): ?>
    <div class="alert alert-error">
        <i class="fa-solid fa-circle-xmark"></i>
        <?php echo htmlspecialchars($error_msg); ?>
    </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="emp_id">รหัสพนักงาน</label>
            <input type="text" id="emp_id" name="emp_id" value="<?php echo htmlspecialchars($employee['emp_id']); ?>" required>
            <div class="help-text">รหัสประจำตัวพนักงาน</div>
        </div>

        <div class="form-group">
            <label for="emp_name">ชื่อ-นามสกุล</label>
            <input type="text" id="emp_name" name="emp_name" value="<?php echo htmlspecialchars($employee['emp_name']); ?>" required>
            <div class="help-text">ชื่อเต็มของพนักงาน</div>
        </div>

        <div class="form-group">
            <label for="emp_unit">หน่วยส่งเสริม</label>
            <select id="emp_unit" name="emp_unit" required>
                <option value="">-- เลือกหน่วยส่งเสริม --</option>
                <?php foreach($zones as $z):
                    $unit_val = ($z['zone_id'] === '000')
                        ? $z['zone_name']
                        : $z['zone_id'].' '.$z['zone_name'];
                    $selected = ($employee['emp_unit'] === $unit_val) ? 'selected' : '';
                ?>
                <option value="<?php echo htmlspecialchars($unit_val); ?>" <?php echo $selected; ?>>
                    <?php echo htmlspecialchars($unit_val); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <div class="help-text">หน่วยส่งเสริมที่พนักงานสังกัด</div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="emp_level">สิทธิ์การใช้งาน</label>
                <select id="emp_level" name="emp_level" required>
                    <option value="u" <?php echo $employee['emp_level'] == 'u' ? 'selected' : ''; ?>>พนักงาน</option>
                    <option value="a" <?php echo $employee['emp_level'] == 'a' ? 'selected' : ''; ?>>แอดมิน</option>
                </select>
                <div class="help-text">
                    <?php if ($employee['emp_level'] == 'a'): ?>
                        <span class="badge-level badge-admin"><i class="fa-solid fa-shield-halved"></i> แอดมิน</span>
                    <?php else: ?>
                        <span class="badge-level badge-user"><i class="fa-solid fa-user"></i> พนักงาน</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="emp_pass">รหัสผ่านใหม่ (ถ้าต้องการเปลี่ยน)</label>
                <input type="password" id="emp_pass" name="emp_pass" placeholder="ปล่อยว่างไว้เพื่อไม่เปลี่ยน">
                <div class="help-text">เว้นว่างหากไม่ต้องการเปลี่ยน</div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> บันทึกการเปลี่ยนแปลง
            </button>
            <a href="manage_users.php" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i> ยกเลิก
            </a>
        </div>
    </form>

</div>

</div>
</body>
</html>