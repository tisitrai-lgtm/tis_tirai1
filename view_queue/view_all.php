<?php
include 'db_connect.php'; 
date_default_timezone_set('Asia/Bangkok');
// --- 1. การแบ่งหน้า ---
$limit = 40; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// --- 2. ระบบจัดการข้อมูล (ลบ/แก้ไข) ---
$msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['selected_entries'])) {
    $ids = $_POST['selected_entries'];
    $ids_string = implode(',', array_map('intval', $ids));

    if ($_POST['action'] == 'delete_selected') {
        if($conn->query("DELETE FROM queue_entries WHERE entry_id IN ($ids_string)")) {
            $msg = "success|ลบสำเร็จ|ลบข้อมูลที่เลือกเรียบร้อยแล้ว";
        }
    } 
    if ($_POST['action'] == 'edit_selected') {
        echo "<form id='ef' action='edit_bulk.php' method='POST'>";
        foreach($ids as $id) echo "<input type='hidden' name='ids[]' value='$id'>";
        echo "</form><script>document.getElementById('ef').submit();</script>";
        exit;
    }
}

// --- 3. การค้นหา ---
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$where_search = $search ? " AND (q.tractor_plate LIKE '%$search%' OR q.trailer_plate LIKE '%$search%') " : "";

// --- 4. SQL Query (แสดงข้อมูลทั้งหมด) ---
$sql = "SELECT q.entry_id, q.tractor_plate, q.trailer_plate, 
               q.queue_number, r.round_number, q.created_at
        FROM queue_entries q
        LEFT JOIN rounds r ON q.round_id = r.round_id
        WHERE 1=1 $where_search
        ORDER BY q.created_at DESC, q.entry_id DESC 
        LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

$total_rows_res = $conn->query("SELECT COUNT(*) FROM queue_entries q WHERE 1=1 $where_search");
$total_rows = $total_rows_res->fetch_row()[0];
$total_pages = ceil($total_rows / $limit);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ระบบจัดการคิวรถ</title>
    <link rel="stylesheet" href="admin_style.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .container { max-width: 1100px; margin: auto; padding: 20px; } /* ขยายความกว้าง */
        .list-table th, .list-table td { padding: 12px 15px; font-size: 1rem; border: 1px solid #e2e8f0; } /* ขยายช่องและตัวหนังสือ */
        .section-card { padding: 25px; border-radius: 12px; background: white; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 20px; }
        .pagination a { padding: 8px 16px; border: 1px solid #ddd; text-decoration: none; color: #333; border-radius: 6px; }
        .pagination a.active { background: #16a085; color: white; border-color: #16a085; }
        .btn-action { padding: 10px 20px; font-size: 0.95rem; border-radius: 6px; cursor: pointer; border: none; color: white; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h1 style="margin:0; font-size: 1.8rem;">📋 รายการข้อมูลคิวทั้งหมด</h1>
        <a href="add_queue.php" style="font-size: 1rem; color: #16a085; text-decoration: none; font-weight: bold;">⬅️ กลับหน้าลงทะเบียน</a>
    </div>

    <form method="GET" style="display:flex; gap:10px; margin-bottom:20px;">
        <input type="text" name="search" placeholder="ค้นหาทะเบียนรถ..." value="<?php echo htmlspecialchars($search); ?>" style="flex:1; padding:12px; border:1px solid #cbd5e1; border-radius:8px; font-size:1rem;">
        <button type="submit" style="padding:12px 25px; background:#334155; color:white; border:none; border-radius:8px; cursor:pointer;">ค้นหา</button>
    </form>

    <form method="POST" id="mainForm">
    <input type="hidden" name="action" id="action_input" value="">
    <div class="section-card">
        <div style="margin-bottom:15px; display:flex; gap:10px;">
            <button type="button" onclick="handleBulk('edit_selected')" class="btn-action" style="background:#f59e0b;">✏️ แก้ไขข้อมูลที่เลือก</button>
            <button type="button" onclick="handleBulk('delete_selected')" class="btn-action" style="background:#ef4444;">🗑️ ลบที่เลือก</button>
        </div>

        <table class="list-table" style="width:100%; border-collapse: collapse;">
            <thead>
                <tr style="background:#f8fafc; text-align: left;">
                    <th style="width:40px; text-align:center;"><input type="checkbox" onclick="for(c of document.getElementsByName('selected_entries[]')) c.checked=this.checked"></th>
                    <th style="text-align:center;">รอบ</th>
                    <th style="text-align:center;">คิวที่</th>
                    <th>ทะเบียนรถลาก</th>
                    <th>ทะเบียนลูกพ่วง</th>
                    <th style="text-align:center;">เวลาที่ลงทะเบียน</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="text-align:center;"><input type="checkbox" name="selected_entries[]" value="<?php echo $row['entry_id']; ?>"></td>
                    <td style="text-align:center;"><span style="background:#e2e8f0; padding:4px 10px; border-radius:5px;">รอบ <?php echo $row['round_number']; ?></span></td>
                    <td style="text-align:center; font-weight:bold; color:#16a085; font-size:1.2rem;"><?php echo $row['queue_number']; ?></td>
                    <td><strong style="font-size:1.1rem;"><?php echo $row['tractor_plate']; ?></strong></td>
                    <td style="color:#64748b;"><?php echo $row['trailer_plate'] ?: '-'; ?></td>
                    <td style="text-align:center; color:#94a3b8;"><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?> น.</td>
                </tr>
                <?php endwhile; ?>
                <?php if($total_rows == 0): ?>
                    <tr><td colspan="6" style="padding:50px; text-align:center; color:#94a3b8;">📭 ไม่พบข้อมูลคิวในขณะนี้</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php
            if ($page > 1) echo "<a href='?page=".($page-1)."&search=$search'>&laquo; ก่อนหน้า</a>";
            for ($i = 1; $i <= $total_pages; $i++) {
                if ($i == 1 || $i == $total_pages || ($i >= $page - 1 && $i <= $page + 1)) {
                    echo "<a href='?page=$i&search=$search' class='".($page==$i?'active':'')."'>$i</a>";
                } elseif ($i == $page - 2 || $i == $page + 2) {
                    echo "<span style='padding:8px;'>...</span>";
                }
            }
            if ($page < $total_pages) echo "<a href='?page=".($page+1)."&search=$search'>ถัดไป &raquo;</a>";
            ?>
        </div>
    </div>
    </form>
</div>

<script>
function handleBulk(action) {
    const checked = document.querySelectorAll('input[name="selected_entries[]"]:checked');
    if (checked.length === 0) {
        Swal.fire('กรุณาเลือกอย่างน้อย 1 รายการ'); return;
    }
    if (action === 'delete_selected') {
        Swal.fire({
            title: 'ยืนยันการลบ?',
            text: `คุณกำลังจะลบข้อมูล ${checked.length} รายการ`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'ใช่, ลบเลย',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('action_input').value = action;
                document.getElementById('mainForm').submit();
            }
        });
    } else {
        document.getElementById('action_input').value = action;
        document.getElementById('mainForm').submit();
    }
}
<?php if($msg): $m = explode('|', $msg); ?>
    Swal.fire({ icon: '<?php echo $m[0]; ?>', title: '<?php echo $m[1]; ?>', text: '<?php echo $m[2]; ?>' });
<?php endif; ?>
</script>
</body>
</html>