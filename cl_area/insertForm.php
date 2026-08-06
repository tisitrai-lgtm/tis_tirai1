<?php
// insertForm.php
session_start();
$promotion_unit = $_SESSION['selected_unit_name'] ?? '';
$production_year = $_SESSION['selected_production_year_label'] ?? '';

if (!isset($conn)) {
    include_once 'db_connect.php'; 
}

$current_unit_id = $_SESSION['selected_unit_id'] ?? null;
$current_unit_name = $_SESSION['selected_unit_name'] ?? 'ไม่ได้เลือกหน่วยงาน';
$current_production_year_label = $_SESSION['selected_production_year_label'] ?? 'ไม่ได้เลือกปี';
$session_nss_name = $_SESSION['nss_name'] ?? ''; // ดึงชื่อ นสส. จาก Session ตอน Login

// ✅ ส่วนที่เพิ่ม: ดึงรายชื่อ NSS จากตาราง employees ตามหน่วยงานที่เลือก
$nss_list = [];
if ($current_unit_id) {
    try {
        if (isset($conn)) {
            $stmt_nss = $conn->prepare("SELECT emp_name FROM employees WHERE unit_id = :unit_id AND role = 'NSS' ORDER BY emp_name ASC");
            $stmt_nss->execute([':unit_id' => $current_unit_id]);
            $nss_list = $stmt_nss->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log("Error fetching NSS list: " . $e->getMessage());
    }
}

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
$all_provinces = [];
try {
    if (isset($conn)) {
        $stmt_provinces = $conn->query("SELECT id, province_name FROM provinces ORDER BY province_name ASC");
        $all_provinces = $stmt_provinces->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Error fetching provinces (insertForm.php): " . $e->getMessage());
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

<form id="insertDataForm">
    <div class="row g-3">
         
        <div class="col-md-6">
            <label for="production_year_modal" class="form-label">ปีการผลิต</label>
        <select class="form-control" name="production_year" id="production_year_modal" disabled>
            <option value="<?php echo htmlspecialchars($current_production_year_label); ?>" selected>
                <?php echo htmlspecialchars($current_production_year_label); ?>
            </option>
            <?php // ถ้าต้องการให้ dropdown มีตัวเลือกอื่นที่ disabled ก็สามารถวนลูปแสดงตรงนี้ได้ ?>
        </select>
        <input type="hidden" name="production_year" value="<?php echo htmlspecialchars($current_production_year_label); ?>">
        </div>

        <div class="col-md-6">
            <label for="plot_id_modal" class="form-label">ID แปลง <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="plot_id_modal" name="plot_id" maxlength="7" pattern="[^\s]*" placeholder="" required onchange="fetchPlotData()">

        </div>
        <div class="col-md-6">
            <label for="plcontract_number_modal" class="form-label">เลขที่สัญญา <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="plcontract_number_modal" name="plcontract_number" maxlength="6" pattern="[^\s]*" placeholder="" value="00" required>
        </div>

        <div class="col-md-6">
            <label for="quota_name_modal" class="form-label">ชื่อโควต้า</label>
            <input type="text" class="form-control" name="quota_name">
        </div>
        <div class="col-md-4">
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
        <div class="col-md-4">
            <label for="promotion_unit_modal" class="form-label">หน่วยส่งเสริม</label>
            <input type="text" class="form-control" name="promotion_unit" value="<?php echo htmlspecialchars($promotion_unit); ?>" disabled>
        </div>

        <div class="col-md-4">
            <label for="promoter_area_modal" class="form-label">เขตนักส่งเสริม (นสส.)</label>
            <input list="nss_list_data" class="form-control" name="promoter_area" id="promoter_area_modal" value="<?php echo htmlspecialchars($session_nss_name); ?>" placeholder="เลือกชื่อหรือพิมพ์เอง">
            <datalist id="nss_list_data">
                <?php foreach ($nss_list as $nss): ?>
                    <option value="<?php echo htmlspecialchars($nss['emp_name']); ?>">
                <?php endforeach; ?>
            </datalist>
        </div>

        <div class="col-md-4">
            <label for="village_modal" class="form-label">หมู่ที่</label>
            <input type="text" class="form-control" name="village">
        </div>
        <div class="col-md-4">
            <label for="district_sub_modal" class="form-label">ตำบล</label>
            <input type="text" class="form-control" name="district_sub">
        </div>
        <div class="col-md-4">
            <label for="district_modal" class="form-label">อำเภอ</label>
            <input type="text" class="form-control" name="district">
        </div>
        <div class="col-md-4">
            <label for="province_modal" class="form-label">จังหวัด <small class="text-muted">(เลือกจากรายการ ▼)</small></label>
            <select class="form-control" name="province" id="province_modal" required>
            <option value="" disabled selected>-- เลือกจังหวัด --</option>
            <option value="อุตรดิตถ์">อุตรดิตถ์</option> 
            <option value="สุโขทัย">สุโขทัย</option>
            <option value="แพร่">แพร่</option>
            <option value="พิษณุโลก">พิษณุโลก</option>
            <option value="ตาก">ตาก</option>
        </select>
        </div>
        <div class="col-md-4">
            <label for="square_meters_modal" class="form-label">ตารางเมตร</label>
            <input type="number" class="form-control" id="square_meters_modal" name="square_meters">
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
        <input type="hidden" name="production_year" value="<?php echo htmlspecialchars($production_year); ?>">
    </div>
    <div class="modal-footer mt-4">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="button" class="btn btn-success" onclick="submitInsertForm()">บันทึก</button>
    </div>
</form>

<script>
function validateFormModal() {
    const plotId = document.getElementById('plot_id_modal');
    const contractNo = document.getElementById('plcontract_number_modal');
    if (!plotId.value.trim() || !contractNo.value.trim()) {
        alert("กรุณากรอก Plot ID และ เลขสัญญา");
        return false;
    }
    return true;
}
</script>
<script>
function fetchPlotData() {
    const plotId = document.getElementById('plot_id_modal').value.trim();
    if (!plotId) return;

    fetch('get_plot_data.php?plot_id=' + encodeURIComponent(plotId))
        .then(response => response.json())
        .then(data => {
            if (data.found) {
                document.getElementById('plcontract_number_modal').value = data.contract_number;
                document.querySelector('input[name="quota_name"]').value = data.quota_name;
                
                // ✅ แก้ไข: ถ้ามีข้อมูล นสส. เดิมในฐานข้อมูลให้แสดงชื่อนั้น 
                // แต่ถ้าไม่มี (เป็นค่าว่าง) ให้คงชื่อ นสส. ปัจจุบันที่อยู่ในช่องไว้ (เช่นชื่อจาก Session)
                if(data.promoter_area && data.promoter_area.trim() !== "") {
                    document.getElementById('promoter_area_modal').value = data.promoter_area;
                }
                
                document.getElementById('sugar_type_modal').value = data.sugar_type;
                document.querySelector('input[name="promotion_unit"]').value = data.promotion_unit;
            } else {
                document.getElementById('plcontract_number_modal').value = '';
                document.querySelector('input[name="quota_name"]').value = '';
                // เมื่อไม่พบข้อมูลแปลง ไม่ต้องล้างช่อง promoter_area เพื่อให้ชื่อ นสส. จาก Session ยังอยู่
                document.getElementById('sugar_type_modal').value = '';
                document.querySelector('input[name="promotion_unit"]').value = '<?php echo $promotion_unit; ?>';
            }
        })
        .catch(err => console.error(err));
}
</script>