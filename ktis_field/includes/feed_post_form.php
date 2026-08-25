<?php
/**
 * includes/feed_post_form.php
 * ฟอร์มสร้างโพสต์ใหม่แบบ Modern Floating Action Button (FAB) + Modal Popup (เฉพาะ Admin)
 * @var array  $zones
 * @var array  $_SESSION
 */
?>
<?php if(($_SESSION['emp_level'] ?? '') === 'a'): ?>

<style>
/* ── Floating Action Button (FAB) ── */
.fab-post-btn {
    position: fixed;
    bottom: 28px;
    right: 28px;
    width: 58px;
    height: 58px;
    border-radius: 50%;
    background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);
    color: #ffffff;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 25px rgba(225, 29, 72, 0.45), 0 4px 10px rgba(0, 0, 0, 0.15);
    z-index: 1000;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    outline: none;
}
.fab-post-btn:hover {
    transform: scale(1.1) translateY(-3px);
    box-shadow: 0 14px 30px rgba(225, 29, 72, 0.55), 0 6px 12px rgba(0, 0, 0, 0.2);
}
.fab-post-btn:active {
    transform: scale(0.95);
}
.fab-post-btn i {
    font-size: 1.5rem;
    transition: transform 0.25s ease;
}
.fab-post-btn:hover i {
    transform: rotate(90deg);
}

/* Tooltip on Desktop hover */
.fab-post-btn::before {
    content: "แจ้งเรื่อง / เพิ่มโพสต์";
    position: absolute;
    right: 70px;
    background: #0f172a;
    color: #f8fafc;
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 0.82rem;
    font-weight: 700;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transform: translateX(10px);
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.fab-post-btn:hover::before {
    opacity: 1;
    transform: translateX(0);
}

/* Mobile & Tablet Positioning (Above Bottom Nav) */
@media (max-width: 1024px) {
    .fab-post-btn {
        bottom: 85px !important;
        right: 20px !important;
        width: 56px;
        height: 56px;
    }
    .fab-post-btn::before {
        display: none !important;
    }
}

/* ── Post Creation Modal Popup ── */
.post-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.78);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    z-index: 2147483648;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px 14px;
    opacity: 0;
    transition: opacity 0.25s ease;
}
.post-modal-overlay.show {
    display: flex;
    opacity: 1;
}

.post-modal-card {
    background: #ffffff;
    border-radius: 24px;
    border: 1px solid rgba(226, 232, 240, 0.9);
    max-width: 540px;
    width: 100%;
    max-height: calc(100dvh - 36px);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.35), 0 0 35px rgba(225, 29, 72, 0.1);
    transform: translateY(30px) scale(0.96);
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.post-modal-overlay.show .post-modal-card {
    transform: translateY(0) scale(1);
}

/* Modal Header */
.post-modal-header {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    padding: 14px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 3px solid #e11d48;
    flex-shrink: 0;
}
.post-modal-header .header-title {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #f8fafc;
    font-weight: 800;
    font-size: 1rem;
}
.post-modal-header .header-title i {
    color: #e11d48;
    font-size: 1.1rem;
}
.btn-close-modal {
    background: rgba(255, 255, 255, 0.1);
    border: none;
    color: #cbd5e1;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
    transition: all 0.15s ease;
}
.btn-close-modal:hover {
    background: #e11d48;
    color: #ffffff;
    transform: rotate(90deg);
}

/* Modal Body */
.post-modal-body {
    padding: 18px 20px;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}
.post-modal-body::-webkit-scrollbar {
    width: 5px;
}
.post-modal-body::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

/* Form Labels & Inputs */
.field-label {
    display: block;
    font-weight: 700;
    font-size: 0.86rem;
    color: #334155;
    margin-bottom: 6px;
}
.field-label .req { color: #e11d48; }
.field-label .hint { font-weight: 400; color: #94a3b8; font-size: .76rem; margin-left: 4px; }

.form-field {
    width: 100%;
    padding: 9px 12px;
    border: 1.5px solid #e2e8f0;
    border-radius: 11px;
    font-size: .9rem;
    font-weight: 600;
    background: #f8fafc;
    color: #1e293b;
    outline: none;
    transition: all 0.2s ease;
    font-family: inherit;
}
.form-field:focus {
    border-color: #e11d48;
    background: #ffffff;
    box-shadow: 0 0 0 3.5px rgba(225, 29, 72, 0.12);
}
select.form-field {
    cursor: pointer;
    height: 42px;
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2.5' stroke='%23e11d48'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19.5 8.25l-7.5 7.5-7.5-7.5' /%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 15px 15px;
    padding-right: 36px;
}
textarea.form-field { resize: vertical; min-height: 65px; }

.form-grid-2x { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px; }
.prob-selects-stack { display: flex; flex-direction: column; gap: 7px; margin-bottom: 14px; }

/* Image Upload Section */
.img-upload-section { margin-bottom: 16px; }
.img-upload-grid {
    display: flex;
    flex-direction: column;
    gap: 8px;
    background: #f8fafc;
    padding: 12px;
    border-radius: 12px;
    border: 1.5px dashed #cbd5e1;
}
.img-upload-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.img-upload-label {
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    border-radius: 9px;
    font-size: .82rem;
    font-weight: 700;
    color: #475569;
    transition: all .2s;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.img-upload-label:hover {
    background: #fee2e2;
    border-color: #e11d48;
    color: #e11d48;
    transform: translateY(-1px);
}
.img-upload-label i { color: #e11d48; }
.img-preview-text { font-size: .8rem; color: #10b981; font-weight: 700; }

.form-divider { border: none; border-top: 1px solid #f1f5f9; margin: 14px 0; }

/* Submit Button */
.btn-submit-post {
    width: 100%;
    padding: 11px;
    background: linear-gradient(135deg, #e11d48 0%, #be123c 100%);
    color: #ffffff;
    border: none;
    border-radius: 12px;
    font-size: 0.95rem;
    font-weight: 800;
    font-family: inherit;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all .2s ease;
    box-shadow: 0 4px 15px rgba(225, 29, 72, 0.35);
}
.btn-submit-post:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(225, 29, 72, 0.45);
}
.btn-submit-post:active { transform: translateY(0); }

@media (max-width: 600px) {
    .post-modal-overlay {
        padding: 10px 10px;
    }
    .post-modal-card {
        border-radius: 18px;
        max-height: calc(100dvh - 24px);
    }
    .post-modal-header {
        padding: 11px 14px;
    }
    .post-modal-header .header-title {
        font-size: 0.92rem;
    }
    .post-modal-body {
        padding: 12px 14px;
    }
    .form-grid-2x {
        grid-template-columns: 1fr;
        gap: 9px;
        margin-bottom: 10px;
    }
    .prob-selects-stack {
        gap: 6px;
        margin-bottom: 10px;
    }
    .field-label {
        font-size: 0.8rem;
        margin-bottom: 4px;
    }
    .form-field {
        padding: 8px 11px;
        font-size: 0.86rem;
        border-radius: 9px;
    }
    select.form-field {
        height: 38px;
        background-size: 13px 13px;
    }
    textarea.form-field {
        min-height: 55px;
    }
    .img-upload-grid {
        padding: 8px;
        gap: 6px;
    }
    .img-upload-label {
        padding: 6px 11px;
        font-size: 0.78rem;
    }
    .form-divider {
        margin: 10px 0;
    }
    .btn-submit-post {
        padding: 10px;
        font-size: 0.9rem;
        border-radius: 11px;
    }
}

/* Autocomplete Dropdown Styling */
.autocomplete-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    box-shadow: 0 10px 20px -3px rgba(0, 0, 0, 0.12);
    z-index: 50;
    max-height: 200px;
    overflow-y: auto;
    margin-top: 4px;
}
.autocomplete-item {
    padding: 10px 14px;
    cursor: pointer;
    font-size: 0.92rem;
    color: #1e293b;
    transition: background 0.1s ease;
    border-bottom: 1px solid #f1f5f9;
}
.autocomplete-item:last-child { border-bottom: none; }
.autocomplete-item:hover {
    background: #fee2e2;
    color: #e11d48;
    font-weight: 700;
}
.autocomplete-no-result {
    padding: 10px 14px;
    color: #64748b;
    font-size: 0.9rem;
    text-align: center;
}

/* Dark Mode Support */
.dark-mode .post-modal-card {
    background: #1e293b;
    border-color: #334155;
    box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.7);
}
.dark-mode .field-label { color: #f8fafc; }
.dark-mode .form-field {
    background: #0f172a;
    border-color: #475569;
    color: #f8fafc;
}
.dark-mode .form-field:focus {
    background: #16202e;
    border-color: #e11d48;
}
.dark-mode .img-upload-grid {
    background: #0f172a;
    border-color: #334155;
}
.dark-mode .img-upload-label {
    background: #1e293b;
    border-color: #334155;
    color: #cbd5e1;
}
.dark-mode .form-divider { border-top-color: #334155; }
.dark-mode .autocomplete-dropdown {
    background: #1e293b;
    border-color: #475569;
}
.dark-mode .autocomplete-item {
    color: #f8fafc;
    border-bottom-color: #334155;
}
.dark-mode .autocomplete-item:hover {
    background: #be123c;
    color: #ffffff;
}
</style>

<!-- 🔴 Floating Action Button (FAB) สำหรับสร้างโพสต์ใหม่ -->
<button type="button" class="fab-post-btn" id="fabPostBtn" onclick="openPostModal()" title="สร้างโพสต์ / แจ้งเหตุใหม่" aria-label="สร้างโพสต์ใหม่">
    <i class="fa-solid fa-plus"></i>
</button>

<!-- 🪟 Modal Popup ฟอร์มสร้างโพสต์ใหม่ -->
<div class="post-modal-overlay" id="postModalOverlay" onclick="closePostModal(event)">
    <div class="post-modal-card" onclick="event.stopPropagation()">

        <!-- Header -->
        <div class="post-modal-header">
            <div class="header-title">
                <i class="fa-solid fa-file-pen"></i>
                <span>แจ้งเรื่องรถอ้อยสกปรก</span>
            </div>
            <button type="button" class="btn-close-modal" onclick="closePostModal()" title="ปิดหน้าต่าง">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Body Form -->
        <div class="post-modal-body">
            <form id="uploadForm" enctype="multipart/form-data">

                <!-- หน่วย -->
                <div style="margin-bottom: 16px;">
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

                <!-- ทะเบียนรถ & เบอร์รถตัด (Grid 2 คอลัมน์) -->
                <div class="form-grid-2x">
                    <div>
                        <label class="field-label">
                            ทะเบียนรถบรรทุก <span class="req">*</span>
                        </label>
                        <input type="text" name="truck_number"
                               placeholder="เช่น 80-1234 นว."
                               class="form-field" required>
                    </div>
                    <div style="position: relative;">
                        <label class="field-label">
                            เบอร์รถตัด <span class="req">*</span> <span class="hint">(พิมพ์ค้นหาหรือเลือก)</span>
                        </label>
                        <input type="text" id="harvester_search" placeholder="พิมพ์ค้นหา เช่น 71..." class="form-field" autocomplete="off" required>
                        <input type="hidden" name="harvester_number" id="harvester_number" data-valid="false" required>
                        <div id="harvester_dropdown" class="autocomplete-dropdown" style="display: none;"></div>
                    </div>
                </div>

                <!-- ประเภทปัญหา (1, 2, 3) -->
                <div class="prob-selects-stack">
                    <div>
                        <label class="field-label">
                            ปัญหาที่พบ <span class="req">*</span>
                            <span class="hint">(เลือกได้สูงสุด 3 รายการ)</span>
                        </label>
                        <select name="problem_1" class="form-field prob-sel" required>
                            <option value="">-- ปัญหาที่ 1 (บังคับ) --</option>
                        </select>
                    </div>
                    <div>
                        <select name="problem_2" class="form-field prob-sel">
                            <option value="">-- ปัญหาที่ 2 (ถ้ามี) --</option>
                        </select>
                    </div>
                    <div>
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
                    <textarea name="post_text" rows="3" placeholder="ระบุรายละเอียดเพิ่มเติม..." class="form-field"></textarea>
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