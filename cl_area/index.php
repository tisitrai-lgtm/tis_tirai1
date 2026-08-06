<?php
// ###########################################################
// ไฟล์: index.php (ฉบับปรับปรุงดีไซน์ใหม่)
// ###########################################################
session_start();

if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

include_once 'db_connect.php';

$error_message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['unit_id']) && isset($_POST['production_year_label'])) {
        $selected_unit_id = $_POST['unit_id'];
        $selected_production_year_label = $_POST['production_year_label'];

        try {
            $stmt_unit = $conn->prepare("SELECT unit_name FROM units WHERE unit_id = :unit_id");
            $stmt_unit->bindParam(':unit_id', $selected_unit_id);
            $stmt_unit->execute();
            $unit_row = $stmt_unit->fetch(PDO::FETCH_ASSOC);

            if ($unit_row) {
                $_SESSION['selected_unit_id'] = $selected_unit_id;
                $_SESSION['selected_unit_name'] = $unit_row['unit_name'];
                $_SESSION['selected_production_year_label'] = $selected_production_year_label;

                header("Location: land_info_display.php");
                exit();
            } else {
                $error_message = "ไม่พบหน่วยงานที่เลือก";
            }
        } catch (PDOException $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

// ดึงข้อมูลหน่วยงาน
$units = [];
$stmt_units = $conn->query("SELECT unit_id, unit_name FROM units ORDER BY unit_name ASC");
$units = $stmt_units->fetchAll(PDO::FETCH_ASSOC);

// ดึงรายการปี
$production_years = [];
$stmt_years = $conn->query("SELECT year_label FROM production_years WHERE is_active = TRUE ORDER BY year_label DESC");
$production_years = $stmt_years->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ | ระบบรังวัดพื้นที่</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        :root {
            /* โทนสีน้ำเงินที่คุณเลือก */
            --bg-overlay: linear-gradient(135deg, rgba(26, 35, 126, 0.85) 0%, rgba(13, 71, 161, 0.85) 100%);
            --primary-color: #1a237e;
            --accent-color: #2979ff;
            --white: #ffffff;
            --text-dark: #1e272e;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Sarabun', sans-serif;
        }

        body {
            /* ใส่รูปภาพ bg1.png และทับด้วยสี Gradient โปร่งแสง */
            background: linear-gradient( rgba(255, 255, 255, 0.13) 0%, rgba(33, 82, 156, 0.85) 110%),
            url('icon/bg1.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .login-card {
            /* ปรับความโปร่งใสของตัวการ์ดให้ดูเหมือนกระจก (Glassmorphism) */
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            padding: 45px 40px;
            border-radius: 28px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.4);
            width: 100%;
            max-width: 420px;
            border: 1px solid rgba(255,255,255,0.3);
        }

        .header-section {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo-icon {
            font-size: 55px;
            color: var(--primary-color);
            margin-bottom: 12px;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));
        }

        .form-title {
            color: var(--text-dark);
            font-size: 26px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .form-subtitle {
            color: #576574;
            font-size: 15px;
            margin-top: 5px;
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-label {
            display: block;
            margin-bottom: 9px;
            font-size: 14px;
            font-weight: 600;
            color: var(--primary-color);
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #8395a7;
            font-size: 18px;
        }

        .form-select {
            width: 100%;
            padding: 14px 15px 14px 48px;
            border: 2px solid #f1f2f6;
            border-radius: 14px;
            font-size: 16px;
            color: var(--text-dark);
            background-color: var(--white);
            transition: all 0.3s ease;
            appearance: none;
            cursor: pointer;
        }

        .form-select:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 4px rgba(41, 121, 255, 0.15);
        }

        .submit-button {
            width: 100%;
            padding: 16px;
            /* ปุ่มใช้สีน้ำเงินเข้มแบบทึบเพื่อให้เด่นบนพื้นหลังกระจก */
            background: linear-gradient(135deg, #1a237e 0%, #0d47a1 100%);
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            box-shadow: 0 10px 20px rgba(13, 71, 161, 0.3);
        }

        .submit-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 25px rgba(13, 71, 161, 0.4);
            filter: brightness(1.1);
        }

        /* Custom Dropdown Arrow */
        .input-wrapper::after {
            content: "\F282";
            font-family: "bootstrap-icons";
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #8395a7;
            pointer-events: none;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="header-section">
            <i class="bi bi-geo-alt-fill logo-icon"></i>
            <h2 class="form-title">ระบบรังวัดพื้นที่</h2>
            <p class="form-subtitle">กรุณาเลือกข้อมูลเพื่อเข้าสู่ระบบ</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="error-box">
                <i class="bi bi-exclamation-circle-fill"></i>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <form action="index.php" method="POST">
            <div class="form-group">
                <label for="unit_id" class="form-label">หน่วยงานส่งเสริม</label>
                <div class="input-wrapper">
                    <i class="bi bi-building"></i>
                    <select id="unit_id" name="unit_id" required class="form-select">
                        <option value="" disabled selected>เลือกหน่วยงาน</option>
                        <?php foreach ($units as $unit): ?>
                            <option value="<?php echo htmlspecialchars($unit['unit_id']); ?>">
                                <?php echo htmlspecialchars($unit['unit_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="production_year_label" class="form-label">ปีการผลิต</label>
                <div class="input-wrapper">
                    <i class="bi bi-calendar-check"></i>
                    <select id="production_year_label" name="production_year_label" required class="form-select">
                        <option value="" disabled selected>เลือกปีการผลิต</option>
                        <?php foreach ($production_years as $year): ?>
                            <option value="<?php echo htmlspecialchars($year['year_label']); ?>">
                                <?php echo htmlspecialchars($year['year_label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <button type="submit" class="submit-button">
                <span>ตกลงเข้าสู่ระบบ</span>
                <i class="bi bi-arrow-right-short"></i>
            </button>
        </form>
    </div>

</body>
</html>