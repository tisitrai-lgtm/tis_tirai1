<?php
require("dbconnect.php");
$round = mysqli_real_escape_string($con, $_POST['print_round']);

$sql = "SELECT created_at FROM pdf_export_logs WHERE print_round = '$round' ORDER BY created_at ASC";
$res = mysqli_query($con, $sql);

echo "<ul class='list-group list-group-flush'>";
$i = 1;
while($row = mysqli_fetch_assoc($res)) {
    $label = ($i == 1) ? "สร้างครั้งแรก" : "พิมพ์ซ้ำ/แก้ไข ครั้งที่ ".($i-1);
    $color = ($i == 1) ? "text-success" : "text-info";
    echo "<li class='list-group-item d-flex justify-content-between'>";
    echo "<span class='$color'><i class='bx bx-check-circle'></i> $label</span>";
    echo "<span>".date('d/m/Y H:i:s', strtotime($row['created_at']))."</span>";
    echo "</li>";
    $i++;
}
echo "</ul>";
?>