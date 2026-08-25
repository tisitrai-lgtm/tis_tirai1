<?php
/**
 * includes/feed_filter_card.php
 * ส่วน filter วันที่ + status tabs
 * @var string $search_date
 * @var string $status_tab
 */
$today_str = date('Y-m-d');
$prev_date = date('Y-m-d', strtotime($search_date . ' -1 day'));
$next_date = date('Y-m-d', strtotime($search_date . ' +1 day'));
?>
<div class="filter-card">
    <form method="GET" action="index.php" class="filter-form">
        <div class="form-group">
            <div class="filter-label-row">
                <label for="search_date_input">
                    <i class="fa-solid fa-calendar-days" style="color:#e11d48; margin-right:4px;"></i> 
                    เรียกดูข้อมูลประจำวันที่
                </label>
                <div class="date-shortcuts">
                    <a href="index.php?search_date=<?php echo $prev_date; ?>&status_tab=<?php echo urlencode($status_tab); ?>" class="date-link">
                        <i class="fa-solid fa-chevron-left"></i> ย้อนหลัง
                    </a>
                    <?php if($search_date < $today_str): ?>
                    <a href="index.php?search_date=<?php echo $next_date; ?>&status_tab=<?php echo urlencode($status_tab); ?>" class="date-link">
                        ถัดไป <i class="fa-solid fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                    <?php if($search_date !== $today_str): ?>
                    <a href="index.php?search_date=<?php echo $today_str; ?>&status_tab=<?php echo urlencode($status_tab); ?>" class="date-link-today">
                        <i class="fa-solid fa-rotate-left"></i> วันนี้
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <input type="date" id="search_date_input" name="search_date" value="<?php echo $search_date; ?>" max="<?php echo $today_str; ?>" 
                   style="-webkit-appearance:none; appearance:none; min-width:0; max-width:100%; box-sizing:border-box; height:46px; display:block;"
                   class="form-input">
        </div>
        <input type="hidden" name="status_tab" value="<?php echo htmlspecialchars($status_tab); ?>">
        <button type="submit" class="btn-search">
            <i class="fa-solid fa-magnifying-glass"></i> ค้นหา
        </button>
    </form>
    
    <div class="status-tabs">
        <a href="index.php?search_date=<?php echo urlencode($search_date); ?>&status_tab=all" class="tab-item <?php echo $status_tab == 'all' ? 'active' : ''; ?>">
            <i class="fa-solid fa-layer-group"></i> ทั้งหมด
        </a>
        <a href="index.php?search_date=<?php echo urlencode($search_date); ?>&status_tab=pending" class="tab-item tab-pending <?php echo $status_tab == 'pending' ? 'active' : ''; ?>">
            <i class="fa-solid fa-clock"></i> รอดำเนินการ
        </a>
        <a href="index.php?search_date=<?php echo urlencode($search_date); ?>&status_tab=success" class="tab-item tab-success <?php echo $status_tab == 'success' ? 'active' : ''; ?>">
            <i class="fa-solid fa-circle-check"></i> ดำเนินการแล้ว
        </a>
    </div>
</div>