<?php
require_once 'db_connect.php'; 

$sql = "SELECT year_label FROM production_years ORDER BY year_label DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>ระบบรูปใส่แปลงประมาณตัน Audit</title>
    <link rel="icon" href="icon/2.png" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.2);
            --glass-border: rgba(255, 255, 255, 0.3);
            --primary-blue: #007bff;
            --accent-blue: #00d2ff;
        }

        body, html {
            min-height: 100%;
            font-family: 'Kanit', sans-serif;
            background-image: url('icon/bg.jpg');
            background-size: cover;
            background-position: center center;
            background-attachment: fixed;
        }

        body::before {
            content: '';
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(244, 247, 246, 0.85); /* Minimalist light overlay */
            backdrop-filter: blur(8px);
            z-index: -1;
        }

        .container {
            min-height: 100vh; display: flex; flex-direction: column;
            justify-content: center; align-items: center; padding: 20px;
            position: relative; z-index: 1;
        }

        .system-title {
            font-family: 'Outfit', sans-serif; font-size: 3.5rem; font-weight: 800;
            color: #2b2b2b; margin-bottom: 2.5rem; text-align: center;
            text-transform: uppercase; letter-spacing: 2px;
        }
        .system-title span { color: var(--accent-blue); }

        .card {
            width: 100%; max-width: 450px; padding: 3rem 2.5rem;
            background: #ffffff;
            border: 1px solid #eaeaea; border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05); text-align: center;
        }

        .card-icon-wrapper {
            background: #f8f9fa; width: 80px; height: 80px;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem; border: 1px solid #eaeaea;
        }

        .card-icon { width: 50px; height: 50px; object-fit: contain; }

        h4.card-title { font-weight: 600; margin-bottom: 1.5rem; color: #2b2b2b; font-size: 1.6rem; }

        @media (max-width: 576px) {
            .system-title { font-size: 2.2rem; margin-bottom: 1.5rem; }
            .card { padding: 2rem 1.25rem; }
            .card-icon-wrapper { width: 70px; height: 70px; }
            .card-icon { width: 40px; height: 40px; }
            h4.card-title { font-size: 1.4rem; }
            .btn-primary { padding: 0.8rem; font-size: 1rem; }
        }
    </style>
</head>
<body>
<div class="container">
    <h1 class="system-title">Au<span>Dit</span> Ton</h1>
    <div class="card">
        <div class="card-icon-wrapper"><img src="icon/2.png" alt="Icon" class="card-icon"></div>
        <h4 class="card-title">เลือกปีการผลิต</h4>
        <form id="loginForm" action="dashboard.php" method="get">
            <div class="mb-4">
                <label for="year" class="form-label">ประจำปีการผลิต</label>
                <select class="form-select" name="year" id="year" required>
                    <option value="" disabled selected>-- กรุณาเลือกปี --</option>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($row['year_label']); ?>"><?php echo htmlspecialchars($row['year_label']); ?></option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>
            <input type="hidden" name="msg" value="login">
            <button type="submit" class="btn btn-primary w-100">
                <i class='bx bx-log-in-circle me-2'></i>เข้าสู่ระบบ Dashboard
            </button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const msg = urlParams.get('msg');

        if (msg === 'logout') {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
            Toast.fire({ icon: 'success', title: 'รีเซ็ตปีการผลิตเรียบร้อย' });
        }

        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const year = document.getElementById('year').value;
            if (year) {
                Swal.fire({
                    title: 'กำลังเข้าสู่ระบบ...',
                    html: `กำลังจัดเตรียมข้อมูลปี <b>${year}</b>`,
                    timer: 1500,
                    timerProgressBar: true,
                    didOpen: () => { Swal.showLoading(); },
                });
            }
        });
    });
</script>
</body>
</html>
<?php if (isset($conn)) $conn->close(); ?>