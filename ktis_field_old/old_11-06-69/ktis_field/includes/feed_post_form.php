<?php
/**
 * includes/feed_post_form.php
 * ฟอร์มสร้างโพสต์ใหม่ (เฉพาะ Admin ออฟฟิศกลาง)
 * ต้องการตัวแปร: $zones, $_SESSION
 */
/**
 * includes/feed_post_form.php
 * @var array  $zones
 * @var array  $_SESSION
 */
?>
        <?php if($_SESSION['emp_unit'] == 'ประจำออฟฟิตกลาง' && $_SESSION['emp_level'] == 'a'): ?>
            <div class="admin-action-zone">
                <button type="button" class="btn-toggle-form" onclick="togglePostForm()">
                    <i class="fa-solid fa-circle-plus" id="toggleIcon"></i> <span id="toggleText">แจ้งเรื่องรถอ้อยสกปรกเพิ่ม</span>
                </button>
                
                <div class="admin-post-card" id="adminPostForm">
                    <h3 style="margin-top:0; color:#1e293b; font-size:1.05rem;"><i class="fa-solid fa-file-pen" style="color:#e11d48;"></i> รายละเอียดข้อมูลการแจ้งเหตุ</h3>
                    <form id="uploadForm" enctype="multipart/form-data">
                        <div class="form-grid-2">
                            <div>
                                <label style="font-weight:600; font-size:0.9rem; color:#475569;">หน่วยส่งเสริมที่รับผิดชอบ</label>
                                <select name="target_unit" class="form-input" style="height: 44px;" required>
                                    <option value="">-- เลือกหน่วยส่งเสริม --</option>
                                    <?php foreach($zones as $zone): ?>
                                        <option value="<?php echo htmlspecialchars($zone['zone_id'] . ' ' . $zone['zone_name']); ?>">
                                            <?php echo htmlspecialchars($zone['zone_id'] . ' : ' . $zone['zone_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label style="font-weight:600; font-size:0.9rem; color:#475569;">ทะเบียนรถบรรทุกอ้อย</label>
                                <input type="text" name="truck_number" placeholder="เช่น 12-3456" class="form-input" required>
                            </div>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="font-weight:600; font-size:0.9rem; color:#475569;">ปัญหาที่พบ <span style="color:#94a3b8; font-weight:400;">(เลือกได้สูงสุด 3 รายการ)</span></label>
                            <div style="display:flex; flex-direction:column; gap:6px;" id="prob-selects-wrap">
                                <select name="problem_1" class="form-input prob-sel" style="height:42px;" required>
                                    <option value="">-- ปัญหาที่ 1 (บังคับ) --</option>
                                </select>
                                <select name="problem_2" class="form-input prob-sel" style="height:42px;">
                                    <option value="">-- ปัญหาที่ 2 (ถ้ามี) --</option>
                                </select>
                                <select name="problem_3" class="form-input prob-sel" style="height:42px;">
                                    <option value="">-- ปัญหาที่ 3 (ถ้ามี) --</option>
                                </select>
                            </div>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="font-weight:600; font-size:0.9rem; color:#475569;">รายละเอียดข้อความเพิ่มเติม (ถ้ามี)</label>
                            <textarea name="post_text" rows="3" placeholder="ระบุรายละเอียดเพิ่มเติม..." class="form-input"></textarea>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label style="font-weight:600; font-size:0.9rem; color:#475569;">
                                แนบรูปภาพหลักฐาน
                                <span style="font-weight:400; color:#94a3b8; font-size:0.8rem; margin-left:5px;"><i class="fa-solid fa-compress"></i> บีบอัดอัตโนมัติ 800px / 75%</span>
                            </label>
                            <div class="image-upload-grid">
                                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                                    <label style="cursor:pointer;display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:7px;font-size:0.85rem;font-weight:600;color:#475569;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                                        <i class="fa-solid fa-camera"></i> รูปที่ 1 *
                                        <input type="file" accept="image/*" style="display:none;" onchange="previewCompress(this,'prev1','img_b64_1')">
                                    </label>
                                    <span id="prev1" style="font-size:0.78rem;color:#10b981;font-weight:600;"></span>
                                </div>
                                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                                    <label style="cursor:pointer;display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:7px;font-size:0.85rem;font-weight:600;color:#475569;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                                        <i class="fa-solid fa-camera"></i> รูปที่ 2
                                        <input type="file" accept="image/*" style="display:none;" onchange="previewCompress(this,'prev2','img_b64_2')">
                                    </label>
                                    <span id="prev2" style="font-size:0.78rem;color:#10b981;font-weight:600;"></span>
                                </div>
                                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                                    <label style="cursor:pointer;display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:7px;font-size:0.85rem;font-weight:600;color:#475569;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                                        <i class="fa-solid fa-camera"></i> รูปที่ 3
                                        <input type="file" accept="image/*" style="display:none;" onchange="previewCompress(this,'prev3','img_b64_3')">
                                    </label>
                                    <span id="prev3" style="font-size:0.78rem;color:#10b981;font-weight:600;"></span>
                                </div>
                            </div>
                            <!-- hidden inputs เก็บ base64 ที่บีบแล้ว -->
                            <input type="hidden" name="img_b64_1" id="img_b64_1">
                            <input type="hidden" name="img_b64_2" id="img_b64_2">
                            <input type="hidden" name="img_b64_3" id="img_b64_3">
                        </div>
                        <button type="submit" class="btn-submit-post">ยืนยันการบันทึกข้อมูล</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>