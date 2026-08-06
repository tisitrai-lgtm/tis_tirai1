<?php
/**
 * logout.php - ล้างระบบ Session และดึงกลับไปหน้า Login
 */
session_start();

// ล้างค่า Session ทั้งหมด
$_SESSION = array();

// ทำลาย Session ในระบบ
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// ส่งกลับไปหน้าล็อกอิน
header("location: login.php");
exit;
?>