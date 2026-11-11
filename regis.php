<?php 
// เริ่มต้นเซสชัน
session_start();

// ดึงไฟล์เชื่อมต่อฐานข้อมูล
include 'config.php';

$status = '';

// เมื่อมีการส่งข้อมูลแบบ POST จากฟอร์มลงทะเบียน
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $pass = $_POST['pass'];
    $pass2 = $_POST['pass2'];
    $tel = trim($_POST['tel']);

    $escaped_email = mysqli_real_escape_string($conn, $email);
    $escaped_name = mysqli_real_escape_string($conn, $name);
    $escaped_tel = mysqli_real_escape_string($conn, $tel);

    // ตรวจสอบความถูกต้องของข้อมูล
    if ($pass !== $pass2) {
        $status = 'notmatch'; // รหัสผ่านทั้งสองช่องไม่ตรงกัน
    } else if (strlen($pass) < 8) {
        $status = 'short'; // รหัสผ่านสั้นกว่า 8 ตัวอักษร
    } else {
        // ตรวจสอบว่าอีเมลนี้มีอยู่ในระบบแล้วหรือไม่
        $sql_check_email = "SELECT * FROM company WHERE email = '$escaped_email'";
        $email_query = mysqli_query($conn, $sql_check_email);
        $existing_user_count = mysqli_num_rows($email_query);

        if ($existing_user_count > 0) {
            $status = 'error'; // อีเมลซ้ำในระบบ
        } else {
            // เข้ารหัสรหัสผ่านด้วย MD5
            $hashed_password = md5($pass);

            // บันทึกข้อมูลบริษัทใหม่ลงฐานข้อมูล
            $sql_insert = "INSERT INTO company (email, pass, name, tel, role_id, status) VALUES ('$escaped_email', '$hashed_password', '$escaped_name', '$escaped_tel', 2, 'Y')";
            mysqli_query($conn, $sql_insert);

            // ส่งไปหน้าเข้าสู่ระบบพร้อมแจ้งสถานะ
            header('location: login.php?status=registered');
            exit();
        }
    }
}

// กำหนดข้อความแจ้งเตือนตาม status
$alert_type = '';
$alert_text = '';

if ($status == 'short') {
    $alert_type = 'danger';
    $alert_text = 'รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร';
} else if ($status == 'notmatch') {
    $alert_type = 'danger';
    $alert_text = 'รหัสผ่านทั้งสองช่องไม่ตรงกัน';
} else if ($status == 'error') {
    $alert_type = 'danger';
    $alert_text = 'อีเมลนี้ถูกใช้งานในระบบแล้ว กรุณาใช้อีเมลอื่น';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - Ecolyn</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <style>
        body {
            background: #08543c;
        }
        .card-white {
            border-radius: 13px;
        }
        .btn-green {
            background: #08543c;
            color: white;
        }
        .btn-green:hover {
            background: #023c2a;
            color: white;
        }
        .text-green {
            color: #08543c;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-center align-items-center vh-100 py-5">
            <div class="card card-white col-md-5 col-lg-5 p-4 shadow">
                <div class="text-center">
                    <img src="img/Logo1.png" style="width: 200px;" alt="โลโก้">
                    <h3 class="mt-2 fw-bold">สมัครสมาชิก</h3>
                    <p class="text-muted small">ระบบติดตามและประเมินผลการลดก๊าซเรือนกระจกในองค์กร</p>
                </div>

                <?php if ($alert_text != '') { ?>
                    <div class="alert alert-<?php echo $alert_type; ?> p-2 text-center mt-3">
                        <?php echo $alert_text; ?>
                    </div>
                <?php } ?>

                <!-- ฟอร์มลงทะเบียนสมาชิก -->
                <form action="regis.php" method="post">
                    <div class="mt-2">
                        <label class="form-label">อีเมล</label>
                        <input type="email" class="form-control" placeholder="กรุณากรอกอีเมล" name="email" required>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">รหัสผ่าน</label>
                        <input type="password" class="form-control" placeholder="กรุณากรอกรหัสผ่านอย่างน้อย 8 ตัวอักษร" name="pass" autocomplete="new-password" required>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">ยืนยันรหัสผ่าน</label>
                        <input type="password" class="form-control" placeholder="กรุณากรอกรหัสผ่านอีกครั้ง" name="pass2" autocomplete="new-password" required>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">ชื่อหน่วยงาน/องค์กร</label>
                        <input type="text" class="form-control" placeholder="กรุณากรอกชื่อหน่วยงาน" name="name" required>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">เบอร์โทรติดต่อ</label>
                        <input type="tel" class="form-control" placeholder="กรุณากรอกเบอร์โทร" name="tel" required>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-green w-100 py-2">ลงทะเบียน</button>
                        <p class="text-center text-muted small mt-3">
                            มีบัญชีอยู่แล้ว? <a href="login.php" class="text-green text-decoration-none fw-bold">เข้าสู่ระบบ</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ส่วนท้ายเว็บไซต์ -->
    <footer class="text-center border-top text-light p-4 mt-5">
        <small>&copy; <?php echo date('Y'); ?> Ecolyn - ระบบติดตามการลดก๊าซเรือนกระจกในองค์กร โทร. 8888888</small>
    </footer>
</body>
</html>
