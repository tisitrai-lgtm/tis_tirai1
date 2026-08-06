<?php
// add_queue.php
require_once 'queue_logic.php'; 
date_default_timezone_set('Asia/Bangkok');

if (!isset($current_round_info)) {
    $current_round_info = getActiveRoundInfo($conn); 
}

$view_round_num = $_GET['view_round'] ?? ($current_round_info['round_number'] ?? 0);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการคิวรถบรรทุกอ้อย | Modern Control</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin_style.css?v1.2=<?php echo time(); ?>">
    <style>
        :root {
            --primary: #2563eb;
            --success: #10b981;
            --bg-body: #f1f5f9;
            --card-bg: #ffffff;
            --danger: #ef4444;
            --warning: #f59e0b;
        }

        body { font-family: 'Sarabun', sans-serif; background-color: var(--bg-body); color: #1e293b; }

        /* แถบควบคุมด้านบน */
        .control-panel {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            padding: 20px;
            border-radius: 16px;
            color: white;
            margin-bottom: 25px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .selector-group { display: flex; align-items: center; gap: 12px; }

        .select-round-custom {
            padding: 10px 15px;
            border-radius: 10px;
            border: 2px solid #3b82f6;
            background: #ffffff;
            font-weight: 700;
            color: #1e40af;
            font-size: 1rem;
            cursor: pointer;
        }

        /* ปุ่มเพิ่มรอบ */
        .btn-add-round {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-add-round:hover { transform: translateY(-2px); box-shadow: 0 6px 12px rgba(16, 185, 129, 0.4); }

        /* การ์ดลงทะเบียน */
        .registration-card {
            border-top: 5px solid var(--primary);
            position: relative;
        }

        .round-badge {
            position: absolute;
            top: -15px;
            right: 20px;
            background: var(--primary);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .btn-register {
            background: var(--primary) !important;
            width: 100%;
            padding: 15px !important;
            font-size: 1.1rem !important;
            border-radius: 12px !important;
            color: white;
            border: none;
            cursor: pointer;
        }

        .edit-highlight { border: 2px solid #f59e0b !important; background-color: #fffbeb !important; }
        
        /* สไตล์ตารางและการจัดการ */
        .list-table thead th { background-color: #f8fafc; color: #475569; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; padding: 15px; }
        .plate-badge.head { background: #dbeafe; color: #1e40af; font-weight: 700; padding: 5px 10px; border-radius: 6px; }
        .plate-badge.tail { background: #f1f5f9; color: #475569; padding: 5px 10px; border-radius: 6px; border: 1px dashed #cbd5e1; }
        
        .action-buttons { display: flex; gap: 8px; justify-content: center; align-items: center; }
        .btn-table { border: none; background: none; cursor: pointer; font-size: 1.2rem; transition: transform 0.2s; }
        .btn-table:hover { transform: scale(1.2); }
    </style>
</head>
<body>
    <div id="toast-container"></div>
    
    <div class="container">
        <header style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <span style="font-size: 2.5rem;">🚜</span>
            <div>
                <h1 style="margin:0; font-size: 1.5rem; color: #0f172a;">ระบบจัดการคิวรถบรรทุกอ้อย</h1>
                <p style="margin:0; color: #64748b; font-size: 0.9rem;">หน้าจัดการลงทะเบียนและจัดการรอบ</p>
            </div>
        </header>

        <div class="control-panel">
            <div class="selector-group">
                <div>
                    <label style="display:block; font-size: 0.75rem; color: #cbd5e1; margin-bottom: 4px;">เลือกเครื่อง/รอบที่กำลังทำงาน</label>
                    <form method="GET" action="add_queue.php" id="view-round-form">
                        <select name="view_round" class="select-round-custom" onchange="this.form.submit()">
                            <?php
                            $all_rounds_query = $conn->query("SELECT round_number, is_active FROM rounds ORDER BY round_number DESC LIMIT 15");
                            if ($all_rounds_query->num_rows > 0) {
                                while($r = $all_rounds_query->fetch_assoc()) {
                                    $selected = ($r['round_number'] == $view_round_num) ? 'selected' : '';
                                    
                                    // แก้ไขปัญหา "สี่เหลี่ยม" โดยใช้สัญลักษณ์ตัวอักษรมาตรฐานที่รองรับทุกเบราว์เซอร์
                                    $status_symbol = ($r['is_active'] == 1) ? '●' : '○'; 
                                    echo "<option value='{$r['round_number']}' $selected>{$status_symbol} รอบที่ {$r['round_number']}</option>";
                                }
                            } else {
                                echo "<option value='0'>ยังไม่มีรอบ</option>";
                            }
                            ?>
                        </select>
                    </form>
                </div>
            </div>

            <form method="POST" onsubmit="return confirm('คุณต้องการสร้างรอบใหม่ (ลำดับถัดไป) ในระบบใช่หรือไม่?');">
                <input type="hidden" name="action" value="start_new_round">
                <button type="submit" class="btn-add-round">
                    <span>➕</span> เพิ่มรอบใหม่ในระบบ
                </button>
            </form>
        </div>

        <div class="section-card registration-card">
            <div class="round-badge">📍 กำลังบันทึกลง: รอบที่ <?php echo $view_round_num; ?></div>
            
            <h2 class="section-title">
                <?php echo $edit_entry_id ? '✏️ แก้ไขข้อมูลคิว #' . htmlspecialchars($edit_data['queue_number']) : '📝 ลงทะเบียนรถเข้าคิว'; ?>
            </h2>
            
            <form method="POST" action="add_queue.php?view_round=<?php echo $view_round_num; ?>" class="form-grid" 
                  onsubmit="return <?php echo $edit_entry_id ? "confirm('ยืนยันการบันทึกข้อมูลที่แก้ไขใช่หรือไม่?')" : "true"; ?>;">
                
                <input type="hidden" name="current_round_number" value="<?php echo htmlspecialchars($view_round_num); ?>">

                <?php if ($edit_entry_id): ?>
                    <input type="hidden" name="action" value="update_entry">
                    <input type="hidden" name="entry_id" value="<?php echo $edit_entry_id; ?>">
                <?php endif; ?>

                <div class="reg-input-group">
                    <div>
                        <label>หมายเลขคิว</label>
                        <input type="text" name="manual_queue_number" required placeholder="ระบุเลขคิว" 
                               value="<?php echo htmlspecialchars($edit_data['queue_number'] ?? ''); ?>"
                               class="<?php echo $edit_entry_id ? 'edit-highlight' : ''; ?>">
                    </div>
                    <div>
                        <label>ทะเบียนรถหัวลาก</label>
                        <input type="text" name="tractor_plate" id="tractor_plate" required 
                               list="plates_list" placeholder="สท.80-1234" maxlength="11"
                               oninput="formatLicensePlate(this)" autocomplete="off" 
                               value="<?php echo htmlspecialchars($edit_data['tractor_plate'] ?? ''); ?>"
                               class="<?php echo $edit_entry_id ? 'edit-highlight' : ''; ?>">
                    </div>
                    <div>
                        <label>ทะเบียนพ่วง (ถ้ามี)</label>
                        <input type="text" name="trailer_plate" id="trailer_plate" 
                               list="plates_list" placeholder="สท.80-5678" maxlength="11"
                               oninput="formatLicensePlate(this)" autocomplete="off"
                               value="<?php echo htmlspecialchars($edit_data['trailer_plate'] ?? ''); ?>"
                               class="<?php echo $edit_entry_id ? 'edit-highlight' : ''; ?>">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-register" <?php echo ($view_round_num == 0 && !$edit_entry_id) ? 'disabled' : ''; ?>>
                        <?php echo $edit_entry_id ? '💾 บันทึกการแก้ไข' : '✅ ยืนยันบันทึกคิวลงรอบที่ ' . $view_round_num; ?>
                    </button>
                    
                    <?php if ($edit_entry_id): ?>
                        <a href="add_queue.php?view_round=<?php echo $view_round_num; ?>" 
                           style="display:block; text-align:center; margin-top:10px; color:var(--danger); text-decoration:none; font-weight:bold;"
                           onclick="return confirm('ยกเลิกการแก้ไขและกลับสู่หน้าลงทะเบียนปกติ?')">ยกเลิกแก้ไข</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="section-card">
            <h2 class="section-title">📋 รายการคิวรอบที่ <?php echo $view_round_num; ?></h2>
            <div class="table-responsive">
                <table class="list-table">
                    <thead>
                        <tr>
                            <th>คิว</th>
                            <th>ทะเบียนหัวลาก</th>
                            <th>ทะเบียนพ่วง</th>
                            <th>เวลา</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($current_queue_entries) > 0): ?>
                            <?php foreach ($current_queue_entries as $entry): ?>
                            <tr>
                                <td style="font-weight:bold; color:var(--primary);"><?php echo htmlspecialchars($entry['queue_number']); ?></td>
                                <td><span class="plate-badge head"><?php echo htmlspecialchars($entry['tractor_plate']); ?></span></td>
                                <td>
                                    <?php if ($entry['trailer_plate']): ?>
                                        <span class="plate-badge tail"><?php echo htmlspecialchars($entry['trailer_plate']); ?></span>
                                    <?php else: ?>
                                        <span style="color:#cbd5e1; font-size:0.8rem;">-ไม่มีพ่วง-</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size: 0.85rem; color: #64748b;">
                                    🕒 <?php echo isset($entry['created_at']) ? date('H:i', strtotime($entry['created_at'])) : '-'; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="add_queue.php?action=edit&entry_id=<?php echo $entry['entry_id']; ?>&view_round=<?php echo $view_round_num; ?>" 
                                           class="btn-table" title="แก้ไข">✏️</a>
                                        
                                        <form method="POST" onsubmit="return confirm('ยืนยันการลบคิวหมายเลข <?php echo $entry['queue_number']; ?> ใช่หรือไม่?');" style="margin:0;">
                                            <input type="hidden" name="action" value="delete_entry">
                                            <input type="hidden" name="entry_id" value="<?php echo $entry['entry_id']; ?>">
                                            <button type="submit" class="btn-table">🗑️</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align:center; padding: 40px; color: #94a3b8;">ยังไม่มีคิวในรอบนี้</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    function formatLicensePlate(input) {
        let value = input.value.replace(/[^ก-ฮ0-9]/g, ''); 
        let formatted = '';
        if (/^[0-9]+$/.test(value)) {
            formatted = value.substring(0, 4);
        } else if (value.length > 0) {
            let charsMatch = value.match(/[ก-ฮ]{0,2}/);
            let chars = charsMatch ? charsMatch[0] : '';
            let numbers = value.replace(/[ก-ฮ]/g, '');
            formatted = chars;
            if (chars.length >= 2) {
                formatted += '.';
                let digits1 = numbers.substring(0, 2);
                formatted += digits1;
                if (digits1.length === 2) {
                    formatted += '-';
                    formatted += numbers.substring(2, 6);
                }
            } else {
                formatted += numbers.substring(0, 6);
            }
        }
        input.value = formatted;
    }

    function showToast(message, type) {
        if (!message) return;
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        toast.className = `toast ${type}`; 
        toast.innerHTML = `<span>${message}</span>`;
        container.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 500);
        }, 4000);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const msg = "<?php echo isset($message) ? addslashes($message) : ''; ?>";
        const cls = "<?php echo isset($message_class) ? $message_class : ''; ?>".replace('alert-', '');
        if (msg) showToast(msg, cls);
    });
    </script>

    <datalist id="plates_list">
        <?php if(isset($existing_plates)) foreach ($existing_plates as $plate): ?>
            <option value="<?php echo htmlspecialchars($plate); ?>">
        <?php endforeach; ?>
    </datalist>
</body>
</html>