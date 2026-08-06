<?php
// ###########################################################
// ไฟล์: exportPDF.php
// ###########################################################

session_start(); // สำคัญ: ต้องมี session_start() เพื่อดึงค่าที่ Login มาใช้
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!class_exists('FPDF')) {
    include('1/fpdf.php'); 
}

require 'db_connect.php'; 

if (!$conn) {
    die("Connection failed: Database connection object not found.");
}

// --- ส่วนที่ 1: ดึงข้อมูลจากตาราง land_info (โค้ดเดิมของคุณ) ---
$id = $_GET['id'] ?? null;
if (!filter_var($id, FILTER_VALIDATE_INT)) {
    die("Invalid ID provided.");
}

$cer_stmt = $conn->prepare("SELECT * FROM land_info WHERE id = :id");
$cer_stmt->bindParam(':id', $id, PDO::PARAM_INT);
$cer_stmt->execute();
$re_cer = $cer_stmt->fetch(PDO::FETCH_ASSOC);

if (!$re_cer) {
    die("Data not found for ID: " . htmlspecialchars($id));
}

// --- ส่วนที่ 2: เตรียมชื่อ นสส. และ หัวหน้าหน่วย ---

// 1. ชื่อ นสส. ดึงมาจากฟิลด์ 'promoter_area' ในฐานข้อมูลที่เพิ่ง Query มา (ซึ่งมาจากหน้า insertForm)
$nss_name = !empty($re_cer['promoter_area']) ? $re_cer['promoter_area'] : '                       ';

// 2. ชื่อหัวหน้าหน่วย ดึงอัตโนมัติจากตาราง employees ตาม unit_id ที่ Login อยู่
$head_name = '                         '; // ค่าเริ่มต้น
$current_unit_id = $_SESSION['selected_unit_id'] ?? null;

if ($current_unit_id) {
    $stmt_head = $conn->prepare("SELECT emp_name FROM employees WHERE unit_id = :unit_id AND role = 'HEAD' LIMIT 1");
    $stmt_head->execute([':unit_id' => $current_unit_id]);
    $head_data = $stmt_head->fetch(PDO::FETCH_ASSOC);
    if ($head_data) {
        $head_name = $head_data['emp_name'];
    }
}

// --- ส่วนที่ 3: สร้าง PDF ---
$pdf = new FPDF();


// 1. ความปลอดภัย: ใช้ Prepared Statement สำหรับ $_GET['id']
$id = $_GET['id'] ?? null; // ใช้ Null Coalescing Operator เพื่อป้องกัน Undefined index error หากไม่มี ID

if (!filter_var($id, FILTER_VALIDATE_INT)) { // ตรวจสอบว่า ID เป็นตัวเลขเท่านั้น
    die("Invalid ID provided.");
}

// ✅ แก้ไขตรงนี้: ใช้ bindParam() ของ PDO แทน bind_param() ของ MySQLi
$cer_stmt = $conn->prepare("SELECT * FROM land_info WHERE id = :id"); // ใช้ Placeholder แบบ named parameter :id
if ($cer_stmt === false) {
    // หากเกิดข้อผิดพลาดในการ prepare
    die("Prepare failed: " . htmlspecialchars($conn->errorInfo()[2])); // สำหรับ PDO
}
// $cer_stmt->bind_param("i", $id); // <<< ลบบรรทัดนี้ออก!

// ✅ ใช้ bindParam() หรือส่ง array ใน execute()
$cer_stmt->bindParam(':id', $id, PDO::PARAM_INT); // ผูกค่าแบบ Named Parameter และระบุประเภทเป็น INT
$cer_stmt->execute(); // หรือ $cer_stmt->execute([':id' => $id]); ถ้าต้องการส่ง array ใน execute()

// รับผลลัพธ์
$re_cer = $cer_stmt->fetch(PDO::FETCH_ASSOC); // ใช้ fetch() ของ PDO แทน fetch_assoc() ของ MySQLi

// 2. การจัดการข้อมูล: ตรวจสอบว่าพบข้อมูลหรือไม่
if (!$re_cer) {
    die("Data not found for ID: " . htmlspecialchars($id));
}


// ตั้งค่า PDF
$pdf->addpage('P');
// ตรวจสอบให้แน่ใจว่าไฟล์ฟอนต์ THSarabun.php และ THSarabun.ttf อยู่ในโฟลเดอร์เดียวกันกับ fpdf.php
// หรืออยู่ในโฟลเดอร์ที่สามารถเข้าถึงได้และได้สร้าง .php font file ด้วย makefont.php แล้ว
$pdf->addfont('sa','','THSarabun.php'); // ฟอนต์สำหรับภาษาไทย

$pdf->setfont('sa','',16); // ตั้งค่าฟอนต์เริ่มต้น
// ใช้ $pdf->SetXY() และ $pdf->Cell() ตามลำดับที่ถูกต้อง

// 3. ✅ การใช้ข้อมูลที่ดึงมา
// ตัวอย่างการแสดงผล
$pdf->setXY(15,170);
$pdf->Cell(0,10,iconv('utf-8','cp874','ปีการผลิต :     '.$re_cer['production_year']),0,1,'c');

$pdf->setXY(15,180);
$pdf->Cell(0,10,iconv('utf-8','cp874','ไอดีแปลง :     '.$re_cer['plot_id']),0,1,'c');

$pdf->setXY(69,180);
$pdf->Cell(0,10,iconv('utf-8','cp874','ชนิดอ้อย :    '.$re_cer['sugar_type']),0,1,'c');

$pdf->setXY(15,190);
$pdf->Cell(0,10,iconv('utf-8','cp874','เลขสัญญา :    '.$re_cer['plcontract_number']),0,1,'c');

$pdf->setXY(69,190);
$pdf->Cell(0,10,iconv('utf-8','cp874','ชื่อโคต้า :     '.$re_cer['quota_name']),0,1,'c');

$pdf->setXY(140,180);
$pdf->Cell(0,10,iconv('utf-8','cp874','หน่วยส่งเสริม :     '.$re_cer['promotion_unit']),0,1,'c');

$pdf->setXY(140,190);
$pdf->Cell(0,10,iconv('utf-8','cp874','เขต นสส.   :  '.$re_cer['promoter_area']),0,1,'c');

$pdf->setXY(15,200);
$pdf->Cell(0,10,iconv('utf-8','cp874','ที่อยู่แปลง      หมู่ : '.$re_cer['village']),0,1,'c');

$pdf->setXY(68,200);
$pdf->Cell(0,10,iconv('utf-8','cp874','   ตำบล : '.$re_cer['district_sub']),0,1,'c');

$pdf->setXY(108,200);
$pdf->Cell(0,10,iconv('utf-8','cp874','อำเภอ : '.$re_cer['district']),0,1,'c');

$pdf->setXY(148,200);
$pdf->Cell(0,10,iconv('utf-8','cp874','จังหวัด : '.$re_cer['province']),0,1,'c');

$pdf->setXY(10,215);
$pdf->Cell(0,0,'',1,1,'c');

$pdf->setXY(35,220);
$pdf->Cell(0,10,iconv('utf-8','cp874','เนื้อที่      '.$re_cer['square_meters']),0,1,'c');
$pdf->setXY(65,220);
$pdf->Cell(0,10,iconv('utf-8','cp874','ตารางเมตร'),0,1,'c');

$pdf->setXY(35,230);
$pdf->Cell(0,10,iconv('utf-8','cp874','เนื้อที่         '.$re_cer['rai']),0,1,'c');

$pdf->setXY(70,230);
$pdf->Cell(0,10,iconv('utf-8','cp874','ไร่          '.$re_cer['ngan']),0,1,'c');

$pdf->setXY(95,230);
$pdf->Cell(0,10,iconv('utf-8','cp874','งาน          '.$re_cer['wah']),0,1,'c');

$pdf->setXY(120,230);
$pdf->Cell(0,10,iconv('utf-8','cp874','       ตารางวา'),0,1,'c');

$pdf->setXY(35,240);
$pdf->Cell(0,10,iconv('utf-8','cp874','เนื้อที่จ่ายเงินส่งเสริม          '.$re_cer['rai_adjusted']),0,1,'c');
$pdf->setXY(90 ,240);
$pdf->Cell(0,10,iconv('utf-8','cp874',' ไร่'),0,1,'c');


$pdf->setXY(12,255);
$pdf->Cell(0,10,iconv('utf-8','cp874', '   ลงชื่อ....................................นสส.'),0,1,'c');
$pdf->setXY(11,265);
$pdf->Cell(0,10,iconv('utf-8','cp874','   (         ' . $nss_name . '        )'),0,1,'c');

$pdf->setXY(72,255);
$pdf->Cell(0,10,iconv('utf-8','cp874','   ลงชื่อ.....................................หัวหน้าหน่วย'),0,1,'c');
$pdf->setXY(74,265);
$pdf->Cell(0,10,iconv('utf-8','cp874','    (          ' . $head_name . '        )'),0,1,'c');

$pdf->setXY(145,255);
$pdf->Cell(0,10,iconv('utf-8','cp874','ลงชื่อ....................................ผู้ตรวจ'),0,1,'c');
$pdf->setXY(145,265);
$pdf->Cell(0,10,iconv('utf-8','cp874',' (                                     )'),0,1,'c');


#กรอบรูป (คอมเมนต์นี้ไม่น่าจะเกี่ยวกับการแสดงผลรูปจริง)


$pdf->setXY(88,5);
$pdf->setfont('sa','',18);
$pdf->Cell(0,10,iconv('utf-8','cp874','รายการคำนวนแผนที่'),0,1,'c');

#200m (คอมเมนต์นี้ไม่น่าจะเกี่ยวกับการแสดงผลจริง)


$pdf->Output();
?>