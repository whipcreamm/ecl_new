<?php
// เริ่มต้นเซสชัน
session_start();

// ดึงไฟล์เชื่อมต่อฐานข้อมูล
include 'config.php';

// ตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่
if (!isset($_SESSION['company_id'])) {
    header('location: login.php');
    exit();
}

// ตรวจสอบสิทธิ์ผู้ดูแลระบบ (Admin: role_id = 1)
$company_id = (int)$_SESSION['company_id'];
$sql_check_admin = "SELECT * FROM company WHERE id = $company_id";
$admin_query = mysqli_query($conn, $sql_check_admin);
$company_data = mysqli_fetch_assoc($admin_query);

if (!$company_data || $company_data['role_id'] != 1) {
    header('location: login.php');
    exit();
}

// รับค่าตัวกรองค้นหาจาก URL ถ้าไม่มีให้ใช้ค่าเริ่มต้น
if (isset($_GET['from_month']) && $_GET['from_month'] != '') {
    $from_month = $_GET['from_month'];
} else {
    $from_month = date('Y-01');
}

if (isset($_GET['to_month']) && $_GET['to_month'] != '') {
    $to_month = $_GET['to_month'];
} else {
    $to_month = date('Y-m');
}

if (isset($_GET['type'])) {
    $utility_type = $_GET['type'];
} else {
    $utility_type = '';
}

if (isset($_GET['q'])) {
    $keyword = $_GET['q'];
} else {
    $keyword = '';
}

// ตั้งค่า Header สำหรับส่งออกเป็นไฟล์ CSV ภาษาไทย
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=ecolyn_monthly_report_' . date('Ymd_His') . '.csv');

// ใส่ UTF-8 BOM เพื่อให้โปรแกรม Microsoft Excel เปิดอ่านภาษาไทยได้ถูกต้อง ไม่เป็นภาษาต่างดาว
echo "\xEF\xBB\xBF";

// เปิด output stream สำหรับเขียนข้อมูล CSV
$output_file = fopen('php://output', 'w');

// เขียนหัวตารางข้อมูลใน CSV
$csv_headers = array(
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
);
fputcsv($output_file, $csv_headers);

// ดึงรายการบันทึกการใช้พลังงานทั้งหมด เรียงลำดับจากวันที่ล่าสุด
$sql_logs = "SELECT * FROM utility_logs ORDER BY record_date DESC, id DESC";
$log_result = mysqli_query($conn, $sql_logs);

while ($row = mysqli_fetch_assoc($log_result)) {
    $log_month = date('Y-m', strtotime($row['record_date']));

    // กรองช่วงเดือน
    if ($log_month < $from_month || $log_month > $to_month) {
        continue;
    }

    // กรองประเภทการใช้งาน (ไฟฟ้า หรือ น้ำประปา)
    if ($utility_type != '' && $row['type'] != $utility_type) {
        continue;
    }

    // ค้นหาชื่อบริษัทของบันทึกนี้
    $log_company_id = (int)$row['company_id'];
    $sql_com = "SELECT * FROM company WHERE id = $log_company_id";
    $com_res = mysqli_query($conn, $sql_com);
    $com_data = mysqli_fetch_assoc($com_res);
    $company_name = $com_data ? $com_data['name'] : '-';

    // กรองด้วยคำค้นหาชื่อบริษัท
    if ($keyword != '') {
        if (strpos($company_name, $keyword) === false) {
            continue;
        }
    }

    // กำหนดข้อความภาษาไทยสำหรับประเภทการใช้งาน
    if ($row['type'] == 'elec') {
        $type_text = 'ไฟฟ้า';
    } else {
        $type_text = 'น้ำประปา';
    }

    $month_year_display = date('m/Y', strtotime($row['record_date']));

    // บันทึกแถวข้อมูลลงใน CSV
    $row_data = array(
        $log_month,
        $month_year_display,
        $company_name,
        $type_text,
        $row['meter_read'],
        $row['amount'],
        $row['prev_amount'],
        $row['save_unit'],
        $row['co2e_save'],
        $row['note']
    );
    fputcsv($output_file, $row_data);
}

// ปิดการเขียนไฟล์และสิ้นสุดการทำงาน
fclose($output_file);
exit();
?>
