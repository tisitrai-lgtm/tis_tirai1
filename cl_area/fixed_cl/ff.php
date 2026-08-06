<script>
$(document).on('click', '.delete-btn', function () {
    const id = $(this).data('id');

    if (!id) {
        Toastify({
            text: "❌ ไม่พบ ID ที่ต้องการลบ",
            duration: 3000,
            gravity: "top",
            position: "right",
            backgroundColor: "#dc3545"
        }).showToast();
        return;
    }

    if (!confirm(`คุณแน่ใจหรือไม่ว่าต้องการลบข้อมูล`)) {
        return;
    }

    $.ajax({
        url: 'deleteQueryString.php', // ✅ แก้ไขตรงนี้!
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function (res) {
            if (res.status === 'success') {
                Toastify({
                    text: "✅ " + res.message,
                    duration: 3000,
                    close: true,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#4CAF50"
                }).showToast();
                location.reload();
            } else {
                Toastify({
                    text: "❌ " + res.message,
                    duration: 3000,
                    close: true,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#dc3545"
                }).showToast();
            }
        },
        error: function (xhr, status, error) {
            let errorMessage = "❌ ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้";
            if (xhr.status === 404) {
                errorMessage = "❌ ไม่พบไฟล์ที่ร้องขอ (404 Not Found) - ตรวจสอบชื่อไฟล์และพาธ";
            } else if (xhr.status === 500) {
                errorMessage = "❌ เกิดข้อผิดพลาดภายในเซิร์ฟเวอร์ (500 Internal Server Error)";
            } else if (xhr.responseText) {
                try {
                    const errorRes = JSON.parse(xhr.responseText);
                    if (errorRes.message) {
                        errorMessage = "❌ ข้อผิดพลาด: " + errorRes.message;
                    }
                } catch (e) {
                    // Not JSON, display generic message
                }
            }
            Toastify({
                text: errorMessage,
                duration: 5000,
                close: true,
                gravity: "top",
                position: "right",
                backgroundColor: "#dc3545"
            }).showToast();
            console.error("AJAX Error Details:", xhr, status, error);
        }
    });
});
</script>
$.ajax({
        url: 'insertData.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(result) {
            if (result.status === 'success') {
                // ✅ แจ้งเตือนแบบ toast
                Toastify({
                    text: "✅ " + result.message,
                    duration: 3000,
                    close: true,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#4CAF50"
                }).showToast();

                // ✅ ปิด modal
                $('#insertFormModal').modal('hide');

                // ✅ เพิ่มแถวใหม่
                if (result.data) {
                    myDataTable.row.add([
                        result.data.production_year || '',
                        result.data.plot_id || '',
                        result.data.plcontract_number || '',
                        result.data.sugar_type || '',
                        result.data.quota_name || '',
                        result.data.promotion_unit || '',
                        result.data.promoter_area || '',
                        result.data.village || '',
                        result.data.district_sub || '',
                        result.data.district || '',
                        result.data.province || '',
                        result.data.square_meters || '',
                        result.data.rai || '',
                        result.data.ngan || '',
                        result.data.wah || '',
                        result.data.rai_adjusted || '',
                        `<a href="deleteQueryString.php?id=${result.data.id}" class="btn btn-danger btn-sm">ลบข้อมูล</a>`,
                        `<a target="_blank" href="exportPDF.php?id=${result.data.id}" class="btn btn-primary btn-sm">Print</a>`
                    ]).draw(false);
                }

                // ✅ รีเซ็ตฟอร์ม
                $('#insertDataForm')[0].reset();
                if (typeof convertSquareMetersModal === 'function') {
                    convertSquareMetersModal();
                }

            } else {
                alert("❌ เกิดข้อผิดพลาด: " + result.message);
            }
        },
        error: function(xhr, status, error) {
            alert("❌ ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้: " + error);
        }