<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Manager - นำเข้าข้อมูลแปลงอ้อย (.GDB)</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; padding: 40px; }
        .upload-container { background: white; padding: 30px; border-radius: 8px; max-width: 500px; margin: 0 auto; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h2 { color: #2c3e50; margin-top: 0; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; color: #34495e; }
        select, input[type="file"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { background-color: #27ae60; color: white; border: none; padding: 12px 20px; border-radius: 4px; width: 100%; font-size: 16px; cursor: pointer; font-weight: bold; }
        button:hover { background-color: #219653; }
    </style>
</head>
<body>

<div class="upload-container">
    <h2>ระบบผู้จัดการ: นำเข้าข้อมูลแปลงอ้อย</h2>
    <hr style="border: 0; border-top: 1px solid #eee; margin-bottom: 20px;">
    
    <form action="upload_action.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="crop_year">เลือกปีการผลิตที่ต้องการบันทึก:</label>
            <select name="crop_year" id="crop_year" required>
                <option value="68-69">ปีการผลิต 2568 / 2569</option>
                <option value="69-70">ปีการผลิต 2569 / 2570</option>
                <option value="70-71">ปีการผลิต 2570 / 2571</option>
            </select>
        </div>

        <div class="form-group">
            <label for="gdb_file">เลือกไฟล์ GDB (บีบอัดเป็น .zip):</label>
            <input type="file" name="gdb_file" id="gdb_file" accept=".zip" required>
        </div>

        <button type="submit">🚀 เริ่มนำเข้าข้อมูลเข้าสู่ระบบ</button>
    </form>
</div>

</body>
</html>