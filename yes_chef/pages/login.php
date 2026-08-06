<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>yes_Chef — เข้าสู่ห้องครัว</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Noto+Sans+Thai:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <style>
    :root {
      --flame:   #F97316;
      --ember:   #EA580C;
      --cream:   #FFF7ED;
      --ash:     #78716C;
      --smoke:   #F5F5F4;
    }

    * { box-sizing: border-box; }

    body {
      font-family: 'Noto Sans Thai', sans-serif;
      background-color: var(--smoke);
      min-height: 100vh;
      overflow: hidden;
    }

    /* ── Animated Background ─────────────────────────────── */
    .bg-canvas {
      position: fixed; inset: 0; z-index: 0;
      background: #fff;
    }
    .bg-canvas::before {
      content: '';
      position: absolute; inset: 0;
      background:
        radial-gradient(ellipse 80% 60% at 110% 120%, rgba(249,115,22,.12) 0%, transparent 60%),
        radial-gradient(ellipse 60% 50% at -10% -10%, rgba(249,115,22,.07) 0%, transparent 60%);
    }

    /* floating dots */
    .dot {
      position: absolute;
      border-radius: 50%;
      background: var(--flame);
      opacity: .06;
      animation: float linear infinite;
    }
    @keyframes float {
      0%   { transform: translateY(0)   rotate(0deg); }
      100% { transform: translateY(-110vh) rotate(360deg); }
    }

    /* ── Card ────────────────────────────────────────────── */
    .card {
      position: relative; z-index: 10;
      background: #fff;
      border-radius: 24px;
      box-shadow:
        0 2px 4px rgba(0,0,0,.04),
        0 8px 32px rgba(249,115,22,.10),
        0 32px 64px rgba(0,0,0,.06);
    }

    /* ── Logo text ───────────────────────────────────────── */
    .logo-text {
      font-family: 'Playfair Display', serif;
      font-weight: 900;
      color: var(--flame);
      letter-spacing: -.5px;
    }

    /* ── Google button ───────────────────────────────────── */
    .btn-google {
      display: flex; align-items: center; justify-content: center; gap: 12px;
      width: 100%;
      padding: 14px 24px;
      border-radius: 14px;
      border: 2px solid #e5e7eb;
      background: #fff;
      font-family: 'Noto Sans Thai', sans-serif;
      font-size: 15px;
      font-weight: 500;
      color: #374151;
      cursor: pointer;
      transition: all .2s cubic-bezier(.4,0,.2,1);
      position: relative;
      overflow: hidden;
    }
    .btn-google::after {
      content: '';
      position: absolute; inset: 0;
      background: var(--flame);
      opacity: 0;
      transition: opacity .2s;
    }
    .btn-google:hover {
      border-color: var(--flame);
      color: var(--flame);
      box-shadow: 0 4px 20px rgba(249,115,22,.18);
      transform: translateY(-1px);
    }
    .btn-google:active { transform: translateY(0); }

    /* ── Divider chef hat icon ───────────────────────────── */
    .chef-divider {
      display: flex; align-items: center; gap: 12px;
      color: #d1d5db;
      font-size: 13px;
    }
    .chef-divider::before,
    .chef-divider::after {
      content: ''; flex: 1;
      height: 1px; background: #e5e7eb;
    }

    /* ── Error banner ────────────────────────────────────── */
    .error-banner {
      display: flex; align-items: center; gap: 10px;
      background: #FFF1F0;
      border: 1px solid #FECACA;
      border-radius: 12px;
      padding: 12px 16px;
      font-size: 13.5px;
      color: #DC2626;
      animation: slideDown .25s ease;
    }
    @keyframes slideDown {
      from { opacity: 0; transform: translateY(-8px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* ── Feature pills ───────────────────────────────────── */
    .pill {
      display: inline-flex; align-items: center; gap: 6px;
      background: var(--cream);
      border-radius: 99px;
      padding: 5px 14px;
      font-size: 12px;
      color: var(--ember);
      font-weight: 500;
    }

    /* ── Entrance animation ──────────────────────────────── */
    .animate-up {
      opacity: 0;
      transform: translateY(24px);
      animation: enterUp .5s cubic-bezier(.22,1,.36,1) forwards;
    }
    @keyframes enterUp {
      to { opacity: 1; transform: none; }
    }
  </style>
</head>
<body>

<!-- ── Animated background ── -->
<div class="bg-canvas" id="bgCanvas"></div>

<!-- ── Main Layout ─────────────────────────────────────────── -->
<div class="relative z-10 min-h-screen flex flex-col lg:flex-row">

  <!-- Left: Branding panel (desktop only) -->
  <div class="hidden lg:flex flex-col justify-between flex-1 px-16 py-14"
       style="background: linear-gradient(145deg, #fff 0%, #FFF7ED 100%);">

    <!-- Logo -->
    <div class="animate-up" style="animation-delay:.05s">
      <span class="logo-text text-5xl">yes_Chef</span>
      <p class="mt-2 text-sm" style="color:var(--ash)">ระบบจัดการครัวสำหรับมืออาชีพ</p>
    </div>

    <!-- Hero text -->
    <div class="animate-up" style="animation-delay:.15s">
      <h1 class="text-5xl font-black leading-tight" style="color:#1c1917; font-family:'Playfair Display',serif;">
        ครัวที่ดี<br/>
        <span style="color:var(--flame)">เริ่มต้น</span><br/>
        จากทีมที่ดี
      </h1>
      <p class="mt-5 text-base leading-relaxed" style="color:var(--ash); max-width:380px;">
        จัดการทีมครัว มอบสิทธิ์พ่อครัว ติดตามทุกการกระทำในครัวของคุณ
        ด้วยระบบที่ออกแบบมาสำหรับเชฟโดยเฉพาะ
      </p>

      <!-- Feature pills -->
      <div class="flex flex-wrap gap-2 mt-6">
        <span class="pill">🔐 Google Login</span>
        <span class="pill">👨‍🍳 Chef ID</span>
        <span class="pill">🍳 Kitchen Groups</span>
        <span class="pill">📋 Activity Logs</span>
      </div>
    </div>

    <!-- Footer note -->
    <p class="text-xs animate-up" style="color:#c4b9b0; animation-delay:.25s">
      © 2025 yes_Chef · All rights reserved
    </p>
  </div>

  <!-- Right: Login card -->
  <div class="flex items-center justify-center flex-1 p-6 lg:p-12">
    <div class="card w-full max-w-md p-8 lg:p-10 animate-up" style="animation-delay:.1s">

      <!-- Mobile logo -->
      <div class="lg:hidden text-center mb-8">
        <span class="logo-text text-4xl">yes_Chef</span>
        <p class="mt-1 text-xs" style="color:var(--ash)">ระบบจัดการครัวสำหรับมืออาชีพ</p>
      </div>

      <!-- Chef hat icon -->
      <div class="flex justify-center mb-6">
        <div class="w-20 h-20 rounded-2xl flex items-center justify-center text-4xl"
             style="background:var(--cream);">
          👨‍🍳
        </div>
      </div>

      <h2 class="text-2xl font-bold text-center mb-1" style="color:#1c1917; font-family:'Playfair Display',serif;">
        เข้าสู่ห้องครัว
      </h2>
      <p class="text-sm text-center mb-7" style="color:var(--ash)">
        ใช้บัญชี Gmail เพื่อเข้าสู่ระบบ yes_Chef
      </p>

      <!-- Error banners (PHP conditional) -->
      <?php if (!empty($_GET['error'])): ?>
        <?php $errMap = [
          'gmail_only'    => ['🚫', 'อนุญาตเฉพาะบัญชี @gmail.com เท่านั้น'],
          'google_denied' => ['⚠️', 'การเชื่อมต่อ Google ถูกยกเลิก'],
          'token_failed'  => ['⚠️', 'เกิดข้อผิดพลาดในการยืนยันตัวตน กรุณาลองใหม่'],
          'no_email'      => ['📧', 'ไม่สามารถดึงอีเมลจาก Google ได้'],
          'invalid_state' => ['🔒', 'Session หมดอายุ กรุณาลองเข้าสู่ระบบใหม่'],
        ]; ?>
        <div class="error-banner mb-5">
          <span><?= $errMap[$_GET['error']][0] ?? '⚠️' ?></span>
          <span><?= htmlspecialchars($errMap[$_GET['error']][1] ?? 'เกิดข้อผิดพลาด กรุณาลองใหม่') ?></span>
        </div>
      <?php endif; ?>

      <!-- Google Login Button -->
      <a href="/givewater/yes_chef/auth/google_login.php" style="text-decoration:none;">
        <button class="btn-google" type="button">
          <!-- Google G logo SVG -->
          <svg width="20" height="20" viewBox="0 0 48 48">
            <path fill="#FFC107" d="M43.6 20H24v8h11.3C33.7 33 29.3 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3 0 5.7 1.1 7.8 2.9l6-6C34.5 6.5 29.5 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20c11 0 20-8 20-20 0-1.3-.1-2.7-.4-4z"/>
            <path fill="#FF3D00" d="M6.3 14.7l7 5.1C15 16.1 19.2 13 24 13c3 0 5.7 1.1 7.8 2.9l6-6C34.5 6.5 29.5 4 24 4 16.3 4 9.7 8.4 6.3 14.7z"/>
            <path fill="#4CAF50" d="M24 44c5.3 0 10.2-1.9 13.9-5.1l-6.4-5.4C29.4 35.1 26.8 36 24 36c-5.3 0-9.7-3-11.3-7.4l-7 5.4C9.8 39.7 16.4 44 24 44z"/>
            <path fill="#1976D2" d="M43.6 20H24v8h11.3c-.9 2.6-2.5 4.8-4.7 6.4l6.4 5.4C40.5 36.4 44 30.6 44 24c0-1.3-.1-2.7-.4-4z"/>
          </svg>
          <span>เข้าสู่ระบบด้วย Google</span>
        </button>
      </a>

      <div class="chef-divider my-6">
        <span>yes_Chef ใช้เฉพาะ Gmail เท่านั้น</span>
      </div>

      <!-- Info note -->
      <div class="rounded-xl p-4 text-center" style="background:var(--cream);">
        <p class="text-xs leading-relaxed" style="color:#92400E;">
          🔒 ระบบตรวจสอบว่าอีเมลของคุณต้องเป็น <strong>@gmail.com</strong> เท่านั้น<br/>
          บัญชี Google Workspace หรืออีเมลอื่นจะไม่สามารถเข้าใช้งานได้
        </p>
      </div>

    </div>
  </div>
</div>

<script>
// ── Floating background dots ───────────────────────────────
(function() {
  const canvas = document.getElementById('bgCanvas');
  const count  = 18;
  for (let i = 0; i < count; i++) {
    const d = document.createElement('div');
    d.className = 'dot';
    const size = 20 + Math.random() * 120;
    d.style.cssText = `
      width:${size}px; height:${size}px;
      left:${Math.random() * 100}%;
      bottom:-${size}px;
      animation-duration:${12 + Math.random() * 20}s;
      animation-delay:-${Math.random() * 30}s;
    `;
    canvas.appendChild(d);
  }
})();
</script>
</body>
</html>
