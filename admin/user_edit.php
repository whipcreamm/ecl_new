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

// เมื่อผู้ดูแลระบบดำเนินการจัดการสมาชิก (ระงับ, เปิดใช้งาน, หรือรีเซ็ตรหัสผ่าน)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_id = (int)$_POST['id'];
    $action = $_POST['action'];
    $status_redirect = 'myuser';

    // ป้องกันไม่ให้แอดมินดำเนินการกับบัญชีของตนเอง
    if ($target_id != $cid) {
        $status_redirect = $action;

        if ($action == 'suspend') {
            // ระงับการใช้งานบัญชี (status = 'N')
            $sql_suspend = "UPDATE company SET status = 'N' WHERE id = $target_id";
            mysqli_query($conn, $sql_suspend);

        } else if ($action == 'activate') {
            // เปิดใช้งานบัญชีตามปกติ (status = 'Y')
            $sql_activate = "UPDATE company SET status = 'Y' WHERE id = $target_id";
            mysqli_query($conn, $sql_activate);

        } else if ($action == 'reset') {
            // รีเซ็ตรหัสผ่านเป็น 12345678
            $default_pass_hash = md5('12345678');
            $sql_reset = "UPDATE company SET pass = '$default_pass_hash' WHERE id = $target_id";
            mysqli_query($conn, $sql_reset);

            $noti_msg = 'ผู้ดูแลระบบได้รีเซ็ตรหัสผ่านของคุณเป็น 12345678 แล้ว กรุณาเปลี่ยนรหัสผ่านทันที';
            $escaped_noti = mysqli_real_escape_string($conn, $noti_msg);
            $sql_send_noti = "INSERT INTO noti (recip_id, message, link, create_at) VALUES ($target_id, '$escaped_noti', 'pass.php', NOW())";
            mysqli_query($conn, $sql_send_noti);
        }
    }

    header('location: user_edit.php?status=' . $status_redirect);
    exit();
}

// กำหนดข้อความแจ้งเตือนตาม status ใน URL
$alert_type = '';
$alert_text = '';

if (isset($_GET['status'])) {
    $cur_status = $_GET['status'];
    if ($cur_status == 'suspend') {
        $alert_type = 'success';
        $alert_text = 'ระงับบัญชีผู้ใช้เรียบร้อยแล้ว';
    } else if ($cur_status == 'activate') {
        $alert_type = 'success';
        $alert_text = 'เปิดใช้งานบัญชีผู้ใช้เรียบร้อยแล้ว';
    } else if ($cur_status == 'reset') {
        $alert_type = 'success';
        $alert_text = 'รีเซ็ตรหัสผ่านเป็น 12345678 เรียบร้อยแล้ว';
    } else if ($cur_status == 'myuser') {
        $alert_type = 'danger';
        $alert_text = 'ไม่สามารถดำเนินการกับบัญชีของตัวเองได้';
    }
}

// รับค่าคำค้นหา
$find = '';
if (isset($_GET['find'])) {
    $find = trim($_GET['find']);
} else if (isset($_GET['q'])) {
    $find = trim($_GET['q']);
}

$escaped_kw = mysqli_real_escape_string($conn, $find);
$where_condition = '';
if ($find != '') {
    $where_condition = "WHERE name LIKE '%$escaped_kw%' OR email LIKE '%$escaped_kw%'";
}

// 1. คำนวณแต้มสะสมทั้งหมดของแต่ละบริษัท
$points_map = array();
$sql_points = "SELECT * FROM eco_point_ledger";
$query_points = mysqli_query($conn, $sql_points);
while ($pt = mysqli_fetch_assoc($query_points)) {
    $comp_id = (int)$pt['company_id'];
    if (!isset($points_map[$comp_id])) {
        $points_map[$comp_id] = 0;
    }
    $points_map[$comp_id] = $points_map[$comp_id] + (int)$pt['point'];
}

// 2. คำนวณปริมาณ CO2 ที่ลดได้ทั้งหมดของแต่ละบริษัท
$co2_map = array();
$sql_co2 = "SELECT * FROM utility_logs";
$query_co2 = mysqli_query($conn, $sql_co2);
while ($u = mysqli_fetch_assoc($query_co2)) {
    $comp_id = (int)$u['company_id'];
    if (!isset($co2_map[$comp_id])) {
        $co2_map[$comp_id] = 0;
    }
    $co2_map[$comp_id] = $co2_map[$comp_id] + (float)$u['co2e_save'];
}

// 3. ดึงรายการสมาชิกทั้งหมดตามเงื่อนไขค้นหา
$members_list = array();
$sql_members = "SELECT * FROM company $where_condition";
$query_members = mysqli_query($conn, $sql_members);

while ($m = mysqli_fetch_assoc($query_members)) {
    $mid = (int)$m['id'];
    if (isset($points_map[$mid])) {
        $m['point'] = $points_map[$mid];
    } else {
        $m['point'] = 0;
    }

    if (isset($co2_map[$mid])) {
        $m['co2'] = $co2_map[$mid];
    } else {
        $m['co2'] = 0;
    }

    $members_list[] = $m;
}

// 4. จัดเรียงลำดับ: นำ Admin (role_id = 1) ขึ้นก่อน แล้วตามด้วยแต้มสะสมจากมากไปหาน้อย
$role_col = array();
$point_col = array();

foreach ($members_list as $item) {
    $role_col[] = (int)$item['role_id'];
    $point_col[] = (int)$item['point'];
}

if (count($members_list) > 0) {
    array_multisort($role_col, SORT_ASC, $point_col, SORT_DESC, $members_list);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสมาชิก - Ecolyn Admin</title>
</head>
<body class="bg-light">
    <!-- แถบเมนูด้านบน -->
    <?php include '../navbar.php'; ?>

    <div class="container">
        <h3 class="fw-bold mt-3 text-center">จัดการสมาชิก</h3>

        <?php if ($alert_text != '') { ?>
            <div class="alert alert-<?php echo $alert_type; ?> p-2 text-center mt-3">
                <?php echo $alert_text; ?>
            </div>
        <?php } ?>

        <!-- ฟอร์มค้นหาสมาชิก -->
        <form class="row g-2 mt-2 mb-3" method="get" action="user_edit.php">
            <div class="col-md-8">
                <input name="find" class="form-control" placeholder="ค้นหาชื่อบริษัท หรืออีเมล..." value="<?php echo htmlspecialchars($find); ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-green w-100">ค้นหา</button>
            </div>
            <div class="col-md-2">
                <a href="user_edit.php" class="btn btn-outline-secondary w-100">ล้างค้นหา</a>
            </div>
        </form>

        <div class="card shadow-sm p-3">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle text-center mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="text-start">ชื่อบริษัท</th>
                            <th class="text-start">อีเมล</th>
                            <th>สิทธิ์</th>
                            <th>สถานะ</th>
                            <th>แต้มสะสม</th>
                            <th>ลดคาร์บอน (kgCO₂e)</th>
                            <th style="min-width: 200px;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    foreach ($members_list as $m) { 
                        $is_current_admin = ($m['id'] == $cid);
                        $is_active = ($m['status'] == 'Y');
                        $is_admin_role = ($m['role_id'] == 1);
                    ?>
                        <tr>
                            <td class="text-start fw-bold"><?php echo htmlspecialchars($m['name']); ?></td>
                            <td class="text-start"><?php echo htmlspecialchars($m['email']); ?></td>
                            <td>
                                <?php if ($is_admin_role) { ?>
                                    <span class="badge bg-dark">แอดมิน</span>
                                <?php } else { ?>
                                    <span class="badge bg-primary">สมาชิก</span>
                                <?php } ?>
                            </td>
                            <td>
                                <?php if ($is_active) { ?>
                                    <span class="badge bg-success">ปกติ</span>
                                <?php } else { ?>
                                    <span class="badge bg-danger">ระงับ</span>
                                <?php } ?>
                            </td>
                            <td><?php echo number_format($m['point']); ?></td>
                            <td class="text-success fw-bold"><?php echo number_format($m['co2'], 2); ?></td>
                            <td class="text-nowrap">
                                <?php if ($is_current_admin) { ?>
                                    <button type="button" class="btn btn-sm btn-secondary" disabled>บัญชีปัจจุบัน</button>
                                <?php } else { ?>
                                    <form method="post" action="user_edit.php" class="d-flex justify-content-center gap-1">
                                        <input type="hidden" name="id" value="<?php echo (int)$m['id']; ?>">
                                        <?php if ($is_active) { ?>
                                            <button type="submit" name="action" value="suspend" class="btn btn-sm btn-danger" onclick="return confirm('ยืนยันระงับบัญชีสมาชิกนี้?')">ระงับ</button>
                                        <?php } else { ?>
                                            <button type="submit" name="action" value="activate" class="btn btn-sm btn-success">คืนสิทธิ์</button>
                                        <?php } ?>
                                        <button type="submit" name="action" value="reset" class="btn btn-sm btn-warning text-dark" onclick="return confirm('ยืนยันรีเซ็ตรหัสผ่านเป็น 12345678?')">รีเซ็ตรหัส</button>
                                    </form>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                    <?php if (count($members_list) == 0) { ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">ไม่พบข้อมูลสมาชิกตามที่ค้นหา</td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ส่วนท้ายเว็บไซต์ -->
    <?php include '../footer.php'; ?>
    <script src="<?php echo $base; ?>js/bootstrap.bundle.min.js"></script>
</body>
</html>
