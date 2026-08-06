<?php
header('Content-Type: application/json');
require_once 'config.php';
session_start();

if (!isset($_SESSION['emp_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (isset($_GET['id'])) {
    $noti_id = intval($_GET['id']);
    $my_emp_id = $_SESSION['emp_id'];

    try {
        $stmt = $conn->prepare("DELETE FROM notifications WHERE noti_id = ? AND emp_id = ?");
        $stmt->execute([$noti_id, $my_emp_id]);
        
        echo json_encode(['success' => true]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No ID provided']);
    exit;
}
?>
