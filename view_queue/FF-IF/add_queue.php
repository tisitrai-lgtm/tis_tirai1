<?php
// ... (ส่วน PHP Logic ทั้งหมด: includes, ตัวแปร, ฟังก์ชัน, POST/GET Logic เหมือนเดิม) ...
include 'db_connect.php'; 

$max_queue_limit = 750;
$message = ''; 
$message_class = ''; // ตัวแปรสำหรับคลาส CSS ของข้อความแจ้งเตือน
$edit_entry_id = null; 
$edit_data = []; 

// ตัวแปรสำหรับ Pagination
$limit_options = [10, 20, 50, 100];
$limit = (int)($_GET['limit'] ?? $limit_options[0]); 
if (!in_array($limit, $limit_options)) {
    $limit = $limit_options[0];
}

$current_page = (int)($_GET['page'] ?? 1); 
$offset = ($current_page - 1) * $limit; 

// ดึงคำค้นหาจาก URL (GET request)
$search_term = $_GET['search'] ?? ''; 
$search_term = trim($conn->real_escape_string($search_term)); 

// ... (ฟังก์ชัน getActiveRoundInfo(), getAllEndedRounds() เหมือนเดิม) ...
function getActiveRoundInfo($conn) {
    $sql = "SELECT r.round_id, r.round_number, COUNT(q.entry_id) AS current_count 
            FROM rounds r 
            LEFT JOIN queue_entries q ON r.round_id = q.round_id
            WHERE r.is_active = 1 
            GROUP BY r.round_id, r.round_number";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null; 
}

function getAllEndedRounds($conn) {
    // เรียงตาม round_number DESC (เลขรอบสูงสุดไปน้อยสุด)
    $sql = "SELECT round_number FROM rounds WHERE is_active = 0 ORDER BY round_number DESC"; 
    $result = $conn->query($sql);
    $rounds = [];
    while ($row = $result->fetch_assoc()) {
        $rounds[] = $row['round_number'];
    }
    return $rounds;
}

// * ส่วนที่ถูกปรับปรุง: Logic POST/CRUD Operations เหมือนเดิม *
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Logic การจัดการรอบ (Start/End/Undo)
        if ($_POST['action'] === 'start_new_round') {
            $current_info = getActiveRoundInfo($conn);
            if ($current_info) {
                 $message = '❌ ผิดพลาด! ยังมีรอบที่ ' . $current_info['round_number'] . ' กำลังทำงานอยู่.';
                 $message_class = 'alert-error';
            } else {
                $last_round_result = $conn->query("SELECT MAX(round_number) AS max_num FROM rounds");
                $last_round = $last_round_result->fetch_assoc();
                $new_round_number = ($last_round['max_num'] ?? 0) + 1;
                $sql_insert_round = "INSERT INTO rounds (round_number, start_time, max_queue, is_active) 
                                     VALUES ($new_round_number, NOW(), $max_queue_limit, 1)";
                if ($conn->query($sql_insert_round) === TRUE) {
                    $message = '✅ เริ่มรอบใหม่สำเร็จ! รอบที่ ' . $new_round_number . ' ถูกเปิดใช้งานแล้ว.';
                    $message_class = 'alert-success';
                } else {
                    $message = '❌ เกิดข้อผิดพลาดในการเริ่มรอบ: ' . $conn->error;
                    $message_class = 'alert-error';
                }
            }
        } elseif ($_POST['action'] === 'end_current_round') {
            $current_round_info = getActiveRoundInfo($conn);
            if ($current_round_info) {
                $round_id = $current_round_info['round_id'];
                $round_number = $current_round_info['round_number'];
                $conn->query("UPDATE rounds SET is_active = 0, end_time = NOW() WHERE round_id = $round_id");
                $message = '🛑 จบรอบสำเร็จ! รอบที่ ' . $round_number . ' ถูกปิดแล้ว.';
                $message_class = 'alert-warning';
            } else {
                $message = '❌ ไม่มีรอบที่กำลังทำงานอยู่ให้ปิด.';
                $message_class = 'alert-error';
            }
        } elseif ($_POST['action'] === 'undo_end_round_manual') {
            $round_to_revert = (int)($_POST['round_number_to_undo'] ?? 0); 
            if ($round_to_revert <= 0) {
                $message = '❌ ผิดพลาด! กรุณาเลือกรอบที่ต้องการย้อนกลับ.';
                $message_class = 'alert-error';
            } else {
                $current_info = getActiveRoundInfo($conn);
                if ($current_info) {
                    $message = '❌ ไม่สามารถย้อนกลับได้! กรุณาจบรอบที่ ' . $current_info['round_number'] . ' ก่อน.';
                    $message_class = 'alert-error';
                } else {
                    $sql_check = "SELECT round_id FROM rounds 
                                    WHERE round_number = $round_to_revert AND is_active = 0";
                    $result_check = $conn->query($sql_check);
                    if ($result_check->num_rows > 0) {
                        $row = $result_check->fetch_assoc();
                        $round_id = $row['round_id'];
                        $conn->query("UPDATE rounds SET is_active = 1, end_time = NULL WHERE round_id = $round_id");
                        $message = '↩️ ย้อนกลับสำเร็จ! รอบที่ ' . $round_to_revert . ' กลับมาทำงานแล้ว.';
                        $message_class = 'alert-info';
                    } else {
                        $message = '❌ ไม่พบรอบที่ ' . $round_to_revert . ' หรือรอบดังกล่าวยัง Active อยู่.';
                        $message_class = 'alert-error';
                    }
                }
            }
        // Logic การลบรายการคิว * (ส่วนนี้ถูกปรับปรุงแล้วในคำขอก่อนหน้า) *
        } elseif ($_POST['action'] === 'delete_entry' && isset($_POST['entry_id'])) {
            $entry_id = (int)$_POST['entry_id'];

            // 1. ดึงข้อมูลก่อนลบเพื่อใช้ในข้อความแจ้งเตือน
            $sql_fetch = "SELECT queue_number, tractor_plate, trailer_plate FROM queue_entries WHERE entry_id = $entry_id";
            $result_fetch = $conn->query($sql_fetch);
            $deleted_data = $result_fetch->fetch_assoc();

            if ($deleted_data) {
                $queue_number = htmlspecialchars($deleted_data['queue_number']);
                $plates = htmlspecialchars($deleted_data['tractor_plate']) . 
                          (!empty($deleted_data['trailer_plate']) ? ' / ' . htmlspecialchars($deleted_data['trailer_plate']) : '');
                
                // 2. ดำเนินการลบ
                $sql_delete = "DELETE FROM queue_entries WHERE entry_id = $entry_id";
                
                if ($conn->query($sql_delete) === TRUE) {
                    // 3. แสดงข้อความแจ้งเตือนโดยใช้ข้อมูลที่ดึงมา
                    $message = '🗑️ ลบคิวที่ ' . $queue_number . ' (ทะเบียน: ' . $plates . ') สำเร็จแล้ว.';
                    $message_class = 'alert-warning';
                } else {
                    $message = '❌ เกิดข้อผิดพลาดในการลบ: ' . $conn->error;
                    $message_class = 'alert-error';
                }
            } else {
                $message = '❌ ไม่พบรายการคิวที่ต้องการลบ.';
                $message_class = 'alert-error';
            }
        // Logic การอัปเดตรายการคิวที่แก้ไข
        } elseif ($_POST['action'] === 'update_entry' && isset($_POST['entry_id'])) {
            $entry_id = (int)$_POST['entry_id'];
            $updated_queue_number = trim($_POST['manual_queue_number']);
            $updated_tractor_plate = strtoupper(trim($_POST['tractor_plate']));
            $updated_trailer_plate = strtoupper(trim($_POST['trailer_plate']));
            $trailer_plate_value = empty($updated_trailer_plate) ? "NULL" : "'$updated_trailer_plate'";

            $sql_update = "UPDATE queue_entries SET 
                            queue_number = '$updated_queue_number',
                            tractor_plate = '$updated_tractor_plate',
                            trailer_plate = $trailer_plate_value
                            WHERE entry_id = $entry_id";
            
            if ($conn->query($sql_update) === TRUE) {
                $message = '✏️ แก้ไขคิวที่ ' . $updated_queue_number . ' สำเร็จแล้ว.';
                $message_class = 'alert-success';
            } else {
                $message = '❌ เกิดข้อผิดพลาดในการแก้ไข: ' . $conn->error;
                $message_class = 'alert-error';
            }
        }
    
    // Logic การลงทะเบียนคิวใหม่ (ไม่มี entry_id)
    } elseif (isset($_POST['tractor_plate']) && !isset($_POST['entry_id'])) { 
        $tractor_plate = strtoupper(trim($_POST['tractor_plate']));
        $trailer_plate = strtoupper(trim($_POST['trailer_plate'])); 
        $manual_queue_number = trim($_POST['manual_queue_number']); 

        $current_round_info = getActiveRoundInfo($conn); 
        
        if (!$current_round_info) {
            $message = '❌ ผิดพลาด! กรุณากดปุ่ม เริ่มรอบใหม่ ก่อนทำการลงทะเบียนคิว.';
            $message_class = 'alert-error';
        } elseif (empty($tractor_plate) || empty($manual_queue_number)) { 
            $message = '❌ ผิดพลาด! กรุณากรอกทะเบียนรถหัวลากและหมายเลขคิว.';
            $message_class = 'alert-error';
        } else {
            $current_round_id = $current_round_info['round_id'];
            
            // 1. ตรวจสอบความซ้ำซ้อนของ 'หมายเลขคิว' ในรอบปัจจุบัน
            $sql_check_queue = "SELECT entry_id FROM queue_entries 
                                    WHERE round_id = $current_round_id 
                                    AND queue_number = '$manual_queue_number'"; 
            $result_check_queue = $conn->query($sql_check_queue);

            if ($result_check_queue->num_rows > 0) {
                // หมายเลขคิวซ้ำ -> แสดงข้อความแจ้งเตือน
                $message = '❌ ผิดพลาด! หมายเลขคิว ' . $manual_queue_number . ' ถูกใช้ในรอบนี้แล้ว. (อนุญาตให้ใช้สัญลักษณ์ "/" เช่น 12/1 ได้)';
                $message_class = 'alert-error';
                
            } else {
                // 2. หมายเลขคิวไม่ซ้ำ -> ดำเนินการลงทะเบียนคิว (ไม่ตรวจสอบทะเบียนรถซ้ำ)
                $trailer_plate_value = empty($trailer_plate) ? "NULL" : "'$trailer_plate'";

                $sql_insert = "INSERT INTO queue_entries (round_id, tractor_plate, trailer_plate, queue_number) 
                                    VALUES ($current_round_id, '$tractor_plate', $trailer_plate_value, '$manual_queue_number')";
                
                if ($conn->query($sql_insert) === TRUE) {
                    $message = '✅ ลงทะเบียนสำเร็จ! ทะเบียน: ' . $tractor_plate . (!empty($trailer_plate) ? ' / ' . $trailer_plate : '') . ' คิวที่: ' . $manual_queue_number . ' ในรอบที่ ' . $current_round_info['round_number'] . '';
                    $message_class = 'alert-success';
                    
                } else {
                    $message = '❌ Error: ' . $conn->error;
                    $message_class = 'alert-error';
                }
            }
        }
    }
}


// Logic EDIT (แก้ไข) - Step 1: ดึงข้อมูลเพื่อโหลดเข้าฟอร์ม
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['entry_id'])) {
    $edit_entry_id = (int)$_GET['entry_id'];
    $sql_edit = "SELECT * FROM queue_entries WHERE entry_id = $edit_entry_id";
    $result_edit = $conn->query($sql_edit);
    if ($result_edit->num_rows === 1) {
        $edit_data = $result_edit->fetch_assoc();
        $message = '🛠️ กำลังแก้ไขคิวที่ ' . $edit_data['queue_number'] . '. กรุณาแก้ไขข้อมูลในฟอร์มด้านบน.';
        $message_class = 'alert-info';
    } else {
        $edit_entry_id = null;
        $message = '❌ ไม่พบข้อมูลคิวที่ต้องการแก้ไข.';
        $message_class = 'alert-error';
    }
}

// ... (ส่วนการดึงข้อมูลและ Pagination Logic เหมือนเดิม) ...
$current_round_info = getActiveRoundInfo($conn);
$ended_rounds = getAllEndedRounds($conn); 

$current_queue_entries = [];
$total_records = 0;
$total_pages = 1;

if ($current_round_info) {
    $current_round_id = $current_round_info['round_id'];
    
    // 1. สร้างเงื่อนไขการค้นหา (WHERE clause)
    $where_clause = "WHERE round_id = $current_round_id";
    if (!empty($search_term)) {
        $where_clause .= " AND (
            queue_number LIKE '%$search_term%' OR
            tractor_plate LIKE '%$search_term%' OR
            trailer_plate LIKE '%$search_term%'
        )";
    }
    
    // 2. นับจำนวนรายการทั้งหมดสำหรับ Pagination
    $sql_count = "SELECT COUNT(*) AS total FROM queue_entries $where_clause";
    $result_count = $conn->query($sql_count);
    $total_records = $result_count->fetch_assoc()['total'];
    $total_pages = ceil($total_records / $limit);
    
    // ปรับหน้าปัจจุบันถ้าเกินจำนวนหน้าที่มี
    if ($current_page > $total_pages) {
        $current_page = $total_pages;
        $offset = ($current_page - 1) * $limit;
    }
    // ปรับ offset หากหน้าแรกไม่มีข้อมูล (เช่น หน้าปัจจุบันถูกตั้งค่าเป็น 0)
    if ($offset < 0) $offset = 0;


    // 3. ดึงข้อมูลตาม Limit และ Offset
    $sql_entries = "SELECT * FROM queue_entries 
                     $where_clause
                     ORDER BY queue_number ASC
                     LIMIT $limit OFFSET $offset";
    
    $result_entries = $conn->query($sql_entries);
    while($row = $result_entries->fetch_assoc()) {
        $current_queue_entries[] = $row;
    }
}

$conn->close();

function buildQueryString($params) {
    $query = $_GET;
    foreach ($params as $key => $value) {
        if ($value === null) {
            unset($query[$key]);
        } else {
            $query[$key] = $value;
        }
    }
    return http_build_query($query);
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการคิวรถบรรทุกอ้อย | ผู้ควบคุม</title>
    <style>
        /* --- 1. Global Reset & Typography (Modern Look) --- */
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background-color: #f4f7f9; 
            color: #333;
        }
        .container { 
            max-width: 1100px; /* ขยายความกว้างขึ้นเล็กน้อย */
            margin: 0 auto; 
            padding: 30px; 
            background-color: #ffffff; 
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); 
            border-radius: 12px; 
        }
        h1 { 
            color: #007bff; 
            border-bottom: 2px solid #e9ecef; 
            padding-bottom: 10px; 
            margin-bottom: 20px;
        }
        h2 { 
            color: #333; 
            margin-top: 25px; 
            border-left: 5px solid #007bff; 
            padding-left: 10px;
        }

        /* --- 2. Form & Input Styling (Clean & Modern) --- */
        input[type="text"], select, button { 
            padding: 10px; 
            margin: 8px 0; 
            border: 1px solid #ced4da; 
            border-radius: 6px; 
            box-sizing: border-box;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        input[type="text"]:focus, select:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            outline: none;
        }

        /*  NEW: โหมดแก้ไข  */
        .edit-data-input {
            background-color: #fff8e1; /* สีเหลืองอ่อนสำหรับโหมดแก้ไข */
        }
        
        .reg-input-group { 
            display: grid; 
            grid-template-columns: 1fr 1fr; /* แบ่ง 2 คอลัมน์สำหรับทะเบียนรถ */
            gap: 15px; 
            margin-bottom: 15px; 
        }
        .queue-input { grid-column: span 2; } /* คิวที่ใช้พื้นที่เต็มความกว้าง */
        .queue-input input, .reg-input-group input[type="text"] { width: 100%; }

        /* --- 3. Button & Message Styling (เฉพาะปุ่ม) --- */
        .btn-group button { 
            padding: 10px 15px; 
            margin: 5px 5px 5px 0; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            font-weight: bold;
            transition: background-color 0.2s, transform 0.1s, box-shadow 0.2s;
        }
        .btn-group button:active, .btn-register:active {
            transform: scale(0.98);
        }

        .btn-start { background-color: #28a745; color: white; }
        .btn-end { background-color: #dc3545; color: white; }
        .btn-undo { 
            background-color: #ffc107; 
            color: #333; 
            height: 40px; 
            line-height: 18px; 
            vertical-align: top;
        } 
        .btn-register { 
            background-color: #007bff; 
            color: white; 
            padding: 14px 20px; 
            margin: 15px 0 8px 0; 
            width: 100%; 
            font-size: 18px; 
        }
        button:disabled, select:disabled { 
            background-color: #a0a0a0; 
            cursor: not-allowed; 
            opacity: 0.7;
        }

        /* --- 4. Round Control (Status Bar) --- */
        .round-control { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 30px; 
            padding: 15px; 
            border: 1px solid #ced4da;
            border-radius: 8px; 
            background-color: #f8f9fa;
        }
        .round-status h2 { 
            margin: 0; 
            border-left: none; 
            padding-left: 0;
            color: #28a745; 
        }
        .round-status p { margin: 5px 0 0 0; font-size: 16px; }
        .round-status-inactive h2 { color: #dc3545; } 

        .undo-control { 
            border-left: 1px solid #ddd; 
            padding-left: 15px; 
            margin-left: 15px; 
            display: inline-flex; 
            align-items: center;
        }
        .undo-control label { font-size: 14px; margin-right: 5px; } 
        .undo-control select { width: 100px; margin-right: 5px; } 

        /* --- 5. Table, Search, Pagination Styling --- */
        .search-control { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 20px; 
            gap: 15px;
        }
        .search-box { flex-grow: 1; display: flex; }
        .search-box input[type="text"] { flex-grow: 1; margin-right: 10px; }
        .search-box button { background-color: #6c757d; color: white; border: none; }
        .limit-select label { margin-right: 5px; }
        .limit-select select { height: 40px; padding: 5px; }

        .list-table { 
            width: 100%; 
            border-collapse: separate; 
            border-spacing: 0;
            margin-top: 20px; 
            border-radius: 8px; 
            overflow: hidden; 
        }
        .list-table th, .list-table td { 
            border: 1px solid #dee2e6; 
            padding: 12px; 
            text-align: center; 
            font-size: 14px; 
        }
        .list-table th { 
            background-color: #007bff; 
            color: white; 
            border-top: none; 
            border-bottom: 2px solid #0056b3;
        }
        .list-table tbody tr:nth-child(even) { background-color: #f2f2f2; }
        .list-table tbody tr:hover { background-color: #e9f5ff; }

        .btn-table { 
            padding: 6px 12px; 
            margin: 2px; 
            font-size: 13px; 
            cursor: pointer; 
            border-radius: 4px;
            border: none;
            font-weight: normal;
        }
        .btn-edit { background-color: #ffc107; color: #333; }
        .btn-delete { background-color: #dc3545; color: white; }
        
        /* ปรับสีพื้นหลังแถวในตารางเมื่ออยู่ในโหมดแก้ไข */
        .edit-mode { background-color: #fff3cd !important; font-weight: bold; } 

        /* Pagination */
        .pagination { display: flex; justify-content: center; margin-top: 20px; }
        .pagination a, .pagination span { 
            padding: 8px 14px; margin: 0 4px; border: 1px solid #ced4da; 
            text-decoration: none; color: #007bff; border-radius: 4px; 
            font-size: 15px;
            transition: background-color 0.2s, color 0.2s;
        }
        .pagination span.current-page { 
            background-color: #007bff; 
            color: white; 
            border-color: #007bff; 
            font-weight: bold; 
        }
        .pagination a:hover { background-color: #e9ecef; }
        .pagination .ellipsis { border: none; background-color: transparent; color: #6c757d; padding: 8px 5px; }


        /* --- 6. NEW: Toast Notification Styles --- */

        #toast-container {
            position: fixed;
            top: 20px; /* ระยะห่างจากด้านบน */
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000; /* อยู่ด้านบนสุด */
            width: 90%;
            max-width: 450px;
            pointer-events: none; /* อนุญาตให้คลิกผ่านได้เมื่อไม่มี Toast */
        }

        .toast {
            padding: 15px 20px;
            margin-bottom: 10px;
            border-radius: 8px;
            font-weight: bold;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            opacity: 0;
            transform: translateY(-20px); /* เริ่มต้นอยู่สูงกว่าเล็กน้อย */
            transition: all 0.3s ease-in-out;
            pointer-events: auto; /* เปิดใช้งานการคลิกบน Toast */
            border-left: 5px solid;
            line-height: 1.5;
        }

        /* Class สำหรับการแสดง/ซ่อน Toast (ใช้ร่วมกับ JS) */
        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        /* Toast States Mapping (จาก alert-class ใน PHP) */
        .toast.success { 
            background-color: #d4edda; 
            color: #155724; 
            border-color: #28a745; 
        }
        .toast.error { 
            background-color: #f8d7da; 
            color: #721c24; 
            border-color: #dc3545; 
        }
        .toast.warning { 
            background-color: #fff3cd; 
            color: #856404; 
            border-color: #ffc107; 
        }
        .toast.info { 
            background-color: #d1ecf1; 
            color: #0c5460; 
            border-color: #007bff; 
        }

        /* เพิ่ม Transition ให้กับปุ่มและตารางเพื่อความสวยงาม */
        .btn-group button:hover, .btn-register:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .list-table tbody tr {
            transition: background-color 0.3s ease;
        }

    </style>
</head>
<body>
    <div id="toast-container"></div>
    
    <div class="container">
        <h1>🚜 ระบบจัดการคิวรถบรรทุกอ้อย (Manual Control)</h1>
        
        <hr>
        
        <div class="round-control">
            <div class="round-status <?php echo $current_round_info ? '' : 'round-status-inactive'; ?>">
                <?php if ($current_round_info): ?>
                    <h2>✅ รอบปัจจุบัน: <?php echo $current_round_info['round_number']; ?></h2>
                    <p>คิวสะสม: <?php echo $current_round_info['current_count']; ?> / <?php echo $max_queue_limit; ?></p>
                <?php else: ?>
                    <h2>🛑 สถานะ: ไม่มีรอบที่กำลังทำงาน</h2>
                    <p>กรุณากด ริ่มรอบใหม่ เพื่อลงทะเบียนคิว</p>
                <?php endif; ?>
            </div>

            <div class="btn-group">
                <form method="POST" style="display: inline-block;" onsubmit="return confirm('⚠️ ยืนยันการ เริ่มรอบใหม่ ใช่หรือไม่?');">
                    <input type="hidden" name="action" value="start_new_round">
                    <button type="submit" class="btn-start" <?php echo $current_round_info ? 'disabled' : ''; ?>>▶️ เริ่มรอบใหม่</button>
                </form>
                
                <form method="POST" style="display: inline-block;" onsubmit="return confirm('🛑 ยืนยันที่จะ จบรอบปัจจุบัน (รอบที่ <?php echo $current_round_info['round_number'] ?? '...'; ?>) ใช่หรือไม่?');">
                    <input type="hidden" name="action" value="end_current_round">
                    <button type="submit" class="btn-end" <?php echo !$current_round_info ? 'disabled' : ''; ?>>⏹️ จบรอบปัจจุบัน</button>
                </form>

                <div class="undo-control">
                    <form method="POST" style="display: flex; align-items: center;" onsubmit="
                        var selectedRound = document.getElementById('round_number_to_undo').value;
                        if (!selectedRound) {
                            alert('กรุณาเลือกรอบที่ต้องการย้อนกลับ.');
                            return false;
                        }
                        return confirm('↩️ ยืนยันที่จะ ย้อนกลับ ไปรอบที่ ' + selectedRound + ' ใช่หรือไม่? รอบปัจจุบันจะถูกเปิดใช้งาน.');
                    ">
                        <label for="round_number_to_undo">ย้อนกลับไปรอบที่:</label>
                        
                        <select id="round_number_to_undo" name="round_number_to_undo" required <?php echo $current_round_info ? 'disabled' : ''; ?>>
                            <option value="">-- เลือก --</option>
                            <?php foreach ($ended_rounds as $round_num): ?>
                                <option value="<?php echo $round_num; ?>">รอบ <?php echo $round_num; ?></option>
                            <?php endforeach; ?>
                        </select>
                        
                        <input type="hidden" name="action" value="undo_end_round_manual">
                        <button type="submit" class="btn-undo" <?php echo ($current_round_info || empty($ended_rounds)) ? 'disabled' : ''; ?>>↩️ ย้อนกลับ</button>
                    </form>
                </div>
            </div>
        </div>
        
        <hr>

        <h2><?php echo $edit_entry_id ? '✏️ แก้ไขข้อมูลคิว (Entry ID: ' . $edit_entry_id . ')' : '📝 ลงทะเบียนคิวรถใหม่'; ?></h2>
        
        <form method="POST" action="add_queue.php">
            <?php if ($edit_entry_id): ?>
                <input type="hidden" name="action" value="update_entry">
                <input type="hidden" name="entry_id" value="<?php echo $edit_entry_id; ?>">
            <?php endif; ?>

            <div class="reg-input-group">
                <div class="queue-input">
                    <label for="manual_queue_number">หมายเลขคิว (คีย์เอง):</label>
                    <input type="text" id="manual_queue_number" name="manual_queue_number" required 
                            style="text-transform: uppercase;" placeholder="เช่น 12 หรือ 12/1" 
                            class="<?php echo $edit_entry_id ? 'edit-data-input' : ''; ?>" value="<?php echo htmlspecialchars($edit_data['queue_number'] ?? ''); ?>">
                </div>
                <div>
                    <label for="tractor_plate">ทะเบียนรถหัวลาก (รถเดียว):</label>
                    <input type="text" id="tractor_plate" name="tractor_plate" required 
                            style="text-transform: uppercase;" placeholder="เช่น อต80-1234"
                            class="<?php echo $edit_entry_id ? 'edit-data-input' : ''; ?>" value="<?php echo htmlspecialchars($edit_data['tractor_plate'] ?? ''); ?>">
                </div>
                <div>
                    <label for="trailer_plate">ทะเบียนรถพ่วงข้าง (รถพ่วง) (ถ้ามี):</label>
                    <input type="text" id="trailer_plate" name="trailer_plate" 
                            style="text-transform: uppercase;" placeholder="ถ้ามี เช่น อต70-5678"
                            class="<?php echo $edit_entry_id ? 'edit-data-input' : ''; ?>" value="<?php echo htmlspecialchars($edit_data['trailer_plate'] ?? ''); ?>">
                </div>
            </div>
            
            <button type="submit" class="btn-register" <?php echo (!$current_round_info && !$edit_entry_id) ? 'disabled' : ''; ?>>
                <?php echo $edit_entry_id ? '💾 บันทึกการแก้ไข' : '✅ ลงทะเบียนคิวใหม่'; ?>
            </button>
            <?php if ($edit_entry_id): ?>
                <a href="add_queue.php" style="display: block; text-align: center; margin-top: 10px; color: #dc3545; font-weight: bold;">❌ ยกเลิกการแก้ไข</a>
            <?php endif; ?>
        </form>

        
        <hr>

        <h2>📋 รายการคิวในรอบปัจจุบัน</h2>
        
        <?php if ($current_round_info): ?>
            <div class="search-control">
                <div class="limit-select">
                    <form method="GET" action="add_queue.php" style="display: inline-block;">
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_term); ?>">
                        <label for="limit">แสดง:</label>
                        <select id="limit" name="limit" onchange="this.form.submit()">
                            <?php foreach ($limit_options as $option): ?>
                                <option value="<?php echo $option; ?>" <?php echo $limit == $option ? 'selected' : ''; ?>>
                                    <?php echo $option; ?> 
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>

                <div class="search-box">
                    <form method="GET" action="add_queue.php" style="display: flex; flex-grow: 1;">
                        <input type="hidden" name="limit" value="<?php echo $limit; ?>"> 
                        <input type="text" name="search" placeholder="ค้นหาคิวหรือทะเบียนรถ..." value="<?php echo htmlspecialchars($search_term); ?>">
                        <button type="submit">🔍 ค้นหา</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($current_round_info && $total_records > 0): ?>
            <p style="font-size: 14px; text-align: right; margin-bottom: 5px; color: #6c757d;">
                แสดงรายการที่ <?php echo $offset + 1; ?> ถึง <?php echo min($offset + $limit, $total_records); ?> จากทั้งหมด <?php echo $total_records; ?> รายการ
            </p>
            <table class="list-table">
                <thead>
                    <tr>
                        <th style="width: 15%;">คิวที่</th>
                        <th style="width: 25%;">ทะเบียนหัวลาก</th>
                        <th style="width: 25%;">ทะเบียนพ่วงข้าง</th>
                        <th style="width: 35%;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($current_queue_entries as $entry): ?>
                    <tr class="<?php echo ($edit_entry_id === (int)$entry['entry_id']) ? 'edit-mode' : ''; ?>">
                        <td><?php echo htmlspecialchars($entry['queue_number']); ?></td>
                        <td><?php echo htmlspecialchars($entry['tractor_plate']); ?></td>
                        <td><?php echo htmlspecialchars($entry['trailer_plate'] ?? '-'); ?></td>
                        <td>
                            <a href="add_queue.php?<?php echo buildQueryString(['action' => 'edit', 'entry_id' => $entry['entry_id']]); ?>">
                                <button type="button" class="btn-table btn-edit">✏️ แก้ไข</button>
                            </a>
                            
                            <form method="POST" style="display: inline-block;" onsubmit="return confirm('⚠️ ยืนยันการลบคิว <?php echo $entry['queue_number']; ?> นี้หรือไม่? ข้อมูลจะถูกลบถาวร');">
                                <input type="hidden" name="action" value="delete_entry">
                                <input type="hidden" name="entry_id" value="<?php echo $entry['entry_id']; ?>">
                                <button type="submit" class="btn-table btn-delete">🗑️ ลบ</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="pagination">
                <?php if ($total_pages > 1): ?>
                    <?php if ($current_page > 1): ?>
                        <a href="add_queue.php?<?php echo buildQueryString(['page' => 1]); ?>">หน้าแรก</a>
                    <?php endif; ?>

                    <?php 
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);

                    if ($start_page > 1): ?>
                        <span class="ellipsis">...</span>
                    <?php endif; ?>

                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <?php if ($i == $current_page): ?>
                            <span class="current-page"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="add_queue.php?<?php echo buildQueryString(['page' => $i]); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($end_page < $total_pages): ?>
                        <span class="ellipsis">...</span>
                    <?php endif; ?>

                    <?php if ($current_page < $total_pages): ?>
                        <a href="add_queue.php?<?php echo buildQueryString(['page' => $total_pages]); ?>">หน้าสุดท้าย</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <?php if (!empty($search_term) && count($current_queue_entries) === 0): ?>
                <p style="text-align: center; color: orange; margin-top: 15px; padding: 10px; border: 1px dashed orange;">ไม่พบข้อมูลสำหรับคำค้นหา "<?php echo htmlspecialchars($search_term); ?>" ในรอบปัจจุบัน</p>
            <?php endif; ?>
        <?php elseif ($current_round_info && $total_records == 0): ?>
            <p style="text-align: center; color: #6c757d; padding: 20px; border: 1px dashed #ced4da;">ยังไม่มีการบันทึกคิวในรอบที่ <?php echo $current_round_info['round_number']; ?></p>
        <?php else: ?>
            <p style="text-align: center; color: #dc3545; padding: 20px; border: 1px dashed #dc3545;">กรุณาเริ่มรอบใหม่ก่อนจึงจะสามารถแสดงรายการคิวได้</p>
        <?php endif; ?>

    </div>
    
    <script>
        // NEW: Function to display the toast message
        function showToast(message, className) {
            if (!message) return;

            const container = document.getElementById('toast-container');
            
            // 1. Create Toast element
            const toast = document.createElement('div');
            // ใช้ className ที่แปลงแล้ว (success, error, warning, info)
            toast.className = `toast ${className}`; 
            toast.innerHTML = `<p>${message}</p>`;

            // 2. Append and Show (Fade In)
            container.appendChild(toast);
            // requestAnimationFrame ensures the CSS transition works
            requestAnimationFrame(() => {
                toast.classList.add('show');
            });

            // 3. Hide (Fade Out) after 2.5s (2500ms)
            const duration = 2500; 
            setTimeout(() => {
                toast.classList.remove('show');
            }, duration);

            // 4. Remove from DOM after transition (0.3s transition + a small buffer)
            setTimeout(() => {
                toast.remove();
            }, duration + 300);
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // ดึงค่า PHP $message และ $message_class
            const phpMessage = "<?php echo $message; ?>";
            let phpClass = "<?php echo $message_class; ?>"; 
            
            if (phpMessage) {
                // แปลงคลาสจาก 'alert-success' ให้เป็น 'success' เพื่อใช้กับ Toast CSS
                phpClass = phpClass.replace('alert-', '');
                showToast(phpMessage, phpClass);
            }

            // [NEW] เพิ่มการเน้นฟอร์มในโหมดแก้ไขและเลื่อนหน้าจอ
            <?php if ($edit_entry_id): ?>
                const editForm = document.querySelector('form[action="add_queue.php"]');
                if (editForm) {
                    editForm.style.transition = 'all 0.4s ease-in-out';
                    editForm.style.border = '2px solid #ffc107';
                    editForm.style.borderRadius = '8px';
                    editForm.style.padding = '15px';
                    editForm.style.backgroundColor = '#fffaf2';
                    
                    // เลื่อนหน้าจอไปที่ฟอร์มแก้ไข
                    editForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            <?php endif; ?>
        });
    </script>
</body>
</html>