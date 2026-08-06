<?php
/**
 * includes/feed_scripts.php
 * JavaScript ทั้งหมดของหน้า feed
 * ปรับปรุง: 
 *  1. แทน alert()/confirm() ด้วย SweetAlert2 ทุกจุด
 *  2. อัปโหลดรูปมี progress bar + % แสดงชัดเจน
 *  3. หลังบันทึก/แก้ไข อัปเดต DOM ตรงจุด ไม่ reload ทั้งหน้า
 */
?>
<script>

// ══════════════════════════════════════════
//  TOAST HELPER — ใช้ SweetAlert2 toast แทน alert()
// ══════════════════════════════════════════
function showToast(icon, title, timer = 2800) {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon,
        title,
        showConfirmButton: false,
        timer,
        timerProgressBar: true,
        customClass: { popup: 'sa2-th' }
    });
}

function showConfirm(title, text, confirmText, confirmColor = '#e11d48') {
    return Swal.fire({
        title,
        text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: confirmColor,
        cancelButtonColor: '#64748b',
        reverseButtons: true,
        customClass: { popup: 'sa2-th' }
    });
}

// ══════════════════════════════════════════
//  Reactions (like / love / wow)
// ══════════════════════════════════════════
function doReaction(postId, type){
    const fd = new FormData();
    fd.append('post_id', postId);
    fd.append('reaction_type', type);
    fetch('post_reaction.php', {method:'POST', body:fd})
    .then(r=>r.json())
    .then(data=>{
        if(data.status !== 'success') return;
        ['like','love','wow'].forEach(t=>{
            const btn = document.getElementById('rbtn-'+postId+'-'+t);
            const cnt = document.getElementById('rcnt-'+postId+'-'+t);
            if(btn) btn.style.background = (data.my_reaction===t) ? '#f1f5f9' : '#fff';
            if(cnt) cnt.textContent = data.counts[t] > 0 ? data.counts[t] : '';
        });
        const total = Object.values(data.counts).reduce((a,b)=>a+b,0);
        const totalEl = document.getElementById('rtotal-'+postId);
        if(totalEl){ totalEl.textContent = total>0 ? total+' คน' : ''; totalEl.style.display = total>0 ? '' : 'none'; }
    })
    .catch(()=>showToast('error','เกิดข้อผิดพลาด'));
}

// ══════════════════════════════════════════
//  ลบ comment (Admin only) — DOM update ไม่ reload
// ══════════════════════════════════════════
function deleteReply(replyId, postId) {
    showConfirm('ลบความคิดเห็น?', 'ความคิดเห็นนี้จะถูกลบออกจากระบบถาวร', 'ลบเลย')
    .then(result => {
        if (!result.isConfirmed) return;
        const fd = new FormData();
        fd.append('reply_id', replyId);
        fd.append('action', 'delete');
        fetch('reply_action.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                const row = document.getElementById('reply-row-' + replyId);
                if (row) {
                    row.style.transition = 'opacity .25s, max-height .3s';
                    row.style.overflow = 'hidden';
                    row.style.maxHeight = row.offsetHeight + 'px';
                    row.style.opacity = '0';
                    setTimeout(() => { row.style.maxHeight = '0'; row.style.padding = '0'; row.style.margin = '0'; }, 50);
                    setTimeout(() => row.remove(), 350);
                }
                showToast('success', 'ลบความคิดเห็นแล้ว');
            } else {
                showToast('error', data.message || 'เกิดข้อผิดพลาด');
            }
        })
        .catch(() => showToast('error', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้'));
    });
}

// ══════════════════════════════════════════
//  เปลี่ยน status โพสต์ (Admin only) — DOM update ไม่ reload
// ══════════════════════════════════════════
function toggleStatus(postId, currentStatus) {
    const newStatus = currentStatus === 'pending' ? 'success' : 'pending';
    const confirmText = newStatus === 'success' ? 'ยืนยันดำเนินการแล้ว' : 'เปลี่ยนเป็นรอดำเนินการ';
    const confirmColor = newStatus === 'success' ? '#10b981' : '#e11d48';
    const msg = newStatus === 'success'
        ? 'ยืนยันว่าดำเนินการเรื่องนี้เรียบร้อยแล้ว?'
        : 'เปลี่ยนกลับเป็น "รอดำเนินการ"?';

    showConfirm('ยืนยันเปลี่ยนสถานะ?', msg, confirmText, confirmColor)
    .then(result => {
        if (!result.isConfirmed) return;
        const fd = new FormData();
        fd.append('post_id', postId);
        fd.append('job_status', newStatus);
        fetch('post_status.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                // อัปเดต UI ไม่ต้อง reload
                const card = document.getElementById('post-card-' + postId);
                const btn  = document.getElementById('status-btn-' + postId);
                if (card) {
                    card.style.borderLeftColor = newStatus === 'success' ? '#10b981' : '#e11d48';
                    const badge = card.querySelector('[data-status-badge]');
                    if (badge) {
                        badge.textContent = newStatus === 'success' ? 'ดำเนินการแล้ว' : 'รอดำเนินการ';
                        badge.style.background = newStatus === 'success' ? '#d1fae5' : '#fee2e2';
                        badge.style.color = newStatus === 'success' ? '#065f46' : '#991b1b';
                    }
                }
                if (btn) {
                    btn.style.background = newStatus === 'success' ? '#e11d48' : '#10b981';
                    btn.dataset.status = newStatus;
                    btn.setAttribute('onclick', "toggleStatus(" + postId + ", '" + newStatus + "')");
                    btn.innerHTML = newStatus === 'success'
                        ? '<i class="fa-solid fa-rotate-left"></i> เปลี่ยนเป็นรอดำเนินการ'
                        : '<i class="fa-solid fa-circle-check"></i> ยืนยันดำเนินการแล้ว';
                }
                showToast('success', newStatus === 'success' ? 'เปลี่ยนเป็น "ดำเนินการแล้ว"' : 'เปลี่ยนเป็น "รอดำเนินการ"');
            } else {
                showToast('error', data.message || 'เกิดข้อผิดพลาด');
            }
        })
        .catch(() => showToast('error', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้'));
    });
}

// ══════════════════════════════════════════
//  ลบโพสต์ (Admin only) — DOM update ไม่ reload
// ══════════════════════════════════════════
function deletePost(postId) {
    showConfirm(
        'ยืนยันลบรายการนี้?',
        'ข้อมูลรถอ้อยสกปรกรายการนี้จะถูกลบออกจากระบบถาวร ไม่สามารถกู้คืนได้',
        'ลบเลย'
    ).then(result => {
        if (!result.isConfirmed) return;
        const fd = new FormData();
        fd.append('post_id', postId);
        fetch('post_delete.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                const card = document.getElementById('post-card-' + postId);
                if (card) {
                    card.style.transition = 'opacity .3s, transform .3s';
                    card.style.opacity = '0';
                    card.style.transform = 'translateX(20px)';
                    setTimeout(() => card.remove(), 320);
                }
                showToast('success', 'ลบรายการแล้ว');
            } else {
                showToast('error', data.message || 'ลบไม่สำเร็จ');
            }
        })
        .catch(() => showToast('error', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้'));
    });
}

// ══════════════════════════════════════════
//  แก้ไข comment — DOM update ไม่ reload
// ══════════════════════════════════════════
function enableEditMode(replyId) {
    document.getElementById('reply-text-' + replyId).style.display = 'none';
    document.getElementById('edit-box-' + replyId).style.display = 'block';
    const inp = document.getElementById('edit-input-' + replyId);
    if (inp) { inp.focus(); inp.select(); }
}

function cancelEdit(replyId) {
    document.getElementById('reply-text-' + replyId).style.display = 'block';
    document.getElementById('edit-box-' + replyId).style.display = 'none';
}

function saveEdit(replyId) {
    const inputEl = document.getElementById('edit-input-' + replyId);
    const textValue = inputEl ? inputEl.value : '';
    if (textValue.trim() === '') {
        showToast('warning', 'กรุณากรอกข้อความก่อนบันทึก');
        return;
    }
    const fd = new FormData();
    fd.append('reply_id', replyId);
    fd.append('reply_text', textValue);

    // รูปใหม่ (ถ้ามี)
    const imgInput = document.getElementById('edit-img-input-' + replyId);
    if (imgInput && imgInput.files[0]) fd.append('reply_image', imgInput.files[0]);

    // ลบรูป (ถ้ากดปุ่มลบรูป)
    const delFlag = document.getElementById('edit-del-img-' + replyId);
    if (delFlag && delFlag.value === '1') fd.append('delete_image', '1');

    fetch('reply_edit.php', { method: 'POST', body: fd })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            const textEl = document.getElementById('reply-text-' + replyId);
            if (textEl) textEl.innerHTML = data.updated_text || textValue.replace(/\n/g, '<br>');

            // อัปเดตรูปใน DOM
            const row = document.getElementById('reply-row-' + replyId);
            if (row) {
                const oldImg = row.querySelector('.chat-embedded-img');
                if (oldImg) oldImg.closest('div').remove();
                if (data.img_html) {
                    const contentBox = row.querySelector('.chat-content-box');
                    if (contentBox) contentBox.insertAdjacentHTML('beforeend', data.img_html);
                }
            }

            document.getElementById('edit-box-' + replyId).style.display = 'none';
            document.getElementById('reply-text-' + replyId).style.display = '';

            // badge แก้ไขแล้ว
            if (row && !row.querySelector('.edited-tag')) {
                const ts = row.querySelector('.chat-timestamp');
                if (ts) {
                    const tag = document.createElement('span');
                    tag.className = 'edited-tag';
                    tag.textContent = 'แก้ไขแล้ว';
                    ts.appendChild(tag);
                }
            }
            showToast('success', 'บันทึกการแก้ไขแล้ว');
        } else {
            showToast('error', data.message || 'บันทึกไม่สำเร็จ');
        }
    })
    .catch(() => showToast('error', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้'));
}

// preview รูปใน edit box — แสดง thumbnail ทันที + ลบรูปเดิมอัตโนมัติ
function previewEditImg(input, replyId) {
    if (!input.files || !input.files[0]) return;

    // ลบรูปเดิมอัตโนมัติก่อนแนบใหม่ (server จะ unlink รูปเดิมแล้วบันทึกอันใหม่แทน)
    const delFlag = document.getElementById('edit-del-img-' + replyId);
    if (delFlag) delFlag.value = '0'; // ไม่ต้องส่ง delete_image เพราะ server จัดการเองเมื่อมีไฟล์ใหม่

    const preview = document.getElementById('edit-img-preview-' + replyId);
    if (!preview) return;

    const reader = new FileReader();
    reader.onload = e => {
        preview.src = e.target.result;
        preview.style.display = 'block';
        // แสดงชื่อไฟล์ + ขนาด
        const kb = Math.round(input.files[0].size / 1024);
        const nameEl = document.getElementById('edit-img-name-' + replyId);
        if (nameEl) nameEl.textContent = input.files[0].name.substring(0, 24) + ' (' + kb + ' KB)';
    };
    reader.readAsDataURL(input.files[0]);
}

// กดลบรูปใน edit box
function markDeleteEditImg(replyId) {
    const delFlag = document.getElementById('edit-del-img-' + replyId);
    if (delFlag) delFlag.value = '1';
    const preview = document.getElementById('edit-img-preview-' + replyId);
    if (preview) { preview.src = ''; preview.style.display = 'none'; }
    const imgInput = document.getElementById('edit-img-input-' + replyId);
    if (imgInput) imgInput.value = '';
    const nameEl = document.getElementById('edit-img-name-' + replyId);
    if (nameEl) nameEl.textContent = '';
    showToast('info', 'จะลบรูปเมื่อกดบันทึก');
}

// ══════════════════════════════════════════
//  ดูประวัติการแก้ไข comment (แทน alert)
// ══════════════════════════════════════════
function showEditHistory(historyText) {
    Swal.fire({
        title: 'ประวัติการแก้ไข',
        html: '<pre style="text-align:left;font-family:Sarabun,sans-serif;font-size:.85rem;white-space:pre-wrap;line-height:1.7;">' + historyText.replace(/</g,'&lt;') + '</pre>',
        confirmButtonText: 'ปิด',
        confirmButtonColor: '#1e293b',
        customClass: { popup: 'sa2-th' }
    });
}

// ══════════════════════════════════════════
//  แสดงชื่อไฟล์ที่แนบ
// ══════════════════════════════════════════
function displayFileName(input, previewId) {
    const wrap = previewId
        ? document.getElementById(previewId)
        : (input.closest('form') ? input.closest('form').querySelector('.file-status-preview') : null);

    if (!input.files || !input.files[0]) {
        if (wrap) { wrap.style.display = 'none'; wrap.innerHTML = ''; }
        return;
    }
    const reader = new FileReader();
    reader.onload = e => {
        if (wrap) {
            wrap.style.display = 'block';
            wrap.innerHTML = `<img src="${e.target.result}" style="width:52px;height:52px;object-fit:cover;border-radius:7px;border:1.5px solid #e2e8f0;vertical-align:middle;margin-right:6px;"><span style="font-size:.78rem;color:#10b981;font-weight:700;vertical-align:middle;"><i class="fa-solid fa-check-circle"></i> ${input.files[0].name.substring(0,22)} (${Math.round(input.files[0].size/1024)} KB)</span>`;
        }
    };
    reader.readAsDataURL(input.files[0]);
}

// ══════════════════════════════════════════
//  เปิด/ปิด Admin Post Form
// ══════════════════════════════════════════
function togglePostForm() {
    const formBox = document.getElementById('adminPostForm');
    const textBtn = document.getElementById('toggleText');
    const iconBtn = document.getElementById('toggleIcon');
    if (formBox.style.display === 'block') {
        formBox.style.display = 'none';
        textBtn.innerText = 'แจ้งเรื่องรถอ้อยสกปรกเพิ่ม';
        iconBtn.className = 'fa-solid fa-circle-plus';
    } else {
        formBox.style.display = 'block';
        textBtn.innerText = 'ปิดกล่องฟอร์มกรอกข้อมูล';
        iconBtn.className = 'fa-solid fa-circle-minus';
        loadProblemOptions();
    }
}

// ══════════════════════════════════════════
//  Compress รูป (800px / 75%) พร้อม Progress UI
// ══════════════════════════════════════════
function compressImage(file, onProgress, callback) {
    const MAX = 800;
    const QUALITY = 0.75;
    const reader = new FileReader();

    // Phase 1: อ่านไฟล์
    onProgress(10, 'กำลังอ่านไฟล์...');
    reader.onload = function(e) {
        onProgress(35, 'กำลังโหลดรูปภาพ...');
        const img = new Image();
        img.onload = function() {
            onProgress(55, 'กำลังปรับขนาด...');
            let w = img.width, h = img.height;
            if (w > MAX || h > MAX) {
                if (w > h) { h = Math.round(h * MAX / w); w = MAX; }
                else       { w = Math.round(w * MAX / h); h = MAX; }
            }
            // ใช้ requestAnimationFrame ให้ UI ไม่กระตุก
            requestAnimationFrame(() => {
                onProgress(72, 'กำลังบีบอัด...');
                setTimeout(() => {
                    const canvas = document.createElement('canvas');
                    canvas.width = w; canvas.height = h;
                    canvas.getContext('2d').drawImage(img, 0, 0, w, h);
                    onProgress(92, 'กำลังแปลงไฟล์...');
                    setTimeout(() => {
                        const b64 = canvas.toDataURL('image/jpeg', QUALITY);
                        onProgress(100, 'เสร็จแล้ว');
                        callback(b64);
                    }, 30);
                }, 30);
            });
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
}

function previewCompress(input, spanId, hiddenId) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    const span = document.getElementById(spanId);
    if (!span) return;

    // สร้าง progress UI
    span.innerHTML = `
        <div style="display:flex;flex-direction:column;gap:4px;min-width:160px;">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                <span id="${spanId}_label" style="font-size:.78rem;color:#475569;">กำลังเตรียมรูป...</span>
                <span id="${spanId}_pct" style="font-size:.78rem;font-weight:700;color:#10b981;min-width:32px;text-align:right;">0%</span>
            </div>
            <div style="height:5px;background:#e2e8f0;border-radius:10px;overflow:hidden;">
                <div id="${spanId}_bar" style="height:100%;width:0%;background:linear-gradient(90deg,#10b981,#059669);border-radius:10px;transition:width .2s ease;"></div>
            </div>
        </div>`;

    compressImage(
        file,
        (pct, label) => {
            const bar   = document.getElementById(spanId + '_bar');
            const lbl   = document.getElementById(spanId + '_label');
            const pctEl = document.getElementById(spanId + '_pct');
            if (bar)   bar.style.width = pct + '%';
            if (lbl)   lbl.textContent = label;
            if (pctEl) pctEl.textContent = pct + '%';
        },
        (b64) => {
            document.getElementById(hiddenId).value = b64;
            const kb = Math.round(b64.length * 0.75 / 1024);
            span.innerHTML = `<span style="color:#10b981;font-size:.82rem;font-weight:700;">
                <i class="fa-solid fa-check-circle"></i>
                ${file.name.substring(0, 20)} (~${kb} KB)
            </span>`;
        }
    );
}

// ══════════════════════════════════════════
//  โหลด Problem options
// ══════════════════════════════════════════
function loadProblemOptions() {
    fetch('api_problem_types.php')
    .then(res => res.json())
    .then(data => {
        if (!data || data.status !== 'success') return;
        const sels = document.querySelectorAll('.prob-sel');
        sels.forEach((sel, idx) => {
            const placeholder = idx === 0 ? '-- ปัญหาที่ 1 (บังคับ) --' : '-- ปัญหาที่ ' + (idx + 1) + ' (ถ้ามี) --';
            sel.innerHTML = '<option value="">' + placeholder + '</option>';
            data.data.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item.problem_name;
                opt.textContent = item.problem_name;
                sel.appendChild(opt);
            });
        });
    });
}

// ══════════════════════════════════════════
//  Submit โพสต์ใหม่ (Admin) — Toast แทน alert
// ══════════════════════════════════════════
if (document.getElementById('uploadForm')) {
    document.getElementById('uploadForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const p1 = formData.get('problem_1') || '';
        const p2 = formData.get('problem_2') || '';
        const p3 = formData.get('problem_3') || '';
        const combined = [p1, p2, p3].filter(v => v.trim() !== '').join(' / ');
        if (!combined) {
            showToast('warning', 'กรุณาเลือกปัญหาที่พบอย่างน้อย 1 รายการ');
            return;
        }
        formData.set('problem_detail', combined);
        const harvesterNum = formData.get('harvester_number') || '';
        const harvesterValid = document.getElementById('harvester_number')?.dataset.valid === 'true';
        if (!harvesterNum || !harvesterValid) {
            showToast('warning', 'กรุณาพิมพ์ค้นหาและเลือกเบอร์รถตัดจากดรอปดาวน์');
            return;
        }
        if (!formData.get('img_b64_1') || formData.get('img_b64_1') === '') {
            showToast('warning', 'กรุณาแนบรูปภาพอย่างน้อย 1 รูป');
            return;
        }
        const btn = this.querySelector('.btn-submit-post');
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> กำลังบันทึก...'; }

        fetch('post_create.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'บันทึกสำเร็จ!',
                    text: data.message,
                    confirmButtonText: 'โหลดหน้าใหม่',
                    confirmButtonColor: '#10b981',
                    customClass: { popup: 'sa2-th' }
                }).then(() => location.reload());
            } else {
                showToast('error', data.message || 'บันทึกไม่สำเร็จ');
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> ยืนยันการบันทึกข้อมูล'; }
            }
        })
        .catch(() => {
            showToast('error', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้');
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> ยืนยันการบันทึกข้อมูล'; }
        });
    });
}

// ══════════════════════════════════════════
//  Reply form — DOM update ไม่ reload
// ══════════════════════════════════════════
document.addEventListener('submit', function(e) {
    if (!e.target.classList.contains('replyForm')) return;
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const btn = form.querySelector('.btn-chat-send');
    if (btn) btn.disabled = true;

    fetch('reply_action.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            // เพิ่ม comment ใหม่เข้า DOM โดยไม่ reload
            const postId  = formData.get('post_id');
            const card    = document.getElementById('post-card-' + postId);
            const list    = card ? card.querySelector('.comments-list') : null;
            if (list && data.html) {
                list.insertAdjacentHTML('beforeend', data.html);
            }
            // clear form
            form.querySelector('[name="reply_text"]').value = '';
            const fileInput = form.querySelector('[name="reply_image"]');
            if (fileInput) fileInput.value = '';
            const preview = form.querySelector('.file-status-preview');
            if (preview) preview.style.display = 'none';
            showToast('success', 'ส่งความคิดเห็นแล้ว');
        } else {
            showToast('error', data.message || 'ส่งไม่สำเร็จ');
        }
    })
    .catch(() => showToast('error', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้'))
    .finally(() => { if (btn) btn.disabled = false; });
});

// ══════════════════════════════════════════
//  Scroll to post จากการแจ้งเตือน
// ══════════════════════════════════════════
function scrollToTargetPost(postId, notiId) {
    document.getElementById('notiDropdown')?.classList.remove('show');

    const fd = new FormData();
    fd.append('noti_id', notiId);
    fetch('noti_read_action.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(() => {
        const badge = document.getElementById('notiBadgeCount');
        if (badge) {
            const cur = parseInt(badge.innerText);
            if (cur > 1) { badge.innerText = cur - 1; } else { badge.remove(); }
        }
    });

    const postCard = document.getElementById('post-card-' + postId);
    if (postCard) {
        postCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
        postCard.classList.add('highlight-target-post');
        setTimeout(() => postCard.classList.remove('highlight-target-post'), 2000);
    } else {
        Swal.fire({
            icon: 'info',
            title: 'โพสต์อยู่วันอื่น',
            text: 'โพสต์นี้อาจอยู่คนละวัน กรุณาเลือกวันที่ค้นหาเองครับ',
            confirmButtonText: 'ตกลง',
            confirmButtonColor: '#1e293b',
            customClass: { popup: 'sa2-th' }
        });
    }
}

// ══════════════════════════════════════════
//  Feed Load More
// ══════════════════════════════════════════
let feedOffset = 10;
function loadMorePosts() {
    const spinner = document.getElementById('load-more-spinner');
    const button  = document.getElementById('btn-load-more');
    if (spinner) spinner.style.display = 'inline-block';
    if (button)  button.disabled = true;

    const searchDate = typeof globalSearchDate !== 'undefined' ? globalSearchDate : '';
    const statusTab  = typeof globalStatusTab  !== 'undefined' ? globalStatusTab  : 'all';

    fetch('api_load_more_posts.php?offset=' + feedOffset + '&search_date=' + searchDate + '&status_tab=' + statusTab)
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const container = document.getElementById('feed-items-container');
            if (container) container.insertAdjacentHTML('beforeend', data.html);
            feedOffset += 10;
            if (!data.has_more) {
                document.querySelector('.load-more-wrapper')?.remove();
            }
        } else {
            showToast('error', data.message || 'เกิดข้อผิดพลาดในการโหลดข้อมูล');
        }
    })
    .catch(() => showToast('error', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้'))
    .finally(() => {
        if (spinner) spinner.style.display = 'none';
        if (button)  button.disabled = false;
    });
}

// ══════════════════════════════════════════
//  Autocomplete Harvester Number (รถตัด)
// ══════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function() {
    const inputSearch = document.getElementById('harvester_search');
    const inputHidden = document.getElementById('harvester_number');
    const dropdown = document.getElementById('harvester_dropdown');
    
    if (!inputSearch) return;

    let items = [];

    // ค้นหาเมื่อผู้ใช้พิมพ์
    inputSearch.addEventListener('input', function() {
        const query = this.value.trim();
        inputHidden.value = ''; // เคลียร์ค่าจริงเมื่อมีการแก้ไข
        inputHidden.dataset.valid = 'false';

        // ดึงข้อมูลผ่าน API
        fetch('api_harvesters.php?q=' + encodeURIComponent(query))
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                items = res.data;
                populateDropdown(items);
            }
        })
        .catch(err => console.error('Error fetching harvesters:', err));
    });

    // แสดง dropdown เมื่อโฟกัส (ดึงค่าเริ่มต้นถ้าว่างเปล่า)
    inputSearch.addEventListener('focus', function() {
        const query = this.value.trim();
        fetch('api_harvesters.php?q=' + encodeURIComponent(query))
        .then(res => res.json())
        .then(res => {
            if (res.status === 'success') {
                items = res.data;
                populateDropdown(items);
            }
        });
    });

    function populateDropdown(list) {
        dropdown.innerHTML = '';
        
        if (list.length === 0) {
            dropdown.innerHTML = '<div class="autocomplete-no-result">ไม่พบข้อมูลรถตัด</div>';
            dropdown.style.display = 'block';
            return;
        }

        list.forEach((item) => {
            const div = document.createElement('div');
            div.className = 'autocomplete-item';
            // แสดง "เบอร์รถ (ชื่อรถ)" ถ้ามีชื่อรถเพิ่มเติม
            const displayName = item.harvester_name ? `${item.harvester_number} (${item.harvester_name})` : item.harvester_number;
            div.textContent = displayName;
            div.addEventListener('click', function(e) {
                e.stopPropagation();
                selectItem(item);
            });
            dropdown.appendChild(div);
        });
        dropdown.style.display = 'block';
    }

    function selectItem(item) {
        inputSearch.value = item.harvester_number;
        inputHidden.value = item.harvester_number;
        inputHidden.dataset.valid = 'true';
        dropdown.style.display = 'none';
        dropdown.innerHTML = '';
    }

    // ปิด dropdown เมื่อคลิกที่อื่นข้างนอก
    document.addEventListener('click', function(e) {
        if (e.target !== inputSearch && e.target !== dropdown && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
});

</script>