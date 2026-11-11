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

// ดึงรายการข่าวสารทั้งหมด เรียงจากข่าวใหม่ไปข่าวเก่า
$sql_news = "SELECT * FROM news ORDER BY id DESC";
$news_result = mysqli_query($conn, $sql_news);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข่าวสารและความรู้ - Ecolyn</title>
</head>
<body class="bg-light">
    <!-- แถบเมนูด้านบน -->
    <?php include 'navbar.php'; ?>

    <div class="container">
        <h3 class="fw-bold mt-3">ข่าวสารและความรู้</h3>

        <div class="row">
            <?php 
            // วนลูปแสดงการ์ดข่าวสารแต่ละรายการ
            while ($news_item = mysqli_fetch_assoc($news_result)) { 
                $news_id = $news_item['id'];
                $news_head = $news_item['head'];
                $news_detail = $news_item['detail'];
                $news_pic = $news_item['pic'];
                $news_date = date('d/m/Y', strtotime($news_item['create_at']));
            ?>
                <!-- การ์ดสรุปข่าวสาร -->
                <div class="col-md-4 mt-3">
                    <div class="card card-white shadow-sm p-2 h-100" data-bs-toggle="modal" data-bs-target="#newsModal_<?php echo $news_id; ?>" style="cursor: pointer;">
                        <?php if ($news_pic != '') { ?>
                            <img src="img/<?php echo htmlspecialchars($news_pic); ?>" class="card-img-top border" style="height: 200px; object-fit: cover;">
                        <?php } else { ?>
                            <div class="bg-secondary text-white d-flex justify-content-center align-items-center" style="height: 200px;">
                                ไม่มีรูปภาพ
                            </div>
                        <?php } ?>
                        <div class="card-body">
                            <h5 class="fw-bold"><?php echo htmlspecialchars($news_head); ?></h5>
                            <p class="text-muted small"><?php echo htmlspecialchars($news_detail); ?></p>
                        </div>
                    </div>
                </div>

                <!-- ป็อปอัป (Modal) แสดงรายละเอียดข่าวแบบเต็ม -->
                <div class="modal fade" id="newsModal_<?php echo $news_id; ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title fw-bold"><?php echo htmlspecialchars($news_head); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <?php if ($news_pic != '') { ?>
                                    <img src="img/<?php echo htmlspecialchars($news_pic); ?>" class="w-100 mb-3" style="max-height: 400px; object-fit: cover;">
                                <?php } ?>
                                <p><?php echo nl2br(htmlspecialchars($news_detail)); ?></p>
                                <small class="text-muted">ลงข่าวเมื่อ <?php echo $news_date; ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <!-- ส่วนท้ายเว็บไซต์ -->
    <?php include 'footer.php'; ?>
    <script src="<?php echo $base; ?>js/bootstrap.bundle.min.js"></script>
</body>
</html>
