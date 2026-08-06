<?php
require_once 'db_connect.php'; 

// ดึงปีทั้งหมดจากฐานข้อมูล
$sql = "SELECT year_label FROM production_years ORDER BY year_label DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>ระบบตรวจสอบแปลงอ้อย | เข้าสู่ระบบ</title>

    <link rel="icon" href="icon/unnamed.png" type="image/png">
    <link rel="manifest" href="manifest.json">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="index.css">
    
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('sw.js').then(function(registration) {
                    console.log('ServiceWorker registration successful with scope: ', registration.scope);
                }, function(err) {
                    console.log('ServiceWorker registration failed: ', err);
                });
            });
        }
    </script>

    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px 0;
            background-image: url('icon/bg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            position: relative;
            z-index: 0;
        }
        body::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(244, 247, 246, 0.75);
            backdrop-filter: blur(6px);
            z-index: -1;
        }

        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 15px;
        }

        @media (max-width: 576px) {
            .logo-container {
                width: 80px !important;
                height: 80px !important;
                margin-bottom: 1rem !important;
            }
            .logo-img {
                width: 50px !important;
                height: 50px !important;
            }
            .system-title {
                font-size: 1.5rem !important;
            }
            .glass-card {
                padding: 1.5rem !important;
            }
        }

        .glass-card {
            text-align: center;
            animation: fadeIn 0.8s ease-out;
        }

        .logo-container {
            width: 120px;
            height: 120px;
            background: #f1f5f9;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 2rem;
            border: 3px solid var(--primary);
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }

        .logo-img {
            width: 70px;
            height: 70px;
            object-fit: contain;
        }

        .system-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .system-subtitle {
            font-weight: 300;
            opacity: 0.8;
            margin-bottom: 2rem;
            font-size: 1rem;
        }

        .form-select-premium {
            background: #ffffff;
            border: 2px solid #cbd5e1;
            color: var(--text-main);
            border-radius: 15px;
            padding: 15px;
            font-size: 1.15rem;
            transition: all 0.3s;
        }

        .form-select-premium:focus {
            background: #ffffff;
            box-shadow: 0 0 15px rgba(30, 58, 138, 0.1);
            border-color: var(--primary);
            color: var(--text-main);
        }

        .form-select-premium option {
            background: white;
            color: var(--text-main);
        }

        .btn-premium {
            width: 100%;
            padding: 12px;
            font-size: 1.1rem;
            letter-spacing: 1px;
            margin-top: 1rem;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="glass-card fade-in">
        <div class="logo-container">
            <img src="icon/unnamed.png" alt="Logo" class="logo-img">
        </div>
        
        <h1 class="system-title">ระบบตรวจสอบแปลงอ้อย</h1>
        <p class="system-subtitle" style="font-size: 1.2rem; color: #64748b;">กรุณาเลือกปีการผลิตเพื่อเข้าสู่ระบบ</p>

        <form action="dashboard.php" method="get">
            <div class="mb-4 text-start">
                <label for="year" class="form-label text-secondary fw-bold ms-2 mb-2" style="font-size: 1.1rem;">
                    <i class='bx bx-calendar'></i> ปีการผลิต
                </label>
                <select class="form-select form-select-premium" name="year" id="year" required>
                    <option value="" disabled selected>-- เลือกปีการผลิต --</option>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($row['year_label']); ?>">
                                <?php echo htmlspecialchars($row['year_label']); ?>
                            </option>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <option value="" disabled>ไม่พบข้อมูลปีการผลิต</option>
                    <?php endif; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-premium">
                <i class='bx bx-log-in-circle'></i> เข้าสู่ระบบ
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php 
if (isset($conn)) {
    $conn->close(); 
}
?>