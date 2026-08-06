<?php
require_once 'db_connect.php'; // ตรวจสอบให้แน่ใจว่า db_connect.php เชื่อมต่อฐานข้อมูลได้อย่างถูกต้อง

// 1. ดึงปีทั้งหมดจากตาราง production_years
$sql_years = "SELECT year_label FROM production_years ORDER BY year_label DESC";
$result_years = $conn->query($sql_years);

// 2. ดึง Agency ทั้งหมดจากตาราง cane_plot_data (ไม่ซ้ำกัน)
// กรองค่าที่เป็น NULL หรือว่างออกไป เพื่อให้ตัวเลือกดูสะอาด
$sql_agency = "SELECT DISTINCT agency FROM cane_plot_data WHERE agency IS NOT NULL AND agency != '' ORDER BY agency ASC";
$result_agency = $conn->query($sql_agency);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>ระบบรูปใส่แปลงประมาณตัน</title>

    <link rel="icon" href="icon/2.png" type="image/png">

    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />

    <style>
        body, html {
            height: 100%;
            background-color: #f0f8ff; /* Light, soft blue background */
            font-family: 'Kanit', sans-serif; /* Apply Kanit font */
        }
        .container {
            height: 100%;
            display: flex;
            flex-direction: column; /* Stack elements vertically */
            justify-content: center;
            align-items: center;
            padding: 20px; /* Add some padding for smaller screens */
        }
        .system-title {
            font-size: 2.5rem; /* Larger system title */
            font-weight: 700;
            color: #1a4d7c; /* Darker blue for system title */
            margin-bottom: 2rem; /* Space below system title */
            text-align: center;
        }
        .card {
            width: 100%;
            max-width: 420px; /* Card width */
            padding: 2.5rem; /* More generous padding */
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15); /* Stronger, softer shadow */
            border-radius: 16px; /* More rounded corners */
            background: #fff;
            border: none; /* Remove default border */
            text-align: center; /* Center align content within the card */
        }
        .card-icon {
            width: 80px; /* Adjust icon size as needed */
            height: 80px;
            margin-bottom: 1.5rem; /* Space below icon */
            display: block; /* Ensure it behaves as a block element for margin auto */
            margin-left: auto;
            margin-right: auto;
            object-fit: contain; /* Ensure the image fits well */
        }
        h4.card-title {
            font-weight: 600; /* Slightly less bold than system title */
            margin-bottom: 2rem; /* More space below title */
            color: #333; /* Standard dark text color */
            font-size: 1.7rem; /* Slightly larger title for form */
        }
        .form-label {
            font-weight: 600;
            color: #555;
            text-align: left; /* Align label to the left */
            display: block; /* Make label a block element */
            margin-bottom: 0.5rem; /* Space below label */
        }
        select.form-select {
            padding: 0.75rem 1rem; /* More padding for select */
            font-size: 1.1rem; /* Larger font in select */
            border-radius: 8px; /* More rounded select */
            border: 1px solid #a8c1de; /* Lighter blue border for select */
            box-shadow: none; /* Remove default focus shadow */
            transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        select.form-select:focus {
            border-color: #007bff; /* Bootstrap's primary blue on focus */
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25); /* Bootstrap's focus shadow */
            outline: 0;
        }
        .btn-primary {
            background-color: #007bff; /* Blue button */
            border: none;
            font-weight: 600;
            padding: 0.8rem 1.5rem; /* Larger button padding */
            border-radius: 8px; /* More rounded button */
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.2s ease;
            font-size: 1.1rem; /* Larger button font */
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.2); /* Subtle shadow for button */
        }
        .btn-primary:hover {
            background-color: #0056b3; /* Darker blue on hover */
            transform: translateY(-2px); /* Slight lift on hover */
            box-shadow: 0 6px 15px rgba(0, 123, 255, 0.3);
        }
        .btn-primary:active {
            transform: translateY(0); /* Reset on click */
            box-shadow: 0 2px 5px rgba(0, 123, 255, 0.2);
        }
    </style>
</head>
<body>

<div class="container">
    <h1 class="system-title">ระบบรูปใส่แปลงประมาณตัน</h1>

    <div class="card">
        <img src="icon/2.png" alt="Sugarcane Icon" class="card-icon">
        
        <h4 class="card-title">เลือกปีการผลิตและหน่วยงาน</h4>
        <form action="user_dashboard.php" method="get">
            
            <div class="mb-4">
                <label for="year" class="form-label">ปีการผลิต</label>
                <select class="form-select" name="year" id="year" required>
                    <option value="" disabled selected>-- กรุณาเลือกปี --</option>
                    <?php if ($result_years && $result_years->num_rows > 0): ?>
                        <?php while ($row = $result_years->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($row['year_label']); ?>">
                                <?php echo htmlspecialchars($row['year_label']); ?>
                            </option>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <option value="" disabled>ไม่มีข้อมูลปี</option>
                    <?php endif; ?>
                </select>
            </div>

            <div class="mb-4">
                <label for="agency" class="form-label">หน่วยนักส่งเสริม</label>
                <select class="form-select" name="agency" id="agency" required>
                    <option value="" disabled selected>-- กรุณาเลือกหน่วยนักส่งเสริม --</option>
                    <?php if ($result_agency && $result_agency->num_rows > 0): ?>
                        <?php while ($row_agency = $result_agency->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($row_agency['agency']); ?>">
                                <?php echo htmlspecialchars($row_agency['agency']); ?>
                            </option>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <option value="" disabled>ไม่พบข้อมูลหน่วยนักส่งเสริม</option>
                    <?php endif; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary w-100">เข้าสู่ระบบ</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php 
// Close database connection after all operations are done
if (isset($conn)) {
    $conn->close(); 
}
?>