<?php
session_start();
require("dbconnect.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $print_round = mysqli_real_escape_string($con, $_POST['print_round']);
    $plot_ids = $_POST['plot_ids'];
    $year_rai = mysqli_real_escape_string($con, $_POST['year_rai']);
    $emp_id = $_SESSION['emp_id'];

    // 1. ลบข้อมูลเก่าในรอบการพิมพ์นั้นออกก่อน
    mysqli_query($con, "DELETE FROM print_history WHERE print_round = '$print_round' AND emp_id = '$emp_id'");

    // 2. เพิ่มข้อมูลใหม่ที่เพิ่งติ๊กเข้าไป
    foreach ($plot_ids as $pid) {
        $pid_safe = mysqli_real_escape_string($con, $pid);
        $sql = "INSERT INTO print_history (emp_id, plot_id, year_rai, print_date, print_round) 
                VALUES ('$emp_id', '$pid_safe', '$year_rai', NOW(), '$print_round')";
        mysqli_query($con, $sql);
    }
    echo "success";
}
?>