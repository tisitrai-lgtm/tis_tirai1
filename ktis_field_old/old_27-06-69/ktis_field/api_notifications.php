<?php
/**
 * api_notifications.php - JSON API endpoint for fetching notifications (AJAX Polling)
 */
header('Content-Type: application/json');
session_start();
require_once 'config.php';

if (!isset($_SESSION['emp_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$my_emp_id = $_SESSION['emp_id'];
$response = [
    'success' => true,
    'noti_count' => 0,
    'notifications' => []
];

try {
    // Count unread
    $stmt_count = $conn->prepare("SELECT COUNT(*) AS unread FROM notifications WHERE is_read = 0 AND emp_id = :emp_id");
    $stmt_count->execute([':emp_id' => $my_emp_id]);
    $response['noti_count'] = (int)$stmt_count->fetch()['unread'];

    // List recent 20 notifications
    $stmt_list = $conn->prepare(
        "SELECT n.*, p.problem_detail 
         FROM notifications n
         LEFT JOIN posts p ON n.post_id = p.post_id
         WHERE n.emp_id = :emp_id 
         ORDER BY n.noti_id DESC LIMIT 20"
    );
    $stmt_list->execute([':emp_id' => $my_emp_id]);
    $list = $stmt_list->fetchAll(PDO::FETCH_ASSOC);

    // Format fields (e.g., dates) for JSON output
    foreach ($list as $noti) {
        $response['notifications'][] = [
            'noti_id' => (int)$noti['noti_id'],
            'post_id' => $noti['post_id'] !== null ? (int)$noti['post_id'] : null,
            'noti_text' => $noti['noti_text'],
            'is_read' => (int)$noti['is_read'],
            'problem_detail' => $noti['problem_detail'] ?? '',
            'created_at' => date('d/m/Y H:i', strtotime($noti['created_at'])) . ' น.'
        ];
    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
