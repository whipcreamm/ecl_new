<?php 
session_start();
include 'config.php';
if(!isset($_SESSION['company_id'])){
    header('location:login.php');
    exit();
}
$cid=(int)$_SESSION['company_id'];
$sql = "select * from news";
$news_result = mysqli_query($conn,$sql);
$sql2 = "select * from company where id=$cid";
$name_com = mysqli_query($conn,$sql2);
$name_result = mysqli_fetch_assoc($name_com);

$have_pack = mysqli_num_rows(mysqli_query($conn, "select * from members where company_id=$cid and status_mem='Y' and (end_mem is null or end_mem >= curdate()) limit 1"));
if($have_pack > 0){
    header('location: dashboard.php');
    exit();
}
$buypack = mysqli_fetch_assoc(mysqli_query($conn, "select * from members left join pack on pack_id = pack_id where company_id=$cid and status_mem='N' limit 1"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>index</title>
<style>
    body{
    }
    
    </style>
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>
    <div class="container mt-3">
        <h4 class="fw-bold">สวัสดี <?= htmlspecialchars($name_result['name']);  ?> 👋</h4>
        <p>ระบบติดตามและประเมินผลการลดก๊าซเรือนกระจกในองค์กร</p>

        <?php if($buypack): ?>
            <div class="alert alert-info" p-2 mt-3 text-center shadow-sm>คุณได้ส่งคำขอสมัครแพ็กเกจ <strong><?= htmlspecialchars($buypack['name']) ?></strong> แล้ว กรุณารอแอดมินอนุมัติ</div>
        <?php else: ?>
            <div class="alert alert-danger">คุณยังไม่ได้สมัครแพ็กเกจ กรุณาสมัครแพ็กเกจเพื่อเริ่มบันทึกค่าน้ำค่าไฟ <a href="pack.php" class="text-decoration-none fw-bold text-danger">คลิกเพื่อสมัครแพ็กเกจ</a></div>
        <?php endif; ?>

        <h5 class="fw-bold">เมนูที่เปิดให้บริการ</h5>
        <div class="row mt-3">
            <div class="col-3">
                <a href="product.php" class="text-decoration-none">
                    <div class="card card-white shadow-sm p-3">
                        <center>
                            <img src="img/Logo.png" style="width: 60px;">
                            <h5 class="text-green fw-bold mt-3">ร้านค้า</h5>
                            <p class="text-center text-muted small">เลือกซื้อสินค้ารักโลก</p>
                        </center>
                    </div>
                </a>
            </div>

            <div class="col-3">
                <a href="new.php" class="text-decoration-none">
                    <div class="card card-white shadow-sm p-3">
                        <center>
                            <img src="img/Logo.png" style="width: 60px;">
                            <h5 class="text-green fw-bold mt-3">ข่าวสาร</h5>
                            <p class="text-center text-muted small">ติดตามข่าวสารประชาสัมพันธ์</p>
                        </center>
                    </div>
                </a>
            </div>

            <div class="col-3">
                <a href="product.php" class="text-decoration-none">
                    <div class="card card-white shadow-sm p-3">
                        <center>
                            <img src="img/Logo.png" style="width: 60px;">
                            <h5 class="text-green fw-bold mt-3">ดูการจัดอันดับ</h5>
                            <p class="text-center text-muted small">การจัดอันดับหน่วยงานที่ลดก๊าซเรือนกระจก</p>
                        </center>
                    </div>
                </a>
             </div>

            <div class="col-3">
                <a href="pack.php" class="text-decoration-none">
                    <div class="card card-green shadow-sm p-3">
                        <center>
                            <img src="img/Logo.png" style="width: 60px;">
                            <h5 class="text-white fw-bold mt-3">แพ็กเกจ</h5>
                            <p class="text-center text-light small">เลือกแพ็กเกจเพื่อใช้งานเต็มระบบ</p>
                        </center>
                    </div>
                </a>
            </div>
        </div>
        <div class="mt-3 border-top">
            <div class="card card-white shadow-sm p-3 mt-3">
                <h6 class="fw-bold">📢 ข่าวสารล่าสุด</h6>
                <?php if(mysqli_num_rows($news_result)>0): ?>
                    <?php while($news = mysqli_fetch_assoc($news_result)): ?>
                        <div class="border-bottom p-2">
                            <a href="new.php" class="text-dark fw-semibold text-decoration-none"><?= htmlspecialchars($news['head']) ?></a><br>
                            <small class="text-muted"><?= date('d/m/Y',strtotime($news['create_at'])) ?></small>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<script src="<?= $base ?>js.bootstrap.bundle.min.js"></script>
<?php include 'footer.php'; ?>
</body>
</html>