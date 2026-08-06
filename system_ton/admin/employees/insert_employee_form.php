<?php
// เชื่อมต่อฐานข้อมูล
include '../../db_connect.php';

// ดึงข้อมูล Roles (บทบาท)
$roles = $conn->query("SELECT role_id, role_name FROM roles");

// ดึงข้อมูล Units (หน่วยงาน/แผนก)
$units = $conn->query("SELECT unit_id, unit_name FROM units");

// ดึงข้อมูล Potential Supervisors (ผู้ที่สามารถเป็นหัวหน้างานได้)
// ในเบื้องต้น เราจะดึงทุกคนที่มี role_id = 2 (Supervisor) มาแสดง
$supervisors = $conn->query("SELECT employee_id, CONCAT(first_name, ' ', last_name) AS full_name FROM employees WHERE role_id = 2");

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เพิ่มข้อมูลพนักงานใหม่</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; }
        .container { max-width: 600px; margin: auto; background: #f4f4f4; padding: 20px; border-radius: 8px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="email"], input[type="password"], select {
            width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;
        }
        button { background-color: #5cb85c; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>

<div class="container">
    <h2>แบบฟอร์มเพิ่มพนักงานใหม่</h2>
    <form action="process_insert.php" method="POST">

        <div class="form-group">
            <label for="first_name">ชื่อจริง:</label>
            <input type="text" id="first_name" name="first_name" required>
        </div>
        <div class="form-group">
            <label for="last_name">นามสกุล:</label>
            <input type="text" id="last_name" name="last_name" required>
        </div>
        <div class="form-group">
            <label for="employee_code">รหัสพนักงาน (ใช้สำหรับเข้าสู่ระบบ):</label>
            <input type="text" id="employee_code" name="employee_code" required>
        </div>
        <div class="form-group">
            <label for="password">รหัสผ่าน (ชั่วคราว):</label>
            <input type="password" id="password" name="password" required>
        </div>

        <div class="form-group">
            <label for="role_id">บทบาท (Role):</label>
            <select id="role_id" name="role_id" required>
                <?php while($row = $roles->fetch_assoc()): ?>
                    <option value="<?php echo $row['role_id']; ?>"><?php echo $row['role_name']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="unit_id">หน่วยงาน:</label>
            <select id="unit_id" name="unit_id" required>
                <option value="">-- เลือกหน่วยงาน --</option>
                <?php while($row = $units->fetch_assoc()): ?>
                    <option value="<?php echo $row['unit_id']; ?>"><?php echo $row['unit_name']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="supervisor_id">หัวหน้างานโดยตรง:</label>
            <select id="supervisor_id" name="supervisor_id">
                <option value="">-- ไม่มีหัวหน้างาน (เช่น Admin หรือ CEO) --</option>
                <?php if ($supervisors->num_rows > 0): ?>
                    <?php while($row = $supervisors->fetch_assoc()): ?>
                        <option value="<?php echo $row['employee_id']; ?>"><?php echo $row['full_name']; ?></option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
        </div>
        
        <button type="submit">บันทึกข้อมูลพนักงาน</button>

    </form>
</div>

</body>
</html>
