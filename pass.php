<?php
session_start();
include 'config.php'; // ต้องมีตัวแปร $conn

// ---------- 1) ตรวจสอบการเข้าสู่ระบบ ----------
if (!isset($_SESSION['company_id'])) {
    header("location:login.php");
    exit();
}
$company_id = (int)$_SESSION['company_id'];

// ---------- 2) เมื่อกดบันทึกรหัสผ่านใหม่ ----------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $old     = $_POST['old'];
    $new     = $_POST['new'];
    $confirm = $_POST['new2'];

    // ดึงรหัสผ่านปัจจุบันของผู้ใช้
    $rs = mysqli_query($conn, "SELECT * FROM company WHERE id = $company_id");
    $row = mysqli_fetch_assoc($rs);

    if (md5($old) != $row['pass']) {
        $status = 'wrongold';          // รหัสเดิมไม่ถูก
    } elseif (strlen($new) < 8) {
        $status = 'short';             // รหัสใหม่สั้นเกินไป
    } elseif ($new != $confirm) {
        $status = 'notmatch';          // รหัสใหม่สองช่องไม่ตรงกัน
    } else {
        $hashed = md5($new);
        mysqli_query($conn, "UPDATE company SET pass = '$hashed' WHERE id = $company_id");
        $status = 'success';
    }
    header("location:pass.php?status=$status");
    exit();
}

// ---------- 3) ข้อความแจ้งเตือน ----------
$msg = '';
$color = '';
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'success') {
        $msg = 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว';
        $color = 'success';
    } elseif ($_GET['status'] == 'wrongold') {
        $msg = 'รหัสผ่านเดิมไม่ถูกต้อง';
        $color = 'danger';
    } elseif ($_GET['status'] == 'notmatch') {
        $msg = 'รหัสผ่านใหม่ทั้งสองช่องไม่ตรงกัน';
        $color = 'danger';
    } elseif ($_GET['status'] == 'short') {
        $msg = 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 8 ตัวอักษร';
        $color = 'danger';
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
    <?php include 'navbar.php'; ?>
    <div class="container">
        <div class="d-flex justify-content-center mt-4">
            <div class="card shadow-sm col-md-8 col-lg-5 p-4">
                <h4 class="fw-bold text-center mb-3">🔑 เปลี่ยนรหัสผ่าน</h4>

                <?php if ($msg != '') { ?>
                    <div class="alert alert-<?= $color ?> p-2 text-center mb-3"><?= $msg ?></div>
                <?php } ?>

                <form method="post">
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
                    <button class="btn btn-success w-100 py-2 mt-2">💾 บันทึกรหัสผ่านใหม่</button>
                </form>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
<script src="<?= $base ?>js/bootstrap.bundle.min.js"></script>
</body>
</html>