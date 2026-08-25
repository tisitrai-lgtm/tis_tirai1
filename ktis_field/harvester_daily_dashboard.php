<?php
/**
 * harvester_daily_dashboard.php — Admin Dashboard การเช็ครถตัดประจำวัน
 */

// 1. โหลด Data Logic และตัวแปรทั้งหมด
require_once __DIR__ . '/includes/dashboard/data_logic.php';

// 2. โหลด Header ส่วนกลางของเว็บ (เช่น Sidebar Nav Header)
include __DIR__ . '/includes/nav_u_header.php';

// 3. โหลด Header เฉพาะของ Dashboard (CSS / Tailwind Config)
include __DIR__ . '/includes/dashboard/header.php';
?>

<!-- 4. โหลดเนื้อหาหลักของหน้าแดชบอร์ด -->
<?php include __DIR__ . '/includes/dashboard/body_content.php'; ?>