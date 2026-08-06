<?php
/**
 * api_load_more_posts.php - AJAX API for loading more feed posts
 */
header('Content-Type: application/json');
session_start();
require_once 'config.php';

if (!isset($_SESSION['emp_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$offset = max(0, (int)($_GET['offset'] ?? 10));
$search_date = $_GET['search_date'] ?? date('Y-m-d');
$status_tab = $_GET['status_tab'] ?? 'all';
$crop_year = $_SESSION['crop_year'] ?? '';

$sql = "SELECT p.*, e.emp_name
        FROM posts p
        JOIN employee e ON p.emp_id = e.emp_id
        WHERE p.crop_year = :crop_year
        AND DATE(p.created_at) = :search_date";
if ($status_tab == 'pending')      { $sql .= " AND p.job_status = 'pending'"; }
elseif ($status_tab == 'success')  { $sql .= " AND p.job_status = 'success'"; }
$sql .= " ORDER BY p.created_at DESC LIMIT 11 OFFSET :offset";

try {
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':crop_year', $crop_year, PDO::PARAM_STR);
    $stmt->bindParam(':search_date', $search_date, PDO::PARAM_STR);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $posts = $stmt->fetchAll();

    $has_more = false;
    if (count($posts) > 10) {
        $has_more = true;
        array_pop($posts);
    }

    // Render using includes/feed_post_cards.php
    $is_ajax = true;
    ob_start();
    include 'includes/feed_post_cards.php';
    $html = ob_get_clean();

    echo json_encode([
        'success' => true,
        'html' => $html,
        'has_more' => $has_more
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}