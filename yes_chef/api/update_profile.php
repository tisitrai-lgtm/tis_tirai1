<?php
// ============================================================
// yes_Chef — api/update_profile.php
// POST handler: แก้ไข nickname หรืออัปโหลด avatar
// ============================================================

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/chef_tag.php';
require_once __DIR__ . '/../includes/logger.php';

// ── Auth guard ────────────────────────────────────────────
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$db     = getDB();

// ดึงข้อมูล user ปัจจุบัน
$user = $db->query("SELECT * FROM users WHERE id = {$userId}")->fetch();
if (!$user) {
    echo json_encode(['ok' => false, 'message' => 'User not found']);
    exit;
}

$changed = [];

// ── อัปเดต Nickname ───────────────────────────────────────
if (!empty($_POST['nickname'])) {
    $newNick = sanitizeNickname($_POST['nickname']);

    if (mb_strlen($newNick) < 2) {
        echo json_encode(['ok' => false, 'message' => 'ชื่อเล่นต้องมีอย่างน้อย 2 ตัวอักษร']);
        exit;
    }

    // ถ้าชื่อเล่นเปลี่ยน → สุ่ม tag ใหม่
    if ($newNick !== $user['nickname']) {
        try {
            $newTag = generateChefTag($newNick);
        } catch (RuntimeException $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
            exit;
        }

        $oldDisplay = "{$user['nickname']}#{$user['chef_tag']}";
        $newDisplay = "{$newNick}#{$newTag}";

        $stmt = $db->prepare("UPDATE users SET nickname = :n, chef_tag = :t WHERE id = :id");
        $stmt->execute([':n' => $newNick, ':t' => $newTag, ':id' => $userId]);

        logProfileChange($userId, 'nickname', $oldDisplay, $newDisplay);
        $changed['display_name'] = $newDisplay;
    }
}

// ── อัปโหลด Avatar ────────────────────────────────────────
if (!empty($_FILES['avatar']['tmp_name'])) {
    $file    = $_FILES['avatar'];
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $mime    = mime_content_type($file['tmp_name']);

    if (!in_array($mime, $allowed, true)) {
        echo json_encode(['ok' => false, 'message' => 'ไฟล์ต้องเป็น JPG, PNG, WEBP หรือ GIF']);
        exit;
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        echo json_encode(['ok' => false, 'message' => 'ไฟล์ต้องมีขนาดไม่เกิน 2MB']);
        exit;
    }

    $ext     = match($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
        default      => 'jpg',
    };
    $newFile = "avatar_{$userId}_" . time() . ".{$ext}";
    $dest    = __DIR__ . '/../assets/uploads/avatars/' . $newFile;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        $newUrl = '/assets/uploads/avatars/' . $newFile;

        // ลบไฟล์เก่า (ถ้ามีและไม่ใช่ URL ภายนอก)
        $oldUrl = $user['avatar_url'];
        if ($oldUrl && str_starts_with($oldUrl, '/assets/')) {
            $oldPath = __DIR__ . '/..' . $oldUrl;
            if (file_exists($oldPath)) @unlink($oldPath);
        }

        $stmt = $db->prepare("UPDATE users SET avatar_url = :url WHERE id = :id");
        $stmt->execute([':url' => $newUrl, ':id' => $userId]);

        logProfileChange($userId, 'avatar_url', $oldUrl, $newUrl);
        $changed['avatar_url'] = $newUrl;
    } else {
        echo json_encode(['ok' => false, 'message' => 'อัปโหลดรูปล้มเหลว']);
        exit;
    }
}

if (empty($changed)) {
    echo json_encode(['ok' => false, 'message' => 'ไม่มีการเปลี่ยนแปลง']);
    exit;
}

echo json_encode(['ok' => true, 'changes' => $changed]);
