<?php
ob_start();
session_start();

if (!isset($_SESSION['emp_id'])) {
    die("กรุณาเข้าสู่ระบบก่อนครับ");
}

if (!class_exists('FPDF')) {
    include('cl_area/1/fpdf.php'); 
}

require 'dbconnect.php'; 

// --- ส่วนที่เพิ่ม: ดึงปีการผลิตจาก Session เพื่อล็อคข้อมูล ---
$selected_year = $_SESSION['year_rai'] ?? ''; 
$year_escaped = mysqli_real_escape_string($con, $selected_year);

// 1. จัดการรับค่าจาก POST (เพราะหน้าบ้านส่งเป็น Array plot_ids มา) ---
$plot_ids = isset($_POST['plot_ids']) ? $_POST['plot_ids'] : [];

if (empty($plot_ids)) {
    echo "<script>alert('กรุณาเลือกรายการแปลงก่อนครับ'); window.history.back();</script>";
    exit;
}

// --- 2. ดึงข้อมูลเพื่อบันทึก Log (ป้องกันคนเหลี่ยม) ---
// เราจะดึงข้อมูลจากแปลงแรกในรายการที่เลือกมาเพื่อเอาเลขสัญญาและชื่อโควต้า (ดึงตามปีที่เลือก)
$first_plot_id = mysqli_real_escape_string($con, $plot_ids[0]);
$sql_check = "SELECT contract_number, quota FROM image_water WHERE plot_id = '$first_plot_id' AND year_rai = '$year_escaped' LIMIT 1";
$res_check = mysqli_query($con, $sql_check);
$row_log_data = mysqli_fetch_assoc($res_check);

if($row_log_data) {
    $log_emp_id = mysqli_real_escape_string($con, $_SESSION['emp_id']);
    $log_contract = mysqli_real_escape_string($con, $row_log_data['contract_number']);
    $log_quota = mysqli_real_escape_string($con, $row_log_data['quota']);

    // บันทึกลงตาราง pdf_export_logs
    $sql_log = "INSERT INTO pdf_export_logs (emp_id, contract_number, quota, created_at) 
                VALUES ('$log_emp_id', '$log_contract', '$log_quota', NOW())";
    
    mysqli_query($con, $sql_log); 
}

// --- 1. ดึงข้อมูล นสส. (ดึงหน่วย emp_unit) ---
$emp_id = mysqli_real_escape_string($con, $_SESSION['emp_id']);
$res_emp = mysqli_query($con, "SELECT emp_name, emp_unit FROM employee WHERE emp_id = '$emp_id'");
$row_emp = mysqli_fetch_assoc($res_emp);
$emp_unit = $row_emp['emp_unit'] ?? '................';

// --- 2. ดึงเลขลำดับ (นับตามรายการที่พิมพ์จริง โดยล็อคตามปีที่เลือก) ---
$sql_count = "SELECT COUNT(DISTINCT print_round) as row_num 
              FROM print_history 
              WHERE emp_id = '$emp_id' AND year_rai = '$year_escaped'";
$res_count = mysqli_query($con, $sql_count);
$row_count = mysqli_fetch_assoc($res_count);
$print_no = $row_count['row_num'] ?? '1';

// --- 3. ดึงข้อมูลเจ้าของสัญญา (ล็อคปีการผลิต) ---
$sql_owner = "SELECT * FROM image_water WHERE plot_id = '$first_plot_id' AND year_rai = '$year_escaped' LIMIT 1";
$res_owner = mysqli_query($con, $sql_owner);
$owner = mysqli_fetch_assoc($res_owner);

// เตรียมข้อมูลลงตัวแปร (ถ้าไม่มีให้ว่างไว้)
$f_name = $owner['farmer_name'] ?? '';
// --- จุดที่แก้ไข: ทำเลขสัญญาให้เป็น 6 หลัก ---
$c_no   = isset($owner['contract_number']) ? str_pad($owner['contract_number'], 6, "0", STR_PAD_LEFT) : '';
// ---------------------------------------
$cit_id = $owner['citizen_id'] ?? '';
$addr   = $owner['address_no'] ?? '';
$moo    = $owner['moo'] ?? '';
$tam    = $owner['tambon'] ?? '';
$amp    = $owner['amphoe'] ?? '';
$prov   = $owner['province'] ?? '';

$pdf = new FPDF();
$pdf->AddPage('P');
$pdf->AddFont('sa','','THSarabun.php');

// --- ฟังก์ชันสำหรับเขียนตัวหนังสือทับเส้นจุดไข่ปลา ---
function TextOnDot($pdf, $x, $y, $text) {
    $pdf->SetXY($x, $y - 0.8); // ขยับขึ้นนิดนึงเพื่อให้ตัวหนังสือลอยเหนือเส้น
    $pdf->Cell(0, 10, iconv('utf-8', 'cp874', $text), 0, 0);
}

// --- เริ่มวาดเอกสาร ---

// 1. ลำดับที่
$pdf->SetFont('sa','',16);
$pdf->SetXY(150, 10);
$pdf->Cell(45, 10, iconv('utf-8', 'cp874', 'ลำดับที่ ........................'), 0, 0, 'R');
TextOnDot($pdf, 180, 10.2, $print_no); // ใส่เลขทับเส้น

// 2. หัวข้อ
$pdf->SetFont('sa','',16);
$pdf->SetY(22);
$pdf->Cell(0, 8, iconv('utf-8', 'cp874', 'บริษัท น้ำตาลไทยเอกลักษณ์ จำกัด'), 0, 1, 'C');
$pdf->Cell(0, 8, iconv('utf-8', 'cp874', 'ใบสมัครเข้าร่วมโครงการสร้างผลผลิตอ้อยโดยการให้น้ำทุกชนิดอ้อย'), 0, 1, 'C');
$pdf->Cell(0, 8, iconv('utf-8', 'cp874', "เพื่อรับเงินช่วยเหลือค่าใช้จ่ายในการให้น้ำไร่ละ 200 บาท ปีการผลิต $selected_year"), 0, 1, 'C');

// 3. ทีมส่งเสริม (ใช้ emp_unit)
$pdf->SetFont('sa','',16);
$pdf->SetXY(15, 45);
$pdf->Cell(0, 10, iconv('utf-8', 'cp874', 'ทีมส่งเสริม ................................................'), 0, 0);
TextOnDot($pdf, 48, 45, $emp_unit); // พิมพ์หน่วยทับเส้น

// --- 4. วันที่ (ดึงค่าปัจจุบันอัตโนมัติ) ---
$curr_day   = date('d'); // วันที่ปัจจุบัน
$curr_month = array(
    "01"=>"มกราคม", "02"=>"กุมภาพันธ์", "03"=>"มีนาคม", "04"=>"เมษายน",
    "05"=>"พฤษภาคม", "06"=>"มิถุนายน", "07"=>"กรกฎาคม", "08"=>"สิงหาคม",
    "09"=>"กันยายน", "10"=>"ตุลาคม", "11"=>"พฤศจิกายน", "12"=>"ธันวาคม"
)[date('m')]; // เดือนปัจจุบันภาษาไทย
$curr_year  = date('Y') + 543; // ปี พ.ศ. ปัจจุบัน

$pdf->SetXY(80, 53);
$pdf->Cell(0, 10, iconv('utf-8', 'cp874', 'วันที่ ......... เดือน ............................ พ.ศ. ............'), 0, 0);

// พิมพ์ค่าปัจจุบันทับเส้นจุดไข่ปลา
TextOnDot($pdf, 91, 53, $curr_day);    // ใส่เลขวันที่
TextOnDot($pdf, 114, 53, $curr_month); // ใส่ชื่อเดือน
TextOnDot($pdf, 142, 53, $curr_year);  // ใส่ พ.ศ. ปัจจุบัน (เช่น 2569)

// 5. ข้อมูลข้าพเจ้า (พิมพ์ทับเส้นจุดไข่ปลา)
$pdf->SetXY(15, 63);
$pdf->Cell(0, 10, iconv('utf-8', 'cp874', 'ข้าพเจ้า .................................................... เลขสัญญา ............................ เลขบัตรประชาชน ...........................................'), 0, 0);
TextOnDot($pdf, 32, 63, $f_name);  // ชื่อชาวไร่
TextOnDot($pdf, 100, 63, $c_no);  // เลขสัญญา (แสดง 6 หลักแล้วจ้ะ)
TextOnDot($pdf, 155, 63, $cit_id); // บัตรประชาชน

$pdf->SetXY(15, 73);
$pdf->Cell(0, 10, iconv('utf-8', 'cp874', 'บ้านเลขที่ ............. หมู่ที่ ......... ตำบล ................................ อำเภอ ................................ จังหวัด ................................'), 0, 0);
TextOnDot($pdf, 35, 73, $addr); // บ้านเลขที่
TextOnDot($pdf, 65, 73, $moo);  // หมู่
TextOnDot($pdf, 88, 73, $tam);  // ตำบล
TextOnDot($pdf, 130, 73, $amp); // อำเภอ
TextOnDot($pdf, 172, 73, $prov); // จังหวัด

// --- เนื้อหาหลักเกณฑ์ ---
$pdf->SetFont('sa','',14);
$pdf->SetXY(15, 85);
$pdf->MultiCell(180, 7, iconv('utf-8', 'cp874', "        มีความประสงค์ขอเข้าร่วมโครงการเพิ่มผลผลิตต่อไร่ ปีการผลิต $selected_year โครงการสร้างผลผลิตอ้อยด้วยการให้น้ำอ้อยเพื่อรับเงินช่วยเหลือค่าใช้จ่ายในการให้น้ำไร่ละ 200 บาท ตามหลักเกณฑ์ที่กำหนด ดังนี้"), 0, 'L');

$pdf->SetFont('sa','',15);
$pdf->SetXY(15, 98);
$pdf->Cell(0, 10, iconv('utf-8', 'cp874', 'หลักเกณฑ์การขอรับสิ่งจูงใจในการให้น้ำอ้อย'), 0, 1);

$pdf->SetFont('sa','',14);
$pdf->SetXY(20, 108);
$rules = "1. ชาวไร่ต้องสมัครเข้าร่วมโครงการเพื่อรับสิ่งจูงใจในการให้น้ำอ้อยเป็นรายแปลง\n2. ชาวไร่ต้องดำเนินการให้น้ำทุกชนิดอ้อยอย่างน้อย 2 ครั้ง ขึ้นไป\n3. ให้นักส่งเสริมถ่ายภาพการให้น้ำอ้อยของชาวไร่โดยมีการระบุวันที่ พิกัดแปลงอ้อย ID แปลงอ้อย ชนิดอ้อย เลขสัญญา\n   ชื่อโควต้าชาวไร่ ทั้ง 2 ครั้ง\n4. ลงข้อมูลการให้น้ำอ้อยในฐานข้อมูลการให้น้ำตรงกับวันที่ชาวไร่ให้น้ำในแต่ละครั้ง\n5. เมื่อชาวไร่ดำเนินการให้น้ำแล้วเสร็จ ตั้งแต่ครั้งที่ 1 ให้นำข้อมูลส่งให้ทีมออดิท และคณะกรรมการน้ำเพื่อการผลิตอ้อย\n   ประจำหน่วย (ก.น.อ.) ตรวจรับรองความถูกต้องของข้อมูลการให้น้ำ ทุกครั้งที่มีการให้น้ำของชาวไร่\n6. การจ่ายเงินค่าใช้จ่ายในการให้น้ำอ้อยไร่ละ 200 บาท จ่ายหลังจากให้น้ำครบ 2 ครั้ง และมีการตรวจรับรองความถูกต้อง\n   เรียบร้อยแล้ว 1 เดือน\n7. เมื่อปิดหีบมีการเก็บข้อมูลผลผลิตอ้อยแปลงที่เข้าร่วมโครงการ";
$pdf->MultiCell(175, 6, iconv('utf-8', 'cp874', $rules), 0, 'L');

// --- ตารางแปลงอ้อย (ล็อคตามปีการผลิตที่เลือก) ---
$total_area = 0; $plots_data = [];
foreach ($plot_ids as $pid) {
    $p_id_safe = mysqli_real_escape_string($con, $pid);
    $res = mysqli_query($con, "SELECT * FROM image_water WHERE plot_id = '$p_id_safe' AND year_rai = '$year_escaped'");
    if($r = mysqli_fetch_assoc($res)) { 
        $plots_data[] = $r; 
        $total_area += (float)$r['area_rai']; 
    }
}

$pdf->SetFont('sa','',15);
$pdf->SetXY(35, 168);
$pdf->Cell(0, 10, iconv('utf-8', 'cp874', 'แปลงอ้อยที่เข้าร่วมโครงการ จำนวน ................. แปลง พื้นรวม ................. ไร่'), 0, 0);
TextOnDot($pdf, 92, 168, count($plots_data)); // จำนวนแปลง
TextOnDot($pdf, 128, 168, $total_area);       // พื้นที่รวม

$y_row = 176;
for ($i = 0; $i < 6; $i++) {
    $pdf->SetXY(15, $y_row);
    $pdf->Cell(0, 10, iconv('utf-8', 'cp874', "แปลงที่ ".($i+1)." ID ............................. ชนิดอ้อย ........................... พื้นที่ .................... ไร่"), 0, 0);
    if (isset($plots_data[$i])) {
        TextOnDot($pdf, 40, $y_row, $plots_data[$i]['plot_id']);
        TextOnDot($pdf, 75, $y_row, $plots_data[$i]['suga_type']);
        TextOnDot($pdf, 111, $y_row, $plots_data[$i]['area_rai']);
    }
    $y_row += 9;
}

// --- ลายเซ็น ---
$pdf->SetXY(3, 233);
$pdf->Cell(95, 7, iconv('utf-8', 'cp874', 'ลงชื่อ ......................................... ชาวไร่อ้อย'), 0, 0, 'C');
$pdf->SetXY(0, 239);
$pdf->Cell(95, 7, iconv('utf-8', 'cp874', '( ......................................... )'), 0, 0, 'C');

$pdf->SetXY(93, 233);
$pdf->Cell(95, 7, iconv('utf-8', 'cp874', 'ลงชื่อ ......................................... นักส่งเสริม'), 0, 1, 'C');
$pdf->SetXY(90, 239);
$pdf->Cell(95, 7, iconv('utf-8', 'cp874', '( ......................................... )'), 0, 1, 'C');

$pdf->SetXY(8, 248);
$pdf->Cell(95, 7, iconv('utf-8', 'cp874', 'ลงชื่อ ......................................... หัวหน้าทีมส่งเสริม'), 0, 0, 'C');
$pdf->SetXY(0, 254);
$pdf->Cell(95, 7, iconv('utf-8', 'cp874', '( ......................................... )'), 0, 0, 'C');

$pdf->SetXY(110, 248);
$pdf->Cell(95, 7, iconv('utf-8', 'cp874', 'ลงชื่อ ......................................... หัวหน้าเขต/ผู้จัดการสายปฏิบัติการ'), 0, 1, 'C');
$pdf->SetXY(90, 254);
$pdf->Cell(95, 7, iconv('utf-8', 'cp874', '( ......................................... )'), 0, 1, 'C');

$pdf->SetXY(0, 261);
$pdf->Cell(0, 7, iconv('utf-8', 'cp874', 'ลงชื่อ ......................................... ผู้อนุมัติโครงการ'), 0, 1, 'C');
$pdf->SetXY(45, 268);
$pdf->Cell(95, 7, iconv('utf-8', 'cp874', '( ......................................... )'), 0, 1, 'C');


// ==========================================
// ส่วนที่ 2: หน้าแนบรูปภาพ (แผ่นละ 3 แปลง x 2 รูป = 6 รูปต่อแผ่น)
// ==========================================

$chunks = array_chunk($plots_data, 3); 

foreach ($chunks as $page_idx => $batch_plots) {
    $pdf->AddPage('P');
    $pdf->SetFont('sa', '', 16);

    $start_num = ($page_idx * 3) + 1;
    $end_num = $start_num + count($batch_plots) - 1;
    
    $pdf->SetXY(0, 10);
    $pdf->Cell(0, 10, iconv('utf-8', 'cp874', 'เอกสารแนบรูปภาพการให้น้ำ แปลงที่ ' . $start_num . ' - ' . $end_num), 0, 1, 'C');
    
    $pdf->SetFontSize(14);
    // --- จุดที่แก้ไข: หัวกระดาษหน้าแนบรูปภาพให้แสดงเลขสัญญา 6 หลัก ---
    $pdf->Cell(0, 5, iconv('utf-8', 'cp874', 'เลขสัญญา: ' . $c_no), 0, 1, 'C');

    $box_w = 85;      
    $box_h = 55;      
    $start_x = 12;    
    $start_y = 35;    
    $gap_x = 15;      
    $gap_y = 22;      

    foreach ($batch_plots as $i => $plot) {
        $y_pos = $start_y + ($i * ($box_h + $gap_y));
        $p_id = $plot['plot_id'];

        $pdf->SetFont('sa', '', 14);
        $pdf->SetXY($start_x, $y_pos - 7);
        $pdf->Cell(0, 7, iconv('utf-8', 'cp874', "แปลงที่ " . ($start_num + $i) . " ID: " . $p_id), 0, 1, 'L');

        // --- รูปที่ 1 ---
        $pdf->Rect($start_x, $y_pos, $box_w, $box_h);
        $img1 = $plot['water_image1']; 
        
        if (!empty($img1) && file_exists($img1)) {
            $pdf->Image($img1, $start_x + 1, $y_pos + 1, $box_w - 2, $box_h - 2);
        } else {
            $pdf->SetFont('sa', '', 10);
            $pdf->SetXY($start_x, $y_pos + ($box_h / 2) - 5);
            $pdf->Cell($box_w, 10, iconv('utf-8', 'cp874', 'ไม่พบรูปครั้งที่ 1'), 0, 0, 'C');
        }

        // --- รูปที่ 2 ---
        $pdf->Rect($start_x + $box_w + $gap_x, $y_pos, $box_w, $box_h);
        $img2 = $plot['water_image2']; 
        
        if (!empty($img2) && file_exists($img2)) {
            $pdf->Image($img2, $start_x + $box_w + $gap_x + 1, $y_pos + 1, $box_w - 2, $box_h - 2);
        } else {
            $pdf->SetXY($start_x + $box_w + $gap_x, $y_pos + ($box_h / 2) - 5);
            $pdf->Cell($box_w, 10, iconv('utf-8', 'cp874', 'ไม่พบรูปครั้งที่ 2'), 0, 0, 'C');
        }
    }
}

// --- แก้ไข 5 บรรทัดสุดท้ายเป็นแบบนี้จ๊ะ ---
ob_end_clean();
$pdf->SetTitle(iconv('utf-8', 'cp874', 'SUGAR Givewater '. ($c_no ?? '')));
$filename = "ใบรับเงินช่วยเหลือให้น้ำอ้อยปี_" . $selected_year . "_สัญญา_" . ($c_no ?? 'ไม่มีเลข') . ".pdf";

$encoded_filename = rawurlencode($filename);
$pdf->Output('I', $encoded_filename);
?>