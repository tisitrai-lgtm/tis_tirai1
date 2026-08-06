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
    header("Location: manage_users.php?error=ไม่พบพนักงานที่ต้องการดู");
    exit;
}

// คำนวณข้อมูลเพิ่มเติม
$created_date = isset($employee['created_at']) ? $employee['created_at'] : 'ไม่มีข้อมูล';
$updated_date = isset($employee['updated_at']) ? $employee['updated_at'] : 'ไม่มีข้อมูล';

// ดึงข้อมูลรถตัดที่ดูแล
$assigned_harvesters = [];
if ($employee && $employee['is_harvester_manager'] == 1) {
    $stmt_h = $conn->prepare("
        SELECT h.harvester_number 
        FROM employee_harvester eh 
        JOIN harvesters h ON eh.harvester_id = h.harvester_id 
        WHERE eh.emp_id = ? AND h.is_active = 1
        ORDER BY h.harvester_id ASC
    ");
    $stmt_h->execute([$emp_id]);
    $assigned_harvesters = $stmt_h->fetchAll(PDO::FETCH_COLUMN);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <link rel="icon" type="image/jpeg" href="icon/iconweb.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดพนักงาน - TIS SMART FIELD</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: 'Sarabun', sans-serif; 
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }

        .content-wrapper { flex: 1 0 auto; }
        
        .page-container {
            max-width: 700px;
            margin: 0 auto;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            padding: 8px 12px;
            border-radius: 6px;
            transition: background 0.2s;
        }

        .back-link:hover {
            background: rgba(59, 130, 246, 0.1);
        }

        .card-header {
            background: white;
            border-radius: 12px 12px 0 0;
            border: 0.5px solid #e2e8f0;
            padding: 24px;
            display: flex;
            align-items: flex-start;
            gap: 20px;
            border-bottom: 2px solid #f1f5f9;
        }

        .avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.8rem;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .avatar.is-admin {
            background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);
            box-shadow: 0 4px 12px rgba(225, 29, 72, 0.3);
        }

        .header-info h1 {
            font-size: 1.4rem;
            color: #1e293b;
            margin: 0 0 8px 0;
            font-weight: 700;
        }

        .header-info .emp-id {
            font-size: 0.9rem;
            color: #94a3b8;
            font-family: monospace;
            margin-bottom: 12px;
        }

        .badge-container {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .badge-admin {
            background: #fee2e2;
            color: #e11d48;
        }

        .badge-user {
            background: #d1fae5;
            color: #059669;
        }

        .card-body {
            background: white;
            border: 0.5px solid #e2e8f0;
            border-top: none;
            padding: 0;
        }

        .info-section {
            padding: 24px;
            border-bottom: 1px solid #f1f5f9;
        }

        .info-section:last-child {
            border-bottom: none;
        }

        .section-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f1f5f9;
        }

        .section-title i {
            color: #3b82f6;
            font-size: 1.1rem;
        }

        .info-row {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 16px;
            margin-bottom: 14px;
        }

        .info-row:last-child {
            margin-bottom: 0;
        }

        .info-label {
            font-weight: 600;
            color: #475569;
            font-size: 0.9rem;
        }

        .info-value {
            color: #1e293b;
            font-size: 0.95rem;
            word-break: break-word;
        }

        .info-value.mono {
            font-family: monospace;
            background: #f8fafc;
            padding: 6px 10px;
            border-radius: 4px;
            display: inline-block;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-active {
            background: #d1fae5;
            color: #065f46;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .card-footer {
            background: white;
            border: 0.5px solid #e2e8f0;
            border-top: 2px solid #f1f5f9;
            border-radius: 0 0 12px 12px;
            padding: 20px 24px;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .btn {
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            font-family: 'Sarabun', sans-serif;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-edit {
            background: #3b82f6;
            color: white;
        }

        .btn-edit:hover {
            background: #2563eb;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .btn-delete {
            background: #ef4444;
            color: white;
        }

        .btn-delete:hover {
            background: #dc2626;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .btn-back {
            background: #e2e8f0;
            color: #475569;
        }

        .btn-back:hover {
            background: #cbd5e1;
        }

        .empty-field {
            color: #cbd5e1;
            font-style: italic;
        }

        @media (max-width: 600px) {
            .card-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
                padding: 20px;
            }

            .header-info {
                width: 100%;
                text-align: center;
            }

            .header-info h1 {
                font-size: 1.2rem;
            }

            .badge-container {
                justify-content: center;
            }

            .info-row {
                grid-template-columns: 1fr;
                gap: 6px;
            }

            .info-label {
                font-weight: 700;
                color: #94a3b8;
                font-size: 0.85rem;
                text-transform: uppercase;
            }

            .card-footer {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }

            .info-section {
                padding: 16px;
            }
        }
        .status-chip {
        display: inline-block;
        padding: 5px 15px;
        border-radius: 20px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s;
        border: 2px solid transparent;
    }
    /* สีแดงสำหรับไม่ใช้งาน */
    .status-chip.inactive { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }
    /* สีเขียวสำหรับใช้งาน */
    .status-chip.active { background: #d1fae5; color: #059669; border-color: #6ee7b7; }
    </style>
    <link rel="stylesheet" href="global_smoothness.css">
</head>
<?php include 'includes/nav_u_header.php'; ?>
<body>

<div class="content-wrapper">



<div class="page-container">

    <a href="manage_users.php" class="back-link">
        <i class="fa-solid fa-arrow-left"></i> กลับไปหน้าจัดการพนักงาน
    </a>

    <div class="card-header">
        <div class="avatar <?php echo $employee['emp_level'] == 'a' ? 'is-admin' : ''; ?>">
            <?php 
            $initials = mb_substr(trim($employee['emp_name']), 0, 2, 'UTF-8');
            echo htmlspecialchars($initials);
            ?>
        </div>
        <div class="header-info">
            <h1><?php echo htmlspecialchars($employee['emp_name']); ?></h1>
            <div class="emp-id"><?php echo htmlspecialchars($employee['emp_id']); ?></div>
            <div class="badge-container">
                <?php if ($employee['emp_level'] == 'a'): ?>
                    <span class="badge badge-admin">
                        <i class="fa-solid fa-shield-halved"></i> แอดมิน
                    </span>
                <?php else: ?>
                    <span class="badge badge-user">
                        <i class="fa-solid fa-user"></i> พนักงาน
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card-body">

        <!-- ข้อมูลพื้นฐาน -->
        <div class="info-section">
            <div class="section-title">
                <i class="fa-solid fa-user-circle"></i> ข้อมูลพื้นฐาน
            </div>
            
            <div class="info-row">
                <div class="info-label">รหัสพนักงาน</div>
                <div class="info-value mono"><?php echo htmlspecialchars($employee['emp_id']); ?></div>
            </div>

            <div class="info-row">
                <div class="info-label">ชื่อ-นามสกุล</div>
                <div class="info-value"><?php echo htmlspecialchars($employee['emp_name']); ?></div>
            </div>

            <div class="info-row">
                <div class="info-label">หน่วยงาน</div>
                <div class="info-value"><?php echo htmlspecialchars($employee['emp_unit']); ?></div>
            </div>
        </div>

        <!-- ข้อมูลสิทธิ์การใช้งาน -->
        <div class="info-section">
    <div class="section-title">
        <i class="fa-solid fa-lock"></i> สิทธิ์การใช้งาน
    </div>

    <div class="info-row">
        <div class="info-label">ระดับสิทธิ์</div>
        <div class="info-value">
            <?php if ($employee['emp_level'] == 'a'): ?>
                <span class="status-badge status-active">
                    <i class="fa-solid fa-shield-halved"></i> แอดมิน
                </span>
            <?php else: ?>
                <span class="status-badge status-active">
                    <i class="fa-solid fa-user"></i> พนักงาน
                </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="info-row">
        <div class="info-label">สถานะผู้ดูแล</div>
        <div class="info-value">
            <?php if (isset($employee['is_harvester_manager']) && $employee['is_harvester_manager'] == 1): ?>
                <span class="status-badge status-active" style="background: #fef3c7; color: #92400e; border: 1px solid #fbbf24;">
                    <i class="fa-solid fa-truck-pickup"></i> เป็นผู้ดูแลรถตัด
                </span>
            <?php else: ?>
                <span class="status-badge status-inactive">
                    <i class="fa-solid fa-user-slash"></i> พนักงานทั่วไป
                </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="info-row">
    <div class="info-label">สถานะระบบ</div>
    <div class="info-value">
        <div class="status-chip <?php echo ($employee['status'] == 1) ? 'active' : 'inactive'; ?>" 
             id="status-chip" 
             onclick="toggleStatus('<?php echo $employee['ID']; ?>', this)">
            <?php echo ($employee['status'] == 1) ? 'ใช้งาน' : 'ไม่ใช้งาน'; ?>
        </div>
    </div>
</div>

        <?php if (isset($employee['is_harvester_manager']) && $employee['is_harvester_manager'] == 1): ?>
        <!-- รถตัดที่ดูแล -->
        <div class="info-section">
            <div class="section-title" style="border-bottom: 2px solid #fbbf24;">
                <i class="fa-solid fa-tractor" style="color: #d97706;"></i> รถตัดที่ดูแล
            </div>
            <div class="info-row">
                <div class="info-label">รายการรถที่ดูแล</div>
                <div class="info-value">
                    <?php if (!empty($assigned_harvesters)): ?>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <?php foreach ($assigned_harvesters as $h_num): ?>
                                <span style="background: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 6px; font-size: 0.85rem; font-weight: 600; border: 1px solid #fcd34d; display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="fa-solid fa-tractor" style="font-size: 0.75rem;"></i> <?php echo htmlspecialchars($h_num); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <span class="empty-field">ยังไม่มีรถตัดที่ดูแล</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ข้อมูลระบบ -->
        <div class="info-section">
            <div class="section-title">
                <i class="fa-solid fa-database"></i> ข้อมูลระบบ
            </div>

            <div class="info-row">
                <div class="info-label">วันที่สร้าง</div>
                <div class="info-value">
                    <?php 
                    if ($created_date && $created_date != 'ไม่มีข้อมูล') {
                        echo htmlspecialchars(date('d/m/Y H:i', strtotime($created_date)));
                    } else {
                        echo '<span class="empty-field">ไม่มีข้อมูล</span>';
                    }
                    ?>
                </div>
            </div>

            <div class="info-row">
                <div class="info-label">แก้ไขล่าสุด</div>
                <div class="info-value">
                    <?php 
                    if ($updated_date && $updated_date != 'ไม่มีข้อมูล') {
                        echo htmlspecialchars(date('d/m/Y H:i', strtotime($updated_date)));
                    } else {
                        echo '<span class="empty-field">ไม่มีข้อมูล</span>';
                    }
                    ?>
                </div>
            </div>

            <div class="info-row">
                <div class="info-label">ID ระบบ</div>
                <div class="info-value mono"><?php echo htmlspecialchars($employee['ID']); ?></div>
            </div>
        </div>

    </div>

    <div class="card-footer">
        <a href="edit_user.php?id=<?php echo $employee['ID']; ?>" class="btn btn-edit">
            <i class="fa-solid fa-pen-to-square"></i> แก้ไข
        </a>
        <a href="delete_user.php?id=<?php echo $employee['ID']; ?>" class="btn btn-delete"
           onclick="return confirm('ยืนยันลบ <?php echo htmlspecialchars($employee['emp_name']); ?> ออกจากระบบ?')">
            <i class="fa-solid fa-trash"></i> ลบ
        </a>
        <a href="manage_users.php" class="btn btn-back">
            <i class="fa-solid fa-arrow-left"></i> ยกเลิก
        </a>
    </div>

</div>

</div>
<script>
function toggleStatus(id, el) {
    // เช็คว่าปัจจุบันเป็นคลาสไหน
    const isNowActive = el.classList.contains('active'); 
    const newStatus = isNowActive ? 0 : 1; // ถ้ากดจากเขียว(active) จะกลายเป็น 0

    fetch('update_user_status.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `id=${id}&status=${newStatus}`
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            // สลับ Class
            el.classList.toggle('active');
            el.classList.toggle('inactive');
            // เปลี่ยนข้อความ
            el.innerText = (newStatus == 1) ? 'ใช้งาน' : 'ไม่ใช้งาน';
        } else {
            alert('เกิดข้อผิดพลาดในการอัปเดต');
        }
    });
}
</script>
</body>
</html>