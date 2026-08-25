<?php
/**
 * includes/dashboard/body_content.php — Main Body UI for Dashboard
 */
?>
<div class="page-wrapper">
    <?php include __DIR__ . '/../nav_u_sidebar.php'; ?>

    <div class="main-content">
        
        <!-- Header & Title Section -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider mb-1">
                    <i class="fa-solid fa-gauge-high"></i> ระบบบริหารจัดการรถตัดอ้อย
                </div>
                <h1 class="text-2xl md:text-3xl font-extrabold text-slate-900 dark:text-white flex items-center gap-3">
                    <span class="p-2.5 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 rounded-xl">
                        <i class="fa-solid fa-tractor"></i>
                    </span>
                    การเช็ครถตัดประจำวัน
                </h1>
                <p class="text-xs md:text-sm text-slate-500 dark:text-slate-400 mt-1">
                    ติดตาม ตรวจสอบ และเปรียบเทียบผลการตรวจเช็กรถตัดอ้อยประจำวัน (ปีการผลิต <?php echo htmlspecialchars($crop_year); ?>)
                </p>
            </div>

            <!-- Action Buttons Header -->
            <div class="flex items-center gap-2 flex-wrap">
                <a href="harvester_map.php" 
                   class="px-4 py-2.5 bg-gradient-to-r from-sky-600 to-blue-600 hover:from-sky-700 hover:to-blue-700 text-white text-xs md:text-sm font-bold rounded-xl shadow-md hover:shadow-lg transition-all duration-200 flex items-center gap-2">
                    <i class="fa-solid fa-map-location-dot"></i>
                    <span>แผนที่พิกัดรถตัด (GIS Map)</span>
                </a>
                <button type="button" onclick="openComparisonModal()" 
                        class="px-4 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-xs md:text-sm font-bold rounded-xl shadow-md hover:shadow-lg transition-all duration-200 flex items-center gap-2">
                    <i class="fa-solid fa-code-compare"></i>
                    <span>เปรียบเทียบข้อมูล (Comparison View)</span>
                </button>
                <a href="?date=<?php echo date('Y-m-d'); ?>" 
                   class="px-3.5 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-xs md:text-sm font-semibold rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 transition flex items-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-calendar-day text-emerald-500"></i>
                    <span>วันนี้</span>
                </a>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="glass-card rounded-2xl p-4 mb-6 shadow-sm">
            <form method="GET" action="harvester_daily_dashboard.php" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter_status); ?>">

                <!-- วันที่ -->
                <div class="sm:col-span-5 md:col-span-4 w-full">
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">
                        <i class="fa-solid fa-calendar-days text-emerald-500 mr-1"></i> วันที่
                    </label>
                    <input type="date" name="date" value="<?php echo htmlspecialchars($filter_date); ?>"
                           max="<?php echo date('Y-m-d'); ?>"
                           style="-webkit-appearance:none; appearance:none; min-width:0; max-width:100%; box-sizing:border-box; height:42px; display:block;"
                           class="w-full px-3 py-2.5 bg-white dark:bg-slate-800 border-2 border-emerald-400 rounded-xl text-sm font-bold focus:ring-2 focus:ring-emerald-500 outline-none text-slate-800 dark:text-slate-100 shadow-sm">
                    <div class="text-[11px] text-slate-400 mt-1 flex gap-3 flex-wrap">
                        <?php
                        $today = date('Y-m-d');
                        $prev  = date('Y-m-d', strtotime($filter_date.' -1 day'));
                        $next  = date('Y-m-d', strtotime($filter_date.' +1 day'));
                        ?>
                        <a href="?date=<?php echo $prev; ?>&status=<?php echo urlencode($filter_status); ?>" class="text-emerald-600 font-bold hover:underline"><i class="fa-solid fa-chevron-left"></i> ย้อนหลัง</a>
                        <?php if($filter_date < $today): ?>
                        <a href="?date=<?php echo $next; ?>&status=<?php echo urlencode($filter_status); ?>" class="text-emerald-600 font-bold hover:underline">ถัดไป <i class="fa-solid fa-chevron-right"></i></a>
                        <?php endif; ?>
                        <?php if($filter_date !== $today): ?>
                        <a href="?date=<?php echo $today; ?>" class="text-rose-500 font-bold hover:underline ml-auto">วันนี้</a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ค้นหาเบอร์รถ -->
                <div class="sm:col-span-4 md:col-span-5 w-full">
                    <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">
                        <i class="fa-solid fa-magnifying-glass text-rose-500 mr-1"></i> ค้นหาเบอร์รถ
                    </label>
                    <input type="text" name="q" value="<?php echo htmlspecialchars($search_q); ?>" placeholder="เช่น รถตัดเบอร์ 1 หรือ 71"
                           style="-webkit-appearance:none; appearance:none; min-width:0; max-width:100%; box-sizing:border-box; height:42px; display:block;"
                           class="w-full px-3 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 outline-none text-slate-800 dark:text-slate-100">
                </div>

                <!-- ปุ่มกรอง & ล้าง -->
                <div class="sm:col-span-3 md:col-span-3 flex gap-2 w-full">
                    <button type="submit" class="flex-1 py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-xl shadow-sm transition flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-filter"></i> กรอง
                    </button>
                    <?php if($search_q || $filter_date !== $today): ?>
                    <a href="harvester_daily_dashboard.php" class="py-2.5 px-3.5 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-sm font-semibold rounded-xl hover:bg-slate-300 transition flex items-center justify-center" title="ล้าง">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- KPI Cards -->
        <?php
        $base_url = '?date='.urlencode($filter_date);
        $kpi_cards = [
            ['status'=>'',     'label'=>'รถตัดทั้งหมด',    'count'=>$total_harvesters, 'sub'=>'ในฐานข้อมูล',         'icon'=>'fa-tractor',            'color'=>'slate',  'bar'=>'bg-slate-400'],
            ['status'=>'pending','label'=>'ยังไม่ได้ตรวจ',   'count'=>$cnt_pending,      'sub'=>'รอการลงพื้นที่ตรวจ',   'icon'=>'fa-clock',              'color'=>'amber',  'bar'=>'bg-amber-500'],
            ['status'=>'passed', 'label'=>'ผ่านการตรวจ',     'count'=>$cnt_passed,       'sub'=>'อุปกรณ์พร้อมทำงาน',    'icon'=>'fa-circle-check',       'color'=>'emerald','bar'=>'bg-emerald-500'],
            ['status'=>'failed', 'label'=>'ไม่ผ่านการตรวจ',  'count'=>$cnt_failed,       'sub'=>'พบอุปกรณ์ชำรุด/บกพร่อง','icon'=>'fa-triangle-exclamation','color'=>'rose',   'bar'=>'bg-rose-500'],
            ['status'=>'alert',  'label'=>'โพสต์ 3 วันติด',  'count'=>$cnt_alerts,       'sub'=>'ต้องลงตรวจด่วน!',       'icon'=>'fa-bell',               'color'=>'red',    'bar'=>'bg-gradient-to-r from-rose-600 to-red-600','extra'=>'col-span-2 md:col-span-1'],
        ];
        $color_map = [
            'slate'  => ['text'=>'text-slate-500 dark:text-slate-400',  'num'=>'text-slate-900 dark:text-white',         'sub'=>'text-slate-400',                     'icon_bg'=>'bg-slate-100 dark:bg-slate-800',        'icon_txt'=>'text-slate-600 dark:text-slate-300', 'border_active'=>'border-slate-500',  'border_hover'=>'hover:border-slate-400'],
            'amber'  => ['text'=>'text-amber-600 dark:text-amber-400',  'num'=>'text-amber-600 dark:text-amber-400',     'sub'=>'text-amber-700/60 dark:text-amber-400/60','icon_bg'=>'bg-amber-500/10',                  'icon_txt'=>'text-amber-600 dark:text-amber-400', 'border_active'=>'border-amber-500',  'border_hover'=>'hover:border-amber-300'],
            'emerald'=> ['text'=>'text-emerald-600 dark:text-emerald-400','num'=>'text-emerald-600 dark:text-emerald-400','sub'=>'text-emerald-700/60 dark:text-emerald-400/60','icon_bg'=>'bg-emerald-500/10',              'icon_txt'=>'text-emerald-600 dark:text-emerald-400','border_active'=>'border-emerald-500','border_hover'=>'hover:border-emerald-300'],
            'rose'   => ['text'=>'text-rose-600 dark:text-rose-400',    'num'=>'text-rose-600 dark:text-rose-400',       'sub'=>'text-rose-700/60 dark:text-rose-400/60', 'icon_bg'=>'bg-rose-500/10',                     'icon_txt'=>'text-rose-600 dark:text-rose-400',   'border_active'=>'border-rose-500',   'border_hover'=>'hover:border-rose-300'],
            'red'    => ['text'=>'text-rose-700 dark:text-rose-300',    'num'=>'text-rose-700 dark:text-rose-300',       'sub'=>'text-rose-600 dark:text-rose-400 font-semibold','icon_bg'=>'bg-rose-600',                 'icon_txt'=>'text-white',                         'border_active'=>'border-red-500',    'border_hover'=>'hover:border-red-400'],
        ];
        ?>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3.5 mb-6">
        <?php foreach($kpi_cards as $kc):
            $c = $color_map[$kc['color']];
            $is_active = ($filter_status === $kc['status']);
            $link = $kc['status'] === '' ? $base_url : $base_url.'&status='.$kc['status'];
            $active_cls = $is_active ? 'ring-2 ring-offset-1 '.$c['border_active'].' border-2 '.$c['border_active'] : 'border border-slate-200 dark:border-slate-700 '.$c['border_hover'];
            $extra = $kc['extra'] ?? '';
        ?>
        <a href="<?php echo $link; ?>"
           class="glass-card rounded-2xl p-4 flex flex-col justify-between shadow-sm relative overflow-hidden transition cursor-pointer no-underline <?php echo $active_cls.' '.$extra; ?>"
           style="text-decoration:none;">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold <?php echo $c['text']; ?>">
                    <?php if($kc['color']==='red'): ?><i class="fa-solid fa-fire pulse-subtle mr-1"></i><?php endif; ?>
                    <?php echo $kc['label']; ?>
                </span>
                <span class="w-9 h-9 rounded-xl <?php echo $c['icon_bg']; ?> <?php echo $c['icon_txt']; ?> flex items-center justify-center text-base">
                    <i class="fa-solid <?php echo $kc['icon']; ?>"></i>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-2xl md:text-3xl font-extrabold <?php echo $c['num']; ?>"><?php echo number_format($kc['count']); ?></div>
                <div class="text-[11px] <?php echo $c['sub']; ?> mt-0.5"><?php echo $kc['sub']; ?></div>
            </div>
            <?php if($is_active): ?>
            <div class="absolute top-0 right-0 p-1.5">
                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded <?php echo $c['border_active']; ?> border <?php echo $c['text']; ?> bg-white/80">กำลังดู</span>
            </div>
            <?php endif; ?>
            <div class="absolute bottom-0 left-0 right-0 h-1 <?php echo $kc['bar']; ?>"></div>
        </a>
        <?php endforeach; ?>
        </div>

        <!-- Harvester List Table -->
        <div class="glass-card rounded-2xl shadow-sm overflow-hidden mb-8">
            <div class="p-4 md:p-5 border-b border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h3 class="font-extrabold text-base md:text-lg text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="fa-solid fa-list-check text-emerald-500"></i>
                        รายการรถตัดอ้อยและการตรวจเช็คประจำวัน
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        แสดงข้อมูลประจำวันที่ <span class="font-bold text-slate-700 dark:text-slate-200"><?php echo thai_date_fmt($filter_date, $thai_months ?? []); ?></span>
                        (<?php echo count($table_rows); ?> คัน)
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left text-xs md:text-sm border-collapse min-w-[850px]">
                    <thead>
                        <tr class="bg-slate-100/80 dark:bg-slate-800/80 text-slate-600 dark:text-slate-300 font-bold border-b border-slate-200 dark:border-slate-700">
                            <th class="py-3.5 px-4">เบอร์รถตัด</th>
                            <th class="py-3.5 px-4">โพสต์วันนี้</th>
                            <th class="py-3.5 px-4">สถานะการตรวจ</th>
                            <th class="py-3.5 px-4">ผู้ตรวจ</th>
                            <th class="py-3.5 px-4 text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        <?php if(empty($table_rows)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-10 text-slate-400">
                                    <i class="fa-solid fa-folder-open text-3xl mb-2 block"></i>
                                    ไม่พบข้อมูลรถตัดตรงเงื่อนไขการค้นหา
                                </td>
                            </tr>
                        <?php else:
                            $total_rows  = count($table_rows);
                            $total_pages = max(1, (int)ceil($total_rows / $per_page));
                            $page        = min($page, $total_pages);
                            $offset_rows = ($page - 1) * $per_page;
                            $page_rows   = array_slice($table_rows, $offset_rows, $per_page);
                        ?>
                            <?php foreach($page_rows as $idx => $r): ?>
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition <?php echo $r['has_alert_3d']?'bg-rose-50/40 dark:bg-rose-950/20':''; ?>">
                                    <td class="py-3.5 px-4 font-bold flex items-center gap-2">
                                        <span>#<?php echo $r['short_number']; ?></span>
                                        <?php if($r['has_alert_3d']): ?>
                                            <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-rose-100 dark:bg-rose-900/60 text-rose-600 dark:text-rose-300 border border-rose-300 dark:border-rose-700 flex items-center gap-1 animate-pulse" title="มีโพสต์แจ้งปัญหาติดต่อกัน 3 วันขึ้นไป!">
                                                <i class="fa-solid fa-triangle-exclamation"></i> โพสต์ 3 วันติด
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3.5 px-4"><?php echo $r['posts_cnt']; ?> โพสต์</td>
                                    <td class="py-3.5 px-4">
                                        <?php if($r['check_status']=='passed'): ?><span class="text-emerald-600 font-bold">ผ่านการตรวจ</span>
                                        <?php elseif($r['check_status']=='failed'): ?><span class="text-rose-600 font-bold">ไม่ผ่านการตรวจ</span>
                                        <?php else: ?><span class="text-amber-600 font-bold">ยังไม่ได้ตรวจ</span><?php endif; ?>
                                    </td>
                                    <td class="py-3.5 px-4"><?php echo htmlspecialchars($r['inspector_name']); ?></td>
                                    <td class="py-3.5 px-4 text-center">
                                        <a href="harvester_admin.php?date=<?php echo urlencode($filter_date); ?>&harvester=<?php echo urlencode($r['harvester_number']); ?>"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition shadow-sm no-underline hover:text-white">
                                            <i class="fa-solid fa-eye text-[11px]"></i> ดูรายละเอียด
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Controls -->
            <?php if (!empty($table_rows) && $total_pages > 1): 
                $start_row = $offset_rows + 1;
                $end_row   = min($offset_rows + $per_page, $total_rows);
                $build_page_link = function($p) use ($filter_date, $search_q, $filter_status) {
                    $params = ['page' => $p];
                    if (!empty($filter_date))   $params['date'] = $filter_date;
                    if ($search_q !== '')       $params['q'] = $search_q;
                    if ($filter_status !== '')  $params['status'] = $filter_status;
                    return '?' . http_build_query($params);
                };
            ?>
            <div class="p-4 border-t border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row items-center justify-between gap-3 bg-slate-50/50 dark:bg-slate-800/30">
                <div class="text-xs text-slate-500 dark:text-slate-400">
                    แสดงรายการที่ <span class="font-bold text-slate-700 dark:text-slate-200"><?php echo $start_row; ?></span> - <span class="font-bold text-slate-700 dark:text-slate-200"><?php echo $end_row; ?></span> จากทั้งหมด <span class="font-bold text-slate-700 dark:text-slate-200"><?php echo $total_rows; ?></span> คัน (หน้า <?php echo $page; ?>/<?php echo $total_pages; ?>)
                </div>

                <div class="flex items-center gap-1.5">
                    <!-- ปุ่มก่อนหน้า (Prev) -->
                    <?php if ($page > 1): ?>
                        <a href="<?php echo $build_page_link($page - 1); ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-emerald-50 hover:border-emerald-300 hover:text-emerald-600 transition text-xs font-semibold flex items-center gap-1">
                            <i class="fa-solid fa-chevron-left text-[10px]"></i> ก่อนหน้า
                        </a>
                    <?php else: ?>
                        <span class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 text-slate-300 dark:text-slate-600 cursor-not-allowed text-xs font-semibold flex items-center gap-1">
                            <i class="fa-solid fa-chevron-left text-[10px]"></i> ก่อนหน้า
                        </span>
                    <?php endif; ?>

                    <!-- ตัวเลขหน้า (Page Numbers) -->
                    <div class="flex items-center gap-1">
                        <?php
                        $start_p = max(1, $page - 2);
                        $end_p   = min($total_pages, $page + 2);
                        
                        if ($start_p > 1) {
                            echo '<a href="'.$build_page_link(1).'" class="w-8 h-8 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-emerald-50 hover:text-emerald-600 flex items-center justify-center text-xs font-semibold">1</a>';
                            if ($start_p > 2) {
                                echo '<span class="px-1 text-slate-400 text-xs">...</span>';
                            }
                        }

                        for ($p = $start_p; $p <= $end_p; $p++):
                            $is_cur = ($p === $page);
                        ?>
                            <?php if ($is_cur): ?>
                                <span class="w-8 h-8 rounded-lg bg-emerald-600 text-white font-bold flex items-center justify-center text-xs shadow-sm">
                                    <?php echo $p; ?>
                                </span>
                            <?php else: ?>
                                <a href="<?php echo $build_page_link($p); ?>" class="w-8 h-8 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-emerald-50 hover:border-emerald-300 hover:text-emerald-600 transition flex items-center justify-center text-xs font-semibold">
                                    <?php echo $p; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php
                        if ($end_p < $total_pages) {
                            if ($end_p < $total_pages - 1) {
                                echo '<span class="px-1 text-slate-400 text-xs">...</span>';
                            }
                            echo '<a href="'.$build_page_link($total_pages).'" class="w-8 h-8 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-emerald-50 hover:text-emerald-600 flex items-center justify-center text-xs font-semibold">'.$total_pages.'</a>';
                        }
                        ?>
                    </div>

                    <!-- ปุ่มถัดไป (Next) -->
                    <?php if ($page < $total_pages): ?>
                        <a href="<?php echo $build_page_link($page + 1); ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-emerald-50 hover:border-emerald-300 hover:text-emerald-600 transition text-xs font-semibold flex items-center gap-1">
                            ถัดไป <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        </a>
                    <?php else: ?>
                        <span class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 text-slate-300 dark:text-slate-600 cursor-not-allowed text-xs font-semibold flex items-center gap-1">
                            ถัดไป <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Scripts สำหรับเปิด/ปิด Modal -->
<script>
function openComparisonModal() {
    document.getElementById('comparisonModal').classList.remove('hidden');
    document.getElementById('comparisonModal').classList.add('flex');
}
function closeComparisonModal() {
    document.getElementById('comparisonModal').classList.add('hidden');
    document.getElementById('comparisonModal').classList.remove('flex');
}
function openManagerModal() {
    document.getElementById('managerModal').classList.remove('hidden');
    document.getElementById('managerModal').classList.add('flex');
}
function closeManagerModal() {
    document.getElementById('managerModal').classList.add('hidden');
    document.getElementById('managerModal').classList.remove('flex');
}
function filterAlertOnly() {
    window.location.href = '?date=<?php echo urlencode($filter_date); ?>&status=alert';
}
</script>
<?php include __DIR__ . '/manager_popup.php'; ?>
<?php include __DIR__ . '/modals.php'; ?>