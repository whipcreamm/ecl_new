<?php
session_start();
include '../config.php';

$company_id = (int)($_SESSION['company_id'] ?? 0);
$company = mysqli_fetch_assoc(mysqli_query($conn, "select * from company where id=$company_id"));

if (!$company || $company['role_id'] != 1) {
    header('location: ../login.php');
    exit();
}

// ช่วงเดือนที่เลือก
$from_month = $_GET['from_month'] ?? date('Y-01');
$to_month = $_GET['to_month'] ?? date('Y-m');
if($from_month > $to_month){
    $temp_month = $from_month;
    $from_month = $to_month;
    $to_month = $temp_month;
}

// การ์ดสรุป
$total_members = mysqli_num_rows(mysqli_query($conn, "select * from company"));
$total_standard = mysqli_num_rows(mysqli_query($conn, "select * from members where status_mem='Y' and (end_mem is null or end_mem >= curdate()) and pack_id in (select id from pack where name like '%standard%' or name like '%มาตรฐาน%')"));
$total_premium = mysqli_num_rows(mysqli_query($conn, "select * from members where status_mem='Y' and (end_mem is null or end_mem >= curdate()) and pack_id in (select id from pack where name like '%premium%' or name like '%พรีเมี่%')"));
$total_new_orders = mysqli_num_rows(mysqli_query($conn, "select * from product_order where status='pending'"));

// ---------- คำสั่งซื้อ: ดึงดิบ แล้วบวกเองใน PHP ----------
$income = [];        // เดือน => รวมเงิน
$sales = [];         // ชื่อสินค้า => รวมเงิน
$shop_of = [];       // ชื่อสินค้า => ชื่อร้าน
$order_result = mysqli_query($conn, "select * from product_order where status <> 'cancel' order by create_at");
while($o = mysqli_fetch_assoc($order_result)){
    $month = date('Y-m', strtotime($o['create_at']));
    if($month < $from_month || $month > $to_month) continue;

    // รายได้รายเดือน
    if(!isset($income[$month])) $income[$month] = 0;
    $income[$month] += $o['qty'];

    // ยอดขายสินค้า (ดึงสินค้าแยก ไม่ join)
    $pr = mysqli_fetch_assoc(mysqli_query($conn, "select * from product where id_pro={$o['product_id']}"));
    $pname = $pr['name'];
    if(!isset($sales[$pname])){
        $sales[$pname] = 0;
        $shop_of[$pname] = $pr['shop_name'];
    }
    $sales[$pname] += $o['qty'];
}
ksort($income);
arsort($sales);                      // เรียงยอดขายมาก -> น้อย
$sales = array_slice($sales, 0, 8, true);

$chart_labels = array_keys($income);
$chart_values = array_values($income);

// ---------- บันทึกค่าน้ำไฟ: ดึงดิบ แล้วบวกเองใน PHP ----------
$carbon = [];      // เดือน => ข้อมูลสรุป
$co2_of = [];      // ชื่อบริษัท => รวม co2
$months_of = [];   // ชื่อบริษัท => [เดือนที่บันทึก]
$total_co2 = 0;
$log_result = mysqli_query($conn, "select * from utility_logs order by record_date");
while($l = mysqli_fetch_assoc($log_result)){
    $month = date('Y-m', strtotime($l['record_date']));
    if($month < $from_month || $month > $to_month) continue;

    // สรุปรายเดือน
    if(!isset($carbon[$month])){
        $carbon[$month] = ['companies' => [], 'elec_use' => 0, 'elec_save' => 0, 'water_use' => 0, 'water_save' => 0, 'co2' => 0];
    }
    $carbon[$month]['companies'][$l['company_id']] = 1;
    if($l['type'] == 'elec'){
        $carbon[$month]['elec_use'] += $l['amount'];
        $carbon[$month]['elec_save'] += $l['save_unit'];
    }else{
        $carbon[$month]['water_use'] += $l['amount'];
        $carbon[$month]['water_save'] += $l['save_unit'];
    }
    $carbon[$month]['co2'] += $l['co2e_save'];

    // อันดับสมาชิก (ดึงชื่อบริษัทแยก)
    $cm = mysqli_fetch_assoc(mysqli_query($conn, "select * from company where id={$l['company_id']}"));
    $cname = $cm['name'];
    if(!isset($co2_of[$cname])) $co2_of[$cname] = 0;
    $co2_of[$cname] += $l['co2e_save'];
    $months_of[$cname][$month] = 1;

    $total_co2 += $l['co2e_save'];
}
krsort($carbon);                    // เดือนใหม่ -> เก่า
arsort($co2_of);                    // co2 มาก -> น้อย
$top_list = array_slice($co2_of, 0, 10, true);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ดผู้ดูแลระบบ</title>
</head>
<style>
   
</style>
<body class="bg-light">
<?php include '../navbar.php'; ?>

<div class="container mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold">แดชบอร์ดภาพรวมระบบ (รายเดือน)</h4>
        <div>
            <a href="kiosk.php" class="btn btn-info btn-sm ">แดชบอร์ดแสดงผลอัตโนมัติ</a>
            <a href="export.php" class="btn btn-success btn-sm">ส่งออกรายงาน (CSV)</a>
        </div>
    </div>

    <!-- ตัวกรอง -->
    <div class="card p-3 shadow-sm mb-4">
        <form class="row g-2 align-items-end" method="get">
            <div class="col-md-5">
                <label class="form-label small">ตั้งแต่ประจำเดือน</label>
                <input type="month" name="from_month" class="form-control form-control-sm" value="<?= $from_month ?>">
            </div>
            <div class="col-md-5">
                <label class="form-label small">ถึงประจำเดือน</label>
                <input type="month" name="to_month" class="form-control form-control-sm" value="<?= $to_month ?>">
            </div>
            <div class="col-md-2">
                <button class="btn btn-success btn-sm w-100">ค้นหารายเดือน</button>
            </div>
        </form>
    </div>

    <!-- การ์ดสรุป -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card p-3 text-center shadow-sm h-100 bg-success text-white">
                <h3 class="fw-bold mb-0"><?= number_format($total_members) ?></h3>
                <small>สมาชิกทั้งหมด</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card p-3 text-center shadow-sm h-100">
                <h3 class="fw-bold mb-0 text-success"><?= number_format($total_standard) ?></h3>
                <small class="text-muted">แพ็กเกจมาตรฐาน</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card p-3 text-center shadow-sm h-100">
                <h3 class="fw-bold mb-0 text-success"><?= number_format($total_premium) ?></h3>
                <small class="text-muted">แพ็กเกจพรีเมียม</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card p-3 text-center shadow-sm h-100">
                <h3 class="fw-bold mb-0 <?= $total_new_orders > 0 ? 'text-danger' : 'text-success' ?>"><?= number_format($total_new_orders) ?></h3>
                <small class="text-muted">คำสั่งซื้อสินค้าใหม่</small>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <!-- กราฟรายได้ -->
        <div class="col-lg-7">
            <div class="card p-3 shadow-sm h-100">
                <h6 class="fw-bold">รายได้/รายเดือน</h6>
                <div style="height:260px"><canvas id="incomeChart"></canvas></div>
            </div>
        </div>

        <!-- ยอดขายสินค้า -->
        <div class="col-lg-5">
            <div class="card p-3 shadow-sm h-100">
                <h6 class="fw-bold">ยอดขายสินค้า</h6>
                <table class="table table-bordered table-hover align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th>ชื่อสินค้า</th>
                            <th>ร้านค้า</th>
                            <th class="text-end">ยอดขาย</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($sales as $pname => $total): ?>
                        <tr>
                            <td><?= $pname ?></td>
                            <td><?= $shop_of[$pname] ?></td>
                            <td class="text-end"><?= number_format($total) ?> ชิ้น</td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- สรุปคาร์บอนรายเดือน -->
    <div class="card p-3 shadow-sm mb-4">
        <h5 class="fw-bold mb-3">ตารางสรุปผลการลดคาร์บอนและการประหยัดรายเดือน</h5>
        <table class="table table-bordered table-hover align-middle text-center">
            <thead class="bg-light">
                <tr>
                    <th>ประจำเดือน</th>
                    <th>จำนวนองค์กรที่บันทึก</th>
                    <th>ไฟฟ้าใช้ไป (kWh)</th>
                    <th>ไฟฟ้าประหยัด (บาท)</th>
                    <th>น้ำใช้ไป (ลบ.ม.)</th>
                    <th>น้ำประหยัด (บาท)</th>
                    <th>รวม CO₂ ที่ลดได้ (kg)</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($carbon as $month => $c): ?>
                <tr>
                    <td><?= date('m/Y', strtotime($month . '-01')) ?></td>
                    <td><?= count($c['companies']) ?> แห่ง</td>
                    <td><?= number_format($c['elec_use'], 2) ?></td>
                    <td class="text-success"><?= number_format($c['elec_save'], 2) ?></td>
                    <td><?= number_format($c['water_use'], 2) ?></td>
                    <td class="text-success"><?= number_format($c['water_save'], 2) ?></td>
                    <td class="text-success"><?= number_format($c['co2'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- อันดับสมาชิก -->
    <div class="card p-3 shadow-sm">
        <h6 class="fw-bold mb-3">สมาชิกที่ช่วยลด CO₂ สูงสุดในช่วงเดือนที่เลือก</h6>
        <table class="table table-bordered table-hover align-middle">
            <thead class="bg-light">
                <tr>
                    <th class="text-center">อันดับ</th>
                    <th>ชื่อหน่วยงาน</th>
                    <th class="text-center">จำนวนเดือนที่บันทึก</th>
                    <th class="text-end">CO₂ ที่ลดได้ (kgCO₂e / %)</th>
                </tr>
            </thead>
            <tbody>
            <?php $rank = 1; foreach($top_list as $cname => $co2): ?>
                <tr>
                    <td class="text-center"><?= $rank++ ?></td>
                    <td><?= $cname ?></td>
                    <td class="text-center"><?= count($months_of[$cname]) ?> เดือน</td>
                    <td class="text-end"><?= number_format($co2, 2) ?> </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script src="<?= $base ?>js/bootstrap.bundle.min.js"></script>
</div>
<?php include '../footer.php' ?>
<script src="<?= $base ?>js/chart.umd.js"></script>
<script>
new Chart('incomeChart', {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{label: 'รายได้ (บาท)', data: <?= json_encode($chart_values) ?>, backgroundColor: '#198754'}]
    },
    options: {responsive: true, maintainAspectRatio: false, scales: {y: {beginAtZero: true}}}
});
</script>
</body>
</html>