<?php
require_once 'db_connect.php';

// ดึงข้อมูลทั้งหมดของปีที่เลือก
$selected_year = $_GET['year'] ?? '';

if (!$selected_year) {
    echo "กรุณาเลือกปีการผลิตก่อน <a href='index.php'>กลับไปหน้าเลือกปี</a>";
    exit;
}

$sql = "SELECT * FROM soil_data WHERE production_year = ? ORDER BY id DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $selected_year);
$stmt->execute();
$result = $stmt->get_result();

function getDescription($type, $value) {
    $options = [
        'soil_type' => [1 => 'ดีมาก', 2 => 'ดี', 3 => 'พอใช้', 0 => '-', null => '-'],
        'soil_preparation_details' => [1 => 'ดีมาก', 2 => 'ดี', 3 => 'พอใช้', 0 => '-', null => '-'],
        'cane_variety' => [1 => 'ดีมาก', 2 => 'ดี', 3 => 'พอใช้', 0 => '-', null => '-'],
        'planting_details' => [1 => 'มาตรฐาน', 2 => 'ไม่ได้มาตรฐาน', 0 => '-', null => '-'],
        'watering_details' => [1 => 'มี', 2 => 'ไม่มี', 0 => '-', null => '-'],
    ];
    return $options[$type][$value] ?? '-';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8" />
    <title>Dashboard ปีการผลิต <?php echo htmlspecialchars($selected_year); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link href="style_db.css" rel="stylesheet" />
    <style>
        table.dataTable td, table.dataTable th {
            vertical-align: middle;
            text-align: center;
        }
        .img-thumbnail {
            max-width: 80px;
            cursor: pointer;
        }
    </style>
</head>
<body>
<?php include 'nav.php'; ?>

<div class="container mt-4">
    <h3>ข้อมูลปีการผลิต: <?php echo htmlspecialchars($selected_year); ?></h3>

    <!-- ห่อ table ด้วย div.table-responsive -->
    <div class="table-responsive">
        <table id="dataTable" class="table table-striped table-bordered table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ปีผลิต</th>
                    <th>หน่วยงาน</th>
                    <th>เลขสัญญา</th>
                    <th>โควต้า</th>
                    <th>ID แปลง</th>
                    <th>พื้นที่ (ไร่)</th>
                    <th>ชนิดดิน</th>
                    <th>รูปดิน</th>
                    <th>เตรียมดิน</th>
                    <th>รูปเตรียมดิน</th>
                    <th>พันธุ์อ้อย</th>
                    <th>รูปพันธุ์อ้อย</th>
                    <th>รายละเอียดปลูก</th>
                    <th>รูปปลูก</th>
                    <th>การให้น้ำ</th>
                    <th>รูปให้น้ำ</th>
                    <th>เปอร์เซ็นต์งอก</th>
                    <th>รูปเปอร์เซ็นต์งอก</th>
                    <th>หมายเหตุ</th>
                    <th>วันที่บันทึก</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['production_year']); ?></td>
                        <td><?php echo htmlspecialchars($row['agency']); ?></td>
                        <td><?php echo htmlspecialchars($row['contract_number']); ?></td>
                        <td><?php echo htmlspecialchars($row['quota']); ?></td>
                        <td><?php echo htmlspecialchars($row['plot_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['rai_area']); ?></td>
                        <td><?php echo getDescription('soil_type', $row['soil_type']); ?></td>
                        <td>
                            <?php if ($row['soil_image']): ?>
                                <img src="images/<?php echo htmlspecialchars($row['soil_image']); ?>" alt="soil" class="img-thumbnail zoomable-image" data-bs-toggle="modal" data-bs-target="#imageModal" data-imgsrc="images/<?php echo htmlspecialchars($row['soil_image']); ?>">
                            <?php else: echo '-'; endif; ?>
                        </td>
                        <td><?php echo getDescription('soil_preparation_details', $row['soil_preparation_details']); ?></td>
                        <td>
                            <?php if ($row['soil_preparation_image']): ?>
                                <img src="images/<?php echo htmlspecialchars($row['soil_preparation_image']); ?>" alt="soil prep" class="img-thumbnail zoomable-image" data-bs-toggle="modal" data-bs-target="#imageModal" data-imgsrc="images/<?php echo htmlspecialchars($row['soil_preparation_image']); ?>">
                            <?php else: echo '-'; endif; ?>
                        </td>
                        <td><?php echo getDescription('cane_variety', $row['cane_variety']); ?></td>
                        <td>
                            <?php if ($row['cane_variety_image']): ?>
                                <img src="images/<?php echo htmlspecialchars($row['cane_variety_image']); ?>" alt="cane" class="img-thumbnail zoomable-image" data-bs-toggle="modal" data-bs-target="#imageModal" data-imgsrc="images/<?php echo htmlspecialchars($row['cane_variety_image']); ?>">
                            <?php else: echo '-'; endif; ?>
                        </td>
                        <td><?php echo getDescription('planting_details', $row['planting_details']); ?></td>
                        <td>
                            <?php if ($row['planting_image']): ?>
                                <img src="images/<?php echo htmlspecialchars($row['planting_image']); ?>" alt="planting" class="img-thumbnail zoomable-image" data-bs-toggle="modal" data-bs-target="#imageModal" data-imgsrc="images/<?php echo htmlspecialchars($row['planting_image']); ?>">
                            <?php else: echo '-'; endif; ?>
                        </td>
                        <td><?php echo getDescription('watering_details', $row['watering_details']); ?></td>
                        <td>
                            <?php if ($row['watering_image']): ?>
                                <img src="images/<?php echo htmlspecialchars($row['watering_image']); ?>" alt="watering" class="img-thumbnail zoomable-image" data-bs-toggle="modal" data-bs-target="#imageModal" data-imgsrc="images/<?php echo htmlspecialchars($row['watering_image']); ?>">
                            <?php else: echo '-'; endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['germination_percentage']); ?></td>
                        <td>
                            <?php if ($row['germination_image']): ?>
                                <img src="images/<?php echo htmlspecialchars($row['germination_image']); ?>" alt="germination" class="img-thumbnail zoomable-image" data-bs-toggle="modal" data-bs-target="#imageModal" data-imgsrc="images/<?php echo htmlspecialchars($row['germination_image']); ?>">
                            <?php else: echo '-'; endif; ?>
                        </td>
                        <td><?php echo nl2br(htmlspecialchars($row['notes'])); ?></td>
                        <td><?php echo $row['created_at']; ?></td>
                        <td>
                            <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning mb-1">แก้ไข</a>
                            <button class="btn btn-sm btn-danger btn-delete" data-id="<?php echo $row['id']; ?>">ลบ</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div> <!-- /.table-responsive -->
</div>

<!-- Modal สำหรับแสดงรูปภาพขยาย -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="imageModalLabel">ขยายภาพ</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
      </div>
      <div class="modal-body text-center">
        <img src="" id="modalImage" alt="ขยายภาพ" style="max-width: 100%; max-height: 80vh;">
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#dataTable').DataTable({
        scrollX: true,
        lengthMenu: [ [20, 25, 50, 100], [20, 25, 50, 100] ],
        language: {
            lengthMenu: "แสดง _MENU_ แถว",
            zeroRecords: "ไม่พบข้อมูล",
            info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ แถว",
            infoEmpty: "ไม่มีข้อมูล",
            infoFiltered: "(กรองจากทั้งหมด _MAX_ แถว)",
            search: "ค้นหา:",
            paginate: {
                first: "หน้าแรก",
                last: "หน้าสุดท้าย",
                next: "ถัดไป",
                previous: "ก่อนหน้า"
            },
        },
        order: [[0, "desc"]]
    });

    // เปิด modal แสดงภาพแบบขยาย
    $('.zoomable-image').on('click', function() {
        const src = $(this).data('imgsrc');
        $('#modalImage').attr('src', src);
    });

    // ปุ่มลบข้อมูล
    $('.btn-delete').on('click', function() {
        const id = $(this).data('id');
        if(confirm('คุณแน่ใจหรือว่าต้องการลบข้อมูลนี้?')) {
            $.ajax({
                url: 'deleteData.php',
                type: 'POST',
                data: { id: id },
                success: function(response) {
                    alert('ลบข้อมูลสำเร็จ');
                    location.reload();
                },
                error: function() {
                    alert('เกิดข้อผิดพลาดในการลบข้อมูล');
                }
            });
        }
    });
});
</script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
