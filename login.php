<?php   
include 'config.php';
session_start();

$status = '';
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $email = $_POST['email'];
    $pass = md5($_POST['pass']);
    $u = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM company WHERE email='$email' AND pass='$pass'"));
    if(!$u){
        $status = 'error';
    }elseif(($u['status'] ?? 'Y') == 'N'){
        $status = 'suspend';
    }else{
        $_SESSION['company_id'] = (int)$u['id'];
        $_SESSION['name'] = $u['name'];
        $_SESSION['email'] = $u['email'];
        $_SESSION['tel'] = $u['tel'];
        $_SESSION['role'] = (int)($u['role_id'] ?? 2);
        if($_SESSION['role'] === 1){
            header('location: admin/dashboard.php');
        }else{
            header('location: index.php');
        }
        exit();
    }
}
$alerts = [
    'error' => ['danger','อีเมลหรือรหัสผ่านไม่ถูกต้อง'],
    'suspend' => ['warning','บัญชีผู้ใช้ถูกระงับ กรุณาติดต่อแอดมิน'],
    'logout' => ['info','ออกจากระบบเรียบร้อยแล้ว'],
];
$alert = $alerts[$status] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>login</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
<style>
    body{
        background: #08543c;
    }
    .card-white{
        border-radius: 13px;
    }
    .btn-green{
        background: #08543c;
        color:white;
    }
    .btn-green:hover{
        background: #023c2a;
        color:white;
    }
    .text-green{
        color: #08543c;
    }
</style>
</head>  
<body>
    <div class="container">
        <div class="d-flex justify-content-center align-items-center vh-100">
            <div class="card card-white col-md-5 col-md-4 col-md-5 p-3">
                <center>
                    <img src="img/Logo1.png" style="width:200px;">
                    <h3>เข้าสู่ระบบ</h3>
                    <p>ระบบติดตามและประเมินผลการลดก๊าซเรือนกระจกในองค์กร</p>
                </center>
                <?php if($alert): ?>
                    <div class="alert alert-<?= $alert[0] ?> p-2 text-center mt-3"><?= $alert[1] ?></div>
                <?php endif; ?>
                <form action="" method="post">
                    <div class="mt-2">
                        <label class="form-label">อีเมล</label>
                        <input type="email" class="form-control" placeholder="กรุณากรอกอีเมล" name="email" required>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">รหัสผ่าน</label>
                        <input type="password" class="form-control" placeholder="กรุณากรอกรหัสผ่าน" name="pass" autocomplete="new-password" required>
                        <p class="text-end small mt-1"><a href="forgot.php" class="text-secondary text-decoration-none ">ลืมรหัสผ่าน</a></p>
                    </div>
                    <div class="mt-3">
                        <center><button class="btn btn-green btn-green:hover w-100">เข้าสู่ระบบ</button></center>
                        <p class="text-center text-muted small mt-3">ยังไม่มีบัญชี? <a href="regis.php" class="text-green text-decoration-none ">สมัครสมาชิก</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
<footer class="text-center border-top text-light p-4 mt-5">
    <small >&copy; <?= date('Y') ?> Ecolyn - ระบบติดตามการลดคาร์บอนในองค์กร โทร. 088888</small>
</footer>
</body>
</html>