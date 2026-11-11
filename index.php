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

$cid = (int)$_SESSION['company_id'];

// 1. ดึงข้อมูลข่าวสารทั้งหมด
$sql_news = "SELECT * FROM news ORDER BY id DESC";
$news_result = mysqli_query($conn, $sql_news);

// 2. ดึงข้อมูลชื่อบริษัทของตัวเอง
$sql_com = "SELECT * FROM company WHERE id = $cid";
$company_query = mysqli_query($conn, $sql_com);
$company_row = mysqli_fetch_assoc($company_query);
$company_name = $company_row ? $company_row['name'] : '';

// 3. ตรวจสอบว่ามีแพ็กเกจที่อนุมัติแล้วและยังไม่หมดอายุหรือไม่
$sql_check_pack = "SELECT * FROM members WHERE company_id = $cid AND status_mem = 'Y' AND (end_mem IS NULL OR end_mem >= CURDATE()) LIMIT 1";
$check_pack_query = mysqli_query($conn, $sql_check_pack);
$have_active_pack = mysqli_num_rows($check_pack_query);

// ถ้ามีแพ็กเกจพร้อมใช้งานอยู่แล้ว ให้ข้ามไปหน้าแดชบอร์ดหลักทันที
if ($have_active_pack > 0) {
    header('location: dashboard.php');
    exit();
}

// 4. ตรวจสอบว่ามีแพ็กเกจที่เพิ่งสมัครและกำลังรอแอดมินอนุมัติหรือไม่ (status_mem = 'N')
$sql_pending_pack = "SELECT members.*, pack.name AS pack_name FROM members LEFT JOIN pack ON members.pack_id = pack.id WHERE members.company_id = $cid AND members.status_mem = 'N' LIMIT 1";
$pending_query = mysqli_query($conn, $sql_pending_pack);
$pending_pack = mysqli_fetch_assoc($pending_query);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าแรก - Ecolyn</title>
</head>
<body class="bg-light">
    <!-- แถบเมนูด้านบน -->
    <?php include 'navbar.php'; ?>

    <div class="container mt-3">
        <h4 class="fw-bold">สวัสดี <?php echo htmlspecialchars($company_name); ?> 👋</h4>
        <p class="text-muted">ระบบติดตามและประเมินผลการลดก๊าซเรือนกระจกในองค์กร</p>

        <!-- กล่องแจ้งเตือนสถานะการสมัครแพ็กเกจ -->
        <?php if ($pending_pack) { ?>
            <div class="alert alert-info p-3 mt-3 text-center shadow-sm">
                คุณได้ส่งคำขอสมัครแพ็กเกจ <strong><?php echo htmlspecialchars($pending_pack['pack_name']); ?></strong> แล้ว กรุณารอผู้ดูแลระบบอนุมัติ
            </div>
        <?php } else { ?>
            <div class="alert alert-danger p-3 mt-3 shadow-sm">
                คุณยังไม่ได้สมัครแพ็กเกจ กรุณาสมัครแพ็กเกจเพื่อเริ่มบันทึกค่าน้ำค่าไฟ 
                <a href="pack.php" class="text-decoration-none fw-bold text-danger">คลิกเพื่อสมัครแพ็กเกจ</a>
            </div>
        <?php } ?>

        <!-- ส่วนเมนูการให้บริการ -->
        <h5 class="fw-bold mt-4">เมนูที่เปิดให้บริการ</h5>
        <div class="row mt-3">
            <div class="col-md-3 col-6 mb-3">
                <a href="product.php" class="text-decoration-none">
                    <div class="card card-white shadow-sm p-3 text-center h-100">
                        <img src="img/Logo.png" style="width: 60px; margin: 0 auto;" alt="ร้านค้า">
                        <h5 class="text-green fw-bold mt-3">ร้านค้า</h5>
                        <p class="text-muted small mb-0">เลือกซื้อสินค้ารักโลก</p>
                    </div>
                </a>
            </div>

            <div class="col-md-3 col-6 mb-3">
                <a href="news.php" class="text-decoration-none">
                    <div class="card card-white shadow-sm p-3 text-center h-100">
                        <img src="img/Logo.png" style="width: 60px; margin: 0 auto;" alt="ข่าวสาร">
                        <h5 class="text-green fw-bold mt-3">ข่าวสาร</h5>
                        <p class="text-muted small mb-0">ติดตามข่าวสารประชาสัมพันธ์</p>
                    </div>
                </a>
            </div>

            <div class="col-md-3 col-6 mb-3">
                <a href="rank.php" class="text-decoration-none">
                    <div class="card card-white shadow-sm p-3 text-center h-100">
                        <img src="img/Logo.png" style="width: 60px; margin: 0 auto;" alt="การจัดอันดับ">
                        <h5 class="text-green fw-bold mt-3">ดูการจัดอันดับ</h5>
                        <p class="text-muted small mb-0">อันดับหน่วยงานที่ลดก๊าซเรือนกระจก</p>
                    </div>
                </a>
            </div>

            <div class="col-md-3 col-6 mb-3">
                <a href="pack.php" class="text-decoration-none">
                    <div class="card card-green shadow-sm p-3 text-center h-100">
                        <img src="img/Logo.png" style="width: 60px; margin: 0 auto;" alt="แพ็กเกจ">
                        <h5 class="text-white fw-bold mt-3">แพ็กเกจ</h5>
                        <p class="text-light small mb-0">เลือกแพ็กเกจเพื่อใช้งานเต็มระบบ</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- รายการข่าวสารล่าสุด -->
        <div class="mt-3 border-top pt-3">
            <div class="card card-white shadow-sm p-3 mt-2">
                <h6 class="fw-bold mb-3">📢 ข่าวสารล่าสุด</h6>
                <?php 
                $news_count = mysqli_num_rows($news_result);
                if ($news_count > 0) { 
                    while ($news = mysqli_fetch_assoc($news_result)) { 
                ?>
                        <div class="border-bottom p-2">
                            <a href="news.php" class="text-dark fw-semibold text-decoration-none">
                                <?php echo htmlspecialchars($news['head']); ?>
                            </a>
                            <br>
                            <small class="text-muted"><?php echo date('d/m/Y', strtotime($news['create_at'])); ?></small>
                        </div>
                <?php 
                    } 
                } else { 
                ?>
                    <p class="text-muted text-center py-3 mb-0">ยังไม่มีข่าวสารในขณะนี้</p>
                <?php } ?>
            </div>
        </div>
    </div>

    <!-- ส่วนท้ายเว็บไซต์ -->
    <?php include 'footer.php'; ?>
    <script src="<?php echo $base; ?>js/bootstrap.bundle.min.js"></script>
</body>
</html>
