<?php
// functions.php

function thai_date_fmt($date, $months = []) {
    if(empty($date)) return '-';
    $m = !empty($months) ? $months : ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
    $ts = strtotime($date);
    return (int)date('d',$ts).' '.$m[(int)date('m',$ts)].' '.((int)date('Y',$ts)+543);
}

function thai_datetime_fmt($datetime) {
    if(empty($datetime)) return '-';
    $m = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
    $ts = strtotime($datetime);
    return (int)date('d',$ts).' '.$m[(int)date('m',$ts)].' '.((int)date('Y',$ts)+543).' '.date('H:i น.',$ts);
}
?>