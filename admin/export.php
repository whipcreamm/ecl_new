<?php
// เริ่มต้นเซสชัน
session_start();

// ดึงไฟล์เชื่อมต่อฐานข้อมูล
include '../config.php';

// ตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่ ถ้ายังไม่ล็อกอินให้กลับไปหน้า login
if (!isset($_SESSION['company_id'])) {
    header('location: ../login.php');
    exit();
}

// ตรวจสอบสิทธิ์ผู้ดูแลระบบ (Admin: role_id = 1)
$company_id = (int)$_SESSION['company_id'];
$sql_check_admin = "SELECT * FROM company WHERE id = $company_id";
$admin_query = mysqli_query($conn, $sql_check_admin);
$company_data = mysqli_fetch_assoc($admin_query);

if (!$company_data || $company_data['role_id'] != 1) {
    header('location: ../login.php');
    exit();
}

// รับช่วงเดือนที่ต้องการส่งออก (ค่าเริ่มต้นคือตั้งแต่ต้นปีจนถึงเดือนปัจจุบัน)
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

// สลับเดือนหากผู้ใช้เลือกเดือนเริ่มต้นมากกว่าเดือนสิ้นสุด
if ($from_month > $to_month) {
    $temp_month = $from_month;
    $from_month = $to_month;
    $to_month = $temp_month;
}

$pack_date_field = 'start_mem';
$monthly_sales_rows = array();

// 1. รวบรวมข้อมูลคำสั่งซื้อสินค้า (ไม่รวมรายการที่ยกเลิก)
$sql_orders = "SELECT * FROM product_order WHERE status <> 'cancel' ORDER BY create_at ASC";
$order_result = mysqli_query($conn, $sql_orders);

while ($order = mysqli_fetch_assoc($order_result)) {
    $order_month = date('Y-m', strtotime($order['create_at']));

    if ($order_month < $from_month || $order_month > $to_month) {
        continue;
    }

    if (!isset($monthly_sales_rows[$order_month])) {
        $monthly_sales_rows[$order_month] = array(
            'p_orders' => 0,
            'p_qty'    => 0,
            'p_total'  => 0,
            'k_orders' => 0,
            'k_total'  => 0
        );
    }

    $monthly_sales_rows[$order_month]['p_orders'] = $monthly_sales_rows[$order_month]['p_orders'] + 1;
    $monthly_sales_rows[$order_month]['p_qty']    = $monthly_sales_rows[$order_month]['p_qty'] + (int)$order['qty'];
    $monthly_sales_rows[$order_month]['p_total']  = $monthly_sales_rows[$order_month]['p_total'] + (float)$order['final_price'];
}

// 2. รวบรวมข้อมูลคำสั่งซื้อแพ็กเกจ (เฉพาะที่อนุมัติแล้ว status_mem = 'Y')
$sql_members = "SELECT * FROM members WHERE status_mem = 'Y'";
$member_result = mysqli_query($conn, $sql_members);

while ($member = mysqli_fetch_assoc($member_result)) {
    $date_val = $member[$pack_date_field];
    if (empty($date_val) || $date_val == '0000-00-00') {
        continue;
    }

    $pack_month = date('Y-m', strtotime($date_val));
    if ($pack_month < $from_month || $pack_month > $to_month) {
        continue;
    }

    $pack_id = (int)$member['pack_id'];
    $sql_pack = "SELECT * FROM pack WHERE id = $pack_id";
    $pack_res = mysqli_query($conn, $sql_pack);
    $pack_info = mysqli_fetch_assoc($pack_res);
    $pack_price = $pack_info ? (float)$pack_info['price'] : 0;

    if (!isset($monthly_sales_rows[$pack_month])) {
        $monthly_sales_rows[$pack_month] = array(
            'p_orders' => 0,
            'p_qty'    => 0,
            'p_total'  => 0,
            'k_orders' => 0,
            'k_total'  => 0
        );
    }

    $monthly_sales_rows[$pack_month]['k_orders'] = $monthly_sales_rows[$pack_month]['k_orders'] + 1;
    $monthly_sales_rows[$pack_month]['k_total']  = $monthly_sales_rows[$pack_month]['k_total'] + $pack_price;
}

// เรียงลำดับเดือนจากเก่าไปหาใหม่
ksort($monthly_sales_rows);

// 3. ส่งออกข้อมูลเป็นไฟล์ CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=ecolyn_sales_' . date('Ymd_His') . '.csv');

// ใส่ UTF-8 BOM เพื่อให้โปรแกรม Excel เปิดภาษาไทยได้อย่างถูกต้อง
echo "\xEF\xBB\xBF";
$output_file = fopen('php://output', 'w');

// เขียนหัวตาราง CSV
$csv_headers = array(
    'ประจำเดือน',
    'คำสั่งซื้อสินค้า (รายการ)',
    'จำนวนสินค้าที่ขาย (ชิ้น)',
    'ยอดขายสินค้า (บาท)',
    'แพ็กเกจที่ซื้อ (รายการ)',
    'ยอดขายแพ็กเกจ (บาท)',
    'รายได้รวม (บาท)'
);
fputcsv($output_file, $csv_headers);

// ตัวแปรสำหรับคำนวณยอดรวมทั้งหมด
$sum_p_orders = 0;
$sum_p_qty = 0;
$sum_p_total = 0;
$sum_k_orders = 0;
$sum_k_total = 0;

foreach ($monthly_sales_rows as $month_str => $row_data) {
    $month_display = date('m/Y', strtotime($month_str . '-01'));
    $total_income = $row_data['p_total'] + $row_data['k_total'];

    $csv_row = array(
        $month_display,
        $row_data['p_orders'],
        $row_data['p_qty'],
        $row_data['p_total'],
        $row_data['k_orders'],
        $row_data['k_total'],
        $total_income
    );
    fputcsv($output_file, $csv_row);

    $sum_p_orders = $sum_p_orders + $row_data['p_orders'];
    $sum_p_qty    = $sum_p_qty + $row_data['p_qty'];
    $sum_p_total  = $sum_p_total + $row_data['p_total'];
    $sum_k_orders = $sum_k_orders + $row_data['k_orders'];
    $sum_k_total  = $sum_k_total + $row_data['k_total'];
}

// เขียนแถวสรุปรวมทั้งหมด
$total_all_income = $sum_p_total + $sum_k_total;
$csv_total_row = array(
    'รวมทั้งหมด',
    $sum_p_orders,
    $sum_p_qty,
    $sum_p_total,
    $sum_k_orders,
    $sum_k_total,
    $total_all_income
);
fputcsv($output_file, $csv_total_row);

fclose($output_file);
exit();
?>
