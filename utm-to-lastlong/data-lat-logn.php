<?php 
$is_ajax = isset($_GET['is_ajax']);
include_once 'db_config.php'; 

// 1. Pagination Logic
if (isset($is_index) && $is_index === true) {
    $limit = 10;
    $page = 1;
    $start = 0;
} else {
    $limit = isset($_GET['show']) ? intval($_GET['show']) : 20;
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $start = ($page - 1) * $limit;
}
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Anuphan:wght@300;400;600;700&family=Inter:wght@400;700&display=swap');

    .history-table-wrapper { 
        font-family: 'Anuphan', sans-serif;
        background: #f4f7fe; 
        padding: clamp(10px, 4vw, 25px); 
        border-radius: 24px; 
        box-shadow: 0 15px 35px rgba(0,0,0,0.05); 
        max-width: 1200px;
        margin: 10px auto;
    }

    .header-area { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        margin-bottom: 20px; 
        background: #ffffff;
        padding: 15px 20px;
        border-radius: 18px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        flex-wrap: wrap;
        gap: 10px;
    }

    .header-area h3 { color: #6c5ce7; font-weight: 700; margin: 0; font-size: 1.2rem; }

    /* Limit Selector Style */
    .limit-selector { font-size: 14px; color: #636e72; font-weight: 600; }
    .limit-selector select {
        padding: 6px 12px;
        border-radius: 10px;
        border: 2px solid #edf2f7;
        font-family: 'Anuphan';
        outline: none;
        cursor: pointer;
    }

    /* Table & Responsive Card */
    table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
    th { padding: 15px; color: #b2bec3; font-weight: 600; text-align: center; font-size: 13px; text-transform: uppercase; }
    
    td { 
        padding: 15px; background: #ffffff; 
        border: none; text-align: center;
        color: #2d3436; font-size: 15px;
        transition: 0.2s;
    }
    
    td:first-child { border-radius: 16px 0 0 16px; }
    td:last-child { border-radius: 0 16px 16px 0; }
    tr:hover td { background: #fcfdff; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.03); }

    /* Badges */
    .note-badge {
        background: #d2f8ff; color: #0a4fe4; 
        padding: 6px 14px; border-radius: 10px; 
        font-weight: 600; font-size: 13px; display: inline-block;
        max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .coords-badge {
        background: #f1f3f5; color: #495057;
        padding: 6px 12px; border-radius: 10px;
        font-family: 'Inter'; font-weight: 700; font-size: 13px;
    }

    .gmap-btn {
        background: #339af0; color: white !important;
        padding: 8px 16px; border-radius: 12px;
        text-decoration: none; font-size: 13px; font-weight: 600;
        display: inline-flex; align-items: center; gap: 5px;
    }

    .action-edit { color: #4c6ef5; font-weight: 700; text-decoration: none; margin-right: 12px; }
    .action-delete { color: #fa5252; font-weight: 700; text-decoration: none; }

    /* MOBILE BREAKPOINT */
    @media screen and (max-width: 768px) {
        .header-area { flex-direction: column; text-align: center; }
        thead { display: none; } /* ซ่อนหัวตารางในมือถือ */
        
        tr { 
            display: block; background: #fff; margin-bottom: 15px; 
            border-radius: 20px; padding: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.02);
        }
        
        td { 
            display: flex; justify-content: space-between; align-items: center;
            text-align: right; padding: 10px 15px; 
            border-bottom: 1px solid #f8f9fa;
        }
        
        td:last-child { border-bottom: none; }
        td:first-child, td:last-child { border-radius: 0; }
        
        td::before {
            content: attr(data-label);
            font-weight: 700; color: #adb5bd; font-size: 12px; text-transform: uppercase;
        }
        
        .note-badge { max-width: 180px; }
        .gmap-btn { width: 40%; justify-content: center; }
    }
</style>

<div class="history-table-wrapper">
    <div class="header-area">
        <h3>🚀 บันทึกพิกัดล่าสุด</h3>
        
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <?php if(!isset($is_index) || $is_index === false): ?>
                <div class="limit-selector" style="margin-right: 10px;">
                    แสดง <select onchange="changeLimit(this.value)">
                        <?php foreach([20, 50, 100, 250] as $v): ?>
                            <option value="<?= $v ?>" <?= $limit == $v ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div style="display: flex; gap: 8px;">
                <a href="map-all.php" style="
                    background: #00b894; color: white !important; 
                    padding: 8px 16px; border-radius: 12px; 
                    text-decoration: none; font-weight: 700; font-size: 14px;
                    box-shadow: 0 4px 12px rgba(0,184,148,0.2);
                    display: inline-flex; align-items: center; gap: 5px;
                    transition: 0.3s;
                " onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    📍 ดูหมุดทั้งหมด
                </a>

               
            </div>
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>จัดการ</th> 
                <th>โน๊ต</th>
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
                    $lat = round($row['latitude'], 6);
                    $lng = round($row['longitude'], 6);
                    $clean_note = htmlspecialchars($row['note']);
                    
                    echo "<tr>";
                        echo "<td data-label='จัดการ'>";
// ค้นหาบรรทัดที่มีปุ่มแก้ไขเดิม แล้วแทนที่ด้วยอันนี้ครับ
// ตรงส่วน <tbody> ในไฟล์ data-lat-logn.php
            echo "<a href='javascript:void(0)' class='action-edit' 
                    data-id='".$row['id']."' 
                    data-note='".$clean_note."' 
                     onclick='handleEditClick(this)'>เพิ่มโน๊ต</a>";         
            echo "<a href='delete.php?id=".$row['id']."' 
                    class='action-delete' 
                    onclick='return confirm(\"ยืนยันการลบ?\")'>ลบหมุด</a>";
                        echo "</td>";
                        echo "<td data-label='โน๊ต'><span class='note-badge'>" . (!empty($row['note']) ? $clean_note : "---") . "</span></td>";
                        echo "<td data-label='พิกัด'><span class='coords-badge'>$lat, $lng</span></td>";
                        echo "<td data-label='นำทาง'><a href='https://www.google.com/maps?q=$lat,$lng' target='_blank' class='gmap-btn'>📍 Google Maps</a></td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='4' style='padding:50px; color:#adb5bd;'>ไม่มีข้อมูล</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <?php if(!isset($is_index) || $is_index === false): 
        $total_res = $conn->query("SELECT COUNT(id) FROM conversion_logs");
        $total_records = $total_res->fetch_row()[0];
        $total_pages = ceil($total_records / $limit);
        
        if($total_pages > 1): ?>
            <div style="text-align:center; margin-top:20px; display: flex; justify-content: center; gap: 8px; flex-wrap: wrap;">
                <?php for($i=1; $i<=$total_pages; $i++): ?>
                    <a href="javascript:void(0)" onclick="goToPage(<?= $i ?>, <?= $limit ?>)" style="
                        min-width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;
                        border-radius: 12px; text-decoration: none; font-weight: 700; transition: 0.3s;
                        background: <?= ($page == $i) ? '#6c5ce7' : '#fff' ?>;
                        color: <?= ($page == $i) ? '#fff' : '#6c5ce7' ?>;
                        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
                    "><?= $i ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function changeLimit(newLimit) {
    if (typeof openHistoryModal === "function") {
        loadHistoryAjax(1, newLimit);
    } else {
        window.location.href = `?page=1&show=${newLimit}`;
    }
}

function goToPage(page, limit) {
    if (typeof openHistoryModal === "function") {
        loadHistoryAjax(page, limit);
    } else {
        window.location.href = `?page=${page}&show=${limit}`;
    }
}

function loadHistoryAjax(page, limit) {
    const container = document.getElementById('history-content');
    if(container) {
        container.style.opacity = '0.5'; // ทำจางๆ ระหว่างโหลด
        fetch(`data-lat-logn.php?is_ajax=1&page=${page}&show=${limit}`)
            .then(res => res.text())
            .then(html => {
                container.innerHTML = html;
                container.style.opacity = '1';
            });
    }
}
</script>