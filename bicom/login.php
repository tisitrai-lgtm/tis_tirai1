<?php
session_start();
require 'db_connect.php';

$error = '';
try {
    $stmt = $pdo->query("SELECT STATN_CODE, STATN_NAME FROM stations ORDER BY STATN_CODE ASC");
    $stations = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Database Connection Error";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['statn_code'])) {
    $statn_code = $_POST['statn_code'];
    $stmt_check = $pdo->prepare("SELECT STATN_NAME FROM stations WHERE STATN_CODE = ?");
    $stmt_check->execute([$statn_code]);
    $station = $stmt_check->fetch();

    if ($station) {
        $_SESSION['statn_code'] = $statn_code;
        $_SESSION['statn_name'] = $station['STATN_NAME'];
        header("Location: index.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BAI COM</title>
    <link rel="icon" href="bg/v2.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            /* แก้ไขส่วนพื้นหลังเป็นรูปภาพ */
            background: url('bg/v1.jpg') no-repeat center center fixed;
            background-size: cover;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            position: relative;
        }

        /* เพิ่ม Overlay เพื่อให้ภาพพื้นหลังไม่แย่งสายตาจากฟอร์ม */
        body::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.3); /* ปรับความเข้มของภาพพื้นหลัง (0.3 คือจางๆ) */
            z-index: 1;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 24px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            position: relative;
            z-index: 2; /* ให้อยู่เหนือ Overlay */
        }
         .card-icon {
            width: 80px;
            height: 60px;
            margin-bottom: 1.5rem;
            display: block;
            margin-left: auto;
            margin-right: auto;
            object-fit: contain;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2)); /* เพิ่มเงาให้รูปภาพดูมีมิติ */
        }

        .btn-primary {
            background: #1a2a6c;
            border: none;
            border-radius: 12px;
            padding: 12px;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background: #b21f1f;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(178, 31, 31, 0.4);
        }

        .form-select {
            border-radius: 12px;
            padding: 12px;
            border: 2px solid #eee;
        }

        .form-select:focus {
            border-color: #1a2a6c;
            box-shadow: none;
        }

        .icon-box {
            width: 60px;
            height: 60px;
            background: #1a2a6c;
            color: white;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
         @media (max-width: 768px) {
        .navbar {
            padding: 0.6rem 0.5rem;
        }
        .navbar .container-fluid {
            flex-wrap: nowrap;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .navbar .container-fluid::-webkit-scrollbar { display: none; }

        .navbar .btn {
            padding: 6px 15px !important;
            margin-left: 5px; /* ลดระยะห่างบนมือเพื่อไม่ให้ล้นเกินไป */
            font-size: 0.8rem !important;
        }
        
        .text-long { display: none; }
        .text-short { display: inline-block; }
    }

    /* --- การปรับแต่งสำหรับคอมพิวเตอร์ --- */
    @media (min-width: 769px) {
        .text-long { display: inline-block; }
        .text-short { display: none; }
    }
       
    </style>
</head>
<body>

<div class="glass-card text-center">
    
<div class="card-icon">
    <img src="bg/v2.png" alt="Sugarcane Icon" style="width: 80px; height: 80px;">
</div>     
    <h3 class="fw-bold mb-1" style="color: #1a2a6c;">BAI COM</h3>
    <p class="text-muted mb-4 small">ระบบจัดการข้อมูลใบคอม</p>

    <?php if($error): ?>
        <div class="alert alert-danger py-2 border-0 small"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="text-start mb-4">
            <label class="form-label ps-2 small fw-bold text-uppercase">กรุณาเลือกหน่วย</label>
            <div class="input-group">
                <select name="statn_code" class="form-select" required>
                    <option value="" selected disabled>เลือกหน่วยงานของคุณ...</option>
                    <?php foreach ($stations as $row): ?>
                        <option value="<?= $row['STATN_CODE'] ?>">
                            <?= $row['STATN_CODE'] ?> — <?= $row['STATN_NAME'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 fw-bold">
            เข้าสู่ระบบ <i data-lucide="arrow-right" class="ms-2 d-inline-block" style="width: 18px;"></i>
        </button>
    </form>

   <div class="text-center mt-3">
            <a href="executive_report.php" class="text-decoration-none">
                 <p class="text-muted" style="font-size: 0.75rem;">&copy; ดูข้อมูลทั้งหมด</p>
            </a>
    </div>
</div>

<script>
    lucide.createIcons();
</script>
</body>
</html>