<?php
/**
 * api_get_logs.php — ดึงประวัติการทำงาน (System Logs) รองรับการกรองตามวันที่และค้นหา
 * TIS SMART FIELD - ฝ่ายไร่
 */
require_once 'config.php';
session_start();

if (!isset($_SESSION['emp_id']) || ($_SESSION['emp_level'] ?? '') !== 'a') {
    die('<div class="empty-list" style="padding:24px; color:#e11d48;">คุณไม่มีสิทธิ์เข้าถึงข้อมูลนี้</div>');
}

$date_from   = trim($_GET['date_from'] ?? ($_GET['date'] ?? ''));
$date_to     = trim($_GET['date_to'] ?? '');
$search_q    = trim($_GET['q'] ?? '');
$action_type = trim($_GET['type'] ?? '');
$limit       = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
if ($limit <= 0 || $limit > 1000) $limit = 999999;

$where = [];
$params = [];

if ($date_from !== '' && $date_to !== '') {
    if ($date_to < $date_from) {
        [$date_from, $date_to] = [$date_to, $date_from];
    }
    $where[] = "DATE(sl.created_at) BETWEEN :d_from AND :d_to";
    $params[':d_from'] = $date_from;
    $params[':d_to']   = $date_to;
} elseif ($date_from !== '') {
    $where[] = "DATE(sl.created_at) = :d_from";
    $params[':d_from'] = $date_from;
}

if ($search_q !== '') {
    $where[] = "(e.emp_name LIKE :sq OR sl.action_by LIKE :sq OR sl.action_type LIKE :sq OR sl.log_details LIKE :sq OR sl.target_id LIKE :sq)";
    $params[':sq'] = "%$search_q%";
}

if ($action_type !== '') {
    $where[] = "sl.action_type = :atype";
    $params[':atype'] = $action_type;
}

$where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$sql = "
    SELECT sl.*, e.emp_name, e.emp_unit 
    FROM system_logs sl 
    LEFT JOIN employee e ON sl.action_by = e.emp_id 
    $where_sql 
    ORDER BY sl.log_id DESC 
    LIMIT $limit
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($logs)) {
    echo '
    <div class="empty-list" style="padding:36px; text-align:center; color:#94a3b8;">
        <i class="fa-solid fa-clipboard-list" style="font-size:2rem; color:#cbd5e1; margin-bottom:8px; display:block;"></i>
        ไม่พบประวัติการทำงานในเงื่อนไขและวันที่ที่เลือก
    </div>';
    exit;
}
?>
<style>
    .log-table { width: 100%; border-collapse: collapse; font-size: 0.84rem; text-align: left; }
    .log-table th { background: #f8fafc; color: #475569; font-weight: 700; padding: 10px 14px; border-bottom: 2px solid #e2e8f0; position: sticky; top: 0; z-index: 2; white-space: nowrap; }
    .log-table td { padding: 9px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: #334155; }
    .log-table tr:hover { background-color: #f8fafc; }
    .log-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; }
    .log-badge.type-add, .log-badge.type-create { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
    .log-badge.type-edit, .log-badge.type-update { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .log-badge.type-del, .log-badge.type-delete { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    .log-badge.type-settings { background: #ede9fe; color: #5b21b6; border: 1px solid #ddd6fe; }
    .log-badge.type-login { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
    .log-badge.type-default { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
    
    .dark-mode .log-table th { background: #0f172a !important; color: #94a3b8 !important; border-bottom-color: #334155 !important; }
    .dark-mode .log-table td { border-bottom-color: #1e293b !important; color: #cbd5e1 !important; }
    .dark-mode .log-table tr:hover { background-color: #1e293b !important; }
</style>

<div class="log-wrap" style="overflow-x:auto;">
    <table class="log-table">
        <thead>
            <tr>
                <th style="width: 140px;">วันที่ / เวลา</th>
                <th style="width: 160px;">ผู้ดำเนินการ</th>
                <th style="width: 110px;">ประเภท</th>
                <th style="width: 120px;">เป้าหมาย</th>
                <th>รายละเอียดการทำงาน</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($logs as $log): 
            $raw_type = strtolower($log['action_type'] ?? '');
            $badge_class = 'type-default';
            if (str_contains($raw_type, 'add') || str_contains($raw_type, 'create') || str_contains($raw_type, 'insert')) {
                $badge_class = 'type-add';
            } elseif (str_contains($raw_type, 'edit') || str_contains($raw_type, 'update') || str_contains($raw_type, 'mod')) {
                $badge_class = 'type-edit';
            } elseif (str_contains($raw_type, 'del') || str_contains($raw_type, 'remove')) {
                $badge_class = 'type-del';
            } elseif (str_contains($raw_type, 'setting')) {
                $badge_class = 'type-settings';
            } elseif (str_contains($raw_type, 'login') || str_contains($raw_type, 'auth')) {
                $badge_class = 'type-login';
            }
        ?>
        <tr>
            <td>
                <div style="font-weight:700;font-size:.82rem;color:#1e293b;">
                    <?php echo date('d/m/Y', strtotime($log['created_at'])); ?>
                </div>
                <div style="font-size:.73rem;color:#94a3b8;">
                    <i class="fa-regular fa-clock" style="font-size:.7rem;"></i> <?php echo date('H:i:s น.', strtotime($log['created_at'])); ?>
                </div>
            </td>
            <td>
                <div style="font-weight:700;"><?php echo htmlspecialchars($log['emp_name'] ?? $log['action_by']); ?></div>
                <?php if(!empty($log['emp_unit'])): ?>
                <div style="font-size:.72rem;color:#64748b;"><?php echo htmlspecialchars($log['emp_unit']); ?></div>
                <?php endif; ?>
            </td>
            <td>
                <span class="log-badge <?php echo $badge_class; ?>">
                    <?php echo htmlspecialchars($log['action_type']); ?>
                </span>
            </td>
            <td style="font-family:monospace;font-weight:600;color:#0284c7;">
                <?php echo htmlspecialchars($log['target_id'] ?? '-'); ?>
            </td>
            <td>
                <?php echo htmlspecialchars($log['log_details']); ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>