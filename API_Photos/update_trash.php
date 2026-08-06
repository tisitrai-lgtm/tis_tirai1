<?php
// update_trash.php
require_once 'db_config.php';

if (isset($_POST['id']) && isset($_POST['trash_percentage'])) {
    $id = $_POST['id'];
    $val = $_POST['trash_percentage'];
    
    $sql = "UPDATE plots_inspection SET trash_percentage = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$val, $id]);
    
    echo "success";
}
?>