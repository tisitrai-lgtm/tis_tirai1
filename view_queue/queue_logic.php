<?php
// queue_logic.php
include 'db_connect.php'; 
date_default_timezone_set('Asia/Bangkok');
$max_queue_limit = 850;
$message = ''; 
$message_class = ''; 
$edit_entry_id = null; 
$edit_data = []; 

$thai_now = date('Y-m-d H:i:s'); 
$plate_pattern = "/^([ก-ฮ]{2}\.[0-9]{2}-[0-9]{4}|[0-9]{1,4})$/u";

// --- Pagination & Limit ---
$limit_options = [25, 50, 100, 250, 'all'];
$limit_val = $_GET['limit'] ?? 25; 

if ($limit_val === 'all') {
    $limit = 999999; 
} else {
    $limit = (int)$limit_val;
    if (!in_array($limit, [25, 50, 100, 250])) { $limit = 25; }
}

$current_page = (int)($_GET['page'] ?? 1); 
$offset = ($current_page - 1) * $limit; 
$search_term = $_GET['search'] ?? ''; 
$search_term = trim($conn->real_escape_string($search_term)); 

// --- Functions ---
function getActiveRoundInfo($conn) {
    // ดึงรอบล่าสุดที่ยังเป็น 1 (Active)
    $sql = "SELECT r.round_id, r.round_number, COUNT(q.entry_id) AS current_count 
            FROM rounds r 
            LEFT JOIN queue_entries q ON r.round_id = q.round_id
            WHERE r.is_active = 1 
            GROUP BY r.round_id, r.round_number
            ORDER BY r.round_number DESC LIMIT 1";
    $result = $conn->query($sql);
    return ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
}

$current_round_info = getActiveRoundInfo($conn);

// --- CRUD Operations ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        
        // [แก้ไขจุดนี้] สร้างรอบใหม่ได้ทันที ไม่เช็คว่ามีรอบ Active อยู่หรือไม่
        if ($_POST['action'] === 'start_new_round') {
            $last_round_result = $conn->query("SELECT MAX(round_number) AS max_num FROM rounds");
            $last_round = $last_round_result->fetch_assoc();
            $new_round_number = ($last_round['max_num'] ?? 0) + 1;
            
            $sql_insert_round = "INSERT INTO rounds (round_number, start_time, max_queue, is_active) 
                                 VALUES ($new_round_number, '$thai_now', $max_queue_limit, 1)";
            
            if ($conn->query($sql_insert_round) === TRUE) {
                $message = '✨ สร้างรอบใหม่สำเร็จ! เริ่มใช้งานรอบที่ ' . $new_round_number;
                $message_class = 'alert-success';
                // บังคับให้หน้าเว็บเปลี่ยนไปดูรอบที่เพิ่งสร้างใหม่
                header("Location: add_queue.php?view_round=$new_round_number&message=" . urlencode($message));
                exit;
            }
        } 
        
        // ลบคิว
        elseif ($_POST['action'] === 'delete_entry' && isset($_POST['entry_id'])) {
            $entry_id = (int)$_POST['entry_id'];
            $conn->query("DELETE FROM queue_entries WHERE entry_id = $entry_id");
            $message = '🗑️ ลบคิวสำเร็จ!';
            $message_class = 'alert-warning';
        }

        // อัปเดตคิว (แก้ไขข้อมูล)
        elseif ($_POST['action'] === 'update_entry' && isset($_POST['entry_id'])) {
            $entry_id = (int)$_POST['entry_id'];
            $u_q_num = trim($_POST['manual_queue_number']);
            $u_tractor = strtoupper(trim($_POST['tractor_plate']));
            $u_trailer = strtoupper(trim($_POST['trailer_plate']));

            if (!preg_match($plate_pattern, $u_tractor)) {
                $message = "❌ รูปแบบทะเบียนไม่ถูกต้อง";
                $message_class = "alert-error";
            } else {
                $trailer_val = empty($u_trailer) ? "NULL" : "'$u_trailer'";
                $sql_update = "UPDATE queue_entries SET queue_number='$u_q_num', tractor_plate='$u_tractor', trailer_plate=$trailer_val WHERE entry_id=$entry_id";
                if ($conn->query($sql_update)) {
                    $message = '✏️ แก้ไขคิวสำเร็จ!';
                    $message_class = 'alert-success';
                }
            }
        }
    } 
    
    // บันทึกคิวใหม่ (Normal Insert)
    elseif (isset($_POST['tractor_plate']) && !isset($_POST['entry_id'])) {
        $tractor_plate = strtoupper(trim($_POST['tractor_plate']));
        $trailer_plate = strtoupper(trim($_POST['trailer_plate']));
        $manual_q = trim($_POST['manual_queue_number']);
        $target_round_num = (int)$_POST['current_round_number'];
        
        $res_round = $conn->query("SELECT round_id FROM rounds WHERE round_number = $target_round_num");
        $round_data = $res_round->fetch_assoc();

        if (!$round_data) {
            $message = '❌ ไม่พบข้อมูลรอบที่ระบุ!';
            $message_class = 'alert-error';
        } else {
            $current_round_id = $round_data['round_id'];
            // เช็คคิวซ้ำเฉพาะในรอบเดียวกัน
            $check = $conn->query("SELECT entry_id FROM queue_entries WHERE round_id=$current_round_id AND queue_number='$manual_q'");
            if ($check->num_rows > 0) {
                $message = "❌ หมายเลขคิว $manual_q ถูกใช้ไปแล้วในรอบที่ $target_round_num";
                $message_class = 'alert-error';
            } else {
                $trailer_val = empty($trailer_plate) ? "NULL" : "'$trailer_plate'";
                $sql_ins = "INSERT INTO queue_entries (round_id, tractor_plate, trailer_plate, queue_number, created_at) 
                            VALUES ($current_round_id, '$tractor_plate', $trailer_val, '$manual_q', '$thai_now')";
                if ($conn->query($sql_ins)) {
                    $message = "✅ บันทึกคิว $manual_q เข้ารอบที่ $target_round_num สำเร็จ";
                    $message_class = 'alert-success';
                }
            }
        }
    }
}

// --- เตรียมข้อมูลแสดงผล ---
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['entry_id'])) {
    $edit_entry_id = (int)$_GET['entry_id'];
    $result_edit = $conn->query("SELECT * FROM queue_entries WHERE entry_id = $edit_entry_id");
    $edit_data = $result_edit->fetch_assoc();
}

// ดึงข้อความจาก Redirect (ถ้ามี)
if (isset($_GET['message'])) {
    $message = $_GET['message'];
    $message_class = 'alert-success';
}

$view_round_num = $_GET['view_round'] ?? ($current_round_info['round_number'] ?? 0);

// ถ้าไม่มีรอบเลย ให้พยายามหารอบล่าสุดจาก DB
if ($view_round_num == 0) {
    $res_latest = $conn->query("SELECT round_number FROM rounds ORDER BY round_number DESC LIMIT 1");
    if ($row_latest = $res_latest->fetch_assoc()) {
        $view_round_num = $row_latest['round_number'];
    }
}

$res_v = $conn->query("SELECT round_id FROM rounds WHERE round_number = " . (int)$view_round_num);
$v_data = $res_v->fetch_assoc();

$current_queue_entries = [];
$total_records = 0;
$total_pages = 1;

if ($v_data) {
    $current_round_id = $v_data['round_id'];
    $where_clause = "WHERE round_id = $current_round_id";
    if (!empty($search_term)) {
        $where_clause .= " AND (queue_number LIKE '%$search_term%' OR tractor_plate LIKE '%$search_term%')";
    }
    
    $res_count = $conn->query("SELECT COUNT(*) AS total FROM queue_entries $where_clause");
    $total_records = $res_count->fetch_assoc()['total'] ?? 0;
    $total_pages = ($limit_val === 'all') ? 1 : ceil($total_records / $limit);

    $sql_entries = "SELECT * FROM queue_entries $where_clause ORDER BY created_at DESC, entry_id DESC";
    if ($limit_val !== 'all') { $sql_entries .= " LIMIT $limit OFFSET $offset"; }
    
    $res_entries = $conn->query($sql_entries);
    while($res_entries && $row = $res_entries->fetch_assoc()) { $current_queue_entries[] = $row; }
}

function buildQueryString($params) {
    $query = $_GET;
    foreach ($params as $key => $value) {
        if ($value === null) unset($query[$key]);
        else $query[$key] = $value;
    }
    return http_build_query($query);
}

function getUniquePlates($conn) {
    $sql = "SELECT DISTINCT plate FROM (
                SELECT tractor_plate AS plate FROM queue_entries
                UNION 
                SELECT trailer_plate AS plate FROM queue_entries WHERE trailer_plate IS NOT NULL
            ) AS combined_plates WHERE plate != '' ORDER BY plate ASC";
    $result = $conn->query($sql);
    $plates = [];
    while ($result && $row = $result->fetch_assoc()) { $plates[] = $row['plate']; }
    return $plates;
}
$existing_plates = getUniquePlates($conn);
?>