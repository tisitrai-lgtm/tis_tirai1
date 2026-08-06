<?php
// ###########################################################
// ไฟล์: deleteQueryString.php
// ###########################################################

// ✅ ควรใช้ error_reporting(0); และลบ ini_set('display_errors', 1); ออกเมื่อใช้งานจริง
// แต่เพื่อการ debug ชั่วคราว เก็บไว้ก่อนได้
//ini_set('display_errors', 1);
//error_reporting(E_ALL); // เปิดแสดง error ทั้งหมด

// ✅ เปิด output buffering ที่จุดเริ่มต้นของไฟล์
ob_start();

require 'db_connect.php'; // เรียกไฟล์เชื่อมต่อฐานข้อมูล

// ตั้งค่า header ให้ส่ง JSON
header('Content-Type: application/json');

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT); // รับค่า ID จาก POST และตรวจสอบว่าเป็นตัวเลข

if (!$id) { // ถ้าไม่มี ID หรือ ID ไม่ถูกต้อง
    http_response_code(400); // ✅ ตั้งค่า HTTP Status Code เป็น 400 Bad Request
    ob_clean(); // ✅ ล้าง output buffer ก่อนส่ง JSON
    echo json_encode(['status' => 'error', 'message' => 'ID ไม่ถูกต้อง']); // ส่งข้อความผิดพลาดกลับไป
    exit; // หยุดการทำงาน
}

try {
    $stmt = $conn->prepare("DELETE FROM land_info WHERE id = :id LIMIT 1"); // ลบแค่ 1 แถวที่มี ID ตรงกัน
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute(); // ประมวลผลคำสั่ง SQL

    if ($stmt->rowCount() > 0) { // ถ้ามีการลบข้อมูลไปแล้วอย่างน้อย 1 แถว
        http_response_code(200); // ✅ ตั้งค่า HTTP Status Code เป็น 200 OK
        ob_clean(); // ✅ ล้าง output buffer ก่อนส่ง JSON
        echo json_encode(['status' => 'success', 'message' => 'ลบข้อมูลเรียบร้อยแล้ว']); // ส่งข้อความสำเร็จ
    } else { // ถ้าไม่พบข้อมูลที่ต้องการลบ (เช่น ID นั้นไม่มีอยู่ในฐานข้อมูลแล้ว)
        http_response_code(404); // ✅ ตั้งค่า HTTP Status Code เป็น 404 Not Found
        ob_clean(); // ✅ ล้าง output buffer ก่อนส่ง JSON
        echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลที่ต้องการลบ']); // ส่งข้อความไม่พบข้อมูล
    }
} catch (PDOException $e) { // ถ้าเกิดข้อผิดพลาดในการเชื่อมต่อหรือทำงานกับฐานข้อมูล
    http_response_code(500); // ✅ ตั้งค่า HTTP Status Code เป็น 500 Internal Server Error
    ob_clean(); // ✅ ล้าง output buffer ก่อนส่ง JSON
    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]); // ส่งข้อความผิดพลาดพร้อมรายละเอียด
}
?>