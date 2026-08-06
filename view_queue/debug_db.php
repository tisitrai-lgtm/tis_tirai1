<?php
include 'db_connect.php';
$result = $conn->query("SELECT q.*, r.round_number FROM queue_entries q LEFT JOIN rounds r ON q.round_id = r.round_id WHERE r.round_number = 56");
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "ID: " . $row['entry_id'] . " | Plate: " . $row['tractor_plate'] . " | Round: " . $row['round_number'] . "\n";
    }
} else {
    echo "No results found for round 56.\n";
}
?>
