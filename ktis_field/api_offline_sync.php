<?php
/**
 * api_offline_sync.php — รับข้อมูลผลตรวจเช็กรถตัดที่บันทึกไว้ในโหมดออฟไลน์ขึ้นเซิร์ฟเวอร์
 * KTIS SMART FIELD - ฝ่ายไร่
 */
require_once 'config.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);

if (!$data || empty($data['harvester_number'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Data Payload']);
    exit;
}

$emp_id           = $data['emp_id'] ?? ($_SESSION['emp_id'] ?? 'unknown');
$harvester_number = trim($data['harvester_number']);
$crop_year        = $data['crop_year'] ?? ($_SESSION['crop_year'] ?? '69/70');
$field_condition  = trim($data['field_condition'] ?? 'ปกติ');
$field_cond_etc   = trim($data['field_condition_etc'] ?? '');
$latitude         = !empty($data['latitude']) ? floatval($data['latitude']) : null;
$longitude        = !empty($data['longitude']) ? floatval($data['longitude']) : null;
$location_name    = trim($data['location_name'] ?? '');
$checked_at       = !empty($data['checked_at']) ? $data['checked_at'] : date('Y-m-d H:i:s');
$items            = $data['items'] ?? [];

// ฟังก์ชันแปลง Base64 เป็นไฟล์รูปภาพ
function saveBase64CheckImage(?string $b64, string $prefix = 'sync_'): ?string {
    if (empty($b64)) return null;

    if (strpos($b64, ',') !== false) {
        $b64 = explode(',', $b64, 2)[1];
    }

    $imgData = base64_decode($b64);
    if (!$imgData) return null;

    $date_folder = date('Y/m/d');
    $dir = __DIR__ . '/im_user_check/' . $date_folder . '/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $filename = $prefix . time() . '_' . mt_rand(1000, 9999) . '.jpg';
    $filepath = $dir . $filename;

    if (file_put_contents($filepath, $imgData)) {
        return 'im_user_check/' . $date_folder . '/' . $filename;
    }
    return null;
}

$img_harvester = saveBase64CheckImage($data['img_harvester_b64'] ?? null, 'hv_');
$img_field     = saveBase64CheckImage($data['img_field_b64'] ?? null, 'fd_');

try {
    $conn->beginTransaction();

    $stmt = $conn->prepare("
        INSERT INTO check_sessions 
            (emp_id, harvester_number, crop_year, field_condition, field_condition_etc, latitude, longitude, location_name, img_harvester, img_field, checked_at)
        VALUES 
            (:emp_id, :hn, :cy, :fc, :fce, :lat, :lng, :loc, :imh, :imf, :chk_at)
    ");
    $stmt->execute([
        ':emp_id' => $emp_id,
        ':hn'     => $harvester_number,
        ':cy'     => $crop_year,
        ':fc'     => $field_condition,
        ':fce'    => $field_cond_etc ?: null,
        ':lat'    => $latitude,
        ':lng'    => $longitude,
        ':loc'    => $location_name ?: null,
        ':imh'    => $img_harvester,
        ':imf'    => $img_field,
        ':chk_at' => $checked_at
    ]);

    $session_id = $conn->lastInsertId();

    if (!empty($items) && is_array($items)) {
        $stmt_r = $conn->prepare("INSERT INTO check_results (session_id, item_id, pass, note) VALUES (:sid, :iid, :pass, :note)");
        foreach ($items as $it) {
            $iid  = intval($it['item_id'] ?? 0);
            $pass = intval($it['pass'] ?? 1);
            $note = trim($it['note'] ?? '');
            if ($iid > 0) {
                $stmt_r->execute([
                    ':sid'  => $session_id,
                    ':iid'  => $iid,
                    ':pass' => $pass,
                    ':note' => (!$pass && $note) ? $note : null
                ]);
            }
        }
    }

    $conn->commit();

    echo json_encode([
        'status'     => 'success',
        'message'    => 'Synced successfully',
        'session_id' => $session_id
    ]);
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode([
        'status'  => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
