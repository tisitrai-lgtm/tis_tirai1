<?php
// ============================================================
// yes_Chef — api/kitchen_permission.php
// POST: Head Chef อนุมัติ / เปลี่ยน role / ระงับ สมาชิก
// ============================================================

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/logger.php';

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
    exit;
}

$body      = json_decode(file_get_contents('php://input'), true) ?? [];
$action    = $body['action']     ?? '';   // 'approve' | 'change_role' | 'suspend' | 'remove'
$kitchenId = (int)($body['kitchen_id'] ?? 0);
$targetId  = (int)($body['user_id']    ?? 0);
$newRole   = $body['role']       ?? null; // สำหรับ change_role

$actorId = (int)$_SESSION['user_id'];
$db      = getDB();

// ── ตรวจสอบว่า actor เป็น Head Chef ของ kitchen นี้จริง ───
$kitchenStmt = $db->prepare("SELECT * FROM kitchens WHERE id = :kid AND owner_id = :oid LIMIT 1");
$kitchenStmt->execute([':kid' => $kitchenId, ':oid' => $actorId]);
$kitchen = $kitchenStmt->fetch();

if (!$kitchen) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'คุณไม่ใช่หัวหน้าเชฟของห้องครัวนี้']);
    exit;
}

// ── ดึงสถานะปัจจุบันของ target ────────────────────────────
$memberStmt = $db->prepare("
    SELECT * FROM kitchen_members WHERE kitchen_id = :kid AND user_id = :uid LIMIT 1
");
$memberStmt->execute([':kid' => $kitchenId, ':uid' => $targetId]);
$member = $memberStmt->fetch();

if (!$member) {
    echo json_encode(['ok' => false, 'message' => 'ไม่พบสมาชิกนี้ในห้องครัว']);
    exit;
}

$oldStatus = $member['status'];
$oldRole   = $member['role'];

$allowedActions = ['approve', 'change_role', 'suspend', 'remove'];
if (!in_array($action, $allowedActions, true)) {
    echo json_encode(['ok' => false, 'message' => 'action ไม่ถูกต้อง']);
    exit;
}

$allowedRoles = ['head_chef', 'sous_chef', 'cook', 'trainee'];

// ── ดำเนินการ ──────────────────────────────────────────────
try {
    $db->beginTransaction();

    switch ($action) {
        case 'approve':
            $upd = $db->prepare("
                UPDATE kitchen_members
                SET status = 'active', approved_at = NOW(), approved_by = :actor
                WHERE kitchen_id = :kid AND user_id = :uid
            ");
            $upd->execute([':actor' => $actorId, ':kid' => $kitchenId, ':uid' => $targetId]);
            logPermission($kitchenId, $actorId, $targetId, 'approve', [
                'old_status' => $oldStatus, 'new_status' => 'active',
            ]);
            break;

        case 'change_role':
            if (!in_array($newRole, $allowedRoles, true)) {
                throw new InvalidArgumentException('role ไม่ถูกต้อง');
            }
            $upd = $db->prepare("
                UPDATE kitchen_members SET role = :role
                WHERE kitchen_id = :kid AND user_id = :uid
            ");
            $upd->execute([':role' => $newRole, ':kid' => $kitchenId, ':uid' => $targetId]);
            logPermission($kitchenId, $actorId, $targetId, 'change_role', [
                'old_role' => $oldRole, 'new_role' => $newRole,
            ]);
            break;

        case 'suspend':
            $upd = $db->prepare("
                UPDATE kitchen_members SET status = 'suspended'
                WHERE kitchen_id = :kid AND user_id = :uid
            ");
            $upd->execute([':kid' => $kitchenId, ':uid' => $targetId]);
            logPermission($kitchenId, $actorId, $targetId, 'suspend', [
                'old_status' => $oldStatus, 'new_status' => 'suspended',
            ]);
            break;

        case 'remove':
            $del = $db->prepare("
                DELETE FROM kitchen_members WHERE kitchen_id = :kid AND user_id = :uid
            ");
            $del->execute([':kid' => $kitchenId, ':uid' => $targetId]);
            logPermission($kitchenId, $actorId, $targetId, 'remove', [
                'old_status' => $oldStatus, 'old_role' => $oldRole,
            ]);
            break;
    }

    $db->commit();
    echo json_encode(['ok' => true, 'action' => $action]);

} catch (Throwable $e) {
    $db->rollBack();
    error_log('[yes_Chef][kitchen_permission] ' . $e->getMessage());
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}
