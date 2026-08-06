<?php 
// เปิดแสดง Error เพื่อเช็คว่า PHP พังตรงไหน (ถ้าแก้เสร็จแล้วค่อยเอาออก)
ini_set('display_errors', 1);
error_reporting(E_ALL);

include_once 'db_config.php'; 
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <title>แผนที่ดาวเทียม - ภาคเหนือตอนล่าง</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        #map { height: 100vh; width: 100%; background-color: #eee; } /* ใส่สีพื้นหลังกันหน้าขาวตอนรอโหลด */
        body { margin: 0; padding: 0; }
        
        .back-btn { 
            position: absolute; top: 15px; left: 60px; z-index: 1000; 
            padding: 10px 18px; background: white; border-radius: 8px; 
            text-decoration: none; color: #333; font-weight: bold; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.3); border: none;
        }
    </style>
</head>
<body>

<a href="index.php" class="back-btn">⬅ กลับหน้าหลัก</a>
<div id="map"></div>

<script>
    // 1. ตรวจสอบว่า Leaflet โหลดสำเร็จไหม
    if (typeof L === 'undefined') {
        alert("ไม่สามารถโหลดแผนที่ได้ กรุณาเช็คการเชื่อมต่ออินเทอร์เน็ต");
    }

    var map = L.map('map').setView([17.0078, 99.8235], 9);

    // 2. เปลี่ยน URL เป็น HTTPS และใช้ Tile ของ Google Hybrid ที่เสถียรขึ้น
    L.tileLayer('https://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        subdomains:['mt0','mt1','mt2','mt3'],
        attribution: '&copy; Google Maps'
    }).addTo(map);

    // 3. ปักหมุดจาก Database
    <?php
    if ($conn->connect_error) {
        echo "console.error('Database Connection Failed: " . $conn->connect_error . "');";
    } else {
        $sql = "SELECT note, latitude, longitude FROM conversion_logs";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                // ทำความสะอาดข้อมูล Note ป้องกัน JS พัง
                $note = !empty($row['note']) ? $row['note'] : "ไม่มีหมายเหตุ";
                $note = htmlspecialchars($note, ENT_QUOTES, 'UTF-8');
                $note = str_replace(array("\r", "\n"), ' ', $note); // ลบการขึ้นบรรทัดใหม่
                $note = addslashes($note); 
                
                $lat = (float)$row['latitude'];
                $lng = (float)$row['longitude'];
                
                if ($lat != 0 && $lng != 0) {
                    echo "L.marker([$lat, $lng]).addTo(map).bindPopup('<b>หมายเหตุ:</b><br>$note');\n";
                }
            }
        }
    }
    ?>
</script>

</body>
</html>