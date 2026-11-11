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
    $reason = trim($_POST['reason']);
    $status = 'error';

    $row = mysqli_fetch_assoc(mysqli_query($conn, "select * from mission_parti where id=$id and status='pending'"));

    if($row){
        $mi = mysqli_fetch_assoc(mysqli_query($conn, "select * from mission where id={$row['mission_id']}"));
        $to = (int)$row['company_id'];
        $point = (int)$mi['point_reward'];
        $title = $mi['title'];
        $title_sql = mysqli_real_escape_string($conn, $title);
        $message = '';

        if($action == 'approve'){
            mysqli_query($conn, "update mission_parti set status='complete' where id=$id");
            mysqli_query($conn, "insert into eco_point_ledger(company_id,point,reason) values ($to,$point,'สำเร็จภารกิจ: $title_sql')");
            $message = "ยินดีด้วย! ภารกิจ '$title' ได้รับการอนุมัติแล้ว (+$point แต้ม)";
            $status = 'approve';
        }elseif($action == 'reject'){
            if($reason == ''){
                $status = 'reason';
            }else{
                mysqli_query($conn, "update mission_parti set status='reject' where id=$id");
                $message = "หลักฐานภารกิจ '$title' ไม่ผ่านการอนุมัติ เหตุผล: $reason กรุณาตรวจสอบและส่งใหม่";
                $status = 'reject';
            }
        }

        if($message != ''){
            $message = mysqli_real_escape_string($conn, $message);
            mysqli_query($conn, "insert into noti(recip_id,message,type,link) values ($to,'$message','mission','mission.php')");
        }
    }
    header("location: mission_review.php?status=$status");
    exit();
}

$st = $_GET['status'] ?? '';
$alerts = [
    'approve' => ['success', 'อนุมัติภารกิจและมอบแต้มให้สมาชิกเรียบร้อยแล้ว'],
    'reject'  => ['warning', 'ไม่อนุมัติหลักฐานภารกิจนี้ และแจ้งเหตุผลให้สมาชิกแล้ว'],
    'reason'  => ['warning', 'กรุณาระบุเหตุผลที่ไม่อนุมัติ'],
    'error'   => ['danger', 'ไม่พบรายการหลักฐาน หรือรายการนี้ถูกจัดการไปแล้ว']
];
$alert = $alerts[$st] ?? null;

// เก็บภารกิจกับบริษัทไว้ใน array ตาม id
$ms = [];
$r = mysqli_query($conn, "select * from mission");
while($x = mysqli_fetch_assoc($r)) $ms[$x['id']] = $x;
$cos = [];
$r = mysqli_query($conn, "select * from company");
while($x = mysqli_fetch_assoc($r)) $cos[$x['id']] = $x;

$mission_result = mysqli_query($conn, "select * from mission_parti where status='pending' order by submit_at desc, id desc");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>mission review</title>
</head>
<body class="bg-light">
    <?php include '../navbar.php'; ?>

    <div class="container">
        <h3 class="fw-bold mt-3 text-center">ตรวจหลักฐานภารกิจ</h3>

        <?php if($alert): ?>
            <div class="alert alert-<?= $alert[0] ?> p-2 text-center mt-3"><?= $alert[1] ?></div>
        <?php endif; ?>

        <div class="card shadow-sm p-3 mt-3">
            <table class="table table-hover table-bordered align-middle">
                <thead class="bg-light">
                    <tr class="text-center">
                        <th>ภารกิจ</th>
                        <th>แต้ม</th>
                        <th>ผู้ส่ง</th>
                        <th>ส่งเมื่อ</th>
                        <th>คำอธิบาย</th>
                        <th>หลักฐาน</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($m = mysqli_fetch_assoc($mission_result)): ?>
                    <?php
                        $mi = $ms[$m['mission_id']];
                        $co = $cos[$m['company_id']];
                    ?>
                    <tr class="text-center">
                        <td class="text-start"><?= $mi['title'] ?></td>
                        <td class="text-success fw-bold">+<?= (int)$mi['point_reward'] ?></td>
                        <td class="text-start"><?= $co['name'] ?></td>
                        <td><?= $m['submit_at'] ? date('d/m/Y H:i', strtotime($m['submit_at'])) : '-' ?></td>
                        <td class=""><?= $m['note'] ?: '-' ?></td>
                        <td>
                            <?php if($m['evidence_url'] != ''): ?>
                                <a href="../uploads/<?= $m['evidence_url'] ?>" target="_blank">
                                    <img src="../uploads/<?= $m['evidence_url'] ?>" class="border rounded" style="height:60px; object-fit:cover;">
                                </a>
                            <?php else: ?>
                                <span class="text-muted small">ไม่มีรูป</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post">
                                <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                <input type="text" name="reason" class="form-control form-control-sm mb-1" placeholder="เหตุผลถ้าไม่ผ่าน" required>
                                <div class="d-flex gap-1">
                                    <button name="action" value="approve" formnovalidate class="btn btn-sm btn-success w-100">อนุมัติ</button>
                                    <button name="action" value="reject" class="btn btn-sm btn-danger w-100" onclick="return confirm('ยืนยันไม่อนุมัติหลักฐานนี้?')">ไม่ผ่าน</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <?php if(mysqli_num_rows($mission_result) == 0): ?>
            <p class="text-muted text-center mt-4">ไม่มีหลักฐานภารกิจที่รอการตรวจสอบในขณะนี้</p>
        <?php endif; ?>
    </div>
<?php include '../footer.php'; ?>
<script src="<?= $base ?>js/bootstrap.bundle.min.js"></script>
</body>
</html>