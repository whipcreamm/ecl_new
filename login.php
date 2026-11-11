<?php
// เริ่มต้นเซสชัน
session_start();

// ดึงไฟล์เชื่อมต่อฐานข้อมูล
include 'config.php';

$status = '';

// เมื่อมีการส่งข้อมูลแบบ POST เพื่อเข้าสู่ระบบ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $raw_pass = $_POST['pass'];
    $hashed_pass = md5($raw_pass);

    // ค้นหาบัญชีผู้ใช้ด้วยอีเมลและรหัสผ่าน
    $sql_login = "SELECT * FROM company WHERE email = '$email' AND pass = '$hashed_pass'";
    $query_login = mysqli_query($conn, $sql_login);
    $user = mysqli_fetch_assoc($query_login);

    if (!$user) {
        $status = 'error'; // ข้อมูลไม่ถูกต้อง
    } else {
        $user_status = isset($user['status']) ? $user['status'] : 'Y';

        if ($user_status == 'N') {
            $status = 'suspend'; // บัญชีถูกระงับ
        } else {
            // บันทึกข้อมูลลงในเซสชัน
            $_SESSION['company_id'] = (int)$user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['tel'] = $user['tel'];

            $role_id = isset($user['role_id']) ? (int)$user['role_id'] : 2;
            $_SESSION['role'] = $role_id;

            // ตรวจสอบบทบาท: ถ้าเป็น Admin (role_id = 1) ไปที่หลังบ้าน
            if ($role_id === 1) {
                header('location: admin/dashboard.php');
            } else {
                header('location: index.php');
            }
            exit();
        }
    }
}

// ตรวจสอบสถานะการแจ้งเตือนจาก URL หรือจากการประมวลผล
if (isset($_GET['status'])) {
    $status = $_GET['status'];
}

$alert_type = '';
$alert_text = '';

if ($status == 'error') {
    $alert_type = 'danger';
    $alert_text = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
} else if ($status == 'suspend') {
    $alert_type = 'warning';
    $alert_text = 'บัญชีผู้ใช้ถูกระงับ กรุณาติดต่อแอดมิน';
} else if ($status == 'login') {
    $alert_type = 'info';
    $alert_text = 'ออกจากระบบเรียบร้อยแล้ว';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - Ecolyn</title>
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
        <div class="d-flex justify-content-center align-items-center vh-100">
            <div class="card card-white col-md-5 p-4 shadow">
                <div class="text-center">
                    <img src="img/Logo1.png" style="width: 200px;" alt="โลโก้">
                    <h3 class="mt-2 fw-bold">เข้าสู่ระบบ</h3>
                    <p class="text-muted small">ระบบติดตามและประเมินผลการลดก๊าซเรือนกระจกในองค์กร</p>
                </div>

                <?php if ($alert_text != '') { ?>
                    <div class="alert alert-<?php echo $alert_type; ?> p-2 text-center mt-3">
                        <?php echo $alert_text; ?>
                    </div>
                <?php } ?>

                <!-- ฟอร์มเข้าสู่ระบบ -->
                <form action="login.php" method="post">
                    <div class="mt-2">
                        <label class="form-label">อีเมล</label>
                        <input type="email" class="form-control" placeholder="กรุณากรอกอีเมล" name="email" required>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">รหัสผ่าน</label>
                        <input type="password" class="form-control" placeholder="กรุณากรอกรหัสผ่าน" name="pass" autocomplete="current-password" required>
                        <p class="text-end small mt-1">
                            <a href="forgot.php" class="text-secondary text-decoration-none">ลืมรหัสผ่าน?</a>
                        </p>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-green w-100 py-2">เข้าสู่ระบบ</button>
                        <p class="text-center text-muted small mt-3">
                            ยังไม่มีบัญชี? <a href="regis.php" class="text-green text-decoration-none fw-bold">สมัครสมาชิก</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ส่วนท้ายเว็บไซต์ -->
    <footer class="text-center border-top text-light p-4 mt-5">
        <small>&copy; <?php echo date('Y'); ?> Ecolyn - ระบบติดตามการลดคาร์บอนในองค์กร โทร. 088888</small>
    </footer>
</body>
</html>
