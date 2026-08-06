<?php
require("dbconnect.php");

if (!isset($_GET['plot_id']) || !isset($_GET['year_rai'])) {
  echo "<div class='text-center mt-5 text-danger'>ไม่พบข้อมูลที่ต้องการแก้ไข</div>";
  exit();
}

$plot_id = $_GET['plot_id'];
$year_rai = $_GET['year_rai'];
$sql = "SELECT * FROM image_water WHERE plot_id = ? AND year_rai = ?";
$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, "ss", $plot_id, $year_rai);mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if (mysqli_num_rows($result) == 0) {
  echo "<div class='text-center mt-5 text-danger'>ไม่พบข้อมูลที่ต้องการแก้ไข</div>";
  exit();
}
$data = mysqli_fetch_assoc($result);
$current_status = $data['join_status'] ?? 'notjoin';

// จัดรูปแบบวันที่ให้น้ำแต่ละครั้ง (ถ้ามีค่า)
function formatWaterDate($dateStr) {
  if (empty($dateStr) || $dateStr === '0000-00-00') return '-';
  $ts = strtotime($dateStr);
  return $ts ? date('d/m/Y', $ts) : '-';
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>แก้ไขข้อมูลการให้น้ำ</title>
  <link rel="icon" href="icon/icon_login.png" type="image/x-icon">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@500;600;700&family=Sarabun:wght@400;500;600&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    :root {
      --primary: #2563EB;
      --primary-dark: #1D4ED8;
      --primary-soft: #EAF1FF;
      --bg: #F4F7FC;
      --card: #FFFFFF;
      --border: #E2E8F0;
      --text: #1E293B;
      --text-muted: #64748B;
      --amber: #D97706;
      --amber-soft: #FEF3E2;
      --danger: #DC2626;
      --danger-soft: #FEF2F2;
      --display: 'Kanit', sans-serif;
      --sans: 'Sarabun', sans-serif;
    }

    * { box-sizing: border-box; }

    body {
      background: var(--bg);
      font-family: var(--sans);
      color: var(--text);
      min-height: 100vh;
      margin: 0;
      padding: 32px 16px 64px;
    }

    .page-wrap { max-width: 880px; margin: 0 auto; }

    /* ===== Header ===== */
    .page-header {
      background: var(--card);
      border: 1px solid var(--border);
      border-left: 5px solid var(--primary);
      border-radius: 16px;
      padding: 22px 26px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
      margin-bottom: 18px;
      box-shadow: 0 4px 14px rgba(37, 99, 235, 0.06);
    }

    .page-header-left { display: flex; align-items: center; gap: 14px; }

    .icon-badge {
      width: 46px;
      height: 46px;
      border-radius: 12px;
      background: var(--primary);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
      flex-shrink: 0;
    }

    .page-header h1 {
      font-family: var(--display);
      font-size: 1.35rem;
      font-weight: 600;
      margin: 0;
      color: var(--text);
    }

    .page-header p {
      margin: 2px 0 0;
      color: var(--text-muted);
      font-size: 0.85rem;
    }

    .plot-pill {
      background: var(--primary-soft);
      color: var(--primary-dark);
      font-weight: 600;
      font-size: 0.85rem;
      padding: 7px 16px;
      border-radius: 999px;
      white-space: nowrap;
    }

    @media (max-width: 560px) {
      .page-header { flex-direction: column; align-items: flex-start; }
    }

    /* ===== Cards ===== */
    .card-section {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 26px;
      margin-bottom: 16px;
      box-shadow: 0 2px 8px rgba(30, 41, 59, 0.04);
    }

    @media (max-width: 576px) {
      .card-section { padding: 18px; }
    }

    .section-title {
      font-family: var(--display);
      font-weight: 600;
      font-size: 1.02rem;
      color: var(--text);
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 20px;
    }

    .section-title i { color: var(--primary); }

    .subsection-title {
      font-family: var(--display);
      font-weight: 600;
      font-size: 0.88rem;
      color: var(--text-muted);
      margin: 22px 0 12px;
      padding-top: 18px;
      border-top: 1px dashed var(--border);
    }

    .spec-grid:first-of-type .subsection-title { border-top: none; padding-top: 0; margin-top: 0; }

    /* ===== Spec grid (read-only info) ===== */
    .spec-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 18px 20px;
    }

    @media (max-width: 576px) {
      .spec-grid { grid-template-columns: repeat(2, 1fr); }
    }

    .spec-item .spec-label {
      display: block;
      font-size: 0.74rem;
      color: var(--text-muted);
      margin-bottom: 4px;
    }

    .spec-item .spec-value {
      font-weight: 600;
      font-size: 0.98rem;
      color: var(--text);
    }

    .spec-item.amber .spec-value { color: var(--amber); }
    .spec-item .spec-value.empty { color: var(--text-muted); font-weight: 400; }

    /* ===== Segmented toggle ===== */
    .segmented {
      display: inline-flex;
      background: var(--primary-soft);
      border-radius: 999px;
      padding: 4px;
      gap: 4px;
    }

    .segmented input { display: none; }

    .segmented label {
      padding: 9px 24px;
      border-radius: 999px;
      font-size: 0.9rem;
      font-weight: 600;
      color: var(--primary-dark);
      cursor: pointer;
      transition: all 0.2s ease;
      margin: 0;
      user-select: none;
    }

    .segmented input:checked + label {
      background: var(--primary);
      color: #fff;
      box-shadow: 0 3px 8px rgba(37, 99, 235, 0.3);
    }

    /* ===== Photo tiles ===== */
    .photo-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 16px;
    }

    @media (max-width: 700px) {
      .photo-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 420px) {
      .photo-grid { grid-template-columns: 1fr; }
    }

    .photo-tile {
      background: transparent;
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 12px;
    }

    .photo-tile-label {
      display: flex;
      align-items: center;
      gap: 8px;
      font-weight: 600;
      font-size: 0.86rem;
      color: var(--text);
      margin-bottom: 10px;
    }

    .photo-tile-label .num {
      width: 22px;
      height: 22px;
      border-radius: 50%;
      background: var(--primary);
      color: #fff;
      font-size: 0.7rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .photo-tile-label .num.event { background: var(--amber); }

    .dropzone {
      position: relative;
      display: block;
      cursor: pointer;
      border-radius: 10px;
      overflow: hidden;
      border: 1.5px dashed #C7D2FE;
      background: transparent !important;
      transition: border-color 0.2s ease;
    }

    .dropzone:hover { border-color: var(--primary); }

    .dropzone input[type="file"] {
      position: absolute;
      inset: 0;
      opacity: 0;
      cursor: pointer;
    }

    .media-box {
      width: 100%;
      height: 150px;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      background: transparent !important;
    }

    .media-box img { width: 100%; height: 100%; object-fit: cover; }

    .placeholder {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 6px;
      color: var(--text-muted);
      background: transparent !important;
    }

    .placeholder svg { color: var(--primary); }
    .placeholder span { font-size: 0.76rem; }

    .replace-hint {
      position: absolute;
      bottom: 0; left: 0; right: 0;
      background: rgba(37, 99, 235, 0.85);
      color: #fff;
      font-size: 0.72rem;
      text-align: center;
      padding: 4px 0;
      opacity: 0;
      transition: opacity 0.2s ease;
    }

    .dropzone:hover .replace-hint { opacity: 1; }

    .btn-delete-img {
      width: 100%;
      margin-top: 10px;
      border-radius: 8px;
      padding: 7px;
      font-size: 0.82rem;
      font-weight: 600;
      color: var(--danger);
      background: var(--danger-soft);
      border: none;
    }

    .btn-delete-img:hover { background: var(--danger); color: #fff; }

    /* ===== Actions ===== */
    .actions-bar {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 12px;
      margin-top: 24px;
    }

    .btn-submit {
      background: var(--primary);
      border: none;
      padding: 13px 40px;
      border-radius: 999px;
      font-weight: 600;
      color: #fff;
      font-size: 0.96rem;
      transition: all 0.2s ease;
    }

    .btn-submit:hover {
      background: var(--primary-dark);
      transform: translateY(-1px);
      box-shadow: 0 10px 20px rgba(37, 99, 235, 0.25);
    }

    .btn-back {
      border-radius: 999px;
      padding: 9px 20px;
      font-weight: 500;
      font-size: 0.86rem;
      color: var(--text-muted);
      border: 1px solid var(--border);
      background: transparent;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .btn-back:hover { background: var(--primary-soft); color: var(--primary-dark); }
  </style>
</head>
<body>

<div class="page-wrap">

  <div class="page-header">
    <div class="page-header-left">
      <div class="icon-badge"><i class='bi bi-droplet-fill'></i></div>
      <div>
        <h1>แก้ไขข้อมูลการให้น้ำ</h1>
        <p>แก้ไขสถานะและรูปภาพประกอบของแปลง</p>
      </div>
    </div>
    <div class="plot-pill">แปลง #<?= htmlspecialchars($data['plot_id']) ?></div>
  </div>

  <form action="user_water_updatedata.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="plot_id" value="<?= htmlspecialchars($data['plot_id']) ?>">

    <div class="card-section">
      <div class="section-title"><i class="bi bi-info-circle"></i> ข้อมูลเบื้องต้น</div>
      <div class="spec-grid">
        <div class="spec-item">
          <span class="spec-label">ปีการผลิต</span>
          <span class="spec-value"><?= htmlspecialchars($data['year_rai']) ?></span>
        </div>
        <div class="spec-item">
          <span class="spec-label">รหัส นสส.</span>
          <span class="spec-value"><?= htmlspecialchars($data['emp_id']) ?></span>
        </div>
        <div class="spec-item">
          <span class="spec-label">เลขที่สัญญา</span>
          <span class="spec-value"><?= htmlspecialchars($data['contract_number']) ?></span>
        </div>
        <div class="spec-item amber">
          <span class="spec-label">โควต้า</span>
          <span class="spec-value"><?= htmlspecialchars($data['quota']) ?></span>
        </div>
        <div class="spec-item">
          <span class="spec-label">จำนวนไร่</span>
          <span class="spec-value"><?= htmlspecialchars($data['area_rai']) ?> ไร่</span>
        </div>
        <div class="spec-item">
          <span class="spec-label">ชนิดอ้อย</span>
          <span class="spec-value"><?= htmlspecialchars($data['suga_type']) ?></span>
        </div>
      </div>

      <div class="subsection-title"><i class="bi bi-person-vcard"></i> ข้อมูลเจ้าของแปลง / ที่อยู่</div>
      <div class="spec-grid">
        <div class="spec-item">
          <span class="spec-label">เลขบัตรประชาชน</span>
          <span class="spec-value<?= empty($data['citizen_id']) ? ' empty' : '' ?>"><?= !empty($data['citizen_id']) ? htmlspecialchars($data['citizen_id']) : '-' ?></span>
        </div>
        <div class="spec-item">
          <span class="spec-label">บ้านเลขที่</span>
          <span class="spec-value<?= empty($data['house_no']) ? ' empty' : '' ?>"><?= !empty($data['house_no']) ? htmlspecialchars($data['house_no']) : '-' ?></span>
        </div>
        <div class="spec-item">
          <span class="spec-label">ตำบล</span>
          <span class="spec-value<?= empty($data['sub_district']) ? ' empty' : '' ?>"><?= !empty($data['sub_district']) ? htmlspecialchars($data['sub_district']) : '-' ?></span>
        </div>
        <div class="spec-item">
          <span class="spec-label">อำเภอ</span>
          <span class="spec-value<?= empty($data['district']) ? ' empty' : '' ?>"><?= !empty($data['district']) ? htmlspecialchars($data['district']) : '-' ?></span>
        </div>
        <div class="spec-item">
          <span class="spec-label">จังหวัด</span>
          <span class="spec-value<?= empty($data['province']) ? ' empty' : '' ?>"><?= !empty($data['province']) ? htmlspecialchars($data['province']) : '-' ?></span>
        </div>
        <div class="spec-item">
          <span class="spec-label">แหล่งน้ำ</span>
          <span class="spec-value<?= empty($data['water_source']) ? ' empty' : '' ?>"><?= !empty($data['water_source']) ? htmlspecialchars($data['water_source']) : '-' ?></span>
        </div>
      </div>

      <div class="subsection-title"><i class="bi bi-clock-history"></i> วิธีและวันที่ให้น้ำ</div>
      <div class="spec-grid">
        <div class="spec-item">
          <span class="spec-label">วิธีการให้น้ำ ครั้งที่ 1</span>
          <span class="spec-value<?= empty($data['water_method1']) ? ' empty' : '' ?>"><?= !empty($data['water_method1']) ? htmlspecialchars($data['water_method1']) : '-' ?></span>
        </div>
        <div class="spec-item">
          <span class="spec-label">วันที่ให้น้ำ ครั้งที่ 1</span>
          <span class="spec-value<?= empty($data['water_date1']) ? ' empty' : '' ?>"><?= formatWaterDate($data['water_date1']) ?></span>
        </div>
        <div class="spec-item"></div>
        <div class="spec-item">
          <span class="spec-label">วิธีการให้น้ำ ครั้งที่ 2</span>
          <span class="spec-value<?= empty($data['water_method2']) ? ' empty' : '' ?>"><?= !empty($data['water_method2']) ? htmlspecialchars($data['water_method2']) : '-' ?></span>
        </div>
        <div class="spec-item">
          <span class="spec-label">วันที่ให้น้ำ ครั้งที่ 2</span>
          <span class="spec-value<?= empty($data['water_date2']) ? ' empty' : '' ?>"><?= formatWaterDate($data['water_date2']) ?></span>
        </div>
        <div class="spec-item"></div>
        <div class="spec-item">
          <span class="spec-label">วิธีการให้น้ำ ครั้งที่ 3</span>
          <span class="spec-value<?= empty($data['water_method3']) ? ' empty' : '' ?>"><?= !empty($data['water_method3']) ? htmlspecialchars($data['water_method3']) : '-' ?></span>
        </div>
        <div class="spec-item">
          <span class="spec-label">วันที่ให้น้ำ ครั้งที่ 3</span>
          <span class="spec-value<?= empty($data['water_date3']) ? ' empty' : '' ?>"><?= formatWaterDate($data['water_date3']) ?></span>
        </div>
      </div>

      <input type="hidden" name="year_rai" value="<?= htmlspecialchars($data['year_rai']) ?>">
      <input type="hidden" name="emp_id" value="<?= htmlspecialchars($data['emp_id']) ?>">
      <input type="hidden" name="contract_number" value="<?= htmlspecialchars($data['contract_number']) ?>">
      <input type="hidden" name="quota" value="<?= htmlspecialchars($data['quota']) ?>">
      <input type="hidden" name="area_rai" value="<?= htmlspecialchars($data['area_rai']) ?>">
      <input type="hidden" name="suga_type" value="<?= htmlspecialchars($data['suga_type']) ?>">
    </div>

    <div class="card-section">
      <div class="section-title"><i class="bi bi-check2-square"></i> สถานะการเข้าร่วมโครงการ</div>
      <div class="segmented">
        <input type="radio" name="join_status" id="radioJoin" value="join" <?= $current_status === 'join' ? 'checked' : '' ?>>
        <label for="radioJoin">เข้าร่วม</label>

        <input type="radio" name="join_status" id="radioNotJoin" value="notjoin" <?= $current_status === 'notjoin' ? 'checked' : '' ?>>
        <label for="radioNotJoin">ไม่เข้าร่วม</label>
      </div>
    </div>

    <div class="card-section">
      <div class="section-title"><i class="bi bi-images"></i> รูปภาพแปลงอ้อย</div>

      <div class="photo-grid">
        <?php
        $images = [
          "water_image1" => ["label" => "การให้น้ำ ครั้งที่ 1", "num" => "1", "event" => false],
          "water_image2" => ["label" => "การให้น้ำ ครั้งที่ 2", "num" => "2", "event" => false],
          "water_image3" => ["label" => "การให้น้ำ ครั้งที่ 3", "num" => "3", "event" => false],
          "flood_image"  => ["label" => "น้ำท่วม", "num" => "<i class='bi bi-water'></i>", "event" => true],
          "drought_image"=> ["label" => "ภัยแล้ง", "num" => "<i class='bi bi-brightness-high'></i>", "event" => true],
          "other_image"  => ["label" => "อื่นๆ", "num" => "<i class='bi bi-paperclip'></i>", "event" => true],
        ];

        foreach ($images as $name => $meta) {
          $img_path = $data[$name];
          $has_img = (!empty($img_path) && file_exists($img_path));
          $num_class = $meta['event'] ? 'num event' : 'num';
          $img_path_safe = htmlspecialchars($img_path ?? '');
          $plot_id_js = htmlspecialchars($data['plot_id'], ENT_QUOTES);
          $year_rai_js = htmlspecialchars($data['year_rai'], ENT_QUOTES);
          $name_js = htmlspecialchars($name, ENT_QUOTES);

          echo '<div class="photo-tile">';
          echo "  <div class=\"photo-tile-label\"><span class=\"$num_class\">{$meta['num']}</span> {$meta['label']}</div>";
          echo '  <label class="dropzone">';
          echo "    <input type=\"file\" name=\"$name\" accept=\"image/*\" onchange=\"previewImage(this)\">";
          echo '    <div class="media-box">';
          if ($has_img) {
            echo "      <img src=\"$img_path_safe\">";
          } else {
            echo '      <div class="placeholder"><svg width="28" height="28" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M4.406 3.342A5.53 5.53 0 0 1 8 2c2.69 0 4.923 2 5.166 4.579C14.758 6.804 16 8.137 16 9.773 16 11.569 14.502 13 12.687 13H3.781C1.708 13 0 11.366 0 9.318c0-1.763 1.266-3.223 2.942-3.593.143-.863.698-1.723 1.464-2.383Zm.653.757c-.757.653-1.153 1.44-1.153 2.056v.448l-.445.049C2.064 6.805 1 7.952 1 9.318 1 10.785 2.23 12 3.781 12h8.906C13.98 12 15 10.988 15 9.773c0-1.216-1.02-2.228-2.313-2.228h-.5v-.5C12.188 4.825 10.328 3 8 3a4.53 4.53 0 0 0-2.941 1.099Z"/><path d="M7.646 5.146a.5.5 0 0 1 .708 0l2 2a.5.5 0 0 1-.708.708L8.5 6.707V10.5a.5.5 0 0 1-1 0V6.707L6.354 7.854a.5.5 0 1 1-.708-.708l2-2Z"/></svg><span>แตะเพื่ออัปโหลด</span></div>';
          }
          echo '    </div>';
          echo '    <div class="replace-hint">แตะเพื่อเปลี่ยนรูป</div>';
          echo '  </label>';
          if ($has_img) {
            echo "  <a href=\"javascript:void(0);\" class=\"btn btn-delete-img\" onclick=\"confirmDelete('{$name_js}', '{$plot_id_js}', '{$year_rai_js}')\"><i class=\"bi bi-trash\"></i> ลบรูปภาพนี้</a>";
          }
          echo '</div>';
        }
        ?>
      </div>
    </div>

    <div class="actions-bar">
      <button type="submit" class="btn-submit">
        <i class="bi bi-save2"></i> บันทึกการแก้ไขข้อมูล
      </button>
      <a href="user_page.php" class="btn-back">
        <i class="bi bi-arrow-left-circle"></i> กลับหน้าแรก
      </a>
    </div>
  </form>

</div>

<script>
  function previewImage(input) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    const mediaBox = input.closest('.dropzone').querySelector('.media-box');

    reader.onload = function (e) {
      mediaBox.innerHTML = `<img src="${e.target.result}">`;
    };
    reader.readAsDataURL(file);
  }

  function confirmDelete(imageName, plotId, yearRai) {
    Swal.fire({
      title: 'แน่ใจว่าจะลบรูปนี้ ?',
      text: "รูปภาพนี้จะถูกลบออกถาวรเลยนะ",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#DC2626',
      cancelButtonColor: '#2563EB',
      confirmButtonText: 'ลบเลย',
      cancelButtonText: 'ยกเลิก',
      heightAuto: false,
      customClass: { popup: 'rounded-4' }
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = `user_waterdelete.php?delete_image=${imageName}&plot_id=${plotId}&year_rai=${yearRai}`;
      }
    });
  }
</script>

</body>
</html>