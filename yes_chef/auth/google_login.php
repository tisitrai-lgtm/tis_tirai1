<?php
// ============================================================
// yes_Chef — auth/google_login.php
// สร้าง OAuth URL + state แล้ว redirect ไป Google
// ============================================================

session_start();
require_once __DIR__ . '/../config/google_oauth.php';

// สร้าง state แบบ random เพื่อป้องกัน CSRF
$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

$params = http_build_query([
    'client_id'     => GOOGLE_CLIENT_ID,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope'         => 'openid email profile',
    'state'         => $state,
    'access_type'   => 'online',
    'prompt'        => 'select_account',
]);

header('Location: ' . GOOGLE_AUTH_URL . '?' . $params);
exit;
