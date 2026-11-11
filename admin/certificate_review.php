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

if(isset($_POST['approve_id'])){
    $id = (int)$_POST['approve_id'];
    $today = date('Y-m-d');
    $status = 'error';

    $row = mysqli_fetch_assoc(mysqli_query($conn, "select * from members where id=$id and cert_status='N'"));
    if($row){
        mysqli_query($conn, "update members set cert_status='Y', cert_date='$today' where id=$id");

        $to = (int)$row['company_id'];
        $text = 'ใบรับรองของคุณถูกส่งไปทางอีเมลแล้ว กรุณาตรวจสอบกล่องจดหมาย Gmail';
        mysqli_query($conn, "insert into noti(recip_id,message,type,link) values ($to,'$text','certificate','index.php')");
        $status = 'success';
    }
    header("location: certificate_review.php?status=$status");
    exit();
}
$st = $_GET['status'] ?? '';
$alerts = [
    'success' => ['success', 'อนุมัติเรียบร้อยแล้ว ระบบแจ้งเตือนผู้ใช้ให้ตรวจสอบอีเมลแล้ว'],
    'error'   => ['warning', 'ไม่พบรายการ หรือรายการนี้อนุมัติไปแล้ว']
];
$alert = $alerts[$st] ?? null;

// เก็บข้อมูลบริษัทไว้ใน array ตาม id
$cos = [];
$r = mysqli_query($conn, "select * from company");
while($x = mysqli_fetch_assoc($r)) $cos[$x['id']] = $x;

$cert_result = mysqli_query($conn, "select * from members where cert_status is not null order by cert_status asc, id desc");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>certificate review</title>
</head>
<body class="bg-light">
    <?php include '../navbar.php'; ?>

    <div class="container">
        <h3 class="fw-bold mt-3 text-center">ตรวจสอบและอนุมัติใบรับรอง</h3>

        <?php if($alert): ?>
            <div class="alert alert-<?= $alert[0] ?> p-2 text-center mt-3"><?= $alert[1] ?></div>
        <?php endif; ?>

        <div class="card shadow-sm p-3 mt-3">
            <table class="table table-hover table-bordered align-middle">
                <thead class="bg-light">
                    <tr class="text-center">
                        <th>ชื่อบริษัท</th>
                        <th>อีเมล</th>
                        <th>วันที่ขอ</th>
                        <th>สถานะ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($c = mysqli_fetch_assoc($cert_result)): ?>
                    <?php $co = $cos[$c['company_id']]; ?>
                    <tr class="text-center">
                        <td class="text-start"><?= $co['name'] ?></td>
                        <td class="text-start"><?= $co['email'] ?></td>
                        <td><?= $c['cert_date'] ?: '-' ?></td>
                        <td>
                            <?php if($c['cert_status'] == 'Y'): ?>
                                <span class="badge bg-success">ส่งใบรับรองแล้ว</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">รอส่งใบรับรอง</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap">
                            <?php if($c['cert_status'] == 'Y'): ?>
                                <button class="btn btn-sm btn-secondary" disabled>อนุมัติเมื่อ <?= $c['cert_date'] ?></button>
                            <?php else: ?>
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="https://mail.google.com" target="_blank" class="btn btn-sm btn-outline-primary">เปิด Gmail</a>
                                    <form method="post" onsubmit="return confirm('ยืนยันว่าส่งใบรับรองทาง Gmail เรียบร้อยแล้วใช่หรือไม่?')">
                                        <input type="hidden" name="approve_id" value="<?= $c['id'] ?>">
                                        <button class="btn btn-sm btn-success">ยืนยันส่งแล้ว</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <?php if(mysqli_num_rows($cert_result) == 0): ?>
            <p class="text-muted text-center mt-4">ไม่มีรายการขอใบรับรองในขณะนี้</p>
        <?php endif; ?>
    </div>
<?php include '../footer.php'; ?>
<script src="<?= $base ?>js/bootstrap.bundle.min.js"></script>
</body>
</html>