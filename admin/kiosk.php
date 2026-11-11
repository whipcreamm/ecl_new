<?php
session_start();
include '../config.php';
if (!isset($_SESSION['company_id'])) {
    header('location:../login.php');
    exit();
}
$cid = (int)$_SESSION['company_id'];

$me = mysqli_fetch_assoc(mysqli_query($conn, "select * from company where id=$cid"));
if (!$me || $me['role_id'] != 1) {
    header('location:../login.php');
    exit();
}

// ---------- บริษัทที่เข้าร่วม (role_id = 2) ----------
$name_of = [];   // id => ชื่อบริษัท
$co2_of = [];    // id => รวม co2 ที่ลดได้
$elec_of = [];   // id => รวมไฟฟ้าที่ประหยัด
$company_result = mysqli_query($conn, "select * from company where role_id=2");
while($c = mysqli_fetch_assoc($company_result)){
    $name_of[$c['id']] = $c['name'];
    $co2_of[$c['id']] = 0;
    $elec_of[$c['id']] = 0;
}
$total_company = count($name_of);

// ---------- บันทึกค่าน้ำไฟ: ดึงดิบ แล้วบวกเองใน PHP ----------
$total_co2 = 0;
$total_elec = 0;
$total_water = 0;
$log_result = mysqli_query($conn, "select * from utility_logs");
while($l = mysqli_fetch_assoc($log_result)){
    $total_co2 += $l['co2e_save'];
    if($l['type'] == 'elec'){
        $total_elec += $l['save_unit'];
    }elseif($l['type'] == 'water'){
        $total_water += $l['save_unit'];
    }

    // อันดับ (เฉพาะบริษัท role_id = 2)
    if(isset($name_of[$l['company_id']])){
        $co2_of[$l['company_id']] += $l['co2e_save'];
        if($l['type'] == 'elec'){
            $elec_of[$l['company_id']] += $l['save_unit'];
        }
    }
}
arsort($co2_of);
arsort($elec_of);
$top_co2 = array_slice($co2_of, 0, 5, true);
$top_elec = array_slice($elec_of, 0, 5, true);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="30">
    <title>kiosk</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { background: linear-gradient(135deg, #0e3d2c, #175e43); color: #fff; min-height: 100vh; }
        .kiosk-card {
            background: rgba(255,255,255,.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,.2);
            border-radius: 16px;
        }
    </style>
</head>
<body class="p-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold mb-0">Ecolyn Dashboard Kiosk</h1>
                <p class="text-white-50">ภาพรวมการประหยัดพลังงานและลดคาร์บอน (รีเฟรชอัตโนมัติทุก 30 วินาที)</p>
            </div>
            <a href="dashboard.php" class="btn btn-outline-light">กลับสู่ระบบ</a>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="kiosk-card p-4 text-center">
                    <h5 class="text-white-50">บริษัทเข้าร่วม</h5>
                    <h1 class="display-5 fw-bold text-info"><?= number_format($total_company) ?></h1>
                    <span>บริษัท</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="kiosk-card p-4 text-center">
                    <h5 class="text-white-50">CO₂ ลดได้สะสม</h5>
                    <h1 class="display-5 fw-bold text-warning"><?= number_format($total_co2, 2) ?></h1>
                    <span>kgCO₂e</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="kiosk-card p-4 text-center">
                    <h5 class="text-white-50">ประหยัดไฟฟ้า</h5>
                    <h1 class="display-5 fw-bold text-success"><?= number_format($total_elec, 2) ?></h1>
                    <span>kWh</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="kiosk-card p-4 text-center">
                    <h5 class="text-white-50">ประหยัดน้ำประปา</h5>
                    <h1 class="display-5 fw-bold text-primary"><?= number_format($total_water, 2) ?></h1>
                    <span>ลบ.ม.</span>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="kiosk-card p-4">
                    <h4 class="fw-bold mb-3">อันดับบริษัทที่ลด CO₂ ได้สูงสุด</h4>
                    <?php $rank = 1; foreach($top_co2 as $id => $co2): ?>
                        <div class="d-flex justify-content-between align-items-center p-3 border-bottom border-secondary">
                            <span>#<?= $rank++ ?> <?= $name_of[$id] ?></span>
                            <span class="badge bg-success"><?= number_format($co2, 2) ?> kgCO₂e</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-md-6">
                <div class="kiosk-card p-4">
                    <h4 class="fw-bold mb-3">อันดับบริษัทที่ประหยัดไฟฟ้าสูงสุด</h4>
                    <?php $rank = 1; foreach($top_elec as $id => $kwh): ?>
                        <div class="d-flex justify-content-between align-items-center p-3 border-bottom border-secondary">
                            <b>#<?= $rank++ ?> <?= $name_of[$id] ?></b>
                            <span class="badge bg-warning text-dark"><?= number_format($kwh, 2) ?> kWh</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</body>
<script src="<?= $base ?>js/bootstrap.bundle.min.js"></script>
</html>