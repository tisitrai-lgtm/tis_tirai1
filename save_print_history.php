<?php
session_start();
require("dbconnect.php");

if (isset($_POST['plot_ids']) && isset($_SESSION['emp_id'])) {
    $emp_id = mysqli_real_escape_string($con, $_SESSION['emp_id']);
    $year_rai = mysqli_real_escape_string($con, $_POST['year_rai']);
    $print_round = mysqli_real_escape_string($con, $_POST['print_round']);
    
    foreach ($_POST['plot_ids'] as $plot_id) {
        $plot_id = mysqli_real_escape_string($con, $plot_id);
        // บันทึกโดยใช้ print_round เดียวกันสำหรับกลุ่มที่เลือกพร้อมกัน
        $sql = "INSERT INTO print_history (plot_id, emp_id, year_rai, print_date, print_round) 
                VALUES ('$plot_id', '$emp_id', '$year_rai', NOW(), '$print_round')";
        mysqli_query($con, $sql);
    }
    echo "success";
}
?>