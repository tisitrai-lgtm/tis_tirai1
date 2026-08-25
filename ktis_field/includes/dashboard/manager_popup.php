<?php
/**
 * includes/dashboard/manager_popup.php — Manager Info Popup / Modal
 */
// คุณสามารถใส่โครงสร้าง Modal สำหรับแสดงข้อมูลผู้ดูแลรถตัดตรงนี้ได้ครับ
?>
<div id="managerModal" class="fixed inset-0 z-[99999] hidden items-center justify-center modal-backdrop p-4" style="z-index: 99999;">
    <div class="glass-card w-full max-w-md rounded-2xl p-6 shadow-xl relative">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-700">
            <h3 class="font-extrabold text-base text-slate-900 dark:text-white">ข้อมูลผู้ดูแลรถตัด</h3>
            <button type="button" onclick="closeManagerModal()" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        <div class="py-4 text-sm text-slate-600 dark:text-slate-300" id="managerModalContent">
            <!-- ข้อมูลผู้ดูแลจะถูกโหลดใส่ที่นี่ด้วย JavaScript -->
            กำลังโหลดข้อมูล...
        </div>
        <div class="flex justify-end pt-3 border-t border-slate-200 dark:border-slate-700">
            <button type="button" onclick="closeManagerModal()" class="px-4 py-2 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl">ปิด</button>
        </div>
    </div>
</div>