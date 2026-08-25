<?php /** includes/settings/settings_scripts.php — JS ทั้งหมดสำหรับ setting_system.php */ ?>
<script>
function showAlert(msg, type='success') {
    const a = document.getElementById('alert-area');
    a.innerHTML = `<div class="alert-box alert-${type}"><i class="fa-solid fa-${type==='success'?'circle-check':'circle-exclamation'}"></i>${msg}</div>`;
    setTimeout(() => { a.innerHTML = ''; }, 3500);
}
function fadeRemove(el){ if(!el)return; el.style.transition='opacity .2s'; el.style.opacity='0'; setTimeout(()=>el.remove(),200); }

// ── Problem Types ──
function addProblem() {
    const name = document.getElementById('new-prob').value.trim();
    if(!name){ showAlert('กรุณากรอกชื่อปัญหา','error'); return; }
    const fd=new FormData(); fd.append('problem_name',name);
    fetch('api_problem_types.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        if(d.status==='success'){
            document.getElementById('new-prob').value='';
            const list=document.getElementById('prob-list');
            list.querySelector('.empty-list')?.remove();
            list.insertAdjacentHTML('beforeend',`<div class="list-item" id="prob-${d.new_id}">
                <div class="list-item-text"><i class="fa-solid fa-circle-exclamation" style="color:#e11d48;font-size:.75rem;margin-right:5px;"></i>${name}</div>
                <button class="btn-del" onclick="deleteProblem(${d.new_id})"><i class="fa-solid fa-trash-can"></i> ลบ</button>
            </div>`);
            showAlert('เพิ่ม "'+name+'" เรียบร้อย');
        } else showAlert(d.message,'error');
    });
}
function deleteProblem(id){
    if(!confirm('ยืนยันลบประเภทปัญหานี้?'))return;
    fetch('api_problem_types.php',{method:'DELETE',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'problem_id='+id})
    .then(r=>r.json()).then(d=>{ if(d.status==='success'){ fadeRemove(document.getElementById('prob-'+id)); showAlert('ลบเรียบร้อย'); } else showAlert(d.message,'error'); });
}

// ── Zones ──
function addZone(){
    const zid=document.getElementById('new-zone-id').value.trim();
    const zname=document.getElementById('new-zone-name').value.trim();
    if(!zid||!zname){ showAlert('กรุณากรอกรหัสและชื่อหน่วยให้ครบ','error'); return; }
    const fd=new FormData(); fd.append('zone_id',zid); fd.append('zone_name',zname);
    fetch('api_zones.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        if(d.status==='success'){
            document.getElementById('new-zone-id').value='';
            document.getElementById('new-zone-name').value='';
            const list=document.getElementById('zone-list');
            list.querySelector('.empty-list')?.remove();
            list.insertAdjacentHTML('beforeend',`<div class="list-item" id="zone-${zid}">
                <div class="list-item-text"><span style="background:#e0f2fe;color:#0369a1;padding:1px 7px;border-radius:4px;font-size:.75rem;font-weight:700;margin-right:6px;">${zid}</span>${zname}</div>
                <button class="btn-del" onclick="deleteZone('${zid}')"><i class="fa-solid fa-trash-can"></i> ลบ</button>
            </div>`);
            showAlert('เพิ่มหน่วย '+zid+' '+zname+' เรียบร้อย');
        } else showAlert(d.message,'error');
    });
}
function deleteZone(zid){
    if(!confirm('ยืนยันลบหน่วยส่งเสริม '+zid+'?\n⚠️ จะส่งผลกับพนักงานที่สังกัดหน่วยนี้'))return;
    fetch('api_zones.php',{method:'DELETE',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'zone_id='+zid})
    .then(r=>r.json()).then(d=>{ if(d.status==='success'){ fadeRemove(document.getElementById('zone-'+zid)); showAlert('ลบหน่วย '+zid+' เรียบร้อย'); } else showAlert(d.message,'error'); });
}

// ── Check Items Cut ──
function addCutItem(){
    const name=document.getElementById('new-cut').value.trim();
    if(!name){ showAlert('กรุณากรอกชื่อรายการ','error'); return; }
    const fd=new FormData(); fd.append('action','add'); fd.append('item_name',name);
    fetch('api_check_items.php?table=cut',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        if(d.status==='success'){
            document.getElementById('new-cut').value='';
            const list=document.getElementById('cut-list');
            list.querySelector('.empty-list')?.remove();
            list.insertAdjacentHTML('beforeend',`<div class="list-item" id="cut-${d.new_id}">
                <div class="list-item-text"><i class="fa-solid fa-screwdriver-wrench" style="color:#3b82f6;font-size:.75rem;margin-right:5px;"></i>${name}</div>
                <div style="display:flex;gap:6px;">
                    <button class="btn-del" style="border-color:#bfdbfe;color:#3b82f6;" onclick="editCutItem(${d.new_id},'${name}')"><i class="fa-solid fa-pen"></i> แก้ไข</button>
                    <button class="btn-del" onclick="deleteCutItem(${d.new_id})"><i class="fa-solid fa-trash-can"></i> ลบ</button>
                </div>
            </div>`);
            showAlert('เพิ่ม "'+name+'" เรียบร้อย');
        } else showAlert(d.message,'error');
    });
}
function editCutItem(id,oldName){
    const n=prompt('แก้ไขชื่อ:',oldName); if(!n||n.trim()===oldName)return;
    const fd=new FormData(); fd.append('action','edit'); fd.append('item_id',id); fd.append('item_name',n.trim());
    fetch('api_check_items.php?table=cut',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        if(d.status==='success'){ document.getElementById('cut-'+id).querySelector('.list-item-text').innerHTML=`<i class="fa-solid fa-screwdriver-wrench" style="color:#3b82f6;font-size:.75rem;margin-right:5px;"></i>${n.trim()}`; showAlert('แก้ไขเรียบร้อย'); } else showAlert(d.message,'error');
    });
}
function deleteCutItem(id){
    if(!confirm('ยืนยันลบรายการนี้?'))return;
    const fd=new FormData(); fd.append('action','delete'); fd.append('item_id',id);
    fetch('api_check_items.php?table=cut',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{ if(d.status==='success'){ fadeRemove(document.getElementById('cut-'+id)); showAlert('ลบเรียบร้อย'); } else showAlert(d.message,'error'); });
}

// ── Check Items Field ──
function addFieldItem(){
    const name=document.getElementById('new-field').value.trim();
    if(!name){ showAlert('กรุณากรอกชื่อรายการ','error'); return; }
    const fd=new FormData(); fd.append('action','add'); fd.append('item_name',name);
    fetch('api_check_items.php?table=field',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        if(d.status==='success'){
            document.getElementById('new-field').value='';
            const list=document.getElementById('field-list');
            list.querySelector('.empty-list')?.remove();
            list.insertAdjacentHTML('beforeend',`<div class="list-item" id="field-${d.new_id}">
                <div class="list-item-text"><i class="fa-solid fa-seedling" style="color:#f59e0b;font-size:.75rem;margin-right:5px;"></i>${name}</div>
                <div style="display:flex;gap:6px;">
                    <button class="btn-del" style="border-color:#fde68a;color:#d97706;" onclick="editFieldItem(${d.new_id},'${name}')"><i class="fa-solid fa-pen"></i> แก้ไข</button>
                    <button class="btn-del" onclick="deleteFieldItem(${d.new_id})"><i class="fa-solid fa-trash-can"></i> ลบ</button>
                </div>
            </div>`);
            showAlert('เพิ่ม "'+name+'" เรียบร้อย');
        } else showAlert(d.message,'error');
    });
}
function editFieldItem(id,oldName){
    const n=prompt('แก้ไขชื่อ:',oldName); if(!n||n.trim()===oldName)return;
    const fd=new FormData(); fd.append('action','edit'); fd.append('item_id',id); fd.append('item_name',n.trim());
    fetch('api_check_items.php?table=field',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        if(d.status==='success'){ document.getElementById('field-'+id).querySelector('.list-item-text').innerHTML=`<i class="fa-solid fa-seedling" style="color:#f59e0b;font-size:.75rem;margin-right:5px;"></i>${n.trim()}`; showAlert('แก้ไขเรียบร้อย'); } else showAlert(d.message,'error');
    });
}
function deleteFieldItem(id){
    if(!confirm('ยืนยันลบรายการนี้?'))return;
    const fd=new FormData(); fd.append('action','delete'); fd.append('item_id',id);
    fetch('api_check_items.php?table=field',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{ if(d.status==='success'){ fadeRemove(document.getElementById('field-'+id)); showAlert('ลบเรียบร้อย'); } else showAlert(d.message,'error'); });
}

// ── Harvesters ──
function addHarvester(){
    const num=document.getElementById('new-hv-num').value.trim();
    const name=document.getElementById('new-hv-name').value.trim();
    if(!num){ showAlert('กรุณากรอกเบอร์รถตัด','error'); return; }
    const fd=new FormData(); fd.append('action','add'); fd.append('harvester_number',num); fd.append('harvester_name',name);
    fetch('api_harvesters.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        if(d.status==='success'){
            document.getElementById('new-hv-num').value='';
            document.getElementById('new-hv-name').value='';
            const list=document.getElementById('hv-list');
            list.querySelector('.empty-list')?.remove();
            list.insertAdjacentHTML('beforeend',`<div class="list-item" id="hv-${d.new_id}">
                <div><div class="list-item-text"><i class="fa-solid fa-tractor" style="color:#06b6d4;font-size:.75rem;margin-right:5px;"></i>${num}${name?` <span style="font-size:.75rem;color:#94a3b8;">(${name})</span>`:''}</div>
                <div class="list-item-sub"><span style="color:#10b981;">● ใช้งาน</span></div></div>
                <div style="display:flex;gap:5px;">
                    <button class="btn-del" style="border-color:#fde68a;color:#d97706;" onclick="toggleHarvester(${d.new_id},0)"><i class="fa-solid fa-ban"></i> ปลดระวาง</button>
                    <button class="btn-del" onclick="deleteHarvester(${d.new_id})"><i class="fa-solid fa-trash-can"></i> ลบ</button>
                </div>
            </div>`);
            showAlert('เพิ่มรถตัด "'+num+'" เรียบร้อย');
        } else showAlert(d.message,'error');
    });
}
function toggleHarvester(id,active){
    const fd=new FormData(); fd.append('action','toggle'); fd.append('harvester_id',id); fd.append('is_active',active);
    fetch('api_harvesters.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        if(d.status==='success'){ showAlert(active?'เปิดใช้งานเรียบร้อย':'ปลดระวางเรียบร้อย'); setTimeout(()=>location.reload(),800); }
        else showAlert(d.message,'error');
    });
}
function deleteHarvester(id){
    if(!confirm('ยืนยันลบรถตัดนี้? ข้อมูลการ assign จะถูกลบด้วย'))return;
    const fd=new FormData(); fd.append('action','delete'); fd.append('harvester_id',id);
    fetch('api_harvesters.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        if(d.status==='success'){ fadeRemove(document.getElementById('hv-'+id)); showAlert('ลบเรียบร้อย'); }
        else showAlert(d.message,'error');
    });
}


// ── System Settings ──
async function toggleMaintenanceQuick(){
    const inpMode = document.getElementById('inp-maintenance-mode');
    const inpMsg  = document.getElementById('inp-maintenance-msg');
    const inpUntil= document.getElementById('inp-maintenance-until');
    if(!inpMode) return;

    const currentVal = inpMode.value;
    const newVal = (currentVal === '1') ? '0' : '1';
    const confirmMsg = (newVal === '1') 
        ? '⚠️ ยืนยันการสั่งปิดปรับปรุงระบบชั่วคราว?\nผู้ใช้ทั่วไป (User) จะไม่สามารถเข้าใช้งานได้ทันที'
        : '🟢 ยืนยันการเปิดระบบให้ใช้งานตามปกติ?';

    if(!confirm(confirmMsg)) return;

    // บันทึก mode
    const fd = new FormData();
    fd.append('setting_key', 'maintenance_mode');
    fd.append('setting_value', newVal);
    
    try {
        const res = await fetch('api_settings.php', { method: 'POST', body: fd });
        const data = await res.json();
        if(data.status === 'success'){
            inpMode.value = newVal;

            // บันทึกข้อความและเวลาถ้ามี
            if(inpMsg) {
                const fdMsg = new FormData();
                fdMsg.append('setting_key', 'maintenance_message');
                fdMsg.append('setting_value', inpMsg.value.trim());
                await fetch('api_settings.php', { method: 'POST', body: fdMsg });
            }
            if(inpUntil) {
                const fdUntil = new FormData();
                fdUntil.append('setting_key', 'maintenance_until');
                fdUntil.append('setting_value', inpUntil.value.trim());
                await fetch('api_settings.php', { method: 'POST', body: fdUntil });
            }

            showAlert(newVal === '1' ? 'เปิดโหมดปิดปรับปรุงระบบเรียบร้อย (User เข้าไม่ได้)' : 'เปิดระบบให้ใช้งานตามปกติเรียบร้อย');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message, 'error');
        }
    } catch(err) {
        showAlert('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์', 'error');
    }
}

async function saveAllSettings(){
    const inputs=document.querySelectorAll('.setting-input');
    for(const inp of inputs){
        const fd=new FormData(); fd.append('setting_key',inp.dataset.key); fd.append('setting_value',inp.value.trim());
        const r=await fetch('api_settings.php',{method:'POST',body:fd});
        const d=await r.json();
        if(d.status!=='success'){ showAlert('เกิดข้อผิดพลาด: '+d.message,'error'); return; }
    }
    showAlert('บันทึกการตั้งค่าทั้งหมดเรียบร้อย ✓');
    setTimeout(() => location.reload(), 1000);
}

// ── Logs ──
let logDebounceTimer = null;
function loadLogsDebounced() {
    clearTimeout(logDebounceTimer);
    logDebounceTimer = setTimeout(loadLogs, 300);
}

function loadLogs() {
    const limit    = document.getElementById('log-limit')?.value || 50;
    const dateFrom = document.getElementById('log-date-from')?.value || '';
    const dateTo   = document.getElementById('log-date-to')?.value || '';
    const searchQ  = document.getElementById('log-search')?.value.trim() || '';

    const container = document.getElementById('log-container');
    if (!container) return;

    container.innerHTML = `
        <div style="padding: 16px;">
            <div class="skeleton skeleton-row" style="height:44px; margin-bottom:10px;"></div>
            <div class="skeleton skeleton-row" style="height:44px; margin-bottom:10px;"></div>
            <div class="skeleton skeleton-row" style="height:44px; margin-bottom:10px;"></div>
            <div class="skeleton skeleton-row" style="height:44px;"></div>
        </div>
    `;

    const params = new URLSearchParams({
        limit: limit,
        date_from: dateFrom,
        date_to: dateTo,
        q: searchQ
    });

    fetch('api_get_logs.php?' + params.toString())
        .then(r => r.text())
        .then(html => {
            container.innerHTML = html;
        })
        .catch(err => {
            container.innerHTML = '<div class="empty-list" style="padding:24px; color:#e11d48;">เกิดข้อผิดพลาดในการโหลดประวัติ</div>';
        });
}

function setLogRange(daysAgoFrom, daysAgoTo) {
    const today = new Date();
    const dFrom = new Date();
    dFrom.setDate(today.getDate() - daysAgoFrom);
    const dTo = new Date();
    dTo.setDate(today.getDate() - daysAgoTo);
    
    const formatDate = (d) => {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    };

    const elFrom = document.getElementById('log-date-from');
    const elTo   = document.getElementById('log-date-to');
    if (elFrom) elFrom.value = formatDate(dFrom);
    if (elTo) elTo.value = formatDate(dTo);
    loadLogs();
}

function setLogMonthRange() {
    const today = new Date();
    const y = today.getFullYear();
    const m = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');

    const elFrom = document.getElementById('log-date-from');
    const elTo   = document.getElementById('log-date-to');
    if (elFrom) elFrom.value = `${y}-${m}-01`;
    if (elTo) elTo.value = `${y}-${m}-${day}`;
    loadLogs();
}

function clearLogDate() {
    const elFrom = document.getElementById('log-date-from');
    const elTo   = document.getElementById('log-date-to');
    const elSearch = document.getElementById('log-search');
    if (elFrom) elFrom.value = '';
    if (elTo) elTo.value = '';
    if (elSearch) elSearch.value = '';
    loadLogs();
}

loadLogs();
</script>