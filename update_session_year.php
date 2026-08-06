<?php
session_start();
if (isset($_POST['year'])) {
    $_SESSION['selected_year'] = $_POST['year'];
    session_write_close();
}
?>