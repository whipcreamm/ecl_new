<?php 
    session_start();
    include 'config.php';

    $status = '';
    if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $name = $_POST['name'];
    $email = $_POST['email'];
    $pass = $_POST['pass'];
    $pass2 = $_POST['pass2'];
    $tel = $_POST['tel'];
    if($pass !== $pass2){
        $status = 'notmatch';
    }elseif(strlen($pass) < 8){
        $status = 'short';        
    }elseif(mysqli_num_rows(mysqli_query($conn, "select * from company where email='$email'")) > 0){
        $status ='error';
    }else{
        $h_pass = md5($pass);
        mysqli_query($conn, "insert into company(email,pass,name,tel) values ('$email','$h_pass','$name','$tel')");
        $status ='success';
    }
}
$alerts = [
    'short' => ['danger','รหัสผ่านต้องมีความยาวอย่างน้อย 8 อักษร'],
    'notmatch' => ['danger','รหัสผ่านไม่ตรงกัน'],
    'error' => ['danger','อีเมลนี้มีในระบบอยู่แล้ว'],
];
$alert = $alerts[$status] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            <div class="card card-white col-md-5 col-lg-5 col-sm-6 p-4" >
                <center>
                    <img src="img/Logo1.png" style="width:200px;">
                    <h3>สมัครสมาชิก</h3>
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
                        <input type="password" class="form-control" placeholder="กรุณากรอกรหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร" name="pass" autocomplete="new-password" required>
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
                        <center><button class="btn btn-green w-100">เข้าสู่ระบบ</button></center>
                        <p class="text-center text-muted small mt-3">ยังไม่มีบัญชี <a href="login.php" class="text-green text-decoration-none ">เข้าสู่ระบบ</a></p>
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