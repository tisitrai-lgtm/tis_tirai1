<?php include_once 'functions.php'; ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <link rel="icon" type="image/png" href="icon/TIS-1.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบแปลงค่าหมุด TIS</title>
    <link href="https://fonts.googleapis.com/css2?family=Anuphan:wght@300;400;600;700&family=Inter:wght@400;700&display=swap" rel="stylesheet">
    
    <style>
        /* จัดวาง Layout หลัก ให้เป็นโทนเดียวกับหน้าประวัติ */
        body { 
            font-family: 'Anuphan', sans-serif; 
            background: #f4f7fe; 
            padding: 20px; 
            margin: 0;
            color: #2d3436;
        }
        
        .wrapper { max-width: 1000px; margin: auto; }
        
        /* สไตล์ของกรอบ (Card) แบบ Soft UI */
        .card { 
            background: #fff; 
            padding: clamp(20px, 5vw, 35px); 
            border-radius: 24px; 
            box-shadow: 0 15px 35px rgba(108, 92, 231, 0.08); 
            margin-bottom: 25px; 
            border: 1px solid rgba(255,255,255,0.8);
        }
        
        h2 { color: #6c5ce7; text-align: center; margin-top: 0; font-weight: 700; font-size: 1.5rem; }
        
        /* สไตล์ Form */
        label { display: block; font-weight: 600; margin-bottom: 8px; color: #636e72; font-size: 0.9rem; }
        
        input[type="number"], input[type="text"], textarea { 
            width: 100%; 
            padding: 14px; 
            margin-bottom: 20px; 
            border: 2px solid #edf2f7; 
            border-radius: 12px; 
            box-sizing: border-box; 
            font-family: 'Anuphan';
            font-size: 16px;
            transition: 0.3s;
            outline: none;
            background: #f8fafc;
        }

        input:focus { border-color: #6c5ce7; background: #fff; box-shadow: 0 0 0 4px rgba(108, 92, 231, 0.1); }
        
        .btn-calc { 
            width: 100%; 
            padding: 16px; 
            background: #6c5ce7; 
            color: white; 
            border: none; 
            cursor: pointer; 
            border-radius: 16px; 
            font-size: 16px; 
            font-weight: 700; 
            box-shadow: 0 8px 15px rgba(108, 92, 231, 0.2);
            transition: 0.3s;
        }
        .btn-calc:hover { transform: translateY(-2px); box-shadow: 0 12px 20px rgba(108, 92, 231, 0.3); }
        
        /* สไตล์ผลลัพธ์ */
        .result-area { 
            background: #fff; 
            padding: 25px; 
            margin-top: 25px; 
            border-radius: 20px; 
            border: 2px dashed #6c5ce7; 
            text-align: center; 
        }
        
        .coords-display {
            font-family: 'Inter';
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3436;
            margin: 10px 0;
        }

        .map-link { 
            display: inline-block;
            margin-top: 15px;
            color: #339af0; 
            font-weight: 700; 
            text-decoration: none; 
            font-size: 16px; 
            padding: 10px 20px;
            background: rgba(51, 154, 240, 0.1);
            border-radius: 12px;
            transition: 0.3s;
        }
        .map-link:hover { background: #339af0; color: #fff; }

        .btn-save { 
            width: 100%; 
            padding: 14px; 
            background: #00b894; 
            color: white; 
            border: none; 
            border-radius: 12px; 
            font-size: 16px; 
            cursor: pointer; 
            font-weight: 700; 
            margin-top: 15px; 
            box-shadow: 0 5px 15px rgba(0, 184, 148, 0.2);
        }

        /* Map UI */
        #map-result {
            width: 100%; 
            height: 400px; 
            border-radius: 20px; 
            border: none; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin: 20px 0;
        }

        /* ประวัติย่อ */
        .history-preview-btn {
            display: block;
            text-align: center;
            padding: 15px;
            background: #fff;
            color: #6c5ce7;
            font-weight: 700;
            text-decoration: none;
            border-radius: 15px;
            border: 2px solid #6c5ce7;
            transition: 0.3s;
        }
        .history-preview-btn:hover { background: #6c5ce7; color: #fff; }

        /* ปรับปรุง Modal ให้สวยขึ้น */
        .modal-content {
            background: #f4f7fe; 
            margin: 2% auto; 
            padding: 20px; 
            border-radius: 28px; 
            width: 95%; 
            max-width: 1100px; 
            height: 85vh; 
            overflow-y: auto; 
            position: relative; 
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            border: 8px solid rgba(255,255,255,0.3);
        }

        @media (max-width: 768px) {
            body { padding: 10px; }
            .card { padding: 20px; }
            #map-result { height: 300px; }
        }
        /* ดันให้ SweetAlert2 อยู่บนสุดของทุกสิ่ง */
.swal2-container {
    z-index: 99999 !important;
}
    </style>
</head>
<body>
 <?php include 'nvb.php'; ?>
<div class="wrapper">
   
    <div class="card">
        <h2>🔄 แปลงพิกัด UTM 47N เป็น (lat,lon)</h2>
        <form method="POST">
            <label>ค่า UTM พิกัส X :</label>
            <input type="number" name="ux" step="0.001" placeholder="ระบุค่า X" required value="<?= $_POST['ux'] ?? '' ?>">
            
            <label>ค่า UTM พิกัส Y :</label>
            <input type="number" name="uy" step="0.001" placeholder="ระบุค่า Y" required value="<?= $_POST['uy'] ?? '' ?>">
            
            <button type="submit" name="do_calc" class="btn-calc">คำนวณพิกัด</button>
        </form>

        <?php
        if (isset($_POST['do_calc'])) {
            $raw_x = floatval($_POST['ux']);
            $raw_y = floatval($_POST['uy']);
            $res = utmToLatLong($raw_x, $raw_y, 47);

            $lat = number_format($res['lat'], 7, '.', '');
            $lng = number_format($res['long'], 7, '.', '');
            $ax  = $res['adj_x']; 
            $ay  = $res['adj_y'];
            
            $g_url = "https://www.google.com/maps?q=$lat,$lng{$lat},{$lng}";

            echo "<div class='result-area'>";
                echo "<span style='color:#636e72; font-weight:600;'>พิกัดที่คำนวณได้:</span>";
                echo "<div class='coords-display'>$lat, $lng</div>";

                echo "<div style='margin: 20px 0;'>";
                    echo "<div id='map-result'></div>";
                    echo "<link rel='stylesheet' href='https://unpkg.com/leaflet@1.9.4/dist/leaflet.css' />";
                    echo "<script src='https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'></script>";
                    echo "<script>
                        var mapResult = L.map('map-result').setView([$lat, $lng], 17);
                        L.tileLayer('http://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
                            maxZoom: 20, subdomains:['mt0','mt1','mt2','mt3'],
                            attribution: '© Google Maps Satellite'
                        }).addTo(mapResult);
                        L.marker([$lat, $lng]).addTo(mapResult)
                            .bindPopup('<b>พิกัดคำนวณ</b><br>$lat, $lng').openPopup();
                    </script>";
                echo "</div>";

                echo "<a href='$g_url' target='_blank' class='map-link'>📍 เปิดใน Google Maps</a>";
                echo "<div style='margin-top:15px; color:#adb5bd; font-size:12px;'>ค่าปรับแก้จริง: X: $ax, Y: $ay</div>";
                
                echo "<form action='insertData.php' method='POST'>";
                echo "<input type='hidden' name='utm_x' value='$raw_x'><input type='hidden' name='utm_y' value='$raw_y'>";
                echo "<input type='hidden' name='adj_x' value='$ax'><input type='hidden' name='adj_y' value='$ay'>";
                echo "<input type='hidden' name='lat' value='$lat'><input type='hidden' name='lng' value='$lng'><input type='hidden' name='note' value=''>";
                echo "<button type='submit' class='btn-save'>💾 บันทึกลงฐานข้อมูล</button>";
                echo "</form>";
            echo "</div>";
        }
        ?>
    </div>

    <div class="card" style="padding: 10px; background: transparent; box-shadow: none; border: none;">
        <?php 
            $is_index = true; 
            include 'data-lat-logn.php'; 
        ?>
        <div style="margin-top: 20px; text-align: center;">
            <a href="javascript:void(0)" onclick="openHistoryModal()" class="history-preview-btn">
                ดูประวัติทั้งหมดแบบละเอียด (Pop-up) ⮕
            </a>
        </div>
    </div>
</div>

<div id="historyModal" style="display:none; position:fixed; z-index:10000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.7); backdrop-filter:blur(8px);">
    <div class="modal-content">
        <button onclick="closeHistoryModal()" style="position:absolute; right:20px; top:20px; background:#ff7675; color:white; border:none; border-radius:50%; width:40px; height:40px; cursor:pointer; font-size:20px; font-weight:bold; z-index:100;">×</button>
        <div id="history-content">
            <div style="text-align:center; padding:100px;">
                <h3 style="color:#6c5ce7;">กำลังดึงข้อมูล...</h3>
            </div>
        </div>
    </div>
</div>

<div id="editModal" style="display:none; position:fixed; z-index:20000; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.6);">
    <div style="background:#fff; width:90%; max-width:400px; margin:15% auto; padding:30px; border-radius:24px; box-shadow:0 10px 40px rgba(0,0,0,0.2); position:relative;">
        <h3 style="margin-top:0; color:#6c5ce7;">📝 เพิ่มโน๊ตช่วยบันทึกหมุด</h3>        
        <form id="editForm">
            <input type="hidden" id="edit_id" name="id">
            <label>ข้อความหมายเหตุ:</label>
            <textarea id="edit_note" name="note" rows="4" placeholder="กรอกข้อมูลที่นี่..."></textarea>
            <div style="display:flex; gap:10px;">
                <button type="submit" style="flex:2; padding:14px; background:#00b894; color:#fff; border:none; border-radius:12px; cursor:pointer; font-weight:700;">บันทึก</button>
                <button type="button" onclick="closeEditModal()" style="flex:1; padding:14px; background:#f1f2f6; color:#636e72; border:none; border-radius:12px; cursor:pointer; font-weight:600;">ยกเลิก</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// ฟังก์ชันจัดการประวัติ (History Modal)
function openHistoryModal() {
    document.getElementById('historyModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
    fetch('data-lat-logn.php?is_ajax=1')
        .then(res => res.text())
        .then(data => { document.getElementById('history-content').innerHTML = data; });
}

function closeHistoryModal() {
    document.getElementById('historyModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// ฟังก์ชันแก้ไข (Edit Modal)
function handleEditClick(element) {
    const id = element.getAttribute('data-id');
    const note = element.getAttribute('data-note');
    
    // --- จุดที่เพิ่ม: ปลดล็อคปุ่มบันทึกทุกครั้งที่เปิดหน้าต่างแก้ไข ---
    const btnSubmit = document.querySelector('#editForm button[type="submit"]');
    if(btnSubmit) {
        btnSubmit.disabled = false;
        btnSubmit.innerText = 'บันทึก';
    }
    // ------------------------------------------------------

    document.getElementById('edit_id').value = id;
    document.getElementById('edit_note').value = note;
    document.getElementById('editModal').style.display = 'block';
    setTimeout(() => document.getElementById('edit_note').focus(), 100);
}

function closeEditModal() { 
    document.getElementById('editModal').style.display = 'none'; 
}

document.getElementById('editForm').onsubmit = function(e) {
    e.preventDefault();
    
    const btnSubmit = this.querySelector('button[type="submit"]');
    btnSubmit.disabled = true;
    btnSubmit.innerText = 'กำลังบันทึก...';

    const formData = new FormData(this);

    fetch('update_note.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        if(data.trim() === 'success') {
            closeEditModal(); 
            
            Swal.fire({
                icon: 'success',
                title: 'บันทึกเรียบร้อย!',
                showConfirmButton: false,
                timer: 1500,
                target: document.body 
            }).then(() => {
                // เช็คว่าถ้าเป็นหน้า Pop-up ประวัติให้โหลด AJAX
                const historyModal = document.getElementById('historyModal');
                if (historyModal && historyModal.style.display === 'block') {
                    const limit = document.querySelector('.limit-selector select')?.value || 20;
                    loadHistoryAjax(1, limit); 
                } else {
                    location.reload();
                }
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: data,
                target: document.body
            });
            // คืนค่าปุ่มถ้าบันทึกไม่สำเร็จ
            btnSubmit.disabled = false;
            btnSubmit.innerText = 'บันทึก';
        }
    })
    .catch(error => {
        Swal.fire('Error', 'การเชื่อมต่อผิดพลาด', 'error');
        // คืนค่าปุ่มถ้าเน็ตหลุด/error
        btnSubmit.disabled = false;
        btnSubmit.innerText = 'บันทึก';
    });
};

window.onclick = function(e) {
    if (e.target == document.getElementById('editModal')) closeEditModal();
    if (e.target == document.getElementById('historyModal')) {
        if(typeof closeHistoryModal === "function") closeHistoryModal();
    }
}
</script>
</body>
</html>