<?php
header('Content-Type: application/json');
require('dbconnect.php');

$response = ['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วนหรือไม่ถูกต้อง'];

if (isset($_POST["emp_id"], $_POST["emp_name"], $_POST["emp_unit"], $_POST["emp_level"])) {
    $emp_id = $_POST["emp_id"];
    $emp_name = $_POST["emp_name"];
    $emp_unit = $_POST["emp_unit"];
    $emp_level = $_POST["emp_level"];
    $emp_pass = $_POST["emp_pass"];

    // Use prepared statements to prevent SQL injection
    if (!empty($emp_pass)) {
        // Update with new password
        $hashed_pass = md5($emp_pass);
        $sql = "UPDATE employee SET emp_name=?, emp_unit=?, emp_level=?, emp_pass=? WHERE emp_id=?";
        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, "sssss", $emp_name, $emp_unit, $emp_level, $hashed_pass, $emp_id);
    } else {
        // Update without changing password
        $sql = "UPDATE employee SET emp_name=?, emp_unit=?, emp_level=? WHERE emp_id=?";
        $stmt = mysqli_prepare($con, $sql);
        mysqli_stmt_bind_param($stmt, "ssss", $emp_name, $emp_unit, $emp_level, $emp_id);
    }

    if (mysqli_stmt_execute($stmt)) {
        $response['status'] = 'success';
        $response['message'] = 'อัปเดตข้อมูลเรียบร้อยแล้ว!';
    } else {
        $response['message'] = 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล: ' . mysqli_error($con);
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($con);
echo json_encode($response);
?>
