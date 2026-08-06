<?php
// ============================================================
// yes_Chef — api/setup_nickname.php
// POST handler: บันทึก nickname + สุ่ม chef_tag
// ============================================================

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/google_oauth.php';  // ← เพิ่มบรรทัดนี้
require_once __DIR__ . '/../includes/chef_tag.php';
require_once __DIR__ . '/../includes/logger.php';

// ── Auth guard ────────────────────────────────────────────
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed']);
    exit;
}

$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$raw      = $body['nickname'] ?? '';
$nickname = sanitizeNickname($raw);

// ── Validation ────────────────────────────────────────────
if (mb_strlen($nickname) < 2) {
    echo json_encode(['ok' => false, 'message' => 'ชื่อเล่นต้องมีอย่างน้อย 2 ตัวอักษร']);
    exit;
}
if (mb_strlen($nickname) > 40) {
    echo json_encode(['ok' => false, 'message' => 'ชื่อเล่นต้องไม่เกิน 40 ตัวอักษร']);
    exit;
}

// ── สุ่ม tag ──────────────────────────────────────────────
try {
    $tag = generateChefTag($nickname);
} catch (RuntimeException $e) {
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
    exit;
}

// ── บันทึกลง DB ───────────────────────────────────────────
$userId = (int)$_SESSION['user_id'];
$db     = getDB();

try {
    $stmt = $db->prepare("
    UPDATE users
    SET nickname = :nick, chef_tag = :tag, is_profile_setup = 1
    WHERE id = :id AND is_profile_setup = 0
");
    $stmt->execute([':nick' => $nickname, ':tag' => $tag, ':id' => $userId]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['ok' => false, 'message' => 'บัญชีนี้ตั้งชื่อเล่นไปแล้ว']);
        exit;
    }

    $_SESSION['is_setup_done'] = 1;
    $_SESSION['display_name']  = "{$nickname}#{$tag}";

    logProfileChange($userId, 'nickname', null, "{$nickname}#{$tag}");

    echo json_encode([
        'ok'           => true,
        'display_name' => "{$nickname}#{$tag}",
        'redirect' => APP_URL . '/pages/dashboard.php',
    ]);

} catch (Throwable $e) {
    error_log('[yes_Chef][setup_nickname] ' . $e->getMessage());
    echo json_encode(['ok' => false, 'message' => 'เกิดข้อผิดพลาด กรุณาลองใหม่']);
}
