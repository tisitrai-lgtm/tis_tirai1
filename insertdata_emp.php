<?php
header('Content-Type: application/json');
require('dbconnect.php');

$response = ['status' => 'error', 'message' => 'ข้อมูลไม่ครบถ้วน'];

if (isset($_POST["emp_id"], $_POST['emp_pass'], $_POST["emp_name"], $_POST["emp_unit"], $_POST["emp_level"])) {
    $emp_id = $_POST["emp_id"];
    $emp_pass = md5($_POST['emp_pass']);
    $emp_name = $_POST["emp_name"];
    $emp_unit = $_POST["emp_unit"];
    $emp_level = $_POST["emp_level"];

    // Use prepared statements to prevent SQL injection
    // 1. Check for duplicates
    $stmt = mysqli_prepare($con, "SELECT emp_id FROM employee WHERE emp_id = ?");
    mysqli_stmt_bind_param($stmt, "s", $emp_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        $response['message'] = 'รหัสพนักงานนี้มีอยู่แล้วในระบบ!';
    } else {
        mysqli_stmt_close($stmt); // Close previous statement

        // 2. Insert new employee
        $stmt = mysqli_prepare($con, "INSERT INTO employee(emp_id, emp_pass, emp_name, emp_unit, emp_level) VALUES(?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sssss", $emp_id, $emp_pass, $emp_name, $emp_unit, $emp_level);
        
        if (mysqli_stmt_execute($stmt)) {
            $response['status'] = 'success';
            $response['message'] = 'เพิ่มข้อมูลพนักงานเรียบร้อยแล้ว!';
        } else {
            $response['message'] = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . mysqli_error($con);
        }
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($con);
echo json_encode($response);
?>
