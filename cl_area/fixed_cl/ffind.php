<?php 

//error_reporting(0);    // ปิด error แสดงบนจอ (ยังบันทึก log ได้)
//ob_start();            // เปิด output buffering

include 'db_connect.php';

// ✅ เพิ่ม session_start() เพื่อให้สามารถเข้าถึง $_SESSION ได้
session_start();

// ล้าง buffer output เผื่อมี output แทรก
if (ob_get_level()) {
    ob_end_clean();
}

// ตั้งค่า header ให้ส่ง JSON
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'เกิดข้อผิดพลาดที่ไม่รู้จัก'];

try {
    // ✅ ดึงค่า promotion_unit และ production_year จาก Session
    // (เพราะนี่คือค่าที่ใช้กรองข้อมูลใน land_info_display.php)
    if (!isset($_SESSION['selected_unit_name']) || !isset($_SESSION['selected_production_year_label'])) {
        $response['message'] = 'ไม่พบข้อมูลหน่วยงานหรือปีการผลิตใน Session';
        echo json_encode($response);
        exit();
    }
    $production_year = $_SESSION["selected_production_year_label"]; // ใช้ค่าจาก Session สำหรับ production_year
    $promotion_unit = $_SESSION["selected_unit_name"];             // ใช้ค่าจาก Session สำหรับ promotion_unit


    // รับข้อมูลอื่นๆ จาก POST
    $plcontract_number = $_POST["plcontract_number"] ?? '';
    $plot_id = $_POST["plot_id"] ?? '';
    // $production_year = $_POST["production_year"] ?? ''; // ❌ ลบบรรทัดนี้ออก (เพราะดึงมาจาก Session แล้ว)
    $quota_name = $_POST["quota_name"] ?? '';
    
    // แปลงค่าตัวเลข (ถ้าผิดพลาดจะเป็น null)
    $square_meters = filter_var($_POST["square_meters"] ?? '', FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE); 
    $rai = filter_var($_POST["rai"] ?? '', FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
    $ngan = filter_var($_POST["ngan"] ?? '', FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
    $wah = filter_var($_POST["wah"] ?? '', FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
    $rai_adjusted = filter_var($_POST["rai_adjusted"] ?? '', FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE);
    
    $sugar_type = $_POST["sugar_type"] ?? '';
    // $promotion_unit = $_POST["promotion_unit"] ?? ''; // ❌ ลบบรรทัดนี้ออก (เพราะดึงมาจาก Session แล้ว)
    $promoter_area = $_POST["promoter_area"] ?? '';
    $village = $_POST["village"] ?? '';
    $district_sub = $_POST["district_sub"] ?? '';
    $district = $_POST["district"] ?? '';
    $province = $_POST["province"] ?? '';

    // ✅ เพิ่มการลบช่องว่างออกจาก plcontract_number และ plot_id
    // ใช้ str_replace เพื่อลบช่องว่าง (' ')
    $plcontract_number = str_replace(' ', '', $plcontract_number);
    $plot_id = str_replace(' ', '', $plot_id);

    // ตรวจสอบข้อมูลจำเป็น
    if (empty($plot_id) || empty($plcontract_number)) {
        $response['message'] = 'IDแปลง และ เลขสัญญา ห้ามว่าง';
        echo json_encode($response);
        exit();
    }

    // ✅ เพิ่มการตรวจสอบความยาวสูงสุด (จากที่เคยคุยกัน)
    if (strlen($plot_id) > 7) {
        $response['message'] = 'IDแปลง ต้องไม่เกิน 7 หลัก';
        echo json_encode($response);
        exit();
    }

    if (strlen($plcontract_number) > 6) {
        $response['message'] = 'เลขสัญญา ต้องไม่เกิน 6 หลัก';
        echo json_encode($response);
        exit();
    }

    // ✅ (ทางเลือก) หากต้องการตรวจสอบว่ามีช่องว่างประเภทอื่นที่ไม่ใช่แค่ ' ' หลงเหลืออยู่หรือไม่
    // เช่น tab หรือ newline
    /*
    if (preg_match('/\s/', $plot_id)) {
        $response['message'] = 'IDแปลง ห้ามมีช่องว่าง';
        echo json_encode($response);
        exit();
    }
    if (preg_match('/\s/', $plcontract_number)) {
        $response['message'] = 'เลขสัญญา ห้ามมีช่องว่าง';
        echo json_encode($response);
        exit();
    }
    */


    // เช็ค plot_id ซ้ำในปีผลิตเดียวกัน
    $checkPlotIdSql = "SELECT COUNT(*) FROM land_info WHERE plot_id = :plot_id AND production_year = :production_year";
    $stmt = $conn->prepare($checkPlotIdSql);
    $stmt->bindParam(':plot_id', $plot_id);
    $stmt->bindParam(':production_year', $production_year);
    $stmt->execute();
    if ($stmt->fetchColumn() > 0) {
        $response['message'] = 'Error: แปลงID ' . htmlspecialchars($plot_id) . ' นี้ใช้ไปแล้วสำหรับปีการผลิตนี้.';
        echo json_encode($response);
        exit();
    }

    // เช็คเลขสัญญาซ้ำในหน่วยงานและปีผลิตเดียวกัน
    $checkPlcontractNumberSql = "SELECT COUNT(*) FROM land_info WHERE plcontract_number = :plcontract_number AND promotion_unit = :promotion_unit AND production_year = :production_year";
    $stmt = $conn->prepare($checkPlcontractNumberSql);
    $stmt->bindParam(':plcontract_number', $plcontract_number);
    $stmt->bindParam(':promotion_unit', $promotion_unit); 
    $stmt->bindParam(':production_year', $production_year);
    $stmt->execute();
    if ($stmt->fetchColumn() > 0) {
        $response['message'] = 'Error: เลขสัญญา ' . htmlspecialchars($plcontract_number) . ' นี้มีอยู่แล้วสำหรับหน่วยงานและปีการผลิตนี้.';
        echo json_encode($response);
        exit();
    }

    // Insert ข้อมูล
    $sql = "INSERT INTO land_info(
        plcontract_number, plot_id, production_year, quota_name, square_meters, 
        rai, ngan, wah, rai_adjusted, sugar_type, promotion_unit, 
        promoter_area, village, district_sub, district, province
    ) VALUES (
        :plcontract_number, :plot_id, :production_year, :quota_name, :square_meters, 
        :rai, :ngan, :wah, :rai_adjusted, :sugar_type, :promotion_unit, 
        :promoter_area, :village, :district_sub, :district, :province
    )";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':plcontract_number', $plcontract_number); // ใช้ตัวแปรที่ถูกลบช่องว่างแล้ว
    $stmt->bindParam(':plot_id', $plot_id); // ใช้ตัวแปรที่ถูกลบช่องว่างแล้ว
    $stmt->bindParam(':production_year', $production_year);
    $stmt->bindParam(':quota_name', $quota_name);
    $stmt->bindParam(':square_meters', $square_meters);
    $stmt->bindParam(':rai', $rai);
    $stmt->bindParam(':ngan', $ngan);
    $stmt->bindParam(':wah', $wah);
    $stmt->bindParam(':rai_adjusted', $rai_adjusted);
    $stmt->bindParam(':sugar_type', $sugar_type);
    $stmt->bindParam(':promotion_unit', $promotion_unit);
    $stmt->bindParam(':promoter_area', $promoter_area);
    $stmt->bindParam(':village', $village);
    $stmt->bindParam(':district_sub', $district_sub);
    $stmt->bindParam(':district', $district);
    $stmt->bindParam(':province', $province);

    if ($stmt->execute()) {
        // ดึง ID ล่าสุด
        $lastInsertId = $conn->lastInsertId();

        // ดึงข้อมูลแถวที่เพิ่มใหม่ (เพื่อส่งกลับไปอัปเดต DataTables)
        $stmt_fetch = $conn->prepare("SELECT * FROM land_info WHERE id = :id");
        $stmt_fetch->bindParam(':id', $lastInsertId);
        $stmt_fetch->execute();
        $newData = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

        $response = [
            'status' => 'success',
            'message' => 'บันทึกข้อมูลสำเร็จ!',
            'data' => $newData
        ];
        echo json_encode($response);
        exit();
    } else {
        $errorInfo = $stmt->errorInfo();
        $response = [
            'status' => 'error',
            'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . ($errorInfo[2] ?? 'Unknown error')
        ];
        echo json_encode($response);
        exit();
    }

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'ข้อผิดพลาดฐานข้อมูล: ' . $e->getMessage()
    ]);
    exit();

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
    exit();
}
?>