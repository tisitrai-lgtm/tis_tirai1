<?php
// ============================================================
// yes_Chef — includes/logger.php
// ระบบบันทึก Log ทุกประเภทลง MySQL
// ============================================================

require_once __DIR__ . '/../config/database.php';

/**
 * ดึง IP จริงของผู้ใช้ (รองรับ Proxy/CDN)
 */
function getClientIP(): string {
    $keys = ['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR'];
    foreach ($keys as $k) {
        if (!empty($_SERVER[$k])) {
            $ip = trim(explode(',', $_SERVER[$k])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '0.0.0.0';
}

// ------------------------------------------------------------
// AUTH LOG
// ------------------------------------------------------------

/**
 * บันทึก Log การ Login/Logout
 *
 * @param string      $action  'login_success' | 'login_failed' | 'logout'
 * @param int|null    $userId  user.id (null ถ้า login ล้มเหลว)
 * @param string|null $email   email ที่พยายาม login
 * @param string|null $note    เหตุผล เช่น "not_gmail_account"
 */
function logAuth(string $action, ?int $userId = null, ?string $email = null, ?string $note = null): void {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO auth_logs (user_id, email_attempt, action, ip_address, user_agent, note)
            VALUES (:uid, :email, :action, :ip, :ua, :note)
        ");
        $stmt->execute([
            ':uid'    => $userId,
            ':email'  => $email,
            ':action' => $action,
            ':ip'     => getClientIP(),
            ':ua'     => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            ':note'   => $note,
        ]);
    } catch (Throwable $e) {
        error_log('[yes_Chef][logAuth] ' . $e->getMessage());
    }
}

// ------------------------------------------------------------
// PROFILE CHANGE LOG
// ------------------------------------------------------------

/**
 * บันทึก Log เมื่อแก้ไขข้อมูล Profile
 *
 * @param int    $userId
 * @param string $field     ชื่อ field ที่เปลี่ยน เช่น 'nickname', 'avatar_url'
 * @param mixed  $oldValue
 * @param mixed  $newValue
 */
function logProfileChange(int $userId, string $field, $oldValue, $newValue): void {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO profile_change_logs (user_id, field_changed, old_value, new_value, ip_address)
            VALUES (:uid, :field, :old, :new, :ip)
        ");
        $stmt->execute([
            ':uid'   => $userId,
            ':field' => $field,
            ':old'   => $oldValue,
            ':new'   => $newValue,
            ':ip'    => getClientIP(),
        ]);
    } catch (Throwable $e) {
        error_log('[yes_Chef][logProfileChange] ' . $e->getMessage());
    }
}

// ------------------------------------------------------------
// PERMISSION LOG
// ------------------------------------------------------------

/**
 * บันทึก Log เมื่อ Head Chef เปลี่ยนสิทธิ์สมาชิก
 *
 * @param int    $kitchenId
 * @param int    $actorId     Head Chef ที่กระทำ
 * @param int    $targetId    สมาชิกที่ถูกเปลี่ยน
 * @param string $action      'approve' | 'suspend' | 'change_role' | 'remove'
 * @param array  $changes     ['old_status'=>'...','new_status'=>'...','old_role'=>'...','new_role'=>'...']
 */
function logPermission(int $kitchenId, int $actorId, int $targetId, string $action, array $changes = []): void {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO permission_logs
                (kitchen_id, actor_id, target_id, action, old_status, new_status, old_role, new_role, ip_address)
            VALUES
                (:kid, :actor, :target, :action, :os, :ns, :or, :nr, :ip)
        ");
        $stmt->execute([
            ':kid'    => $kitchenId,
            ':actor'  => $actorId,
            ':target' => $targetId,
            ':action' => $action,
            ':os'     => $changes['old_status'] ?? null,
            ':ns'     => $changes['new_status'] ?? null,
            ':or'     => $changes['old_role']   ?? null,
            ':nr'     => $changes['new_role']   ?? null,
            ':ip'     => getClientIP(),
        ]);
    } catch (Throwable $e) {
        error_log('[yes_Chef][logPermission] ' . $e->getMessage());
    }
}
