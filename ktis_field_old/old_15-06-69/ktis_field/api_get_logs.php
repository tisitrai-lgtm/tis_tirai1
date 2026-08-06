<?php
require_once 'config.php'; // เรียกไฟล์ config ที่มีตัวแปร $conn
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
if($limit > 1000) $limit = 999999; // กันไว้กรณีเลือก "ทั้งหมด"

$sql = "SELECT sl.*, e.emp_name FROM system_logs sl LEFT JOIN employee e ON sl.action_by = e.emp_id ORDER BY sl.log_id DESC LIMIT $limit";
$logs = $conn->query($sql)->fetchAll();

if(empty($logs)) { echo '<div class="empty-list" style="padding:32px;">ยังไม่มีประวัติ</div>'; exit; }
?>
<div class="log-wrap">
    <table>
        <thead>
            <tr><th>วันที่/เวลา</th><th>ผู้ดำเนินการ</th><th>ประเภท</th><th>เป้าหมาย</th><th>รายละเอียด</th></tr>
        </thead>
        <tbody>
        <?php foreach($logs as $log): ?>
        <tr>
            <td>
                <div style="font-weight:700;font-size:.8rem;"><?php echo date('d M Y', strtotime($log['created_at'])); ?></div>
                <div style="font-size:.73rem;color:#94a3b8;"><?php echo date('H:i น.', strtotime($log['created_at'])); ?></div>
            </td>
            <td><?php echo htmlspecialchars($log['emp_name'] ?? $log['action_by']); ?></td>
            <td><?php echo htmlspecialchars($log['action_type']); ?></td>
            <td style="font-family:monospace;"><?php echo htmlspecialchars($log['target_id'] ?? '-'); ?></td>
            <td><?php echo htmlspecialchars($log['log_details']); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>