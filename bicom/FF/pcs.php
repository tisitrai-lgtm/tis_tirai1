<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/XBase/Table.php';
require_once __DIR__ . '/db_connect.php';

function formatSqlDate($val) {
    $val = trim((string)$val);
    if (empty($val) || $val == '00000000') return null;
    $digits = preg_replace('/[^0-9]/', '', $val);
    if (strlen($digits) === 8) {
        $y = substr($digits, 0, 4); $m = substr($digits, 4, 2); $d = substr($digits, 6, 2);
        return "$y-$m-$d";
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['dbf_file'])) {
    $file_tmp = $_FILES['dbf_file']['tmp_name'];
    $file_name = $_FILES['dbf_file']['name'];

    try {
        $table = new \XBase\Table($file_tmp);
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

        $pdo->beginTransaction();

        // --- ตัดคำสั่ง DELETE ออกแล้ว เพื่อให้ข้อมูลเก่าคงอยู่ ---

        $placeholders = implode(',', array_fill(0, count($fixedHeaders), '?'));
        $sql = "INSERT INTO conversion_logs (" . implode(',', $fixedHeaders) . ") VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);

        while ($record = $table->nextRecord()) {
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
            $stmt->execute($rowData);
        }

        $pdo->commit();
        $table->close();
        $_SESSION['msg'] = "นำเข้าข้อมูลจากไฟล์ $file_name สำเร็จ (บันทึกเพิ่มเรียบร้อย)";
        header("Location: index.php");
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}