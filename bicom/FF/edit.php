<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['statn_code']) || !isset($_GET['log_id'])) {
    header("Location: index.php");
    exit;
}

$log_id = $_GET['log_id'];
$statn_code = $_SESSION['statn_code'];

// ดึงข้อมูลเดิมออกมาแสดงในฟอร์ม
$stmt = $pdo->prepare("SELECT * FROM conversion_logs WHERE LOG_ID = ? AND STATN_CODE = ?");
$stmt->execute([$log_id, $statn_code]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    echo "ไม่พบข้อมูลที่ต้องการแก้ไข";
    exit;
}

// ประมวลผลเมื่อมีการกดปุ่ม "บันทึกการแก้ไข"
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $farmr_name = $_POST['FARMR_NAME'];
    $weigh_cane = $_POST['WEIGH_CANE'];
    $cane_type  = $_POST['CANE_TYPE'];
    $truck_code = $_POST['TRUCK_CODE'];

    try {
        $sql = "UPDATE conversion_logs 
                SET FARMR_NAME = ?, WEIGH_CANE = ?, CANE_TYPE = ?, TRUCK_CODE = ? 
                WHERE LOG_ID = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$farmr_name, $weigh_cane, $cane_type, $truck_code, $log_id]);
        
        header("Location: index.php?msg=updated");
        exit;
    } catch (PDOException $e) {
        $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขข้อมูลใบรับอ้อย - <?= htmlspecialchars($data['WEIGH_DOCC']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f1f5f9; }
        .edit-card { max-width: 600px; margin: 50px auto; border-radius: 15px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<div class="container">
    <div class="card edit-card">
        <div class="card-header bg-primary text-white p-4" style="border-radius: 15px 15px 0 0;">
            <h5 class="mb-0">แก้ไขข้อมูลใบรับอ้อยเลขที่: <?= htmlspecialchars($data['WEIGH_DOCC']) ?></h5>
        </div>
        <div class="card-body p-4 bg-white">
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold">ชื่อ-นามสกุลชาวไร่</label>
                    <input type="text" name="FARMR_NAME" class="form-control" value="<?= htmlspecialchars($data['FARMR_NAME']) ?>" required>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">น้ำหนักอ้อย (ตัน)</label>
                        <input type="number" step="0.001" name="WEIGH_CANE" class="form-control" value="<?= $data['WEIGH_CANE'] ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">ทะเบียนรถ</label>
                        <input type="text" name="TRUCK_CODE" class="form-control" value="<?= htmlspecialchars($data['TRUCK_CODE']) ?>" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">ประเภทอ้อย</label>
                    <select name="CANE_TYPE" class="form-select">
                        <option value="อ้อยสด" <?= ($data['CANE_TYPE'] == 'อ้อยสด' || $data['CANE_TYPE'] == '0') ? 'selected' : '' ?>>อ้อยสด</option>
                        <option value="อ้อยไฟไหม้" <?= ($data['CANE_TYPE'] == 'อ้อยไฟไหม้' || $data['CANE_TYPE'] == '1') ? 'selected' : '' ?>>อ้อยไฟไหม้</option>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success flex-fill fw-bold">บันทึกการแก้ไข</button>
                    <a href="index.php" class="btn btn-light border flex-fill fw-bold">ยกเลิก</a>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>