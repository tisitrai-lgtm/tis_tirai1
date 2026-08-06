<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
// ฟังก์ชันสำหรับเรียกใช้งานแจ้งเตือน
function showAlert($type, $title, $text) {
    echo "
    <script>
        Swal.fire({
            icon: '$type',
            title: '$title',
            text: '$text',
            confirmButtonColor: '#0d6efd',
        });
    </script>
    ";
}

// ตรวจสอบการแจ้งเตือนจาก Session (ถ้ามี)
if (isset($_SESSION['success'])) {
    showAlert('success', 'สำเร็จ!', $_SESSION['success']);
    unset($_SESSION['success']); // แสดงแล้วลบทิ้งทันที
}

if (isset($_SESSION['error'])) {
    showAlert('error', 'เกิดข้อผิดพลาด!', $_SESSION['error']);
    unset($_SESSION['error']);
}
?>