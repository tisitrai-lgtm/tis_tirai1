<?php
/**
 * post_create.php — รับ FormData จาก index.php แล้วบันทึกโพสต์ใหม่
 * รองรับ: problem_detail (1-3), รูปภาพ base64 (บีบแล้วจาก client)
 */
require_once 'config.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

// ── ตรวจสิทธิ์: เฉพาะ Admin ออฟฟิศกลาง ──
if (!isset($_SESSION['emp_id'])
    || $_SESSION['emp_level'] !== 'a'
    || $_SESSION['emp_unit']  !== 'ประจำออฟฟิตกลาง') {
    echo json_encode(['status' => 'error', 'message' => 'ไม่มีสิทธิ์สร้างโพสต์']);
    exit;
}

// ── รับค่าจากฟอร์ม ──
$emp_id        = $_SESSION['emp_id'];
$crop_year     = $_SESSION['crop_year'];
$target_unit   = trim($_POST['target_unit']   ?? '');
$truck_number  = trim($_POST['truck_number']  ?? '');
$post_text     = trim($_POST['post_text']     ?? '');

// ปัญหา 3 ช่อง
$problem_1 = trim($_POST['problem_1'] ?? '');
$problem_2 = trim($_POST['problem_2'] ?? '') ?: null;
$problem_3 = trim($_POST['problem_3'] ?? '') ?: null;

// ── Validate ──
if (!$target_unit || !$truck_number || !$problem_1) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลให้ครบ (หน่วย, ทะเบียน, ปัญหาที่ 1)']);
    exit;
}

// ── บันทึกรูปภาพ base64 ──────────────────────────────
/**
 * รับ base64 dataURL จาก client (บีบแล้ว 800px/75%)
 * แปลงเป็นไฟล์ .jpg บันทึกใน uploads/YYYY-MM-DD/
 * คืนค่า relative path หรือ '' ถ้าไม่มี
 */
function saveBase64Image(string $b64, string $prefix = ''): string {
    if (empty($b64)) return '';

    // ตัด data:image/...;base64, ออก
    if (strpos($b64, ',') !== false) {
        $b64 = explode(',', $b64, 2)[1];
    }

    $data = base64_decode($b64);
    if (!$data) return '';

    $dir = 'uploads/' . date('Y-m-d') . '/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $filename = $prefix . time() . '_' . rand(1000, 9999) . '.jpg';
    $path     = $dir . $filename;
    file_put_contents($path, $data);

    return $path;
}

$img1 = saveBase64Image($_POST['img_b64_1'] ?? '', 'p1_');
$img2 = saveBase64Image($_POST['img_b64_2'] ?? '', 'p2_') ?: null;
$img3 = saveBase64Image($_POST['img_b64_3'] ?? '', 'p3_') ?: null;

if (!$img1) {
    echo json_encode(['status' => 'error', 'message' => 'กรุณาแนบรูปภาพอย่างน้อย 1 รูป']);
    exit;
}

// ── INSERT ──
try {
    $sql = "INSERT INTO posts 
                (emp_id, target_unit, truck_number, post_text,
                 problem_detail, problem_detail_2, problem_detail_3,
                 post_image, post_image_2, post_image_3, crop_year)
            VALUES
                (:emp_id, :target_unit, :truck_number, :post_text,
                 :p1, :p2, :p3,
                 :img1, :img2, :img3, :crop_year)";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':emp_id'      => $emp_id,
        ':target_unit' => $target_unit,
        ':truck_number'=> $truck_number,
        ':post_text'   => $post_text,
        ':p1'          => $problem_1,
        ':p2'          => $problem_2,
        ':p3'          => $problem_3,
        ':img1'        => $img1,
        ':img2'        => $img2,
        ':img3'        => $img3,
        ':crop_year'   => $crop_year,
    ]);

    $new_post_id = $conn->lastInsertId();

    // ── แจ้งเตือน: Insert notification ให้พนักงานทุกคนในหน่วยนั้น ──
    $unit_short = $target_unit; // เช่น "111 บางขลัง"
    $prob_label = $problem_1 . ($problem_2 ? ' / ' . $problem_2 : '') . ($problem_3 ? ' / ' . $problem_3 : '');
    $noti_text  = "แจ้งรถสกปรก: {$truck_number} — {$prob_label}";

    $stmt_emp = $conn->prepare(
        "SELECT emp_id FROM employee WHERE emp_unit = :unit AND emp_level = 'u'"
    );
    $stmt_emp->execute([':unit' => $unit_short]);
    $target_emps = $stmt_emp->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($target_emps)) {
        $stmt_noti = $conn->prepare(
            "INSERT INTO notifications (post_id, emp_id, target_unit, noti_text)
             VALUES (:pid, :eid, :unit, :txt)"
        );
        foreach ($target_emps as $eid) {
            $stmt_noti->execute([
                ':pid'  => $new_post_id,
                ':eid'  => $eid,
                ':unit' => $unit_short,
                ':txt'  => $noti_text,
            ]);
        }
    }

    // ── System log ──
    $conn->prepare(
        "INSERT INTO system_logs (action_by, action_type, target_id, log_details)
         VALUES (:by, 'CREATE_POST', :tid, :det)"
    )->execute([
        ':by'  => $emp_id,
        ':tid' => $new_post_id,
        ':det' => "สร้างโพสต์ #{$new_post_id} หน่วย:{$target_unit} รถ:{$truck_number} ปัญหา:{$prob_label}",
    ]);

    echo json_encode([
        'status'  => 'success',
        'message' => "บันทึกโพสต์สำเร็จ และแจ้งเตือน " . count($target_emps) . " คนในหน่วย {$target_unit}",
        'post_id' => $new_post_id,
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'DB Error: ' . $e->getMessage()]);
}