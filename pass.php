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

$company_id = (int)$_SESSION['company_id'];

// เมื่อผู้ใช้กดปุ่มบันทึกรหัสผ่านใหม่
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $old_pass = $_POST['old'];
    $new_pass = $_POST['new'];
    $confirm_pass = $_POST['new2'];

    // ดึงรหัสผ่านปัจจุบันของผู้ใช้จากฐานข้อมูล
    $sql_user = "SELECT * FROM company WHERE id = $company_id";
    $query_user = mysqli_query($conn, $sql_user);
    $user_row = mysqli_fetch_assoc($query_user);

    $old_pass_hashed = md5($old_pass);
    $current_pass_in_db = $user_row['pass'];

    // ตรวจสอบเงื่อนไขต่าง ๆ
    if ($old_pass_hashed != $current_pass_in_db) {
        $status = 'wrongold'; // รหัสผ่านเดิมไม่ถูกต้อง
    } else if (strlen($new_pass) < 8) {
        $status = 'short'; // รหัสผ่านใหม่สั้นเกินไป
    } else if ($new_pass != $confirm_pass) {
        $status = 'notmatch'; // รหัสผ่านใหม่สองช่องไม่ตรงกัน
    } else {
        // อัปเดตรหัสผ่านใหม่ลงฐานข้อมูล
        $new_pass_hashed = md5($new_pass);
        $sql_update_pass = "UPDATE company SET pass = '$new_pass_hashed' WHERE id = $company_id";
        mysqli_query($conn, $sql_update_pass);
        $status = 'success';
    }

    // รีไดเรกต์กลับมาพร้อมข้อความสถานะ
    header('location: pass.php?status=' . $status);
    exit();
}

// กำหนดข้อความแจ้งเตือนตาม status ใน URL
$alert_message = '';
$alert_color = '';

if (isset($_GET['status'])) {
    $status_param = $_GET['status'];

    if ($status_param == 'success') {
        $alert_message = 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว';
        $alert_color = 'success';
    } else if ($status_param == 'wrongold') {
        $alert_message = 'รหัสผ่านเดิมไม่ถูกต้อง';
        $alert_color = 'danger';
    } else if ($status_param == 'notmatch') {
        $alert_message = 'รหัสผ่านใหม่ทั้งสองช่องไม่ตรงกัน';
        $alert_color = 'danger';
    } else if ($status_param == 'short') {
        $alert_message = 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 8 ตัวอักษร';
        $alert_color = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เปลี่ยนรหัสผ่าน - Ecolyn</title>
</head>
<body class="bg-light">
    <!-- แถบเมนูด้านบน -->
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="d-flex justify-content-center mt-4">
            <div class="card shadow-sm col-md-8 col-lg-5 p-4">
                <h4 class="fw-bold text-center mb-3">🔑 เปลี่ยนรหัสผ่าน</h4>

                <?php if ($alert_message != '') { ?>
                    <div class="alert alert-<?php echo $alert_color; ?> p-2 text-center mb-3">
                        <?php echo $alert_message; ?>
                    </div>
                <?php } ?>

                <!-- ฟอร์มเปลี่ยนรหัสผ่าน -->
                <form method="post" action="">
                    <div class="mb-3">
                        <label class="form-label">รหัสผ่านเดิม</label>
                        <input type="password" name="old" class="form-control" placeholder="กรอกรหัสผ่านปัจจุบัน" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">รหัสผ่านใหม่</label>
                        <input type="password" name="new" class="form-control" placeholder="รหัสผ่านใหม่อย่างน้อย 8 ตัวอักษร" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ยืนยันรหัสผ่านใหม่</label>
                        <input type="password" name="new2" class="form-control" placeholder="ยืนยันรหัสผ่านใหม่อีกครั้ง" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100 py-2 mt-2">💾 บันทึกรหัสผ่านใหม่</button>
                </form>
            </div>
        </div>
    </div>

    <!-- ส่วนท้ายเว็บไซต์ -->
    <?php include 'footer.php'; ?>
    <script src="<?php echo $base; ?>js/bootstrap.bundle.min.js"></script>
</body>
</html>
