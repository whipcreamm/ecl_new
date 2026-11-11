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

// ช่วงเดือน (ส่งมาจากหน้า dashboard)
$from_month = $_GET['from_month'] ?? date('Y-01');
$to_month = $_GET['to_month'] ?? date('Y-m');
if($from_month > $to_month){
    $temp_month = $from_month;
    $from_month = $to_month;
    $to_month = $temp_month;
}

// คอลัมน์วันที่ซื้อแพ็กเกจในตาราง members (แก้ให้ตรงตารางจริง)
$pack_date = 'start_mem';

// เดือน => ข้อมูลของเดือนนั้น (ตารางเดียว)
$rows = [];

// ---------- คำสั่งซื้อสินค้า ----------
$order_result = mysqli_query($conn, "select * from product_order where status <> 'cancel' order by create_at");
while($o = mysqli_fetch_assoc($order_result)){
    $month = date('Y-m', strtotime($o['create_at']));
    if($month < $from_month || $month > $to_month) continue;

    if(!isset($rows[$month])) $rows[$month] = ['p_orders' => 0, 'p_qty' => 0, 'p_total' => 0, 'k_orders' => 0, 'k_total' => 0];
    $rows[$month]['p_orders'] += 1;
    $rows[$month]['p_qty'] += $o['qty'];
    $rows[$month]['p_total'] += $o['final_price'];
}

// ---------- คำสั่งซื้อแพ็กเกจ (เฉพาะที่อนุมัติแล้ว) ----------
$member_result = mysqli_query($conn, "select * from members where status_mem='Y'");
while($mb = mysqli_fetch_assoc($member_result)){
    if(empty($mb[$pack_date]) || $mb[$pack_date] == '0000-00-00') continue;
    $month = date('Y-m', strtotime($mb[$pack_date]));
    if($month < $from_month || $month > $to_month) continue;

    $pk = mysqli_fetch_assoc(mysqli_query($conn, "select * from pack where id={$mb['pack_id']}"));

    if(!isset($rows[$month])) $rows[$month] = ['p_orders' => 0, 'p_qty' => 0, 'p_total' => 0, 'k_orders' => 0, 'k_total' => 0];
    $rows[$month]['k_orders'] += 1;
    $rows[$month]['k_total'] += $pk['price'];
}
ksort($rows);

// ---------- ส่งออก CSV ----------
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=ecolyn_sales_' . date('Ymd_His') . '.csv');

echo "\xEF\xBB\xBF"; // UTF-8 BOM ให้ Excel อ่านภาษาไทยได้
$output = fopen('php://output', 'w');

fputcsv($output, ['ประจำเดือน', 'คำสั่งซื้อสินค้า (รายการ)', 'จำนวนสินค้าที่ขาย (ชิ้น)', 'ยอดขายสินค้า (บาท)', 'แพ็กเกจที่ซื้อ (รายการ)', 'ยอดขายแพ็กเกจ (บาท)', 'รายได้รวม (บาท)']);

$sum = ['p_orders' => 0, 'p_qty' => 0, 'p_total' => 0, 'k_orders' => 0, 'k_total' => 0];
foreach($rows as $month => $r){
    fputcsv($output, [date('m/Y', strtotime($month . '-01')), $r['p_orders'], $r['p_qty'], $r['p_total'], $r['k_orders'], $r['k_total'], $r['p_total'] + $r['k_total']]);
    $sum['p_orders'] += $r['p_orders'];
    $sum['p_qty'] += $r['p_qty'];
    $sum['p_total'] += $r['p_total'];
    $sum['k_orders'] += $r['k_orders'];
    $sum['k_total'] += $r['k_total'];
}
fputcsv($output, ['รวมทั้งหมด', $sum['p_orders'], $sum['p_qty'], $sum['p_total'], $sum['k_orders'], $sum['k_total'], $sum['p_total'] + $sum['k_total']]);

fclose($output);
exit();