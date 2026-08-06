/* includes/nav_script.js */

document.addEventListener('DOMContentLoaded', function () {

    const toggleBtn  = document.getElementById('navToggle');
    const toggleIcon = document.getElementById('navToggleIcon');
    const navMenu    = document.getElementById('navMenu');
    const notiBtn    = document.getElementById('notiBellBtn');
    const notiBox    = document.getElementById('notiBox');
    const adminGroup = document.getElementById('adminNavGroup');
    const adminToggle= document.getElementById('adminToggle');

    // ── 1. Hamburger Menu ──
    if (toggleBtn && navMenu) {
        toggleBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            const open = navMenu.classList.toggle('show-menu');
            if (toggleIcon) {
                toggleIcon.className = open ? 'fa-solid fa-xmark' : 'fa-solid fa-bars';
            }
            // ปิด dropdown อื่น
            if (notiBox)    notiBox.classList.remove('show');
            if (adminGroup) adminGroup.classList.remove('open');
        });
    }

    // ── 2. Notification Dropdown ──
    if (notiBtn && notiBox) {
        notiBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            notiBox.classList.toggle('show');
            if (adminGroup) adminGroup.classList.remove('open');
        });
    }

    // ── 3. Admin Dropdown ──
    if (adminToggle && adminGroup) {
        adminToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            adminGroup.classList.toggle('open');
            if (notiBox) notiBox.classList.remove('show');
        });
    }

    // ── 4. คลิกข้างนอกปิดทุก dropdown ──
    document.addEventListener('click', function (e) {
        if (notiBox && !notiBox.contains(e.target) && e.target !== notiBtn) {
            notiBox.classList.remove('show');
        }
        if (adminGroup && !adminGroup.contains(e.target)) {
            adminGroup.classList.remove('open');
        }
        if (navMenu && !navMenu.contains(e.target) && e.target !== toggleBtn) {
            const wasOpen = navMenu.classList.contains('show-menu');
            navMenu.classList.remove('show-menu');
            if (wasOpen && toggleIcon) toggleIcon.className = 'fa-solid fa-bars';
        }
    });

    // ── 5. ลบแจ้งเตือน AJAX ──
    window.deleteNotification = function (event, notiId) {
        event.stopPropagation();
        if (!confirm('ลบแจ้งเตือนนี้?')) return;

        const notiRow = event.target.closest('.noti-item-row');

        fetch('delete_notification.php?id=' + notiId)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    notiRow.style.transition = 'opacity .25s, transform .25s';
                    notiRow.style.opacity    = '0';
                    notiRow.style.transform  = 'translateX(20px)';
                    setTimeout(() => {
                        notiRow.remove();
                        const badge = document.querySelector('.noti-count-badge');
                        if (badge) {
                            const cnt = parseInt(badge.innerText) - 1;
                            cnt > 0 ? (badge.innerText = cnt) : badge.remove();
                        }
                        const wrapper = document.querySelector('.noti-list-wrapper');
                        if (wrapper && !wrapper.querySelector('.noti-item-row')) {
                            wrapper.innerHTML = '<div class="noti-empty"><i class="fa-regular fa-bell-slash" style="font-size:1.5rem;display:block;margin-bottom:6px;"></i>ไม่มีการแจ้งเตือนใหม่</div>';
                        }
                    }, 280);
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            })
            .catch(() => alert('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้'));
    };

});