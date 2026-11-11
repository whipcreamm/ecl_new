<?php
// เริ่มต้นเซสชัน
session_start();

// ดึงไฟล์เชื่อมต่อฐานข้อมูล
include '../config.php';

// ตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่ ถ้ายังไม่ล็อกอินให้กลับไปหน้า login
if (!isset($_SESSION['company_id'])) {
    header('location: ../login.php');
    exit();
}

$cid = (int)$_SESSION['company_id'];

// ตรวจสอบสิทธิ์ผู้ดูแลระบบ (Admin: role_id = 1)
$sql_check_admin = "SELECT * FROM company WHERE id = $cid";
$query_admin = mysqli_query($conn, $sql_check_admin);
$me = mysqli_fetch_assoc($query_admin);

if (!$me || $me['role_id'] != 1) {
    header('location: ../login.php');
    exit();
}

// รายการค่าตั้งต้นระบบ: key, ค่าเริ่มต้น, ชื่อที่แสดง, หน่วย
$default_settings = array(
    array('key' => 'baseline_elec',  'val' => 240,    'label' => 'เกณฑ์มาตรฐานการใช้ไฟฟ้า (Baseline รายเดือน)',   'unit' => 'kWh/เดือน'),
    array('key' => 'baseline_water', 'val' => 15,     'label' => 'เกณฑ์มาตรฐานการใช้น้ำประปา (Baseline รายเดือน)', 'unit' => 'ลบ.ม./เดือน'),
    array('key' => 'factor_elec',    'val' => 0.4999, 'label' => 'Emission Factor ไฟฟ้า',                         'unit' => 'kgCO₂e/kWh'),
    array('key' => 'factor_water',   'val' => 0.7000, 'label' => 'Emission Factor น้ำประปา',                      'unit' => 'kgCO₂e/ลบ.ม.'),
    array('key' => 'point_per_kg',   'val' => 10,     'label' => 'อัตราแต้มสะสมต่อ 1 kgCO₂e',                     'unit' => 'แต้ม/kgCO₂e')
);

// ตรวจสอบและบันทึกค่าเริ่มต้นหากยังไม่มีในฐานข้อมูล
foreach ($default_settings as $setting_item) {
    $s_key = $setting_item['key'];
    $s_val = $setting_item['val'];
    $s_label = $setting_item['label'];
    $s_unit = $setting_item['unit'];

    $sql_check_exist = "SELECT * FROM eco_setting WHERE setting_key = '$s_key'";
    $exist_query = mysqli_query($conn, $sql_check_exist);
    $has_record = mysqli_num_rows($exist_query);

    if ($has_record == 0) {
        $sql_insert_default = "INSERT INTO eco_setting (setting_key, setting_value, label, unit) VALUES ('$s_key', $s_val, '$s_label', '$s_unit')";
        mysqli_query($conn, $sql_insert_default);
    }
}

// เมื่อผู้ดูแลระบบกดปุ่มบันทึกการตั้งค่า
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['settings']) && is_array($_POST['settings'])) {
        foreach ($_POST['settings'] as $key => $value) {
            $escaped_key = mysqli_real_escape_string($conn, $key);
            $numeric_value = (float)$value;

            if ($numeric_value > 0) {
                $sql_update_setting = "UPDATE eco_setting SET setting_value = $numeric_value WHERE setting_key = '$escaped_key'";
                mysqli_query($conn, $sql_update_setting);
            }
        }
    }
    header('location: setting.php?status=success');
    exit();
}

// ดึงรายการตั้งค่าทั้งหมดมาแสดงในฟอร์ม
$sql_all_settings = "SELECT * FROM eco_setting ORDER BY setting_key ASC";
$setting_result = mysqli_query($conn, $sql_all_settings);

$is_save_success = (isset($_GET['status']) && $_GET['status'] == 'success');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่าระบบ - Ecolyn Admin</title>
</head>
<body class="bg-light">
    <!-- แถบเมนูด้านบน -->
    <?php include '../navbar.php'; ?>

    <div class="container">
        <h3 class="fw-bold mt-3 text-center">ตั้งค่าระบบคำนวณค่าน้ำ-ไฟรายเดือน</h3>

        <?php if ($is_save_success) { ?>
            <div class="alert alert-success p-2 text-center mt-3">
                บันทึกการตั้งค่าเรียบร้อยแล้ว
            </div>
        <?php } ?>

        <div class="row">
            <!-- ส่วนฟอร์มแก้ไขค่าระบบ -->
            <div class="col-lg-7 mt-3">
                <div class="card card-white shadow-sm p-4 h-100">
                    <form method="post" action="setting.php">
                        <?php while ($r = mysqli_fetch_assoc($setting_result)) { ?>
                            <div class="mb-3">
                                <label class="form-label fw-bold"><?php echo htmlspecialchars($r['label']); ?></label>
                                <div class="input-group">
                                    <input type="number" step="0.0001" min="0.0001" class="form-control" required name="settings[<?php echo htmlspecialchars($r['setting_key']); ?>]" value="<?php echo (float)$r['setting_value']; ?>">
                                    <span class="input-group-text"><?php echo htmlspecialchars($r['unit']); ?></span>
                                </div>
                            </div>
                        <?php } ?>
                        <button type="submit" class="btn btn-green w-100 py-2 mt-2">บันทึกการตั้งค่าทั้งหมด</button>
                    </form>
                </div>
            </div>

            <!-- ส่วนคำอธิบายเกณฑ์ต่าง ๆ -->
            <div class="col-lg-5 mt-3">
                <div class="card card-white shadow-sm p-4 h-100">
                    <h5 class="fw-bold">คำอธิบายเกณฑ์คำนวณรายเดือน</h5>
                    <div class="mt-3 border-top text-muted small pt-3">
                        <p>
                            <b>เกณฑ์มาตรฐาน (Baseline รายเดือน):</b><br>
                            เกณฑ์การใช้งานมาตรฐานต่อเดือนสำหรับองค์กร หากใช้น้อยกว่าเกณฑ์นี้ ระบบถือเป็นปริมาณที่ประหยัดได้
                        </p>
                        <p>
                            <b>Emission Factor:</b><br>
                            ค่าการปล่อยก๊าซเรือนกระจกต่อหน่วย อ้างอิงตามมาตรฐานของ อบก. (องค์การบริหารจัดการก๊าซเรือนกระจก - TGO) เพื่อใช้แปลงปริมาณพลังงานที่ประหยัดได้เป็นน้ำหนักก๊าซเรือนกระจก (kgCO₂e)
                        </p>
                        <p class="mb-0">
                            <b>อัตราแต้มสะสม:</b><br>
                            จำนวนแต้มที่องค์กรสมาชิกจะได้รับตอบแทนต่อการลดคาร์บอน 1 kgCO₂e ในแต่ละเดือน
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ส่วนท้ายเว็บไซต์ -->
    <?php include '../footer.php'; ?>
    <script src="<?php echo $base; ?>js/bootstrap.bundle.min.js"></script>
</body>
</html>
