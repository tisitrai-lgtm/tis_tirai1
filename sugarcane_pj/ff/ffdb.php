<?php
// เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล
require_once 'db_connect.php'; // ตรวจสอบ Path ให้ถูกต้อง

// เริ่มต้น Session เพื่อใช้งาน selected_year ใน nav.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$selected_year = $_GET['year'] ?? ''; // รับค่าปีการผลิตจาก URL

// ตรวจสอบว่ามีการเลือกปีมาหรือไม่
if (empty($selected_year)) {
    echo '<!DOCTYPE html>
              <html lang="th">
              <head>
                  <meta charset="UTF-8">
                  <meta name="viewport" content="width=device-width, initial-scale=1.0">
                  <title>Error</title>
                  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
                  <style>
                      body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: #f8f9fa; }
                      .alert { margin-top: 20px; }
                  </style>
              </head>
              <body>
                  <div class="container text-center">
                      <div class="alert alert-danger" role="alert">
                          <h2>กรุณาเลือกปีการผลิตจากหน้าแรกก่อนครับ</h2>
                          <p><a href="index.php" class="btn btn-primary mt-3">กลับไปหน้าเลือกปี</a></p>
                      </div>
                  </div>
              </body>
              </html>';
    exit;
}

// --- การตั้งค่าสำหรับการแบ่งหน้า (Pagination) และจำนวนแถวต่อหน้า ---
$results_per_page_options = [10, 25, 50, 100]; // ตัวเลือกจำนวนแถวต่อหน้า
$results_per_page = isset($_GET['limit']) && in_array((int)$_GET['limit'], $results_per_page_options) ? (int)$_GET['limit'] : 10;

$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $results_per_page;
if ($offset < 0) $offset = 0; // ป้องกัน offset ติดลบ

// --- การตั้งค่าสำหรับการค้นหา (Search) ---
$search_term = $_GET['search'] ?? '';
$search_term = htmlspecialchars(trim($search_term)); // ทำความสะอาดและป้องกัน XSS

// --- เตรียมส่วนของ SQL Query สำหรับ WHERE Clause และ Parameters ---
$where_parts = ["production_year = ?"];
$param_values = [$selected_year];
$param_types_base = "s"; // s สำหรับ string (production_year)

if (!empty($search_term)) {
    // เพิ่มเงื่อนไขการค้นหาในคอลัมน์ที่ต้องการ
    $where_parts[] = "(agency LIKE ? OR contract_number LIKE ? OR plot_id LIKE ? OR notes LIKE ?)";
    $search_like = '%' . $search_term . '%';
    $param_values[] = $search_like;
    $param_values[] = $search_like;
    $param_values[] = $search_like;
    $param_values[] = $search_like;
    $param_types_base .= "ssss"; // ssss สำหรับ search_like 4 ตัว
}

$where_clause = "WHERE " . implode(" AND ", $where_parts);

// สร้าง array ของ references สำหรับ bind_param
$refs = [];
foreach($param_values as $key => $value) {
    $refs[$key] = &$param_values[$key];
}

// --- 1. Query เพื่อหาจำนวนข้อมูลทั้งหมด (สำหรับคำนวณจำนวนหน้า) ---
$count_sql = "SELECT COUNT(*) AS total_records FROM soil_data " . $where_clause;
$count_stmt = $conn->prepare($count_sql);
if ($count_stmt === false) {
    die("Error preparing count statement: " . $conn->error);
}
// ใช้ call_user_func_array เพื่อ bind_param ด้วยอาร์เรย์ของพารามิเตอร์
// ต้องใส่ $param_types_base เป็น argument ตัวแรก
call_user_func_array([$count_stmt, 'bind_param'], array_merge([$param_types_base], $refs)); // Line 72
$count_stmt->execute();
$total_records_result = $count_stmt->get_result()->fetch_assoc();
$total_records = $total_records_result['total_records'];
$total_pages = ceil($total_records / $results_per_page);

// ปรับ current_page หากอยู่นอกช่วง
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
    $offset = ($current_page - 1) * $results_per_page;
} elseif ($current_page < 1) {
    $current_page = 1;
    $offset = 0;
}


// --- 2. Main data query (with LIMIT for pagination) ---
$data_sql = "SELECT * FROM soil_data " . $where_clause . " ORDER BY id DESC LIMIT ? OFFSET ?";
$data_stmt = $conn->prepare($data_sql);
if ($data_stmt === false) {
    die("Error preparing data statement: " . $conn->error);
}

// เตรียมพารามิเตอร์สำหรับ data_stmt
$data_param_values = array_merge($param_values, [$results_per_page, $offset]);
$data_param_types = $param_types_base . "ii"; // เพิ่ม ii สำหรับ LIMIT และ OFFSET

// สร้าง array ของ references สำหรับ bind_param ของ data_stmt
$data_refs = [];
foreach($data_param_values as $key => $value) {
    $data_refs[$key] = &$data_param_values[$key];
}

call_user_func_array([$data_stmt, 'bind_param'], array_merge([$data_param_types], $data_refs)); // Line 99
$data_stmt->execute();
$result = $data_stmt->get_result();

// Function to construct image path (ปรับแก้พารามิเตอร์ให้ตรงกับการเรียกใช้)
function getImagePath($production_year, $agency, $contract_number, $plot_id, $image_filename) {
    if (empty($image_filename)) {
        return null;
    }

    // Sanitize values for directory names, matching insertData.php logic
    // ใช้ตัวแปรที่รับมาโดยตรง ไม่ต้องเข้าถึงด้วย $row["key"] แล้ว
    $safe_production_year = preg_replace('/[^a-zA-Z0-9_\-]/', '', $production_year);
    $safe_agency = preg_replace('/[^a-zA-Z0-9_\-]/', '', $agency);
    $safe_contract_number = preg_replace('/[^a-zA-Z0-9_\-]/', '', $contract_number);
    $safe_plot_id = preg_replace('/[^a-zA-Z0-9_\-]/', '', $plot_id);

    // Construct the full path based on the structure in insertData.php
    $base_upload_dir = 'images/';
    $specific_upload_dir = $base_upload_dir .
                            (!empty($safe_production_year) ? $safe_production_year . '/' : 'unknown_year/') .
                            (!empty($safe_agency) ? $safe_agency . '/' : 'unknown_agency/') .
                            (!empty($safe_contract_number) ? $safe_contract_number . '/' : 'unknown_contract/') .
                            (!empty($safe_plot_id) ? $safe_plot_id . '/' : 'unknown_plot_id/');

    return $specific_upload_dir . htmlspecialchars($image_filename);
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลดินและอ้อย ปี <?php echo htmlspecialchars($selected_year); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="style_db.css" rel="stylesheet">
    <link href="style_nav.css" rel="stylesheet">
</head>
<body>
    <?php
    // เรียกใช้ไฟล์ Navbar ที่สร้างไว้
    require_once 'nav.php'; // ตรวจสอบ Path ให้ถูกต้อง ถ้า nav.php อยู่ในโฟลเดอร์เดียวกัน
    ?>

    <div class="container-fluid">
        <h1 class="mb-4">ข้อมูลการเพาะปลูกอ้อย ปี <?php echo htmlspecialchars($selected_year); ?></h1>

        <?php
        // แสดงข้อความ Success/Error จากการ insert
        if (isset($_GET['status'])) {
            if ($_GET['status'] == 'success') {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i> บันทึกข้อมูลเรียบร้อยแล้ว!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
            } elseif ($_GET['status'] == 'error') {
                $error_message = $_GET['message'] ?? 'เกิดข้อผิดพลาดในการบันทึกข้อมูล';
                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-x-circle-fill me-2"></i> ' . htmlspecialchars($error_message) . '
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
            }
        }
        ?>

        <div class="row align-items-center mb-3">
            <div class="col-md-auto d-flex align-items-center mb-2 mb-md-0">
                <label for="limit-select" class="me-2 text-nowrap">แสดง</label>
                <select class="form-select form-select-sm" id="limit-select" onchange="this.form.submit()" style="width: auto;">
                    <?php foreach ($results_per_page_options as $option) : ?>
                        <option value="<?php echo $option; ?>" <?php echo ($results_per_page == $option) ? 'selected' : ''; ?>>
                            <?php echo $option; ?> แถว
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md">
                <form class="d-flex justify-content-end" method="GET" action="dashboard.php">
                    <input type="hidden" name="year" value="<?php echo htmlspecialchars($selected_year); ?>">
                    <input type="hidden" name="limit" value="<?php echo htmlspecialchars($results_per_page); ?>">
                    <div class="input-group" style="max-width: 300px;">
                        <input class="form-control" type="search" placeholder="ค้นหา..." aria-label="Search" name="search" value="<?php echo htmlspecialchars($search_term); ?>">
                        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                    <?php if (!empty($search_term)) : ?>
                        <a href="dashboard.php?year=<?php echo htmlspecialchars($selected_year); ?>&limit=<?php echo htmlspecialchars($results_per_page); ?>" class="btn btn-outline-secondary ms-2 text-nowrap"><i class="bi bi-x-circle"></i> ล้างค้นหา</a>
                    <?php endif; ?>
                </form>
            </div>
            
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th scope="col" class="text-center">ปี</th>
                        <th scope="col" class="text-center">หน่วยงาน</th>
                        <th scope="col" class="text-center">เลขสัญญา</th>
                        <th scope="col" class="text-center">โควต้า</th>
                        <th scope="col" class="text-center">ID แปลง</th>
                        <th scope="col" class="text-center">ไร่</th>
                        <th scope="col" class="text-center">ดิน</th>
                        <th scope="col" class="text-center">ภาพดิน</th>
                        <th scope="col" class="text-center">เตรียมดิน</th>
                        <th scope="col" class="text-center">ภาพเตรียมดิน</th>
                        <th scope="col" class="text-center">พันธุ์อ้อย</th>
                        <th scope="col" class="text-center">ภาพพันธุ์อ้อย</th>
                        <th scope="col" class="text-center">ปลูก</th>
                        <th scope="col" class="text-center">ภาพปลูก</th>
                        <th scope="col" class="text-center">ให้น้ำ</th>
                        <th scope="col" class="text-center">ภาพให้น้ำ</th>
                        <th scope="col" class="text-center">% การงอก</th>
                        <th scope="col" class="text-center">ภาพ % งอก</th>
                        <th scope="col" class="text-center">หมายเหตุ</th>
                        <th scope="col" class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        // ข้อมูลที่แยกคอลัมน์แล้ว (แสดงค่าตรงๆ ไม่ผ่าน decodeValue)
        echo "<td>" . htmlspecialchars($row["production_year"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["agency"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["contract_number"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["quota"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["plot_id"]) . "</td>";
        echo "<td>" . htmlspecialchars($row["rai_area"]) . " ไร่</td>";
        echo "<td class='text-center'>" . htmlspecialchars($row["soil_type"]) . "</td>"; // แสดงค่าตรงๆ

        // การแสดงภาพใน Modal สำหรับ soil_image
        echo "<td class='text-center'>";
        $soil_image_path = getImagePath(
            $row["production_year"],    // ส่งค่า production_year
            $row["agency"],             // ส่งค่า agency
            $row["contract_number"],    // ส่งค่า contract_number
            $row["plot_id"],            // ส่งค่า plot_id
            $row["soil_image"]          // ส่งชื่อไฟล์รูปภาพ
        );
        if (!empty($soil_image_path) && file_exists($soil_image_path)) {
            echo "<img src='" . htmlspecialchars($soil_image_path) . "' alt='Soil Image' class='image-thumb' data-bs-toggle='modal' data-bs-target='#imageModal' data-imgsrc='" . htmlspecialchars($soil_image_path) . "' style='cursor:pointer;'>";
        } else {
            echo ""; // เปลี่ยนเป็นค่าว่าง
        }
        echo "</td>";

        echo "<td class='text-center'>" . htmlspecialchars($row["soil_preparation_details"]) . "</td>"; // แสดงค่าตรงๆ
        echo "<td class='text-center'>";
        $soil_preparation_image_path = getImagePath(
            $row["production_year"],
            $row["agency"],
            $row["contract_number"],
            $row["plot_id"],
            $row["soil_preparation_image"]
        );
        if (!empty($soil_preparation_image_path) && file_exists($soil_preparation_image_path)) {
            echo "<img src='" . htmlspecialchars($soil_preparation_image_path) . "' alt='Preparation Image' class='image-thumb' data-bs-toggle='modal' data-bs-target='#imageModal' data-imgsrc='" . htmlspecialchars($soil_preparation_image_path) . "' style='cursor:pointer;'>";
        } else {
            echo ""; // เปลี่ยนเป็นค่าว่าง
        }
        echo "</td>";

        echo "<td class='text-center'>" . htmlspecialchars($row["cane_variety"]) . "</td>"; // แสดงค่าตรงๆ
        echo "<td class='text-center'>";
        $cane_variety_image_path = getImagePath(
            $row["production_year"],
            $row["agency"],
            $row["contract_number"],
            $row["plot_id"],
            $row["cane_variety_image"]
        );
        if (!empty($cane_variety_image_path) && file_exists($cane_variety_image_path)) {
            echo "<img src='" . htmlspecialchars($cane_variety_image_path) . "' alt='Cane Variety Image' class='image-thumb' data-bs-toggle='modal' data-bs-target='#imageModal' data-imgsrc='" . htmlspecialchars($cane_variety_image_path) . "' style='cursor:pointer;'>";
        } else {
            echo ""; // เปลี่ยนเป็นค่าว่าง
        }
        echo "</td>";

        echo "<td class='text-center'>" . htmlspecialchars($row["planting_details"]) . "</td>"; // แสดงค่าตรงๆ
        echo "<td class='text-center'>";
        $planting_image_path = getImagePath(
            $row["production_year"],
            $row["agency"],
            $row["contract_number"],
            $row["plot_id"],
            $row["planting_image"]
        );
        if (!empty($planting_image_path) && file_exists($planting_image_path)) {
            echo "<img src='" . htmlspecialchars($planting_image_path) . "' alt='Planting Image' class='image-thumb' data-bs-toggle='modal' data-bs-target='#imageModal' data-imgsrc='" . htmlspecialchars($planting_image_path) . "' style='cursor:pointer;'>";
        } else {
            echo ""; // เปลี่ยนเป็นค่าว่าง
        }
        echo "</td>";

        echo "<td class='text-center'>" . htmlspecialchars($row["watering_details"]) . "</td>"; // แสดงค่าตรงๆ
        echo "<td class='text-center'>";
        $watering_image_path = getImagePath(
            $row["production_year"],
            $row["agency"],
            $row["contract_number"],
            $row["plot_id"],
            $row["watering_image"]
        );
        if (!empty($watering_image_path) && file_exists($watering_image_path)) {
            echo "<img src='" . htmlspecialchars($watering_image_path) . "' alt='Watering Image' class='image-thumb' data-bs-toggle='modal' data-bs-target='#imageModal' data-imgsrc='" . htmlspecialchars($watering_image_path) . "' style='cursor:pointer;'>";
        } else {
            echo ""; // เปลี่ยนเป็นค่าว่าง
        }
        echo "</td>";

        echo "<td class='text-center'>" . htmlspecialchars($row["germination_percentage"]) . "%</td>";
        echo "<td class='text-center'>";
        $germination_image_path = getImagePath(
            $row["production_year"],
            $row["agency"],
            $row["contract_number"],
            $row["plot_id"],
            $row["germination_image"]
        );
        if (!empty($germination_image_path) && file_exists($germination_image_path)) {
            echo "<img src='" . htmlspecialchars($germination_image_path) . "' alt='Germination Image' class='image-thumb' data-bs-toggle='modal' data-bs-target='#imageModal' data-imgsrc='" . htmlspecialchars($germination_image_path) . "' style='cursor:pointer;'>";
        } else {
            echo ""; // เปลี่ยนเป็นค่าว่าง
        }
        echo "</td>";

        echo "<td>" . htmlspecialchars($row["notes"]) . "</td>";
        // ถ้าคุณมีคอลัมน์ "วันที่บันทึก" ด้วย ให้เพิ่มตรงนี้
        // echo "<td>" . htmlspecialchars($row["created_at"]) . "</td>";

        echo "<td class='text-center action-links'>
                <a href='edit_data.php?id=" . $row["id"] . "' class='btn btn-warning btn-sm me-1'>แก้ไข</a>
                <a href='delete_data.php?id=" . $row["id"] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"คุณแน่ใจหรือไม่ที่จะลบข้อมูลนี้?\")'>ลบ</a>
            </td>";
        echo "</tr>";
    }
} else {
    // ปรับ colspan ให้เท่ากับจำนวนคอลัมน์ใหม่ (22 คอลัมน์ตาม header ที่ผมเคยให้ไป)
    echo "<tr><td colspan='22' class='text-center no-data-message'>ไม่พบข้อมูลสำหรับปี " . htmlspecialchars($selected_year);
    if (!empty($search_term)) {
        echo " และคำค้นหา '" . htmlspecialchars($search_term) . "'";
    }
    echo "</td></tr>";
}
?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3">
            <div>
                <?php
                    $start_record = ($offset + 1);
                    $end_record = min($offset + $results_per_page, $total_records);
                ?>
                แสดง <?php echo $start_record; ?> ถึง <?php echo $end_record; ?> จาก <?php echo $total_records; ?> แถว
            </div>

            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-end mb-0">
                    <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="dashboard.php?year=<?php echo htmlspecialchars($selected_year); ?>&page=<?php echo $current_page - 1; ?>&limit=<?php echo htmlspecialchars($results_per_page); ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">ก่อนหน้า</a>
                    </li>
                    <?php
                    // Logic เพื่อแสดงเลขหน้าให้เหมาะสม (เหมือนในรูป image_ee6efc.png)
                    $num_links_around_current = 2; // จำนวนหน้าที่จะแสดงรอบๆ หน้าปัจจุบัน
                    $start_page = max(1, $current_page - $num_links_around_current);
                    $end_page = min($total_pages, $current_page + $num_links_around_current);

                    // แสดงหน้าแรกเสมอ ถ้าไม่อยู่ในช่วงที่แสดง
                    if ($start_page > 1) {
                        echo '<li class="page-item"><a class="page-link" href="dashboard.php?year=' . htmlspecialchars($selected_year) . '&page=1&limit=' . htmlspecialchars($results_per_page) . (!empty($search_term) ? '&search=' . urlencode($search_term) : '') . '">1</a></li>';
                        if ($start_page > 2) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                    }

                    for ($i = $start_page; $i <= $end_page; $i++) : ?>
                        <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                            <a class="page-link" href="dashboard.php?year=<?php echo htmlspecialchars($selected_year); ?>&page=<?php echo $i; ?>&limit=<?php echo htmlspecialchars($results_per_page); ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor;

                    // แสดงหน้าสุดท้ายเสมอ ถ้าไม่อยู่ในช่วงที่แสดง
                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link" href="dashboard.php?year=' . htmlspecialchars($selected_year) . '&page=' . $total_pages . '&limit=' . htmlspecialchars($results_per_page) . (!empty($search_term) ? '&search=' . urlencode($search_term) : '') . '">' . $total_pages . '</a></li>';
                    }
                    ?>
                    <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="dashboard.php?year=<?php echo htmlspecialchars($selected_year); ?>&page=<?php echo $current_page + 1; ?>&limit=<?php echo htmlspecialchars($results_per_page); ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">ถัดไป</a>
                    </li>
                </ul>
            </nav>
            <div class="col-md-auto text-md-end mt-2 mt-md-0">
                <a href="insertForm.php?year=<?php echo htmlspecialchars($selected_year); ?>" class="btn btn-success btn-add-data">
                    <i class="bi bi-plus-circle"></i> เพิ่มข้อมูลใหม่
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">ดูภาพขยาย</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img src="" id="modalImage" class="img-fluid" alt="ภาพขยาย">
      </div>
    </div>
  </div>
</div>

<script>
  // เมื่อกดที่ภาพ thumbnail ให้เอา src จาก data-imgsrc มาแสดงใน modal
  document.querySelectorAll('.image-thumb').forEach(img => {
    img.addEventListener('click', function() {
      const src = this.getAttribute('data-imgsrc');
      document.getElementById('modalImage').setAttribute('src', src);
    });
  });

  // เคลียร์รูปใน modal เมื่อ modal ถูกปิด
  const imageModal = document.getElementById('imageModal');
  imageModal.addEventListener('hidden.bs.modal', function () {
    document.getElementById('modalImage').setAttribute('src', '');
  });

  // เมื่อเลือกจำนวนแถวที่แสดง ให้ submit ฟอร์ม
  document.getElementById('limit-select').addEventListener('change', function() {
      // สร้าง URL ใหม่โดยคงค่า year และ search ไว้
      const currentUrl = new URL(window.location.href);
      currentUrl.searchParams.set('limit', this.value);
      currentUrl.searchParams.set('page', 1); // กลับไปหน้าแรกเสมอเมื่อเปลี่ยน limit
      window.location.href = currentUrl.toString();
  });
</script>

</body>
</html>
<?php
// ควรปิดการเชื่อมต่อฐานข้อมูลหลังจากใช้งานเสร็จ
if (isset($count_stmt) && $count_stmt instanceof mysqli_stmt) {
    $count_stmt->close();
}
if (isset($data_stmt) && $data_stmt instanceof mysqli_stmt) {
    $data_stmt->close();
}
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>