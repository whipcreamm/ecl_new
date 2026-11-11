<?php
session_start();
include 'config.php';
if (!isset($_SESSION['company_id'])) {
    header('location:login.php');
    exit();
}
$company_id = $_SESSION['company_id'];

// แอดมิน หรือสมาชิกที่มีแพ็กเกจที่ใช้งานอยู่
$company = mysqli_fetch_assoc(mysqli_query($conn, "select * from company where id=$company_id"));
$is_admin = $company['role_id'] == 1;
$has_package = mysqli_num_rows(mysqli_query($conn, "select * from members where company_id=$company_id and status_mem='Y' and (end_mem is null or end_mem >= curdate())"));
if(!$is_admin && $has_package == 0){
    header('location: package.php');
    exit();
}

// ค่า factor
$settings = [];
$setting_result = mysqli_query($conn, "select * from eco_setting");
while($setting_row = mysqli_fetch_assoc($setting_result)){
    $settings[$setting_row['setting_key']] = $setting_row['setting_value'];
}
$factor_electricity = $settings['factor_elec'] ?? 0.4999;
$factor_water = $settings['factor_water'] ?? 0.7;

// ตัวกรอง
$from_month = $_GET['from_month'] ?? date('Y-01');
$to_month = $_GET['to_month'] ?? date('Y-m');
$utility_type = $_GET['type'] ?? '';
if($from_month > $to_month){
    $temp_month = $from_month;
    $from_month = $to_month;
    $to_month = $temp_month;
}

// ---------- ดึงบันทึกทั้งหมด แล้วบวกเองใน PHP ----------
$monthly = [];     // เดือน => ข้อมูลของบริษัทเรา
$type_co2 = [];    // ประเภท => co2 (ทั้งระบบ)
$co2_of = [];      // ชื่อบริษัท => รวม co2
$months_of = [];   // ชื่อบริษัท => [เดือนที่บันทึก]
$id_of = [];       // ชื่อบริษัท => id
$total_baht_saved = 0;
$total_carbon_used = 0;
$total_carbon_previous = 0;

$log_result = mysqli_query($conn, "select * from utility_logs order by record_date");
while($l = mysqli_fetch_assoc($log_result)){
    $month = date('Y-m', strtotime($l['record_date']));
    if($month < $from_month || $month > $to_month) continue;
    if($utility_type != '' && $l['type'] != $utility_type) continue;

    // สัดส่วนตามประเภท (ทั้งระบบ)
    if(!isset($type_co2[$l['type']])) $type_co2[$l['type']] = 0;
    $type_co2[$l['type']] += $l['co2e_save'];

    // อันดับสมาชิก (ดึงชื่อบริษัทแยก)
    $cm = mysqli_fetch_assoc(mysqli_query($conn, "select * from company where id={$l['company_id']}"));
    $cname = $cm['name'];
    if(!isset($co2_of[$cname])){
        $co2_of[$cname] = 0;
        $id_of[$cname] = $l['company_id'];
    }
    $co2_of[$cname] += $l['co2e_save'];
    $months_of[$cname][$month] = 1;

    // สรุปรายเดือนเฉพาะบริษัทเรา
    if($l['company_id'] != $company_id) continue;
    if(!isset($monthly[$month])){
        $monthly[$month] = ['elec_use' => 0, 'elec_prev' => 0, 'water_use' => 0, 'water_prev' => 0, 'baht' => 0];
    }
    if($l['type'] == 'elec'){
        $monthly[$month]['elec_use'] += $l['amount'];
        $monthly[$month]['elec_prev'] += $l['prev_amount'];
    }else{
        $monthly[$month]['water_use'] += $l['amount'];
        $monthly[$month]['water_prev'] += $l['prev_amount'];
    }
    $monthly[$month]['baht'] += $l['save_unit'];
}
krsort($monthly);      // เดือนใหม่ -> เก่า
arsort($co2_of);       // co2 มาก -> น้อย
$top_list = array_slice($co2_of, 0, 10, true);

// คำนวณคาร์บอนรายเดือน
foreach($monthly as $month => $m){
    $used = $m['elec_use'] * $factor_electricity + $m['water_use'] * $factor_water;
    $previous = $m['elec_prev'] * $factor_electricity + $m['water_prev'] * $factor_water;
    $saved = $previous - $used;
    $monthly[$month]['carbon_used'] = $used;
    $monthly[$month]['carbon_saved'] = $saved;
    $monthly[$month]['percent_saved'] = $previous > 0 ? $saved / $previous * 100 : 0;
    $monthly[$month]['percent_used'] = $previous > 0 ? $used / $previous * 100 : 0;
    $total_carbon_used += $used;
    $total_carbon_previous += $previous;
    $total_baht_saved += $m['baht'];
}
$total_carbon_saved = $total_carbon_previous - $total_carbon_used;

// แต้มและอันดับของตัวเอง
$my_points = 0;
$point_result = mysqli_query($conn, "select * from eco_point_ledger where company_id=$company_id");
while($p = mysqli_fetch_assoc($point_result)){
    $my_points += $p['point'];
}
$my_rank = '-';
$rank = 1;
foreach($top_list as $cname => $co2){
    if($id_of[$cname] == $company_id) $my_rank = '#' . $rank;
    $rank++;
}

// ข้อมูลกราฟ (เก่า -> ใหม่)
$chart_labels = [];
$chart_values = [];
foreach(array_reverse($monthly, true) as $month => $m){
    $chart_labels[] = $month;
    $chart_values[] = (float)$m['carbon_saved'];
}

$type_names = ['elec' => 'ไฟฟ้า', 'water' => 'น้ำประปา'];
$type_labels = [];
$type_values = [];
foreach($type_co2 as $t => $v){
    $type_labels[] = $type_names[$t];
    $type_values[] = (float)$v;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ดสรุปผลการลดคาร์บอน</title>
</head>
<body class="bg-light">
<?php include 'navbar.php'; ?>

<div class="container mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold">รายงานสรุปผลการลดคาร์บอน (รายเดือน)</h4>
        <a href="export.php?from_month=<?= $from_month ?>&to_month=<?= $to_month ?>&type=<?= $utility_type ?>" class="btn btn-outline-success btn-sm">ส่งออกข้อมูลรายเดือน (CSV)</a>
    </div>

    <!-- การ์ดสรุป -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card p-3 text-center shadow-sm h-100 bg-success text-white">
                <h3 class="fw-bold mb-0"><?= number_format($total_carbon_saved, 2) ?></h3>
                <small>คาร์บอนที่ลดได้สะสม (kgCO₂e)</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card p-3 text-center shadow-sm h-100">
                <h3 class="fw-bold mb-0 text-warning"><?= number_format($total_baht_saved, 2) ?></h3>
                <small class="text-muted">ประหยัดค่าใช้จ่ายรวม (บาท)</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card p-3 text-center shadow-sm h-100">
                <h3 class="fw-bold mb-0 text-success"><?= number_format($my_points) ?></h3>
                <small class="text-muted">แต้มสะสม</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card p-3 text-center shadow-sm h-100">
                <h3 class="fw-bold mb-0 text-primary"><?= $my_rank ?></h3>
                <small class="text-muted">อันดับในตาราง</small>
            </div>
        </div>
    </div>

    <!-- ตัวกรอง -->
    <div class="card p-3 shadow-sm mb-4">
        <form class="row g-2 align-items-end" method="get">
            <div class="col-md-4">
                <label class="form-label small">ตั้งแต่ประจำเดือน</label>
                <input type="month" name="from_month" class="form-control form-control-sm" value="<?= $from_month ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small">ถึงประจำเดือน</label>
                <input type="month" name="to_month" class="form-control form-control-sm" value="<?= $to_month ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small">ประเภท</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">ทั้งหมด</option>
                    <option value="elec" <?= $utility_type == 'elec' ? 'selected' : '' ?>>ไฟฟ้า</option>
                    <option value="water" <?= $utility_type == 'water' ? 'selected' : '' ?>>น้ำประปา</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-success btn-sm w-100">ค้นหารายเดือน</button>
            </div>
        </form>
    </div>

    <!-- กราฟ -->
    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card p-3 shadow-sm h-100">
                <h6 class="fw-bold">ปริมาณ CO₂ ที่ลดได้รายเดือน (kgCO₂e)</h6>
                <div style="height:260px"><canvas id="monthChart"></canvas></div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card p-3 shadow-sm h-100">
                <h6 class="fw-bold">สัดส่วน CO₂ ลดได้แยกตามประเภท</h6>
                <div style="height:260px"><canvas id="typeChart"></canvas></div>
            </div>
        </div>
    </div>

    <!-- สรุปรายเดือน -->
    <div class="card p-3 shadow-sm mb-4">
        <h5 class="fw-bold mb-3">สรุปรายเดือน</h5>
        <table class="table table-bordered table-hover align-middle text-center">
            <thead class="bg-light">
                <tr>
                    <th>ประจำเดือน</th>
                    <th>ใช้ไฟ (kWh)</th>
                    <th>ใช้น้ำ (ลบ.ม.)</th>
                    <th>ประหยัด (บ.)</th>
                    <th>คาร์บอนใช้ (kg)</th>
                    <th>ลดได้ (kg)</th>
                    <th>ลด %</th>
                    <th>ใช้ %</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($monthly as $month => $m): ?>
                <tr>
                    <td><?= date('m/Y', strtotime($month . '-01')) ?></td>
                    <td><?= number_format($m['elec_use'], 2) ?></td>
                    <td><?= number_format($m['water_use'], 2) ?></td>
                    <td class="<?= $m['baht'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= number_format($m['baht'], 2) ?></td>
                    <td><?= number_format($m['carbon_used'], 2) ?></td>
                    <td class="<?= $m['carbon_saved'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= number_format($m['carbon_saved'], 2) ?></td>
                    <td class="<?= $m['carbon_saved'] >= 0 ? 'text-success' : 'text-danger' ?>"><?= number_format($m['percent_saved'], 1) ?>%</td>
                    <td><?= number_format($m['percent_used'], 1) ?>%</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <small class="text-muted">ลด % = คาร์บอนที่ลดได้ ÷ คาร์บอนเดือนก่อน | ใช้ % = คาร์บอนเดือนนี้ ÷ คาร์บอนเดือนก่อน</small>
    </div>

    <!-- อันดับ -->
    <div class="card p-3 shadow-sm">
        <h6 class="fw-bold mb-3">สมาชิกที่ช่วยลด CO₂ สูงสุดในช่วงเดือนที่เลือก</h6>
        <table class="table table-bordered table-hover align-middle">
            <thead class="bg-light">
                <tr>
                    <th class="text-center">อันดับ</th>
                    <th>ชื่อบริษัท/สถานศึกษา</th>
                    <th class="text-center">จำนวนเดือนที่บันทึก</th>
                    <th class="text-end">CO₂ ที่ลดได้ (kgCO₂e)</th>
                </tr>
            </thead>
            <tbody>
            <?php $rank = 1; foreach($top_list as $cname => $co2): ?>
                <tr class="<?= $id_of[$cname] == $company_id ? 'table-success fw-bold' : '' ?>">
                    <td class="text-center"><?= $rank++ ?></td>
                    <td>
                        <?= $cname ?>
                        <?php if($id_of[$cname] == $company_id): ?><span class="badge bg-success">องค์กรของคุณ</span><?php endif; ?>
                    </td>
                    <td class="text-center"><?= count($months_of[$cname]) ?> เดือน</td>
                    <td class="text-end"><?= number_format($co2, 2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="<?= $base ?>js/bootstrap.bundle.min.js"></script>
<?php include 'footer.php'; ?>

<script src="<?= $base ?>js/chart.umd.js"></script>
<script>
new Chart('monthChart', {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{label: 'CO₂ ที่ลดได้', data: <?= json_encode($chart_values) ?>, backgroundColor: '#198754'}]
    },
    options: {responsive: true, maintainAspectRatio: false, scales: {y: {beginAtZero: true}}}
});

new Chart('typeChart', {
    type: 'pie',
    data: {
        labels: <?= json_encode($type_labels, JSON_UNESCAPED_UNICODE) ?>,
        datasets: [{data: <?= json_encode($type_values) ?>, backgroundColor: ['#198754', '#20bec9']}]
    },
    options: {responsive: true, maintainAspectRatio: false, plugins: {legend: {position: 'bottom'}}}
});
</script>
</body>
</html>