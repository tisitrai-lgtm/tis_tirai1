<?php
function utmToLatLong($raw_easting, $raw_northing, $zone = 47) {
    // 1. นำค่า UTM ดิบมาปรับ Offset ก่อนคำนวณ (Pre-calculate)
    $easting = floatval($raw_easting) - 342;
    $northing = floatval($raw_northing) + 312;

    // 2. เริ่มกระบวนการแปลงค่าโดยใช้ค่าที่ปรับแล้ว ($easting, $northing)
    $a = 6378137.0; 
    $f = 1 / 298.257223563;
    $k0 = 0.9996;
    $e_sq = 1 - pow(1 - $f, 2);
    $e1 = (1 - sqrt(1 - $e_sq)) / (1 + sqrt(1 - $e_sq));
    
    $x = $easting - 500000; 
    $y = $northing;
    $lon0 = ($zone * 6 - 183);

    $M = $y / $k0;
    $mu = $M / ($a * (1 - $e_sq / 4 - 3 * pow($e_sq, 2) / 64 - 5 * pow($e_sq, 3) / 256));

    $phi1 = $mu + (3 * $e1 / 2 - 27 * pow($e1, 3) / 32) * sin(2 * $mu) 
            + (21 * pow($e1, 2) / 16 - 55 * pow($e1, 4) / 32) * sin(4 * $mu)
            + (151 * pow($e1, 3) / 96) * sin(6 * $mu);

    $C1 = ($e_sq / (1 - $e_sq)) * pow(cos($phi1), 2);
    $T1 = pow(tan($phi1), 2);
    $N1 = $a / sqrt(1 - $e_sq * pow(sin($phi1), 2));
    $R1 = $a * (1 - $e_sq) / pow(1 - $e_sq * pow(sin($phi1), 2), 1.5);
    $D = $x / ($N1 * $k0);

    $lat = $phi1 - ($N1 * tan($phi1) / $R1) * (pow($D, 2) / 2 - (5 + 3 * $T1 + 10 * $C1 - 4 * pow($C1, 2)) * pow($D, 4) / 24);
    $lon = ($D - (1 + 2 * $T1 + $C1) * pow($D, 3) / 6) / cos($phi1);

    return [
        'lat'   => rad2deg($lat),
        'long'  => $lon0 + rad2deg($lon),
        'adj_x' => $easting, // ส่งค่าที่ลบ 342 แล้วกลับไป
        'adj_y' => $northing // ส่งค่าที่บวก 312 แล้วกลับไป
    ];
}
?>