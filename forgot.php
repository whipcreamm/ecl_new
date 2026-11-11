<?php
session_start();
include 'config.php'; // ต้องมีตัวแปร $conn

// ถ้าล็อกอินอยู่แล้ว ไม่ต้องใช้หน้านี้
if (isset($_SESSION['company_id'])) {
    header("location:index.php");
    exit();
}

$status = '';
$email_input = '';

// ---------- รับค่าจากฟอร์ม ----------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email_input = trim($_POST['email']);
    $email = mysqli_real_escape_string($conn, $email_input);

    // ค้นหาอีเมลในระบบ
    $result = mysqli_query($conn, "select * from company where email = '$email'");
    $user = mysqli_fetch_assoc($result);

    if (!$user) {
        $status = 'notfound';
    } else {
        // แจ้งเตือนแอดมินทุกคน (role_id = 1)
        $message = $user['name'] . ' (' . $user['email'] . ') ขอรีเซ็ตรหัสผ่าน';
        $message = mysqli_real_escape_string($conn, $message);
        $link    = 'admin/user.php?find=' . urlencode($user['email']);

        $admins = mysqli_query($conn, "select * from company where role_id = 1");
        while ($ad = mysqli_fetch_assoc($admins)) {
            $ad_id = (int)$ad['id'];
            mysqli_query($conn, "insert into noti (recip_id, message, link)values ($ad_id, '$message', '$link')");
        }
        $status = 'success';
    }
}

// ---------- ข้อความแจ้งเตือน ----------
$msg = '';
$color = '';
if ($status == 'success') {
    $msg = 'ส่งคำขอเรียบร้อยแล้ว แจ้งผู้ดูแลระบบเพื่อรับการรีเซ็ตรหัสผ่าน';
    $color = 'success';
} elseif ($status == 'notfound') {
    $msg = 'ไม่พบอีเมลนี้ในระบบ';
    $color = 'danger';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลืมรหัสผ่าน - Ecolyn</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <style>
        body { background: #058d76; }
        .card-white { border-radius: 13px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-center align-items-center vh-100">
            <div class="card card-white shadow-sm p-4 col-md-5 col-lg-4">
                <h4 class="fw-bold mb-1">ลืมรหัสผ่าน</h4>
                <p class="text-muted small mb-3">ระบบจะส่งคำขอไปให้ผู้ดูแลระบบช่วยรีเซ็ตรหัสผ่าน</p>

                <?php if ($msg != '') { ?>
                    <div class="alert alert-<?= $color ?> py-2 small"><?= $msg ?></div>
                <?php } ?>

                <form method="post">
                    <label class="form-label">อีเมลของคุณ</label>
                    <input type="email" name="email" class="form-control mb-3" placeholder="กรุณากรอกอีเมล"
                           required autofocus value="<?= htmlspecialchars($email_input) ?>">
                    <button class="btn btn-success w-100 py-2">ส่งคำขอ</button>
                    <div class="text-center mt-3 small">
                        <a href="login.php" class="text-success text-decoration-none">กลับไปหน้าเข้าสู่ระบบ</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>