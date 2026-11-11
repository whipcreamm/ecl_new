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

// เมื่อผู้ดูแลระบบกดยืนยันการส่งใบรับรอง
if (isset($_POST['approve_id'])) {
    $approve_id = (int)$_POST['approve_id'];
    $today_date = date('Y-m-d');
    $status_result = 'error';

    // ค้นหารายการคำขอที่ยังไม่อนุมัติ (cert_status = 'N')
    $sql_find_cert = "SELECT * FROM members WHERE id = $approve_id AND cert_status = 'N'";
    $query_find_cert = mysqli_query($conn, $sql_find_cert);
    $cert_row = mysqli_fetch_assoc($query_find_cert);

    if ($cert_row) {
        // อัปเดตสถานะเป็นอนุมัติแล้ว (cert_status = 'Y') และบันทึกวันที่
        $sql_update_cert = "UPDATE members SET cert_status = 'Y', cert_date = '$today_date' WHERE id = $approve_id";
        mysqli_query($conn, $sql_update_cert);

        // ส่งข้อความแจ้งเตือนไปยังบริษัทสมาชิก
        $target_company_id = (int)$cert_row['company_id'];
        $noti_message = 'ใบรับรองของคุณถูกส่งไปทางอีเมลแล้ว กรุณาตรวจสอบกล่องจดหมายอีเมลของคุณ';
        $escaped_msg = mysqli_real_escape_string($conn, $noti_message);

        $sql_send_noti = "INSERT INTO noti (recip_id, message, type, link, create_at) VALUES ($target_company_id, '$escaped_msg', 'certificate', 'index.php', NOW())";
        mysqli_query($conn, $sql_send_noti);

        $status_result = 'success';
    }

    header('location: certificate_review.php?status=' . $status_result);
    exit();
}

// กำหนดข้อความแจ้งเตือนตาม status ใน URL
$alert_type = '';
$alert_text = '';

if (isset($_GET['status'])) {
    $current_status = $_GET['status'];
    if ($current_status == 'success') {
        $alert_type = 'success';
        $alert_text = 'อนุมัติเรียบร้อยแล้ว ระบบได้ส่งข้อความแจ้งเตือนให้ผู้ใช้ทราบแล้ว';
    } else if ($current_status == 'error') {
        $alert_type = 'warning';
        $alert_text = 'ไม่พบรายการคำขอ หรือรายการนี้ได้รับการอนุมัติไปเรียบร้อยแล้ว';
    }
}

// ดึงข้อมูลบริษัททั้งหมดเก็บไว้ใน array เพื่อนำมาแมปชื่อและอีเมล
$companies_map = array();
$sql_companies = "SELECT * FROM company";
$query_companies = mysqli_query($conn, $sql_companies);

while ($comp = mysqli_fetch_assoc($query_companies)) {
    $comp_id = (int)$comp['id'];
    $companies_map[$comp_id] = $comp;
}

// ดึงรายการคำขอใบรับรองทั้งหมด
$sql_cert_list = "SELECT * FROM members WHERE cert_status IS NOT NULL ORDER BY cert_status ASC, id DESC";
$cert_result = mysqli_query($conn, $sql_cert_list);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตรวจสอบและอนุมัติใบรับรอง - Ecolyn Admin</title>
</head>
<body class="bg-light">
    <!-- แถบเมนูด้านบน -->
    <?php include '../navbar.php'; ?>

    <div class="container">
        <h3 class="fw-bold mt-3 text-center">ตรวจสอบและอนุมัติใบรับรอง</h3>

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
                            <th class="text-start">ชื่อบริษัท/องค์กร</th>
                            <th class="text-start">อีเมล</th>
                            <th>วันที่ขอ/อนุมัติ</th>
                            <th>สถานะ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    while ($c = mysqli_fetch_assoc($cert_result)) { 
                        $member_cid = (int)$c['company_id'];
                        $company_info = isset($companies_map[$member_cid]) ? $companies_map[$member_cid] : null;

                        $comp_name = $company_info ? $company_info['name'] : '-';
                        $comp_email = $company_info ? $company_info['email'] : '-';
                        $is_approved = ($c['cert_status'] == 'Y');
                        $cert_date_display = !empty($c['cert_date']) ? $c['cert_date'] : '-';
                    ?>
                        <tr>
                            <td class="text-start fw-bold"><?php echo htmlspecialchars($comp_name); ?></td>
                            <td class="text-start"><?php echo htmlspecialchars($comp_email); ?></td>
                            <td><?php echo $cert_date_display; ?></td>
                            <td>
                                <?php if ($is_approved) { ?>
                                    <span class="badge bg-success">ส่งใบรับรองแล้ว</span>
                                <?php } else { ?>
                                    <span class="badge bg-warning text-dark">รอส่งใบรับรอง</span>
                                <?php } ?>
                            </td>
                            <td class="text-nowrap">
                                <?php if ($is_approved) { ?>
                                    <button type="button" class="btn btn-sm btn-secondary" disabled>
                                        อนุมัติแล้วเมื่อ <?php echo $cert_date_display; ?>
                                    </button>
                                <?php } else { ?>
                                    <div class="d-flex justify-content-center gap-1">
                                        <a href="https://mail.google.com" target="_blank" class="btn btn-sm btn-outline-primary">เปิด Gmail</a>
                                        <form method="post" action="certificate_review.php" onsubmit="return confirm('ยืนยันว่าส่งใบรับรองทางอีเมลเรียบร้อยแล้วใช่หรือไม่?')">
                                            <input type="hidden" name="approve_id" value="<?php echo (int)$c['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-success">ยืนยันส่งแล้ว</button>
                                        </form>
                                    </div>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                    <?php if (mysqli_num_rows($cert_result) == 0) { ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">ไม่มีรายการขอใบรับรองในขณะนี้</td>
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
