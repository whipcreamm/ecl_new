<?php
// เริ่มต้นเซสชัน
session_start();

// ดึงไฟล์เชื่อมต่อฐานข้อมูล
include 'config.php';

// ตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่ ถ้ายังไม่ล็อกอินให้กลับไปหน้า login
if (!isset($_SESSION['company_id'])) {
    header('location: login.php');
    exit();
}

$company_id = (int)$_SESSION['company_id'];

// 1. ตรวจสอบสิทธิ์ผู้ใช้งาน (ต้องเป็น Admin หรือสมาชิกที่มีแพ็กเกจที่ยังไม่หมดอายุ)
$sql_user_company = "SELECT * FROM company WHERE id = $company_id";
$query_user_company = mysqli_query($conn, $sql_user_company);
$company = mysqli_fetch_assoc($query_user_company);

$is_admin = ($company && isset($company['role_id']) && $company['role_id'] == 1);

$sql_pack_check = "SELECT * FROM members WHERE company_id = $company_id AND status_mem = 'Y' AND (end_mem IS NULL OR end_mem >= CURDATE())";
$query_pack_check = mysqli_query($conn, $sql_pack_check);
$has_package = mysqli_num_rows($query_pack_check);

if (!$is_admin && $has_package == 0) {
    header('location: pack.php');
    exit();
}

// 2. ดึงค่าสัมประสิทธิ์การปล่อยก๊าซเรือนกระจก (Emission Factor)
$settings = array();
$sql_settings = "SELECT * FROM eco_setting";
$query_settings = mysqli_query($conn, $sql_settings);

while ($setting_row = mysqli_fetch_assoc($query_settings)) {
    $s_key = $setting_row['setting_key'];
    $s_val = $setting_row['setting_value'];
    $settings[$s_key] = $s_val;
}

if (isset($settings['factor_elec'])) {
    $factor_electricity = (float)$settings['factor_elec'];
} else {
    $factor_electricity = 0.4999;
}

if (isset($settings['factor_water'])) {
    $factor_water = (float)$settings['factor_water'];
} else {
    $factor_water = 0.7;
}

// 3. จัดการตัวกรองช่วงเดือนและประเภทการใช้งาน
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

if ($from_month > $to_month) {
    $temp_month = $from_month;
    $from_month = $to_month;
    $to_month = $temp_month;
}

if (isset($_GET['type'])) {
    $utility_type = $_GET['type'];
} else {
    $utility_type = '';
}

// 4. คำนวณสรุปข้อมูลสถิติ
$monthly = array();        // สรุปข้อมูลรายเดือนของบริษัทเรา
$type_co2 = array();       // สัดส่วน CO2 แยกตามประเภท (ทั้งระบบ)
$co2_by_company = array(); // รวม CO2 ของแต่ละบริษัท
$months_by_company = array(); // เดือนที่บันทึกของแต่ละบริษัท
$id_by_company = array();     // รหัสบริษัท

$total_baht_saved = 0;
$total_carbon_used = 0;
$total_carbon_previous = 0;

$sql_logs = "SELECT * FROM utility_logs ORDER BY record_date ASC";
$log_result = mysqli_query($conn, $sql_logs);

while ($log = mysqli_fetch_assoc($log_result)) {
    $log_date = $log['record_date'];
    $month = date('Y-m', strtotime($log_date));

    // กรองช่วงเดือน
    if ($month < $from_month || $month > $to_month) {
        continue;
    }

    // กรองประเภทการใช้งาน
    if ($utility_type != '' && $log['type'] != $utility_type) {
        continue;
    }

    $log_type = $log['type'];
    $co2_save_val = (float)$log['co2e_save'];

    // รวมสัดส่วน CO2 ทั้งระบบตามประเภท
    if (!isset($type_co2[$log_type])) {
        $type_co2[$log_type] = 0;
    }
    $type_co2[$log_type] = $type_co2[$log_type] + $co2_save_val;

    // ดึงชื่อบริษัทเพื่อรวบรวมอันดับ
    $log_cid = (int)$log['company_id'];
    $sql_c = "SELECT * FROM company WHERE id = $log_cid";
    $query_c = mysqli_query($conn, $sql_c);
    $c_row = mysqli_fetch_assoc($query_c);
    $c_name = $c_row ? $c_row['name'] : 'ไม่ระบุ';

    if (!isset($co2_by_company[$c_name])) {
        $co2_by_company[$c_name] = 0;
        $id_by_company[$c_name] = $log_cid;
    }
    $co2_by_company[$c_name] = $co2_by_company[$c_name] + $co2_save_val;
    $months_by_company[$c_name][$month] = 1;

    // ประมวลผลเฉพาะข้อมูลของบริษัทที่กำลังล็อกอินอยู่
    if ($log_cid != $company_id) {
        continue;
    }

    if (!isset($monthly[$month])) {
        $monthly[$month] = array(
            'elec_use'   => 0,
            'elec_prev'  => 0,
            'water_use'  => 0,
            'water_prev' => 0,
            'baht'       => 0
        );
    }

    if ($log_type == 'elec') {
        $monthly[$month]['elec_use'] = $monthly[$month]['elec_use'] + (float)$log['amount'];
        $monthly[$month]['elec_prev'] = $monthly[$month]['elec_prev'] + (float)$log['prev_amount'];
    } else {
        $monthly[$month]['water_use'] = $monthly[$month]['water_use'] + (float)$log['amount'];
        $monthly[$month]['water_prev'] = $monthly[$month]['water_prev'] + (float)$log['prev_amount'];
    }

    $monthly[$month]['baht'] = $monthly[$month]['baht'] + (float)$log['save_unit'];
}

// เรียงลำดับเดือนจากใหม่ไปหาเก่าสำหรับตาราง
krsort($monthly);

// จัดเรียงอันดับบริษัทที่ลด CO2 ได้มากที่สุด 10 อันดับแรก
arsort($co2_by_company);
$top_list = array_slice($co2_by_company, 0, 10, true);

// คำนวณคาร์บอนที่ใช้ และคาร์บอนที่ลดได้ในแต่ละเดือน
foreach ($monthly as $month_key => $data) {
    $used_carbon = ($data['elec_use'] * $factor_electricity) + ($data['water_use'] * $factor_water);
    $previous_carbon = ($data['elec_prev'] * $factor_electricity) + ($data['water_prev'] * $factor_water);
    $saved_carbon = $previous_carbon - $used_carbon;

    if ($previous_carbon > 0) {
        $percent_saved = ($saved_carbon / $previous_carbon) * 100;
        $percent_used = ($used_carbon / $previous_carbon) * 100;
    } else {
        $percent_saved = 0;
        $percent_used = 0;
    }

    $monthly[$month_key]['carbon_used'] = $used_carbon;
    $monthly[$month_key]['carbon_saved'] = $saved_carbon;
    $monthly[$month_key]['percent_saved'] = $percent_saved;
    $monthly[$month_key]['percent_used'] = $percent_used;

    $total_carbon_used = $total_carbon_used + $used_carbon;
    $total_carbon_previous = $total_carbon_previous + $previous_carbon;
    $total_baht_saved = $total_baht_saved + $data['baht'];
}

$total_carbon_saved = $total_carbon_previous - $total_carbon_used;

// 5. คำนวณแต้มสะสมและอันดับของบริษัทตัวเอง
$my_points = 0;
$sql_my_pts = "SELECT * FROM eco_point_ledger WHERE company_id = $company_id";
$query_my_pts = mysqli_query($conn, $sql_my_pts);

while ($pt = mysqli_fetch_assoc($query_my_pts)) {
    $my_points = $my_points + (int)$pt['point'];
}

$my_rank = '-';
$current_rank = 1;
foreach ($co2_by_company as $c_name => $c_co2) {
    if (isset($id_by_company[$c_name]) && $id_by_company[$c_name] == $company_id) {
        $my_rank = '#' . $current_rank;
        break;
    }
    $current_rank = $current_rank + 1;
}

// 6. เตรียมข้อมูลสำหรับส่งให้กราฟ Chart.js (เรียงจากเดือนเก่าไปหาใหม่)
$chart_labels = array();
$chart_values = array();

$monthly_asc = array_reverse($monthly, true);
foreach ($monthly_asc as $m_name => $m_data) {
    $chart_labels[] = $m_name;
    $chart_values[] = round($m_data['carbon_saved'], 2);
}

$type_display_names = array('elec' => 'ไฟฟ้า', 'water' => 'น้ำประปา');
$pie_labels = array();
$pie_values = array();

foreach ($type_co2 as $t_key => $t_sum) {
    if (isset($type_display_names[$t_key])) {
        $pie_labels[] = $type_display_names[$t_key];
    } else {
        $pie_labels[] = $t_key;
    }
    $pie_values[] = round($t_sum, 2);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ดสรุปผลการลดคาร์บอน - Ecolyn</title>
</head>
<body class="bg-light">
    <!-- แถบเมนูด้านบน -->
    <?php include 'navbar.php'; ?>

    <div class="container mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0">รายงานสรุปผลการลดคาร์บอน (รายเดือน)</h4>
            <?php if ($is_admin) { ?>
                <a href="export.php?from_month=<?php echo $from_month; ?>&to_month=<?php echo $to_month; ?>&type=<?php echo $utility_type; ?>" class="btn btn-outline-success btn-sm">
                    ส่งออกข้อมูลรายเดือน (CSV)
                </a>
            <?php } ?>
        </div>

        <!-- การ์ดสรุปตัวเลขสถิติภาพรวม -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card p-3 text-center shadow-sm h-100 bg-success text-white">
                    <h3 class="fw-bold mb-0"><?php echo number_format($total_carbon_saved, 2); ?></h3>
                    <small>คาร์บอนที่ลดได้สะสม (kgCO₂e)</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3 text-center shadow-sm h-100">
                    <h3 class="fw-bold mb-0 text-warning"><?php echo number_format($total_baht_saved, 2); ?></h3>
                    <small class="text-muted">ประหยัดค่าใช้จ่ายรวม (บาท)</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3 text-center shadow-sm h-100">
                    <h3 class="fw-bold mb-0 text-success"><?php echo number_format($my_points); ?></h3>
                    <small class="text-muted">แต้มสะสมขององค์กร</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3 text-center shadow-sm h-100">
                    <h3 class="fw-bold mb-0 text-primary"><?php echo $my_rank; ?></h3>
                    <small class="text-muted">อันดับในตาราง</small>
                </div>
            </div>
        </div>

        <!-- กล่องตัวกรองค้นหาตามเดือนและประเภท -->
        <div class="card p-3 shadow-sm mb-4">
            <form class="row g-2 align-items-end" method="get" action="dashboard.php">
                <div class="col-md-4">
                    <label class="form-label small">ตั้งแต่ประจำเดือน</label>
                    <input type="month" name="from_month" class="form-control form-control-sm" value="<?php echo $from_month; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">ถึงประจำเดือน</label>
                    <input type="month" name="to_month" class="form-control form-control-sm" value="<?php echo $to_month; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">ประเภท</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">ทั้งหมด</option>
                        <option value="elec" <?php if ($utility_type == 'elec') { echo 'selected'; } ?>>ไฟฟ้า</option>
                        <option value="water" <?php if ($utility_type == 'water') { echo 'selected'; } ?>>น้ำประปา</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success btn-sm w-100">ค้นหารายเดือน</button>
                </div>
            </form>
        </div>

        <!-- กราฟสถิติ -->
        <div class="row g-3 mb-4">
            <div class="col-lg-7">
                <div class="card p-3 shadow-sm h-100">
                    <h6 class="fw-bold">ปริมาณ CO₂ ที่ลดได้รายเดือน (kgCO₂e)</h6>
                    <div style="height: 260px;">
                        <canvas id="monthChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card p-3 shadow-sm h-100">
                    <h6 class="fw-bold">สัดส่วน CO₂ ลดได้แยกตามประเภท</h6>
                    <div style="height: 260px;">
                        <canvas id="typeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- ตารางสรุปรายเดือน -->
        <div class="card p-3 shadow-sm mb-4">
            <h5 class="fw-bold mb-3">สรุปผลการใช้งานรายเดือน</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle text-center mb-1">
                    <thead class="bg-light">
                        <tr>
                            <th>ประจำเดือน</th>
                            <th>ใช้ไฟ (kWh)</th>
                            <th>ใช้น้ำ (ลบ.ม.)</th>
                            <th>ประหยัด (บาท)</th>
                            <th>คาร์บอนที่ปล่อย (kg)</th>
                            <th>คาร์บอนที่ลดได้ (kg)</th>
                            <th>สัดส่วนลด (%)</th>
                            <th>สัดส่วนใช้ (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    foreach ($monthly as $month_str => $m) { 
                        $is_baht_saved = ($m['baht'] >= 0);
                        $is_carbon_saved = ($m['carbon_saved'] >= 0);
                    ?>
                        <tr>
                            <td><?php echo date('m/Y', strtotime($month_str . '-01')); ?></td>
                            <td><?php echo number_format($m['elec_use'], 2); ?></td>
                            <td><?php echo number_format($m['water_use'], 2); ?></td>
                            <td class="<?php echo $is_baht_saved ? 'text-success' : 'text-danger'; ?>">
                                <?php echo number_format($m['baht'], 2); ?>
                            </td>
                            <td><?php echo number_format($m['carbon_used'], 2); ?></td>
                            <td class="<?php echo $is_carbon_saved ? 'text-success' : 'text-danger'; ?>">
                                <?php echo number_format($m['carbon_saved'], 2); ?>
                            </td>
                            <td class="<?php echo $is_carbon_saved ? 'text-success' : 'text-danger'; ?>">
                                <?php echo number_format($m['percent_saved'], 1); ?>%
                            </td>
                            <td><?php echo number_format($m['percent_used'], 1); ?>%</td>
                        </tr>
                    <?php } ?>
                    <?php if (count($monthly) == 0) { ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-3">ไม่พบข้อมูลบันทึกในช่วงเวลานี้</td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
            <small class="text-muted mt-2 d-block">
                * สัดส่วนลด (%) = คาร์บอนที่ลดได้ ÷ คาร์บอนเดือนก่อน | สัดส่วนใช้ (%) = คาร์บอนเดือนนี้ ÷ คาร์บอนเดือนก่อน
            </small>
        </div>

        <!-- ตารางอันดับสมาชิกที่ลด CO2 สูงสุด -->
        <div class="card p-3 shadow-sm">
            <h6 class="fw-bold mb-3">สมาชิกที่ช่วยลด CO₂ สูงสุดในช่วงเดือนที่เลือก</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="text-center" style="width: 100px;">อันดับ</th>
                            <th>ชื่อบริษัท/สถานศึกษา</th>
                            <th class="text-center" style="width: 200px;">จำนวนเดือนที่บันทึก</th>
                            <th class="text-end" style="width: 220px;">CO₂ ที่ลดได้ (kgCO₂e)</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    $rank_counter = 1; 
                    foreach ($top_list as $company_title => $co2_val) { 
                        $is_current_user = (isset($id_by_company[$company_title]) && $id_by_company[$company_title] == $company_id);
                        $recorded_months_count = isset($months_by_company[$company_title]) ? count($months_by_company[$company_title]) : 0;
                    ?>
                        <tr class="<?php if ($is_current_user) { echo 'table-success fw-bold'; } ?>">
                            <td class="text-center"><?php echo $rank_counter; ?></td>
                            <td>
                                <?php echo htmlspecialchars($company_title); ?>
                                <?php if ($is_current_user) { ?>
                                    <span class="badge bg-success ms-1">องค์กรของคุณ</span>
                                <?php } ?>
                            </td>
                            <td class="text-center"><?php echo $recorded_months_count; ?> เดือน</td>
                            <td class="text-end"><?php echo number_format($co2_val, 2); ?></td>
                        </tr>
                    <?php 
                        $rank_counter = $rank_counter + 1;
                    } 
                    ?>
                    <?php if (count($top_list) == 0) { ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">ยังไม่มีข้อมูลสถิติ</td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ส่วนท้ายเว็บไซต์ -->
    <?php include 'footer.php'; ?>
    <script src="<?php echo $base; ?>js/bootstrap.bundle.min.js"></script>

    <!-- กราฟ Chart.js -->
    <script src="<?php echo $base; ?>js/chart.umd.js"></script>
    <script>
        // กราฟแท่งแสดง CO2 ที่ลดได้ในแต่ละเดือน
        const barLabels = <?php echo json_encode($chart_labels); ?>;
        const barValues = <?php echo json_encode($chart_values); ?>;

        new Chart('monthChart', {
            type: 'bar',
            data: {
                labels: barLabels,
                datasets: [{
                    label: 'CO₂ ที่ลดได้ (kgCO₂e)',
                    data: barValues,
                    backgroundColor: '#198754'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // กราฟวงกลมแสดงสัดส่วนแยกตามประเภท (ไฟฟ้า / น้ำประปา)
        const pieLabels = <?php echo json_encode($pie_labels, JSON_UNESCAPED_UNICODE); ?>;
        const pieValues = <?php echo json_encode($pie_values); ?>;

        new Chart('typeChart', {
            type: 'pie',
            data: {
                labels: pieLabels,
                datasets: [{
                    data: pieValues,
                    backgroundColor: ['#198754', '#20bec9']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
