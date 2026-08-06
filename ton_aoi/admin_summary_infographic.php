<?php
// admin_summary_infographic.php (ปรับปรุงเน้น Pie Chart และสรุปหน่วยงาน)
require_once 'db_connect.php'; 
require_once 'nav.php'; 

// 1. ดึงข้อมูลตัวเลือก (ปี, หน่วย, ชนิดอ้อย) สำหรับ Filter (เหมือนเดิม)
$years = [];
$agencies = [];
$suga_types = [];

// ดึงปี
$sql_years = "SELECT DISTINCT production_year FROM cane_plot_data ORDER BY production_year DESC";
$result_years = $conn->query($sql_years);
while ($row = $result_years->fetch_assoc()) {
    $years[] = htmlspecialchars($row['production_year']);
}

// ดึงหน่วยส่งเสริม
$sql_agencies = "SELECT DISTINCT agency FROM cane_plot_data ORDER BY agency ASC";
$result_agencies = $conn->query($sql_agencies);
while ($row = $result_agencies->fetch_assoc()) {
    $agencies[] = htmlspecialchars($row['agency']);
}

// ดึงชนิดอ้อย
$sql_suga_types = "SELECT DISTINCT suga_type FROM cane_plot_data WHERE suga_type IS NOT NULL AND suga_type != '' ORDER BY suga_type ASC";
$result_suga_types = $conn->query($sql_suga_types);
while ($row = $result_suga_types->fetch_assoc()) {
    $suga_types[] = htmlspecialchars($row['suga_type']);
}

// กำหนดค่าเริ่มต้นสำหรับ Filter
$default_year = $years[0] ?? '';
$default_agency = $selected_agency ?? ''; 
$default_suga_type = ''; 

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สรุป Infographic ข้อมูลรูปภาพ (Admin)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <style>
        body { 
            font-family: 'Kanit', sans-serif; 
            background-color: #f0f2f5;
        }
        .container-main {
            padding-top: 20px;
        }
        .chart-card {
            background-color: #ffffff;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            min-height: 400px;
            display: flex;
            flex-direction: column;
        }
        .filter-section {
            background-color: #ffffff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10;
            border-radius: 15px;
        }
        .summary-box {
            background-color: #e6f3f0;
            border-left: 5px solid #00796b;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
    </style>
</head>
<body>

<div class="container container-main">
    <h2 class="text-center mb-4 text-success"><i class="fas fa-chart-pie"></i> สรุปสถานะรูปภาพแปลงอ้อย (สำหรับ Admin)</h2>
    
    <div class="filter-section row g-3">
        <div class="col-md-4 col-sm-6">
            <label for="filter_year" class="form-label">ปีการผลิต</label>
            <select id="filter_year" class="form-select">
                <?php foreach ($years as $year): ?>
                    <option value="<?php echo $year; ?>" <?php echo ($year == $default_year) ? 'selected' : ''; ?>>
                        <?php echo $year; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 col-sm-6">
            <label for="filter_agency" class="form-label">หน่วยส่งเสริม (ตัวกรองหลัก)</label>
            <select id="filter_agency" class="form-select">
                <option value="">-- ทั้งหมด (ใช้สำหรับตารางสรุปหน่วยงาน) --</option>
                <?php foreach ($agencies as $agency): ?>
                    <option value="<?php echo $agency; ?>" <?php echo ($agency == $default_agency) ? 'selected' : ''; ?>>
                        <?php echo $agency; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 col-sm-12">
            <label for="filter_suga_type" class="form-label">ชนิดอ้อย</label>
            <select id="filter_suga_type" class="form-select">
                <option value="">-- ทั้งหมด --</option>
                <?php foreach ($suga_types as $type): ?>
                    <option value="<?php echo $type; ?>" <?php echo ($type == $default_suga_type) ? 'selected' : ''; ?>>
                        <?php echo $type; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    
    <div id="summary-result-container" class="row g-4 position-relative">
        <div class="text-center p-5 col-12" id="initial-loading">
            <div class="spinner-border text-success" role="status"><span class="visually-hidden">Loading...</span></div>
            <p class="mt-2 text-muted">กำลังโหลดข้อมูลสรุป...</p>
        </div>
        </div>

</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script> 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    let pieChart; // ตัวแปรสำหรับเก็บ instance ของ Chart.js

    $(document).ready(function() {
        
        // ฟังก์ชันหลักในการดึงข้อมูลสถิติ
        function loadInfographicData() {
            var year = $('#filter_year').val();
            var agency = $('#filter_agency').val();
            var sugaType = $('#filter_suga_type').val();

            var container = $('#summary-result-container');
            
            // แสดง Spinner
            container.html(`
                <div class="loading-overlay">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `);
            
            $.ajax({
                url: 'admin_fetch_summary.php', // ใช้ไฟล์เดิมแต่แก้ไขตรรกะในนั้น
                type: 'GET',
                dataType: 'json',
                data: {
                    year: year,
                    agency: agency, // จะถูกใช้เมื่อ filter_agency ไม่ว่างเปล่า
                    suga_type: sugaType,
                    mode: 'agency_summary' // เพิ่ม Mode เพื่อบอกว่าต้องการสรุปแบบหน่วยงาน
                },
                success: function(response) {
                    if (response.success) {
                        displaySummary(response.data);
                    } else {
                        container.html('<div class="col-12"><div class="alert alert-danger text-center">เกิดข้อผิดพลาดในการดึงข้อมูล: ' + response.message + '</div></div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", status, error, xhr.responseText);
                    container.html('<div class="col-12"><div class="alert alert-danger text-center">ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์เพื่อดึงข้อมูลสถิติได้</div></div>');
                }
            });
        }

        // ฟังก์ชันแสดงผลสรุป (Pie Chart และตาราง)
        function displaySummary(data) {
            var container = $('#summary-result-container');
            container.empty(); 

            // 1. ส่วน Pie Chart (แสดงสัดส่วนแปลงที่มีรูป/ไม่มีรูป รวมทั้งหมด)
            var totalWithImage = data.total_summary.with_image;
            var totalNoImage = data.total_summary.total - totalWithImage;

            var chartHtml = `
                <div class="col-lg-6 col-md-12">
                    <div class="chart-card">
                        <h4 class="text-center mb-3 text-success"><i class="fas fa-chart-pie"></i> สัดส่วนภาพรวมแปลงทั้งหมด</h4>
                        <div style="max-height: 350px;">
                            <canvas id="plotsPieChart"></canvas>
                        </div>
                        <div class="summary-box mt-3">
                            <p class="mb-0">รวมแปลงทั้งหมด: **${data.total_summary.total}** แปลง</p>
                            <p class="mb-0 text-success">มีรูปภาพ: **${totalWithImage}** แปลง</p>
                            <p class="mb-0 text-danger">ไม่มีรูปภาพ: **${totalNoImage}** แปลง</p>
                        </div>
                    </div>
                </div>
            `;
            
            // 2. ส่วนตารางสรุปหน่วยงาน
            var tableHtml = `
                <div class="col-lg-6 col-md-12">
                    <div class="chart-card">
                        <h4 class="text-center mb-3 text-success"><i class="fas fa-table"></i> สรุปสถานะรูปภาพแยกตามหน่วยส่งเสริม</h4>
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-sm table-striped table-hover">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th>หน่วย</th>
                                        <th class="text-center">รวมแปลง</th>
                                        <th class="text-center text-success">มีรูปภาพ</th>
                                        <th class="text-center text-danger">ไม่มีรูปภาพ</th>
                                        <th class="text-center">% มีรูป</th>
                                    </tr>
                                </thead>
                                <tbody>
            `;
            
            // เรียงลำดับจาก % มีรูปภาพน้อยที่สุดไปมากที่สุด
            const sortedAgencies = data.agency_summary.sort((a, b) => a.percent - b.percent);

            sortedAgencies.forEach(agency => {
                const percent = agency.percent.toFixed(1);
                const percentColor = percent < 50 ? 'text-danger fw-bold' : 'text-success';
                
                tableHtml += `
                    <tr>
                        <td>${agency.agency}</td>
                        <td class="text-center">${agency.total}</td>
                        <td class="text-center">${agency.with_image}</td>
                        <td class="text-center">${agency.missing_image}</td>
                        <td class="text-center ${percentColor}">${percent}%</td>
                    </tr>
                `;
            });

            tableHtml += `
                                </tbody>
                            </table>
                        </div>
                        <div class="summary-box mt-auto">
                            <p class="mb-0">หน่วยงานที่มีรูปภาพน้อยที่สุด: **${sortedAgencies[0].agency} (${sortedAgencies[0].percent.toFixed(1)}%)**</p>
                            <p class="mb-0">หน่วยงานที่มีรูปภาพมากที่สุด: **${sortedAgencies[sortedAgencies.length - 1].agency} (${sortedAgencies[sortedAgencies.length - 1].percent.toFixed(1)}%)**</p>
                        </div>
                    </div>
                </div>
            `;
            
            container.html(chartHtml + tableHtml);
            
            // วาด Pie Chart
            renderPieChart(totalWithImage, totalNoImage);
        }
        
        function renderPieChart(withImage, noImage) {
            const ctx = document.getElementById('plotsPieChart');

            if (pieChart) {
                pieChart.destroy(); // ทำลาย Chart เก่าก่อน
            }
            
            pieChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['มีรูปภาพ', 'ไม่มีรูปภาพ'],
                    datasets: [{
                        data: [withImage, noImage],
                        backgroundColor: [
                            '#4CAF50', // สีเขียว (มีรูป)
                            '#F44336'  // สีแดง (ไม่มีรูป)
                        ],
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                font: {
                                    family: 'Kanit',
                                    size: 14
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed !== null) {
                                        label += context.parsed.toLocaleString() + ' แปลง';
                                    }
                                    return label;
                                }
                            }
                        },
                        title: {
                            display: false
                        }
                    }
                }
            });
        }


        // 🚨 Event Listener สำหรับ Filter
        $('#filter_year, #filter_agency, #filter_suga_type').on('change', function() {
            loadInfographicData();
        });

        // โหลดข้อมูลครั้งแรกเมื่อหน้าเว็บโหลดเสร็จ
        loadInfographicData();
    });
</script>
</body>
</html>
<?php 
if (isset($conn) && $conn) {
    $conn->close();
} 
?>