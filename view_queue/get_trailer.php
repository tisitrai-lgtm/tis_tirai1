<?php
// get_trailer.php
include 'db_connect.php';

if (isset($_GET['tractor_plate'])) {
    $tractor = $conn->real_escape_string($_GET['tractor_plate']);
    
    // ค้นหาคิวล่าสุดของหัวลากคันนี้ที่มีทะเบียนพ่วง
    $sql = "SELECT trailer_plate FROM queue_entries 
            WHERE tractor_plate = '$tractor' AND trailer_plate IS NOT NULL 
            ORDER BY created_at DESC LIMIT 1";
            
    $result = $conn->query($sql);
    if ($row = $result->fetch_assoc()) {
        echo $row['trailer_plate'];
    }
}
$conn->close();
?>