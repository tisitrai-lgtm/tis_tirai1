<?php
// fetch_years.php
require_once 'db_connect.php'; // ตรวจสอบให้แน่ใจว่า db_connect.php สามารถเข้าถึงได้

header('Content-Type: application/json');

$years = [];
$sql = "SELECT DISTINCT year_label FROM production_years ORDER BY year_label DESC"; // ดึงปีที่ไม่ซ้ำกันและเรียงจากมากไปน้อย
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $years[] = $row['year_label'];
    }
    echo json_encode($years);
} else {
    // ส่งข้อผิดพลาดกลับไปในรูปแบบ JSON หากมีปัญหา
    echo json_encode(['error' => 'Failed to fetch years: ' . $conn->error]);
}

$conn->close();
?>