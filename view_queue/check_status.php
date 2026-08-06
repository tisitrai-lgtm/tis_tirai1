<?php
include 'db_connect.php'; 
mb_internal_encoding("UTF-8");

// --- 1. ดึงทะเบียนมาทำ Suggestion ---
function getPlateSuggestions($conn) {
    $sql = "SELECT DISTINCT plate FROM (
                SELECT tractor_plate AS plate FROM queue_entries
                UNION 
                SELECT trailer_plate AS plate FROM queue_entries WHERE trailer_plate IS NOT NULL
            ) AS combined 
            WHERE plate != '' AND plate != '-'
            ORDER BY plate ASC";
    $result = $conn->query($sql);
    $plates = [];
    while ($row = $result->fetch_assoc()) { $plates[] = $row['plate']; }
    return $plates;
}

// --- 2. AJAX Search (ปรับปรุง Logic ค้นหาเลขท้าย) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_type'])) {
    header('Content-Type: application/json');
    $val = mb_strtoupper(trim($_POST['search_val']), 'UTF-8');
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $limit = 5; 
    $offset = ($page - 1) * $limit;

    if (empty($val)) {
        echo json_encode(['found' => false, 'message' => 'กรุณากรอกทะเบียน']);
        exit;
    }

    $safe_val = $conn->real_escape_string($val);
    
    // Logic: ใช้ % นำหน้า เพื่อให้หาเจอทุกอันที่ "ลงท้ายด้วย" เลขที่คีย์มา
    // หรือถ้าคีย์มาแค่บางส่วน ก็จะดึงรายการที่เกี่ยวข้องทั้งหมดขึ้นมาโชว์
    $where_clause = "q.tractor_plate LIKE '%$safe_val%' OR q.trailer_plate LIKE '%$safe_val%'";
    
    $sql = "SELECT q.*, r.round_number 
            FROM queue_entries q
            LEFT JOIN rounds r ON q.round_id = r.round_id
            WHERE $where_clause
            ORDER BY r.round_number DESC, q.created_at DESC, q.entry_id DESC
            LIMIT $limit OFFSET $offset";

    $count_sql = "SELECT COUNT(*) as total FROM queue_entries q WHERE $where_clause";
    
    $result = $conn->query($sql);
    $total_rows = $conn->query($count_sql)->fetch_assoc()['total'];
    $total_pages = ceil($total_rows / $limit);

    $data_list = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data_list[] = [
                'round' => $row['round_number'],
                'queue' => $row['queue_number'],
                'tractor' => $row['tractor_plate'],
                'trailer' => $row['trailer_plate'] ?: '-'
            ];
        }
        echo json_encode(['found'=>true, 'results'=>$data_list, 'current_page'=>$page, 'total_pages'=>$total_pages]);
    } else {
        echo json_encode(['found'=>false]);
    }
    $conn->close(); exit;
}

$plates_list = getPlateSuggestions($conn);
$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เช็คคิวรถอ้อย</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #16a085; --bg: #f8fafc; }
        body { font-family: 'Kanit', sans-serif; background: var(--bg); margin: 0; padding: 20px; display: flex; justify-content: center; }
        
        .main-wrapper { 
            width: 100%; max-width: 550px; 
            background: white; border-radius: 25px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.08); 
            padding: 30px 20px; min-height: 80vh;
        }

        .header-box { 
            background: #235d4e; padding: 20px; border-radius: 15px; color: white; text-align: center; margin-bottom: 30px;
        }

        .search-area { margin-bottom: 10px; }
        .input-label { font-size: 0.9rem; color: #94a3b8; margin-bottom: 8px; display: block; text-align: left; }
        input { 
            width: 100%; padding: 15px; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 1.1rem;
            box-sizing: border-box; font-family: 'Kanit'; margin-bottom: 15px;
        }
        .btn-search { 
            width: 100%; padding: 15px; background: #3fa389; color: white; border: none;
            border-radius: 12px; font-size: 1.2rem; font-weight: 600; cursor: pointer;
        }
        .hint-text { font-size: 0.8rem; color: #64748b; margin-top: -10px; margin-bottom: 20px; text-align: left; }

        .divider { height: 1px; background: #f1f5f9; margin: 20px 0; }
        
        /* สไตล์รายการผลลัพธ์ */
        .result-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 18px 5px; border-bottom: 1px solid #f1f5f9;
        }
        .result-item:last-child { border-bottom: none; }
        .round-tag { background: #f1f5f9; color: #64748b; padding: 2px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: bold; }
        .plate-name { font-size: 1.15rem; font-weight: 600; color: #1e293b; margin: 5px 0; }
        .trailer-name { font-size: 0.9rem; color: #94a3b8; }
        
        .queue-box { text-align: right; }
        .q-label { font-size: 0.75rem; color: #94a3b8; display: block; }
        .q-number { font-size: 2.2rem; font-weight: 800; color: #16a085; line-height: 1; }

        .pagination { display: flex; justify-content: center; gap: 8px; padding: 20px 0; }
        .pg-btn { padding: 8px 14px; border: 1px solid #e2e8f0; background: white; border-radius: 8px; cursor: pointer; color: #64748b; }
        .pg-btn.active { background: #16a085; color: white; border-color: #16a085; font-weight: bold; }
    </style>
</head>
<body>

<div class="main-wrapper">
    <div class="header-box">
        <h2 style="margin:0;">ระบบตรวจสอบคิวรถ</h2>
        <p style="margin:5px 0 0 0; font-weight: 300; font-size: 0.85rem; opacity: 0.8;">ตรวจสอบประวัติคิวได้รวดเร็ว</p>
    </div>

    <div class="search-area">
        <label class="input-label">🔎 ค้นหาทะเบียนรถ</label>
        <input type="text" id="plate-input" placeholder="พิมพ์เลข 4 ตัวท้าย เช่น 4475" list="plate-suggestions">
        <p class="hint-text">* หากจำทะเบียนเต็มไม่ได้ ให้พิมพ์เฉพาะตัวเลข 4 ตัวท้าย</p>
        <button class="btn-search" onclick="doSearch()">ค้นหาข้อมูล</button>
    </div>

    <datalist id="plate-suggestions">
        <?php foreach($plates_list as $p): ?> <option value="<?php echo htmlspecialchars($p); ?>"> <?php endforeach; ?>
    </datalist>

    <div class="divider"></div>

    <div id="results-content"></div>
    <div id="pg-content" class="pagination"></div>
</div>

<script>
    let currentQ = "";

    function doSearch() {
        currentQ = document.getElementById('plate-input').value;
        if(!currentQ) return;
        loadPage(1);
    }

    function loadPage(p) {
        const content = document.getElementById('results-content');
        const pg = document.getElementById('pg-content');
        content.innerHTML = "<p style='text-align:center; color:#94a3b8; padding:20px;'>⌛ กำลังค้นหาข้อมูลที่เกี่ยวข้อง...</p>";

        fetch('check_status.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({ search_type: 'any', search_val: currentQ, page: p })
        })
        .then(r => r.json())
        .then(data => {
            if(data.found) {
                let html = "";
                data.results.forEach(item => {
                    html += `
                    <div class="result-item">
                        <div>
                            <span class="round-tag">รอบที่ ${item.round}</span>
                            <div class="plate-name">${item.tractor}</div>
                            <div class="trailer-name">พ่วง: ${item.trailer}</div>
                        </div>
                        <div class="queue-box">
                            <span class="q-label">คิวที่</span>
                            <span class="q-number">${item.queue}</span>
                        </div>
                    </div>`;
                });
                content.innerHTML = html;
                renderPagination(data.current_page, data.total_pages);
            } else {
                content.innerHTML = "<div style='text-align:center; color:#94a3b8; padding:40px;'><p>❌ ไม่พบข้อมูล</p><p style='font-size:0.8rem;'>ลองพิมพ์เฉพาะตัวเลข 4 ตัวท้าย</p></div>";
                pg.innerHTML = "";
            }
        });
    }

    function renderPagination(curr, total) {
        const pg = document.getElementById('pg-content');
        if(total <= 1) { pg.innerHTML = ""; return; }
        let h = "";
        for(let i=1; i<=total; i++) {
            if(i==1 || i==total || (i>=curr-1 && i<=curr+1)) {
                h += `<button class="pg-btn ${i==curr?'active':''}" onclick="loadPage(${i})">${i}</button>`;
            }
        }
        pg.innerHTML = h;
    }
</script>

</body>
</html>