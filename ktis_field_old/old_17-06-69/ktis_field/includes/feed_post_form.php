<?php
/**
 * includes/feed_post_form.php
 * ฟอร์มสร้างโพสต์ใหม่ (เฉพาะ Admin ออฟฟิศกลาง)
 * @var array  $zones
 * @var array  $_SESSION
 */
?>
<?php if($_SESSION['emp_unit'] == 'ประจำออฟฟิตกลาง' && $_SESSION['emp_level'] == 'a'): ?>

<style>
/* ── feed_post_form: theme styles ── */
.admin-action-zone { margin-bottom: 20px; text-align: right; }

.btn-toggle-form {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 11px 22px; background: #e11d48; color: white;
    border: none; border-radius: 25px; font-weight: 700;
    font-family: 'Sarabun', sans-serif; font-size: 0.95rem;
    cursor: pointer; transition: background .15s;
    box-shadow: 0 3px 10px rgba(225,29,72,.25);
}
.btn-toggle-form:hover { background: #be123c; }

.admin-post-card {
    display: none; background: white;
    border-radius: 14px; border: .5px solid #e2e8f0;
    margin-top: 14px; overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,.07);
}

/* card header bar */
.form-card-header {
    background: #1e293b; padding: 14px 20px;
    display: flex; align-items: center; gap: 10px;
    border-bottom: 3px solid #e11d48;
}
.form-card-header i  { color: #e11d48; font-size: 1rem; }
.form-card-header span { color: #f8fafc; font-weight: 700; font-size: .95rem; }

.form-card-body { padding: 20px; }

/* labels */
.field-label {
    display: block; font-weight: 700; font-size: .83rem;
    color: #374151; margin-bottom: 7px;
}
.field-label .req { color: #e11d48; }
.field-label .hint { font-weight: 400; color: #94a3b8; font-size: .78rem; margin-left: 5px; }

/* inputs */
.form-field {
    width: 100%; padding: 10px 13px;
    border: 1.5px solid #e2e8f0; border-radius: 8px;
    font-size: .95rem; font-family: 'Sarabun', sans-serif;
    background: #f8fafc; color: #1e293b; outline: none;
    transition: border-color .15s, background .15s;
}
.form-field:focus { border-color: #e11d48; background: white; }
select.form-field { cursor: pointer; height: 44px; }
textarea.form-field { resize: vertical; }

/* 2 col grid */
.form-grid-2x { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px; }

/* prob selects */
.prob-selects-stack { display: flex; flex-direction: column; gap: 7px; margin-bottom: 16px; }

/* image upload */
.img-upload-section { margin-bottom: 16px; }
.img-upload-grid { display: flex; flex-direction: column; gap: 8px; background: #f8fafc; padding: 14px; border-radius: 10px; border: 1.5px dashed #e2e8f0; }
.img-upload-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.img-upload-label {
    cursor: pointer; display: inline-flex; align-items: center; gap: 7px;
    padding: 8px 16px; background: white; border: 1.5px solid #e2e8f0;
    border-radius: 8px; font-size: .85rem; font-weight: 700;
    color: #475569; font-family: 'Sarabun', sans-serif;
    transition: all .15s;
}
.img-upload-label:hover { background: #fee2e2; border-color: #e11d48; color: #e11d48; }
.img-upload-label i { color: #e11d48; }
.img-preview-text { font-size: .78rem; color: #10b981; font-weight: 600; }

/* divider */
.form-divider { border: none; border-top: 1px solid #f1f5f9; margin: 16px 0; }

/* submit */
.btn-submit-post {
    width: 100%; padding: 13px;
    background: #1e293b; color: white;
    border: none; border-radius: 9px;
    font-size: 1rem; font-weight: 700;
    font-family: 'Sarabun', sans-serif; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: background .15s;
}
.btn-submit-post:hover { background: #0f172a; }

@media (max-width: 600px) {
    .form-grid-2x { grid-template-columns: 1fr; }
}
</style>

<div class="admin-action-zone">
    <button type="button" class="btn-toggle-form" onclick="togglePostForm()">
        <i class="fa-solid fa-circle-plus" id="toggleIcon"></i>
        <span id="toggleText">แจ้งเรื่องรถอ้อยสกปรกเพิ่ม</span>
    </button>

    <div class="admin-post-card" id="adminPostForm">

        <!-- Header -->
        <div class="form-card-header">
            <i class="fa-solid fa-file-pen"></i>
            <span>รายละเอียดข้อมูลการแจ้งเหตุ</span>
        </div>

        <div class="form-card-body">
            <form id="uploadForm" enctype="multipart/form-data">

                <!-- หน่วย + ทะเบียน -->
                <div class="form-grid-2x">
                    <div>
                        <label class="field-label">
                            หน่วยส่งเสริมที่รับผิดชอบ <span class="req">*</span>
                        </label>
                        <select name="target_unit" class="form-field" required>
                            <option value="">-- เลือกหน่วยส่งเสริม --</option>
                            <?php foreach($zones as $zone): ?>
                            <option value="<?php echo htmlspecialchars($zone['zone_id'].' '.$zone['zone_name']); ?>">
                                <?php echo htmlspecialchars($zone['zone_id'].' : '.$zone['zone_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="field-label">
                            ทะเบียนรถบรรทุกอ้อย <span class="req">*</span>
                        </label>
                        <input type="text" name="truck_number"
                               placeholder="เช่น 12-3456" class="form-field" required>
                    </div>
                </div>

                <!-- ปัญหาที่พบ -->
                <div>
                    <label class="field-label">
                        ปัญหาที่พบ <span class="req">*</span>
                        <span class="hint">(เลือกได้สูงสุด 3 รายการ)</span>
                    </label>
                    <div class="prob-selects-stack" id="prob-selects-wrap">
                        <select name="problem_1" class="form-field prob-sel" required>
                            <option value="">-- ปัญหาที่ 1 (บังคับ) --</option>
                        </select>
                        <select name="problem_2" class="form-field prob-sel">
                            <option value="">-- ปัญหาที่ 2 (ถ้ามี) --</option>
                        </select>
                        <select name="problem_3" class="form-field prob-sel">
                            <option value="">-- ปัญหาที่ 3 (ถ้ามี) --</option>
                        </select>
                    </div>
                </div>

                <hr class="form-divider">

                <!-- รายละเอียดเพิ่มเติม -->
                <div style="margin-bottom:16px;">
                    <label class="field-label">
                        รายละเอียดข้อความเพิ่มเติม
                        <span class="hint">(ถ้ามี)</span>
                    </label>
                    <textarea name="post_text" rows="3"
                              placeholder="ระบุรายละเอียดเพิ่มเติม..."
                              class="form-field"></textarea>
                </div>

                <!-- แนบรูป -->
                <div class="img-upload-section">
                    <label class="field-label">
                        แนบรูปภาพหลักฐาน
                        <span class="hint">
                            <i class="fa-solid fa-compress"></i> บีบอัดอัตโนมัติ 800px / 75%
                        </span>
                    </label>
                    <div class="img-upload-grid">
                        <?php
                        $img_slots = [
                            ['id'=>'1','label'=>'รูปที่ 1','req'=>true],
                            ['id'=>'2','label'=>'รูปที่ 2','req'=>false],
                            ['id'=>'3','label'=>'รูปที่ 3','req'=>false],
                        ];
                        foreach($img_slots as $slot):
                        ?>
                        <div class="img-upload-row">
                            <label class="img-upload-label">
                                <i class="fa-solid fa-camera"></i>
                                <?php echo $slot['label']; ?><?php echo $slot['req']?' *':''; ?>
                                <input type="file" accept="image/*" style="display:none;"
                                       onchange="previewCompress(this,'prev<?php echo $slot['id']; ?>','img_b64_<?php echo $slot['id']; ?>')">
                            </label>
                            <span class="img-preview-text" id="prev<?php echo $slot['id']; ?>"></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="img_b64_1" id="img_b64_1">
                    <input type="hidden" name="img_b64_2" id="img_b64_2">
                    <input type="hidden" name="img_b64_3" id="img_b64_3">
                </div>

                <button type="submit" class="btn-submit-post">
                    <i class="fa-solid fa-floppy-disk"></i> ยืนยันการบันทึกข้อมูล
                </button>

            </form>
        </div>
    </div>
</div>

<?php endif; ?>