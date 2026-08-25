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
            // ปิด hamburger menu ด้วย
            if (navMenu) navMenu.classList.remove('show-menu');
            if (toggleIcon) toggleIcon.className = 'fa-solid fa-bars';
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

    // ── 3.1 Toggle Category Accordion ──
    window.toggleNavCategory = function (e, btn) {
        if (e) e.stopPropagation();
        const group = btn.closest('.nav-category-group');
        if (group) {
            group.classList.toggle('open');
        }
    };

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

    // ── 7. Swipe Lightbox ──
    let _savedScrollY = 0; // บันทึก scroll position ก่อนเปิด lightbox
    let currentGalleryImages = [];
    let currentImageIndex = 0;

    // Create lightbox HTML
    const lightboxDiv = document.createElement('div');
    lightboxDiv.id = 'swipe-lightbox';
    lightboxDiv.className = 'custom-lightbox';
    lightboxDiv.innerHTML = `
        <span class="lightbox-close" id="sw-close"><i class="fa-solid fa-xmark"></i></span>
        <span class="lightbox-arrow lightbox-prev" id="sw-prev"><i class="fa-solid fa-chevron-left"></i></span>
        <span class="lightbox-arrow lightbox-next" id="sw-next"><i class="fa-solid fa-chevron-right"></i></span>
        <div class="lightbox-content">
            <img id="swipe-lightbox-img" src="" alt="Gallery Image" draggable="false">
        </div>
        <div class="lightbox-counter" id="sw-counter">1 / 1</div>
    `;
    document.documentElement.appendChild(lightboxDiv);

    const swImg = lightboxDiv.querySelector('#swipe-lightbox-img');
    const swCounter = lightboxDiv.querySelector('#sw-counter');
    const swPrev = lightboxDiv.querySelector('#sw-prev');
    const swNext = lightboxDiv.querySelector('#sw-next');
    const swClose = lightboxDiv.querySelector('#sw-close');

    function updateLightboxImage() {
        if (currentGalleryImages.length === 0) return;
        swImg.style.opacity = '0';
        setTimeout(() => {
            swImg.src = currentGalleryImages[currentImageIndex];
            swCounter.innerText = `${currentImageIndex + 1} / ${currentGalleryImages.length}`;
            
            // Hide arrows if only 1 image
            if (currentGalleryImages.length <= 1) {
                swPrev.style.display = 'none';
                swNext.style.display = 'none';
            } else {
                swPrev.style.display = 'flex';
                swNext.style.display = 'flex';
            }
            swImg.style.opacity = '1';
        }, 150);
    }

    function showPrevImage() {
        if (currentGalleryImages.length <= 1) return;
        currentImageIndex = (currentImageIndex - 1 + currentGalleryImages.length) % currentGalleryImages.length;
        updateLightboxImage();
    }

    function showNextImage() {
        if (currentGalleryImages.length <= 1) return;
        currentImageIndex = (currentImageIndex + 1) % currentGalleryImages.length;
        updateLightboxImage();
    }

    function openLightboxLockScroll() {
        // No body style locking to prevent scroll jump bugs.
        // Scroll locking is handled by touchmove and wheel preventDefault on the lightbox itself.
    }

    function closeLightbox() {
        lightboxDiv.classList.remove('open');
    }

    // Event listeners
    swPrev.addEventListener('click', (e) => { e.stopPropagation(); showPrevImage(); });
    swNext.addEventListener('click', (e) => { e.stopPropagation(); showNextImage(); });
    swClose.addEventListener('click', (e) => { e.stopPropagation(); closeLightbox(); });
    lightboxDiv.addEventListener('click', (e) => {
        if (e.target === lightboxDiv || e.target.classList.contains('lightbox-content')) {
            closeLightbox();
        }
    });

    // Prevent background scrolling while lightbox is open
    lightboxDiv.addEventListener('wheel', (e) => {
        if (lightboxDiv.classList.contains('open')) {
            e.preventDefault();
        }
    }, { passive: false });
    lightboxDiv.addEventListener('touchmove', (e) => {
        if (lightboxDiv.classList.contains('open')) {
            e.preventDefault();
        }
    }, { passive: false });

    // Event delegation for opening
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('post-img') || e.target.classList.contains('chat-embedded-img') || e.target.classList.contains('rp-thumb')) {
            e.preventDefault();
            e.stopPropagation();

            // Find gallery group (all post-imgs in same post-image-gallery, all chat-embedded-imgs in same comments-list, or all rp-thumbs in same list)
            const galleryContainer = e.target.closest('.post-image-gallery') || e.target.closest('.comments-list') || e.target.closest('.rp-list') || e.target.closest('.hist-card');
            if (galleryContainer) {
                const imgs = Array.from(galleryContainer.querySelectorAll('.post-img, .chat-embedded-img, .rp-thumb'));
                currentGalleryImages = imgs.map(img => img.src);
                currentImageIndex = imgs.indexOf(e.target);
                if (currentImageIndex === -1) currentImageIndex = 0;
            } else {
                currentGalleryImages = [e.target.src];
                currentImageIndex = 0;
            }

            updateLightboxImage();
            openLightboxLockScroll();
            lightboxDiv.classList.add('open');
        }
    });

    // Keyboard support
    document.addEventListener('keydown', (e) => {
        if (!lightboxDiv.classList.contains('open')) return;
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') showPrevImage();
        if (e.key === 'ArrowRight') showNextImage();
    });

    // Swipe / Drag Support
    let startX = 0;
    let isDragging = false;
    let dragThreshold = 50;

    const handleDragStart = (e) => {
        isDragging = true;
        startX = e.clientX || e.touches[0].clientX;
        swImg.style.transition = 'none';
    };

    const handleDragMove = (e) => {
        if (!isDragging) return;
        const currentX = e.clientX || (e.touches && e.touches[0].clientX);
        if (currentX === undefined) return;
        const diffX = currentX - startX;
        
        // Apply transform to slide image visually
        swImg.style.transform = `translateX(${diffX}px) scale(0.98)`;
    };

    const handleDragEnd = (e) => {
        if (!isDragging) return;
        isDragging = false;
        swImg.style.transition = 'transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s ease';
        
        // Check final transform offset from style
        const transformValue = swImg.style.transform;
        const match = transformValue.match(/translateX\(([-\d.]+)px\)/);
        const diffX = match ? parseFloat(match[1]) : 0;
        
        swImg.style.transform = '';
        
        if (diffX > dragThreshold) {
            showPrevImage();
        } else if (diffX < -dragThreshold) {
            showNextImage();
        }
    };

    // Touch events
    swImg.addEventListener('touchstart', handleDragStart, { passive: true });
    swImg.addEventListener('touchmove', handleDragMove, { passive: true });
    swImg.addEventListener('touchend', handleDragEnd, { passive: true });

    // Mouse events (for desktop dragging)
    swImg.addEventListener('mousedown', handleDragStart);
    document.addEventListener('mousemove', handleDragMove);
    document.addEventListener('mouseup', handleDragEnd);

    // ── 8. Real-time Notification Polling (30 seconds) ──
    let _lastNotiCount = -1; // -1 = ยังไม่เคย poll ครั้งแรก

    // ── Toast notification ──
    function showNotiToast(count) {
        // ลบ toast เดิมถ้ามีอยู่
        const old = document.getElementById('noti-toast');
        if (old) old.remove();

        const toast = document.createElement('div');
        toast.id = 'noti-toast';
        toast.innerHTML = `
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:36px;height:36px;background:#e11d48;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fa-solid fa-bell" style="color:#fff;font-size:.9rem;animation:bellRing .4s ease 2;"></i>
                </div>
                <div>
                    <div style="font-weight:700;font-size:.88rem;color:#1e293b;">มีการแจ้งเตือนใหม่!</div>
                    <div style="font-size:.78rem;color:#64748b;">กระดิ่งมีข้อความเข้า ${count} รายการ</div>
                </div>
                <button onclick="this.closest('#noti-toast').remove()"
                        style="background:none;border:none;color:#94a3b8;font-size:1rem;cursor:pointer;margin-left:4px;padding:4px;flex-shrink:0;">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        `;
        Object.assign(toast.style, {
            position: 'fixed',
            bottom: '24px',
            right: '20px',
            background: '#fff',
            border: '1px solid #e2e8f0',
            borderLeft: '4px solid #e11d48',
            borderRadius: '12px',
            padding: '12px 14px',
            boxShadow: '0 8px 24px rgba(0,0,0,.13)',
            zIndex: '99999',
            minWidth: '260px',
            maxWidth: '320px',
            fontFamily: "'Sarabun',sans-serif",
            animation: 'toastSlideIn .3s ease-out forwards',
            cursor: 'pointer',
        });
        toast.addEventListener('click', function(e) {
            if (e.target.closest('button')) return;
            const bellBtn = document.getElementById('notiBellBtn');
            if (bellBtn) bellBtn.click(); // เปิด dropdown
            toast.remove();
        });
        document.body.appendChild(toast);

        // สั่นมือถือ (ถ้า browser รองรับ)
        if (navigator.vibrate) navigator.vibrate([100, 50, 100]);

        // หายไปอัตโนมัติ 5 วิ
        setTimeout(() => {
            if (toast.parentNode) {
                toast.style.animation = 'toastSlideOut .3s ease-in forwards';
                setTimeout(() => toast.remove(), 300);
            }
        }, 5000);
    }

    // inject CSS สำหรับ toast animation (ทำครั้งเดียว)
    (function(){
        if (document.getElementById('noti-toast-style')) return;
        const s = document.createElement('style');
        s.id = 'noti-toast-style';
        s.textContent = `
            @keyframes toastSlideIn {
                from { opacity:0; transform:translateX(100%); }
                to   { opacity:1; transform:translateX(0); }
            }
            @keyframes toastSlideOut {
                from { opacity:1; transform:translateX(0); }
                to   { opacity:0; transform:translateX(100%); }
            }
            @keyframes bellRing {
                0%,100% { transform:rotate(0); }
                25%      { transform:rotate(-20deg); }
                75%      { transform:rotate(20deg); }
            }
            html.dark-mode #noti-toast {
                background:#1e293b !important;
                border-color:#334155 !important;
            }
            html.dark-mode #noti-toast .noti-toast-title { color:#f8fafc !important; }
        `;
        document.head.appendChild(s);
    })();

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function pollNotifications() {
        fetch('api_notifications.php')
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // ── แจ้งเตือน toast ถ้ามีข้อความใหม่เข้ามา ──
                    const newCount = data.noti_count;
                    if (_lastNotiCount >= 0 && newCount > _lastNotiCount) {
                        showNotiToast(newCount);
                    }
                    _lastNotiCount = newCount;

                    // 1. Update Badge
                    const bellBtn = document.getElementById('notiBellBtn');
                    if (bellBtn) {
                        let badge = bellBtn.querySelector('.noti-count-badge');
                        if (data.noti_count > 0) {
                            if (badge) {
                                badge.innerText = data.noti_count;
                            } else {
                                badge = document.createElement('span');
                                badge.className = 'noti-count-badge';
                                badge.innerText = data.noti_count;
                                bellBtn.appendChild(badge);
                            }
                        } else {
                            if (badge) badge.remove();
                        }
                    }

                    // 2. Update Dropdown Header "อ่านทั้งหมด"
                    const notiBox = document.getElementById('notiBox');
                    if (notiBox) {
                        const notiHeader = notiBox.querySelector('.noti-header');
                        if (notiHeader) {
                            let markAllRead = notiHeader.querySelector('a');
                            if (data.noti_count > 0) {
                                if (!markAllRead) {
                                    markAllRead = document.createElement('a');
                                    markAllRead.href = 'notification_api.php';
                                    markAllRead.style.color = '#10b981';
                                    markAllRead.style.fontSize = '.75rem';
                                    markAllRead.style.textDecoration = 'none';
                                    markAllRead.style.fontWeight = '600';
                                    markAllRead.innerHTML = '<i class="fa-solid fa-check-double"></i> อ่านทั้งหมด';
                                    notiHeader.appendChild(markAllRead);
                                }
                            } else {
                                if (markAllRead) markAllRead.remove();
                            }
                        }

                        // 3. Update Dropdown List
                        const wrapper = notiBox.querySelector('.noti-list-wrapper');
                        if (wrapper) {
                            if (data.notifications.length === 0) {
                                wrapper.innerHTML = `
                                    <div class="noti-empty">
                                        <i class="fa-regular fa-bell-slash" style="font-size:1.5rem;display:block;margin-bottom:6px;"></i>
                                        ไม่มีการแจ้งเตือนใหม่
                                    </div>
                                `;
                            } else {
                                let html = '';
                                data.notifications.forEach(noti => {
                                    html += `
                                        <div class="noti-item-row ${noti.is_read == 0 ? 'unread-row' : ''}"
                                             onclick="location.href='notification_api.php?post_id=${noti.post_id}'">
                                            <div class="noti-icon">
                                                <i class="fa-solid ${noti.is_read == 0 ? 'fa-envelope' : 'fa-envelope-open'}"></i>
                                            </div>
                                            <div class="noti-info-text">
                                                <div class="noti-main-text ${noti.is_read == 0 ? 'bold' : ''}">
                                                    ${escapeHtml(noti.noti_text)}
                                                </div>
                                                ${noti.problem_detail ? `
                                                <div class="noti-problem-preview">
                                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                                    ${escapeHtml(noti.problem_detail.substring(0, 50))}
                                                </div>
                                                ` : ''}
                                                <div class="noti-time-stamp">
                                                    <i class="fa-regular fa-clock"></i>
                                                    ${noti.created_at}
                                                </div>
                                            </div>
                                            <button class="btn-dismiss-noti"
                                                    onclick="deleteNotification(event, ${noti.noti_id})"
                                                    title="ลบการแจ้งเตือน">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </div>
                                    `;
                                });
                                wrapper.innerHTML = html;
                            }
                        }
                    }
                }
            })
            .catch(err => console.warn("Notification poll failed:", err));
    }

    // Start polling: poll ครั้งแรกทันที แล้ว poll ทุก 20 วิ
    if (document.getElementById('notiBellBtn')) {
        pollNotifications(); // ครั้งแรก set _lastNotiCount
        setInterval(pollNotifications, 20000); // ทุก 20 วิ
    }

    // ==========================================================================
    // 🔔 2. GLOBAL MODERN TOAST NOTIFICATION SYSTEM
    // ==========================================================================
    window.showToast = function(arg1, arg2, duration = 3500) {
        if (!arg1 && !arg2) return;
        let msg = arg1;
        let type = arg2 || 'success';
        
        // ถ้าส่งแบบ showToast('success', 'ข้อความ') หรือ showToast('error', 'ข้อความ')
        if (['success', 'error', 'warning', 'info'].includes(arg1) && typeof arg2 === 'string') {
            type = arg1;
            msg = arg2;
        }
        if (!msg) return;

        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = 'custom-toast';
        
        let iconClass = 'fa-check';
        if (type === 'error')   iconClass = 'fa-circle-xmark';
        if (type === 'warning') iconClass = 'fa-triangle-exclamation';
        if (type === 'info')    iconClass = 'fa-circle-info';

        toast.innerHTML = `
            <div class="toast-icon ${type}"><i class="fa-solid ${iconClass}"></i></div>
            <div class="toast-msg">${escapeHtml(msg)}</div>
            <button type="button" class="toast-close" title="ปิด"><i class="fa-solid fa-xmark"></i></button>
            <div class="toast-progress ${type}" style="animation-duration: ${duration}ms;"></div>
        `;

        container.appendChild(toast);
        requestAnimationFrame(() => {
            toast.classList.add('show');
        });

        const timer = setTimeout(() => {
            toast.classList.remove('show');
            toast.classList.add('hide');
            setTimeout(() => toast.remove(), 300);
        }, duration);

        toast.querySelector('.toast-close').addEventListener('click', () => {
            clearTimeout(timer);
            toast.classList.remove('show');
            toast.classList.add('hide');
            setTimeout(() => toast.remove(), 300);
        });
    };

    // Helper decode Thai text safely
    function safeDecodeThai(val) {
        if (!val) return '';
        try {
            return decodeURIComponent(val);
        } catch(e) {
            try {
                return decodeURIComponent(escape(val));
            } catch(e2) {
                return val;
            }
        }
    }

    // Auto Toast from URL parameters (e.g. ?msg=... or ?success=... or ?error=...)
    try {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('success') || urlParams.has('msg')) {
            const rawMsg = urlParams.get('success') || urlParams.get('msg');
            if (rawMsg && rawMsg !== '1' && rawMsg !== 'true') {
                window.showToast(safeDecodeThai(rawMsg), 'success');
            }
        }
        if (urlParams.has('error')) {
            const rawErr = urlParams.get('error');
            if (rawErr && rawErr !== '1' && rawErr !== 'true') {
                window.showToast(safeDecodeThai(rawErr), 'error');
            }
        }
    } catch(e) {}

});