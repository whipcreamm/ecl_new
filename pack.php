<?php
session_start();
include 'config.php';
if (!isset($_SESSION['company_id'])) {
    header('location:login.php');
    exit();
}
$cid = (int)$_SESSION['company_id'];

// แพ็กเกจที่ใช้อยู่ (เอาแถวล่าสุด)
$now = mysqli_fetch_assoc(mysqli_query($conn, "select * from members where company_id=$cid and status_mem='Y' and (end_mem is null or end_mem >= curdate()) order by id desc limit 1"));
$now_id = $now['pack_id'] ?? 0;

// เก็บแพ็กเกจไว้ใน array ตาม id
$packs = [];
$r = mysqli_query($conn, "select * from pack");
while($x = mysqli_fetch_assoc($r)) $packs[$x['id']] = $x;

// กดเลือกแพ็กเกจ (บันทึกเป็นรอแอดมินอนุมัติ)
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $pack_id = (int)$_POST['pack_id'];
    $price = $packs[$pack_id]['price'] ?? 0;
    if($now){
        // มีแพ็กเกจอยู่แล้ว = ขอเปลี่ยนแพ็ก (เก็บในแถวเดิม)
        mysqli_query($conn, "update members set newpack_id=$pack_id, newpay='$price', new_date=curdate() where id={$now['id']}");
        $act = 'ขอเปลี่ยนเป็นแพ็กเกจ';
    }else{
        // ยังไม่มีแพ็กเกจ = สมัครใหม่
        mysqli_query($conn, "delete from members where company_id=$cid and status_mem='N'");
        mysqli_query($conn, "insert into members(company_id,pack_id,status_mem) values ($cid,$pack_id,'N')");
        $act = 'สมัครแพ็กเกจ';
    }

    // แจ้งแอดมินทุกคน
    $me = mysqli_fetch_assoc(mysqli_query($conn, "select * from company where id=$cid"));
    $text = mysqli_real_escape_string($conn, "องค์กร '{$me['name']}' $act {$packs[$pack_id]['name']}");
    $admins = mysqli_query($conn, "select * from company where role_id=1");
    while($ad = mysqli_fetch_assoc($admins)){
        mysqli_query($conn, "insert into noti(recip_id,message,type,link) values({$ad['id']},'$text','pack','admin/pack_review.php')");
    }

    $_SESSION['msg'] = ['success', 'ส่งคำขอเรียบร้อยแล้ว รอแอดมินอนุมัติ'];
    header('location: pack.php');
    exit();
}

$msg = $_SESSION['msg'] ?? null;
unset($_SESSION['msg']);

// แพ็กเกจที่รออนุมัติ (ขอเปลี่ยน หรือ สมัครใหม่)
if($now && $now['newpack_id']){
    $wait_id = $now['newpack_id'];
}else{
    $wait = mysqli_fetch_assoc(mysqli_query($conn, "select * from members where company_id=$cid and status_mem='N' limit 1"));
    $wait_id = $wait['pack_id'] ?? 0;
}

$pack_result = mysqli_query($conn, "select * from pack order by id asc");


?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>pack</title>
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container">
        <h3 class="fw-bold mt-3 text-center">เลือกแพ็กเกจรักษ์โลก</h3>

        <?php if($msg): ?>
            <div class="alert alert-<?= $msg[0] ?> p-2 text-center mt-3"><?= $msg[1] ?></div>
        <?php endif; ?>
        <div class="row">
            <?php while($p = mysqli_fetch_assoc($pack_result)): ?>
                <div class="col-md-6 mt-3">
                    <div class="card card-white shadow-sm p-3 h-100 ">
                        <div class="d-flex justify-content-between">
                            <h5 class="fw-bold"><?= $p['name'] ?></h5>
                            <p class="text-success fw-bold"><?= $p['price'] ?> บาท</p>
                        </div>
                        <div class="mt-3 border-top">
                            <p class="text-muted small"><?= $p['detail'] ?></p>
                        </div>
                        <div class="mt-auto pt-5">
                            <?php if($p['id'] == $now_id): ?>
                                <button class="btn btn-secondary w-100" disabled>คุณใช้แพ็กเกจนี้อยู่</button>
                            <?php elseif($p['id'] == $wait_id): ?>
                                <button class="btn btn-warning w-100" disabled>รอแอดมินอนุมัติ</button>
                            <?php else: ?>
                                <form method="post">
                                    <input type="hidden" name="pack_id" value="<?= $p['id'] ?>">
                                    <button class="btn btn-success w-100">เลือกแพ็กเกจ</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
<?php include 'footer.php'; ?>
<script src="<?= $base ?>js/bootstrap.bundle.min.js"></script>
</body>
</html>