<?php
// ต้องมีไฟล์ db_connect.php ในโครงสร้างเดียวกัน
include 'db_connect.php'; 

// ตั้งค่าให้ PHP รองรับ UTF-8 สำหรับการค้นหาทะเบียนที่มีภาษาไทย
mb_internal_encoding("UTF-8");

// ฟังก์ชันสำหรับดึงข้อมูลรอบที่กำลังทำงาน
function getActiveRoundInfo($conn) {
    $sql = "SELECT r.round_id, r.round_number, COUNT(q.entry_id) AS current_count 
            FROM rounds r 
            LEFT JOIN queue_entries q ON r.round_id = q.round_id
            WHERE r.is_active = 1 
            GROUP BY r.round_id, r.round_number";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null; 
}

// ----------------------------------------------------
// --- 1. AJAX Search Handler (จัดการการค้นหา) ---
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['round_num'])) {
    header('Content-Type: application/json');
    
    $round_num = trim($_POST['round_num']);
    // แปลงเป็นตัวใหญ่เพื่อค้นหาให้ตรงกับฐานข้อมูล และรองรับอักขระไทย
    $queue_num = mb_strtoupper(trim($_POST['queue_num'] ?? ''), 'UTF-8');       // หมายเลขคิวหลัก (รวมหัว/พ่วง)
    $head_plate_num = mb_strtoupper(trim($_POST['head_plate_num'] ?? ''), 'UTF-8'); // ทะเบียนหัวลาก
    $towed_plate_num = mb_strtoupper(trim($_POST['towed_plate_num'] ?? ''), 'UTF-8'); // ทะเบียนพ่วงข้าง
    
    $response = [
        'found' => false, 
        'round_searched' => htmlspecialchars($round_num), 
        'queue_searched' => htmlspecialchars($queue_num),
        'head_plate_searched' => htmlspecialchars($head_plate_num),
        'towed_plate_searched' => htmlspecialchars($towed_plate_num),
        'search_criteria_met' => false
    ];

    // ตรวจสอบความถูกต้องหลัก: ต้องมีรอบ และต้องมีอย่างน้อยหนึ่งใน (คิว, หัวลาก, พ่วงข้าง)
    if (empty($round_num) || (empty($queue_num) && empty($head_plate_num) && empty($towed_plate_num))) {
        $response['message'] = 'กรุณากรอกหมายเลขรอบ และต้องกรอก คิว, ทะเบียนหัวลาก, หรือทะเบียนพ่วงข้าง อย่างน้อยหนึ่งอย่าง';
        echo json_encode($response);
        $conn->close();
        exit;
    }

    $response['search_criteria_met'] = true;
    
    $safe_round_num = $conn->real_escape_string($round_num);
    
    // 1. Find round_id
    $sql_round = "SELECT round_id FROM rounds WHERE round_number = '$safe_round_num'";
    $result_round = $conn->query($sql_round);
    
    if ($result_round->num_rows > 0) {
        $round_data = $result_round->fetch_assoc();
        $round_id = $round_data['round_id'];
        
        $where_clause = "WHERE round_id = $round_id";
        
        // Build WHERE clause based on ALL provided optional inputs (using AND)
        if (!empty($queue_num)) {
            $safe_queue_num = $conn->real_escape_string($queue_num);
            $where_clause .= " AND queue_number = '$safe_queue_num'";
        }
        if (!empty($head_plate_num)) {
            $safe_head_plate_num = $conn->real_escape_string($head_plate_num);
            $where_clause .= " AND tractor_plate = '$safe_head_plate_num'";
        }
        if (!empty($towed_plate_num)) {
            $safe_towed_plate_num = $conn->real_escape_string($towed_plate_num);
            // ใช้ COALESCE เพื่อจัดการกรณีที่ trailer_plate เป็น NULL (เนื่องจากฐานข้อมูลเดิมมีการบันทึก NULL)
            $where_clause .= " AND COALESCE(trailer_plate, '') = '$safe_towed_plate_num'";
        }

        // Final SQL Query (SELECT * to get all info)
        $sql_entry = "SELECT queue_number, tractor_plate, trailer_plate FROM queue_entries $where_clause";
        
        $result_entry = $conn->query($sql_entry);
        
        if ($result_entry->num_rows > 0) {
            $entry = $result_entry->fetch_assoc();
            $response['found'] = true;
            $response['queue_number'] = htmlspecialchars($entry['queue_number']);
            $response['tractor_plate'] = htmlspecialchars($entry['tractor_plate']);
            $response['trailer_plate'] = htmlspecialchars($entry['trailer_plate'] ?? '-'); // ตรวจสอบ NULL
        } else {
            $response['message'] = 'ไม่พบรายการคิวที่ตรงกับเงื่อนไขที่ระบุในรอบนี้';
        }
        
    } else {
        $response['message'] = 'ไม่พบหมายเลขรอบที่ระบุในระบบ';
    }

    echo json_encode($response);
    $conn->close();
    exit;
}

// ----------------------------------------------------
// --- 2. Initial Page Load (สำหรับแสดงสถานะปัจจุบัน) ---
// ----------------------------------------------------
$current_round_info = getActiveRoundInfo($conn);
$round_number = $current_round_info['round_number'] ?? 'N/A';
$current_count = $current_round_info['current_count'] ?? 0;
$is_active = $current_round_info ? true : false;

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สถานะคิวรถอ้อย | ผู้ดู</title>
    <style>
        /* Modern CSS Reset */
        *, *::before, *::after {
            box-sizing: border-box;
        }

        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            padding: 0; 
            background-color: #f4f7f9; 
            color: #333;
            text-align: center;
            line-height: 1.6;
        }
        .container { 
            /* ปรับให้กระชับลง */
            max-width: 750px; 
            margin: 20px auto; 
            padding: 30px; 
            background-color: #ffffff; 
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); 
            border-radius: 18px; 
        }
        h1 { 
            color: #007bff; 
            margin-bottom: 5px;
            font-size: 30px; /* ลดขนาด H1 เล็กน้อย */
            font-weight: 700;
        }
        h2 {
            font-size: 18px; /* ลดขนาด H2 เล็กน้อย */
            color: #5a6270;
            margin-top: 10px;
            margin-bottom: 20px; /* ลด margin-bottom */
        }

        /* Status Box */
        .status-box {
            padding: 20px; /* ลด padding */
            margin: 20px 0 30px 0; /* ลด margin */
            border-radius: 12px;
            color: white;
            font-size: 20px;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .status-active { background-color: #28a745; }
        .status-inactive { background-color: #dc3545; }
        .status-box p { margin: 5px 0 0 0; font-size: 15px; opacity: 0.9; } /* ลดขนาด p เล็กน้อย */

        /* --- Search Form (4 Input - 2x2 Grid) --- */
        #search-form {
            display: grid;
            grid-template-columns: repeat(2, 1fr); 
            gap: 20px 15px; /* ลด gap */
            margin-bottom: 25px; /* ลด margin-bottom */
        }
        
        .input-group {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }
        
        #search-button {
            grid-column: span 2; 
            padding: 14px; /* ลด padding */
            background-color: #007bff;
            color: white;
            border-radius: 10px;
            font-size: 18px; /* ลดขนาด font */
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
        }
        #search-button:hover { background-color: #0056b3; }
        #search-button:active { transform: scale(0.99); }
        #search-button:disabled { background-color: #a0a0a0; cursor: not-allowed; box-shadow: none; }
        
        .input-group label {
            font-weight: 600;
            margin-bottom: 6px; /* ลด margin-bottom */
            font-size: 14px; /* ลดขนาด font */
            color: #007bff;
            width: 100%;
        }
        #round-input, #queue-input, #head-plate-input, #towed-plate-input {
            width: 100%;
            padding: 12px; /* ลด padding */
            border: 1px solid #ced4da;
            border-radius: 8px;
            text-align: center;
            font-size: 17px; /* ลดขนาด font */
            font-weight: 600;
            color: #333;
            background-color: #ffffff;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        #round-input:focus, #queue-input:focus, #head-plate-input:focus, #towed-plate-input:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
            outline: none;
        }

        /* NEW: Faint Placeholder Style */
        #search-form input::placeholder {
            color: #b0b0b0; /* สีเทาอ่อน */
            opacity: 1; 
            font-weight: normal; /* ตัวอย่างจะดูจางลง */
        }

        /* --- Result Display (Plate Info) --- */
        #result-display {
            min-height: 180px; /* ลด min-height */
            padding: 25px; /* ลด padding */
            border: 1px solid #e9ecef;
            border-radius: 12px;
            background-color: #ffffff;
            margin-top: 20px; /* ลด margin-top */
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .result-found {
            border: 3px solid #28a745 !important;
            background-color: #e6ffed !important;
        }
        .result-not-found {
            border: 3px solid #dc3545 !important;
            background-color: #ffe6e6 !important;
        }
        
        .plates-display {
            width: 90%;
            margin: 10px auto; /* ลด margin */
        }
        .plate-label {
            font-size: 15px;
            margin-top: 10px; /* ลด margin-top */
        }
        .plate-value {
            font-size: 34px; /* ลดขนาด font */
            font-weight: 800;
            color: #007bff;
            margin: 5px 0; /* ลด margin */
            border-bottom: 3px solid #007bff;
        }
        .plate-value.trailer {
            color: #28a745; 
            border-bottom: 3px solid #28a745;
        }
        .queue-result {
            font-size: 45px; /* ลดขนาด font */
            font-weight: 900;
            color: #dc3545;
        }
        .check-message {
            font-size: 13px; /* ลดขนาด font */
            margin-top: 15px; /* ลด margin-top */
        }
        
        /* Responsive Adjustment for smaller screens */
        @media (max-width: 768px) {
            .container {
                margin: 15px;
                padding: 20px;
            }
            #search-form {
                grid-template-columns: 1fr; /* Single column layout on mobile */
                gap: 15px;
            }
            #search-button {
                grid-column: span 1;
            }
            .queue-result {
                font-size: 38px;
            }
            .plate-value {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📑 ระบบตรวจสอบคิวรถอ้อย</h1>
        
        <div class="status-box <?php echo $is_active ? 'status-active' : 'status-inactive'; ?>">
            <?php if ($is_active): ?>
                รอบที่กำลังทำงาน: <?php echo $round_number; ?>
                <p>คิวที่ลงทะเบียนแล้วในรอบนี้: <?php echo $current_count; ?> รายการ</p>
            <?php else: ?>
                สถานะ: ปิดรอบ
                <p>โปรดกรอกหมายเลขรอบเพื่อค้นหารายการที่ผ่านมา</p>
            <?php endif; ?>
        </div>
        
        <h2>กรอกข้อมูลรอบ และ หมายเลขคิว หรือ ทะเบียนรถหัวลาก</h2>
        <form id="search-form">
            <div class="input-group">
                <label for="round-input">1. หมายเลขรอบ (ต้องกรอบ):</label>
                <input type="number" id="round-input" name="round_num" placeholder="กรอบรอบที่" required min="1">
            </div>
            
            <div class="input-group">
                <label for="queue-input">2. หมายเลขคิว (หัวลาก/พ่วง):</label>
                <input type="text" id="queue-input" name="queue_num" placeholder="เช่น 45 หรือ 45/1">
            </div>
            
            <div class="input-group">
                <label for="head-plate-input">3. ทะเบียนรถหัวลาก:</label>
                <input type="text" id="head-plate-input" name="head_plate_num" placeholder="เช่น อต80-9654">
            </div>

            <div class="input-group">
                <label for="towed-plate-input">4. ทะเบียนพ่วง:</label>
                <input type="text" id="towed-plate-input" name="towed_plate_num" placeholder="เช่น อต80-5678">
            </div>
            
            <button type="submit" id="search-button">
                🔍 ค้นหาข้อมูลคิว
            </button>
        </form>

        <div id="result-display">
            กรุณากรอกข้อมูลเพื่อเริ่มค้นหา...
        </div>

    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('search-form');
            const roundInput = document.getElementById('round-input');
            const queueInput = document.getElementById('queue-input');
            const headPlateInput = document.getElementById('head-plate-input');
            const towedPlateInput = document.getElementById('towed-plate-input');
            const resultDisplay = document.getElementById('result-display');
            const searchButton = document.getElementById('search-button');

            // --- Function สำหรับแสดงผลลัพธ์ ---
            function displayResult(data) {
                resultDisplay.className = '';
                resultDisplay.innerHTML = '';
                
                if (data.found) {
                    resultDisplay.classList.add('result-found');
                    
                    let trailerDisplay;
                    const isTrailerPlateEmpty = (data.trailer_plate === '-' || data.trailer_plate.trim() === '');
                    
                    if (isTrailerPlateEmpty) {
                        trailerDisplay = `
                            <p class="plate-label">ทะเบียนพ่วง :</p>
                            <div class="plate-value trailer" style="border-bottom: 3px dashed #28a745;">ไม่พบในระบบ</div>
                        `;
                    } else {
                        trailerDisplay = `
                            <p class="plate-label">ทะเบียนพ่วง:</p>
                            <div class="plate-value trailer">${data.trailer_plate}</div>
                        `;
                    }

                    resultDisplay.innerHTML = `
                        <h2>✅ พบข้อมูลคิวในระบบแล้ว!</h2>
                        
                        <p class="plate-label">หมายเลขคิว (รอบที่ ${data.round_searched}):</p>
                        <div class="queue-result">${data.queue_number}</div>
                        
                        <div class="plates-display">
                            <p class="plate-label">ทะเบียนรถหัวลาก (Tractor Plate):</p>
                            <div class="plate-value">${data.tractor_plate}</div>
                        </div>
                        
                        <div class="plates-display">${trailerDisplay}</div>
                        
                        <p class="check-message">
                          
                        </p>
                    `;

                } else {
                    // แสดงเมื่อไม่พบ
                    resultDisplay.classList.add('result-not-found');
                    
                    let searchInfo = '';
                    // แสดงข้อมูลที่ผู้ใช้ค้นหา
                    if (data.queue_searched) searchInfo += `คิว: ${data.queue_searched} / `;
                    if (data.head_plate_searched) searchInfo += `หัวลาก: ${data.head_plate_searched} / `;
                    if (data.towed_plate_searched) searchInfo += `พ่วงข้าง: ${data.towed_plate_searched}`;
                    searchInfo = searchInfo.replace(/ \/ $/, ''); // ลบเครื่องหมาย / สุดท้าย

                    resultDisplay.innerHTML = `
                        <h2>❌ ไม่พบข้อมูลคิว</h2>
                        <p style="margin-bottom: 5px;">รอบที่: ${data.round_searched}</p>
                        <p>เงื่อนไขที่ค้นหา: ${searchInfo}</p>
                        <p style="font-weight: bold; color: #dc3545; margin-top: 15px;">ไม่พบรายการคิวที่ตรงกับ "ทุกเงื่อนไข" ที่ระบุ</p>
                        <p style="font-size: 14px; margin-top: 10px;">โปรดตรวจสอบข้อมูลที่ป้อนอีกครั้ง</p>
                    `;
                }
            }

            // --- Event Listener สำหรับการค้นหาหลัก (Submit) ---
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const round_num = roundInput.value.trim();
                const queue_num = queueInput.value.trim(); 
                const head_plate_num = headPlateInput.value.trim();
                const towed_plate_num = towedPlateInput.value.trim();

                // Validation: Round must be filled, and at least one of the optionals must be filled
                if (!round_num) {
                    roundInput.focus();
                    resultDisplay.className = 'result-not-found';
                    resultDisplay.innerHTML = '<h2>❌ ผิดพลาด</h2><p>กรุณากรอกหมายเลขรอบ (Round) ที่ต้องการค้นหา</p>';
                    return;
                }
                
                if (!queue_num && !head_plate_num && !towed_plate_num) {
                    resultDisplay.className = 'result-not-found';
                    resultDisplay.innerHTML = '<h2>❌ ผิดพลาด</h2><p>กรุณากรอก หมายเลขคิว, ทะเบียนหัวลาก, หรือ ทะเบียนพ่วงข้าง อย่างน้อยหนึ่งช่อง</p>';
                    return;
                }
                
                searchButton.disabled = true;
                searchButton.textContent = 'กำลังค้นหา...';

                // ใช้ Fetch API เพื่อส่งข้อมูลแบบ AJAX
                fetch('check_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    // ส่งข้อมูลทั้งหมด 4 ค่า
                    body: `round_num=${encodeURIComponent(round_num)}&queue_num=${encodeURIComponent(queue_num)}&head_plate_num=${encodeURIComponent(head_plate_num)}&towed_plate_num=${encodeURIComponent(towed_plate_num)}`
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    displayResult(data);
                })
                .catch(error => {
                    console.error('Error during fetch:', error);
                    resultDisplay.innerHTML = `<h2>เกิดข้อผิดพลาดในการเชื่อมต่อ!</h2><p>(${error.message})</p>`;
                    resultDisplay.classList.add('result-not-found');
                })
                .finally(() => {
                    searchButton.disabled = false;
                    searchButton.textContent = '🔍 ค้นหาข้อมูลคิว';
                });
            });
        });
    </script>
</body>
</html>