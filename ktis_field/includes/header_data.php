<?php
/**
 * includes/header_data.php - ประมวลผลข้อมูลแจ้งเตือนและสิทธิ์ผู้ใช้สำหรับ Navbar
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

$noti_count = 0;
$notifications = [];

if(isset($_SESSION['emp_id']) && isset($_SESSION['emp_unit'])) {
    $my_unit = $_SESSION['emp_unit'];
    $my_emp_id = $_SESSION['emp_id'];
    $my_level = $_SESSION['emp_level'] ?? 'u';

    try {
        // นับจำนวน Unread ที่ยังไม่ได้อ่าน
        $stmt_count = $conn->prepare("SELECT COUNT(*) AS unread FROM notifications WHERE is_read = 0 AND emp_id = :emp_id");
        $stmt_count->execute([':emp_id' => $my_emp_id]);
        $noti_count = $stmt_count->fetch()['unread'];

        // ดึงรายการแจ้งเตือนล่าสุด 8 รายการ
        $stmt_list = $conn->prepare(
            "SELECT n.*, p.problem_detail, DATE(p.created_at) AS post_date 
             FROM notifications n
             LEFT JOIN posts p ON n.post_id = p.post_id
             WHERE n.emp_id = :emp_id 
             ORDER BY n.noti_id DESC LIMIT 20"
        );
        $stmt_list->execute([':emp_id' => $my_emp_id]);
        $notifications = $stmt_list->fetchAll();

    } catch (Exception $e) {
        error_log("Notification System Error: " . $e->getMessage());
    }
}
$current_page = basename($_SERVER['PHP_SELF']);

// ── ตรวจสอบโหมดปิดปรับปรุงระบบ (Maintenance Mode) ──
$is_maintenance = false;
$is_admin = isset($_SESSION['emp_level']) && $_SESSION['emp_level'] === 'a';

try {
    $stmt_m = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'maintenance_mode'");
    $m_val = $stmt_m ? $stmt_m->fetchColumn() : '0';
    if ($m_val === '1') {
        $is_maintenance = true;
        $exempt_pages = ['maintenance.php', 'login.php', 'logout.php', 'api_settings.php'];
        if (!$is_admin && !in_array($current_page, $exempt_pages)) {
            header("Location: maintenance.php");
            exit;
        }
    }
} catch (Exception $e) {}
?>
