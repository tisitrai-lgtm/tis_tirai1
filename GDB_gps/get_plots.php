<?php
// get_plots.php
require_once 'config.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // เผื่อเรียกข้ามโดเมน

// รับปีการผลิตจากหน้าเว็บออดิต (ถ้าไม่มีให้ดึงปี 68-69 เป็นค่าเริ่มต้น)
$crop_year = $_GET['year'] ?? '68-69';

try {
    // คำสั่ง SQL ดึงข้อมูลแปลงและแปลงพิกัดไบนารีเป็น GeoJSON Text
    $sql = "SELECT id, plot_code, owner_name, area_rai, crop_year, 
                   ST_AsGeoJSON(SHAPE) as geojson 
            FROM sugarcane_plots 
            WHERE crop_year = :crop_year";
            
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':crop_year', $crop_year);
    $stmt->execute();
    
    $features = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (empty($row['geojson'])) continue;
        
        $features[] = [
            "type" => "Feature",
            "geometry" => json_decode($row['geojson']), // แปลงข้อความ JSON ให้เป็น Object แผนที่
            "properties" => [
                "id" => $row['id'],
                "code" => $row['plot_code'],
                "owner" => $row['owner_name'],
                "area" => floatval($row['area_rai']),
                "year" => $row['crop_year']
            ]
        ];
    }
    
    // จัดโครงสร้างให้เป็น FeatureCollection ตามมาตรฐาน GIS
    $response = [
        "type" => "FeatureCollection",
        "features" => $features
    ];
    
    echo json_encode($response);

} catch (PDOException $e) {
    echo json_encode(["error" => "เกิดข้อผิดพลาดในการดึงข้อมูล: " . $e->getMessage()]);
}
?>