<?php
/**
 * post_edit.php - ระบบแก้ไขสถานะ ปรับปรุงรายละเอียด หรือลบโพสต์ (สำหรับ Admin)
 */
require_once 'config.php';
session_start();

if(!isset($_SESSION["emp_id"]) || $_SESSION["emp_level"] !== 'a') {
    echo json_encode(["status" => "error", "message" => "คุณไม่มีสิทธิ์เข้าถึงฟังก์ชันนี้"]);
    exit;
}

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    if($post_id <= 0) {
        echo json_encode(["status" => "error", "message" => "ไม่พบรหัสโพสต์ที่ต้องการทำรายการ"]);
        exit;
    }

    // 🛠️ กรณีที่ 1: อัปเดตสถานะงานเป็น ดำเนินการเสร็จสิ้น (Success) เพื่อปิดจ๊อบ
    if($action == 'update_status') {
        $new_status = $_POST['job_status']; // 'pending' หรือ 'success'
        
        try {
            $conn->beginTransaction();

            $sql = "UPDATE posts SET job_status = :job_status WHERE post_id = :post_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':job_status' => $new_status, ':post_id' => $post_id]);

            // บันทึก Log การทำงานลงตารางประวัติระบบ
            $log_sql = "INSERT INTO system_logs (action_by, action_type, target_id, log_details) 
                        VALUES (:action_by, 'EDIT_POST', :target_id, :log_details)";
            $log_stmt = $conn->prepare($log_sql);
            $log_stmt->execute([
                ':action_by' => $_SESSION['emp_id'],
                ':target_id' => $post_id,
                ':log_details' => "เปลี่ยนสถานะโพสต์แจ้งเหตุเป็น " . ($new_status == 'success' ? 'ดำเนินการแล้ว' : 'รอดำเนินการ')
            ]);

            $conn->commit();
            echo json_encode(["status" => "success", "message" => "อัปเดตสถานะงานเรียบร้อย"]);
        } catch(Exception $e) {
            $conn->rollBack();
            echo json_encode(["status" => "error", "message" => "ล้มเหลว: " . $e->getMessage()]);
        }
        exit;
    }

    // 🗑️ กรณีที่ 2: กดลบโพสต์ทิ้ง (เมื่อโพสต์ข้อมูลผิดพลาด)
    if($action == 'delete_post') {
        try {
            $conn->beginTransaction();

            // ดึงที่อยู่ไฟล์รูปเก่ามาลบออกจาก Server ด้วยเพื่อไม่ให้รกดิสก์
            $stmt_img = $conn->prepare("SELECT post_image, post_image_2, post_image_3 FROM posts WHERE post_id = :post_id");
            $stmt_img->execute([':post_id' => $post_id]);
            $images = $stmt_img->fetch();

            if($images) {
                foreach(['post_image', 'post_image_2', 'post_image_3'] as $f) {
                    if(!empty($images[$f]) && file_exists($images[$f])) {
                        unlink($images[$f]); // ลบไฟล์รูปจริงออกจากโฟลเดอร์ uploads
                    }
                }
            }

            // คำสั่งลบโพสต์ (ตาราง replies และ notifications จะถูกลบออโต้ด้วย CASCADE จาก Foreign Key)
            $sql = "DELETE FROM posts WHERE post_id = :post_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':post_id' => $post_id]);

            // บันทึก Log ประวัติการลบ
            $log_sql = "INSERT INTO system_logs (action_by, action_type, target_id, log_details) 
                        VALUES (:action_by, 'DELETE_POST', :target_id, :log_details)";
            $log_stmt = $conn->prepare($log_sql);
            $log_stmt->execute([
                ':action_by' => $_SESSION['emp_id'],
                ':target_id' => $post_id,
                ':log_details' => "ลบโพสต์แจ้งเหตุรถอ้อยสกปรกไอดี #" . $post_id . " ออกจากระบบถาวร"
            ]);

            $conn->commit();
            echo json_encode(["status" => "success", "message" => "ลบข้อมูลโพสต์เรียบร้อย"]);
        } catch(Exception $e) {
            $conn->rollBack();
            echo json_encode(["status" => "error", "message" => "ลบไม่สำเร็จ: " . $e->getMessage()]);
        }
        exit;
    }
}
?>