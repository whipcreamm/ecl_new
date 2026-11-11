<?php
// เริ่มต้นเซสชัน
session_start();

// ดึงไฟล์เชื่อมต่อฐานข้อมูล
include '../config.php';

// ตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่ ถ้ายังไม่ล็อกอินให้กลับไปหน้า login
$company_id = 0;
if (isset($_SESSION['company_id'])) {
    $company_id = (int)$_SESSION['company_id'];
}

$sql_company = "SELECT * FROM company WHERE id = $company_id";
$company_query = mysqli_query($conn, $sql_company);
$company = mysqli_fetch_assoc($company_query);

if (!$company || $company['role_id'] != 1) {
    header('location: ../login.php');
    exit();
}

// รับค่าช่วงเดือนที่เลือก (ค่าเริ่มต้นคือตั้งแต่ต้นปีจนถึงเดือนปัจจุบัน)
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

// 1. คำนวณตัวเลขสถิติบนการ์ดสรุป
$sql_count_members = "SELECT * FROM company";
$total_members = mysqli_num_rows(mysqli_query($conn, $sql_count_members));

$sql_standard = "SELECT * FROM members WHERE status_mem = 'Y' AND (end_mem IS NULL OR end_mem >= CURDATE()) AND pack_id IN (SELECT id FROM pack WHERE name LIKE '%standard%' OR name LIKE '%มาตรฐาน%')";
$total_standard = mysqli_num_rows(mysqli_query($conn, $sql_standard));

$sql_premium = "SELECT * FROM members WHERE status_mem = 'Y' AND (end_mem IS NULL OR end_mem >= CURDATE()) AND pack_id IN (SELECT id FROM pack WHERE name LIKE '%premium%' OR name LIKE '%พรีเมี่%')";
$total_premium = mysqli_num_rows(mysqli_query($conn, $sql_premium));

$sql_pending_orders = "SELECT * FROM product_order WHERE status = 'pending'";
$total_new_orders = mysqli_num_rows(mysqli_query($conn, $sql_pending_orders));

// 2. คำนวณยอดขายสินค้าและยอดขายรายเดือน
$monthly_income = array();
$product_sales = array();
$product_shops = array();

$sql_orders = "SELECT * FROM product_order WHERE status <> 'cancel' ORDER BY create_at ASC";
$order_result = mysqli_query($conn, $sql_orders);

while ($order = mysqli_fetch_assoc($order_result)) {
    $order_month = date('Y-m', strtotime($order['create_at']));

    if ($order_month < $from_month || $order_month > $to_month) {
        continue;
    }

    $order_qty = (int)$order['qty'];

    if (!isset($monthly_income[$order_month])) {
        $monthly_income[$order_month] = 0;
    }
    $monthly_income[$order_month] = $monthly_income[$order_month] + $order_qty;

    // ดึงข้อมูลสินค้าเพื่อเก็บสถิติยอดขาย
    $pid = (int)$order['product_id'];
    $sql_pr = "SELECT * FROM product WHERE id_pro = $pid";
    $pr_query = mysqli_query($conn, $sql_pr);
    $pr_data = mysqli_fetch_assoc($pr_query);

    if ($pr_data) {
        $product_name = $pr_data['name'];
        if (!isset($product_sales[$product_name])) {
            $product_sales[$product_name] = 0;
            $product_shops[$product_name] = isset($pr_data['shop_name']) ? $pr_data['shop_name'] : '-';
        }
        $product_sales[$product_name] = $product_sales[$product_name] + $order_qty;
    }
}

ksort($monthly_income);
arsort($product_sales);
$top_product_sales = array_slice($product_sales, 0, 8, true);

$chart_labels = array_keys($monthly_income);
$chart_values = array_values($monthly_income);

// 3. คำนวณสรุปผลการลดคาร์บอนและการประหยัดพลังงาน
$carbon_summary = array();
$company_co2_totals = array();
$company_recorded_months = array();
$total_system_co2 = 0;

$sql_logs = "SELECT * FROM utility_logs ORDER BY record_date ASC";
$log_result = mysqli_query($conn, $sql_logs);

while ($log = mysqli_fetch_assoc($log_result)) {
    $log_month = date('Y-m', strtotime($log['record_date']));

    if ($log_month < $from_month || $log_month > $to_month) {
        continue;
    }

    if (!isset($carbon_summary[$log_month])) {
        $carbon_summary[$log_month] = array(
            'companies'  => array(),
            'elec_use'   => 0,
            'elec_save'  => 0,
            'water_use'  => 0,
            'water_save' => 0,
            'co2'        => 0
        );
    }

    $log_cid = (int)$log['company_id'];
    $carbon_summary[$log_month]['companies'][$log_cid] = 1;

    $amount = (float)$log['amount'];
    $save_unit = (float)$log['save_unit'];
    $co2_saved = (float)$log['co2e_save'];

    if ($log['type'] == 'elec') {
        $carbon_summary[$log_month]['elec_use'] = $carbon_summary[$log_month]['elec_use'] + $amount;
        $carbon_summary[$log_month]['elec_save'] = $carbon_summary[$log_month]['elec_save'] + $save_unit;
    } else {
        $carbon_summary[$log_month]['water_use'] = $carbon_summary[$log_month]['water_use'] + $amount;
        $carbon_summary[$log_month]['water_save'] = $carbon_summary[$log_month]['water_save'] + $save_unit;
    }
    $carbon_summary[$log_month]['co2'] = $carbon_summary[$log_month]['co2'] + $co2_saved;

    // รวบรวมสถิติรายบริษัท
    $sql_c = "SELECT * FROM company WHERE id = $log_cid";
    $c_query = mysqli_query($conn, $sql_c);
    $c_data = mysqli_fetch_assoc($c_query);
    $company_name = $c_data ? $c_data['name'] : 'ไม่ระบุ';

    if (!isset($company_co2_totals[$company_name])) {
        $company_co2_totals[$company_name] = 0;
    }
    $company_co2_totals[$company_name] = $company_co2_totals[$company_name] + $co2_saved;
    $company_recorded_months[$company_name][$log_month] = 1;

    $total_system_co2 = $total_system_co2 + $co2_saved;
}

krsort($carbon_summary);
arsort($company_co2_totals);
$top_co2_companies = array_slice($company_co2_totals, 0, 10, true);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ดภาพรวมระบบ - Ecolyn Admin</title>
</head>
<body class="bg-light">
    <!-- แถบเมนูด้านบน -->
    <?php include '../navbar.php'; ?>

    <div class="container mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0">แดชบอร์ดภาพรวมระบบ (รายเดือน)</h4>
            <div>
                <a href="kiosk.php" class="btn btn-info btn-sm text-dark fw-semibold">แดชบอร์ดแสดงผลอัตโนมัติ (Kiosk)</a>
                <a href="export.php" class="btn btn-success btn-sm ms-1">ส่งออกรายงาน (CSV)</a>
            </div>
        </div>

        <!-- กล่องตัวกรองช่วงเดือน -->
        <div class="card p-3 shadow-sm mb-4">
            <form class="row g-2 align-items-end" method="get" action="dashboard.php">
                <div class="col-md-5">
                    <label class="form-label small">ตั้งแต่ประจำเดือน</label>
                    <input type="month" name="from_month" class="form-control form-control-sm" value="<?php echo $from_month; ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label small">ถึงประจำเดือน</label>
                    <input type="month" name="to_month" class="form-control form-control-sm" value="<?php echo $to_month; ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success btn-sm w-100">ค้นหารายเดือน</button>
                </div>
            </form>
        </div>

        <!-- การ์ดตัวเลขสรุป 4 กล่อง -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card p-3 text-center shadow-sm h-100 bg-success text-white">
                    <h3 class="fw-bold mb-0"><?php echo number_format($total_members); ?></h3>
                    <small>สมาชิกทั้งหมด</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3 text-center shadow-sm h-100">
                    <h3 class="fw-bold mb-0 text-success"><?php echo number_format($total_standard); ?></h3>
                    <small class="text-muted">แพ็กเกจมาตรฐาน</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3 text-center shadow-sm h-100">
                    <h3 class="fw-bold mb-0 text-success"><?php echo number_format($total_premium); ?></h3>
                    <small class="text-muted">แพ็กเกจพรีเมียม</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card p-3 text-center shadow-sm h-100">
                    <h3 class="fw-bold mb-0 <?php if ($total_new_orders > 0) { echo 'text-danger'; } else { echo 'text-success'; } ?>">
                        <?php echo number_format($total_new_orders); ?>
                    </h3>
                    <small class="text-muted">คำสั่งซื้อสินค้าใหม่</small>
                </div>
            </div>
        </div>

        <!-- กราฟรายได้ และ ตารางยอดขายสินค้า -->
        <div class="row g-3 mb-4">
            <div class="col-lg-7">
                <div class="card p-3 shadow-sm h-100">
                    <h6 class="fw-bold">ยอดขายสินค้าตามช่วงเดือน (ชิ้น)</h6>
                    <div style="height: 260px;">
                        <canvas id="incomeChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card p-3 shadow-sm h-100">
                    <h6 class="fw-bold mb-3">ยอดขายสินค้าอันดับสูงสุด</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>ชื่อสินค้า</th>
                                    <th>ร้านค้า</th>
                                    <th class="text-end">ยอดขาย</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($top_product_sales as $p_title => $sale_count) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($p_title); ?></td>
                                    <td><?php echo htmlspecialchars(isset($product_shops[$p_title]) ? $product_shops[$p_title] : '-'); ?></td>
                                    <td class="text-end fw-bold text-success"><?php echo number_format($sale_count); ?> ชิ้น</td>
                                </tr>
                            <?php } ?>
                            <?php if (count($top_product_sales) == 0) { ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">ยังไม่มีข้อมูลยอดขาย</td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ตารางสรุปการลดคาร์บอนและการประหยัดพลังงานรายเดือน -->
        <div class="card p-3 shadow-sm mb-4">
            <h5 class="fw-bold mb-3">ตารางสรุปผลการลดคาร์บอนและการประหยัดรายเดือน</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle text-center mb-0">
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
                    <?php foreach ($carbon_summary as $month_str => $c_data) { ?>
                        <tr>
                            <td><?php echo date('m/Y', strtotime($month_str . '-01')); ?></td>
                            <td><?php echo count($c_data['companies']); ?> แห่ง</td>
                            <td><?php echo number_format($c_data['elec_use'], 2); ?></td>
                            <td class="text-success fw-bold"><?php echo number_format($c_data['elec_save'], 2); ?></td>
                            <td><?php echo number_format($c_data['water_use'], 2); ?></td>
                            <td class="text-success fw-bold"><?php echo number_format($c_data['water_save'], 2); ?></td>
                            <td class="text-success fw-bold"><?php echo number_format($c_data['co2'], 2); ?></td>
                        </tr>
                    <?php } ?>
                    <?php if (count($carbon_summary) == 0) { ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">ไม่พบข้อมูลบันทึกในช่วงเวลานี้</td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- อันดับสมาชิกที่ลด CO2 สูงสุด -->
        <div class="card p-3 shadow-sm">
            <h6 class="fw-bold mb-3">สมาชิกที่ช่วยลด CO₂ สูงสุดในช่วงเดือนที่เลือก</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="text-center" style="width: 100px;">อันดับ</th>
                            <th>ชื่อหน่วยงาน</th>
                            <th class="text-center" style="width: 200px;">จำนวนเดือนที่บันทึก</th>
                            <th class="text-end" style="width: 220px;">CO₂ ที่ลดได้ (kgCO₂e)</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    $rank_counter = 1; 
                    foreach ($top_co2_companies as $org_name => $co2_val) { 
                        $months_count = isset($company_recorded_months[$org_name]) ? count($company_recorded_months[$org_name]) : 0;
                    ?>
                        <tr>
                            <td class="text-center"><?php echo $rank_counter; ?></td>
                            <td class="fw-bold"><?php echo htmlspecialchars($org_name); ?></td>
                            <td class="text-center"><?php echo $months_count; ?> เดือน</td>
                            <td class="text-end text-success fw-bold"><?php echo number_format($co2_val, 2); ?></td>
                        </tr>
                    <?php 
                        $rank_counter = $rank_counter + 1;
                    } 
                    ?>
                    <?php if (count($top_co2_companies) == 0) { ?>
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
    <?php include '../footer.php'; ?>
    <script src="<?php echo $base; ?>js/bootstrap.bundle.min.js"></script>

    <!-- กราฟ Chart.js -->
    <script src="<?php echo $base; ?>js/chart.umd.js"></script>
    <script>
        const chartLabels = <?php echo json_encode($chart_labels); ?>;
        const chartValues = <?php echo json_encode($chart_values); ?>;

        new Chart('incomeChart', {
            type: 'bar',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'ยอดสั่งซื้อสินค้า (ชิ้น)',
                    data: chartValues,
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
    </script>
</body>
</html>
