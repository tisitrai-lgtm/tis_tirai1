<?php
// ###########################################################
// ไฟล์: insertForm.php (เนื้อหาสำหรับ Modal)
// แบบฟอร์มสำหรับบันทึกข้อมูลตารางวัดที่ดิน
// ###########################################################

// เริ่มต้นใช้งาน session
// ตรวจสอบว่า session ถูกเริ่มแล้วหรือไม่ ถ้ายังก็เริ่ม
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล
// ตรวจสอบว่า $conn ถูกกำหนดแล้วหรือไม่ เพื่อไม่ให้เรียกซ้ำถ้าถูก include ในไฟล์ที่มี db_connect อยู่แล้ว
if (!isset($conn)) {
    include_once 'db_connect.php'; // $conn object จะถูกสร้างขึ้นที่นี่
}

// ###########################################################
// ตรวจสอบว่าผู้ใช้ได้เลือกหน่วยงานและปีมาแล้วหรือไม่ใน Session
// (ไม่ต้อง Redirect เพราะจะแสดงใน Modal ที่มี Session ตรวจสอบอยู่แล้ว)
// ###########################################################
$current_unit_id = $_SESSION['selected_unit_id'] ?? null;
$current_unit_name = $_SESSION['selected_unit_name'] ?? 'ไม่ได้เลือกหน่วยงาน';
$current_production_year_label = $_SESSION['selected_production_year_label'] ?? 'ไม่ได้เลือกปี';

// ดึงข้อมูลสำหรับ dropdowns (ในกรณีที่ต้องการให้ผู้ใช้เลือกเองได้ในอนาคต แต่ตอนนี้จะถูก disable)
// ดึงรายการปีการผลิตจากตาราง production_years
$all_production_years = [];
try {
    if (isset($conn)) { // ตรวจสอบว่า $conn มีอยู่ก่อนใช้งาน
        $stmt_prod_years = $conn->query("SELECT year_label FROM production_years ORDER BY year_label DESC");
        $all_production_years = $stmt_prod_years->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Error fetching production years (insertForm.php): " . $e->getMessage());
}

// ดึงรายการหน่วยส่งเสริมจากตาราง units
$all_units = [];
try {
    if (isset($conn)) { // ตรวจสอบว่า $conn มีอยู่ก่อนใช้งาน
        $stmt_all_units = $conn->query("SELECT unit_id, unit_name FROM units ORDER BY unit_name ASC");
        $all_units = $stmt_all_units->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Error fetching all units (insertForm.php): " . $e->getMessage());
}

// โค้ดสำหรับแสดงข้อความสถานะ (ถ้ามี) เช่น จาก insertData.php หลังจากบันทึกข้อมูล
// ใน Modal เราจะจัดการข้อความนี้ด้วย JavaScript แทน
$message = $_SESSION['form_message'] ?? '';
$message_type = $_SESSION['form_message_type'] ?? '';
unset($_SESSION['form_message']);
unset($_SESSION['form_message_type']);
?>

<?php if (!empty($message)): // แสดงข้อความสถานะที่อาจจะถูกส่งมาก่อนหน้านี้ (ถ้ามีการโหลดหน้านี้โดยตรง) ?>
    <div class="status-message <?php echo $message_type; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<form id="insertDataForm" method="POST">
    
    <div class="form-group mb-3">
        <label for="production_year_modal" class="form-label">ปีการผลิต</label>
        <select class="form-control" name="production_year" id="production_year_modal" disabled>
            <option value="<?php echo htmlspecialchars($current_production_year_label); ?>" selected>
                <?php echo htmlspecialchars($current_production_year_label); ?>
            </option>
            <?php // ถ้าต้องการให้ dropdown มีตัวเลือกอื่นที่ disabled ก็สามารถวนลูปแสดงตรงนี้ได้ ?>
        </select>
        <input type="hidden" name="production_year" value="<?php echo htmlspecialchars($current_production_year_label); ?>">
    </div>
    
    <div class="form-group mb-3">
        <label for="plot_id_modal" class="form-label">IDแปลง <span class="text-danger">*</span></label>
        <input type="text" class="form-control" name="plot_id" id="plot_id_modal" maxlength="255" placeholder="" required>
    </div> 
    
    <div class="form-group mb-3">
        <label for="plcontract_number_modal" class="form-label">เลขสัญญา <span class="text-danger">*</span></label>
        <input type="text" class="form-control" name="plcontract_number" id="plcontract_number_modal" maxlength="255" placeholder="" value="00" required>
    </div>
    
    <div class="form-group mb-3">
        <label for="sugar_type_modal" class="form-label">ชนิดอ้อย <small class="text-muted">(เลือกจากรายการ ▼)</small></label>
        <select class="form-control" name="sugar_type" id="sugar_type_modal">
            <option value="" disabled selected>--Select--</option> 
            <option value="01 อ้อยข้ามแล้ง">01 อ้อยข้ามแล้ง</option> 
            <option value="02 อ้อยน้ำราด-น้ำสูบ">02 อ้อยน้ำราด-น้ำสูบ</option>
            <option value="03 อ้อยต้นฝน">03 อ้อยต้นฝน</option>
            <option value="04 อ้อยตอ 1">04 อ้อยตอ 1</option>
            <option value="05 อ้อยตอ 2">05 อ้อยตอ 2</option>
            <option value="06 อ้อยตอ 3">06 อ้อยตอ 3</option>
            <option value="07 อ้อยตอ 4 ขึ้นไป">07 อ้อยตอ 4 ขึ้นไป</option>
            <option value="08 อ้อยขยายพันธ์ุ">08 อ้อยขยายพันธ์ุ</option>
            <option value="09 อ้อยแล้งตัดพันธ์ุ">09 อ้อยแล้งตัดพันธ์ุ</option>
            <option value="10 อ้อยตอตัดพันธ์ุ">10 อ้อยตอตัดพันธ์ุ</option>
            <option value="11 อ้อยไม่คุ้มบำรุง">11 อ้อยไม่คุ้มบำรุง</option>
            <option value="99 อ้อยลอย">99 อ้อยลอย</option>
        </select>
    </div>
    
    <div class="form-group mb-3">
        <label for="quota_name_modal" class="form-label">ชื่อโควตา</label>
        <input type="text" class="form-control" name="quota_name" id="quota_name_modal" maxlength="255" placeholder="">
    </div>
    
    <div class="form-group mb-3">
        <label for="promotion_unit_modal" class="form-label">หน่วยส่งเสริม <small class="text-muted">(เลือกจากรายการ ▼)</small></label>
        <select class="form-control" id="promotion_unit_modal" name="promotion_unit" disabled>
            <option value="<?php echo htmlspecialchars($current_unit_name); ?>" selected>
                <?php echo htmlspecialchars($current_unit_name); ?>
            </option>
            <?php // ถ้าต้องการให้ dropdown มีตัวเลือกอื่นที่ disabled ก็สามารถวนลูปแสดงตรงนี้ได้ ?>
        </select>
        <input type="hidden" name="promotion_unit" value="<?php echo htmlspecialchars($current_unit_name); ?>">
    </div>
    
    <div class="form-group mb-3">
        <label for="promoter_area_modal" class="form-label">เขต นักส่งเสริม (ชื่อ-นามสกุล)</label>
        <input type="text" class="form-control" name="promoter_area" id="promoter_area_modal" maxlength="255" placeholder="">
    </div>
    
    <div class="form-group mb-3">
        <label for="village_modal" class="form-label">หมู่ที่</label>
        <input type="text" class="form-control" name="village" id="village_modal" maxlength="255" placeholder="">
    </div>
    
    <div class="form-group mb-3">
        <label for="district_sub_modal" class="form-label">ตำบล</label>
        <input type="text" class="form-control" name="district_sub" id="district_sub_modal" maxlength="255" placeholder="">
    </div>
    
    <div class="form-group mb-3">
        <label for="district_modal" class="form-label">อำเภอ</label>
        <input type="text" class="form-control" name="district" id="district_modal" maxlength="255" placeholder="">
    </div>
    
    <div class="form-group mb-3">
        <label for="province_modal" class="form-label">จังหวัด</label>
        <input type="text" class="form-control" name="province" id="province_modal" maxlength="255" placeholder="">
    </div>
    
    <div class="form-group mb-3">
        <label for="square_meters_modal" class="form-label">จำนวนตารางเมตร</label>
        <input type="number" class="form-control" name="square_meters" id="square_meters_modal" placeholder="จำนวนตารางเมตร" oninput="convertSquareMetersModal()" min="0" step="0.01">
    </div>
    
    <div class="form-group mb-3">
        <label class="form-label">ผลลัพธ์การแปลง:</label>
        <p id="conversion_result_modal" class="form-control bg-light text-muted"></p>
    </div>
    <div class="form-group mb-3">
        <label class="form-label">ส่งเสริม(ไร่):</label>
        <p id="adjusted_conversion_result_modal" class="form-control bg-light text-muted"></p>
    </div>
    
    <input type="hidden" name="rai" id="rai_modal_hidden">
    <input type="hidden" name="ngan" id="ngan_modal_hidden">
    <input type="hidden" name="wah" id="wah_modal_hidden">
    <input type="hidden" name="rai_adjusted" id="rai_adjusted_modal_hidden">
    
    <div class="modal-footer d-flex justify-content-end gap-2">
        <button type="submit" class="btn btn-success">บันทึกข้อมูล</button>
        <button type="button" class="btn btn-modern-secondary" data-bs-dismiss="modal">ยกเลิก</button>
    </div>
</form>