<?php
session_start();

// ตรวจสอบสิทธิ์ผู้ดูแลระบบก่อนอนุญาตให้บันทึกข้อมูลใดๆ ทั้งสิ้น
// (ไฟล์นี้ไม่เคยมีการเช็คสิทธิ์มาก่อนเลย ใครก็ตามที่รู้ URL ยิง POST ตรงมาได้โดยไม่ต้องล็อกอิน)
if (!isset($_SESSION['emp_level']) || $_SESSION['emp_level'] !== "a") {
    header("Location: login.php");
    exit();
}

// เชื่อมต่อฐานข้อมูลผ่านไฟล์กลาง แทนการ hardcode ค่า localhost/root/ว่างเปล่า
// (ค่าที่ hardcode ไว้ก่อนหน้านี้ใช้ได้แค่บน XAMPP เครื่อง local เท่านั้น พอขึ้น production จะเชื่อมต่อไม่ได้)
require("dbconnect.php");
$conn = $con; // คงชื่อตัวแปร $conn ไว้เหมือนเดิม เพื่อไม่ต้องแก้โค้ดส่วนล่างทั้งไฟล์

// ตั้งค่าการเข้ารหัสเป็น utf8mb4 สำหรับภาษาไทย
$conn->set_charset("utf8mb4"); 

// ฟังก์ชันอัปโหลดรูปภาพ
// รับค่า emp_id, contract_number, plot_id, และชื่อฟิลด์รูปภาพ เพื่อสร้าง path
function uploadImage($fileInputName, $emp_id, $contract_number, $plot_id) {
    // ตรวจสอบว่ามีการเลือกไฟล์และไม่มีข้อผิดพลาดในการอัปโหลด
    if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
        // สร้าง path สำหรับโฟลเดอร์รูปภาพ
        // ใช้ urlencode เพื่อจัดการอักขระพิเศษในชื่อโฟลเดอร์
        $targetDir = "images/water/" . urlencode($emp_id) . "/" . urlencode($contract_number) . "/" . urlencode($plot_id) . "/";

        // ตรวจสอบและสร้างโฟลเดอร์ถ้ายังไม่มี
        // ใช้ 0755 เพื่อความปลอดภัย (Owner read/write/execute, Group read/execute, Others read/execute)
        // แทน 0777 ถ้าไม่จำเป็นต้องให้ทุกคนมีสิทธิ์เขียน
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0755, true)) {
                error_log("Failed to create directory: " . $targetDir);
                return null; // ถ้าสร้างโฟลเดอร์ไม่ได้ ให้คืนค่า null
            }
        }

        $fileName = basename($_FILES[$fileInputName]["name"]);
        // ป้องกันชื่อไฟล์ซ้ำโดยใช้ timestamp นำหน้า
        $filePath = $targetDir . time() . "_" . $fileName;

        // ย้ายไฟล์ที่อัปโหลดจาก temp ไปยังโฟลเดอร์เป้าหมาย
        if (move_uploaded_file($_FILES[$fileInputName]["tmp_name"], $filePath)) {
            return $filePath; // คืนค่า path ที่จะบันทึกในฐานข้อมูล
        } else {
            // บันทึกข้อผิดพลาดในการย้ายไฟล์ลง log
            error_log("Failed to move uploaded file from " . $_FILES[$fileInputName]["tmp_name"] . " to " . $filePath . " Error: " . $_FILES[$fileInputName]["error"]);
            return null; // ถ้าอัปโหลดไม่สำเร็จ ให้คืนค่า null
        }
    }
    return null; // ถ้าไม่มีไฟล์ถูกเลือกหรือมีข้อผิดพลาดในการอัปโหลด
}

// ตรวจสอบรูปแบบวันที่ (yyyy-mm-dd) ก่อนบันทึก กันค่าพังจากฟอร์ม / ช่อง DATE ของ MySQL
function isValidDate($dateStr) {
    if (empty($dateStr)) return false;
    $d = DateTime::createFromFormat('Y-m-d', $dateStr);
    return $d && $d->format('Y-m-d') === $dateStr;
}

// ตรวจสอบว่าเป็นการส่งข้อมูลแบบ POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับค่าจากฟอร์ม
    // ใช้ isset() และ ?? '' เพื่อป้องกัน Undefined index notice
    $emp_id = $_POST['emp_id'] ?? '';
    $year_rai = $_POST['year_rai'] ?? '';
    $contract_number = $_POST['contract_number'] ?? '';
    $quota = $_POST['quota'] ?? ''; // โควต้า ไม่ได้ใช้ใน path รูปภาพที่คุณระบุ แต่ยังคงรับไว้
    $plot_id = $_POST['plot_id'] ?? '';
    $area_rai = $_POST['area_rai'] ?? '';
    $suga_type = $_POST['suga_type'] ?? '';

    // --- ➕ ฟิลด์ใหม่: ข้อมูลเจ้าของแปลง / ที่อยู่ ---
    $citizen_id   = trim($_POST['citizen_id'] ?? '');
    $house_no     = trim($_POST['house_no'] ?? '');
    $sub_district = trim($_POST['sub_district'] ?? '');
    $district     = trim($_POST['district'] ?? '');
    $province     = trim($_POST['province'] ?? '');
    $water_source = trim($_POST['water_source'] ?? '');

    // --- ➕ ฟิลด์ใหม่: วิธีและวันที่ให้น้ำ (ครั้งที่ 1-3) ---
    // ถ้าไม่ได้กรอกหรือรูปแบบวันที่ผิด ให้เป็น NULL แทนที่จะเป็น '' (ป้องกัน DATE column error)
    $water_method1 = trim($_POST['water_method1'] ?? '');
    $water_date1_raw = trim($_POST['water_date1'] ?? '');
    $water_date1 = isValidDate($water_date1_raw) ? $water_date1_raw : null;

    $water_method2 = trim($_POST['water_method2'] ?? '');
    $water_date2_raw = trim($_POST['water_date2'] ?? '');
    $water_date2 = isValidDate($water_date2_raw) ? $water_date2_raw : null;

    $water_method3 = trim($_POST['water_method3'] ?? '');
    $water_date3_raw = trim($_POST['water_date3'] ?? '');
    $water_date3 = isValidDate($water_date3_raw) ? $water_date3_raw : null;

    // เรียกใช้ฟังก์ชันอัปโหลดภาพสำหรับแต่ละฟิลด์
    // ส่ง emp_id, contract_number, plot_id เข้าไปในฟังก์ชัน
    $water_image1 = uploadImage("water_image1", $emp_id, $contract_number, $plot_id);
    $water_image2 = uploadImage("water_image2", $emp_id, $contract_number, $plot_id);
    $water_image3 = uploadImage("water_image3", $emp_id, $contract_number, $plot_id);
    $flood_image = uploadImage("flood_image", $emp_id, $contract_number, $plot_id);
    $drought_image = uploadImage("drought_image", $emp_id, $contract_number, $plot_id);
    $other_image = uploadImage("other_image", $emp_id, $contract_number, $plot_id);

    // บันทึกข้อมูลลงฐานข้อมูลด้วย Prepared Statement เพื่อความปลอดภัย
    $sql = "INSERT INTO image_water (
        emp_id, year_rai, contract_number, quota, plot_id, area_rai, suga_type,
        water_image1, water_image2, water_image3,
        flood_image, drought_image, other_image,
        citizen_id, house_no, sub_district, district, province, water_source,
        water_method1, water_date1, water_method2, water_date2, water_method3, water_date3
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    // ตรวจสอบว่า prepare สำเร็จหรือไม่
    if ($stmt === false) {
        die("เกิดข้อผิดพลาดในการเตรียม Statement: " . $conn->error);
    }

    // bind_param: s = string (สำหรับทุกตัวแปรในที่นี้ รวมถึงวันที่ที่เป็น NULL ก็ยังใช้ 's' ได้ปกติ)
    $stmt->bind_param("sssssssssssssssssssssssss",
        $emp_id, $year_rai, $contract_number, $quota, $plot_id, $area_rai, $suga_type,
        $water_image1, $water_image2, $water_image3,
        $flood_image, $drought_image, $other_image,
        $citizen_id, $house_no, $sub_district, $district, $province, $water_source,
        $water_method1, $water_date1, $water_method2, $water_date2, $water_method3, $water_date3
    );

    // แสดงผลลัพธ์
    if ($stmt->execute()) {
        // ใช้ Heredoc สำหรับ HTML เพื่อความสะอาด
        echo <<<HTML
        <html>
        <head>
            <meta charset="UTF-8">
            <title>บันทึกข้อมูลสำเร็จ</title>
            <meta http-equiv="refresh" content="3;URL=water_insertForm.php">
            <style>
                body {
                    background-color: #e6f2ff;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                    margin: 0;
                    font-family: 'Segoe UI', sans-serif;
                }
                .success-box {
                    background-color: #ffffff;
                    padding: 40px;
                    border-radius: 15px;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
                    text-align: center;
                    max-width: 400px;
                    animation: fadeIn 0.5s ease-in-out;
                }
                .checkmark-svg {
                    width: 80px;
                    height: 80px;
                    margin: 0 auto 20px;
                }
                .checkmark-icon {
                    width: 100%;
                    height: 100%;
                    stroke: #28a745;
                    stroke-width: 4;
                    stroke-miterlimit: 10;
                    animation: scale .3s ease-in-out .9s both;
                }
                .checkmark-circle {
                    stroke-dasharray: 166;
                    stroke-dashoffset: 166;
                    stroke: #28a745;
                    animation: strokeCircle 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
                }
                .checkmark-check {
                    stroke-dasharray: 48;
                    stroke-dashoffset: 48;
                    stroke: #28a745;
                    animation: strokeCheck 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.6s forwards;
                }

                @keyframes strokeCircle {
                    100% { stroke-dashoffset: 0; }
                }
                @keyframes strokeCheck {
                    100% { stroke-dashoffset: 0; }
                }
                @keyframes scale {
                    0%, 100% { transform: none; }
                    50% { transform: scale(1.1); }
                }
                @keyframes fadeIn {
                    from { opacity: 0; transform: scale(0.9); }
                    to { opacity: 1; transform: scale(1); }
                }
            </style>
            <script>
                // JavaScript redirect สำรอง เผื่อ meta refresh ไม่ทำงาน
                setTimeout(function(){
                    window.location.href = 'water_insertForm.php';
                }, 3000);
            </script>
        </head>
        <body>
            <div class="success-box">
                <div class="checkmark-svg">
                    <svg class="checkmark-icon" viewBox="0 0 52 52">
                        <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
                        <path class="checkmark-check" fill="none" d="M14 27l7 7 16-16"/>
                    </svg>
                </div>
                <h2>บันทึกข้อมูลเรียบร้อยแล้ว</h2>
                <p>กำลังกลับไปหน้าฟอร์ม...</p>
            </div>
        </body>
        </html>
        HTML;
    } else {
        // แสดงข้อผิดพลาดจากฐานข้อมูล
        echo "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $stmt->error;
    }

    $stmt->close();
} else {
    // หากเข้าถึงไฟล์นี้โดยตรง ไม่ใช่ผ่านการ submit form
    echo "ไม่พบข้อมูลที่ส่งมา.";
}

$conn->close();
?>