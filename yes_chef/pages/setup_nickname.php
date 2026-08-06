<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>yes_Chef — ตั้งชื่อเชฟของคุณ</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Noto+Sans+Thai:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <style>
    :root { --flame:#F97316; --ember:#EA580C; --cream:#FFF7ED; --ash:#78716C; }
    body { font-family:'Noto Sans Thai',sans-serif; background:var(--cream); min-height:100vh; }

    .card {
      background:#fff;
      border-radius:24px;
      box-shadow:0 4px 6px rgba(0,0,0,.04),0 20px 60px rgba(249,115,22,.10);
    }
    .logo-text { font-family:'Playfair Display',serif; font-weight:900; color:var(--flame); }

    .input-nick {
      width:100%; padding:14px 18px;
      border:2px solid #e5e7eb; border-radius:14px;
      font-family:'Noto Sans Thai',sans-serif; font-size:16px; color:#1c1917;
      outline:none; transition:border-color .2s, box-shadow .2s;
    }
    .input-nick:focus {
      border-color:var(--flame);
      box-shadow:0 0 0 4px rgba(249,115,22,.12);
    }

    .tag-preview {
      display:inline-flex; align-items:center; gap:4px;
      background:var(--cream); border-radius:99px;
      padding:4px 14px; font-size:14px; font-weight:600;
      color:var(--ember); transition:all .3s;
    }

    .btn-confirm {
      width:100%; padding:15px;
      background:var(--flame); color:#fff;
      border:none; border-radius:14px;
      font-family:'Noto Sans Thai',sans-serif; font-size:16px; font-weight:600;
      cursor:pointer; transition:all .2s;
    }
    .btn-confirm:hover { background:var(--ember); transform:translateY(-1px); box-shadow:0 8px 24px rgba(249,115,22,.35); }
    .btn-confirm:active { transform:none; }
    .btn-confirm:disabled { background:#d1d5db; cursor:not-allowed; transform:none; box-shadow:none; }

    .step-dot { width:8px; height:8px; border-radius:50%; }

    @keyframes enterUp {
      from { opacity:0; transform:translateY(20px); }
      to   { opacity:1; transform:none; }
    }
    .animate-up { opacity:0; animation:enterUp .45s cubic-bezier(.22,1,.36,1) forwards; }

    .spinner { animation:spin .7s linear infinite; }
    @keyframes spin { to { transform:rotate(360deg); } }
  </style>
</head>
<body>
<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: /pages/login.php');
    exit;
}
if (!empty($_SESSION['is_setup_done'])) {
    header('Location: /pages/dashboard.php');
    exit;
}
?>

<div class="min-h-screen flex items-center justify-center p-6">
  <div class="card w-full max-w-lg p-8 lg:p-10 animate-up">

    <!-- Progress steps -->
    <div class="flex items-center justify-center gap-2 mb-8">
      <div class="step-dot" style="background:var(--flame)"></div>
      <div style="width:40px;height:2px;background:var(--flame)"></div>
      <div class="step-dot" style="background:#e5e7eb"></div>
      <div style="width:40px;height:2px;background:#e5e7eb"></div>
      <div class="step-dot" style="background:#e5e7eb"></div>
    </div>

    <!-- Header -->
    <div class="text-center mb-8">
      <div class="text-5xl mb-4">🎽</div>
      <h1 class="text-2xl font-bold" style="font-family:'Playfair Display',serif;color:#1c1917;">
        ตั้งชื่อเล่นเชฟของคุณ
      </h1>
      <p class="mt-2 text-sm" style="color:var(--ash);">
        ชื่อเล่นนี้จะแสดงในห้องครัวของคุณ<br/>ระบบจะสุ่ม <strong>ID 4 หลัก</strong> ให้โดยอัตโนมัติ
      </p>
    </div>

    <!-- Form -->
    <div id="setupForm">

      <!-- Input -->
      <label class="block mb-2 text-sm font-medium" style="color:#374151;">
        ชื่อเล่นเชฟ
      </label>
      <input
        type="text"
        id="nicknameInput"
        class="input-nick"
        placeholder="เช่น เชฟสมชาย, Chef Mike"
        maxlength="40"
        autocomplete="off"
      />
      <p class="mt-1.5 text-xs" style="color:var(--ash);">2–40 ตัวอักษร · ไม่ต้องใส่ # หรือตัวเลข</p>

      <!-- Preview tag -->
      <div class="flex items-center justify-between mt-5 mb-1">
        <span class="text-sm font-medium" style="color:#374151;">ชื่อที่จะแสดงในระบบ</span>
      </div>
      <div class="rounded-xl p-4 flex items-center gap-3" style="background:var(--cream);">
        <div class="w-10 h-10 rounded-full flex items-center justify-center text-xl" style="background:white;">
          👨‍🍳
        </div>
        <div>
          <div id="previewName" class="font-semibold" style="color:#1c1917;">
            <span id="previewNick" style="color:#9ca3af;">ชื่อเล่น</span><span class="tag-preview ml-2" id="previewTag">#????</span>
          </div>
          <div class="text-xs mt-0.5" style="color:var(--ash);">ID จะถูกสุ่มเมื่อกดยืนยัน</div>
        </div>
      </div>

      <!-- Error message -->
      <div id="errorMsg" class="hidden mt-4 rounded-xl p-3 text-sm" style="background:#FFF1F0;color:#DC2626;border:1px solid #FECACA;"></div>

      <!-- Submit button -->
      <button class="btn-confirm mt-6" id="confirmBtn" disabled>
        <span id="btnText">ยืนยันชื่อเชฟ →</span>
        <svg id="btnSpinner" class="spinner hidden w-5 h-5 mx-auto" fill="none" viewBox="0 0 24 24">
          <circle cx="12" cy="12" r="10" stroke="white" stroke-width="3" stroke-dasharray="30 60"/>
        </svg>
      </button>

      <p class="text-center text-xs mt-4" style="color:#9ca3af;">
        ⚠️ ชื่อเล่นสามารถแก้ไขได้ภายหลัง แต่ ID 4 หลักจะเปลี่ยนด้วย
      </p>
    </div>

    <!-- Success state -->
    <div id="successState" class="hidden text-center py-4">
      <div class="text-5xl mb-4">🎉</div>
      <h2 class="text-xl font-bold mb-2" style="font-family:'Playfair Display',serif;color:#1c1917;">ยินดีต้อนรับเข้าครัว!</h2>
      <div class="tag-preview text-lg px-6 py-2 mx-auto mb-4" id="successName" style="font-size:18px;"></div>
      <p class="text-sm" style="color:var(--ash);">กำลังพาคุณไปยังแดชบอร์ด...</p>
    </div>

  </div>
</div>

<script>
const BASE = '/givewater/yes_chef';
const input      = document.getElementById('nicknameInput');
const previewNick = document.getElementById('previewNick');
const confirmBtn  = document.getElementById('confirmBtn');

// Live preview
input.addEventListener('input', () => {
  const val = input.value.replace(/#\d{1,4}$/, '').trim();
  if (val.length >= 2) {
    previewNick.textContent = val;
    previewNick.style.color = '#1c1917';
    confirmBtn.disabled = false;
  } else {
    previewNick.textContent = val || 'ชื่อเล่น';
    previewNick.style.color = '#9ca3af';
    confirmBtn.disabled = true;
  }
});

// Submit
confirmBtn.addEventListener('click', async () => {
  const nick = input.value.replace(/#\d{1,4}$/, '').trim();
  if (nick.length < 2) return;

  confirmBtn.disabled = true;
  document.getElementById('btnText').classList.add('hidden');
  document.getElementById('btnSpinner').classList.remove('hidden');
  document.getElementById('errorMsg').classList.add('hidden');

  try {
    const res = await fetch(`${BASE}/api/setup_nickname.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ nickname: nick }),
    });
    const data = await res.json();

    if (data.ok) {
      document.getElementById('setupForm').classList.add('hidden');
      document.getElementById('successState').classList.remove('hidden');
      document.getElementById('successName').textContent = data.display_name;
      setTimeout(() => { window.location.href = data.redirect; }, 1800);
    } else {
      showError(data.message || 'เกิดข้อผิดพลาด');
    }
  } catch {
    showError('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้');
  } finally {
    confirmBtn.disabled = false;
    document.getElementById('btnText').classList.remove('hidden');
    document.getElementById('btnSpinner').classList.add('hidden');
  }
});

function showError(msg) {
  const el = document.getElementById('errorMsg');
  el.textContent = '⚠️ ' + msg;
  el.classList.remove('hidden');
}
</script>
</body>
</html>
