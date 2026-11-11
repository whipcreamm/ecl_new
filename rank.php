<?php
session_start();
include 'config.php';

if(!isset($_SESSION['company_id'])){
    header('location: login.php');
    exit();
}
$cid = $_SESSION['company_id'];

// รวมแต้มของแต่ละบริษัท
$points = [];
$r = mysqli_query($conn, "select * from eco_point_ledger");
while($p = mysqli_fetch_assoc($r)){
    if(!isset($points[$p['company_id']])) $points[$p['company_id']] = 0;
    $points[$p['company_id']] += $p['point'];
}

// รวม CO2 ของแต่ละบริษัท
$co2 = [];
$r = mysqli_query($conn, "select * from utility_logs");
while($l = mysqli_fetch_assoc($r)){
    if(!isset($co2[$l['company_id']])) $co2[$l['company_id']] = 0;
    $co2[$l['company_id']] += $l['co2e_save'];
}

// บริษัทที่เป็นสมาชิก (role 2, ใช้งานอยู่, มีแพ็กเกจ)
$rank_list = [];
$r = mysqli_query($conn, "select * from company where role_id=2 and status='Y'");
while($c = mysqli_fetch_assoc($r)){
    $has_pack = mysqli_num_rows(mysqli_query($conn, "select * from members where company_id={$c['id']} and pack_id != 0"));
    if($has_pack == 0) continue;

    $c['points'] = $points[$c['id']] ?? 0;
    $c['co2'] = $co2[$c['id']] ?? 0;
    $rank_list[] = $c;
}

// เรียงแต้มมาก -> น้อย (แต้มเท่ากันดู co2)
// ใช้ array_multisort ไม่ต้องเขียน function
$sort_points = [];
$sort_co2 = [];
foreach($rank_list as $c){
    $sort_points[] = $c['points'];
    $sort_co2[] = $c['co2'];
}
array_multisort($sort_points, SORT_DESC, $sort_co2, SORT_DESC, $rank_list);
$rank_list = array_slice($rank_list, 0, 50);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อันดับผู้ช่วยลดคาร์บอน</title>
</head>
<body class="bg-light">
<?php include 'navbar.php'; ?>

<div class="container px-4 mb-5">
    <h4 class="fw-bold mb-1">อันดับผู้ช่วยลดคาร์บอน</h4>
    <p class="text-muted small mb-4">ตารางจัดอันดับองค์กรที่ร่วมลดก๊าซเรือนกระจกและสะสมแต้ม</p>

    <div class="card p-3 shadow-sm">
        <table class="table table-hover table-bordered align-middle">
            <thead class="bg-light">
                <tr>
                    <th class="text-center">อันดับ</th>
                    <th>ชื่อองค์กร / บริษัท</th>
                    <th class="text-end">แต้มสะสม</th>
                    <th class="text-end">CO₂ ที่ลดได้ (kgCO₂e)</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($rank_list as $index => $c): ?>
                <tr class="<?= $c['id'] == $cid ? 'table-success fw-bold' : '' ?>">
                    <td class="text-center">
                        <?php if($index == 0): ?>🥇 1
                        <?php elseif($index == 1): ?>🥈 2
                        <?php elseif($index == 2): ?>🥉 3
                        <?php else: ?>#<?= $index + 1 ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= $c['name'] ?>
                        <?php if($c['id'] == $cid): ?><span class="badge bg-success">องค์กรของคุณ</span><?php endif; ?>
                    </td>
                    <td class="text-end"><?= number_format($c['points']) ?> แต้ม</td>
                    <td class="text-end"><?= number_format($c['co2'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>
<script src="<?= $base ?>js/bootstrap.bundle.min.js"></script>
</body>
</html>