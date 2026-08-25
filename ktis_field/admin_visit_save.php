<?php
/**
 * admin_visit_save.php — หน้ากรอกบันทึกการลงพื้นที่ตรวจสอบรถตัด (Admin)
 * GET  ?harvester_number=...&date=...  → แสดงฟอร์ม
 * POST action=save                     → บันทึกแล้ว redirect กลับ dashboard
 */
date_default_timezone_set('Asia/Bangkok');
require_once 'config.php';
session_start();

if(!isset($_SESSION['emp_id']) || $_SESSION['emp_level'] !== 'a'){
    header("location: login.php"); exit;
}

$thai_months = ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน',
                'กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];

$harvester_number = trim($_GET['harvester'] ?? $_GET['harvester_number'] ?? $_POST['harvester_number'] ?? '');
$visit_date       = trim($_GET['date']             ?? $_POST['visit_date']        ?? date('Y-m-d'));

// ── POST: บันทึก ──
if($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save'){
    $hn          = trim($_POST['harvester_number'] ?? '');
    $vdate       = trim($_POST['visit_date']       ?? '');
    $has_problem = isset($_POST['has_problem']) ? (int)$_POST['has_problem'] : 0;
    $prob_detail = trim($_POST['problem_detail'] ?? '');
    $action_taken= trim($_POST['action_taken']   ?? '');

    if($hn && $vdate){
        try {
            // เช็คซ้ำ
            $chk = $conn->prepare("SELECT visit_id FROM admin_field_visits WHERE harvester_number=:hn AND visit_date=:dt LIMIT 1");
            $chk->execute([':hn'=>$hn,':dt'=>$vdate]);
            $existing = $chk->fetch();

            if($existing){
                $conn->prepare("UPDATE admin_field_visits SET has_problem=:hp,problem_detail=:pd,action_taken=:at,emp_id=:eid,emp_name=:en WHERE visit_id=:vid")
                     ->execute([':hp'=>$has_problem,':pd'=>$prob_detail?:null,':at'=>$action_taken?:null,
                                ':eid'=>$_SESSION['emp_id'],':en'=>$_SESSION['emp_name'],':vid'=>$existing['visit_id']]);
            } else {
                $conn->prepare("INSERT INTO admin_field_visits (harvester_number,visit_date,emp_id,emp_name,has_problem,problem_detail,action_taken) VALUES (:hn,:dt,:eid,:en,:hp,:pd,:at)")
                     ->execute([':hn'=>$hn,':dt'=>$vdate,':eid'=>$_SESSION['emp_id'],':en'=>$_SESSION['emp_name'],
                                ':hp'=>$has_problem,':pd'=>$prob_detail?:null,':at'=>$action_taken?:null]);
            }
            // redirect กลับ dashboard พร้อมวันที่เดิม
            header("location: harvester_daily_dashboard.php?date=".urlencode($vdate)."&saved=1"); exit;
        } catch(Exception $e){
            $err = 'เกิดข้อผิดพลาด: '.$e->getMessage();
        }
    } else {
        $err = 'กรุณากรอกข้อมูลให้ครบ';
    }
}

// ── วันที่แสดง (ภาษาไทย) ──
$ts  = strtotime($visit_date);
$d   = (int)date('d',$ts);
$m   = (int)date('m',$ts);
$y   = (int)date('Y',$ts)+543;
$date_th = $d.' '.$thai_months[$m].' '.$y;

include 'includes/nav_u_header.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>บันทึกลงพื้นที่ - TIS SMART FIELD</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;}
body{font-family:'Sarabun',sans-serif;background:#f1f5f9;margin:0;}
.pw{max-width:560px;margin:28px auto;padding:0 16px 60px;}
.ph{display:flex;align-items:center;gap:12px;margin-bottom:22px;}
.ph-ico{width:46px;height:46px;background:linear-gradient(135deg,#e11d48,#be123c);border-radius:11px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.ph-ico i{color:#fff;font-size:1.3rem;}
.ph-title{font-size:1.1rem;font-weight:700;color:#1e293b;}
.ph-sub{font-size:.78rem;color:#64748b;}
.card{background:#fff;border-radius:14px;border:.5px solid #e2e8f0;overflow:hidden;}
.card-hd{background:#1e293b;padding:14px 18px;border-bottom:3px solid #e11d48;display:flex;align-items:center;gap:9px;}
.card-hd i{color:#e11d48;}
.card-hd span{color:#f8fafc;font-weight:700;font-size:.95rem;}
.card-bd{padding:20px;}
.info-bar{display:flex;flex-wrap:wrap;gap:8px;background:#f8fafc;border-radius:9px;border:1px solid #e2e8f0;padding:11px 14px;margin-bottom:18px;}
.info-chip{display:inline-flex;align-items:center;gap:5px;font-size:.82rem;font-weight:600;color:#475569;}
.info-chip i{color:#94a3b8;}
.info-chip b{color:#1e293b;}
.fl{display:block;font-weight:700;font-size:.83rem;color:#374151;margin-bottom:7px;}
.fi{width:100%;padding:10px 13px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:.93rem;font-family:'Sarabun',sans-serif;background:#f8fafc;color:#1e293b;outline:none;transition:border-color .15s;}
.fi:focus{border-color:#e11d48;background:#fff;}
textarea.fi{resize:vertical;min-height:80px;}
.form-group{margin-bottom:16px;}
.radio-row{display:flex;gap:10px;margin-bottom:16px;}
.radio-card{flex:1;border:2px solid #e2e8f0;border-radius:10px;padding:12px;cursor:pointer;transition:all .15s;text-align:center;}
.radio-card:hover{border-color:#e11d48;background:#fff0f0;}
.radio-card input{display:none;}
.radio-card.selected-ok{border-color:#10b981;background:#f0fdf4;}
.radio-card.selected-fail{border-color:#e11d48;background:#fef2f2;}
.radio-card .rc-ico{font-size:1.5rem;margin-bottom:6px;}
.radio-card .rc-lbl{font-weight:700;font-size:.85rem;}
.btn-save{width:100%;padding:13px;background:#e11d48;color:#fff;border:none;border-radius:9px;font-size:1rem;font-weight:700;font-family:'Sarabun',sans-serif;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:7px;margin-top:6px;transition:background .15s;}
.btn-save:hover{background:#be123c;}
.btn-back{display:block;text-align:center;margin-top:12px;color:#64748b;font-size:.85rem;font-weight:600;text-decoration:none;}
.btn-back:hover{color:#1e293b;}
.err-box{background:#fee2e2;border:1px solid #fecaca;color:#991b1b;padding:11px 14px;border-radius:8px;font-weight:600;font-size:.88rem;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
.detail-wrap{display:none;}
.detail-wrap.show{display:block;}
</style>
<div class="content-wrapper">
<div class="pw">

<div class="ph">
    <div class="ph-ico"><i class="fa-solid fa-person-walking-luggage"></i></div>
    <div>
        <div class="ph-title">บันทึกการลงพื้นที่ตรวจสอบ</div>
        <div class="ph-sub">Admin บันทึกผลการลงพื้นที่ตรวจรถตัดที่มีปัญหาเกิน 3 วัน</div>
    </div>
</div>

<?php if(!empty($err ?? '')): ?>
<div class="err-box"><i class="fa-solid fa-circle-exclamation"></i><?php echo htmlspecialchars($err); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-hd"><i class="fa-solid fa-clipboard-check"></i><span>กรอกผลการลงพื้นที่</span></div>
    <div class="card-bd">

        <!-- ข้อมูลรถ -->
        <div class="info-bar">
            <div class="info-chip"><i class="fa-solid fa-tractor"></i>รถตัด: <b><?php echo htmlspecialchars($harvester_number); ?></b></div>
            <div class="info-chip">|</div>
            <div class="info-chip"><i class="fa-solid fa-calendar-day"></i>วันที่: <b><?php echo $date_th; ?></b></div>
            <div class="info-chip">|</div>
            <div class="info-chip"><i class="fa-solid fa-user-tie"></i>Admin: <b><?php echo htmlspecialchars($_SESSION['emp_name']); ?></b></div>
        </div>

        <form method="POST" action="admin_visit_save.php">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="harvester_number" value="<?php echo htmlspecialchars($harvester_number); ?>">
            <input type="hidden" name="visit_date" value="<?php echo htmlspecialchars($visit_date); ?>">

            <!-- พบปัญหาหรือไม่ -->
            <label class="fl">ผลการลงพื้นที่ตรวจสอบ <span style="color:#e11d48;">*</span></label>
            <div class="radio-row">
                <label class="radio-card" id="card-ok" onclick="selectResult(0)">
                    <input type="radio" name="has_problem" value="0" id="r-ok">
                    <div class="rc-ico">✅</div>
                    <div class="rc-lbl" style="color:#10b981;">ปกติ ไม่พบปัญหา</div>
                </label>
                <label class="radio-card" id="card-fail" onclick="selectResult(1)">
                    <input type="radio" name="has_problem" value="1" id="r-fail">
                    <div class="rc-ico">⚠️</div>
                    <div class="rc-lbl" style="color:#e11d48;">พบปัญหา ต้องดำเนินการ</div>
                </label>
            </div>

            <!-- รายละเอียดปัญหา (แสดงเมื่อเลือกพบปัญหา) -->
            <div class="detail-wrap" id="detail-wrap">
                <div class="form-group">
                    <label class="fl">รายละเอียดปัญหาที่พบ</label>
                    <textarea name="problem_detail" class="fi" placeholder="เช่น ใบมีดสึก, อ้อยไฟไหม้ติดต่อกัน..."></textarea>
                </div>
                <div class="form-group">
                    <label class="fl">การดำเนินการ / แก้ไข</label>
                    <textarea name="action_taken" class="fi" placeholder="เช่น แจ้งช่างซ่อมแล้ว, สั่งหยุดรถเพื่อตรวจสอบ..."></textarea>
                </div>
            </div>

            <button type="submit" class="btn-save" id="submitBtn">
                <i class="fa-solid fa-floppy-disk"></i> บันทึกการลงพื้นที่
            </button>
        </form>

        <a href="harvester_daily_dashboard.php?date=<?php echo urlencode($visit_date); ?>" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> กลับหน้า Dashboard
        </a>
    </div>
</div>

</div>
</div>
<?php include 'includes/nav_u_footer.php'; ?>
<script>
function selectResult(hasProblem){
    document.getElementById('r-ok').checked   = (hasProblem === 0);
    document.getElementById('r-fail').checked = (hasProblem === 1);
    document.getElementById('card-ok').className   = 'radio-card' + (hasProblem===0?' selected-ok':'');
    document.getElementById('card-fail').className = 'radio-card' + (hasProblem===1?' selected-fail':'');
    document.getElementById('detail-wrap').className = 'detail-wrap' + (hasProblem===1?' show':'');
}
document.getElementById('submitBtn').closest('form').addEventListener('submit',function(){
    const btn=document.getElementById('submitBtn');
    btn.disabled=true;
    btn.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i> กำลังบันทึก...';
});
</script>
</body>
</html>