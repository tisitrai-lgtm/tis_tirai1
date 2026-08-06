<?php 
include_once 'db_config.php'; 

// 1. กำหนดจำนวนรายการต่อหน้า
if (isset($is_index)) {
    $limit = 10;
    $start = 0; 
} else {
    $limit = isset($_GET['show']) ? intval($_GET['show']) : 20;
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $start = ($page - 1) * $limit;
}
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Anuphan:wght@300;400;600&family=Inter:wght@400;700&display=swap');

    .history-table-wrapper { 
        font-family: 'Anuphan', sans-serif;
        background: #f4f7fe; /* พื้นหลังสีฟ้าอ่อนนวลๆ */
        padding: 25px; 
        border-radius: 24px; 
        box-shadow: 0 15px 35px rgba(0,0,0,0.1); 
        max-width: 1200px;
        margin: 20px auto;
    }

    .header-area { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        margin-bottom: 25px; 
        background: #ffffff;
        padding: 15px 25px;
        border-radius: 18px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }

    .header-area h3 { color: #2d3436; font-weight: 700; margin: 0; font-size: 1.3rem; }

    /* ปุ่มสีสันสดใส */
    .btn-home { 
        background: #6c5ce7; color: white !important; 
        padding: 10px 20px; border-radius: 12px; text-decoration: none; font-weight: 600;
        transition: 0.3s;
    }
    .btn-map { 
        background: #00b894; color: white !important; 
        padding: 10px 20px; border-radius: 12px; text-decoration: none; font-weight: 600;
        box-shadow: 0 4px 10px rgba(0,184,148,0.3);
    }
    .btn-home:hover, .btn-map:hover { opacity: 0.9; transform: translateY(-2px); }

    /* ปรับแต่งตารางให้มีสีสัน */
    table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
    th { 
        padding: 15px; color: #636e72; font-weight: 600; 
        text-align: center; border: none; font-size: 13px;
    }
    
    tbody tr { transition: 0.3s; }
    td { 
        padding: 15px; background: #ffffff; 
        border: none; text-align: center;
        color: #2d3436;
    }
    
    /* ขอบโค้งให้แต่ละแถว */
    td:first-child { border-radius: 15px 0 0 15px; }
    td:last-child { border-radius: 0 15px 15px 0; }
    
    tr:hover td { background: #eef2ff; transform: scale(1.01); }

    /* ป้ายชื่อพิกัดและหมายเหตุ */
    .note-badge {
        background: #ffeaa7; color: #d63031; 
        padding: 5px 12px; border-radius: 8px; 
        font-weight: 600; display: inline-block; font-size: 14px;
    }
    .coords-badge {
        background: #dfe6e9; color: #2d3436;
        padding: 5px 10px; border-radius: 8px;
        font-family: 'Inter'; font-weight: 700; font-size: 13px;
    }

    /* ปุ่ม Google Maps สีน้ำเงินสด */
    .gmap-btn {
        background: #0984e3; color: white !important;
        padding: 8px 15px; border-radius: 10px;
        text-decoration: none; font-size: 12px; font-weight: 600;
        display: inline-block;
    }

    /* สไตล์ปุ่มแก้ไขและลบ */
    .action-edit { color: #0984e3; font-weight: 700; text-decoration: none; margin-right: 10px; }
    .action-delete { color: #d63031; font-weight: 700; text-decoration: none; }

    /* Mobile Responsive */
    @media screen and (max-width: 768px) {
        .header-area { flex-direction: column; gap: 15px; text-align: center; }
        thead { display: none; }
        td { display: block; text-align: right; padding: 12px 20px; border-bottom: 1px solid #f1f2f6; }
        td:first-child, td:last-child { border-radius: 0; }
        tr { display: block; background: #fff; margin-bottom: 15px; border-radius: 20px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        td::before {
            content: attr(data-label);
            float: left; font-weight: 700; color: #b2bec3; font-size: 12px;
        }
        .note-badge { max-width: 150px; }
    }
</style>

<div class="history-table-wrapper">
    <div class="header-area">
        <h3 style="color: #6c5ce7;">🚀 ระบบบันทึกพิกัด</h3>
        <div style="display: flex; gap: 10px;">
            <?php if(!isset($is_index)): ?>
                <a href="index.php" class="btn-home">หน้าหลัก</a>
                <a href="map-all.php" class="btn-map">📍 ดูแผนที่ทั้งหมด</a>
            <?php endif; ?>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>จัดการ</th> 
                <th>รายละเอียด / หมายเหตุ</th>
                <th>พิกัด (LAT, LNG)</th>
                <th>นำทาง</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT * FROM conversion_logs ORDER BY id DESC LIMIT $start, $limit";
            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $lat = $row['latitude'];
                    $lng = $row['longitude'];
                    $gmap_url = "https://www.google.com/maps/search/?api=1&query=$lat,$lng";
                    $clean_note = htmlspecialchars($row['note']);
                    
                    echo "<tr>";
                        echo "<td data-label='จัดการ'>";
                            echo "<a href='javascript:void(0)' class='action-edit' data-id='".$row['id']."' data-note='".$clean_note."' onclick='handleEditClick(this)'>แก้ไข</a>";
                            echo "<a href='delete.php?id=".$row['id']."' class='action-delete' onclick='return confirm(\"ต้องการลบใช่หรือไม่?\")'>ลบ</a>";
                        echo "</td>";
                        echo "<td data-label='หมายเหตุ'><span class='note-badge'>" . (!empty($row['note']) ? $clean_note : "ไม่มีข้อมูล") . "</span></td>";
                        echo "<td data-label='พิกัด'><span class='coords-badge'>" . round($lat, 6) . ", " . round($lng, 6) . "</span></td>";
                        echo "<td data-label='นำทาง'><a href='$gmap_url' target='_blank' class='gmap-btn'>🔵 Google Maps</a></td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='4' style='padding:40px; color:#b2bec3;'>-- ยังไม่มีข้อมูลในระบบ --</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <?php if(!isset($is_index)): 
        $total_res = $conn->query("SELECT COUNT(id) FROM conversion_logs");
        $total_pages = ceil($total_res->fetch_row()[0] / $limit);
        if($total_pages > 1): ?>
            <div style="text-align:center; margin-top:25px;">
                <?php for($i=1; $i<=$total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>&show=<?= $limit ?>" style="
                        display:inline-block; padding:8px 15px; margin:0 5px; border-radius:10px;
                        background: <?= ($page == $i) ? '#6c5ce7' : '#fff' ?>;
                        color: <?= ($page == $i) ? '#fff' : '#6c5ce7' ?>;
                        text-decoration:none; font-weight:700; box-shadow: 0 4px 10px rgba(0,0,0,0.05);
                    "><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; 
    else: ?>
        <div style="text-align:center; margin-top:20px;">
            <a href="data-lat-logn.php" style="color:#6c5ce7; font-weight:700; text-decoration:none;">ดูประวัติทั้งหมดแบบละเอียด ⮕</a>
        </div>
    <?php endif; ?>
</div>