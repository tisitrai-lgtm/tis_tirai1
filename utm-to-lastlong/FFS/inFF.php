<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>UTM to LatLong Converter (Company Standard)</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; padding: 20px; background: #f4f4f4; }
        .container { max-width: 500px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin: auto; }
        h2 { color: #333; text-align: center; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="number"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background: #218838; }
        .result { margin-top: 20px; padding: 15px; background: #e9ecef; border-left: 5px solid #007bff; }
    </style>
</head>
<body>

<div class="container">
    <h2>UTM 47N ⮕ Lat/Long</h2>
    <form method="POST">
        <div class="form-group">
            <label>ค่า UTM X (Easting):</label>
            <input type="number" name="utm_x" step="0.001" required value="<?php echo $_POST['utm_x'] ?? ''; ?>">
        </div>
        <div class="form-group">
            <label>ค่า UTM Y (Northing):</label>
            <input type="number" name="utm_y" step="0.001" required value="<?php echo $_POST['utm_y'] ?? ''; ?>">
        </div>
        <button type="submit" name="convert">แปลงค่าพิกัด</button>
    </form>

    <?php
    if (isset($_POST['convert'])) {
        $raw_x = floatval($_POST['utm_x']);
        $raw_y = floatval($_POST['utm_y']);

        // 1. นำค่ามาลบ Offset ตามสูตรของบริษัท
        $x_adjusted = $raw_x - 342;
        $y_adjusted = $raw_y + 312;

        // 2. ฟังก์ชันแปลงค่า (UTM WGS84 Zone 47)
        $coords = utmToLatLong($x_adjusted, $y_adjusted, 47);

        echo "<div class='result'>";
        echo "<strong>ผลลัพธ์หลังคำนวณ:</strong><br>";
        echo "X (Adjusted): " . number_format($x_adjusted, 3) . "<br>";
        echo "Y (Adjusted): " . number_format($y_adjusted, 3) . "<hr>";
        echo "<strong>Latitude:</strong> " . $coords['lat'] . "<br>";
        echo "<strong>Longitude:</strong> " . $coords['long'] . "<br>";
        echo "<br><a href='https://www.google.com/maps?q={$coords['lat']},{$coords['long']}' target='_blank'>ดูใน Google Maps</a>";
        echo "</div>";
    }

    function utmToLatLong($easting, $northing, $zone) {
        $a = 6378137.0; 
        $f = 1 / 298.257223563;
        $k0 = 0.9996;
        $e = sqrt(1 - pow(1 - $f, 2));
        $e1 = (1 - sqrt(1 - pow($e, 2))) / (1 + sqrt(1 - pow($e, 2)));
        
        $x = $easting - 500000;
        $y = $northing;
        $lon0 = ($zone * 6 - 183);

        $M = $y / $k0;
        $mu = $M / ($a * (1 - pow($e, 2) / 4 - 3 * pow($e, 4) / 64 - 5 * pow($e, 6) / 256));

        $phi1 = $mu + (3 * $e1 / 2 - 27 * pow($e1, 3) / 32) * sin(2 * $mu) 
                + (21 * pow($e1, 2) / 16 - 55 * pow($e1, 4) / 32) * sin(4 * $mu)
                + (151 * pow($e1, 3) / 96) * sin(6 * $mu);

        $C1 = pow($e, 2) * pow(cos($phi1), 2) / (1 - pow($e, 2));
        $T1 = pow(tan($phi1), 2);
        $N1 = $a / sqrt(1 - pow($e, 2) * pow(sin($phi1), 2));
        $R1 = $a * (1 - pow($e, 2)) / pow(1 - pow($e, 2) * pow(sin($phi1), 2), 1.5);
        $D = $x / ($N1 * $k0);

        $lat = $phi1 - ($N1 * tan($phi1) / $R1) * (pow($D, 2) / 2 - (5 + 3 * $T1 + 10 * $C1 - 4 * pow($C1, 2) - 9 * pow($e, 2)) * pow($D, 4) / 24 + (61 + 90 * $T1 + 298 * $C1 + 45 * pow($T1, 2) - 252 * pow($e, 2) - 3 * pow($C1, 2)) * pow($D, 6) / 720);
        $lon = ($D - (1 + 2 * $T1 + $C1) * pow($D, 3) / 6 + (5 - 2 * $C1 + 28 * $T1 - 3 * pow($C1, 2) + 8 * pow($e, 2) + 24 * pow($T1, 2)) * pow($D, 5) / 120) / cos($phi1);

        return ['lat' => rad2deg($lat), 'long' => $lon0 + rad2deg($lon)];
    }
    ?>
</div>

</body>
</html>