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

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $id = (int)$_POST['id'];
    $action = $_POST['action'];
    $status = 'myuser';

    if($id != $cid){
        $status = $action;
        if($action == 'suspend'){
            mysqli_query($conn, "update company set status='N' where id=$id");
        }elseif($action == 'activate'){
            mysqli_query($conn, "update company set status='Y' where id=$id");
        }elseif($action == 'reset'){
            $pass = md5('12345678');
            mysqli_query($conn, "update company set pass='$pass' where id=$id");
            $msg = 'ผู้ดูแลระบบได้รีเซ็ตรหัสผ่านของคุณเป็น 12345678 แล้ว กรุณาเปลี่ยนรหัสผ่านทันที';
            mysqli_query($conn, "insert into noti(recip_id,message,link) values ($id,'$msg','pass.php')");
        }
    }
    header("location: user.php?status=$status");
    exit();
}
$st = $_GET['status'] ?? '';
$alerts = [
    'suspend'  => ['success', 'ระงับบัญชีเรียบร้อยแล้ว'],
    'activate' => ['success', 'เปิดใช้งานบัญชีเรียบร้อยแล้ว'],
    'reset'    => ['success', 'รีเซ็ตรหัสผ่านเป็น 12345678 เรียบร้อยแล้ว'],
    'myuser'   => ['danger', 'ไม่สามารถดำเนินการกับบัญชีของตัวเองได้']
];
$alert = $alerts[$st] ?? null;

$find = trim($_GET['find'] ?? '');
$kw = mysqli_real_escape_string($conn, $find);
$where = $find != '' ? "where name like '%$kw%' or email like '%$kw%'" : '';

// รวมแต้ม กับ CO2 ต่อบริษัท แล้วคำนวณใน PHP
$points = [];
$r = mysqli_query($conn, "select * from eco_point_ledger");
while($p = mysqli_fetch_assoc($r)){
    $points[$p['company_id']] = ($points[$p['company_id']] ?? 0) + $p['point'];
}
$co2 = [];
$r = mysqli_query($conn, "select * from utility_logs");
while($u = mysqli_fetch_assoc($r)){
    $co2[$u['company_id']] = ($co2[$u['company_id']] ?? 0) + $u['co2e_save'];
}

// สมาชิก (ค้นหาตาม $where) แล้วใส่แต้ม / CO2
$members = [];
$r = mysqli_query($conn, "select * from company $where");
while($m = mysqli_fetch_assoc($r)){
    $m['point'] = $points[$m['id']] ?? 0;
    $m['co2'] = $co2[$m['id']] ?? 0;
    $members[] = $m;
}
// เรียง แอดมินก่อน แล้วแต้มมากไปน้อย
usort($members, fn($a, $b) => [$a['role_id'], $b['point']] <=> [$b['role_id'], $a['point']]);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>user</title>
</head>
<body class="bg-light">
    <?php include '../navbar.php'; ?>

    <div class="container">
        <h3 class="fw-bold mt-3 text-center">จัดการสมาชิก</h3>

        <?php if($alert): ?>
            <div class="alert alert-<?= $alert[0] ?> p-2 text-center mt-3"><?= $alert[1] ?></div>
        <?php endif; ?>

        <form class="row g-2 mt-2 mb-3" method="get">
            <div class="col-md-8">
                <input name="find" class="form-control" placeholder="ค้นหาชื่อบริษัท หรืออีเมล..." value="<?= htmlspecialchars($find) ?>">
            </div>
            <div class="col-md-2"><button class="btn btn-green w-100">ค้นหา</button></div>
            <div class="col-md-2"><a href="user.php" class="btn btn-outline-secondary w-100">ล้างค้นหา</a></div>
        </form>

        <div class="card shadow-sm p-3">
            <table class="table table-hover table-bordered align-middle">
                <thead class="bg-light">
                    <tr class="text-center">
                        <th>ชื่อบริษัท</th>
                        <th>อีเมล</th>
                        <th>สิทธิ์</th>
                        <th>สถานะ</th>
                        <th>แต้ม</th>
                        <th>ลดคาร์บอน (kgCO₂e)</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($members as $m): ?>
                    <tr class="text-center">
                        <td class="text-start"><?= htmlspecialchars($m['name']) ?></td>
                        <td class="text-start"><?= htmlspecialchars($m['email']) ?></td>
                        <td>
                            <?php if($m['role_id'] == 1): ?>
                                <span class="badge bg-dark">แอดมิน</span>
                            <?php else: ?>
                                <span class="badge bg-primary">สมาชิก</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($m['status'] == 'Y'): ?>
                                <span class="badge bg-success">ปกติ</span>
                            <?php else: ?>
                                <span class="badge bg-danger">ระงับ</span>
                            <?php endif; ?>
                        </td>
                        <td><?= number_format($m['point']) ?></td>
                        <td class="text-success"><?= number_format($m['co2'], 2) ?></td>
                        <td class="text-nowrap">
                            <?php if($m['id'] == $cid): ?>
                                <button class="btn btn-sm btn-secondary" disabled>บัญชีปัจจุบัน</button>
                            <?php else: ?>
                                <form method="post" class="d-flex justify-content-center gap-1">
                                    <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                    <?php if($m['status'] == 'Y'): ?>
                                        <button name="action" value="suspend" class="btn btn-sm btn-danger" onclick="return confirm('ยืนยันระงับสมาชิกนี้?')">ระงับ</button>
                                    <?php else: ?>
                                        <button name="action" value="activate" class="btn btn-sm btn-success">คืนสิทธิ์</button>
                                    <?php endif; ?>
                                    <button name="action" value="reset" class="btn btn-sm btn-warning" onclick="return confirm('ยืนยันรีเซ็ตรหัสผ่านเป็น 12345678?')">รีเซ็ตรหัส</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if(count($members) == 0): ?>
            <p class="text-muted text-center mt-4">ไม่พบข้อมูลสมาชิก</p>
        <?php endif; ?>
    </div>
<?php include '../footer.php'; ?>
<script src="<?= $base ?>js/bootstrap.bundle.min.js"></script>
</body>
</html>