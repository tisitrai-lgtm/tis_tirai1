<?php
/**
 * includes/feed_scripts.php
 * JavaScript ทั้งหมดของหน้า feed
 */
?>
<script>
// --- ลบ comment (Admin only) ---
function deleteReply(replyId, postId) {
    if(!confirm('ยืนยันลบความคิดเห็นนี้ออกจากระบบ?')) return;
    const fd = new FormData();
    fd.append('reply_id', replyId);
    fd.append('action', 'delete');
    fetch('reply_action.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        if(data.status === 'success') {
            const row = document.getElementById('reply-row-' + replyId);
            if(row) {
                row.style.transition = 'opacity .25s, max-height .3s';
                row.style.overflow = 'hidden';
                row.style.maxHeight = row.offsetHeight + 'px';
                row.style.opacity = '0';
                setTimeout(() => { row.style.maxHeight = '0'; row.style.padding = '0'; row.style.margin = '0'; }, 50);
                setTimeout(() => row.remove(), 350);
            }
        } else { alert(data.message); }
    });
}

// --- เปลี่ยน status โพสต์ (Admin only) ---
function toggleStatus(postId, currentStatus) {
    const newStatus = currentStatus === 'pending' ? 'success' : 'pending';
    const confirmMsg = newStatus === 'success' ? 'ยืนยันว่าดำเนินการเรื่องนี้เรียบร้อยแล้ว?' : 'เปลี่ยนกลับเป็น "รอดำเนินการ"?';
    if(!confirm(confirmMsg)) return;
    const fd = new FormData();
    fd.append('post_id', postId);
    fd.append('job_status', newStatus);
    fetch('post_status.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        if(data.status === 'success') {
            // อัปเดต UI ไม่ต้อง reload
            const card = document.getElementById('post-card-' + postId);
            const btn  = document.getElementById('status-btn-' + postId);
            if(card) {
                card.style.borderLeftColor = newStatus === 'success' ? '#10b981' : '#e11d48';
                // อัปเดต badge สถานะ
                const badge = card.querySelector('[data-status-badge]');
                if(badge) {
                    badge.textContent = newStatus === 'success' ? 'ดำเนินการแล้ว' : 'รอดำเนินการ';
                    badge.style.background = newStatus === 'success' ? '#d1fae5' : '#fee2e2';
                    badge.style.color = newStatus === 'success' ? '#065f46' : '#991b1b';
                }
            }
            if(btn) {
                btn.style.background = newStatus === 'success' ? '#e11d48' : '#10b981';
                btn.dataset.status = newStatus;
                btn.setAttribute('onclick', "toggleStatus("+postId+", '"+newStatus+"')");
                btn.innerHTML = newStatus === 'success'
                    ? '<i class="fa-solid fa-rotate-left"></i> เปลี่ยนเป็นรอดำเนินการ'
                    : '<i class="fa-solid fa-circle-check"></i> ยืนยันดำเนินการแล้ว';
            }
        } else { alert(data.message); }
    });
}

// --- ส่วนสคริปต์ควบคุมการลบโพสต์แอดมินกลาง ---
function deletePost(postId) {
    if(confirm('พี่แน่ใจใช่ไหมครับว่าจะลบรายการรถอ้อยสกปรกรายการนี้ออกจากระบบ?')) {
        let formData = new FormData();
        formData.append('post_id', postId);
        
        fetch('post_delete.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if(data.status === 'success') {
                document.getElementById('post-card-' + postId).remove();
            }
        });
    }
}

// --- ส่วนสคริปต์สลับเปิด-ปิด กล่องฟอร์มแก้ไขคอมเมนต์ ---
function enableEditMode(replyId) {
    document.getElementById('reply-text-' + replyId).style.display = 'none';
    document.getElementById('edit-box-' + replyId).style.display = 'block';
}

function cancelEdit(replyId) {
    document.getElementById('reply-text-' + replyId).style.display = 'block';
    document.getElementById('edit-box-' + replyId).style.display = 'none';
}

// --- ส่งข้อมูลอัปเดตคำคอมเมนต์ใหม่ทาง AJAX ---
function saveEdit(replyId) {
    let textValue = document.getElementById('edit-input-' + replyId).value;
    if(textValue.trim() === "") { alert("กรุณากรอกข้อความด้วยครับพี่"); return; }
    
    let formData = new FormData();
    formData.append('reply_id', replyId);
    formData.append('reply_text', textValue);
    
    fetch('reply_edit.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if(data.status === 'success') { location.reload(); }
    });
}

function displayFileName(input) {
    let form = input.closest('form');
    let previewLabel = form.querySelector('.file-status-preview');
    if (input.files && input.files.length > 0) { previewLabel.style.display = 'block'; } 
    else { previewLabel.style.display = 'none'; }
}

function togglePostForm() {
    let formBox = document.getElementById('adminPostForm');
    let textBtn = document.getElementById('toggleText');
    let iconBtn = document.getElementById('toggleIcon');
    if (formBox.style.display === 'block') {
        formBox.style.display = 'none'; textBtn.innerText = 'แจ้งเรื่องรถอ้อยสกปรกเพิ่ม'; iconBtn.className = 'fa-solid fa-circle-plus';
    } else {
        formBox.style.display = 'block'; textBtn.innerText = 'ปิดกล่องฟอร์มกรอกข้อมูล'; iconBtn.className = 'fa-solid fa-circle-minus';
        loadProblemOptions(); // ← เพิ่มบรรทัดนี้
    }
}

// ══ compress image via canvas (800px, quality 0.75) ══
function compressImage(file, callback) {
    const MAX = 800;
    const QUALITY = 0.75;
    const reader = new FileReader();
    reader.onload = function(e) {
        const img = new Image();
        img.onload = function() {2
            let w = img.width, h = img.height;
            if(w > MAX || h > MAX) {
                if(w > h) { h = Math.round(h * MAX / w); w = MAX; }
                else       { w = Math.round(w * MAX / h); h = MAX; }
            }
            const canvas = document.createElement('canvas');
            canvas.width = w; canvas.height = h;
            canvas.getContext('2d').drawImage(img, 0, 0, w, h);
            callback(canvas.toDataURL('image/jpeg', QUALITY));
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
}

function previewCompress(input, spanId, hiddenId) {
    if(!input.files || !input.files[0]) return;
    const file = input.files[0];
    document.getElementById(spanId).innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> กำลังบีบ...';
    compressImage(file, function(b64) {
        document.getElementById(hiddenId).value = b64;
        // แสดงขนาดโดยประมาณ
        const kb = Math.round(b64.length * 0.75 / 1024);
        document.getElementById(spanId).innerHTML = '<i class="fa-solid fa-check"></i> ' + file.name.substring(0,18) + ' (~' + kb + ' KB)';
    });
}

// โหลด problem options ลงทั้ง 3 select
function loadProblemOptions() {
    fetch('api_problem_types.php')
    .then(res => res.json())
    .then(data => {
        if(!data || data.status !== 'success') return;
        const sels = document.querySelectorAll('.prob-sel');
        sels.forEach((sel, idx) => {
            const placeholder = idx === 0 ? '-- ปัญหาที่ 1 (บังคับ) --' : '-- ปัญหาที่ ' + (idx+1) + ' (ถ้ามี) --';
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

if(document.getElementById('uploadForm')) {
    document.getElementById('uploadForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        // เพิ่ม problem_detail รวม 3 ช่อง
        const p1 = formData.get('problem_1') || '';
        const p2 = formData.get('problem_2') || '';
        const p3 = formData.get('problem_3') || '';
        const combined = [p1, p2, p3].filter(v => v.trim() !== '').join(' / ');
        if(!combined) { alert('กรุณาเลือกปัญหาที่พบอย่างน้อย 1 รายการ'); return; }
        formData.set('problem_detail', combined);
        if(!formData.get('img_b64_1') || formData.get('img_b64_1') === '') {
            alert('กรุณาแนบรูปภาพอย่างน้อย 1 รูป'); return;
        }
        fetch('post_create.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => { alert(data.message); if(data.status === 'success') { location.reload(); } });
    });
}

document.querySelectorAll('.replyForm').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        fetch('reply_action.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => { alert(data.message); if(data.status === 'success') { location.reload(); } });
    });
});
// --- ฟังก์ชันเลื่อนหน้าจออัตโนมัติไปหาโพสต์อ้อยที่ถูกแท็กจากกระดิ่งแจ้งเตือน ---
function scrollToTargetPost(postId, notiId) {
    // 1. ปิดหน้าต่าง Dropdown แจ้งเตือนก่อน
    document.getElementById("notiDropdown").classList.remove("show");
    
    // 2. ส่ง AJAX ไปบอกหลังบ้านว่าคอมพิวเตอร์เปิดอ่านแจ้งเตือนนี้แล้วนะ
    let formData = new FormData();
    formData.append('noti_id', notiId);
    fetch('noti_read_action.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        // ลดตัวเลขแจ้งเตือนบนหน้ากากเว็บออโต้
        let badge = document.getElementById("notiBadgeCount");
        if(badge) {
            let currentCount = parseInt(badge.innerText);
            if(currentCount > 1) { badge.innerText = currentCount - 1; } 
            else { badge.remove(); }
        }
    });

    // 3. ตรวจสอบว่ากล่องโพสต์นั้นอยู่ในหน้าจอปัจจุบันไหม
    let postCard = document.getElementById('post-card-' + postId);
    
    if(postCard) {
        // 🚀 เลื่อนหน้าจอลงไปหาแบบนุ่มนวล (Smooth Scroll)
        postCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        // ⚡ สาดแสงสีเหลืองกะพริบไฮไลท์ตอกย้ำให้พนักงานเห็นชัดเจน
        postCard.classList.add('highlight-target-post');
        
        // เมื่อแอนิเมชันจบ ให้ลบคลาสออกเพื่อคืนสู่สภาพเดิม
        setTimeout(() => {
            postCard.classList.remove('highlight-target-post');
        }, 2000);
    } else {
        // กรณีที่โพสต์นั้นอยู่คนละวัน ให้ reload หน้าเดิมเพื่อให้ชันค้นหาวันเอง
        alert("โพสต์นี้อาจอยู่วันอื่น กรุณาเลือกวันที่ค้นหาเองครับพี่");
    }
}
</script>