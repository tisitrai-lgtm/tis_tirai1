<?php
// ============================================================
// yes_Chef — auth/google_callback.php
// รับ callback จาก Google OAuth 2.0
// ============================================================

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/google_oauth.php';
require_once __DIR__ . '/../includes/logger.php';
require_once __DIR__ . '/../includes/chef_tag.php';

// ── 1. ตรวจสอบ state (CSRF) ───────────────────────────────
if (empty($_GET['state']) || $_GET['state'] !== ($_SESSION['oauth_state'] ?? '')) {
    logAuth('login_failed', null, null, 'invalid_oauth_state');
    header('Location: ' . APP_URL . '/pages/login.php?error=invalid_state');
    exit;
}
unset($_SESSION['oauth_state']);

// ── 2. ตรวจสอบ error จาก Google ──────────────────────────
if (!empty($_GET['error'])) {
    logAuth('login_failed', null, null, 'google_error:' . htmlspecialchars($_GET['error']));
    header('Location: ' . APP_URL . '/pages/login.php?error=google_denied');
    exit;
}

// ── 3. แลก code เป็น access_token ────────────────────────
$tokenResp = httpPost(GOOGLE_TOKEN_URL, [
    'code'          => $_GET['code'],
    'client_id'     => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'grant_type'    => 'authorization_code',
]);

if (empty($tokenResp['access_token'])) {
    logAuth('login_failed', null, null, 'token_exchange_failed');
    header('Location: ' . APP_URL . '/pages/login.php?error=token_failed');
    exit;
}

// ── 4. ดึงข้อมูล User จาก Google ─────────────────────────
$gUser = httpGet(GOOGLE_USERINFO, $tokenResp['access_token']);
$email = strtolower(trim($gUser['email'] ?? ''));

if (empty($email)) {
    logAuth('login_failed', null, null, 'no_email_from_google');
    header('Location: ' . APP_URL . '/pages/login.php?error=no_email');
    exit;
}

// ── 5. บังคับ Gmail เท่านั้น ──────────────────────────────
if (!str_ends_with($email, '@gmail.com')) {
    logAuth('login_failed', null, $email, 'not_gmail_account');
    header('Location: ' . APP_URL . '/pages/login.php?error=gmail_only');
    exit;
}

// ── 6. หา / สร้าง User ในฐานข้อมูล ──────────────────────
$db   = getDB();
$stmt = $db->prepare("SELECT * FROM users WHERE google_id = :gid OR email = :email LIMIT 1");
$stmt->execute([':gid' => $gUser['sub'], ':email' => $email]);
$user = $stmt->fetch();

if (!$user) {
    // สร้าง User ใหม่ (ยังไม่มี nickname — is_setup_done = 0)
   $ins = $db->prepare("
    INSERT INTO users (google_id, email, nickname, chef_tag, avatar_url, is_profile_setup)
    VALUES (:gid, :email, '', '0000', :avatar, 0)
");
    $ins->execute([
        ':gid'    => $gUser['sub'],
        ':email'  => $email,
        ':avatar' => $gUser['picture'] ?? null,
    ]);
    $userId = (int)$db->lastInsertId();
    $user   = $db->query("SELECT * FROM users WHERE id = {$userId}")->fetch();
}

// ── 7. ตั้งค่า Session ────────────────────────────────────
$_SESSION['user_id']       = $user['id'];
$_SESSION['email']         = $user['email'];
$_SESSION['is_setup_done'] = (int)$user['is_profile_setup'];

logAuth('login_success', $user['id'], $email);

// ── 8. Redirect ───────────────────────────────────────────
if (!$user['is_profile_setup']) {
    header('Location: ' . APP_URL . '/pages/setup_nickname.php');
} else {
    header('Location: ' . APP_URL . '/pages/dashboard.php');
}
exit;

// ── Helpers ───────────────────────────────────────────────

function httpPost(string $url, array $data): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_TIMEOUT        => 10,
    ]);
    $body = curl_exec($ch);
    curl_close($ch);
    return json_decode($body, true) ?? [];
}

function httpGet(string $url, string $token): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$token}"],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $body = curl_exec($ch);
    curl_close($ch);
    return json_decode($body, true) ?? [];
}
