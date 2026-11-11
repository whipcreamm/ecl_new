<?php
session_start();
include 'config.php';
if (!isset($_SESSION['company_id'])) {
    header('location:login.php');
    exit();
}
$news = mysqli_query($conn, "select * from news order by id desc");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>news</title>
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container">
        <h3 class="fw-bold mt-3">ข่าวสารและความรู้</h3>

        <div class="row">
            <?php while($n = mysqli_fetch_assoc($news)): ?>
                <div class="col-md-4 mt-3">
                    <div class="card card-white shadow-sm p-2 h-100" data-bs-toggle="modal" data-bs-target="#news<?= $n['id'] ?>">
                        <?php if($n['pic'] != ''): ?>
                            <img src="img/<?= $n['pic'] ?>" class="card-img-top border" style="height:200px;">
                        <?php else: ?>
                            <div class="bg-secondary text-white d-flex justify-content-center align-items-center" style="height:200px;">ไม่มีรูปภาพ</div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="fw-bold"><?= $n['head'] ?></h5>
                            <p class="text-muted small"><?= $n['detail'] ?></p>
                        </div>
                    </div>
                </div>

                 <div class="modal fade" id="news<?= $n['id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title fw-bold"><?= htmlspecialchars($n['head']) ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <?php if($n['pic'] != ''): ?>
                                    <img src="img/<?= $n['pic'] ?>" class="w-100 mb-3">
                                <?php endif; ?>
                                <p><?= htmlspecialchars($n['detail']) ?></p>
                                <small class="text-muted">ลงข่าวเมื่อ <?= date('d/m/Y', strtotime($n['create_at'])) ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    <script src="<?= $base ?>js/bootstrap.bundle.min.js"></script>
<?php include 'footer.php'?>
    
</body>
</html>