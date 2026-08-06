<?php 
session_start();
// รับค่าจากฟอร์มถ้ามีการกดบันทึก
if(isset($_POST['ui_size'])) {
    $_SESSION['ui_size'] = $_POST['ui_size'];
    header("Location: add_queue.php?msg=ปรับขนาดหน้าจอแล้ว&type=success");
    exit;
}
include 'nvb.php'; 
?>

<div class="container" style="margin-top: 30px; max-width: 800px;">
    <div class="card" style="text-align: center;">
        <h2 style="justify-content: center;">📏 ตั้งค่าขนาดการแสดงผล</h2>
        <p style="color: #64748b;">เลือกขนาดที่เหมาะกับหน้าจอและสายตาของคุณ</p>
        
        <form method="POST" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px;">
            
            <label style="cursor: pointer;">
                <input type="radio" name="ui_size" value="normal" <?php echo ($_SESSION['ui_size'] ?? '') != 'large' ? 'checked' : ''; ?> style="display:none;" onchange="this.form.submit()">
                <div class="size-option" style="padding: 40px; border: 2px solid #e2e8f0; border-radius: 20px;">
                    <span style="font-size: 1.5rem;">📱</span>
                    <h3 style="margin: 10px 0 5px;">ขนาดปกติ</h3>
                    <p style="font-size: 0.8rem; color: #94a3b8;">เหมาะสำหรับแท็บเล็ต หรือดูข้อมูลเยอะๆ</p>
                </div>
            </label>

            <label style="cursor: pointer;">
                <input type="radio" name="ui_size" value="large" <?php echo ($_SESSION['ui_size'] ?? '') == 'large' ? 'checked' : ''; ?> style="display:none;" onchange="this.form.submit()">
                <div class="size-option" style="padding: 40px; border: 2px solid #e2e8f0; border-radius: 20px;">
                    <span style="font-size: 2.5rem;">🖥️</span>
                    <h3 style="margin: 10px 0 5px;">ขนาดใหญ่พิเศษ</h3>
                    <p style="font-size: 0.8rem; color: #94a3b8;">ตัวอักษรใหญ่ ทะเบียนชัดเจน มองเห็นระยะไกล</p>
                </div>
            </label>
        </form>
    </div>
</div>

<style>
    input[type="radio"]:checked + .size-option {
        border-color: #3b82f6 !important;
        background: #eff6ff;
        box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.2);
    }
    .size-option:hover {
        background: #f8fafc;
        transform: translateY(-5px);
    }
</style>

<?php include 'footer.php'; ?>