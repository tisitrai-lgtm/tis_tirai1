<?php include_once 'db_config.php'; ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แผนที่ดาวเทียมพิกัดทั้งหมด</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        body, html { margin: 0; padding: 0; height: 100%; }
        #map { height: 100vh; width: 100%; }
        
        .back-btn { 
            position: absolute; top: 20px; left: 60px; z-index: 1000;
            padding: 10px 20px; background: rgba(255, 255, 255, 0.9); border-radius: 8px;
            text-decoration: none; color: #333; font-weight: bold;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3); border: none;
        }
        .back-btn:hover { background: #fff; color: #007bff; }
    </style>
</head>
<body>

<a href="index.php" class="back-btn">⬅ กลับหน้าหลัก</a>

<div id="map"></div>

<script>
    /**
     * 1. ตั้งค่าแผนที่
     * พิกัด [17.0078, 99.8235] คือโซน สุโขทัย อุตรดิตถ์ พิษณุโลก ตาก
     * ซูมระดับ 9 จะเห็นภาพกว้างครอบคลุม 4 จังหวัด
     */
    var map = L.map('map').setView([17.0078, 99.8235], 9);

    /**
     * 2. เรียกใช้ Layer ภาพถ่ายดาวเทียมจาก Google (Hybrid)
     * mt0-mt3 คือ Subdomains ของ Google Maps
     * lyrs=y คือโหมด Hybrid (ดาวเทียม + ชื่อสถานที่/ถนน)
     */
    L.tileLayer('http://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
        maxZoom: 20,
        subdomains:['mt0','mt1','mt2','mt3'],
        attribution: '&copy; Google Maps Satellite'
    }).addTo(map);

    /**
     * 3. ดึงข้อมูลพิกัดจาก Database มาปักหมุดทั้งหมด
     */
    <?php
    $sql = "SELECT note, latitude, longitude FROM conversion_logs";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $note = !empty($row['note']) ? addslashes(htmlspecialchars($row['note'])) : "ไม่มีหมายเหตุ";
            $lat = $row['latitude'];
            $lng = $row['longitude'];
            
            // ปักหมุดพร้อมแสดงหมายเหตุเมื่อคลิก
            echo "L.marker([$lat, $lng]).addTo(map).bindPopup('<b>หมายเหตุ:</b><br>$note');\n";
        }
    }
    ?>
</script>

</body>
</html>