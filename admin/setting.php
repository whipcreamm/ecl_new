<?php
session_start();
include '../config.php';
if (!isset($_SESSION['company_id'])) {
    header('location:../login.php');
    exit();
}
$cid = (int)$_SESSION['company_id'];

$me = mysqli_fetch_assoc(mysqli_query($conn, "select * from company where id=$cid"));
if (!$me || $me['role_id'] != 1) {
    header('location:../login.php');
    exit();
}

// ค่าตั้งต้น: key, ค่าเริ่มต้น, ชื่อที่แสดง, หน่วย
$default_settings = [
    ['baseline_elec',  240,    'เกณฑ์มาตรฐานการใช้ไฟฟ้า (Baseline รายเดือน)',   'kWh/เดือน'],
    ['baseline_water', 15,     'เกณฑ์มาตรฐานการใช้น้ำประปา (Baseline รายเดือน)', 'ลบ.ม./เดือน'],
    ['factor_elec',    0.4999, 'Emission Factor ไฟฟ้า',                         'kgCO₂e/kWh'],
    ['factor_water',   0.7000, 'Emission Factor น้ำประปา',                      'kgCO₂e/ลบ.ม.'],
    ['point_per_kg',   10,     'อัตราแต้มสะสมต่อ 1 kgCO₂e',                     'แต้ม/kgCO₂e']
];
foreach($default_settings as $s){
    $has = mysqli_num_rows(mysqli_query($conn, "select * from eco_setting where setting_key='$s[0]'"));
    if($has == 0){
        mysqli_query($conn, "insert into eco_setting(setting_key,setting_value,label,unit) values ('$s[0]',$s[1],'$s[2]','$s[3]')");
    }
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    foreach($_POST['settings'] as $key => $value){
        $key = mysqli_real_escape_string($conn, $key);
        $value = (float)$value;
        if($value > 0){
            mysqli_query($conn, "update eco_setting set setting_value=$value where setting_key='$key'");
        }
    }
    header('location: setting.php?status=success');
    exit();
}

$setting_result = mysqli_query($conn, "select * from eco_setting order by setting_key");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>setting</title>
</head>
<body class="bg-light">
    <?php include '../navbar.php'; ?>

    <div class="container">
        <h3 class="fw-bold mt-3 text-center">ตั้งค่าระบบคำนวณค่าน้ำ-ไฟรายเดือน</h3>

        <?php if(($_GET['status'] ?? '') == 'success'): ?>
            <div class="alert alert-success p-2 text-center mt-3">บันทึกการตั้งค่าเรียบร้อยแล้ว</div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-7 mt-3">
                <div class="card card-white shadow-sm p-3 h-100">
                    <form method="post">
                        <?php while($r = mysqli_fetch_assoc($setting_result)): ?>
                            <div class="mb-3">
                                <label class="form-label fw-bold"><?= $r['label'] ?></label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" min="0.0001" class="form-control" required
                                           name="settings[<?= $r['setting_key'] ?>]" value="<?= (float)$r['setting_value'] ?>">
                                    <span class="input-group-text"><?= $r['unit'] ?></span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                        <button class="btn btn-green w-100">บันทึกการตั้งค่าทั้งหมด</button>
                    </form>
                </div>
            </div>

            <div class="col-lg-5 mt-3">
                <div class="card card-white shadow-sm p-3 h-100">
                    <h5 class="fw-bold">คำอธิบายเกณฑ์คำนวณรายเดือน</h5>
                    <div class="mt-3 border-top text-muted small pt-3">
                        <p><b>เกณฑ์มาตรฐาน (Baseline รายเดือน):</b><br>เกณฑ์การใช้งานมาตรฐานต่อเดือนสำหรับองค์กร หากใช้น้อยกว่าเกณฑ์นี้ ระบบถือเป็นปริมาณที่ประหยัดได้</p>
                        <p><b>Emission Factor:</b><br>ค่าการปล่อยก๊าซเรือนกระจกต่อหน่วย อ้างอิงมาตรฐาน อบก. (TGO) ใช้แปลงปริมาณที่ประหยัดเป็น kgCO₂e</p>
                        <p class="mb-0"><b>อัตราแต้มสะสม:</b><br>จำนวนแต้มที่องค์กรได้รับต่อ 1 kgCO₂e ที่ลดได้ในแต่ละเดือน</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php include '../footer.php'; ?>
<script src="<?= $base ?>js/bootstrap.bundle.min.js"></script>
</body>
</html>