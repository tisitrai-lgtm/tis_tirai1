<?php /** includes/settings/logs.php — System Logs with Date Range Filter */ ?>
<div class="card card-full">
    <div class="card-hd purple">
        <div class="card-hd-l">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>ประวัติการทำงาน (System Audit Logs)</span>
        </div>
        <div style="display:flex; align-items:center; gap:8px;">
            <select id="log-limit" onchange="loadLogs()" style="background:#fff;border-radius:6px;border:none;padding:5px 9px;font-size:.8rem;font-weight:700;color:#1e293b;cursor:pointer;">
                <option value="50" selected>50 รายการ</option>
                <option value="100">100 รายการ</option>
                <option value="500">500 รายการ</option>
                <option value="999999">ทั้งหมด</option>
            </select>
        </div>
    </div>

    <!-- แถบตัวกรองวันที่และการค้นหา -->
    <div style="background:#f8fafc; border-bottom:1px solid #e2e8f0; padding:12px 16px;">
        <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center; justify-content:space-between;">
            
            <!-- วันที่ & ค้นหา -->
            <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
                <div style="display:flex; align-items:center; gap:5px;">
                    <span style="font-size:.78rem; font-weight:700; color:#475569;"><i class="fa-solid fa-calendar-day" style="color:#8b5cf6;"></i> วันเริ่มต้น:</span>
                    <input type="date" id="log-date-from" onchange="loadLogs()" style="padding:5px 9px; border:1.5px solid #cbd5e1; border-radius:7px; font-size:.82rem; font-family:inherit; background:#fff; outline:none;" max="<?php echo date('Y-m-d'); ?>">
                </div>

                <div style="display:flex; align-items:center; gap:5px;">
                    <span style="font-size:.78rem; font-weight:700; color:#475569;"><i class="fa-solid fa-calendar-check" style="color:#8b5cf6;"></i> วันสิ้นสุด:</span>
                    <input type="date" id="log-date-to" onchange="loadLogs()" style="padding:5px 9px; border:1.5px solid #cbd5e1; border-radius:7px; font-size:.82rem; font-family:inherit; background:#fff; outline:none;" max="<?php echo date('Y-m-d'); ?>">
                </div>

                <div style="display:flex; align-items:center; gap:4px; margin-left:4px;">
                    <button type="button" class="btn-log-shortcut" onclick="setLogRange(0, 0)">วันนี้</button>
                    <button type="button" class="btn-log-shortcut" onclick="setLogRange(6, 0)">7 วัน</button>
                    <button type="button" class="btn-log-shortcut" onclick="setLogRange(29, 0)">30 วัน</button>
                    <button type="button" class="btn-log-shortcut" onclick="setLogMonthRange()">เดือนนี้</button>
                    <button type="button" class="btn-log-shortcut" onclick="clearLogDate()" style="color:#e11d48;"><i class="fa-solid fa-rotate-left"></i> ล้างวัน</button>
                </div>
            </div>

            <!-- ค้นหา Keyword -->
            <div style="display:flex; align-items:center; gap:6px; flex:1; max-width:280px; min-width:180px;">
                <div style="position:relative; width:100%;">
                    <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:9px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:.78rem;"></i>
                    <input type="text" id="log-search" oninput="loadLogsDebounced()" placeholder="ค้นหาชื่อ/การกระทำ..." style="width:100%; padding:6px 10px 6px 28px; border:1.5px solid #cbd5e1; border-radius:7px; font-size:.82rem; font-family:inherit; background:#fff; outline:none;">
                </div>
            </div>

        </div>
    </div>

    <!-- ตาราง Logs -->
    <div class="card-bd" id="log-container" style="max-height:480px;overflow-y:auto;overflow-x:auto;padding:0;">
        <div style="text-align:center; padding:30px; color:#94a3b8;"><i class="fa-solid fa-spinner fa-spin"></i> กำลังโหลดประวัติการทำงาน...</div>
    </div>
</div>

<style>
.btn-log-shortcut {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 5px;
    padding: 4px 8px;
    font-size: 0.75rem;
    font-weight: 700;
    color: #475569;
    cursor: pointer;
    transition: all .15s;
    font-family: inherit;
    white-space: nowrap;
}
.btn-log-shortcut:hover {
    background: #ede9fe;
    border-color: #8b5cf6;
    color: #5b21b6;
}
.dark-mode .btn-log-shortcut {
    background: #0f172a !important;
    border-color: #334155 !important;
    color: #cbd5e1 !important;
}
.dark-mode .btn-log-shortcut:hover {
    background: #1e293b !important;
    border-color: #8b5cf6 !important;
    color: #c4b5fd !important;
}
.dark-mode #log-date-from, .dark-mode #log-date-to, .dark-mode #log-search {
    background: #0f172a !important;
    border-color: #334155 !important;
    color: #f8fafc !important;
}
</style>