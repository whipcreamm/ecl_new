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

$cid = (int)$_SESSION['company_id'];

// ตรวจสอบสิทธิ์ผู้ดูแลระบบ (Admin: role_id = 1)
$sql_check_admin = "SELECT * FROM company WHERE id = $cid";
$query_admin = mysqli_query($conn, $sql_check_admin);
$me = mysqli_fetch_assoc($query_admin);

if (!$me || $me['role_id'] != 1) {
    header('location: ../login.php');
    exit();
}

// 1. ดึงรายชื่อบริษัทสมาชิกทั่วไป (role_id = 2)
$company_names = array();
$company_co2 = array();
$company_elec = array();

$sql_companies = "SELECT * FROM company WHERE role_id = 2";
$query_companies = mysqli_query($conn, $sql_companies);

while ($comp = mysqli_fetch_assoc($query_companies)) {
    $comp_id = (int)$comp['id'];
    $company_names[$comp_id] = $comp['name'];
    $company_co2[$comp_id] = 0;
    $company_elec[$comp_id] = 0;
}
$total_companies_count = count($company_names);

// 2. ดึงข้อมูลบันทึกค่าน้ำ-ไฟทั้งหมด แล้วคำนวณยอดรวมในระบบ
$total_co2_saved = 0;
$total_elec_saved = 0;
$total_water_saved = 0;

$sql_logs = "SELECT * FROM utility_logs";
$query_logs = mysqli_query($conn, $sql_logs);

while ($log = mysqli_fetch_assoc($query_logs)) {
    $co2_saved = (float)$log['co2e_save'];
    $save_unit = (float)$log['save_unit'];
    $log_type = $log['type'];
    $log_cid = (int)$log['company_id'];

    $total_co2_saved = $total_co2_saved + $co2_saved;

    if ($log_type == 'elec') {
        $total_elec_saved = $total_elec_saved + $save_unit;
    } else if ($log_type == 'water') {
        $total_water_saved = $total_water_saved + $save_unit;
    }

    // รวมสถิติรายบริษัท
    if (isset($company_names[$log_cid])) {
        $company_co2[$log_cid] = $company_co2[$log_cid] + $co2_saved;
        if ($log_type == 'elec') {
            $company_elec[$log_cid] = $company_elec[$log_cid] + $save_unit;
        }
    }
}

// จัดเรียงอันดับจากมากไปหาน้อย และเลือก 5 อันดับแรก
arsort($company_co2);
arsort($company_elec);
$top_co2_list = array_slice($company_co2, 0, 5, true);
$top_elec_list = array_slice($company_elec, 0, 5, true);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="30">
    <title>Ecolyn Kiosk Dashboard</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { 
            background: linear-gradient(135deg, #0e3d2c, #175e43); 
            color: #ffffff; 
            min-height: 100vh; 
        }
        .kiosk-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
        }
    </style>
</head>
<body class="p-4">
    <div class="container">
        <!-- แถบส่วนหัว Kiosk -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold mb-0">Ecolyn Dashboard Kiosk</h1>
                <p class="text-white-50 mb-0">ภาพรวมการประหยัดพลังงานและลดคาร์บอน (รีเฟรชข้อมูลอัตโนมัติทุก 30 วินาที)</p>
            </div>
            <a href="dashboard.php" class="btn btn-outline-light">กลับสู่ระบบจัดการ</a>
        </div>

        <!-- การ์ดตัวเลขสรุปภาพรวม 4 กล่อง -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="kiosk-card p-4 text-center">
                    <h5 class="text-white-50">บริษัทเข้าร่วม</h5>
                    <h1 class="display-5 fw-bold text-info"><?php echo number_format($total_companies_count); ?></h1>
                    <span>บริษัท</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="kiosk-card p-4 text-center">
                    <h5 class="text-white-50">CO₂ ลดได้สะสม</h5>
                    <h1 class="display-5 fw-bold text-warning"><?php echo number_format($total_co2_saved, 2); ?></h1>
                    <span>kgCO₂e</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="kiosk-card p-4 text-center">
                    <h5 class="text-white-50">ประหยัดไฟฟ้า</h5>
                    <h1 class="display-5 fw-bold text-success"><?php echo number_format($total_elec_saved, 2); ?></h1>
                    <span>kWh</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="kiosk-card p-4 text-center">
                    <h5 class="text-white-50">ประหยัดน้ำประปา</h5>
                    <h1 class="display-5 fw-bold text-primary"><?php echo number_format($total_water_saved, 2); ?></h1>
                    <span>ลบ.ม.</span>
                </div>
            </div>
        </div>

        <!-- ตารางอันดับชั้นนำ 5 อันดับ -->
        <div class="row g-4">
            <div class="col-md-6">
                <div class="kiosk-card p-4">
                    <h4 class="fw-bold mb-3">อันดับบริษัทที่ลด CO₂ ได้สูงสุด</h4>
                    <?php 
                    $rank_co2 = 1; 
                    foreach ($top_co2_list as $comp_id => $co2_val) { 
                        $comp_title = isset($company_names[$comp_id]) ? $company_names[$comp_id] : '-';
                    ?>
                        <div class="d-flex justify-content-between align-items-center p-3 border-bottom border-secondary">
                            <span>#<?php echo $rank_co2; ?> <?php echo htmlspecialchars($comp_title); ?></span>
                            <span class="badge bg-success fs-6"><?php echo number_format($co2_val, 2); ?> kgCO₂e</span>
                        </div>
                    <?php 
                        $rank_co2 = $rank_co2 + 1;
                    } 
                    ?>
                    <?php if (count($top_co2_list) == 0) { ?>
                        <p class="text-white-50 text-center py-3 mb-0">ยังไม่มีข้อมูลสถิติ</p>
                    <?php } ?>
                </div>
            </div>

            <div class="col-md-6">
                <div class="kiosk-card p-4">
                    <h4 class="fw-bold mb-3">อันดับบริษัทที่ประหยัดไฟฟ้าสูงสุด</h4>
                    <?php 
                    $rank_elec = 1; 
                    foreach ($top_elec_list as $comp_id => $kwh_val) { 
                        $comp_title = isset($company_names[$comp_id]) ? $company_names[$comp_id] : '-';
                    ?>
                        <div class="d-flex justify-content-between align-items-center p-3 border-bottom border-secondary">
                            <b>#<?php echo $rank_elec; ?> <?php echo htmlspecialchars($comp_title); ?></b>
                            <span class="badge bg-warning text-dark fs-6"><?php echo number_format($kwh_val, 2); ?> kWh</span>
                        </div>
                    <?php 
                        $rank_elec = $rank_elec + 1;
                    } 
                    ?>
                    <?php if (count($top_elec_list) == 0) { ?>
                        <p class="text-white-50 text-center py-3 mb-0">ยังไม่มีข้อมูลสถิติ</p>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
