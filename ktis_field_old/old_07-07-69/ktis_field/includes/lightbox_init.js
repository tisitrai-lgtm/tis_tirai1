/**
 * includes/lightbox_init.js
 * v4: เวอร์ชันเรียบง่ายที่สุด ไม่มี wheel/touchmove listener, ไม่มี requestAnimationFrame
 * ใช้สำหรับ debug ว่าโค้ด lightbox เป็นสาเหตุของอาการเด้ง scroll หรือไม่
 */
(function () {
    let currentGallery = [];
    let currentIndex   = 0;
    let lb, lbImg, lbCounter, lbPrev, lbNext, lbClose;
    let touchStartX = 0;

    function ensureLightboxDOM() {
        if (document.getElementById('customLightbox')) return;
        const div = document.createElement('div');
        div.id = 'customLightbox';
        div.style.cssText =
            'position:fixed;top:0;left:0;width:100%;height:100%;' +
            'background:rgba(15,23,42,0.92);z-index:999999;' +
            'display:none;align-items:center;justify-content:center;touch-action:none;';
        div.innerHTML =
            '<div id="lbCloseBtn" style="position:absolute;top:20px;right:25px;color:#fff;font-size:2rem;cursor:pointer;width:45px;height:45px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:rgba(255,255,255,0.1);">&times;</div>' +
            '<div id="lbPrevBtn" style="position:absolute;left:20px;top:50%;transform:translateY(-50%);color:#fff;font-size:2rem;cursor:pointer;width:45px;height:45px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:rgba(255,255,255,0.1);">&#8249;</div>' +
            '<img id="lbImg" src="" style="max-width:90vw;max-height:85vh;border-radius:8px;">' +
            '<div id="lbNextBtn" style="position:absolute;right:20px;top:50%;transform:translateY(-50%);color:#fff;font-size:2rem;cursor:pointer;width:45px;height:45px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:rgba(255,255,255,0.1);">&#8250;</div>' +
            '<div id="lbCounter" style="position:absolute;bottom:25px;left:50%;transform:translateX(-50%);color:#fff;font-size:0.9rem;background:rgba(0,0,0,0.5);padding:5px 14px;border-radius:20px;"></div>';
        document.documentElement.appendChild(div);

        lb        = div;
        lbImg     = document.getElementById('lbImg');
        lbCounter = document.getElementById('lbCounter');
        lbPrev    = document.getElementById('lbPrevBtn');
        lbNext    = document.getElementById('lbNextBtn');
        lbClose   = document.getElementById('lbCloseBtn');

        lbClose.onclick = function (e) { e.stopPropagation(); closeLB(); };
        lb.onclick = function (e) { if (e.target === lb) closeLB(); };
        lbPrev.onclick = function (e) { e.stopPropagation(); showIndex(currentIndex - 1); };
        lbNext.onclick = function (e) { e.stopPropagation(); showIndex(currentIndex + 1); };

        lbImg.addEventListener('touchstart', function (e) { touchStartX = e.touches[0].clientX; }, { passive: true });
        lbImg.addEventListener('touchend', function (e) {
            const dx = e.changedTouches[0].clientX - touchStartX;
            if (Math.abs(dx) > 50) { dx > 0 ? showIndex(currentIndex - 1) : showIndex(currentIndex + 1); }
        }, { passive: true });

        // Prevent background scrolling while open
        lb.addEventListener('wheel', function (e) { e.preventDefault(); }, { passive: false });
        lb.addEventListener('touchmove', function (e) { e.preventDefault(); }, { passive: false });
    }

    function showIndex(idx) {
        if (currentGallery.length === 0) return;
        currentIndex = (idx + currentGallery.length) % currentGallery.length;
        lbImg.src = currentGallery[currentIndex];
        const multi = currentGallery.length > 1;
        lbPrev.style.display = multi ? 'flex' : 'none';
        lbNext.style.display = multi ? 'flex' : 'none';
        lbCounter.style.display = multi ? 'block' : 'none';
        lbCounter.textContent = (currentIndex + 1) + ' / ' + currentGallery.length;
    }

    function openLB(gallery, index) {
        ensureLightboxDOM();
        currentGallery = gallery;
        showIndex(index);
        lb.style.display = 'flex';
    }

    function closeLB() {
        if (!lb) return;
        lb.style.display = 'none';
    }

    document.addEventListener('click', function (e) {
        if (lb && lb.contains(e.target)) return;
        const img = e.target.closest('.js-lightbox-img');
        if (!img) return;
        e.preventDefault();
        let gallery = [];
        try { gallery = JSON.parse(img.dataset.gallery || '[]'); } catch (err) { gallery = [img.src]; }
        const idx = parseInt(img.dataset.index || '0', 10);
        openLB(gallery, idx);
    });
})();