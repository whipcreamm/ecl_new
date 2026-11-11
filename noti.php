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

// แปลงรหัสองค์กรให้เป็นตัวเลขจำนวนเต็ม
$cid = (int)$_SESSION['company_id'];

// ดึงข้อมูลการแจ้งเตือนขององค์กรนี้ ล่าสุด 50 รายการ
$sql_noti = "SELECT * FROM noti WHERE recip_id = $cid ORDER BY create_at DESC LIMIT 50";
$noti_result = mysqli_query($conn, $sql_noti);

// อัปเดตสถานะการแจ้งเตือนทั้งหมดขององค์กรนี้ให้เป็นอ่านแล้ว (is_read = 1)
$sql_update_read = "UPDATE noti SET is_read = 1 WHERE recip_id = $cid";
mysqli_query($conn, $sql_update_read);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การแจ้งเตือน - Ecolyn</title>
</head>
<body class="bg-light">
    <!-- แถบเมนูด้านบน -->
    <?php include 'navbar.php'; ?>

    <div class="container">
        <h3 class="fw-bold mt-3 text-center">การแจ้งเตือนของคุณ</h3>

        <div class="row">
            <?php 
            // วนลูปแสดงรายการแจ้งเตือนแต่ละรายการ
            while ($item = mysqli_fetch_assoc($noti_result)) { 
                $message_text = $item['message'];
                $is_read = $item['is_read'];
                $created_time = date('d/m/Y H:i', strtotime($item['create_at']));
                $link_url = $item['link'];
            ?>
                <div class="col-12 mt-3">
                    <div class="card card-white shadow-sm p-3 h-100">
                        <div class="d-flex justify-content-between">
                            <h6 class="fw-bold">
                                <?php echo htmlspecialchars($message_text); ?>
                                <?php if ($is_read == 0) { ?>
                                    <span class="badge bg-danger">ใหม่</span>
                                <?php } ?>
                            </h6>
                            <p class="text-muted small"><?php echo $created_time; ?></p>
                        </div>
                        <?php if (!empty($link_url)) { ?>
                            <div class="mt-auto pt-2 border-top">
                                <a href="<?php echo htmlspecialchars($link_url); ?>" class="btn btn-outline-success mt-2">ดูรายละเอียด</a>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        </div>

        <?php 
        // ถ้าไม่มีข้อมูลการแจ้งเตือนเลย
        $total_rows = mysqli_num_rows($noti_result);
        if ($total_rows == 0) { 
        ?>
            <p class="text-muted text-center mt-4">ยังไม่มีการแจ้งเตือนในขณะนี้</p>
        <?php } ?>
    </div>

    <!-- ส่วนท้ายเว็บไซต์ -->
    <?php include 'footer.php'; ?>
    <script src="<?php echo $base; ?>js/bootstrap.bundle.min.js"></script>
</body>
</html>
