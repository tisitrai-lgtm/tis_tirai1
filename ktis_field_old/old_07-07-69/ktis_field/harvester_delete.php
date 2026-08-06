<?php
/**
 * harvester_delete.php — ลบผลตรวจรถตัดอ้อย
 */
date_default_timezone_set('Asia/Bangkok');
require_once 'config.php';
session_start();

if(!isset($_SESSION["emp_id"])){ header("location: login.php"); exit; }
if(($_SESSION['emp_level'] ?? 'u') === 'a'){ header("location: harvester_admin.php"); exit; }

$session_id = (int)($_GET['id'] ?? 0);
if(!$session_id){ header("Location: harvester.php"); exit; }

// ── ดึงข้อมูล session เพื่อยืนยันก่อนลบ ──
$sess_data = null;
try {
    $st = $conn->prepare("SELECT * FROM check_sessions WHERE session_id=:sid");
    $st->execute([':sid'=>$session_id]);
    $sess_data = $st->fetch();
} catch(Exception $e){}

if(!$sess_data){ header("Location: harvester.php"); exit; }

// ── ป้องกัน: ลบได้เฉพาะของตัวเอง (หรือ admin) ──
if($sess_data['emp_id'] != $_SESSION['emp_id'] && ($_SESSION['emp_level'] ?? 'u') !== 'a'){
    header("Location: harvester.php"); exit;
}

// ── POST: ยืนยันลบจริง ──
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '')==='confirm_delete'){
    try {
        // ลบ check_results ก่อน (FK)
        $conn->prepare("DELETE FROM check_results WHERE session_id=:sid")->execute([':sid'=>$session_id]);
        // ลบ check_sessions
        $conn->prepare("DELETE FROM check_sessions WHERE session_id=:sid")->execute([':sid'=>$session_id]);

        $_SESSION['flash_status']='success';
        $_SESSION['flash_msg']="ลบผลตรวจรถตัดเบอร์ <strong>".htmlspecialchars($sess_data['harvester_number'])."</strong> เรียบร้อยแล้ว";
        header("Location: harvester.php"); exit;
    } catch(Exception $e){
        $_SESSION['flash_status']='error';
        $_SESSION['flash_msg']='เกิดข้อผิดพลาดขณะลบ: '.$e->getMessage();
        header("Location: harvester.php"); exit;
    }
}

// ── GET: แสดงหน้ายืนยัน ──
$thai_months=['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน',
              'กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
$dt = strtotime($sess_data['checked_at']);
$date_str = (int)date('d',$dt).' '.$thai_months[(int)date('m',$dt)].' '.((int)date('Y',$dt)+543);
$time_str = date('H:i น.',$dt);

// นับจำนวนรายการ
$total=0; $pass=0;
try {
    $r = $conn->prepare("SELECT COUNT(*) AS t, SUM(pass) AS p FROM check_results WHERE session_id=:sid");
    $r->execute([':sid'=>$session_id]);
    $row=$r->fetch(); $total=(int)$row['t']; $pass=(int)$row['p'];
} catch(Exception $e){}

include 'includes/nav_u_header.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ลบผลตรวจรถตัดอ้อย - KTIS SMART FIELD</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;}
body{font-family:'Sarabun',sans-serif;background:#f1f5f9;margin:0;}
.content-wrapper{flex:1 0 auto;}
.page-wrap{max-width:520px;margin:40px auto;padding:0 14px 60px;}

.page-header{display:flex;align-items:center;gap:12px;margin-bottom:24px;}
.page-header-icon{width:46px;height:46px;background:linear-gradient(135deg,#e11d48,#be123c);border-radius:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.page-header-icon i{color:#fff;font-size:1.3rem;}
.page-header-title{font-size:1.15rem;font-weight:700;color:#1e293b;margin-bottom:2px;}
.page-header-sub{font-size:.8rem;color:#64748b;}

.confirm-card{background:#fff;border-radius:14px;border:1.5px solid #fecaca;overflow:hidden;box-shadow:0 8px 24px rgba(225,29,72,.08);}
.confirm-card-header{background:#fff1f2;padding:18px 22px;border-bottom:1px solid #fecaca;display:flex;align-items:center;gap:10px;}
.confirm-card-header i{color:#e11d48;font-size:1.1rem;}
.confirm-card-header span{font-weight:700;color:#9f1239;font-size:.95rem;}
.confirm-card-body{padding:24px 22px;}

.warn-icon{width:64px;height:64px;background:#fee2e2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;}
.warn-icon i{color:#e11d48;font-size:1.8rem;}

.confirm-title{text-align:center;font-size:1rem;font-weight:700;color:#1e293b;margin-bottom:6px;}
.confirm-sub{text-align:center;font-size:.85rem;color:#64748b;margin-bottom:22px;}

.info-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:16px 18px;margin-bottom:22px;}
.info-row{display:flex;align-items:center;gap:8px;padding:5px 0;font-size:.88rem;color:#334155;border-bottom:1px solid #f1f5f9;}
.info-row:last-child{border-bottom:none;padding-bottom:0;}
.info-row i{width:18px;color:#94a3b8;text-align:center;flex-shrink:0;}
.info-label{color:#94a3b8;font-size:.78rem;min-width:80px;}
.info-val{font-weight:700;color:#1e293b;}

.result-badge{display:inline-flex;align-items:center;gap:4px;font-size:.75rem;font-weight:700;padding:3px 9px;border-radius:10px;}
.result-ok{background:#d1fae5;color:#065f46;}
.result-fail{background:#fee2e2;color:#991b1b;}

.btn-row{display:flex;gap:10px;flex-direction:column;}
.btn-delete{width:100%;padding:13px;background:#e11d48;color:#fff;border:none;border-radius:9px;font-size:1rem;font-weight:700;font-family:'Sarabun',sans-serif;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:7px;transition:background .15s;}
.btn-delete:hover{background:#be123c;}
.btn-cancel{display:block;text-align:center;padding:12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:.9rem;font-weight:700;font-family:'Sarabun',sans-serif;color:#64748b;text-decoration:none;background:#fff;transition:background .15s;}
.btn-cancel:hover{background:#f8fafc;}

.swal2-popup{font-family:'Sarabun',sans-serif !important;}
</style>
</head>
<body>
<div class="content-wrapper">
<div class="page-wrap">

    <div class="page-header">
        <div class="page-header-icon"><i class="fa-solid fa-trash-can"></i></div>
        <div>
            <div class="page-header-title">ยืนยันการลบผลตรวจ</div>
            <div class="page-header-sub">การลบจะไม่สามารถย้อนกลับได้</div>
        </div>
    </div>

    <div class="confirm-card">
        <div class="confirm-card-header">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span>คุณกำลังจะลบข้อมูลต่อไปนี้</span>
        </div>
        <div class="confirm-card-body">

            <div class="warn-icon"><i class="fa-solid fa-trash-can"></i></div>
            <div class="confirm-title">ยืนยันลบผลตรวจรถตัดอ้อย?</div>
            <div class="confirm-sub">ข้อมูลทั้งหมดของรายการนี้จะถูกลบออกจากระบบถาวร</div>

            <div class="info-box">
                <div class="info-row">
                    <i class="fa-solid fa-tractor"></i>
                    <span class="info-label">เบอร์รถตัด</span>
                    <span class="info-val"><?php echo htmlspecialchars($sess_data['harvester_number']); ?></span>
                </div>
                <div class="info-row">
                    <i class="fa-solid fa-calendar-day"></i>
                    <span class="info-label">วันที่บันทึก</span>
                    <span class="info-val"><?php echo $date_str.' · '.$time_str; ?></span>
                </div>
                <div class="info-row">
                    <i class="fa-solid fa-leaf"></i>
                    <span class="info-label">สภาพแปลง</span>
                    <span class="info-val"><?php echo htmlspecialchars($sess_data['field_condition'] ?: '—'); ?></span>
                </div>
                <div class="info-row">
                    <i class="fa-solid fa-clipboard-check"></i>
                    <span class="info-label">ผลตรวจ</span>
                    <span>
                        <?php if($total>0): ?>
                            <?php if($pass==$total): ?>
                                <span class="result-badge result-ok"><i class="fa-solid fa-check-double"></i> ผ่านทั้งหมด (<?php echo $total; ?> รายการ)</span>
                            <?php else: ?>
                                <span class="result-badge result-fail"><i class="fa-solid fa-triangle-exclamation"></i> ไม่ผ่าน <?php echo $total-$pass; ?>/<?php echo $total; ?> รายการ</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color:#94a3b8;">—</span>
                        <?php endif; ?>
                    </span>
                </div>
                <?php if(!empty($sess_data['img_harvester']) || !empty($sess_data['img_field'])): ?>
                <div class="info-row">
                    <i class="fa-solid fa-camera"></i>
                    <span class="info-label">ภาพแนบ</span>
                    <span class="info-val" style="color:#e11d48;">
                        <?php
                        $img_count = (!empty($sess_data['img_harvester']) ? 1:0) + (!empty($sess_data['img_field']) ? 1:0);
                        echo $img_count.' รูป (ไฟล์ภาพจะยังคงอยู่บนเซิร์ฟเวอร์)';
                        ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <div class="btn-row">
                <form method="POST" action="harvester_delete.php?id=<?php echo $session_id; ?>">
                    <input type="hidden" name="action" value="confirm_delete">
                    <button type="submit" class="btn-delete" id="deleteBtn">
                        <i class="fa-solid fa-trash-can"></i> ยืนยัน ลบรายการนี้
                    </button>
                </form>
                <a href="harvester.php" class="btn-cancel">
                    <i class="fa-solid fa-xmark"></i> ยกเลิก ไม่ลบ
                </a>
            </div>

        </div>
    </div>

</div>
</div>
<?php include 'includes/nav_u_footer.php'; ?>
<script>
document.getElementById('deleteBtn').closest('form').addEventListener('submit', function(){
    const btn = document.getElementById('deleteBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> กำลังลบ...';
});
</script>
</body>
</html>