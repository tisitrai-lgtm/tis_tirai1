<?php
// ==========================================================
// 🔧 maintenance_check.php
// ใส่ require ไฟล์นี้ไว้บรรทัดแรกสุดของทุกหน้าที่ต้องการ "ปิดปรับปรุง"
// ยกเว้น login.php และ chk.php ห้ามใส่ ไม่งั้นแอดมิน login เข้าไม่ได้เลย
// ==========================================================

// เผื่อหน้าที่ include ไฟล์นี้ยังไม่ได้เรียก session_start()
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// อ่านสถานะโหมดปรับปรุงจากไฟล์ (แก้ผ่านหน้า admin_maintenance_settings.php ได้เลย ไม่ต้องแก้โค้ด)
$maintenance_status_file = __DIR__ . '/maintenance_status.txt';
$maintenance_mode = false;
if (file_exists($maintenance_status_file)) {
    $maintenance_mode = trim(file_get_contents($maintenance_status_file)) === '1';
}

if ($maintenance_mode) {
    // เช็คว่าเป็นแอดมิน (login แล้ว และ emp_level = 'a') หรือไม่
    $is_admin = isset($_SESSION['emp_level']) && $_SESSION['emp_level'] === 'a';

    if (!$is_admin) {
        // ไม่ใช่แอดมิน (หรือยังไม่ได้ login) -> โชว์หน้ากำลังปรับปรุง แล้วหยุดทันที
        require __DIR__ . '/maintenance.php';
        exit;
    }
    // ถ้าเป็นแอดมิน -> ปล่อยผ่าน ทำงานต่อตามปกติ
}