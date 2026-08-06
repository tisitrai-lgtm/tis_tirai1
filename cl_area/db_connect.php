<?php
$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "ezyro_39396635_cl_area";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        http_response_code(500); 
        echo json_encode([
            'status' => 'error', 
            'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้ในขณะนี้ กรุณาลองใหม่อีกครั้งภายหลัง (Code: DB_CONN_ERR)'
        ]);
        exit(); 
    } else {
        die("ไม่สามารถเชื่อมต่อฐานข้อมูลได้ในขณะนี้ กรุณาลองใหม่อีกครั้งภายหลัง");
    }
}
?>
