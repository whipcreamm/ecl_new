<?php
session_start();
include 'config.php';
if (!isset($_SESSION['company_id'])) {
    header('location:login.php');
    exit();
}
$cid = (int)$_SESSION['company_id'];

$noti_result = mysqli_query($conn, "select * from noti where recip_id=$cid order by create_at desc limit 50");
mysqli_query($conn, "update noti set is_read=1 where recip_id=$cid");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>noti</title>
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container">
        <h3 class="fw-bold mt-3 text-center">การแจ้งเตือนของคุณ</h3>

        <div class="row">
            <?php while($n = mysqli_fetch_assoc($noti_result)): ?>
                <div class="col-12 mt-3">
                    <div class="card card-white shadow-sm p-3 h-100">
                        <div class="d-flex justify-content-between">
                            <h6 class="fw-bold">
                                <?= $n['message'] ?>
                                <?php if($n['is_read'] == 0): ?>
                                    <span class="badge bg-danger">ใหม่</span>
                                <?php endif; ?>
                            </h6>
                            <p class="text-muted small"><?= date('d/m/Y H:i', strtotime($n['create_at'])) ?></p>
                        </div>
                        <?php if(!empty($n['link'])): ?>
                            <div class="mt-auto pt-2 border-top">
                                <a href="<?= $n['link'] ?>" class="btn btn-outline-success  mt-2">ดูรายละเอียด</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <?php if(mysqli_num_rows($noti_result) == 0): ?>
            <p class="text-muted text-center mt-4">ยังไม่มีการแจ้งเตือนในขณะนี้</p>
        <?php endif; ?>
    </div>
<?php include 'footer.php'; ?>
<script src="<?= $base ?>js/bootstrap.bundle.min.js"></script>
</body>
</html>