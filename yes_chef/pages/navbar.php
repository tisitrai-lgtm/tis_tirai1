<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>YesChef — <?= $pageTitle ?? 'Dashboard' ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Noto+Sans+Thai:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <style>
    :root {
      --flame:  #F97316;
      --ember:  #EA580C;
      --cream:  #FFF7ED;
      --ash:    #78716C;
      --smoke:  #FAFAF9;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Noto Sans Thai', sans-serif; background: var(--smoke); min-height: 100vh; }

    /* ── NAVBAR ─────────────────────────────────────────── */
    .yc-nav {
      position: sticky; top: 0; z-index: 100;
      height: 60px;
      background: #fff;
      border-bottom: 1px solid #F3EDE8;
      display: flex; align-items: center;
      padding: 0 28px;
      gap: 0;
    }
    .yc-logo {
      font-family: 'Playfair Display', serif;
      font-weight: 900;
      font-size: 22px;
      color: var(--flame);
      letter-spacing: -.3px;
      text-decoration: none;
      flex-shrink: 0;
    }
    .yc-logo span { color: #1c1917; }

    .yc-nav-links {
      display: flex; align-items: center; gap: 4px;
      margin-left: 32px; flex: 1;
      list-style: none;
    }
    .yc-nav-links a {
      display: flex; align-items: center; gap: 6px;
      padding: 6px 12px; border-radius: 8px;
      font-size: 14px; font-weight: 500; color: var(--ash);
      text-decoration: none; transition: all .15s;
    }
    .yc-nav-links a:hover { background: var(--cream); color: var(--ember); }
    .yc-nav-links a.active { background: var(--cream); color: var(--flame); font-weight: 600; }

    /* ── User dropdown ──────────────────────────────────── */
    .yc-user-menu { position: relative; margin-left: auto; }
    .yc-user-btn {
      display: flex; align-items: center; gap: 8px;
      padding: 5px 10px 5px 5px;
      border-radius: 10px; border: 1px solid #F3EDE8;
      background: #fff; cursor: pointer;
      transition: all .15s;
    }
    .yc-user-btn:hover { border-color: #F9C59D; background: var(--cream); }
    .yc-avatar {
      width: 32px; height: 32px; border-radius: 8px;
      object-fit: cover; flex-shrink: 0;
    }
    .yc-user-name { font-size: 13.5px; font-weight: 600; color: #1c1917; }
    .yc-chef-tag { font-size: 11px; color: var(--ember); font-weight: 500; }
    .yc-chevron {
      width: 16px; height: 16px; color: var(--ash); flex-shrink: 0;
      transition: transform .2s;
    }
    .yc-user-menu.open .yc-chevron { transform: rotate(180deg); }

    /* Dropdown panel */
    .yc-dropdown {
      position: absolute; right: 0; top: calc(100% + 8px);
      width: 200px;
      background: #fff;
      border: 1px solid #F3EDE8;
      border-radius: 14px;
      box-shadow: 0 8px 32px rgba(0,0,0,.09);
      overflow: hidden;
      opacity: 0; transform: translateY(-6px) scale(.98);
      pointer-events: none;
      transition: all .18s cubic-bezier(.22,1,.36,1);
    }
    .yc-user-menu.open .yc-dropdown {
      opacity: 1; transform: none; pointer-events: all;
    }
    .yc-dropdown-header {
      padding: 12px 14px;
      border-bottom: 1px solid #F9F5F2;
    }
    .yc-dropdown-header p { font-size: 12px; color: var(--ash); }
    .yc-dropdown-header strong {
      display: block; font-size: 13.5px; font-weight: 600; color: #1c1917; margin-top: 2px;
    }
    .yc-dropdown-item {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 14px; font-size: 13.5px; font-weight: 500;
      color: #374151; text-decoration: none; cursor: pointer;
      border: none; background: none; width: 100%; text-align: left;
      transition: background .12s;
    }
    .yc-dropdown-item:hover { background: var(--cream); color: var(--ember); }
    .yc-dropdown-item.danger { color: #DC2626; }
    .yc-dropdown-item.danger:hover { background: #FFF1F0; }
    .yc-dropdown-divider { height: 1px; background: #F9F5F2; margin: 2px 0; }
    .yc-dropdown-item svg { width: 16px; height: 16px; flex-shrink: 0; }
  </style>
</head>
<body>

<?php
// ── Pull user from session / DB ──────────────────────────
session_start();
require_once __DIR__ . '/config/database.php';
if (empty($_SESSION['user_id'])) { header('Location: /pages/login.php'); exit; }
if (empty($_SESSION['is_setup_done'])) { header('Location: /pages/setup_nickname.php'); exit; }
$db     = getDB();
$userId = (int)$_SESSION['user_id'];
$user   = $db->query("SELECT * FROM users WHERE id = {$userId}")->fetch();
$avatarSrc = $user['avatar_url'] ?: 'https://ui-avatars.com/api/?name='.urlencode($user['nickname']).'&background=F97316&color=fff&size=80';
?>

<!-- ── NAVBAR ──────────────────────────────────────────── -->
<nav class="yc-nav">

  <!-- Logo -->
  <a href="/pages/dashboard.php" class="yc-logo">Yes<span>Chef</span></a>

  <!-- Nav links -->
  <ul class="yc-nav-links">
    <li>
      <a href="/pages/dashboard.php" class="<?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>">
        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
        Dashboard
      </a>
    </li>
    <li>
      <a href="/pages/my_kitchens.php" class="<?= ($activePage ?? '') === 'kitchens' ? 'active' : '' ?>">
        <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        ห้องครัวของฉัน
      </a>
    </li>
  </ul>

  <!-- User dropdown -->
  <div class="yc-user-menu" id="userMenu">
    <button class="yc-user-btn" onclick="toggleMenu()" aria-haspopup="true" aria-expanded="false" id="userBtn">
      <img src="<?= htmlspecialchars($avatarSrc) ?>" class="yc-avatar" alt="avatar" />
      <span>
        <span class="yc-user-name"><?= htmlspecialchars($user['nickname']) ?></span>
        <span class="yc-chef-tag">#<?= htmlspecialchars($user['chef_tag']) ?></span>
      </span>
      <svg class="yc-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
        <polyline points="6 9 12 15 18 9"/>
      </svg>
    </button>

    <!-- Dropdown -->
    <div class="yc-dropdown" id="dropdown" role="menu">
      <div class="yc-dropdown-header">
        <p>เข้าสู่ระบบในฐานะ</p>
        <strong><?= htmlspecialchars($user['nickname']) ?>#<?= htmlspecialchars($user['chef_tag']) ?></strong>
      </div>
      <a href="/pages/profile.php" class="yc-dropdown-item" role="menuitem">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
        โปรไฟล์เชฟ
      </a>
      <div class="yc-dropdown-divider"></div>
      <a href="/auth/logout.php" class="yc-dropdown-item danger" role="menuitem">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        ออกจากระบบ
      </a>
    </div>
  </div>

</nav>
<!-- ── END NAVBAR ──────────────────────────────────────── -->

<script>
function toggleMenu() {
  const menu = document.getElementById('userMenu');
  const btn  = document.getElementById('userBtn');
  const open = menu.classList.toggle('open');
  btn.setAttribute('aria-expanded', open);
}
document.addEventListener('click', e => {
  const menu = document.getElementById('userMenu');
  if (!menu.contains(e.target)) {
    menu.classList.remove('open');
    document.getElementById('userBtn').setAttribute('aria-expanded', 'false');
  }
});
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.getElementById('userMenu').classList.remove('open');
  }
});
</script>