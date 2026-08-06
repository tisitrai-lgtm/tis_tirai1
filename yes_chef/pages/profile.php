<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>yes_Chef — ตั้งค่าโปรไฟล์</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Noto+Sans+Thai:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <style>
    :root { --flame:#F97316; --ember:#EA580C; --cream:#FFF7ED; --ash:#78716C; --smoke:#F5F5F4; }
    body { font-family:'Noto Sans Thai',sans-serif; background:var(--smoke); min-height:100vh; }

    /* Navbar */
    .navbar {
      background:#fff;
      border-bottom:1px solid #f0ebe6;
      position:sticky; top:0; z-index:50;
    }
    .logo-text { font-family:'Playfair Display',serif; font-weight:900; color:var(--flame); }

    /* Cards */
    .card {
      background:#fff;
      border-radius:20px;
      box-shadow:0 2px 4px rgba(0,0,0,.04), 0 8px 32px rgba(0,0,0,.05);
    }
    .section-label {
      font-size:11px; font-weight:600; letter-spacing:.08em;
      text-transform:uppercase; color:#9ca3af;
      padding-bottom:12px; border-bottom:1px solid #f3f4f6; margin-bottom:20px;
    }

    /* Avatar upload */
    .avatar-wrap {
      position:relative; width:96px; height:96px;
      border-radius:24px; overflow:hidden;
      box-shadow:0 4px 16px rgba(249,115,22,.15);
      cursor:pointer;
    }
    .avatar-wrap img { width:100%; height:100%; object-fit:cover; }
    .avatar-overlay {
      position:absolute; inset:0;
      background:rgba(249,115,22,.75);
      display:flex; align-items:center; justify-content:center;
      opacity:0; transition:opacity .2s;
      color:#fff; font-size:22px;
    }
    .avatar-wrap:hover .avatar-overlay { opacity:1; }

    /* Form inputs */
    .input-field {
      width:100%; padding:12px 16px;
      border:2px solid #f3f4f6; border-radius:12px;
      font-family:'Noto Sans Thai',sans-serif; font-size:15px; color:#1c1917;
      outline:none; transition:border-color .2s, box-shadow .2s;
      background:#fff;
    }
    .input-field:focus { border-color:var(--flame); box-shadow:0 0 0 3px rgba(249,115,22,.10); }
    .input-field:disabled { background:#f9fafb; color:#9ca3af; cursor:not-allowed; }
    .input-field.readonly { background:#f9fafb; }

    /* Tag badge */
    .tag-badge {
      display:inline-flex; align-items:center; gap:4px;
      background:var(--cream); border-radius:99px;
      padding:4px 12px; font-size:13px; font-weight:600; color:var(--ember);
    }

    /* Buttons */
    .btn-primary {
      padding:12px 28px;
      background:var(--flame); color:#fff;
      border:none; border-radius:12px;
      font-family:'Noto Sans Thai',sans-serif; font-size:15px; font-weight:600;
      cursor:pointer; transition:all .2s;
    }
    .btn-primary:hover { background:var(--ember); transform:translateY(-1px); box-shadow:0 6px 20px rgba(249,115,22,.30); }
    .btn-primary:disabled { background:#d1d5db; cursor:not-allowed; transform:none; box-shadow:none; }
    .btn-ghost {
      padding:12px 20px; background:transparent; color:var(--ash);
      border:1px solid #e5e7eb; border-radius:12px;
      font-family:'Noto Sans Thai',sans-serif; font-size:15px; cursor:pointer; transition:all .2s;
    }
    .btn-ghost:hover { border-color:var(--flame); color:var(--flame); background:var(--cream); }

    /* Toast */
    .toast {
      position:fixed; bottom:24px; right:24px; z-index:999;
      display:flex; align-items:center; gap:10px;
      padding:14px 20px;
      border-radius:14px;
      font-size:14px; font-weight:500;
      box-shadow:0 8px 32px rgba(0,0,0,.12);
      transform:translateY(80px); opacity:0;
      transition:all .35s cubic-bezier(.22,1,.36,1);
    }
    .toast.show { transform:none; opacity:1; }
    .toast.success { background:#fff; border-left:4px solid #22C55E; color:#166534; }
    .toast.error   { background:#fff; border-left:4px solid #EF4444; color:#991B1B; }

    /* Log table */
    .log-table { border-collapse:collapse; width:100%; }
    .log-table th {
      text-align:left; padding:10px 16px;
      font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.06em;
      color:#9ca3af; background:#fafafa; border-bottom:1px solid #f3f4f6;
    }
    .log-table td { padding:12px 16px; font-size:13px; border-bottom:1px solid #f9fafb; color:#374151; }
    .log-table tr:last-child td { border-bottom:none; }
    .log-table tr:hover td { background:#fffbf7; }

    @keyframes enterUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:none} }
    .animate-up { opacity:0; animation:enterUp .4s cubic-bezier(.22,1,.36,1) forwards; }
  </style>
</head>
<body>
<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/logger.php';

// ── Auth guard ──────────────────────────────────────────────
if (empty($_SESSION['user_id'])) { header('Location: /pages/login.php'); exit; }
if (empty($_SESSION['is_setup_done'])) { header('Location: /pages/setup_nickname.php'); exit; }

$db     = getDB();
$userId = (int)$_SESSION['user_id'];
$user   = $db->query("SELECT * FROM users WHERE id = {$userId}")->fetch();

// ── ดึง Profile change logs ────────────────────────────────
$logs = $db->prepare("
    SELECT field_changed, old_value, new_value, ip_address, created_at
    FROM profile_change_logs
    WHERE user_id = :id
    ORDER BY created_at DESC LIMIT 10
");
$logs->execute([':id' => $userId]);
$logRows = $logs->fetchAll();
?>

<!-- ── Navbar ──────────────────────────────────────────────── -->
<nav class="navbar px-6 py-4">
  <div class="max-w-5xl mx-auto flex items-center justify-between">
    <a href="/pages/dashboard.php" style="text-decoration:none;" class="logo-text text-2xl">yes_Chef</a>
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 rounded-xl overflow-hidden">
        <img src="<?= htmlspecialchars($user['avatar_url'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($user['nickname']) . '&background=F97316&color=fff') ?>"
             alt="avatar" style="width:100%;height:100%;object-fit:cover;" />
      </div>
      <span class="text-sm font-medium" style="color:#374151;"><?= htmlspecialchars($user['nickname']) ?><span class="tag-badge ml-1">#<?= htmlspecialchars($user['chef_tag']) ?></span></span>
    </div>
  </div>
</nav>

<!-- ── Page body ───────────────────────────────────────────── -->
<div class="max-w-5xl mx-auto px-4 lg:px-6 py-8">

  <!-- Page title -->
  <div class="mb-8 animate-up" style="animation-delay:.05s">
    <h1 class="text-3xl font-black" style="font-family:'Playfair Display',serif; color:#1c1917;">ตั้งค่าโปรไฟล์</h1>
    <p class="mt-1 text-sm" style="color:var(--ash);">จัดการข้อมูลส่วนตัวและรูปโปรไฟล์เชฟของคุณ</p>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- ── Left: Avatar card ─────────────────────────── -->
    <div class="lg:col-span-1 animate-up" style="animation-delay:.1s">
      <div class="card p-6">
        <div class="section-label">รูปโปรไฟล์</div>

        <div class="flex flex-col items-center gap-4">
          <!-- Avatar -->
          <div class="avatar-wrap" id="avatarWrap" onclick="document.getElementById('avatarInput').click()">
            <img src="<?= htmlspecialchars($user['avatar_url'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($user['nickname']) . '&background=F97316&color=fff&size=200') ?>"
                 alt="avatar" id="avatarImg" />
            <div class="avatar-overlay">📷</div>
          </div>
          <input type="file" id="avatarInput" accept="image/*" class="hidden" />

          <div class="text-center">
            <p class="font-semibold text-base" style="color:#1c1917;"><?= htmlspecialchars($user['nickname']) ?></p>
            <span class="tag-badge mt-1">#<?= htmlspecialchars($user['chef_tag']) ?></span>
          </div>

          <button class="btn-ghost w-full text-sm" onclick="document.getElementById('avatarInput').click()">
            📷 เปลี่ยนรูปโปรไฟล์
          </button>
          <p class="text-xs text-center" style="color:#9ca3af;">JPG, PNG, WEBP · ไม่เกิน 2MB</p>
        </div>

        <!-- Chef info locked -->
        <div class="mt-6 rounded-xl p-4" style="background:#f9fafb; border:1px dashed #e5e7eb;">
          <p class="text-xs font-semibold mb-3" style="color:#9ca3af;">🔒 ข้อมูลที่แก้ไขไม่ได้</p>
          <div class="space-y-2">
            <div>
              <p class="text-xs" style="color:#9ca3af;">อีเมล</p>
              <p class="text-sm font-medium" style="color:#374151;"><?= htmlspecialchars($user['email']) ?></p>
            </div>
            <div>
              <p class="text-xs" style="color:#9ca3af;">Chef ID</p>
              <p class="text-sm font-medium" style="color:var(--ember);">#<?= htmlspecialchars($user['chef_tag']) ?></p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Right: Edit form ──────────────────────────── -->
    <div class="lg:col-span-2 space-y-6">

      <!-- Profile form -->
      <div class="card p-6 animate-up" style="animation-delay:.15s">
        <div class="section-label">ข้อมูลส่วนตัว</div>

        <div class="space-y-5" id="profileForm">

          <!-- Nickname -->
          <div>
            <label class="block text-sm font-medium mb-2" style="color:#374151;">ชื่อเล่นเชฟ</label>
            <input type="text" id="nicknameInput" class="input-field"
                   value="<?= htmlspecialchars($user['nickname']) ?>" maxlength="40"
                   placeholder="ชื่อเล่นของคุณ" />
            <p class="mt-1.5 text-xs" style="color:var(--ash);">⚠️ หากเปลี่ยนชื่อเล่น เลข ID 4 หลักจะถูกสุ่มใหม่</p>
          </div>

          <!-- Email (locked) -->
          <div>
            <label class="block text-sm font-medium mb-2" style="color:#374151;">อีเมล
              <span class="ml-2 text-xs px-2 py-0.5 rounded-full" style="background:#f3f4f6;color:#9ca3af;">แก้ไขไม่ได้</span>
            </label>
            <input type="text" class="input-field readonly" value="<?= htmlspecialchars($user['email']) ?>" disabled />
          </div>

          <!-- Chef tag (locked) -->
          <div>
            <label class="block text-sm font-medium mb-2" style="color:#374151;">Chef ID Tag
              <span class="ml-2 text-xs px-2 py-0.5 rounded-full" style="background:#f3f4f6;color:#9ca3af;">แก้ไขไม่ได้</span>
            </label>
            <div class="input-field readonly flex items-center gap-2" style="background:#f9fafb;">
              <span class="tag-badge" id="currentTag">#<?= htmlspecialchars($user['chef_tag']) ?></span>
              <span class="text-sm" style="color:#9ca3af;">สุ่มโดยระบบอัตโนมัติ</span>
            </div>
          </div>

          <!-- Action buttons -->
          <div class="flex items-center gap-3 pt-2">
            <button class="btn-primary" id="saveBtn">บันทึกการเปลี่ยนแปลง</button>
            <button class="btn-ghost" id="resetBtn">ยกเลิก</button>
          </div>
        </div>
      </div>

      <!-- Change history -->
      <div class="card p-6 animate-up" style="animation-delay:.2s">
        <div class="section-label">ประวัติการแก้ไข (10 รายการล่าสุด)</div>

        <?php if (empty($logRows)): ?>
          <div class="text-center py-8" style="color:#9ca3af;">
            <div class="text-3xl mb-2">📋</div>
            <p class="text-sm">ยังไม่มีประวัติการแก้ไข</p>
          </div>
        <?php else: ?>
          <div style="overflow-x:auto;">
            <table class="log-table">
              <thead>
                <tr>
                  <th>ฟิลด์</th>
                  <th>ค่าเก่า</th>
                  <th>ค่าใหม่</th>
                  <th>IP</th>
                  <th>เวลา</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($logRows as $row): ?>
                  <tr>
                    <td>
                      <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium"
                            style="background:var(--cream);color:var(--ember);">
                        <?= htmlspecialchars($row['field_changed']) ?>
                      </span>
                    </td>
                    <td style="color:#9ca3af;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                      <?= $row['old_value'] ? htmlspecialchars($row['old_value']) : '—' ?>
                    </td>
                    <td style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                      <?= htmlspecialchars($row['new_value'] ?? '—') ?>
                    </td>
                    <td style="font-family:monospace;font-size:12px;color:#9ca3af;">
                      <?= htmlspecialchars($row['ip_address']) ?>
                    </td>
                    <td style="white-space:nowrap;color:#9ca3af;font-size:12px;">
                      <?= date('d/m/Y H:i', strtotime($row['created_at'])) ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<!-- Toast -->
<div class="toast" id="toast">
  <span id="toastIcon"></span>
  <span id="toastMsg"></span>
</div>

<script>
const originalNick = <?= json_encode($user['nickname']) ?>;
const nickInput    = document.getElementById('nicknameInput');
const saveBtn      = document.getElementById('saveBtn');
const resetBtn     = document.getElementById('resetBtn');

// ── Avatar preview ──────────────────────────────────────────
let pendingAvatar = null;
document.getElementById('avatarInput').addEventListener('change', e => {
  const file = e.target.files[0];
  if (!file) return;
  if (file.size > 2 * 1024 * 1024) { showToast('ไฟล์ต้องมีขนาดไม่เกิน 2MB', 'error'); return; }
  pendingAvatar = file;
  const url = URL.createObjectURL(file);
  document.getElementById('avatarImg').src = url;
  showToast('เลือกรูปแล้ว กด "บันทึก" เพื่อยืนยัน', 'success');
});

// ── Save ────────────────────────────────────────────────────
saveBtn.addEventListener('click', async () => {
  const newNick = nickInput.value.replace(/#\d{1,4}$/, '').trim();
  if (newNick.length < 2) { showToast('ชื่อเล่นต้องมีอย่างน้อย 2 ตัวอักษร', 'error'); return; }

  const noChange = newNick === originalNick && !pendingAvatar;
  if (noChange) { showToast('ไม่มีการเปลี่ยนแปลง', 'error'); return; }

  saveBtn.disabled = true;
  saveBtn.textContent = 'กำลังบันทึก...';

  const fd = new FormData();
  if (newNick !== originalNick) fd.append('nickname', newNick);
  if (pendingAvatar) fd.append('avatar', pendingAvatar);

  try {
    const res  = await fetch('/api/update_profile.php', { method:'POST', body: fd });
    const data = await res.json();

    if (data.ok) {
      if (data.changes?.display_name) {
        const parts = data.changes.display_name.split('#');
        document.getElementById('currentTag').textContent = '#' + parts[1];
      }
      pendingAvatar = null;
      showToast('บันทึกข้อมูลสำเร็จ! 🎉', 'success');
      setTimeout(() => location.reload(), 1200);
    } else {
      showToast(data.message || 'เกิดข้อผิดพลาด', 'error');
    }
  } catch {
    showToast('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
  } finally {
    saveBtn.disabled = false;
    saveBtn.textContent = 'บันทึกการเปลี่ยนแปลง';
  }
});

// ── Reset ───────────────────────────────────────────────────
resetBtn.addEventListener('click', () => {
  nickInput.value = originalNick;
  pendingAvatar   = null;
  document.getElementById('avatarImg').src =
    <?= json_encode($user['avatar_url'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($user['nickname']) . '&background=F97316&color=fff&size=200') ?>;
  showToast('ยกเลิกการเปลี่ยนแปลงแล้ว', 'success');
});

// ── Toast helper ────────────────────────────────────────────
function showToast(msg, type = 'success') {
  const t   = document.getElementById('toast');
  const ico = document.getElementById('toastIcon');
  const txt = document.getElementById('toastMsg');
  t.className = `toast ${type}`;
  ico.textContent = type === 'success' ? '✅' : '⚠️';
  txt.textContent = msg;
  t.classList.add('show');
  clearTimeout(t._timer);
  t._timer = setTimeout(() => t.classList.remove('show'), 3000);
}
</script>
</body>
</html>
