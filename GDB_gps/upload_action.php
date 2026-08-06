<?php
// upload_action.php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $crop_year = $_POST['crop_year'];
    
    if (!isset($_FILES['gdb_file']) || $_FILES['gdb_file']['error'] !== UPLOAD_ERR_OK) {
        die("<div style='color:red;'>เกิดข้อผิดพลาดในการอัปโหลดไฟล์กรุณาลองใหม่อีกครั้ง</div>");
    }

    $file_name = $_FILES['gdb_file']['name'];
    $file_tmp = $_FILES['gdb_file']['tmp_name'];
    
    echo "<h2>กำลังประมวลผลนำเข้าข้อมูล...</h2>";
    echo "🌾 ปีการผลิต: " . htmlspecialchars($crop_year) . "<br>";
    echo "📦 ไฟล์ที่อัปโหลด: " . htmlspecialchars($file_name) . "<br><br>";
    
    if (!is_dir('uploads')) {
        mkdir('uploads', 0777, true);
    }

    // ตั้งชื่อไฟล์และทางเดินระบบ (Path) ใหม่ป้องกันชื่อซ้ำ
    $timestamp = time();
    $target_zip = "uploads/" . $timestamp . "_" . $file_name;
    $extract_to = "uploads/extracted_" . $timestamp;

    if (move_uploaded_file($file_tmp, $target_zip)) {
        
        // 1. แตกไฟล์ .zip ออกมา
        $zip = new ZipArchive;
        if ($zip->open($target_zip) === TRUE) {
            $zip->extractTo($extract_to);
            $zip->close();
            echo "✔️ แตกไฟล์สำรองข้อมูลชั่วคราวสำเร็จ...<br>";
            
            // 2. ค้นหาโฟลเดอร์ .gdb ที่อยู่ข้างใน (ค้นหาลึกลงไปทุกชั้นโฟลเดอร์)
            $gdb_folder = null;
            $dir_iterator = new RecursiveDirectoryIterator($extract_to);
            $iterator = new RecursiveIteratorIterator($dir_iterator);
            foreach ($iterator as $file) {
                if ($file->isDir() && substr($file->getPathname(), -4) === '.gdb') {
                    $gdb_folder = $file->getPathname();
                    break;
                }
            }

            // กรณีค้นหาชั้นแรกตรงๆ
            if (!$gdb_folder) {
                $dirs = glob($extract_to . "/*.gdb");
                if (!empty($dirs)) { $gdb_folder = $dirs[0]; }
            }

            if ($gdb_folder) {
                echo "🔎 พบโฟลเดอร์ฐานข้อมูลแผนที่: <code>" . basename($gdb_folder) . "</code><br>";
                echo "🚀 กำลังแปลงพิกัดและบันทึกลง MySQL (อาจใช้เวลาสักครู่สำหรับ 30,000 แปลง)...<br>";
                
                // 3. เรียกใช้คำสั่ง ogr2ogr ดึงพิกัดไบนารีจาก GDB ยัดเข้าตาราง sugarcane_plots ตรงๆ
                // หมายเหตุ: เช็กให้ชัวร์ว่าเซิร์ฟเวอร์ลงโปรแกรม GDAL แล้ว และตั้งค่าชื่อชั้นข้อมูล (Layer) ให้ตรง
                $db_host = "localhost";
                $db_name = "gdb_gps";
                $db_user = "root";
                $db_pass = ""; // รหัสผ่าน MySQL ของคุณ

                // คำสั่งรันระบบแปลงพิกัด (แปลงเป็นระบบพิกัดโลก EPSG:4326 เสมอเพื่อให้ใช้กับแผนที่ดาวเทียมได้)
                $command = "ogr2ogr -f \"MySQL\" MySQL:\"$db_name,host=$db_host,user=$db_user,password=$db_pass\" \"$gdb_folder\" -nln sugarcane_plots -append -t_srs EPSG:4326";
                
                // สั่งประมวลผลที่ระบบปฏิบัติการหลังบ้าน
                exec($command, $output, $return_var);

                if ($return_var === 0) {
                    
                    // 4. หลังจากข้อมูลโพลีกอนเข้าตารางแล้ว เราจะอัปเดตคอลัมน์ 'crop_year' และ 'plot_code' ให้สมบูรณ์
                    // โดยปกติ ogr2ogr จะแอดข้อมูลพิกัดไปที่ฟิลด์ SHAPE แต่อาจจะปล่อยให้ crop_year เป็นค่าว่าง
                    try {
                        // อัปเดตปีการผลิตให้ข้อมูลชุดที่เพิ่งแอดเข้ามาใหม่
                        $stmt = $conn->prepare("UPDATE sugarcane_plots SET crop_year = :crop_year WHERE crop_year = '' OR crop_year IS NULL");
                        $stmt->execute([':crop_year' => $crop_year]);
                        
                        // ถ้ารหัสแปลงถูกเก็บไว้ในฟิลด์อื่น (เช่น Name จาก Garmin) ให้ดึงมาใส่ใน plot_code 
                        // ในที่นี้เราเซ็ตอัปเดตเบื้องต้น หรือถ้าใน GDB มีฟิลด์อื่นอยู่แล้วระบบจะสร้างคอลัมน์ให้อัตโนมัติครับ
                        
                        echo "<h3 style='color:green;'>🎉 สำเร็จ! นำเข้าแปลงอ้อยเข้าสู่ระบบปีการผลิต $crop_year เรียบร้อยแล้ว</h3>";
                        echo "<a href='manager.php'><button style='padding:10px;'>กลับหน้าหลัก</button></a>";
                        
                    } catch (PDOException $e) {
                        echo "<div style='color:red;'>บันทึกปีการผลิตล้มเหลว: " . $e->getMessage() . "</div>";
                    }

                } else {
                    echo "<div style='color:red;'>❌ เกิดข้อผิดพลาดในการแปลงไฟล์แผนที่ (Error Code: $return_var)</div>";
                    echo "กรุณาตรวจสอบว่าเซิร์ฟเวอร์ของคุณติดตั้งเครื่องมือ GDAL/ogr2ogr เรียบร้อยแล้ว";
                }

            } else {
                echo "<div style='color:red;'>❌ ไม่พบโฟลเดอร์นามสกุล .gdb อยู่ภายในไฟล์ .zip ที่ส่งมา</div>";
            }
            
            // 5. เคลียร์ไฟล์ขยะในโฟลเดอร์ชั่วคราวเพื่อประหยัดพื้นที่ความจำเซิร์ฟเวอร์
            @array_map('unlink', glob("$extract_to/*.*"));
            @rmdir($extract_to);
            @unlink($target_zip);

        } else {
            echo "<div style='color:red;'>❌ ไม่สามารถเปิดไฟล์ .zip ได้ ไฟล์อาจจะชำรุด</div>";
        }
    } else {
        echo "<div style='color:red;'>❌ ไม่สามารถบันทึกไฟล์ลงเซิร์ฟเวอร์ได้ ตรวจสอบสิทธิ์การเขียนโฟลเดอร์ uploads</div>";
    }
}
?>