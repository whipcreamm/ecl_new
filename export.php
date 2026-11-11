<?php
session_start();
include '../config.php';

$company_id = $_SESSION['company_id'];

// เฉพาะแอดมิน (role_id = 1)
$company = mysqli_fetch_assoc(mysqli_query($conn, "select * from company where id=$company_id"));
if($company['role_id'] != 1){
    header('location: ../login.php');
    exit();
}

// ตัวกรอง
$from_month = $_GET['from_month'] ?? date('Y-01');
$to_month = $_GET['to_month'] ?? date('Y-m');
$utility_type = $_GET['type'] ?? '';
$keyword = $_GET['q'] ?? '';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=ecolyn_monthly_report_' . date('Ymd_His') . '.csv');

echo "\xEF\xBB\xBF"; // UTF-8 BOM ให้ Excel อ่านภาษาไทยได้
$output = fopen('php://output', 'w');
fputcsv($output, [
    'ประจำเดือน',
    'เดือน/ปี',
    'ชื่อบริษัท/หน่วยงาน',
    'ประเภท',
    'เลขมิเตอร์ล่าสุด',
    'ปริมาณการใช้ในเดือน (หน่วย)',
    'ปริมาณเดือนก่อน (หน่วย)',
    'ประหยัดได้ในเดือน (หน่วย)',
    'kgCO2e ที่ลดได้',
    'หมายเหตุ'
]);

$result = mysqli_query($conn, "select * from utility_logs order by record_date desc, id desc");
while($l = mysqli_fetch_assoc($result)){
    $month = date('Y-m', strtotime($l['record_date']));
    if($month < $from_month || $month > $to_month) continue;
    if($utility_type != '' && $l['type'] != $utility_type) continue;

    // ดึงชื่อบริษัทแยก
    $cm = mysqli_fetch_assoc(mysqli_query($conn, "select * from company where id={$l['company_id']}"));
    if($keyword != '' && strpos($cm['name'], $keyword) === false) continue;

    fputcsv($output, [
        $month,
        date('m/Y', strtotime($l['record_date'])),
        $cm['name'],
        $l['type'] == 'elec' ? 'ไฟฟ้า' : 'น้ำประปา',
        $l['meter_read'],
        $l['amount'],
        $l['prev_amount'],
        $l['save_unit'],
        $l['co2e_save'],
        $l['note']
    ]);
}
fclose($output);
exit();