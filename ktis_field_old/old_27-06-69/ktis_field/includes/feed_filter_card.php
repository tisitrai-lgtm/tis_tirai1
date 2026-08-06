<?php
/**
 * includes/feed_filter_card.php
 * ส่วน filter วันที่ + status tabs
 * ต้องการตัวแปร: $search_date, $status_tab
 */
/**
 * includes/feed_filter_card.php
 * @var string $search_date
 * @var string $status_tab
 */
?>
        <div class="filter-card">
            <form method="GET" action="index.php" class="filter-form">
                <div class="form-group">
                    <label><i class="fa-solid fa-calendar-days"></i> เรียกดูข้อมูลประจำวันที่</label>
                    <input type="date" name="search_date" value="<?php echo $search_date; ?>" class="form-input">
                </div>
                <input type="hidden" name="status_tab" value="<?php echo $status_tab; ?>">
                <button type="submit" class="btn-search"><i class="fa-solid fa-magnifying-glass"></i> ค้นหา</button>
            </form>
            
            <div class="status-tabs">
                <a href="index.php?search_date=<?php echo $search_date; ?>&status_tab=all" class="tab-item <?php echo $status_tab == 'all' ? 'tab-all active' : 'tab-inactive'; ?>">ทั้งหมด</a>
                <a href="index.php?search_date=<?php echo $search_date; ?>&status_tab=pending" class="tab-item <?php echo $status_tab == 'pending' ? 'tab-pending active' : 'tab-inactive'; ?>">รอดำเนินการ</a>
                <a href="index.php?search_date=<?php echo $search_date; ?>&status_tab=success" class="tab-item <?php echo $status_tab == 'success' ? 'tab-success active' : 'tab-inactive'; ?>">ดำเนินการแล้ว</a>
            </div>
        </div>