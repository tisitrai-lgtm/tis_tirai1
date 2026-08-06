<?php
session_start();
require("dbconnect.php");

if (isset($_POST['username'], $_POST['password'], $_POST['year_rai'])) {
    $username = $_POST['username'];
    // เก็บ Password แบบ Plain text ไว้เฉพาะสำหรับทำ Cookie (ถ้าติ๊ก remember)
    $password_raw = $_POST['password']; 
    $password = md5($password_raw);
    $year_rai = $_POST['year_rai'];
    
    // ดึงค่าจาก Checkbox "จดจำรหัสผ่าน"
    $remember = isset($_POST['remember']); 

    $sql = "SELECT * FROM employee WHERE emp_id = ? AND emp_pass = ?";
    $stmt = mysqli_prepare($con, $sql);

    if (!$stmt) {
        header("Location: login.php?status=fail");
        exit();
    }

    mysqli_stmt_bind_param($stmt, 'ss', $username, $password);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);

        $_SESSION['emp_id'] = $row['emp_id'];
        $_SESSION['emp_name'] = $row['emp_name'];
        $_SESSION['emp_level'] = $row['emp_level'];
        
        // --- ส่วนที่เพิ่มเข้าไปเดิมของเธอ ---
        $_SESSION['year_rai'] = $year_rai; 
        $_SESSION['selected_year'] = $year_rai; 
        // -----------------------

        // ⭐ ส่วนที่เพิ่มใหม่: จัดการระบบจดจำรหัสผ่าน (Cookies) ⭐
        if ($remember) {
            // ตั้งค่าให้จำไว้นาน 30 วัน (86400 วินาที * 30)
            setcookie("remember_user", $username, time() + (86400 * 30), "/");
            setcookie("remember_pass", $password_raw, time() + (86400 * 30), "/");
        } else {
            // ถ้าไม่ได้ติ๊ก ให้เคลียร์ Cookie เก่าทิ้งทันที
            setcookie("remember_user", "", time() - 3600, "/");
            setcookie("remember_pass", "", time() - 3600, "/");
        }
        // ---------------------------------------------

        if ($row['emp_level'] == 'a') {
            header("Location: login.php?status=success_a");
        } elseif ($row['emp_level'] == 'u') {
            header("Location: login.php?status=success_u");
        } else {
            header("Location: login.php?status=fail");
        }
    } else {
        header("Location: login.php?status=fail");
    }

    mysqli_stmt_close($stmt);
} else {
    header("Location: login.php?status=fail");
}
?>

<style>
  .modal-content {
    border-radius: 15px;
    padding: 30px;
    text-align: center;
    background-color: #fff;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  }
  .modal-body i {
    font-size: 48px;
    margin-bottom: 15px;
  }
  .icon-success {
    color: #28a745;
    animation: rotateCheck 1s ease-in-out;
  }
  .icon-fail {
    color: #dc3545;
    animation: shake 0.5s ease-in-out;
  }

  @keyframes rotateCheck {
    0% { transform: rotate(0deg); opacity: 0; }
    100% { transform: rotate(360deg); opacity: 1; }
  }

  @keyframes shake {
    0%, 100% { transform: translateX(0); }
    20%, 60% { transform: translateX(-10px); }
    40%, 80% { transform: translateX(10px); }
  }
</style>