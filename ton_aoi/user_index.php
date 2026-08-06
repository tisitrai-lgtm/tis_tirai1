<?php
// 🔧 แก้ไข: บังคับไม่ให้ browser แคชหน้านี้ไว้
// (สำคัญมากสำหรับผู้ใช้ที่ "เพิ่มลงหน้าจอโฮม" เพราะไม่มี service worker/manifest คอยจัดการ cache ให้
//  ถ้าไม่กันตรงนี้ browser จะโชว์หน้าเก่าที่แคชไว้ ทำให้ผู้ใช้เห็นค่าเดิมค้างอยู่)
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once 'db_connect.php'; // ตรวจสอบให้แน่ใจว่า db_connect.php เชื่อมต่อฐานข้อมูลได้อย่างถูกต้อง

// 1. ดึงปีทั้งหมดจากตาราง production_years
$sql_years = "SELECT year_label FROM production_years ORDER BY year_label DESC";
$result_years = $conn->query($sql_years);

// 2. ดึง Agency ทั้งหมดจากตาราง cane_plot_data (ไม่ซ้ำกัน)
// กรองค่าที่เป็น NULL หรือว่างออกไป เพื่อให้ตัวเลือกดูสะอาด
$sql_agency = "SELECT DISTINCT agency FROM cane_plot_data WHERE agency IS NOT NULL AND agency != '' ORDER BY agency ASC";
$result_agency = $conn->query($sql_agency);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>ระบบรูปใส่แปลงประมาณตัน (ผู้ใช้)</title>

    <!-- 🔧 แก้ไข: meta tag กันแคชสำรอง เผื่อ browser บางตัวไม่สนใจ HTTP header -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />

    <link rel="icon" href="icon/2.png" type="image/png">

    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />

    <style>
        /* ----------------------------------------------------------- */
        /* BASE STYLES (MOBILE FIRST & DESKTOP)                         */
        /* ----------------------------------------------------------- */
        body, html {
            height: 100%;
            background-color: #f0f8ff; 
            font-family: 'Kanit', sans-serif; 
            font-size: 16px; 
            
            /* 🚨 ภาพพื้นหลังเดียว เต็มจอ (icon/bg.jpg) */
            background-image: url('icon/bg.jpg'); 
            background-repeat: no-repeat;
            background-size: cover;      
            background-position: center center;
            background-attachment: fixed;
        }
        
        /* Overlay สำหรับภาพพื้นหลังเพื่อลดความเข้มและทำให้ข้อความอ่านง่ายขึ้น */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.7); /* สีขาวโปร่งใส 70% */
            z-index: -1;
        }

        .container {
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
            z-index: 1;
        }
        .system-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #ffffffff;
            margin-bottom: 2rem;
            text-align: center;
        }
        .card {
            width: 100%;
            max-width: 420px;
            padding: 2.5rem;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
            border-radius: 16px;
            background: #fff;
            border: none;
            text-align: center;
        }
        .card-icon {
            width: 80px;
            height: 80px;
            margin-bottom: 1.5rem;
            display: block;
            margin-left: auto;
            margin-right: auto;
            object-fit: contain;
        }
        h4.card-title {
            font-weight: 600;
            margin-bottom: 2rem;
            color: #333;
            font-size: 1.7rem;
        }
        .form-label {
            font-weight: 600;
            color: #555;
            text-align: left;
            display: block;
            margin-bottom: 0.5rem;
        }
        select.form-select {
            padding: 0.75rem 1rem;
            font-size: 1.1rem;
            border-radius: 8px;
            border: 1px solid #a8c1de;
            box-shadow: none;
            transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        select.form-select:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
            outline: 0;
        }
        .btn-primary {
            background-color: #007bff;
            border: none;
            font-weight: 600;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.2s ease;
            font-size: 1.1rem;
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.2);
        }
        .btn-primary:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0, 123, 255, 0.3);
        }
        .btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 2px 5px rgba(0, 123, 255, 0.2);
        }
        .card a {
            font-weight: 500;
            color: #6c757d;
            transition: color 0.2s;
            text-decoration: none;
        }
        .card a:hover {
            color: #007bff;
        }

        /* ----------------------------------------------------------- */
        /* 📱 MOBILE RESPONSIVE ADJUSTMENTS (< 576px)                  */
        /* ----------------------------------------------------------- */
        @media (max-width: 576px) {
            .system-title {
                font-size: 2rem; 
                margin-bottom: 1.5rem;
            }
            .card {
                padding: 1.5rem; 
                box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            }
             .card-icon {
                width: 60px; 
                height: 60px;
                margin-bottom: 1rem;
            }
            h4.card-title {
                font-size: 1.5rem; 
                margin-bottom: 1.5rem;
            }
            select.form-select {
                padding: 0.6rem 0.75rem; 
                font-size: 1rem; 
            }
            .btn-primary {
                padding: 0.7rem 1.2rem; 
                font-size: 1rem;
            }
             .mb-4 {
                margin-bottom: 1.5rem !important; 
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h1 class="system-title">ระบบรูปใส่ประมาณตัน</h1>

    <div class="card">
        <img src="icon/2.png" alt="Sugarcane Icon" class="card-icon">
        
        <h4 class="card-title">เลือกปีการผลิต</h4>
        <form action="user_dashboard_evaluate.php" method="get">
            
            <div class="mb-4">
                <label for="year" class="form-label">ปีการผลิต</label>
                <select class="form-select" name="year" id="year" required>
                    <option value="" disabled selected>-- กรุณาเลือกปีการผลิต --</option>
                    <?php if ($result_years && $result_years->num_rows > 0): ?>
                        <?php while ($row = $result_years->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($row['year_label']); ?>">
                                <?php echo htmlspecialchars($row['year_label']); ?>
                            </option>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <option value="" disabled>ไม่มีข้อมูลปี</option>
                    <?php endif; ?>
                </select>
            </div>

            <div class="mb-4">
                <label for="agency" class="form-label">หน่วยนักส่งเสริม</label>
                <select class="form-select" name="agency" id="agency" required>
                    <option value="" disabled selected>-- กรุณาเลือกหน่วยนักส่งเสริม --</option>
                    <?php if ($result_agency && $result_agency->num_rows > 0): ?>
                        <?php while ($row_agency = $result_agency->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($row_agency['agency']); ?>">
                                <?php echo htmlspecialchars($row_agency['agency']); ?>
                            </option>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <option value="" disabled>ไม่พบข้อมูลหน่วยนักส่งเสริม</option>
                    <?php endif; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">เข้าสู่ระบบ</button>
            <br><br>
        
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php 
// Close database connection after all operations are done
if (isset($conn)) {
    $conn->close(); 
}
?>