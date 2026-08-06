<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/XBase/Table.php';
require_once __DIR__ . '/db_connect.php';

function formatSqlDate($val) {
    $val = trim((string)$val);
    if (empty($val) || $val == '00000000') return null;
    $digits = preg_replace('/[^0-9]/', '', $val);
    if (strlen($digits) === 8) {
        $y = substr($digits, 0, 4); 
        $m = substr($digits, 4, 2); 
        $d = substr($digits, 6, 2);
        return "$y-$m-$d";
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['dbf_file'])) {
    $file_tmp = $_FILES['dbf_file']['tmp_name'];
    $file_name = $_FILES['dbf_file']['name'];

    try {
        // --- ส่วนที่ 1: วิเคราะห์ไฟล์และเก็บข้อมูลแถวแรกไว้ ---
        $table = new \XBase\Table($file_tmp);
        $firstRecord = $table->nextRecord();
        
        if (!$firstRecord) {
            $table->close();
            throw new Exception("ไฟล์ DBF ว่างเปล่า ไม่มีข้อมูลภายใน");
        }

        // ดึงค่าเพื่อใช้ลบข้อมูลเก่า
        $statnCode = trim((string)$firstRecord->getByIndex(0)); 
        $workDateRaw = $firstRecord->getByIndex(1);            
        $workDateFormatted = formatSqlDate($workDateRaw);

        if (!$workDateFormatted || empty($statnCode)) {
            $table->close();
            throw new Exception("ไม่พบข้อมูลระบุตัวตน (Date: $workDateFormatted, Station: $statnCode)");
        }

        // --- ส่วนที่ 2: เริ่ม Transaction และลบข้อมูลเก่า ---
        $pdo->beginTransaction();

        $deleteSql = "DELETE FROM conversion_logs WHERE WORK_DATE = ? AND STATN_CODE = ?";
        $delStmt = $pdo->prepare($deleteSql);
        $delStmt->execute([$workDateFormatted, $statnCode]);

        // เตรียมโครงสร้าง Header
        $fixedHeaders = [
            'STATN_CODE', 'WORK_DATE', 'WEIGH_DOCC', 'FARMR_CODE', 'FARMR_NAME', 
            'WEIGH_QUE', 'SWEET_CANE', 'PURI_CANE', 'BRIX_CANE', 'POL_CANE', 
            'WEIGH_CANE', 'CANE_TYPE', 'TRUCK_CODE', 'CANE_TCCUT', 'CANE_TCHOL', 
            'TRUCK_RATE', 'TRUCK_UP', 'TRUCK_MCUT', 'FARMR_TRCK', 'FARMR_TNAM', 
            'FARMR_UP', 'FARMR_UNAM', 'FARMR_CUT', 'FARMR_CNAM', 'FLAG_CUT1', 
            'TRUCK_DUCK', 'DATE_CUT', 'TIME_CUT', 'LAND_NUMB', 'LAND_ID', 
            'DATE_QUE', 'TIME_QUE', 'WEIGH_DTIN', 'WEIGH_DTOU', 'WEIGH_TMIN', 
            'WEIGH_TMOU', 'TIME_HFAC', 'TIME_MFAC', 'TIME_HTOTL', 'TIME_MTOTL', 'FLAG_ID'
        ];
        $dateFields = ['WORK_DATE', 'DATE_CUT', 'DATE_QUE', 'WEIGH_DTIN', 'WEIGH_DTOU'];

        $placeholders = implode(',', array_fill(0, count($fixedHeaders), '?'));
        $sqlInsert = "INSERT INTO conversion_logs (" . implode(',', $fixedHeaders) . ") VALUES ($placeholders)";
        $stmtInsert = $pdo->prepare($sqlInsert);

        $rowCount = 0;

        // --- ฟังก์ชันช่วยจัดการข้อมูลแต่ละ Record ---
        $processRecord = function($record) use ($fixedHeaders, $dateFields) {
            $rowData = [];
            for ($i = 0; $i < count($fixedHeaders); $i++) {
                $colName = $fixedHeaders[$i];
                $val = $record->getByIndex($i);
                if (in_array($colName, $dateFields)) {
                    $val = formatSqlDate($val);
                } else if (is_string($val)) {
                    $val = iconv('CP874', 'UTF-8//IGNORE', trim($val));
                }
                $rowData[] = $val;
            }
            return $rowData;
        };

        // 1. Insert ข้อมูลแถวแรกที่อ่านค้างไว้ (ใบรับ 002086)
        $stmtInsert->execute($processRecord($firstRecord));
        $rowCount++;

        // 2. วนลูป Insert ข้อมูลแถวที่เหลือทั้งหมด
        while ($record = $table->nextRecord()) {
            $stmtInsert->execute($processRecord($record));
            $rowCount++;
        }

        $pdo->commit();
        $table->close();
        
        $_SESSION['msg'] = "นำเข้าข้อมูลหน่วย $statnCode ประจำวันที่ $workDateFormatted สำเร็จจำนวน $rowCount รายการ";
        header("Location: index.php");
        exit;

    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        die("ระบบเกิดข้อผิดพลาด: " . $e->getMessage());
    }
}