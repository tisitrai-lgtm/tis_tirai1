<?php
include_once 'db_config.php';

if (isset($_POST['id']) && isset($_POST['note'])) {
    $id = intval($_POST['id']);
    $note = $conn->real_escape_string($_POST['note']);

    $sql = "UPDATE conversion_logs SET note = '$note' WHERE id = $id";

    if ($conn->query($sql)) {
        echo "success"; // ส่งค่านี้กลับไปอย่างเดียว ห้ามมีอย่างอื่น
    } else {
        echo "Error: " . $conn->error;
    }
}
exit; // ใส่ exit เพื่อป้องกันไม่ให้มีค่าอื่นหลุดออกไป