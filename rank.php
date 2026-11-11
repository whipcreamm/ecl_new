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

$cid = (int)$_SESSION['company_id'];

// 1. คำนวณแต้มสะสมทั้งหมดของแต่ละบริษัท
$company_points = array();
$sql_ledger = "SELECT * FROM eco_point_ledger";
$query_ledger = mysqli_query($conn, $sql_ledger);

while ($row = mysqli_fetch_assoc($query_ledger)) {
    $comp_id = (int)$row['company_id'];
    $point_val = (int)$row['point'];

    if (!isset($company_points[$comp_id])) {
        $company_points[$comp_id] = 0;
    }
    $company_points[$comp_id] = $company_points[$comp_id] + $point_val;
}

// 2. คำนวณปริมาณ CO2 ที่ลดได้ทั้งหมดของแต่ละบริษัท
$company_co2 = array();
$sql_logs = "SELECT * FROM utility_logs";
$query_logs = mysqli_query($conn, $sql_logs);

while ($row = mysqli_fetch_assoc($query_logs)) {
    $comp_id = (int)$row['company_id'];
    $co2_val = (float)$row['co2e_save'];

    if (!isset($company_co2[$comp_id])) {
        $company_co2[$comp_id] = 0;
    }
    $company_co2[$comp_id] = $company_co2[$comp_id] + $co2_val;
}

// 3. ดึงรายชื่อบริษัทที่เป็นสมาชิกทั่วไป (role_id = 2 และสถานะเปิดใช้งาน 'Y')
$rank_list = array();
$sql_companies = "SELECT * FROM company WHERE role_id = 2 AND status = 'Y'";
$query_companies = mysqli_query($conn, $sql_companies);

while ($comp = mysqli_fetch_assoc($query_companies)) {
    $comp_id = (int)$comp['id'];

    // ตรวจสอบว่าบริษัทนี้เคยสมัครหรือมีแพ็กเกจหรือไม่
    $sql_check_pack = "SELECT * FROM members WHERE company_id = $comp_id AND pack_id != 0";
    $pack_check_query = mysqli_query($conn, $sql_check_pack);
    $pack_count = mysqli_num_rows($pack_check_query);

    // ถ้าไม่มีแพ็กเกจ ให้ข้ามไป ไม่นำมาจัดอันดับ
    if ($pack_count == 0) {
        continue;
    }

    // กำหนดแต้มและ CO2
    if (isset($company_points[$comp_id])) {
        $comp['points'] = $company_points[$comp_id];
    } else {
        $comp['points'] = 0;
    }

    if (isset($company_co2[$comp_id])) {
        $comp['co2'] = $company_co2[$comp_id];
    } else {
        $comp['co2'] = 0;
    }

    $rank_list[] = $comp;
}

// 4. เรียงลำดับจากแต้มมากไปหาน้อย (ถ้าแต้มเท่ากัน ให้ดูปริมาณ CO2 ที่ลดได้)
$points_column = array();
$co2_column = array();

foreach ($rank_list as $item) {
    $points_column[] = $item['points'];
    $co2_column[] = $item['co2'];
}

if (count($rank_list) > 0) {
    array_multisort($points_column, SORT_DESC, $co2_column, SORT_DESC, $rank_list);
}

// เลือกแสดงเฉพาะ 50 อันดับแรก
$rank_list = array_slice($rank_list, 0, 50);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อันดับผู้ช่วยลดคาร์บอน - Ecolyn</title>
</head>
<body class="bg-light">
    <!-- แถบเมนูด้านบน -->
    <?php include 'navbar.php'; ?>

    <div class="container px-4 mb-5">
        <h4 class="fw-bold mb-1">อันดับผู้ช่วยลดคาร์บอน</h4>
        <p class="text-muted small mb-4">ตารางจัดอันดับองค์กรที่ร่วมลดก๊าซเรือนกระจกและสะสมแต้ม</p>

        <div class="card p-3 shadow-sm">
            <table class="table table-hover table-bordered align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="text-center" style="width: 100px;">อันดับ</th>
                        <th>ชื่อองค์กร / บริษัท</th>
                        <th class="text-end" style="width: 180px;">แต้มสะสม</th>
                        <th class="text-end" style="width: 220px;">CO₂ ที่ลดได้ (kgCO₂e)</th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                $index = 0;
                foreach ($rank_list as $company_item) { 
                    $is_my_company = ($company_item['id'] == $cid);
                ?>
                    <tr class="<?php if ($is_my_company) { echo 'table-success fw-bold'; } ?>">
                        <td class="text-center">
                            <?php 
                            if ($index == 0) {
                                echo '🥇 1';
                            } else if ($index == 1) {
                                echo '🥈 2';
                            } else if ($index == 2) {
                                echo '🥉 3';
                            } else {
                                echo '#' . ($index + 1);
                            }
                            ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($company_item['name']); ?>
                            <?php if ($is_my_company) { ?>
                                <span class="badge bg-success ms-1">องค์กรของคุณ</span>
                            <?php } ?>
                        </td>
                        <td class="text-end"><?php echo number_format($company_item['points']); ?> แต้ม</td>
                        <td class="text-end"><?php echo number_format($company_item['co2'], 2); ?></td>
                    </tr>
                <?php 
                    $index = $index + 1;
                } 
                ?>
                <?php if (count($rank_list) == 0) { ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">ยังไม่มีข้อมูลการจัดอันดับในขณะนี้</td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ส่วนท้ายเว็บไซต์ -->
    <?php include 'footer.php'; ?>
    <script src="<?php echo $base; ?>js/bootstrap.bundle.min.js"></script>
</body>
</html>
