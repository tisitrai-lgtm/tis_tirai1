<?php
ob_start();
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);


if (!isset($_SESSION['emp_id'])) {
    die("กรุณาเข้าสู่ระบบก่อนครับ");
}

if (!class_exists('FPDF')) {
    include('cl_area/1/fpdf.php'); 
}

require 'dbconnect.php'; 

// --- ส่วนที่เพิ่ม: ดึงปีการผลิตจาก Session เพื่อล็อคข้อมูล ---
$selected_year = $_SESSION['selected_year'] ?? ''; 
$year_escaped = mysqli_real_escape_string($con, $selected_year);

// 1. จัดการรับค่าจาก POST (เพราะหน้าบ้านส่งเป็น Array plot_ids มา) ---
$plot_ids = isset($_POST['plot_ids']) ? $_POST['plot_ids'] : [];

if (empty($plot_ids)) {
    echo "<script>alert('กรุณาเลือกรายการแปลงก่อนครับ'); window.history.back();</script>";
    exit;
}

// --- 2. ดึงข้อมูลเพื่อบันทึก Log (ป้องกันคนเหลี่ยม) ---
$first_plot_id = mysqli_real_escape_string($con, $plot_ids[0]);
$sql_check = "SELECT contract_number FROM image_water WHERE plot_id = '$first_plot_id' AND year_rai = '$year_escaped' LIMIT 1";
$res_check = mysqli_query($con, $sql_check);
$row_log_data = mysqli_fetch_assoc($res_check);

if($row_log_data) {
    $log_emp_id = mysqli_real_escape_string($con, $_SESSION['emp_id']);
    $log_contract = mysqli_real_escape_string($con, $row_log_data['contract_number']);

    // ถ้ามีการส่ง print_round โต้งๆ มาจากฟอร์มให้ใช้เลย จะได้แม่นที่สุด
    $log_print_round = isset($_POST['print_round']) ? mysqli_real_escape_string($con, $_POST['print_round']) : '0';

    if ($log_print_round == '0') {
        // หาจาก plot_id ถ้าไม่ได้ส่งมา
        $sql_get_round = "SELECT print_round FROM print_history 
                          WHERE emp_id = '$log_emp_id' AND year_rai = '$year_escaped' AND plot_id = '$first_plot_id'
                          LIMIT 1";
        $res_round = mysqli_query($con, $sql_get_round);
        $row_round = mysqli_fetch_assoc($res_round);
        $log_print_round = $row_round['print_round'] ?? '0';
    }

    // บันทึกลงตาราง pdf_export_logs (ตัด quota ออกเพื่อกัน Error)
    $sql_log = "INSERT INTO pdf_export_logs (emp_id, contract_number, print_round, created_at) 
                VALUES ('$log_emp_id', '$log_contract', '$log_print_round', NOW())";
    
    mysqli_query($con, $sql_log); 
}

// --- 3. ดึงข้อมูล นสส. (ดึงหน่วย emp_unit) ---
$emp_id = mysqli_real_escape_string($con, $_SESSION['emp_id']);
$res_emp = mysqli_query($con, "SELECT emp_name, emp_unit FROM employee WHERE emp_id = '$emp_id'");
$row_emp = mysqli_fetch_assoc($res_emp);
$emp_unit = $row_emp['emp_unit'] ?? '................';

// --- 4. ดึงเลขลำดับ (นับตามรายการที่พิมพ์จริง โดยล็อคตามปีที่เลือก) ---
if (isset($_POST['print_seq'])) {
    $print_no = $_POST['print_seq']; // ใช้เลขลำดับจากตารางบนหน้าเว็บ
} else {
    // นับจำนวนรหัสรอบทั้งหมด (fallback สำหรับกรณีกดพิมพ์จากทางอื่น)
    $sql_count = "SELECT COUNT(DISTINCT print_round) as row_num 
                  FROM print_history 
                  WHERE emp_id = '$emp_id' AND year_rai = '$year_escaped'";
    $res_count = mysqli_query($con, $sql_count);
    $row_count = mysqli_fetch_assoc($res_count);
    $print_no = $row_count['row_num'] ?? '1';
}

// --- 5. ดึงข้อมูลของแปลงทั้งหมดที่เลือกไว้ล่วงหน้า (ต้องใช้ก่อน เพื่อหาค่าที่ซ้ำกันมากที่สุดของหัวเอกสาร) ---
$total_area = 0; $plots_data = [];
foreach ($plot_ids as $pid) {
    $p_id_safe = mysqli_real_escape_string($con, $pid);
    $res = mysqli_query($con, "SELECT * FROM image_water WHERE plot_id = '$p_id_safe' AND year_rai = '$year_escaped'");
    if($r = mysqli_fetch_assoc($res)) { 
        $plots_data[] = $r; 
        $total_area += (float)$r['area_rai']; 
    }
}

// ฟังก์ชันหาค่าที่ซ้ำกันมากที่สุด (mode) จากแปลงทั้งหมดที่เลือกมา สำหรับฟิลด์ที่ระบุ
// ใช้แทนการอิงข้อมูลจากแปลงแรกเพียงแปลงเดียว เผื่อบางแถวกรอกข้อมูลไม่ตรงกัน (ปกติแล้วโควต้าเดียวกันควรมีค่าตรงกันทุกแปลงอยู่แล้ว)
function MostCommonValue($rows, $field) {
    $counts = [];
    foreach ($rows as $row) {
        $val = trim((string)($row[$field] ?? ''));
        if ($val === '') continue;
        if (!isset($counts[$val])) $counts[$val] = 0;
        $counts[$val]++;
    }
    if (empty($counts)) return '';
    arsort($counts);
    return array_key_first($counts);
}

// --- 6. ดึงข้อมูลเจ้าของสัญญา/ที่อยู่ โดยเลือกค่าที่ซ้ำกันมากที่สุดในบรรดาแปลงที่เลือกทั้งหมด ---
// หมายเหตุ: ตาราง image_water ไม่มีคอลัมน์ farmer_name/address_no/moo/tambon/amphoe
// คอลัมน์ 'quota' คือชื่อชาวไร่ตัวจริง และที่อยู่ใช้ house_no / sub_district / district / province
// house_no บางแถวมีข้อมูล "หมู่" รวมอยู่ในค่าเดียวกัน (เช่น "68/15 หมู่ 9") เพราะไม่มีคอลัมน์ moo แยก
$f_name   = MostCommonValue($plots_data, 'quota');
$c_no_raw = MostCommonValue($plots_data, 'contract_number');
$c_no     = $c_no_raw !== '' ? str_pad($c_no_raw, 6, "0", STR_PAD_LEFT) : '';
$cit_id   = MostCommonValue($plots_data, 'citizen_id');
$addr     = MostCommonValue($plots_data, 'house_no');
$tam      = MostCommonValue($plots_data, 'sub_district');
$amp      = MostCommonValue($plots_data, 'district');
$prov     = MostCommonValue($plots_data, 'province');

$pdf = new FPDF();
$pdf->SetAutoPageBreak(false); // ปิด auto page break ของ FPDF เอง เพราะเราคุมการขึ้นหน้าใหม่เองทั้งหมดในโค้ดนี้แล้ว
$pdf->AddPage('P');
$pdf->AddFont('sa','','THSarabun.php');

// ฟังก์ชันสำหรับเขียนตัวหนังสือทับเส้นจุดไข่ปลา
// $w = ความกว้างพื้นที่เขียน (0 = ไม่จำกัด, เขียนชิดซ้ายแบบเดิม), $align = 'C' สำหรับจัดกึ่งกลาง
function TextOnDot($pdf, $x, $y, $text, $w = 0, $align = '') {
    $pdf->SetXY($x, $y - 0.8);
    $pdf->Cell($w, 10, iconv('utf-8', 'cp874', $text), 0, 0, $align);
}

// ฟังก์ชันวัดความกว้างข้อความ (ใช้ font/size ที่ตั้งไว้ล่าสุด)
function TextWidth($pdf, $text) {
    return $pdf->GetStringWidth(iconv('utf-8', 'cp874', $text));
}

// ฟังก์ชันวาด "label + จุดไข่ปลา + ค่า" แบบความกว้างช่องปรับตามความยาวค่าจริง
// ป้องกันตัวหนังสือซ้อนทับ label ถัดไปเวลาค่าจากฐานข้อมูลยาวเกินคาด (เช่น house_no ที่มี "หมู่" รวมอยู่ด้วย)
// คืนค่าตำแหน่ง x ถัดไปสำหรับวาง field ถัดไปบนบรรทัดเดียวกัน
function DottedField($pdf, $x, $y, $label, $value, $min_field_w = 20, $gap = 4) {
    $pdf->SetXY($x, $y);
    $label_w = TextWidth($pdf, $label);
    $pdf->Cell($label_w, 10, iconv('utf-8', 'cp874', $label), 0, 0);

    $x2 = $x + $label_w;

    // ความกว้างช่อง = มากกว่าระหว่าง (ความกว้างขั้นต่ำ) กับ (ความกว้างค่าจริง + เผื่อขอบ)
    $value_w = $value !== '' ? TextWidth($pdf, (string)$value) : 0;
    $field_w = max($min_field_w, $value_w + 4);

    // วาดจุดไข่ปลาให้เต็มความกว้างที่คำนวณได้
    $dot_w = TextWidth($pdf, '.');
    $dots_count = $dot_w > 0 ? max(3, (int) floor($field_w / $dot_w)) : 3;
    $dots = str_repeat('.', $dots_count);

    $pdf->SetXY($x2, $y);
    $pdf->Cell($field_w, 10, iconv('utf-8', 'cp874', $dots), 0, 0);

    if ($value !== '') {
        // จัดค่าให้อยู่กึ่งกลางพื้นที่ dots ของ field นี้ แทนการชิดซ้าย
        TextOnDot($pdf, $x2, $y, (string)$value, $field_w, 'C');
    }

    return $x2 + $field_w + $gap;
}

// --- เริ่มวาดเอกสาร ---
$pdf->SetFont('sa','',14);
$pdf->SetXY(150, 10);
$pdf->Cell(45, 10, iconv('utf-8', 'cp874', 'ลำดับที่ ........................'), 0, 0, 'R');
TextOnDot($pdf, 180, 10.2, $print_no);

$pdf->SetFont('sa','',14);
$pdf->SetY(22);
$pdf->Cell(0, 8, iconv('utf-8', 'cp874', 'บริษัท น้ำตาลไทยเอกลักษณ์ จำกัด'), 0, 1, 'C');
$pdf->Cell(0, 8, iconv('utf-8', 'cp874', 'ใบสมัครเข้าร่วมโครงการสร้างผลผลิตอ้อยโดยการให้น้ำทุกชนิดอ้อย'), 0, 1, 'C');
$pdf->Cell(0, 8, iconv('utf-8', 'cp874', "เพื่อรับเงินช่วยเหลือค่าใช้จ่ายในการให้น้ำไร่ละ 200 บาท ปีการผลิต $selected_year"), 0, 1, 'C');

$pdf->SetFont('sa','',14);
$pdf->SetXY(15, 45);
$pdf->Cell(0, 10, iconv('utf-8', 'cp874', 'ทีมส่งเสริม ................................................'), 0, 0);
TextOnDot($pdf, 48, 45, $emp_unit);

$curr_day   = date('d');
$curr_month = array(
    "01"=>"มกราคม", "02"=>"กุมภาพันธ์", "03"=>"มีนาคม", "04"=>"เมษายน",
    "05"=>"พฤษภาคม", "06"=>"มิถุนายน", "07"=>"กรกฎาคม", "08"=>"สิงหาคม",
    "09"=>"กันยายน", "10"=>"ตุลาคม", "11"=>"พฤศจิกายน", "12"=>"ธันวาคม"
)[date('m')];
$curr_year  = date('Y') + 543;

$x = 80; $y = 53;
$x = DottedField($pdf, $x, $y, 'วันที่ ', $curr_day, 15, 4);
$x = DottedField($pdf, $x, $y, 'เดือน ', $curr_month, 35, 4);
$x = DottedField($pdf, $x, $y, 'พ.ศ. ', $curr_year, 20, 4);

// --- บรรทัด "ข้าพเจ้า / เลขสัญญา / เลขบัตรประชาชน" ---
// เดิมใช้ตำแหน่ง x ตายตัว ถ้าชื่อชาวไร่ยาวจะไปทับ "เลขสัญญา" ได้ เปลี่ยนมาใช้ DottedField คำนวณความกว้างช่องจากค่าจริงแทน
$pdf->SetFont('sa','',14);
$x = 15; $y = 63;
$x = DottedField($pdf, $x, $y, 'ข้าพเจ้า ', $f_name, 55, 4);
$x = DottedField($pdf, $x, $y, 'เลขสัญญา ', $c_no, 22, 4);
$x = DottedField($pdf, $x, $y, 'เลขบัตรประชาชน ', $cit_id, 35, 4);

// --- บรรทัดที่อยู่: บ้านเลขที่ / ตำบล / อำเภอ / จังหวัด (ตัด "หมู่ที่" ออกแล้ว เพราะไม่มีคอลัมน์นี้ในฐานข้อมูล) ---
// ใช้ DottedField เช่นกัน เพราะ house_no บางแถวมีคำว่า "หมู่ x" รวมอยู่ในค่าเดียว ทำให้ยาวกว่าที่คาดไว้
$x = 15; $y = 73;
$x = DottedField($pdf, $x, $y, 'บ้านเลขที่ ', $addr, 25, 4);
$x = DottedField($pdf, $x, $y, 'ตำบล ', $tam, 30, 4);
$x = DottedField($pdf, $x, $y, 'อำเภอ ', $amp, 30, 4);
$x = DottedField($pdf, $x, $y, 'จังหวัด ', $prov, 30, 4);

$pdf->SetFont('sa','',14);
$pdf->SetXY(15, 85);
$pdf->MultiCell(180, 7, iconv('utf-8', 'cp874', "         มีความประสงค์ขอเข้าร่วมโครงการเพิ่มผลผลิตต่อไร่ ปีการผลิต $selected_year โครงการสร้างผลผลิตอ้อยด้วยการให้น้ำอ้อยเพื่อรับเงินช่วยเหลือค่าใช้จ่ายในการให้น้ำไร่ละ 200 บาท ตามหลักเกณฑ์ที่กำหนด ดังนี้"), 0, 'L');

$pdf->SetFont('sa','',14);
$pdf->SetXY(15, 98);
$pdf->Cell(0, 10, iconv('utf-8', 'cp874', 'หลักเกณฑ์การขอรับสิ่งจูงใจในการให้น้ำอ้อย'), 0, 1);

$pdf->SetFont('sa','',14);
$pdf->SetXY(20, 108);
$rules = "1. ชาวไร่ต้องสมัครเข้าร่วมโครงการเพื่อรับสิ่งจูงใจในการให้น้ำอ้อยเป็นรายแปลง\n2. ชาวไร่ต้องดำเนินการให้น้ำทุกชนิดอ้อยอย่างน้อย 1 ครั้ง ขึ้นไป\n3. ให้นักส่งเสริมถ่ายภาพการให้น้ำอ้อยของชาวไร่โดยมีการระบุวันที่ พิกัดแปลงอ้อย ID แปลงอ้อย ชนิดอ้อย เลขสัญญา\n   ชื่อโควต้าชาวไร่ ทั้ง 2 ครั้ง\n4. ลงข้อมูลการให้น้ำอ้อยในฐานข้อมูลการให้น้ำตรงกับวันที่ชาวไร่ให้น้ำในแต่ละครั้ง\n5. เมื่อชาวไร่ดำเนินการให้น้ำแล้วเสร็จ ตั้งแต่ครั้งที่ 1 ให้นำข้อมูลส่งให้ทีมออดิท และคณะกรรมการน้ำเพื่อการผลิตอ้อย\n   ประจำหน่วย (ก.น.อ.) ตรวจรับรองความถูกต้องของข้อมูลการให้น้ำ ทุกครั้งที่มีการให้น้ำของชาวไร่\n6. การจ่ายเงินค่าใช้จ่ายในการให้น้ำอ้อยไร่ละ 200 บาท จ่ายหลังจากให้น้ำครบ 2 ครั้ง และมีการตรวจรับรองความถูกต้อง\n   เรียบร้อยแล้ว 1 เดือน\n7. เมื่อปิดหีบมีการเก็บข้อมูลผลผลิตอ้อยแปลงที่เข้าร่วมโครงการ";
$pdf->MultiCell(175, 6, iconv('utf-8', 'cp874', $rules), 0, 'L');

// --- ส่วนรายการแปลงอ้อย ---
$pdf->SetFont('sa','',14);
$pdf->SetXY(35, 168);
// ใส่ (int) ครอบ $total_area
$pdf->Cell(0, 10, iconv('utf-8', 'cp874', "แปลงอ้อยที่เข้าร่วมโครงการ จำนวน ".count($plots_data)." แปลง พื้นรวม ".(int)$total_area." ไร่"), 0, 1);
$pdf->SetFont('sa','',14);
foreach ($plots_data as $index => $plot) {
    // คำนวณล่วงหน้าว่ารอบการให้น้ำของแปลงนี้มีข้อมูลจริงกี่บรรทัด (มีผลต่อความสูงที่ต้องใช้)
    $rounds_parts_check = [];
    for ($wi = 1; $wi <= 3; $wi++) {
        $method_check = trim((string)($plot['water_method' . $wi] ?? ''));
        if ($method_check !== '') $rounds_parts_check[] = $method_check;
    }
    $has_rounds_line = !empty($rounds_parts_check);

    // ความสูงที่แปลงนี้ทั้งก้อนจะใช้จริง: หัวแปลง(7) + แหล่งน้ำ(6) + รอบให้น้ำถ้ามี(6) + เว้นระยะท้าย(2)
    $entry_height = 7 + 6 + ($has_rounds_line ? 6 : 0) + 2;

    // เช็คจากความสูงจริงที่ต้องใช้ ไม่ใช่แค่ตำแหน่งปัจจุบันเฉยๆ กันไม่ให้ FPDF auto-break แทรกกลางแปลง
    if ($pdf->GetY() + $entry_height > 282) { 
        $pdf->AddPage(); 
        $pdf->SetY(10);
    }

    $y_start = $pdf->GetY();
    $pdf->SetX(15);
    $pdf->Cell(18, 7, iconv('utf-8', 'cp874', "แปลงที่ " . ($index + 1)), 0, 0);
    $pdf->Cell(35, 7, iconv('utf-8', 'cp874', "ID: " . $plot['plot_id']), 0, 0);
    $pdf->Cell(45, 7, iconv('utf-8', 'cp874', "ชนิด: " . $plot['suga_type']), 0, 0);
    $pdf->Cell(0,  7, iconv('utf-8', 'cp874', "พื้นที่: " . (int)$plot['area_rai'] . " ไร่"), 0, 1);

    // วาดกล่องเช็ค (กล่องขาว/กล่องดำ)
    $status = $plot['join_status'] ?? 'join';
    $check_x = 145;
    $box_size = 4;

    // กล่องเข้าร่วม
    $pdf->Rect($check_x, $y_start + 1.5, $box_size, $box_size, $status === 'join' ? 'F' : 'D'); // 'F' คือเติมสีดำ, 'D' คือวาดขอบ
    $pdf->SetXY($check_x + 6, $y_start + 1);
    $pdf->Cell(20, 5, iconv('utf-8', 'cp874', 'เข้าร่วม'), 0, 0);

    // กล่องไม่เข้าร่วม
    $pdf->Rect($check_x + 25, $y_start + 1.5, $box_size, $box_size, $status === 'notjoin' ? 'F' : 'D');
    $pdf->SetXY($check_x + 31, $y_start + 1);
    $pdf->Cell(25, 5, iconv('utf-8', 'cp874', 'ไม่เข้าร่วม'), 0, 1);

    // --- บรรทัดที่ 2: แหล่งน้ำ (แยกบรรทัดของตัวเอง เพราะชื่อแหล่งน้ำอาจยาวกว่านี้มาก ให้มีพื้นที่เต็มบรรทัด) ---
    $water_source_val = trim((string)($plot['water_source'] ?? ''));
    $line1_y = $y_start + 6.5;
    $pdf->SetFont('sa', '', 14);
    $pdf->SetXY(18, $line1_y);
    $pdf->Cell(0, 6, iconv('utf-8', 'cp874', 'แหล่งน้ำ: ' . ($water_source_val !== '' ? $water_source_val : '-')), 0, 0);

    // --- บรรทัดที่ 3: การให้น้ำแต่ละครั้ง (นับเฉพาะครั้งที่มีข้อมูล "วิธีให้น้ำ" ถ้ามีแค่วันที่ไม่มีวิธี ไม่นับ) ---
    $rounds_parts = [];
    for ($wi = 1; $wi <= 3; $wi++) {
        $method = trim((string)($plot['water_method' . $wi] ?? ''));
        $date   = trim((string)($plot['water_date' . $wi] ?? ''));
        if ($method === '') continue;
        $part = "ให้น้ำครั้งที่ $wi: $method";
        if ($date !== '') {
            $part .= " (" . $date . ")";
        }
        $rounds_parts[] = $part;
    }
    $rounds_line = implode('   ', $rounds_parts);

    // ➕ เว้นพื้นที่บรรทัดนี้เฉพาะตอนมีข้อมูลจริงเท่านั้น ถ้าไม่มีข้อมูลรอบให้น้ำเลย ไม่ต้องเผื่อที่ว่างไว้ให้แถวถัดไปมาชิดขึ้น
    $next_y = $line1_y + 6;
    if ($rounds_line !== '') {
        $pdf->SetXY(18, $next_y);
        $pdf->Cell(0, 6, iconv('utf-8', 'cp874', $rounds_line), 0, 0);
        $next_y += 6;
    }

    $pdf->SetY($next_y + 2); // เผื่อระยะห่างเล็กน้อยก่อนแปลงถัดไป (กันดูอัดแน่นเกินไป)
}

// --- ส่วนลายเซ็น: ต่อท้ายแปลงสุดท้ายทันที ไม่ตรึงตำแหน่งตายตัวอีกต่อไป ---
// คำนวณความสูงที่บล็อกลายเซ็นทั้งก้อนต้องใช้ (หัวข้อ Ln + 3 แถวๆ ละ 2 บรรทัด + ระยะเว้นระหว่างแถว)
// --- ส่วนลายเซ็น: ต่อท้ายแปลงสุดท้ายทันที ไม่ตรึงตำแหน่งตายตัวอีกต่อไป ---
$signature_block_height = 10 + (7 * 2) + 5 + (7 * 2) + 5 + (7 * 2); 
$page_bottom_limit = 282; 

if ($pdf->GetY() + $signature_block_height > $page_bottom_limit) {
    $pdf->AddPage();
    $pdf->SetY(20);
} else {
    $pdf->Ln(8);
}

$pdf->SetFont('sa', '', 14);
$pdf->Ln(10); // เว้นระยะห่าง

// แถวที่ 1: ชาวไร่ + นักส่งเสริม
$pdf->SetX(3);
$pdf->Cell(95, 7, iconv('utf-8', 'cp874', 'ลงชื่อ ........................................... ชาวไร่อ้อย'), 0, 0, 'C');
$pdf->Cell(97, 7, iconv('utf-8', 'cp874', 'ลงชื่อ ........................................... นักส่งเสริม'), 0, 1, 'C');

$pdf->SetX(3);
$pdf->Cell(95, 7, iconv('utf-8', 'cp874', '( ........................................... )'), 0, 0, 'C');
$pdf->Cell(95, 7, iconv('utf-8', 'cp874', '( ........................................... )'), 0, 1, 'C');

$pdf->Ln(5); // เว้นระยะห่าง

// แถวที่ 2: หัวหน้าทีมส่งเสริม + หัวหน้าเขต
$pdf->SetX(3);
$pdf->Cell(95, 7, iconv('utf-8', 'cp874', 'ลงชื่อ ........................................... หัวหน้าทีมส่งเสริม'), 0, 0, 'C');
$pdf->Cell(100, 7, iconv('utf-8', 'cp874',    'ลงชื่อ ......................................... หัวหน้าเขต/ผู้จัดการสาย'), 0, 1, 'C');

$pdf->SetX(3);
$pdf->Cell(95, 7, iconv('utf-8', 'cp874', '( ........................................... )'), 0, 0, 'C');
$pdf->Cell(95, 7, iconv('utf-8', 'cp874', '( ........................................... )'), 0, 1, 'C');

$pdf->Ln(5); // เว้นระยะห่าง

// แถวที่ 3: ทีม Audit (ซ้าย) + ผู้อนุมัติโครงการ (ขวา) ให้อยู่แถวเดียวกัน
$pdf->SetX(3);
$pdf->Cell(95, 7, iconv('utf-8', 'cp874', 'ลงชื่อ ......................................... ทีม Audit'), 0, 0, 'C');
$pdf->Cell(95, 7, iconv('utf-8', 'cp874', 'ลงชื่อ ......................................... ผู้อนุมัติโครงการ'), 0, 1, 'C');

$pdf->SetX(3);
$pdf->Cell(95, 7, iconv('utf-8', 'cp874', '( ........................................... )'), 0, 0, 'C');
$pdf->Cell(95, 7, iconv('utf-8', 'cp874', '( ........................................... )'), 0, 1, 'C');


// --- หน้าแนบรูปภาพ ---
$chunks = array_chunk($plots_data, 4); 
foreach ($chunks as $page_idx => $batch_plots) {
    $pdf->AddPage('P');
    $pdf->SetFont('sa', '', 16);
    $start_num = ($page_idx * 4) + 1;
    $end_num = $start_num + count($batch_plots) - 1;
    $pdf->SetXY(0, 10);
    $pdf->Cell(0, 10, iconv('utf-8', 'cp874', 'เอกสารแนบรูปภาพการให้น้ำ แปลงที่ ' . $start_num . ' - ' . $end_num), 0, 1, 'C');
    $pdf->SetFontSize(14);
    $pdf->Cell(0, 5, iconv('utf-8', 'cp874', 'เลขสัญญา: ' . $c_no), 0, 1, 'C');

    // ลดขนาดกล่อง/ระยะห่างจากเดิม (3 แปลง/หน้า) เพื่อให้ 4 แปลงลงหน้าเดียวพอดี
    $box_w = 85; $box_h = 51; $start_x = 12; $start_y = 33; $gap_x = 15; $gap_y = 12;
    foreach ($batch_plots as $i => $plot) {
        $y_pos = $start_y + ($i * ($box_h + $gap_y));
        $pdf->SetXY($start_x, $y_pos - 7);
        $pdf->Cell(0, 7, iconv('utf-8', 'cp874', "แปลงที่ " . ($start_num + $i) . " ID: " . $plot['plot_id']), 0, 1, 'L');
        
        $empId_s = htmlspecialchars(basename($plot['emp_id']));
        $contract_s = htmlspecialchars(basename($plot['contract_number']));
        $plotId_s = htmlspecialchars(basename($plot['plot_id']));
        $base_path = "images/water/{$empId_s}/{$contract_s}/{$plotId_s}/";

        $pdf->Rect($start_x, $y_pos, $box_w, $box_h);
        if (!empty($plot['water_image1'])) {
            $full_img1 = $base_path . htmlspecialchars(basename($plot['water_image1']));
            if(file_exists($full_img1)) {
                $pdf->Image($full_img1, $start_x + 1, $y_pos + 1, $box_w - 2, $box_h - 2);
            }
        }

        $pdf->Rect($start_x + $box_w + $gap_x, $y_pos, $box_w, $box_h);
        if (!empty($plot['water_image2'])) {
            $full_img2 = $base_path . htmlspecialchars(basename($plot['water_image2']));
            if(file_exists($full_img2)) {
                $pdf->Image($full_img2, $start_x + $box_w + $gap_x + 1, $y_pos + 1, $box_w - 2, $box_h - 2);
            }
        }
    }
}

ob_end_clean();
$pdf->SetTitle(iconv('utf-8', 'cp874', 'SUGAR Givewater '. ($c_no ?? '')));
$filename = "ใบช่วยเหลือให้น้ำ_" . $selected_year . "_สัญญา_" . ($c_no ?? '000000') . ".pdf";
$pdf->Output('I', rawurlencode($filename));
?>