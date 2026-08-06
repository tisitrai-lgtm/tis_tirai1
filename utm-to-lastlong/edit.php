<?php 
include_once 'db_config.php'; 
include_once 'functions.php'; 

if (!isset($_GET['id'])) { header("Location: index.php"); exit; }
$id = intval($_GET['id']);

// ส่วนบันทึกข้อมูล (เหมือนเดิม)
if (isset($_POST['update'])) {
    $raw_x = floatval($_POST['ux']);
    $raw_y = floatval($_POST['uy']);
    $note  = $conn->real_escape_string($_POST['note']);
    $res = utmToLatLong($raw_x, $raw_y, 47);
    $lat = $res['lat']; $lng = $res['long'];
    $ax  = $res['adj_x']; $ay  = $res['adj_y'];

    $sql = "UPDATE conversion_logs SET utm_x='$raw_x', utm_y='$raw_y', adj_x='$ax', adj_y='$ay', latitude='$lat', longitude='$lng', note='$note' WHERE id=$id";
    if ($conn->query($sql)) {
        echo "<script>window.location.href='index.php';</script>";
        exit;
    }
}

$sql = "SELECT * FROM conversion_logs WHERE id = $id";
$result = $conn->query($sql);
$data = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>แก้ไขพิกัด</title>
    <link href="https://fonts.googleapis.com/css2?family=Anuphan:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* รีเซ็ตพื้นฐาน */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body { 
            font-family: 'Anuphan', sans-serif !important; 
            background-color: #f4f7fe; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh;
            padding: 15px;
        }

        .edit-card { 
            width: 100%; 
            max-width: 450px; 
            background: #ffffff; 
            padding: 35px 25px; 
            border-radius: 28px; /* ขอบโค้งมนแบบ Modern */
            box-shadow: 0 20px 40px rgba(108, 92, 231, 0.1); 
        }

        .header-area { text-align: center; margin-bottom: 30px; }
        .header-area h2 { 
            color: #6c5ce7; 
            font-size: 1.6rem; 
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        label { 
            display: block; 
            font-weight: 600; 
            margin-bottom: 10px; 
            color: #2d3436; 
            font-size: 0.95rem;
        }

        input, textarea { 
            width: 100% !important; 
            padding: 14px 18px; 
            border: 2px solid #edf2f7; 
            border-radius: 16px; 
            font-size: 16px; 
            font-family: 'Anuphan', sans-serif;
            background: #f8fafc;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            outline: none;
        }

        /* เมื่อกดเลือกช่องกรอก */
        input:focus, textarea:focus {
            border-color: #6c5ce7;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(108, 92, 231, 0.1);
        }

        /* สำหรับช่องที่ห้ามแก้ */
        input[readonly] {
            background-color: #f1f2f6;
            color: #b2bec3;
            border-color: #e2e8f0;
            cursor: not-allowed;
        }

        .btn-group { 
            display: flex; 
            flex-direction: column; 
            gap: 15px; 
            margin-top: 10px;
        }

        .btn-save { 
            padding: 16px; 
            background: #6c5ce7; /* ใช้สีเดียวกับธีมหลัก */
            color: white; 
            border: none; 
            border-radius: 18px; 
            font-weight: 700; 
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 8px 20px rgba(108, 92, 231, 0.3);
        }

        .btn-save:active { transform: scale(0.98); }

        .btn-cancel { 
            padding: 14px; 
            text-align: center; 
            text-decoration: none; 
            color: #636e72; 
            font-weight: 600;
            font-size: 1rem;
            background: #fff;
            border: 2px solid #edf2f7;
            border-radius: 18px;
            transition: 0.2s;
        }

        .btn-cancel:hover { background: #f8fafc; color: #2d3436; }

        /* ปรับแต่งสำหรับมือถือจอเล็ก */
        @media screen and (max-width: 400px) {
            .edit-card { padding: 25px 20px; }
            .header-area h2 { font-size: 1.4rem; }
        }
    </style>
</head>
<body>

<div class="edit-card">
    <div class="header-area">
        <h2>📝 แก้ไขข้อมูล</h2>
        <p style="color: #b2bec3; font-size: 13px; margin-top: 4px;">ปรับปรุงรายละเอียดพิกัด ID: <?= $id ?></p>
    </div>

    <form method="POST">
        <label>ค่า UTM X</label>
        <input type="text" name="ux" value="<?= $data['utm_x'] ?>" readonly>
        
        <label>ค่า UTM Y</label>
        <input type="text" name="uy" value="<?= $data['utm_y'] ?>" readonly>

        <label>หมายเหตุ</label>
        <textarea name="note" rows="4" placeholder="ระบุรายละเอียด..."><?= htmlspecialchars($data['note']) ?></textarea>

        <div class="btn-group">
            <button type="submit" name="update" class="btn-save">💾 บันทึกข้อมูล</button>
            <a href="index.php" class="btn-cancel">กลับหน้าหลัก</a>
        </div>
    </form>
</div>

</body>
</html>