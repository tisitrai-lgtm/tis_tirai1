<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>Auditor - แผนที่ตรวจแปลงอ้อย</title>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        #map { height: 100vh; width: 100vw; }
        
        /* กล่องเลือกปีการผลิตด้านบน */
        .top-filter {
            position: absolute; top: 12px; left: 60px; z-index: 1000;
            background: white; padding: 8px 12px; border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15); display: flex; align-items: center; gap: 8px;
        }
        .top-filter select { padding: 6px; border-radius: 4px; border: 1px solid #ccc; font-size: 14px; font-weight: bold; }

        /* แผงข้อมูลสไตล์แอปมือถือเด้งจากด้านล่าง */
        .bottom-sheet {
            position: absolute; bottom: 0; left: 0; right: 0; z-index: 1000;
            background: white; padding: 20px; border-top-left-radius: 20px; border-top-right-radius: 20px;
            box-shadow: 0 -4px 15px rgba(0,0,0,0.15); display: none;
            max-height: 40vh; overflow-y: auto;
        }
        .bottom-sheet h3 { margin: 0 0 10px 0; color: #2c3e50; font-size: 18px; }
        .bottom-sheet p { margin: 5px 0; color: #555; font-size: 14px; }
        .nav-button {
            display: block; width: 100%; background-color: #007bff; color: white;
            text-align: center; padding: 12px 0; border: none; border-radius: 8px;
            font-size: 16px; font-weight: bold; text-decoration: none; margin-top: 15px;
            box-shadow: 0 4px 6px rgba(0,123,255,0.2);
        }
        .nav-button:active { background-color: #0056b3; }
        
        /* ปุ่มเปิด/ปิด แผงข้อมูล */
        .close-sheet { float: right; font-size: 20px; color: #aaa; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>

    <div class="top-filter">
        <label style="font-size: 13px; font-weight: bold; color: #333;">ปีการผลิต:</label>
        <select id="yearSelect" onchange="fetchPlotsData()">
            <option value="68-69">2568 / 2569</option>
            <option value="69-70">2569 / 2570</option>
            <option value="70-71">2570 / 2571</option>
        </select>
    </div>

    <div id="map"></div>

    <div id="plotDetailSheet" class="bottom-sheet">
        <span class="close-sheet" onclick="closeSheet()">&times;</span>
        <h3 id="sheetTitle">แปลงอ้อย: กำลังโหลด...</h3>
        <p id="sheetCode">รหัสแปลง: -</p>
        <p id="sheetArea">ขนาดพื้นที่: - ไร่</p>
        <p id="sheetYear">ปีการผลิต: -</p>
        <a id="googleNavUrl" href="#" target="_blank" class="nav-button">🗺️ นำทางด้วย Google Maps</a>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // 1. เริ่มตั้งค่าแผนที่ (ตั้งศูนย์กลางไว้ที่พิกัดประเทศไทย และเปิดโหมด Canvas ให้ลื่นไหล)
        const map = L.map('map', { preferCanvas: true }).setView([15.4, 101.2], 7);

        // 2. ดึงแผนที่ภาพถ่ายดาวเทียมของ Google เพื่อให้เห็นแนวต้นอ้อยชัดเจน
        L.tileLayer('https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            attribution: '© Google Maps'
        }).addTo(map);

        let plotsLayer;
        let userMarker;
        
        // 3. ฟังก์ชันดึงข้อมูลแปลงอ้อยจาก PHP ตามปีที่เลือก
        async function fetchPlotsData() {
            const selectedYear = document.getElementById('yearSelect').value;
            closeSheet(); // ปิดหน้าต่างด้านล่างก่อน

            try {
                const response = await fetch(`get_plots.php?year=${selectedYear}`);
                const geojsonData = await response.json();

                // ลบแปลงอ้อยเก่าออกก่อนดึงปีใหม่
                if (plotsLayer) { map.removeLayer(plotsLayer); }

                // วาดโพลีกอนแปลงอ้อยลงแผนที่
                plotsLayer = L.geoJSON(geojsonData, {
                    style: {
                        color: '#ffeb3b',       // เส้นขอบสีเหลืองเรืองแสงตัดกับแผนที่ดาวเทียม
                        weight: 2,
                        fillColor: '#4caf50',   // ข้างในสีเขียวแปลงอ้อย
                        fillOpacity: 0.25
                    },
                    onEachFeature: function (feature, layer) {
                        // เมื่อออดิตจิ้มที่โพลีกอนแปลงอ้อยใด ๆ
                        layer.on('click', function (e) {
                            showPlotDetail(feature.properties, e.latlng);
                            L.DomEvent.stopPropagation(e); // ป้องกันการคลิกซ้อนโดนพื้นหลัง
                        });
                    }
                }).addTo(map);

                // ถ้ามีข้อมูลโพลีกอนให้แผนที่ซูมไปหาแปลงอ้อยอัตโนมัติ
                if (geojsonData.features && geojsonData.features.length > 0) {
                    map.fitBounds(plotsLayer.getBounds());
                }

            } catch (error) {
                console.error("Error fetching map data:", error);
                alert("ไม่สามารถติดต่อเซิร์ฟเวอร์หลังบ้านได้");
            }
        }

        // 4. ฟังก์ชันเปิดหน้าต่างแสดงรายละเอียดแปลงและสร้างลิงก์นำทาง
        function showPlotDetail(properties, clickCoords) {
            document.getElementById('sheetTitle').innerText = "แปลงอ้อย: " + (properties.owner || "ไม่ระบุชื่อ");
            document.getElementById('sheetCode').innerText = "รหัสแปลง: " + properties.code;
            document.getElementById('sheetArea').innerText = "ขนาดพื้นที่: " + properties.area + " ไร่";
            document.getElementById('sheetYear').innerText = "ปีการผลิต: " + properties.year;

            // ฝังพิกัดตรงจุดที่ออดิตคลิก เพื่อสร้างลิงก์เปิดแอป Google Maps นำทางในมือถือ
            const navUrl = `https://www.google.com/maps/dir/?api=1&destination=${clickCoords.lat},${clickCoords.lng}&travelmode=driving`;
            document.getElementById('googleNavUrl').href = navUrl;

            // ดึงแผงข้อมูลเด้งขึ้นมาจากขอบจอด้านล่าง
            document.getElementById('plotDetailSheet').style.display = 'block';
        }

        function closeSheet() {
            document.getElementById('plotDetailSheet').style.display = 'none';
        }

        // 5. ระบบติดตามตำแหน่ง GPS จริงของออดิต (เดินถือไปจุดสีฟ้าจะขยับตามจริง)
        if (navigator.geolocation) {
            navigator.geolocation.watchPosition(function(position) {
                const uLat = position.coords.latitude;
                const uLng = position.coords.longitude;

                // วาดมาร์กเกอร์จุดสีฟ้าแสดงตัวตนออดิตบนแผนที่
                if (!userMarker) {
                    userMarker = L.circleMarker([uLat, uLng], {
                        radius: 8, fillColor: '#007bff', color: '#ffffff', weight: 2, fillOpacity: 1
                    }).addTo(map).bindPopup("ตำแหน่งของคุณ").openPopup();
                } else {
                    userMarker.setLatLng([uLat, uLng]);
                }
            }, function(err) {
                console.log("GPS โหลดพิกัดไม่ได้ หรือไม่ได้เปิดสิทธิ์เข้าถึงพิกัด");
            }, { enableHighAccuracy: true });
        }

        // โหลดข้อมูลแปลงอ้อยครั้งแรกทันทีที่เปิดหน้าเว็บ
        map.whenReady(fetchPlotsData);
    </script>
</body>
</html>