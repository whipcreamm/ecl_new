<?php
session_start();
include '../config.php';
$company_id = (int)($_SESSION['company_id'] ?? 0);
$company = mysqli_fetch_assoc(mysqli_query($conn, "select * from company where id=$company_id"));

if (!$company || $company['role_id'] != 1) {
    header('location: ../login.php');
    exit();
}

if(isset($_POST['approve_id'])){
    $id = (int)$_POST['approve_id'];
    $today = date('Y-m-d');
    $end = date('Y-m-d', strtotime('+1 year'));

    // 1) คำขอเปลี่ยนแพ็กเกจ
    mysqli_query($conn, "update members set pack_id=newpack_id, pay_price=newpay, status_mem='Y', date_mem='$today', end_mem='$end',
        newpack_id=NULL, newpay=NULL, new_date=NULL
        where id=$id and newpack_id is not null");
    $ok = mysqli_affected_rows($conn) > 0;

    // 2) คำขอสมัครใหม่
    if(!$ok){
        mysqli_query($conn, "update members set status_mem='Y', date_mem='$today', end_mem='$end' where id=$id and status_mem='N'");
        $ok = mysqli_affected_rows($conn) > 0;
    }

    header('location: pack_review.php?status=' . ($ok ? 'success' : 'error'));
    exit();
}
$st = $_GET['status'] ?? '';
$alerts = [
    'success' => ['success', 'อนุมัติแพ็กเกจเรียบร้อยแล้ว ผู้ใช้สามารถใช้งานระบบได้ทันที'],
    'error'   => ['warning', 'ไม่พบรายการ หรือรายการนี้อนุมัติไปแล้ว']
];
$alert = $alerts[$st] ?? null;

// เก็บบริษัทกับแพ็กเกจไว้ใน array ตาม id
$cos = [];
$r = mysqli_query($conn, "select * from company");
while($x = mysqli_fetch_assoc($r)) $cos[$x['id']] = $x;
$packs = [];
$r = mysqli_query($conn, "select * from pack");
while($x = mysqli_fetch_assoc($r)) $packs[$x['id']] = $x;

$member_result = mysqli_query($conn, "select * from members order by (status_mem='N' or newpack_id is not null) desc, id desc");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>packages review</title>
</head>
<body class="bg-light">
    <?php include '../navbar.php'; ?>

    <div class="container">
        <h3 class="fw-bold mt-3 text-center">ตรวจสอบและอนุมัติแพ็กเกจ</h3>

        <?php if($alert): ?>
            <div class="alert alert-<?= $alert[0] ?> p-2 text-center mt-3"><?= $alert[1] ?></div>
        <?php endif; ?>

        <div class="card shadow-sm p-3 mt-3">
            <table class="table table-hover table-bordered align-middle">
                <thead class="bg-light">
                    <tr class="text-center">
                        <th>ชื่อบริษัท</th>
                        <th>อีเมล</th>
                        <th>แพ็คเกจ</th>
                        <th>ราคา</th>
                        <th>วันที่</th>
                        <th>สถานะ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($m = mysqli_fetch_assoc($member_result)): ?>
                    <?php
                        $is_change = !empty($m['newpack_id']);
                        $co = $cos[$m['company_id']];
                        $pk = $packs[$m['pack_id']] ?? null;
                        $new = $packs[$m['newpack_id']] ?? null;
                    ?>
                    <tr class="text-center">
                        <td class="text-start"><?= $co['name'] ?></td>
                        <td class="text-start"><?= $co['email'] ?></td>
                        <td>
                            <span class="badge bg-info text-dark"><?= $pk['name'] ?? '' ?></span>
                            <?php if($is_change): ?> → <span class="badge bg-warning text-dark"><?= $new['name'] ?? '' ?></span><?php endif; ?>
                        </td>
                        <td>
                            <b class="text-success"><?= number_format($is_change ? ($m['newpay'] ?? 0) : ($pk['price'] ?? 0)) ?> บาท</b>
                            <?= $is_change ? '<br><small class="text-muted">(หลังหักส่วนลด)</small>' : '' ?>
                        </td>
                        <td><?= $m['date_mem'] ?></td>
                        <td>
                            <?php if($is_change): ?>
                                <span class="badge bg-warning text-dark">ขอเปลี่ยนแพ็ค</span>
                            <?php elseif($m['status_mem'] == 'Y'): ?>
                                <span class="badge bg-success">อนุมัติแล้ว</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">รออนุมัติ</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap">
                            <?php if(!$is_change && $m['status_mem'] == 'Y'): ?>
                                <button class="btn btn-sm btn-secondary" disabled>ใช้งานถึง <?= $m['end_mem'] ?></button>
                            <?php else: ?>
                                <form method="post" onsubmit="return confirm('ยืนยันอนุมัติแพ็คเกจนี้ใช่หรือไม่?')">
                                    <input type="hidden" name="approve_id" value="<?= $m['id'] ?>">
                                    <button class="btn btn-sm btn-success">ยืนยันอนุมัติ</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <?php if(mysqli_num_rows($member_result) == 0): ?>
            <p class="text-muted text-center mt-4">ไม่มีรายการขอสมัครแพ็คเกจในขณะนี้</p>
        <?php endif; ?>
    </div>
<?php include '../footer.php'; ?>
<script src="<?= $base ?>js/bootstrap.bundle.min.js"></script>
</body>
</html>