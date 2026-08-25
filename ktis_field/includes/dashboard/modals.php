<?php
/**
 * includes/dashboard/modals.php — Modals for Comparison and Inspection Details
 */
?>
<!-- Comparison Modal -->
<div id="comparisonModal" class="fixed inset-0 z-[99999] hidden items-center justify-center modal-backdrop p-4" style="z-index: 99999;">
    <div class="glass-card w-full max-w-4xl max-h-[90vh] rounded-2xl shadow-2xl flex flex-col overflow-hidden">
        <div class="p-4 md:p-5 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/50">
            <h3 class="font-extrabold text-lg text-slate-900 dark:text-white flex items-center gap-2">
                <i class="fa-solid fa-code-compare text-emerald-500"></i> เปรียบเทียบข้อมูลการโพสต์และผลการตรวจเช็ค
            </h3>
            <button type="button" onclick="closeComparisonModal()" class="w-8 h-8 rounded-xl bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 flex items-center justify-center hover:bg-slate-300 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-4 md:p-6 overflow-y-auto custom-scrollbar flex-1">
            <table class="w-full text-left text-xs md:text-sm border-collapse">
                <thead>
                    <tr class="bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold border-b">
                        <th class="py-2.5 px-3">เบอร์รถ</th>
                        <th class="py-2.5 px-3">หน่วยงาน</th>
                        <th class="py-2.5 px-3">จำนวนโพสต์</th>
                        <th class="py-2.5 px-3">สถานะตรวจเช็ค</th>
                        <th class="py-2.5 px-3">สถานะเปรียบเทียบ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    <?php if (!empty($comparison_rows)): ?>
                        <?php foreach ($comparison_rows as $comp): ?>
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40">
                            <td class="py-2.5 px-3 font-bold">
                                <a href="harvester_admin.php?date=<?php echo urlencode($filter_date); ?>&harvester=<?php echo urlencode($comp['harvester_number']); ?>" class="text-emerald-600 hover:text-emerald-700 hover:underline flex items-center gap-1" title="ดูรายละเอียดในหน้า Admin">
                                    #<?php echo $comp['short_number']; ?> <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                                </a>
                            </td>
                            <td class="py-2.5 px-3 text-slate-500"><?php echo htmlspecialchars($comp['unit_name']); ?></td>
                            <td class="py-2.5 px-3"><?php echo $comp['posts_cnt']; ?> ครั้ง</td>
                            <td class="py-2.5 px-3">
                                <?php if($comp['check_status']=='passed'): ?><span class="text-emerald-600 font-bold">ผ่าน</span>
                                <?php elseif($comp['check_status']=='failed'): ?><span class="text-rose-600 font-bold">ไม่ผ่าน</span>
                                <?php else: ?><span class="text-amber-600 font-bold">ยังไม่ตรวจ</span><?php endif; ?>
                            </td>
                            <td class="py-2.5 px-3">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold border <?php echo $comp['comp_bg']; ?>">
                                    <?php echo $comp['comp_label']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-6 text-slate-400">ไม่พบข้อมูลเปรียบเทียบ</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/50 flex justify-end">
            <button type="button" onclick="closeComparisonModal()" class="px-4 py-2 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl">ปิด</button>
        </div>
    </div>
</div>