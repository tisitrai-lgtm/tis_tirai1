<?php /** includes/settings/logs.php — System Logs */ ?>
<div class="card card-full">
    <div class="card-hd purple">
        <div class="card-hd-l"><i class="fa-solid fa-clock-rotate-left"></i><span>ประวัติการทำงาน</span></div>
        <select id="log-limit" onchange="loadLogs()" style="background:#fff;border-radius:5px;border:none;padding:4px 8px;font-size:.8rem;cursor:pointer;">
            <option value="50" selected>50 รายการ</option>
            <option value="100">100 รายการ</option>
            <option value="999999">ทั้งหมด</option>
        </select>
    </div>
    <div class="card-bd" id="log-container" style="max-height:400px;overflow-y:auto;overflow-x:auto;padding:0;"></div>
</div>