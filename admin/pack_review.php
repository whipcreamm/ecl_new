<?php
// เริ่มต้นเซสชัน
session_start();

// ดึงไฟล์เชื่อมต่อฐานข้อมูล
include '../config.php';

// ตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่ ถ้ายังไม่ล็อกอินให้กลับไปหน้า login
$company_id = 0;
if (isset($_SESSION['company_id'])) {
    $company_id = (int)$_SESSION['company_id'];
}

$sql_company = "SELECT * FROM company WHERE id = $company_id";
$company_query = mysqli_query($conn, $sql_company);
$company = mysqli_fetch_assoc($company_query);

if (!$company || $company['role_id'] != 1) {
    header('location: ../login.php');
    exit();
}

// เมื่อผู้ดูแลระบบกดปุ่มอนุมัติแพ็กเกจ
if (isset($_POST['approve_id'])) {
    $id = (int)$_POST['approve_id'];
    $today = date('Y-m-d');
    $end_date = date('Y-m-d', strtotime('+1 year'));

    // 1. ตรวจสอบกรณีเป็นคำขอเปลี่ยนแพ็กเกจ (newpack_id มีค่า)
    $sql_update_change = "UPDATE members SET pack_id = newpack_id, pay_price = newpay, status_mem = 'Y', date_mem = '$today', end_mem = '$end_date', newpack_id = NULL, newpay = NULL, new_date = NULL WHERE id = $id AND newpack_id IS NOT NULL";
    mysqli_query($conn, $sql_update_change);
    $affected = mysqli_affected_rows($conn);
    $is_success = ($affected > 0);

    // 2. หากไม่ใช่คำขอเปลี่ยนแพ็กเกจ ให้ตรวจคำขอสมัครแพ็กเกจใหม่ (status_mem = 'N')
    if (!$is_success) {
        $sql_update_new = "UPDATE members SET status_mem = 'Y', date_mem = '$today', end_mem = '$end_date' WHERE id = $id AND status_mem = 'N'";
        mysqli_query($conn, $sql_update_new);
        $affected = mysqli_affected_rows($conn);
        $is_success = ($affected > 0);
    }

    if ($is_success) {
        $redirect_status = 'success';
    } else {
        $redirect_status = 'error';
    }

    header('location: pack_review.php?status=' . $redirect_status);
    exit();
}

// จัดการข้อความแจ้งเตือนตาม status ใน URL
$alert_type = '';
$alert_text = '';

if (isset($_GET['status'])) {
    $status_param = $_GET['status'];
    if ($status_param == 'success') {
        $alert_type = 'success';
        $alert_text = 'อนุมัติแพ็กเกจเรียบร้อยแล้ว สมาชิกสามารถใช้งานระบบได้ทันที';
    } else if ($status_param == 'error') {
        $alert_type = 'warning';
        $alert_text = 'ไม่พบรายการคำขอ หรือรายการนี้ได้รับการอนุมัติไปแล้ว';
    }
}

// ดึงข้อมูลบริษัทและข้อมูลแพ็กเกจทั้งหมดเก็บไว้ใน array
$companies_map = array();
$sql_all_com = "SELECT * FROM company";
$query_all_com = mysqli_query($conn, $sql_all_com);
while ($c_item = mysqli_fetch_assoc($query_all_com)) {
    $c_id = (int)$c_item['id'];
    $companies_map[$c_id] = $c_item;
}

$packs_map = array();
$sql_all_packs = "SELECT * FROM pack";
$query_all_packs = mysqli_query($conn, $sql_all_packs);
while ($pk_item = mysqli_fetch_assoc($query_all_packs)) {
    $pk_id = (int)$pk_item['id'];
    $packs_map[$pk_id] = $pk_item;
}

// ดึงข้อมูลรายการสมาชิกทั้งหมด โดยนำรายการที่รออนุมัติขึ้นก่อน
$sql_members_list = "SELECT * FROM members ORDER BY (status_mem = 'N' OR newpack_id IS NOT NULL) DESC, id DESC";
$member_result = mysqli_query($conn, $sql_members_list);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตรวจสอบและอนุมัติแพ็กเกจ - Ecolyn Admin</title>
</head>
<body class="bg-light">
    <!-- แถบเมนูด้านบน -->
    <?php include '../navbar.php'; ?>

    <div class="container">
        <h3 class="fw-bold mt-3 text-center">ตรวจสอบและอนุมัติแพ็กเกจ</h3>

        <?php if ($alert_text != '') { ?>
            <div class="alert alert-<?php echo $alert_type; ?> p-2 text-center mt-3">
                <?php echo $alert_text; ?>
            </div>
        <?php } ?>

        <div class="card shadow-sm p-3 mt-3">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle text-center mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="text-start">ชื่อบริษัท</th>
                            <th class="text-start">อีเมล</th>
                            <th>แพ็กเกจ</th>
                            <th>ราคา</th>
                            <th>วันที่สมัคร/อนุมัติ</th>
                            <th>สถานะ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    while ($m = mysqli_fetch_assoc($member_result)) { 
                        $member_cid = (int)$m['company_id'];
                        $co = isset($companies_map[$member_cid]) ? $companies_map[$member_cid] : null;

                        $current_pk_id = (int)$m['pack_id'];
                        $new_pk_id = !empty($m['newpack_id']) ? (int)$m['newpack_id'] : 0;
                        $is_change_request = ($new_pk_id > 0);

                        $pk = isset($packs_map[$current_pk_id]) ? $packs_map[$current_pk_id] : null;
                        $new_pk = isset($packs_map[$new_pk_id]) ? $packs_map[$new_pk_id] : null;

                        // คำนวณราคาที่ต้องแสดง
                        if ($is_change_request) {
                            $display_price = isset($m['newpay']) ? (float)$m['newpay'] : 0;
                        } else {
                            $display_price = $pk ? (float)$pk['price'] : 0;
                        }
                    ?>
                        <tr>
                            <td class="text-start fw-bold"><?php echo htmlspecialchars($co ? $co['name'] : '-'); ?></td>
                            <td class="text-start"><?php echo htmlspecialchars($co ? $co['email'] : '-'); ?></td>
                            <td>
                                <span class="badge bg-info text-dark"><?php echo htmlspecialchars($pk ? $pk['name'] : '-'); ?></span>
                                <?php if ($is_change_request) { ?>
                                    &rarr; <span class="badge bg-warning text-dark"><?php echo htmlspecialchars($new_pk ? $new_pk['name'] : '-'); ?></span>
                                <?php } ?>
                            </td>
                            <td>
                                <b class="text-success"><?php echo number_format($display_price); ?> บาท</b>
                                <?php if ($is_change_request) { ?>
                                    <br><small class="text-muted">(แพ็กใหม่)</small>
                                <?php } ?>
                            </td>
                            <td><?php echo !empty($m['date_mem']) ? $m['date_mem'] : '-'; ?></td>
                            <td>
                                <?php if ($is_change_request) { ?>
                                    <span class="badge bg-warning text-dark">ขอเปลี่ยนแพ็ค</span>
                                <?php } else if ($m['status_mem'] == 'Y') { ?>
                                    <span class="badge bg-success">อนุมัติแล้ว</span>
                                <?php } else { ?>
                                    <span class="badge bg-warning text-dark">รออนุมัติ</span>
                                <?php } ?>
                            </td>
                            <td class="text-nowrap">
                                <?php if (!$is_change_request && $m['status_mem'] == 'Y') { ?>
                                    <button type="button" class="btn btn-sm btn-secondary" disabled>
                                        ใช้งานได้ถึง <?php echo $m['end_mem']; ?>
                                    </button>
                                <?php } else { ?>
                                    <form method="post" action="pack_review.php" onsubmit="return confirm('ยืนยันการอนุมัติแพ็กเกจนี้ใช่หรือไม่?')">
                                        <input type="hidden" name="approve_id" value="<?php echo (int)$m['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-success">ยืนยันอนุมัติ</button>
                                    </form>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                    <?php if (mysqli_num_rows($member_result) == 0) { ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">ไม่มีรายการคำขอแพ็กเกจในขณะนี้</td>
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
