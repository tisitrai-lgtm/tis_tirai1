<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_all'])) {
    foreach ($_POST['data'] as $id => $val) {
        $t_plate = $conn->real_escape_string($val['t']);
        $l_plate = $conn->real_escape_string($val['l']);
        $q_num = (int)$val['q'];
        $conn->query("UPDATE queue_entries SET tractor_plate='$t_plate', trailer_plate='$l_plate', queue_number='$q_num' WHERE entry_id=$id");
    }
    echo "<script>alert('แก้ไขข้อมูลเรียบร้อยแล้ว'); window.location='view_all.php';</script>";
    exit;
}

$ids = $_POST['ids'] ?? [];
if (empty($ids)) { header("Location: view_all.php"); exit; }
$ids_string = implode(',', array_map('intval', $ids));

// JOIN ตาราง rounds เพื่อเอา round_number มาโชว์ในหน้าแก้ไข
$result = $conn->query("SELECT q.*, r.round_number FROM queue_entries q LEFT JOIN rounds r ON q.round_id = r.round_id WHERE q.entry_id IN ($ids_string)");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขคิวรถ</title>
    <link rel="stylesheet" href="admin_style.css">
    <style>
        .container { max-width: 900px; margin: auto; padding: 20px; }
        .edit-input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .round-tag { background: #334155; color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.85rem; }
    </style>
</head>
<body>
<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h1>✏️ แก้ไขข้อมูลแบบกลุ่ม</h1>
        <a href="view_all.php" style="text-decoration:none; color:#666;">❌ ยกเลิก</a>
    </div>

    <form method="POST">
        <div class="section-card" style="background:white; padding:20px; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,0.1);">
            <table class="list-table" style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#f1f5f9; text-align:left;">
                        <th style="padding:10px;">รอบ</th>
                        <th style="padding:10px; width:120px;">คิว</th>
                        <th style="padding:10px;">ทะเบียนรถลาก</th>
                        <th style="padding:10px;">ทะเบียนลูกพ่วง</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:10px;"><span class="round-tag">รอบ <?php echo $row['round_number']; ?></span></td>
                        <td style="padding:10px;"><input type="text" name="data[<?php echo $row['entry_id']; ?>][q]" value="<?php echo $row['queue_number']; ?>" class="edit-input"></td>
                        <td style="padding:10px;"><input type="text" name="data[<?php echo $row['entry_id']; ?>][t]" value="<?php echo $row['tractor_plate']; ?>" class="edit-input"></td>
                        <td style="padding:10px;"><input type="text" name="data[<?php echo $row['entry_id']; ?>][l]" value="<?php echo $row['trailer_plate']; ?>" class="edit-input"></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <div style="margin-top:25px; display:flex; gap:10px;">
                <button type="submit" name="update_all" style="background:#16a085; color:white; border:none; padding:12px 30px; border-radius:8px; cursor:pointer; font-weight:bold; font-size:1rem;">💾 บันทึกการแก้ไขทั้งหมด</button>
            </div>
        </div>
    </form>
</div>
</body>
</html>